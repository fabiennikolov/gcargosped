<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_who_can_open_the_panel(): void
    {
        $this->artisan('app:make-admin', [
            '--name' => 'Тест Админ',
            '--email' => 'admin@example.com',
            '--password' => 'TestParola123',
        ])->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('TestParola123', $user->password));

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_it_promotes_an_existing_account_instead_of_duplicating_it(): void
    {
        $existing = User::factory()->create([
            'email' => 'someone@example.com',
            'is_admin' => false,
        ]);

        $this->artisan('app:make-admin', [
            '--name' => 'Повишен',
            '--email' => 'someone@example.com',
            '--password' => 'TestParola123',
        ])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'someone@example.com')->count());
        $this->assertTrue($existing->fresh()->is_admin);
    }

    public function test_it_refuses_a_weak_password(): void
    {
        $this->artisan('app:make-admin', [
            '--name' => 'Тест',
            '--email' => 'weak@example.com',
            '--password' => 'kratka',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }
}
