<?php

namespace Database\Seeders;

use App\Models\Customers;
use App\Models\Product;
use App\Models\Providers;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $users = [
            ['name' => 'Demo Admin', 'email' => 'admin@demo.com', 'phone' => '0908 779 167', 'role' => 'Admin'],
            ['name' => 'Demo Kế toán', 'email' => 'ketoan@demo.com', 'phone' => '0345 051 482', 'role' => 'Kế toán'],
            ['name' => 'Demo Quản lý kho', 'email' => 'quankho@demo.com', 'phone' => '0387 823 982', 'role' => 'Quản lý kho'],
            ['name' => 'Demo Bảo hành', 'email' => 'baohanh@demo.com', 'phone' => '0983 468 473', 'role' => 'Bảo hành'],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => Hash::make('123456'),
            ]);

            if (!empty($userData['role'])) {
                $role = Role::firstOrCreate(['name' => $userData['role']]);
                $user->assignRole($role);
            }
        }
    }
}
