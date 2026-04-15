<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistProfile extends Model
{
    // ── Computed attributes ────────────────────────────────────────────────────

    /**
     * Average star rating across all approved reviews.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->approved()->avg('rating');
        return $avg ? round((float) $avg, 1) : null;
    }

    /**
     * Total number of approved reviews.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->approved()->count();
    }
    use HasUuids;

    protected $fillable = [
        'full_name',
        'stage_name',
        'location',
        'phone_number',
        'dob',
        'category',
        'email',
        'short_bio',
        'bio_1',
        'bio_2',
        'paragraph',
        'tags',
        'starting_price',
        'max_price',
        'avatar_url',
        'cover_url',
        'youtube_link',
        'facebook_link',
        'instagram_link',
        'spotify_link',
        'verification_status',
        'is_onboarded',
    ];

    protected $casts = [
        'is_onboarded' => 'boolean',
        'tags' => 'array',
        'dob' => 'date',
        'starting_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(ArtistReview::class);
    }
}
