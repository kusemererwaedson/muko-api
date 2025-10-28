<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';
    protected $fillable = ['name', 'level_id', 'year_of_study', 'capacity'];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function streams()
    {
        return $this->belongsToMany(Stream::class, 'class_stream', 'class_id', 'stream_id')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}