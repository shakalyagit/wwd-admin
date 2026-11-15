<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide settings for the public directory site (worldweb-directory.com): social links and
 * WWD's Google reviews. Shared table `site_settings`, created by the directory project's
 * database/sql/2026_11_15_create_site_settings.sql (no migration). The directory site caches
 * these values for 10 minutes.
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';
    protected $fillable = ['setting_key', 'setting_value', 'updated_by'];

    /** The keys this page edits, with their labels. */
    public const SOCIAL = [
        'social_facebook_url'  => 'Facebook',
        'social_linkedin_url'  => 'LinkedIn',
        'social_x_url'         => 'X (Twitter)',
        'social_instagram_url' => 'Instagram',
        'social_youtube_url'   => 'YouTube',
    ];

    public static function values(): array
    {
        return static::query()->pluck('setting_value', 'setting_key')->all();
    }

    public static function put(string $key, ?string $value, ?int $user_id): void
    {
        static::updateOrCreate(['setting_key' => $key], ['setting_value' => $value, 'updated_by' => $user_id]);
    }
}
