<?php

use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\ActivityController;
use App\Http\Controllers\Dashboard\AttendanceController;
use App\Http\Controllers\Dashboard\CalendarController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\DishController;
use App\Http\Controllers\Dashboard\EnquiryInboxController;
use App\Http\Controllers\Dashboard\EquipmentController;
use App\Http\Controllers\Dashboard\ExcelExportController;
use App\Http\Controllers\Dashboard\ExcelImportController;
use App\Http\Controllers\Dashboard\ExpenseController;
use App\Http\Controllers\Dashboard\InventoryItemController;
use App\Http\Controllers\Dashboard\MyTimesheetController;
use App\Http\Controllers\Dashboard\OrderBookController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\OrderPaymentController;
use App\Http\Controllers\Dashboard\PrepController;
use App\Http\Controllers\Dashboard\PurchaseOrderController;
use App\Http\Controllers\Dashboard\QuoteController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\StaffController;
use App\Http\Controllers\Dashboard\StockCountController;
use App\Http\Controllers\Dashboard\SupplierController;
use App\Http\Controllers\Dashboard\TimesheetController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\WasteLogController;
use App\Http\Controllers\Dashboard\WeeklySummaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing site
|--------------------------------------------------------------------------
*/

Route::name('site.')->group(function (): void {
    Route::view('/', 'site.home')->name('home');
    Route::view('/services', 'site.services')->name('services');
    Route::view('/menu', 'site.menu')->name('menu');
    Route::view('/gallery', 'site.gallery')->name('gallery');
    Route::view('/about', 'site.about')->name('about');

    Route::get('/contact', [EnquiryController::class, 'create'])->name('contact');
    Route::post('/contact', [EnquiryController::class, 'store'])->name('enquiry.store');

    Route::get('/services/{slug}', function (string $slug) {
        $service = collect(config('catering_services'))->firstWhere('slug', $slug);

        abort_if($service === null, 404);

        return view('site.service', ['service' => $service]);
    })->name('service');
});

/**
 * Sitemap for search engines: the public pages plus one per service, so a new
 * service in config/catering_services.php is listed without touching this.
 */
Route::get('/sitemap.xml', function () {
    $urls = collect(['site.home', 'site.services', 'site.menu', 'site.gallery', 'site.about', 'site.contact'])
        ->map(fn (string $name): string => route($name))
        ->merge(collect(config('catering_services'))->map(fn (array $service): string => route('site.service', $service['slug'])));

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

/**
 * Language toggle. Urdu is the client's working language, English alongside.
 * The choice is saved against the account so it survives the next request.
 */
Route::get('/locale/{locale}', function (Request $request, string $locale): RedirectResponse {
    if (in_array($locale, ['en', 'ur'], true)) {
        session(['locale' => $locale]);
        $request->user()?->forceFill(['locale' => $locale])->save();
    }

    return back();
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Operations dashboard
|--------------------------------------------------------------------------
|
| Grouped by the minimum role level that may reach each area:
|
|   10  staff        own timesheet and account only
|   30  sales        enquiries, quotes, orders
|   40  purchasing   supplier orders and receiving
|   50  kitchen      stock, counts, waste, recipes, prep
|   60  finance      expenses and reports
|   80  management   people, pay, activity
|  100  owner        settings
|
*/

Route::middleware(['auth', 'active'])->prefix('dashboard')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* ------------------------------------------------- everyone, own data */
    Route::get('my-timesheet', [MyTimesheetController::class, 'index'])->name('my-timesheet');
    Route::get('account', [AccountController::class, 'show'])->middleware('password.confirm')->name('account');

    /* ------------------------------------------------------------ sales */
    Route::middleware('role:30')->group(function (): void {
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar');

        Route::get('enquiries', [EnquiryInboxController::class, 'index'])->name('enquiries');
        Route::get('enquiries/{enquiry}', [EnquiryInboxController::class, 'show'])->name('enquiries.show');
        Route::patch('enquiries/{enquiry}', [EnquiryInboxController::class, 'update'])->name('enquiries.update');
        Route::delete('enquiries/{enquiry}', [EnquiryInboxController::class, 'destroy'])->name('enquiries.destroy');
        Route::post('enquiries/{enquiry}/convert', [EnquiryInboxController::class, 'convert'])->name('enquiries.convert');

        // Quotes are prices: sales, finance, management and the owner only.
        Route::middleware('can:handle-quotes')->group(function (): void {
            Route::post('enquiries/{enquiry}/quote', [EnquiryInboxController::class, 'quote'])->name('enquiries.quote');

            Route::get('quotes', [QuoteController::class, 'index'])->name('quotes');
            Route::get('quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
            Route::post('quotes', [QuoteController::class, 'store'])->name('quotes.store');
            Route::get('quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
            Route::get('quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print');
            Route::patch('quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
            Route::delete('quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
            Route::post('quotes/{quote}/lines', [QuoteController::class, 'storeLine'])->name('quotes.lines.store');
            Route::delete('quotes/lines/{line}', [QuoteController::class, 'destroyLine'])->name('quotes.lines.destroy');
            Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
            Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
            Route::post('quotes/{quote}/decline', [QuoteController::class, 'decline'])->name('quotes.decline');
        });

        Route::get('orders', [OrderController::class, 'index'])->name('orders');
        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('orders/{order}/dishes', [OrderController::class, 'storeDish'])->name('orders.dishes.store');
        Route::delete('orders/dishes/{orderDish}', [OrderController::class, 'destroyDish'])->name('orders.dishes.destroy');
        Route::post('orders/{order}/deduct', [OrderController::class, 'deductIngredients'])->name('orders.deduct');
        Route::post('orders/{order}/tasks', [OrderController::class, 'storeTask'])->name('orders.tasks.store');
        Route::post('orders/{order}/tasks/standard', [OrderController::class, 'seedTasks'])->name('orders.tasks.standard');

        // Money on a job: management and the owner, checked here as well as in the page.
        Route::middleware('can:see-financials')->group(function (): void {
            Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
            Route::post('orders/{order}/items', [OrderController::class, 'storeItem'])->name('orders.items.store');
            Route::delete('orders/items/{item}', [OrderController::class, 'destroyItem'])->name('orders.items.destroy');
            Route::post('orders/{order}/payments', [OrderPaymentController::class, 'store'])->name('orders.payments.store');
            Route::delete('payments/{payment}', [OrderPaymentController::class, 'destroy'])->name('payments.destroy');

            // The simple order book: whole order on one page, entered weekly.
            Route::get('order-book/new', [OrderBookController::class, 'create'])->name('order-book.create');
            Route::post('order-book', [OrderBookController::class, 'store'])->name('order-book.store');
            Route::get('order-book/{order}/edit', [OrderBookController::class, 'edit'])->name('order-book.edit');
            Route::put('order-book/{order}', [OrderBookController::class, 'update'])->name('order-book.update');
            Route::get('payments', [OrderBookController::class, 'payments'])->name('payments');
            Route::get('weekly-summary', [WeeklySummaryController::class, 'index'])->name('weekly-summary');
            Route::get('weekly-summary/export', [WeeklySummaryController::class, 'export'])->name('weekly-summary.export');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers');
            Route::get('customers/{key}', [CustomerController::class, 'show'])->name('customers.show');
        });
    });

    /* ---------------------------------------------------------- kitchen */
    Route::middleware('role:50')->group(function (): void {
        Route::get('dishes', [DishController::class, 'index'])->name('dishes');
        Route::get('dishes/create', [DishController::class, 'create'])->name('dishes.create');
        Route::post('dishes', [DishController::class, 'store'])->name('dishes.store');
        Route::get('dishes/{dish}', [DishController::class, 'show'])->name('dishes.show');
        Route::patch('dishes/{dish}', [DishController::class, 'update'])->name('dishes.update');
        Route::delete('dishes/{dish}', [DishController::class, 'destroy'])->name('dishes.destroy');
        Route::post('dishes/{dish}/ingredients', [DishController::class, 'storeIngredient'])->name('dishes.ingredients.store');
        Route::delete('dishes/ingredients/{ingredient}', [DishController::class, 'destroyIngredient'])->name('dishes.ingredients.destroy');

        Route::get('prep', [PrepController::class, 'index'])->name('prep');
        Route::post('prep/{task}/toggle', [PrepController::class, 'toggle'])->name('prep.toggle');
        Route::delete('prep/{task}', [PrepController::class, 'destroy'])->name('prep.destroy');

        Route::get('inventory', [InventoryItemController::class, 'index'])->name('inventory');
        Route::get('inventory/create', [InventoryItemController::class, 'create'])->name('inventory.create');
        Route::get('inventory/{item}', [InventoryItemController::class, 'show'])->name('inventory.show');
        Route::post('inventory/{item}/adjust', [InventoryItemController::class, 'adjust'])->name('inventory.adjust');

        Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts');
        Route::get('stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
        Route::post('stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
        Route::get('stock-counts/{stockCount}', [StockCountController::class, 'show'])->name('stock-counts.show');
        Route::patch('stock-counts/{stockCount}', [StockCountController::class, 'update'])->name('stock-counts.update');
        Route::post('stock-counts/{stockCount}/complete', [StockCountController::class, 'complete'])->name('stock-counts.complete');
        Route::delete('stock-counts/{stockCount}', [StockCountController::class, 'destroy'])->name('stock-counts.destroy');

        Route::get('waste', [WasteLogController::class, 'index'])->name('waste');
        Route::post('waste', [WasteLogController::class, 'store'])->name('waste.store');
        Route::delete('waste/{wasteLog}', [WasteLogController::class, 'destroy'])->name('waste.destroy');

        Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment');
        Route::post('equipment', [EquipmentController::class, 'store'])->name('equipment.store');
        Route::patch('equipment/{equipmentItem}', [EquipmentController::class, 'update'])->name('equipment.update');
        Route::delete('equipment/{equipmentItem}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');
        Route::post('equipment/{assignment}/return', [EquipmentController::class, 'returnItems'])->name('equipment.return');
        Route::post('equipment/maintenance', [EquipmentController::class, 'storeMaintenance'])->name('equipment.maintenance.store');
        Route::post('equipment/maintenance/{maintenance}/complete', [EquipmentController::class, 'completeMaintenance'])->name('equipment.maintenance.complete');
    });

    /* ------------------------------------------------------- purchasing */
    Route::middleware('role:40')->group(function (): void {
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::patch('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');
    });

    /* ----------------------------------------------- inventory writes */
    Route::middleware('role:80')->group(function (): void {
        Route::post('inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
        Route::get('inventory/{item}/edit', [InventoryItemController::class, 'edit'])->name('inventory.edit');
        Route::patch('inventory/{item}', [InventoryItemController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{item}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');
    });

    /* ----------------------------------------------------------- money */
    Route::middleware('role:60')->group(function (): void {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('reports', [ReportController::class, 'index'])->name('reports');
        Route::get('reports/export/{report}', [ReportController::class, 'export'])->name('reports.export');
    });

    /* ---------------------------------------------------------- people */
    Route::middleware('role:80')->group(function (): void {
        Route::get('staff', [StaffController::class, 'index'])->name('staff');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('staff/{staffProfile}', [StaffController::class, 'show'])->name('staff.show');
        Route::patch('staff/{staffProfile}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('staff/{staffProfile}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('staff/{staffProfile}/leave', [StaffController::class, 'storeLeave'])->name('staff.leave.store');
        Route::post('leave/{leaveRequest}/decide', [StaffController::class, 'decideLeave'])->name('leave.decide');

        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance');
        Route::post('attendance/roster', [AttendanceController::class, 'roster'])->name('attendance.roster');
        Route::post('attendance/{record}/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('attendance/{record}/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::post('attendance/{record}/absent', [AttendanceController::class, 'markAbsent'])->name('attendance.absent');
        Route::delete('attendance/{record}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::post('attendance/manual', [AttendanceController::class, 'storeManual'])->name('attendance.manual');

        Route::get('timesheets', [TimesheetController::class, 'index'])->name('timesheets');
        Route::post('timesheets/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve');
        Route::post('timesheets/pay', [TimesheetController::class, 'pay'])->name('timesheets.pay');
        Route::delete('wage-payments/{wagePayment}', [TimesheetController::class, 'destroyPayment'])->name('wage-payments.destroy');
        Route::post('attendance/quick', [AttendanceController::class, 'storeQuick'])->name('attendance.quick');

        Route::get('activity', [ActivityController::class, 'index'])->name('activity');

        Route::get('logins', [UserController::class, 'index'])->name('users');
        Route::post('logins', [UserController::class, 'store'])->name('users.store');
        Route::patch('logins/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('logins/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    });

    /* ------------------------------------------------ excel in and out */
    Route::middleware('role:80')->group(function (): void {
        Route::get('excel/staff/export', [ExcelExportController::class, 'staff'])->name('excel.staff.export');
        Route::get('excel/shifts/export', [ExcelExportController::class, 'shifts'])->name('excel.shifts.export');
        Route::get('excel/stock/export', [ExcelExportController::class, 'stock'])->name('excel.stock.export');
        Route::get('excel/wages/export', [ExcelExportController::class, 'wages'])->name('excel.wages.export');
        Route::get('excel/shifts/template', [ExcelImportController::class, 'shiftsTemplate'])->name('excel.shifts.template');
        Route::post('excel/shifts/import', [ExcelImportController::class, 'shifts'])->name('excel.shifts.import');

        Route::get('excel/staff/template', [ExcelImportController::class, 'staffTemplate'])->name('excel.staff.template');
        Route::get('excel/stock/template', [ExcelImportController::class, 'stockTemplate'])->name('excel.stock.template');
        Route::post('excel/staff/import', [ExcelImportController::class, 'staff'])->name('excel.staff.import');
        Route::post('excel/stock/import', [ExcelImportController::class, 'stock'])->name('excel.stock.import');
    });

    /* --------------------------------------------------------- settings */
    Route::middleware('role:100')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings');
        Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
