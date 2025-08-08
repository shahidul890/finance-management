# Personal Finance Management API

This Laravel application provides a comprehensive API for managing personal expenses and incomes. The system includes categories, expenses, incomes, and dashboard analytics.

## Features

- **User Authentication**: Secure API access using Sanctum tokens
- **Categories Management**: Create and organize expense/income categories
- **Expense Tracking**: Record and manage personal expenses
- **Income Tracking**: Record and manage income sources (including recurring income)
- **Dashboard Analytics**: Get financial insights and statistics
- **Search and Filtering**: Filter transactions by date, category, and other criteria

## Database Schema

### Categories Table
- `id` (Primary Key)
- `name` (string) - Category name
- `description` (text, nullable) - Category description
- `type` (enum) - 'expense', 'income', or 'both'
- `color` (string) - Hex color code for UI
- `user_id` (Foreign Key) - Links to users table

### Expenses Table
- `id` (Primary Key)
- `title` (string) - Expense title
- `description` (text, nullable) - Expense description
- `amount` (decimal) - Expense amount
- `expense_date` (date) - When the expense occurred
- `user_id` (Foreign Key) - Links to users table
- `category_id` (Foreign Key, nullable) - Links to categories table
- `payment_method` (string, nullable) - How payment was made
- `receipt_path` (string, nullable) - Path to receipt file
- `tags` (JSON, nullable) - Array of tags

### Incomes Table
- `id` (Primary Key)
- `title` (string) - Income title
- `description` (text, nullable) - Income description
- `amount` (decimal) - Income amount
- `income_date` (date) - When the income was received
- `user_id` (Foreign Key) - Links to users table
- `category_id` (Foreign Key, nullable) - Links to categories table
- `source` (string, nullable) - Income source
- `is_recurring` (boolean) - Whether income is recurring
- `recurring_frequency` (string, nullable) - 'weekly', 'monthly', 'quarterly', 'yearly'
- `tags` (JSON, nullable) - Array of tags

## API Endpoints

All endpoints require authentication except for login/register.

### Authentication
- `POST /api/login` - Login user
- `POST /api/register` - Register new user
- `POST /api/logout` - Logout user
- `GET /api/user` - Get authenticated user info

### Dashboard
- `GET /api/dashboard` - Get financial overview and statistics
  - Query params: `period` (month/year/custom), `start_date`, `end_date`

### Categories
- `GET /api/categories` - List all categories for authenticated user
  - Query params: `type` (expense/income/both)
- `POST /api/categories` - Create new category
- `GET /api/categories/{id}` - Get specific category
- `PUT/PATCH /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

### Expenses
- `GET /api/expenses` - List expenses with pagination
  - Query params: `start_date`, `end_date`, `category_id`, `search`, `per_page`
- `POST /api/expenses` - Create new expense
- `GET /api/expenses/{id}` - Get specific expense
- `PUT/PATCH /api/expenses/{id}` - Update expense
- `DELETE /api/expenses/{id}` - Delete expense
- `GET /api/expenses/stats` - Get expense statistics
  - Query params: `start_date`, `end_date`

### Incomes
- `GET /api/incomes` - List incomes with pagination
  - Query params: `start_date`, `end_date`, `category_id`, `is_recurring`, `search`, `per_page`
- `POST /api/incomes` - Create new income
- `GET /api/incomes/{id}` - Get specific income
- `PUT/PATCH /api/incomes/{id}` - Update income
- `DELETE /api/incomes/{id}` - Delete income
- `GET /api/incomes/stats` - Get income statistics
  - Query params: `start_date`, `end_date`

## Example API Requests

### Create Category
```json
POST /api/categories
{
    "name": "Food & Dining",
    "description": "Restaurants and groceries",
    "type": "expense",
    "color": "#FF6B6B"
}
```

### Create Expense
```json
POST /api/expenses
{
    "title": "Lunch at restaurant",
    "description": "Team lunch meeting",
    "amount": 45.50,
    "expense_date": "2025-08-06",
    "category_id": 1,
    "payment_method": "credit_card",
    "tags": ["business", "meal"]
}
```

### Create Income
```json
POST /api/incomes
{
    "title": "Monthly Salary",
    "description": "Regular employment income",
    "amount": 5000.00,
    "income_date": "2025-08-01",
    "category_id": 8,
    "source": "employer",
    "is_recurring": true,
    "recurring_frequency": "monthly"
}
```

## Dashboard Analytics

The dashboard endpoint provides comprehensive financial analytics:

- **Summary**: Total income, expenses, net balance for the period
- **Monthly Trends**: 12-month chart data for income/expense trends
- **Category Breakdown**: Top spending and earning categories
- **Recent Transactions**: Latest expenses and incomes
- **Budget Analysis**: Comparison with previous periods

## Security Features

- All endpoints require authentication
- Users can only access their own data
- Input validation on all requests
- SQL injection protection
- XSS protection

## Setup Instructions

1. Run migrations: `php artisan migrate`
2. Seed categories: `php artisan db:seed`
3. Configure authentication (Sanctum tokens)
4. Set up frontend to consume the API

## Default Categories

The system comes with pre-configured categories:

**Expense Categories:**
- Food & Dining
- Transportation
- Shopping
- Entertainment
- Healthcare
- Utilities
- Education

**Income Categories:**
- Salary
- Freelance
- Investment
- Business
- Other Income

**Mixed Categories:**
- Travel
- Home

Each category has a unique color for better UI visualization.
