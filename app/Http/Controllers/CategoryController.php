<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function category_create(Request $request)
    {
        $parents = Category::where('parent_cat_id', 0)
            ->select('category_id', 'cat_name', 'cat_path')
            ->orderBy('cat_name')
            ->get();

        // ?parent=ID opens the form as "sub-category of ID"
        $selected_parent = $parents->firstWhere('category_id', $request->parent);

        return view('categories.create', compact('parents', 'selected_parent'));
    }

    public function category_store(Request $request)
    {
        $request->validate([
            'type'          => 'required|in:main,sub',
            'cat_name'      => 'required|string|max:100',
            'parent_cat_id' => 'required_if:type,sub|nullable|integer',
            'cat_info'      => 'nullable|string|max:2000',
            'cat_meta_desc' => 'nullable|string|max:255',
            'cat_keyword'   => 'nullable|string|max:225',
            'cat_icon'      => [
                'nullable', 'string', 'max:20000',
                // Inline SVG is rendered on the public site: only allow plain markup
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\s*<svg[\s>]/i', $value) || !preg_match('/<\/svg>\s*$/i', $value)) {
                        $fail('The icon must be SVG code starting with <svg and ending with </svg>.');
                    } elseif (preg_match('/<script|<foreignobject|\son\w+\s*=|javascript:/i', $value)) {
                        $fail('The icon contains scripts or event handlers, which are not allowed.');
                    }
                },
            ],
            'is_feature'    => 'nullable|boolean',
        ], [
            'cat_name.required'         => 'Enter a category name.',
            'parent_cat_id.required_if' => 'Choose the main category this sub-category belongs to.',
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $request->cat_name));
        $is_sub = $request->type === 'sub';
        $parent = null;

        if ($is_sub) {
            $parent = Category::where('category_id', $request->parent_cat_id)->where('parent_cat_id', 0)->first();
            if (!$parent) {
                return redirect()->back()->withInput()->withErrors(['parent_cat_id' => 'That main category no longer exists.']);
            }
        }

        $parent_id = $parent ? $parent->category_id : 0;
        $path = $this->category_path($name, $parent);

        if ($error = $this->duplicate_error($name, $parent_id, $path)) {
            return redirect()->back()->withInput()->withErrors(['cat_name' => $error]);
        }

        $category = DB::transaction(function () use ($request, $name, $is_sub, $parent, $parent_id, $path) {
            $category = new Category();
            $category->cat_name = $name;
            $category->parent_cat_id = $parent_id;
            $category->cat_info = $request->cat_info;
            $category->cat_meta_desc = $request->cat_meta_desc ?? '';
            $category->cat_keyword = $request->cat_keyword ?? '';
            $category->cat_path = $path;
            $category->cat_child = '';
            $category->cat_number_links = 0;
            $category->cat_icon = $is_sub ? 'default.png' : ($request->cat_icon ? trim($request->cat_icon) : null);
            $category->is_feature = $is_sub ? null : ($request->boolean('is_feature') ? 1 : 0);
            $category->save();

            // cat_child is "id" for a main category and "parent_id/id" for a sub-category (needs the new id)
            $category->cat_child = $is_sub ? $parent->category_id . '/' . $category->category_id : (string) $category->category_id;
            $category->save();

            return $category;
        });

        $message = $is_sub
            ? 'Sub-category "' . $category->cat_name . '" added under ' . trim($parent->cat_name) . '.'
            : 'Main category "' . $category->cat_name . '" added.';

        if ($request->input('after') === 'another') {
            return redirect()->route('category_create', $is_sub ? ['parent' => $parent_id] : [])->with('success', $message);
        }
        return redirect()->route('category_count', ['search' => $category->cat_name])->with('success', $message);
    }

    // Live check while typing: returns the URL path and any duplicate message
    public function category_check_name(Request $request)
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $request->name));
        $parent = $request->filled('parent') ? Category::where('category_id', $request->parent)->where('parent_cat_id', 0)->first() : null;

        if ($name === '' || ($request->filled('parent') && !$parent)) {
            return response()->json(['path' => null, 'duplicate' => false, 'message' => null]);
        }

        $path = $this->category_path($name, $parent);
        $error = $this->duplicate_error($name, $parent ? $parent->category_id : 0, $path);

        return response()->json(['path' => $path, 'duplicate' => (bool) $error, 'message' => $error]);
    }

    // URL path used by the public site: "main-slug" or "parent-path/sub-slug"
    private function category_path($name, $parent = null)
    {
        $slug = Str::slug($name);
        return $parent ? trim($parent->cat_path) . '/' . $slug : $slug;
    }

    // Same name under the same parent (ignoring case, spacing and "&" vs "and"), or the same URL path
    private function duplicate_error($name, $parent_id, $path)
    {
        $key = $this->name_key($name);

        $same_name = Category::where('parent_cat_id', $parent_id)
            ->get(['category_id', 'cat_name'])
            ->first(fn($category) => $this->name_key($category->cat_name) === $key);

        if ($same_name) {
            return '"' . trim($same_name->cat_name) . '" already exists' . ($parent_id ? ' in this main category.' : ' as a main category.');
        }

        $same_path = Category::whereRaw('LOWER(TRIM(cat_path)) = ?', [mb_strtolower($path)])->first(['cat_name']);
        if ($same_path) {
            return 'This would have the same web address as "' . trim($same_path->cat_name) . '" (' . $path . '). Choose a different name.';
        }

        return null;
    }

    private function name_key($name)
    {
        return Str::slug(str_replace('&', ' and ', mb_strtolower(trim($name))));
    }
}
