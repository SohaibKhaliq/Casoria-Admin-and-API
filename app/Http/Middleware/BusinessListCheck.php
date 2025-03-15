<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;

class BusinessListCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('user')) {
                \Auth::logout();
                abort(403, 'Unauthorized action.');
            }
            $businessId = request()->session()->get('selected_business');
            $businesses = Business::getAllBusinesses();
            $selected_business = $businesses->where('id', $businessId)->first();
            $auth = auth()->user();

            if (auth()->user()->hasRole('admin')) {
                if (
                    str_contains($request->route()->getName(), 'backend.bookings')
                    && $request->route()->getName() !== 'backend.bookings.index_data'
                    && $request->route()->getName() !== 'backend.bookings.datatable_view'
                ) {
                    if (! isset($selected_business) && count($businesses) > 0) {
                        $selected_business = $businesses[0];
                    }
                }
            }

            if (auth()->user()->hasRole('employee')) {
                try {
                    $selected_business = Business::find(auth()->user()->business->business_id);
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }

            $isSingleBusiness = false;

            if (count($businesses) == 1) {
                $isSingleBusiness = true;
                $selected_business = $businesses[0];
            }

            $data = [
                'auth_user_businesses' => $businesses,
                'selected_business' => $selected_business,
                'selected_business_id' => isset($selected_business) ? $selected_business->id : 0,
                'is_single_business' => $isSingleBusiness,
                'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
            ];

            $request->merge([
                'selected_session_business_id' => isset($selected_business) ? $selected_business->id : null,
                'is_single_business' => $isSingleBusiness,
            ]);

            view()->share($data);
        }

        return $next($request);
    }
}
