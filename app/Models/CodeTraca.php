<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeTraca extends Model
{
    protected $table = 'code_traca';
    protected $fillable = ['code', 'parcelle_id', 'societe_id'];

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class, 'parcelle_id');
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class, 'societe_id');
    }

    public function soufrages()
    {
        return $this->hasMany(Soufrage::class);
    }

    public function triages()
    {
        return $this->hasMany(Triage::class);
    }

    public function paletisationLots()
    {
        return $this->hasMany(PaletisationLot::class);
    }
}
