<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Package\Models\BookingPackages;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;

class BackendController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (auth()->user()->hasRole('employee')) {
            return redirect(RouteServiceProvider::EMPLOYEE_LOGIN_REDIRECT);
        }
        $global_booking = false;
        $today = Carbon::today();
        $action = $request->action ?? 'reset';
        if (isset($request->date_range) && $action !== 'reset') {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) { // When both start and end dates are provided
                $startDate = $dates[0] ?? date('Y-m-d');
                $endDate = $dates[1] ?? date('Y-m-d');
            } elseif (count($dates) == 1) { // When only a single date is provided
                $startDate = $dates[0] ?? date('Y-m-d');
                $endDate = $startDate; // Use the same date for both start and end
            } else { // Default case, fallback to last 10 days
                $startDate = Carbon::now()->subDays(10)->toDateString();
                $endDate = Carbon::now()->toDateString();
            }
        } else {
            $startDate = Carbon::now()->subDays(10)->toDateString();
            $endDate = Carbon::now()->toDateString();
        }

        $date_range = $startDate.' to '.$endDate;
        $data = [
            'total_appointments' => 0,
            'total_commission' => 0,
            'total_revenue' => 0,
            'total_new_customers' => 0,
            'upcomming_appointments' => [],
            'top_services' => [],
            'revenue_chart' => [],
            'total_orders' => 0,
            'product_sales' => 0,
        ];

        $totalServices = BookingService::whereHas('booking', function ($query) use ($startDate, $endDate) {
            $query->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate);
            })->business();
        });
        $data['total_appointments'] = Booking::where(function ($query) use ($startDate, $endDate) {
            $query->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate);
            });
        })->where('status', 'completed')->business()->count();
        $data['total_commission'] = Booking::with('commission')->business()->whereDate('start_date_time', '>=', $startDate)
            ->whereDate('start_date_time', '<=', $endDate)->get();

        $data['total_commission'] = \Currency::format($data['total_commission']->sum(function ($booking) {
            return $booking->commission->commission_amount ?? 0;
        }));

        $bookings = BookingTransaction::with('booking')->whereHas('booking', function ($query) use ($startDate, $endDate) {
            $query->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate)
                    ->where('status', 'completed');
            })->business();
        })->pluck('booking_id')->toArray();

        $totalServiceAmount = BookingService::whereIn('booking_id', $bookings)->sum('service_price');
        $totalPackageAmount = BookingPackages::whereIn('booking_id', $bookings)->sum('package_price');

        $data['total_revenue'] = \Currency::format($totalServiceAmount + $totalPackageAmount);

        $data['total_new_customers'] = User::where('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->count();

        $datetime = Carbon::now()->setTimezone(setting('default_time_zone') ?? 'UTC');

        $data['upcomming_appointments'] = Booking::with('business', 'user', 'services')
            ->where('start_date_time', '>=', $datetime)->orderBy('start_date_time')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->whereHas('business', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->whereNotIn('status', ['completed', 'cancelled']) // Exclude both statuses
            ->business()
            ->take(10)
            ->get();

        $data['top_services'] = $totalServices->with('service')->select(
            'service_id',
            \DB::raw('COUNT(*) as total_service_count'),
            \DB::raw('SUM(service_price) as total_service_price')
        )
            ->groupBy('service_id')
            ->orderByDesc('total_service_price')
            ->limit(5)
            ->get();

        $chartBookingRevenue = Booking::select(
            \DB::raw('DATE(bookings.start_date_time) AS booking_date'),
            \DB::raw('SUM(booking_services.service_price) AS total_price'),
            \DB::raw('COUNT(DISTINCT booking_services.booking_id) AS total_booking')
        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->whereDate('bookings.start_date_time', '>=', $startDate)
            ->whereDate('bookings.start_date_time', '<=', $endDate)
            ->where('status', 'completed')
            ->business()
            ->groupBy(\DB::raw('DATE(bookings.start_date_time)'))
            ->get();

        $data['revenue_chart']['xaxis'] = $chartBookingRevenue?->pluck('booking_date')->toArray() ?? [];
        $data['revenue_chart']['total_bookings'] = $chartBookingRevenue?->pluck('total_booking')->toArray() ?? [];
        $data['revenue_chart']['total_price'] = $chartBookingRevenue?->pluck('total_price')->toArray() ?? [];

        $orders = Order::where(function ($q) {
            $q->orWhereIn('order_group_id', OrderGroup::pluck('id'));
        });

        $data['total_orders'] = $orders->where('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)->count();

        $data['product_sales'] = \Currency::format(
            $orders->where('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)->sum('total_admin_earnings')
        );

        return view('backend.index', compact('data', 'date_range', 'global_booking'));
    }

    public function setCurrentBusiness($business_id)
    {
        request()->session()->forget('selected_business');

        request()->session()->put('selected_business', $business_id);

        return redirect()->back()->with('success', 'Current Business Has Been Changes')->withInput();
    }

    public function resetBusiness()
    {
        request()->session()->forget('selected_business');

        return redirect()->back()->with('success', 'Show All Business Content')->withInput();
    }

    public function setUserSetting(Request $request)
    {
        auth()->user()->update(['user_setting' => $request->settings]);

        return response()->json(['status' => true]);
    }
}
