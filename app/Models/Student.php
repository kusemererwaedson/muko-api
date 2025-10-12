<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'register_no', 'first_name', 'last_name', 'class', 'section', 'roll',
        'gender', 'birthday', 'admission_date', 'email', 'phone', 'address',
        'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relationship', 'active'
    ];

    protected $casts = [
        'birthday' => 'date',
        'admission_date' => 'date',
        'active' => 'boolean',
    ];

    public function feeAllocations()
    {
        return $this->hasMany(FeeAllocation::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
