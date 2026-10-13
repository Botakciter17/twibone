<?php

use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CampaignController as PublicCampaignController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect halaman utama
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.campaigns.index');
    }
    return redirect()->route('login');
});

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [AdminCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [AdminCampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}/edit', [AdminCampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}', [AdminCampaignController::class, 'update'])->name('campaigns.update');
    Route::patch('/campaigns/{campaign}/toggle', [AdminCampaignController::class, 'toggle'])->name('campaigns.toggle');
    Route::delete('/campaigns/{campaign}', [AdminCampaignController::class, 'destroy'])->name('campaigns.destroy');
});

// User Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Redirect dashboard ke admin campaigns
Route::get('/dashboard', function () {
    return redirect()->route('admin.campaigns.index');
})->middleware(['auth'])->name('dashboard');

// Auth routes (Breeze)
require __DIR__ . '/auth.php';

// Halaman editor publik (harus ditaruh paling bawah agar tidak bentrok dengan rute lain)
Route::get('/{slug}', [PublicCampaignController::class, 'show'])->name('campaign.show');
