<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="integer", example=1, description="ID pengguna"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Nama pengguna"),
 *     @OA\Property(property="email", type="string", example="johndoe@example.com", description="Email pengguna"),
 *     @OA\Property(property="umur", type="integer", example=25, description="Umur pengguna (opsional)"),
 *     @OA\Property(property="status_keanggotaan", type="string", example="active", description="Status keanggotaan (opsional)")
 * )
 */

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }
}
