<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'fee_group_id', 'amount', 'due_date', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeGroup()
    {
        return $this->belongsTo(FeeGroup::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }
}