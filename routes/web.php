<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SocialAccountController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('/social-accounts', [SocialAccountController::class, 'index'])->name('social-accounts.index');
    Route::get('/social/{provider}/redirect', [SocialAccountController::class, 'redirect'])->name('social.redirect');
    Route::get('/social/{provider}/callback', [SocialAccountController::class, 'callback'])->name('social.callback');

    Route::resource('posts', PostController::class);
});

require __DIR__.'/settings.php';
