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
        Schema::table('delivery_men', function (Blueprint $table) {
            if (!Schema::hasColumns('delivery_men', ['withdraw_req_status']))
            {
                $table->tinyInteger('dm_withdraw_to_store_status')->default(0)->nullable()->comment('0=> no latest req sent yet. 1=> Request sent to Store, 2=> Store accepted the request.');
                $table->string('last_withdraw_date_to_store')->nullable()->comment('Last date to withdraw collected cash to store');
                $table->tinyInteger('withdraw_req_status')->default(0)->nullable()->comment('1=>dm got withdraw req from store. 0=>has not got withdraw req from store');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            //
        });
    }
};
