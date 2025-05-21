<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke tabel role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Mendapatkan nama role dari user.
     *
     * @return string
     */
    public function getHighestRoleName()
    {
        // Cek apakah role adalah objek hasil relasi
        if (is_object($this->role) && isset($this->role->name)) {
            return $this->role->name;
        } 
        
        // Jika tidak ada relasi role, cek apakah ada string role (legacy)
        if (isset($this->attributes['role']) && !empty($this->attributes['role'])) {
            return $this->attributes['role'];
        }
        
        return 'viewer';
    }

    /**
     * Cek apakah user memiliki role tertentu.
     *
     * @param string|array $roleName Role name atau array of role names
     * @return bool
     */
    public function hasRole($roleName)
    {
        // Dapatkan role name dari user
        $userRoleName = $this->getHighestRoleName();
        
        // Jika parameter adalah array, cek apakah role user ada dalam array tersebut
        if (is_array($roleName)) {
            return in_array($userRoleName, $roleName);
        }
        
        // Jika parameter adalah string, bandingkan langsung
        return $userRoleName === $roleName;
    }

    /**
     * Cek apakah user adalah admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Cek apakah user adalah editor.
     *
     * @return bool
     */
    public function isEditor()
    {
        return $this->hasRole('editor');
    }

    /**
     * Cek apakah user adalah verifikator level 1.
     *
     * @return bool
     */
    public function isVerifikator1()
    {
        return $this->hasRole('verifikator1');
    }

    /**
     * Cek apakah user adalah verifikator level 2.
     *
     * @return bool
     */
    public function isVerifikator2()
    {
        return $this->hasRole('verifikator2');
    }

    /**
     * Cek apakah user adalah viewer.
     *
     * @return bool
     */
    public function isViewer()
    {
        return $this->hasRole('viewer') || (!$this->role_id && !isset($this->attributes['role']));
    }
    
    /**
     * Metode helper untuk memeriksa apakah user memiliki salah satu dari beberapa role.
     *
     * @param array $roles Array dari role names
     * @return bool
     */
    public function hasAnyRole(array $roles)
    {
        return $this->hasRole($roles);
    }
}