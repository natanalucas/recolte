<?php

namespace App\Traits;

use App\Models\CodeTraca;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCodeTraca
{
    public function codeTraca(): BelongsTo
    {
        // On combine ça avec la simplification précédente ;)
        return $this->belongsTo(CodeTraca::class);
    }
}