<?php

namespace App\Http\Controllers;

use App\Models\BusinessReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReviewController extends Controller
{
    // All customer reviews (business_reviews, written on the Listing site) with headline stats. Read-only:
    // the Listing site keeps businesses.avg_rating / total_reviews in step with this table.
    public function reviews(Request $request)
    {
        // rating is ENUM('0'..'5'): CONCAT gives its value (a plain cast would give the ENUM position)
        $stars = 'CAST(CONCAT(business_reviews.rating) AS UNSIGNED)';
        $no_reply = "COALESCE(business_reviews.review_reply_message, '') = ''";
        $today = now()->toDateString();
        $since = now()->subDays(30);

        $stats = BusinessReview::selectRaw(
            "COUNT(*) as total,
            COUNT(DISTINCT business_reviews.business_id) as businesses,
            SUM(DATE(business_reviews.created_at) = ?) as today,
            SUM(DATE(business_reviews.created_at) = ?) as yesterday,
            SUM(business_reviews.created_at >= ?) as new_30,
            SUM(business_reviews.created_at >= ?) as this_month,
            ROUND(AVG(CASE WHEN $stars > 0 THEN $stars END), 1) as average,
            SUM($no_reply) as waiting,
            SUM($stars BETWEEN 1 AND 2) as low,
            SUM($stars BETWEEN 1 AND 2 AND business_reviews.created_at >= ?) as low_30,
            SUM(business_reviews.status = '0') as hidden",
            [$today, now()->subDay()->toDateString(), $since, now()->startOfMonth(), $since]
        )->first();

        $by_stars = BusinessReview::selectRaw("$stars as stars, COUNT(*) as total")
            ->groupBy('stars')
            ->pluck('total', 'stars');

        $top_businesses = BusinessReview::leftjoin('businesses', 'businesses.business_id', '=', 'business_reviews.business_id')
            ->selectRaw("business_reviews.business_id, businesses.business_name, COUNT(*) as total, ROUND(AVG(CASE WHEN $stars > 0 THEN $stars END), 1) as average")
            ->groupBy('business_reviews.business_id', 'businesses.business_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $tab = in_array($request->tab, ['waiting', 'replied', 'low', 'hidden']) ? $request->tab : 'all';
        $has_source = Schema::hasColumn('business_reviews', 'source');

        $reviews = BusinessReview::leftjoin('businesses', 'businesses.business_id', '=', 'business_reviews.business_id')
            ->leftjoin('users', 'users.id', '=', 'business_reviews.user_id')
            ->leftjoin('categories', 'categories.category_id', '=', 'business_reviews.category_id')
            ->select('business_reviews.*', 'businesses.business_name', 'categories.cat_name as category_name',
                'users.first_name as reviewer_first_name', 'users.last_name as reviewer_last_name', 'users.email as reviewer_email')
            ->selectRaw("$stars as stars")
            ->when($tab === 'waiting', fn($q) => $q->whereRaw($no_reply))
            ->when($tab === 'replied', fn($q) => $q->whereRaw("NOT ($no_reply)"))
            ->when($tab === 'low', fn($q) => $q->whereRaw("$stars BETWEEN 1 AND 2"))
            ->when($tab === 'hidden', fn($q) => $q->where('business_reviews.status', '0'))
            ->when($request->filled('rating') && ctype_digit((string) $request->rating), fn($q) => $q->whereRaw("$stars = ?", [(int) $request->rating]))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('business_reviews.created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('business_reviews.created_at', '<=', $request->to_date))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('businesses.business_name', 'like', $term)
                        ->orWhere('business_reviews.description', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhereRaw("CONCAT_WS(' ', users.first_name, users.last_name) like ?", [$term]);
                });
            })
            ->orderByDesc('business_reviews.created_at')
            ->orderByDesc('business_reviews.id')
            ->paginate(50)
            ->withQueryString();

        $has_filters = $request->filled('q') || $request->filled('rating') || $request->filled('from_date') || $request->filled('to_date');

        return view('reviews.index', compact('stats', 'by_stars', 'top_businesses', 'reviews', 'tab', 'has_source', 'has_filters'));
    }
}
