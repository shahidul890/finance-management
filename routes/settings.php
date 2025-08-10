<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', '2fa'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});

Route::get('settings/2fa', [TwoFactorController::class, 'showTwoFactorSettings'])->name('settings.2fa')->middleware(['auth']);

Route::post('settings/2fa', [TwoFactorController::class, 'enableTwoFactor'])->name('settings.2fa.enable')->middleware(['auth', 'throttle:6,1']);
Route::put('settings/2fa/disable', [TwoFactorController::class, 'disableTwoFactor'])->name('settings.2fa.disable')->middleware(['auth', 'throttle:6,1']);
