<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BudgetController extends Controller
{
    /**
     * Display a listing of budgets.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $status = $request->get('status', 'active');
        
        $query = Budget::forUser($userId)
            ->with('category')
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc');

        // Paginate first, before mapping
        $budgets = $query->paginate($request->get('per_page', 15));

        // Transform the paginated items
        $budgets->getCollection()->transform(function ($budget) {
            // Update spent amount before returning
            $budget->updateSpentAmount();
            
            return [
                'id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'category' => $budget->category ? [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                    'color' => $budget->category->color,
                ] : null,
                'budget_amount' => $budget->budget_amount,
                'spent_amount' => $budget->spent_amount,
                'remaining_amount' => $budget->remaining_amount,
                'spent_percentage' => $budget->spent_percentage,
                'period_type' => $budget->period_type,
                'start_date' => $budget->start_date->format('Y-m-d'),
                'end_date' => $budget->end_date->format('Y-m-d'),
                'alert_percentage' => $budget->alert_percentage,
                'status' => $budget->status,
                'is_over_budget' => $budget->is_over_budget,
                'is_alert_triggered' => $budget->is_alert_triggered,
                'description' => $budget->description,
                'created_at' => $budget->created_at,
                'updated_at' => $budget->updated_at,
            ];
        });

        // Get summary statistics from all budgets (not just current page)
        $allBudgets = Budget::forUser($userId)
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();

        $summary = [
            'total_budgets' => $allBudgets->count(),
            'total_budget_amount' => $allBudgets->sum('budget_amount'),
            'active_budgets' => $allBudgets->where('status', 'active')->count(),
            'over_budget_count' => $allBudgets->where('is_over_budget', true)->count(),
            'alert_triggered_count' => $allBudgets->where('is_alert_triggered', true)->count(),
        ];

        // Return standard Laravel pagination format
        $response = $budgets->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'budget_name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'budget_amount' => 'required|numeric|min:0',
            'period_type' => ['required', Rule::in(['monthly', 'yearly', 'custom'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'alert_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
        ]);

        // Ensure category belongs to the user if provided
        if ($validated['category_id']) {
            $category = Category::where('id', $validated['category_id'])
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
        }

        $budget = Budget::create([
            'user_id' => Auth::id(),
            ...$validated,
            'alert_percentage' => $validated['alert_percentage'] ?? 80,
        ]);

        // Update spent amount
        $budget->updateSpentAmount();

        return response()->json([
            'message' => 'Budget created successfully',
            'budget' => $budget->load('category')
        ], 201);
    }

    /**
     * Display the specified budget.
     */
    public function show(Budget $budget): JsonResponse
    {
        // Ensure budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $budget->load('category');
        $budget->updateSpentAmount();

        return response()->json([
            'budget' => [
                'id' => $budget->id,
                'budget_name' => $budget->budget_name,
                'category' => $budget->category ? [
                    'id' => $budget->category->id,
                    'name' => $budget->category->name,
                    'color' => $budget->category->color,
                ] : null,
                'budget_amount' => $budget->budget_amount,
                'spent_amount' => $budget->spent_amount,
                'remaining_amount' => $budget->remaining_amount,
                'spent_percentage' => $budget->spent_percentage,
                'period_type' => $budget->period_type,
                'start_date' => $budget->start_date->format('Y-m-d'),
                'end_date' => $budget->end_date->format('Y-m-d'),
                'alert_percentage' => $budget->alert_percentage,
                'status' => $budget->status,
                'is_over_budget' => $budget->is_over_budget,
                'is_alert_triggered' => $budget->is_alert_triggered,
                'description' => $budget->description,
                'created_at' => $budget->created_at,
                'updated_at' => $budget->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        // Ensure budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $validated = $request->validate([
            'budget_name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'budget_amount' => 'sometimes|numeric|min:0',
            'period_type' => ['sometimes', Rule::in(['monthly', 'yearly', 'custom'])],
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'alert_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => ['sometimes', Rule::in(['active', 'paused', 'completed'])],
            'description' => 'nullable|string',
        ]);

        // Ensure category belongs to the user if provided
        if (isset($validated['category_id']) && $validated['category_id']) {
            $category = Category::where('id', $validated['category_id'])
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
        }

        $budget->update($validated);
        $budget->updateSpentAmount();

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget->load('category')
        ]);
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Budget $budget): JsonResponse
    {
        // Ensure budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $budget->delete();

        return response()->json([
            'message' => 'Budget deleted successfully'
        ]);
    }

    /**
     * Get budget analytics and insights.
     */
    public function analytics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $period = $request->get('period', 'current'); // current, last_month, last_3_months
        
        $startDate = match($period) {
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'last_3_months' => Carbon::now()->subMonths(3)->startOfMonth(),
            default => Carbon::now()->startOfMonth(),
        };
        
        $endDate = match($period) {
            'last_month' => Carbon::now()->subMonth()->endOfMonth(),
            'last_3_months' => Carbon::now()->endOfMonth(),
            default => Carbon::now()->endOfMonth(),
        };

        $budgets = Budget::forUser($userId)
            ->with('category')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        $analytics = [
            'total_budgets' => $budgets->count(),
            'total_budget_amount' => $budgets->sum('budget_amount'),
            'total_spent' => $budgets->sum('spent_amount'),
            'average_spent_percentage' => $budgets->avg('spent_percentage') ?? 0,
            'over_budget_count' => $budgets->where('is_over_budget', true)->count(),
            'under_budget_count' => $budgets->where('is_over_budget', false)->count(),
            'categories_performance' => $budgets->groupBy('category_id')->map(function ($categoryBudgets) {
                $category = $categoryBudgets->first()->category;
                return [
                    'category' => $category ? $category->name : 'No Category',
                    'budgets_count' => $categoryBudgets->count(),
                    'total_budget' => $categoryBudgets->sum('budget_amount'),
                    'total_spent' => $categoryBudgets->sum('spent_amount'),
                    'average_performance' => $categoryBudgets->avg('spent_percentage'),
                ];
            })->values(),
        ];

        return response()->json($analytics);
    }
}
