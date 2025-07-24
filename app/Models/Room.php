<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
       use HasFactory;

    protected $fillable = ['name', 'capacity'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
