<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('/social-accounts', [SocialAccountController::class, 'index'])->name('social-accounts.index');
    Route::patch('/social-accounts/{socialAccount}', [SocialAccountController::class, 'update'])->name('social-accounts.update');
    Route::delete('/social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy'])->name('social-accounts.destroy');
    Route::get('/social/{provider}/redirect', [SocialAccountController::class, 'redirect'])->name('social.redirect');
    Route::get('/social/{provider}/callback', [SocialAccountController::class, 'callback'])->name('social.callback');

    Route::resource('posts', PostController::class);

    Route::post('workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::resource('workspaces', WorkspaceController::class)->except(['show']);

    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])->name('workspaces.members.index');
    Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])->name('workspaces.members.store');
    Route::put('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])->name('workspaces.members.destroy');
});

require __DIR__.'/settings.php';
