<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useFinance, type DashboardData } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TrendingUp, TrendingDown, DollarSign, Repeat } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const { fetchDashboard, loading, error, formatCurrency } = useFinance();
const dashboardData = ref<DashboardData | null>(null);
const selectedPeriod = ref('month');

const loadDashboard = async () => {
    try {
        dashboardData.value = await fetchDashboard({ period: selectedPeriod.value });
    } catch (err) {
        console.error('Failed to load dashboard:', err);
    }
};

onMounted(() => {
    loadDashboard();
});

const getBalanceColor = (balance: number) => {
    return balance >= 0 ? 'text-green-600' : 'text-red-600';
};

const getPercentageColor = (percentage: number) => {
    return percentage >= 0 ? 'text-red-500' : 'text-green-500';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 overflow-x-auto">
            <!-- Period Selector -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Financial Dashboard</h1>
                <div class="flex gap-2">
                    <Button 
                        :variant="selectedPeriod === 'month' ? 'default' : 'outline'"
                        size="sm"
                        @click="selectedPeriod = 'month'; loadDashboard()"
                    >
                        This Month
                    </Button>
                    <Button 
                        :variant="selectedPeriod === 'year' ? 'default' : 'outline'"
                        size="sm"
                        @click="selectedPeriod = 'year'; loadDashboard()"
                    >
                        This Year
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center text-red-500 p-8">
                <p>{{ error }}</p>
                <Button @click="loadDashboard()" class="mt-4">Try Again</Button>
            </div>

            <!-- Dashboard Content -->
            <div v-else-if="dashboardData" class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Income -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Income</CardTitle>
                            <TrendingUp class="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(dashboardData.summary.total_income) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ selectedPeriod === 'month' ? 'This month' : 'This year' }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Expenses -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Expenses</CardTitle>
                            <TrendingDown class="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-red-600">
                                {{ formatCurrency(dashboardData.summary.total_expenses) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ selectedPeriod === 'month' ? 'This month' : 'This year' }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Net Balance -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Net Balance</CardTitle>
                            <DollarSign class="h-4 w-4" :class="getBalanceColor(dashboardData.summary.net_balance)" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold" :class="getBalanceColor(dashboardData.summary.net_balance)">
                                {{ formatCurrency(dashboardData.summary.net_balance) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Income - Expenses
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Recurring Incomes -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Recurring Incomes</CardTitle>
                            <Repeat class="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ dashboardData.summary.recurring_incomes_count }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Active recurring sources
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Budget Analysis -->
                <Card v-if="selectedPeriod === 'month'">
                    <CardHeader>
                        <CardTitle>Budget Analysis</CardTitle>
                        <CardDescription>Comparison with previous month</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-4">
                            <div>
                                <p class="text-sm text-muted-foreground">Previous Month</p>
                                <p class="text-lg font-semibold">
                                    {{ formatCurrency(dashboardData.budget_analysis.previous_month_expenses) }}
                                </p>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-muted-foreground">Difference</p>
                                <p class="text-lg font-semibold" :class="getPercentageColor(dashboardData.budget_analysis.percentage_change)">
                                    {{ dashboardData.budget_analysis.percentage_change >= 0 ? '+' : '' }}{{ dashboardData.budget_analysis.percentage_change }}%
                                    ({{ formatCurrency(dashboardData.budget_analysis.difference) }})
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Categories -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Expense Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Top Expense Categories</CardTitle>
                            <CardDescription>Your biggest spending areas</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="category in dashboardData.top_expense_categories.slice(0, 5)"
                                    :key="category.name"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div 
                                            class="w-3 h-3 rounded-full" 
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <span class="text-sm font-medium">{{ category.name }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-red-600">
                                        {{ formatCurrency(category.total) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Top Income Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Top Income Categories</CardTitle>
                            <CardDescription>Your main income sources</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="category in dashboardData.top_income_categories.slice(0, 5)"
                                    :key="category.name"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div 
                                            class="w-3 h-3 rounded-full" 
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <span class="text-sm font-medium">{{ category.name }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-green-600">
                                        {{ formatCurrency(category.total) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Financial Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Bank Accounts Summary -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Bank Accounts</CardTitle>
                            <CardDescription>Total bank balance</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ formatCurrency(dashboardData.bank_accounts_summary.total_balance) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ dashboardData.bank_accounts_summary.total_accounts }} account(s)
                            </p>
                            <div class="mt-2 text-sm">
                                <span class="text-muted-foreground">Net change: </span>
                                <span :class="getBalanceColor(dashboardData.bank_accounts_summary.net_change)">
                                    {{ formatCurrency(dashboardData.bank_accounts_summary.net_change) }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- DPS Overview -->
                    <Card>
                        <CardHeader>
                            <CardTitle>DPS Investments</CardTitle>
                            <CardDescription>Active DPS accounts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(dashboardData.investment_overview.dps.total_amount) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ dashboardData.investment_overview.dps.count }} active DPS
                            </p>
                        </CardContent>
                    </Card>

                    <!-- FDR Overview -->
                    <Card>
                        <CardHeader>
                            <CardTitle>FDR Investments</CardTitle>
                            <CardDescription>Active FDR accounts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(dashboardData.investment_overview.fdr.total_amount) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ dashboardData.investment_overview.fdr.count }} active FDR
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Loans Overview -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Loans</CardTitle>
                            <CardDescription>Active loan accounts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-orange-600">
                                {{ formatCurrency(dashboardData.investment_overview.loans.total_amount) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ dashboardData.investment_overview.loans.count }} active loan(s)
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Budget Overview -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Budget Status</CardTitle>
                            <CardDescription>Current budget utilization</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ formatCurrency(dashboardData.budget_overview.total_spent) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                of {{ formatCurrency(dashboardData.budget_overview.total_budget_amount) }} budgeted
                            </p>
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full" 
                                        :style="{ width: Math.min((dashboardData.budget_overview.total_spent / dashboardData.budget_overview.total_budget_amount) * 100, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Client Overview -->
                    <Card v-if="dashboardData.client_overview">
                        <CardHeader>
                            <CardTitle>Clients</CardTitle>
                            <CardDescription>Client management overview</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-purple-600">
                                {{ dashboardData.client_overview.total_clients }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ dashboardData.client_overview.active_clients }} active
                            </p>
                            <div class="mt-2 text-sm">
                                <span class="text-muted-foreground">Client income: </span>
                                <span class="text-green-600 font-semibold">
                                    {{ formatCurrency(dashboardData.client_overview.total_client_income) }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Expense Breakdown -->
                <Card>
                    <CardHeader>
                        <CardTitle>Expense Breakdown by Type</CardTitle>
                        <CardDescription>Spending distribution across categories</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-600">
                                    {{ formatCurrency(dashboardData.expense_breakdown.regular) }}
                                </div>
                                <p class="text-xs text-muted-foreground">Regular Expenses</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-600">
                                    {{ formatCurrency(dashboardData.expense_breakdown.dps_payments) }}
                                </div>
                                <p class="text-xs text-muted-foreground">DPS Payments</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-green-600">
                                    {{ formatCurrency(dashboardData.expense_breakdown.fdr_investments) }}
                                </div>
                                <p class="text-xs text-muted-foreground">FDR Investments</p>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-orange-600">
                                    {{ formatCurrency(dashboardData.expense_breakdown.loan_payments) }}
                                </div>
                                <p class="text-xs text-muted-foreground">Loan Payments</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Expenses -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Expenses</CardTitle>
                            <CardDescription>Your latest spending</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="expense in dashboardData.recent_transactions.expenses"
                                    :key="expense.id"
                                    class="flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-medium">{{ expense.title }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ expense.category?.name }} • {{ new Date(expense.expense_date).toLocaleDateString() }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-red-600">
                                        -{{ formatCurrency(expense.amount) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Incomes -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Incomes</CardTitle>
                            <CardDescription>Your latest earnings</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="income in dashboardData.recent_transactions.incomes"
                                    :key="income.id"
                                    class="flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-medium">{{ income.title }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ income.category?.name }} • {{ new Date(income.income_date).toLocaleDateString() }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-green-600">
                                        +{{ formatCurrency(income.amount) }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
