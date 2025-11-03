<?php
namespace Msdev2\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Msdev2\Shopify\Models\User;

class CreateAgent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msdev2:agent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an agent user (interactive)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Create or update an agent user.');

        $email = $this->ask('Email (will be used as login)');
        if (empty($email)) {
            $this->error('Email is required.');
            return 1;
        }

        $name = $this->ask('Full name');
        $mobile = $this->ask('Mobile (optional)');
        $password = $this->secret('Password (will be hidden)');

        if (empty($password)) {
            $this->error('Password is required.');
            return 1;
        }

        $hashed = Hash::make($password);

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->name = $name ?: $user->name;
            if (!empty($mobile)) $user->mobile = $mobile;
            $user->password = $hashed;
            $user->save();
            $this->info("Updated agent: {$user->email}");
        } else {
            $user = User::create([
                'name' => $name ?: $email,
                'first_name' => $name ?: null,
                'email' => $email,
                'mobile' => $mobile,
                'password' => $hashed,
                'token' => rand(10000000, 999999999)
            ]);
            $this->info("Created agent: {$user->email}");
        }

        $this->line('Done. You can now login at ' . config('app.url') . '/agent');
        return 0;
    }

}
