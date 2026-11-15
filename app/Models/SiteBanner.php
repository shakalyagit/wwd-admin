<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Photos for the hero at the top of the public home page (worldweb-directory.com). Active banners rotate there.
 * Shared table `site_banners`, created by the directory project's database/sql/2026_11_15_create_site_banners.sql
 * (no migration). Image files are in the shared disk under banner/. The directory site caches the list for 10 minutes.
 */
class SiteBanner extends Model
{
    protected $table = 'site_banners';
    protected $primaryKey = 'banner_id';
    protected $fillable = [
        'headline', 'subtext', 'button_text', 'button_url', 'credit_text', 'credit_url',
        'image_file', 'image_file_small', 'focus', 'sort_order', 'is_active', 'starts_on', 'ends_on', 'updated_by',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'starts_on' => 'date',
        'ends_on'   => 'date',
    ];

    /** The part of the photo that stays in view when a screen crops it. */
    public const FOCUS = ['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'];

    /** live, scheduled, ended or off: what the home page does with it today. */
    public function status(): string
    {
        if (!$this->is_active) {
            return 'off';
        }
        $today = now()->startOfDay();
        if ($this->starts_on && $this->starts_on->gt($today)) {
            return 'scheduled';
        }
        if ($this->ends_on && $this->ends_on->lt($today)) {
            return 'ended';
        }
        return 'live';
    }
}
