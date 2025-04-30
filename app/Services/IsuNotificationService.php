<?php

namespace App\Services;

use App\Models\Isu;
use App\Models\Notifikasi;
use App\Models\User;
use App\Models\RefStatus;

class IsuNotificationService
{
    /**
     * Membuat notifikasi saat isu dikirim ke verifikator
     *
     * @param Isu $isu
     * @param int $statusId
     * @param User $actor
     * @return void
     */
    public static function notifyForVerification(Isu $isu, int $statusId, User $actor)
    {
        $roleTarget = '';
        $statusName = '';

        // Tentukan role target berdasarkan status
        if ($statusId == RefStatus::getVerifikasi1Id()) {
            $roleTarget = 'verifikator1';
            $statusName = 'Verifikasi 1';
        } elseif ($statusId == RefStatus::getVerifikasi2Id()) {
            $roleTarget = 'verifikator2';
            $statusName = 'Verifikasi 2';
        } else {
            return; // Bukan status verifikasi, tidak perlu notifikasi
        }

        // Dapatkan semua user dengan role target
        $users = User::whereHas('roles', function($query) use ($roleTarget) {
            $query->where('name', $roleTarget);
        })->get();

        // Buat notifikasi untuk setiap verifikator
        foreach ($users as $user) {
            self::createNotification(
                $user->id,
                'Isu Baru untuk Diverifikasi',
                "Isu '{$isu->judul}' memerlukan {$statusName} dari Anda.",
                'verifikasi',
                route('isu.show', $isu->id),
                [
                    'isu_id' => $isu->id,
                    'status_id' => $statusId,
                    'actor_id' => $actor->id,
                    'actor_name' => $actor->name
                ]
            );
        }

        // Jika status verifikasi 1, beri tahu editor bahwa isu sedang diproses
        if ($statusId == RefStatus::getVerifikasi1Id() && $isu->created_by) {
            $editor = User::find($isu->created_by);
            if ($editor) {
                self::createNotification(
                    $editor->id,
                    'Isu Sedang Diproses',
                    "Isu '{$isu->judul}' sedang dalam proses {$statusName}.",
                    'info',
                    route('isu.show', $isu->id),
                    [
                        'isu_id' => $isu->id,
                        'status_id' => $statusId
                    ]
                );
            }
        }

        // Jika status verifikasi 2, beri tahu verifikator 1 bahwa isu telah diteruskan
        if ($statusId == RefStatus::getVerifikasi2Id()) {
            // Cari verifikator 1 yang terakhir memproses isu ini dari log
            $verifikator1 = $isu->logIsus()
                ->whereHas('user', function($query) {
                    $query->whereHas('roles', function($q) {
                        $q->where('name', 'verifikator1');
                    });
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($verifikator1) {
                self::createNotification(
                    $verifikator1->user_id,
                    'Isu Diteruskan ke Verifikator 2',
                    "Isu '{$isu->judul}' telah diteruskan ke Verifikator 2.",
                    'info',
                    route('isu.show', $isu->id),
                    [
                        'isu_id' => $isu->id,
                        'status_id' => $statusId
                    ]
                );
            }
        }
    }

    /**
     * Membuat notifikasi saat isu ditolak
     *
     * @param Isu $isu
     * @param string $reason
     * @param User $actor
     * @return void
     */
    public static function notifyForRejection(Isu $isu, string $reason, User $actor)
    {
        // Notifikasi untuk pemilik/pembuat isu (editor)
        if ($isu->created_by) {
            $editor = User::find($isu->created_by);
            if ($editor) {
                self::createNotification(
                    $editor->id,
                    'Isu Ditolak',
                    "Isu '{$isu->judul}' telah ditolak dengan alasan: {$reason}",
                    'tolak',
                    route('isu.show', $isu->id),
                    [
                        'isu_id' => $isu->id,
                        'status_id' => RefStatus::getDitolakId(),
                        'reason' => $reason,
                        'actor_id' => $actor->id,
                        'actor_name' => $actor->name
                    ]
                );
            }
        }

        // Jika yang menolak adalah Verifikator 2, beri tahu Verifikator 1
        if ($actor->hasRole('verifikator2')) {
            // Cari verifikator 1 yang terakhir memproses isu ini dari log
            $verifikator1 = $isu->logIsus()
                ->whereHas('user', function($query) {
                    $query->whereHas('roles', function($q) {
                        $q->where('name', 'verifikator1');
                    });
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($verifikator1) {
                self::createNotification(
                    $verifikator1->user_id,
                    'Isu Ditolak oleh Verifikator 2',
                    "Isu '{$isu->judul}' yang Anda setujui telah ditolak oleh Verifikator 2 dengan alasan: {$reason}",
                    'tolak',
                    route('isu.show', $isu->id),
                    [
                        'isu_id' => $isu->id,
                        'status_id' => RefStatus::getDitolakId(),
                        'reason' => $reason,
                        'actor_id' => $actor->id,
                        'actor_name' => $actor->name
                    ]
                );
            }
        }
    }

    /**
     * Membuat notifikasi saat isu dipublikasikan
     *
     * @param Isu $isu
     * @param User $actor
     * @return void
     */
    public static function notifyForPublication(Isu $isu, User $actor)
    {
        // Notifikasi untuk pemilik/pembuat isu (editor)
        if ($isu->created_by) {
            $editor = User::find($isu->created_by);
            if ($editor) {
                self::createNotification(
                    $editor->id,
                    'Isu Dipublikasikan',
                    "Isu '{$isu->judul}' telah berhasil dipublikasikan.",
                    'publikasi',
                    route('isu.show', $isu->id),
                    [
                        'isu_id' => $isu->id,
                        'status_id' => RefStatus::getDipublikasiId(),
                        'actor_id' => $actor->id,
                        'actor_name' => $actor->name
                    ]
                );
            }
        }

        // Notifikasi untuk verifikator 1 yang telah memproses isu ini
        $verifikator1 = $isu->logIsus()
            ->whereHas('user', function($query) {
                $query->whereHas('roles', function($q) {
                    $q->where('name', 'verifikator1');
                });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($verifikator1) {
            self::createNotification(
                $verifikator1->user_id,
                'Isu Telah Dipublikasikan',
                "Isu '{$isu->judul}' yang Anda verifikasi telah berhasil dipublikasikan.",
                'publikasi',
                route('isu.show', $isu->id),
                [
                    'isu_id' => $isu->id,
                    'status_id' => RefStatus::getDipublikasiId(),
                    'actor_id' => $actor->id,
                    'actor_name' => $actor->name
                ]
            );
        }
    }

    /**
     * Helper untuk membuat notifikasi
     *
     * @param int $userId
     * @param string $judul
     * @param string $pesan
     * @param string $tipe
     * @param string $link
     * @param array $data
     * @return Notifikasi
     */
    protected static function createNotification(int $userId, string $judul, string $pesan, string $tipe, string $link, array $data = [])
    {
        return Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'link' => $link,
            'is_read' => false,
            'data' => $data
        ]);
    }
}
