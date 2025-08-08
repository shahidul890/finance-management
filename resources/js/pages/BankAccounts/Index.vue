<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type BankAccount } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2, CreditCard, Wallet, TrendingUp, Building } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bank Accounts',
        href: '/bank-accounts',
    },
];

const { 
    fetchBankAccounts, 
    createBankAccount, 
    updateBankAccount, 
    deleteBankAccount, 
    loading, 
    formatCurrency 
} = useFinance();

const bankAccounts = ref<{ data: BankAccount[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const searchQuery = ref('');
const selectedAccountType = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingAccount = ref<BankAccount | null>(null);

// Form data
const form = ref({
    account_name: '',
    account_number: '',
    bank_name: '',
    account_type: 'savings',
    current_balance: '',
    initial_amount: '',
    description: '',
    is_active: true,
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);

const loadBankAccounts = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedAccountType.value) params.account_type = selectedAccountType.value;
        
        const data = await fetchBankAccounts(params);
        bankAccounts.value = data;
    } catch (err) {
        console.error('Failed to load bank accounts:', err);
    }
};

const resetForm = () => {
    form.value = {
        account_name: '',
        account_number: '',
        bank_name: '',
        account_type: 'savings',
        current_balance: '',
        initial_amount: '',
        description: '',
        is_active: true,
    };
};

const handleCreate = async () => {
    try {
        const accountData: any = { ...form.value };
        if (accountData.balance) accountData.balance = parseFloat(accountData.balance);
        if (accountData.initial_amount) accountData.initial_amount = parseFloat(accountData.initial_amount);
        
        await createBankAccount(accountData);
        showCreateDialog.value = false;
        resetForm();
        loadBankAccounts();
        console.log('Bank account created successfully');
    } catch {
        console.error('Failed to create bank account');
    }
};

const handleEdit = (account: BankAccount) => {
    editingAccount.value = account;
    form.value = {
        account_name: account.account_name,
        account_number: account.account_number,
        bank_name: account.bank_name,
        account_type: account.account_type,
        current_balance: account.current_balance.toString(),
        initial_amount: account.initial_amount.toString(),
        description: account.description || '',
        is_active: account.is_active,
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingAccount.value) return;
    
    try {
        const accountData: any = { ...form.value };
        if (accountData.balance) accountData.balance = parseFloat(accountData.balance);
        if (accountData.initial_amount) accountData.initial_amount = parseFloat(accountData.initial_amount);
        
        await updateBankAccount(editingAccount.value.id, accountData);
        showEditDialog.value = false;
        editingAccount.value = null;
        resetForm();
        loadBankAccounts();
        console.log('Bank account updated successfully');
    } catch {
        console.error('Failed to update bank account');
    }
};

const handleDelete = async (account: BankAccount) => {
    if (!confirm('Are you sure you want to delete this bank account?')) return;
    
    try {
        await deleteBankAccount(account.id);
        loadBankAccounts();
        console.log('Bank account deleted successfully');
    } catch {
        console.error('Failed to delete bank account');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadBankAccounts();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadBankAccounts();
};

const getAccountTypeIcon = (type: string) => {
    switch (type) {
        case 'checking': return CreditCard;
        case 'savings': return Wallet;
        case 'credit': return CreditCard;
        case 'investment': return TrendingUp;
        default: return Building;
    }
};

const getAccountTypeColor = (type: string) => {
    switch (type) {
        case 'checking': return 'text-blue-600';
        case 'savings': return 'text-green-600';
        case 'credit': return 'text-orange-600';
        case 'investment': return 'text-purple-600';
        default: return 'text-gray-600';
    }
};

const getStatusBadge = (isActive: boolean) => {
    return isActive 
        ? { text: 'Active', color: 'text-green-600 bg-green-100' }
        : { text: 'Inactive', color: 'text-red-600 bg-red-100' };
};

const totalPages = computed(() => {
    return bankAccounts.value.meta?.last_page || 1;
});

const summary = computed(() => {
    const accounts = bankAccounts.value.data || [];
    return {
        total_accounts: accounts.length,
        total_balance: accounts.reduce((sum, account) => sum + account.current_balance, 0),
        active_accounts: accounts.filter(account => account.is_active).length,
        account_types: new Set(accounts.map(account => account.account_type)).size,
    };
});

onMounted(() => {
    loadBankAccounts();
});
</script>

<template>
    <Head title="Bank Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Bank Accounts</h1>
                    <p class="text-muted-foreground">Manage your bank accounts and track balances</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Account
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-7/8 overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Add New Bank Account</DialogTitle>
                                <DialogDescription>
                                    Enter the details of your bank account.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="account_name">Account Name</Label>
                                    <Input 
                                        id="account_name" 
                                        v-model="form.account_name" 
                                        placeholder="Enter account name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="bank_name">Bank Name</Label>
                                    <Input 
                                        id="bank_name" 
                                        v-model="form.bank_name" 
                                        placeholder="Enter bank name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="account_number">Account Number</Label>
                                    <Input 
                                        id="account_number" 
                                        v-model="form.account_number" 
                                        placeholder="Enter account number"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="account_type">Account Type</Label>
                                    <select 
                                        id="account_type" 
                                        v-model="form.account_type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="savings">Savings</option>
                                        <option value="checking">Checking</option>
                                        <option value="credit">Credit</option>
                                        <option value="investment">Investment</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="initial_amount">Initial Amount</Label>
                                    <Input 
                                        id="initial_amount" 
                                        v-model="form.initial_amount" 
                                        type="number" 
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="balance">Current Balance</Label>
                                    <Input 
                                        id="balance" 
                                        v-model="form.current_balance" 
                                        type="number" 
                                        step="0.01"
                                        placeholder="0.00"
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
                                <div class="flex items-center space-x-2">
                                    <input 
                                        id="is_active" 
                                        v-model="form.is_active" 
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300"
                                    />
                                    <Label for="is_active">Active Account</Label>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button @click="handleCreate" :disabled="loading">
                                    {{ loading ? 'Creating...' : 'Create Account' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </DialogOverlay>
                </Dialog>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <Building class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Accounts</p>
                                <p class="text-2xl font-bold">{{ summary.total_accounts }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <Wallet class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Balance</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(bankAccounts.total_balance || summary.total_balance) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <CreditCard class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Active Accounts</p>
                                <p class="text-2xl font-bold">{{ summary.active_accounts }}</p>
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
                                <p class="text-sm font-medium text-muted-foreground">Account Types</p>
                                <p class="text-2xl font-bold">{{ summary.account_types }}</p>
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
                                    placeholder="Search accounts..."
                                    class="pl-10"
                                    @keyup.enter="handleSearch"
                                />
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select 
                                v-model="selectedAccountType" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All account types</option>
                                <option value="savings">Savings</option>
                                <option value="checking">Checking</option>
                                <option value="credit">Credit</option>
                                <option value="investment">Investment</option>
                            </select>
                        </div>
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bank Accounts Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Account List</CardTitle>
                    <CardDescription>
                        {{ bankAccounts.meta?.total || 0 }} total accounts
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="bankAccounts.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No bank accounts found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Account</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Bank</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Account Number</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Balance</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="account in bankAccounts.data" :key="account.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ account.account_name }}</div>
                                            <div v-if="account.description" class="text-sm text-muted-foreground">
                                                {{ account.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="font-medium">{{ account.bank_name }}</div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <component 
                                                :is="getAccountTypeIcon(account.account_type)" 
                                                :class="getAccountTypeColor(account.account_type)"
                                                class="h-4 w-4"
                                            />
                                            <span class="capitalize">{{ account.account_type }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="font-mono text-sm">{{ account.account_number }}</span>
                                    </td>
                                    <td class="p-4 align-middle font-semibold">
                                        {{ formatCurrency(account.possible_current_balance) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getStatusBadge(account.is_active).color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent"
                                        >
                                            {{ getStatusBadge(account.is_active).text }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="handleEdit(account)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(account)">
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
                        <DialogTitle>Edit Bank Account</DialogTitle>
                        <DialogDescription>
                            Update the account details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-account_name">Account Name</Label>
                            <Input 
                                id="edit-account_name" 
                                v-model="form.account_name" 
                                placeholder="Account name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-bank_name">Bank Name</Label>
                            <Input 
                                id="edit-bank_name" 
                                v-model="form.bank_name" 
                                placeholder="Bank name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-account_number">Account Number</Label>
                            <Input 
                                id="edit-account_number" 
                                v-model="form.account_number" 
                                placeholder="Account number"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-account_type">Account Type</Label>
                            <select 
                                id="edit-account_type" 
                                v-model="form.account_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="savings">Savings</option>
                                <option value="checking">Checking</option>
                                <option value="credit">Credit</option>
                                <option value="investment">Investment</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-initial_amount">Initial Amount</Label>
                            <Input 
                                id="edit-initial_amount" 
                                v-model="form.initial_amount" 
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-balance">Current Balance</Label>
                            <Input 
                                id="edit-balance" 
                                v-model="form.current_balance" 
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
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
                        <div class="flex items-center space-x-2">
                            <input 
                                id="edit-is_active" 
                                v-model="form.is_active" 
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300"
                            />
                            <Label for="edit-is_active">Active Account</Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="handleUpdate" :disabled="loading">
                            {{ loading ? 'Updating...' : 'Update Account' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
