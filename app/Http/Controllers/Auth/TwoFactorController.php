<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    public function showTwoFactorSettings(Request $request)
    {
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();

        $QRImage = $google2fa->getQRCodeInline(
            config('app.name'),
            $request->user()->email,
            $secretKey
        );

        return inertia('settings/TwoFactor', [
            'QRImage' => $QRImage,
            'secretKey' => $secretKey,
            'enabled_2fa' => Auth::user()->enabled_2fa,
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $validated = $request->validate(['secret' => 'required', 'passcode' => 'required']);

        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($validated['secret'], $validated['passcode'])) {
            $request->user()->update(['google_2fa_secret' => $validated['secret'], 'enabled_2fa' => true]);
            return redirect()->route('settings.2fa')->with('success', 'Two-factor authentication enabled successfully.');
        }

        return back()->withErrors(['passcode' => 'Invalid passcode. Please try again.']);
    }

    public function disableTwoFactor(Request $request)
    {
        $request->user()->update(['google_2fa_secret' => null, 'enabled_2fa' => false]);
        return redirect()->route('settings.2fa')->with('success', 'Two-factor authentication disabled successfully.');
    }

    public function showVerifyPage(Request $request)
    {
        return inertia('auth/TwoFactorVerify');
    }

    public function verify(Request $request)
    {
        $validated = $request->validate(['passcode' => 'required']);

        $google2fa = new Google2FA();

        if ($google2fa->verifyKey(Auth::user()->google_2fa_secret, $validated['passcode'])) {
            $request->session()->put('2fa_passed', true);
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['passcode' => 'Invalid passcode. Please try again.']);
    }
}
