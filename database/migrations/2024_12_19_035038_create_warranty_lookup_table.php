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
        Schema::create('warranty_lookup', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('sn_id');
            $table->integer('customer_id')->nullable();
            $table->dateTime('export_return_date');
            $table->integer('warranty');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_lookup');
    }
};