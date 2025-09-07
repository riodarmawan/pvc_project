<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = [
        'username', 'password_hash', 'full_name', 'email', 'role_id', 'default_branch_id', 'is_active'
    ];

    protected $hidden = ['password_hash'];

    // Auth akan membaca hash dari kolom ini
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * [BARU] Relasi Many-to-Many ke tabel 'branches'.
     *
     * Menghubungkan seorang pengguna dengan banyak cabang melalui
     * tabel pivot 'user_branches'. Ini akan memperbaiki error.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,      // Model tujuan
            'user_branches',    // Nama tabel pivot
            'user_id',          // Foreign key di pivot untuk model ini (User)
            'branch_id'         // Foreign key di pivot untuk model tujuan (Branch)
        );
    }

    /**
     * [TAMBAHAN] Relasi ke Role pengguna.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * [TAMBAHAN] Relasi ke cabang default pengguna.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }
}
