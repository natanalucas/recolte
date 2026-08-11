<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCodeTraca;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Triage extends Model
{
    use HasCodeTraca;

    protected $fillable = [
        'enqueteur_id',
        'fiche_number',
        'code_traca_id',
        'type_carton',
        // 'type_certification_id' → supprimé, car relation many-to-many
        'debut',
        'fin',
        'tapis',
        'nombre',
        'qualite',
        'societe_id',
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin'   => 'datetime',
        'tapis' => 'array',
    ];

    // Nouvelle relation many-to-many
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(TypeCertification::class, 'certification_triage', 'triage_id', 'type_certification_id')->withTimestamps();
    }

    // Relation avec le code de traçabilité
    public function codeTraca(): BelongsTo
    {
        return $this->belongsTo(CodeTraca::class, 'code_traca_id');
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(Enqueteur::class, 'enqueteur_id');
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }
}