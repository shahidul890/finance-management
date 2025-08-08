<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('expense_type', ['regular', 'dps_payment', 'fdr_investment', 'loan_payment'])->default('regular')->after('tags');
            $table->unsignedBigInteger('related_id')->nullable()->after('expense_type');
            $table->enum('related_type', ['dps', 'fdr', 'loan'])->nullable()->after('related_id');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null')->after('related_type');
            
            $table->index(['expense_type', 'related_id']);
            $table->index(['user_id', 'expense_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropIndex(['expense_type', 'related_id']);
            $table->dropIndex(['user_id', 'expense_type']);
            $table->dropColumn(['expense_type', 'related_id', 'related_type', 'bank_account_id']);
        });
    }
};
