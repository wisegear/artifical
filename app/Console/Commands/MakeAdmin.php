<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'blog:admin {email : Email of an existing registered account}';

    protected $description = 'Grant blog admin access to a registered user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Register this account on the website first.');

            return self::FAILURE;
        }
        $user->is_admin = true;
        $user->save();
        $this->info('Admin access granted.');

        return self::SUCCESS;
    }
}
