<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAddress extends Model
{
    use HasFactory;
    protected $primaryKey = 'business_address_id';
    protected $fillable = [
        'business_id',
        'street_line_1',
        'street_line_2',
        'city',
        'province_state_territory',
        'ref_country_id',
        'postal_code',
        'ref_address_type_id'
    ];
}
