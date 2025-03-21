<?php

namespace Modules\Page\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Page\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Get all pages and return them in JSON format.
     *
     * @return JsonResponse
     */
    public static function index(): JsonResponse
    {
        $pages = Page::all();

        return response()->json([
            'status' => true,
            'data' => $pages,
        ]);
    }
}