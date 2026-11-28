@extends('layouts.main')
@section('content')
@php
    $values = $draft->only(\App\Models\AdminListingDraft::FIELDS) + ['category_text' => $draft->category_text];
    $logo_url = $draft->logo_file ? route('listing_draft_logo', encrypt($draft->admin_listing_draft_id)) . '?v=' . md5($draft->logo_file) : null;
    $status_class = ['uploaded' => 'badge-subtle-primary', 'needs_fix' => 'badge-subtle-warning', 'approved' => 'badge-subtle-success', 'skipped' => 'badge-subtle-secondary'][$draft->status];
    $total = $siblings->count();
@endphp
<div class="content-header">
    <div>
        <h5>Admin listings · Review business <span class="badge rounded-pill {{ $status_class }} align-middle ms-1">{{ \App\Models\AdminListingDraft::STATUSES[$draft->status] }}</span></h5>
        <p class="page-subtitle">
            From "{{ $import->file_name ?? 'Excel' }}", row {{ $draft->row_number }}.
            @if ($position !== false && $total > 1) {{ $position + 1 }} of {{ $total }} in {{ \App\Models\AdminListingDraft::STATUSES[$draft->status] }}. @endif
            Check the details, add a logo if you have one, then approve.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('listing_import', ['status' => $draft->status]) }}" class="btn btn-light text-nowrap"><i class="bi bi-arrow-left"></i> Back to list</a>
        @if ($next_id)
        <a href="{{ route('listing_draft', encrypt($next_id)) }}" class="btn btn-light text-nowrap">Next <i class="bi bi-chevron-right"></i></a>
        @endif
    </div>
</div>
@include('admin_listings.partials.nav')
<x-flash />

@if ($draft->status === 'approved')
<div class="alert alert-success">
    <i class="bi bi-patch-check"></i> Approved {{ optional($draft->approved_at)->format('d M Y, h:i A') }}. The business is live.
    @if ($business)
    <a href="{{ route('edit_business', encrypt($business->business_id)) }}" class="alert-link ms-1">Open business</a>
    · <a href="{{ config('services.wwd.listing_url') . $business->wwd_url }}" target="_blank" rel="noopener" class="alert-link">View public listing</a>
    @endif
</div>
@elseif ($errors->any())
<div class="alert alert-danger">
    <strong>Fix these before approving:</strong>
    <ul class="mb-0 mt-1">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    @if (session('duplicate_id'))
    <a href="{{ route('edit_business', session('duplicate_id')) }}" class="alert-link">Open the existing business</a>
    @endif
    @if (old('logo_pending'))
    <div class="small mt-1">The logo you added wasn't saved because of the problem above. Please add it again.</div>
    @endif
</div>
@elseif ($draft->problems)
<div class="alert alert-warning">
    <strong><i class="bi bi-exclamation-triangle"></i> To fix:</strong>
    <ul class="mb-0 mt-1">
        @foreach ($draft->problems as $problem)
        <li>{{ $problem }}</li>
        @endforeach
    </ul>
</div>
@endif
@if ($draft->status === 'skipped')
<div class="alert alert-secondary d-flex align-items-center gap-2">
    <span><i class="bi bi-slash-circle"></i> Skipped{{ $draft->skip_reason ? ': ' . $draft->skip_reason : '' }}.</span>
    <form method="POST" action="{{ route('listing_draft_skip', encrypt($draft->admin_listing_draft_id)) }}" class="ms-auto">
        @csrf
        <input type="hidden" name="restore" value="1">
        <button type="submit" class="btn btn-light btn-sm">Move back to review</button>
    </form>
</div>
@endif

<form action="{{ route('listing_draft_save', encrypt($draft->admin_listing_draft_id)) }}" method="POST" enctype="multipart/form-data" id="listingForm" novalidate>
    @csrf
    @if ($next_id)
    <input type="hidden" name="next" value="{{ encrypt($next_id) }}">
    @endif
    <fieldset @disabled($draft->status === 'approved')>
        <div class="row g-3">
            <div class="col-xl-8">
                @include('admin_listings.partials.form_fields', ['values' => $values, 'logo_url' => $logo_url])
            </div>

            <div class="col-xl-4">
                @include('admin_listings.partials.source_card', ['values' => $values])

                @if ($draft->status !== 'approved')
                <div class="card mt-3">
                    <div class="card-header"><h6 class="mb-0">When you approve</h6></div>
                    <div class="card-body">
                        <ol class="listing-steps">
                            <li>The business goes <strong>live</strong> on the WWD public listing.</li>
                            <li>The owner gets the email with <strong>View your listing</strong> and <strong>Claim my business</strong> links, then reminders on day 3, 10 and 30 until it's claimed.</li>
                        </ol>
                        @if (!$draft->logo_file)
                        <div class="small text-600 mt-2"><i class="bi bi-image"></i> No logo yet: the listing shows the business's initials until the owner adds one.</div>
                        @endif
                        @if (!$template || !$template->is_active)
                        <div class="alert alert-warning small mb-0 mt-2"><i class="bi bi-exclamation-triangle"></i> The invite email is switched off in Email templates, so no email will be sent.</div>
                        @endif
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" name="action" value="approve" class="btn btn-primary btn-lg" id="approveBtn"
                        onclick="return confirm('Approve this business? It goes live on WWD and the owner is emailed.');">
                        <i class="bi bi-patch-check"></i> Approve &amp; list{{ $next_id ? ', then next' : '' }}
                    </button>
                    <button type="submit" name="action" value="save" class="btn btn-light" id="saveBtn"><i class="bi bi-check2"></i> Save changes</button>
                    @if ($draft->status !== 'skipped')
                    <button type="button" class="btn btn-link text-danger" data-bs-toggle="modal" data-bs-target="#skipModal"><i class="bi bi-slash-circle"></i> Skip this business</button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </fieldset>
</form>

@if ($draft->status !== 'approved')
<div class="modal fade" id="skipModal" tabindex="-1" aria-labelledby="skipTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('listing_draft_skip', encrypt($draft->admin_listing_draft_id)) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="skipTitle">Skip "{{ $draft->business_name }}"?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="skip_reason">Reason (optional)</label>
                <input type="text" class="form-control" id="skip_reason" name="reason" maxlength="250" placeholder="e.g. Website not working">
                <div class="form-text">Skipped businesses aren't listed. You can move them back later.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Skip</button>
            </div>
        </form>
    </div>
</div>
<x-logo-editor :name="$draft->business_name ?: 'This business'" :place="$draft->city" />
@endif
@endsection
@if ($draft->status !== 'approved')
@push('vendor_scripts')
<script src="/assets/js/wwd-logo-editor.js?v={{ @filemtime(public_path('assets/js/wwd-logo-editor.js')) }}"></script>
@endpush
@section('scripts')
@include('admin_listings.partials.duplicate_check_js', ['submit_ids' => ['approveBtn']])
@endsection
@endif
