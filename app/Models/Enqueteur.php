<?php
// app/Models/Enqueteur.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enqueteur extends Model
{
    use HasFactory, SoftDeletes;

    // ✅ Champs autorisés en écriture (jamais de guarded: [])
    protected $fillable = [
        'poste',
        'travail',
        'user_id',
        'is_active',
    ];

    // ✅ Champs cachés dans les réponses JSON
    protected $hidden = [
        'password',
        'deleted_at',
    ];

    // ✅ Cast automatique des types
    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Mutateur : hacher automatiquement le password
    protected function password(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => bcrypt($value),
        );
    }

    // ✅ Scope : uniquement les enquêteurs actifs
    public function scopeActif($query)
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        // Laravel va chercher par défaut la colonne 'user_id' dans votre table enquêteurs
        return $this->belongsTo(User::class);
    }

    protected function travail(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower(trim($value)),
        );
    }

    /**
     * Force le champ 'poste' en minuscules
     */
    protected function poste(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower(trim($value)),
        );
    }

}