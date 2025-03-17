<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Currency\Models\Currency;

class SettingController extends Controller
{
    public function appConfiguraton(Request $request)
    {
        $settings = Setting::all()->pluck('val', 'name');

        $currencies = Currency::all();
        $response = [];

        // Define the specific names you want to include
        $specificNames = ['app_name', 'footer_text', 'primary', 'google_maps_key', 'helpline_number', 'copyright', 'inquriy_email', 'customer_app_play_store', 'customer_app_app_store', 'version_code'];
        // Fetch currency data
        $currencies = Currency::all();

        $currencyData = null;
        if ($currencies->isNotEmpty()) {
            $currency = $currencies->first();
            $currencyData = [
                'currency_name' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
                'currency_position' => $currency->currency_position,
                'no_of_decimal' => $currency->no_of_decimal,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];
        }

        if (isset($settings['version_code'])) {
            $response['version_code'] = intval($settings['version_code']);
        } else {

            $response['version_code'] = 0;
        }
        if ($request->user_id) {
            $user = User::withTrashed()->find($request->user_id);

            if ($user && $user->trashed()) {
                $response['is_user_authorized'] = false;
            } elseif ($user) {
                $response['is_user_authorized'] = true;
            } else {
                $response['is_user_authorized'] = false;
            }
        } else {
            $response['is_user_authorized'] = false;
        }
        $response['currency'] = $currencyData;
        $response['otp_login_status'] = 'false';
        // Add locale language to the response
        $response['application_language'] = app()->getLocale();
        $response['status'] = true;

        return response()->json($response);
    }
}
