<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class UserController extends Controller
{
    public function users()
    {
        $users = User::leftjoin('businesses', 'users.id', '=', 'businesses.user_id')
            ->select('users.*', 'businesses.business_website', 'businesses.is_claimed')
            ->orderBy('users.id', 'desc')->paginate(50);
        return view('users.index', compact('users'));
    }

    public function users_status_update(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        return response()->json([
            'status' => $user->status,
            'badge' => users_status($user->status),
        ]);
    }

    public function user_list_filter(Request $request)
    {
        $currentPage = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $query = User::leftjoin('businesses', 'users.id', '=', 'businesses.user_id')
            ->select('users.*', 'businesses.business_website', 'businesses.is_claimed');

        if ($request->filled('email')) {
            $query->where('users.email', $request->email);
        }

        if ($request->filled('status')) {
            $query->where('users.status', $request->status);
        }

        $users = $query->orderBy('users.id', 'desc')->paginate(50);

        $html = '';

        if ($users->count()) {
            foreach ($users as $row) {
                $html .= '<tr>';
                $html .= '<td>' . e($row->first_name . ' ' . $row->last_name) . '</td>';
                $html .= '<td>' . e($row->email) . '</td>';
                $html .= '<td>
                            <a href="' . e($row->business_website) . '" target="_blank" rel="noopener noreferrer">
                                ' . e($row->business_website) . '
                            </a>
                        </td>';
                $html .= '<td>' . date('d-m-Y', strtotime($row->created_at)) . '</td>';
                $html .= '<td>' . date('d-m-Y H:i:s', strtotime($row->last_login_at)) . '</td>';
                $html .= '<td>' . status_badge($row->is_claimed) . '</td>';

                $html .= '<td class="text-nowrap">
                    <span class="user-status-toggle" data-id="' . $row->id . '" style="cursor:pointer"> ' . users_status($row->status) . ' </span>
                </td>';

                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>
            <td colspan="7" class="text-center">' . no_record_found_in_table() . '</td>
        </tr>';
        }

        $pagination = $users
            ->appends(array_merge($request->all(), ['page' => $currentPage]))
            ->links('pagination::bootstrap-5')
            ->render();

        return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => $currentPage]);
    }
}
