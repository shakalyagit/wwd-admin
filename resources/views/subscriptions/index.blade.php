@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Subscription List</h5>
    <div class="clear"></div>
</div>
<x-flash />
<div class="card mt-2">
    <div class="card-header">
        <div class="ms-auto pull-left">
            <a href="{{ route('subscriptions') }}" title="Refresh" style="color: #5E6E82;"><span
                    class="bi bi-arrow-clockwise fs-6 cursor-pointer"></span>
            </a>
            <span class="bi bi-funnel fs-6 cursor-pointer" title="Filter" data-bs-toggle="offcanvas"
                data-bs-target="#filterOffcanvas"></span>
        </div>
        <div class="clear"></div>
    </div>
    <div id="tableExample">
        <div class="table-responsive scrollbar">
            <table class="table mb-0 data-table fs-10 tableToExport">
                <thead class="bg-200">
                    <tr>
                        <th class="text-900 sort text-nowrap">Subscription date</th>
                        <th class="text-900 sort text-nowrap">Business name</th>
                        <th class="text-900 sort text-nowrap">Buyer email</th>
                        <th class="text-900 sort text-nowrap">Status</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody id="subscription_filter_data">
                    @if (count($subscriptions) > 0)
                    @foreach ($subscriptions as $value)
                    <tr>
                        <td class="text-nowrap">{{ date('d-m-Y', strtotime($value->subscription_start_date)) }}</td>
                        <td class="text-nowrap">{{ $value->business_name }}</td>
                        <td class="text-nowrap">{{ $value->subscriber_id }}</td>
                        <td class="text-nowrap">{!! subscription_status($value->status) !!}</td>
                        <td class="text-nowrap">
                            <a class="badge bg-info rounded rounded-circle"
                                style="padding-top: 7px; padding-bottom: 7px;"
                                href="{{ route('view_transactions', Crypt::encrypt($value->subscription_id)) }}"
                                title="View"><i class="bi bi-eye fs-9"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="7" class="text-center"><?php echo no_record_found_in_table(); ?></td>
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
                {{ $subscriptions->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@include('subscriptions.off_canvas')
@endsection
@section('scripts')
<script>
    $('#subscription_filter').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('subscriptions_filter') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                $('#subscription_filter_data').html(res.html);
                var off_canvas_element = document.getElementById('filterOffcanvas');
                var off_canvas_instance = bootstrap.Offcanvas.getInstance(off_canvas_element);
                off_canvas_instance.hide();
            }
        });
    });
</script>
@endsection