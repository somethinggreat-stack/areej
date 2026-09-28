<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Everyone's own security settings: password, two-step sign in, passkeys.
 *
 * The forms post straight to Fortify and the passkeys package, which do the
 * work; this page only shows where each one stands. It sits behind a password
 * confirmation, as those endpoints do.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $twoFactorPending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('dashboard.account.show', [
            'user' => $user->load('role'),
            'twoFactorOn' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $twoFactorPending,
            'qrCode' => $twoFactorPending ? $user->twoFactorQrCodeSvg() : null,
            'setupKey' => $twoFactorPending ? decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $user->two_factor_secret !== null && in_array(session('status'), [
                'two-factor-authentication-confirmed',
                'recovery-codes-generated',
            ], true) ? $user->recoveryCodes() : [],
            'passkeys' => $user->passkeys()->latest()->get(),
        ]);
    }
}
