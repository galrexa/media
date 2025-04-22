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
     * @return LogIsu
     */
    public static function logIsuActivity($isuId, $action, $fieldChanged = null, $oldValue = null, $newValue = null, Request $request = null)
    {
        if ($request === null) {
            $request = request();
        }
        
        return LogIsu::create([
            'isu_id' => $isuId,
            'user_id' => Auth::id(),
            'action' => $action,
            'field_changed' => $fieldChanged,
            'old_value' => $oldValue ? (is_array($oldValue) ? json_encode($oldValue) : $oldValue) : null,
            'new_value' => $newValue ? (is_array($newValue) ? json_encode($newValue) : $newValue) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }
    
    /**
     * Mencatat log perubahan field
     *
     * @param int $isuId ID Isu
     * @param array $originalData Data asli
     * @param array $newData Data baru
     * @param Request|null $request Request object
     */
    public static function logIsuChanges($isuId, $originalData, $newData, Request $request = null)
    {
        foreach ($newData as $field => $value) {
            // Skip beberapa field yang tidak perlu dicatat
            if (in_array($field, ['created_at', 'updated_at', '_token', '_method'])) {
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
                        $request
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
                    $request
                );
            }
        }
    }
}