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
        'status_id'  // Tambahkan kolom status_id
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
}
