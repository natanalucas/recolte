<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaletisationLot extends Model
{
    protected $fillable = [
        'paletisation_id',
        'lot_number',
        'code_traca_id',
        'nb_cartons',
    ];

    // Relation avec la palette
    public function paletisation(): BelongsTo
    {
        return $this->belongsTo(Paletisation::class);
    }

    // Relation avec le code de traçabilité
    public function codeTraca(): BelongsTo
    {
        return $this->belongsTo(CodeTraca::class);
    }

    // Relation many-to-many avec les certifications
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(TypeCertification::class, 'certification_paletisation_lot', 'paletisation_lot_id', 'type_certification_id')
                    ->withTimestamps();
    }
}