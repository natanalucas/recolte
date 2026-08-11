<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeCertification extends Model
{
    // Ajout de societe_id dans les champs mass-assignable
    protected $fillable = ['nom', 'societe_id'];

    // Relation avec la table des sociétés
    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function triages(): BelongsToMany
    {
        return $this->belongsToMany(Triage::class, 'triage_certification');
    }
}