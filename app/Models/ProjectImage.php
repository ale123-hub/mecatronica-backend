<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    protected $table = 'project_images';

    protected $fillable = ['project_id', 'image_url'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}