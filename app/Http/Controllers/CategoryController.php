<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::forUser(Auth::id())->with(['expenses', 'incomes']);

        // Filter by type if provided
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['expense', 'income', 'both'])],
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['color'] = $validated['color'] ?? '#3B82F6';

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category->load(['expenses', 'incomes']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        // Check if category belongs to authenticated user
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this category.');
        }

        return response()->json([
            'category' => $category->load(['expenses', 'incomes']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        // Check if category belongs to authenticated user
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this category.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['expense', 'income', 'both'])],
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->load(['expenses', 'incomes']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category belongs to authenticated user
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this category.');
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
