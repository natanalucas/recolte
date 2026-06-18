<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpeditionPalette extends Model
{
    protected $fillable = ['expedition_id', 'paletisation_id'];

    public function paletisation()
    {
        return $this->belongsTo(Paletisation::class);
    }
}
