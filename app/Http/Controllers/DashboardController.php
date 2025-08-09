<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Category;
use App\Models\Budget;
use App\Models\BankAccount;
use App\Models\Dps;
use App\Models\Fdr;
use App\Models\Loan;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard financial overview
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $period = $request->get('period', 'month'); // month, year, custom
        
        // Date range calculation
        switch ($period) {
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;

            case 'custom':
                $startDate = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
                $endDate = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));
                break;
            default: // month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Total Income and Expenses
        $totalIncome = Income::forUser($userId)
            ->dateRange($startDate, $endDate)
            ->sum('amount');
            
        $totalExpenses = Expense::forUser($userId)
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $netBalance = $totalIncome - $totalExpenses;

        // Monthly trends for chart (last 12 months)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthlyIncome = Income::forUser($userId)
                ->dateRange($monthStart, $monthEnd)
                ->sum('amount');
                
            $monthlyExpenses = Expense::forUser($userId)
                ->dateRange($monthStart, $monthEnd)
                ->sum('amount');
            
            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'income' => (float) $monthlyIncome,
                'expenses' => (float) $monthlyExpenses,
                'net' => (float) ($monthlyIncome - $monthlyExpenses),
            ];
        }

        // Top spending categories
        $topExpenseCategories = Expense::dateRange($startDate, $endDate)
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', DB::raw('SUM(expenses.amount) as total'))
            ->whereColumn('expenses.user_id', '=', $userId)
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Top income sources
        $topIncomeCategories = Income::dateRange($startDate, $endDate)
            ->join('categories', 'incomes.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', DB::raw('SUM(incomes.amount) as total'))
            ->whereColumn('incomes.user_id', '=', $userId)
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Recent transactions
        $recentExpenses = Expense::with('category')
            ->whereUserId($userId)
            ->orderBy('expense_date', 'desc')
            ->limit(5)
            ->get();
            
        $recentIncomes = Income::whereUserId($userId)
            ->with('category')
            ->orderBy('income_date', 'desc')
            ->limit(5)
            ->get();

        // Recurring incomes count
        $recurringIncomesCount = Income::whereUserId($userId)
            ->where('is_recurring', true)
            ->count();

        // Bank Accounts Summary
        $bankAccounts = BankAccount::whereUserId($userId)->get();
        $bankAccountsSummary = [
            'total_accounts' => $bankAccounts->count(),
            'total_balance' => (float) $bankAccounts->sum('current_balance'),
            'total_initial_amount' => (float) $bankAccounts->sum('initial_amount'),
            'net_change' => (float) ($bankAccounts->sum('current_balance') - $bankAccounts->sum('initial_amount')),
        ];

        // Investment Overview (DPS, FDR, Loans)
        $activeDps = Dps::whereUserId($userId)->where('status', 'active')->get();
        $activeFdrs = Fdr::whereUserId($userId)->where('status', 'active')->get();
        $activeLoans = Loan::whereUserId($userId)->where('status', 'active')->get();

        $investmentOverview = [
            'dps' => [
                'count' => $activeDps->count(),
                'total_amount' => (float) $activeDps->sum('amount'),
            ],
            'fdr' => [
                'count' => $activeFdrs->count(),
                'total_amount' => (float) $activeFdrs->sum('amount'),
            ],
            'loans' => [
                'count' => $activeLoans->count(),
                'total_amount' => (float) $activeLoans->sum('amount'),
            ],
        ];

        // Budget Overview
        $budgets = Budget::whereUserId($userId)->get();
        $budgetOverview = [
            'total_budgets' => $budgets->count(),
            'total_budget_amount' => (float) $budgets->sum('budget_amount'),
            'total_spent' => (float) $budgets->sum('spent_amount'),
        ];

        // Expense breakdown by type
        $expenseBreakdown = [
            'regular' => (float) Expense::whereUserId($userId)->dateRange($startDate, $endDate)->where('expense_type', 'regular')->sum('amount'),
            'dps_payments' => (float) Expense::whereUserId($userId)->dateRange($startDate, $endDate)->where('expense_type', 'dps_payment')->sum('amount'),
            'fdr_investments' => (float) Expense::whereUserId($userId)->dateRange($startDate, $endDate)->where('expense_type', 'fdr_investment')->sum('amount'),
            'loan_payments' => (float) Expense::whereUserId($userId)->dateRange($startDate, $endDate)->where('expense_type', 'loan_payment')->sum('amount'),
        ];

        // Client Overview
        $clients = Client::whereUserId($userId)->with('incomes')->get();
        $clientOverview = [
            'total_clients' => $clients->count(),
            'active_clients' => $clients->where('status', 'active')->count(),
            'total_client_income' => (float) $clients->sum(function($client) {
                return $client->incomes->sum('amount');
            }),
        ];

        // Budget Analysis
        $currentMonthBudget = $budgets->where('period_type', 'monthly')->sum('budget_amount');
        $previousMonthBudget = $budgets->where('period_type', 'monthly')
            ->where('start_date', '<', $startDate)
            ->sum('budget_amount');

        return response()->json([
            'period' => [
                'type' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_income' => (float) $totalIncome,
                'total_expenses' => (float) $totalExpenses,
                'net_balance' => (float) $netBalance,
                'recurring_incomes_count' => $recurringIncomesCount,
            ],
            'monthly_trends' => $monthlyData,
            'top_expense_categories' => $topExpenseCategories,
            'top_income_categories' => $topIncomeCategories,
            'recent_transactions' => [
                'expenses' => $recentExpenses,
                'incomes' => $recentIncomes,
            ],
            'bank_accounts_summary' => $bankAccountsSummary,
            'investment_overview' => $investmentOverview,
            'budget_overview' => $budgetOverview,
            'expense_breakdown' => $expenseBreakdown,
            'client_overview' => $clientOverview,
            'budget_analysis' => [
                'current_month_budget' => (float) $budgets->where('period_type', 'monthly')->sum('budget_amount'),
                'previous_month_budget' => (float) $budgets->where('period_type', 'monthly')->where('start_date', '<', $startDate)->sum('budget_amount'),
                'difference' => (float) ($budgets->where('period_type', 'monthly')->sum('budget_amount') - $budgets->where('period_type', 'monthly')->where('start_date', '<', $startDate)->sum('budget_amount')),
                'percentage_change' => $previousMonthBudget ? (($currentMonthBudget - $previousMonthBudget) / $previousMonthBudget * 100) : 0,
            ]
        ]);
    }
}
