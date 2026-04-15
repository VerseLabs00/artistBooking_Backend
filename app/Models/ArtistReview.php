<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'artist_profile_id',
        'customer_id',
        'reviewer_name',
        'reviewer_email',
        'rating',
        'title',
        'body',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function artistProfile()
    {
        return $this->belongsTo(ArtistProfile::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
