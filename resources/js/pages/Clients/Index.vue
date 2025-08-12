<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import { useFinance, type Client } from '@/composables/useFinance';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogOverlay, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Search, Filter, Edit, Trash2, User, Building2, Phone, Mail, Calendar, Recycle } from 'lucide-vue-next';
import { DateRangePicker } from '@/components/ui/daterangepicker';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/clients',
    },
];

const { 
    fetchClients, 
    createClient, 
    updateClient, 
    deleteClient,
    loading, 
    formatCurrency 
} = useFinance();

const clients = ref<{ data: Client[]; meta: any; links: any }>({ data: [], meta: {}, links: {} });
const searchQuery = ref('');
const selectedStatus = ref<string>('');
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingClient = ref<Client | null>(null);

// Form data
const form = ref({
    name: '',
    email: '',
    phone: '',
    address: '',
    company: '',
    notes: '',
    status: 'active',
});

// Pagination
const currentPage = ref(1);
const perPage = ref(15);
const dateRange = ref<{start: Date, end: Date} | null>(null);
const dateRangeType = ref();

const loadClients = async () => {
    try {
        const params: any = {
            page: currentPage.value,
            per_page: perPage.value,
        };
        
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedStatus.value) params.status = selectedStatus.value;
        if(dateRange.value){
            params.start_date = dateRange.value.start.toISOString().split('T')[0];
            params.end_date = dateRange.value.end.toISOString().split('T')[0];
        }

        params.date_range = dateRangeType.value;
        
        const data = await fetchClients(params);
        clients.value = data;
    } catch (err) {
        console.error('Failed to load clients:', err);
    }
};

const onDateRangeChange = (range: { start: Date; end: Date } | null) => {
    dateRangeType.value = "custom";
    dateRange.value = range;
};

const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        phone: '',
        address: '',
        company: '',
        notes: '',
        status: 'active',
    };
};

const handleCreate = async () => {
    try {
        await createClient(form.value);
        showCreateDialog.value = false;
        resetForm();
        loadClients();
        console.log('Client created successfully');
    } catch {
        console.error('Failed to create client');
    }
};

const handleEdit = (client: Client) => {
    editingClient.value = client;
    form.value = {
        name: client.name,
        email: client.email || '',
        phone: client.phone || '',
        address: client.address || '',
        company: client.company || '',
        notes: client.notes || '',
        status: client.status,
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingClient.value) return;
    
    try {
        await updateClient(editingClient.value.id, form.value);
        showEditDialog.value = false;
        editingClient.value = null;
        resetForm();
        loadClients();
        console.log('Client updated successfully');
    } catch {
        console.error('Failed to update client');
    }
};

const handleDelete = async (client: Client) => {
    if (!confirm('Are you sure you want to delete this client?')) return;
    
    try {
        await deleteClient(client.id);
        loadClients();
        console.log('Client deleted successfully');
    } catch {
        console.error('Failed to delete client');
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    loadClients();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    loadClients();
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active': return { text: 'Active', color: 'text-green-600 bg-green-100' };
        case 'inactive': return { text: 'Inactive', color: 'text-red-600 bg-red-100' };
        default: return { text: 'Unknown', color: 'text-gray-600 bg-gray-100' };
    }
};

const totalPages = computed(() => {
    return clients.value.last_page || 1;
});

const summary = computed(() => {
    const clientList = clients.value.data || [];
    return {
        total_clients: clientList.length,
        active_clients: clientList.filter(client => client.status === 'active').length,
        inactive_clients: clientList.filter(client => client.status === 'inactive').length,
        total_income: clientList.reduce((sum, client) => {
            const clientIncome = client.incomes?.reduce((incSum, income) => {
                const amount = parseFloat(income.amount?.toString() || '0');
                return incSum + (isNaN(amount) ? 0 : amount);
            }, 0) || 0;
            return sum + clientIncome;
        }, 0),
    };
});

const clearFilter = () => {
    dateRange.value = null;
    dateRangeType.value = null;
    loadClients();
}

const handleThisMonthFilter = () => {
    dateRangeType.value = "month";
    loadClients();
}

onMounted(() => {
    loadClients();
});
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Clients</h1>
                    <p class="text-muted-foreground">Manage your clients and their information</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Client
                        </Button>
                    </DialogTrigger>
                    <DialogOverlay>
                        <DialogContent class="sm:max-w-[425px] md:max-w-2xl max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Add New Client</DialogTitle>
                                <DialogDescription>
                                    Enter the details of your client.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="name">Name *</Label>
                                    <Input 
                                        id="name" 
                                        v-model="form.name" 
                                        placeholder="Client name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="email">Email</Label>
                                    <Input 
                                        id="email" 
                                        v-model="form.email" 
                                        type="email"
                                        placeholder="client@example.com"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input 
                                        id="phone" 
                                        v-model="form.phone" 
                                        placeholder="+1234567890"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="company">Company</Label>
                                    <Input 
                                        id="company" 
                                        v-model="form.company" 
                                        placeholder="Company name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="address">Address</Label>
                                    <textarea 
                                        id="address" 
                                        v-model="form.address" 
                                        placeholder="Client address"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    ></textarea>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="status">Status</Label>
                                    <select 
                                        id="status" 
                                        v-model="form.status"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="notes">Notes</Label>
                                    <textarea 
                                        id="notes" 
                                        v-model="form.notes" 
                                        placeholder="Additional notes about the client"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    ></textarea>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button @click="handleCreate" :disabled="loading">
                                    {{ loading ? 'Creating...' : 'Create Client' }}
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
                                <User class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Clients</p>
                                <p class="text-2xl font-bold">{{ summary.total_clients }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <User class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Active Clients</p>
                                <p class="text-2xl font-bold">{{ summary.active_clients }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <User class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Inactive Clients</p>
                                <p class="text-2xl font-bold">{{ summary.inactive_clients }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <Building2 class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Income</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(summary.total_income) }}</p>
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
                                    placeholder="Search clients..."
                                    class="pl-10"
                                    @keyup.enter="handleSearch"
                                />
                            </div>
                        </div>
                        <div>
                            <DateRangePicker @update:modelValue="onDateRangeChange" />
                        </div>
                        <div class="">
                            <Button @click="handleThisMonthFilter" variant="outline">
                                <Calendar class="h-4 w-4 mr-2" />
                                This Month
                            </Button>
                        </div>
                        <div class="sm:w-40">
                            <select 
                                v-model="selectedStatus" 
                                @change="handleSearch"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <Button @click="handleSearch" variant="outline">
                            <Filter class="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                        <Button @click="clearFilter" variant="outline">
                            <Recycle class="h-4 w-4 mr-2" />
                            Clear
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Clients Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Client List</CardTitle>
                    <CardDescription>
                        {{ clients.total || 0 }} total clients
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                    
                    <div v-else-if="clients.data.length === 0" class="text-center py-8 text-muted-foreground">
                        No clients found
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Client</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Contact</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Company</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Total Income</th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="client in clients.data" :key="client.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <div class="font-medium">{{ client.name }}</div>
                                            <div v-if="client.address" class="text-sm text-muted-foreground">
                                                {{ client.address }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="space-y-1">
                                            <div v-if="client.email" class="flex items-center gap-2 text-sm">
                                                <Mail class="h-3 w-3 text-muted-foreground" />
                                                {{ client.email }}
                                            </div>
                                            <div v-if="client.phone" class="flex items-center gap-2 text-sm">
                                                <Phone class="h-3 w-3 text-muted-foreground" />
                                                {{ client.phone }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="client.company" class="font-medium">{{ client.company }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span 
                                            :class="getStatusBadge(client.status).color"
                                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent"
                                        >
                                            {{ getStatusBadge(client.status).text }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle font-semibold text-green-600">
                                        {{ formatCurrency(client.incomes?.reduce((sum, income) => sum + parseFloat(income.amount), 0) || 0) }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" @click="handleEdit(client)">
                                                <Edit class="h-3 w-3" />
                                            </Button>
                                            <Button size="sm" variant="outline" @click="handleDelete(client)">
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
                        <DialogTitle>Edit Client</DialogTitle>
                        <DialogDescription>
                            Update the client details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-name">Name *</Label>
                            <Input 
                                id="edit-name" 
                                v-model="form.name" 
                                placeholder="Client name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-email">Email</Label>
                            <Input 
                                id="edit-email" 
                                v-model="form.email" 
                                type="email"
                                placeholder="client@example.com"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-phone">Phone</Label>
                            <Input 
                                id="edit-phone" 
                                v-model="form.phone" 
                                placeholder="+1234567890"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-company">Company</Label>
                            <Input 
                                id="edit-company" 
                                v-model="form.company" 
                                placeholder="Company name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-address">Address</Label>
                            <textarea 
                                id="edit-address" 
                                v-model="form.address" 
                                placeholder="Client address"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-status">Status</Label>
                            <select 
                                id="edit-status" 
                                v-model="form.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-notes">Notes</Label>
                            <textarea 
                                id="edit-notes" 
                                v-model="form.notes" 
                                placeholder="Additional notes about the client"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="handleUpdate" :disabled="loading">
                            {{ loading ? 'Updating...' : 'Update Client' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
