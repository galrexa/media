<?php
// app/Models/Isu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RefSkala;
use App\Models\RefTone;
use App\Models\Kategori;
use App\Models\RefStatus;

class Isu extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'isus';

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'judul',
        'tanggal',
        'isu_strategis',
        'skala',
        'tone',
        'status_id', // Tambahan kolom status
        'rangkuman',
        'narasi_positif',
        'narasi_negatif',
        'alasan_penolakan',
        'created_by',
        'updated_by'
    ];

    // Relasi ke User pembuat
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke User yang terakhir edit
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relasi ke log
    public function logs()
    {
        return $this->hasMany(LogIsu::class, 'isu_id')->orderBy('created_at', 'desc');
    }

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'datetime',
        'isu_strategis' => 'boolean',
    ];

    /**
     * Mendapatkan semua referensi yang terkait dengan isu ini.
     */
    public function referensi()
    {
        return $this->hasMany(ReferensiIsu::class, 'isu_id');
    }

    /**
     * Relasi ke tabel kategori.
     */
    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'isu_kategori', 'isu_id', 'kategori_id')
                    ->withTimestamps();
    }

    /**
     * Relasi ke tabel ref_skala.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refSkala()
    {
        return $this->belongsTo(RefSkala::class, 'skala', 'id');
    }

    /**
     * Relasi ke tabel ref_tone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refTone()
    {
        return $this->belongsTo(RefTone::class, 'tone', 'id');
    }

    /**
     * Relasi ke tabel ref_status.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(RefStatus::class, 'status_id');
    }

    /**
     * Cek apakah isu dapat diedit oleh peran tertentu.
     *
     * @param string $role Nama peran user (editor, verifikator1, verifikator2)
     * @return bool
     */
    public function canBeEditedBy($role)
    {
        // Role editor hanya bisa edit jika status Draft atau Ditolak
        if ($role === 'editor') {
            return in_array($this->status_id, [RefStatus::DRAFT, RefStatus::DITOLAK]); // ID untuk Draft dan Ditolak
        }

        // Role verifikator1 hanya bisa edit jika status Verifikasi 1
        if ($role === 'verifikator1') {
            return $this->status_id === RefStatus::VERIFIKASI_1; // ID untuk Verifikasi 1 = 2
        }

        // Role verifikator2 hanya bisa edit jika status Verifikasi 2
        if ($role === 'verifikator2') {
            return $this->status_id === RefStatus::VERIFIKASI_2; // ID untuk Verifikasi 2 = 4 (PERBAIKAN!)
        }

        // Admin dapat edit semua status
        if ($role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Get isu yang dapat dilihat oleh peran tertentu berdasarkan status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role Nama peran user
     * @param int $userId ID user saat ini
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleToRole($query, $role, $userId)
    {
        if ($role === 'admin') {
            return $query;
        } elseif ($role === 'editor') {
            return $query->where('created_by', $userId);
        } elseif ($role === 'verifikator1') {
            return $query->where('status_id', RefStatus::VERIFIKASI_1);
        } elseif ($role === 'verifikator2') {
            return $query->where('status_id', RefStatus::VERIFIKASI_2);
        }

        return $query;
    }

    /**
     * Cek apakah isu sudah dipublikasi.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->status_id === RefStatus::DIPUBLIKASI;
    }

    /**
     * Cek apakah isu sedang ditolak.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status_id === RefStatus::DITOLAK;
    }

}
