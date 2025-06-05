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
        // Modify sale_details table to support negative quantities for returns
        Schema::table('sale_details', function (Blueprint $table) {
            // Change quantity to allow negative values (remove unsigned constraint)
            $table->decimal('quantity', 10, 3)->change();
            
            // Add fields to track return information
            $table->boolean('is_return')->default(false)->after('total');
            $table->string('return_invoice')->nullable()->after('is_return');
            $table->text('return_note')->nullable()->after('return_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            // Revert quantity back to unsigned
            $table->decimal('quantity', 10, 3)->unsigned()->change();
            
            // Remove return tracking fields
            $table->dropColumn(['is_return', 'return_invoice', 'return_note']);
        });
    }
};
