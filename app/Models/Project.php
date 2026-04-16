<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
       'title', 'description', 'image', 'category', 'semester_id', 'shift_id'
    ];

    // Relaciones
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function students()
    {
        return $this->belongsToMany(\App\Models\Student::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }
    public function images()
    {
        return $this->hasMany(ProjectImage::class, 'project_id');
    }
}