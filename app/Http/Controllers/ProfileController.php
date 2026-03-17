<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile journey page (transition timeline).
     *
     * We explicitly call withoutGlobalScopes() on the history query because the
     * UserTransitionHistory model has no global scope, but we want to be safe;
     * and we eager-load org names to avoid N+1 on the timeline cards.
     */
    public function show(Request $request): Response
    {
        $user = $request->user()->load(['organization']);
        $isSuperadmin = $user->hasRole(['Superadmin', 'Admin']);

        $history = $user->transitionHistory()
            ->get()
            ->map(fn ($record) => [
                'id'                   => $record->id,
                'from_organization'    => $record->fromOrganization
                    ? ['id' => $record->fromOrganization->id, 'name' => $record->fromOrganization->name, 'slug' => $record->fromOrganization->slug, 'color_theme' => $record->fromOrganization->color_theme]
                    : null,
                'to_organization'      => ['id' => $record->toOrganization->id, 'name' => $record->toOrganization->name, 'slug' => $record->toOrganization->slug, 'color_theme' => $record->toOrganization->color_theme],
                'transitioned_at'      => $record->transitioned_at->toISOString(),
                'transitioned_at_human'=> $record->transitioned_at->translatedFormat('d F Y'),
            ]);

        return Inertia::render('Profile/Show', [
            'profileUser' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'roles'        => $user->getRoleNames()->values(),
                'dob'          => $isSuperadmin ? null : $user->dob?->format('d M Y'),
                'age'          => $isSuperadmin ? null : $user->dob?->age,
                'organization' => $user->organization ? [
                    'id'          => $user->organization->id,
                    'name'        => $user->organization->name,
                    'slug'        => $user->organization->slug,
                    'color_theme' => $user->organization->color_theme,
                ] : null,
            ],
            'history' => $history,
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Force-update screen for members with incomplete profile data.
     */
    public function completeProfile(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_completed_at) {
            return Redirect::route('dashboard');
        }

        return Inertia::render('Member/CompleteProfile');
    }

    /**
     * Persist forced profile completion fields and continue to dashboard.
     */
    public function storeCompleteProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'education_level' => ['required', 'string', 'max:120'],
            'current_profession' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $request->user()->update([
            ...$data,
            'profile_completed_at' => now(),
        ]);

        return Redirect::route('dashboard')->with('success', 'Profil berjaya dikemas kini.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_public_in_directory'] = $request->boolean('is_public_in_directory');

        if ($request->hasFile('profile_photo')) {
            $oldPath = ltrim(str_replace('/storage/', '', parse_url((string) $request->user()->profile_photo_path, PHP_URL_PATH) ?? ''), '/');
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $newPath = $request->file('profile_photo')->store('profiles', 'public');
            $validated['profile_photo_path'] = '/storage/' . ltrim($newPath, '/');
        }

        if (
            ! $request->user()->profile_completed_at
            && ! empty($validated['phone'])
            && ! empty($validated['education_level'])
            && ! empty($validated['current_profession'])
        ) {
            $validated['profile_completed_at'] = now();
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
