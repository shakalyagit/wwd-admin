<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Shared table: reviews are written on the Listing site and answered by owners in WWD Business. Read-only here.
class BusinessReview extends Model
{
    protected $table = 'business_reviews';
}
