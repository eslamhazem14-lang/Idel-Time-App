<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_can_register(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Test Dev', 'email' => 'Dev@Example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'developer', 'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'dev@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Developer, $user->role);
        $this->assertNotNull($user->developerProfile);
        $this->assertNotNull($user->wallet);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_requester_can_register(): void
    {
        Notification::fake();
        $this->post('/register', [
            'name' => 'Acme', 'email' => 'acme@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'requester', 'terms' => '1',
        ])->assertRedirect();

        $this->assertSame(UserRole::Requester, User::query()->where('email', 'acme@example.test')->first()->role);
    }

    public function test_cannot_self_register_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Evil', 'email' => 'evil@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin', 'terms' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'evil@example.test']);
    }

    public function test_registration_requires_terms_and_unique_email(): void
    {
        $this->developer(['email' => 'taken@example.test']);
        $this->post('/register', ['name' => 'X', 'email' => 'taken@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'developer'])
            ->assertSessionHasErrors(['email', 'terms']);
    }

    public function test_user_can_login_and_is_sent_to_role_dashboard(): void
    {
        $dev = $this->developer(['email' => 'dev@example.test']);
        $this->post('/login', ['email' => 'dev@example.test', 'password' => 'password'])->assertRedirect(route('developer.dashboard'));
        $this->assertAuthenticatedAs($dev);
        $this->assertNotNull($dev->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->developer(['email' => 'dev@example.test']);
        $this->post('/login', ['email' => 'dev@example.test', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->suspended()->create(['email' => 's@example.test']);
        $this->post('/login', ['email' => 's@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->developer(['email' => 'dev@example.test']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'dev@example.test', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'dev@example.test', 'password' => 'password'])->assertStatus(429);
    }

    public function test_email_verification_link_verifies(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect(route('developer.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_logout(): void
    {
        $this->actingAs($this->developer())->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
