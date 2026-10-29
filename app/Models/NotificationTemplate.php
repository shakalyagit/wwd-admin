<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;
    protected $primaryKey = 'notification_template_id';
    protected $fillable = ['subject', 'body', 'is_active', 'starts_at'];
    protected $casts = ['starts_at' => 'datetime', 'is_active' => 'boolean'];
}
