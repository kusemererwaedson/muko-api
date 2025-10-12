<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\FeeGroup;
use App\Models\FeeAllocation;
use App\Models\FeePayment;
use App\Models\Account;
use App\Models\VoucherHead;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@mukoschool.com',
            'password' => Hash::make('password')
        ]);

        // Only admin user, no sample data
    }
}