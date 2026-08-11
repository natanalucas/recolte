<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paletisation extends Model
{
    protected $fillable = [
        'enqueteur_id',
        'fiche_number',
        'num_palette',
        'type_carton',
        'debut',
        'fin',
        'societe_id',
        // 'type_certification_id' supprimé
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin'   => 'datetime',
    ];

    public function lots(): HasMany
    {
        return $this->hasMany(PaletisationLot::class)->orderBy('lot_number');
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(Enqueteur::class, 'enqueteur_id');
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function expeditionPalettes()
    {
        return $this->hasMany(ExpeditionPalette::class);
    }
}