<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistProfile extends Model
{



    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->approved()->avg('rating');
        return $avg ? round((float) $avg, 1) : null;
    }


    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->approved()->count();
    }
    use HasUuids;

    protected $fillable = [
        'user_id',
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
        'full_price',
        'advance',
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
        'full_price' => 'decimal:2',
        'advance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(ArtistReview::class);
    }

    public function bankDetails()
    {
        return $this->hasOne(ArtistBankDetail::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function calendarEntries()
    {
        return $this->hasMany(ArtistCalendarEntry::class);
    }

    public function scopeAvailableOnDate($query, string $date)
    {
        return $query
            ->whereDoesntHave('bookings', function ($q) use ($date) {
                $q->whereDate('event_date', $date)
                  ->whereIn('booking_status', ['confirmed', 'pending_payment', 'completed']);
            })
            ->whereDoesntHave('calendarEntries', function ($q) use ($date) {
                $q->whereDate('entry_date', $date);
            });
    }
}
