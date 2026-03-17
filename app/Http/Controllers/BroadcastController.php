<?php

namespace App\Http\Controllers;

use App\Jobs\SendBroadcastJob;
use App\Models\BroadcastMessage;
use App\Models\UsrahGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('Superadmin');

        $messages = BroadcastMessage::query()
            ->with(['organization:id,name', 'usrahGroup:id,name'])
            ->latest()
            ->take(20)
            ->get()
            ->map(fn (BroadcastMessage $message) => [
                'id' => $message->id,
                'title' => $message->title,
                'target_criteria' => $message->target_criteria,
                'organization_name' => $message->organization?->name,
                'usrah_group_name' => $message->usrahGroup?->name,
                'sent_at' => $message->sent_at?->toDateTimeString(),
            ]);

        $usrahGroups = UsrahGroup::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Broadcasts', [
            'recentMessages' => $messages,
            'usrahGroups' => $usrahGroups,
            'defaultOrganizationId' => $user->current_organization_id,
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target_criteria' => ['required', 'in:all,unpaid_fees,specific_usrah'],
            'usrah_group_id' => ['nullable', 'integer', 'exists:usrah_groups,id'],
        ]);

        if ($data['target_criteria'] === 'specific_usrah') {
            abort_if(empty($data['usrah_group_id']), 422, 'Sila pilih kumpulan usrah sasaran.');
        }

        $message = BroadcastMessage::create([
            'organization_id' => $user->current_organization_id,
            'title' => $data['title'],
            'content' => $data['content'],
            'target_criteria' => $data['target_criteria'],
            'usrah_group_id' => $data['target_criteria'] === 'specific_usrah'
                ? $data['usrah_group_id']
                : null,
        ]);

        SendBroadcastJob::dispatch($message->id);

        return back()->with('success', 'Broadcast sedang diproses dan akan dihantar berperingkat.');
    }
}
