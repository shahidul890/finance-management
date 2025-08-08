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
        Schema::create('dps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('dps_name');
            $table->string('dps_number')->unique();
            $table->decimal('monthly_installment', 15, 2);
            $table->integer('tenure_months');
            $table->decimal('interest_rate', 5, 2);
            $table->date('start_date');
            $table->date('maturity_date');
            $table->decimal('total_deposited', 15, 2)->default(0);
            $table->decimal('maturity_amount', 15, 2);
            $table->integer('paid_installments')->default(0);
            $table->integer('remaining_installments');
            $table->enum('status', ['active', 'completed', 'closed', 'defaulted'])->default('active');
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date');
            $table->boolean('auto_debit')->default(false);
            $table->json('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dps');
    }
};
