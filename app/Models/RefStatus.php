<?php
// app/Models/RefStatus.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefStatus extends Model
{
    use HasFactory;

    protected $table = 'ref_status';

    protected $fillable = [
        'nama',
        'deskripsi',
        'warna',
        'urutan',
        'aktif'
    ];

    /**
     * List of status constants
     */
    const DRAFT = 1;
    const VERIFIKASI_1 = 2;
    const REVISI_L1 = 3;
    const VERIFIKASI_2 = 4;
    const REVISI_L2 = 5;
    const DIPUBLIKASI = 6;
    const DITOLAK = 7;

    /**
     * Mendapatkan ID untuk status Draft
     *
     * @return int
     */
    public static function getDraftId()
    {
        return self::DRAFT;
    }

    /**
     * Mendapatkan ID untuk status Verifikasi 1
     *
     * @return int
     */
    public static function getVerifikasi1Id()
    {
        return self::VERIFIKASI_1;
    }

    /**
     * Mendapatkan ID untuk status Verifikasi 2
     *
     * @return int
     */
    public static function getVerifikasi2Id()
    {
        return self::VERIFIKASI_2;
    }

    /**
     * Mendapatkan ID untuk status Ditolak
     *
     * @return int
     */
    public static function getDitolakId()
    {
        return self::DITOLAK;
    }

    /**
     * Mendapatkan ID untuk status Dipublikasi
     *
     * @return int
     */
    public static function getDipublikasiId()
    {
        return self::DIPUBLIKASI;
    }

    /**
     * Mendapatkan nama status berdasarkan ID
     *
     * @param int $id
     * @return string|null
     */
    public static function getNamaById($id)
    {
        $status = self::find($id);
        return $status ? $status->nama : null;
    }

    /**
     * Mendapatkan semua status yang aktif
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return self::where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    /**
     * Scope a query to only include active statuses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Mendapatkan isus dengan status ini
     */
    public function isus()
    {
        return $this->hasMany(Isu::class, 'status_id');
    }
}
