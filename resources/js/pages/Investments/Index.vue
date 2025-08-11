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
    createInvestment,
    updateInvestment,
    deleteInvestment,
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
    // DPS fields
    dps_name: '',
    dps_number: '',
    monthly_installment: '',
    tenure_months: '',
    interest_rate: '',
    start_date: new Date().toISOString().split('T')[0],
    maturity_date: '',
    
    // FDR fields
    fdr_name: '',
    fdr_number: '',
    principal_amount: '', // Used for FDR
    fdr_tenure_months: '',
    fdr_interest_rate: '',
    fdr_start_date: new Date().toISOString().split('T')[0],
    fdr_maturity_date: '',
    
    // Loan fields
    loan_name: '',
    loan_number: '',
    loan_type: '',
    loan_principal_amount: '', // Different from FDR principal_amount - will map to principal_amount for loan
    loan_tenure_months: '',
    loan_interest_rate: '',
    loan_start_date: new Date().toISOString().split('T')[0],
    monthly_emi: '',
    
    // Common fields
    bank_account_id: '',
    status: 'active',
    additional_info: '',
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
        bankAccounts.value = Array.isArray(result?.data) ? result.data : Array.isArray(result) ? result : [];
    } catch (err) {
        console.error('Failed to load bank accounts:', err);
    }
};

const resetForm = () => {
    form.value = {
        type: 'dps',
        // DPS fields
        dps_name: '',
        dps_number: '',
        monthly_installment: '',
        tenure_months: '',
        interest_rate: '',
        start_date: new Date().toISOString().split('T')[0],
        maturity_date: '',
        
        // FDR fields
        fdr_name: '',
        fdr_number: '',
        principal_amount: '',
        fdr_tenure_months: '',
        fdr_interest_rate: '',
        fdr_start_date: new Date().toISOString().split('T')[0],
        fdr_maturity_date: '',
        
        // Loan fields
        loan_name: '',
        loan_number: '',
        loan_type: '',
        loan_principal_amount: '',
        loan_tenure_months: '',
        loan_interest_rate: '',
        loan_start_date: new Date().toISOString().split('T')[0],
        monthly_emi: '',
        
        // Common fields
        bank_account_id: '',
        status: 'active',
        additional_info: '',
    };
};

const prepareFormData = () => {
    const data: any = {
        type: form.value.type,
        bank_account_id: form.value.bank_account_id || null,
        status: form.value.status,
        additional_info: form.value.additional_info ? { notes: form.value.additional_info } : null,
    };

    if (form.value.type === 'dps') {
        Object.assign(data, {
            dps_name: form.value.dps_name,
            dps_number: form.value.dps_number,
            monthly_installment: parseFloat(form.value.monthly_installment) || 0,
            tenure_months: parseInt(form.value.tenure_months) || 0,
            interest_rate: parseFloat(form.value.interest_rate) || 0,
            start_date: form.value.start_date,
            maturity_date: form.value.maturity_date || null,
        });
    } else if (form.value.type === 'fdr') {
        Object.assign(data, {
            fdr_name: form.value.fdr_name,
            fdr_number: form.value.fdr_number,
            principal_amount: parseFloat(form.value.principal_amount) || 0,
            fdr_tenure_months: parseInt(form.value.fdr_tenure_months) || 0,
            fdr_interest_rate: parseFloat(form.value.fdr_interest_rate) || 0,
            fdr_start_date: form.value.fdr_start_date,
            fdr_maturity_date: form.value.fdr_maturity_date || null,
        });
    } else if (form.value.type === 'loan') {
        Object.assign(data, {
            loan_name: form.value.loan_name,
            loan_number: form.value.loan_number,
            loan_type: form.value.loan_type,
            principal_amount: parseFloat(form.value.loan_principal_amount) || 0,
            loan_tenure_months: parseInt(form.value.loan_tenure_months) || 0,
            loan_interest_rate: parseFloat(form.value.loan_interest_rate) || 0,
            loan_start_date: form.value.loan_start_date,
            monthly_emi: parseFloat(form.value.monthly_emi) || 0,
        });
    }

    return data;
};

const handleCreate = async () => {
    try {
        const formData = prepareFormData();
        await createInvestment(formData);
        showCreateDialog.value = false;
        resetForm();
        await loadInvestments();
        console.log('Investment created successfully');
    } catch (error) {
        console.error('Failed to create investment:', error);
    }
};

const handleEdit = (investment: Investment) => {
    editingInvestment.value = investment;
    // Extract the proper form data based on investment type
    const details = investment.details || investment;

    form.value = {
        type: investment.type || investment.type,
        // DPS fields
        dps_name: details.dps_name || '',
        dps_number: details.dps_number || '',
        monthly_installment: details.monthly_installment?.toString() || '',
        tenure_months: details.tenure_months?.toString() || '',
        interest_rate: details.interest_rate?.toString() || '',
        start_date: details.start_date || '',
        maturity_date: details.maturity_date || '',
        
        // FDR fields
        fdr_name: details.fdr_name || '',
        fdr_number: details.fdr_number || '',
        principal_amount: details.principal_amount?.toString() || '',
        fdr_tenure_months: details.tenure_months?.toString() || '',
        fdr_interest_rate: details.interest_rate?.toString() || '',
        fdr_start_date: details.start_date || '',
        fdr_maturity_date: details.maturity_date || '',
        
        // Loan fields
        loan_name: details.loan_name || '',
        loan_number: details.loan_number || '',
        loan_type: details.loan_type || '',
        loan_principal_amount: details.principal_amount?.toString() || '',
        loan_tenure_months: details.tenure_months?.toString() || '',
        loan_interest_rate: details.interest_rate?.toString() || '',
        loan_start_date: details.start_date || '',
        monthly_emi: details.monthly_emi?.toString() || '',
        
        // Common fields
        bank_account_id: details.bank_account_id?.toString() || '',
        status: details.status || 'active',
        additional_info: details.additional_info || '',
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingInvestment.value) return;
    
    try {
        const formData = prepareFormData();
        await updateInvestment(editingInvestment.value.id, formData);
        showEditDialog.value = false;
        editingInvestment.value = null;
        resetForm();
        await loadInvestments();
        console.log('Investment updated successfully');
    } catch (error) {
        console.error('Failed to update investment:', error);
    }
};

const handleDelete = async (investment: Investment) => {
    if (!confirm('Are you sure you want to delete this investment?')) return;
    
    try {
        await deleteInvestment(investment.id);
        await loadInvestments();
        console.log('Investment deleted successfully');
    } catch (error) {
        console.error('Failed to delete investment:', error);
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
        investment_types: new Set(investmentList.map(investment => investment.type || investment.type)).size,
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
                        <Button @click="resetForm">
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
                                
                                <!-- DPS Fields -->
                                <template v-if="form.type === 'dps'">
                                    <div class="grid gap-2">
                                        <Label for="dps_name">DPS Name</Label>
                                        <Input 
                                            id="dps_name" 
                                            v-model="form.dps_name" 
                                            placeholder="Enter DPS name"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="dps_number">DPS Number (Optional)</Label>
                                        <Input 
                                            id="dps_number" 
                                            v-model="form.dps_number" 
                                            placeholder="Enter DPS number"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="monthly_installment">Monthly Installment</Label>
                                        <Input 
                                            id="monthly_installment" 
                                            v-model="form.monthly_installment" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="tenure_months">Tenure (Months)</Label>
                                        <Input 
                                            id="tenure_months" 
                                            v-model="form.tenure_months" 
                                            type="number"
                                            placeholder="24"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="interest_rate">Interest Rate (%)</Label>
                                        <Input 
                                            id="interest_rate" 
                                            v-model="form.interest_rate" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="8.5"
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
                                        <Label for="maturity_date">Maturity Date (Optional)</Label>
                                        <Input 
                                            id="maturity_date" 
                                            v-model="form.maturity_date" 
                                            type="date"
                                        />
                                    </div>
                                </template>

                                <!-- FDR Fields -->
                                <template v-if="form.type === 'fdr'">
                                    <div class="grid gap-2">
                                        <Label for="fdr_name">FDR Name</Label>
                                        <Input 
                                            id="fdr_name" 
                                            v-model="form.fdr_name" 
                                            placeholder="Enter FDR name"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fdr_number">FDR Number (Optional)</Label>
                                        <Input 
                                            id="fdr_number" 
                                            v-model="form.fdr_number" 
                                            placeholder="Enter FDR number"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="principal_amount">Principal Amount</Label>
                                        <Input 
                                            id="principal_amount" 
                                            v-model="form.principal_amount" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fdr_tenure_months">Tenure (Months)</Label>
                                        <Input 
                                            id="fdr_tenure_months" 
                                            v-model="form.fdr_tenure_months" 
                                            type="number"
                                            placeholder="12"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fdr_interest_rate">Interest Rate (%)</Label>
                                        <Input 
                                            id="fdr_interest_rate" 
                                            v-model="form.fdr_interest_rate" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="7.5"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fdr_start_date">Start Date</Label>
                                        <Input 
                                            id="fdr_start_date" 
                                            v-model="form.fdr_start_date" 
                                            type="date"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="fdr_maturity_date">Maturity Date (Optional)</Label>
                                        <Input 
                                            id="fdr_maturity_date" 
                                            v-model="form.fdr_maturity_date" 
                                            type="date"
                                        />
                                    </div>
                                </template>

                                <!-- Loan Fields -->
                                <template v-if="form.type === 'loan'">
                                    <div class="grid gap-2">
                                        <Label for="loan_name">Loan Name</Label>
                                        <Input 
                                            id="loan_name" 
                                            v-model="form.loan_name" 
                                            placeholder="Enter loan name"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_number">Loan Number (Optional)</Label>
                                        <Input 
                                            id="loan_number" 
                                            v-model="form.loan_number" 
                                            placeholder="Enter loan number"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_type">Loan Type</Label>
                                        <Input 
                                            id="loan_type" 
                                            v-model="form.loan_type" 
                                            placeholder="e.g., Personal, Home, Car"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_principal_amount">Principal Amount</Label>
                                        <Input 
                                            id="loan_principal_amount" 
                                            v-model="form.loan_principal_amount" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_tenure_months">Tenure (Months)</Label>
                                        <Input 
                                            id="loan_tenure_months" 
                                            v-model="form.loan_tenure_months" 
                                            type="number"
                                            placeholder="60"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_interest_rate">Interest Rate (%)</Label>
                                        <Input 
                                            id="loan_interest_rate" 
                                            v-model="form.loan_interest_rate" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="12.5"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="loan_start_date">Start Date</Label>
                                        <Input 
                                            id="loan_start_date" 
                                            v-model="form.loan_start_date" 
                                            type="date"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="monthly_emi">Monthly EMI</Label>
                                        <Input 
                                            id="monthly_emi" 
                                            v-model="form.monthly_emi" 
                                            type="number" 
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </template>

                                <!-- Common Fields -->
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
                                    <Label for="additional_info">Additional Information (Optional)</Label>
                                    <textarea 
                                        id="additional_info" 
                                        v-model="form.additional_info" 
                                        placeholder="Optional notes or description"
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
                                            <div v-if="investment.details?.additional_info" class="text-sm text-muted-foreground">
                                                {{ investment.details.additional_info }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <component 
                                                :is="getInvestmentTypeIcon(investment.type || investment.type)" 
                                                :class="getInvestmentTypeColor(investment.type || investment.type)"
                                                class="h-4 w-4"
                                            />
                                            <span class="font-medium">{{ (investment.type || investment.type).toUpperCase() }}</span>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ getInvestmentTypeName(investment.type || investment.type) }}
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle font-semibold">
                                        {{ formatCurrency(investment.amount) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="font-medium">{{ investment.details?.interest_rate || 0 }}%</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="text-sm">
                                            <div class="font-medium">{{ new Date(investment.details?.start_date || investment.created_at).toLocaleDateString() }}</div>
                                            <div v-if="investment.details?.maturity_date || investment.details?.end_date" class="text-muted-foreground">
                                                to {{ new Date(investment.details.maturity_date || investment.details.end_date).toLocaleDateString() }}
                                            </div>
                                            <div v-else class="text-muted-foreground">No end date</div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="investment.bank_account">
                                            <div class="font-medium">{{ investment.bank_account.account_number }}</div>
                                            <div class="text-sm text-muted-foreground">{{ investment.bank_account.bank_name }}</div>
                                        </div>
                                        <span v-else class="text-muted-foreground">No account</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getStatusBadge(investment.details.status).color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent"
                                        >
                                            {{ getStatusBadge(investment.details.status).text }}
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
                        
                        <!-- DPS Fields -->
                        <template v-if="form.type === 'dps'">
                            <div class="grid gap-2">
                                <Label for="edit-dps_name">DPS Name</Label>
                                <Input 
                                    id="edit-dps_name" 
                                    v-model="form.dps_name" 
                                    placeholder="Enter DPS name"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-dps_number">DPS Number (Optional)</Label>
                                <Input 
                                    id="edit-dps_number" 
                                    v-model="form.dps_number" 
                                    placeholder="Enter DPS number"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-monthly_installment">Monthly Installment</Label>
                                <Input 
                                    id="edit-monthly_installment" 
                                    v-model="form.monthly_installment" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-tenure_months">Tenure (Months)</Label>
                                <Input 
                                    id="edit-tenure_months" 
                                    v-model="form.tenure_months" 
                                    type="number"
                                    placeholder="24"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-interest_rate">Interest Rate (%)</Label>
                                <Input 
                                    id="edit-interest_rate" 
                                    v-model="form.interest_rate" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="8.5"
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
                                <Label for="edit-maturity_date">Maturity Date (Optional)</Label>
                                <Input 
                                    id="edit-maturity_date" 
                                    v-model="form.maturity_date" 
                                    type="date"
                                />
                            </div>
                        </template>

                        <!-- FDR Fields -->
                        <template v-if="form.type === 'fdr'">
                            <div class="grid gap-2">
                                <Label for="edit-fdr_name">FDR Name</Label>
                                <Input 
                                    id="edit-fdr_name" 
                                    v-model="form.fdr_name" 
                                    placeholder="Enter FDR name"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-fdr_number">FDR Number (Optional)</Label>
                                <Input 
                                    id="edit-fdr_number" 
                                    v-model="form.fdr_number" 
                                    placeholder="Enter FDR number"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-principal_amount">Principal Amount</Label>
                                <Input 
                                    id="edit-principal_amount" 
                                    v-model="form.principal_amount" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-fdr_tenure_months">Tenure (Months)</Label>
                                <Input 
                                    id="edit-fdr_tenure_months" 
                                    v-model="form.fdr_tenure_months" 
                                    type="number"
                                    placeholder="12"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-fdr_interest_rate">Interest Rate (%)</Label>
                                <Input 
                                    id="edit-fdr_interest_rate" 
                                    v-model="form.fdr_interest_rate" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="7.5"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-fdr_start_date">Start Date</Label>
                                <Input 
                                    id="edit-fdr_start_date" 
                                    v-model="form.fdr_start_date" 
                                    type="date"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-fdr_maturity_date">Maturity Date (Optional)</Label>
                                <Input 
                                    id="edit-fdr_maturity_date" 
                                    v-model="form.fdr_maturity_date" 
                                    type="date"
                                />
                            </div>
                        </template>

                        <!-- Loan Fields -->
                        <template v-if="form.type === 'loan'">
                            <div class="grid gap-2">
                                <Label for="edit-loan_name">Loan Name</Label>
                                <Input 
                                    id="edit-loan_name" 
                                    v-model="form.loan_name" 
                                    placeholder="Enter loan name"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_number">Loan Number (Optional)</Label>
                                <Input 
                                    id="edit-loan_number" 
                                    v-model="form.loan_number" 
                                    placeholder="Enter loan number"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_type">Loan Type</Label>
                                <Input 
                                    id="edit-loan_type" 
                                    v-model="form.loan_type" 
                                    placeholder="e.g., Personal, Home, Car"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_principal_amount">Principal Amount</Label>
                                <Input 
                                    id="edit-loan_principal_amount" 
                                    v-model="form.loan_principal_amount" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_tenure_months">Tenure (Months)</Label>
                                <Input 
                                    id="edit-loan_tenure_months" 
                                    v-model="form.loan_tenure_months" 
                                    type="number"
                                    placeholder="60"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_interest_rate">Interest Rate (%)</Label>
                                <Input 
                                    id="edit-loan_interest_rate" 
                                    v-model="form.loan_interest_rate" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="12.5"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-loan_start_date">Start Date</Label>
                                <Input 
                                    id="edit-loan_start_date" 
                                    v-model="form.loan_start_date" 
                                    type="date"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-monthly_emi">Monthly EMI</Label>
                                <Input 
                                    id="edit-monthly_emi" 
                                    v-model="form.monthly_emi" 
                                    type="number" 
                                    step="0.01"
                                    placeholder="0.00"
                                />
                            </div>
                        </template>

                        <!-- Common Fields -->
                        <div class="grid gap-2">
                            <Label for="edit-bank_account_id">Bank Account (Optional)</Label>
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
                            <Label for="edit-additional_info">Additional Information (Optional)</Label>
                            <textarea 
                                id="edit-additional_info" 
                                v-model="form.additional_info" 
                                placeholder="Optional notes or description"
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
