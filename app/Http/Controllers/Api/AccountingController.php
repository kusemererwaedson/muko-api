<?php
// app/Http/Controllers/Api/AccountingController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\VoucherHead;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class AccountingController extends Controller
{
    public function accounts()
    {
        try {
            $accounts = Account::latest()->get();
            return response()->json($accounts);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch accounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeAccount(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'description' => 'nullable|string',
                'account_type' => 'required|in:cash,bank,mobile_money',
                'provider' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'current_balance' => 'required|numeric|min:0',
            ], [
                'name.required' => 'Account name is required',
                'name.max' => 'Account name cannot exceed 255 characters',
                'type.required' => 'Account type is required',
                'account_type.required' => 'Please select an account type',
                'account_type.in' => 'Invalid account type. Must be cash, bank, or mobile_money',
                'current_balance.required' => 'Opening balance is required',
                'current_balance.numeric' => 'Opening balance must be a valid number',
                'current_balance.min' => 'Opening balance cannot be negative',
                'provider.max' => 'Provider name cannot exceed 255 characters',
                'account_number.max' => 'Account number cannot exceed 255 characters',
            ]);

            $account = Account::create($validated);

            return response()->json([
                'message' => 'Account created successfully',
                'data' => $account
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function voucherHeads()
    {
        try {
            $voucherHeads = VoucherHead::latest()->get();
            return response()->json($voucherHeads);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch voucher heads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeVoucherHead(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ], [
                'name.required' => 'Voucher head name is required',
                'name.max' => 'Voucher head name cannot exceed 255 characters',
            ]);

            $voucherHead = VoucherHead::create($validated);

            return response()->json([
                'message' => 'Voucher head created successfully',
                'data' => $voucherHead
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create voucher head',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transactions()
    {
        try {
            $transactions = Transaction::with(['account', 'voucherHead', 'user'])
                ->latest()
                ->get();
            return response()->json($transactions);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeTransaction(Request $request)
    {
        try {
            $validated = $request->validate([
                'voucher_head_id' => 'required|exists:voucher_heads,id',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0',
                'type' => 'required|in:debit,credit',
                'description' => 'nullable|string',
                'date' => 'required|date',
            ], [
                'voucher_head_id.required' => 'Voucher head is required',
                'voucher_head_id.exists' => 'Selected voucher head does not exist',
                'account_id.required' => 'Account is required',
                'account_id.exists' => 'Selected account does not exist',
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a valid number',
                'amount.min' => 'Amount cannot be negative',
                'type.required' => 'Transaction type is required',
                'type.in' => 'Transaction type must be either debit or credit',
                'date.required' => 'Transaction date is required',
                'date.date' => 'Invalid date format',
            ]);

            // Get the account to check balance
            $account = Account::findOrFail($validated['account_id']);

            // Check for insufficient balance if transaction is a debit
            if ($validated['type'] === 'debit') {
                if (!$account->hasSufficientBalance($validated['amount'])) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors' => [
                            'amount' => [
                                sprintf(
                                    'Insufficient balance. Current balance is %s, but you are trying to debit %s',
                                    number_format($account->current_balance, 2),
                                    number_format($validated['amount'], 2)
                                )
                            ]
                        ]
                    ], 422);
                }
            }

            // Create the transaction
            $transaction = Transaction::create([
                'voucher_head_id' => $validated['voucher_head_id'],
                'account_id' => $validated['account_id'],
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'date' => $validated['date'],
                'user_id' => auth()->id() ?? 1,
            ]);

            // Update account balance
            $account->updateBalance($validated['amount'], $validated['type']);

            // Reload transaction with relationships
            $transaction->load(['account', 'voucherHead', 'user']);

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Account not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}