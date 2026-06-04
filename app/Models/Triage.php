<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCodeTraca;

class Triage extends Model
{
    use HasCodeTraca;

    protected $fillable = [
        'agent_name', 'fiche_number', 'code_traca_id', 'type_carton',
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
}