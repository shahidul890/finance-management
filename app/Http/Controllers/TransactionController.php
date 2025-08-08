<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['bankAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by type if provided
        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        // Filter by bank account if provided
        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Search in description
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        // Calculate summary statistics
        $totalIn = Transaction::where('type', 'in')->sum('amount');
        $totalOut = Transaction::where('type', 'out')->sum('amount');
        $totalTransactions = Transaction::count();
        $netBalance = $totalIn - $totalOut;

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net_balance' => $netBalance,
            ],
            'bank_accounts' => BankAccount::where('is_active', true)->get(['id', 'bank_name', 'account_name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => ['required', Rule::in(['in', 'out'])],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'related_model' => 'nullable|string|max:255',
            'related_model_id' => 'nullable|integer',
            'transaction_date' => 'required|date',
        ]);

        $transaction = Transaction::create($validated);
        $transaction->load('bankAccount');

        // Update bank account balance
        $bankAccount = BankAccount::find($validated['bank_account_id']);
        if ($validated['type'] === 'in') {
            $bankAccount->current_balance += $validated['amount'];
            $bankAccount->available_balance += $validated['amount'];
        } else {
            $bankAccount->current_balance -= $validated['amount'];
            $bankAccount->available_balance -= $validated['amount'];
        }
        $bankAccount->save();

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load('bankAccount');
        
        return response()->json([
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => ['required', Rule::in(['in', 'out'])],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'related_model' => 'nullable|string|max:255',
            'related_model_id' => 'nullable|integer',
            'transaction_date' => 'required|date',
        ]);

        // Revert old transaction from bank account balance
        $oldBankAccount = BankAccount::find($transaction->bank_account_id);
        if ($transaction->type === 'in') {
            $oldBankAccount->current_balance -= $transaction->amount;
            $oldBankAccount->available_balance -= $transaction->amount;
        } else {
            $oldBankAccount->current_balance += $transaction->amount;
            $oldBankAccount->available_balance += $transaction->amount;
        }
        $oldBankAccount->save();

        // Update transaction
        $transaction->update($validated);
        $transaction->load('bankAccount');

        // Apply new transaction to bank account balance
        $newBankAccount = BankAccount::find($validated['bank_account_id']);
        if ($validated['type'] === 'in') {
            $newBankAccount->current_balance += $validated['amount'];
            $newBankAccount->available_balance += $validated['amount'];
        } else {
            $newBankAccount->current_balance -= $validated['amount'];
            $newBankAccount->available_balance -= $validated['amount'];
        }
        $newBankAccount->save();

        return response()->json([
            'message' => 'Transaction updated successfully',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        // Revert transaction from bank account balance
        $bankAccount = BankAccount::find($transaction->bank_account_id);
        if ($transaction->type === 'in') {
            $bankAccount->current_balance -= $transaction->amount;
            $bankAccount->available_balance -= $transaction->amount;
        } else {
            $bankAccount->current_balance += $transaction->amount;
            $bankAccount->available_balance += $transaction->amount;
        }
        $bankAccount->save();

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully',
        ]);
    }
}
