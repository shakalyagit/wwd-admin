<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLayout extends Model
{
    use HasFactory;
    protected $primaryKey = 'email_layout_id';
    protected $fillable = ['name', 'html', 'button_html', 'logo_url', 'brand_color', 'site_url', 'support_email', 'footer_text'];
    protected $casts = ['is_default' => 'boolean'];
}
