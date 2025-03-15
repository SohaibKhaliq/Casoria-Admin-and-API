<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Modules\Booking\Models\Booking;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Employee\Models\BusinessEmployee;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBusinesses;
use Modules\Employee\Models\BranchEmployee;

class Business extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;

    const CUSTOM_FIELD_MODEL = 'App\Models\Business';

    protected $casts = [
        'contact_number' => 'string',
        'payment_method' => 'array',
        'city' => 'integer',
        'state' => 'integer',
        'country' => 'integer',
    ];

    protected $appends = ['feature_image'];

    /**
     * Get all the settings.
     *
     * @return mixed
     */
    public static function getAllBusinesses()
    {
        return Cache::rememberForever('business.all', function () {
            return self::get();
        });
    }

    /**
     * Flush the cache.
     */
    public static function flushCache()
    {
        Cache::forget('business.all');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            self::flushCache();
        });

        static::created(function () {
            self::flushCache();
        });

        static::deleted(function () {
            self::flushCache();
        });
    }

    public function gallery()
    {
        return $this->hasMany(BusinessGallery::class, 'id', 'feature_image');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function businessEmployee()
    {
        return $this->hasMany(BranchEmployee::class, 'business_id');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    public function gallerys()
    {
        return $this->hasMany(BusinessGallery::class, 'business_id', 'id');
    }

    public function businessHours()
    {
        return $this->hasMany(BussinessHour::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_businesses');
    }

    public function businessServices()
    {
        return $this->hasMany(ServiceBusinesses::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}