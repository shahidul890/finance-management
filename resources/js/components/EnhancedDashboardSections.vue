<template>
<!-- Enhanced Dashboard Section -->
<div v-if="dashboardData" class="space-y-6">
  <!-- Bank Accounts Summary Section -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bank Accounts Summary</h3>
      <a href="/bank-accounts" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ dashboardData.bank_accounts_summary?.total_accounts || 0 }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Accounts</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ dashboardData.bank_accounts_summary?.total_balance?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Current Balance</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ dashboardData.bank_accounts_summary?.total_initial_amount?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Initial Amount</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold" 
           :class="(dashboardData.bank_accounts_summary?.net_change || 0) >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ (dashboardData.bank_accounts_summary?.net_change || 0) >= 0 ? '+' : '' }}${{ dashboardData.bank_accounts_summary?.net_change?.toLocaleString() || '0' }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Net Change</p>
      </div>
    </div>
  </div>

  <!-- Budget Overview Section -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Budget Overview</h3>
      <a href="/budgets" class="text-blue-600 hover:text-blue-800 text-sm">Manage Budgets</a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ dashboardData.budget_overview?.total_budgets || 0 }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Active Budgets</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ dashboardData.budget_overview?.total_budget_amount?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Budget</p>
      </div>
      <div class="text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ dashboardData.budget_overview?.total_spent?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Spent</p>
      </div>
      <div class="text-center">
        <div class="flex items-center justify-center space-x-2">
          <span class="text-2xl font-bold text-red-600">{{ dashboardData.budget_overview?.over_budget_count || 0 }}</span>
          <span class="text-yellow-500">{{ dashboardData.budget_overview?.alert_triggered_count || 0 }}</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Over Budget / Alerts</p>
      </div>
    </div>
  </div>

  <!-- Investment Overview Section -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Investment Overview</h3>
      <a href="/investments" class="text-blue-600 hover:text-blue-800 text-sm">View Details</a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- DPS Summary -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900">
            <span class="text-blue-600 dark:text-blue-400 text-lg">💰</span>
          </div>
          <div class="ml-3">
            <h4 class="font-semibold text-gray-900 dark:text-white">DPS</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ dashboardData.investment_overview?.dps?.count || 0 }} accounts</p>
          </div>
        </div>
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Deposited:</span>
            <span class="text-gray-900 dark:text-white">${{ dashboardData.investment_overview?.dps?.total_deposited?.toLocaleString() || '0' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Maturity:</span>
            <span class="text-green-600 dark:text-green-400">${{ dashboardData.investment_overview?.dps?.maturity_amount?.toLocaleString() || '0' }}</span>
          </div>
        </div>
      </div>

      <!-- FDR Summary -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <div class="p-2 rounded-full bg-green-100 dark:bg-green-900">
            <span class="text-green-600 dark:text-green-400 text-lg">📈</span>
          </div>
          <div class="ml-3">
            <h4 class="font-semibold text-gray-900 dark:text-white">FDR</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ dashboardData.investment_overview?.fdr?.count || 0 }} accounts</p>
          </div>
        </div>
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Principal:</span>
            <span class="text-gray-900 dark:text-white">${{ dashboardData.investment_overview?.fdr?.total_principal?.toLocaleString() || '0' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Maturity:</span>
            <span class="text-green-600 dark:text-green-400">${{ dashboardData.investment_overview?.fdr?.maturity_amount?.toLocaleString() || '0' }}</span>
          </div>
        </div>
      </div>

      <!-- Loans Summary -->
      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-center mb-3">
          <div class="p-2 rounded-full bg-red-100 dark:bg-red-900">
            <span class="text-red-600 dark:text-red-400 text-lg">🏠</span>
          </div>
          <div class="ml-3">
            <h4 class="font-semibold text-gray-900 dark:text-white">Loans</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ dashboardData.investment_overview?.loans?.count || 0 }} accounts</p>
          </div>
        </div>
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Outstanding:</span>
            <span class="text-red-600 dark:text-red-400">${{ dashboardData.investment_overview?.loans?.outstanding_balance?.toLocaleString() || '0' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Monthly EMI:</span>
            <span class="text-gray-900 dark:text-white">${{ dashboardData.investment_overview?.loans?.monthly_emi?.toLocaleString() || '0' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Expense Breakdown Section -->
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expense Breakdown by Type</h3>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white">${{ dashboardData.expense_breakdown?.regular?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Regular Expenses</p>
      </div>
      <div class="text-center">
        <p class="text-xl font-bold text-blue-600">${{ dashboardData.expense_breakdown?.dps_payments?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">DPS Payments</p>
      </div>
      <div class="text-center">
        <p class="text-xl font-bold text-green-600">${{ dashboardData.expense_breakdown?.fdr_investments?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">FDR Investments</p>
      </div>
      <div class="text-center">
        <p class="text-xl font-bold text-red-600">${{ dashboardData.expense_breakdown?.loan_payments?.toLocaleString() || '0' }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Loan Payments</p>
      </div>
    </div>
  </div>
</div>
</template>

<script setup>
defineProps({
  dashboardData: Object
})
</script>
