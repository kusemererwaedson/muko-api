<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Student::with(['class', 'stream'])->get();
    }

    public function headings(): array
    {
        return [
            'LIN',
            'First Name',
            'Middle Name',
            'Last Name',
            'Class',
            'Stream',
            'Gender',
            'Birthday',
            'Admission Date',
            'Address',
            'Guardian Name',
            'Guardian Phone',
            'Guardian Email',
            'Guardian Relationship',
            'Status'
        ];
    }

    public function map($student): array
    {
        return [
            $student->lin,
            $student->first_name,
            $student->middle_name,
            $student->last_name,
            $student->class->name ?? '',
            $student->stream->name ?? '',
            $student->gender,
            $student->birthday?->format('Y-m-d'),
            $student->admission_date?->format('Y-m-d'),
            $student->address,
            $student->guardian_name,
            $student->guardian_phone,
            $student->guardian_email,
            $student->guardian_relationship,
            $student->active ? 'Active' : 'Inactive'
        ];
    }
}