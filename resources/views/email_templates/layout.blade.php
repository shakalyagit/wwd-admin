@extends('layouts.main')
@section('content')
@php
    $braces = fn($name) => '{' . '{' . $name . '}' . '}';
    $layout_vars = [
        'Content' => 'The template body (required)', 'HeaderTitle' => 'Template header title', 'HeaderSubtitle' => 'Template header subtitle',
        'Preheader' => 'Inbox preview text', 'FooterNote' => 'Template footer note (or default footer text)', 'Subject' => 'Email subject',
        'LogoURL' => 'Logo URL below', 'BrandColor' => 'Brand colour below', 'SiteURL' => 'Website below', 'SupportEmail' => 'Support email below', 'Year' => 'Current year',
    ];
@endphp
<div class="content-header">
    <div>
        <div class="page-breadcrumb">
            <a href="{{ route('email_templates') }}"><i class="bi bi-arrow-left"></i> Email templates</a>
        </div>
        <h5>Master layout</h5>
        <p class="page-subtitle">The header, signature and footer around every email. Changes apply to all templates in all three projects.</p>
    </div>
</div>
<x-flash />

<form action="{{ route('email_layout_action') }}" method="POST" id="layoutForm" novalidate>
    @csrf
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><h5>Brand</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="logo_url">Logo URL <span class="text-600 fw-normal">(public image address, shown on the red header)</span></label>
                            <input type="url" name="logo_url" id="logo_url" class="form-control @error('logo_url') is-invalid @enderror" value="{{ old('logo_url', $layout->logo_url) }}">
                            @error('logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="brand_color">Brand colour</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="brand_color_picker" value="{{ old('brand_color', $layout->brand_color) }}" aria-label="Pick colour">
                                <input type="text" name="brand_color" id="brand_color" class="form-control font-monospace @error('brand_color') is-invalid @enderror" value="{{ old('brand_color', $layout->brand_color) }}" maxlength="7">
                            </div>
                            @error('brand_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="site_url">Website</label>
                            <input type="url" name="site_url" id="site_url" class="form-control @error('site_url') is-invalid @enderror" value="{{ old('site_url', $layout->site_url) }}">
                            @error('site_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="support_email">Support email</label>
                            <input type="email" name="support_email" id="support_email" class="form-control @error('support_email') is-invalid @enderror" value="{{ old('support_email', $layout->support_email) }}">
                            @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="footer_text">Default footer text</label>
                            <input type="text" name="footer_text" id="footer_text" class="form-control" value="{{ old('footer_text', $layout->footer_text) }}" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header justify-content-between">
                    <h5>Layout HTML</h5>
                    <span class="small text-600">For advanced edits</span>
                </div>
                <div class="card-body">
                    <textarea name="html" id="html" rows="16" class="form-control font-monospace code-area @error('html') is-invalid @enderror" spellcheck="false">{{ old('html', $layout->html) }}</textarea>
                    @error('html')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label class="form-label mt-3" for="button_html">Button HTML <span class="text-600 fw-normal">(uses {{ $braces('ButtonText') }} and {{ $braces('ButtonURL') }})</span></label>
                    <textarea name="button_html" id="button_html" rows="4" class="form-control font-monospace code-area" spellcheck="false">{{ old('button_html', $layout->button_html) }}</textarea>
                    <details class="mt-3">
                        <summary class="small fw-medium">Placeholders the layout can use</summary>
                        <table class="table table-sm mt-2 mb-0 var-table">
                            @foreach ($layout_vars as $name => $meaning)
                            <tr><td><code>{{ $braces($name) }}</code></td><td class="text-600">{{ $meaning }}</td></tr>
                            @endforeach
                        </table>
                    </details>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save layout</button>
                <a href="{{ route('email_templates') }}" class="btn btn-light">Cancel</a>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card preview-card">
                <div class="card-header justify-content-between">
                    <h5>Preview <span class="text-600 fw-normal small">with “Business approved”</span></h5>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Preview width">
                        <button type="button" class="btn btn-light active" data-width="600"><i class="bi bi-display"></i> Desktop</button>
                        <button type="button" class="btn btn-light" data-width="375"><i class="bi bi-phone"></i> Mobile</button>
                    </div>
                </div>
                <div class="preview-stage">
                    <iframe id="previewFrame" title="Layout preview" sandbox="allow-same-origin"></iframe>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    (function() {
        var form = document.getElementById('layoutForm');
        var frame = document.getElementById('previewFrame');
        var timer;

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        function refresh_preview() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                $.post(@json(route('email_layout_preview')), $(form).serialize(), function(email) {
                    frame.srcdoc = email.html;
                });
            }, 400);
        }

        // Keep the colour picker and the text box in sync
        var picker = document.getElementById('brand_color_picker');
        var color = document.getElementById('brand_color');
        picker.addEventListener('input', function() { color.value = picker.value; refresh_preview(); });
        color.addEventListener('input', function() {
            if (/^#[0-9a-fA-F]{6}$/.test(color.value)) {
                picker.value = color.value;
            }
        });

        $('[data-width]').on('click', function() {
            $('[data-width]').removeClass('active');
            $(this).addClass('active');
            frame.style.width = this.dataset.width + 'px';
        });

        form.addEventListener('input', refresh_preview);
        refresh_preview();
    })();
</script>
@endsection
