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

    const UPDATED_AT = null;
    const CREATED_AT = 'created_at';

    public function isu()
    {
        return $this->belongsTo(Isu::class, 'isu_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(RefStatus::class, 'status_id');
    }

    public function scopeStatusChanges($query)
    {
        return $query->where('field_changed', 'status');
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function setKategoriAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['kategori'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value) && !empty($value)) {
            $this->attributes['kategori'] = $this->isJson($value) ? $value : json_encode([$value], JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['kategori'] = $value;
        }
    }

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

    public function getFormattedOldValue()
    {
        return $this->formatValue($this->old_value, $this->field_changed);
    }

    public function getFormattedNewValue()
    {
        return $this->formatValue($this->new_value, $this->field_changed);
    }

    protected function formatValue($value, $field)
    {
        if ($value === null || $value === '') {
            return '(kosong)';
        }

        switch ($field) {
            case 'kategori':
                try {
                    if (is_string($value)) {
                        if ($this->isJson($value)) {
                            $decoded = json_decode($value, true);
                            if (is_array($decoded) && isset($decoded[0]) && isset($decoded[0]['value'])) {
                                return implode(', ', array_column($decoded, 'value'));
                            } elseif (is_array($decoded)) {
                                return implode(', ', array_map(function($item) {
                                    return is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item;
                                }, $decoded));
                            }
                        }
                        return $value;
                    } elseif (is_array($value)) {
                        if (isset($value[0]) && isset($value[0]['value'])) {
                            return implode(', ', array_column($value, 'value'));
                        }
                        return implode(', ', array_map(function($item) {
                            return is_array($item) || is_object($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item;
                        }, $value));
                    }
                    return (string) $value;
                } catch (\Exception $e) {
                    \Log::error('Gagal memformat kategori: ' . $e->getMessage());
                    return '(Format kategori tidak valid)';
                }
                
            case 'status':
                if (is_numeric($value)) {
                    $status = RefStatus::find($value);
                    return $status ? $status->nama : (string) $value;
                }
                return (string) $value;

            case 'tanggal':
            case 'tanggal_mulai':
            case 'tanggal_selesai':
                if (strtotime($value)) {
                    return date('d/m/Y', strtotime($value));
                }
                return (string) $value;

            case 'deskripsi':
            case 'konten':
            case 'keterangan':
            case 'rangkuman':
            case 'narasi_positif':
            case 'narasi_negatif':
                return $this->normalizeString(strip_tags((string) $value));
                
            default:
                if (is_string($value)) {
                    return $this->normalizeString($value);
                }
                if (is_array($value)) {
                    try {
                        return json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        \Log::error('Gagal memformat data: ' . $e->getMessage());
                        return '(Format data tidak valid)';
                    }
                } elseif (is_object($value)) {
                    try {
                        return json_encode($value, JSON_UNESCAPED_UNICODE);
                    } catch (\Exception $e) {
                        \Log::error('Gagal memformat data: ' . $e->getMessage());
                        return '(Format data tidak valid)';
                    }
                }
                return $this->normalizeString((string) $value);
        }
    }

    protected function isJson($string) 
    {
        if (!is_string($string)) {
            return false;
        }
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public function getInitialIsuData()
    {
        if ($this->action !== 'CREATE') {
            return [];
        }
        
        $initialData = [
            'field_changed' => $this->field_changed,
            'value' => $this->getFormattedNewValue()
        ];
        
        return $initialData;
    }

    public function getProcessedValue($field, $value)
    {
        if ($value === null || $value === '' || $value === '(kosong)') {
            return '(kosong)';
        }
        
        if (in_array($field, ['rangkuman', 'narasi_positif', 'narasi_negatif', 'deskripsi'])) {
            $value = strip_tags($value);
            $value = $this->normalizeString($value);
        }
        
        if ($field === 'tone' && is_numeric($value)) {
            try {
                $tone = \App\Models\RefTone::find($value);
                return $tone ? $tone->nama : "Tone {$value}";
            } catch (\Exception $e) {
                \Log::error('Gagal memformat tone: ' . $e->getMessage());
                return "Tone {$value}";
            }
        }
        
        if ($field === 'skala' && is_numeric($value)) {
            try {
                $skala = \App\Models\RefSkala::find($value);
                return $skala ? $skala->nama : "Skala {$value}";
            } catch (\Exception $e) {
                \Log::error('Gagal memformat skala: ' . $e->getMessage());
                return "Skala {$value}";
            }
        }
        
        if ($field === 'status' && is_string($value) && $this->isJson($value)) {
            try {
                $statusData = json_decode($value, true);
                if (isset($statusData['nama'])) {
                    return $statusData['nama'];
                }
            } catch (\Exception $e) {
                \Log::error('Gagal memformat status: ' . $e->getMessage());
            }
        }
        
        if (is_string($value)) {
            return $this->normalizeString($value);
        }
        
        return $value;
    }

    protected function normalizeString($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);
        
        return $string;
    }

    public function hasMeaningfulChange($oldValue, $newValue)
    {
        // Normalisasi nilai untuk field kategori
        if ($this->field_changed === 'kategori') {
            // Konversi old_value ke array kategori
            $oldCategories = [];
            if (is_string($oldValue)) {
                if ($this->isJson($oldValue)) {
                    $decoded = json_decode($oldValue, true);
                    if (is_array($decoded) && isset($decoded[0]) && isset($decoded[0]['value'])) {
                        $oldCategories = array_column($decoded, 'value');
                    } elseif (is_array($decoded)) {
                        $oldCategories = $decoded;
                    }
                } else {
                    $oldCategories = array_map('trim', explode(',', $oldValue));
                }
            } elseif (is_array($oldValue)) {
                if (isset($oldValue[0]) && isset($oldValue[0]['value'])) {
                    $oldCategories = array_column($oldValue, 'value');
                } else {
                    $oldCategories = $oldValue;
                }
            }
            
            // Konversi new_value ke array kategori
            $newCategories = [];
            if (is_string($newValue)) {
                if ($this->isJson($newValue)) {
                    $decoded = json_decode($newValue, true);
                    if (is_array($decoded) && isset($decoded[0]) && isset($decoded[0]['value'])) {
                        $newCategories = array_column($decoded, 'value');
                    } elseif (is_array($decoded)) {
                        $newCategories = $decoded;
                    }
                } else {
                    $newCategories = array_map('trim', explode(',', $newValue));
                }
            } elseif (is_array($newValue)) {
                if (isset($newValue[0]) && isset($newValue[0]['value'])) {
                    $newCategories = array_column($newValue, 'value');
                } else {
                    $newCategories = $newValue;
                }
            }
            
            // Normalisasi: hapus kategori kosong, ubah ke lowercase, dan urutkan
            $oldCategories = array_filter(array_map(function($item) {
                return is_string($item) ? strtolower(trim($item)) : $item;
            }, $oldCategories));
            $newCategories = array_filter(array_map(function($item) {
                return is_string($item) ? strtolower(trim($item)) : $item;
            }, $newCategories));
            
            sort($oldCategories);
            sort($newCategories);
            
            // Bandingkan array kategori
            return $oldCategories !== $newCategories;
        }
        
        // Untuk field lain, gunakan normalisasi default
        $normalizedOld = $this->normalizeString($oldValue);
        $normalizedNew = $this->normalizeString($newValue);
        
        return $normalizedOld !== $normalizedNew;
    }

    public function getProcessedOldValue()
    {
        return $this->getProcessedValue($this->field_changed, $this->getFormattedOldValue());
    }

    public function getProcessedNewValue()
    {
        return $this->getProcessedValue($this->field_changed, $this->getFormattedNewValue());
    }
}