<?php

// app/Http/Controllers/Api/FeeController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeAllocation;
use App\Models\FeeGroup;
use App\Models\FeePayment;
use App\Models\FeeType;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FeeController extends Controller
{
    public function types(Request $request): JsonResponse
    {
        $query = FeeType::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('from_date')) {
            $query->where('from', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('to', '<=', $request->get('to_date'));
        }

        $perPage = $request->get('per_page', 15);
        $types = $query->latest()->paginate($perPage);

        return response()->json($types);
    }

    public function storeType(Request $request): JsonResponse
    {
        $data = $request->all();
        if (isset($data[0]) && is_array($data[0])) {
            $types = [];
            foreach ($data as $typeData) {
                $validated = validator($typeData, [
                    'name' => 'required|string|max:255|unique:fee_types',
                    'description' => 'nullable|string',
                    'from' => 'required|date',
                    'to' => 'required|date|after:from',
                ])->validate();
                $validated['created_by'] = auth()->id() ?? 1;
                $validated['edited_by'] = auth()->id() ?? 1;
                $types[] = FeeType::create($validated);
            }

            return response()->json($types, 201);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_types',
            'description' => 'nullable|string',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);
        $validated['created_by'] = auth()->id() ?? 1;
        $validated['edited_by'] = auth()->id() ?? 1;
        $type = FeeType::create($validated);

        return response()->json($type, 201);
    }

    public function groups(Request $request): JsonResponse
    {
        $query = FeeGroup::with('feeType');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('class')) {
            $query->where('class', $request->get('class'));
        }

        if ($request->has('fee_type_id')) {
            $query->where('fee_type_id', $request->get('fee_type_id'));
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->get('min_amount'));
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->get('max_amount'));
        }

        $perPage = $request->get('per_page', 15);
        $groups = $query->latest()->paginate($perPage);

        return response()->json($groups);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $data = $request->all();
        if (isset($data[0]) && is_array($data[0])) {
            $groups = [];
            foreach ($data as $groupData) {
                $validated = validator($groupData, [
                    'name' => 'required|string|max:255',
                    'class' => 'required|string|max:50',
                    'fee_type_id' => 'required|exists:fee_types,id',
                    'amount' => 'required|numeric|min:0',
                    'due_date' => 'required|date',
                ])->validate();
                $groups[] = FeeGroup::create($validated);
            }

            return response()->json($groups, 201);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:50',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
        ]);
        $group = FeeGroup::create($request->all());

        return response()->json($group->load('feeType'), 201);
    }

    public function allocations(Request $request): JsonResponse
    {
        $query = FeeAllocation::with(['student', 'feeGroup.feeType']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        if ($request->has('fee_group_id')) {
            $query->where('fee_group_id', $request->get('fee_group_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('overdue')) {
            $query->where('due_date', '<', now())
                  ->where('status', '!=', 'paid');
        }

        $perPage = $request->get('per_page', 15);
        $allocations = $query->latest()->paginate($perPage);

        return response()->json($allocations);
    }

    public function storeAllocation(Request $request): JsonResponse
    {
        $data = $request->all();
        if (isset($data[0]) && is_array($data[0])) {
            $allocations = [];
            foreach ($data as $allocationData) {
                $validated = validator($allocationData, [
                    'student_id' => 'required|exists:students,id',
                    'fee_group_id' => 'required|exists:fee_groups,id',
                ])->validate();
                $feeGroup = FeeGroup::find($validated['fee_group_id']);
                $allocations[] = FeeAllocation::create([
                    'student_id' => $validated['student_id'],
                    'fee_group_id' => $validated['fee_group_id'],
                    'amount' => $feeGroup->amount,
                    'due_date' => $feeGroup->due_date,
                    'status' => 'pending',
                ]);
            }

            return response()->json($allocations, 201);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_group_id' => 'required|exists:fee_groups,id',
        ]);
        $feeGroup = FeeGroup::find($request->fee_group_id);
        $allocation = FeeAllocation::create([
            'student_id' => $request->student_id,
            'fee_group_id' => $request->fee_group_id,
            'amount' => $feeGroup->amount,
            'due_date' => $feeGroup->due_date,
            'status' => 'pending',
        ]);

        return response()->json($allocation->load(['student', 'feeGroup']), 201);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = FeePayment::with(['student', 'feeAllocation.feeGroup.feeType']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->get('to_date'));
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->get('min_amount'));
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->get('max_amount'));
        }

        $perPage = $request->get('per_page', 15);
        $payments = $query->latest('payment_date')->paginate($perPage);

        return response()->json($payments);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $data = $request->all();
        if (isset($data[0]) && is_array($data[0])) {
            $payments = [];
            foreach ($data as $paymentData) {
                $validated = validator($paymentData, [
                    'student_id' => 'required|exists:students,id',
                    'fee_allocation_id' => 'required|exists:fee_allocations,id',
                    'amount' => 'required|numeric|min:0',
                    'payment_method' => 'required|string',
                    'payment_date' => 'required|date',
                    'remarks' => 'nullable|string',
                ])->validate();
                $payments[] = FeePayment::create([
                    'student_id' => $validated['student_id'],
                    'fee_allocation_id' => $validated['fee_allocation_id'],
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'remarks' => $validated['remarks'] ?? null,
                    'collected_by' => auth()->id() ?? 1,
                ]);
            }

            return response()->json($payments, 201);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_allocation_id' => 'required|exists:fee_allocations,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);
        $payment = FeePayment::create([
            'student_id' => $request->student_id,
            'fee_allocation_id' => $request->fee_allocation_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'remarks' => $request->remarks,
            'collected_by' => auth()->id() ?? 1,
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

    $overdueAmount = FeeAllocation::where('due_date', '<', now())
                                ->where('status', '!=', 'paid')
                                ->sum('amount');

    $recentPayments = FeePayment::with(['student', 'feeAllocation.feeGroup.feeType'])
                               ->orderBy('payment_date', 'desc')
                               ->limit(10)
                               ->get();

    // Monthly collection data for chart (12 months)
    $monthlyData = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthlyData[$i] = 0;
    }

    $monthlyCollection = FeePayment::select(
        DB::raw('MONTH(payment_date) as month'),
        DB::raw('SUM(amount) as total')
    )
    ->whereYear('payment_date', date('Y'))
    ->groupBy('month')
    ->orderBy('month')
    ->get();

    foreach ($monthlyCollection as $month) {
        $monthlyData[$month->month] = $month->total;
    }

    // Monthly expense data (12 months)
    $monthlyExpenseData = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthlyExpenseData[$i] = 0;
    }

    $monthlyExpenses = DB::table('transactions')
        ->select(
            DB::raw('MONTH(date) as month'),
            DB::raw('SUM(amount) as total')
        )
        ->where('type', 'debit')
        ->whereYear('date', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    foreach ($monthlyExpenses as $month) {
        $monthlyExpenseData[$month->month] = $month->total;
    }

    $expenseCategories = array_values($monthlyExpenseData);

    // NEW: Calculate total expenses (all debit transactions)
    $totalExpenses = DB::table('transactions')
        ->where('type', 'debit')
        ->sum('amount');

    // NEW: Get account balances by account type
    $cashAtHand = DB::table('accounts')
        ->where('account_type', 'cash')
        ->sum('current_balance');

    $cashInBank = DB::table('accounts')
        ->where('account_type', 'bank')
        ->sum('current_balance');

    $cashOnMobileMoney = DB::table('accounts')
        ->where('account_type', 'mobile_money')
        ->sum('current_balance');

    // NEW: Get all accounts with their current balances
    $accounts = DB::table('accounts')
        ->select('id', 'name', 'account_type', 'provider', 'current_balance')
        ->orderBy('account_type')
        ->orderBy('name')
        ->get();

    return response()->json([
        'totalStudents' => $totalStudents,
        'totalCollected' => $totalCollected,
        'totalDue' => $totalDue,
        'overdueCount' => $overdueCount,
        'overdueAmount' => $overdueAmount,
        'recentPayments' => $recentPayments,
        'monthlyCollection' => array_values($monthlyData),
        'expenseCategories' => $expenseCategories,
        // NEW FIELDS
        'totalExpenses' => $totalExpenses,
        'cashAtHand' => $cashAtHand,
        'cashInBank' => $cashInBank,
        'cashOnMobileMoney' => $cashOnMobileMoney,
        'accounts' => $accounts,
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
                        "Dear {$student->guardian_name},\n\n".
                        "This is a reminder that your child {$student->first_name}'s {$allocation->feeGroup->feeType->name} fee of UGX ".
                        number_format($allocation->amount).' was due on '.
                        date('M j, Y', strtotime($allocation->due_date)).".\n\n".
                        "Student: {$student->first_name} {$student->last_name}\n".
                        "Class: {$student->class}\n".
                        "Registration No: {$student->registration_no}\n\n".
                        "Please make the payment as soon as possible to avoid any inconvenience.\n\n".
                        "Thank you,\nMuko High School Administration",
                        function ($message) use ($student) {
                            $message->to($student->guardian_email)
                                   ->subject('Fee Payment Reminder - Muko High School');
                        }
                    );
                    $sentCount++;
                } catch (\Exception $e) {
                    \Log::error('Failed to send reminder email: '.$e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => "Sent {$sentCount} reminder emails successfully",
            'sent_count' => $sentCount,
            'total_overdue' => $overdueAllocations->count(),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $query = FeeAllocation::with(['student.schoolClass', 'feeGroup.feeType', 'feePayments']);

        if ($request->has('class')) {
            $query->whereHas('student.schoolClass', function ($q) use ($request) {
                $q->where('name', $request->get('class'));
            });
        }

        if ($request->has('fee_type_id')) {
            $query->whereHas('feeGroup', function ($q) use ($request) {
                $q->where('fee_type_id', $request->get('fee_type_id'));
            });
        }

        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'overdue') {
                $query->where('due_date', '<', now())->where('status', '!=', 'paid');
            } elseif ($status === 'due') {
                $query->where('status', '!=', 'paid');
            } elseif ($status === 'paid') {
                $query->where('status', 'paid');
            }
        }

        $allocations = $query->get();

        $report = $allocations->map(function ($allocation) {
            $totalPaid = $allocation->feePayments->sum('amount');
            $balance = $allocation->amount - $totalPaid;
            $isOverdue = $allocation->due_date < now() && $balance > 0;

            return [
                'student_name' => $allocation->student->full_name ?? 'N/A',
                'registration_no' => $allocation->student->registration_no ?? 'N/A',
                'class' => $allocation->student->class_name ?? 'N/A',
                'fee_type' => $allocation->feeGroup->feeType->name ?? 'N/A',
                'amount' => $allocation->amount,
                'paid' => $totalPaid,
                'balance' => $balance,
                'due_date' => $allocation->due_date,
                'status' => $isOverdue ? 'overdue' : ($balance > 0 ? 'due' : 'paid'),
                'days_overdue' => $isOverdue ? now()->diffInDays($allocation->due_date) : 0,
            ];
        });

        return response()->json($report);
    }
}
