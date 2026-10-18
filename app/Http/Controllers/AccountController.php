<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.index', [
            'user' => $request->user(),
            'plans' => $request->user()->dietPlans()->with('preferredDiet')->paginate(10),
        ]);
    }
}
