<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_the_expected_credential_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee('method="post"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('type="submit"', false);
    }

    public function test_active_user_can_log_in_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'login-test@example.test',
            'password' => 'password',
            'estado' => 'activo',
            'two_factor_enabled' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(session('two_factor_passed'));
    }

    public function test_invalid_credentials_return_validation_errors(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'missing-user@example.test',
            'password' => 'incorrect',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->get(route('login'))->assertSee('is-invalid', false);
    }

    public function test_two_factor_page_renders_accessible_code_and_resend_controls(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => false])
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('two-factor-page', false)
            ->assertSee('Verificación en dos pasos')
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('autocomplete="one-time-code"', false)
            ->assertSee('aria-describedby="two-factor-instruction"', false)
            ->assertSee('¿No recibiste el código?')
            ->assertSee('action="'.route('two-factor.verify').'"', false)
            ->assertSee('action="'.route('two-factor.resend').'"', false);
    }
}
