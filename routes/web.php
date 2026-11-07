<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupManageController;
use App\Http\Controllers\PublicRallyController;
use App\Http\Controllers\RallyManageController;
use App\Http\Controllers\SeriesManageController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', [PublicRallyController::class, 'home'])->name('home');
Route::get('/groups/{group:slug}', [PublicRallyController::class, 'showGroup'])->name('groups.show');
Route::get('/series/{series:slug}', [PublicRallyController::class, 'showSeries'])->name('series.show');
Route::get('/rallies/{rally:slug}', [PublicRallyController::class, 'showRally'])->name('rallies.show');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/dev-login', [AuthController::class, 'devLogin'])->name('dev.login');
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Authenticated user routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Player actions (login required)
    Route::post('/rallies/{rally:slug}/join', [PublicRallyController::class, 'joinRally'])->name('rallies.join');
    Route::post('/rallies/{rally:slug}/locations/{location}/checkin', [PublicRallyController::class, 'checkin'])->name('rallies.checkin');
    Route::get('/rallies/{rally:slug}/certificate', [PublicRallyController::class, 'certificate'])->name('rallies.certificate');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Creator & Group management
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/groups/create', [GroupManageController::class, 'create'])->name('groups.create');
        Route::post('/groups', [GroupManageController::class, 'store'])->name('groups.store');
        Route::get('/groups/{group:slug}/edit', [GroupManageController::class, 'edit'])->name('groups.edit');
        Route::put('/groups/{group:slug}', [GroupManageController::class, 'update'])->name('groups.update');
        Route::post('/groups/{group:slug}/members', [GroupManageController::class, 'addMember'])->name('groups.members.store');
        Route::delete('/groups/{group:slug}/members/{user}', [GroupManageController::class, 'removeMember'])->name('groups.members.destroy');

        Route::get('/series/create', [SeriesManageController::class, 'create'])->name('series.create');
        Route::post('/series', [SeriesManageController::class, 'store'])->name('series.store');
        Route::get('/series/{series:slug}/edit', [SeriesManageController::class, 'edit'])->name('series.edit');
        Route::put('/series/{series:slug}', [SeriesManageController::class, 'update'])->name('series.update');

        Route::get('/rallies/create', [RallyManageController::class, 'create'])->name('rallies.create');
        Route::post('/rallies', [RallyManageController::class, 'store'])->name('rallies.store');
        Route::get('/rallies/{rally:slug}/edit', [RallyManageController::class, 'edit'])->name('rallies.edit');
        Route::put('/rallies/{rally:slug}', [RallyManageController::class, 'update'])->name('rallies.update');

        Route::post('/rallies/{rally:slug}/locations', [RallyManageController::class, 'storeLocation'])->name('rallies.locations.store');
        Route::post('/rallies/{rally:slug}/locations/copy', [RallyManageController::class, 'copyLocation'])->name('rallies.locations.copy');
        Route::put('/rallies/{rally:slug}/locations/{location}', [RallyManageController::class, 'updateLocation'])->name('rallies.locations.update');
        Route::delete('/rallies/{rally:slug}/locations/{location}', [RallyManageController::class, 'destroyLocation'])->name('rallies.locations.destroy');
    });
});
