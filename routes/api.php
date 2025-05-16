<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



use App\Http\Controllers\Api\AuthController;

Route::group([
    "middleware" => "api",
    "prefix" => "auth"
], function ($router) {
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::post("refresh", [AuthController::class, "refresh"]);
    Route::get("user-profile", [AuthController::class, "userProfile"]);
    Route::post("verify-otp", [AuthController::class, "verifyOtp"]);
    Route::post("resend-otp", [AuthController::class, "resendOtp"]);
    Route::post("update-profile", [AuthController::class, "updateProfile"]);
    Route::post("change-password", [AuthController::class, "changePassword"]);
});




use App\Http\Controllers\Api\EquipmentCategoryController;
use App\Http\Controllers\Api\EquipmentController;

// Equipment Categories Routes
Route::apiResource("equipment-categories", EquipmentCategoryController::class);

// Equipment Routes
Route::apiResource("equipment", EquipmentController::class);




use App\Http\Controllers\Api\RentalController;

Route::group(["middleware" => "auth:api"], function () {
    Route::get("rentals/my-rentals", [RentalController::class, "indexUser"]);
    Route::get("rentals/owner-rentals", [RentalController::class, "indexOwner"]);
    Route::get("rentals/all", [RentalController::class, "indexAdmin"])->middleware("permission:manage_rentals"); // Admin only
    Route::post("rentals", [RentalController::class, "store"]);
    Route::get("rentals/{rental}", [RentalController::class, "show"]);
    Route::patch("rentals/{rental}/status", [RentalController::class, "updateStatus"]); // Permissions handled in controller
});




use App\Http\Controllers\Api\PaymentController;

Route::group(["middleware" => "auth:api"], function () {
    Route::get("payments/my-payments", [PaymentController::class, "indexUser"]);
    Route::get("payments/owner-payments", [PaymentController::class, "indexOwner"]);
    Route::get("payments/all", [PaymentController::class, "indexAdmin"])->middleware("permission:manage_payments");
    Route::post("payments/initiate", [PaymentController::class, "initiatePayment"]);
    Route::get("payments/{payment}", [PaymentController::class, "show"]);
    Route::patch("payments/{payment}/status", [PaymentController::class, "updatePaymentStatusAdmin"])->middleware("permission:manage_payments");
});

// This is a public route for payment gateway callbacks, no auth middleware
Route::post("payments/gateway-callback", [PaymentController::class, "handleGatewayCallback"]);

