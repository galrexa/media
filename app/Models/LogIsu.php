<?php
// app/Models/LogIsu.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LogIsu extends Model
{
    use HasFactory;

    protected $table = 'log_isu';

    protected $fillable = [
        'isu_id',
        'user_id',
        'action',
        'field_changed',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
        'status_id'
    ];

    // Tidak perlu updated_at karena log hanya dibuat sekali
    const UPDATED_AT = null;
    const CREATED_AT = 'created_at';

    // Relasi ke Isu
    public function isu()
    {
        return $this->belongsTo(Isu::class, 'isu_id');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Status
    public function status()
    {
        return $this->belongsTo(RefStatus::class, 'status_id');
    }

    /**
     * Scope untuk mendapatkan log perubahan status
     */
    public function scopeStatusChanges($query)
    {
        return $query->where('field_changed', 'status');
    }

    /**
     * Scope untuk mendapatkan log berdasarkan status
     */
    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    /**
     * Mendapatkan warna CSS berdasarkan action
     */
    public function getActionColorClass()
    {
        switch ($this->action) {
            case 'CREATE':
                return 'text-success';
            case 'UPDATE':
                return 'text-primary';
            case 'DELETE':
                return 'text-danger';
            default:
                return 'text-secondary';
        }
    }

    /**
     * Mendapatkan ikon berdasarkan jenis aksi
     */
    public function getActionIcon()
    {
        switch ($this->action) {
            case 'CREATE':
                return 'fa-solid fa-plus-circle';
            case 'UPDATE':
                return 'fa-solid fa-edit';
            case 'DELETE':
                return 'fa-solid fa-trash';
            default:
                return 'fa-solid fa-info-circle';
        }
    }

    /**
     * Format nilai untuk tampilan di history
     * Secara khusus menangani format tanggal, HTML, dan JSON
     */
    public function getFormattedOldValue()
    {
        return $this->formatValue($this->old_value, $this->field_changed);
    }

    /**
     * Format nilai baru untuk tampilan di history
     */
    public function getFormattedNewValue()
    {
        return $this->formatValue($this->new_value, $this->field_changed);
    }

    /**
     * Memformat nilai untuk tampilan
     * Menstandarkan format tanggal, HTML, dan JSON
     */
    protected function formatValue($value, $fieldName)
    {
        // Handle nilai kosong
        if ($value === null || $value === '') {
            return '(kosong)';
        }
        
        // Coba parse sebagai JSON
        $jsonValue = json_decode($value, true);
        if ($jsonValue !== null && is_array($jsonValue)) {
            // Format khusus untuk array [{"value":"x"}]
            if (isset($jsonValue[0]['value'])) {
                return $jsonValue[0]['value'];
            }
            
            // Format lainnya, kembalikan sebagai string JSON yang lebih rapi
            return json_encode($jsonValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        // Jika field berupa tanggal, format dengan standardisasi
        if (strpos($fieldName, 'tanggal') !== false) {
            return $this->formatValueIfDate($value);
        }
        
        // Jika nilai mengandung HTML, bersihkan
        if (strpos($value, '<') !== false) {
            // Pertahankan konten HTML tetapi tampilkan lebih rapi untuk UI
            $cleanedValue = strip_tags($value);
            // Hapus whitespace berlebih
            $cleanedValue = preg_replace('/\s+/', ' ', $cleanedValue);
            return trim($cleanedValue);
        }
        
        return $value;
    }

    /**
     * Memformat nilai jika berupa tanggal
     * Menstandarkan format tanggal untuk tampilan
     */
    protected function formatValueIfDate($value)
    {
        // Cek apakah nilai merupakan tanggal dengan format ISO
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
            try {
                // Coba parse sebagai tanggal dengan timezone server
                return Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d');
            } catch (\Exception $e) {
                // Jika gagal parse, kembalikan nilai asli
                return $value;
            }
        }
        
        // Cek apakah nilai berupa tanggal dengan format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            // Sudah dalam format yang diharapkan
            return $value;
        }
        
        // Coba parse format lain jika ada
        try {
            $date = Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Bukan tanggal valid, kembalikan nilai asli
            return $value;
        }
    }

    /**
     * Memeriksa apakah perubahan cukup signifikan untuk dicatat
     * Static method untuk digunakan di controller
     */
    public static function isSignificantChange($field, $oldValue, $newValue)
    {
        // Skip jika keduanya null atau empty string
        if (($oldValue === null || $oldValue === '') && 
            ($newValue === null || $newValue === '')) {
            return false;
        }
        
        // Jika field tanggal, normalisasi dulu
        if (strpos($field, 'tanggal') !== false) {
            try {
                if (!empty($oldValue)) {
                    $oldValue = Carbon::parse($oldValue)->format('Y-m-d');
                }
                if (!empty($newValue)) {
                    $newValue = Carbon::parse($newValue)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Jika gagal parse sebagai tanggal, gunakan nilai asli
            }
        }
        
        // Untuk field HTML, normalisasi whitespace
        if (strpos($oldValue, '<') !== false || strpos($newValue, '<') !== false) {
            $oldNormalized = preg_replace('/\s+|&nbsp;/', ' ', strip_tags($oldValue));
            $newNormalized = preg_replace('/\s+|&nbsp;/', ' ', strip_tags($newValue));
            
            return trim($oldNormalized) !== trim($newNormalized);
        }
        
        // Untuk nilai JSON
        $oldJson = json_decode($oldValue, true);
        $newJson = json_decode($newValue, true);
        
        if ($oldJson !== null && $newJson !== null) {
            // Untuk format [{"value":"x"}]
            if (is_array($oldJson) && isset($oldJson[0]['value']) && 
                is_array($newJson) && isset($newJson[0]['value'])) {
                return $oldJson[0]['value'] !== $newJson[0]['value'];
            }
            
            // Untuk format JSON lainnya, bandingkan sebagai string yang dinormalisasi
            return json_encode($oldJson, JSON_UNESCAPED_UNICODE) !== json_encode($newJson, JSON_UNESCAPED_UNICODE);
        }
        
        // Default: perubahan signifikan jika nilai berbeda
        return $oldValue !== $newValue;
    }

    /**
     * Boot the model.
     * Jalankan hook sebelum menyimpan untuk normalisasi nilai
     */
    protected static function boot()
    {
        parent::boot();

        // Hook yang berjalan sebelum model disimpan
        static::creating(function ($logIsu) {
            // Normalisasi tanggal jika field berkaitan dengan tanggal
            if (strpos($logIsu->field_changed, 'tanggal') !== false) {
                // Normalisasi nilai lama jika berupa tanggal
                if (!empty($logIsu->old_value)) {
                    try {
                        // Coba parse sebagai tanggal
                        $date = Carbon::parse($logIsu->old_value);
                        $logIsu->old_value = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Jika bukan tanggal valid, biarkan nilai asli
                    }
                }
                
                // Normalisasi nilai baru jika berupa tanggal
                if (!empty($logIsu->new_value)) {
                    try {
                        // Coba parse sebagai tanggal
                        $date = Carbon::parse($logIsu->new_value);
                        $logIsu->new_value = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Jika bukan tanggal valid, biarkan nilai asli
                    }
                }
            }
            
            // Jika tidak ada perubahan signifikan, batalkan pembuatan log
            if ($logIsu->action == 'UPDATE' && 
                !self::isSignificantChange($logIsu->field_changed, $logIsu->old_value, $logIsu->new_value)) {
                return false; // Batalkan pembuatan log
            }
        });
    }

    /**
     * Mendapatkan deskripsi perubahan yang lebih manusiawi
     */
    public function getHumanReadableChange()
    {
        if ($this->action == 'CREATE') {
            return 'Isu baru dibuat';
        } elseif ($this->action == 'DELETE') {
            return 'Isu dihapus';
        } elseif ($this->action == 'UPDATE') {
            $fieldName = $this->field_changed;
            
            // Jika perubahan pada field tanggal
            if (strpos($fieldName, 'tanggal') !== false) {
                return "Perubahan {$fieldName}";
            }
            
            $oldFormatted = $this->getFormattedOldValue();
            $newFormatted = $this->getFormattedNewValue();
            
            // Perubahan format JSON atau HTML tanpa perubahan nilai
            if (($oldFormatted == $newFormatted) && 
                ((json_decode($this->old_value) !== null) || 
                 (strpos($this->old_value, '<') !== false))) {
                return "Perubahan format pada {$fieldName}";
            }
            
            // Default: tampilkan perubahan nilai
            return "Mengubah {$fieldName} dari \"{$oldFormatted}\" menjadi \"{$newFormatted}\"";
        }
        
        return 'Perubahan dilakukan';
    }
    
    /**
     * Mendapatkan warna label untuk field tertentu
     */
    public function getFieldLabelColor()
    {
        $field = strtolower($this->field_changed);
        
        // Warna berdasarkan tipe field
        if (strpos($field, 'tanggal') !== false) {
            return 'bg-info';
        } elseif (in_array($field, ['status', 'kategori', 'prioritas'])) {
            return 'bg-primary';
        } elseif (in_array($field, ['judul', 'narasi', 'deskripsi', 'ringkasan', 'rangkuman'])) {
            return 'bg-success';
        } elseif (strpos($field, 'catatan') !== false) {
            return 'bg-warning';
        }
        
        return 'bg-secondary';
    }
}