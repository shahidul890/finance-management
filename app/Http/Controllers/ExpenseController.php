<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Dps;
use App\Models\Fdr;
use App\Models\Loan;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expense::forUser(Auth::id())->with(['category', 'bankAccount', 'dps', 'fdr', 'loan']);

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by expense type
        if ($request->has('expense_type')) {
            $query->byType($request->expense_type);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenses = $query->orderBy('expense_date', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'payment_method' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'expense_type' => ['nullable', Rule::in(['regular', 'dps_payment', 'fdr_investment', 'loan_payment'])],
            'related_id' => 'nullable|integer',
            'related_type' => ['nullable', Rule::in(['dps', 'fdr', 'loan'])],
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['expense_type'] = $validated['expense_type'] ?? 'regular';

        // Verify category belongs to user if provided
        if (isset($validated['category_id'])) {
            $categoryBelongsToUser = \App\Models\Category::where('id', $validated['category_id'])
                                                        ->where('user_id', Auth::id())
                                                        ->exists();
            if (!$categoryBelongsToUser) {
                return response()->json(['error' => 'Invalid category'], 422);
            }
        }

        // Verify bank account belongs to user if provided
        if (isset($validated['bank_account_id'])) {
            $bankAccountBelongsToUser = BankAccount::where('id', $validated['bank_account_id'])
                                                  ->where('user_id', Auth::id())
                                                  ->exists();
            if (!$bankAccountBelongsToUser) {
                return response()->json(['error' => 'Invalid bank account'], 422);
            }
        }

        // Validate related investment if provided
        if (isset($validated['related_id']) && isset($validated['related_type'])) {
            $relatedModel = match($validated['related_type']) {
                'dps' => Dps::class,
                'fdr' => Fdr::class,
                'loan' => Loan::class,
                default => null,
            };

            if ($relatedModel) {
                $relatedExists = $relatedModel::where('id', $validated['related_id'])
                                              ->where('user_id', Auth::id())
                                              ->exists();
                if (!$relatedExists) {
                    return response()->json(['error' => 'Invalid related investment'], 422);
                }
            }
        }

        $expense = Expense::create($validated);

        // Update related investment if applicable
        if ($expense->expense_type !== 'regular' && $expense->related_id) {
            $this->updateRelatedInvestment($expense);
        }

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense->load(['category', 'bankAccount', 'dps', 'fdr', 'loan']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): JsonResponse
    {
        // Check if expense belongs to authenticated user
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this expense.');
        }

        return response()->json([
            'expense' => $expense->load('category'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        // Check if expense belongs to authenticated user
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this expense.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'payment_method' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        // Verify category belongs to user if provided
        if (isset($validated['category_id'])) {
            $categoryBelongsToUser = \App\Models\Category::where('id', $validated['category_id'])
                                                        ->where('user_id', Auth::id())
                                                        ->exists();
            if (!$categoryBelongsToUser) {
                return response()->json(['error' => 'Invalid category'], 422);
            }
        }

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense->load('category'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        // Check if expense belongs to authenticated user
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this expense.');
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }

    /**
     * Get expense statistics for the user.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        $query = Expense::forUser($userId);

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        } else {
            // Default to current month
            $query->whereMonth('expense_date', now()->month)
                  ->whereYear('expense_date', now()->year);
        }

        $stats = [
            'total_amount' => $query->sum('amount'),
            'total_count' => $query->count(),
            'average_amount' => $query->avg('amount'),
            'by_category' => $query->join('categories', 'expenses.category_id', '=', 'categories.id')
                                   ->selectRaw('categories.name, SUM(amount) as total, COUNT(*) as count')
                                   ->groupBy('categories.id', 'categories.name')
                                   ->orderBy('total', 'desc')
                                   ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Update related investment (DPS, FDR, Loan) when expense is recorded.
     */
    private function updateRelatedInvestment(Expense $expense): void
    {
        if (!$expense->related_id || !$expense->related_type) {
            return;
        }

        switch ($expense->expense_type) {
            case 'dps_payment':
                $this->updateDpsPayment($expense);
                break;
            case 'fdr_investment':
                $this->updateFdrInvestment($expense);
                break;
            case 'loan_payment':
                $this->updateLoanPayment($expense);
                break;
        }
    }

    /**
     * Update DPS when payment is made.
     */
    private function updateDpsPayment(Expense $expense): void
    {
        $dps = Dps::find($expense->related_id);
        if ($dps && $dps->user_id === $expense->user_id) {
            $dps->increment('total_deposited', $expense->amount);
            $dps->increment('paid_installments');
            $dps->decrement('remaining_installments');
            $dps->update([
                'last_payment_date' => $expense->expense_date,
                'next_payment_date' => $expense->expense_date->addMonth(),
            ]);
        }
    }

    /**
     * Update FDR when investment is made.
     */
    private function updateFdrInvestment(Expense $expense): void
    {
        $fdr = Fdr::find($expense->related_id);
        if ($fdr && $fdr->user_id === $expense->user_id) {
            $fdr->increment('principal_amount', $expense->amount);
            // Recalculate maturity amount if needed
            $maturityAmount = $fdr->principal_amount * 
                            (1 + ($fdr->interest_rate / 100) * ($fdr->tenure_months / 12));
            $fdr->update(['maturity_amount' => $maturityAmount]);
        }
    }

    /**
     * Update Loan when payment is made.
     */
    private function updateLoanPayment(Expense $expense): void
    {
        $loan = Loan::find($expense->related_id);
        if ($loan && $loan->user_id === $expense->user_id) {
            $loan->increment('amount_paid', $expense->amount);
            $loan->decrement('outstanding_balance', $expense->amount);
            $loan->increment('paid_emis');
            $loan->decrement('remaining_emis');
            $loan->update([
                'last_payment_date' => $expense->expense_date,
                'next_payment_date' => $expense->expense_date->addMonth(),
            ]);
        }
    }
}
