@extends('layouts.main')
@section('content')
@php
    $is_new = !$banner->exists;
    $action = $is_new ? route('banner_store') : route('edit_banner_action', encrypt($banner->banner_id));
    $focus = old('focus', $banner->focus ?: 'center');
    $image_url = $is_new ? null : banner_image_url($banner->image_file);
    $limit_mb = max(1, round($upload_limit / 1024));
@endphp
<div class="content-header">
    <div>
        <div class="page-breadcrumb">
            <a href="{{ route('banners') }}"><i class="bi bi-arrow-left"></i> Home banners</a>
        </div>
        <h5>{{ $is_new ? 'New home banner' : $banner->headline }}</h5>
        <p class="page-subtitle">A photo across the top of the home page, with a headline over it. The search box always sits on top.</p>
    </div>
    @unless ($is_new)
    <form action="{{ route('delete_banner', encrypt($banner->banner_id)) }}" method="POST"
        onsubmit="return confirm('Delete this banner and its photo? This can\'t be undone.');">
        @csrf
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
    @endunless
</div>
<x-flash />

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" novalidate id="bannerForm">
    @csrf
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><h5>Photo</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="image">{{ $is_new ? 'Photo' : 'Replace the photo' }}</label>
                            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                JPG, PNG or WebP, landscape, at least 1200 × 500 px (best 2400 × 1000), up to {{ $limit_mb }} MB.
                                The headline sits on the left, so a photo with its subject on the right works best.
                                Use photos you own or have permission to use.
                            </div>
                        </div>
                        <div class="col-12">
                            <span class="form-label d-block">Keep in view on phones</span>
                            <div class="btn-group" role="group" aria-label="Keep in view on phones">
                                @foreach (\App\Models\SiteBanner::FOCUS as $value => $label)
                                <input type="radio" class="btn-check" name="focus" id="focus_{{ $value }}" value="{{ $value }}" autocomplete="off" @checked($focus === $value)>
                                <label class="btn btn-outline-secondary btn-sm" for="focus_{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                            <div class="form-text">Phones show a narrow slice of the photo. Choose the part that matters.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5>Text over the photo</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="headline">Headline</label>
                            <input type="text" name="headline" id="headline" class="form-control @error('headline') is-invalid @enderror"
                                value="{{ old('headline', $banner->headline) }}" maxlength="90" placeholder="e.g. Find a dentist you can trust" data-preview="headline">
                            @error('headline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="subtext">Line under the headline <span class="text-600">(optional)</span></label>
                            <input type="text" name="subtext" id="subtext" class="form-control @error('subtext') is-invalid @enderror"
                                value="{{ old('subtext', $banner->subtext) }}" maxlength="180" data-preview="subtext">
                            @error('subtext')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="button_text">Button text <span class="text-600">(optional)</span></label>
                            <input type="text" name="button_text" id="button_text" class="form-control @error('button_text') is-invalid @enderror"
                                value="{{ old('button_text', $banner->button_text) }}" maxlength="40" placeholder="e.g. Dentists" data-preview="button_text">
                            @error('button_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="button_url">Button link</label>
                            <input type="url" name="button_url" id="button_url" class="form-control @error('button_url') is-invalid @enderror"
                                value="{{ old('button_url', $banner->button_url) }}" maxlength="500" placeholder="https://worldweb-directory.com/categories/…">
                            @error('button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="credit_text">Photo credit <span class="text-600">(optional)</span></label>
                            <input type="text" name="credit_text" id="credit_text" class="form-control @error('credit_text') is-invalid @enderror"
                                value="{{ old('credit_text', $banner->credit_text) }}" maxlength="90" placeholder="e.g. Sunrise Bakery, Kolkata" data-preview="credit_text">
                            @error('credit_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="credit_url">Credit link</label>
                            <input type="url" name="credit_url" id="credit_url" class="form-control @error('credit_url') is-invalid @enderror"
                                value="{{ old('credit_url', $banner->credit_url) }}" maxlength="500" placeholder="The business's WWD listing">
                            @error('credit_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5>When it shows</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1"
                                    @checked(session()->hasOldInput() ? old('is_active') === '1' : ($is_new || $banner->is_active))>
                                <label class="form-check-label" for="is_active">On: rotate this banner on the home page</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="starts_on">First day <span class="text-600">(optional)</span></label>
                            <input type="date" name="starts_on" id="starts_on" class="form-control @error('starts_on') is-invalid @enderror"
                                value="{{ old('starts_on', optional($banner->starts_on)->format('Y-m-d')) }}">
                            @error('starts_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="ends_on">Last day <span class="text-600">(optional)</span></label>
                            <input type="date" name="ends_on" id="ends_on" class="form-control @error('ends_on') is-invalid @enderror"
                                value="{{ old('ends_on', optional($banner->ends_on)->format('Y-m-d')) }}">
                            @error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sort_order">Order</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
                                value="{{ old('sort_order', $banner->sort_order) }}" min="0" max="999">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Lowest shows first.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-3 banner-preview-card">
                <div class="card-header"><h5>Preview</h5></div>
                <div class="card-body">
                    <p class="text-600 fs-10 mb-2">Computer</p>
                    <div class="banner-preview is-wide focus-{{ $focus }}" id="previewWide">
                        <img src="{{ $image_url }}" alt="" @if (!$image_url) hidden @endif>
                        <div class="banner-preview-text">
                            <b data-show="headline">{{ old('headline', $banner->headline) ?: 'Your headline' }}</b>
                            <span data-show="subtext">{{ old('subtext', $banner->subtext) }}</span>
                            <em data-show="button_text">{{ old('button_text', $banner->button_text) }}</em>
                            <span class="banner-preview-search">Search box</span>
                        </div>
                        <small data-show="credit_text">{{ old('credit_text', $banner->credit_text) }}</small>
                    </div>
                    <p class="text-600 fs-10 mt-3 mb-2">Phone</p>
                    <div class="banner-preview is-phone focus-{{ $focus }}" id="previewPhone">
                        <img src="{{ $image_url }}" alt="" @if (!$image_url) hidden @endif>
                        <div class="banner-preview-text">
                            <b data-show="headline">{{ old('headline', $banner->headline) ?: 'Your headline' }}</b>
                            <span class="banner-preview-search">Search box</span>
                        </div>
                    </div>
                    <p class="text-600 fs-10 mt-2 mb-0">The live site crops the phone photo when you save, keeping the part you chose.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('banners') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $is_new ? 'Add banner' : 'Save banner' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('bannerForm');
        var previews = [document.getElementById('previewWide'), document.getElementById('previewPhone')];
        var objectUrl = null;

        form.querySelectorAll('[data-preview]').forEach(function (input) {
            input.addEventListener('input', function () {
                var field = input.getAttribute('data-preview');
                var text = input.value.trim();
                document.querySelectorAll('[data-show="' + field + '"]').forEach(function (node) {
                    node.textContent = text || (field === 'headline' ? 'Your headline' : '');
                });
            });
        });

        form.querySelectorAll('input[name="focus"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                previews.forEach(function (box) {
                    box.classList.remove('focus-left', 'focus-center', 'focus-right');
                    box.classList.add('focus-' + radio.value);
                });
            });
        });

        document.getElementById('image').addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file || !/^image\//.test(file.type)) { return; }
            if (objectUrl) { URL.revokeObjectURL(objectUrl); }
            objectUrl = URL.createObjectURL(file);
            previews.forEach(function (box) {
                var img = box.querySelector('img');
                img.src = objectUrl;
                img.hidden = false;
            });
        });
    })();
</script>
@endpush
