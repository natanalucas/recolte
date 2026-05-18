<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    protected $fillable = [
        'slug',
        'name'
    ];

    const ADMIN = 'admin';
    const ENQUETEUR = 'enqueteur';
    const PRODUCTEUR = 'producteur';

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
