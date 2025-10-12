<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\VoucherHead;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function accounts()
    {
        $accounts = Account::latest()->get();
        return response()->json($accounts);
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $account = Account::create($request->all());
        return response()->json($account, 201);
    }

    public function voucherHeads()
    {
        $voucherHeads = VoucherHead::latest()->get();
        return response()->json($voucherHeads);
    }

    public function storeVoucherHead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $voucherHead = VoucherHead::create($request->all());
        return response()->json($voucherHead, 201);
    }

    public function transactions()
    {
        $transactions = Transaction::with(['account', 'voucherHead', 'user'])
            ->latest()
            ->get();
        return response()->json($transactions);
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'voucher_head_id' => 'required|exists:voucher_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:debit,credit',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $transaction = Transaction::create([
            'voucher_head_id' => $request->voucher_head_id,
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'type' => $request->type,
            'description' => $request->description,
            'date' => $request->date,
            'user_id' => auth()->id() ?? 1
        ]);

        return response()->json($transaction, 201);
    }
}