<?php

namespace App\Http\Controllers;

use App\Models\SiteBanner;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Home banners: the photos that rotate at the top of the public home page (worldweb-directory.com).
 * Table site_banners (shared DB, created by the directory site's SQL file). Files: shared disk, banner/.
 */
class BannerController extends Controller
{
    private const WIDE_WIDTH = 2000;            // computers: the photo is scaled down to this width, never cropped
    private const SMALL_SIZE = [900, 1100];     // phones: cropped around the chosen focus
    private const MAX_UPLOAD_KB = 8192;

    public function banners()
    {
        try {
            $banners = SiteBanner::orderBy('sort_order')->orderBy('banner_id')->get();
            $table_missing = false;
        } catch (\Throwable $e) {
            $banners = collect();
            $table_missing = true;
        }
        $live_count = $banners->filter(fn ($banner) => $banner->status() === 'live')->count();

        return view('banners.index', compact('banners', 'table_missing', 'live_count'));
    }

    public function banner_create()
    {
        $banner = new SiteBanner([
            'focus'      => 'center',
            'is_active'  => true,
            'sort_order' => ((int) SiteBanner::max('sort_order')) + 1,
        ]);
        $upload_limit = $this->upload_limit_kb();

        return view('banners.form', compact('banner', 'upload_limit'));
    }

    public function banner_store(Request $request)
    {
        $data = $this->validated($request, true);

        try {
            [$data['image_file'], $data['image_file_small']] = $this->save_images($request->file('image'), $data['focus']);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $this->image_error());
        }

        try {
            SiteBanner::create($data);
        } catch (\Throwable $e) {
            $this->delete_files([$data['image_file'], $data['image_file_small']]);
            throw $e;
        }

        return redirect()->route('banners')->with('success', 'Banner added. The home page shows it within 10 minutes.');
    }

    public function edit_banner($id)
    {
        $banner = SiteBanner::findOrFail($this->decrypt_id($id));
        $upload_limit = $this->upload_limit_kb();

        return view('banners.form', compact('banner', 'upload_limit'));
    }

    public function edit_banner_action(Request $request, $id)
    {
        $banner = SiteBanner::findOrFail($this->decrypt_id($id));
        $data = $this->validated($request, false);
        $old_files = [$banner->image_file, $banner->image_file_small];

        try {
            if ($request->hasFile('image')) {
                [$data['image_file'], $data['image_file_small']] = $this->save_images($request->file('image'), $data['focus']);
            } elseif ($data['focus'] !== $banner->focus) {
                // New focus, same photo: crop the phone version again from the saved wide photo.
                $data['image_file_small'] = $this->save_small($banner->image_file, $data['focus']);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $this->image_error());
        }

        try {
            $banner->update($data);
        } catch (\Throwable $e) {
            $this->delete_files(array_diff(array_filter([$data['image_file'] ?? null, $data['image_file_small'] ?? null]), $old_files));
            throw $e;
        }

        $unused = array_diff(array_filter($old_files), [$banner->image_file, $banner->image_file_small]);
        $this->delete_files($unused);

        return redirect()->route('banners')->with('success', 'Banner saved. The home page shows changes within 10 minutes.');
    }

    public function banner_status($id)
    {
        $banner = SiteBanner::findOrFail($this->decrypt_id($id));
        $banner->update(['is_active' => !$banner->is_active, 'updated_by' => Auth::id()]);

        return redirect()->back()->with('success', '"' . $banner->headline . '" is now ' . ($banner->is_active ? 'on' : 'off') . '.');
    }

    public function delete_banner($id)
    {
        $banner = SiteBanner::findOrFail($this->decrypt_id($id));
        $banner->delete();
        $this->delete_files([$banner->image_file, $banner->image_file_small]);

        return redirect()->route('banners')->with('success', 'Banner "' . $banner->headline . '" deleted.');
    }

    private function validated(Request $request, bool $creating): array
    {
        $url_rule = ['nullable', 'url:http,https', 'max:500'];
        $request->validate([
            'headline'    => 'required|string|max:90',
            'subtext'     => 'nullable|string|max:180',
            'button_text' => 'nullable|string|max:40|required_with:button_url',
            'button_url'  => array_merge($url_rule, ['required_with:button_text']),
            'credit_text' => 'nullable|string|max:90',
            'credit_url'  => $url_rule,
            'image'       => [$creating ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . $this->upload_limit_kb(), 'dimensions:min_width=1200,min_height=500'],
            'focus'       => 'required|in:' . implode(',', array_keys(SiteBanner::FOCUS)),
            'sort_order'  => 'nullable|integer|min:0|max:999',
            'is_active'   => 'nullable|in:1',
            'starts_on'   => 'nullable|date',
            'ends_on'     => 'nullable|date|after_or_equal:starts_on',
        ], [
            'image.required'          => 'Choose a photo.',
            'image.mimes'             => 'The photo must be a JPG, PNG or WebP file.',
            'image.max'               => 'The photo is too big. Keep it under ' . round($this->upload_limit_kb() / 1024) . ' MB.',
            'image.dimensions'        => 'The photo is too small. Use one at least 1200 px wide and 500 px high (best: 2400 × 1000).',
            'image.uploaded'          => 'The photo didn\'t upload. It may be bigger than the server allows (' . round($this->upload_limit_kb() / 1024) . ' MB).',
            'button_text.required_with' => 'Add the button text, or clear the button link.',
            'button_url.required_with'  => 'Add where the button goes, or clear the button text.',
            '*.url'                   => 'Enter a full web address starting with https://',
            'ends_on.after_or_equal'  => 'The last day can\'t be before the first day.',
        ]);

        $text = fn ($field) => trim((string) $request->input($field)) ?: null;

        return [
            'headline'    => trim($request->input('headline')),
            'subtext'     => $text('subtext'),
            'button_text' => $text('button_text'),
            'button_url'  => $text('button_url'),
            'credit_text' => $text('credit_text'),
            'credit_url'  => $text('credit_url'),
            'focus'       => $request->input('focus'),
            'sort_order'  => (int) $request->input('sort_order', 0),
            'is_active'   => $request->boolean('is_active'),
            'starts_on'   => $request->input('starts_on') ?: null,
            'ends_on'     => $request->input('ends_on') ?: null,
            'updated_by'  => Auth::id(),
        ];
    }

    /** Saves the wide photo (computers) and the cropped one (phones) as fresh JPEGs. Returns both file names. */
    private function save_images(UploadedFile $file, string $focus): array
    {
        $manager = new ImageManager(new Driver());
        $name = 'banner_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(6));

        $wide = $manager->read($file->getRealPath())
            ->scaleDown(width: self::WIDE_WIDTH)
            ->toJpeg(quality: 80, progressive: true, strip: true);
        Storage::disk('shared')->put('banner/' . $name . '.jpg', (string) $wide);

        return [$name . '.jpg', $this->save_small($name . '.jpg', $focus)];
    }

    private function save_small(string $wide_file, string $focus): string
    {
        $manager = new ImageManager(new Driver());
        $small_file = preg_replace('/\.jpg$/', '', $wide_file) . '_m_' . $focus . '.jpg';

        $small = $manager->read(Storage::disk('shared')->path('banner/' . $wide_file))
            ->cover(self::SMALL_SIZE[0], self::SMALL_SIZE[1], $focus)
            ->toJpeg(quality: 78, progressive: true, strip: true);
        Storage::disk('shared')->put('banner/' . $small_file, (string) $small);

        return $small_file;
    }

    private function delete_files(array $files): void
    {
        foreach (array_filter($files) as $file) {
            if (preg_match('/^banner_[A-Za-z0-9_]+\.jpg$/', $file)) {
                Storage::disk('shared')->delete('banner/' . $file);
            }
        }
    }

    private function image_error(): string
    {
        return 'The server couldn\'t process this photo. Save it as a JPG about 2400 px wide (under 3 MB) and try again.';
    }

    /** The upload size this server accepts, in KB: our limit or PHP's, whichever is smaller. */
    private function upload_limit_kb(): int
    {
        $to_kb = function ($value) {
            $value = trim((string) $value);
            $number = (float) $value;
            return match (strtolower(substr($value, -1))) {
                'g' => (int) ($number * 1024 * 1024),
                'm' => (int) ($number * 1024),
                'k' => (int) $number,
                default => (int) ($number / 1024),
            };
        };
        $limits = array_filter([self::MAX_UPLOAD_KB, $to_kb(ini_get('upload_max_filesize')), $to_kb(ini_get('post_max_size'))]);

        return min($limits);
    }

    private function decrypt_id($id)
    {
        try {
            return decrypt($id);
        } catch (DecryptException $e) {
            abort(404);
        }
    }
}
