<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSkillController extends Controller
{
    public function index(Request $r)
    {
        $like = $r->filled('search') ? '%'.addcslashes($r->search, '%_\\').'%' : null;
        return ApiResponse::success(Skill::query()
            ->when($like, fn ($q) => $q->where('name', 'like', $like))
            ->orderBy('name')->paginate(min((int) $r->query('per_page', 50), 200)));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120', 'unique:skills,name']]);
        
        return ApiResponse::created(Skill::create($d), 'Skill created');
    }

    public function update(Request $r, int $id)
    {
        $m = Skill::findOrFail($id);
        $d = $r->validate(['name' => ['required', 'string', 'max:120', Rule::unique('skills', 'name')->ignore($m->id)]]);
        
        $m->update($d);
        return ApiResponse::success($m, 'Skill updated');
    }

    public function destroy(int $id)
    {
        Skill::findOrFail($id)->delete();
        return ApiResponse::success(null, 'Skill deleted');
    }
}
