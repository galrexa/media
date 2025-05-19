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
     * Memformat nilai lama untuk ditampilkan di history
     * 
     * @return string
     */
    public function getFormattedOldValue()
    {
        return $this->formatValue($this->old_value, $this->field_changed);
    }

    /**
     * Memformat nilai baru untuk ditampilkan di history
     * 
     * @return string
     */
    public function getFormattedNewValue()
    {
        return $this->formatValue($this->new_value, $this->field_changed);
    }

    /**
     * Memformat nilai sesuai tipe field
     * 
     * @param string $value
     * @param string $field
     * @return string
     */
    protected function formatValue($value, $field)
    {
        // Jika nilai kosong atau null
        if ($value === null || $value === '') {
            return '(kosong)';
        }

        // Format berdasarkan jenis field
        switch ($field) {
            case 'kategori':
                // Penanganan berbagai format kategori
                if (is_string($value)) {
                    // Cek jika string berbentuk JSON array
                    if ($this->isJson($value)) {
                        $decoded = json_decode($value, true);
                        
                        // Format: [{"value":"Makan Bergizi Gratis"}]
                        if (is_array($decoded) && isset($decoded[0]) && isset($decoded[0]['value'])) {
                            return implode(', ', array_column($decoded, 'value'));
                        }
                        
                        // Format: ["Makan Bergizi Gratis"] atau array sederhana lainnya
                        elseif (is_array($decoded)) {
                            return implode(', ', array_map(function($item) {
                                return is_array($item) ? json_encode($item) : $item;
                            }, $decoded));
                        }
                    }
                    
                    // Jika hanya string biasa, kembalikan apa adanya
                    return $value;
                }
                // Jika sudah berbentuk array
                elseif (is_array($value)) {
                    // Cek format [{"value":"Makan Bergizi Gratis"}]
                    if (isset($value[0]) && isset($value[0]['value'])) {
                        return implode(', ', array_column($value, 'value'));
                    }
                    
                    // Format array biasa
                    return implode(', ', array_map(function($item) {
                        return is_array($item) || is_object($item) ? json_encode($item) : $item;
                    }, $value));
                }
                
                // Fallback: kembalikan sebagai string
                return (string) $value;
                
            case 'status':
                // Cek apakah nilai adalah ID status
                if (is_numeric($value)) {
                    $status = RefStatus::find($value);
                    return $status ? $status->nama : (string) $value;
                }
                return (string) $value;

            // Format untuk tanggal
            case 'tanggal':
            case 'tanggal_mulai':
            case 'tanggal_selesai':
                if (strtotime($value)) {
                    return date('d/m/Y', strtotime($value));
                }
                return (string) $value;

            // Format untuk kolom WYSIWYG atau text panjang
            case 'deskripsi':
            case 'konten':
            case 'keterangan':
            case 'rangkuman':
            case 'narasi_positif':
            case 'narasi_negatif':
                return strip_tags((string) $value); // Menghapus tag HTML dan menampilkan semua teks
                
            default:
                // Pastikan nilai selalu dikembalikan sebagai string
                if (is_array($value)) {
                    try {
                        return json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        return '(data kompleks)';
                    }
                } elseif (is_object($value)) {
                    try {
                        return json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        return '(data kompleks)';
                    }
                }
                return (string) $value;
        }
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