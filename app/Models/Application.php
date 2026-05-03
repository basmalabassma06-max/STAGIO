<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Application extends Model
{
    protected $fillable = [
        'student_id',
        'offer_id',
        'status',
        'cv',
        'motivation'
    ];
    protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];

const PENDING = 'pending';
const ACCEPTED_BY_COMPANY = 'accepted_by_company';
const REJECTED_BY_COMPANY = 'rejected_by_company';
const REJECTED_BY_ADMIN = 'rejected_by_admin';
const WITHDRAWN = 'withdrawn';
const PENDING_ADMIN = 'pending_admin';
const VALIDATED = 'validated';
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function internship()
    {
        return $this->hasOne(Internship::class);
    }

}