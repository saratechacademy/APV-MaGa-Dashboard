<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_manual_renders_in_both_languages_and_covers_sources_and_alerts(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);

        $this->actingAs($user)->get('/help?lang=fr')
            ->assertOk()
            ->assertSee('Sources de données & Alertes')
            ->assertSee('LI-COR (HOBOnet)')
            ->assertSee('id="section-9"', false);

        $this->actingAs($user)->get('/help?lang=en')
            ->assertOk()
            ->assertSee('Data Sources & Alerts')
            ->assertSee('E-mail alerts');
    }

    public function test_the_developer_docs_render_in_both_languages_without_printing_a_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);

        foreach (['fr', 'en'] as $lang) {
            $this->actingAs($admin)->get("/developer-docs?lang={$lang}")
                ->assertOk()
                ->assertSee('thingsboard:sync')
                ->assertSee('git pull origin main')
                ->assertDontSee('apvmaga@2026');
        }
    }
}
