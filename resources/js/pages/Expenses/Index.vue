<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type Expense, type Category, type BankAccount, type Investment } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses',
        href: '/expenses',
    },
];

const { 
    fetchExpenses, 
    createExpense, 
    updateExpense, 
    deleteExpense, 
    fetchCategories,
    fetchBankAccounts,
    fetchInvestments,
    loading, 
    formatCurrency 
} = useFinance();

const expenses = ref<{ data: Expense[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const categories = ref<Category[]>([]);
const bankAccounts = ref<BankAccount[]>([]);
const investments = ref<Investment[]>([]);
const searchQuery = ref('');
const selectedCategory = ref<string>('');
const selectedExpenseType = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingExpense = ref<Expense | null>(null);

// Form data
const form = ref({
    title: '',
    description: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    category_id: '',
    payment_method: '',
    tags: '',
    expense_type: 'regular',
    related_id: '',
    related_type: '',
    bank_account_id: '',
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);

const loadExpenses = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_id = selectedCategory.value;
        if (selectedExpenseType.value) params.expense_type = selectedExpenseType.value;
        
        expenses.value = await fetchExpenses(params);
    } catch (err) {
        console.error('Failed to load expenses:', err);
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

const loadBankAccounts = async () => {
    try {
        const result = await fetchBankAccounts();
        bankAccounts.value = result.data;
    } catch (err) {
        console.error('Failed to load bank accounts:', err);
    }
};

const loadInvestments = async () => {
    try {
        const result = await fetchInvestments();
        investments.value = result.data;
    } catch (err) {
        console.error('Failed to load investments:', err);
    }
};

const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        amount: '',
        expense_date: new Date().toISOString().split('T')[0],
        category_id: '',
        payment_method: '',
        tags: '',
        expense_type: 'regular',
        related_id: '',
        related_type: '',
        bank_account_id: '',
    };
};

const handleCreate = async () => {
    try {
        const expenseData: any = { ...form.value };
        if (expenseData.amount) expenseData.amount = parseFloat(expenseData.amount);
        if (expenseData.category_id === '') expenseData.category_id = null;
        if (expenseData.tags) {
            expenseData.tags = expenseData.tags.split(',').map((tag: string) => tag.trim());
        }
        
        await createExpense(expenseData);
        showCreateDialog.value = false;
        resetForm();
        loadExpenses();
        console.log('Expense created successfully');
    } catch {
        console.error('Failed to create expense');
    }
};

const handleEdit = (expense: Expense) => {
    editingExpense.value = expense;
    form.value = {
        title: expense.title,
        description: expense.description || '',
        amount: expense.amount.toString(),
        expense_date: expense.expense_date,
        category_id: expense.category_id?.toString() || '',
        payment_method: expense.payment_method || '',
        tags: expense.tags?.join(', ') || '',
        expense_type: expense.expense_type || 'regular',
        related_id: expense.related_id?.toString() || '',
        related_type: expense.related_type || '',
        bank_account_id: expense.bank_account_id?.toString() || '',
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingExpense.value) return;
    
    try {
        const expenseData: any = { ...form.value };
        if (expenseData.amount) expenseData.amount = parseFloat(expenseData.amount);
        if (expenseData.category_id === '') expenseData.category_id = null;
        if (expenseData.tags) {
            expenseData.tags = expenseData.tags.split(',').map((tag: string) => tag.trim());
        }
        
        await updateExpense(editingExpense.value.id, expenseData);
        showEditDialog.value = false;
        editingExpense.value = null;
        resetForm();
        loadExpenses();
        console.log('Expense updated successfully');
    } catch {
        console.error('Failed to update expense');
    }
};

const handleDelete = async (expense: Expense) => {
    if (!confirm('Are you sure you want to delete this expense?')) return;
    
    try {
        await deleteExpense(expense.id);
        loadExpenses();
        console.log('Expense deleted successfully');
    } catch {
        console.error('Failed to delete expense');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadExpenses();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadExpenses();
};

const totalPages = computed(() => {
    return expenses.value.meta?.last_page || 1;
});

const getFilteredInvestments = () => {
    const expenseType = form.value.expense_type;
    if (expenseType === 'dps_payment') {
        return investments.value.filter(investment => investment.type === 'dps');
    } else if (expenseType === 'fdr_investment') {
        return investments.value.filter(investment => investment.type === 'fdr');
    } else if (expenseType === 'loan_payment') {
        return investments.value.filter(investment => investment.type === 'loan');
    }
    return investments.value;
};

const updateRelatedType = () => {
    const selectedInvestment = investments.value.find(
        investment => investment.id.toString() === form.value.related_id
    );
    if (selectedInvestment) {
        form.value.related_type = selectedInvestment.type;
    }
};

const getExpenseTypeBadge = (expenseType: string) => {
    switch (expenseType) {
        case 'dps_payment': return { text: 'DPS', color: 'text-blue-600 bg-blue-100' };
        case 'fdr_investment': return { text: 'FDR', color: 'text-green-600 bg-green-100' };
        case 'loan_payment': return { text: 'Loan', color: 'text-orange-600 bg-orange-100' };
        default: return { text: 'Regular', color: 'text-gray-600 bg-gray-100' };
    }
};

onMounted(() => {
    loadExpenses();
    loadCategories();
    loadBankAccounts();
    loadInvestments();
});
</script>

<template>
    <Head title="Expenses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Expenses</h1>
                    <p class="text-muted-foreground">Track and manage your expenses</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Expense
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-7/8 overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Add New Expense</DialogTitle>
                                <DialogDescription>
                                    Enter the details of your expense.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="title">Title</Label>
                                    <Input 
                                        id="title" 
                                        v-model="form.title" 
                                        placeholder="Expense title"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="amount">Amount</Label>
                                    <Input 
                                        id="amount" 
                                        v-model="form.amount" 
                                        type="number" 
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="expense_date">Date</Label>
                                    <Input 
                                        id="expense_date" 
                                        v-model="form.expense_date" 
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="category">Category</Label>
                                    <select 
                                        id="category" 
                                        v-model="form.category_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">No category</option>
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
                                    <Label for="expense_type">Expense Type</Label>
                                    <select 
                                        id="expense_type" 
                                        v-model="form.expense_type"
                                        @change="form.related_id = ''; form.related_type = ''"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="regular">Regular Expense</option>
                                        <option value="dps_payment">DPS Payment</option>
                                        <option value="fdr_investment">FDR Investment</option>
                                        <option value="loan_payment">Loan Payment</option>
                                    </select>
                                </div>
                                <div v-if="form.expense_type !== 'regular'" class="grid gap-2">
                                    <Label for="related_investment">Related Investment</Label>
                                    <select 
                                        id="related_investment" 
                                        v-model="form.related_id"
                                        @change="updateRelatedType"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Select Investment</option>
                                        <option 
                                            v-for="investment in getFilteredInvestments()" 
                                            :key="investment.id"
                                            :value="investment.id.toString()"
                                        >
                                            {{ investment.title }} ({{ investment.type.toUpperCase() }})
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="bank_account">Bank Account (Optional)</Label>
                                    <select 
                                        id="bank_account" 
                                        v-model="form.bank_account_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">No bank account</option>
                                        <option 
                                            v-for="account in bankAccounts" 
                                            :key="account.id"
                                            :value="account.id.toString()"
                                        >
                                            {{ account.account_name }} - {{ account.bank_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="payment_method">Payment Method</Label>
                                    <Input 
                                        id="payment_method" 
                                        v-model="form.payment_method" 
                                        placeholder="Cash, Card, etc."
                                    />
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
                                <div class="grid gap-2">
                                    <Label for="tags">Tags</Label>
                                    <Input 
                                        id="tags" 
                                        v-model="form.tags" 
                                        placeholder="Comma-separated tags"
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button @click="handleCreate" :disabled="loading">
                                    {{ loading ? 'Creating...' : 'Create Expense' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </DialogOverlay>
                </Dialog>
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
                                    placeholder="Search expenses..."
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
                        <div class="sm:w-48">
                            <select 
                                v-model="selectedExpenseType" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All types</option>
                                <option value="regular">Regular</option>
                                <option value="dps_payment">DPS Payment</option>
                                <option value="fdr_investment">FDR Investment</option>
                                <option value="loan_payment">Loan Payment</option>
                            </select>
                        </div>
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Expenses Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Expense List</CardTitle>
                    <CardDescription>
                        {{ expenses.meta?.total || 0 }} total expenses
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="expenses.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No expenses found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Title</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amount</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Payment Method</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Tags</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="expense in expenses.data" :key="expense.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ expense.title }}</div>
                                            <div v-if="expense.description" class="text-sm text-muted-foreground">
                                                {{ expense.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="expense.category" class="flex items-center gap-2">
                                            <div 
                                                class="w-3 h-3 rounded-full" 
                                                :style="{ backgroundColor: expense.category.color }"
                                            ></div>
                                            {{ expense.category.name }}
                                        </div>
                                        <span v-else class="text-muted-foreground">No category</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getExpenseTypeBadge(expense.expense_type || 'regular').color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent"
                                        >
                                            {{ getExpenseTypeBadge(expense.expense_type || 'regular').text }}
                                        </span>
                                        <div v-if="expense.expense_type !== 'regular' && (expense.dps || expense.fdr || expense.loan)" class="text-xs text-muted-foreground mt-1">
                                            {{ (expense.dps || expense.fdr || expense.loan)?.title }}
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle font-semibold text-red-600">
                                        {{ formatCurrency(expense.amount) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        {{ new Date(expense.expense_date).toLocaleDateString() }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="expense.payment_method" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">
                                            {{ expense.payment_method }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="expense.tags && expense.tags.length > 0" class="flex flex-wrap gap-1">
                                            <span 
                                                v-for="tag in expense.tags" 
                                                :key="tag" 
                                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80"
                                            >
                                                {{ tag }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="handleEdit(expense)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(expense)">
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
                <DialogOverlay>
                <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-7/8 overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Expense</DialogTitle>
                        <DialogDescription>
                            Update the expense details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-title">Title</Label>
                            <Input 
                                id="edit-title" 
                                v-model="form.title" 
                                placeholder="Expense title"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-amount">Amount</Label>
                            <Input 
                                id="edit-amount" 
                                v-model="form.amount" 
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-expense_date">Date</Label>
                            <Input 
                                id="edit-expense_date" 
                                v-model="form.expense_date" 
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
                                <option value="">No category</option>
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
                            <Label for="edit-expense_type">Expense Type</Label>
                            <select 
                                id="edit-expense_type" 
                                v-model="form.expense_type"
                                @change="form.related_id = ''; form.related_type = ''"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="regular">Regular Expense</option>
                                <option value="dps_payment">DPS Payment</option>
                                <option value="fdr_investment">FDR Investment</option>
                                <option value="loan_payment">Loan Payment</option>
                            </select>
                        </div>
                        <div v-if="form.expense_type !== 'regular'" class="grid gap-2">
                            <Label for="edit-related_investment">Related Investment</Label>
                            <select 
                                id="edit-related_investment" 
                                v-model="form.related_id"
                                @change="updateRelatedType"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select Investment</option>
                                <option 
                                    v-for="investment in getFilteredInvestments()" 
                                    :key="investment.id"
                                    :value="investment.id.toString()"
                                >
                                    {{ investment.title }} ({{ investment.type.toUpperCase() }})
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-bank_account">Bank Account (Optional)</Label>
                            <select 
                                id="edit-bank_account" 
                                v-model="form.bank_account_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">No bank account</option>
                                <option 
                                    v-for="account in bankAccounts" 
                                    :key="account.id"
                                    :value="account.id.toString()"
                                >
                                    {{ account.account_name }} - {{ account.bank_name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-payment_method">Payment Method</Label>
                            <Input 
                                id="edit-payment_method" 
                                v-model="form.payment_method" 
                                placeholder="Cash, Card, etc."
                            />
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
                        <div class="grid gap-2">
                            <Label for="edit-tags">Tags</Label>
                            <Input 
                                id="edit-tags" 
                                v-model="form.tags" 
                                placeholder="Comma-separated tags"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="handleUpdate" :disabled="loading">
                            {{ loading ? 'Updating...' : 'Update Expense' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
                </DialogOverlay>
            </Dialog>
        </div>
    </AppLayout>
</template>
