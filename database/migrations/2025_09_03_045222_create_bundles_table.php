<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('bundles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shop_id'); // shopify shop reference
        $table->string('shopify_product_id')->nullable();
        $table->string('title')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundles');
    }
};
