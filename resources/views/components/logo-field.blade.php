{{-- Logo upload field used with <x-logo-editor> (same tool as WWD Business). Picking, dropping or pasting an image
     opens the editor; "Use this logo" puts a 500 x 500 PNG into the file input, so the form's normal submit uploads it. --}}
@props(['name' => 'logo', 'url' => null, 'initials' => 'WWD', 'error' => null])
<div data-logo-field data-previews="#logo_preview" data-ready-text="New logo ready. It is uploaded when you save.">
    <div class="logo-field">
        <span class="biz-logo" id="logo_preview">
            @if ($url)
            <img src="{{ $url }}" alt="Current logo">
            @else
            {{ $initials }}
            @endif
        </span>
        <label class="logo-drop" for="{{ $name }}_input" data-logo-drop>
            <b><i class="bi bi-upload"></i> <span data-logo-drop-label>{{ $url ? 'Replace logo' : 'Upload a logo' }}</span></b>
            <span>Drop an image here, click to choose a file, or paste it. JPG, PNG, WebP or SVG up to 5 MB.</span>
        </label>
        <input type="file" name="{{ $name }}" id="{{ $name }}_input" accept="image/png,image/jpeg,image/webp" class="visually-hidden">
    </div>
    @if ($error)
    <div class="invalid-feedback d-block field-error">{{ $error }}</div>
    @endif
    <div class="invalid-feedback d-block" data-logo-error role="alert" hidden></div>
    <input type="hidden" name="logo_pending" value="" data-logo-pending>
    <div class="logo-actions mt-2">
        <span class="logo-status small" data-logo-status></span>
        <button type="button" class="btn btn-link btn-sm px-0" data-logo-adjust hidden><i class="bi bi-sliders"></i> Adjust</button>
        {{ $slot }}
    </div>
</div>
