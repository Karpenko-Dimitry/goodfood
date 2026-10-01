<?php

namespace App\Http\Controllers;

use App\Models\Diet;
use Illuminate\View\View;

class DietController extends Controller
{
    public function index(): View
    {
        return view('diets.index', [
            'diets' => Diet::published()->paginate(12),
        ]);
    }

    public function show(Diet $diet): View
    {
        abort_unless($diet->is_published, 404);

        return view('diets.show', [
            'diet' => $diet,
            'dishes' => $diet->dishes()->published()->with('category')->take(8)->get(),
            'others' => Diet::published()->whereKeyNot($diet->id)->take(3)->get(),
        ]);
    }
}
