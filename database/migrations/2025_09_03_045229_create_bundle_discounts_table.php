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
    Schema::create('bundle_discounts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('bundle_id');
        $table->integer('min_qty');
        $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
        $table->decimal('discount_value', 8, 2); // 14% or 5$ etc
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_discounts');
    }
};
