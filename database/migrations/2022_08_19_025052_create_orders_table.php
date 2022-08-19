<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_user_id');
            $table->foreign('customer_user_id')->references('id')->on('users');
            $table->unsignedBigInteger('merchant_user_id');
            $table->unsignedBigInteger('user_shops_id');
            $table->foreign('user_shops_id')->references('id')->on('user_shops');
            $table->string('mode_of_payment')->default('cod');
            $table->string('address');
            $table->string('contact');
            $table->decimal('delivery_charge');
            $table->decimal('convenience_fee');
            $table->text('note')->nullable();
            $table->decimal('total');
            $table->string('status')->default('pending');
            $table->string('remarks')->nullable();
            $table->string('proof_url', 1000)->nullable();
            $table->dateTime('changed_at_preparing')->nullable();
            $table->dateTime('changed_at_delivered')->nullable();
            $table->dateTime('changed_at_completed')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
