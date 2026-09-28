<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Logins: who can create, change and switch them off, and what a switched-off
 * login can still do (nothing).
 */
class LoginManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'a-long-enough-password';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $slug, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('slug', $slug)->value('id'),
            'is_active' => true,
        ], $overrides));
    }

    private function roleId(string $slug): int
    {
        return Role::where('slug', $slug)->value('id');
    }

    /* ------------------------------------------------------------ signing in */

    public function test_a_switched_off_login_cannot_sign_in(): void
    {
        $this->userWithRole(Role::SALES, ['email' => 'off@example.com', 'password' => self::PASSWORD, 'is_active' => false]);

        $this->post('/login', ['email' => 'off@example.com', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_active_login_signs_in(): void
    {
        $this->userWithRole(Role::SALES, ['email' => 'on@example.com', 'password' => self::PASSWORD]);

        $this->post('/login', ['email' => 'on@example.com', 'password' => self::PASSWORD])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_someone_switched_off_mid_session_is_signed_out_even_from_the_overview(): void
    {
        $user = $this->userWithRole(Role::STAFF, ['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_sign_in_screens_all_exist(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false);
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/some-token?email=a@example.com')->assertOk();
    }

    public function test_the_account_page_asks_for_the_password_first(): void
    {
        $user = $this->userWithRole(Role::STAFF);

        $this->actingAs($user)->get('/dashboard/account')->assertRedirect(route('password.confirm'));

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/dashboard/account')
            ->assertOk()
            ->assertSee('Change your password')
            ->assertSee('Two-step sign in')
            ->assertSee('Passkeys');
    }

    public function test_anyone_can_change_their_own_password(): void
    {
        $user = $this->userWithRole(Role::STAFF, ['password' => self::PASSWORD]);

        $this->actingAs($user)->put('/user/password', [
            'current_password' => self::PASSWORD,
            'password' => 'my-brand-new-password',
            'password_confirmation' => 'my-brand-new-password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('my-brand-new-password', $user->fresh()->password));
    }

    /* -------------------------------------------------------- the Logins page */

    public function test_management_creates_a_login_linked_to_the_rota(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $profile = StaffProfile::create(['full_name' => 'Bilal Ahmed', 'employment_type' => 'part_time', 'department' => 'kitchen', 'is_active' => true]);

        $this->actingAs($manager)->post('/dashboard/logins', [
            'name' => 'Bilal Ahmed',
            'email' => 'bilal@example.com',
            'role_id' => $this->roleId(Role::STAFF),
            'staff_profile_id' => $profile->id,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertSessionHasNoErrors();

        $login = User::where('email', 'bilal@example.com')->firstOrFail();

        $this->assertTrue(Hash::check(self::PASSWORD, $login->password));
        $this->assertSame(Role::STAFF, $login->role->slug);
        $this->assertSame($login->id, $profile->fresh()->user_id);
    }

    public function test_management_cannot_hand_out_management_or_owner_roles(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);

        $this->actingAs($manager)->post('/dashboard/logins', [
            'name' => 'Too High',
            'email' => 'high@example.com',
            'role_id' => $this->roleId(Role::OWNER),
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'high@example.com']);
    }

    public function test_management_cannot_change_another_manager_but_the_owner_can(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $otherManager = $this->userWithRole(Role::MANAGEMENT);
        $owner = $this->userWithRole(Role::OWNER);

        $payload = [
            'name' => $otherManager->name,
            'email' => $otherManager->email,
            'role_id' => $this->roleId(Role::MANAGEMENT),
            'is_active' => 0,
        ];

        $this->actingAs($manager)->patch("/dashboard/logins/{$otherManager->id}", $payload)->assertForbidden();
        $this->assertTrue($otherManager->fresh()->is_active);

        $this->actingAs($owner)->patch("/dashboard/logins/{$otherManager->id}", $payload)->assertSessionHasNoErrors();
        $this->assertFalse($otherManager->fresh()->is_active);
    }

    public function test_nobody_can_switch_their_own_login_off_from_the_logins_page(): void
    {
        $owner = $this->userWithRole(Role::OWNER);

        $this->actingAs($owner)->patch("/dashboard/logins/{$owner->id}", [
            'name' => $owner->name,
            'email' => $owner->email,
            'role_id' => $owner->role_id,
            'is_active' => 0,
        ])->assertForbidden();

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_resetting_a_password_sets_it_and_signs_them_out(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $cook = $this->userWithRole(Role::STAFF, ['remember_token' => 'still-remembered']);

        $this->actingAs($manager)->post("/dashboard/logins/{$cook->id}/password", [
            'password' => 'fresh-password-for-you',
            'password_confirmation' => 'fresh-password-for-you',
        ])->assertSessionHasNoErrors();

        $cook->refresh();
        $this->assertTrue(Hash::check('fresh-password-for-you', $cook->password));
        $this->assertNull($cook->remember_token);
    }

    public function test_passwords_must_be_at_least_twelve_characters(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $cook = $this->userWithRole(Role::STAFF);

        $this->actingAs($manager)->post("/dashboard/logins/{$cook->id}/password", [
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ])->assertSessionHasErrors('password');
    }

    public function test_the_logins_page_is_for_management_and_above(): void
    {
        $this->actingAs($this->userWithRole(Role::MANAGER))->get('/dashboard/logins')->assertForbidden();
        $this->actingAs($this->userWithRole(Role::MANAGEMENT))->get('/dashboard/logins')->assertOk();
    }

    public function test_archiving_a_staff_member_switches_their_login_off(): void
    {
        $manager = $this->userWithRole(Role::MANAGEMENT);
        $cook = $this->userWithRole(Role::STAFF);
        $profile = StaffProfile::create(['user_id' => $cook->id, 'full_name' => 'Leaving Soon', 'employment_type' => 'full_time', 'department' => 'kitchen', 'is_active' => true]);

        $this->actingAs($manager)->delete("/dashboard/staff/{$profile->id}")->assertRedirect();

        $this->assertFalse($cook->fresh()->is_active);
    }
}
