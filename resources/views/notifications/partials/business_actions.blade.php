{{-- Resend + pause/resume buttons for one business. Needs $business_id and $paused. --}}
<form action="{{ route('notification_resend', encrypt($business_id)) }}" method="post" class="d-inline"
    onsubmit="return confirm('Send the verification reminder to this business now?');">
    @csrf
    <button type="submit" class="badge bg-info rounded rounded-circle border-0"
        style="padding-top: 7px; padding-bottom: 7px;" title="Resend now"><i class="bi bi-send fs-9"></i></button>
</form>
<form action="{{ route('notification_pause_toggle', encrypt($business_id)) }}" method="post" class="d-inline"
    onsubmit="return confirm('{{ $paused ? 'Resume' : 'Pause' }} notifications for this business?');">
    @csrf
    @if ($paused)
    <button type="submit" class="badge bg-success rounded rounded-circle border-0"
        style="padding-top: 7px; padding-bottom: 7px;" title="Resume notifications"><i class="bi bi-play-fill fs-9"></i></button>
    @else
    <button type="submit" class="badge bg-warning rounded rounded-circle border-0"
        style="padding-top: 7px; padding-bottom: 7px;" title="Pause notifications"><i class="bi bi-pause-fill fs-9"></i></button>
    @endif
</form>
