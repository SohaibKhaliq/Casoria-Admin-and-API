<?php

namespace App\Trait;

trait CommonQuery
{
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeBusinessBased($query, $business_id = null)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeMultiBusinessBased($query, $business_id = [])
    {
        return $query->whereIn('business_id', $business_id);
    }
}
