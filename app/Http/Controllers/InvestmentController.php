<?php

namespace App\Http\Controllers;

use App\Models\Dps;
use App\Models\Fdr;
use App\Models\Loan;
use App\Models\Investment;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvestmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // If it's an API request, return JSON
        if ($request->expectsJson()) {
            return $this->apiIndex($request);
        }

        // For web requests, return Inertia view
        return Inertia::render('Investments/Index');
    }

    /**
     * API method to get investments list
     */
    private function apiIndex(Request $request): JsonResponse
    {
        $query = Investment::forUser(Auth::id());

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('dps', function($dpsQuery) use ($search) {
                    $dpsQuery->where('dps_name', 'like', "%{$search}%");
                })->orWhereHas('fdr', function($fdrQuery) use ($search) {
                    $fdrQuery->where('fdr_name', 'like', "%{$search}%");
                })->orWhereHas('loan', function($loanQuery) use ($search) {
                    $loanQuery->where('loan_name', 'like', "%{$search}%");
                });
            });
        }

        $investments = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

        // Transform the data to include computed fields
        $investments->through(function ($investment) {
            return [
                'id' => $investment->id,
                'type' => $investment->type,
                'title' => $investment->title,
                'amount' => $investment->amount,
                'created_at' => $investment->created_at,
                'updated_at' => $investment->updated_at,
                'details' => $investment->investable,
                'bank_account' => $investment->investable?->bankAccount,
            ];
        });

        return response()->json($investments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:dps,fdr,loan',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            
            // DPS specific fields
            'dps_name' => 'required_if:type,dps|string|max:255',
            'dps_number' => 'nullable|string|max:100',
            'monthly_installment' => 'required_if:type,dps|numeric|min:0',
            'tenure_months' => 'required_if:type,dps|integer|min:1',
            'interest_rate' => 'required_if:type,dps|numeric|min:0|max:100',
            'start_date' => 'required_if:type,dps|date',
            'maturity_date' => 'nullable|date|after:start_date',
            
            // FDR specific fields
            'fdr_name' => 'required_if:type,fdr|string|max:255',
            'fdr_number' => 'nullable|string|max:100',
            'principal_amount' => 'required_if:type,fdr|numeric|min:0',
            'fdr_tenure_months' => 'required_if:type,fdr|integer|min:1',
            'fdr_interest_rate' => 'required_if:type,fdr|numeric|min:0|max:100',
            'fdr_start_date' => 'required_if:type,fdr|date',
            'fdr_maturity_date' => 'nullable|date|after:fdr_start_date',
            
            // Loan specific fields
            'loan_name' => 'required_if:type,loan|string|max:255',
            'loan_number' => 'nullable|string|max:100',
            'loan_type' => 'nullable|string|max:100',
            'principal_amount' => 'required_if:type,loan|numeric|min:0',
            'loan_tenure_months' => 'required_if:type,loan|integer|min:1',
            'loan_interest_rate' => 'required_if:type,loan|numeric|min:0|max:100',
            'loan_start_date' => 'required_if:type,loan|date',
            'monthly_emi' => 'required_if:type,loan|numeric|min:0',
            
            // Common fields
            'status' => 'nullable|in:active,completed,cancelled',
            'additional_info' => 'nullable|array',
        ]);

        // Verify bank account belongs to user if provided
        if (isset($validated['bank_account_id'])) {
            $bankAccountBelongsToUser = BankAccount::where('id', $validated['bank_account_id'])
                                                  ->where('user_id', Auth::id())
                                                  ->exists();
            if (!$bankAccountBelongsToUser) {
                return response()->json(['error' => 'Invalid bank account'], 422);
            }
        }

        // Create the specific investment schema
        $schemaModel = $this->createInvestmentSchema($validated);

        // Create the investment record
        $investment = $schemaModel->investments()->create([
            'user_id' => Auth::id(),
            'type' => $validated['type']
        ]);

        return response()->json([
            'message' => 'Investment created successfully',
            'investment' => $investment->load(['dps', 'fdr', 'loan']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Investment $investment): JsonResponse
    {
        // Check if investment belongs to authenticated user
        if ($investment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this investment.');
        }

        return response()->json([
            'investment' => [
                'id' => $investment->id,
                'type' => $investment->type,
                'title' => $investment->title,
                'amount' => $investment->amount,
                'status' => $investment->status,
                'created_at' => $investment->created_at,
                'updated_at' => $investment->updated_at,
                'details' => $investment->investable,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investment $investment): JsonResponse
    {
        // Check if investment belongs to authenticated user
        if ($investment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this investment.');
        }

        $validated = $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'status' => 'nullable|in:active,completed,cancelled',
            'additional_info' => 'nullable|array',
            
            // Type-specific validations based on current schema_type
            'dps_name' => 'required_if:type,dps|string|max:255',
            'dps_number' => 'required_if:type,dps|string|max:255',
            'monthly_installment' => 'required_if:type,dps|numeric|min:0',

            'fdr_name' => 'required_if:type,fdr|string|max:255',
            'principal_amount' => 'required_if:type,fdr|numeric|min:0',

            'loan_name' => 'required_if:type,loan|string|max:255',
            'principal_amount' => 'required_if:type,loan|numeric|min:0',

            'monthly_installment' => 'filled|numeric|min:0',
            'tenure_months' => 'filled|numeric|min:1',
            'interest_rate' => 'filled|numeric|min:1',
            'start_date' => 'filled|date',
            'maturity_date' => 'filled|date',
            'status' => 'filled'
        ]);

        // Add the current type to validated data for rule checking
        $validated['type'] = $investment->type;

        // Verify bank account belongs to user if provided
        if (isset($validated['bank_account_id'])) {
            $bankAccountBelongsToUser = BankAccount::where('id', $validated['bank_account_id'])
                                                  ->where('user_id', Auth::id())
                                                  ->exists();
            if (!$bankAccountBelongsToUser) {
                return response()->json(['error' => 'Invalid bank account'], 422);
            }
        }

        // Update the specific investment schema
        $this->updateInvestmentSchema($investment, $validated);

        return response()->json([
            'message' => 'Investment updated successfully',
            'investment' => $investment->fresh()->load(['dps', 'fdr', 'loan']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investment $investment): JsonResponse
    {
        // Check if investment belongs to authenticated user
        if ($investment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this investment.');
        }

        // Delete the related schema model first
        $this->deleteInvestmentSchema($investment);

        // Delete the investment record
        $investment->delete();

        return response()->json([
            'message' => 'Investment deleted successfully',
        ]);
    }

    /**
     * Get investment statistics for the user.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        $investments = Investment::forUser($userId)->with(['dps', 'fdr', 'loan'])->get();

        $stats = [
            'total_count' => $investments->count(),
            'by_type' => [
                'dps' => $investments->where('schema_type', 'dps')->count(),
                'fdr' => $investments->where('schema_type', 'fdr')->count(),
                'loan' => $investments->where('schema_type', 'loan')->count(),
            ],
            'total_amounts' => [
                'dps' => $investments->where('schema_type', 'dps')->sum('amount'),
                'fdr' => $investments->where('schema_type', 'fdr')->sum('amount'),
                'loan' => $investments->where('schema_type', 'loan')->sum('amount'),
            ],
            'by_status' => $investments->groupBy('status')->map->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Create investment schema based on type
     */
    private function createInvestmentSchema(array $validated)
    {
        return match($validated['type']) {
            'dps' => $this->createDpsSchema($validated),
            'fdr' => $this->createFdrSchema($validated),
            'loan' => $this->createLoanSchema($validated),
            default => throw new \InvalidArgumentException('Invalid investment type'),
        };
    }

    /**
     * Create DPS schema
     */
    private function createDpsSchema(array $validated)
    {
        $maturityDate = $validated['maturity_date'] ?? 
                       now()->parse($validated['start_date'])->addMonths($validated['tenure_months']);
        
        $totalAmount = $validated['monthly_installment'] * $validated['tenure_months'];
        $maturityAmount = $totalAmount * (1 + ($validated['interest_rate'] / 100) * ($validated['tenure_months'] / 12));

        return Dps::create([
            'user_id' => Auth::id(),
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'dps_name' => $validated['dps_name'],
            'dps_number' => $validated['dps_number'] ?? null,
            'monthly_installment' => $validated['monthly_installment'],
            'tenure_months' => $validated['tenure_months'],
            'interest_rate' => $validated['interest_rate'],
            'start_date' => $validated['start_date'],
            'maturity_date' => $maturityDate,
            'total_deposited' => 0,
            'maturity_amount' => $maturityAmount,
            'paid_installments' => 0,
            'remaining_installments' => $validated['tenure_months'],
            'status' => $validated['status'] ?? 'active',
            'next_payment_date' => now()->parse($validated['start_date'])->addMonth(),
            'additional_info' => $validated['additional_info'] ?? null,
        ]);
    }

    /**
     * Create FDR schema
     */
    private function createFdrSchema(array $validated)
    {
        $maturityDate = $validated['fdr_maturity_date'] ?? 
                       now()->parse($validated['fdr_start_date'])->addMonths($validated['fdr_tenure_months']);
        
        $maturityAmount = $validated['principal_amount'] * 
                         (1 + ($validated['fdr_interest_rate'] / 100) * ($validated['fdr_tenure_months'] / 12));

        return Fdr::create([
            'user_id' => Auth::id(),
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'fdr_name' => $validated['fdr_name'],
            'fdr_number' => $validated['fdr_number'] ?? null,
            'principal_amount' => $validated['principal_amount'],
            'interest_rate' => $validated['fdr_interest_rate'],
            'tenure_months' => $validated['fdr_tenure_months'],
            'start_date' => $validated['fdr_start_date'],
            'maturity_date' => $maturityDate,
            'maturity_amount' => $maturityAmount,
            'interest_earned' => 0,
            'status' => $validated['status'] ?? 'active',
            'additional_info' => $validated['additional_info'] ?? null,
        ]);
    }

    /**
     * Create Loan schema
     */
    private function createLoanSchema(array $validated)
    {
        $totalEmis = $validated['loan_tenure_months'];
        $totalAmountPayable = $validated['monthly_emi'] * $totalEmis;
        $endDate = now()->parse($validated['loan_start_date'])->addMonths($validated['loan_tenure_months']);

        return Loan::create([
            'user_id' => Auth::id(),
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'loan_name' => $validated['loan_name'],
            'loan_number' => $validated['loan_number'] ?? null,
            'loan_type' => $validated['loan_type'] ?? 'personal',
            'principal_amount' => $validated['principal_amount'],
            'interest_rate' => $validated['loan_interest_rate'],
            'tenure_months' => $validated['loan_tenure_months'],
            'monthly_emi' => $validated['monthly_emi'],
            'start_date' => $validated['loan_start_date'],
            'end_date' => $endDate,
            'total_amount_payable' => $totalAmountPayable,
            'amount_paid' => 0,
            'outstanding_balance' => $validated['principal_amount'],
            'paid_emis' => 0,
            'remaining_emis' => $totalEmis,
            'status' => $validated['status'] ?? 'active',
            'next_payment_date' => now()->parse($validated['loan_start_date'])->addMonth(),
            'additional_info' => $validated['additional_info'] ?? null,
        ]);
    }

    /**
     * Update investment schema
     */
    private function updateInvestmentSchema(Investment $investment, array $validated): void
    {
        $schemaModel = match($investment->type) {
            'dps' => Dps::find($investment->schema_id),
            'fdr' => Fdr::find($investment->schema_id),
            'loan' => Loan::find($investment->schema_id),
            default => null,
        };

        if (!$schemaModel || $schemaModel->user_id !== Auth::id()) {
            throw new \Exception('Investment schema not found or unauthorized');
        }

        // Update based on type
        switch ($investment->type) {
            case 'dps':
                $schemaModel->update([
                    'dps_name' => $validated['dps_name'] ?? $schemaModel->dps_name,
                    'dps_number' => $validated['dps_number'] ?? $schemaModel->dps_number,
                    'monthly_installment' => $validated['monthly_installment'] ?? $schemaModel->monthly_installment,
                    'bank_account_id' => $validated['bank_account_id'] ?? $schemaModel->bank_account_id,
                    'status' => $validated['status'] ?? $schemaModel->status,
                    'additional_info' => $validated['additional_info'] ?? $schemaModel->additional_info,
                ]);
                break;
            case 'fdr':
                $schemaModel->update([
                    'fdr_name' => $validated['fdr_name'] ?? $schemaModel->fdr_name,
                    'principal_amount' => $validated['principal_amount'] ?? $schemaModel->principal_amount,
                    'bank_account_id' => $validated['bank_account_id'] ?? $schemaModel->bank_account_id,
                    'status' => $validated['status'] ?? $schemaModel->status,
                    'additional_info' => $validated['additional_info'] ?? $schemaModel->additional_info,
                ]);
                break;
            case 'loan':
                $schemaModel->update([
                    'loan_name' => $validated['loan_name'] ?? $schemaModel->loan_name,
                    'principal_amount' => $validated['principal_amount'] ?? $schemaModel->principal_amount,
                    'bank_account_id' => $validated['bank_account_id'] ?? $schemaModel->bank_account_id,
                    'status' => $validated['status'] ?? $schemaModel->status,
                    'additional_info' => $validated['additional_info'] ?? $schemaModel->additional_info,
                ]);
                break;
        }
    }

    /**
     * Delete investment schema
     */
    private function deleteInvestmentSchema(Investment $investment): void
    {
        $schemaModel = match($investment->type) {
            'dps' => Dps::find($investment->schema_id),
            'fdr' => Fdr::find($investment->schema_id),
            'loan' => Loan::find($investment->schema_id),
            default => null,
        };

        if ($schemaModel && $schemaModel->user_id === Auth::id()) {
            $schemaModel->delete();
        }
    }
}
