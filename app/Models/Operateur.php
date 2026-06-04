<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operateur extends Model
{
    protected $fillable = ['nom', 'prenom', 'travail'];

    // Accesseur pratique pour afficher le badge
    public function getTravailLabelAttribute(): string
    {
        return $this->travail === 'jour' ? '☀️ Jour' : '🌙 Nuit';
    }
}
