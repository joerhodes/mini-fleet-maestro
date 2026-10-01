<?php

use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\EditRoleController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashBoardController::class)->name('dashboard');
    Route::get('roles', RoleController::class)->name('roles.index');
    Route::get('roles/{role}', EditRoleController::class)->name('roles.edit');
});

require __DIR__.'/settings.php';
