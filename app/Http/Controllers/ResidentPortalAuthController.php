<?php

namespace App\Http\Controllers;

use App\Exceptions\ActivationRateLimitedException;
use App\Exceptions\BhwisUnavailableException;
use App\Exceptions\ResidentAlreadyActivatedException;
use App\Exceptions\ResidentIdentityMismatchException;
use App\Models\Resident;
use App\Services\Bhwis\ResidentActivationService;
use App\Services\ResidentEmailVerificationService;
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
        return view('resident-portal.auth.register', [
            'emailChallengeId' => old('email_challenge_id', session('resident_email_challenge_id')),
            'emailChallengeAddress' => old('email', session('resident_email_challenge_address')),
        ]);
    }

    public function sendEmailCode(Request $request, ResidentEmailVerificationService $verification): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        try {
            $challenge = $verification->send($validated, $request);
        } catch (ResidentIdentityMismatchException) {
            return back()->withInput($request->only(['resident_id', 'last_name', 'birth_date', 'email']))->withErrors(['resident_id' => 'No matching resident record was found. Check the Resident ID/PIN, last name (surname), and birth date exactly as recorded in the local resident record.']);
        } catch (ResidentAlreadyActivatedException) {
            return back()->withInput($request->only(['resident_id', 'last_name', 'birth_date', 'email']))->withErrors(['resident_id' => 'This resident account is already activated.']);
        } catch (BhwisUnavailableException) {
            return back()->withInput($request->only(['resident_id', 'last_name', 'birth_date', 'email']))->withErrors(['resident_id' => 'Resident verification is temporarily unavailable. Please try again later.']);
        }

        return back()->withInput($request->only(['resident_id', 'last_name', 'birth_date', 'email']))->with([
            'resident_email_challenge_id' => $challenge->challenge_id,
            'resident_email_challenge_address' => $challenge->email,
            'status' => 'A six-digit confirmation code was sent to '.$challenge->email.'.',
        ]);
    }

    public function showForgotMpin(): View
    {
        return view('resident-portal.auth.forgot-mpin');
    }

    public function resetMpin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'mpin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $resident = Resident::query()
            ->where('resident_id', trim($validated['resident_id']))
            ->whereDate('birth_date', $validated['birth_date'])
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($validated['last_name']))])
            ->where('is_active', true)
            ->first();

        if (! $resident) {
            return back()->withInput($request->except(['mpin', 'mpin_confirmation']))
                ->withErrors(['resident_id' => 'We could not verify those resident details. Please check them or contact your barangay office.']);
        }

        $resident->forceFill(['mpin' => Hash::make($validated['mpin'])])->save();

        return redirect()->route('resident-portal.login')->with('status', 'Your MPIN has been reset. You can now sign in.');
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

    public function register(Request $request, ResidentActivationService $activation, ResidentEmailVerificationService $verification): RedirectResponse
    {
        $validated = $request->validate([
            'resident_id' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'email_challenge_id' => ['required', 'uuid'],
            'email_code' => ['required', 'digits:6'],
            'mpin' => ['required', 'digits:6', 'confirmed'],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_notice_acknowledged' => ['required', 'accepted'],
            'bhwis_import_consented' => ['required', 'accepted'],
        ]);

        $challenge = $verification->verify($validated);

        try {
            $resident = $activation->activate($validated, $request, 'web');
        } catch (ResidentIdentityMismatchException) {
            return back()->withInput($request->except(['mpin', 'mpin_confirmation']))
                ->withErrors(['resident_id' => 'No matching resident record was found. Check the Resident ID/PIN, last name (surname), and birth date exactly as recorded in the local resident record.']);
        } catch (ResidentAlreadyActivatedException) {
            return back()->withErrors(['resident_id' => 'This resident account is already activated.']);
        } catch (BhwisUnavailableException) {
            return back()->withInput($request->except(['mpin', 'mpin_confirmation']))
                ->withErrors(['resident_id' => 'Resident verification is temporarily unavailable. Please try again later.'])
                ->setStatusCode(503);
        } catch (ActivationRateLimitedException $e) {
            return back()->withErrors(['resident_id' => 'Too many activation attempts. Please try again later.'])
                ->setStatusCode(429);
        }

        $verification->consume($challenge);

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
