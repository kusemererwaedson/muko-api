<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'message', 'recipient_count', 'delivered_count', 'status', 'scheduled_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
}