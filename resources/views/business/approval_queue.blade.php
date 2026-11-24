@extends('layouts.main')
@section('content')
@php $ready_count = $businesses->total(); @endphp
<div class="content-header">
    <div>
        <h5>Businesses</h5>
        <p class="page-subtitle">Ready for approval: owner verified the website and wrote a business description, waiting for your decision. Oldest first.</p>
    </div>
    <a href="{{ route('business_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add business</a>
</div>
<x-flash />
@include('business.partials.tabs')

<div class="card">
    @if ($businesses->count())
    <div class="table-responsive">
        <table class="table mb-0 approval-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Category</th>
                    <th>Waiting</th>
                    <th>Profile</th>
                    <th class="text-end">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($businesses as $business)
                @php
                    $owner = $owners->get($business->user_id);
                    $profile = business_profile_score($business, (bool) $business->logo_path, (bool) $business->has_hours, (bool) $business->has_address);
                    $waiting_days = \Carbon\Carbon::parse($business->created_at)->startOfDay()->diffInDays(now()->startOfDay());
                @endphp
                <tr>
                    <td>
                        {!! business_logo_thumb($business->business_name, $business->logo_path) !!}
                        @unless ($business->logo_path)
                            <span class="badge bg-warning-subtle text-warning-emphasis ms-1" title="No logo yet: the public page shows the business's initials. The approved email asks the owner to add one.">No logo</span>
                        @endunless
                        <div class="approval-meta">
                            @if ($business->business_website)
                            <a href="{{ $business->business_website }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($business->business_website, '/')) }} <i class="bi bi-box-arrow-up-right"></i></a>
                            @endif
                            <span>{{ $owner ? trim($owner->first_name . ' ' . $owner->last_name) : 'Owner' }} · {{ $business->business_email }}</span>
                        </div>
                    </td>
                    <td>{{ $business->category_name ?? '–' }}</td>
                    <td style="white-space: nowrap;">
                        <span class="waiting {{ $waiting_days >= 7 ? 'is-late' : '' }}">{{ $waiting_days === 0 ? 'Today' : $waiting_days . ' ' . Str::plural('day', $waiting_days) }}</span>
                        <div class="user-sub">Submitted {{ date('d M Y', strtotime($business->created_at)) }}</div>
                    </td>
                    <td>{!! profile_score_badge($profile) !!}</td>
                    <td class="text-end" style="white-space: nowrap;">
                        <a class="btn btn-light btn-sm" href="{{ route('edit_business', encrypt($business->business_id)) }}" title="Open full details"><i class="bi bi-pencil"></i> Review</a>
                        <form method="POST" action="{{ route('business_approve', $business->business_id) }}" class="d-inline"
                            onsubmit="return confirm('Approve {{ addslashes(trim($business->business_name)) }}? The owner will get the Business approved email.');">
                            @csrf
                            <input type="hidden" name="return" value="queue">
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal"
                            data-reject-url="{{ route('business_reject', $business->business_id) }}"
                            data-business-name="{{ trim($business->business_name) }}" data-business-email="{{ $business->business_email }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $businesses->links('pagination::bootstrap-5') }}
    </div>
    @else
    <div class="text-center py-5 px-3">
        <div class="kpi-icon green mx-auto mb-3" style="width: 48px; height: 48px; font-size: 22px;"><i class="bi bi-check2-all"></i></div>
        <h5 class="mb-1">All caught up</h5>
        <p class="text-600 mb-0">No businesses are waiting for approval. A business appears here once its owner has verified the website and written a business description.</p>
    </div>
    @endif
</div>

@include('business.partials.reject_modal', ['return' => 'queue', 'business' => null])
@endsection
