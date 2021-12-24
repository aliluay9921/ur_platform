<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ChangeCurrncyController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PaymentMethodsController;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::middleware(['auth:api', 'restrict'])->group(function () {
    route::put("activation_account", [AuthController::class, "activationAccount"]);
    Route::middleware('active')->group(function () {
        route::get("info_auth", [AuthController::class, "infoAuth"]);
        route::get("get_cards", [CardController::class, "getCards"]);
        route::get("get_companies", [CompanyController::class, "getCompanies"]);
        route::get("get_payment_methods", [PaymentMethodsController::class, "getPaymentMethods"]);
        route::put("update_auth_user", [AuthController::class, "updateAuthUser"]);
    });
    Route::middleware('admin')->group(function () {

        route::post("add_company", [CompanyController::class, "addCompany"]);
        route::post("add_payment_method", [PaymentMethodsController::class, "addPaymentMethod"]);
        route::post("add_card", [CardController::class, "addCard"]);
        route::post("add_currency", [ChangeCurrncyController::class, "addCurrency"]);

        route::put("edit_currency", [ChangeCurrncyController::class, "editCurrency"]);
        route::put("edit_card", [CardController::class, "editCard"]);
        route::put("edit_payment_method", [PaymentMethodsController::class, "editPaymentMethod"]);
        route::put("edit_company", [CompanyController::class, "editCompany"]);
        route::put("toggle_active_company", [CompanyController::class, "toggleActiveCompany"]);
        route::put("toggle_restrict_user", [AuthController::class, "toggleRestrictUser"]);
    });
});