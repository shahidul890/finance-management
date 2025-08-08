<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Income::forUser(Auth::id())->with(['category', 'client']);

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by recurring status
        if ($request->has('is_recurring')) {
            if ($request->is_recurring) {
                $query->recurring();
            } else {
                $query->where('is_recurring', false);
            }
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $incomes = $query->orderBy('income_date', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($incomes);
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
            'income_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'source' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_frequency' => [
                'nullable',
                Rule::requiredIf($request->is_recurring),
                Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $validated['user_id'] = Auth::id();

        // Verify category belongs to user if provided
        if (isset($validated['category_id'])) {
            $categoryBelongsToUser = \App\Models\Category::where('id', $validated['category_id'])
                                                        ->where('user_id', Auth::id())
                                                        ->exists();
            if (!$categoryBelongsToUser) {
                return response()->json(['error' => 'Invalid category'], 422);
            }
        }

        // Verify client belongs to user if provided
        if (isset($validated['client_id'])) {
            $clientBelongsToUser = \App\Models\Client::where('id', $validated['client_id'])
                                                   ->where('user_id', Auth::id())
                                                   ->exists();
            if (!$clientBelongsToUser) {
                return response()->json(['error' => 'Invalid client'], 422);
            }
        }

        $income = Income::create($validated);

        return response()->json([
            'message' => 'Income created successfully',
            'income' => $income->load(['category', 'client']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Income $income): JsonResponse
    {
        // Check if income belongs to authenticated user
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this income.');
        }

        return response()->json([
            'income' => $income->load('category'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Income $income): JsonResponse
    {
        // Check if income belongs to authenticated user
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this income.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'source' => 'nullable|string|max:100',
            'is_recurring' => 'boolean',
            'recurring_frequency' => [
                'nullable',
                Rule::requiredIf($request->is_recurring),
                Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])
            ],
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

        // Verify client belongs to user if provided
        if (isset($validated['client_id'])) {
            $clientBelongsToUser = \App\Models\Client::where('id', $validated['client_id'])
                                                   ->where('user_id', Auth::id())
                                                   ->exists();
            if (!$clientBelongsToUser) {
                return response()->json(['error' => 'Invalid client'], 422);
            }
        }

        $income->update($validated);

        return response()->json([
            'message' => 'Income updated successfully',
            'income' => $income->load(['category', 'client']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Income $income): JsonResponse
    {
        // Check if income belongs to authenticated user
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this income.');
        }

        $income->delete();

        return response()->json([
            'message' => 'Income deleted successfully',
        ]);
    }

    /**
     * Get income statistics for the user.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        $query = Income::forUser($userId);

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        } else {
            // Default to current month
            $query->whereMonth('income_date', now()->month)
                  ->whereYear('income_date', now()->year);
        }

        $stats = [
            'total_amount' => $query->sum('amount'),
            'total_count' => $query->count(),
            'average_amount' => $query->avg('amount'),
            'recurring_count' => $query->where('is_recurring', true)->count(),
            'by_category' => $query->join('categories', 'incomes.category_id', '=', 'categories.id')
                                   ->selectRaw('categories.name, SUM(amount) as total, COUNT(*) as count')
                                   ->groupBy('categories.id', 'categories.name')
                                   ->orderBy('total', 'desc')
                                   ->get(),
            'by_source' => $query->selectRaw('source, SUM(amount) as total, COUNT(*) as count')
                                 ->whereNotNull('source')
                                 ->groupBy('source')
                                 ->orderBy('total', 'desc')
                                 ->get(),
        ];

        return response()->json($stats);
    }
}
