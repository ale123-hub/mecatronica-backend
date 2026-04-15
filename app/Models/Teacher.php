<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    // Relación con proyectos (muchos a muchos)
    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}