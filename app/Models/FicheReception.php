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
        'caissette',
        'pourcentage_dechet',
        'collecte',
        'depart_champ',
        'retour_station',
        'calibre',
        'qualite_livraison',
        'societe_id', // ajout
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

    // Relation corrigée : Enqueteur, pas User
    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(Enqueteur::class);
    }

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function soufrages()
    {
        return $this->hasMany(Soufrage::class, 'reception_id');
    }

    public function getQuantiteKgAttribute(): ?float
    {
        if ($this->caissette && $this->poids_par_caissette) {
            return $this->caissette * $this->poids_par_caissette;
        }
        return null;
    }
}