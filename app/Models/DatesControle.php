<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatesControle extends Model
{

    protected $table = 'dates_controle';

    protected $fillable = ['producteur_id', 'date_controle'];

    protected $casts = [
        'date_controle' => 'date',
    ];

    public function producteur(): BelongsTo
    {
        return $this->belongsTo(Producteur::class);
    }
}
