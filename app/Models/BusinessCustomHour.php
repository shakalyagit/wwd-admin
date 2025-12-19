<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCustomHour extends Model
{
    use HasFactory;
    protected $primaryKey = 'business_custom_hours_id';
    protected $fillable = [
        'business_id',
        'day_of_week',
        'name',
        'start_ts',
        'end_ts'
    ];
}
