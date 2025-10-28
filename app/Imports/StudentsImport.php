<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Stream;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $class = SchoolClass::where('name', $row['class'])->first();
            $stream = null;
            
            if ($class && !empty($row['stream'])) {
                $stream = Stream::where('name', $row['stream'])
                               ->where('class_id', $class->id)
                               ->first();
            }

            Student::create([
                'register_no' => $row['lin'],
                'lin' => $row['lin'],
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'],
                'class' => $row['class'],
                'class_id' => $class?->id,
                'stream_id' => $stream?->id,
                'gender' => strtolower($row['gender']),
                'birthday' => !empty($row['birthday']) ? date('Y-m-d', strtotime($row['birthday'])) : null,
                'admission_date' => date('Y-m-d', strtotime($row['admission_date'])),
                'address' => $row['address'] ?? null,
                'guardian_name' => $row['guardian_name'],
                'guardian_phone' => $row['guardian_phone'],
                'guardian_email' => $row['guardian_email'] ?? null,
                'guardian_relationship' => $row['guardian_relationship'] ?? null,
                'active' => true,
            ]);
        }
    }
}