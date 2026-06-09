<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AI\BusinessIntelligenceController;
use App\Http\Controllers\Api\ArticleLookupController;
use App\Http\Controllers\Access\PermissionController;
use App\Http\Controllers\Access\RoleController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CompteTController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\ReglementController;
use App\Http\Controllers\PurchaseDocumentController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockDocumentController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TransporteurController;
use App\Http\Controllers\SaaS\OnboardingController;
use App\Http\Controllers\SaaS\SupportDashboardController;
use App\Http\Controllers\SaaS\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/articles/search', [ArticleLookupController::class, 'search'])->name('articles.search');
        Route::get('/articles/barcode/{code}', [ArticleLookupController::class, 'barcode'])->name('articles.barcode');
    });

    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('saas.onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('saas.onboarding.store');
    Route::get('/support', [SupportDashboardController::class, 'index'])->middleware('can:internal.support.view')->name('support.dashboard');
    Route::patch('/support/tenants/{tenant}/toggle', [SupportDashboardController::class, 'toggleTenant'])->middleware('can:internal.support.update')->name('support.tenants.toggle');
    Route::get('/support/audit-logs', [AuditLogController::class, 'index'])->middleware('can:internal.support.view')->name('support.audit-logs');

    Route::get('/home', [HomeController::class, 'index'])->middleware(['can:view-erp', 'tenant.active'])->name('home');

    Route::prefix('ai')->name('ai.')->middleware('can:view-erp')->group(function () {
        Route::get('/', [BusinessIntelligenceController::class, 'index'])->name('index');
        Route::post('/reports/run', [BusinessIntelligenceController::class, 'report'])->name('reports.run');
        Route::get('/stock-alerts', [BusinessIntelligenceController::class, 'stockAlerts'])->name('stock-alerts');
        Route::get('/documents/{document}/export/pdf', [BusinessIntelligenceController::class, 'exportDocumentPdf'])->name('documents.export.pdf');
        Route::get('/documents/{document}/export/excel', [BusinessIntelligenceController::class, 'exportDocumentExcel'])->name('documents.export.excel');
        Route::post('/reports/export/{format}', [BusinessIntelligenceController::class, 'exportReport'])
            ->whereIn('format', ['pdf', 'excel'])
            ->name('reports.export');
    });

    Route::prefix('erp')->name('erp.')->middleware('can:view-erp')->group(function () {
        Route::prefix('ventes')->name('sales.')->middleware('can:documents.view')->group(function () {
            Route::get('/documents', [SalesDocumentController::class, 'index'])->name('documents.index');
            Route::get('/documents/create', [SalesDocumentController::class, 'create'])->middleware('can:documents.create')->name('documents.create');
            Route::post('/documents', [SalesDocumentController::class, 'store'])->middleware(['can:documents.create', 'plan.documents'])->name('documents.store');
        });

        Route::prefix('achats')->name('purchases.')->middleware('can:documents.view')->group(function () {
            Route::get('/documents', [PurchaseDocumentController::class, 'index'])->name('documents.index');
            Route::get('/documents/create', [PurchaseDocumentController::class, 'create'])->middleware('can:documents.create')->name('documents.create');
            Route::post('/documents', [PurchaseDocumentController::class, 'store'])->middleware(['can:documents.create', 'plan.documents'])->name('documents.store');
        });

        Route::prefix('stock')->name('stock.')->middleware('can:stocks.view')->group(function () {
            Route::get('/documents', [StockDocumentController::class, 'index'])->name('documents.index');
            Route::get('/', [StockController::class, 'index'])->name('index');
            Route::patch('/{stock}/adjust', [StockController::class, 'adjust'])->middleware('can:stocks.adjust')->name('adjust');
        });

        Route::prefix('clients')->name('clients.')->middleware('can:tiers.view')->group(function () {
            Route::get('/', [CompteTController::class, 'clients'])->name('index');
        });

        Route::prefix('fournisseurs')->name('suppliers.')->middleware('can:tiers.view')->group(function () {
            Route::get('/', [CompteTController::class, 'suppliers'])->name('index');
        });

        Route::prefix('payments')->name('payments.')->middleware('can:reglements.view')->group(function () {
            Route::get('reglements', [ReglementController::class, 'index'])->name('reglements.index');
            Route::get('reglements/create', [ReglementController::class, 'create'])->middleware('can:reglements.create')->name('reglements.create');
            Route::post('reglements', [ReglementController::class, 'store'])->middleware('can:reglements.create')->name('reglements.store');
            Route::delete('reglements/{reglement}', [ReglementController::class, 'destroy'])->middleware('can:reglements.delete')->name('reglements.destroy');
        });
    });

    Route::middleware('can:families.view')->group(function () {
        Route::get('/families/create', [FamilyController::class, 'create'])->middleware('can:families.create')->name('families.create');
        Route::post('/families', [FamilyController::class, 'store'])->middleware('can:families.create')->name('families.store');
        Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
        Route::get('/families/{family}', [FamilyController::class, 'show'])->name('families.show');
        Route::get('/families/{family}/edit', [FamilyController::class, 'edit'])->middleware('can:families.update')->name('families.edit');
        Route::put('/families/{family}', [FamilyController::class, 'update'])->middleware('can:families.update')->name('families.update');
        Route::patch('/families/{family}', [FamilyController::class, 'update'])->middleware('can:families.update');
        Route::delete('/families/{family}', [FamilyController::class, 'destroy'])->middleware('can:families.delete')->name('families.destroy');
    });

    Route::middleware('can:articles.view')->group(function () {
        Route::get('/articles/create', [ArticleController::class, 'create'])->middleware('can:articles.create')->name('articles.create');
        Route::post('/articles', [ArticleController::class, 'store'])->middleware('can:articles.create')->name('articles.store');
        Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
        Route::get('/articles/{article}/export-pdf', [ArticleController::class, 'exportPdf'])->name('articles.export-pdf');
        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->middleware('can:articles.update')->name('articles.edit');
        Route::put('/articles/{article}', [ArticleController::class, 'update'])->middleware('can:articles.update')->name('articles.update');
        Route::patch('/articles/{article}', [ArticleController::class, 'update'])->middleware('can:articles.update');
        Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->middleware('can:articles.delete')->name('articles.destroy');
    });

    Route::middleware('can:depots.view')->group(function () {
        Route::get('/depots', [DepotController::class, 'index'])->name('depots.index');
        Route::get('/depots/create', [DepotController::class, 'create'])->middleware('can:depots.create')->name('depots.create');
        Route::post('/depots', [DepotController::class, 'store'])->middleware('can:depots.create')->name('depots.store');
        Route::get('/depots/{depot}/edit', [DepotController::class, 'edit'])->middleware('can:depots.update')->name('depots.edit');
        Route::put('/depots/{depot}', [DepotController::class, 'update'])->middleware('can:depots.update')->name('depots.update');
        Route::patch('/depots/{depot}', [DepotController::class, 'update'])->middleware('can:depots.update');
        Route::delete('/depots/{depot}', [DepotController::class, 'destroy'])->middleware('can:depots.delete')->name('depots.destroy');
    });

    Route::middleware('can:taxes.view')->group(function () {
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::get('/taxes/create', [TaxController::class, 'create'])->middleware('can:taxes.create')->name('taxes.create');
        Route::post('/taxes', [TaxController::class, 'store'])->middleware('can:taxes.create')->name('taxes.store');
        Route::get('/taxes/{tax}', [TaxController::class, 'show'])->name('taxes.show');
        Route::get('/taxes/{tax}/edit', [TaxController::class, 'edit'])->middleware('can:taxes.update')->name('taxes.edit');
        Route::put('/taxes/{tax}', [TaxController::class, 'update'])->middleware('can:taxes.update')->name('taxes.update');
        Route::patch('/taxes/{tax}', [TaxController::class, 'update'])->middleware('can:taxes.update');
        Route::delete('/taxes/{tax}', [TaxController::class, 'destroy'])->middleware('can:taxes.delete')->name('taxes.destroy');
    });

    Route::middleware('can:tiers.view')->group(function () {
        Route::get('/tiers/create', [CompteTController::class, 'create'])->middleware('can:tiers.create')->name('tiers.create');
        Route::post('/tiers', [CompteTController::class, 'store'])->middleware('can:tiers.create')->name('tiers.store');
        Route::get('/tiers', [CompteTController::class, 'index'])->name('tiers.index');
        Route::get('/tiers/{tier}', [CompteTController::class, 'show'])->name('tiers.show');
        Route::get('/tiers/{tier}/edit', [CompteTController::class, 'edit'])->middleware('can:tiers.update')->name('tiers.edit');
        Route::put('/tiers/{tier}', [CompteTController::class, 'update'])->middleware('can:tiers.update')->name('tiers.update');
        Route::patch('/tiers/{tier}', [CompteTController::class, 'update'])->middleware('can:tiers.update');
        Route::delete('/tiers/{tier}', [CompteTController::class, 'destroy'])->middleware('can:tiers.delete')->name('tiers.destroy');
        Route::get('/clients', [CompteTController::class, 'clients'])->name('tiers.clients');
        Route::get('/fournisseurs', [CompteTController::class, 'suppliers'])->name('tiers.suppliers');
    });

    Route::middleware('can:transporteurs.view')->group(function () {
        Route::get('/transporteurs', [TransporteurController::class, 'index'])->name('transporteurs.index');
        Route::get('/transporteurs/create', [TransporteurController::class, 'create'])->middleware('can:transporteurs.create')->name('transporteurs.create');
        Route::post('/transporteurs', [TransporteurController::class, 'store'])->middleware('can:transporteurs.create')->name('transporteurs.store');
        Route::get('/transporteurs/{transporteur}/edit', [TransporteurController::class, 'edit'])->middleware('can:transporteurs.update')->name('transporteurs.edit');
        Route::put('/transporteurs/{transporteur}', [TransporteurController::class, 'update'])->middleware('can:transporteurs.update')->name('transporteurs.update');
        Route::patch('/transporteurs/{transporteur}', [TransporteurController::class, 'update'])->middleware('can:transporteurs.update');
        Route::delete('/transporteurs/{transporteur}', [TransporteurController::class, 'destroy'])->middleware('can:transporteurs.delete')->name('transporteurs.destroy');
    });

    Route::middleware('can:stocks.view')->group(function () {
        Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::patch('/stocks/{stock}/adjust', [StockController::class, 'adjust'])->middleware('can:stocks.adjust')->name('stocks.adjust');
        Route::get('/stock/documents', [StockDocumentController::class, 'index'])->name('documents.stock');
    });

    Route::middleware('can:reglements.view')->group(function () {
        Route::get('/reglements', [ReglementController::class, 'index'])->name('reglements.index');
        Route::get('/reglements/create', [ReglementController::class, 'create'])->middleware('can:reglements.create')->name('reglements.create');
        Route::post('/reglements', [ReglementController::class, 'store'])->middleware('can:reglements.create')->name('reglements.store');
        Route::delete('/reglements/{reglement}', [ReglementController::class, 'destroy'])->middleware('can:reglements.delete')->name('reglements.destroy');
    });

    Route::middleware('can:documents.view')->group(function () {
        Route::get('/ventes/documents', [SalesDocumentController::class, 'index'])->name('documents.sales');
        Route::get('/achats/documents', [PurchaseDocumentController::class, 'index'])->name('documents.purchases');
        Route::post('/documents/{document}/duplicate', [DocumentController::class, 'duplicate'])->middleware('can:documents.duplicate')->name('documents.duplicate');
        Route::patch('/documents/{document}/status', [DocumentController::class, 'updateStatus'])->middleware('can:documents.status')->name('documents.update-status');
        Route::get('/documents/create', [DocumentController::class, 'create'])->middleware('can:documents.create')->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->middleware(['can:documents.create', 'plan.documents'])->name('documents.store');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->middleware('can:documents.update')->name('documents.edit');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->middleware('can:documents.update')->name('documents.update');
        Route::patch('/documents/{document}', [DocumentController::class, 'update'])->middleware('can:documents.update');
        Route::patch('/documents/{document}/validate', [DocumentController::class, 'validateLifecycle'])->middleware('can:documents.update')->name('documents.validate');
        Route::patch('/documents/{document}/post', [DocumentController::class, 'postLifecycle'])->middleware('can:documents.status')->name('documents.post');
        Route::patch('/documents/{document}/cancel', [DocumentController::class, 'cancelLifecycle'])->middleware('can:documents.delete')->name('documents.cancel');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->middleware('can:documents.delete')->name('documents.destroy');

        // Invoice and thermal ticket routes
        Route::get('/invoices/{document}/pdf', [InvoiceController::class, 'downloadInvoicePdf'])->name('invoices.pdf');
        Route::get('/invoices/{document}/preview', [InvoiceController::class, 'previewInvoicePdf'])->name('invoices.preview');
        Route::get('/invoices/{document}/thermal', [InvoiceController::class, 'printThermalTicket'])->name('invoices.thermal');
        Route::get('/invoices/{document}/thermal-preview', [InvoiceController::class, 'previewThermalTicket'])->name('invoices.thermal-preview');
        Route::get('/invoices/{document}/thermal-escpos', [InvoiceController::class, 'getThermalTicketEscPos'])->name('invoices.thermal-escpos');
    });

    Route::prefix('access')->name('access.')->middleware('can:admin.panel')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('can:access.roles.create')->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('can:access.roles.create')->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:access.roles.update')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('can:access.roles.update')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('can:access.roles.delete')->name('roles.destroy');
    });

    Route::prefix('access')->name('access.')->middleware('can:admin.panel')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/create', [PermissionController::class, 'create'])->middleware('can:access.permissions.create')->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->middleware('can:access.permissions.create')->name('permissions.store');
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('can:access.permissions.update')->name('permissions.edit');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('can:access.permissions.update')->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('can:access.permissions.delete')->name('permissions.destroy');
    });

    Route::prefix('accounting')->name('accounting.')->middleware('can:internal.support.view')->group(function () {
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [ChartOfAccountController::class, 'index'])->name('index');
            Route::get('/{account}', [ChartOfAccountController::class, 'show'])->name('show');
        });
        Route::prefix('entries')->name('entries.')->group(function () {
            Route::get('/', [JournalEntryController::class, 'index'])->name('index');
            Route::get('/{entry}', [JournalEntryController::class, 'show'])->name('show');
        });
    });
});
