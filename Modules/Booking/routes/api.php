<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\Backend\API\BookingsController;
use Modules\Booking\Http\Controllers\Backend\API\PaymentController;
use Modules\Booking\Models\Booking;

Route::group(['middleware' => 'auth:sanctum', 'as' => 'backend.'], function () {
    Route::apiResource('bookings', BookingsController::class);
    // GET all bookings api
    Route::get('all-bookings', [BookingsController::class, 'index']);
    Route::post('booking-update', [BookingsController::class, 'update']);
    Route::get('booking-list', [BookingsController::class, 'bookingList']);
    Route::get('booking-detail', [BookingsController::class, 'bookingDetail']);
    Route::get('search-booking', [BookingsController::class, 'searchBookings']);
    Route::post('save-booking', [BookingsController::class, 'store']);
    Route::post('save-payment', [PaymentController::class, 'savePayment']);
    Route::get('booking-status', [BookingsController::class, 'statusList']);
    Route::get('booking-invoice-download', [Modules\Booking\Http\Controllers\Backend\BookingsController::class, 'downloadInvoice'])->name('bookings.downloadinvoice');
    Route::post('store-in-queue', [BookingsController::class, 'storeInQueue']);
    Route::get('businesshour', [BookingsController::class, 'getBusinessHours']);
    Route::get('service-dates', [BookingsController::class, 'getServiceDates']);
    Route::get('time-slots', [BookingsController::class, 'getTimeSlots']);
});