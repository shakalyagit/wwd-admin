@extends('layouts.main')
@section('content')
<div class="content-header">
    <h5 class="pull-left">Edit Template: {{ $template->name }}</h5>
    <a href="{{ route('notification_templates') }}" class="btn btn-light pull-right"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="clear"></div>
</div>
<x-flash />
<div class="card mt-2">
    <div class="card-body">
        <form action="{{ route('edit_notification_template_action', encrypt($template->notification_template_id)) }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                    value="{{ old('subject', $template->subject) }}" maxlength="255" required>
                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Body</label>
                <textarea name="body" id="template_body" class="form-control" rows="14">{{ old('body', $template->body) }}</textarea>
                @error('body')<div class="text-danger fs-10">{{ $message }}</div>@enderror
                <div class="form-text">
                    Placeholders: <code>{business_name}</code>, <code>{owner_name}</code>, <code>{business_email}</code>,
                    <code>{claim_url}</code>. The WWD header, sign-off and footer are added automatically.
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Auto-send for submissions from</label>
                    <input type="date" name="starts_at" class="form-control"
                        value="{{ old('starts_at', optional($template->starts_at)->format('Y-m-d')) }}">
                    <div class="form-text">Leave empty to include all past unverified businesses.</div>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-center">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active (scheduler sends this)</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save template</button>
        </form>
    </div>
</div>
@endsection
@push('vendor_scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
@endpush
@section('scripts')
<script>
    tinymce.init({
        selector: '#template_body',
        height: 400,
        menubar: false,
        plugins: 'link lists code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | alignleft aligncenter | code',
        // Keep {claim_url} as-is inside links instead of turning it into a relative URL
        convert_urls: false,
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });
</script>
@endsection
