<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Currency\Models\Currency;
use Modules\Page\Http\Controllers\Backend\PageController;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\returnSelf;

class SettingController extends Controller
{
    // public function appConfiguraton(Request $request)
    // {
    //     $settings = Setting::all();

    //     $currencies = Currency::all();
    //     $response = [];

    //     // Define the specific names you want to include
    //     $specificNames = ['app_name', 'primary', 'helpline_number', 'copyright', 'inquriy_email', 'version_code'];
    //     // Fetch currency data
    //     $currencies = Currency::all();

    //     $currencyData = null;
    //     if ($currencies->isNotEmpty()) {
    //         $currency = $currencies->first();
    //         $currencyData = [
    //             'currency_name' => $currency->currency_name,
    //             'currency_symbol' => $currency->currency_symbol,
    //             'currency_code' => $currency->currency_code,
    //             'currency_position' => $currency->currency_position,
    //             'no_of_decimal' => $currency->no_of_decimal,
    //             'thousand_separator' => $currency->thousand_separator,
    //             'decimal_separator' => $currency->decimal_separator,
    //         ];
    //     }

    //     if (isset($settings['version_code'])) {
    //         $response['version_code'] = intval($settings['version_code']);
    //     } else {

    //         $response['version_code'] = 0;
    //     }
    //     if ($request->user_id) {
    //         $user = User::withTrashed()->find($request->user_id);

    //         if ($user && $user->trashed()) {
    //             $response['is_user_authorized'] = false;
    //         } elseif ($user) {
    //             $response['is_user_authorized'] = true;
    //         } else {
    //             $response['is_user_authorized'] = false;
    //         }
    //     } else {
    //         $response['is_user_authorized'] = false;
    //     }
    //     $response['currency'] = $currencyData;
    //     $response['otp_login_status'] = 'false';
    //     // Add locale language to the response
    //     $response['application_language'] = app()->getLocale();
    //     $response['status'] = true;
    //     $response['data'] = collect($settings)->only($specificNames);;

    //     return response()->json($response);
    // }

    public function appConfiguraton(Request $request)
    {
        // Get all settings with correct column names
        $settings = Setting::pluck('val', 'name')->toArray();
        $address = implode(', ', array_filter([
            $settings['bussiness_address_line_1'] ?? null,
            $settings['bussiness_address_line_2'] ?? null,
            $settings['bussiness_address_city'] ?? null,
            $settings['bussiness_address_state'] ?? null,
            $settings['bussiness_address_country'] ?? null,
            $settings['bussiness_address_postal_code'] ?? null,
        ]));


        $pagesResponse = PageController::index();

        if (!$pagesResponse instanceof \Illuminate\Http\JsonResponse) {
            Log::error('Invalid response type from PageController::index()', ['response' => $pagesResponse]);
            $pages = collect(); // Set to an empty collection
        } else {
            $pagesContent = $pagesResponse->getContent();
            $decodedPages = json_decode($pagesContent, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($decodedPages['data']) && is_array($decodedPages['data'])) {
                $pages = collect($decodedPages['data'])->map(function ($page) {
                    return [
                        'name' => $page['name'] ?? 'N/A',
                        'description' => $page['description'] ?? 'N/A',
                    ];
                });
            } else {
                Log::error('Invalid JSON response from PageController::index()', [
                    'response_content' => $pagesContent,
                    'json_error' => json_last_error_msg(),
                ]);
                $pages = collect(); // Set to an empty collection if JSON is invalid
            }
        }


        // Fetch currency data
        $currency = Currency::first();
        $currencyData = $currency ? [
            'currency_name' => $currency->currency_name,
            'currency_symbol' => $currency->currency_symbol,
            'currency_code' => $currency->currency_code,
            'currency_position' => $currency->currency_position,
            'no_of_decimal' => $currency->no_of_decimal,
            'thousand_separator' => $currency->thousand_separator,
            'decimal_separator' => $currency->decimal_separator,
        ] : null;
        // get all except mail_driver,mail_host, mail_port, mail_username, mail_password, mail_encryption, mail_from_address, mail_from_name
        $settings = collect($settings)->except([
            'mail_driver',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'bussiness_address_line_1',
            'bussiness_address_line_2',
            'bussiness_address_city',
            'bussiness_address_state',
            'bussiness_address_country',
            'bussiness_address_postal_code',
        ]);
        // Prepare response
        $response = [
            'version_code' => "1.0.0",
            'pages' => $pages,
            'is_user_authorized' => false,
            'currency' => $currencyData,
            'application_language' => app()->getLocale(),
            'status' => true,
            'data' => $settings,  // Send **filtered** settings
            'address' => $address,
        ];

        // Check user authorization
        if ($request->user_id) {
            $user = User::withTrashed()->find($request->user_id);
            $response['is_user_authorized'] = $user ? !$user->trashed() : false;
        }

        return response()->json($response);
    }
}