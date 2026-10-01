<?php

namespace App\Http\Controllers;

use App\Models\Diet;
use App\Models\Dish;
use App\Models\DishCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $dishes = Dish::published()
            ->with('category')
            ->when($request->category, fn ($q, $slug) => $q->whereRelation('category', 'slug', $slug))
            ->when($request->diet, fn ($q, $slug) => $q->whereRelation('diets', 'slug', $slug))
            ->when($request->q, fn ($q, $term) => $q->where('name->'.$locale, 'like', '%'.$term.'%'))
            ->when($request->max_kcal, fn ($q, $kcal) => $q->where('calories', '<=', (int) $kcal))
            ->when($request->sort === 'protein', fn ($q) => $q->orderByDesc('protein'), fn ($q) => $q->orderBy('calories'))
            ->paginate(12)
            ->withQueryString();

        return view('dishes.index', [
            'dishes' => $dishes,
            'categories' => DishCategory::orderBy('sort')->get(),
            'diets' => Diet::published()->get(),
        ]);
    }

    public function show(Dish $dish): View
    {
        abort_unless($dish->is_published, 404);

        $dish->load('category', 'diets');

        return view('dishes.show', [
            'dish' => $dish,
            'related' => Dish::published()->where('dish_category_id', $dish->dish_category_id)->whereKeyNot($dish->id)->take(3)->get(),
        ]);
    }
}
