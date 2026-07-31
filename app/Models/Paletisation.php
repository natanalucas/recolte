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
        'type_certification_id',
        'debut',
        'fin',
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin'   => 'datetime',
    ];
    /**
     * Les 3 lots rattachés à cette palette, toujours triés par numéro de lot.
     */
    public function lots(): HasMany
    {
        return $this->hasMany(PaletisationLot::class)->orderBy('lot_number');
    }

    public function typeCertification(): BelongsTo
    {
        return $this->belongsTo(TypeCertification::class);
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }


    public function expeditionPalettes()
    {
        return $this->hasMany(ExpeditionPalette::class);
    }




}