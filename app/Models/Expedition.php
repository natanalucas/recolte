<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expedition extends Model
{
    protected $fillable = [
        'enqueteur_id', 'fiche_number', 'conteneur', 'immatriculation',
        'proprete_conteneur', 'proprete_camion', 'debut_empotage',
        'fin_empotage', 'depart_station', 'arrivee_port', 'bateau',
        'bon_livraison', 'observations'
    ];

    public function palettes()
    {
        return $this->hasMany(ExpeditionPalette::class);
    }
}