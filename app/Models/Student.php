<?php
// app/Models/Student.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_no', 'lin', 'first_name', 'middle_name', 'last_name',
        'class_id', 'stream_id', 'gender', 'birthday',
        'admission_date', 'address', 'picture',
        'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relationship',
        'active', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'birthday' => 'date',
        'admission_date' => 'date',
        'active' => 'boolean',
    ];

    protected $appends = ['full_name', 'class_name', 'stream_name'];

    protected static function booted()
    {
        static::creating(function ($student) {
            $student->created_by = Auth::id() ?? 1; // fallback to system user ID 1
            $student->updated_by = Auth::id() ?? 1;
        });

        static::updating(function ($student) {
            $student->updated_by = Auth::id() ?? 1;
        });
    }

    // Relations
    public function feeAllocations()
    {
        return $this->hasMany(FeeAllocation::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

        public function feeTypes()
    {
        return $this->belongsToMany(FeeType::class, 'student_fee_type')
            ->withTimestamps();
    }

    public function feeGroups()
    {
        return $this->belongsToMany(FeeGroup::class, 'student_fee_group')
            ->withTimestamps();
    }
    // Accessors
    public function getFullNameAttribute()
    {
        $name = $this->first_name;
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        $name .= ' ' . $this->last_name;
        return $name;
    }

    public function getClassNameAttribute()
    {
        return $this->schoolClass?->name;
    }

    public function getStreamNameAttribute()
    {
        return $this->stream?->name;
    }
}
