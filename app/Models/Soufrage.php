<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCodeTraca;

class Soufrage extends Model
{
    use HasCodeTraca;
    
    protected $fillable = [
        'agent_name', 'fiche_number', 'raqt_id', 'lieu_traitement',
        'cycle', 'box', 'concent', 'parcelle', 'code_traca_id',
        'caissette', 'soufre', 'debut', 'fin',
        'operateur_id', 'controle_raqt',
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
}