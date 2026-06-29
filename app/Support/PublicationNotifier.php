<?php

namespace App\Support;

use App\Models\Publication;
use App\Models\PublicationTeam;
use App\Models\PublicationTeamAssignment;
use App\Models\User;
use App\Notifications\PublicationWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class PublicationNotifier
{
    public static function notifyEmployeeAssignments(PublicationTeam $team, Collection $assignments): void
    {
        if ($assignments->isEmpty()) {
            return;
        }

        $team->loadMissing('publication');
        $publication = $team->publication;

        if (!$publication) {
            return;
        }

        foreach ($assignments as $assignment) {
            if (!$assignment instanceof PublicationTeamAssignment) {
                continue;
            }

            $assignment->loadMissing('user');
            $user = $assignment->user;

            if (!$user || $user->role !== 'pegawai' || !$user->is_active) {
                continue;
            }

            $roleLabel = $assignment->assignment_role_label;
            $message = 'Anda ditugaskan sebagai ' . $roleLabel . ' pada publikasi "' . $publication->nama_publikasi . '".';

            $user->notify(new PublicationWorkflowNotification(
                title: 'Penugasan Publikasi Baru',
                message: $message,
                actionUrl: route('employee.tasks.show', $team),
                actionLabel: 'Buka Tugas',
                mailSubject: 'Penugasan Publikasi Baru',
                icon: 'bi-person-check'
            ));
        }
    }

    public static function notifyLeadersForApproval(Publication $publication): void
    {
        $leaders = User::where('tenant_id', $publication->tenant_id)
            ->where('role', 'pimpinan')
            ->where('is_active', true)
            ->get();

        if ($leaders->isEmpty()) {
            return;
        }

        Notification::send($leaders, new PublicationWorkflowNotification(
            title: 'Publikasi Menunggu Persetujuan',
            message: 'Publikasi "' . $publication->nama_publikasi . '" telah selesai diperiksa dan menunggu persetujuan pimpinan.',
            actionUrl: route('leader.approvals.show', $publication),
            actionLabel: 'Buka Persetujuan',
            mailSubject: 'Publikasi Menunggu Persetujuan',
            icon: 'bi-patch-check'
        ));
    }

    public static function notifyTenantAdminsPublicationReady(Publication $publication): void
    {
        $admins = User::where('tenant_id', $publication->tenant_id)
            ->whereIn('role', ['admin_provinsi', 'admin_kabkota'])
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new PublicationWorkflowNotification(
            title: 'Publikasi Siap Rilis',
            message: 'Publikasi "' . $publication->nama_publikasi . '" telah selesai difinalisasi oleh operator website dan siap rilis.',
            actionUrl: route('tenant.ready-release.index', ['tahun' => $publication->tahun]),
            actionLabel: 'Lihat Publikasi Siap Rilis',
            mailSubject: 'Publikasi Siap Rilis',
            icon: 'bi-box-seam'
        ));
    }

    public static function notifyPreparersForRevision(Publication $publication, string $stage): void
    {
        $publication->loadMissing('team.assignments.user');
        $team = $publication->team;

        if (!$team) {
            return;
        }

        $stageLabels = [
            'konten' => 'Pemeriksa Konten',
            'layout' => 'Pemeriksa Layout',
            'infografis' => 'Pemeriksa Infografis',
            'pimpinan' => 'Pimpinan',
        ];

        $stageLabel = $stageLabels[$stage] ?? 'Pemeriksa';

        $preparers = $team->assignments
            ->where('assignment_role', 'penyusun_naskah')
            ->pluck('user')
            ->filter(fn ($user) => $user && $user->role === 'pegawai' && $user->is_active)
            ->unique('id')
            ->values();

        if ($preparers->isEmpty()) {
            return;
        }

        Notification::send($preparers, new PublicationWorkflowNotification(
            title: 'Revisi Publikasi',
            message: 'Publikasi "' . $publication->nama_publikasi . '" dikembalikan/Revisi oleh ' . $stageLabel . '. Silakan periksa detail revisi dan lakukan perbaikan.',
            actionUrl: route('employee.tasks.show', $team),
            actionLabel: 'Buka Revisi',
            mailSubject: 'Revisi Publikasi',
            icon: 'bi-arrow-counterclockwise',
            sendMail: false
        ));
    }

}
