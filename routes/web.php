<?php

use App\Http\Controllers\GalleryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::controller(UserController::class)
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('can:users.view')
                ->name('index');
            Route::get('create', 'create')
                ->middleware('can:users.create')
                ->name('create');
            Route::post('/', 'store')
                ->middleware('can:users.create')
                ->name('store');
            Route::get('{user}', 'show')
                ->middleware('can:users.edit')
                ->name('show');
            Route::get('{user}/edit', 'edit')
                ->middleware('can:users.edit')
                ->name('edit');
            Route::match(['put', 'patch'], '{user}', 'update')
                ->middleware('can:users.edit')
                ->name('update');
            Route::delete('{user}', 'destroy')
                ->middleware('can:users.delete')
                ->name('destroy');
        });

    Route::controller(RoleController::class)
        ->prefix('roles')
        ->name('roles.')
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('can:roles.view')
                ->name('index');
            Route::get('create', 'create')
                ->middleware('can:roles.create')
                ->name('create');
            Route::post('/', 'store')
                ->middleware('can:roles.create')
                ->name('store');
            Route::get('{role}', 'show')
                ->middleware('can:roles.edit')
                ->name('show');
            Route::get('{role}/edit', 'edit')
                ->middleware('can:roles.edit')
                ->name('edit');
            Route::match(['put', 'patch'], '{role}', 'update')
                ->middleware('can:roles.edit')
                ->name('update');
            Route::delete('{role}', 'destroy')
                ->middleware('can:roles.delete')
                ->name('destroy');
        });

    Route::controller(GalleryController::class)
        ->prefix('gallery')
        ->name('gallery.')
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('can:gallery.folders.view')
                ->name('folders.index');
            Route::get('create', 'create')
                ->middleware('can:gallery.folders.create')
                ->name('folders.create');
            Route::post('/', 'store')
                ->middleware('can:gallery.folders.create')
                ->name('folders.store');
            Route::get('{folder}', 'show')
                ->middleware('can:gallery.folders.view')
                ->name('folders.show');
            Route::get('{folder}/edit', 'edit')
                ->middleware('can:gallery.folders.edit')
                ->name('folders.edit');
            Route::match(['put', 'patch'], '{folder}', 'update')
                ->middleware('can:gallery.folders.edit')
                ->name('folders.update');
            Route::delete('{folder}', 'destroy')
                ->middleware('can:gallery.folders.delete')
                ->name('folders.destroy');

            Route::post('{folder}/images', 'uploadImages')
                ->middleware(['can:gallery.folders.edit', 'can:gallery.images.create'])
                ->name('images.upload');
            Route::delete('{folder}/images/{image}', 'destroyImage')
                ->middleware(['can:gallery.folders.edit', 'can:gallery.images.delete'])
                ->name('images.destroy');
        });
});

require __DIR__.'/settings.php';
