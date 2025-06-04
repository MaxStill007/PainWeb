<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('about');
    }

    public function product(): View
    {
        return view('product');
    }

    public function review(): View
    {
        return view('review');
    }

    public function dashboard(): View
    {
        return view('dashboard');
    }
    
}