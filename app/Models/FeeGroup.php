<?php
// app/Models/FeeGroup.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FeeGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class',
        'fee_type_id',
        'amount',
        'due_date',
        'created_by',
        'updated_by'
    ];

        protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($feeGroup) {
            $feeGroup->created_by = Auth::id() ?? 1;
            $feeGroup->updated_by = Auth::id() ?? 1;
        });

        static::updating(function ($feeGroup) {
            $feeGroup->updated_by = Auth::id() ?? 1;
        });
    }
    
    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

        public function students()
    {
        return $this->belongsToMany(Student::class, 'student_fee_group')
            ->withTimestamps();
    }
}