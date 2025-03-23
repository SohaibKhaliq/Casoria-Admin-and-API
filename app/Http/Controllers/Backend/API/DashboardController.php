<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessGallery;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryResource;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Product\Models\Cart;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceGallery;
use Modules\Service\Transformers\ServiceResource;
use Modules\Slider\Models\Slider;
use Modules\Slider\Transformers\SliderResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function dashboardDetail(Request $request)
    {
        // Get authenticated user
        $user = Auth::user();

        // Get user full name
        $userName = trim($user->first_name . ' ' . $user->last_name);

        // Get user's pending bookings with name(category) and image, staff name, date and time
        $pendingBookingQuery = Booking::where('user_id', $user->id)
            ->where('status', 'pending') // Ensure 'pending' is the correct status value
            ->with('booking_service.service:id,name', 'booking_service.employee:id,first_name,last_name', 'business:id,name')
            ->select('id', 'business_id');

        // Log the query for debugging
        Log::info($pendingBookingQuery->toSql());

        $pendingBooking = $pendingBookingQuery->get();

        // Log the results for debugging
        Log::info($pendingBooking);

        // Get all businesses with selected fields
        $businesses = Business::with('address', 'businessHours')
            ->select('id', 'name')
            ->get();

        // Paginate sliders
        $perPage = 10;
        $sliderQuery = Slider::where('status', 1)->paginate($perPage);
        $slider = SliderResource::collection($sliderQuery);

        // If no sliders are found
        if ($sliderQuery->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => __('messages.no_sliders_found'),
            ], 404);
        }

        // Format business data
        $businessData = $businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'name' => $business->name,
                'address' => $business->address ?? null,
                'feature_image' => $business->feature_image, // Accessor handles fallback image
                'business_hours' => $business->businessHours ?? [],
            ];
        });

        // Prepare response
        $responseData = [
            'slider' => [
                'data' => $slider,
                'pagination' => [
                    'current_page' => $sliderQuery->currentPage(),
                    'total_pages' => $sliderQuery->lastPage(),
                    'total_items' => $sliderQuery->total(),
                    'per_page' => $sliderQuery->perPage(),
                ],
            ],
            'user' => $userName,
            'pending_booking' => $pendingBooking,
            'businesses' => $businessData,
        ];

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('messages.dashboard_detail'),
        ], 200);
    }


    public function searchList(Request $request)
    {
        $query = $request->input('query');
        $results = [];

        // Search in Businesses
        $businesses = Business::where('name', 'like', "%{$query}%")->get();
        $results['businesses'] = $businesses;

        // Search in Employees // Need To Add Role Base
        $employees = User::role('employee')->where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%");
        })->get();
        $results['employees'] = $employees;

        // Search in Categories
        $categories = Category::where('name', 'like', "%{$query}%")->get();
        $results['categories'] = $categories;

        $subcategories = Category::where('name', 'like', "%{$query}%")
            ->orWhere('parent_id', 'like', "%{$query}%")
            ->get();
        $results['subcategory'] = $subcategories;

        // Search in Bookings
        $bookings = Booking::where('note', 'like', "%{$query}%")->get();
        $results['bookings'] = $bookings;

        // Search in Services
        $services = Service::where('name', 'like', "%{$query}%")->get();
        $results['services'] = $services;

        return response()->json($results);
    }

    public function globalGallery(Request $request)
    {
        $galleryId = $request->input('gallery_id');

        // Retrieve business gallery
        $businessGallery = BusinessGallery::find($galleryId);
        if ($businessGallery) {
            $business = Business::find($businessGallery->business_id);

            if ($business) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'gallery' => $businessGallery,
                        'business' => $business,
                    ],
                    'message' => __('business.business_gal_retrived'),
                ], 200);
            }
        }

        // Retrieve service gallery
        $serviceGallery = ServiceGallery::find($galleryId);
        if ($serviceGallery) {
            $service = Service::find($serviceGallery->service_id);

            if ($service) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'gallery' => $serviceGallery,
                        'service' => $service,
                    ],
                    'message' => __('service.service_gal_retrived'),
                ], 200);
            }
        }

        // Gallery not found
        return response()->json([
            'status' => false,
            'message' => __('messages.gallery_notfound'),
        ], 404);
    }

    public function activeBookings()
    {
        $user = Auth::user();

        // Fetch active bookings for the authenticated user
        $activeBookings = Booking::where('user_id', $user->id)
            ->where('status', 'pending') // Assuming 'pending' is the status for active bookings
            ->with([
                'business:id,name',
                'services' => function ($query) {
                    $query->select('services.id', 'services.name'); // Changed 'service_name' to 'name'
                }
            ])
            ->get();

        return response()->json([
            'status' => true,
            'data' => $activeBookings,
            'message' => __('messages.active_bookings_retrieved'),
        ], 200);
    }
}
