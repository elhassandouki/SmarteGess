<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CompteTController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReglementController;
use App\Http\Controllers\PurchaseDocumentController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\TransporteurController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('families', FamilyController::class)->except(['show']);
    Route::resource('articles', ArticleController::class)->except(['show']);
    Route::resource('depots', DepotController::class)->except(['show']);
    Route::resource('tiers', CompteTController::class)->parameters(['tiers' => 'tier'])->except(['show']);
    Route::get('/clients', [CompteTController::class, 'clients'])->name('tiers.clients');
    Route::get('/fournisseurs', [CompteTController::class, 'suppliers'])->name('tiers.suppliers');
    Route::resource('transporteurs', TransporteurController::class)->except(['show']);
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::patch('/stocks/{stock}/adjust', [StockController::class, 'adjust'])->name('stocks.adjust');
    Route::resource('reglements', ReglementController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::get('/ventes/documents', [SalesDocumentController::class, 'index'])->name('documents.sales');
    Route::get('/achats/documents', [PurchaseDocumentController::class, 'index'])->name('documents.purchases');
    Route::get('/stock/documents', [StockDocumentController::class, 'index'])->name('documents.stock');
    Route::post('/documents/{document}/duplicate', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
    Route::patch('/documents/{document}/status', [DocumentController::class, 'updateStatus'])->name('documents.update-status');
    Route::resource('documents', DocumentController::class);
});
