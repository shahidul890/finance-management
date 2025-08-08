<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected routes requiring authentication
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    
    // Expenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::get('/expenses/stats', [ExpenseController::class, 'stats']);
    
    // Incomes
    Route::apiResource('incomes', IncomeController::class);
    Route::get('/incomes/stats', [IncomeController::class, 'stats']);
    
    // Budgets
    Route::apiResource('budgets', BudgetController::class);
    Route::get('/budgets/analytics', [BudgetController::class, 'analytics']);
    
    // Bank Accounts
    Route::apiResource('bank-accounts', BankAccountController::class);
    
    // Clients
    Route::apiResource('clients', ClientController::class);
    
});
