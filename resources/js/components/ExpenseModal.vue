<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click="closeModal"
  >
    <div 
      class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-screen overflow-y-auto"
      @click.stop
    >
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ expense ? 'Edit Expense' : 'Add New Expense' }}
        </h3>
        
        <form @submit.prevent="saveExpense" class="space-y-4">
          <!-- Expense Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Expense Type *
            </label>
            <select
              v-model="form.expense_type"
              @change="onExpenseTypeChange"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="regular">Regular Expense</option>
              <option value="dps_payment">DPS Payment</option>
              <option value="fdr_investment">FDR Investment</option>
              <option value="loan_payment">Loan Payment</option>
            </select>
          </div>

          <!-- Related Investment (for non-regular expenses) -->
          <div v-if="form.expense_type !== 'regular'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ getRelatedLabel() }} *
            </label>
            <select
              v-model="form.related_id"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select {{ getRelatedLabel() }}</option>
              <option
                v-for="item in relatedItems"
                :key="item.id"
                :value="item.id"
              >
                {{ getRelatedItemLabel(item) }}
              </option>
            </select>
          </div>

          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Title *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter expense title"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter expense description"
            ></textarea>
          </div>

          <!-- Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Amount *
            </label>
            <input
              v-model.number="form.amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="0.00"
            />
          </div>

          <!-- Expense Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Expense Date *
            </label>
            <input
              v-model="form.expense_date"
              type="date"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Category
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

          <!-- Bank Account -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Bank Account
            </label>
            <select
              v-model="form.bank_account_id"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select bank account</option>
              <option
                v-for="account in bankAccounts"
                :key="account.id"
                :value="account.id"
              >
                {{ account.account_name }} - {{ account.bank_name }}
              </option>
            </select>
          </div>

          <!-- Payment Method -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Payment Method
            </label>
            <select
              v-model="form.payment_method"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select payment method</option>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="credit_card">Credit Card</option>
              <option value="debit_card">Debit Card</option>
              <option value="check">Check</option>
              <option value="online">Online Payment</option>
              <option value="mobile_banking">Mobile Banking</option>
            </select>
          </div>

          <!-- Tags -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Tags
            </label>
            <input
              v-model="form.tags"
              type="text"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter tags separated by commas"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Separate multiple tags with commas
            </p>
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
              {{ loading ? 'Saving...' : (expense ? 'Update Expense' : 'Create Expense') }}
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
  expense: Object,
  categories: Array,
  bankAccounts: Array,
  dpsList: Array,
  fdrList: Array,
  loanList: Array
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)

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
  bank_account_id: ''
})

// Computed property for related items based on expense type
const relatedItems = computed(() => {
  switch (form.value.expense_type) {
    case 'dps_payment':
      return props.dpsList || []
    case 'fdr_investment':
      return props.fdrList || []
    case 'loan_payment':
      return props.loanList || []
    default:
      return []
  }
})

// Watch for expense prop changes to populate form
watch(() => props.expense, (newExpense) => {
  if (newExpense) {
    form.value = {
      title: newExpense.title || '',
      description: newExpense.description || '',
      amount: newExpense.amount || '',
      expense_date: newExpense.expense_date || new Date().toISOString().split('T')[0],
      category_id: newExpense.category_id || '',
      payment_method: newExpense.payment_method || '',
      tags: Array.isArray(newExpense.tags) ? newExpense.tags.join(', ') : (newExpense.tags || ''),
      expense_type: newExpense.expense_type || 'regular',
      related_id: newExpense.related_id || '',
      related_type: newExpense.related_type || '',
      bank_account_id: newExpense.bank_account_id || ''
    }
  } else {
    // Reset form for new expense
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
      bank_account_id: ''
    }
  }
}, { immediate: true })

const onExpenseTypeChange = () => {
  // Reset related fields when expense type changes
  form.value.related_id = ''
  
  // Set related_type based on expense_type
  switch (form.value.expense_type) {
    case 'dps_payment':
      form.value.related_type = 'dps'
      break
    case 'fdr_investment':
      form.value.related_type = 'fdr'
      break
    case 'loan_payment':
      form.value.related_type = 'loan'
      break
    default:
      form.value.related_type = ''
  }
}

const getRelatedLabel = () => {
  switch (form.value.expense_type) {
    case 'dps_payment':
      return 'DPS Account'
    case 'fdr_investment':
      return 'FDR Account'
    case 'loan_payment':
      return 'Loan Account'
    default:
      return 'Related Item'
  }
}

const getRelatedItemLabel = (item) => {
  switch (form.value.expense_type) {
    case 'dps_payment':
      return `${item.dps_name} - ${item.dps_number || 'N/A'}`
    case 'fdr_investment':
      return `${item.fdr_name} - ${item.fdr_number || 'N/A'}`
    case 'loan_payment':
      return `${item.loan_name} - ${item.loan_number || 'N/A'}`
    default:
      return item.name || 'Unknown'
  }
}

const closeModal = () => {
  emit('close')
}

const saveExpense = async () => {
  loading.value = true
  
  try {
    // Prepare the data
    const data = { ...form.value }
    
    // Convert tags string to array
    if (data.tags) {
      data.tags = data.tags.split(',').map(tag => tag.trim()).filter(tag => tag)
    } else {
      data.tags = []
    }
    
    // Clear related fields if expense type is regular
    if (data.expense_type === 'regular') {
      data.related_id = null
      data.related_type = null
    }
    
    const url = props.expense ? `/api/expenses/${props.expense.id}` : '/api/expenses'
    const method = props.expense ? 'put' : 'post'
    
    const response = await window.axios[method](url, data)
    
    emit('saved', response.data)
  } catch (error) {
    console.error('Error saving expense:', error)
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      alert('Validation errors:\n' + errors.join('\n'))
    } else {
      alert('Error saving expense. Please try again.')
    }
  } finally {
    loading.value = false
  }
}
</script>
