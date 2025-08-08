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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('loan_name');
            $table->string('loan_number')->unique();
            $table->enum('loan_type', ['personal', 'home', 'car', 'education', 'business', 'other']);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('tenure_months');
            $table->decimal('monthly_emi', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_amount_payable', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2);
            $table->integer('paid_emis')->default(0);
            $table->integer('remaining_emis');
            $table->enum('status', ['active', 'completed', 'defaulted', 'foreclosed'])->default('active');
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date');
            $table->boolean('auto_debit')->default(false);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->json('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
