<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
const TYPES = ['convention','certificate'];
    protected $fillable = [
    'internship_id','file_path','type','generated_at'
];

public function internship() {
    return $this->belongsTo(Internship::class);
}
}
