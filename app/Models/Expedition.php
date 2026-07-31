<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Expedition extends Model
{
    protected $fillable = [
        'enqueteur_id',
        'fiche_number',
        'conteneur',
        'immatriculation',
        'proprete_conteneur',
        'proprete_camion',
        'debut_empotage',
        'fin_empotage',
        'depart_station',
        'arrivee_port',
        'bateau',
        'bon_livraison',
        'observations',
    ];

    protected $casts = [
        'debut_empotage' => 'datetime',
        'fin_empotage'   => 'datetime',
        'depart_station' => 'datetime',
        'arrivee_port'   => 'datetime',
    ];

    public function palettes()
    {
        return $this->hasMany(ExpeditionPalette::class);
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }
}