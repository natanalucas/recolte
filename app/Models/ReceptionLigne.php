<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceptionLigne extends Model
{
    protected $fillable = [
        'fiche_reception_id',
        'parcelle_id',
        'voiture',
        'commune',
        'caissette',             // nombre saisi
        'collecte',
        'depart_champ',
        'retour_station',
    ];

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    // Accesseur pratique si besoin côté API/PDF
    public function getQuantiteKgAttribute(): ?float
    {
        if ($this->caissette && $this->ficheReception?->poids_par_caissette) {
            return $this->caissette * $this->ficheReception->poids_par_caissette;
        }
        return null;
    }

    public function ficheReception()
    {
        return $this->belongsTo(FicheReception::class);
    }
}
