<?php

use App\Http\Controllers\MaterialFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/learn')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/materials/{material}/file', [MaterialFileController::class, 'show'])->name('materials.file');
    Route::get('/materials/{material}/thumbnail', [MaterialFileController::class, 'thumbnail'])->name('materials.thumbnail');
});
