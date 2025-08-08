<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    @click="closeModal"
  >
    <div 
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
      @click.stop
    >
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ account ? 'Edit Bank Account' : 'Add New Bank Account' }}
        </h3>
        
        <form @submit.prevent="saveAccount" class="space-y-4">
          <!-- Bank Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Bank Name *
            </label>
            <input
              v-model="form.bank_name"
              type="text"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter bank name"
            />
          </div>

          <!-- Account Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Account Name *
            </label>
            <input
              v-model="form.account_name"
              type="text"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter account name"
            />
          </div>

          <!-- Account Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Account Number *
            </label>
            <input
              v-model="form.account_number"
              type="text"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter account number"
            />
          </div>

          <!-- Account Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Account Type *
            </label>
            <select
              v-model="form.account_type"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">Select account type</option>
              <option value="savings">Savings Account</option>
              <option value="checking">Checking Account</option>
              <option value="current">Current Account</option>
              <option value="business">Business Account</option>
              <option value="credit">Credit Account</option>
              <option value="investment">Investment Account</option>
            </select>
          </div>

          <!-- Initial Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Initial Amount *
            </label>
            <input
              v-model.number="form.initial_amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="0.00"
            />
          </div>

          <!-- Current Balance -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Current Balance *
            </label>
            <input
              v-model.number="form.current_balance"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="0.00"
            />
          </div>

          <!-- Available Balance -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Available Balance
            </label>
            <input
              v-model.number="form.available_balance"
              type="number"
              step="0.01"
              min="0"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="0.00"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Leave empty to use current balance
            </p>
          </div>

          <!-- Branch -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Branch
            </label>
            <input
              v-model="form.branch"
              type="text"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter branch name"
            />
          </div>

          <!-- IFSC Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              IFSC Code
            </label>
            <input
              v-model="form.ifsc_code"
              type="text"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter IFSC code"
            />
          </div>

          <!-- SWIFT Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              SWIFT Code
            </label>
            <input
              v-model="form.swift_code"
              type="text"
              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              placeholder="Enter SWIFT code"
            />
          </div>

          <!-- Is Active -->
          <div class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Account is active
            </label>
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
              {{ loading ? 'Saving...' : (account ? 'Update Account' : 'Create Account') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  account: Object
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)

const form = ref({
  bank_name: '',
  account_name: '',
  account_number: '',
  account_type: '',
  initial_amount: '',
  current_balance: '',
  available_balance: '',
  branch: '',
  ifsc_code: '',
  swift_code: '',
  is_active: true
})

// Watch for account prop changes to populate form
watch(() => props.account, (newAccount) => {
  if (newAccount) {
    form.value = {
      bank_name: newAccount.bank_name || '',
      account_name: newAccount.account_name || '',
      account_number: newAccount.account_number || '',
      account_type: newAccount.account_type || '',
      initial_amount: newAccount.initial_amount || '',
      current_balance: newAccount.current_balance || '',
      available_balance: newAccount.available_balance || '',
      branch: newAccount.branch || '',
      ifsc_code: newAccount.ifsc_code || '',
      swift_code: newAccount.swift_code || '',
      is_active: newAccount.is_active !== undefined ? newAccount.is_active : true
    }
  } else {
    // Reset form for new account
    form.value = {
      bank_name: '',
      account_name: '',
      account_number: '',
      account_type: '',
      initial_amount: '',
      current_balance: '',
      available_balance: '',
      branch: '',
      ifsc_code: '',
      swift_code: '',
      is_active: true
    }
  }
}, { immediate: true })

const closeModal = () => {
  emit('close')
}

const saveAccount = async () => {
  loading.value = true
  
  try {
    const url = props.account ? `/api/bank-accounts/${props.account.id}` : '/api/bank-accounts'
    const method = props.account ? 'put' : 'post'
    
    const response = await window.axios[method](url, form.value)
    
    emit('saved', response.data)
  } catch (error) {
    console.error('Error saving bank account:', error)
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      alert('Validation errors:\n' + errors.join('\n'))
    } else {
      alert('Error saving bank account. Please try again.')
    }
  } finally {
    loading.value = false
  }
}
</script>
