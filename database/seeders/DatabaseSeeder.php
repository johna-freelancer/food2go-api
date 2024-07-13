<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->state([
            'first_name' => 'John Anthony',
            'last_name' => 'Almario',
            'email' => 'jadalmario.freelancer@gmail.com',
            'password' => '@Unknown0322',
            'status' => 'active',
            'role' => 'admin'
        ])->create();

        \App\Models\User::factory()->state([
            'first_name' => 'Gabriel',
            'last_name' => 'Castro',
            'email' => 'gabminer05@gmail.com',
            'password' => 'Maler11!',
            'status' => 'active',
            'role' => 'admin'
        ])->create();
    }
}
