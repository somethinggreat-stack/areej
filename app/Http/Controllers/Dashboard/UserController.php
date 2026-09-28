<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Who can sign in, as what.
 *
 * A staff profile is the person on the rota; a login is how they reach the
 * dashboard. They are linked here so a staff member can see their own
 * timesheet. Management looks after every login below management; only the
 * owner can hand out or change management and owner logins.
 */
class UserController extends Controller
{
    public function __construct(private readonly Activity $activity) {}

    public function index(Request $request): View
    {
        $actor = $request->user();

        $users = User::query()
            ->with(['role', 'staffProfile'])
            ->when($request->query('filter') === 'off', fn ($query) => $query->where('is_active', false),
                fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('dashboard.users.index', [
            'users' => $users,
            'filter' => $request->query('filter', ''),
            'roles' => $actor->assignableRoles(),
            'unlinkedStaff' => $this->linkableStaff(),
            'actor' => $actor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'role_id' => ['required', Rule::in($actor->assignableRoles()->pluck('id'))],
            'staff_profile_id' => ['nullable', Rule::in($this->linkableStaff()->pluck('id'))],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $data['role_id'],
                'locale' => 'en',
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->linkStaff($user, $data['staff_profile_id'] ?? null);

            return $user;
        });

        $this->activity->created($user, __('Login created for :name (:role)', [
            'name' => $user->name,
            'role' => $user->role->name_en,
        ]));

        return redirect()->route('users')->with('status', __('Login created for :name. Give them the password in person, and ask them to change it from their Account page.', ['name' => $user->name]));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->canManageLogin($user), 403, __('You cannot change this login.'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', Rule::in($actor->assignableRoles()->pluck('id'))],
            'staff_profile_id' => ['nullable', Rule::in($this->linkableStaff($user)->pluck('id'))],
            'is_active' => ['required', 'boolean'],
        ]);

        $before = $user->getOriginal();

        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'is_active' => (bool) $data['is_active'],
            ]);

            $this->linkStaff($user, $data['staff_profile_id'] ?? null);

            if (! $user->is_active) {
                $this->signOutEverywhere($user);
            }
        });

        $this->activity->updated($user, __('Login updated: :name', ['name' => $user->name]), $before);

        return back()->with('status', $user->is_active
            ? __('Saved.')
            : __(':name can no longer sign in, and has been signed out.', ['name' => $user->name]));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageLogin($user), 403, __('You cannot change this login.'));

        $data = $request->validate([
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user->forceFill(['password' => $data['password']])->save();
        $this->signOutEverywhere($user);

        $this->activity->log('password_reset', __('Password reset for :name', ['name' => $user->name]), $user);

        return back()->with('status', __('New password set for :name. They have been signed out everywhere.', ['name' => $user->name]));
    }

    /**
     * Staff profiles not yet tied to a login, plus the one this login already has.
     *
     * @return Collection<int, StaffProfile>
     */
    private function linkableStaff(?User $user = null): Collection
    {
        return StaffProfile::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('user_id')
                ->when($user, fn ($inner) => $inner->orWhere('user_id', $user->id)))
            ->orderBy('full_name')
            ->get();
    }

    private function linkStaff(User $user, int|string|null $staffProfileId): void
    {
        StaffProfile::where('user_id', $user->id)
            ->when($staffProfileId, fn ($query) => $query->where('id', '!=', $staffProfileId))
            ->update(['user_id' => null]);

        if ($staffProfileId) {
            StaffProfile::whereKey($staffProfileId)->update(['user_id' => $user->id]);
        }
    }

    /**
     * Ends every session and "keep me signed in" cookie the person holds, so a
     * password change or a switch-off takes effect on every device at once.
     */
    private function signOutEverywhere(User $user): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => null])->save();
    }
}
