<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessDetailResource;
use App\Http\Resources\BusinessEmployeeResource;
use App\Http\Resources\BusinessGalleryResource;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\ServiceResource;
use App\Models\Business;
use App\Models\BusinessGallery;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\BusinessEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Transformers\EmployeeReviewResource;
use Modules\Service\Models\ServiceBusinesses;
use Modules\Tax\Models\Tax;
use PhpOffice\PhpSpreadsheet\Calculation\Engine\BranchPruner;

class BusinessController extends Controller
{
    public function businessList(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Get the number of items per page from the request (default: 10)

        $businesses = Business::with('businessHours', 'address', 'media')->where('status', 1)->paginate($perPage);

        if ($businesses->isEmpty()) {
            return response()->json(['status' => true, 'message' => __('business.business_isempty')]);
        }

        $businessCollection = BusinessResource::collection($businesses);

        return response()->json([
            'status' => true,
            'data' => $businessCollection,
            'message' => __('business.business_list'),
        ], 200);
    }

    public function businessDetails(Request $request)
    {
        $businessId = $request->business_id;
        $business = Business::with('businessHours', 'media', 'gallerys')->find($businessId);

        $employeeIds = BranchEmployee::where('business_id', $businessId)
            ->distinct()
            ->pluck('employee_id');

        $averageRating = EmployeeRating::whereIn('employee_id', $employeeIds)->avg('rating');

        $business['average_rating'] = $averageRating;

        $business['total_review'] = EmployeeRating::whereIn('employee_id', $employeeIds)->count();

        if ($business) {
            $businessDetails = new BusinessDetailResource($business);

            return response()->json(['status' => true, 'data' => $businessDetails, 'message' => __('business.business_details')]);
        } else {
            return response()->json(['status' => false, 'message' => __('business.business_notfound')]);
        }
    }

    public function businessService(Request $request)
    {
        $businessId = $request->input('business_id');

        // $businessServices = ServiceBusinesses::where('business_id', $businessId)->get();
        $business = Business::find($businessId);

        if (! $business) {
            return response()->json(['status' => true, 'message' => __('business.business_noservice')]);
        }

        $serviceDetails = ServiceResource::collection($business->services);

        return response()->json(['status' => true, 'data' => $serviceDetails, 'message' => __('business.business_service')]);
    }

    public function businessReviews(Request $request)
    {
        $businessId = $request->business_id;

        $perPage = $request->input('per_page', 10);

        $employeeIds = BranchEmployee::where('business_id', $businessId)
            ->distinct()
            ->pluck('employee_id');

        $reviews = EmployeeRating::with('user')
            ->whereIn('employee_id', $employeeIds);

        $reviews = $reviews->orderBy('updated_at', 'desc')->paginate($perPage);
        $review = EmployeeReviewResource::collection($reviews);

        return response()->json([
            'status' => true,
            'data' => $review,
            'message' => __('business.business_review'),
        ]);
    }

    public function businessEmployee(Request $request)
    {
        $businessId = $request->input('business_id');

        $perPage = $request->input('per_page', 10);

        $businessEmployees = BranchEmployee::where('business_id', $businessId)->pluck('employee_id');
        $employee = User::with(['media', 'businesses', 'services'])->where('status', 1);
        $employee = $employee->whereIn('id', $businessEmployees);
        $employee = $employee->paginate($perPage);
        $responseData = EmployeeResource::collection($employee);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('employee.employee_list'),
        ], 200);

        return response()->json(['status' => true, 'data' => BusinessEmployeeResource::collection($employeeDetails), 'message' => __('business.business_employee')]);
    }

    public function businessGallery(Request $request)
    {
        $businessId = $request->input('business_id');

        $businessGalleries = BusinessGallery::where('business_id', $businessId)->get();

        if ($businessGalleries->isEmpty()) {
            return response()->json(['status' => true, 'message' => __('business.business_nogallery')]);
        }

        $galleryData = BusinessGalleryResource::collection($businessGalleries);

        return response()->json(['status' => true, 'data' => $galleryData, 'message' => __('business.business_gallery')]);
    }

    public function assign_list($id)
    {
        $business_user = BranchEmployee::with('employee')->where('business_id', $id)->get();
        $business_user = $business_user->each(function ($data) {
            $data['name'] = $data->employee->name;
            $data['avatar'] = $data->employee->avatar;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $business_user]);
    }

    public function assign_update($id, Request $request)
    {
        BranchEmployee::where('business_id', $id)->delete();
        foreach ($request->users as $key => $value) {
            BranchEmployee::create([
                'business_id' => $id,
                'employee_id' => $value['employee_id'],
                'is_primary' => $value['is_primary'],
            ]);
        }

        return response()->json(['status' => true, 'message' => __('business.business_update')]);
    }

    public function businessConfig(Request $request)
    {
        $business_id = $request->business_id;

        $business_slot = BussinessHour::where('business_id', $business_id)->get();

        $business_tax = Tax::active()
            ->whereNull('module_type')
            ->orWhere('module_type', 'services')
            ->where('status', 1)->get()
            ->map(function ($tax) {
                return [
                    'name' => $tax->title,
                    'type' => $tax->type,
                    'percent' => $tax->type == 'percent' ? $tax->value : 0,
                    'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
                ];
            })
            ->toArray();
        $tax = $business_tax;

        $response = [
            'slot' => $business_slot,
            'tax' => $tax,
            'slot_duration' => setting('slot_duration'),
        ];

        return response()->json(['status' => true, 'data' => $response], 200);
    }

    public function verifySlot(Request $request)
    {
        $employee_id = $request->employee_id;
        $start_date_time = $request->start_date_time;

        $booking = Booking::with('bookingService')->where('start_date_time', $start_date_time)
            ->whereHas('bookingService', function ($query) use ($employee_id) {
                $query->where('employee_id', $employee_id);
            });
        if ($booking->count() > 0) {
            return response()->json(['status' => false, 'message' => __('business.business_reserved')]);
        } else {
            return response()->json(['status' => true, 'message' => '']);
        }
    }

    public function show($id)
    {
        $business = Business::findOrFail($id);
        return new BusinessResource($business);
    }
}
