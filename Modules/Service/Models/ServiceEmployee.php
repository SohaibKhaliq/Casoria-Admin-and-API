<?php

namespace Modules\Service\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Models\BusinessEmployee;

class ServiceEmployee extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'employee_id'];

    protected $casts = [

        'service_id' => 'integer',
        'employee_id' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function businesses()
    {
        return $this->hasMany(BusinessEmployee::class, 'employee_id');
    }
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }
}
