<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_email', 'recipient_name', 'subject', 'message', 
        'status', 'sent_at', 'error_message', 'email_type'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}