<?php

namespace Modules\Employee\Models;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRating extends Model
{
    use HasFactory;

    protected $table = 'employee_rating';

    protected $fillable = [
        'employee_id',
        'review_msg',
        'rating',
        'user_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Employee\Database\factories\BusinessEmployeeFactory::new();
    }

    public function getBusiness()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function businessemployee()
    {
        return $this->belongsTo(BranchEmployee::class); // Updated reference to BranchEmployee class
    }
}
