<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Businesses an admin listed for outreach (admin-only data; the business row is in businesses)
class AdminListing extends Model
{
    protected $primaryKey = 'admin_listing_id';

    protected $fillable = ['business_id', 'source_name', 'source_url', 'added_by', 'claimed_at'];

    protected $casts = ['claimed_at' => 'datetime'];
}
