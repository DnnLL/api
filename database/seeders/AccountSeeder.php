<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run()
    {
        Account::create([
            'account_number' => 'ACC12345',
            'balance' => 1000.00,
        ]);
    }
}
