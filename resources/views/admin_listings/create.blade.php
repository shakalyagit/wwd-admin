@extends('layouts.main')
@section('content')
<div class="content-header">
    <div>
        <h5>Admin listings · Add business</h5>
        <p class="page-subtitle">List a business you found on another directory. It goes live on WWD right away and the owner is emailed an invite to claim it.</p>
    </div>
</div>
@include('admin_listings.partials.nav')
<x-flash />

@if ($errors->any())
<div class="alert alert-danger">
    <strong>Please check the form.</strong>
    @if (session('duplicate_id'))
    <a href="{{ route('edit_business', session('duplicate_id')) }}" class="alert-link ms-1">Open the existing business</a>
    @endif
    @if (old('logo_pending'))
    <div class="small mt-1">The logo you added wasn't saved because of the problem above. Please add it again.</div>
    @endif
</div>
@endif

<form action="{{ route('business_store') }}" method="POST" enctype="multipart/form-data" id="listingForm" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            @include('admin_listings.partials.form_fields', ['values' => null, 'logo_url' => null])
        </div>

        <div class="col-xl-4">
            @include('admin_listings.partials.source_card', ['values' => null])

            <div class="card mt-3">
                <div class="card-header"><h6 class="mb-0">What happens when you save</h6></div>
                <div class="card-body">
                    <ol class="listing-steps">
                        <li>The business goes <strong>live</strong> on WWD (approved, not claimed).</li>
                        <li>The owner gets the <strong>claim invite</strong> email now, with a link to view the listing, then reminders on day 3, 10 and 30 while it isn't claimed.</li>
                        <li>Follow clicks, claims and paid upgrades under <a href="{{ route('admin_listings') }}">Admin listings</a>.</li>
                    </ol>
                    @if (!$template || !$template->is_active)
                    <div class="alert alert-warning small mb-0 mt-2"><i class="bi bi-exclamation-triangle"></i> The invite email is switched off in Email templates, so no email will be sent.</div>
                    @endif
                </div>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary btn-lg" id="listingSubmit"><i class="bi bi-plus-lg"></i> List business &amp; send invite</button>
            </div>
        </div>
    </div>
</form>

<x-logo-editor name="This business" />
@endsection
@push('vendor_scripts')
<script src="/assets/js/wwd-logo-editor.js?v={{ @filemtime(public_path('assets/js/wwd-logo-editor.js')) }}"></script>
@endpush
@section('scripts')
@include('admin_listings.partials.duplicate_check_js', ['submit_ids' => ['listingSubmit']])
@endsection
