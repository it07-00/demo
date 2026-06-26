<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;

class ResetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password 
                            {identifier? : The email or username of the user} 
                            {--password= : The new password (leave blank to be prompted or reset to default)} 
                            {--default : Reset password to default value (1Zbia9HdgUizySSt)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user\'s password';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $identifier = $this->argument('identifier');

        if (! $identifier) {
            $identifier = $this->ask('Please enter the user\'s email or username');
        }

        if (! $identifier) {
            $this->error('Username or email is required.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("User not found with email or username: {$identifier}");

            return self::FAILURE;
        }

        // Check if resetting to default
        if ($this->option('default')) {
            $userService->resetPasswordToDefault($user);
            $this->info("Password for user '{$user->name}' ({$user->username}) has been reset to default: ".UserService::DEFAULT_RESET_PASSWORD);

            return self::SUCCESS;
        }

        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Enter the new password');

            if (! $password) {
                $this->error('Password cannot be empty.');

                return self::FAILURE;
            }

            $confirmPassword = $this->secret('Confirm the new password');

            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        $userService->resetPassword($user, $password);

        $this->info("Password for user '{$user->name}' ({$user->username}) has been reset successfully!");

        return self::SUCCESS;
    }
}
