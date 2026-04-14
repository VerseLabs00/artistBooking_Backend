<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistMedia extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'media_type',
        'purpose',
        'url',
        'cloudinary_public_id',
        'is_external_link',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
