<?php

namespace Modules\Employee\Models;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchEmployee extends Model
{
    use HasFactory;

    protected $table = 'business_employee';

    protected $fillable = [
        'employee_id',
        'business_id',
        'is_primary',
    ];

    protected static function newFactory()
    {
        return \Modules\Employee\Database\factories\BusinessEmployeeFactory::new();
    }

    public function getBusiness()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function rating()
    {
        return $this->hasMany(EmployeeRating::class, 'id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
