<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserShopTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_shop', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->string('address');
            $table->string('contact');
            $table->timestamp('open_hour')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('close_hour')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('status')->defaut('closed');
            $table->boolean('monday')->default('1');
            $table->boolean('tuesday')->default('1');
            $table->boolean('wednesday')->default('1');
            $table->boolean('thursday')->default('1');
            $table->boolean('friday')->default('1');
            $table->boolean('saturday')->default('1');
            $table->boolean('sunday')->default('1');
            $table->boolean('pm_cod')->default('1');
            $table->boolean('pm_gcash')->default('1');
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
        Schema::dropIfExists('user_shop');
    }
}
