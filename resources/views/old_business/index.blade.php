@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Old Business List</h5>

    <div class="clear"></div>
</div>
<x-flash />
<div class="card mt-2">
    <div class="card-header">
        <div class="clear"></div>
    </div>
    <div id="tableExample">
        <div class="table-responsive scrollbar">
            <table class="table mb-0 data-table fs-10 tableToExport">
                <thead class="bg-200">
                    <tr>
                        <th class="text-900 sort text-nowrap">Business name</th>
                        <th class="text-900 sort text-nowrap">Email</th>
                        <th class="text-900 sort text-nowrap">Business URL</th>
                        <th class="text-900 sort text-nowrap">Category name</th>
                        <th class="text-900 sort text-nowrap">Approved</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody id="business_filter_data">
                    @if (count($old_businesses) > 0)
                    @foreach ($old_businesses as $value)
                    <tr>
                        <td class="text-nowrap">{{ $value->caption }}</td>
                        <td class="text-nowrap">{{ $value->mail }}</td>
                        <td class="text-nowrap">{{ $value->url }}</td>
                        <td class="text-nowrap">{{ $value->category_name }}</td>
                        <td class="text-nowrap">{!! status_badge($value->approved) !!}</td>
                        <td class="text-nowrap">
                            <a class="badge bg-info rounded rounded-circle"
                                style="padding-top: 7px; padding-bottom: 7px;"
                                href="{{ route('edit_old_business', Crypt::encrypt($value->old_business_id)) }}"
                                title="Edit"><i class="bi bi-pencil fs-9"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="10" class="text-center"><?php echo no_record_found_in_table(); ?></td>
                    </tr>
                    @endif
                </tbody>
            </table>
            <div class="text-center justify-content-center filter-loader" style="display: none">
                <div class="spinner-border text-danger" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <div class="pagination-wrapper">
                {{ $old_businesses->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@endsection