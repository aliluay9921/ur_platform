<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ChangeCurrncyController;
use App\Http\Controllers\PaymentMethodsController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post("login", [AuthController::class, "login"]);
Route::post("register", [AuthController::class, "register"]);
Route::post('forget_password', [AuthController::class, 'forgetPassword']); //->middleware('throttle:1,3');
route::get("get_countries", [AuthController::class, "getCountries"]);




Route::middleware(['auth:api', 'restrict', 'localization'])->group(function () {


    route::get("info_auth", [AuthController::class, "infoAuth"]);
    route::put("activation_account", [AuthController::class, "activationAccount"]);

    Route::middleware('active')->group(function () {
        route::get("get_transactions", [TransactionController::class, "getTransactions"]);
        route::get("get_order_status_by_transactions", [OrderStatusController::class, "getOrderStatusByTransactions"]);
        route::get("get_cards", [CardController::class, "getCards"]);
        route::get("get_order_key_types", [AdminController::class, "getOrderKeyTypes"]);
        route::get("get_companies", [CompanyController::class, "getCompanies"]);
        route::get("get_payment_methods", [PaymentMethodsController::class, "getPaymentMethods"]);
        route::get("get_currencies", [ChangeCurrncyController::class, "getCurrency"]);
        route::get("get_tickets", [TicketController::class, "getTickets"]);

        route::post("change_email", [AuthController::class, 'changeEmail']);
        route::post("check_user_name", [AuthController::class, "checkUserName"]);
        route::post("buy_card", [CardController::class, "buyCard"]);
        route::post("add_comment_ticket", [TicketController::class, "addCommentTicket"]);
        route::post("open_ticket", [TicketController::class, "openTicket"]);
        route::post("add_deposit", [TransactionController::class, "addDeposit"]);
        route::post("add_withdraw", [TransactionController::class, "addWithdraw"]);

        route::delete("delete_comment", [TicketController::class, "deleteComment"]);

        route::put("close_ticket", [TicketController::class, "closeTicket"]);
        route::put("update_auth_user", [AuthController::class, "updateAuthUser"]);

        Route::middleware('admin')->group(function () {

            route::get("get_admin_logs", [AdminController::class, "getAdminLogs"]);
            route::get("get_users", [AdminController::class, "getUsers"]);

            route::post("add_company", [CompanyController::class, "addCompany"]);
            route::post("add_payment_method", [PaymentMethodsController::class, "addPaymentMethod"]);
            route::post("add_card", [CardController::class, "addCard"]);
            route::post("add_serial_card", [CardController::class, "addSerialCard"]);
            route::post("add_currency", [ChangeCurrncyController::class, "addCurrency"]);
            route::put("change_type_order_status", [OrderStatusController::class, "changeTypeOrderStatus"]);
            route::put("edit_currency", [ChangeCurrncyController::class, "editCurrency"]);
            route::put("edit_card", [CardController::class, "editCard"]);
            route::put("edit_payment_method", [PaymentMethodsController::class, "editPaymentMethod"]);
            route::put("edit_company", [CompanyController::class, "editCompany"]);
            route::put("toggle_active_company", [CompanyController::class, "toggleActiveCompany"]);
            route::put("toggle_active_payment_method", [PaymentMethodsController::class, "toggleActivePaymentMethod"]);
            route::put("toggle_restrict_user", [AuthController::class, "toggleRestrictUser"]);
            route::post("edit_images", [CompanyController::class, "editImage"]);
            route::delete("delete_currency", [ChangeCurrncyController::class, "deleteCurrency"]);
            route::delete("delete_company", [CompanyController::class, "deleteCompany"]);
            route::delete("delete_payment_method", [PaymentMethodsController::class, "deletePaymentMethod"]);
            route::delete("delete_card", [CardController::class, "deleteCard"]);
        });
    });
});