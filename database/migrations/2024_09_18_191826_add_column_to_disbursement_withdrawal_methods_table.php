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
        if (!Schema::hasColumns('disbursement_withdrawal_methods', ['store_name', 'pending_status']))
        {
            Schema::table('disbursement_withdrawal_methods', function (Blueprint $table) {
                $table->text('store_name')->nullable();
                $table->tinyInteger('pending_status')->default(1)->nullable();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       //
    }
};
