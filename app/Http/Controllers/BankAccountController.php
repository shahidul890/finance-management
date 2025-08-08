<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BankAccount::forUser(Auth::id())->with(['dps', 'fdrs', 'loans']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('account_name', 'like', '%' . $request->search . '%')
                  ->orWhere('bank_name', 'like', '%' . $request->search . '%')
                  ->orWhere('account_number', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by account type
        if ($request->has('account_type') && $request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        // Filter by active status
        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
        }

        $bankAccounts = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

        // Calculate total balance from the current page items
        $totalBalance = $bankAccounts->getCollection()->sum('current_balance');

        // Add total balance to the response
        $response = $bankAccounts->toArray();
        $response['total_balance'] = $totalBalance;

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:bank_accounts',
            'account_type' => 'required|string|max:50',
            'initial_amount' => 'required|numeric|min:0',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'branch' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['available_balance'] = $validated['available_balance'] ?? $validated['current_balance'];

        $bankAccount = BankAccount::create($validated);

        return response()->json([
            'message' => 'Bank account created successfully',
            'bank_account' => $bankAccount->load(['dps', 'fdrs', 'loans']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BankAccount $bankAccount): JsonResponse
    {
        if ($bankAccount->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this bank account.');
        }

        return response()->json([
            'bank_account' => $bankAccount->load(['dps', 'fdrs', 'loans']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankAccount $bankAccount): JsonResponse
    {
        if ($bankAccount->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this bank account.');
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number,' . $bankAccount->id,
            'account_type' => 'required|string|max:50',
            'initial_amount' => 'sometimes|numeric|min:0',
            'current_balance' => 'required|numeric|min:0',
            'available_balance' => 'nullable|numeric|min:0',
            'branch' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array',
        ]);

        $bankAccount->update($validated);

        return response()->json([
            'message' => 'Bank account updated successfully',
            'bank_account' => $bankAccount->load(['dps', 'fdrs', 'loans']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        if ($bankAccount->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this bank account.');
        }

        $bankAccount->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully',
        ]);
    }
}
