<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            
            $table->boolean('mop_gcash')->default(false);
            $table->boolean('mop_cod')->default(true);
            $table->integer('gcash_number')->nullable();
            $table->decimal('minimum_delivery_charge', 8, 2)->default(0);
            $table->decimal('delivery_amount_threshold', 8, 2)->default(0);
            $table->integer('delivery_items_threshold')->default(0);
            $table->decimal('delivery_distance_threshold', 8, 2)->default(0);
            $table->decimal('rate_per_km', 8, 2)->default(0);

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

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
        Schema::dropIfExists('shop_configurations');
    }
}
