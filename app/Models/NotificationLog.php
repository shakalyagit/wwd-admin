<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;
    protected $primaryKey = 'notification_log_id';
    protected $fillable = ['type', 'business_id', 'recipient_email', 'subject', 'step', 'trigger', 'status', 'error', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];
}
