<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useFinance } from '@/composables/useFinance';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { BarChart3, TrendingUp, TrendingDown, Calendar } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: '/reports',
    },
];

const { 
    fetchExpenseStats, 
    fetchIncomeStats,
    loading, 
    formatCurrency 
} = useFinance();

const expenseStats = ref<any>(null);
const incomeStats = ref<any>(null);
const dateRange = ref({
    start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
});

const loadStats = async () => {
    try {
        const [expenseData, incomeData] = await Promise.all([
            fetchExpenseStats(dateRange.value),
            fetchIncomeStats(dateRange.value),
        ]);
        expenseStats.value = expenseData;
        incomeStats.value = incomeData;
    } catch (err) {
        console.error('Failed to load stats:', err);
    }
};

const handleDateChange = () => {
    loadStats();
};

onMounted(() => {
    loadStats();
});
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Financial Reports</h1>
                    <p class="text-muted-foreground">Analyze your spending and income patterns</p>
                </div>
            </div>

            <!-- Date Range Selector -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Date Range
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date</Label>
                            <Input 
                                id="start_date"
                                v-model="dateRange.start_date" 
                                type="date"
                                @change="handleDateChange"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_date">End Date</Label>
                            <Input 
                                id="end_date"
                                v-model="dateRange.end_date" 
                                type="date"
                                @change="handleDateChange"
                            />
                        </div>
                        <div class="flex items-end">
                            <Button @click="loadStats" :disabled="loading">
                                {{ loading ? 'Loading...' : 'Update Report' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <!-- Statistics Cards -->
            <div v-else class="space-y-6">
                <!-- Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Total Expenses -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Expenses</CardTitle>
                            <TrendingDown class="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-red-600">
                                {{ expenseStats ? formatCurrency(expenseStats.total_amount) : '$0.00' }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ expenseStats?.total_count || 0 }} transactions
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Income -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Income</CardTitle>
                            <TrendingUp class="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">
                                {{ incomeStats ? formatCurrency(incomeStats.total_amount) : '$0.00' }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ incomeStats?.total_count || 0 }} transactions
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Net Balance -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Net Balance</CardTitle>
                            <BarChart3 class="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div 
                                class="text-2xl font-bold"
                                :class="(incomeStats?.total_amount || 0) - (expenseStats?.total_amount || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ formatCurrency((incomeStats?.total_amount || 0) - (expenseStats?.total_amount || 0)) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Income - Expenses
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Detailed Reports -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Expense Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Expenses by Category</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="expenseStats?.by_category?.length > 0" class="space-y-3">
                                <div 
                                    v-for="category in expenseStats.by_category" 
                                    :key="category.name"
                                    class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                                >
                                    <div>
                                        <div class="font-medium">{{ category.name }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ category.count }} transactions
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-red-600">
                                            {{ formatCurrency(category.total) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ expenseStats.total_amount > 0 ? Math.round((category.total / expenseStats.total_amount) * 100) : 0 }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No expense data available for this period
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Income Categories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Income by Category</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="incomeStats?.by_category?.length > 0" class="space-y-3">
                                <div 
                                    v-for="category in incomeStats.by_category" 
                                    :key="category.name"
                                    class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                                >
                                    <div>
                                        <div class="font-medium">{{ category.name }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ category.count }} transactions
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-green-600">
                                            {{ formatCurrency(category.total) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ incomeStats.total_amount > 0 ? Math.round((category.total / incomeStats.total_amount) * 100) : 0 }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                No income data available for this period
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Income Sources -->
                <Card v-if="incomeStats?.by_source?.length > 0">
                    <CardHeader>
                        <CardTitle>Income by Source</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="source in incomeStats.by_source" 
                                :key="source.source"
                                class="p-4 rounded-lg bg-green-50 border border-green-200"
                            >
                                <div class="font-medium text-green-900">{{ source.source }}</div>
                                <div class="text-lg font-semibold text-green-700">
                                    {{ formatCurrency(source.total) }}
                                </div>
                                <div class="text-sm text-green-600">
                                    {{ source.count }} transactions
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Averages -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Average Transaction Amounts</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Average Expense</span>
                                    <span class="font-semibold text-red-600">
                                        {{ expenseStats ? formatCurrency(expenseStats.average_amount || 0) : '$0.00' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Average Income</span>
                                    <span class="font-semibold text-green-600">
                                        {{ incomeStats ? formatCurrency(incomeStats.average_amount || 0) : '$0.00' }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="incomeStats?.recurring_count > 0">
                        <CardHeader>
                            <CardTitle>Recurring Income</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">
                                    {{ incomeStats.recurring_count }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    Active recurring income sources
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
