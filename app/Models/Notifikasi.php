<?php
// app/Models/Notifikasi.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'link',
        'is_read',
        'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array'
    ];

    /**
     * Relasi ke user penerima notifikasi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menandai notifikasi sebagai sudah dibaca
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();

        return $this;
    }

    /**
     * Mendapatkan notifikasi yang belum dibaca
     */
    public static function getUnread($userId)
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    /**
     * Mendapatkan jumlah notifikasi yang belum dibaca
     */
    public static function getUnreadCount($userId)
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->count();
    }

    /**
     * Membuat notifikasi untuk verifikator ketika ada isu baru yang perlu diverifikasi
     */
    public static function createVerifikasiNotifikasi($isu, $roleTarget)
    {
        // Dapatkan semua user dengan role target
        $users = User::whereHas('role', function($query) use ($roleTarget) {
                    $query->where('name', $roleTarget);
                })->get();

        $statusName = '';
        if ($roleTarget === 'verifikator1') {
            $statusName = 'Verifikasi 1';
        } elseif ($roleTarget === 'verifikator2') {
            $statusName = 'Verifikasi 2';
        }

        foreach ($users as $user) {
            self::create([
                'user_id' => $user->id,
                'judul' => 'Isu Baru untuk Diverifikasi',
                'pesan' => "Isu '{$isu->judul}' memerlukan proses {$statusName}.",
                'tipe' => 'verifikasi',
                'link' => route('isu.show', $isu->id),
                'is_read' => false,
                'data' => [
                    'isu_id' => $isu->id,
                    'status_id' => $isu->status_id
                ]
            ]);
        }
    }
}
