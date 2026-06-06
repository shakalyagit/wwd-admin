<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Request;

class SubscriptionController extends Controller
{
    public function subscriptions()
    {
        $subscriptions = DB::table('subscriptions')->leftjoin('businesses', 'subscriptions.business_id', '=', 'businesses.business_id')
            ->select('subscriptions.*', 'businesses.business_name')
            ->paginate(50);
        return view('subscriptions.index', compact('subscriptions'));
    }

    public function view_transactions($id)
    {
        $subscription_id = Crypt::decrypt($id);

        $subscription = DB::table('subscriptions')
            ->leftJoin('businesses', 'subscriptions.business_id', '=', 'businesses.business_id')
            ->where('subscriptions.subscription_id', $subscription_id)
            ->select('subscriptions.*', 'businesses.business_name')
            ->first();

        abort_if(!$subscription, 404);

        // All transactions for this subscription
        $transactions = DB::table('transactions')
            ->where('paypal_subscription_id', $subscription->paypal_subscription_id)
            ->orderBy('payment_date', 'desc')
            ->get();

        // Total paid amount (ONLY successful payments)
        $total_amount = DB::table('transactions')
            ->where('paypal_subscription_id', $subscription->paypal_subscription_id)
            ->where('status', 'completed')
            ->sum('amount');

        return view('subscriptions.view', compact(
            'subscription',
            'transactions',
            'total_amount'
        ));
    }

    public function subscriptions_filter(Request $request)
    {
        $query = DB::table('subscriptions')
            ->leftJoin('businesses', 'subscriptions.business_id', '=', 'businesses.business_id')
            ->select(
                'subscriptions.*',
                'businesses.business_name',
                'businesses.business_website'
            );

        // Filters
        if ($request->from_date) {
            $query->whereDate('subscriptions.subscription_start_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('subscriptions.subscription_end_date', '<=', $request->to_date);
        }

        if ($request->business_name) {
            $query->where('businesses.business_name', 'LIKE', '%' . $request->business_name . '%');
        }

        if ($request->business_website) {
            $query->where('businesses.business_website', 'LIKE', '%' . $request->business_website . '%');
        }

        if ($request->status) {
            $query->where('subscriptions.status', $request->status);
        }

        $subscriptions = $query->orderBy('subscriptions.subscription_id', 'desc')->get();

        // Build HTML manually
        $html = '';

        if ($subscriptions->count()) {
            foreach ($subscriptions as $value) {
                $html .= '<tr>';
                $html .= '<td>' . date('d-m-Y', strtotime($value->subscription_start_date)) . '</td>';
                $html .= '<td>' . e($value->business_name) . '</td>';
                $html .= '<td>' . e($value->subscriber_id) . '</td>';
                $html .= '<td>' . subscription_status($value->status) . '</td>';
                $html .= '<td>
                        <a class="badge bg-info rounded-circle"
                           style="padding-top: 7px; padding-bottom: 7px;"
                           href="' . route('view_transactions', Crypt::encrypt($value->subscription_id)) . '">
                            <i class="bi bi-eye fs-9"></i>
                        </a>
                      </td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>
                    <td colspan="5" class="text-center">No records found</td>
                  </tr>';
        }

        return response()->json([
            'html' => $html
        ]);
    }
}
