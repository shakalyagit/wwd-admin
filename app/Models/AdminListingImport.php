<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One uploaded Excel file of businesses (admin-only)
class AdminListingImport extends Model
{
    protected $primaryKey = 'admin_listing_import_id';

    protected $fillable = ['file_name', 'uploaded_by', 'total_rows', 'uploaded_rows', 'needs_fix_rows', 'skipped_rows', 'skipped_details'];

    protected $casts = ['skipped_details' => 'array'];
}
