<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filter Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="notification_filter">
            @csrf
            <div class="mb-3 apply-button">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Select</option>
                        @foreach ($types as $type => $name)
                        <option value="{{ $type }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Select</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Sent by</label>
                    <select name="trigger" class="form-select">
                        <option value="">Select</option>
                        <option value="auto">Scheduler</option>
                        <option value="manual">Manual resend</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Business name</label>
                    <input type="text" name="business_name" class="form-control" placeholder="Business name">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" name="recipient_email" class="form-control" placeholder="Email">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">From date</label>
                    <input type="date" name="from_date" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">To date</label>
                    <input type="date" name="to_date" class="form-control">
                </div>
            </div>
        </form>
    </div>
</div>
