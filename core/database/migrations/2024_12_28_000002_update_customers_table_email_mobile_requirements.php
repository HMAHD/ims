<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Make email nullable and remove unique constraint if it exists
            $table->string('email', 40)->nullable()->change();
        });

        // Check if unique constraint exists before dropping it
        $indexExists = DB::select("SHOW INDEX FROM customers WHERE Key_name = 'customers_email_unique'");
        if (!empty($indexExists)) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            // Make mobile required and keep unique constraint
            $table->string('mobile', 40)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Revert email to required and add unique constraint back
            $table->string('email', 40)->nullable(false)->change();
            $table->unique('email');

            // Make mobile nullable again
            $table->string('mobile', 40)->nullable()->change();
        });
    }
};
