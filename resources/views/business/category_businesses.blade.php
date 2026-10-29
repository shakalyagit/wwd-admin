@extends('layouts.main')
@section('content')
@php
    $tabs = [
        'all'      => ['All', $status_counts->total],
        'approved' => ['Approved', $status_counts->approved],
        'pending'  => ['Pending', $status_counts->pending],
        'rejected' => ['Rejected', $status_counts->rejected],
    ];
    $scope_query = $direct_only ? ['scope' => 'direct'] : [];
    $show_category_column = $is_main && !$direct_only;
@endphp
<div class="content-header">
    <div>
        <div class="page-breadcrumb">
            <a href="{{ route('category_count') }}"><i class="bi bi-arrow-left"></i> Categories</a>
            @if ($category->parent_cat_name)
            <i class="bi bi-chevron-right" style="font-size: 10px;"></i>
            <a href="{{ route('category_businesses', $category->parent_cat_id) }}">{{ $category->parent_cat_name }}</a>
            @endif
        </div>
        <h5>{{ $category->cat_name }}</h5>
        <p class="page-subtitle">
            @if ($direct_only)
            Businesses filed under this main category only, without a sub-category.
            @elseif ($is_main)
            All businesses in this category and its sub-categories.
            @else
            Sub-category of {{ $category->parent_cat_name }}.
            @endif
        </p>
    </div>
    @if ($is_main)
    <div class="btn-group">
        <a href="{{ route('category_businesses', $category->category_id) }}" class="btn btn-sm {{ $direct_only ? 'btn-light' : 'btn-primary' }}">Including sub-categories</a>
        <a href="{{ route('category_businesses', $category->category_id) }}?scope=direct" class="btn btn-sm {{ $direct_only ? 'btn-primary' : 'btn-light' }}">Main category only</a>
    </div>
    @endif
</div>
<x-flash />

<ul class="nav nav-tabs status-tabs">
    @foreach ($tabs as $key => [$label, $count])
    <li class="nav-item">
        <a class="nav-link {{ $status === $key ? 'active' : '' }}"
            href="{{ route('category_businesses', $category->category_id) }}?{{ http_build_query($scope_query + ($key === 'all' ? [] : ['status' => $key])) }}">
            {{ $label }}<span class="count">{{ (int) $count }}</span>
        </a>
    </li>
    @endforeach
</ul>

<div class="card mt-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Business</th>
                    @if ($show_category_column)
                    <th>Sub-category</th>
                    @endif
                    <th>Submitted</th>
                    <th>Owner verified</th>
                    <th>Admin status</th>
                    <th class="noExl">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($businesses as $value)
                <tr>
                    <td class="text-nowrap">
                        {!! business_logo_thumb($value->business_name, $value->logo_path) !!}
                        @if ($value->business_website)
                        <a href="{{ $value->business_website }}" target="_blank" rel="noopener" class="small text-600 d-inline-block mt-1" style="margin-left: 52px; font-weight: 400;">
                            {{ preg_replace('#^https?://(www\.)?#', '', rtrim($value->business_website, '/')) }} <i class="bi bi-box-arrow-up-right" style="font-size: 10px;"></i>
                        </a>
                        @endif
                    </td>
                    @if ($show_category_column)
                    <td class="text-nowrap">{{ $value->category_name == $category->cat_name ? '—' : $value->category_name }}</td>
                    @endif
                    <td>{{ date('d-m-Y', strtotime($value->created_at)) }}</td>
                    <td>{!! status_badge($value->is_claimed) !!}</td>
                    <td>{!! status_badge($value->is_admin_verified) !!}</td>
                    <td>
                        <a class="badge bg-info rounded rounded-circle"
                            href="{{ route('edit_business', Crypt::encrypt($value->business_id)) }}"
                            title="Edit business"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $show_category_column ? 6 : 5 }}" class="text-center py-5 text-600">
                        <i class="bi bi-inbox d-block fs-6 mb-2"></i>
                        No {{ $status === 'all' ? '' : $status . ' ' }}businesses in this category yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $businesses->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
