<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'fee_allocation_id', 'amount', 'payment_method', 'payment_date', 'remarks', 'collected_by'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeAllocation()
    {
        return $this->belongsTo(FeeAllocation::class);
    }
}