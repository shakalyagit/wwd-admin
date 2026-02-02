@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Users List</h5>
    <a href="{{route('business_create')}}" class="btn btn-primary pull-right"><i class="bi bi-plus"></i> Add Business</a>
    <div class="clear"></div>
</div>
<x-flash />
<div class="card mt-2">
    <div class="card-header">
        <div class="ms-auto pull-left">
            <a href="{{ route('users') }}" title="Refresh" style="color: #5E6E82;"><span
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
                        <th class="text-900 sort text-nowrap">Name</th>
                        <th class="text-900 sort text-nowrap">Email</th>
                        <th class="text-900 sort text-nowrap">Business url</th>
                        <th class="text-900 sort text-nowrap">Created date</th>
                        <th class="text-900 sort text-nowrap">Last login at</th>
                        <th class="text-900 sort text-nowrap">Business verified</th>
                        <th class="text-900 sort text-nowrap">Users status</th>
                    </tr>
                </thead>
                <tbody id="user_filter_data">
                    @if (count($users) > 0)
                    @foreach ($users as $value)
                    <tr>
                        <td class="text-nowrap">{{ $value->first_name .' '. $value->last_name }}</td>
                        <td class="text-nowrap">{{ $value->email }}</td>
                        <td class="text-nowrap">{{ $value->business_website }}</td>
                        <td class="text-nowrap">{{ date('d-m-Y', strtotime($value->created_at)) }}</td>
                        <td class="text-nowrap">{{ date('d-m-Y H:i:s', strtotime($value->last_login_at)) }}</td>
                        <td class="text-nowrap">{!! status_badge($value->is_claimed) !!}</td>
                        <td class="text-nowrap">
                            <span onclick="return confirm('Are you sure you want to change status?')" class="user-status-toggle" data-id="{{ $value->id }}" style="cursor:pointer"> {!! users_status($value->status) !!} </span>
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
                {{ $users->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@include('users.off_canvas')
</div>
@endsection
@section('scripts')
<script>
    $(document).on('click', '.user-status-toggle', function() {

        let el = $(this);
        let userId = el.data('id');

        $.ajax({
            url: "{{ route('users_status_update') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                user_id: userId
            },
            success: function(response) {
                el.html(response.badge);
            }
        });
    });

    // User filter
    let filterApplied = false;
    $('#user_filter').submit(function(e) {
        e.preventDefault();
        filterApplied = true;
        load_risk_data(1);
    });

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        let page = href.split('page=')[1];

        if (!filterApplied) {
            window.location.href = href;
        } else {
            }
        });
        load_risk_data(page);

    function load_risk_data(page) {
        $('.filter-loader').show();
        $('#user_filter_data').hide();
        let formData = $('#user_filter').serialize();

        $.ajax({
            type: "GET",
            url: `/user-list-filter?page=${page}`,
            data: formData,
            success: function(response) {
                $("#user_filter_data").html(response.html);
                $(".pagination-wrapper").html(response.pagination);
                $('#user_filter_data').show();
                $('.filter-loader').hide();

                var off_canvas_element = document.getElementById('filterOffcanvas');
                var off_canvas_instance = bootstrap.Offcanvas.getInstance(off_canvas_element);
                off_canvas_instance.hide();
            }
        });
    }
</script>
@endsection