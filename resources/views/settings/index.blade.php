@extends('layouts.main')
@section('content')
<div class="content-header">
    <div>
        <h5>Public site settings</h5>
        <p class="page-subtitle">Social links in the footer of worldweb-directory.com and WWD's own Google reviews on its home page. The site picks up changes within 10 minutes.</p>
    </div>
</div>
<x-flash />

@if ($table_missing)
    <div class="alert alert-warning">
        The <span class="font-monospace">site_settings</span> table doesn't exist yet. Run
        <span class="font-monospace">2026_11_15_create_site_settings.sql</span> (from the directory site's release) in phpMyAdmin first.
    </div>
@else
<form action="{{ route('settings_action') }}" method="POST" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><h5>Social links</h5></div>
                <div class="card-body">
                    <p class="text-600 fs-10 mb-3">Shown as icons in the site footer under "Follow WWD". Leave a box empty to hide that network.</p>
                    <div class="row g-3">
                        @foreach (\App\Models\SiteSetting::SOCIAL as $field => $label)
                            <div class="col-12">
                                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                <input type="url" name="{{ $field }}" id="{{ $field }}" class="form-control @error($field) is-invalid @enderror"
                                    value="{{ old($field, $values[$field] ?? '') }}" placeholder="https://" maxlength="255">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><h5>WWD on Google</h5></div>
                <div class="card-body">
                    <p class="text-600 fs-10 mb-3">The home page shows WWD's Google rating and up to 3 Google reviews from the last 3 years. It needs a Google Cloud API key with the <b>Places API (New)</b> turned on.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="google_reviews_enabled" id="google_reviews_enabled" value="1"
                                    @checked(old('google_reviews_enabled', $values['google_reviews_enabled'] ?? '1') === '1')>
                                <label class="form-check-label" for="google_reviews_enabled">Show the Google reviews section on the home page</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="google_place_id">Google Place ID</label>
                            <input type="text" name="google_place_id" id="google_place_id" class="form-control font-monospace @error('google_place_id') is-invalid @enderror"
                                value="{{ old('google_place_id', $values['google_place_id'] ?? '') }}" maxlength="200" autocomplete="off">
                            @error('google_place_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">World Web Directory's profile is <span class="font-monospace">ChIJ62qb-Rh6AjoRnmRt-T0PUtc</span>.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="google_places_api_key">API key</label>
                            <input type="password" name="google_places_api_key" id="google_places_api_key" class="form-control font-monospace @error('google_places_api_key') is-invalid @enderror"
                                value="" maxlength="100" autocomplete="new-password" placeholder="{{ $key_hint ? 'Saved (' . $key_hint . '). Type a new key to replace it.' : 'Paste the key' }}">
                            @error('google_places_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                The saved key is never shown. In Google Cloud, limit it to the Places API (New) and to the website's server.
                            </div>
                            @if ($key_hint)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_api_key" id="remove_api_key" value="1">
                                    <label class="form-check-label" for="remove_api_key">Remove the saved key</label>
                                </div>
                            @endif
                        </div>
                        <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-falcon-default btn-sm" id="googleTest" @disabled(!$key_hint)>
                                <i class="bi bi-plug"></i> Check with Google
                            </button>
                            <span class="fs-10 text-600" id="googleTestResult">{{ $key_hint ? 'Uses the saved key and Place ID.' : 'Save a key first.' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save settings</button>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
    $('#googleTest').on('click', function () {
        var $button = $(this), $result = $('#googleTestResult');
        $button.prop('disabled', true);
        $result.removeClass('text-success text-danger').text('Asking Google…');
        $.ajax({
            url: @json(route('settings_google_test')),
            method: 'POST',
            data: { _token: @json(csrf_token()) },
            success: function (res) {
                $result.addClass(res.ok ? 'text-success' : 'text-danger').text(res.message);
            },
            error: function () {
                $result.addClass('text-danger').text('The check failed. Reload the page and try again.');
            },
            complete: function () { $button.prop('disabled', false); }
        });
    });
</script>
@endpush
