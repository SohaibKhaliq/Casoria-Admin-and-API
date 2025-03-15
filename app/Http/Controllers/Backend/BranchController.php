<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessRequest;
use App\Models\Address;
use App\Models\Business;
use App\Models\BusinessGallery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Constant\Models\Constant;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Modules\Employee\Models\BusinessEmployee;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBusinesses;
use Yajra\DataTables\DataTables;

class BusinessController extends Controller
{
    // use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'business.title';

        // module name
        $this->module_name = 'business';

        // module icon
        $this->module_icon = 'fa-solid fa-building';

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
        ]);

        $this->middleware(['permission:view_business'])->only('index');
        $this->middleware(['permission:edit_business'])->only('edit', 'update');
        $this->middleware(['permission:add_business'])->only('store');
        $this->middleware(['permission:delete_business'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $module_action = 'List';

        $filter = [
            'status' => $request->status,
        ];

        $select_data = [
            'BRANCH_FOR' => Constant::getTypeDataObject('BRANCH_SERVICE_GENDER'),
            'PAYMENT_METHODS' => Constant::getTypeDataObject('PAYMENT_METHODS'),
        ];

        $assets = ['select-picker'];
        $columns = CustomFieldGroup::columnJsonValues(new Business());
        $customefield = CustomField::exportCustomFields(new Business());

        return view('backend.business.index_datatable', compact('module_action', 'filter', 'select_data', 'assets', 'columns', 'customefield'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $query = Business::with('media')->get();

        return response()->json($query);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        // dd($actionType, $ids, $request->status);
        switch ($actionType) {
            case 'change-status':
                $businesses = Business::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_status_update');
                break;

            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                $businesses = Business::with('bookings')->whereIn('id', $ids)->get();

                foreach ($businesses as $business) {
                    $business->bookings()->delete();
                    $business->businessServices()->delete();
                    $business->delete();
                }
                $message = __('messages.bulk_status_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('business.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function update_status(Request $request, Business $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('business.status_update')]);
    }

    public function update_select(Request $request, Business $id)
    {
        $actionType = $request->action_type;
        switch ($actionType) {
            case 'update-business-for':
                $id->update(['business_for' => $request->value]);

                return response()->json(['status' => true, 'message' => __('business.business_update')]);
                break;
        }
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;

        $query = Business::withCount('businessEmployee')->with('media', 'address', 'employee');

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        $business_for_list = Constant::getTypeDataKeyValue('BRANCH_SERVICE_GENDER');

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row "  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('backend.business.action_column', compact('module_name', 'data'));
            })
            ->filterColumn('address.city', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('address', function ($q) use ($keyword) {
                        $q->where('city', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->filterColumn('address.postal_code', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('address', function ($q) use ($keyword) {
                        $q->where('postal_code', 'like', '%' . $keyword . '%');
                    });
                }
            })

            ->filterColumn('manager_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('employee', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderColumn('manager_id', function ($query, $order) {
                $query->select('businesses.*')
                    ->leftJoin('users', 'users.id', '=', 'businesses.manager_id')
                    ->orderBy('users.first_name', $order);
            })
            ->filterColumn('business_for', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('business_for', 'like', $keyword . '%');
                }
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->Where('name', 'like', '%' . $keyword . '%');
                    $query->orWhere('contact_email', 'like', '%' . $keyword . '%');
                }
            })
            ->editColumn('status', function ($row) {
                $checked = '';
                if ($row->status) {
                    $checked = 'checked="checked"';
                }

                return '
             <div class="form-check form-switch  ">
                 <input type="checkbox" data-url="' . route('backend.business.update_status', $row->id) . '" data-token="' . csrf_token() . '" class="switch-status-change form-check-input"  id="datatable-row-' . $row->id . '"  name="status" value="' . $row->id . '" ' . $checked . '>
             </div>
            ';
            })
            ->editColumn('name', function ($data) {
                $email = optional($data)->contact_email ?? '--';
                return view('backend.business.business_id', compact('data', 'email'));
            })
            ->editColumn('address.city', function ($data) {
                return $data->address->city ?? '';
            })
            ->editColumn('address.postal_code', function ($data) {
                return $data->address->postal_code ?? '-';
            })
            ->editColumn('manager_id', function ($data) {
                $Profile_image = optional($data->employee)->profile_image ?? default_user_avatar();
                $name = optional($data->employee)->full_name ?? default_user_name();
                $email = optional($data->employee)->email ?? '--';
                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email'));
            })
            ->editColumn('business_for', function ($data) use ($business_for_list) {
                return view('backend.business.select_column', compact('data', 'business_for_list'));
            })
            ->addColumn('assign', function ($data) {
                return "<div class='d-flex align-items-center'>
                <div>
                    <button type='button' data-assign-module='$data->id' data-assign-target='#staff-assign-form' data-assign-event='staff_assign' class='btn btn-primary btn-sm rounded btn-icon'>
                        <b>$data->business_employee_count</b>
                    </button>
                </div>
                 </div>";
            })

            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, Business::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['action', 'status', 'business_for', 'check', 'assign'], $customFieldColumns))
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(BusinessRequest $request)
    {
        $data = $request->except('feature_image');
        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $query = Business::create($data);

        $days = [
            ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'sunday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => true, 'breaks' => []],
        ];

        foreach ($days as $key => $val) {
            $val['business_id'] = $query->id;
            BussinessHour::create($val);
        }

        if (!empty($request->address) && is_string($data['address'])) {
            $request->address = json_decode($data['address'], true);
            $query->address()->save(new Address($request->address));
        }

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        }

        $business_id = $query->id;

        $manager_id = $request->manager_id;

        BusinessEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);

        // $user->syncRoles(['employee', 'manager']);

        \Artisan::call('cache:clear');

        BusinessEmployee::create([
            'business_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;

        $this->assign_service_business($service_id, $business_id);

        $message = __('messages.create_form', ['form' => __('business.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Business::with('address')->findOrFail($id);

        $service_id = ServiceBusinesses::where('business_id', $data->id)->get()->pluck('service_id');

        $data['service_id'] = $service_id;

        if (!is_null($data)) {
            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = $custom_field_data->custom_fields_data->toArray();
        }

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(BusinessRequest $request, $id)
    {
        $query = Business::findOrFail($id);

        $data = $request->except('feature_image'); // Initialize data

        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $query->update($data);

        if (!empty($request->address) && is_string($request['address'])) {
            $request->address = json_decode($request['address'], true);
            $query->address()->update($request->address);
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        } elseif ($request->feature_image === null) {
            $query->clearMediaCollection('feature_image');
        }

        $manager_id = $request->manager_id;
        BusinessEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);
        // if ($user) {
        //     $user->syncRoles(['employee', 'manager']);
        // }

        BusinessEmployee::create([
            'business_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;
        $this->assign_service_business($service_id, $query->id);

        $message = __('messages.update_form', ['form' => __('business.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }
        $data = Business::findOrFail($id);

        $data->bookings()->delete();

        $data->businessServices()->delete();

        $data->businessEmployee()->delete();

        $data->delete();

        $message = __('messages.delete_form', ['form' => __('business.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function assign_list($id)
    {
        $business_user = BusinessEmployee::with('employee', 'getBusiness')->where('business_id', $id)->get();

        $business_user = $business_user->each(function ($data) {

            $data['business_name'] = $data->getBusiness->name;
            $data['name'] = $data->employee->full_name;
            $data['avatar'] = $data->employee->profile_image;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $business_user]);
    }

    public function assign_update(Business $id, Request $request)
    {
        $id->businessEmployee()->delete();

        $employees = [];

        foreach ($request->users as $emp_id) {
            $businessEmployee = BusinessEmployee::where('employee_id', $emp_id)->get();
            if (count($businessEmployee) > 0) {
                BusinessEmployee::where('employee_id', $emp_id)->delete();
            } else {
                $businessEmployee = BusinessEmployee::where('employee_id', $emp_id)->first();
                if (isset($businessEmployee)) {
                    $businessEmployee->update(['business_id' => $id->id]);

                    continue;
                }
            }
            $employees[] = ['employee_id' => $emp_id];
        }

        $id->businessEmployee()->createMany($employees);

        return response()->json(['status' => true, 'message' => __('business.business_successfull')]);
    }

    public function business_list(Request $request)
    {
        $term = $request->q;
        $role = $request->role;
        $query_data = BusinessEmployee::select('*', 'id as employee_id')->where(function ($q) use ($term, $role) {
            if (!empty($term)) {
                $q->orWhere('name', 'LIKE', "%$term%");
            }
            if (!empty($role)) {
                $q->role($role);
            }
        })->get();

        return response()->json($query_data);
    }

    public function getGalleryImages($id)
    {
        $business = Business::findOrFail($id);

        $data = BusinessGallery::where('business_id', $id)->get();

        return response()->json(['data' => $data, 'business' => $business, 'status' => true]);
    }

    public function uploadGalleryImages(Request $request, $id)
    {
        $gallery = collect($request->gallery, true);

        $images = BusinessGallery::where('business_id', $id)->whereNotIn('id', $gallery->pluck('id'))->get();

        foreach ($images as $key => $value) {
            $value->clearMediaCollection('gallery_images');
            $value->delete();
        }

        foreach ($gallery as $key => $value) {
            if ($value['id'] == 'null') {
                $businessGallery = BusinessGallery::create([
                    'business_id' => $id,
                ]);

                $businessGallery->addMedia($value['file'])->toMediaCollection('gallery_images');

                $businessGallery->full_url = $businessGallery->getFirstMediaUrl('gallery_images');
                $businessGallery->save();
            }
        }

        return response()->json(['message' => __('business.update_business_gallery'), 'status' => true]);
    }

    protected function assign_service_business($service_id, $business_id)
    {
        $service_id = is_string($service_id) ? explode(',', $service_id) : $service_id;
        if (isset($service_id) && count($service_id)) {
            $services = Service::whereIn('id', $service_id)->get();
            ServiceBusinesses::where('business_id', $business_id)->delete();
            foreach ($service_id as $key => $value) {
                $service = $services->where('id', $value)->first();
                ServiceBusinesses::create([
                    'service_id' => $value,
                    'business_id' => $business_id,
                    'service_price' => $service->default_price ?? 0,
                    'duration_min' => $service->duration_min,
                ]);
            }
        }
    }

    public function businessData()
    {
        if (Auth::user()->hasRole('manager')) {
            $data = Business::where('id', auth()->user()->business->business_id)->with('address')->first();

            $service_id = ServiceBusinesses::where('business_id', $data->id)->get()->pluck('service_id');

            $data['service_id'] = $service_id;

            if (!is_null($data)) {
                $custom_field_data = $data->withCustomFields();
                $data['custom_field_data'] = $custom_field_data->custom_fields_data->toArray();
            }

            return response()->json(['data' => $data, 'status' => true]);
        } else {
            return response()->json(['message' => 'You are not authorized to access this data.', 'status' => false]);
        }
    }

    public function UpdateBusinessSetting(Request $request)
    {
        $query = Business::findOrFail(auth()->user()->business->business_id);

        $data = $request->except('feature_image');
        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $query->update($data);

        if (!empty($request->address) && is_string($request['address'])) {
            $request->address = json_decode($request['address'], true);
            $query->address()->update($request->address);
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        }

        $business_id = $query->id;

        $manager_id = $request->manager_id;

        BusinessEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);

        if ($user) {
            $user->syncRoles(['employee', 'manager']);
        }

        \Artisan::call('cache:clear');

        BusinessEmployee::create([
            'business_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;

        $this->assign_service_business($service_id, $business_id);

        $message = __('messages.update_form', ['form' => __('business.business_setting')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
