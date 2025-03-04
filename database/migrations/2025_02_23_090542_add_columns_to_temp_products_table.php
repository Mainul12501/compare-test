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
        Schema::table('temp_products', function (Blueprint $table) {
            if(!Schema::hasColumns('temp_products', ['vmw_height', 'vmw_width', 'vmw_length', 'static_weight'])) {
                $table->decimal('vmw_height', 10, 2)->default(0);
                $table->decimal('vmw_width', 10, 2)->default(0);
                $table->decimal('vmw_length', 10, 2)->default(0);
                $table->decimal('static_weight', 10, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_products', function (Blueprint $table) {
            //
        });
    }
};
