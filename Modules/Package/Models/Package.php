<?php

namespace Modules\Package\Models;

use App\Models\BaseModel;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'packages';

    const CUSTOM_FIELD_MODEL = 'Modules\Package\Models\Package';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Package\database\factories\PackageFactory::new();
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function scopeBusiness($query)
    {
        $business_id = request()->selected_session_business_id;
        if (isset($business_id)) {
            return $query->where('business_id', $business_id);
        } else {
            return $query->whereNotNull('business_id');
        }
    }

    public function employees()
    {
        return $this->belongsToMany(PackageEmployee::class, 'package_employees', 'package_id', 'employee_id');
    }

    public function employee()
    {
        return $this->hasMany(PackageEmployee::class, 'package_id');
    }

    public function services()
    {
        return $this->belongsToMany(PackageService::class, 'package_services', 'package_id', 'service_id');
    }

    public function service()
    {
        return $this->hasMany(PackageService::class, 'package_id');
    }

    public function userPackage()
    {
        return $this->hasMany(UserPackage::class, 'package_id');
    }
}
