<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(Request $r)
    {
        $like = $r->filled('search') ? '%'.addcslashes($r->search, '%_\\').'%' : null;
        return ApiResponse::success(Category::query()
            ->when($like, fn ($q) => $q->where('name', 'like', $like))
            ->orderBy('name')->paginate(min((int) $r->query('per_page', 50), 200)));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120', 'unique:categories,name']]);
        $d['slug'] = \Illuminate\Support\Str::slug($d['name']);
        return ApiResponse::created(Category::create($d), 'Category created');
    }

    public function update(Request $r, int $id)
    {
        $m = Category::findOrFail($id);
        $d = $r->validate(['name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($m->id)]]);
        $d['slug'] = \Illuminate\Support\Str::slug($d['name']);
        $m->update($d);
        return ApiResponse::success($m, 'Category updated');
    }

    public function destroy(int $id)
    {
        Category::findOrFail($id)->delete();
        return ApiResponse::success(null, 'Category deleted');
    }
}
