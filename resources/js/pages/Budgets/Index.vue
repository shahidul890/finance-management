<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type Budget, type Category } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2, PiggyBank, Target, AlertTriangle, TrendingUp } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Budgets',
        href: '/budgets',
    },
];

const { 
    fetchBudgets, 
    createBudget, 
    updateBudget, 
    deleteBudget, 
    fetchCategories,
    fetchBudgetAnalytics,
    loading, 
    formatCurrency 
} = useFinance();

const budgets = ref<{ data: Budget[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const categories = ref<Category[]>([]);
const analytics = ref({
    total_budgets: 0,
    active_budgets: 0,
    over_budget_count: 0,
    total_allocated: 0,
    total_spent: 0,
    average_utilization: 0
});

const searchQuery = ref('');
const selectedCategory = ref<string>('');
const selectedPeriod = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingBudget = ref<Budget | null>(null);

// Form data
const form = ref({
    budget_name: '',
    description: '',
    budget_amount: '',
    period_type: 'monthly',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    category_id: '',
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);

const loadBudgets = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_id = selectedCategory.value;
        if (selectedPeriod.value) params.period_type = selectedPeriod.value;
        
        const data = await fetchBudgets(params);
        budgets.value = data;
    } catch (err) {
        console.error('Failed to load budgets:', err);
    }
};

const loadCategories = async () => {
    try {
        const result = await fetchCategories('expense');
        categories.value = result.categories;
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
};

const loadAnalytics = async () => {
    try {
        const data = await fetchBudgetAnalytics();
        analytics.value = data;
    } catch (err) {
        console.error('Failed to load analytics:', err);
    }
};

const resetForm = () => {
    form.value = {
        budget_name: '',
        description: '',
        budget_amount: '',
        period_type: 'monthly',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        category_id: '',
    };
};

const calculateEndDate = () => {
    if (!form.value.start_date || !form.value.period_type) return;
    
    const startDate = new Date(form.value.start_date);
    let endDate = new Date(startDate);
    
    switch (form.value.period_type) {
        case 'monthly':
            endDate.setMonth(endDate.getMonth() + 1);
            break;
        case 'quarterly':
            endDate.setMonth(endDate.getMonth() + 3);
            break;
        case 'yearly':
            endDate.setFullYear(endDate.getFullYear() + 1);
            break;
    }
    
    endDate.setDate(endDate.getDate() - 1); // End on the day before
    form.value.end_date = endDate.toISOString().split('T')[0];
};

const handleCreate = async () => {
    try {
        calculateEndDate();
        const budgetData: any = { ...form.value };
        if (budgetData.amount) budgetData.amount = parseFloat(budgetData.amount);
        if (budgetData.category_id === '') budgetData.category_id = null;
        
        await createBudget(budgetData);
        showCreateDialog.value = false;
        resetForm();
        loadBudgets();
        loadAnalytics();
        console.log('Budget created successfully');
    } catch {
        console.error('Failed to create budget');
    }
};

const handleEdit = (budget: Budget) => {
    editingBudget.value = budget;
    form.value = {
        budget_name: budget.budget_name,
        description: budget.description || '',
        budget_amount: budget.budget_amount.toString(),
        period_type: budget.period_type,
        start_date: budget.start_date,
        end_date: budget.end_date,
        category_id: budget.category_id?.toString() || '',
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingBudget.value) return;
    
    try {
        const budgetData: any = { ...form.value };
        if (budgetData.amount) budgetData.amount = parseFloat(budgetData.amount);
        if (budgetData.category_id === '') budgetData.category_id = null;
        
        await updateBudget(editingBudget.value.id, budgetData);
        showEditDialog.value = false;
        editingBudget.value = null;
        resetForm();
        loadBudgets();
        loadAnalytics();
        console.log('Budget updated successfully');
    } catch {
        console.error('Failed to update budget');
    }
};

const handleDelete = async (budget: Budget) => {
    if (!confirm('Are you sure you want to delete this budget?')) return;
    
    try {
        await deleteBudget(budget.id);
        loadBudgets();
        loadAnalytics();
        console.log('Budget deleted successfully');
    } catch {
        console.error('Failed to delete budget');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadBudgets();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadBudgets();
};

const getProgressBarColor = (percentage: number) => {
    if (percentage < 50) return 'bg-green-500';
    if (percentage < 80) return 'bg-yellow-500';
    if (percentage < 100) return 'bg-orange-500';
    return 'bg-red-500';
};

const getBudgetStatus = (budget: Budget) => {
    if (!budget.is_active) return { text: 'Inactive', color: 'text-gray-500' };
    if (budget.spent_percentage >= 100) return { text: 'Over Budget', color: 'text-red-600' };
    if (budget.spent_percentage >= 80) return { text: 'Near Limit', color: 'text-orange-600' };
    return { text: 'On Track', color: 'text-green-600' };
};

const totalPages = computed(() => {
    return budgets.value.meta?.last_page || 1;
});

onMounted(() => {
    loadBudgets();
    loadCategories();
    loadAnalytics();
});
</script>

<template>
    <Head title="Budgets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Budgets</h1>
                    <p class="text-muted-foreground">Plan and track your spending budgets</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Create Budget
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-7/8 overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Create New Budget</DialogTitle>
                                <DialogDescription>
                                    Set up a budget to track your spending goals.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="budget_name">Budget Name</Label>
                                    <Input 
                                        id="budget_name" 
                                        v-model="form.budget_name" 
                                        placeholder="Enter budget name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="amount">Budget Amount</Label>
                                    <Input 
                                        id="amount" 
                                        v-model="form.budget_amount" 
                                        type="number" 
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="period_type">Period Type</Label>
                                    <select 
                                        id="period_type" 
                                        v-model="form.period_type"
                                        @change="calculateEndDate"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="start_date">Start Date</Label>
                                    <Input 
                                        id="start_date" 
                                        v-model="form.start_date" 
                                        type="date"
                                        @change="calculateEndDate"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="end_date">End Date</Label>
                                    <Input 
                                        id="end_date" 
                                        v-model="form.end_date" 
                                        type="date"
                                        readonly
                                        class="bg-muted"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="category">Category (Optional)</Label>
                                    <select 
                                        id="category" 
                                        v-model="form.category_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">All categories</option>
                                        <option 
                                            v-for="category in categories" 
                                            :key="category.id"
                                            :value="category.id.toString()"
                                        >
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="description">Description</Label>
                                    <textarea 
                                        id="description" 
                                        v-model="form.description" 
                                        placeholder="Optional description"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    ></textarea>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button @click="handleCreate" :disabled="loading">
                                    {{ loading ? 'Creating...' : 'Create Budget' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </DialogOverlay>
                </Dialog>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <PiggyBank class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Budgets</p>
                                <p class="text-2xl font-bold">{{ analytics.total_budgets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <Target class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Active Budgets</p>
                                <p class="text-2xl font-bold">{{ analytics.active_budgets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <AlertTriangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Over Budget</p>
                                <p class="text-2xl font-bold">{{ analytics.over_budget_count }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <TrendingUp class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg. Utilization</p>
                                <p class="text-2xl font-bold">{{ Math.round(analytics.average_utilization) }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input 
                                    v-model="searchQuery" 
                                    placeholder="Search budgets..."
                                    class="pl-10"
                                    @keyup.enter="handleSearch"
                                />
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select 
                                v-model="selectedCategory" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All categories</option>
                                <option 
                                    v-for="category in categories" 
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:w-40">
                            <select 
                                v-model="selectedPeriod" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All periods</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Budget Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Budget List</CardTitle>
                    <CardDescription>
                        {{ budgets.meta?.total || 0 }} total budgets
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="budgets.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No budgets found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Budget</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amount</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Spent</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Progress</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Period</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="budget in budgets.data" :key="budget.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ budget.budget_name }}</div>
                                            <div v-if="budget.description" class="text-sm text-muted-foreground">
                                                {{ budget.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="budget.category" class="flex items-center gap-2">
                                            <div 
                                                class="w-3 h-3 rounded-full" 
                                                :style="{ backgroundColor: budget.category.color }"
                                            ></div>
                                            {{ budget.category.name }}
                                        </div>
                                        <span v-else class="text-muted-foreground">All categories</span>
                                    </td>
                                    <td class="p-4 align-middle font-semibold">
                                        {{ formatCurrency(budget.budget_amount) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="text-sm">
                                            <div class="font-medium">{{ formatCurrency(budget.spent_amount) }}</div>
                                            <div class="text-muted-foreground">{{ formatCurrency(budget.remaining_amount) }} remaining</div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="w-full">
                                            <div class="flex justify-between text-sm mb-1">
                                                <span>{{ Math.round(budget.spent_percentage) }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div 
                                                    :class="getProgressBarColor(budget.spent_percentage)"
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :style="{ width: Math.min(budget.spent_percentage, 100) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="text-sm">
                                            <div class="capitalize font-medium">{{ budget.period_type }}</div>
                                            <div class="text-muted-foreground">
                                                {{ new Date(budget.start_date).toLocaleDateString() }} - 
                                                {{ new Date(budget.end_date).toLocaleDateString() }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getBudgetStatus(budget).color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80"
                                        >
                                            {{ getBudgetStatus(budget).text }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="handleEdit(budget)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(budget)">
                                                <Trash2 class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-center space-x-2 mt-4">
                        <Button 
                            variant="outline" 
                            size="sm"
                            :disabled="currentPage === 1"
                            @click="handlePageChange(currentPage - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ currentPage }} of {{ totalPages }}
                        </span>
                        <Button 
                            variant="outline" 
                            size="sm"
                            :disabled="currentPage === totalPages"
                            @click="handlePageChange(currentPage + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Edit Dialog -->
            <Dialog v-model:open="showEditDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Budget</DialogTitle>
                        <DialogDescription>
                            Update the budget details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-budget_name">Budget Name</Label>
                            <Input 
                                id="edit-budget_name" 
                                v-model="form.budget_name" 
                                placeholder="Budget name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-amount">Budget Amount</Label>
                            <Input 
                                id="edit-amount" 
                                v-model="form.budget_amount" 
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-period_type">Period Type</Label>
                            <select 
                                id="edit-period_type" 
                                v-model="form.period_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-start_date">Start Date</Label>
                            <Input 
                                id="edit-start_date" 
                                v-model="form.start_date" 
                                type="date"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-end_date">End Date</Label>
                            <Input 
                                id="edit-end_date" 
                                v-model="form.end_date" 
                                type="date"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-category">Category</Label>
                            <select 
                                id="edit-category" 
                                v-model="form.category_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All categories</option>
                                <option 
                                    v-for="category in categories" 
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-description">Description</Label>
                            <textarea 
                                id="edit-description" 
                                v-model="form.description" 
                                placeholder="Optional description"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="handleUpdate" :disabled="loading">
                            {{ loading ? 'Updating...' : 'Update Budget' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
