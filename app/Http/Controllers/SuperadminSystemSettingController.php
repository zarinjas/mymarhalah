<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SuperadminSystemSettingController extends Controller
{
    public function index(): Response
    {
        $canManageSystemLogo = Schema::hasTable('app_settings');
        $setting = $canManageSystemLogo ? AppSetting::singleton() : null;

        return Inertia::render('Superadmin/SystemSettings', [
            'systemLogoPath' => $this->normalizeStorageUrl($setting?->system_logo_path),
            'canManageSystemLogo' => $canManageSystemLogo,
        ]);
    }

    public function updateSystemLogo(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_settings')) {
            return back()->with('error', 'Sila jalankan migration terlebih dahulu untuk tetapan MyMarhalah.');
        }

        $data = $request->validate([
            'system_logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $setting = AppSetting::singleton();

        if ($setting->system_logo_path) {
            $oldPath = ltrim(str_replace('/storage/', '', parse_url((string) $setting->system_logo_path, PHP_URL_PATH) ?? ''), '/');
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $storedPath = $data['system_logo']->store('logos/system', 'public');

        $setting->update([
            'system_logo_path' => '/storage/' . ltrim($storedPath, '/'),
        ]);

        return back()->with('success', 'Logo MyMarhalah berjaya dikemas kini.');
    }

    private function normalizeStorageUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parsedPath = parse_url($url, PHP_URL_PATH);

        if (is_string($parsedPath) && str_starts_with($parsedPath, '/storage/')) {
            return $parsedPath;
        }

        return $url;
    }
}
