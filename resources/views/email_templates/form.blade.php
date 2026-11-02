@extends('layouts.main')
@section('content')
@php
    $is_new = !$template->exists;
    $is_system = (bool) $template->is_system;
    $variables = old('variables', $template->variables ?? []);
    $action = $is_new ? route('email_template_store') : route('edit_email_template_action', encrypt($template->email_template_id));
    $global_vars = ['Year' => 'Current year', 'SiteURL' => 'Website address', 'SupportEmail' => 'Support email', 'LogoURL' => 'Logo image URL'];
    // Builds a "Name" placeholder (double curly braces) without writing them literally, which Blade would treat as an echo
    $braces = fn($name) => '{' . '{' . $name . '}' . '}';
@endphp
<div class="content-header">
    <div>
        <div class="page-breadcrumb">
            <a href="{{ route('email_templates') }}"><i class="bi bi-arrow-left"></i> Email templates</a>
        </div>
        <h5>{{ $is_new ? 'New email template' : $template->name }}</h5>
        @unless ($is_new)
        <p class="page-subtitle"><code class="tpl-key">{{ $template->template_key }}</code>
            @if ($is_system)<span class="tpl-tag">System</span> Used by code. Content is editable; the key can’t change.@else<span class="tpl-tag is-custom">Custom</span>@endif
        </p>
        @endunless
    </div>
    @if (!$is_new && !$is_system)
    <form action="{{ route('delete_email_template', encrypt($template->email_template_id)) }}" method="POST"
        onsubmit="return confirm('Delete this template? Projects using its key will stop sending it.');">
        @csrf
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
    @endif
</div>
<x-flash />

<form action="{{ $action }}" method="POST" id="templateForm" novalidate>
    @csrf
    @unless ($is_new)
    <input type="hidden" name="email_template_id" value="{{ $template->email_template_id }}">
    @endunless
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card mb-3">
                <div class="card-header justify-content-between">
                    <h5>Template</h5>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $is_new ? true : $template->is_active))>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $template->name) }}" maxlength="150" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="template_key">Key <span class="text-600 fw-normal">(used by code)</span></label>
                            @if ($is_system)
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="text" class="form-control font-monospace" value="{{ $template->template_key }}" readonly>
                            </div>
                            @else
                            <input type="text" name="template_key" id="template_key" class="form-control font-monospace @error('template_key') is-invalid @enderror"
                                value="{{ old('template_key', $template->template_key) }}" maxlength="80" placeholder="spring_offer" required>
                            @error('template_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="project">Sent by</label>
                            <select name="project" id="project" class="form-select">
                                @foreach (\App\Models\EmailTemplate::PROJECTS as $key => $label)
                                <option value="{{ $key }}" @selected(old('project', $template->project) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="trigger_type">When</label>
                            <select name="trigger_type" id="trigger_type" class="form-select">
                                @foreach (\App\Models\EmailTemplate::TRIGGERS as $key => $label)
                                <option value="{{ $key }}" @selected(old('trigger_type', $template->trigger_type) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description <span class="text-600 fw-normal">(when is it sent?)</span></label>
                            <textarea name="description" id="description" rows="2" class="form-control" maxlength="1000">{{ old('description', $template->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5>Email content</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control tpl-field @error('subject') is-invalid @enderror" value="{{ old('subject', $template->subject) }}" maxlength="255" required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="preheader">Preview text <span class="text-600 fw-normal">(shown after the subject in the inbox)</span></label>
                        <input type="text" name="preheader" id="preheader" class="form-control tpl-field" value="{{ old('preheader', $template->preheader) }}" maxlength="255">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="header_title">Header title</label>
                            <input type="text" name="header_title" id="header_title" class="form-control tpl-field" value="{{ old('header_title', $template->header_title) }}" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="header_subtitle">Header subtitle</label>
                            <input type="text" name="header_subtitle" id="header_subtitle" class="form-control tpl-field" value="{{ old('header_subtitle', $template->header_subtitle) }}" maxlength="255">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="body">Body</label>
                        <textarea name="body" id="body" rows="14" class="form-control @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                        @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">The greeting goes here; the WWD header, “Warm regards” and footer come from the master layout.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label" for="button_text">Button text</label>
                            <input type="text" name="button_text" id="button_text" class="form-control tpl-field @error('button_text') is-invalid @enderror" value="{{ old('button_text', $template->button_text) }}" maxlength="100" placeholder="e.g. Login & Update Your Profile">
                            @error('button_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="button_url">Button link</label>
                            <input type="text" name="button_url" id="button_url" class="form-control tpl-field font-monospace @error('button_url') is-invalid @enderror" value="{{ old('button_url', $template->button_url) }}" maxlength="255" placeholder="{{ $braces('LoginURL') }}">
                            @error('button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="footer_note">Footer note <span class="text-600 fw-normal">(why they got this email)</span></label>
                        <input type="text" name="footer_note" id="footer_note" class="form-control tpl-field" value="{{ old('footer_note', $template->footer_note) }}" maxlength="255" placeholder="Leave empty to use the layout’s default footer text">
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header justify-content-between">
                    <h5>Placeholders</h5>
                    <span class="text-600 small">Click one to insert it where your cursor is</span>
                </div>
                <div class="card-body">
                    @if ($is_system)
                    <p class="small text-600 mb-2"><i class="bi bi-lock"></i> These values are provided by the code that sends this email. The sample is used for the preview and test email.</p>
                    <table class="table table-sm mb-3 var-table">
                        <thead><tr><th>Placeholder</th><th>Sample</th><th>Meaning</th></tr></thead>
                        <tbody>
                            @foreach ($variables as $variable)
                            <tr>
                                <td><button type="button" class="var-chip" data-var="{{ $variable['name'] }}">{{ $braces($variable['name']) }}</button></td>
                                <td class="text-600">{{ $variable['sample'] ?? '' }}</td>
                                <td class="text-600">{{ $variable['description'] ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="small text-600 mb-2">List the values the sending code will provide. Names use letters only, e.g. <code>BusinessName</code>; write them as <code>{{ $braces('BusinessName') }}</code> in the email.</p>
                    <div id="varRows">
                        @foreach ($variables as $i => $variable)
                        <div class="row g-2 mb-2 var-row">
                            <div class="col-4"><input type="text" name="variables[{{ $i }}][name]" class="form-control form-control-sm font-monospace var-name" value="{{ $variable['name'] ?? '' }}" placeholder="Name"></div>
                            <div class="col-3"><input type="text" name="variables[{{ $i }}][sample]" class="form-control form-control-sm" value="{{ $variable['sample'] ?? '' }}" placeholder="Sample value"></div>
                            <div class="col-4"><input type="text" name="variables[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $variable['description'] ?? '' }}" placeholder="Meaning"></div>
                            <div class="col-1"><button type="button" class="btn btn-light btn-sm w-100 var-remove" title="Remove"><i class="bi bi-x-lg"></i></button></div>
                        </div>
                        @endforeach
                    </div>
                    @error('variables.*.name')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <button type="button" class="btn btn-light btn-sm mb-3" id="addVar"><i class="bi bi-plus-lg"></i> Add placeholder</button>
                    <div class="mb-2" id="customChips"></div>
                    @endif
                    <div class="small text-600 mb-1">Available in every template:</div>
                    @foreach ($global_vars as $name => $meaning)
                    <button type="button" class="var-chip is-global" data-var="{{ $name }}" title="{{ $meaning }}">{{ $braces($name) }}</button>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $is_new ? 'Create template' : 'Save changes' }}</button>
                <a href="{{ route('email_templates') }}" class="btn btn-light">Cancel</a>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card preview-card">
                <div class="card-header justify-content-between">
                    <h5>Preview</h5>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Preview width">
                        <button type="button" class="btn btn-light active" data-width="600"><i class="bi bi-display"></i> Desktop</button>
                        <button type="button" class="btn btn-light" data-width="375"><i class="bi bi-phone"></i> Mobile</button>
                    </div>
                </div>
                <div class="preview-subject"><span class="text-600">Subject:</span> <strong id="previewSubject">…</strong></div>
                <div class="preview-stage">
                    <iframe id="previewFrame" title="Email preview" sandbox="allow-same-origin"></iframe>
                </div>
                <div class="card-footer">
                    <label class="form-label small mb-1" for="test_email">Send a test email (uses the sample values)</label>
                    <div class="input-group">
                        <input type="email" id="test_email" class="form-control" value="{{ Auth::user()->email }}" placeholder="you@example.com">
                        <button type="button" class="btn btn-primary" id="sendTest"><i class="bi bi-send"></i> Send test</button>
                    </div>
                    <div class="small mt-2" id="testResult" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('vendor_scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
@endpush

@section('scripts')
<script>
    (function() {
        var form = document.getElementById('templateForm');
        var frame = document.getElementById('previewFrame');
        var last_field = null;
        var timer;

        // Placeholder text built from single braces so Blade leaves it alone
        function braces(name) {
            return '{' + '{' + name + '}' + '}';
        }

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        function form_data() {
            if (window.tinymce && tinymce.get('body')) {
                tinymce.get('body').save();
            }
            return $(form).serializeArray().filter(function(field) { return field.name !== '_token'; });
        }

        // Render the unsaved form on the server so the preview matches real emails exactly
        function refresh_preview() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                $.post(@json(route('email_template_preview')), form_data(), function(email) {
                    document.getElementById('previewSubject').textContent = email.subject || '(no subject)';
                    frame.srcdoc = email.html;
                });
            }, 500);
        }

        tinymce.init({
            selector: '#body',
            height: 380,
            menubar: false,
            plugins: 'link lists table code',
            toolbar: 'undo redo | blocks | bold italic underline forecolor | bullist numlist | link table | alignleft aligncenter | code',
            block_formats: 'Paragraph=p; Heading=h3',
            // Keep placeholders in links exactly as typed
            convert_urls: false,
            entity_encoding: 'raw',
            content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #333; line-height: 1.6; }',
            setup: function(editor) {
                editor.on('input change undo redo keyup', refresh_preview);
                editor.on('focus', function() { last_field = 'tinymce'; });
                editor.on('init', refresh_preview);
            }
        });

        form.addEventListener('input', refresh_preview);
        form.addEventListener('change', refresh_preview);
        form.addEventListener('focusin', function(e) {
            if (e.target.classList.contains('tpl-field')) {
                last_field = e.target;
            }
        });

        // Insert the placeholder into the last focused field (or the body)
        $(document).on('click', '.var-chip', function() {
            var text = braces(this.dataset.var);
            if (last_field && last_field !== 'tinymce') {
                var start = last_field.selectionStart, end = last_field.selectionEnd;
                last_field.value = last_field.value.slice(0, start) + text + last_field.value.slice(end);
                last_field.focus();
                last_field.setSelectionRange(start + text.length, start + text.length);
            } else {
                tinymce.get('body').insertContent(text);
            }
            refresh_preview();
        });

        // Desktop / mobile preview width
        $('[data-width]').on('click', function() {
            $('[data-width]').removeClass('active');
            $(this).addClass('active');
            frame.style.width = this.dataset.width + 'px';
        });

        $('#sendTest').on('click', function() {
            var button = $(this).prop('disabled', true);
            var result = $('#testResult').removeClass('text-danger text-success').text('Sending…');
            $.post(@json(route('email_template_test')), form_data().concat([{ name: 'test_email', value: $('#test_email').val() }]))
                .done(function(response) { result.addClass('text-success').text(response.message); })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && (xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0][0] : xhr.responseJSON.message);
                    result.addClass('text-danger').text(message || 'Could not send the test email.');
                })
                .always(function() { button.prop('disabled', false); });
        });

        // Custom templates: editable placeholder rows, with matching insert chips
        var rows = document.getElementById('varRows');
        if (rows) {
            function render_custom_chips() {
                var chips = document.getElementById('customChips');
                chips.innerHTML = '';
                rows.querySelectorAll('.var-name').forEach(function(input) {
                    var name = input.value.trim();
                    if (/^[A-Za-z][A-Za-z0-9_]*$/.test(name)) {
                        var chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = 'var-chip';
                        chip.dataset.var = name;
                        chip.textContent = braces(name);
                        chips.appendChild(chip);
                    }
                });
            }
            var next_index = rows.querySelectorAll('.var-row').length;
            document.getElementById('addVar').addEventListener('click', function() {
                var i = next_index++;
                var row = document.createElement('div');
                row.className = 'row g-2 mb-2 var-row';
                row.innerHTML = '<div class="col-4"><input type="text" name="variables[' + i + '][name]" class="form-control form-control-sm font-monospace var-name" placeholder="Name"></div>'
                    + '<div class="col-3"><input type="text" name="variables[' + i + '][sample]" class="form-control form-control-sm" placeholder="Sample value"></div>'
                    + '<div class="col-4"><input type="text" name="variables[' + i + '][description]" class="form-control form-control-sm" placeholder="Meaning"></div>'
                    + '<div class="col-1"><button type="button" class="btn btn-light btn-sm w-100 var-remove" title="Remove"><i class="bi bi-x-lg"></i></button></div>';
                rows.appendChild(row);
                row.querySelector('input').focus();
            });
            rows.addEventListener('click', function(e) {
                if (e.target.closest('.var-remove')) {
                    e.target.closest('.var-row').remove();
                    render_custom_chips();
                    refresh_preview();
                }
            });
            rows.addEventListener('input', render_custom_chips);
            render_custom_chips();
        }
    })();
</script>
@endsection
