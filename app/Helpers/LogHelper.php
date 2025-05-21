<?php
// app/Helpers/LogHelper.php
namespace App\Helpers;

use App\Models\LogIsu;
use App\Models\Isu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogHelper
{
    /**
     * Mencatat log untuk Isu
     *
     * @param int $isuId ID Isu
     * @param string $action Jenis aksi (CREATE, UPDATE, DELETE)
     * @param string|null $fieldChanged Nama field yang diubah
     * @param mixed|null $oldValue Nilai lama
     * @param mixed|null $newValue Nilai baru
     * @param Request|null $request Request object
     * @param int|null $statusId Status saat log dibuat
     * @return LogIsu
     */
    public static function logIsuActivity($isuId, $action, $fieldChanged = null, $oldValue = null, $newValue = null, Request $request = null, $statusId = null)
    {
        if ($request === null) {
            $request = request();
        }

        // Jika statusId tidak disediakan, coba ambil dari isu
        if ($statusId === null && $isuId) {
            $isu = Isu::find($isuId);
            if ($isu) {
                $statusId = $isu->status_id;
            }
        }

        return LogIsu::create([
            'isu_id' => $isuId,
            'user_id' => Auth::id(),
            'action' => $action,
            'field_changed' => $fieldChanged,
            'old_value' => $oldValue ? (is_array($oldValue) ? json_encode($oldValue) : $oldValue) : null,
            'new_value' => $newValue ? (is_array($newValue) ? json_encode($newValue) : $newValue) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_id' => $statusId
        ]);
    }

    /**
     * Mencatat log perubahan field
     *
     * @param int $isuId ID Isu
     * @param array $originalData Data asli
     * @param array $newData Data baru
     * @param Request|null $request Request object
     * @param int|null $statusId Status ID saat log dibuat
     */
    public static function logIsuChanges($isuId, $originalData, $newData, Request $request = null, $statusId = null)
    {
        // Jika statusId tidak disediakan, coba ambil dari isu
        if ($statusId === null && $isuId) {
            $isu = Isu::find($isuId);
            if ($isu) {
                $statusId = $isu->status_id;
            }
        }

        foreach ($newData as $field => $value) {
            // Skip beberapa field yang tidak perlu dicatat
            if (in_array($field, ['created_at', 'updated_at', '_token', '_method', 'action'])) {
                continue;
            }

            // Tangani kategori sebagai kasus khusus
            if ($field === 'kategori') {
                // Ambil kategori lama
                $isu = Isu::findOrFail($isuId);
                $oldKategoriString = $isu->kategoris->pluck('nama')->implode(',');

                // Log perubahan jika berbeda
                if ($oldKategoriString != $value) {
                    self::logIsuActivity(
                        $isuId,
                        'UPDATE',
                        'kategori',
                        $oldKategoriString,
                        $value,
                        $request,
                        $statusId
                    );
                }

                continue;
            }

            // Jika nilai berubah, log perubahan
            if (isset($originalData[$field]) && $originalData[$field] != $value) {
                self::logIsuActivity(
                    $isuId,
                    'UPDATE',
                    $field,
                    $originalData[$field],
                    $value,
                    $request,
                    $statusId
                );
            }
        }
    }

    /**
     * Mencatat log untuk perubahan status
     *
     * @param int $isuId ID Isu
     * @param int $oldStatusId Status lama
     * @param int $newStatusId Status baru
     * @param string|null $alasanPenolakan Alasan penolakan jika status Ditolak
     * @param Request|null $request Request object
     * @return LogIsu
     */
    public static function logStatusChange($isuId, $oldStatusId, $newStatusId, $alasanPenolakan = null, Request $request = null)
    {
        // Dapatkan nama status dari ID
        $oldStatusName = \App\Models\RefStatus::getNamaById($oldStatusId) ?? 'Unknown';
        $newStatusName = \App\Models\RefStatus::getNamaById($newStatusId) ?? 'Unknown';

        $log = self::logIsuActivity(
            $isuId,
            'UPDATE',
            'status',
            $oldStatusName,
            $newStatusName,
            $request,
            $newStatusId
        );

        // Jika ada alasan penolakan, log juga
        if ($alasanPenolakan) {
            self::logIsuActivity(
                $isuId,
                'UPDATE',
                'alasan_penolakan',
                null,
                $alasanPenolakan,
                $request,
                $newStatusId
            );
        }

        return $log;
    }
}
