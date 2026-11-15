<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Settings for the public directory site: social links in its footer and WWD's own Google reviews on its home page.
 */
class SettingController extends Controller
{
    public function settings()
    {
        try {
            $values = SiteSetting::values();
            $table_missing = false;
        } catch (\Throwable $e) {
            $values = [];
            $table_missing = true;
        }

        $key = (string) ($values['google_places_api_key'] ?? '');
        $key_hint = $key !== '' ? '•••• ' . substr($key, -4) : null;

        return view('settings.index', compact('values', 'table_missing', 'key_hint'));
    }

    public function settings_action(Request $request)
    {
        $url_rule = ['nullable', 'url:http,https', 'max:255'];
        $rules = [
            'google_place_id'        => ['nullable', 'regex:/^[A-Za-z0-9_-]{10,200}$/'],
            'google_places_api_key'  => ['nullable', 'regex:/^[A-Za-z0-9_-]{20,100}$/'],
            'google_reviews_enabled' => 'nullable|in:1',
            'remove_api_key'         => 'nullable|in:1',
        ];
        foreach (array_keys(SiteSetting::SOCIAL) as $field) {
            $rules[$field] = $url_rule;
        }
        $request->validate($rules, [
            'google_place_id.regex'       => 'That doesn\'t look like a Google Place ID (it starts with ChIJ and has no spaces).',
            'google_places_api_key.regex' => 'That doesn\'t look like a Google API key (letters, numbers, - and _ only).',
            '*.url'                       => 'Enter a full web address starting with https://',
        ]);

        $user_id = Auth::id();
        foreach (array_keys(SiteSetting::SOCIAL) as $field) {
            SiteSetting::put($field, trim((string) $request->input($field)) ?: null, $user_id);
        }
        SiteSetting::put('google_place_id', trim((string) $request->input('google_place_id')) ?: null, $user_id);
        SiteSetting::put('google_reviews_enabled', $request->boolean('google_reviews_enabled') ? '1' : '0', $user_id);

        // The key is never shown again: blank keeps the saved one, "Remove" clears it.
        if ($request->boolean('remove_api_key')) {
            SiteSetting::put('google_places_api_key', null, $user_id);
        } elseif (trim((string) $request->input('google_places_api_key')) !== '') {
            SiteSetting::put('google_places_api_key', trim($request->input('google_places_api_key')), $user_id);
        }

        return redirect()->back()->with('success', 'Settings saved. The public site shows them within 10 minutes.');
    }

    /** "Check with Google": asks the Places API for WWD's rating with the saved key and Place ID. */
    public function settings_google_test()
    {
        $values = SiteSetting::values();
        $key = $values['google_places_api_key'] ?? null;
        $place_id = $values['google_place_id'] ?? null;
        if (!$key || !$place_id) {
            return response()->json(['ok' => false, 'message' => 'Save a Place ID and an API key first.']);
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['X-Goog-Api-Key' => $key, 'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,reviews'])
                ->get('https://places.googleapis.com/v1/places/' . rawurlencode($place_id), ['languageCode' => 'en']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not reach Google. Try again in a minute.']);
        }

        if (!$response->ok()) {
            $reason = $response->json('error.message') ?: ('Google answered with error ' . $response->status());
            return response()->json(['ok' => false, 'message' => $reason]);
        }

        $place = $response->json();
        $recent = collect($place['reviews'] ?? [])->filter(function ($review) {
            return isset($review['publishTime']) && now()->subYears(3)->lt($review['publishTime']);
        })->count();

        return response()->json([
            'ok'      => true,
            'message' => sprintf(
                '%s: %s out of 5 from %s reviews. Google returned %d review(s); %d written in the last 3 years can show on the home page.',
                $place['displayName']['text'] ?? 'Place found',
                $place['rating'] ?? '–',
                number_format((int) ($place['userRatingCount'] ?? 0)),
                count($place['reviews'] ?? []),
                $recent
            ),
        ]);
    }
}
