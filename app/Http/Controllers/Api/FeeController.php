<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use App\Models\FeeGroup;
use App\Models\FeeAllocation;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FeeController extends Controller
{
    public function types(): JsonResponse
    {
        $types = FeeType::latest()->get();
        return response()->json($types);
    }

    public function storeType(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $type = FeeType::create($request->all());
        return response()->json($type, 201);
    }

    public function groups(): JsonResponse
    {
        $groups = FeeGroup::with('feeType')->latest()->get();
        return response()->json($groups);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date'
        ]);

        $group = FeeGroup::create($request->all());
        return response()->json($group->load('feeType'), 201);
    }

    public function allocations(): JsonResponse
    {
        $allocations = FeeAllocation::with(['student', 'feeGroup.feeType'])->latest()->get();
        return response()->json($allocations);
    }

    public function storeAllocation(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_group_id' => 'required|exists:fee_groups,id'
        ]);

        $feeGroup = FeeGroup::find($request->fee_group_id);
        
        $allocation = FeeAllocation::create([
            'student_id' => $request->student_id,
            'fee_group_id' => $request->fee_group_id,
            'amount' => $feeGroup->amount,
            'due_date' => $feeGroup->due_date,
            'status' => 'pending'
        ]);

        return response()->json($allocation->load(['student', 'feeGroup']), 201);
    }

    public function payments(): JsonResponse
    {
        $payments = FeePayment::with(['student', 'feeAllocation.feeGroup.feeType'])->latest()->get();
        return response()->json($payments);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_allocation_id' => 'required|exists:fee_allocations,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string'
        ]);

        $payment = FeePayment::create([
            'student_id' => $request->student_id,
            'fee_allocation_id' => $request->fee_allocation_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'remarks' => $request->remarks,
            'collected_by' => auth()->id() ?? 1
        ]);
        
        return response()->json($payment->load(['student', 'feeAllocation.feeGroup.feeType']), 201);
    }

    public function dashboard(): JsonResponse
    {
        $totalStudents = Student::where('active', true)->count();
        $totalCollected = FeePayment::sum('amount');
        $totalAllocated = FeeAllocation::sum('amount');
        $totalDue = $totalAllocated - $totalCollected;
        $overdueCount = FeeAllocation::where('due_date', '<', now())
                                   ->where('status', '!=', 'paid')
                                   ->count();
        
        $recentPayments = FeePayment::with(['student', 'feeAllocation.feeGroup.feeType'])
                                   ->orderBy('payment_date', 'desc')
                                   ->limit(10)
                                   ->get();
        
        $monthlyCollection = FeePayment::select(
            DB::raw('MONTH(payment_date) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->whereYear('payment_date', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get();
        
        return response()->json([
            'totalStudents' => $totalStudents,
            'totalCollected' => $totalCollected,
            'totalDue' => $totalDue,
            'overdueCount' => $overdueCount,
            'recentPayments' => $recentPayments,
            'monthlyCollection' => $monthlyCollection
        ]);
    }

    public function sendReminders(): JsonResponse
    {
        $overdueAllocations = FeeAllocation::with(['student', 'feeGroup.feeType'])
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->get();

        $sentCount = 0;
        
        foreach ($overdueAllocations as $allocation) {
            $student = $allocation->student;
            
            if ($student && $student->guardian_email) {
                try {
                    Mail::raw(
                        "Dear {$student->guardian_name},\n\n" .
                        "This is a reminder that your child {$student->first_name}'s {$allocation->feeGroup->feeType->name} fee of UGX " .
                        number_format($allocation->amount) . " was due on " .
                        date('M j, Y', strtotime($allocation->due_date)) . ".\n\n" .
                        "Student: {$student->first_name} {$student->last_name}\n" .
                        "Class: {$student->class}\n" .
                        "Register No: {$student->register_no}\n\n" .
                        "Please make the payment as soon as possible to avoid any inconvenience.\n\n" .
                        "Thank you,\nMuko High School Administration",
                        function ($message) use ($student) {
                            $message->to($student->guardian_email)
                                   ->subject('Fee Payment Reminder - Muko High School');
                        }
                    );
                    $sentCount++;
                } catch (\Exception $e) {
                    \Log::error('Failed to send reminder email: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => "Sent {$sentCount} reminder emails successfully",
            'sent_count' => $sentCount,
            'total_overdue' => $overdueAllocations->count()
        ]);
    }
}