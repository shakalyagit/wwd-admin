@extends('layouts.main')
@section('content')
@php
    $not_approved = $counts->pending + $counts->rejected;
    $share = fn($n) => $counts->total ? round($n / $counts->total * 100) : 0;
    // [key, label, value, icon, colour, meta]
    $filter_cards = [
        ['all', 'Total businesses', $counts->total, 'bi-building', 'gray', 'All listings in the directory'],
        ['today', 'Today', $counts->today, 'bi-calendar-check', 'red', $counts->today_pending . ' waiting for review'],
        ['approved', 'Approved', $counts->approved, 'bi-shield-check', 'green', $share($counts->approved) . '% of all businesses'],
        ['not_approved', 'Not approved', $not_approved, 'bi-hourglass-split', 'amber', $counts->pending . ' pending · ' . $counts->rejected . ' rejected'],
    ];
@endphp
<div class="content-header">
    <div>
        <h5>Businesses</h5>
        <p class="page-subtitle">Click a card to filter the list.</p>
    </div>
    <a href="{{ route('business_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add business</a>
</div>
<x-flash />

<div class="row g-3 mb-3">
    @foreach ($filter_cards as [$key, $label, $value, $icon, $color, $meta])
    <div class="col-6 col-xl-3">
        <button type="button" class="card kpi-card kpi-filter {{ $key === 'all' ? 'is-active' : '' }}" data-filter="{{ $key }}"
            aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}">
            <span class="kpi-top"><span class="kpi-label">{{ $label }}</span><span class="kpi-icon {{ $color }}"><i class="bi {{ $icon }}"></i></span></span>
            <span class="kpi-value">{{ number_format($value) }}</span>
            <span class="kpi-meta">{{ $meta }}</span>
        </button>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="ms-auto pull-left">
            <a href="{{ route('business_list') }}" title="Refresh" style="color: #5E6E82;"><span
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
                        <th class="text-900 sort text-nowrap">Submission date</th>
                        <th class="text-900 sort text-nowrap">Business name</th>
                        <th class="text-900 sort text-nowrap">Category name</th>
                        <th class="text-900 sort text-nowrap">Business verified</th>
                        <th class="text-900 sort text-nowrap">Admin verified</th>
                        <th class="text-900 sort text-nowrap" title="Profile completeness score (hover a circle for details)">Profile</th>
                        <th class="text-900 sort text-nowrap noExl">Action</th>
                    </tr>
                </thead>
                <tbody id="business_filter_data">
                    @if (count($business) > 0)
                    @foreach ($business as $value)
                    <tr>
                        <td class="text-nowrap">{{ date('d-m-Y', strtotime($value->created_at)) }}</td>
                        <td class="text-nowrap">{!! business_logo_thumb($value->business_name, $value->logo_path) !!}</td>
                        <td class="text-nowrap">{{ $value->category_name }}</td>
                        <td class="text-nowrap">{!! status_badge($value->is_claimed) !!}</td>
                        <td class="text-nowrap">{!! status_badge($value->is_admin_verified) !!}</td>
                        <td>{!! profile_score_badge(business_profile_score($value, (bool) $value->logo_path, (bool) $value->has_hours, (bool) $value->has_address)) !!}</td>
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
                {{ $business->appends(request()->all())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@include('business.off_canvas')
@endsection
@section('scripts')
<script>
    // Business filter
    let filterApplied = false;
    $('#business_filter').submit(function(e) {
        e.preventDefault();
        filterApplied = true;
        sync_filter_cards();
        load_risk_data(1);
    });

    // Filter cards set the filter panel's fields, then run the same filter
    const today = @json($today);
    const card_filters = {
        all: {},
        today: { from_date: today, to_date: today },
        approved: { admin_verified: '1' },
        not_approved: { admin_verified: 'not_approved' },
    };

    $('.kpi-filter').on('click', function() {
        const form = document.getElementById('business_filter');
        form.reset();
        $.each(card_filters[this.dataset.filter], function(name, value) {
            form.elements[name].value = value;
        });
        $(form).trigger('submit');
    });

    // Highlight the card that matches the current filter panel values (none for custom filters)
    function sync_filter_cards() {
        const values = {};
        $('#business_filter').serializeArray().forEach(function(field) {
            if (field.name !== '_token' && field.value !== '') {
                values[field.name] = field.value;
            }
        });
        const current = JSON.stringify(values);
        $('.kpi-filter').each(function() {
            const active = JSON.stringify(card_filters[this.dataset.filter]) === current;
            $(this).toggleClass('is-active', active).attr('aria-pressed', active ? 'true' : 'false');
        });
    }

    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        let page = href.split('page=')[1];

        if (!filterApplied) {
            window.location.href = href;
        } else {
            load_risk_data(page);
        }
    });

    function load_risk_data(page) {
        $('.filter-loader').show();
        var selected_year = $('#financial_year option:selected').text();
        $('#current-financial-year').text(selected_year);
        $('#business_filter_data').hide();
        let formData = $('#business_filter').serialize();

        $.ajax({
            type: "GET",
            url: `/business-list-filter?page=${page}`,
            data: formData,
            success: function(response) {
                $("#business_filter_data").html(response.html);
                $(".pagination-wrapper").html(response.pagination);
                $('#business_filter_data').show();
                $('.filter-loader').hide();

                var off_canvas_element = document.getElementById('filterOffcanvas');
                var off_canvas_instance = bootstrap.Offcanvas.getOrCreateInstance(off_canvas_element);
                off_canvas_instance.hide();
            }
        });
    }
</script>

@endsection