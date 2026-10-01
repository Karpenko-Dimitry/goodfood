<?php

namespace App\Http\Controllers;

use App\Models\Diet;
use App\Models\Dish;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'featuredDiets' => Diet::published()->where('is_featured', true)->take(6)->get(),
            'diets' => Diet::published()->take(4)->get(),
            'dishes' => Dish::published()->where('is_featured', true)->with('category')->latest()->take(6)->get(),
            'stats' => [
                'diets' => Diet::published()->count(),
                'dishes' => Dish::published()->count(),
            ],
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
