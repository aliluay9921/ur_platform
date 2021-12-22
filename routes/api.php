<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
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
        route::put("update_auth_user", [AuthController::class, "updateAuthUser"]);
    });
    Route::middleware('admin')->group(function () {

        route::post("add_company", [CompanyController::class, "addCompany"]);
        route::put("toggle_restrict_user", [AuthController::class, "toggleRestrictUser"]);
    });
});