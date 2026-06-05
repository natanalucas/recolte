<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeTraca extends Model
{
    protected $table = 'code_traca';
    protected $fillable = ['code', 'parcelle_id'];

    public function parcelle()
    {
        return $this->hasMany(Parcelle::class, 'parcelle_id');
    }
}
