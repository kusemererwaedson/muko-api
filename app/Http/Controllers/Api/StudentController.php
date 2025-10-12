<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['feeAllocations.feeGroup.feeType', 'feePayments'])
            ->latest()
            ->get();
        
        return response()->json($students);
    }

    public function store(Request $request)
    {
        $request->validate([
            'register_no' => 'required|unique:students',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'admission_date' => 'required|date',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_relationship' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $student = Student::create($request->all());
        return response()->json($student, 201);
    }

    public function show(Student $student)
    {
        $student->load(['feeAllocations.feeGroup.feeType', 'feePayments']);
        return response()->json($student);
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'register_no' => 'required|unique:students,register_no,' . $student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'admission_date' => 'required|date',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_relationship' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $student->update($request->all());
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }
}