import { ref, computed } from 'vue';

export interface Category {
    id: number;
    name: string;
    description?: string;
    type: 'expense' | 'income' | 'both';
    color: string;
    user_id: number;
    parent_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface Expense {
    id: number;
    title: string;
    description?: string;
    amount: number;
    expense_date: string;
    user_id: number;
    category_id?: number;
    category?: Category;
    payment_method?: string;
    receipt_path?: string;
    tags?: string[];
    expense_type?: 'regular' | 'dps_payment' | 'fdr_investment' | 'loan_payment';
    related_id?: number;
    related_type?: 'dps' | 'fdr' | 'loan';
    bank_account_id?: number;
    bank_account?: BankAccount;
    dps?: Investment;
    fdr?: Investment;
    loan?: Investment;
    created_at: string;
    updated_at: string;
}

export interface Income {
    id: number;
    title: string;
    description?: string;
    amount: number;
    income_date: string;
    user_id: number;
    category_id?: number;
    category?: Category;
    client_id?: number;
    client?: Client;
    bank_account_id?: number;
    bank_account?: BankAccount
    source?: string;
    is_recurring: boolean;
    recurring_frequency?: string;
    recurring_period?: string;
    tags?: string[];
    created_at: string;
    updated_at: string;
}

export interface Client {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    company?: string;
    notes?: string;
    status: 'active' | 'inactive';
    user_id: number;
    incomes?: Income[];
    created_at: string;
    updated_at: string;
}

export interface Budget {
    id: number;
    budget_name: string;
    description?: string;
    amount: number;
    period_type: 'monthly' | 'quarterly' | 'yearly';
    start_date: string;
    end_date: string;
    user_id: number;
    category_id?: number;
    category?: Category;
    spent_amount: number;
    remaining_amount: number;
    spent_percentage: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface BankAccount {
    id: number;
    account_name: string;
    account_number: string;
    bank_name: string;
    account_type: 'savings' | 'checking' | 'credit' | 'investment';
    current_balance: number;
    initial_amount: number;
    possible_current_balance: number,
    description?: string;
    user_id: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Investment {
    id: number;
    type: 'dps' | 'fdr' | 'loan';
    title: string;
    description?: string;
    amount: number;
    interest_rate: number;
    start_date: string;
    end_date?: string;
    bank_account_id?: number;
    bank_account?: BankAccount;
    status: 'active' | 'matured' | 'closed';
    user_id: number;
    created_at: string;
    updated_at: string;
}

export interface DashboardData {
    period: {
        type: string;
        start_date: string;
        end_date: string;
    };
    summary: {
        total_income: number;
        total_expenses: number;
        net_balance: number;
        recurring_incomes_count: number;
    };
    monthly_trends: Array<{
        month: string;
        income: number;
        expenses: number;
        net: number;
    }>;
    top_expense_categories: Array<{
        name: string;
        color: string;
        total: number;
    }>;
    top_income_categories: Array<{
        name: string;
        color: string;
        total: number;
    }>;
    recent_transactions: {
        expenses: Expense[];
        incomes: Income[];
    };
    budget_analysis?: {
        current_month_expenses: number;
        previous_month_expenses: number;
        difference: number;
        percentage_change: number;
    };
    bank_accounts_summary: {
        total_accounts: number;
        total_balance: number;
        total_initial_amount: number;
        net_change: number;
    };
    investment_overview: {
        dps: {
            count: number;
            total_amount: number;
        };
        fdr: {
            count: number;
            total_amount: number;
        };
        loans: {
            count: number;
            total_amount: number;
        };
    };
    budget_overview: {
        total_budgets: number;
        total_budget_amount: number;
        total_spent: number;
    };
    expense_breakdown: {
        regular: number;
        dps_payments: number;
        fdr_investments: number;
        loan_payments: number;
    };
    client_overview: {
        total_clients: number;
        active_clients: number;
        total_client_income: number;
    };
}

export function useFinance() {
    const loading = ref(false);
    const error = ref<string | null>(null);

    // API call helper
    async function apiCall<T>(url: string, options: RequestInit = {}): Promise<T> {
        loading.value = true;
        error.value = null;

        try {
            // Get CSRF token from meta tag or cookie
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];

            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token && { 'X-CSRF-TOKEN': decodeURIComponent(token) }),
                    ...options.headers,
                },
                credentials: 'same-origin',
                ...options,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Dashboard
    async function fetchDashboard(params: { period?: string; start_date?: string; end_date?: string } = {}): Promise<DashboardData> {
        const queryString = new URLSearchParams(params as Record<string, string>).toString();
        const url = `/api/dashboard${queryString ? `?${queryString}` : ''}`;
        return apiCall<DashboardData>(url);
    }

    // Categories
    async function fetchCategories(type?: string): Promise<{ categories: Category[] }> {
        const url = type ? `/api/categories?type=${type}` : '/api/categories';
        return apiCall<{ categories: Category[] }>(url);
    }

    // Categories
    async function fetchParentCategories(type?: string): Promise<{ categories: Category[] }> {
        const url = type ? `/api/categories?type=${type}&parents_only=true` : '/api/categories?parents_only=true';
        return apiCall<{ categories: Category[] }>(url);
    }

    async function createCategory(data: Partial<Category>): Promise<{ category: Category; message: string }> {
        return apiCall<{ category: Category; message: string }>('/api/categories', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateCategory(id: number, data: Partial<Category>): Promise<{ category: Category; message: string }> {
        return apiCall<{ category: Category; message: string }>(`/api/categories/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteCategory(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/categories/${id}`, {
            method: 'DELETE',
        });
    }

    // Expenses
    async function fetchExpenses(params: {
        page?: number;
        per_page?: number;
        start_date?: string;
        end_date?: string;
        category_id?: number;
        search?: string;
    } = {}): Promise<{
        data: Expense[];
        meta: any;
        links: any;
    }> {
        const queryString = new URLSearchParams(
            Object.entries(params).reduce((acc, [key, value]) => {
                if (value !== undefined && value !== null) {
                    acc[key] = String(value);
                }
                return acc;
            }, {} as Record<string, string>)
        ).toString();
        const url = `/api/expenses${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    async function createExpense(data: Partial<Expense>): Promise<{ expense: Expense; message: string }> {
        return apiCall<{ expense: Expense; message: string }>('/api/expenses', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateExpense(id: number, data: Partial<Expense>): Promise<{ expense: Expense; message: string }> {
        return apiCall<{ expense: Expense; message: string }>(`/api/expenses/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteExpense(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/expenses/${id}`, {
            method: 'DELETE',
        });
    }

    async function fetchExpenseStats(params: { start_date?: string; end_date?: string } = {}): Promise<{
        total_amount: number;
        total_count: number;
        average_amount: number;
        by_category: Array<{ name: string; total: number; count: number }>;
    }> {
        const queryString = new URLSearchParams(params as Record<string, string>).toString();
        const url = `/api/expenses/stats${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    // Incomes
    async function fetchIncomes(params: {
        page?: number;
        per_page?: number;
        start_date?: string;
        end_date?: string;
        category_id?: number;
        is_recurring?: boolean;
        search?: string;
    } = {}): Promise<{
        data: Income[];
        meta: any;
        links: any;
    }> {
        const queryString = new URLSearchParams(
            Object.entries(params).reduce((acc, [key, value]) => {
                if (value !== undefined && value !== null) {
                    acc[key] = String(value);
                }
                return acc;
            }, {} as Record<string, string>)
        ).toString();
        const url = `/api/incomes${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    async function createIncome(data: Partial<Income>): Promise<{ income: Income; message: string }> {
        return apiCall<{ income: Income; message: string }>('/api/incomes', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateIncome(id: number, data: Partial<Income>): Promise<{ income: Income; message: string }> {
        return apiCall<{ income: Income; message: string }>(`/api/incomes/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteIncome(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/incomes/${id}`, {
            method: 'DELETE',
        });
    }

    async function fetchIncomeStats(params: { start_date?: string; end_date?: string } = {}): Promise<{
        total_amount: number;
        total_count: number;
        average_amount: number;
        recurring_count: number;
        by_category: Array<{ name: string; total: number; count: number }>;
        by_source: Array<{ source: string; total: number; count: number }>;
    }> {
        const queryString = new URLSearchParams(params as Record<string, string>).toString();
        const url = `/api/incomes/stats${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    // Budgets
    async function fetchBudgets(params: {
        page?: number;
        per_page?: number;
        category_id?: number;
        period_type?: string;
        search?: string;
    } = {}): Promise<{
        data: Budget[];
        meta: any;
        links: any;
    }> {
        const queryString = new URLSearchParams(
            Object.entries(params).reduce((acc, [key, value]) => {
                if (value !== undefined && value !== null) {
                    acc[key] = String(value);
                }
                return acc;
            }, {} as Record<string, string>)
        ).toString();
        const url = `/api/budgets${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    async function createBudget(data: Partial<Budget>): Promise<{ budget: Budget; message: string }> {
        return apiCall<{ budget: Budget; message: string }>('/api/budgets', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateBudget(id: number, data: Partial<Budget>): Promise<{ budget: Budget; message: string }> {
        return apiCall<{ budget: Budget; message: string }>(`/api/budgets/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteBudget(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/budgets/${id}`, {
            method: 'DELETE',
        });
    }

    async function fetchBudgetAnalytics(): Promise<{
        total_budgets: number;
        active_budgets: number;
        over_budget_count: number;
        total_allocated: number;
        total_spent: number;
        average_utilization: number;
    }> {
        return apiCall('/api/budgets/analytics');
    }

    // Bank Accounts
    async function fetchBankAccounts(params: {
        page?: number;
        per_page?: number;
        account_type?: string;
        search?: string;
    } = {}): Promise<{
        data: BankAccount;
        meta: any;
        links: any;
    }> {
        const queryString = new URLSearchParams(
            Object.entries(params).reduce((acc, [key, value]) => {
                if (value !== undefined && value !== null) {
                    acc[key] = String(value);
                }
                return acc;
            }, {} as Record<string, string>)
        ).toString();
        const url = `/api/bank-accounts${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    async function createBankAccount(data: Partial<BankAccount>): Promise<{ bank_account: BankAccount; message: string }> {
        return apiCall<{ bank_account: BankAccount; message: string }>('/api/bank-accounts', {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async function updateBankAccount(id: number, data: Partial<BankAccount>): Promise<{ bank_account: BankAccount; message: string }> {
        return apiCall<{ bank_account: BankAccount; message: string }>(`/api/bank-accounts/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async function deleteBankAccount(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/bank-accounts/${id}`, {
            method: 'DELETE',
        });
    }

    // Investments
    async function fetchInvestments(params: {
        page?: number;
        per_page?: number;
        type?: string;
        status?: string;
        search?: string;
    } = {}): Promise<{
        data: Investment[];
        meta: any;
        links: any;
    }> {
        const queryString = new URLSearchParams(
            Object.entries(params).reduce((acc, [key, value]) => {
                if (value !== undefined && value !== null) {
                    acc[key] = String(value);
                }
                return acc;
            }, {} as Record<string, string>)
        ).toString();
        const url = `/api/investments${queryString ? `?${queryString}` : ''}`;
        return apiCall(url);
    }

    // Utilities
    function formatCurrency(amount: number): string {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    }

    function formatDate(date: string | Date): string {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    // Client CRUD operations
    async function fetchClients(params: any = {}): Promise<{ data: Client[]; meta: any; links: any }> {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `/api/clients?${queryString}` : '/api/clients';
        
        return apiCall<{ data: Client[]; meta: any; links: any }>(url, {
            method: 'GET'
        });
    }

    async function createClient(data: Partial<Client>): Promise<{ client: Client; message: string }> {
        return apiCall<{ client: Client; message: string }>('/api/clients', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async function updateClient(id: number, data: Partial<Client>): Promise<{ client: Client; message: string }> {
        return apiCall<{ client: Client; message: string }>(`/api/clients/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async function deleteClient(id: number): Promise<{ message: string }> {
        return apiCall<{ message: string }>(`/api/clients/${id}`, {
            method: 'DELETE'
        });
    }

    return {
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        
        // Dashboard
        fetchDashboard,
        
        // Categories
        fetchCategories,
        fetchParentCategories,
        createCategory,
        updateCategory,
        deleteCategory,
        
        // Expenses
        fetchExpenses,
        createExpense,
        updateExpense,
        deleteExpense,
        fetchExpenseStats,
        
        // Incomes
        fetchIncomes,
        createIncome,
        updateIncome,
        deleteIncome,
        fetchIncomeStats,
        
        // Budgets
        fetchBudgets,
        createBudget,
        updateBudget,
        deleteBudget,
        fetchBudgetAnalytics,
        
        // Bank Accounts
        fetchBankAccounts,
        createBankAccount,
        updateBankAccount,
        deleteBankAccount,
        
        // Investments
        fetchInvestments,
        
        // Clients
        fetchClients,
        createClient,
        updateClient,
        deleteClient,
        
        // Utilities
        formatCurrency,
        formatDate,
    };
}
