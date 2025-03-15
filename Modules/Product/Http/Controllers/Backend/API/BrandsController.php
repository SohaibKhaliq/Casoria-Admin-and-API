<?php

namespace Modules\Product\Http\Controllers\Backend\API;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Models\Brands;
use Modules\Product\Transformers\BrandResource;

class BrandsController extends Controller
{
    public function product_brand(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Get the number of items per page from the request (default: 10)
        $businessId = $request->input('business_id');
        $brand = Brands::with('media')
            ->where('status', 1);
        // ->whereHas('businesses', function ($query) use ($businessId) {
        //     $query->where('business_id', $businessId);
        // });

        $brand = $brand->paginate($perPage);
        // $brand = $brand->paginate($perPage)->appends('business_id', $businessId);
        $brandCollection = BrandResource::collection($brand);

        if ($request->has('brand_id') && $request->brand_id != '') {
            $brand = $brand->where('id', $request->brand_id)->first();

            $brandCollection = new BrandResource($brand);
        }

        return response()->json([
            'status' => true,
            'data' => $brandCollection,
            'message' => __('brand.brand_list'),
        ], 200);
    }
}
