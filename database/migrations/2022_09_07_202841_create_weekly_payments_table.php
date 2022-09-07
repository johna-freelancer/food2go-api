<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeklyPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('status')->default('pending');
            $table->string('merchant_name')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->dateTime('merchant_agreed_at')->nullable();
            $table->dateTime('admin_agreed_at')->nullable();
            $table->string('proof_url', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('weekly_payments');
    }
}
