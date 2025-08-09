<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useFinance, type Category } from '@/composables/useFinance';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Edit, Trash2, FolderOpen } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

const { 
    fetchCategories,
    fetchParentCategories,
    createCategory, 
    updateCategory, 
    deleteCategory,
    loading
} = useFinance();

const categories = ref<Category[]>([]);
const parent_categories = ref<Category[]>([]);
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingCategory = ref<Category | null>(null);

// Form data
const form = ref({
    name: '',
    description: '',
    parent_id: '',
    type: 'expense' as 'expense' | 'income' | 'both',
    color: '#3B82F6',
});

const loadCategories = async () => {
    try {
        const result = await fetchCategories();
        const httpParent = await fetchParentCategories();
        categories.value = result.categories;
        parent_categories.value = httpParent.categories;
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
};

const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        parent_id: '',
        type: 'expense',
        color: '#3B82F6',
    };
};

const handleCreate = async () => {
    try {
        await createCategory(form.value);
        showCreateDialog.value = false;
        resetForm();
        loadCategories();
        alert('Category created successfully');
    } catch {
        alert('Failed to create category');
    }
};

const handleEdit = (category: Category) => {
    editingCategory.value = category;
    form.value = {
        name: category.name,
        description: category.description || '',
        parent_id: category.parent_id ? category.parent_id.toString() : '',
        type: category.type,
        color: category.color,
    };
    showEditDialog.value = true;
};

const handleUpdate = async () => {
    if (!editingCategory.value) return;
    
    try {
        await updateCategory(editingCategory.value.id, form.value);
        showEditDialog.value = false;
        editingCategory.value = null;
        resetForm();
        loadCategories();
        alert('Category updated successfully');
    } catch {
        alert('Failed to update category');
    }
};

const handleDelete = async (category: Category) => {
    if (!confirm('Are you sure you want to delete this category?')) return;
    
    try {
        await deleteCategory(category.id);
        loadCategories();
        alert('Category deleted successfully');
    } catch {
        alert('Failed to delete category');
    }
};

const getTypeColor = (type: string) => {
    switch (type) {
        case 'income': return 'text-green-600 bg-green-50';
        case 'expense': return 'text-red-600 bg-red-50';
        case 'both': return 'text-blue-600 bg-blue-50';
        default: return 'text-gray-600 bg-gray-50';
    }
};

onMounted(() => {
    loadCategories();
});
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Categories</h1>
                    <p class="text-muted-foreground">Organize your expenses and incomes</p>
                </div>
                
                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Add Category
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Add New Category</DialogTitle>
                            <DialogDescription>
                                Create a new category to organize your transactions.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input 
                                    id="name" 
                                    v-model="form.name" 
                                    placeholder="Category name"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="name">Parent Category</Label>
                                <select 
                                    id="type" 
                                    v-model="form.parent_id" 
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">No Parent category</option>
                                    <option 
                                        v-for="parent in parent_categories" 
                                        :key="parent.id"
                                        :value="parent.id.toString()"
                                    >
                                        {{  parent.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <Input 
                                    id="description" 
                                    v-model="form.description" 
                                    placeholder="Optional description"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="type">Type</Label>
                                <select 
                                    id="type" 
                                    v-model="form.type" 
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="expense">Expense</option>
                                    <option value="income">Income</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="color">Color</Label>
                                <input 
                                    id="color" 
                                    v-model="form.color" 
                                    type="color"
                                    class="h-10 w-20 rounded-md border cursor-pointer"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button @click="handleCreate" :disabled="loading">
                                {{ loading ? 'Creating...' : 'Create Category' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Categories Grid -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
            
            <div v-else-if="categories.length === 0" class="text-center py-8 text-muted-foreground">
                <FolderOpen class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No categories found</p>
                <p class="text-sm">Create your first category to get started</p>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Card v-for="category in categories" :key="category.id" class="relative">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-4 h-4 rounded-full" 
                                    :style="{ backgroundColor: category.color }"
                                ></div>
                                <CardTitle class="text-lg">{{ category.name }}</CardTitle>
                            </div>
                            <div class="flex gap-1">
                                <Button size="sm" variant="outline" @click="handleEdit(category)">
                                    <Edit class="h-3 w-3" />
                                </Button>
                                <Button size="sm" variant="outline" @click="handleDelete(category)">
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-if="category.description" class="text-sm text-muted-foreground">
                                {{ category.description }}
                            </div>
                            <div class="flex items-center justify-between">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                    :class="getTypeColor(category.type)"
                                >
                                    {{ category.type }}
                                </span>
                                <div class="text-xs text-muted-foreground">
                                    Created {{ new Date(category.created_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Edit Dialog -->
            <Dialog v-model:open="showEditDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Category</DialogTitle>
                        <DialogDescription>
                            Update the category details.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-name">Name</Label>
                            <Input 
                                id="edit-name" 
                                v-model="form.name" 
                                placeholder="Category name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-description">Description</Label>
                            <Input 
                                id="edit-description" 
                                v-model="form.description" 
                                placeholder="Optional description"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="name">Parent Category</Label>
                            <select 
                                id="type" 
                                v-model="form.parent_id" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">No Parent category</option>
                                <option 
                                    v-for="parent in parent_categories" 
                                    :key="parent.id"
                                    :value="parent.id.toString()"
                                >
                                    {{  parent.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-type">Type</Label>
                            <select 
                                id="edit-type" 
                                v-model="form.type" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-color">Color</Label>
                            <input 
                                id="edit-color" 
                                v-model="form.color" 
                                type="color"
                                class="h-10 w-20 rounded-md border cursor-pointer"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="handleUpdate" :disabled="loading">
                            {{ loading ? 'Updating...' : 'Update Category' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
