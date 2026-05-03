<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name'];

   public function students()
{
    return $this->belongsToMany(Student::class, 'student_skill');
}
    public function offers()
    {
        return $this->belongsToMany(Offer::class);
    }
}