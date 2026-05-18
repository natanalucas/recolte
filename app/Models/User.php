<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Traits\HasRole;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;
    use HasRole;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'password',
        'role_id', // N'oubliez pas de l'ajouter ici
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    
protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::title(mb_strtolower($value)),
        );
    }

    /**
     * Formate le prénom (Première lettre en majuscule)
     */
    protected function firstname(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::ucfirst(mb_strtolower($value)),
        );
    }

    /**
     * Formate le nom de famille (Tout en majuscule)
     */
    protected function lastname(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_strtoupper($value),
        );
    }
}
