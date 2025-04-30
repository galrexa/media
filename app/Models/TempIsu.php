<?php
// app/Models/TempIsu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempIsu extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'temp_isus';

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
        'rangkuman',
        'narasi_positif',
        'narasi_negatif',
        'created_by',
        'updated_by',
        'status_id'
    ];

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
     * Relasi ke status isu
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refStatus()
    {
        return $this->belongsTo(RefStatus::class, 'status_id');
    }

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
     * Mendapatkan semua referensi yang terkait dengan isu ini.
     */
    public function referensi()
    {
        return $this->hasMany(ReferensiIsu::class, 'isu_id');
    }

    /**
     * Relasi ke kategori
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
     * Relasi ke User sebagai verifikator level 1
     */
    public function verifikatorL1()
    {
        return $this->belongsTo(User::class, 'verifikator_l1_id');
    }

    /**
     * Relasi ke User sebagai verifikator level 2
     */
    public function verifikatorL2()
    {
        return $this->belongsTo(User::class, 'verifikator_l2_id');
    }

    /**
     * Mendapatkan kode status isu saat ini
     *
     * @return string|null
     */
    public function getStatusKode()
    {
        if ($this->refStatus) {
            return $this->refStatus->kode;
        }
        return null;
    }

    /**
     * Memeriksa apakah isu masih berstatus draft
     */
    public function isDraft()
    {
        return $this->getStatusKode() === 'draft';
    }

    /**
     * Memeriksa apakah isu sedang menunggu verifikasi L1
     */
    public function isMenungguVerifikasiL1()
    {
        return $this->getStatusKode() === 'menunggu_v1';
    }

    /**
     * Memeriksa apakah isu sedang dalam revisi editor
     */
    public function isRevisiEditor()
    {
        return $this->getStatusKode() === 'revisi_editor';
    }

    /**
     * Memeriksa apakah isu sedang menunggu verifikasi L2
     */
    public function isMenungguVerifikasiL2()
    {
        return $this->getStatusKode() === 'menunggu_v2';
    }

    /**
     * Memeriksa apakah isu dikembalikan ke verifikator L1
     */
    public function isRevisiKeVerifikatorL1()
    {
        return $this->getStatusKode() === 'revisi_ke_v1';
    }

    /**
     * Memeriksa apakah isu sudah siap dipublikasikan
     */
    public function isReadyToPublish()
    {
        return $this->getStatusKode() === 'dipublikasikan';
    }

    /**
     * Memeriksa apakah isu sudah ditolak
     */
    public function isRejected()
    {
        return $this->getStatusKode() === 'ditolak';
    }

    /**
     * Memeriksa apakah isu sedang dalam proses verifikasi
     */
    public function isInVerification()
    {
        $statusKode = $this->getStatusKode();
        return in_array($statusKode, ['menunggu_v1', 'menunggu_v2', 'revisi_editor', 'revisi_ke_v1']);
    }

    /**
     * Mengonversi TempIsu ke Isu
     */
    public function convertToIsu()
    {
        $isu = new Isu();

        // Transfer semua atribut yang ada di kedua tabel
        $isu->judul = $this->judul;
        $isu->tanggal = $this->tanggal;
        $isu->isu_strategis = $this->isu_strategis;
        $isu->skala = $this->skala;
        $isu->tone = $this->tone;
        $isu->rangkuman = $this->rangkuman;
        $isu->narasi_positif = $this->narasi_positif;
        $isu->narasi_negatif = $this->narasi_negatif;
        $isu->created_by = $this->created_by;
        $isu->updated_by = $this->updated_by;
        $isu->save();

        // Transfer relasi ke tabel pivot (seperti kategoris)
        if ($this->kategoris()->exists()) {
            $kategoriIds = $this->kategoris->pluck('id')->toArray();
            $isu->kategoris()->sync($kategoriIds);
        }

        // Transfer referensi
        if ($this->referensi()->exists()) {
            foreach ($this->referensi as $ref) {
                $newRef = $ref->replicate();
                $newRef->isu_id = $isu->id;
                $newRef->save();
            }
        }

        return $isu;
    }
}
