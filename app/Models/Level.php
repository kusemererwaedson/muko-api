<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'description'];

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }
}