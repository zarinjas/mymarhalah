<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EventRsvp;
use App\Models\Event;
use App\Models\Infaq;
use App\Models\LibraryItem;
use App\Models\Payment;
use App\Models\UsrahGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function landing(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($this->dashboardRouteFor($request->user()));
        }

        return Inertia::render('Landing');
    }

    public function dashboardRedirect(Request $request): RedirectResponse
    {
        return redirect()->route($this->dashboardRouteFor($request->user()));
    }

    public function admin(Request $request): Response
    {
        $user = $request->user()->load('organization');
        $isSuperadmin = $user->hasRole('Superadmin');

        $totalMembers = User::withoutGlobalScopes()
            ->when(! $isSuperadmin, fn ($query) => $query->where('current_organization_id', $user->current_organization_id))
            ->count();

        $feesCollectedThisMonth = Payment::query()
            ->where('status', 'successful')
            ->where('payable_type', 'membership_fee')
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('created_at', '<=', now()->endOfMonth())
            ->when(! $isSuperadmin, function ($query) use ($user) {
                $query->whereHas('user', function ($innerQuery) use ($user) {
                    $innerQuery->withoutGlobalScopes()->where('current_organization_id', $user->current_organization_id);
                });
            })
            ->sum('amount');

        $activeCampaigns = Campaign::query()
            ->when(! $isSuperadmin, fn ($query) => $query->where('organization_id', $user->current_organization_id))
            ->where('status', 'active')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'target_amount' => (float) $campaign->target_amount,
                'current_amount' => (float) $campaign->current_amount,
                'progress_percent' => $campaign->target_amount > 0
                    ? min(100, round(($campaign->current_amount / $campaign->target_amount) * 100))
                    : 0,
            ]);

        $eventsCount = Event::query()
            ->when(! $isSuperadmin, fn ($query) => $query->where('organization_id', $user->current_organization_id))
            ->count();

        $infaqCount = Infaq::query()
            ->when(! $isSuperadmin, fn ($query) => $query->where(function ($q) use ($user) {
                $q->where('organization_id', $user->current_organization_id)
                  ->orWhereNull('organization_id');
            }))
            ->count();

        $programChart = [
            ['label' => 'Program', 'value' => $eventsCount],
            ['label' => 'Campaigns', 'value' => $activeCampaigns->count()],
            ['label' => 'Infaq', 'value' => $infaqCount],
        ];

        return Inertia::render('Admin/Dashboard', [
            'organization' => [
                'name' => $isSuperadmin ? 'Management' : $user->organization?->name,
                'slug' => $isSuperadmin ? 'management' : $user->organization?->slug,
                'color_theme' => $isSuperadmin ? '#334155' : $user->organization?->color_theme,
            ],
            'overview' => [
                'total_members' => $totalMembers,
                'fees_collected_month' => (float) $feesCollectedThisMonth,
                'total_programs' => (int) ($eventsCount + $activeCampaigns->count() + $infaqCount),
                'program_chart' => $programChart,
            ],
            'managementLinks' => [
                'create_event_url' => route('events.index'),
                'create_program_url' => route('events.index'),
                'create_campaign_url' => route('admin.campaigns.store'),
                'campaigns_url' => route('admin.campaigns.index'),
                'infaq_url' => $isSuperadmin ? route('superadmin.infaq.index') : route('admin.campaigns.index'),
                'information_hub_manage_url' => route('admin.hub.manage'),
                'usrah_manage_url' => route('admin.usrah.index'),
                'broadcasts_url' => route('admin.broadcasts.index'),
                'directory_url' => route('directory.index'),
            ],
            'campaigns' => $activeCampaigns,
        ]);
    }

    public function member(Request $request): Response
    {
        $user = $request->user()->load('organization');

        $nextEventRsvp = EventRsvp::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['going', 'maybe'])
            ->whereHas('event', fn ($query) => $query->where('start_time', '>=', now()))
            ->with('event.organization')
            ->get()
            ->sortBy(fn (EventRsvp $rsvp) => $rsvp->event->start_time)
            ->first();

        $latestFeePayment = Payment::query()
            ->where('user_id', $user->id)
            ->where('status', 'successful')
            ->where('payable_type', 'membership_fee')
            ->latest('created_at')
            ->first();

        $feeIsActive = $latestFeePayment
            && $latestFeePayment->created_at->year === now()->year;

        $feeAmount = (float) ($user->organization?->fee_amount ?? 50.00);

        $usrahGroup = $user->usrahGroups()
            ->with(['members' => fn ($query) => $query->withoutGlobalScopes()->select('users.id', 'name')])
            ->first();

        $isNaqib = $usrahGroup
            ? $usrahGroup->members->contains(fn ($member) => (int) $member->id === (int) $user->id && (bool) $member->pivot->is_naqib)
            : false;

        $naqib = $usrahGroup
            ? $usrahGroup->members->first(fn ($member) => (bool) $member->pivot->is_naqib)
            : null;

        $campaigns = Campaign::query()
            ->where('organization_id', $user->current_organization_id)
            ->where('status', 'active')
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'target_amount' => (float) $campaign->target_amount,
                'current_amount' => (float) $campaign->current_amount,
                'progress_percent' => $campaign->target_amount > 0
                    ? min(100, round(($campaign->current_amount / $campaign->target_amount) * 100))
                    : 0,
            ]);

        $libraryBooks = LibraryItem::query()
            ->where('organization_id', $user->current_organization_id)
            ->latest()
            ->take(12)
            ->get()
            ->map(fn (LibraryItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'file_path' => $item->file_path,
                'cover_image_path' => $item->cover_image_path,
            ]);

        return Inertia::render('Member/Dashboard', [
            'member' => [
                'name' => $user->name,
                'organization' => [
                    'name' => $user->organization?->name,
                    'slug' => $user->organization?->slug,
                    'color_theme' => $user->organization?->color_theme,
                ],
            ],
            'feeStatus' => [
                'status'      => $feeIsActive ? 'active' : 'due',
                'amount_due'  => $feeIsActive ? 0 : $feeAmount,
                'last_paid_at'=> $latestFeePayment?->created_at?->toISOString(),
                'last_reference' => $latestFeePayment?->reference,
            ],
            'nextEvent' => $nextEventRsvp ? [
                'title' => $nextEventRsvp->event->title,
                'start_formatted' => $nextEventRsvp->event->start_time->locale('ms')->isoFormat('ddd, D MMM YYYY [•] h:mm A'),
                'location_or_link' => $nextEventRsvp->event->location_or_link,
                'status' => $nextEventRsvp->status,
            ] : null,
            'usrah' => $usrahGroup ? [
                'id' => $usrahGroup->id,
                'name' => $usrahGroup->name,
                'meeting_day' => $usrahGroup->meeting_day,
                'meeting_time' => $usrahGroup->meeting_time,
                'naqib_name' => $naqib?->name,
                'is_naqib' => $isNaqib,
                'members' => $usrahGroup->members->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'is_naqib' => (bool) $member->pivot->is_naqib,
                ])->values(),
            ] : null,
            'campaigns' => $campaigns,
            'libraryBooks' => $libraryBooks,
        ]);
    }

    private function dashboardRouteFor(User $user): string
    {
        if ($user->hasRole(['Superadmin', 'Admin'])) {
            return 'admin.dashboard';
        }

        return 'member.dashboard';
    }
}
