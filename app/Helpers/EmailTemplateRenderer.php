<?php

namespace App\Helpers;

/**
 * Renders an email from the shared `email_templates` + `email_layouts` tables.
 *
 * Plain PHP with no framework dependency: WWD Business and WWD Listing copy this file
 * unchanged, so all three projects render emails exactly the same way.
 * See docs/email-templates.md.
 *
 * Usage:
 *   $email = EmailTemplateRenderer::render($template_row, $layout_row, ['BusinessName' => 'Sunrise Bakery']);
 *   // $email['subject'], $email['html']
 */
class EmailTemplateRenderer
{
    /**
     * @param array|object $template Row from email_templates
     * @param array|object $layout   Row from email_layouts (the template's layout, or the default one)
     * @param array        $data     Placeholder values without braces, e.g. ['BusinessName' => '...']
     * @return array{subject: string, html: string}
     */
    public static function render($template, $layout, array $data): array
    {
        $template = (array) $template;
        $layout = (array) $layout;
        $vars = self::variables($layout, $data);

        $text = fn($value) => self::fill((string) $value, $vars, false);
        $html = fn($value) => self::fill((string) $value, $vars, true);

        $subject = $text($template['subject'] ?? '');

        $button = '';
        if (!empty($template['button_text']) && !empty($template['button_url'])) {
            $button = self::fill((string) ($layout['button_html'] ?? ''), $vars + [
                'ButtonText' => $text($template['button_text']),
                'ButtonURL'  => $text($template['button_url']),
            ], true);
        }

        // Template parts are already filled, so the layout is filled in one pass without re-reading them
        $parts = [
            '{{Subject}}'        => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
            '{{Preheader}}'      => $html($template['preheader'] ?? ''),
            '{{HeaderTitle}}'    => $html($template['header_title'] ?? ''),
            '{{HeaderSubtitle}}' => $html($template['header_subtitle'] ?? ''),
            '{{Content}}'        => $html($template['body'] ?? '') . $button,
            '{{FooterNote}}'     => $html(($template['footer_note'] ?? '') !== '' ? $template['footer_note'] : ($layout['footer_text'] ?? '')),
        ];
        foreach ($vars as $name => $value) {
            $parts['{{' . $name . '}}'] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        $page = strtr((string) ($layout['html'] ?? '{{Content}}'), $parts);

        return ['subject' => $subject, 'html' => $page];
    }

    /**
     * All placeholder values for an email: the layout's global values plus the caller's data (caller wins).
     * Also useful to work out the real button link before swapping in a click-tracking link.
     */
    public static function variables($layout, array $data): array
    {
        $layout = (array) $layout;

        return array_merge([
            'Year'         => date('Y'),
            'SiteURL'      => $layout['site_url'] ?? '',
            'SupportEmail' => $layout['support_email'] ?? '',
            'LogoURL'      => $layout['logo_url'] ?? '',
            'BrandColor'   => $layout['brand_color'] ?? '#dd3f3b',
        ], array_map(fn($value) => (string) $value, $data));
    }

    /**
     * Replace {{Name}} placeholders. Values are HTML-escaped when $escape is true.
     * Unknown placeholders are removed so recipients never see raw {{...}}.
     */
    public static function fill(string $text, array $vars, bool $escape): string
    {
        $map = [];
        foreach ($vars as $name => $value) {
            $map['{{' . $name . '}}'] = $escape ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : (string) $value;
        }
        $text = strtr($text, $map);

        return preg_replace('/\{\{\s*[A-Za-z0-9_]+\s*\}\}/', '', $text);
    }

    /** Placeholder names used in a piece of text, e.g. ['BusinessName', 'LoginURL'] */
    public static function placeholders(string $text): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $text, $matches);
        return array_values(array_unique($matches[1]));
    }
}
