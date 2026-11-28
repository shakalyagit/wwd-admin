@extends('layouts.main')
@section('content')
@php
    $keep = fn(array $extra) => array_filter(array_merge(request()->only('import', 'q'), $extra), fn($v) => $v !== null && $v !== '');
    $status_icons = ['uploaded' => 'bi-inbox', 'needs_fix' => 'bi-exclamation-triangle', 'approved' => 'bi-patch-check', 'skipped' => 'bi-slash-circle'];
@endphp
<div class="content-header">
    <div>
        <h5>Admin listings · Excel import</h5>
        <p class="page-subtitle">Upload many businesses at once. Each row becomes a draft: review it, add a logo if you have one, then approve to put it live and email the owner.</p>
    </div>
</div>
@include('admin_listings.partials.nav')
<x-flash />
@if ($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Upload businesses</h6></div>
            <div class="card-body">
                <ol class="import-steps">
                    <li>
                        <b>Download the sample Excel</b>
                        <span>It has the columns, a drop-down of WWD categories and instructions.</span>
                        <a href="{{ route('listing_import_sample') }}" class="btn btn-light btn-sm mt-1"><i class="bi bi-download"></i> Download sample</a>
                    </li>
                    <li>
                        <b>Fill it in</b>
                        <span>One business per row, up to 500 rows. Required: business name, website, email, category.</span>
                    </li>
                    <li>
                        <b>Upload it</b>
                        <form method="POST" action="{{ route('listing_import_upload') }}" enctype="multipart/form-data" class="import-upload mt-1">
                            @csrf
                            <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                            <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-upload"></i> Upload</button>
                        </form>
                    </li>
                </ol>
                <p class="small text-600 mb-0 mt-2"><i class="bi bi-info-circle"></i> Rows whose website or name is already on WWD, or repeated in the file, are skipped. Nothing goes live until you approve it.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Recent uploads</h6></div>
            <div class="table-responsive">
                <table class="table mb-0 table-sm import-history">
                    <thead><tr><th>File</th><th class="cat-num">Rows</th><th class="cat-num">Ready</th><th class="cat-num">Fix</th><th class="cat-num">Skipped</th></tr></thead>
                    <tbody>
                        @forelse ($imports as $item)
                        <tr>
                            <td>
                                <a href="{{ route('listing_import', ['import' => $item->admin_listing_import_id]) }}" class="fw-medium text-900">{{ \Illuminate\Support\Str::limit($item->file_name, 32) }}</a>
                                <div class="user-sub">{{ $item->created_at->format('d M Y, h:i A') }} · {{ trim((string) $item->uploader) ?: 'Unknown' }}</div>
                            </td>
                            <td class="cat-num">{{ $item->total_rows }}</td>
                            <td class="cat-num">{{ $item->uploaded_rows }}</td>
                            <td class="cat-num">{{ $item->needs_fix_rows ?: '–' }}</td>
                            <td class="cat-num">{{ $item->skipped_rows ?: '–' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-600 py-4">No uploads yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if ($last_import && $last_import->skipped_details)
<div class="card mb-3 import-skipped">
    <div class="card-header"><h6 class="mb-0">Skipped from "{{ $last_import->file_name }}" <span class="text-600 fw-normal small">· {{ count($last_import->skipped_details) }} {{ \Illuminate\Support\Str::plural('row', count($last_import->skipped_details)) }}, not imported</span></h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th style="width: 70px;">Row</th><th>Business</th><th>Why</th></tr></thead>
            <tbody>
                @foreach ($last_import->skipped_details as $skip)
                <tr><td>{{ $skip['row'] }}</td><td class="fw-medium text-900">{{ $skip['name'] }}</td><td class="text-600">{{ $skip['reason'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<ul class="nav nav-tabs status-tabs">
    @foreach (\App\Models\AdminListingDraft::STATUSES as $key => $label)
    <li class="nav-item">
        <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('listing_import', $keep(['status' => $key])) }}">
            <i class="bi {{ $status_icons[$key] }}"></i> {{ $label }}<span class="count {{ $key === 'needs_fix' && ($counts[$key] ?? 0) ? 'is-alert' : '' }}">{{ number_format($counts[$key] ?? 0) }}</span>
        </a>
    </li>
    @endforeach
</ul>

<div class="card mt-3">
    <div class="card-header">
        <form method="GET" action="{{ route('listing_import') }}" class="users-toolbar">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="cat-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Business, website or email…" autocomplete="off">
            </div>
            @if (request('import'))
            <span class="badge rounded-pill badge-subtle-primary">Upload #{{ request('import') }}</span>
            @endif
            @if (request('import') || request('q'))
            <a href="{{ route('listing_import', ['status' => $status]) }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            @endif
            @if ($status === 'uploaded' && $drafts->count())
            <button type="submit" form="bulkApprove" class="btn btn-primary btn-sm ms-auto" id="bulkApproveBtn" disabled>
                <i class="bi bi-patch-check"></i> Approve selected (<span data-selected>0</span>)
            </button>
            @else
            <span class="users-count ms-auto">{{ number_format($drafts->total()) }} {{ \Illuminate\Support\Str::plural('business', $drafts->total()) }}</span>
            @endif
        </form>
    </div>

    <form method="POST" action="{{ route('listing_drafts_approve') }}" id="bulkApprove"
        onsubmit="return confirm('Approve the selected businesses? They go live on WWD and each owner gets the invite email.');">
        @csrf
    </form>
    <div class="table-responsive">
        <table class="table mb-0 drafts-table">
            <thead>
                <tr>
                    @if ($status === 'uploaded')
                    <th style="width: 36px;"><input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all"></th>
                    @endif
                    <th>Business</th>
                    <th>Category</th>
                    <th>Contact</th>
                    <th>Logo</th>
                    <th>{{ $status === 'needs_fix' ? 'To fix' : ($status === 'approved' ? 'Approved' : ($status === 'skipped' ? 'Reason' : 'Source')) }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drafts as $draft)
                <tr>
                    @if ($status === 'uploaded')
                    <td><input type="checkbox" class="form-check-input draft-check" name="drafts[]" value="{{ $draft->admin_listing_draft_id }}" form="bulkApprove" aria-label="Select {{ $draft->business_name }}"></td>
                    @endif
                    <td>
                        <a href="{{ route('listing_draft', encrypt($draft->admin_listing_draft_id)) }}" class="fw-medium text-900">{{ $draft->business_name ?: '(no name)' }}</a>
                        <div class="user-sub">{{ preg_replace('#^https?://#', '', (string) $draft->business_website) ?: 'no website' }}</div>
                        <div class="user-sub">Row {{ $draft->row_number }} · {{ \Illuminate\Support\Str::limit((string) $draft->file_name, 28) }}</div>
                    </td>
                    <td>
                        @if ($draft->category_name)
                        {{ $draft->category_name }}
                        @elseif ($draft->category_text)
                        <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> {{ $draft->category_text }}</span>
                        @else
                        <span class="text-500">–</span>
                        @endif
                    </td>
                    <td>
                        <span class="d-block">{{ $draft->business_email ?: '–' }}</span>
                        @if ($draft->business_phone)<span class="user-sub">{{ $draft->country_code }} {{ $draft->business_phone }}</span>@endif
                    </td>
                    <td>
                        @if ($draft->logo_file)
                        <span class="badge rounded-pill badge-subtle-success"><i class="bi bi-image"></i> Added</span>
                        @elseif ($status === 'approved')
                        <span class="text-500">–</span>
                        @else
                        <span class="text-500 small text-nowrap">None yet</span>
                        @endif
                    </td>
                    <td class="draft-note">
                        @if ($status === 'needs_fix')
                        <ul class="draft-problems">
                            @foreach ((array) $draft->problems as $problem)
                            <li>{{ $problem }}</li>
                            @endforeach
                        </ul>
                        @elseif ($status === 'approved')
                        {{ optional($draft->approved_at)->format('d M Y') }}
                        @if ($draft->business_id)<div class="user-sub"><a href="{{ route('edit_business', encrypt($draft->business_id)) }}">Open business</a></div>@endif
                        @elseif ($status === 'skipped')
                        <span class="text-600">{{ $draft->skip_reason ?: '–' }}</span>
                        @else
                        {{ $draft->source_name ?: '–' }}
                        @endif
                    </td>
                    <td class="text-end">
                        @if ($status !== 'approved')
                        <a href="{{ route('listing_draft', encrypt($draft->admin_listing_draft_id)) }}" class="btn btn-light btn-sm text-nowrap">
                            {{ $status === 'needs_fix' ? 'Fix' : 'Review' }} <i class="bi bi-chevron-right"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-600">
                        <i class="bi bi-file-earmark-spreadsheet d-block fs-6 mb-2"></i>
                        No {{ strtolower(\App\Models\AdminListingDraft::STATUSES[$status]) }} businesses{{ request('import') || request('q') ? ' match these filters' : '' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $drafts->links('pagination::bootstrap-5') }}
    </div>
</div>
@if (!$template || !$template->is_active)
<div class="alert alert-warning small mt-3"><i class="bi bi-exclamation-triangle"></i> The invite email ("Business listed by WWD") is switched off in Email templates, so approving won't email owners.</div>
@endif
@endsection
@section('scripts')
<script>
    (function() {
        const boxes = Array.from(document.querySelectorAll('.draft-check'));
        const all = document.getElementById('selectAll');
        const button = document.getElementById('bulkApproveBtn');
        if (!button) {
            return;
        }
        function sync() {
            const n = boxes.filter(b => b.checked).length;
            button.disabled = n === 0;
            button.querySelector('[data-selected]').textContent = n;
            if (all) {
                all.checked = n > 0 && n === boxes.length;
                all.indeterminate = n > 0 && n < boxes.length;
            }
        }
        boxes.forEach(b => b.addEventListener('change', sync));
        if (all) {
            all.addEventListener('change', function() {
                boxes.forEach(b => b.checked = all.checked);
                sync();
            });
        }
    })();
</script>
@endsection
