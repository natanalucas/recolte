<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FicheReception extends Model
{
    protected $fillable = [
        'poids_par_caissette', 
        'fiche_number',
        'enqueteur_id',
    ];

    public function lignes()
    {
        return $this->hasMany(ReceptionLigne::class);
    }
}
