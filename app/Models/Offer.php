<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'location',
        'wilaya',     // 🔥 optional (good)
        'type',
        'deadline',
        'is_active'
    ];

    // 🔗 Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // 🔗 Skills (pivot table)
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    // 🔗 Applications
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // 🔗 Internships
    public function internships()
    {
        return $this->hasMany(Internship::class);
    }

    // 🔥 helper: expired ?
    public function isExpired()
    {
        return $this->deadline && now()->gt($this->deadline);
    }

    // 🔥 helper: active ?
    public function isAvailable()
{
    return $this->is_active &&
        (!$this->deadline || $this->deadline > now());
}
    public function scopeActive($query)
{
    return $query->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('deadline')
              ->orWhere('deadline', '>', now());
        });
}
public function savedOffers()
{
    return $this->belongsToMany(
        \App\Models\Student::class,
        'saved_offers',
        'offer_id',
        'student_id'
    );
}
}