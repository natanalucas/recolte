<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RAQT extends Model
{
    protected $table = 'raqt';

    protected $fillable = [
        'nom',
        'prenom',
    ];

}
