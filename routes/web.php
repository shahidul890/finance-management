<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


// Financial Management Routes
Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
    Route::get('/expenses', function () {
        return Inertia::render('Expenses/Index');
    })->name('expenses.index');
    
    Route::get('/incomes', function () {
        return Inertia::render('Incomes/Index');
    })->name('incomes.index');
    
    Route::get('/categories', function () {
        return Inertia::render('Categories/Index');
    })->name('categories.index');
    
    Route::get('/budgets', function () {
        return Inertia::render('Budgets/Index');
    })->name('budgets.index');
    
    Route::get('/bank-accounts', function () {
        return Inertia::render('BankAccounts/Index');
    })->name('bank-accounts.index');

    Route::inertia('transactions', 'Transactions/Index')->name('transactions.index');

    Route::get('/investments', function () {
        return Inertia::render('Investments/Index');
    })->name('investments.index');
    
    Route::get('/clients', function () {
        return Inertia::render('Clients/Index');
    })->name('clients.index');
    
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');
    
    // API Routes for AJAX calls
    Route::prefix('api')->group(function () {
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
        Route::get('/budgets/analytics', [BudgetController::class, 'analytics']);
        Route::apiResource('budgets', BudgetController::class);
        
        // Bank Accounts
        Route::apiResource('bank-accounts', BankAccountController::class);

        // Transactions
        Route::apiResource('transactions', TransactionController::class);
        
        // Clients
        Route::apiResource('clients', ClientController::class);
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
