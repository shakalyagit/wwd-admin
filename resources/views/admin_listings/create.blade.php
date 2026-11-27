@extends('layouts.main')
@section('content')
<div class="content-header">
    <div>
        <h5>Add business</h5>
        <p class="page-subtitle">List a business you found on another directory. It goes live on WWD right away and the owner is emailed an invite to claim it.</p>
    </div>
    <a href="{{ route('admin_listings') }}" class="btn btn-light"><i class="bi bi-bar-chart"></i> Admin listings</a>
</div>
<x-flash />

@if ($errors->any())
<div class="alert alert-danger">
    <strong>Please check the form.</strong>
    @if (session('duplicate_id'))
    <a href="{{ route('edit_business', session('duplicate_id')) }}" class="alert-link ms-1">Open the existing business</a>
    @endif
</div>
@endif

<form action="{{ route('business_store') }}" method="POST" enctype="multipart/form-data" id="listingForm" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Business</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="business_website">Website <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('business_website') is-invalid @enderror" id="business_website" name="business_website"
                                value="{{ old('business_website') }}" placeholder="https://example.com" autocomplete="off" required>
                            <div class="form-text">Only the domain is kept, e.g. https://example.com</div>
                            @error('business_website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="dup-hint" data-for="website" hidden></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="business_name">Business name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('business_name') is-invalid @enderror" id="business_name" name="business_name"
                                value="{{ old('business_name') }}" maxlength="255" autocomplete="off" required>
                            @error('business_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="dup-hint" data-for="name" hidden></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="business_email">Business email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('business_email') is-invalid @enderror" id="business_email" name="business_email"
                                value="{{ old('business_email') }}" maxlength="50" placeholder="info@example.com" required>
                            <div class="form-text">The claim invite goes here, and the owner confirms the claim with a code sent to it.</div>
                            @error('business_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="dup-hint is-info" data-for="email" hidden></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Choose a category</option>
                                @foreach ($parents as $parent)
                                <optgroup label="{{ $parent->cat_name }}">
                                    <option value="{{ $parent->category_id }}" @selected(old('category_id') == $parent->category_id)>{{ $parent->cat_name }} (general)</option>
                                    @foreach ($children->where('parent_cat_id', $parent->category_id) as $child)
                                    <option value="{{ $child->category_id }}" @selected(old('category_id') == $child->category_id)>{{ $child->cat_name }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="business_desc">Description</label>
                            <textarea class="form-control" id="business_desc" name="business_desc" rows="5" maxlength="5000"
                                placeholder="What the business does, as shown on its listing">{{ old('business_desc') }}</textarea>
                            @error('business_desc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="logo">Logo</label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">
                            <div class="form-text">PNG, JPG, WebP or GIF, up to 2 MB. Resized to 500 × 500.</div>
                            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <span class="form-label d-block">Opening hours</span>
                            <div class="listing-hours"><i class="bi bi-clock"></i> Mon – Sat 8:00 AM – 11:00 PM · Sunday closed</div>
                            <div class="form-text">Set automatically. The owner can change them after claiming.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h6 class="mb-0">Address <span class="text-600 fw-normal small">· optional</span></h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="street_line_1">Address line 1</label>
                            <input type="text" class="form-control" id="street_line_1" name="street_line_1" value="{{ old('street_line_1') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="street_line_2">Address line 2</label>
                            <input type="text" class="form-control" id="street_line_2" name="street_line_2" value="{{ old('street_line_2') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="province_state_territory">State / province</label>
                            <input type="text" class="form-control" id="province_state_territory" name="province_state_territory" value="{{ old('province_state_territory') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="postal_code">Postal code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ref_country_id">Country</label>
                            <select name="ref_country_id" id="ref_country_id" class="form-select @error('ref_country_id') is-invalid @enderror">
                                <option value="">No address</option>
                                @foreach ($ref_countries as $country)
                                <option value="{{ $country->ref_countries_id }}" @selected(old('ref_country_id') == $country->ref_countries_id)>{{ $country->printable_name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Needed to save an address.</div>
                            @error('ref_country_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Found on <span class="text-600 fw-normal small">· admin reference only</span></h6></div>
                <div class="card-body">
                    <label class="form-label" for="source_name">Source site</label>
                    <input type="text" class="form-control" id="source_name" name="source_name" value="{{ old('source_name') }}" maxlength="100"
                        list="sourceList" placeholder="e.g. Yelp">
                    <datalist id="sourceList">
                        @foreach ($sources as $source)
                        <option value="{{ $source }}">
                        @endforeach
                    </datalist>
                    <label class="form-label mt-3" for="source_url">Page on that site</label>
                    <input type="url" class="form-control @error('source_url') is-invalid @enderror" id="source_url" name="source_url" value="{{ old('source_url') }}" maxlength="500" placeholder="https://…">
                    @error('source_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Never shown on the public site. Used for the claim rate by source report.</div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h6 class="mb-0">What happens when you save</h6></div>
                <div class="card-body">
                    <ol class="listing-steps">
                        <li>The business goes <strong>live</strong> on WWD (approved, not claimed).</li>
                        <li>The owner gets the <strong>claim invite</strong> email now, then on day 3, 10 and 30 while it isn't claimed.</li>
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
@endsection
@section('scripts')
<script>
    // Live duplicate check: website and name must be unique across current listings
    (function() {
        const form = document.getElementById('listingForm');
        const fields = ['business_website', 'business_name', 'business_email'].map(id => document.getElementById(id));
        let timer = null;
        let blocked = { website: false, name: false };

        function hint(key, html) {
            const box = form.querySelector('.dup-hint[data-for="' + key + '"]');
            box.hidden = !html;
            box.innerHTML = html || '';
        }

        function escape(text) {
            return $('<div>').text(text || '').html();
        }

        function check() {
            $.post(@json(route('business_check')), {
                _token: @json(csrf_token()),
                business_website: fields[0].value,
                business_name: fields[1].value,
                business_email: fields[2].value
            }).done(function(res) {
                blocked.website = !!res.website;
                blocked.name = !!res.name;
                hint('website', res.website ? '<i class="bi bi-exclamation-circle"></i> Already listed as <a href="' + res.website.url + '" target="_blank">' + escape(res.website.name) + '</a>' : '');
                hint('name', res.name ? '<i class="bi bi-exclamation-circle"></i> Name already used by <a href="' + res.name.url + '" target="_blank">' + escape(res.name.name) + '</a> (' + escape(res.name.website || 'no website') + ')' : '');
                hint('email', res.email ? '<i class="bi bi-info-circle"></i> ' + res.email + ' other business' + (res.email > 1 ? 'es use' : ' uses') + ' this email. It will be linked to the same owner account.' : '');
                document.getElementById('listingSubmit').disabled = blocked.website || blocked.name;
            });
        }

        fields.forEach(function(field) {
            field.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(check, 450);
            });
        });
        if (fields[0].value || fields[1].value) {
            check();
        }
    })();
</script>
@endsection
