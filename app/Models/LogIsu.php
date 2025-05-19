<?php
// app/Models/LogIsu.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
                return 'fas fa-plus-circle';
            case 'UPDATE':
                return 'fas fa-edit';
            case 'DELETE':
                return 'fas fa-trash';
            default:
                return 'fas fa-info-circle';
        }
    }

    /**
     * Memformat nilai sesuai tipe field dengan kontrol expand/collapse
     * 
     * @param string $value
     * @param string $field
     * @return array ['short' => string, 'full' => string, 'truncated' => bool]
     */
    protected function formatValue($value, $field)
    {
        // Jika nilai kosong atau null
        if ($value === null || $value === '') {
            return [
                'short' => '(kosong)',
                'full' => '(kosong)',
                'truncated' => false
            ];
        }

        // Format berdasarkan jenis field
        switch ($field) {
            case 'kategori':
                // 🔧 PERBAIKAN: Handle semua kemungkinan tipe data
                $formatted = '';
                
                if (is_string($value)) {
                    // Cek apakah string adalah JSON
                    if ($this->isJson($value)) {
                        $categories = json_decode($value, true);
                        if (is_array($categories)) {
                            $formatted = implode(', ', $categories);
                        } else {
                            // JSON decode gagal menghasilkan array
                            $formatted = $value;
                        }
                    } else {
                        // String biasa, bukan JSON
                        $formatted = $value;
                    }
                } elseif (is_array($value)) {
                    // ✅ Sudah array, langsung implode
                    $formatted = implode(', ', $value);
                } elseif (is_object($value)) {
                    // Object, convert ke JSON string
                    try {
                        $formatted = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        $formatted = '(data kompleks)';
                    }
                } else {
                    // Tipe data lain, convert ke string
                    $formatted = (string) $value;
                }
                
                return $this->handleLongText($formatted);
                
            case 'status':
                if (is_numeric($value)) {
                    try {
                        $status = \App\Models\RefStatus::find($value);
                        $formatted = $status ? $status->nama : (string) $value;
                    } catch (\Exception $e) {
                        $formatted = (string) $value;
                    }
                } else {
                    $formatted = (string) $value;
                }
                return $this->handleLongText($formatted);

            case 'tanggal':
            case 'tanggal_mulai':
            case 'tanggal_selesai':
                if (is_string($value) && strtotime($value)) {
                    $formatted = date('d/m/Y', strtotime($value));
                } else {
                    $formatted = (string) $value;
                }
                return $this->handleLongText($formatted);

            // 🔥 TEXT PANJANG DENGAN EXPAND/COLLAPSE
            case 'deskripsi':
            case 'konten':
            case 'keterangan':
                $strValue = strip_tags((string) $value);
                return $this->handleLongText($strValue);
                
            default:
                // 🔧 PERBAIKAN: Handle semua tipe data dengan aman
                $formatted = '';
                
                if (is_array($value)) {
                    try {
                        // Filter out non-scalar values untuk menghindari error
                        $scalarValues = array_filter($value, function($item) {
                            return is_scalar($item) || is_null($item);
                        });
                        $formatted = implode(', ', $scalarValues);
                    } catch (\Exception $e) {
                        $formatted = '(data kompleks)';
                    }
                } elseif (is_object($value)) {
                    try {
                        $formatted = json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        $formatted = '(data kompleks)';
                    }
                } else {
                    // Scalar value atau null
                    $formatted = (string) $value;
                }
                
                return $this->handleLongText($formatted);
        }
    }

    /**
     * Handle teks panjang dengan support expand/collapse
     * 
     * @param string $text
     * @param int $limit
     * @return array
     */
    protected function handleLongText($text, $limit = 100)
    {
        $text = trim($text);
        
        if (strlen($text) <= $limit) {
            return [
                'short' => $text,
                'full' => $text,
                'truncated' => false
            ];
        }
        
        return [
            'short' => substr($text, 0, $limit),
            'full' => $text,
            'truncated' => true
        ];
    }

    /**
     * Mendapatkan data formatted untuk old value
     * 
     * @return array
     */
    public function getFormattedOldValueData()
    {
        return $this->formatValue($this->old_value, $this->field_changed);
    }

    /**
     * Mendapatkan data formatted untuk new value
     * 
     * @return array
     */
    public function getFormattedNewValueData()
    {
        return $this->formatValue($this->new_value, $this->field_changed);
    }

    /**
     * Memformat nilai lama untuk ditampilkan di history
     * 
     * @return string
     */
    public function getFormattedOldValue()
    {
        $data = $this->getFormattedOldValueData();
        return $data['short'] . ($data['truncated'] ? '...' : '');
    }

    /**
     * Backward compatibility - tetap support method lama
     * 
     * @return string
     */
    public function getFormattedNewValue()
    {
        $data = $this->getFormattedNewValueData();
        return $data['short'] . ($data['truncated'] ? '...' : '');
    }

    /**
     * Mengecek apakah string adalah JSON valid
     * 
     * @param string $string
     * @return bool
     */
    protected function isJson($string) 
    {
        if (!is_string($string)) {
            return false;
        }
        
        // String kosong bukanlah JSON valid
        if (empty($string)) {
            return false;
        }
        
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}