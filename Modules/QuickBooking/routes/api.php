<?php

use Illuminate\Support\Facades\Route;
use Modules\QuickBooking\Http\Controllers\Backend\QuickBookingsController;

Route::group(['prefix' => 'quick-booking'], function () {
    Route::get('business-list', [QuickBookingsController::class, 'business_list']);
    Route::get('slot-time-list', [QuickBookingsController::class, 'slot_time_list']);
    Route::get('slot-date-list', [QuickBookingsController::class, 'slot_date_list']);
    Route::get('services-list', [QuickBookingsController::class, 'services_list']);
    Route::get('employee-list', [QuickBookingsController::class, 'employee_list']);
    Route::post('store', [QuickBookingsController::class, 'create_booking'])->name('api.quick_bookings.store');
});
