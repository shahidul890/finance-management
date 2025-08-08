<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type Income, type Category, type Client } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Incomes',
        href: '/incomes',
    },
];

const { 
    fetchIncomes, 
    createIncome, 
    updateIncome, 
    deleteIncome, 
    fetchCategories,
    fetchClients,
    loading, 
    formatCurrency 
} = useFinance();

const incomes = ref<{ data: Income[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const categories = ref<Category[]>([]);
const clients = ref<Client[]>([]);
const searchQuery = ref('');
const selectedCategory = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingIncome = ref<Income | null>(null);

// Form data
const form = ref({
    title: '',
    description: '',
    amount: '',
    income_date: new Date().toISOString().split('T')[0],
    category_id: '',
    client_id: '',
    source: '',
    is_recurring: false,
    recurring_period: 'monthly',
    tags: '',
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);

const loadIncomes = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_id = selectedCategory.value;
        
        const data = await fetchIncomes(params);
        incomes.value = data;
    } catch (err) {
        console.error('Failed to load incomes:', err);
    }
};

const loadCategories = async () => {
    try {
        const result = await fetchCategories('income');
        categories.value = result.categories;
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
};

const loadClients = async () => {
    try {
        const result = await fetchClients({ status: 'active' });
        clients.value = result.data;
    } catch (err) {
        console.error('Failed to load clients:', err);
    }
};

const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        amount: '',
        income_date: new Date().toISOString().split('T')[0],
        category_id: '',
        client_id: '',
        source: '',
        is_recurring: false,
        recurring_period: 'monthly',
        tags: '',
    };
};

const handleCreate = async () => {
    try {
        const incomeData: any = { ...form.value };
        if (incomeData.amount) incomeData.amount = parseFloat(incomeData.amount);
        if (incomeData.category_id === '') incomeData.category_id = null;
        if (incomeData.tags) {
            incomeData.tags = incomeData.tags.split(',').map((tag: string) => tag.trim());
        }
        
        await createIncome(incomeData);
        showCreateDialog.value = false;
        resetForm();
        loadIncomes();
        console.log('Income created successfully');
    } catch {
        console.error('Failed to create income');
    }
};

const handleEdit = (income: Income) => {
    editingIncome.value = income;
    form.value = {
        title: income.title,
        description: income.description || '',
        amount: income.amount.toString(),
        income_date: income.income_date,
        category_id: income.category_id?.toString() || '',
        client_id: income.client_id?.toString() || '',
        source: income.source || '',
        is_recurring: income.is_recurring || false,
        recurring_period: income.recurring_period || 'monthly',
        tags: income.tags?.join(', ') || '',
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingIncome.value) return;
    
    try {
        const incomeData: any = { ...form.value };
        if (incomeData.amount) incomeData.amount = parseFloat(incomeData.amount);
        if (incomeData.category_id === '') incomeData.category_id = null;
        if (incomeData.tags) {
            incomeData.tags = incomeData.tags.split(',').map((tag: string) => tag.trim());
        }
        
        await updateIncome(editingIncome.value.id, incomeData);
        showEditDialog.value = false;
        editingIncome.value = null;
        resetForm();
        loadIncomes();
        console.log('Income updated successfully');
    } catch {
        console.error('Failed to update income');
    }
};

const handleDelete = async (income: Income) => {
    if (!confirm('Are you sure you want to delete this income?')) return;
    
    try {
        await deleteIncome(income.id);
        loadIncomes();
        console.log('Income deleted successfully');
    } catch {
        console.error('Failed to delete income');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadIncomes();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadIncomes();
};

const totalPages = computed(() => {
    return incomes.value.meta?.last_page || 1;
});

onMounted(() => {
    loadIncomes();
    loadCategories();
    loadClients();
});
</script>

<template>
    <Head title="Incomes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Incomes</h1>
                    <p class="text-muted-foreground">Track and manage your income sources</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Income
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-7/8 overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Add New Income</DialogTitle>
                                <DialogDescription>
                                    Enter the details of your income.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="title">Title</Label>
                                    <Input 
                                        id="title" 
                                        v-model="form.title" 
                                        placeholder="Enter title"
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
                                    <Label for="income_date">Date</Label>
                                    <Input 
                                        id="income_date" 
                                        v-model="form.income_date" 
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
                                    <Label for="client">Client</Label>
                                    <select 
                                        id="client" 
                                        v-model="form.client_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">No client</option>
                                        <option 
                                            v-for="client in clients" 
                                            :key="client.id"
                                            :value="client.id.toString()"
                                        >
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="source">Source</Label>
                                    <Input 
                                        id="source" 
                                        v-model="form.source" 
                                        placeholder="Income source"
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
                                    {{ loading ? 'Creating...' : 'Create Income' }}
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
                                    placeholder="Search incomes..."
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
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Income Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Income List</CardTitle>
                    <CardDescription>
                        {{ incomes.meta?.total || 0 }} total incomes
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="incomes.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No incomes found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Title</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Category</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Client</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Amount</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Source</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Tags</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="income in incomes.data" :key="income.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ income.title }}</div>
                                            <div v-if="income.description" class="text-sm text-muted-foreground">
                                                {{ income.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="income.category" class="flex items-center gap-2">
                                            <div 
                                                class="w-3 h-3 rounded-full" 
                                                :style="{ backgroundColor: income.category.color }"
                                            ></div>
                                            {{ income.category.name }}
                                        </div>
                                        <span v-else class="text-muted-foreground">No category</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="income.client" class="text-sm">
                                            <div class="font-medium">{{ income.client.name }}</div>
                                            <div v-if="income.client.company" class="text-muted-foreground">{{ income.client.company }}</div>
                                        </div>
                                        <span v-else class="text-muted-foreground">No client</span>
                                    </td>
                                    <td class="p-4 align-middle font-semibold text-green-600">
                                        {{ formatCurrency(income.amount) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        {{ new Date(income.income_date).toLocaleDateString() }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="income.source" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">
                                            {{ income.source }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div v-if="income.tags && income.tags.length > 0" class="flex flex-wrap gap-1">
                                            <span 
                                                v-for="tag in income.tags" 
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
                                            <Button size="sm" variant="outline" @click="handleEdit(income)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(income)">
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
                            <DialogTitle>Edit Income</DialogTitle>
                            <DialogDescription>
                                Update the income details.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="edit-title">Title</Label>
                                <Input 
                                    id="edit-title" 
                                    v-model="form.title" 
                                    placeholder="Income title"
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
                                <Label for="edit-income_date">Date</Label>
                                <Input 
                                    id="edit-income_date" 
                                    v-model="form.income_date" 
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
                                <Label for="edit-client">Client</Label>
                                <select 
                                    id="edit-client" 
                                    v-model="form.client_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">No client</option>
                                    <option 
                                        v-for="client in clients" 
                                        :key="client.id"
                                        :value="client.id.toString()"
                                    >
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-source">Source</Label>
                                <Input 
                                    id="edit-source" 
                                    v-model="form.source" 
                                    placeholder="Income source"
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
                                {{ loading ? 'Updating...' : 'Update Income' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </DialogOverlay>    
            </Dialog>
        </div>
    </AppLayout>
</template>
