<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    protected $fillable = [
        'student_id','company_id','offer_id','status','start_date','end_date', 'validated_by',
    'validated_at',
    'rejected_by',
    'rejected_at'
     
    ];
    const PENDING_ADMIN = 'pending_admin';
const VALIDATED = 'validated';
const REJECTED_BY_ADMIN = 'rejected_by_admin';

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function offer() {
        return $this->belongsTo(Offer::class);
    }

    public function documents() {
        return $this->hasMany(Document::class);
    }
}