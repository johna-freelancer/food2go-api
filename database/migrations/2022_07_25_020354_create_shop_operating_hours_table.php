<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOperatingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->enum('day_of_week', [1,2,3,4,5,6,7]); // Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday
            $table->time('opening_time');
            $table->time('closing_time');
            $table->boolean('is_enabled')->default(false);

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
        Schema::dropIfExists('shop_operating_hours');
    }
}
