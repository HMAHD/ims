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
        // Check if email column has unique constraint and remove it
        try {
            // First, update any empty email values to null
            DB::table('customers')->where('email', '')->update(['email' => null]);
            
            // Try to drop unique constraint if it exists
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }
        
        // Ensure email is nullable
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 40)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 40)->nullable(false)->change();
            $table->unique('email');
        });
    }
};
