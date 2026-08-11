<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enqueteur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'poste',
        'travail',
        'user_id',
        'is_active',
        // 'societe_id' a été supprimé (désormais dans User)
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActif($query)
    {
        return $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // La société est accessible via l'utilisateur associé
    // On peut définir un accesseur pour faciliter l'affichage
    public function getSocieteIdAttribute()
    {
        return $this->user?->societe_id;
    }

    public function getSocieteNomAttribute()
    {
        return $this->user?->societe?->nom;
    }
}