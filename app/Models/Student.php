<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'university',
        'wilaya',
        // Digital CV
        'cv_summary',
        'cv_languages',
        // Shared profile + Digital CV fields
        'education',
        'experience',
        'projects',
        // Other profile fields
        'github_link',
        'bio',
        'phone',
        'linkedin',
        'portfolio',
    ];

    // ── Relations (unchanged) ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'student_skill');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function internships()
    {
        return $this->hasMany(Internship::class);
    }

    public function savedOffers()
    {
        return $this->belongsToMany(Offer::class, 'saved_offers')->withTimestamps();
    }

    /**
     * Digital CV is considered filled when summary + at least
     * education OR experience exists.
     */
    public function hasDigitalCv(): bool
    {
        return !empty($this->cv_summary)
            && (!empty($this->education) || !empty($this->experience));
    }
}