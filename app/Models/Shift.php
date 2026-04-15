<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relación inversa con proyectos
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}