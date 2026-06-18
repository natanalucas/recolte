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
    ];

    public function lignes()
    {
        return $this->hasMany(ReceptionLigne::class);
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }
}
