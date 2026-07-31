<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FicheReception extends Model
{
    protected $fillable = [
        'poids_par_caissette',
        'fiche_number',
        'enqueteur_id',
        'parcelle_id',
        'voiture',
        'commune',
        'caissette',              // nombre saisi
        'pourcentage_dechet',     // % de déchet constaté
        'collecte',
        'depart_champ',
        'retour_station'
    ];

    protected $casts = [
        'depart_champ'   => 'datetime',
        'retour_station' => 'datetime',
        'collecte'       => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($fiche) {
            $fiche->updateQuietly([
                'fiche_number' => 'RECEP' . $fiche->id,
            ]);
        });
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    // Relation avec Soufrage (si nécessaire)
    public function soufrages()
    {
        return $this->hasMany(Soufrage::class, 'reception_id');
    }

    // Accesseur pour la quantité en kg
    public function getQuantiteKgAttribute(): ?float
    {
        if ($this->caissette && $this->poids_par_caissette) {
            return $this->caissette * $this->poids_par_caissette;
        }
        return null;
    }
}