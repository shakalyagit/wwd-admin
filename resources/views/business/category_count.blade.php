@extends('layouts.main')
@section('content')
@php
    $approval_rate = $summary['total'] ? round($summary['approved'] / $summary['total'] * 100) : 0;
    $row_data = fn($name, $stats) => 'data-name="' . e(mb_strtolower($name)) . '" data-total="' . $stats['total'] . '" data-approved="' . $stats['approved']
        . '" data-rate="' . ($stats['total'] ? round($stats['approved'] / $stats['total'] * 100, 1) : -1) . '"';
@endphp
<div class="content-header">
    <div>
        <h5>Categories</h5>
        <p class="page-subtitle">Submissions and approvals for every main category and sub-category.</p>
    </div>
    <a href="{{ route('category_create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add category</a>
</div>
<x-flash />

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Main categories</span><span class="kpi-icon gray"><i class="bi bi-diagram-3"></i></span></div>
            <div class="kpi-value">{{ $summary['main_count'] }}</div>
            <div class="kpi-meta"><strong>{{ $summary['sub_count'] }}</strong> sub-categories</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Submitted businesses</span><span class="kpi-icon red"><i class="bi bi-building"></i></span></div>
            <div class="kpi-value">{{ number_format($summary['total']) }}</div>
            <div class="kpi-meta"><strong>{{ number_format($summary['pending']) }}</strong> pending · <strong>{{ number_format($summary['rejected']) }}</strong> rejected</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Approved</span><span class="kpi-icon green"><i class="bi bi-shield-check"></i></span></div>
            <div class="kpi-value">{{ number_format($summary['approved']) }}</div>
            <div class="kpi-meta"><strong>{{ $approval_rate }}%</strong> approval rate</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card kpi-card">
            <div class="kpi-top"><span class="kpi-label">Empty sub-categories</span><span class="kpi-icon amber"><i class="bi bi-inbox"></i></span></div>
            <div class="kpi-value">{{ $summary['empty_subs'] }}</div>
            <div class="kpi-meta">No submissions yet</div>
        </div>
    </div>
</div>

@if ($summary['uncategorized'] > 0)
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i>
    {{ $summary['uncategorized'] }} {{ Str::plural('business', $summary['uncategorized']) }} point to a category that no longer exists and aren't counted below.
</div>
@endif

<div class="card">
    <div class="card-header cat-toolbar">
        <div class="cat-search">
            <i class="bi bi-search"></i>
            <input type="search" id="catSearch" class="form-control form-control-sm" placeholder="Search categories…"
                value="{{ $search }}" autocomplete="off">
        </div>
        <select id="catFilter" class="form-select form-select-sm" aria-label="Show">
            <option value="all">All categories</option>
            <option value="with">With submissions</option>
            <option value="empty">Empty sub-categories</option>
        </select>
        <select id="catSort" class="form-select form-select-sm" aria-label="Sort by">
            <option value="total">Most submissions</option>
            <option value="approved">Most approved</option>
            <option value="rate">Highest approval rate</option>
            <option value="name">Name A–Z</option>
        </select>
        <button type="button" class="btn btn-light btn-sm ms-auto" id="catToggleAll"><i class="bi bi-arrows-expand"></i> <span>Expand all</span></button>
    </div>
    <div class="table-responsive">
        <table class="table cat-table mb-0">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="cat-num">Total</th>
                    <th class="cat-num">Approved</th>
                    <th class="cat-num">Pending</th>
                    <th>Approval rate</th>
                </tr>
            </thead>
            @foreach ($groups as $group)
            <tbody class="cat-group" {!! $row_data($group['name'], $group['stats']) !!}>
                <tr class="cat-parent">
                    <td>
                        <div class="cat-name-cell">
                            <button type="button" class="cat-toggle" aria-expanded="false" aria-label="Show sub-categories of {{ $group['name'] }}">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="cat-icon">{{ mb_strtoupper(mb_substr($group['name'], 0, 1)) }}</span>
                            <span>
                                <a href="{{ route('category_businesses', $group['id']) }}" class="cat-title">{{ $group['name'] }}</a>
                                <span class="cat-meta">{{ count($group['subs']) }} {{ Str::plural('sub-category', count($group['subs'])) }}</span>
                            </span>
                            <a href="{{ route('category_create', ['parent' => $group['id']]) }}" class="cat-add-sub"
                                title="Add a sub-category to {{ $group['name'] }}"><i class="bi bi-plus-lg"></i></a>
                        </div>
                    </td>
                    @include('business.partials.category_stat_cells', ['stats' => $group['stats'], 'link' => route('category_businesses', $group['id'])])
                </tr>
                @if ($group['direct']['total'] > 0)
                <tr class="cat-child cat-direct" {!! $row_data($group['name'], $group['direct']) !!}>
                    <td>
                        <a href="{{ route('category_businesses', $group['id']) }}?scope=direct" class="cat-child-name">
                            <i class="bi bi-folder2"></i> Filed under main category only
                        </a>
                    </td>
                    @include('business.partials.category_stat_cells', ['stats' => $group['direct'], 'link' => route('category_businesses', $group['id']) . '?scope=direct'])
                </tr>
                @endif
                @foreach ($group['subs'] as $sub)
                <tr class="cat-child" {!! $row_data($sub['name'], $sub['stats']) !!}>
                    <td>
                        <a href="{{ route('category_businesses', $sub['id']) }}" class="cat-child-name">{{ $sub['name'] }}</a>
                    </td>
                    @include('business.partials.category_stat_cells', ['stats' => $sub['stats'], 'link' => route('category_businesses', $sub['id'])])
                </tr>
                @endforeach
            </tbody>
            @endforeach
            <tbody id="catNoResults" hidden>
                <tr>
                    <td colspan="5" class="text-center py-5 text-600">
                        <i class="bi bi-search d-block fs-6 mb-2"></i>No categories match your search.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function() {
        var table = document.querySelector('.cat-table');
        var groups = Array.prototype.slice.call(table.querySelectorAll('.cat-group'));
        var search = document.getElementById('catSearch');
        var filter = document.getElementById('catFilter');
        var sort = document.getElementById('catSort');
        var toggle_all = document.getElementById('catToggleAll');
        var no_results = document.getElementById('catNoResults');

        function set_open(group, open) {
            group.classList.toggle('is-open', open);
            group.querySelector('.cat-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function sort_key(row) {
            var key = sort.value;
            return key === 'name' ? row.dataset.name : parseFloat(row.dataset[key]);
        }

        function compare(a, b) {
            var x = sort_key(a), y = sort_key(b);
            if (sort.value === 'name') {
                return x.localeCompare(y);
            }
            return y - x || a.dataset.name.localeCompare(b.dataset.name);
        }

        function apply_sort() {
            groups.sort(compare).forEach(function(group) {
                var rows = Array.prototype.slice.call(group.querySelectorAll('.cat-child:not(.cat-direct)'));
                rows.sort(compare).forEach(function(row) {
                    group.appendChild(row);
                });
                table.insertBefore(group, no_results);
            });
        }

        // Search + filter: show matching groups, and inside them the matching sub-categories
        function apply_filter() {
            var term = search.value.trim().toLowerCase();
            var mode = filter.value;
            var any_visible = false;

            groups.forEach(function(group) {
                var parent_match = term === '' || group.dataset.name.indexOf(term) !== -1;
                var visible_children = 0;

                group.querySelectorAll('.cat-child').forEach(function(row) {
                    var total = parseInt(row.dataset.total, 10);
                    var text_ok = parent_match || row.dataset.name.indexOf(term) !== -1;
                    var mode_ok = mode === 'all' || (mode === 'with' && total > 0) ||
                        (mode === 'empty' && total === 0 && !row.classList.contains('cat-direct'));
                    var show = text_ok && mode_ok;
                    row.hidden = !show;
                    if (show) {
                        visible_children++;
                    }
                });

                var group_total = parseInt(group.dataset.total, 10);
                var show_group = mode === 'all' ? (parent_match || visible_children > 0) :
                    mode === 'with' ? group_total > 0 && (parent_match || visible_children > 0) :
                    visible_children > 0;

                group.hidden = !show_group;
                if (show_group) {
                    any_visible = true;
                }
                // Open groups when the user is narrowing down, so matches are visible
                if (term !== '' || mode === 'empty') {
                    set_open(group, show_group && visible_children > 0);
                }
            });

            no_results.hidden = any_visible;
        }

        table.addEventListener('click', function(e) {
            var parent_row = e.target.closest('.cat-parent');
            if (!parent_row || e.target.closest('a')) {
                return;
            }
            var group = parent_row.parentNode;
            set_open(group, !group.classList.contains('is-open'));
        });

        toggle_all.addEventListener('click', function() {
            var open = toggle_all.dataset.open !== '1';
            groups.forEach(function(group) {
                set_open(group, open);
            });
            toggle_all.dataset.open = open ? '1' : '0';
            toggle_all.querySelector('span').textContent = open ? 'Collapse all' : 'Expand all';
            toggle_all.querySelector('i').className = open ? 'bi bi-arrows-collapse' : 'bi bi-arrows-expand';
        });

        var timer;
        search.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(apply_filter, 150);
        });
        filter.addEventListener('change', apply_filter);
        sort.addEventListener('change', apply_sort);

        if (search.value) {
            apply_filter();
        }
    })();
</script>
@endsection
