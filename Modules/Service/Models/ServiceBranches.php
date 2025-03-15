<?php

namespace Modules\Service\Models;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceBusinesses extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'business_id', 'service_price', 'duration_min'];

    protected $casts = [

        'service_id' => 'integer',
        'business_id' => 'integer',
        'service_price' => 'double',
        'duration_min' => 'double',

    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
