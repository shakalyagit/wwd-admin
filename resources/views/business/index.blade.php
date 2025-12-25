@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Business List</h5>

    <div class="clear"></div>
</div>
<x-flash />
<div class="card mt-2">
    <div class="card-header">
        <div class="ms-auto pull-left">
            <a href="{{ route('business_list') }}" title="Refresh" style="color: #5E6E82;"><span
                    class="bi bi-arrow-clockwise fs-6 cursor-pointer"></span></a>
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
                        <th class="text-900 sort text-nowrap">Submission date</th>
                        <th class="text-900 sort text-nowrap">Business name</th>
                        <th class="text-900 sort text-nowrap">Email</th>
                        <th class="text-900 sort text-nowrap">Category name</th>
                        <th class="text-900 sort text-nowrap">Business verified</th>
                        <th class="text-900 sort text-nowrap">Admin verified</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody id="business_filter_data">
                    @if (count($business) > 0)
                    @foreach ($business as $value)
                    <tr>
                        <td class="text-nowrap">{{ date('d-m-Y', strtotime($value->created_at)) }}</td>
                        <td class="text-nowrap">{{ $value->business_name }}</td>
                        <td class="text-nowrap">{{ $value->business_email }}</td>
                        <td class="text-nowrap">{{ $value->category_name }}</td>
                        <td class="text-nowrap">{!! status_badge($value->is_claimed) !!}</td>
                        <td class="text-nowrap">{!! status_badge($value->is_admin_verified) !!}</td>
                        <td class="text-nowrap">
                            <a class="badge bg-info rounded rounded-circle"
                                style="padding-top: 7px; padding-bottom: 7px;"
                                href="{{ route('edit_business', Crypt::encrypt($value->business_id)) }}"
                                title="Edit business"><i class="bi bi-pencil fs-9"></i></a>
                            <a class="badge bg-warning rounded rounded-circle"
                                style="padding-top: 7px; padding-bottom: 7px;"
                                href="{{$value->business_website}}" target="_blank"
                                title="View site"><i class="bi bi-eye fs-9"></i></a>
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
                {{ $business->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@include('business.off_canvas')
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        $('#business_filter').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('business_list_filter') }}",
                type: "GET",
                data: $(this).serialize(),
                success: function(res) {
                    $('#business_filter_data').html(res.html);
                    // $(".pagination-wrapper").html(response.pagination);
                    $('.filter-loader').hide();
                    var off_canvas_element = document.getElementById('filterOffcanvas');
                    var off_canvas_instance = bootstrap.Offcanvas.getInstance(off_canvas_element);
                    off_canvas_instance.hide();
                }
            });
        });
    });
</script>

@endsection