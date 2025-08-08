<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    TrendingUp, 
    TrendingDown, 
    DollarSign, 
    Calculator,
    Plus,
    Search,
    Edit,
    Trash2,
    Calendar,
    Building2
} from 'lucide-vue-next';
import axios from 'axios';

interface Transaction {
    id: number;
    bank_account_id: number;
    type: 'in' | 'out';
    amount: number;
    description: string | null;
    related_model: string | null;
    related_model_id: number | null;
    transaction_date: string;
    created_at: string;
    updated_at: string;
    bank_account: {
        id: number;
        bank_name: string;
        account_name: string;
    };
}

interface BankAccount {
    id: number;
    bank_name: string;
    account_name: string;
}

interface Summary {
    total_transactions: number;
    total_in: number;
    total_out: number;
    net_balance: number;
}

interface PaginatedResponse {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Reactive state
const transactions = ref<PaginatedResponse>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
});
const summary = ref<Summary>({
    total_transactions: 0,
    total_in: 0,
    total_out: 0,
    net_balance: 0
});
const bankAccounts = ref<BankAccount[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const filterType = ref('');
const filterBankAccount = ref('');
const currentPage = ref(1);

// Modal state
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingTransaction = ref<Transaction | null>(null);

// Form state
const form = ref({
    bank_account_id: '',
    type: '',
    amount: '',
    description: '',
    transaction_date: new Date().toISOString().split('T')[0]
});

// Computed
const filteredTransactions = computed(() => transactions.value.data);

// Methods
const fetchTransactions = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: '15'
        });
        
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (filterType.value) params.append('type', filterType.value);
        if (filterBankAccount.value) params.append('bank_account_id', filterBankAccount.value);

        const response = await axios.get(`/api/transactions?${params}`);
        transactions.value = response.data.transactions;
        summary.value = response.data.summary;
        bankAccounts.value = response.data.bank_accounts;
        currentPage.value = page;
    } catch (error) {
        console.error('Error fetching transactions:', error);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        bank_account_id: '',
        type: '',
        amount: '',
        description: '',
        transaction_date: new Date().toISOString().split('T')[0]
    };
};

const createTransaction = async () => {
    try {
        const payload = {
            ...form.value,
            amount: parseFloat(form.value.amount)
        };
        
        await axios.post('/api/transactions', payload);
        showCreateModal.value = false;
        resetForm();
        fetchTransactions(currentPage.value);
    } catch (error) {
        console.error('Error creating transaction:', error);
    }
};

const editTransaction = (transaction: Transaction) => {
    editingTransaction.value = transaction;
    form.value = {
        bank_account_id: transaction.bank_account_id.toString(),
        type: transaction.type,
        amount: transaction.amount.toString(),
        description: transaction.description || '',
        transaction_date: transaction.transaction_date
    };
    showEditModal.value = true;
};

const updateTransaction = async () => {
    if (!editingTransaction.value) return;
    
    try {
        const payload = {
            ...form.value,
            amount: parseFloat(form.value.amount)
        };
        
        await axios.put(`/api/transactions/${editingTransaction.value.id}`, payload);
        showEditModal.value = false;
        resetForm();
        editingTransaction.value = null;
        fetchTransactions(currentPage.value);
    } catch (error) {
        console.error('Error updating transaction:', error);
    }
};

const deleteTransaction = async (transaction: Transaction) => {
    if (!confirm('Are you sure you want to delete this transaction?')) return;
    
    try {
        await axios.delete(`/api/transactions/${transaction.id}`);
        fetchTransactions(currentPage.value);
    } catch (error) {
        console.error('Error deleting transaction:', error);
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const goToPage = (page: number) => {
    fetchTransactions(page);
};

// Lifecycle
onMounted(() => {
    fetchTransactions();
});
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="[{ title: 'Transactions', href: route('transactions.index') }]">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Transactions</h1>
                    <p class="text-muted-foreground">Track all your transactions from here</p>
                </div>
                <Dialog v-model:open="showCreateModal">
                    <DialogTrigger as-child>
                        <Button @click="showCreateModal = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Transaction
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Create New Transaction</DialogTitle>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="bank_account">Bank Account</Label>
                                <select 
                                    v-model="form.bank_account_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select bank account</option>
                                    <option 
                                        v-for="account in bankAccounts" 
                                        :key="account.id" 
                                        :value="account.id.toString()"
                                    >
                                        {{ account.bank_name }} - {{ account.account_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="type">Type</Label>
                                <select 
                                    v-model="form.type"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select type</option>
                                    <option value="in">Income</option>
                                    <option value="out">Expense</option>
                                </select>
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
                                <Label for="transaction_date">Date</Label>
                                <Input 
                                    id="transaction_date" 
                                    v-model="form.transaction_date" 
                                    type="date" 
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <textarea 
                                    id="description" 
                                    v-model="form.description" 
                                    placeholder="Enter transaction description"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                ></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <Button variant="outline" @click="showCreateModal = false">Cancel</Button>
                            <Button @click="createTransaction">Create</Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <Calculator class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Transactions</p>
                                <p class="text-2xl font-bold">{{ summary.total_transactions }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <TrendingUp class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Income</p>
                                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_in) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <TrendingDown class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Expenses</p>
                                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_out) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <DollarSign class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Net Balance</p>
                                <p class="text-2xl font-bold" :class="{
                                    'text-green-600': summary.net_balance >= 0,
                                    'text-red-600': summary.net_balance < 0
                                }">
                                    {{ formatCurrency(summary.net_balance) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search transactions..."
                                    class="pl-8"
                                    @input="fetchTransactions(1)"
                                />
                            </div>
                        </div>
                        <div class="w-full md:w-48">
                            <select 
                                v-model="filterType" 
                                @change="fetchTransactions(1)"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Types</option>
                                <option value="in">Income</option>
                                <option value="out">Expense</option>
                            </select>
                        </div>
                        <div class="w-full md:w-64">
                            <select 
                                v-model="filterBankAccount" 
                                @change="fetchTransactions(1)"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Accounts</option>
                                <option 
                                    v-for="account in bankAccounts" 
                                    :key="account.id" 
                                    :value="account.id.toString()"
                                >
                                    {{ account.bank_name }} - {{ account.account_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Transactions Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Recent Transactions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b transition-colors hover:bg-muted/50">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Bank Account</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Description</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Amount</th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td colspan="6" class="h-24 px-4 align-middle text-center">
                                        Loading transactions...
                                    </td>
                                </tr>
                                <tr v-else-if="filteredTransactions.length === 0">
                                    <td colspan="6" class="h-24 px-4 align-middle text-center">
                                        No transactions found
                                    </td>
                                </tr>
                                <tr v-else v-for="transaction in filteredTransactions" :key="transaction.id" class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                            {{ formatDate(transaction.transaction_date) }}
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 text-muted-foreground" />
                                            <div>
                                                <div class="font-medium">{{ transaction.bank_account.bank_name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ transaction.bank_account.account_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="{
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': transaction.type === 'in',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': transaction.type === 'out'
                                        }">
                                            {{ transaction.type === 'in' ? 'Income' : 'Expense' }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        {{ transaction.description || '-' }}
                                    </td>
                                    <td class="p-4 align-middle text-right font-medium" :class="{
                                        'text-green-600': transaction.type === 'in',
                                        'text-red-600': transaction.type === 'out'
                                    }">
                                        {{ transaction.type === 'in' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="editTransaction(transaction)"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="deleteTransaction(transaction)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex items-center justify-between" v-if="transactions.last_page > 1">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ ((currentPage - 1) * transactions.per_page) + 1 }} to {{ Math.min(currentPage * transactions.per_page, transactions.total) }} of {{ transactions.total }} transactions
                        </div>
                        <div class="flex items-center gap-2">
                            <Button 
                                variant="outline" 
                                size="sm"
                                :disabled="currentPage <= 1"
                                @click="goToPage(currentPage - 1)"
                            >
                                Previous
                            </Button>
                            <span class="text-sm text-muted-foreground">
                                Page {{ currentPage }} of {{ transactions.last_page }}
                            </span>
                            <Button 
                                variant="outline" 
                                size="sm"
                                :disabled="currentPage >= transactions.last_page"
                                @click="goToPage(currentPage + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Edit Modal -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Transaction</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit_bank_account">Bank Account</Label>
                            <select 
                                v-model="form.bank_account_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select bank account</option>
                                <option 
                                    v-for="account in bankAccounts" 
                                    :key="account.id" 
                                    :value="account.id.toString()"
                                >
                                    {{ account.bank_name }} - {{ account.account_name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_type">Type</Label>
                            <select 
                                v-model="form.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select type</option>
                                <option value="in">Income</option>
                                <option value="out">Expense</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_amount">Amount</Label>
                            <Input 
                                id="edit_amount" 
                                v-model="form.amount" 
                                type="number" 
                                step="0.01" 
                                placeholder="0.00" 
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_transaction_date">Date</Label>
                            <Input 
                                id="edit_transaction_date" 
                                v-model="form.transaction_date" 
                                type="date" 
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_description">Description</Label>
                            <textarea 
                                id="edit_description" 
                                v-model="form.description" 
                                placeholder="Enter transaction description"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <Button variant="outline" @click="showEditModal = false">Cancel</Button>
                        <Button @click="updateTransaction">Update</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div> 
    </AppLayout>
</template>