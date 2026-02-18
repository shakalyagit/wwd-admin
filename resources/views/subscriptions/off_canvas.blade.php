<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filter Subscription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="subscription_filter">
            @csrf
            <div class="mb-3 apply-button">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-md-6">
                    <div class="form-group">
                        <label class="form-label">From date</label>
                        <input type="date" name="from_date" class="form-control">
                    </div>
                </div>
                <div class="mb-3 col-md-6">
                    <div class="form-group">
                        <label class="form-label">To date</label>
                        <input type="date" name="to_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Business name</label>
                    <input type="text" name="business_name" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Business URL</label>
                    <input type="text" name="business_website" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Select</option>
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
            </div>
        </form>
    </div>
</div>