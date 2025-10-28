<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_stream', 'stream_id', 'class_id')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'stream_id');
    }
}