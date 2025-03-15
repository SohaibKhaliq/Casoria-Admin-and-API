<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessGallery extends BaseModel
{
    use HasFactory;

    protected $fillable = ['business_id', 'full_url', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }
}
