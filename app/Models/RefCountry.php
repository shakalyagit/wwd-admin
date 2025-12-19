<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefCountry extends Model
{
    use HasFactory;
    protected $primaryKey = 'ref_countries_id';
}
