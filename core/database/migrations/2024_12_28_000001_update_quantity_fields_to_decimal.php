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
        // Update product_stocks table
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->default(0)->change();
        });

        // Update sale_details table
        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update purchase_details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update sale_return_details table
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update purchase_return_details table
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update adjustment_details table
        Schema::table('adjustment_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update transfer_details table
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->unsigned()->change();
        });

        // Update products table alert_quantity
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('alert_quantity', 10, 3)->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert product_stocks table
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->default(0)->change();
        });

        // Revert sale_details table
        Schema::table('sale_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert purchase_details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert sale_return_details table
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert purchase_return_details table
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert adjustment_details table
        Schema::table('adjustment_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert transfer_details table
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->integer('quantity')->unsigned()->change();
        });

        // Revert products table alert_quantity
        Schema::table('products', function (Blueprint $table) {
            $table->integer('alert_quantity')->unsigned()->nullable()->change();
        });
    }
};
