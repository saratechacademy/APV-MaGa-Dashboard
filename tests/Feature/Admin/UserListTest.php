<?php

namespace Tests\Feature\Admin;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Sites" column of the user list used to count owned sites only, which
 * showed 0 for an administrator who can open every site.
 */
class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_column_shows_what_each_user_can_actually_open(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'name' => 'Root Admin']);
        $agent = User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'name' => 'Field Agent']);
        User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active', 'name' => 'New Observer']);

        $owned = Site::factory()->create(['user_id' => $agent->id]);
        $owned->users()->attach($agent->id, ['role' => User::ROLE_AGENT]);   // owner and assigned: still one site
        Site::factory()->create(['user_id' => $admin->id])->users()->attach($agent->id, ['role' => User::ROLE_OBSERVER]);

        $html = $this->actingAs($admin)->get(route('admin.users'))->assertOk()->getContent();

        $sitesCell = function (string $name) use ($html) {
            $row = substr($html, strpos($html, $name));
            preg_match('/Sites this user owns|Administrators have access/', $row, $m, PREG_OFFSET_CAPTURE);
            $cell = substr($row, $m[0][1]);

            return trim(strip_tags(substr($cell, strpos($cell, '>') + 1, strpos($cell, '</td>') - strpos($cell, '>') - 1)));
        };

        $this->assertSame('All', $sitesCell('Root Admin'));
        $this->assertSame('2', $sitesCell('Field Agent'));
        $this->assertSame('0', $sitesCell('New Observer'));
    }
}
