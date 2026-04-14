<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistProfile extends Model
{
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
        'verification_status',
        'is_onboarded',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
