<?php

namespace Modules\QuickBooking\Http\Controllers\Backend;

use App\Events\Backend\UserCreated;
use App\Http\Controllers\Controller;
use App\Models\Address;
// Traits
use App\Models\Business;
use App\Models\User;
use App\Notifications\UserAccountCreated;
// Listing Models
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Booking\Models\Booking;
use Modules\Booking\Trait\BookingTrait;
// Events
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Holiday\Models\Holiday;
use Modules\Service\Models\Service;
use Modules\Service\Transformers\ServiceResource;
use Modules\Tax\Models\Tax;

class QuickBookingsController extends Controller
{
    use BookingTrait;

    public function index()
    {
        if (! setting('is_quick_booking')) {
            return abort(404);
        }

        return view('quickbooking::backend.quickbookings.index');
    }

    // API Methods for listing api
    public function business_list()
    {
        $list = Business::active()->with('address')->select('id', 'name', 'business_for', 'contact_number', 'contact_email')->get();

        return $this->sendResponse($list, __('booking.booking_business'));
    }

    public function slot_time_list(Request $request)
    {
        $day = date('l', strtotime($request->date));

        $data = $this->requestData($request);
        $businessHours = BussinessHour::where('business_id', $data['business_id'])->get();
        $service = Service::where('id', $data['service_id'])->first();
        $serviceDuration = $service->duration_min;

        $slots = $this->getSlots($data['date'], $day, $data['business_id'], $serviceDuration, $data['employee_id']);

        return $this->sendResponse($slots, $businessHours, __('booking.booking_timeslot'));
    }

    public function slot_date_list(Request $request)
    {
        $data = $this->requestData($request);

        $businessHours = BussinessHour::where('business_id', $data['business_id'])->get();
        $holidays = Holiday::where('business_id', $data['business_id'])->get();
        $holidayDates = $holidays->map(function ($holiday) {
            return Carbon::parse($holiday->date)->format('Y-m-d');
        });

        return response()->json([
            'data' => $businessHours,
            'holidays' => $holidayDates,
        ]);
    }

    public function services_list(Request $request)
    {
        $business_id = $request->business_id;

        $data = $this->requestData($request);

        $item = Business::find($data['business_id']);

        $items = $item->services->where('status', 1);

        $list = ServiceResource::collection($items);

        return $this->sendResponse($list, __('booking.booking_sevice'));
    }

    public function employee_list(Request $request)
    {
        $data = $this->requestData($request);

        $list = User::whereHas('services', function ($query) use ($data) {
            $query->where('service_id', $data['service_id']);
        })
            ->whereHas('businesses', function ($query) use ($data) {
                $query->where('business_id', $data['business_id']);
            })
            ->get();

        return $this->sendResponse($list, __('booking.booking_employee'));
    }

    // Create Method for Booking API
    public function create_booking(Request $request)
    {
        $userRequest = $request->user;
        $user = User::where('email', $userRequest['email'])->first();

        if (! isset($user)) {
            $userRequest['password'] = Hash::make('12345678');
            $user = User::create($userRequest);
            // Sync Roles
            $roles = ['user'];
            $user->syncRoles($roles);

            \Artisan::call('cache:clear');

            event(new UserCreated($user));

            $data = [
                'password' => '12345678',
            ];

            try {
                $user->notify(new UserAccountCreated($data));
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        $bookingData = $request->booking;
        $bookingData['user_id'] = $user->id;
        $bookingData['created_by'] = $user->id;
        $bookingData['updated_by'] = $user->id;
        $booking = Booking::create($bookingData);

        $this->updateBookingService($bookingData['services'], $booking->id);

        $booking['user'] = $booking->user;

        $booking['services'] = $booking->services;

        $booking['business'] = $booking->business;

        $businessAddress = Address::where('addressable_id', $booking['business']->id)
            ->where('addressable_type', get_class($booking['business']))
            ->first();

        $booking['business_address'] = $businessAddress;

        try {
            $notify_type = 'cancel_booking';
            $messageTemplate = 'New booking #[[booking_id]] has been booked.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($notify_type, $notify_message, $booking);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        $booking['tax'] = Tax::active()
            ->whereNull('module_type')
            ->orWhere('module_type', 'services')
            ->where('status', 1)
            ->get()
            ->map(function ($tax) {
                return [
                    'name' => $tax->title,
                    'type' => $tax->type,
                    'percent' => $tax->type == 'percent' ? $tax->value : 0,
                    'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
                ];
            })
            ->toArray();

        return $this->sendResponse($booking, __('booking.booking_create'));
    }

    public function requestData($request)
    {
        return [
            'business_id' => $request->business_id,
            'service_id' => $request->service_id,
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'start_date_time' => $request->start_date_time,
        ];
    }
}
