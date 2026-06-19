<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCodeTraca;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Triage extends Model
{
    use HasCodeTraca;

    protected $fillable = [
        'enqueteur_id', 'fiche_number', 'code_traca_id', 'type_carton',
        'type_certification_id', 'debut', 'fin', 'tapis', 'nombre', 'qualite',
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin'   => 'datetime',
        'tapis' => 'array',
    ];

    public function certification()
    {
        return $this->belongsTo(TypeCertification::class, 'type_certification_id');
    }

    public function codeTraca()
    {
        return $this->belongsTo(CodeTraca::class, 'code_traca_id');
    }

    public function enqueteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enqueteur_id');
    }
}