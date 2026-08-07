<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Creates (or promotes) an account that can open the Filament panel.
 *
 * `make:filament-user` does not know about the is_admin flag this project
 * gates panel access on, so a user created with it logs in and then gets a
 * 403. This does both halves in one step.
 */
class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin
                            {--name= : Display name}
                            {--email= : Login e-mail}
                            {--password= : Password (prompted when omitted)}';

    protected $description = 'Create or promote a user who can access the admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Име', required: true);
        $email = $this->option('email') ?: text('Имейл', required: true);
        $password = $this->option('password') ?: promptPassword('Парола', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:180'],
                'password' => ['required', Password::min(8)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );

        // Not mass-assignable, so it has to be set on its own.
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info(sprintf(
            '%s: %s (#%d) — вече има достъп до /admin',
            $existing ? 'Обновен' : 'Създаден',
            $user->email,
            $user->id,
        ));

        return self::SUCCESS;
    }
}
