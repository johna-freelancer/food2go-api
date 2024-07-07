<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryProductLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_product_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_log_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('initial_quantity');
            $table->integer('closing quantity');
            $table->boolean('is_available')->default(false);

            $table->foreign('inventory_log_id')->references('id')->on('inventory_logs')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_product_logs');
    }
}
