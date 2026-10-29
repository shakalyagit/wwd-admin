@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Notifications</h5>
    <div class="clear"></div>
</div>
<x-flash />
@include('notifications.partials.tabs')
<div class="card mt-2">
    <div class="card-header">
        <div class="ms-auto pull-left">
            <a href="{{ route('notifications') }}" title="Refresh" style="color: #5E6E82;"><span
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
                        <th class="text-900 sort text-nowrap">Date</th>
                        <th class="text-900 sort text-nowrap">Business name</th>
                        <th class="text-900 sort text-nowrap">Sent to</th>
                        <th class="text-900 sort text-nowrap">Type</th>
                        <th class="text-900 sort text-nowrap">Reminder</th>
                        <th class="text-900 sort text-nowrap">Status</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody id="notification_filter_data">
                    @include('notifications.partials.log_rows')
                </tbody>
            </table>
            <div class="text-center justify-content-center filter-loader" style="display: none">
                <div class="spinner-border text-danger" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <div class="pagination-wrapper">
                {{ $logs->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@include('notifications.off_canvas')
@endsection
@section('scripts')
<script>
    // Notification log filter
    let filterApplied = false;
    $('#notification_filter').submit(function(e) {
        e.preventDefault();
        filterApplied = true;
        load_notification_data(1);
    });

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        let page = href.split('page=')[1];

        if (!filterApplied) {
            window.location.href = href;
        } else {
            load_notification_data(page);
        }
    });

    function load_notification_data(page) {
        $('.filter-loader').show();
        $('#notification_filter_data').hide();
        let formData = $('#notification_filter').serialize();

        $.ajax({
            type: "GET",
            url: `{{ route('notifications_filter') }}?page=${page}`,
            data: formData,
            success: function(response) {
                $("#notification_filter_data").html(response.html);
                $(".pagination-wrapper").html(response.pagination);
                $('#notification_filter_data').show();
                $('.filter-loader').hide();

                var off_canvas_element = document.getElementById('filterOffcanvas');
                var off_canvas_instance = bootstrap.Offcanvas.getOrCreateInstance(off_canvas_element);
                off_canvas_instance.hide();
            }
        });
    }
</script>
@endsection
