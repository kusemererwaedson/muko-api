<?php
// app/Models/FeeType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'from',
        'to',
        'created_by',
        'edited_by',
    ];

    // Cast 'from' and 'to' as dates
    protected $casts = [
        'from' => 'date',
        'to' => 'date',
    ];

        public function students()
    {
        return $this->belongsToMany(Student::class, 'student_fee_type')
            ->withTimestamps();
    }
}
