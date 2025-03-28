<?php

namespace Modules\Booking\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Trait\BookingTrait;
use Modules\Booking\Trait\PaymentTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Booking\Transformers\BookingDetailResource;
use Modules\Booking\Transformers\BookingPackageDetailResource;
use Modules\Booking\Transformers\BookingListResource;
use Modules\Booking\Transformers\BookingResource;
use Modules\Constant\Models\Constant;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\Package;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackage;
use Modules\Package\Models\UserPackageServices;
use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Package\Models\BookingPackageService;
use Modules\Service\Models\Service;
use Modules\BussinessHour\Models\BussinessHour;
use App\Models\Setting;

class BookingsController extends Controller
{
    use BookingTrait;
    use PaymentTrait;
    protected $module_title;
    public function __construct()
    {
        // Page Title
        $this->module_title = 'Bookings';
    }

    // get all business hours by business id
    public function getBusinessHours(Request $request)
    {
        $business_id = $request->business_id;
        $business_hours = BussinessHour::where('business_id', $business_id)->get();
        return response()->json([
            'status' => true,
            'data' => $business_hours,
            'message' => __('booking.business_hours'),
        ], 200);
    }

    // function to get all of the bookings of authenticated user
    public function index(Request $request)
    {
        $user = Auth::user();
        $bookings = Booking::where('user_id', $user->id)->with('booking_service');

        if ($request->has('status') && isset($request->status)) {
            $status = explode(',', $request->status);
            $bookings->whereIn('status', $status);
        }

        if ($request->has('business_id') && !empty($request->business_id)) {
            $bookings->where('business_id', $request->business_id);
        }

        $per_page = $request->input('per_page', 10);
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $bookings->count();
            }
        }

        $orderBy = 'desc';
        if ($request->has('order_by') && !empty($request->order_by)) {
            $orderBy = $request->order_by;
        }

        // Apply search conditions for booking ID, employee name, and service name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $bookings->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where(function ($nameQuery) use ($search) {
                                $nameQuery->where('first_name', 'LIKE', "%$search%")
                                    ->orWhere('last_name', 'LIKE', "%$search%");
                            });
                        });
                    })
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('service', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'LIKE', "%$search%");
                        });
                    });
            });
        }

        $bookings = $bookings->orderBy('updated_at', $orderBy)->paginate($per_page);
        return $bookings;
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'business_id' => 'required|integer|exists:businesses,id',
            'services' => 'sometimes|array',
            'services.*.service_id' => 'required_with:services|integer|exists:services,id',
            'employee_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }
        $date = Carbon::parse($request->start_date_time)->toDateString();
        // get service by employee_id and business_id on date from start_date_time
        $service_booking = Booking::where('employee_id', $request->employee_id)
            ->where('business_id', $request->business_id)
            ->whereDate('start_date_time', $date)
            ->get();

        // return $service_booking;

        if ($service_booking->isNotEmpty()) {

            // Check if the employee is already booked from the requested time and date
            foreach ($service_booking as $booking) {
                $startDateTime = Carbon::parse($booking->start_date_time);
                $endDateTime = Carbon::parse($booking->end_date_time);

                $requestedStartDateTime = Carbon::parse($request->start_date_time);
                $requestedEndDateTime = $requestedStartDateTime->copy()->addMinutes($request->duration_min);

                if ($requestedStartDateTime->between($startDateTime, $endDateTime) || $requestedEndDateTime->between($startDateTime, $endDateTime)) {
                    return response()->json(['message' => 'This booking slot is not available! Add in the queue?', 'status' => false], 200);
                }
            }
        }
        // Save the booking
        $data = $request->all();
        $data['start_date_time'] = $request->start_date_time;
        // get service by service_id and get then duration_min from the service and make endDateTime
        $service = Service::where('id', $request->service_id)->first();

        $data['start_date_time'] = Carbon::createFromFormat('Y-m-d H:i:s', $data['start_date_time']);
        //also add safety so that it cannot overlap with the existing booked slot's end_date_time

        $data['end_date_time'] = $data['start_date_time']->copy()->addMinutes($service->duration_min);

        // Check for overlapping with existing bookings
        foreach ($service_booking as $booking) {
            $existingStartDateTime = Carbon::parse($booking->start_date_time);
            $existingEndDateTime = Carbon::parse($booking->end_date_time);

            if (
                $data['start_date_time']->between($existingStartDateTime, $existingEndDateTime) ||
                $data['end_date_time']->between($existingStartDateTime, $existingEndDateTime) ||
                ($data['start_date_time']->lte($existingStartDateTime) && $data['end_date_time']->gte($existingEndDateTime))
            ) {

                $remainingTimeInMinutes = $existingEndDateTime->diffInMinutes($data['start_date_time']);
                $remainingHours = intdiv($remainingTimeInMinutes, 60);
                $remainingMinutes = $remainingTimeInMinutes % 60;

                return response()->json([
                    'message' => 'The staff is not available at the selected time. Your start time is ' . $data['start_date_time']->format('H:i') .
                        ' and end time is ' . $data['end_date_time']->format('H:i') .
                        '. An appointment is already booked with someone else from ' . $existingStartDateTime->format('H:i') .
                        ' to ' . $existingEndDateTime->format('H:i') .
                        '. The service duration is ' . $service->duration_min . ' minutes. Remaining time until availability: ' .
                        $remainingHours . ' hours and ' . $remainingMinutes . ' minutes.',
                    'status' => false
                ], 200);
            }
        }
        $data['queue_status'] = 'not_in_queue';
        $data['user_id'] = $request->user_id ?? auth()->id();

        $data['user_id'] = !empty($request->user_id) ? $request->user_id : auth()->user()->id;
        $userId = $data['user_id'];
        $is_reclaim = false;
        $alreadyPurchased = false;
        if (!empty($request->packages) && (!$request->has('is_reclaim') || !$request->is_reclaim)) {

            foreach ($request->packages as $key => $value) {
                $existingPackage = UserPackage::where('package_id', $value['id'])
                    ->where('user_id', $userId)
                    ->exists();
                if ($existingPackage) {
                    $alreadyPurchased = true;
                    return response()->json(['message' => 'Package already purchased.', 'status' => false], 200);
                }
            }
        }

        $booking = Booking::create($data);

        if (!empty($data['coupon_code'])) {
            $coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->first();
            $coupon_data = Coupon::where('coupon_code', $data['coupon_code'])->first();

            $totalPrice = 0;

            // Calculate the total price based on services or packages
            if (!empty($data['services'])) {
                $totalPrice = array_sum(array_column($data['services'], 'service_price'));
            } elseif (!empty($data['packages'])) {
                $totalPrice = array_sum(array_column($data['packages'], 'package_price'));
            }
            // Apply the discount validation
            if ($data['couponDiscountamount'] > $totalPrice) {
                return response()->json(['valid' => false, 'message' => 'Discount exceeds the total price', 'status' => false], 200);
            }
            if (!$coupon) {
                if ($coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    return response()->json(['message' => $message, 'status' => false], 200);
                } else {
                    $redeemCoupon = [
                        'coupon_code' => $data['coupon_code'],
                        'discount' => $data['couponDiscountamount'],
                        'user_id' => $data['user_id'],
                        'coupon_id' => $coupon_data->id,
                        'booking_id' => $booking->id,
                    ];

                    $user_coupon = UserCouponRedeem::create($redeemCoupon);

                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $user_coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        Coupon::where('coupon_code', $data['coupon_code'])->update(['is_expired' => 1]);
                        if ($coupon = Coupon::where('coupon_code', $data['coupon_code'])->first()) {
                            Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                        }
                    }
                }
            } else {
                if ($coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    return response()->json(['message' => $message, 'status' => false], 200);
                } else {
                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        $message = 'Your coupon limit has been reached.';
                        return response()->json(['message' => $message, 'status' => false], 200);
                    } else {
                        $redeemCoupon = [
                            'coupon_code' => $data['coupon_code'],
                            'discount' => $data['couponDiscountamount'],
                            'user_id' => $data['user_id'],
                            'coupon_id' => $coupon_data->id,
                            'booking_id' => $booking->id,
                        ];

                        UserCouponRedeem::create($redeemCoupon);
                        $total_coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->count();
                        if ($total_coupon == $coupon_data->use_limit) {
                            Coupon::where('coupon_code', $data['coupon_code'])->update(['is_expired' => 1]);
                            if ($coupon = Coupon::where('coupon_code', $data['coupon_code'])->first()) {
                                Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                            }
                        }
                    }
                }
            }
        }
        //if package reclaim


        if ($request->has('is_reclaim') && isset($request->packages) && $request->is_reclaim == true) {
            $is_reclaim = true;

            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id, $userId, $is_reclaim);
            foreach ($request->packages as $key => $value) {
                $UserPackages = UserPackage::with('bookings')
                    ->where('package_id', $value['id'])
                    ->where('user_id', $userId)
                    ->get();

                $bookingPackage = BookingPackages::where('booking_id', $booking->id)->first();

                if ($UserPackages->isNotEmpty()) {
                    foreach ($UserPackages as $UserPackage) {
                        foreach ($value['services'] as $service) {
                            $userPackageService = UserPackageServices::where('user_package_id', $UserPackage->id)
                                ->whereHas('packageService', function ($query) use ($service) {
                                    $query->where('service_id', $service['service_id']);
                                })->first();

                            if ($userPackageService) {
                                if ($userPackageService->qty >= 1) {
                                    $bookingPackageService = BookingPackageService::Create([
                                        'booking_id' => $booking->id,
                                        'package_id' => $value['id'],
                                        'user_id' => $userId,
                                        'package_service_id' => $userPackageService->package_service_id,
                                        'booking_package_id' => $bookingPackage->id,
                                        'service_name' => $userPackageService->service_name,
                                        'qty' => $userPackageService->qty - 1,
                                        'service_id' => $service['service_id'],
                                    ]);
                                    $userPackageService->qty -= 1;
                                    $userPackageService->save();
                                }

                                if ($userPackageService->qty == 0) {
                                    $userPackageService->delete();
                                }
                            }
                        }

                        $remainingServices = UserPackageServices::where('user_package_id', $UserPackage->id)->count();
                        if ($remainingServices == 0) {
                            $UserPackage->delete();
                        } else {
                            $UserPackage->type = 'reclaimed';
                            $UserPackage->save();
                        }
                    }
                }
            }
        } else  if ($alreadyPurchased == false) {
            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id, $userId, $is_reclaim);
            $this->storeApiUserPackage($booking->id);
        }

        //service
        if (!empty($request->services)) {
            $this->updateBookingService($request->services, $booking->id);
        }

        $message = 'New ' . Str::singular($this->module_title) . ' Added';
        try {
            $type = 'new_booking';
            $messageTemplate = 'New booking #[[booking_id]] has been booked.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json(['message' => $message, 'status' => true, 'booking_id' => $booking->id], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:bookings,id',
            'user_id' => 'required|integer|exists:users,id',
            'business_id' => 'required|integer|exists:businesses,id',
            'services' => 'sometimes|array',
            'services.*.service_id' => 'required_with:services|integer|exists:services,id',
            'employee_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::findOrFail($request->id);
        $date = Carbon::parse($request->start_date_time)->toDateString();

        $service_booking = Booking::where('employee_id', $request->employee_id)
            ->where('business_id', $request->business_id)
            ->whereDate('start_date_time', $date)
            ->where('id', '!=', $booking->id)
            ->get();

        if ($service_booking->isNotEmpty()) {
            foreach ($service_booking as $existingBooking) {
                $startDateTime = Carbon::parse($existingBooking->start_date_time);
                $endDateTime = Carbon::parse($existingBooking->end_date_time);

                $requestedStartDateTime = Carbon::parse($request->start_date_time);
                $requestedEndDateTime = $requestedStartDateTime->copy()->addMinutes($request->duration_min);

                if ($requestedStartDateTime->between($startDateTime, $endDateTime) || $requestedEndDateTime->between($startDateTime, $endDateTime)) {
                    return response()->json(['message' => 'This booking slot is not available!', 'status' => false], 200);
                }
            }
        }

        $data = $request->all();
        $data['start_date_time'] = Carbon::createFromFormat('Y-m-d H:i:s', $request->start_date_time);
        $service = Service::where('id', $request->service_id)->first();
        $data['end_date_time'] = $data['start_date_time']->copy()->addMinutes($service->duration_min);

        foreach ($service_booking as $existingBooking) {
            $existingStartDateTime = Carbon::parse($existingBooking->start_date_time);
            $existingEndDateTime = Carbon::parse($existingBooking->end_date_time);

            if (
                $data['start_date_time']->between($existingStartDateTime, $existingEndDateTime) ||
                $data['end_date_time']->between($existingStartDateTime, $existingEndDateTime) ||
                ($data['start_date_time']->lte($existingStartDateTime) && $data['end_date_time']->gte($existingEndDateTime))
            ) {
                return response()->json(['message' => 'The staff is not available at the selected time.', 'status' => false], 200);
            }
        }

        $data['queue_status'] = 'not_in_queue';
        $data['user_id'] = $request->user_id ?? auth()->id();

        if (!empty($request->packages)) {
            foreach ($request->packages as $package) {
                $existingPackage = UserPackage::where('package_id', $package['id'])
                    ->where('user_id', $data['user_id'])
                    ->exists();
                if ($existingPackage) {
                    return response()->json(['message' => 'Package already purchased.', 'status' => false], 200);
                }
            }
        }

        $booking->update($data);

        if (!empty($data['coupon_code'])) {
            $coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->first();
            $coupon_data = Coupon::where('coupon_code', $data['coupon_code'])->first();

            $totalPrice = 0;
            if (!empty($data['services'])) {
                $totalPrice = array_sum(array_column($data['services'], 'service_price'));
            } elseif (!empty($data['packages'])) {
                $totalPrice = array_sum(array_column($data['packages'], 'package_price'));
            }

            if ($data['couponDiscountamount'] > $totalPrice) {
                return response()->json(['valid' => false, 'message' => 'Discount exceeds the total price', 'status' => false], 200);
            }

            if (!$coupon) {
                if ($coupon_data->is_expired == 1) {
                    return response()->json(['message' => 'Coupon has expired.', 'status' => false], 200);
                } else {
                    $redeemCoupon = [
                        'coupon_code' => $data['coupon_code'],
                        'discount' => $data['couponDiscountamount'],
                        'user_id' => $data['user_id'],
                        'coupon_id' => $coupon_data->id,
                        'booking_id' => $booking->id,
                    ];
                    UserCouponRedeem::create($redeemCoupon);
                }
            }
        }

        if (!empty($request->packages)) {
            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id, $data['user_id']);
        }

        if (!empty($request->services)) {
            $this->updateBookingService($request->services, $booking->id);
        }

        $message = __('booking.booking_update');
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function updateStatus(Request $request)
    {
        $id = $request->id;
        $booking = Booking::with('services', 'user', 'products')->findOrFail($id);
        $status = $request->status;

        if (isset($request->action_type) && $request->action_type == 'update-status') {
            $status = $request->value;
        }

        $booking->update(['status' => $status]);

        $notify_type = null;

        switch ($status) {
            case 'check_in':
                $notify_type = 'check_in_booking';
                break;
            case 'checkout':
                $notify_type = 'checkout_booking';
                break;
            case 'completed':
                $notify_type = 'complete_booking';
                break;
            case 'cancelled':
                $notify_type = 'cancel_booking';
                break;
            case 'queue':
                $notify_type = 'queue_booking';
                break;
        }

        if (isset($notify_type)) {
            try {
                $this->sendNotificationOnBookingUpdate($notify_type, '', $booking);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        $message = __('booking.status_update');

        return response()->json(['data' => new BookingResource($booking), 'message' => $message, 'status' => true]);
    }

    public function bookingList(Request $request)
    {
        $user = Auth::user();

        $booking = Booking::where('user_id', $user->id)->with('booking_service', 'bookingTransaction', 'bookingPackages.bookedPackageService');

        if ($request->has('status') && isset($request->status)) {

            $status = explode(',', $request->status);
            $booking->whereIn('status', $status);
        }
        if ($request->has('business_id') && !empty($request->business_id)) {
            $booking->where('business_id', $request->business_id);
        }
        $per_page = $request->input('per_page', 10);
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $booking->count();
            }
        }
        $orderBy = 'desc';
        if ($request->has('order_by') && !empty($request->order_by)) {
            $orderBy = $request->order_by;
        }
        // Apply search conditions for booking ID, employee name, and service name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $booking->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where(function ($nameQuery) use ($search) {
                                $nameQuery->where('first_name', 'LIKE', "%$search%")
                                    ->orWhere('last_name', 'LIKE', "%$search%");
                            });
                        });
                    })
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('service', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'LIKE', "%$search%");
                        });
                    });
            });
        }

        $booking = $booking->orderBy('updated_at', $orderBy)->paginate($per_page);

        $items = BookingListResource::collection($booking);

        return response()->json([
            'status' => true,
            'data' => $items,
            'message' => __('booking.booking_list'),
        ], 200);
    }

    public function bookingDetail(Request $request)
    {
        $id = $request->id;

        $booking_data = Booking::with(['business', 'user', 'booking_service', 'payment', 'products', 'bookingPackages'])->where('id', $id)->first();

        if ($booking_data == null) {
            $message = __('booking.booking_not_found');

            return response()->json([
                'status' => false,
                'message' => __('booking.booking_not_found'),
            ], 200);
        }



        $booking_detail = new BookingDetailResource($booking_data);


        return response()->json([
            'status' => true,
            'data' => $booking_detail,
            'message' => __('booking.booking_detail'),
        ], 200);
    }

    public function searchBookings(Request $request)
    {
        $keyword = $request->input('keyword');

        $bookings = Booking::where('note', 'like', "%{$keyword}%")
            ->with('business', 'user')
            ->get();

        return response()->json([
            'status' => true,
            'data' => BookingResource::collection($bookings),
            'message' => __('booking.search_booking'),
        ], 200);
    }

    public function statusList()
    {
        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');
        $checkout_sequence = $booking_status->where('name', 'check_in')->first()->sequence ?? 0;
        $bookingColors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');
        $statusList = [];
        $finalstatusList = [];

        foreach ($booking_status as $key => $value) {
            if ($value->name !== 'cancelled') {
                $statusList = [
                    'status' => $value->name,
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => $value->sequence >= $checkout_sequence,
                ];
                array_push($finalstatusList, $statusList);
                $nextStatus = $booking_status->where('sequence', $value->sequence + 1)->first();
                if ($nextStatus) {
                    $statusList[$value->name]['next_status'] = $nextStatus->name;
                }
            } else {
                $statusList = [
                    'status' => $value->name,
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => true,
                ];
                array_push($finalstatusList, $statusList);
            }
        }

        return response()->json([
            'status' => true,
            'message' => __('booking.booking_status_list'),
            'data' => $finalstatusList,
        ], 200);
    }

    public function storeInQueue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'business_id' => 'required|integer|exists:businesses,id',
            'services' => 'sometimes|array',
            'services.*.service_id' => 'required_with:services|integer|exists:services,id',
            'employee_id' => 'required|integer|exists:users,id',
            'start_date_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->start_date_time);
        $service = Service::where('id', $request->service_id)->first();
        $endDateTime = $startDateTime->copy()->addMinutes($service->duration_min);

        $data = $request->all();
        $data['start_date_time'] = $startDateTime;
        $data['end_date_time'] = $endDateTime;
        $data['queue_status'] = 'in_queue';
        $data['user_id'] = $request->user_id ?? auth()->id();

        $booking = Booking::create($data);

        $message = 'New ' . Str::singular($this->module_title) . ' Added to Queue';
        try {
            $type = 'queue_booking';
            $messageTemplate = 'Booking #[[booking_id]] has been added to the queue.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json([
            'message' => $message,
            'status' => true,
            'booking_id' => $booking->id,
        ], 201);
    }

    public function getServiceDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $business_id = $request->business_id;
        $business_hours = BussinessHour::where('business_id', $business_id)->get();

        $dates = [];
        $currentDate = Carbon::now();
        $endDate = $currentDate->copy()->addMonth();

        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l')); // Get the day name in lowercase (e.g., monday, tuesday)
            $businessHour = $business_hours->firstWhere('day', $dayOfWeek);

            if ($businessHour && $businessHour->is_holiday == 0) { // Check if it's not a holiday
                $dates[] = [
                    'date' => $currentDate->toDateString(),
                    'day' => ucfirst($dayOfWeek), // Capitalize the first letter of the day
                ];
            }

            $currentDate->addDay();
        }

        return response()->json(['status' => true, 'data' => $dates, 'message' => 'Available service dates fetched successfully.'], 200);
    }

    public function getTimeSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $service = Service::findOrFail($request->service_id);
        $business_hours = BussinessHour::where('business_id', $request->business_id)->first();

        if (!$business_hours) {
            return response()->json(['status' => false, 'message' => 'No business hours found for the selected date.'], 404);
        }

        if ($business_hours->is_holiday == 1) {
            return response()->json(['status' => false, 'message' => 'This business is closed on the selected day.'], 200);
        }

        // Get slot_duration from Settings table
        $slot_duration = Setting::where('name', 'slot_duration')->value('val');
        $start_time = Carbon::parse($business_hours->start_time);
        $end_time = Carbon::parse($business_hours->end_time);
        $breaks = $business_hours->breaks ? json_decode($business_hours->breaks, true) : []; // Handle empty breaks gracefully

        // Convert slot_duration from "HH:mm" format to minutes
        [$hours, $minutes] = explode(':', $slot_duration);
        $slot_duration_in_minutes = ($hours * 60) + $minutes;

        // Get service duration
        $service_duration_in_minutes = $service->duration_min;

        $slots = [];
        while ($start_time->addMinutes($slot_duration_in_minutes)->lte($end_time)) {
            $slot_start = $start_time->copy()->subMinutes($slot_duration_in_minutes);
            $slot_end = $slot_start->copy()->addMinutes($service_duration_in_minutes);

            // Ensure the slot does not exceed the business end time
            if ($slot_end->gt($end_time)) {
                break;
            }

            $is_break = false;
            foreach ($breaks as $break) {
                $break_start = Carbon::parse($break['start']);
                $break_end = Carbon::parse($break['end']);
                if ($slot_start->between($break_start, $break_end) || $slot_end->between($break_start, $break_end)) {
                    $is_break = true;
                    break;
                }
            }

            if (!$is_break) {
                $slots[] = $slot_start->format('H:i');
            }
        }

        return response()->json(['status' => true, 'data' => $slots, 'message' => 'Available time slots fetched successfully.'], 200);
    }
}