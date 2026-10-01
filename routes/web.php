<?php

use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\EditRoleController;
use App\Http\Controllers\EditServerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashBoardController::class)->name('dashboard');
    Route::get('roles', RoleController::class)->name('roles.index');
    Route::get('roles/{role}', EditRoleController::class)->name('roles.edit');
    Route::get('servers', ServerController::class)->name('servers.index');
    Route::get('servers/create', EditServerController::class)->name('servers.create');
    Route::get('servers/{server}', EditServerController::class)->name('servers.edit');
});

require __DIR__.'/settings.php';
