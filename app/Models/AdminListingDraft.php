<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One business from an Excel import, reviewed and approved before it becomes a live business (admin-only)
class AdminListingDraft extends Model
{
    protected $primaryKey = 'admin_listing_draft_id';

    public const STATUSES = ['uploaded' => 'Uploaded', 'needs_fix' => 'Needs fix', 'approved' => 'Approved', 'skipped' => 'Skipped'];

    // Fields an admin can edit on the review page (same names as the Add business form)
    public const FIELDS = ['business_name', 'business_website', 'business_email', 'category_id', 'country_code', 'business_phone',
        'business_desc', 'street_line_1', 'street_line_2', 'city', 'province_state_territory', 'postal_code', 'ref_country_id',
        'source_name', 'source_url'];

    protected $guarded = ['admin_listing_draft_id'];

    protected $casts = ['problems' => 'array', 'approved_at' => 'datetime'];
}
