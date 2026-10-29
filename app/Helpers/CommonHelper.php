<?php

if (!function_exists('status_badge')) {
    function status_badge($status)
    {
        switch ($status) {
            case 1:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Verified';
                break;
            case 0:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-warning border border-warning';
                $text = 'Pending';
                break;
            case 2:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Reject';
                break;
            case 'no':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-warning border border-warning';
                $text = 'No';
                break;
            case 'yes':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Yes';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('users_status')) {
    function users_status($status)
    {
        switch ($status) {
            case 1:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Active';
                break;
            case 0:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Inactive';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('payment_badge')) {
    function payment_badge($status)
    {
        switch ($status) {
            case 'F':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-warning border border-warning';
                $text = 'Free';
                break;
            case 'P':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Paid';
                break;
            case 'C':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Cancel';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('subscription_status')) {
    function subscription_status($status)
    {
        switch ($status) {
            case 'CANCELLED':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Cancelled';
                break;
            case 'ACTIVE':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Active';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('notification_status_badge')) {
    function notification_status_badge($status)
    {
        switch ($status) {
            case 'sent':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-success border border-success';
                $text = 'Sent';
                break;
            case 'failed':
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-danger border border-danger';
                $text = 'Failed';
                break;
            default:
                $class = 'badge badge rounded-pill d-block p-2 badge-subtle-dark border border-dark';
                $text = 'N/A';
                break;
        }
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('business_logo_url')) {
    // Public URL of a business logo, or null. UPLOAD_URL points at the logo folder
    // (same rule as the edit business page).
    function business_logo_url($file_path)
    {
        if (empty($file_path)) {
            return null;
        }
        return rtrim(config('filesystems.disks.shared.url'), '/') . '/' . ltrim($file_path, '/');
    }
}

if (!function_exists('business_logo_thumb')) {
    // Logo thumbnail + business name. Falls back to an initial placeholder when there is
    // no logo or the image file is missing.
    function business_logo_thumb($business_name, $file_path)
    {
        $name = trim((string) $business_name);
        $initial = e(mb_strtoupper(mb_substr($name !== '' ? $name : '?', 0, 1)));
        $url = business_logo_url($file_path);
        $img = $url ? '<img src="' . e($url) . '" alt="" loading="lazy" onload="this.classList.add(\'is-loaded\')" onerror="this.remove()">' : '';

        return '<span class="biz-cell"><span class="biz-thumb">' . $initial . $img . '</span>'
            . '<span class="biz-name">' . e($name) . '</span></span>';
    }
}

if (!function_exists('business_profile_score')) {
    /**
     * Profile completeness score (max 100) and colour band for a business.
     * $b needs: is_claimed, is_admin_verified, business_phone, business_desc and the social *_url fields.
     * Green = submitted + owner verified + logo uploaded (ready for admin approval), Yellow = verified without logo,
     * Red = not verified. Admin approval is not part of the score.
     */
    function business_profile_score($b, $has_logo, $has_hours, $has_address)
    {
        $verified = $b->is_claimed == 1;
        $social_links = collect(['facebook_url', 'instragram_url', 'youtube_url', 'twitter_url', 'linkedin_url'])
            ->filter(fn($field) => trim((string) $b->$field) !== '')
            ->count();

        // [label, points earned, max points]
        $items = [
            ['Business submitted', 10, 10],
            ['Owner verified', $verified ? 20 : 0, 20],
            ['Logo uploaded', $has_logo ? 20 : 0, 20],
            ['Business hours', $has_hours ? 10 : 0, 10],
            ['Address with city', $has_address ? 10 : 0, 10],
            ['Phone number', trim((string) $b->business_phone) !== '' ? 10 : 0, 10],
            ['Description (50+ characters)', mb_strlen(trim((string) $b->business_desc)) >= 50 ? 10 : 0, 10],
            ['Social links (1 = 5, 2+ = 10)', $social_links >= 2 ? 10 : ($social_links == 1 ? 5 : 0), 10],
        ];

        return [
            'score' => array_sum(array_column($items, 1)),
            'band'  => $verified && $has_logo ? 'green' : ($verified ? 'yellow' : 'red'),
            'items' => $items,
        ];
    }
}

if (!function_exists('profile_score_badge')) {
    // Score circle for lists; hovering shows the checklist
    function profile_score_badge(array $profile)
    {
        $tooltip = collect($profile['items'])
            ->map(fn($item) => ($item[1] >= $item[2] ? '✓ ' : ($item[1] > 0 ? '◐ ' : '✗ ')) . $item[0] . ': ' . $item[1] . '/' . $item[2])
            ->join("\n");
        $labels = ['green' => 'Verified + logo, ready for approval', 'yellow' => 'Verified, logo missing', 'red' => 'Not verified'];

        return '<span class="score-ring is-' . $profile['band'] . '" style="--score: ' . $profile['score'] . '" title="'
            . e('Profile score ' . $profile['score'] . '/100 · ' . $labels[$profile['band']] . "\n" . $tooltip) . '">'
            . '<span>' . $profile['score'] . '</span></span>';
    }
}
