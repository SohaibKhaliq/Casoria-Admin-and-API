<?php

use App\Http\Controllers\Auth\API\AuthController;
use App\Http\Controllers\Backend\API\AddressController;
use App\Http\Controllers\Backend\API\BusinessController;
use App\Http\Controllers\Backend\API\DashboardController;
use App\Http\Controllers\Backend\API\NotificationsController;
use App\Http\Controllers\Backend\API\SettingController;
use App\Http\Controllers\Backend\API\UserApiController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('business-list', [BusinessController::class, 'businessList']);
Route::get('user-detail', [AuthController::class, 'userDetails']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('social-login', 'socialLogin');
    Route::post('forgot-password', 'forgotPassword');
    Route::get('logout', 'logout');
});

Route::get('dashboard-detail', [DashboardController::class, 'dashboardDetail']);
Route::get('business-configuration', [BusinessController::class, 'businessConfig']);
Route::get('business-detail', [BusinessController::class, 'businessDetails']);
Route::get('business-service', [BusinessController::class, 'businessService']);
Route::get('business-review', [BusinessController::class, 'businessReviews']);
Route::get('business-employee', [BusinessController::class, 'businessEmployee']);
Route::get('business-gallery', [BusinessController::class, 'businessGallery']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('business/assign/{id}', [BusinessController::class, 'assign_update']);
    Route::apiResource('business', BusinessController::class);
    Route::apiResource('user', UserApiController::class);
    Route::apiResource('setting', SettingController::class);
    Route::apiResource('notification', NotificationsController::class);
    Route::get('notification-list', [NotificationsController::class, 'notificationList']);
    Route::get('gallery-list', [DashboardController::class, 'globalGallery']);
    Route::get('search-list', [DashboardController::class, 'searchList']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);

    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('delete-account', [AuthController::class, 'deleteAccount']);

    Route::post('add-address', [AddressController::class, 'store']);
    Route::get('address-list', [AddressController::class, 'AddressList']);
    Route::get('remove-address', [AddressController::class, 'RemoveAddress']);
    Route::post('edit-address', [AddressController::class, 'EditAddress']);

    Route::post('verify-slot', [BusinessController::class, 'verifySlot']);
});
Route::post('app-configuration', [SettingController::class, 'appConfiguraton']);
