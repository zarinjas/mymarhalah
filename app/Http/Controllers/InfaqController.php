<?php

namespace App\Http\Controllers;

use App\Models\Infaq;
use App\Models\InfaqDonation;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InfaqController extends Controller
{
    // ─── Superadmin: list all infaq ─────────────────────────────────────────

    public function index(): Response
    {
        $items = Infaq::query()
            ->with('organization:id,name,slug')
            ->withCount('donations')
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Infaq $infaq) => [
                'id'               => $infaq->id,
                'title'            => $infaq->title,
                'description'      => $infaq->description,
                'image_path'       => $infaq->image_path,
                'type'             => $infaq->type,
                'target_amount'    => $infaq->target_amount,
                'collected_amount' => $infaq->collected_amount,
                'progress_percent' => $infaq->progress_percent,
                'is_active'        => $infaq->is_active,
                'display_order'    => $infaq->display_order,
                'organization_id'  => $infaq->organization_id,
                'organization_name'=> $infaq->organization?->name ?? 'Global',
                'donations_count'  => $infaq->donations_count,
            ]);

        return Inertia::render('Superadmin/InfaqManage', [
            'organizations' => Organization::query()->orderBy('min_age')->get(['id', 'name', 'slug']),
            'infaqItems'    => $items,
        ]);
    }

    // ─── Superadmin: create ──────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'type'            => ['required', 'in:one_off,progress'],
            'target_amount'   => ['nullable', 'numeric', 'min:1', 'max:9999999'],
            'is_active'       => ['nullable', 'boolean'],
            'display_order'   => ['nullable', 'integer', 'min:1', 'max:9999'],
            'infaq_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $imagePath = null;
        if ($request->hasFile('infaq_image')) {
            $storedPath = $request->file('infaq_image')->store('infaq', 'public');
            $imagePath  = '/storage/' . ltrim($storedPath, '/');
        }

        Infaq::create([
            'organization_id' => $data['organization_id'] ?? null,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'image_path'      => $imagePath,
            'type'            => $data['type'],
            'target_amount'   => $data['type'] === 'progress' ? ($data['target_amount'] ?? null) : null,
            'collected_amount'=> 0,
            'is_active'       => (bool) ($data['is_active'] ?? true),
            'display_order'   => (int) ($data['display_order'] ?? 1),
        ]);

        return back()->with('success', 'Infaq berjaya dicipta.');
    }

    // ─── Superadmin: update ──────────────────────────────────────────────────

    public function update(Request $request, Infaq $infaq): RedirectResponse
    {
        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'type'            => ['required', 'in:one_off,progress'],
            'target_amount'   => ['nullable', 'numeric', 'min:1', 'max:9999999'],
            'is_active'       => ['nullable', 'boolean'],
            'display_order'   => ['nullable', 'integer', 'min:1', 'max:9999'],
            'infaq_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $imagePath = $infaq->image_path;

        if ($request->hasFile('infaq_image')) {
            // Delete old image
            if ($imagePath) {
                $oldRel = ltrim(str_replace('/storage/', '', parse_url($imagePath, PHP_URL_PATH) ?? ''), '/');
                if ($oldRel && Storage::disk('public')->exists($oldRel)) {
                    Storage::disk('public')->delete($oldRel);
                }
            }
            $newPath   = $request->file('infaq_image')->store('infaq', 'public');
            $imagePath = '/storage/' . ltrim($newPath, '/');
        }

        $infaq->update([
            'organization_id' => $data['organization_id'] ?? null,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'image_path'      => $imagePath,
            'type'            => $data['type'],
            'target_amount'   => $data['type'] === 'progress' ? ($data['target_amount'] ?? null) : null,
            'is_active'       => (bool) ($data['is_active'] ?? false),
            'display_order'   => (int) ($data['display_order'] ?? 1),
        ]);

        return back()->with('success', 'Infaq berjaya dikemas kini.');
    }

    // ─── Superadmin: delete ──────────────────────────────────────────────────

    public function destroy(Infaq $infaq): RedirectResponse
    {
        if ($infaq->image_path) {
            $oldRel = ltrim(str_replace('/storage/', '', parse_url($infaq->image_path, PHP_URL_PATH) ?? ''), '/');
            if ($oldRel && Storage::disk('public')->exists($oldRel)) {
                Storage::disk('public')->delete($oldRel);
            }
        }

        $infaq->delete();

        return back()->with('success', 'Infaq berjaya dipadam.');
    }

    // ─── Superadmin: seed demo data ──────────────────────────────────────────

    public function seedDemo(): RedirectResponse
    {
        $seeds = [
            [
                'title'         => 'Infaq Masjid Al-Iman',
                'description'   => 'Bantu kami membina kemudahan solat yang lebih selesa untuk komuniti.',
                'type'          => 'progress',
                'target_amount' => 50000,
                'collected_amount' => 23750,
                'display_order' => 1,
            ],
            [
                'title'         => 'Infaq Anak Yatim Ramadan',
                'description'   => 'Sumbangan untuk anak-anak yatim sempena bulan Ramadan yang mulia.',
                'type'          => 'one_off',
                'target_amount' => null,
                'collected_amount' => 8100,
                'display_order' => 2,
            ],
            [
                'title'         => 'Dana Pendidikan Islam',
                'description'   => 'Tajaan kelas Quran & fardhu ain untuk pelajar kurang berkemampuan.',
                'type'          => 'progress',
                'target_amount' => 15000,
                'collected_amount' => 9600,
                'display_order' => 3,
            ],
            [
                'title'         => 'Infaq Buku & Pustaka',
                'description'   => 'Sumbangkan untuk pengembangan koleksi buku perpustakaan komuniti.',
                'type'          => 'progress',
                'target_amount' => 8000,
                'collected_amount' => 4200,
                'display_order' => 4,
            ],
            [
                'title'         => 'Infaq Am — Derma Bebas',
                'description'   => 'Sumbangan am untuk kegunaan operasi pertubuhan.',
                'type'          => 'one_off',
                'target_amount' => null,
                'collected_amount' => 3300,
                'display_order' => 5,
            ],
        ];

        $palettes = [
            ['from' => '#059669', 'to' => '#065f46', 'text' => '#d1fae5'],
            ['from' => '#6366f1', 'to' => '#3730a3', 'text' => '#e0e7ff'],
            ['from' => '#f59e0b', 'to' => '#b45309', 'text' => '#fef3c7'],
            ['from' => '#0ea5e9', 'to' => '#0369a1', 'text' => '#e0f2fe'],
            ['from' => '#ec4899', 'to' => '#9d174d', 'text' => '#fce7f3'],
        ];

        foreach ($seeds as $i => $seed) {
            $palette = $palettes[$i % count($palettes)];
            $shortTitle = mb_substr($seed['title'], 0, 30);

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$palette['from']};stop-opacity:1"/>
      <stop offset="100%" style="stop-color:{$palette['to']};stop-opacity:1"/>
    </linearGradient>
  </defs>
  <rect width="800" height="600" fill="url(#bg)"/>
  <circle cx="700" cy="80" r="180" fill="white" fill-opacity="0.06"/>
  <circle cx="120" cy="520" r="140" fill="white" fill-opacity="0.06"/>
  <text x="60" y="200" font-family="Arial, sans-serif" font-size="26" font-weight="bold" fill="{$palette['text']}" opacity="0.8">INFAQ</text>
  <text x="60" y="260" font-family="Arial, sans-serif" font-size="38" font-weight="900" fill="white">{$shortTitle}</text>
  <text x="60" y="320" font-family="Arial, sans-serif" font-size="20" fill="white" opacity="0.75">Derma &amp; Sumbangan</text>
  <rect x="60" y="380" width="120" height="4" rx="2" fill="white" fill-opacity="0.5"/>
</svg>
SVG;

            $filename  = 'infaq/demo_infaq_' . ($i + 1) . '.svg';
            Storage::disk('public')->put($filename, $svg);
            $imagePath = '/storage/' . ltrim($filename, '/');

            Infaq::updateOrCreate(
                ['title' => $seed['title']],
                array_merge($seed, [
                    'organization_id' => null,
                    'image_path'      => $imagePath,
                    'is_active'       => true,
                ])
            );
        }

        return back()->with('success', 'Demo infaq berjaya dijana (' . count($seeds) . ' item).');
    }

    // ─── Member: detail page ────────────────────────────────────────────────

    public function show(Request $request, Infaq $infaq): Response
    {
        $user = $request->user()->load('organization');

        $isVisible = (bool) $infaq->is_active
            && (
                $infaq->organization_id === null
                || (int) $infaq->organization_id === (int) $user->current_organization_id
            );

        abort_unless($isVisible, 404);

        $recentDonations = InfaqDonation::query()
            ->where('infaq_id', $infaq->id)
            ->where('status', 'confirmed')
            ->with('user:id,name')
            ->latest('created_at')
            ->take(12)
            ->get()
            ->map(fn (InfaqDonation $donation) => [
                'id' => $donation->id,
                'amount' => (float) $donation->amount,
                'created_at' => $donation->created_at->diffForHumans(),
                'donor_name' => $donation->user?->name ?? 'Anonymous',
            ]);

        return Inertia::render('Member/InfaqShow', [
            'infaq' => [
                'id' => $infaq->id,
                'title' => $infaq->title,
                'description' => $infaq->description,
                'image_path' => $infaq->image_path,
                'type' => $infaq->type,
                'target_amount' => $infaq->target_amount,
                'collected_amount' => $infaq->collected_amount,
                'progress_percent' => $infaq->progress_percent,
            ],
            'recentDonations' => $recentDonations,
        ]);
    }

    // ─── Member: submit a donation ───────────────────────────────────────────

    public function donate(Request $request, Infaq $infaq): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($infaq, $user, $data) {
            InfaqDonation::create([
                'infaq_id'  => $infaq->id,
                'user_id'   => $user->id,
                'amount'    => $data['amount'],
                'reference' => 'INFQ-' . strtoupper(Str::random(10)),
                'status'    => 'confirmed',   // simplified: direct confirm
            ]);

            // Increment collected_amount atomically
            $infaq->increment('collected_amount', $data['amount']);
        });

        return back()->with('success', 'Terima kasih! Derma anda sebanyak RM ' . number_format($data['amount'], 2) . ' telah berjaya dicatatkan.');
    }
}
