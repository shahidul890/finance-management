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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enabled_2fa')->default(false)->after('password');
            $table->string('google_2fa_secret')->nullable()->after('enabled_two_factor_security');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('enabled_2fa');
            $table->dropColumn('google_2fa_secret');
        });
    }
};
