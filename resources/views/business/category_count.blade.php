@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Category Count</h5>
    <div class="clear"></div>
</div>

<div class="card mt-2">
    <div class="card-header">
        <form action="{{ route('category_count') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search categories..." value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> Search
                </button>
                @if($search)
                    <a href="{{ route('category_count') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div id="tableExample">
        <div class="table-responsive scrollbar">
            <table class="table mb-0 data-table fs-10">
                <thead class="bg-200">
                    <tr>
                        <th class="text-900 sort text-nowrap">Category</th>
                        <th class="text-900 sort text-nowrap">Admin Approved Count</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($children) > 0)
                        @foreach ($parents as $parent)
                            @foreach ($children->where('parent_cat_id', $parent->category_id) as $child)
                            <tr>
                                <td class="text-nowrap fw-bold">
                                    @if($child->approved_businesses_count > 0)
                                        <a href="{{ route('category_businesses', $child->category_id) }}" class="text-decoration-none">
                                            {{ $parent->cat_name }} &raquo; {{ $child->cat_name }}
                                        </a>
                                    @else
                                        <span class="text-secondary">{{ $parent->cat_name }} &raquo; {{ $child->cat_name }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge {{ $child->approved_businesses_count > 0 ? 'bg-success' : 'bg-secondary' }} rounded-pill fs-10">
                                        {{ $child->approved_businesses_count }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">No categories found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
