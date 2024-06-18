<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(5)->create();

        User::factory()->create([
            'name' => 'Admin Kelvin',
            'email' => 'kelvin@admin.com',
            'password' => Hash::make('12345678'),
            'phone' => '085895226892',
            'address' => 'Surabaya, East Java, Indonesia',
            'roles' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'User Kelvin',
            'email' => 'kelvin@user.com',
            'password' => Hash::make('12345678'),
            'phone' => '085895226892',
            'address' => 'Surabaya, East Java, Indonesia',
            'roles' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Driver Kelvin',
            'email' => 'kelvin@driver.com',
            'password' => Hash::make('12345678'),
            'phone' => '085895226892',
            'address' => 'Surabaya, East Java, Indonesia',
            'roles' => 'driver',
            'license_plate' => 'L 1234 BAJ'
        ]);

        User::factory()->create([
            'name' => 'Seller Kelvin',
            'email' => 'kelvin@restaurant.com',
            'password' => Hash::make('12345678'),
            'phone' => '085895226892',
            'restaurant_address' => 'Surabaya, East Java, Indonesia',
            'restaurant_name' => 'Mie Ayam Cak Gandi',
            'roles' => 'restaurant',
        ]);

        $this->call([
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
