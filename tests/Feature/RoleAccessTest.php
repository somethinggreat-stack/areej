<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->firstOrFail()->id,
        ]);
    }

    public function test_owner_can_see_financials(): void
    {
        $this->assertTrue($this->userWithRole(Role::OWNER)->canSeeFinancials());
    }

    public function test_management_can_see_financials(): void
    {
        $this->assertTrue($this->userWithRole(Role::MANAGEMENT)->canSeeFinancials());
    }

    public function test_kitchen_manager_cannot_see_financials(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);

        $this->assertFalse($manager->canSeeFinancials());
        $this->assertTrue($manager->canManageInventory());
    }

    public function test_staff_cannot_see_financials_or_manage_inventory(): void
    {
        $staff = $this->userWithRole(Role::STAFF);

        $this->assertFalse($staff->canSeeFinancials());
        $this->assertFalse($staff->canManageInventory());
    }

    public function test_user_without_role_is_denied_everything(): void
    {
        $orphan = User::factory()->create(['role_id' => null]);

        $this->assertFalse($orphan->canSeeFinancials());
        $this->assertFalse($orphan->canManageInventory());
        $this->assertFalse($orphan->isOwner());
    }
}
