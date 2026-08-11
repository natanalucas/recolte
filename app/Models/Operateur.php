<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operateur extends Model
{
    protected $fillable = ['nom', 'prenom', 'travail', 'societe_id'];

    // Accesseur pratique pour afficher le badge
    public function getTravailLabelAttribute(): string
    {
        return $this->travail === 'jour' ? '☀️ Jour' : '🌙 Nuit';
    }

    // Relation avec la société
    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }
}