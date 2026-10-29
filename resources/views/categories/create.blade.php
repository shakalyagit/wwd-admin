@extends('layouts.main')
@section('content')
@php
    $type = old('type', $selected_parent ? 'sub' : 'main');
    $parent_id = old('parent_cat_id', $selected_parent->category_id ?? '');
@endphp
<div class="content-header">
    <div>
        <div class="page-breadcrumb">
            <a href="{{ route('category_count') }}"><i class="bi bi-arrow-left"></i> Categories</a>
        </div>
        <h5>Add category</h5>
        <p class="page-subtitle">Create a main category, or a sub-category inside one. Duplicate names are blocked.</p>
    </div>
</div>
<x-flash />

<form action="{{ route('category_store') }}" method="POST" id="categoryForm" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header"><h5>Category</h5></div>
                <div class="card-body">
                    <label class="form-label">Type</label>
                    <div class="type-switch mb-3" role="radiogroup">
                        <label class="type-option">
                            <input type="radio" name="type" value="main" @checked($type === 'main')>
                            <span><i class="bi bi-diagram-3"></i><strong>Main category</strong><small>Top level, e.g. "Food & Dining"</small></span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="type" value="sub" @checked($type === 'sub')>
                            <span><i class="bi bi-diagram-2"></i><strong>Sub-category</strong><small>Inside a main category, e.g. "Bakeries"</small></span>
                        </label>
                    </div>

                    <div class="mb-3 sub-only">
                        <label class="form-label" for="parent_cat_id">Main category</label>
                        <select name="parent_cat_id" id="parent_cat_id" class="form-select @error('parent_cat_id') is-invalid @enderror">
                            <option value="">Choose a main category…</option>
                            @foreach ($parents as $parent)
                            <option value="{{ $parent->category_id }}" @selected((string) $parent_id === (string) $parent->category_id)>{{ trim($parent->cat_name) }}</option>
                            @endforeach
                        </select>
                        @error('parent_cat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cat_name">Name</label>
                        <input type="text" name="cat_name" id="cat_name" maxlength="100" autocomplete="off"
                            class="form-control @error('cat_name') is-invalid @enderror" value="{{ old('cat_name') }}"
                            placeholder="e.g. Bakeries & Cafés" required>
                        <div class="name-check" id="nameCheck" aria-live="polite">
                            @error('cat_name')<span class="is-bad"><i class="bi bi-x-circle"></i> {{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="cat_info">Description</label>
                        <textarea name="cat_info" id="cat_info" rows="4" maxlength="2000"
                            class="form-control @error('cat_info') is-invalid @enderror"
                            placeholder="What kind of businesses belong here? Shown on the category page.">{{ old('cat_info') }}</textarea>
                        @error('cat_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5>Search engines (SEO)</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label" for="cat_meta_desc">Meta description</label>
                            <span class="form-text m-0" id="metaCount">0 / 160</span>
                        </div>
                        <textarea name="cat_meta_desc" id="cat_meta_desc" rows="2" maxlength="255"
                            class="form-control @error('cat_meta_desc') is-invalid @enderror"
                            placeholder="One or two sentences shown in Google results.">{{ old('cat_meta_desc') }}</textarea>
                        @error('cat_meta_desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Aim for 120–160 characters.</div>
                    </div>
                    <div>
                        <label class="form-label" for="cat_keyword">Keywords</label>
                        <input type="text" name="cat_keyword" id="cat_keyword" maxlength="225"
                            class="form-control @error('cat_keyword') is-invalid @enderror" value="{{ old('cat_keyword') }}"
                            placeholder="bakery, cafe, coffee shop">
                        @error('cat_keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Comma separated.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-3 main-only">
                <div class="card-header"><h5>Display</h5></div>
                <div class="card-body">
                    <label class="form-label" for="cat_icon">Icon (SVG code)</label>
                    <div class="icon-field">
                        <div class="icon-preview" id="iconPreview"><i class="bi bi-image"></i></div>
                        <textarea name="cat_icon" id="cat_icon" rows="5" class="form-control font-monospace @error('cat_icon') is-invalid @enderror"
                            placeholder="<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; …>…</svg>" style="font-size: 12px;">{{ old('cat_icon') }}</textarea>
                    </div>
                    @error('cat_icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text mb-3">Optional. Paste an SVG icon, like the existing main categories use.</div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_feature" value="1" id="is_feature" @checked(old('is_feature'))>
                        <label class="form-check-label" for="is_feature">Featured category</label>
                    </div>
                    <div class="form-text">Featured main categories are highlighted on the public site.</div>
                </div>
            </div>

            <div class="card mb-3 sub-only">
                <div class="card-body small text-600">
                    <i class="bi bi-info-circle"></i> Sub-categories use the default icon, like all existing ones.
                </div>
            </div>

            <div class="card save-card">
                <div class="card-body">
                    <div class="url-preview">
                        <span class="text-600 small">Web address</span>
                        <code id="pathPreview">—</code>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="saveButton"><i class="bi bi-check2"></i> Save category</button>
                    <button type="submit" name="after" value="another" class="btn btn-light w-100 mt-2" id="saveAnotherButton">Save and add another</button>
                    <a href="{{ route('category_count') }}" class="btn btn-link w-100 mt-1">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    (function() {
        var form = document.getElementById('categoryForm');
        var name = document.getElementById('cat_name');
        var parent = document.getElementById('parent_cat_id');
        var check = document.getElementById('nameCheck');
        var path_preview = document.getElementById('pathPreview');
        var buttons = [document.getElementById('saveButton'), document.getElementById('saveAnotherButton')];
        var is_duplicate = false;
        var request_id = 0;
        var timer;

        function type() {
            return form.querySelector('input[name=type]:checked').value;
        }

        function show_type() {
            var sub = type() === 'sub';
            form.querySelectorAll('.sub-only').forEach(function(el) { el.hidden = !sub; });
            form.querySelectorAll('.main-only').forEach(function(el) { el.hidden = sub; });
            run_check();
        }

        // Ask the server whether this name is free, and what the URL will be
        function run_check() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                var value = name.value.trim();
                var parent_id = type() === 'sub' ? parent.value : '';
                if (value === '' || (type() === 'sub' && parent_id === '')) {
                    set_status(null, value === '' ? '' : 'Choose a main category to check this name.');
                    path_preview.textContent = '—';
                    return;
                }
                var id = ++request_id;
                $.get(@json(route('category_check_name')), { name: value, parent: parent_id }, function(response) {
                    if (id !== request_id) {
                        return;
                    }
                    path_preview.textContent = response.path ? '/' + response.path : '—';
                    set_status(response.duplicate, response.message);
                });
            }, 250);
        }

        function set_status(duplicate, message) {
            is_duplicate = duplicate === true;
            name.classList.toggle('is-invalid', is_duplicate);
            name.classList.toggle('is-valid', duplicate === false);
            buttons.forEach(function(b) { b.disabled = is_duplicate; });
            if (duplicate === true) {
                check.innerHTML = '<span class="is-bad"><i class="bi bi-x-circle"></i> ' + $('<div>').text(message).html() + '</span>';
            } else if (duplicate === false) {
                check.innerHTML = '<span class="is-good"><i class="bi bi-check-circle"></i> Name is available</span>';
            } else {
                check.textContent = message || '';
            }
        }

        // Live SVG preview (rendered as an image, so scripts in it can't run here)
        var icon = document.getElementById('cat_icon');
        var icon_preview = document.getElementById('iconPreview');
        function show_icon() {
            var svg = icon.value.trim();
            if (/^<svg[\s>]/i.test(svg) && /<\/svg>$/i.test(svg)) {
                var img = new Image();
                img.alt = '';
                img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
                icon_preview.replaceChildren(img);
            } else {
                icon_preview.innerHTML = '<i class="bi bi-image"></i>';
            }
        }

        var meta = document.getElementById('cat_meta_desc');
        var meta_count = document.getElementById('metaCount');
        function count_meta() {
            var n = meta.value.length;
            meta_count.textContent = n + ' / 160';
            meta_count.classList.toggle('text-danger', n > 160);
        }

        form.querySelectorAll('input[name=type]').forEach(function(radio) {
            radio.addEventListener('change', show_type);
        });
        name.addEventListener('input', run_check);
        parent.addEventListener('change', run_check);
        icon.addEventListener('input', show_icon);
        meta.addEventListener('input', count_meta);
        form.addEventListener('submit', function(e) {
            if (is_duplicate) {
                e.preventDefault();
                name.focus();
            }
        });

        show_type();
        show_icon();
        count_meta();
        @if (!$errors->has('cat_name'))
        if (name.value.trim() === '') {
            name.focus();
        }
        @endif
    })();
</script>
@endsection
