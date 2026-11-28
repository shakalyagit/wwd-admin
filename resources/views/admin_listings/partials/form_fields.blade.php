{{-- Business, logo and address fields shared by Add business and the Excel draft review.
     $values: object/array of current values (a draft), or null; old() input always wins.
     $logo_url: current logo to show (draft logo), or null. --}}
@php
    $values = (array) ($values ?? []);
    $val = fn($field, $default = null) => old($field, $values[$field] ?? $default);
@endphp
<div class="card">
    <div class="card-header"><h6 class="mb-0">Business</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="business_website">Website <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('business_website') is-invalid @enderror" id="business_website" name="business_website"
                    value="{{ $val('business_website') }}" placeholder="https://example.com" autocomplete="off" required>
                <div class="form-text">Only the domain is kept, e.g. https://example.com</div>
                @error('business_website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="dup-hint" data-for="website" hidden></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="business_name">Business name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('business_name') is-invalid @enderror" id="business_name" name="business_name"
                    value="{{ $val('business_name') }}" maxlength="255" autocomplete="off" required>
                @error('business_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="dup-hint" data-for="name" hidden></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="business_email">Business email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('business_email') is-invalid @enderror" id="business_email" name="business_email"
                    value="{{ $val('business_email') }}" maxlength="50" placeholder="info@example.com" required>
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
                        <option value="{{ $parent->category_id }}" @selected($val('category_id') == $parent->category_id)>{{ $parent->cat_name }} (general)</option>
                        @foreach ($children->where('parent_cat_id', $parent->category_id) as $child)
                        <option value="{{ $child->category_id }}" @selected($val('category_id') == $child->category_id)>{{ $child->cat_name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                @if (!empty($values['category_text']) && !$val('category_id'))
                <div class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> The Excel said "{{ $values['category_text'] }}", which isn't a WWD category. Choose one.</div>
                @endif
                @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="business_phone">Phone</label>
                <div class="input-group">
                    <select name="country_code" class="form-select listing-phone-code @error('country_code') is-invalid @enderror" aria-label="Phone country code">
                        @foreach ($phone_codes as $code => $label)
                        <option value="{{ $code }}" @selected($val('country_code', '+1') === $code)>{{ $code }} · {{ \Illuminate\Support\Str::before($label, ' (') }}</option>
                        @endforeach
                    </select>
                    <input type="tel" class="form-control @error('business_phone') is-invalid @enderror" id="business_phone" name="business_phone"
                        value="{{ $val('business_phone') }}" maxlength="45" placeholder="(555) 123-4567" autocomplete="off">
                </div>
                @error('business_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('country_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <span class="form-label d-block">Opening hours</span>
                <div class="listing-hours"><i class="bi bi-clock"></i> Mon – Sat 8:00 AM – 11:00 PM · Sunday closed</div>
                <div class="form-text">Set automatically. The owner can change them after claiming.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="business_desc">Description</label>
                <textarea class="form-control" id="business_desc" name="business_desc" rows="5" maxlength="5000"
                    placeholder="What the business does, as shown on its listing">{{ $val('business_desc') }}</textarea>
                @error('business_desc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <span class="form-label d-block">Logo <span class="text-600 fw-normal small">· optional</span></span>
                <x-logo-field name="logo" :url="$logo_url ?? null" :error="$errors->first('logo')"
                    :initials="mb_strtoupper(mb_substr((string) $val('business_name', 'W'), 0, 2))" />
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
                <input type="text" class="form-control" id="street_line_1" name="street_line_1" value="{{ $val('street_line_1') }}" maxlength="50">
                @error('street_line_1')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="street_line_2">Address line 2</label>
                <input type="text" class="form-control" id="street_line_2" name="street_line_2" value="{{ $val('street_line_2') }}" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ $val('city') }}" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="province_state_territory">State / province</label>
                <input type="text" class="form-control" id="province_state_territory" name="province_state_territory" value="{{ $val('province_state_territory') }}" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="postal_code">Postal code</label>
                <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $val('postal_code') }}" maxlength="10">
                @error('postal_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ref_country_id">Country</label>
                <select name="ref_country_id" id="ref_country_id" class="form-select @error('ref_country_id') is-invalid @enderror">
                    <option value="">No address</option>
                    @foreach ($ref_countries as $country)
                    <option value="{{ $country->ref_countries_id }}" @selected($val('ref_country_id') == $country->ref_countries_id)>{{ $country->printable_name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Needed to save an address.</div>
                @error('ref_country_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
