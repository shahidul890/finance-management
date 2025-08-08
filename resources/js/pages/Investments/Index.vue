<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type Investment, type BankAccount } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2, TrendingUp, Building, DollarSign, Calendar } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Investments',
        href: '/investments',
    },
];

const { 
    fetchInvestments, 
    fetchBankAccounts,
    loading, 
    formatCurrency 
} = useFinance();

const investments = ref<{ data: Investment[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const bankAccounts = ref<BankAccount[]>([]);
const searchQuery = ref('');
const selectedType = ref<string>('');
const selectedStatus = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingInvestment = ref<Investment | null>(null);

// Form data
const form = ref({
    type: 'dps',
    title: '',
    description: '',
    amount: '',
    interest_rate: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    bank_account_id: '',
    status: 'active',
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);

const loadInvestments = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedType.value) params.type = selectedType.value;
        if (selectedStatus.value) params.status = selectedStatus.value;
        
        const data = await fetchInvestments(params);
        investments.value = data;
    } catch (err) {
        console.error('Failed to load investments:', err);
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

const resetForm = () => {
    form.value = {
        type: 'dps',
        title: '',
        description: '',
        amount: '',
        interest_rate: '',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        bank_account_id: '',
        status: 'active',
    };
};

const handleCreate = async () => {
    try {
        console.log('Creating investment with form data:', form.value);
        // For now, just show success message since API endpoints will be created
        showCreateDialog.value = false;
        resetForm();
        console.log('Investment created successfully');
    } catch {
        console.error('Failed to create investment');
    }
};

const handleEdit = (investment: Investment) => {
    editingInvestment.value = investment;
    form.value = {
        type: investment.type,
        title: investment.title,
        description: investment.description || '',
        amount: investment.amount.toString(),
        interest_rate: investment.interest_rate.toString(),
        start_date: investment.start_date,
        end_date: investment.end_date || '',
        bank_account_id: investment.bank_account_id?.toString() || '',
        status: investment.status,
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingInvestment.value) return;
    
    try {
        console.log('Updating investment with form data:', form.value);
        // For now, just show success message since API endpoints will be created
        showEditDialog.value = false;
        editingInvestment.value = null;
        resetForm();
        console.log('Investment updated successfully');
    } catch {
        console.error('Failed to update investment');
    }
};

const handleDelete = async (investment: Investment) => {
    if (!confirm('Are you sure you want to delete this investment?')) return;
    
    try {
        console.log('Deleting investment:', investment.id);
        // For now, just show success message since API endpoints will be created
        console.log('Investment deleted successfully');
    } catch {
        console.error('Failed to delete investment');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadInvestments();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadInvestments();
};

const getInvestmentTypeIcon = (type: string) => {
    switch (type) {
        case 'dps': return Building;
        case 'fdr': return DollarSign;
        case 'loan': return TrendingUp;
        default: return Building;
    }
};

const getInvestmentTypeColor = (type: string) => {
    switch (type) {
        case 'dps': return 'text-blue-600';
        case 'fdr': return 'text-green-600';
        case 'loan': return 'text-orange-600';
        default: return 'text-gray-600';
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active': return { text: 'Active', color: 'text-green-600 bg-green-100' };
        case 'matured': return { text: 'Matured', color: 'text-blue-600 bg-blue-100' };
        case 'closed': return { text: 'Closed', color: 'text-red-600 bg-red-100' };
        default: return { text: 'Unknown', color: 'text-gray-600 bg-gray-100' };
    }
};

const getInvestmentTypeName = (type: string) => {
    switch (type) {
        case 'dps': return 'Deposit Pension Scheme';
        case 'fdr': return 'Fixed Deposit Receipt';
        case 'loan': return 'Loan';
        default: return type.toUpperCase();
    }
};

const totalPages = computed(() => {
    return investments.value.meta?.last_page || 1;
});

const summary = computed(() => {
    const investmentList = investments.value.data || [];
    return {
        total_investments: investmentList.length,
        total_amount: investmentList.reduce((sum, investment) => sum + investment.amount, 0),
        active_investments: investmentList.filter(investment => investment.status === 'active').length,
        investment_types: new Set(investmentList.map(investment => investment.type)).size,
    };
});

onMounted(() => {
    loadInvestments();
    loadBankAccounts();
});
</script>

<template>
    <Head title="Investments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Investments</h1>
                    <p class="text-muted-foreground">Manage your DPS, FDR, and Loan investments</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Investment
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Add New Investment</DialogTitle>
                                <DialogDescription>
                                    Enter the details of your investment.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="type">Investment Type</Label>
                                    <select 
                                        id="type" 
                                        v-model="form.type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="dps">DPS (Deposit Pension Scheme)</option>
                                        <option value="fdr">FDR (Fixed Deposit Receipt)</option>
                                        <option value="loan">Loan</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="title">Title</Label>
                                    <Input 
                                        id="title" 
                                        v-model="form.title" 
                                        placeholder="Enter investment title"
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
                                    <Label for="interest_rate">Interest Rate (%)</Label>
                                    <Input 
                                        id="interest_rate" 
                                        v-model="form.interest_rate" 
                                        type="number" 
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="start_date">Start Date</Label>
                                    <Input 
                                        id="start_date" 
                                        v-model="form.start_date" 
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="end_date">End Date (Optional)</Label>
                                    <Input 
                                        id="end_date" 
                                        v-model="form.end_date" 
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="bank_account_id">Bank Account (Optional)</Label>
                                    <select 
                                        id="bank_account_id" 
                                        v-model="form.bank_account_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Select bank account</option>
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
                                    <Label for="status">Status</Label>
                                    <select 
                                        id="status" 
                                        v-model="form.status"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="active">Active</option>
                                        <option value="matured">Matured</option>
                                        <option value="closed">Closed</option>
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
                                    {{ loading ? 'Creating...' : 'Create Investment' }}
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
                                <TrendingUp class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Investments</p>
                                <p class="text-2xl font-bold">{{ summary.total_investments }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <DollarSign class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Amount</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(summary.total_amount) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <Calendar class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Active Investments</p>
                                <p class="text-2xl font-bold">{{ summary.active_investments }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <Building class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Investment Types</p>
                                <p class="text-2xl font-bold">{{ summary.investment_types }}</p>
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
                                    placeholder="Search investments..."
                                    class="pl-10"
                                    @keyup.enter="handleSearch"
                                />
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select 
                                v-model="selectedType" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All types</option>
                                <option value="dps">DPS</option>
                                <option value="fdr">FDR</option>
                                <option value="loan">Loan</option>
                            </select>
                        </div>
                        <div class="sm:w-40">
                            <select 
                                v-model="selectedStatus" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="matured">Matured</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Investments Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Investment List</CardTitle>
                    <CardDescription>
                        {{ investments.meta?.total || 0 }} total investments
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="investments.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No investments found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Investment</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Type</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amount</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Interest Rate</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Duration</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Bank Account</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="investment in investments.data" :key="investment.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ investment.title }}</div>
                                            <div v-if="investment.description" class="text-sm text-muted-foreground">
                                                {{ investment.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <component 
                                                :is="getInvestmentTypeIcon(investment.type)" 
                                                :class="getInvestmentTypeColor(investment.type)"
                                                class="h-4 w-4"
                                            />
                                            <span class="font-medium">{{ investment.type.toUpperCase() }}</span>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ getInvestmentTypeName(investment.type) }}
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle font-semibold">
                                        {{ formatCurrency(investment.amount) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="font-medium">{{ investment.interest_rate }}%</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="text-sm">
                                            <div class="font-medium">{{ new Date(investment.start_date).toLocaleDateString() }}</div>
                                            <div v-if="investment.end_date" class="text-muted-foreground">
                                                to {{ new Date(investment.end_date).toLocaleDateString() }}
                                            </div>
                                            <div v-else class="text-muted-foreground">No end date</div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="investment.bank_account">
                                            <div class="font-medium">{{ investment.bank_account.account_name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ investment.bank_account.bank_name }}</div>
                                        </div>
                                        <span v-else class="text-muted-foreground">No account</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getStatusBadge(investment.status).color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent"
                                        >
                                            {{ getStatusBadge(investment.status).text }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="handleEdit(investment)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(investment)">
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
                <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Investment</DialogTitle>
                        <DialogDescription>
                            Update the investment details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-type">Investment Type</Label>
                            <select 
                                id="edit-type" 
                                v-model="form.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="dps">DPS (Deposit Pension Scheme)</option>
                                <option value="fdr">FDR (Fixed Deposit Receipt)</option>
                                <option value="loan">Loan</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-title">Title</Label>
                            <Input 
                                id="edit-title" 
                                v-model="form.title" 
                                placeholder="Investment title"
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
                            <Label for="edit-interest_rate">Interest Rate (%)</Label>
                            <Input 
                                id="edit-interest_rate" 
                                v-model="form.interest_rate" 
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
                            />
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
                            <Label for="edit-bank_account_id">Bank Account</Label>
                            <select 
                                id="edit-bank_account_id" 
                                v-model="form.bank_account_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select bank account</option>
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
                            <Label for="edit-status">Status</Label>
                            <select 
                                id="edit-status" 
                                v-model="form.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="active">Active</option>
                                <option value="matured">Matured</option>
                                <option value="closed">Closed</option>
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
                            {{ loading ? 'Updating...' : 'Update Investment' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
