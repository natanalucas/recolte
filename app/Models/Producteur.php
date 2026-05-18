<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producteur extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'phone',
        'adresse',
        'ggn',
        'produit',
    ];

    public function parcelles(): HasMany
    {
        return $this->hasMany(Parcelle::class);
    }

    public function controles(): HasMany
    {
        return $this->hasMany(DatesControle::class);
    }
}