<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\DashboardBannerController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InfaqController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\InformationHubController;
use App\Http\Controllers\InformationHubAdminController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\MemberCardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperadminOrganizationController;
use App\Http\Controllers\SuperadminSystemSettingController;
use App\Http\Controllers\UsrahController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing'])->name('landing');

Route::middleware(['auth', 'verified', 'profile_complete'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboardRedirect'])->name('dashboard');

    Route::middleware('role:Superadmin|Admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
        Route::get('/admin/campaigns', [FinancialController::class, 'adminCampaigns'])->name('admin.campaigns.index');
        Route::post('/admin/campaigns', [FinancialController::class, 'storeCampaign'])->name('admin.campaigns.store');
        Route::get('/admin/information-hub/manage', [InformationHubAdminController::class, 'index'])->name('admin.hub.manage');
        Route::post('/admin/information-hub/announcements', [InformationHubAdminController::class, 'storeAnnouncement'])->name('admin.hub.announcements.store');
        Route::put('/admin/information-hub/announcements/{announcement}', [InformationHubAdminController::class, 'updateAnnouncement'])->name('admin.hub.announcements.update');
        Route::patch('/admin/information-hub/announcements/{announcement}/pin', [InformationHubAdminController::class, 'togglePinned'])->name('admin.hub.announcements.pin');
        Route::delete('/admin/information-hub/announcements/{announcement}', [InformationHubAdminController::class, 'destroyAnnouncement'])->name('admin.hub.announcements.destroy');
        Route::post('/admin/information-hub/library', [InformationHubAdminController::class, 'storeLibraryItem'])->name('admin.hub.library.store');
        Route::put('/admin/information-hub/library/{libraryItem}', [InformationHubAdminController::class, 'updateLibraryItem'])->name('admin.hub.library.update');
        Route::delete('/admin/information-hub/library/{libraryItem}', [InformationHubAdminController::class, 'destroyLibraryItem'])->name('admin.hub.library.destroy');

        // Admin: monitor-only transactions
        Route::get('/admin/transactions', [PaymentController::class, 'orgTransactions'])->name('admin.transactions');
        Route::get('/admin/members/export', [ExportController::class, 'exportMembers'])->name('admin.members.export');
        Route::get('/admin/usrah', [UsrahController::class, 'adminIndex'])->name('admin.usrah.index');
        Route::post('/admin/usrah/groups', [UsrahController::class, 'storeGroup'])->name('admin.usrah.groups.store');
        Route::post('/admin/usrah/groups/{usrahGroup}/assign', [UsrahController::class, 'assignMembers'])->name('admin.usrah.groups.assign');
        Route::get('/admin/broadcasts', [BroadcastController::class, 'index'])->name('admin.broadcasts.index');
        Route::post('/admin/broadcasts', [BroadcastController::class, 'store'])->name('admin.broadcasts.store');
    });

    // Superadmin-only: fee management + all transactions
    Route::middleware('role:Superadmin')->group(function () {
        Route::get('/superadmin/fees', [PaymentController::class, 'feesConfig'])->name('superadmin.fees.index');
        Route::put('/superadmin/fees/{organization}', [PaymentController::class, 'updateFee'])->name('superadmin.fees.update');
        Route::get('/superadmin/transactions', [PaymentController::class, 'allTransactions'])->name('superadmin.transactions');
        Route::patch('/superadmin/transactions/{payment}', [PaymentController::class, 'updateTransactionStatus'])->name('superadmin.transactions.update');
        Route::get('/superadmin/pustaka/manage', [InformationHubAdminController::class, 'libraryIndex'])->name('admin.library.manage');
        Route::get('/superadmin/dashboard-banners', [DashboardBannerController::class, 'index'])->name('superadmin.banners.index');
        Route::post('/superadmin/dashboard-banners', [DashboardBannerController::class, 'store'])->name('superadmin.banners.store');
        Route::post('/superadmin/dashboard-banners/seed-demo', [DashboardBannerController::class, 'seedDemo'])->name('superadmin.banners.seed');
        Route::put('/superadmin/dashboard-banners/{dashboardBanner}', [DashboardBannerController::class, 'update'])->name('superadmin.banners.update');
        Route::delete('/superadmin/dashboard-banners/{dashboardBanner}', [DashboardBannerController::class, 'destroy'])->name('superadmin.banners.destroy');
        // Infaq management (Superadmin only)
        Route::get('/superadmin/infaq', [InfaqController::class, 'index'])->name('superadmin.infaq.index');
        Route::post('/superadmin/infaq', [InfaqController::class, 'store'])->name('superadmin.infaq.store');
        Route::post('/superadmin/infaq/seed-demo', [InfaqController::class, 'seedDemo'])->name('superadmin.infaq.seed');
        Route::put('/superadmin/infaq/{infaq}', [InfaqController::class, 'update'])->name('superadmin.infaq.update');
        Route::delete('/superadmin/infaq/{infaq}', [InfaqController::class, 'destroy'])->name('superadmin.infaq.destroy');
        Route::get('/superadmin/organizations', [SuperadminOrganizationController::class, 'index'])->name('superadmin.organizations.index');
        Route::put('/superadmin/organizations/{organization}', [SuperadminOrganizationController::class, 'update'])->name('superadmin.organizations.update');
        Route::post('/superadmin/organizations/{organization}/logo', [SuperadminOrganizationController::class, 'updateLogo'])->name('superadmin.organizations.logo.update');

        Route::get('/superadmin/settings', [SuperadminSystemSettingController::class, 'index'])->name('superadmin.settings.index');
        Route::post('/superadmin/settings/system-logo', [SuperadminSystemSettingController::class, 'updateSystemLogo'])->name('superadmin.settings.system-logo.update');

        Route::redirect('/superadmin/logo-settings', '/superadmin/organizations');
    });

    Route::middleware('role:Member')->group(function () {
        Route::get('/member/dashboard', [MemberDashboardController::class, 'index'])->name('member.dashboard');
        Route::get('/member/financial/overview', [FinancialController::class, 'memberOverview'])->name('member.financial.overview');
        Route::get('/member/announcements', [InformationHubController::class, 'announcements'])->name('member.announcements');
        Route::get('/member/library', [InformationHubController::class, 'library'])->name('member.library');
        Route::get('/member/information-hub', [InformationHubController::class, 'announcements'])->name('member.hub');
        Route::get('/member/usrah', [UsrahController::class, 'myGroup'])->name('member.usrah');
        Route::get('/member/card', [MemberCardController::class, 'show'])->name('member.card');
        Route::get('/member/infaq/{infaq}', [InfaqController::class, 'show'])->name('member.infaq.show');
        Route::post('/member/usrah/{usrahGroup}/attendance', [UsrahController::class, 'logAttendance'])->name('member.usrah.attendance.log');
        Route::post('/member/pay-fee', [PaymentController::class, 'payFee'])->name('member.pay.fee');
        Route::post('/member/infaq/{infaq}/donate', [InfaqController::class, 'donate'])->name('member.infaq.donate');
    });

    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');
});

// ─── Authenticated Member Routes ─────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Force profile update flow (intentionally outside profile_complete middleware blocking)
    Route::get('/member/complete-profile', [ProfileController::class, 'completeProfile'])->name('member.complete-profile');
    Route::post('/member/complete-profile', [ProfileController::class, 'storeCompleteProfile'])->name('member.complete-profile.store');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/journey', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Events — browse + RSVP
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');

    // QR Attendance scan endpoint — auth required so we can attribute the scan
    // to the authenticated user.  If user is a guest, Laravel redirects to login
    // and stores this URL as the `intended` destination.
    Route::get('/events/{id}/attend/{token}', [EventController::class, 'recordAttendance'])
         ->name('events.attend');

    // ─── Admin / Staff Only ──────────────────────────────────────────────────
    Route::middleware('role:Admin|Superadmin')->group(function () {
           Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/qr', [EventController::class, 'showQr'])
             ->name('events.qr');
        Route::get('/events/{event}/print', [EventController::class, 'printAttendance'])
             ->name('events.print');
    });
});

require __DIR__.'/auth.php';
