<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\User;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (you can modify this to seed for specific users)
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please create a user first.');
            return;
        }

        $categories = [
            // Expense categories
            [
                'name' => 'Food & Dining',
                'description' => 'Restaurants, groceries, and food delivery',
                'type' => 'expense',
                'color' => '#FF6B6B',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Transportation',
                'description' => 'Gas, public transport, car maintenance',
                'type' => 'expense',
                'color' => '#4ECDC4',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Shopping',
                'description' => 'Clothing, electronics, household items',
                'type' => 'expense',
                'color' => '#45B7D1',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movies, games, subscriptions',
                'type' => 'expense',
                'color' => '#F7DC6F',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Medical bills, pharmacy, insurance',
                'type' => 'expense',
                'color' => '#BB8FCE',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, internet, phone',
                'type' => 'expense',
                'color' => '#85C1E9',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Education',
                'description' => 'Books, courses, tuition',
                'type' => 'expense',
                'color' => '#F8C471',
                'user_id' => $user->id,
            ],

            // Income categories
            [
                'name' => 'Salary',
                'description' => 'Regular employment income',
                'type' => 'income',
                'color' => '#58D68D',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Freelance',
                'description' => 'Freelance work and consulting',
                'type' => 'income',
                'color' => '#82E0AA',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Investment',
                'description' => 'Dividends, capital gains, interest',
                'type' => 'income',
                'color' => '#A9DFBF',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Business',
                'description' => 'Business income and profits',
                'type' => 'income',
                'color' => '#D5F4E6',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Other Income',
                'description' => 'Gifts, bonuses, refunds',
                'type' => 'income',
                'color' => '#ABEBC6',
                'user_id' => $user->id,
            ],

            // Mixed categories
            [
                'name' => 'Travel',
                'description' => 'Travel expenses and vacation income',
                'type' => 'both',
                'color' => '#FAD7A0',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Home',
                'description' => 'Home expenses and rental income',
                'type' => 'both',
                'color' => '#D7BDE2',
                'user_id' => $user->id,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Default categories have been created for user: ' . $user->name);
    }
}
