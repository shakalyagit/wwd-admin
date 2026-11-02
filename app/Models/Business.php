<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    protected $primaryKey = 'business_id';
    protected $fillable = ['is_admin_verified'];

    // Reasons offered in the reject popup; the label is sent to the owner as {{Reason}}
    public const REJECTION_REASONS = [
        'website_not_opening' => 'Website is not opening',
        'not_english'         => 'Website is not in English',
        'adult_content'       => 'Website has sexual or adult content',
        'gambling'            => 'Website is a gambling site',
        'spam'                => 'Spam or low-quality website',
        'duplicate'           => 'Duplicate listing (this business is already listed)',
        'misleading'          => 'Business information is incorrect or misleading',
        'other'               => 'Other reason',
    ];
}
