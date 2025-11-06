<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('welcome');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
    Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    
    Route::get('notas-fiscais/create', [\App\Http\Controllers\NotaFiscalController::class, 'create'])->name('notas-fiscais.create');
    Route::post('notas-fiscais/upload', [\App\Http\Controllers\NotaFiscalController::class, 'upload'])->name('notas-fiscais.upload');
    Route::patch('notas-fiscais/{notaFiscal}/status', [\App\Http\Controllers\NotaFiscalController::class, 'updateStatus'])->name('notas-fiscais.update-status');
    Route::get('notas-fiscais/{notaFiscal}/pdf', [\App\Http\Controllers\NotaFiscalController::class, 'pdf'])->name('notas-fiscais.pdf');
    Route::get('notas-fiscais/print/report', [\App\Http\Controllers\NotaFiscalController::class, 'printReport'])->name('notas-fiscais.print-report');
    Route::get('notas-fiscais/print/summary', [\App\Http\Controllers\NotaFiscalController::class, 'printSummary'])->name('notas-fiscais.print-summary');
    Route::resource('notas-fiscais', \App\Http\Controllers\NotaFiscalController::class)
        ->except(['create'])
        ->parameters(['notas-fiscais' => 'notaFiscal']);
});

require __DIR__.'/settings.php';
