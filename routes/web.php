<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SocialAccountController;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\CheckSocialAccountLimit;
use App\Http\Middleware\CheckMonthlyPostLimit;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('/social-accounts', [SocialAccountController::class, 'index'])->name('social-accounts.index');
    Route::get('/social/{provider}/redirect', [SocialAccountController::class, 'redirect'])->name('social.redirect');
    Route::get('/social/{provider}/callback', [SocialAccountController::class, 'callback'])->name('social.callback');

    Route::resource('posts', PostController::class);

    // Social connect route protected by SaaS limit
    Route::get('/social/{provider}/redirect', [SocialAccountController::class, 'redirect'])
        ->middleware(CheckSocialAccountLimit::class)
        ->name('social.redirect');

    // Post creation protected by Monthly limit
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware(CheckMonthlyPostLimit::class)
        ->name('posts.store');

});

require __DIR__.'/settings.php';
