<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcelle extends Model
{
    protected $fillable = [
        'producteur_id',
        'num',
        'pieds',
        'surface',
        'gps_lat',
        'gps_long',
        'localisation'
    ];

    protected $casts = [
        'surface'       => 'float',
        'gps_lat'       => 'float',
        'gps_long'      => 'float'
    ];
    public function producteur(): BelongsTo
    {
        return $this->belongsTo(Producteur::class);
    }
}