<?php
// app/Models/User.php - Complete with missing methods

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Hanya field yang diperlukan dari API KSP:
     * - namalengkap, position, department, profile_photo, username, user_id, employee_id, email, password
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',              // namalengkap dari API
        'username',          // username sistem (tidak dari API)
        'email',             // uname dari API (jika valid email)
        'password',          // password sistem (tidak dari API)
        'role_id',           // role lokal sistem
        'api_user_id',       // id_user dari API
        'employee_id',       // id_pegawai dari API
        'position',          // jabatan dari API
        'department',        // satuankerja dari API
        'profile_photo',     // status foto dari API
        'is_active',         // kontrol akses
        'last_api_login',    // tracking API login
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
            'is_active' => 'boolean',
            'last_api_login' => 'datetime',
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
        if (is_object($this->role) && isset($this->role->name)) {
            return $this->role->name;
        } 
        
        if (isset($this->attributes['role']) && !empty($this->attributes['role'])) {
            return $this->attributes['role'];
        }
        
        return 'viewer';
    }

    /**
     * Cek apakah user memiliki role tertentu.
     *
     * @param string|array $roleName
     * @return bool
     */
    public function hasRole($roleName)
    {
        $userRoleName = $this->getHighestRoleName();
        
        if (is_array($roleName)) {
            return in_array($userRoleName, $roleName);
        }
        
        return $userRoleName === $roleName;
    }

    /**
     * Helper methods untuk role checking
     */
    public function isAdmin() { return $this->hasRole('admin'); }
    public function isEditor() { return $this->hasRole('editor'); }
    public function isVerifikator1() { return $this->hasRole('verifikator1'); }
    public function isVerifikator2() { return $this->hasRole('verifikator2'); }
    public function isViewer() { return $this->hasRole('viewer') || (!$this->role_id && !isset($this->attributes['role'])); }
    public function hasAnyRole(array $roles) { return $this->hasRole($roles); }

    /**
     * Cek apakah user sudah pernah login via API (memiliki data dari KSP).
     * 
     * @return bool
     */
    public function hasCompletedApiLogin()
    {
        return !empty($this->api_user_id) && !empty($this->name);
    }

    /**
     * MISSING METHOD: Helper method untuk cek kolom exists dengan safe error handling
     * 
     * @param string $column
     * @return bool
     */
    public function columnExists(string $column): bool
    {
        try {
            return Schema::hasColumn($this->getTable(), $column);
        } catch (\Exception $e) {
            \Log::warning("Cannot check if column exists: {$this->getTable()}.{$column}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update data user dari response API KSP - FIELD TERPILIH SAJA
     * Hanya mengambil: namalengkap, position, department, profile_photo, user_id, employee_id, email
     * 
     * @param array $apiData Data dari API KSP response
     * @return bool Success status
     */
    public function updateFromApiData(array $apiData): bool
    {
        try {
            $updateData = [];
            
            \Log::info('Updating selected user data from KSP API', [
                'user_id' => $this->id,
                'username' => $this->username,
                'selected_fields_only' => true,
                'received_api_data' => array_keys($apiData), // DEBUG: Log field names yang diterima
            ]);
            
            // 1. NAMA LENGKAP - dari field 'namalengkap'
            if (isset($apiData['namalengkap']) && !empty(trim($apiData['namalengkap']))) {
                $updateData['name'] = trim($apiData['namalengkap']);
                \Log::info("Updated name from 'namalengkap'", ['value' => $updateData['name']]);
            }
            
            // 2. POSITION/JABATAN - dari field 'jabatan'
            if (isset($apiData['jabatan']) && !empty(trim($apiData['jabatan']))) {
                if ($this->columnExists('position')) {
                    $updateData['position'] = trim($apiData['jabatan']);
                    \Log::info("Updated position from 'jabatan'", ['value' => $updateData['position']]);
                } else {
                    \Log::warning("Position column does not exist in users table");
                }
            } else {
                \Log::info("No 'jabatan' field in API data or value is empty", [
                    'jabatan_exists' => isset($apiData['jabatan']),
                    'jabatan_value' => $apiData['jabatan'] ?? 'N/A'
                ]);
            }
            
            // 3. DEPARTMENT - dari field 'satuankerja'
            if (isset($apiData['satuankerja']) && !empty(trim($apiData['satuankerja']))) {
                if ($this->columnExists('department')) {
                    $updateData['department'] = trim($apiData['satuankerja']);
                    \Log::info("Updated department from 'satuankerja'", ['value' => $updateData['department']]);
                } else {
                    \Log::warning("Department column does not exist in users table");
                }
            } else {
                \Log::info("No 'satuankerja' field in API data or value is empty", [
                    'satuankerja_exists' => isset($apiData['satuankerja']),
                    'satuankerja_value' => $apiData['satuankerja'] ?? 'N/A'
                ]);
            }
            
            // 4. PROFILE PHOTO - dari field 'foto' (hanya indikator)
            if (isset($apiData['foto']) && !empty($apiData['foto'])) {
                if ($this->columnExists('profile_photo')) {
                    $updateData['profile_photo'] = 'has_photo';
                    \Log::info("Updated profile photo status", ['value' => 'has_photo']);
                }
            }
            
            // 5. API USER ID - dari field 'id_user'
            if (isset($apiData['id_user']) && !empty($apiData['id_user'])) {
                $updateData['api_user_id'] = $apiData['id_user'];
                \Log::info("Updated API user ID", ['value' => $updateData['api_user_id']]);
            }
            
            // 6. EMPLOYEE ID - dari field 'id_pegawai'
            if (isset($apiData['id_pegawai']) && !empty($apiData['id_pegawai'])) {
                if ($this->columnExists('employee_id')) {
                    $updateData['employee_id'] = $apiData['id_pegawai'];
                    \Log::info("Updated employee ID", ['value' => $updateData['employee_id']]);
                }
            }
            
            // 7. EMAIL - dari field 'uname' (jika format email valid)
            if (isset($apiData['uname']) && !empty(trim($apiData['uname']))) {
                $emailCandidate = trim($apiData['uname']);
                if (filter_var($emailCandidate, FILTER_VALIDATE_EMAIL)) {
                    $updateData['email'] = $emailCandidate;
                    \Log::info("Updated email from 'uname'", ['value' => $updateData['email']]);
                } else {
                    \Log::info("uname field not valid email format", ['value' => $emailCandidate]);
                }
            }
            
            // 8. Set status aktif dan waktu login API (selalu)
            if ($this->columnExists('is_active')) {
                $updateData['is_active'] = true;
                \Log::info("Set user status to active");
            }
            
            if ($this->columnExists('last_api_login')) {
                $updateData['last_api_login'] = now();
                \Log::info("Updated last API login time");
            }
            
            // 9. UPDATE USER jika ada data yang perlu diupdate
            if (!empty($updateData)) {
                $this->update($updateData);
                
                \Log::info('Selected user data updated successfully from KSP API', [
                    'user_id' => $this->id,
                    'username' => $this->username,
                    'updated_fields' => array_keys($updateData),
                    'field_count' => count($updateData),
                    // DEBUG: Log nilai akhir
                    'final_position' => $this->fresh()->position,
                    'final_department' => $this->fresh()->department,
                ]);
                
                return true;
            }
            
            \Log::info('No selected fields to update from KSP API', [
                'user_id' => $this->id,
                'username' => $this->username,
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            \Log::error('User updateFromApiData failed', [
                'user_id' => $this->id,
                'username' => $this->username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get display name dengan fallback
     * 
     * @return string
     */
    public function getDisplayName(): string
    {
        // Priority: name (namalengkap dari API) > username
        if (!empty($this->name)) {
            return $this->name;
        }
        
        return $this->username;
    }

    /**
     * Get formatted position dengan department
     * 
     * @return string
     */
    public function getFormattedPosition(): string
    {
        $parts = [];
        
        if (!empty($this->position)) {
            $parts[] = $this->position;
        }
        
        if (!empty($this->department)) {
            $parts[] = $this->department;
        }
        
        return implode(' - ', $parts) ?: 'Tidak diketahui';
    }

    /**
     * Get user status information - simplified untuk field yang diperlukan
     * 
     * @return array
     */
    public function getStatusInfo(): array
    {
        return [
            'is_active' => $this->is_active ?? true,
            'has_api_data' => $this->hasCompletedApiLogin(),
            'last_api_login' => $this->last_api_login,
            'role' => $this->getHighestRoleName(),
            'display_name' => $this->getDisplayName(),
            'position' => $this->getFormattedPosition(),
            'employee_id' => $this->employee_id,
            'api_user_id' => $this->api_user_id,
            'has_photo' => !empty($this->profile_photo),
            'email' => $this->email,
        ];
    }

    /**
     * Get summary info untuk display di UI
     * 
     * @return array
     */
    public function getSummaryInfo(): array
    {
        return [
            'name' => $this->getDisplayName(),
            'position' => $this->getFormattedPosition(),
            'email' => $this->email ?: 'Tidak ada email',
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
            'api_connected' => $this->hasCompletedApiLogin() ? 'Terhubung' : 'Belum terhubung',
            'role' => ucfirst($this->getHighestRoleName()),
        ];
    }

    /**
     * Scopes - hanya yang diperlukan
     */
    public function scopeActive($query) 
    { 
        return $query->where('is_active', true); 
    }
    
    public function scopeApiConnected($query) 
    { 
        return $query->whereNotNull('api_user_id'); 
    }
    
    public function scopeByRole($query, $roleName) 
    { 
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }
    
    public function scopeByDepartment($query, $department) 
    { 
        return $query->where('department', $department); 
    }

    public function scopeByPosition($query, $position) 
    { 
        return $query->where('position', 'like', "%{$position}%"); 
    }

    /**
     * Accessor untuk nama yang selalu ada value
     */
    public function getNameAttribute($value)
    {
        return $value ?: $this->username;
    }

    /**
     * Mutator untuk nama - trim whitespace
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value ? trim($value) : null;
    }

    /**
     * Mutator untuk position - trim whitespace
     */
    public function setPositionAttribute($value)
    {
        $this->attributes['position'] = $value ? trim($value) : null;
    }

    /**
     * Mutator untuk department - trim whitespace
     */
    public function setDepartmentAttribute($value)
    {
        $this->attributes['department'] = $value ? trim($value) : null;
    }

    /**
     * Check apakah user sudah lengkap datanya
     * 
     * @return bool
     */
    public function isProfileComplete(): bool
    {
        return !empty($this->name) && 
               !empty($this->position) && 
               !empty($this->department) &&
               !empty($this->api_user_id);
    }

    /**
     * Get missing profile fields
     * 
     * @return array
     */
    public function getMissingProfileFields(): array
    {
        $missing = [];
        
        if (empty($this->name)) $missing[] = 'Nama lengkap';
        if (empty($this->position)) $missing[] = 'Jabatan';
        if (empty($this->department)) $missing[] = 'Unit kerja';
        if (empty($this->api_user_id)) $missing[] = 'Koneksi API KSP';
        if (empty($this->email)) $missing[] = 'Email';
        
        return $missing;
    }
}