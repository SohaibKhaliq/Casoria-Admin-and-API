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

class DashboardController extends Controller
{
    public function dashboardDetail(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $businessId = $request->input('business_id'); // Assuming the business ID is passed in the request
        $user_id = $request->input('user_id');
        $business = Business::find($businessId);

        if (! $business) {
            return response()->json(['status' => false, 'message' => __('business.business_notfound')], 404);
        }

        $categories = Category::with('media')->where('status', 1)->whereNull('parent_id')
            ->whereHas('services.businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->paginate($perPage)
            ->forPage(1, 6);

        $services = Service::with(['media', 'businesses' => function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
        }])
            ->whereHas('businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->paginate($perPage);

        $employees = User::with('media')->withCount(['businesses', 'services'])
            ->whereHas('businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->orderByDesc('services_count')
            ->paginate($perPage)
            ->forPage(1, 6);

        $slider = SliderResource::collection(Slider::where('status', 1)->paginate($perPage));


        $responseData = [
            'category' => CategoryResource::collection($categories)->toArray(request()),
            'service' => ServiceResource::collection($services)->toArray(request()),
            'top_experts' => EmployeeResource::collection($employees)->toArray(request()),
            'slider' => $slider,
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
}