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
        // Add fields to sales table to track applied returns and due amounts
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('applied_return_amount', 28, 8)->default(0)->after('due_amount');
            $table->decimal('applied_due_amount', 28, 8)->default(0)->after('applied_return_amount');
            $table->json('applied_returns')->nullable()->after('applied_due_amount');
            $table->json('applied_dues')->nullable()->after('applied_returns');
        });

        // Create table to track cross-sale return applications
        Schema::create('sale_return_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_sale_id');
            $table->unsignedBigInteger('original_sale_return_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('applied_amount', 28, 8);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('new_sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('original_sale_return_id')->references('id')->on('sale_returns')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // Create table to track cross-sale due applications
        Schema::create('sale_due_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_sale_id');
            $table->unsignedBigInteger('original_sale_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('applied_amount', 28, 8);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('new_sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('original_sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_due_applications');
        Schema::dropIfExists('sale_return_applications');
        
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['applied_return_amount', 'applied_due_amount', 'applied_returns', 'applied_dues']);
        });
    }
};
