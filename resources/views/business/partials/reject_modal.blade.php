{{--
    Reject popup shared by Edit Business and the Ready-for-approval list.
    Open it with a button that has data-bs-toggle="modal" data-bs-target="#rejectModal" and
    data-reject-url, data-business-name, data-business-email. Optional $return ('queue') is sent back to the controller.
    Optional $business pre-fills the popup (single-business pages), so it is right even before the script runs.
--}}
@php
    $reject_url = old('reject_url', isset($business) ? route('business_reject', $business->business_id) : '');
    $reject_name = old('reject_name', isset($business) ? trim($business->business_name) : '');
    $reject_email = old('reject_email', isset($business) ? $business->business_email : '');
    $rejected_template_url = route('email_templates', ['q' => 'business_rejected']);
@endphp
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ $reject_url }}" id="rejectForm">
            @csrf
            <input type="hidden" name="reject_url" value="{{ $reject_url }}">
            <input type="hidden" name="reject_name" value="{{ $reject_name }}">
            <input type="hidden" name="reject_email" value="{{ $reject_email }}">
            @if (!empty($return))
            <input type="hidden" name="return" value="{{ $return }}">
            @endif
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="rejectModalLabel">Reject <span data-reject-name>{{ $reject_name }}</span></h5>
                    <div class="small text-600">Choose why. The owner will see this reason.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="reason-list" role="radiogroup" aria-label="Reason">
                    @foreach (\App\Models\Business::REJECTION_REASONS as $key => $label)
                    <label class="reason-option">
                        <input type="radio" name="reason" value="{{ $key }}" @checked(old('reason') === $key) required>
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <label class="form-label mt-3" for="reason_note" id="reasonNoteLabel">Note to the owner <span class="text-600 fw-normal">(optional)</span></label>
                <textarea name="reason_note" id="reason_note" rows="2" maxlength="500" class="form-control @error('reason_note') is-invalid @enderror"
                    placeholder="e.g. The website shows a server error since 25 Oct.">{{ old('reason_note') }}</textarea>
                @error('reason_note')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="reject-mail-note mt-3" data-reject-mail-note>
                    @if ($reject_url !== '')
                    @if ($reject_email)
                    <i class="bi bi-envelope"></i> An email using the <a href="{{ route('email_templates', ['q' => 'business_rejected']) }}" target="_blank">Business rejected</a> template will be sent to <strong>{{ $reject_email }}</strong>.
                    @else
                    <i class="bi bi-exclamation-triangle"></i> This business has no email address, so no email will be sent.
                    @endif
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="rejectSubmit" disabled><i class="bi bi-x-circle"></i> <span>Reject</span></button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Reject popup: filled from the button that opened it; submit needs a reason, "Other" needs a written note
    (function() {
        var modal = document.getElementById('rejectModal');
        var form = document.getElementById('rejectForm');
        var note = document.getElementById('reason_note');
        var submit = document.getElementById('rejectSubmit');
        var label = document.getElementById('reasonNoteLabel');
        var template_url = @json($rejected_template_url);

        function set_business(url, name, email) {
            form.action = url;
            form.elements.reject_url.value = url;
            form.elements.reject_name.value = name;
            form.elements.reject_email.value = email;
            modal.querySelector('[data-reject-name]').textContent = name;
            var mail_note = modal.querySelector('[data-reject-mail-note]');
            if (email) {
                mail_note.innerHTML = '<i class="bi bi-envelope"></i> An email using the <a target="_blank"></a> template will be sent to <strong></strong>.';
                mail_note.querySelector('a').href = template_url;
                mail_note.querySelector('a').textContent = 'Business rejected';
                mail_note.querySelector('strong').textContent = email;
            } else {
                mail_note.innerHTML = '<i class="bi bi-exclamation-triangle"></i> This business has no email address, so no email will be sent.';
            }
            submit.querySelector('span').textContent = email ? 'Reject and email owner' : 'Reject';
        }

        function update() {
            var chosen = form.querySelector('input[name=reason]:checked');
            var is_other = chosen && chosen.value === 'other';
            label.innerHTML = is_other ? 'Write the reason <span class="text-danger">*</span>' : 'Note to the owner <span class="text-600 fw-normal">(optional)</span>';
            note.required = is_other;
            submit.disabled = !chosen || (is_other && note.value.trim() === '');
        }

        modal.addEventListener('show.bs.modal', function(e) {
            var button = e.relatedTarget;
            if (button && button.dataset.rejectUrl) {
                // A different business than last time: start clean
                if (form.action !== button.dataset.rejectUrl) {
                    form.reset();
                }
                set_business(button.dataset.rejectUrl, button.dataset.businessName, button.dataset.businessEmail || '');
                update();
            }
        });
        form.addEventListener('change', update);
        note.addEventListener('input', update);
        form.addEventListener('submit', function() {
            submit.disabled = true;
            submit.querySelector('span').textContent = 'Rejecting…';
        });

        // Validation failed on the server: reopen for the same business with the errors
        @if ($errors->has('reason') || $errors->has('reason_note'))
        set_business(@json(old('reject_url')), @json(old('reject_name')), @json(old('reject_email')));
        update();
        new bootstrap.Modal(modal).show();
        @endif
    })();
</script>
@endpush
