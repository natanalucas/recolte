<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaletisationLot extends Model
{
    protected $fillable = [
        'paletisation_id',
        'lot_number',
        'code_traca_id',
        'nb_cartons',
    ];

    public function paletisation(): BelongsTo
    {
        return $this->belongsTo(Paletisation::class);
    }

    /**
     * NB : si le trait `HasCodeTraca` (utilisé par App\Models\Triage) expose déjà
     * une relation équivalente, vous pouvez le réutiliser ici à la place
     * (`use HasCodeTraca;`) pour rester cohérent avec le reste de l'app.
     */
    public function codeTraca(): BelongsTo
    {
        return $this->belongsTo(CodeTraca::class);
    }
}