<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;
    protected $primaryKey = 'notification_log_id';
    protected $fillable = ['type', 'business_id', 'recipient_email', 'subject', 'step', 'trigger', 'status', 'error',
        'tracking_token', 'target_url', 'sent_at', 'clicked_at', 'click_count'];
    protected $casts = ['sent_at' => 'datetime', 'clicked_at' => 'datetime'];
}
