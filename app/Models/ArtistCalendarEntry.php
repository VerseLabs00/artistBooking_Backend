<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistCalendarEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'artist_profile_id',
        'entry_date',
        'title',
        'description',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function artistProfile()
    {
        return $this->belongsTo(ArtistProfile::class);
    }
}
