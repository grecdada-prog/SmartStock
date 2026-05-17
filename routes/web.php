<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\SuperAdminManagerController;
use App\Http\Controllers\SuperAdmin\SuperAdminSellerController;
use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\ManagerSellerController;
use App\Http\Controllers\Manager\CategoryController;
use App\Http\Controllers\Manager\ProductController;
use App\Http\Controllers\Manager\StockController;
use App\Http\Controllers\Manager\ReportController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\POSController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;

/*
| Web Routes
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/csrf-token', function () {
    $response = response()->json(['token' => csrf_token()]);
    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

    return $response;
})->name('csrf-token');

// Routes d'authentification
Route::get('/login', [CustomLoginController::class, 'showLogin'])->name('login');
Route::post('/login', [CustomLoginController::class, 'login']);
Route::post('/logout', [CustomLoginController::class, 'logout'])->name('logout');

// Login Super Admin
Route::get('/superadmin/login', [CustomLoginController::class, 'showSuperAdminLogin'])->name('superadmin.login');
Route::post('/superadmin/login', [CustomLoginController::class, 'superAdminLogin']);

// 2FA Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/setup', [CustomLoginController::class, 'show2FASetup'])->name('2fa.setup');
    Route::post('/2fa/enable', [CustomLoginController::class, 'enable2FA'])->name('2fa.enable');
    Route::post('/2fa/disable', [CustomLoginController::class, 'disable2FA'])->name('2fa.disable');
});

// 2FA Verification (sans middleware auth car pas encore connecté)
Route::get('/2fa/verify', [CustomLoginController::class, 'show2FAVerify'])
    ->middleware('throttle:10,1')
    ->name('2fa.verify');
Route::post('/2fa/verify', [CustomLoginController::class, 'verify2FA'])
    ->middleware('throttle:5,1')
    ->name('2fa.verify.post');

// Profile routes (accessible par tous les utilisateurs authentifiés)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->hasRole('manager')) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasRole('seller')) {
            return redirect()->route('seller.dashboard');
        }

        abort(403, 'Accès non autorisé');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('account.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('account.profile.password.update');
});

/*
| Super Admin Routes
*/
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:super_admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/statistics', [SuperAdminDashboardController::class, 'statistics'])->name('statistics');
    Route::get('/anomalies', [SuperAdminDashboardController::class, 'anomalies'])->name('anomalies');

    // Sessions actives
    Route::get('/sessions/active', [SuperAdminDashboardController::class, 'activeSessions'])->name('sessions.active');
    Route::delete('/sessions/{session}', [SuperAdminDashboardController::class, 'destroySession'])->middleware('throttle:20,60')->name('sessions.destroy');
    Route::post('/sessions/cleanup', [SuperAdminDashboardController::class, 'cleanupSessions'])->middleware('throttle:10,60')->name('sessions.cleanup');

    // Gestion des utilisateurs
    Route::get('/users', [SuperAdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [SuperAdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [SuperAdminUserController::class, 'store'])->middleware('throttle:10,60')->name('users.store');
    Route::get('/users/{user}/edit', [SuperAdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [SuperAdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [SuperAdminUserController::class, 'destroy'])->middleware('throttle:10,60')->name('users.destroy');
    Route::post('/users/{user}/reset-password', [SuperAdminUserController::class, 'resetPassword'])->middleware('throttle:30,1')->name('users.reset-password');
    Route::post('/users/{user}/toggle-status', [SuperAdminUserController::class, 'toggleStatus'])->middleware('throttle:20,60')->name('users.toggle-status');
    Route::post('/users/{user}/force-logout', [SuperAdminUserController::class, 'forceLogout'])->middleware('throttle:20,60')->name('users.force-logout');

    // Gestion des gérants
    Route::get('/managers', [SuperAdminManagerController::class, 'index'])->name('managers.index');
    Route::get('/managers/create', [SuperAdminManagerController::class, 'create'])->name('managers.create');
    Route::post('/managers', [SuperAdminManagerController::class, 'store'])->middleware('throttle:10,60')->name('managers.store');
    Route::get('/managers/{user}/edit', [SuperAdminManagerController::class, 'edit'])->name('managers.edit');
    Route::put('/managers/{user}', [SuperAdminManagerController::class, 'update'])->name('managers.update');
    Route::delete('/managers/{user}', [SuperAdminManagerController::class, 'destroy'])->middleware('throttle:10,60')->name('managers.destroy');

    // Gestion des vendeurs
    Route::get('/sellers', [SuperAdminSellerController::class, 'index'])->name('sellers.index');
    Route::get('/sellers/create', [SuperAdminSellerController::class, 'create'])->name('sellers.create');
    Route::post('/sellers', [SuperAdminSellerController::class, 'store'])->middleware('throttle:10,60')->name('sellers.store');
    Route::get('/sellers/{user}/edit', [SuperAdminSellerController::class, 'edit'])->name('sellers.edit');
    Route::post('/sellers/{user}/reassign-manager', [SuperAdminSellerController::class, 'reassignManager'])->middleware('throttle:10,60')->name('sellers.reassign-manager');
    Route::post('/sellers/{user}/cash-register/close', [SuperAdminSellerController::class, 'closeCashRegister'])->middleware('throttle:10,60')->name('sellers.cash-register.close');
    Route::post('/sellers/{user}/cash-register/open', [SuperAdminSellerController::class, 'openCashRegister'])->middleware('throttle:10,60')->name('sellers.cash-register.open');
    Route::put('/sellers/{user}', [SuperAdminSellerController::class, 'update'])->name('sellers.update');
    Route::delete('/sellers/{user}', [SuperAdminSellerController::class, 'destroy'])->middleware('throttle:10,60')->name('sellers.destroy');

    // Vue globale des produits et ventes
    Route::get('/products', [SuperAdminDashboardController::class, 'products'])->name('products');
    Route::get('/sales', [SuperAdminDashboardController::class, 'sales'])->name('sales');

    // Logs d'activité
    Route::get('/activity-logs', [SuperAdminDashboardController::class, 'activityLogs'])->name('activity-logs');

    // Exports
    Route::get('/users/export/excel', [SuperAdminUserController::class, 'exportExcel'])->name('users.export.excel');
    Route::get('/users/export/pdf', [SuperAdminUserController::class, 'exportPdf'])->name('users.export.pdf');
    Route::get('/sales/export/excel', [SuperAdminDashboardController::class, 'exportSalesExcel'])->name('sales.export.excel');
    Route::get('/sales/export/pdf', [SuperAdminDashboardController::class, 'exportSalesPdf'])->name('sales.export.pdf');
    Route::get('/activity-logs/export/excel', [SuperAdminDashboardController::class, 'exportActivityLogsExcel'])->name('activity-logs.export.excel');
    Route::get('/activity-logs/export/pdf', [SuperAdminDashboardController::class, 'exportActivityLogsPdf'])->name('activity-logs.export.pdf');
});

/*
| Manager Routes
*/
Route::prefix('manager')->name('manager.')->middleware(['auth', 'role:manager'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');

    // Gestion des vendeurs
    Route::get('/sellers', [ManagerSellerController::class, 'index'])->name('sellers.index');
    Route::get('/sellers/create', [ManagerSellerController::class, 'create'])->name('sellers.create');
    Route::post('/sellers', [ManagerSellerController::class, 'store'])->middleware('throttle:10,60')->name('sellers.store');
    Route::get('/sellers/online', [ManagerSellerController::class, 'onlineSellers'])->name('sellers.online');
    Route::get('/sellers/{user}', [ManagerSellerController::class, 'show'])->name('sellers.show');
    Route::get('/sellers/{user}/edit', [ManagerSellerController::class, 'edit'])->name('sellers.edit');
    Route::put('/sellers/{user}', [ManagerSellerController::class, 'update'])->name('sellers.update');
    Route::delete('/sellers/{user}', [ManagerSellerController::class, 'destroy'])->middleware('throttle:10,60')->name('sellers.destroy');
    Route::post('/sellers/{user}/reset-password', [ManagerSellerController::class, 'resetPassword'])->middleware('throttle:30,1')->name('sellers.reset-password');
    Route::post('/sellers/{user}/toggle-status', [ManagerSellerController::class, 'toggleStatus'])->middleware('throttle:20,60')->name('sellers.toggle-status');
    Route::post('/sellers/{user}/force-logout', [ManagerSellerController::class, 'forceLogout'])->middleware('throttle:20,60')->name('sellers.force-logout');
    Route::post('/sellers/{user}/cash-register/close', [ManagerSellerController::class, 'closeCashRegister'])
        ->middleware('throttle:10,60')
        ->name('sellers.cash-register.close');
    Route::post('/sellers/{user}/cash-balance', [ManagerSellerController::class, 'adjustCashBalance'])
        ->middleware('throttle:10,60')
        ->name('sellers.cash-balance');

    // Gestion des catégories
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->middleware('throttle:20,60')->name('categories.toggle-status');

    // Gestion des produits
    Route::get('/products/low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
    Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('throttle:20,60')->name('products.toggle-status');
    Route::resource('products', ProductController::class);

    // Gestion du stock
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low-stock', [StockController::class, 'lowStock'])->name('stock.low-stock');
    Route::get('/stock/restock', [StockController::class, 'showRestockForm'])->name('stock.restock');
    Route::post('/stock/restock', [StockController::class, 'restock'])->middleware('throttle:20,60')->name('stock.restock.store');
    Route::post('/stock/adjust', [StockController::class, 'adjust'])->middleware('throttle:20,60')->name('stock.adjust');
    Route::post('/stock/remove', [StockController::class, 'remove'])->middleware('throttle:20,60')->name('stock.remove');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');

    // Ventes
    Route::get('/sales', [ManagerDashboardController::class, 'sales'])->name('sales');
    Route::get('/sales/export/excel', [ManagerDashboardController::class, 'exportSalesExcel'])->name('sales.export.excel');
    Route::get('/sales/export/pdf', [ManagerDashboardController::class, 'exportSalesPdf'])->name('sales.export.pdf');

    // Rapports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/activity', [ReportController::class, 'activityReport'])->name('reports.activity');
    Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
    Route::get('/reports/sales/export/pdf', [ReportController::class, 'exportSalesReportPdf'])->name('reports.sales.export.pdf');
});

/*
| Seller Routes
*/
Route::prefix('seller')->name('seller.')->middleware(['auth', 'role:seller'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/close-cash-register', [SellerDashboardController::class, 'closeCashRegister'])
        ->middleware('throttle:5,60')
        ->name('dashboard.close-cash-register');
    Route::post('/dashboard/open-cash-register', [SellerDashboardController::class, 'openCashRegister'])
        ->middleware('throttle:5,60')
        ->name('dashboard.open-cash-register');

    // Point de vente (POS)
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
    Route::get('/pos/products', [POSController::class, 'products'])->name('pos.products');
    Route::post('/pos/sale', [POSController::class, 'processSale'])->name('pos.sale');
    // Reçu de vente
    Route::get('/pos/{sale}/receipt', [POSController::class, 'printReceipt'])->name('pos.receipt');

    // Mes ventes
    Route::get('/sales/history', [POSController::class, 'salesHistory'])->name('sales.history');
    Route::get('/sales/{sale}', [POSController::class, 'showSale'])->name('sales.show');

    // Produits (consultation uniquement)
    Route::get('/products', [SellerDashboardController::class, 'products'])->name('products');
    Route::get('/products/{product}', [SellerDashboardController::class, 'showProduct'])->name('products.show');

    // Mes statistiques
    Route::get('/my-stats', [SellerDashboardController::class, 'myStats'])->name('my-stats');
});
