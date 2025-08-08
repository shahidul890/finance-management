<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click="closeModal"
  >
    <div 
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
      @click.stop
    >
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ budget ? 'Edit Budget' : 'Create New Budget' }}
        </h3>
        
        <form @submit.prevent="saveBudget" class="space-y-4">
          <!-- Budget Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Budget Name
            </label>
            <input
              v-model="form.budget_name"
              type="text"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter budget name"
            />
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Category (Optional)
            </label>
            <select
              v-model="form.category_id"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select a category</option>
              <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </div>

          <!-- Budget Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Budget Amount
            </label>
            <input
              v-model.number="form.budget_amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="0.00"
            />
          </div>

          <!-- Period Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Period Type
            </label>
            <select
              v-model="form.period_type"
              @change="updateDateRange"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
              <option value="custom">Custom</option>
            </select>
          </div>

          <!-- Date Range -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Start Date
              </label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                End Date
              </label>
              <input
                v-model="form.end_date"
                type="date"
                required
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
          </div>

          <!-- Alert Percentage -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Alert Percentage (%)
            </label>
            <input
              v-model.number="form.alert_percentage"
              type="number"
              min="0"
              max="100"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="80"
            />
          </div>

          <!-- Status (for edit mode) -->
          <div v-if="budget">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Status
            </label>
            <select
              v-model="form.status"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="active">Active</option>
              <option value="paused">Paused</option>
              <option value="completed">Completed</option>
            </select>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description (Optional)
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter budget description"
            ></textarea>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ loading ? 'Saving...' : (budget ? 'Update Budget' : 'Create Budget') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'

const props = defineProps({
  show: Boolean,
  budget: Object,
  categories: Array
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)

const form = ref({
  budget_name: '',
  category_id: '',
  budget_amount: '',
  period_type: 'monthly',
  start_date: '',
  end_date: '',
  alert_percentage: 80,
  status: 'active',
  description: ''
})

const updateDateRange = () => {
  const now = new Date()
  
  if (form.value.period_type === 'monthly') {
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
    const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    
    form.value.start_date = startOfMonth.toISOString().split('T')[0]
    form.value.end_date = endOfMonth.toISOString().split('T')[0]
  } else if (form.value.period_type === 'yearly') {
    const startOfYear = new Date(now.getFullYear(), 0, 1)
    const endOfYear = new Date(now.getFullYear(), 11, 31)
    
    form.value.start_date = startOfYear.toISOString().split('T')[0]
    form.value.end_date = endOfYear.toISOString().split('T')[0]
  }
}

// Watch for budget prop changes to populate form
watch(() => props.budget, (newBudget) => {
  if (newBudget) {
    form.value = {
      budget_name: newBudget.budget_name || '',
      category_id: newBudget.category?.id || '',
      budget_amount: newBudget.budget_amount || '',
      period_type: newBudget.period_type || 'monthly',
      start_date: newBudget.start_date || '',
      end_date: newBudget.end_date || '',
      alert_percentage: newBudget.alert_percentage || 80,
      status: newBudget.status || 'active',
      description: newBudget.description || ''
    }
  } else {
    // Reset form for new budget
    form.value = {
      budget_name: '',
      category_id: '',
      budget_amount: '',
      period_type: 'monthly',
      start_date: '',
      end_date: '',
      alert_percentage: 80,
      status: 'active',
      description: ''
    }
    updateDateRange()
  }
}, { immediate: true })



const closeModal = () => {
  emit('close')
}

const saveBudget = async () => {
  loading.value = true
  
  try {
    const url = props.budget ? `/api/budgets/${props.budget.id}` : '/api/budgets'
    const method = props.budget ? 'put' : 'post'
    
    const response = await window.axios[method](url, form.value)
    
    emit('saved', response.data)
  } catch (error) {
    console.error('Error saving budget:', error)
    alert('Error saving budget. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>
