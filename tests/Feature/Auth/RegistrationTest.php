<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_registration_screen_shows_billed_plan_prices(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/Register')
            ->where('plans.FREE.price', 0)
            ->where('plans.MIDI.price', 2900)
            ->where('plans.MIDI.price_yearly', 29000)
            ->where('plans.MAXI.price', 7900)
            ->where('plans.MAXI.price_yearly', 79000)
        );
    }

    public function test_new_users_can_register_with_company_and_industry(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_type' => 'create',
            'company_name' => 'Acme Estate Agents',
            'industry' => 'estate-agents',
            'subscription_type' => 'FREE',
            'billing_interval' => 'month',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('taskit_companies', [
            'name' => 'Acme Estate Agents',
            'industry' => 'estate-agents',
        ]);
    }

    public function test_company_industry_controls_todo_type_options(): void
    {
        $company = Company::create([
            'name' => 'Dev Co',
            'industry' => 'software-development',
            'subscription_type' => 'FREE',
        ]);

        $types = \App\Support\Industries::typesFor($company->industry);

        $this->assertSame(['Bug', 'Feature', 'Task', 'Story', 'Epic', 'Compliance', 'Other'], $types);
    }
}
