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
        // First, let's check the current structure
        $columns = DB::select("DESCRIBE customers");
        $emailColumn = collect($columns)->firstWhere('Field', 'email');
        
        if ($emailColumn) {
            // Make email nullable if it's not already
            if ($emailColumn->Null === 'NO') {
                Schema::table('customers', function (Blueprint $table) {
                    $table->string('email', 40)->nullable()->change();
                });
            }
            
            // Try to drop unique constraint if it exists (ignore errors)
            try {
                Schema::table('customers', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
        }

        // Ensure mobile is required
        Schema::table('customers', function (Blueprint $table) {
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
