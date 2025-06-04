<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/product', [PageController::class, 'product'])->name('product');
Route::get('/review', [PageController::class, 'review'])->name('review');