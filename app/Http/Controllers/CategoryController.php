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
        $query = Category::forUser(Auth::id())
            ->with(['parent', 'children', 'expenses', 'incomes']);

        // Filter by type if provided
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by parent/child status
        if ($request->has('parents_only') && $request->parents_only) {
            $query->whereNull('parent_id');
        }

        // Filter by active status
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        // If requesting hierarchical structure
        if ($request->has('hierarchical') && $request->hierarchical) {
            $parentCategories = $categories->whereNull('parent_id');
            $categories = $this->buildHierarchy($parentCategories, $categories);
        }

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Get parent categories only.
     */
    public function parents(Request $request): JsonResponse
    {
        $query = Category::forUser(Auth::id())
            ->parents()
            ->active()
            ->with(['children']);

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

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
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Validate parent category belongs to same user
        if ($validated['parent_id'] ?? null) {
            $parentCategory = Category::where('id', $validated['parent_id'])
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$parentCategory) {
                return response()->json([
                    'message' => 'Invalid parent category',
                    'errors' => ['parent_id' => ['The selected parent category is invalid']]
                ], 422);
            }
        }

        $validated['user_id'] = Auth::id();
        $validated['color'] = $validated['color'] ?? '#3B82F6';
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category->load(['parent', 'children', 'expenses', 'incomes']),
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
            'category' => $category->load(['parent', 'children', 'expenses', 'incomes']),
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
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Validate parent category belongs to same user and prevent circular reference
        if ($validated['parent_id'] ?? null) {
            $parentCategory = Category::where('id', $validated['parent_id'])
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$parentCategory) {
                return response()->json([
                    'message' => 'Invalid parent category',
                    'errors' => ['parent_id' => ['The selected parent category is invalid']]
                ], 422);
            }

            // Prevent setting itself as parent or circular reference
            if ($validated['parent_id'] == $category->id) {
                return response()->json([
                    'message' => 'Cannot set category as its own parent',
                    'errors' => ['parent_id' => ['Cannot set category as its own parent']]
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->load(['parent', 'children', 'expenses', 'incomes']),
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

        // Check if category has any expenses or incomes
        if ($category->expenses()->count() > 0 || $category->incomes()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with associated expenses or incomes',
            ], 422);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Please delete or move subcategories first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Build hierarchical structure from categories.
     */
    private function buildHierarchy($parentCategories, $allCategories)
    {
        return $parentCategories->map(function ($parent) use ($allCategories) {
            $children = $allCategories->where('parent_id', $parent->id);
            $parent->children = $children->values();
            return $parent;
        })->values();
    }
}
