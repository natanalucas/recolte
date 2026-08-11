<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Societe extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'phone',
        'nif',
        'stat',
        'latitude',
        'longitude',
        'logo',
        'manager_id',
    ];

    // Accessor optionnel pour l'URL complète du logo
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
    // Relation avec le Manager (User)
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
