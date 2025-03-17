<?php

namespace Modules\Service\Models;

use App\Models\BaseModel;
use App\Models\Business;
use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;
use Modules\Service\Models\ServiceBusinesses;

class Service extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = ['slug', 'name', 'description', 'duration_min', 'default_price', 'category_id', 'sub_category_id', 'status'];

    protected $appends = ['feature_image'];

    protected $casts = [

        'duration_min' => 'integer',
        'default_price' => 'double',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'status' => 'integer',

    ];

    const CUSTOM_FIELD_MODEL = 'Modules\Service\Models\Service';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Service\database\factories\ServiceFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($table) {
            //
        });

        static::saving(function ($table) {
            //
        });

        static::updating(function ($table) {
            //
        });
    }

    public function employee()
    {
        return $this->hasMany(ServiceEmployee::class, 'service_id', 'id');
    }

    public function gallery()
    {
        return $this->hasMany(ServiceGallery::class, 'id', 'feature_image');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function businesses()
    {
        return $this->hasMany(ServiceBusinesses::class, 'service_id');
    }

    public function businessies()
    {
        return $this->belongsToMany(Business::class, 'service_businesses');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}