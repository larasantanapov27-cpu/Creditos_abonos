<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:login')->name('login.store');
    Route::get('/registro', [AuthController::class, 'register'])->name('register');
    Route::post('/registro', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('register.store');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/home', HomeController::class)->name('home');
    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'toggle'])->name('clientes.toggle');
    Route::resource('clientes', ClienteController::class)->parameters(['clientes' => 'cliente']);
    Route::post('/creditos/calendario', [CreditoController::class, 'preview'])->name('creditos.preview');
    Route::resource('creditos', CreditoController::class)->except('destroy')->parameters(['creditos' => 'credito']);
    Route::get('/consultas', [CreditoController::class, 'index'])->name('consultas');
    Route::post('/abonos/{abono}/cancelar', [AbonoController::class, 'cancel'])->name('abonos.cancel');
    Route::resource('abonos', AbonoController::class)->only(['index', 'create', 'store', 'show'])->parameters(['abonos' => 'abono']);
    Route::get('/catalogos', [CatalogoController::class, 'index'])->name('catalogos.index');
    Route::post('/catalogos/{tipo}', [CatalogoController::class, 'store'])->name('catalogos.store');
    Route::put('/catalogos/{tipo}/{id}', [CatalogoController::class, 'update'])->whereNumber('id')->name('catalogos.update');
    Route::delete('/catalogos/{tipo}/{id}', [CatalogoController::class, 'destroy'])->whereNumber('id')->name('catalogos.destroy');
});
