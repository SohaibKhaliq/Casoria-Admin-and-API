<?php

namespace Modules\Employee\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Employee\Models\BusinessEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Transformers\EmployeeDetailResource;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Transformers\EmployeeReviewResource;
use Modules\Service\Models\ServiceEmployee;

class EmployeeController extends Controller
{
    public function employeeList(Request $request)
    {
        $businessId = $request->input('business_id');
        $perPage = $request->input('per_page', 10);

        $employee = User::role('employee')->with(['media', 'businesses', 'services'])->where('status', 1);
        if ($businessId) {
            $employee = $employee->whereHas('businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            });
        }
        if (! empty($request->service_ids)) {
            $ids = ServiceEmployee::whereIn('service_id', explode(' ', $request->service_ids))->pluck('employee_id');
            $employee = $employee->whereIn('id', $ids);
        }
        if (! empty($request->order_by) && $request->order_by == 'top') {
            $employee = $employee->withCount('services')
                ->orderByDesc('services_count');
        }
        $employee = $employee->paginate($perPage);
        $responseData = EmployeeResource::collection($employee);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('employee.employee_list'),
        ], 200);
    }

    public function employeeDetail(Request $request)
    {
        $businessId = $request->input('business_id');
        $employeeId = $request->input('employee_id');

        if ($businessId && $employeeId) {
            // Fetch employee details based on both business_id and employee_id
            $employee = User::role('employee')->with('media')->whereHas('businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })->find($employeeId);
        } elseif ($businessId) {
            // Fetch employee details based on business_id
            $employee = User::role('employee')->with('media')->whereHas('businesses', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })->first();
        } elseif ($employeeId) {
            // Fetch employee details based on employee_id
            $employee = User::role('employee')->with('media')->find($employeeId);
        } else {
            return response()->json(['status' => false, 'message' => __('employee.business_employee_id')]);
        }

        if ($employee) {
            return response()->json(['status' => true, 'data' => new EmployeeDetailResource($employee), 'message' => __('employee.employee_detail')]);
        } else {
            return response()->json(['status' => false, 'message' => __('employee.employee_notfound')]);
        }
    }

    public function saveRating(Request $request)
    {
        $user = auth()->user();
        $rating_data = $request->all();
        $rating_data['user_id'] = $user->id;
        $result = EmployeeRating::updateOrCreate(['id' => $request->id], $rating_data);

        $message = __('employee.rating_update');
        if ($result->wasRecentlyCreated) {
            $message = __('employee.rating_add');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function deleteRating(Request $request)
    {
        $user = auth()->user();
        $rating = EmployeeRating::where('id', $request->id)->where('user_id', $user->id)->first();
        if ($rating == null) {
            $message = __('employee.rating_notfound');

            return response()->json(['status' => false, 'message' => $message]);
        }
        $message = __('employee.rating_delete');
        $rating->delete();

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function getRating(Request $request)
    {
        $employee_id = $request->employee_id;
        $perPage = $request->input('per_page');

        if (! empty($request->business_id)) {
            $business_employee = BusinessEmployee::where('business_id', $request->business_id)->pluck('employee_id');
            $reviewsQuery = EmployeeRating::whereIn('employee_id', $business_employee)->orderBy('updated_at', 'desc');
        } else {
            $reviewsQuery = EmployeeRating::where('employee_id', $employee_id)->orderBy('updated_at', 'desc');
        }

        if ($perPage === 'all') {
            $reviews = $reviewsQuery->get();
        } else {
            $reviews = $reviewsQuery->paginate($perPage);
        }
        $review = EmployeeReviewResource::collection($reviews);

        return response()->json([
            'status' => true,
            'data' => $review,
            'message' => __('employee.review_list'),
        ], 200);
    }
}
