<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $primaryKey = 'email_template_id';
    protected $fillable = [
        'template_key', 'name', 'description', 'project', 'trigger_type', 'email_layout_id', 'subject', 'preheader',
        'header_title', 'header_subtitle', 'body', 'button_text', 'button_url', 'footer_note', 'variables', 'is_active', 'starts_at',
    ];
    protected $casts = ['variables' => 'array', 'is_active' => 'boolean', 'is_system' => 'boolean', 'starts_at' => 'datetime'];

    public const PROJECTS = ['admin' => 'WWD Admin', 'business' => 'WWD Business', 'listing' => 'WWD Listing', 'all' => 'All projects'];
    public const TRIGGERS = ['event' => 'After an action', 'scheduled' => 'Scheduled', 'manual' => 'Sent manually'];
}
