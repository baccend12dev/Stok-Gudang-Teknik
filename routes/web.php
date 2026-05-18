<?php

use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Login
Route::get('/login', 'AuthController@showLoginForm')->name('login');
Route::post('/login', 'AuthController@login')->name('login.post');

Route::group(['middleware' => 'auth'], function () {
    // Dashboard
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

    //DETAIL DASHBOARD & EXPORT
    Route::get('/dashboard/detail-out', 'DashboardController@detailOut')->name('dashboard.detail.out');
    Route::get('/dashboard/detail-out/export', 'DashboardController@exportDetailOut')->name('dashboard.detail.out.export');
    
    Route::get('/dashboard/detail-in', 'DashboardController@detailIn')->name('dashboard.detail.in');
    Route::get('/dashboard/detail-in/export', 'DashboardController@exportDetailIn')->name('dashboard.detail.in.export');

    /** KATALOG BARANG (Read-Only, untuk semua user termasuk Dept) */
    Route::get('/catalog', 'ItemCatalogController@index')->name('catalog.index');

    /** ITEMS */
    Route::resource('items', 'ItemController');

    /** Buffer Alerts */
    Route::get('/buffer-alerts', 'BufferAlertController@index')->name('buffer-alerts.index');
    Route::get('/buffer-alerts/export', 'BufferAlertController@exportExcel')->name('buffer-alerts.export.excel');

    // Import
    Route::get('/items/import/form', 'ItemController@importForm')->name('items.import.form');
    Route::post('/items/import', 'ItemController@import')->name('items.import');

    /** LPB (barang masuk) */
    Route::resource('lpbs', 'LpbController');

    Route::get('/lpbs', 'LpbController@index')->name('lpbs.index');
    Route::get('/lpbs/create', 'LpbController@create')->name('lpbs.create');
    Route::post('/lpbs', 'LpbController@store')->name('lpbs.store');

    // >>> tambahkan CETAK dan letakkan di atas /{id}
    Route::get('/lpbs/{id}/edit', 'LpbController@edit')->name('lpbs.edit');
    // Route::get('/lpbs/{id}/print', 'LpbController@printView')->name('lpbs.print'); // <-- Dihapus

    Route::put('/lpbs/{id}', 'LpbController@update')->name('lpbs.update');
    Route::delete('/lpbs/{id}', 'LpbController@destroy')->name('lpbs.destroy');

    /** STOCK OPNAME */
    // 1. API & Helper Routes (Ditaruh paling atas agar tidak dianggap sebagai ID oleh Laravel)
    Route::get('api/so/items-by-category', 'StockOpnameController@getItemsByCategory')->name('api.so.items_by_category');
    Route::get('stock-opnames/get-all-items', 'StockOpnameController@getAllItems')->name('stock-opnames.get_all_items');

    // 2. Custom Actions (Process Adjustment & Export Excel)
    Route::post('stock-opnames/{id}/process', 'StockOpnameController@process')->name('stock-opnames.process');
    Route::post('stock-opnames/{id}/rollback', 'StockOpnameController@rollback')->name('stock-opnames.rollback');
    Route::get('stock-opnames/{id}/export', 'StockOpnameController@exportExcel')->name('stock-opnames.export');

    // 3. Standard CRUD (Index, Create, Store, Show, Edit, Update, Destroy)
    // Route::resource otomatis membuat semua route dasar di atas
    Route::resource('stock-opnames', 'StockOpnameController');

    // === BON Keluar ===
    Route::group(['prefix' => 'bons'], function () {

        // API untuk mengambil Divisi berdasarkan Departemen (INI YANG HILANG TADI)
        Route::get('api/divisions', 'BonController@getDivisionsByDept')->name('api.divisions');

        // AJAX lookup item
        Route::get('lookup-items',  'BonController@lookupItems')->name('bons.lookup.items');
        Route::get('resolve-items', 'BonController@resolveItems')->name('bons.resolve.items');

        // APPROVE & REJECT
        Route::post('{id}/approve', ['uses' => 'BonController@approve', 'as' => 'bons.approve']);
        Route::post('{id}/reject',  ['uses' => 'BonController@reject',  'as' => 'bons.reject']);

        // ISSUE & CANCEL & ROLLBACK
        Route::match(['get', 'post'], '{id}/issue', [
            'uses' => 'BonController@issue',
            'as'   => 'bons.issue',
        ]);

        Route::match(['get', 'post'], '{id}/cancel', [
            'uses' => 'BonController@cancel',
            'as'   => 'bons.cancel',
        ]);
        
        Route::post('{id}/rollback', 'BonController@rollback')->name('bons.rollback');

        Route::get('{id}/print', 'BonController@printBon')->name('bons.print');
    });

    // Resource utama BON
    Route::resource('bons', 'BonController');

    /** Departments */
    Route::get('/departments', 'DepartmentController@index')->name('departments.index');

    /** Reports */
    Route::get('/reports',                 'ReportController@index')->name('reports.index');
    Route::get('/reports/items',           'ReportController@items')->name('reports.items');
    Route::get('/reports/stock-card',      'ReportController@stockCard')->name('reports.stock-card');
    Route::get('/reports/department-usage','ReportController@departmentUsage')->name('reports.department-usage');
    Route::get('/reports/saldo',           'ReportController@saldo')->name('reports.saldo');

    // Laporan BON
    Route::get('/reports/bon-items',        'BonController@reportPerItem')->name('reports.bon-items');
    Route::get('/reports/bon-items/export', 'ReportController@exportBonItems')->name('reports.bon-items.export');

    // Laporan LPB
    Route::get('/reports/lpb-items',        'ReportController@reportLpbItems')->name('reports.lpb-items');
    Route::get('/reports/lpb-items/export', 'ReportController@exportLpbItems')->name('reports.lpb-items.export');

    Route::group(['prefix' => 'reports'], function () {
        // INVENTORY MONTHLY (SHEET ALL)
        Route::get('inventory-monthly', 'InventoryMonthlyReportController@index')->name('reports.inventory_monthly.index');
        Route::get('inventory-monthly/export', 'InventoryMonthlyReportController@exportExcel')->name('reports.inventory_monthly.export');
        
        // ROUTE BARU: UNTUK FITUR DRILL-DOWN (AJAX)
        Route::get('inventory-monthly/drill-down', 'InventoryMonthlyReportController@getDrillDown')->name('reports.inventory_monthly.drill_down');
    });

    // ======================
    // REQUESTS (Dept Request)
    // ======================
    Route::get('/requests', 'RequestController@index')->name('requests.index');
    Route::get('/requests/create', 'RequestController@create')->name('requests.create');
    Route::post('/requests', 'RequestController@store')->name('requests.store');
    
    Route::get('/requests/recap/export', 'RequestController@exportRecapExcel')->name('requests.recap.export');
    Route::get('/requests/recap', 'RequestController@recap')->name('requests.recap'); 
    
    // --- TAMBAHAN ROUTE EDIT & DELETE ---
    Route::get('/requests/{id}/edit', 'RequestController@edit')->name('requests.edit');
    Route::put('/requests/{id}', 'RequestController@update')->name('requests.update');
    Route::delete('/requests/{id}', 'RequestController@destroy')->name('requests.destroy');
    
    // --- EDIT PARSIAL (SUPER ADMIN ONLY: Edit item yang belum diproses) ---
    Route::get('/requests/{id}/edit-partial', 'RequestController@editPartial')->name('requests.editPartial');
    Route::put('/requests/{id}/update-partial', 'RequestController@updatePartial')->name('requests.updatePartial');
    // ------------------------------------
    Route::post('/requests/close-period', 'RequestController@closePeriod')->name('requests.closePeriod');

    Route::get('/requests/{id}', 'RequestController@show')->name('requests.show');
    Route::resource('requests', 'RequestController');

    // Admin actions
    Route::post('/requests/{id}/approve', 'RequestController@approve')->name('requests.approve');
    Route::post('/requests/{id}/unapprove', 'RequestController@unapprove')->name('requests.unapprove');
    Route::post('/requests/{id}/reject', 'RequestController@reject')->name('requests.reject');
    Route::post('/requests/{id}/cancel', 'RequestController@cancel')->name('requests.cancel');

    // Create BON from Request (MY SCOPE)
    Route::post('/requests/{id}/create-bon', 'RequestController@createBonFromRequest')->name('requests.createBon');


    // === Export CSV ===
    Route::get('/reports/stock-card/export',       'ReportController@exportStockCard')->name('reports.stock-card.export');
    Route::get('/reports/department-usage/export', 'ReportController@exportDepartmentUsage')->name('reports.department-usage.export');
    Route::get('/reports/saldo/export',            'ReportController@exportSaldo')->name('reports.saldo.export');
    
    // ============================================================
    // USER MANAGEMENT (SUPER ADMIN ONLY)
    // ============================================================
    Route::resource('users', 'UserController');
    // Route khusus Reset Password User
    Route::post('users/{id}/reset-password', 'UserController@resetPassword')->name('users.resetPassword');

    // ============================================================
    // PROFILE (SELF SERVICE - SEMUA USER)
    // ============================================================
    Route::get('profile', 'ProfileController@edit')->name('profile.edit');
    Route::post('profile/password', 'ProfileController@updatePassword')->name('profile.password.update');

    // MASTER DATA: DEPARTMENT BUDGETS (PLAFON)
    Route::get('/budgets', 'DepartmentBudgetController@index')->name('budgets.index');
    Route::post('/budgets', 'DepartmentBudgetController@store')->name('budgets.store');
});

Route::get('/logout', 'AuthController@logout')->name('logout');
