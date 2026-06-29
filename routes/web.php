<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TenantAccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Employee\DocumentReviewController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\EmployeeTaskController;
use App\Http\Controllers\Leader\LeaderApprovalController;
use App\Http\Controllers\Leader\LeaderDashboardController;
use App\Http\Controllers\Tenant\InspectionGuidelineController;
use App\Http\Controllers\Tenant\KnowledgeController;
use App\Http\Controllers\Tenant\MonitoringController;
use App\Http\Controllers\Tenant\PublicationProgressController;
use App\Http\Controllers\Tenant\PublicationController;
use App\Http\Controllers\Tenant\TeamAllocationController;
use App\Http\Controllers\Tenant\TeamTemplateController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\UserAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return match (Auth::user()->role) {
        'admin_sistem' => redirect()->route('admin.system.dashboard'),
        'admin_provinsi', 'admin_kabkota' => redirect()->route('tenant.dashboard'),
        'pegawai' => redirect()->route('employee.dashboard'),
        'pimpinan' => redirect()->route('leader.dashboard'),
        default => (function () use ($request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Dashboard untuk role ini belum tersedia.');
        })(),
    };
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/lupa-kata-sandi', [AuthController::class, 'showForgotPasswordForm'])
        ->name('password.request');
    Route::post('/lupa-kata-sandi/kirim-kode', [AuthController::class, 'sendResetCode'])
        ->name('password.otp.send');
    Route::post('/lupa-kata-sandi/reset', [AuthController::class, 'resetPasswordWithCode'])
        ->name('password.reset.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/notifikasi/{notificationId}/baca', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
});

Route::prefix('admin-system')
    ->name('admin.system.')
    ->middleware(['auth', 'admin.system'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/akun-bps/export', [TenantAccountController::class, 'export'])
            ->name('tenant-accounts.export');

        Route::resource('/akun-bps', TenantAccountController::class)
            ->parameters([
                'akun-bps' => 'user',
            ])
            ->names('tenant-accounts')
            ->except(['show']);
    });

Route::prefix('tenant')
    ->name('tenant.')
    ->middleware(['auth', 'tenant.admin'])
    ->group(function () {
        Route::get('/dashboard', [TenantDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/publikasi/rekap-laporan', [PublicationController::class, 'monthlyReport'])
            ->name('publications.monthly-report');

        Route::get('/publikasi/rekap-laporan/download', [PublicationController::class, 'monthlyReportExcel'])
            ->name('publications.monthly-report.pdf');

        Route::resource('/publikasi', PublicationController::class)
            ->parameters([
                'publikasi' => 'publication',
            ])
            ->names('publications');

        Route::get('/akun-pengguna/export', [UserAccountController::class, 'export'])
            ->name('user-accounts.export');

        Route::resource('/akun-pengguna', UserAccountController::class)
            ->parameters([
                'akun-pengguna' => 'user',
            ])
            ->names('user-accounts')
            ->except(['show']);

        Route::get('/tim-kerja', [TeamAllocationController::class, 'index'])
            ->name('team-allocations.index');

        Route::get('/tim-kerja/create', [TeamAllocationController::class, 'create'])
            ->name('team-allocations.create');

        Route::post('/tim-kerja', [TeamAllocationController::class, 'store'])
            ->name('team-allocations.store');

        Route::get('/tim-kerja/{publicationTeam}/edit', [TeamAllocationController::class, 'edit'])
            ->name('team-allocations.edit');

        Route::put('/tim-kerja/{publicationTeam}', [TeamAllocationController::class, 'update'])
            ->name('team-allocations.update');

        Route::delete('/tim-kerja/{publicationTeam}', [TeamAllocationController::class, 'destroy'])
            ->name('team-allocations.destroy');

        Route::resource('/atur-tim-kerja', TeamTemplateController::class)
            ->parameters([
                'atur-tim-kerja' => 'teamTemplate',
            ])
            ->names('team-templates')
            ->except(['show']);

        Route::get('/tim-kerja/{publicationTeam}/assign', [TeamAllocationController::class, 'assign'])
            ->name('team-allocations.assign');

        Route::put('/tim-kerja/{publicationTeam}/assign', [TeamAllocationController::class, 'updateAssign'])
            ->name('team-allocations.assign.update');

        Route::delete('/atur-tim-kerja/{publicationTeam}/clear', [TeamAllocationController::class, 'clearAssignments'])
            ->name('team-allocations.assign.clear');


        Route::get('/progres-publikasi', [PublicationProgressController::class, 'index'])
            ->name('publication-progress.index');

        Route::get('/progres-publikasi/dokumen/{publicationDocument}/download', [PublicationProgressController::class, 'downloadDocument'])
            ->name('publication-progress.download-document');

        Route::get('/progres-publikasi/dokumen/{publicationDocument}/preview', [PublicationProgressController::class, 'previewDocument'])
            ->name('publication-progress.preview-document');    

        Route::get('/progres-publikasi/{publication}', [PublicationProgressController::class, 'show'])
            ->name('publication-progress.show');

        Route::get('/progres-publikasi/{publication}/log-history', [PublicationProgressController::class, 'history'])
            ->name('publication-progress.history');

        Route::get('/progres-publikasi/{publication}/tim-penyusun', [PublicationProgressController::class, 'authorTeam'])
            ->name('publication-progress.author-team');

        Route::post('/progres-publikasi/{publication}/tim-penyusun/upload-dokumen', [PublicationProgressController::class, 'uploadAuthorDocument'])
            ->name('publication-progress.author-team.upload-document');

        Route::post('/progres-publikasi/{publication}/tim-penyusun/simpan-sprp', [PublicationProgressController::class, 'saveAuthorSprp'])
            ->name('publication-progress.author-team.save-sprp');

        Route::post('/pedoman-pemeriksaan/template-dokumen', [InspectionGuidelineController::class, 'storeTemplate'])
            ->name('inspection-guidelines.templates.store');

        Route::delete('/pedoman-pemeriksaan/template-dokumen/{documentTemplate}', [InspectionGuidelineController::class, 'destroyTemplate'])
            ->name('inspection-guidelines.templates.destroy');

        Route::delete('/pedoman-pemeriksaan/anatomi-tambahan', [InspectionGuidelineController::class, 'destroyCustomSection'])
            ->name('inspection-guidelines.custom-section.destroy');

        Route::resource('/pedoman-pemeriksaan', InspectionGuidelineController::class)
            ->parameters(['pedoman-pemeriksaan' => 'inspectionGuideline'])
            ->names('inspection-guidelines')
            ->except(['show']);

        Route::resource('/knowledge', KnowledgeController::class)
            ->parameters(['knowledge' => 'knowledge'])
            ->names('knowledge')
            ->except(['show']);

        Route::get('/publikasi-siap-rilis', [PublicationProgressController::class, 'readyRelease'])
            ->name('ready-release.index');

        Route::get('/publikasi-siap-rilis/{publication}/download-paket', [PublicationProgressController::class, 'downloadReleasePackage'])
            ->name('ready-release.download-package');

        Route::get('/publikasi-siap-rilis/{publication}/rekap-publikasi/download', [PublicationProgressController::class, 'readyReleaseReportPdf'])
            ->name('ready-release.report.pdf');

        Route::get('/monitoring-kabkota', [MonitoringController::class, 'index'])
            ->name('monitoring.index');
    });

Route::prefix('pegawai')
    ->name('employee.')
    ->middleware(['auth', 'employee'])
    ->group(function () {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/knowledge', [EmployeeTaskController::class, 'knowledge'])
            ->name('knowledge.index');

        Route::get('/publikasi-siap-rilis', [EmployeeTaskController::class, 'readyRelease'])
            ->name('ready-release.index');

        Route::get('/publikasi-siap-rilis/{publicationTeam}/rekap-hasil', [EmployeeTaskController::class, 'workReport'])
            ->name('ready-release.work-report');

        Route::get('/publikasi-siap-rilis/{publicationTeam}/rekap-hasil/download', [EmployeeTaskController::class, 'workReportPdf'])
            ->name('ready-release.work-report.pdf');

        Route::get('/publikasi-siap-rilis/{publicationTeam}/download-paket', [EmployeeTaskController::class, 'downloadReleasePackage'])
            ->name('ready-release.download-package');

        Route::get('/tugas-saya', [EmployeeTaskController::class, 'index'])
            ->name('tasks.index');

        Route::get('/tugas-saya/template-dokumen/{documentTemplate}/download', [EmployeeTaskController::class, 'downloadTemplate'])
            ->name('tasks.download-template');

        Route::get('/tugas-saya/dokumen/{publicationDocument}/download', [EmployeeTaskController::class, 'downloadDocument'])
            ->name('tasks.download-document');

        Route::get('/tugas-saya/revisi/{publicationReview}/download-pdf', [EmployeeTaskController::class, 'downloadReviewRevisionPdf'])
            ->name('tasks.review-revision.pdf');

        // Rute lama tetap diarahkan ke PDF agar tautan lama tidak rusak.
        Route::get('/tugas-saya/revisi/{publicationReview}/download-excel', [EmployeeTaskController::class, 'downloadReviewRevisionExcel'])
            ->name('tasks.review-revision.excel');

        Route::get('/tugas-saya/{publicationTeam}', [EmployeeTaskController::class, 'show'])
            ->name('tasks.show');

        Route::post('/tugas-saya/{publicationTeam}/upload-dokumen', [EmployeeTaskController::class, 'uploadDocument'])
            ->name('tasks.upload-document');

        Route::post('/tugas-saya/{publicationTeam}/simpan-sprp', [EmployeeTaskController::class, 'saveSprp'])
            ->name('tasks.save-sprp');

        Route::post('/tugas-saya/{publicationTeam}/submit-penyusun', [EmployeeTaskController::class, 'submitAuthorWork'])
            ->name('tasks.submit-author-work');

        Route::post('/tugas-saya/{publicationTeam}/upload-draft', [EmployeeTaskController::class, 'uploadDraft'])
            ->name('tasks.upload-draft');

        Route::post('/tugas-saya/{publicationTeam}/pemeriksaan/{type}/simpan-slide', [EmployeeTaskController::class, 'saveReviewSlide'])
            ->where('type', 'konten|layout')
            ->name('tasks.review-slide.save');

        Route::post('/tugas-saya/{publicationTeam}/review-konten', [EmployeeTaskController::class, 'reviewContent'])
            ->name('tasks.review-content');

        Route::post('/tugas-saya/{publicationTeam}/review-layout', [EmployeeTaskController::class, 'reviewLayout'])
            ->name('tasks.review-layout');

        Route::post('/tugas-saya/{publicationTeam}/review-infografis', [EmployeeTaskController::class, 'reviewInfographic'])
            ->name('tasks.review-infographic');

        Route::post('/tugas-saya/{publicationTeam}/operator-website/siap-rilis', [EmployeeTaskController::class, 'completeWebsiteReleasePackage'])
            ->name('tasks.complete-website-release');

        // ── Review Dokumen Publikasi ──
        Route::get('/tugas-saya/{publicationTeam}/review-dokumen', [DocumentReviewController::class, 'show'])
            ->name('tasks.document-review');

        Route::get('/tugas-saya/dokumen/{publicationDocument}/preview-pdf', [DocumentReviewController::class, 'previewPdf'])
            ->name('tasks.preview-pdf');

        Route::post('/tugas-saya/{publicationTeam}/review-dokumen/catatan', [DocumentReviewController::class, 'storeNote'])
            ->name('tasks.document-review.store-note');

        Route::put('/tugas-saya/review-dokumen/catatan/{reviewNote}', [DocumentReviewController::class, 'updateNoteStatus'])
            ->name('tasks.document-review.update-note');

        Route::delete('/tugas-saya/review-dokumen/catatan/{reviewNote}', [DocumentReviewController::class, 'destroyNote'])
            ->name('tasks.document-review.destroy-note');
    });

Route::prefix('pimpinan')
    ->name('leader.')
    ->middleware(['auth', 'leader'])
    ->group(function () {
        Route::get('/dashboard', [LeaderDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/persetujuan', [LeaderApprovalController::class, 'index'])
            ->name('approvals.index');

        Route::get('/siap-rilis', [LeaderDashboardController::class, 'readyRelease'])
            ->name('ready-release.index');

        Route::get('/persetujuan/dokumen/{publicationDocument}/download', [LeaderApprovalController::class, 'downloadDocument'])
            ->name('approvals.download-document');

        Route::get('/persetujuan/{publication}', [LeaderApprovalController::class, 'show'])
            ->name('approvals.show');

        Route::post('/persetujuan/{publication}/keputusan', [LeaderApprovalController::class, 'decide'])
            ->name('approvals.decide');
    });
