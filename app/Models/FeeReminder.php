<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'frequency', 'days', 'student', 'guardian', 'message', 'active'
    ];

    protected $casts = [
        'student' => 'boolean',
        'guardian' => 'boolean',
        'active' => 'boolean',
    ];
}