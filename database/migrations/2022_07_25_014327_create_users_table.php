<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs(DB::raw('CONCAT(first_name , " "  , last_name)'));
            $table->string('email')->unique();
            $table->integer('phone_number')->unique()->nullable();
            $table->string('password');
            $table->enum('status', ['active','inactive','suspended']);
            $table->boolean('phone_number_verified')->default(false);
            $table->string('phone_number_code_verification', 6)->unique()->nullable();
            $table->boolean('email_verified')->default(false);
            $table->string('email_code_verification', 6)->unique()->nullable();
            $table->boolean('is_merchant')->default(false);
            $table->string('role'); //Member and Admin
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
