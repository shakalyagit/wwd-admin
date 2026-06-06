@extends('layouts.main')
@section('content')
<div class="content-header mb-3">
    <a href="{{ route('category_count') }}" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="bi bi-arrow-left"></i> Back to Category Count
    </a>
    <h4>Approved Businesses under "{{ $category->cat_name }}"</h4>
    @if($category->parent_cat_name)
        <span class="text-muted">Parent Category: <strong>{{ $category->parent_cat_name }}</strong></span>
    @endif
</div>

<div class="card mt-2">
    <div id="tableExample">
        <div class="table-responsive scrollbar">
            <table class="table mb-0 data-table fs-10">
                <thead class="bg-200">
                    <tr>
                        <th class="text-900 sort text-nowrap">Submission Date</th>
                        <th class="text-900 sort text-nowrap">Business Name</th>
                        <th class="text-900 sort text-nowrap">Website</th>
                        <th class="text-900 sort text-nowrap">Email</th>
                        <th class="text-900 sort text-nowrap">Phone</th>
                        <th class="text-900 sort text-nowrap">Business Claimed</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($businesses) > 0)
                        @foreach ($businesses as $value)
                        <tr>
                            <td class="text-nowrap">{{ date('d-m-Y', strtotime($value->created_at)) }}</td>
                            <td class="text-nowrap fw-bold">{{ $value->business_name }}</td>
                            <td class="text-nowrap">
                                @if($value->business_website)
                                    <a href="{{ $value->business_website }}" target="_blank" class="text-decoration-none">
                                        {{ $value->business_website }} <i class="bi bi-box-arrow-up-right fs-11"></i>
                                    </a>
                                @else
                                    <span class="text-400">-</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $value->business_email ?? '-' }}</td>
                            <td class="text-nowrap">{{ $value->business_phone ?? '-' }}</td>
                            <td class="text-nowrap">{!! status_badge($value->is_claimed) !!}</td>
                            <td class="text-nowrap">
                                <a class="badge bg-info rounded rounded-circle"
                                   style="padding-top: 7px; padding-bottom: 7px;"
                                   href="{{ route('edit_business', Crypt::encrypt($value->business_id)) }}"
                                   title="Edit business"><i class="bi bi-pencil fs-9"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No approved businesses found in this category.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="pagination-wrapper mt-3 px-3">
                {{ $businesses->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
