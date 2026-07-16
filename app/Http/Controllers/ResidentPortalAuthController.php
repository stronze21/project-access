<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ResidentPortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('resident-portal.auth.login');
    }

    public function showRegister(): View
    {
        return view('resident-portal.auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'mpin' => ['required', 'digits:6'],
        ]);

        $resident = Resident::query()
            ->where('resident_id', $validated['login'])
            ->orWhere('email', $validated['login'])
            ->orWhere('contact_number', $validated['login'])
            ->first();

        $usesBirthdayFallback = $resident
            && ! $resident->mpin
            && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $validated['mpin']);

        if (! $resident || (! $usesBirthdayFallback && (! $resident->mpin || ! Hash::check($validated['mpin'], $resident->mpin)))) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => 'The provided credentials are incorrect.']);
        }

        if (! $resident->is_active) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => 'Your account is inactive. Please contact your barangay office.']);
        }

        Auth::guard('resident')->login($resident, true);
        $request->session()->regenerate();
        $request->session()->put([
            'resident_portal_authenticated_at' => now(),
            'resident_portal_expires_at' => now()->addDays(60),
            'resident_portal_requires_mpin_update' => $usesBirthdayFallback,
        ]);
        $resident->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('resident-portal.home'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'mpin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $resident = Resident::query()
            ->where('resident_id', $validated['resident_id'])
            ->whereRaw('LOWER(last_name) = ?', [strtolower($validated['last_name'])])
            ->whereDate('birth_date', $validated['birth_date'])
            ->first();

        if (! $resident) {
            return back()->withInput($request->except(['mpin', 'mpin_confirmation']))
                ->withErrors(['resident_id' => 'No matching resident record was found.']);
        }

        if ($resident->mpin || $resident->password) {
            return back()->withErrors(['resident_id' => 'This resident account is already activated.']);
        }

        if (! $resident->is_active) {
            return back()->withErrors(['resident_id' => 'This resident record is inactive.']);
        }

        $resident->forceFill(['mpin' => Hash::make($validated['mpin']), 'last_login_at' => now()])->save();
        Auth::guard('resident')->login($resident, true);
        $request->session()->regenerate();
        $request->session()->put([
            'resident_portal_authenticated_at' => now(),
            'resident_portal_expires_at' => now()->addDays(60),
            'resident_portal_requires_mpin_update' => false,
        ]);

        return redirect()->route('resident-portal.home')->with('status', 'Your resident account is now active.');
    }

    public function changeMpin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_mpin' => ['required', 'digits:6'],
            'mpin' => ['required', 'digits:6', 'confirmed', 'different:current_mpin'],
        ]);

        /** @var Resident $resident */
        $resident = Auth::guard('resident')->user();
        $birthdayFallback = ! $resident->mpin && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $validated['current_mpin']);

        if (! $birthdayFallback && (! $resident->mpin || ! Hash::check($validated['current_mpin'], $resident->mpin))) {
            return back()->withErrors(['current_mpin' => 'The current MPIN is incorrect.']);
        }

        $resident->forceFill(['mpin' => Hash::make($validated['mpin'])])->save();
        $request->session()->put('resident_portal_requires_mpin_update', false);

        return back()->with('status', 'MPIN updated successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('resident')->logout();
        $request->session()->forget(['resident_portal_authenticated_at', 'resident_portal_expires_at', 'resident_portal_requires_mpin_update']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('resident-portal.login');
    }
}
