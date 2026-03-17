<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\LibraryItem;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InformationHubAdminController extends Controller
{
    public function libraryIndex(Request $request): Response
    {
        $user = $request->user()->load('organization');
        $isSuperadmin = $user->hasRole('Superadmin');

        $libraryItems = LibraryItem::query()
            ->with('organization:id,name,slug')
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (LibraryItem $item) => [
                'id' => $item->id,
                'organization_id' => $item->organization_id,
                'organization_name' => $item->organization?->name,
                'title' => $item->title,
                'description' => $item->description,
                'file_path' => $item->file_path,
                'cover_image_path' => $item->cover_image_path,
                'category' => $item->category,
                'created_at' => $item->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/LibraryManage', [
            'isSuperadmin' => $isSuperadmin,
            'defaultOrganizationId' => $user->current_organization_id,
            'organizations' => $isSuperadmin
                ? Organization::query()->orderBy('min_age')->get(['id', 'name', 'slug'])
                : collect([[
                    'id' => $user->organization?->id,
                    'name' => $user->organization?->name,
                    'slug' => $user->organization?->slug,
                ]]),
            'libraryItems' => $libraryItems,
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user()->load('organization');
        $isSuperadmin = $user->hasRole('Superadmin');

        $announcements = Announcement::query()
            ->with('organization:id,name,slug')
            ->latest('published_at')
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn (Announcement $item) => [
                'id' => $item->id,
                'organization_id' => $item->organization_id,
                'organization_name' => $item->organization?->name,
                'title' => $item->title,
                'content' => $item->content,
                'is_pinned' => $item->is_pinned,
                'published_at' => $item->published_at?->toDateTimeString(),
                'published_human' => $item->published_at?->locale('ms')->isoFormat('D MMM YYYY, h:mm A'),
            ]);

        $libraryItems = LibraryItem::query()
            ->with('organization:id,name,slug')
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (LibraryItem $item) => [
                'id' => $item->id,
                'organization_id' => $item->organization_id,
                'organization_name' => $item->organization?->name,
                'title' => $item->title,
                'description' => $item->description,
                'file_path' => $item->file_path,
                'cover_image_path' => $item->cover_image_path,
                'category' => $item->category,
                'created_at' => $item->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/InformationHubManage', [
            'isSuperadmin' => $isSuperadmin,
            'defaultOrganizationId' => $user->current_organization_id,
            'organizations' => $isSuperadmin
                ? Organization::query()->orderBy('min_age')->get(['id', 'name', 'slug'])
                : collect([[
                    'id' => $user->organization?->id,
                    'name' => $user->organization?->name,
                    'slug' => $user->organization?->slug,
                ]]),
            'announcements' => $announcements,
            'libraryItems' => $libraryItems,
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $organizationId = $this->resolveOrganizationId($user, $data['organization_id'] ?? null);

        Announcement::create([
            'organization_id' => $organizationId,
            'title' => $data['title'],
            'content' => $data['content'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'published_at' => $data['published_at'] ?? now(),
        ]);

        return back()->with('success', 'Pengumuman berjaya diterbitkan.');
    }

    public function togglePinned(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrganizationAccess($request->user(), $announcement->organization_id);

        $announcement->update([
            'is_pinned' => ! $announcement->is_pinned,
        ]);

        return back()->with('success', 'Status pinned pengumuman dikemas kini.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrganizationAccess($request->user(), $announcement->organization_id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $announcement->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'published_at' => $data['published_at'] ?? $announcement->published_at,
        ]);

        return back()->with('success', 'Pengumuman berjaya dikemas kini.');
    }

    public function destroyAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrganizationAccess($request->user(), $announcement->organization_id);

        $announcement->delete();

        return back()->with('success', 'Pengumuman berjaya dipadam.');
    }

    public function storeLibraryItem(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $organizationId = $this->resolveOrganizationId($user, $data['organization_id'] ?? null);

        $path = $request->file('pdf_file')->store('library', 'public');
        $coverPath = $request->hasFile('cover_image')
            ? '/storage/' . ltrim($request->file('cover_image')->store('library/covers', 'public'), '/')
            : null;

        LibraryItem::create([
            'organization_id' => $organizationId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'Umum',
            'file_path' => '/storage/' . ltrim($path, '/'),
            'cover_image_path' => $coverPath,
        ]);

        return back()->with('success', 'Dokumen PDF berjaya dimuat naik.');
    }

    public function destroyLibraryItem(Request $request, LibraryItem $libraryItem): RedirectResponse
    {
        $this->authorizeOrganizationAccess($request->user(), $libraryItem->organization_id);

        $path = ltrim(str_replace('/storage/', '', parse_url($libraryItem->file_path, PHP_URL_PATH) ?? ''), '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $coverPath = ltrim(str_replace('/storage/', '', parse_url($libraryItem->cover_image_path ?? '', PHP_URL_PATH) ?? ''), '/');

        if ($coverPath !== '' && Storage::disk('public')->exists($coverPath)) {
            Storage::disk('public')->delete($coverPath);
        }

        $libraryItem->delete();

        return back()->with('success', 'Dokumen PDF berjaya dipadam.');
    }

    public function updateLibraryItem(Request $request, LibraryItem $libraryItem): RedirectResponse
    {
        $this->authorizeOrganizationAccess($request->user(), $libraryItem->organization_id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $filePath = $libraryItem->file_path;
        $coverImagePath = $libraryItem->cover_image_path;

        if ($request->hasFile('pdf_file')) {
            $oldPath = ltrim(str_replace('/storage/', '', parse_url($libraryItem->file_path, PHP_URL_PATH) ?? ''), '/');
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $newPath = $request->file('pdf_file')->store('library', 'public');
            $filePath = '/storage/' . ltrim($newPath, '/');
        }

        if ($request->hasFile('cover_image')) {
            $oldCoverPath = ltrim(str_replace('/storage/', '', parse_url($libraryItem->cover_image_path ?? '', PHP_URL_PATH) ?? ''), '/');
            if ($oldCoverPath !== '' && Storage::disk('public')->exists($oldCoverPath)) {
                Storage::disk('public')->delete($oldCoverPath);
            }

            $newCoverPath = $request->file('cover_image')->store('library/covers', 'public');
            $coverImagePath = '/storage/' . ltrim($newCoverPath, '/');
        }

        $libraryItem->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'Umum',
            'file_path' => $filePath,
            'cover_image_path' => $coverImagePath,
        ]);

        return back()->with('success', 'Dokumen PDF berjaya dikemas kini.');
    }

    private function resolveOrganizationId($user, ?int $submittedOrganizationId): int
    {
        if ($user->hasRole('Superadmin')) {
            return $submittedOrganizationId ?: (int) $user->current_organization_id;
        }

        return (int) $user->current_organization_id;
    }

    private function authorizeOrganizationAccess($user, int $organizationId): void
    {
        if ($user->hasRole('Superadmin')) {
            return;
        }

        abort_if((int) $user->current_organization_id !== (int) $organizationId, 403);
    }
}
