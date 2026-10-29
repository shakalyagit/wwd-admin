---
name: laravel-wwd
description: Best practices for this Laravel 10 + Blade + jQuery admin panel (performance, security, structure). Use when writing or reviewing controllers, models, queries, routes, Blade views, AJAX, uploads or mail in wwd-admin.
---

# Laravel 10 / Blade / jQuery: WWD Admin best practices

Follow `CONVENTIONS.md` for naming and layout. This skill adds *how to do it well*. Don't refactor existing code to these rules unless asked; apply them to code you touch.

## Queries (biggest win)
- **No queries in loops** (`foreach` → `Model::find`). Batch with `whereIn`, joins, or eager loading (`with()`); add a `belongsTo`/`hasMany` relation when a join repeats.
- Fetch once: don't re-query a row you already loaded (e.g. `Business::find` twice in one method).
- Select only needed columns on lists: `select('businesses.business_id', ...)`, not `businesses.*`, when the view uses few fields.
- `exists()` instead of `count() > 0` / `get()->isNotEmpty()`; `value('col')`/`pluck()` for single fields.
- Counts per group: `withCount()` or `selectSub`/`groupBy` in SQL, never PHP-side counting of full collections.
- Always `paginate(50)` lists; use `chunkById()` for bulk jobs/exports.
- Filters: use `->when($request->x, fn($q) => $q->where(...))`.
- Lookup tables (categories, countries, `RefCountry`) rarely change: `Cache::remember('categories', 3600, ...)` and clear the cache when they change.
- `findOrFail()` / `firstOrFail()` over `find()` + null access.
- Multi-table writes go in `DB::transaction()`; send mail **after** commit.
- Shared DB: suggest indexes (FKs, `is_admin_verified`, `created_at`) to the main app; don't add migrations here for shared tables.

## Security
- Validate **every** input with `$request->validate()`; whitelist fields before assigning. Keep `$fillable` explicit, never `$guarded = []`.
- Blade: `{{ }}` escapes. Use `{!! !!}` only for trusted helper HTML (badge helpers), never for user data.
- AJAX POSTs send the CSRF token: `_token: "{{ csrf_token() }}"` or `X-CSRF-TOKEN` header.
- Encrypted IDs: `decrypt()` can throw, so wrap it or use `abort(404)` on failure.
- Uploads: `image|mimes:jpg,jpeg,png,webp|max:2048`. Generate filenames with `Str::uuid()` and never trust client names. Store via `Storage::disk('shared')`.
- Destructive actions use POST (not GET) + a confirm modal in the UI.
- Never log or dump passwords/tokens; use `Hash::make` for passwords.

## Controllers & structure
- Keep the inline-controller style, but extract when logic is reused (2+ places) into a `private` method or `app/Helpers` function.
- Repeated, long validation → a FormRequest is acceptable.
- Return early on errors; `redirect()->back()->withInput()->with('error', ...)` on failure.
- Approval mail etc.: `Mail::to()->queue()` if a queue worker exists, else `send()` inside try/catch so mail failure doesn't break the action.
- Type-hint route model binding (`Business $business`) where IDs aren't encrypted.

## Blade & jQuery
- Reuse `layouts.main`, `<x-flash />`, `<x-back-btn />`, offcanvas filter pattern.
- AJAX filter endpoints return rendered HTML partials (`view(...)->render()`) + pagination JSON, matching `business_list_filter`.
- Debounce search inputs (~300ms); disable submit buttons while requests run.
- Put page JS in `@push`/section at the bottom of the view; don't add new vendor libs when one in `public/assets/vendors` already does the job.
- No inline SQL-derived data in JS without `@json()`.

## Efficiency checklist before finishing
1. Any query inside a loop or Blade `@foreach`? → batch it.
2. Same row/table fetched twice? → reuse.
3. Input validated, output escaped, CSRF present?
4. `php -l` changed files; run `vendor/bin/pint --dirty` if available.
5. `php artisan route:list --name=<name>` after route edits; `php artisan view:clear` if Blade looks stale.
