<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filter Business</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="business_filter">
            @csrf
            <div class="mb-3 apply-button">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Business verified</label>
                    <select name="business_verified" class="form-select">
                        <option value="">Select</option>
                        <option value="1">Verified</option>
                        <option value="0">Not Verified</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Admin verified</label>
                    <select name="admin_verified" class="form-select">
                        <option value="">Select</option>
                        <option value="0">Pending</option>
                        <option value="1">Verified</option>
                        <option value="2">Reject</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Is admin posted</label>
                    <select name="is_admin_posted" class="form-select">
                        <option value="">Select</option>
                        <option value="0">User posted</option>
                        <option value="1">Admin posted</option>
                    </select>
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
                    <label class="form-label">Business Email</label>
                    <input type="text" name="business_email" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <div class="form-group">
                    <label class="form-label">Business URL</label>
                    <input type="text" name="business_url" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">Select</option>
                    @foreach($parents as $parent)
                    <option value="{{ $parent->category_id }}">{{ $parent->cat_name }}</option>
                    @foreach($children->where('parent_cat_id', $parent->category_id) as $child)
                    <option value="{{ $child->category_id }}"> {{ $parent->cat_name }} » {{ $child->cat_name }}</option>
                    @endforeach
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">Select</option>
                    <option value="P">Paid</option>
                    <option value="F">Free</option>
                </select>
            </div>
        </form>
    </div>
</div>