<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = [
        'customer_id',
        'artist_profile_id',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function artistProfile()
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }
}
