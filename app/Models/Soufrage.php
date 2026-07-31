<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCodeTraca;

class Soufrage extends Model
{
    use HasCodeTraca;
    
    protected $fillable = [
        'enqueteur_id', 'fiche_number', 'raqt_id', 'lieu_traitement',
        'cycle', 'box', 'concent', 'parcelle_id', 'code_traca_id',
        'caissette', 'soufre', 'debut', 'fin',
        'operateur_id', 'controle_raqt', 'reception_id'
    ];

    protected $casts = [
        'debut'         => 'datetime',
        'fin'           => 'datetime',
        'controle_raqt' => 'boolean',
    ];

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(Operateur::class);
    }

    public function raqt(): BelongsTo
    {
        return $this->belongsTo(Raqt::class);
    }

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class, 'parcelle_id');
    }

    public function codeTraca(): BelongsTo
    {
        return $this->belongsTo(CodeTraca::class, 'code_traca_id');
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(FicheReception::class, 'reception_id');
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(TypeCertification::class, 'concent');
    }
    
}