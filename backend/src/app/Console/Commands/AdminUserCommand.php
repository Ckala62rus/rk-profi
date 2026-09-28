<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Безопасно создаёт администратора или сбрасывает его пароль.
 */
class AdminUserCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'admin:user
                            {action : create or reset}
                            {email : Administrator email address}
                            {--name= : Administrator name; required when creating an account}';

    /**
     * @var string
     */
    protected $description = 'Create an administrator or reset an existing administrator password';

    /**
     * Выполняет создание пользователя или безопасный сброс пароля.
     */
    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));
        $email = strtolower(trim((string) $this->argument('email')));

        if (! in_array($action, ['create', 'reset'], true)) {
            $this->error('Action must be either "create" or "reset".');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid administrator email address.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($action === 'create') {
            if ($user !== null) {
                $this->error('An administrator with this email already exists. Use "reset" to change its password.');

                return self::FAILURE;
            }

            $name = trim((string) ($this->option('name') ?? $this->ask('Administrator name')));

            if ($name === '') {
                $this->error('Administrator name is required.');

                return self::FAILURE;
            }

            if (! $this->confirm("Create administrator {$email}?", true)) {
                $this->warn('No changes were made.');

                return self::SUCCESS;
            }
        } else {
            if ($user === null) {
                $this->error('No administrator with this email was found. Use "create" to add one.');

                return self::FAILURE;
            }

            if (! $this->confirm("Reset the password for {$email} and revoke all of its active API tokens?", true)) {
                $this->warn('No changes were made.');

                return self::SUCCESS;
            }
        }

        $password = $this->secret('New password (at least 16 characters, with upper/lowercase letters, a number, and a symbol)');
        $confirmation = $this->secret('Confirm the new password');

        if (! hash_equals($password, $confirmation)) {
            $this->error('The passwords do not match. No changes were made.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::min(16)->mixedCase()->numbers()->symbols()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($action === 'create') {
            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info('Administrator created. Store the password in an approved password manager.');

            return self::SUCCESS;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();
        $revokedTokens = $user->tokens()->delete();

        $this->info("Password changed and {$revokedTokens} active API token(s) revoked.");

        return self::SUCCESS;
    }
}
