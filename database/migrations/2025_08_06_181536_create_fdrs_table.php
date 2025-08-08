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
        Schema::create('fdrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('fdr_name');
            $table->string('fdr_number')->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('tenure_months');
            $table->date('start_date');
            $table->date('maturity_date');
            $table->decimal('maturity_amount', 15, 2);
            $table->enum('interest_payout', ['monthly', 'quarterly', 'yearly', 'on_maturity'])->default('on_maturity');
            $table->decimal('interest_earned', 15, 2)->default(0);
            $table->enum('status', ['active', 'matured', 'premature_closed', 'renewed'])->default('active');
            $table->boolean('auto_renewal')->default(false);
            $table->date('last_interest_paid')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fdrs');
    }
};
