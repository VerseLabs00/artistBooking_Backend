<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ArtistBankDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'artist_profile_id',
        'account_holder_name',
        'bank_name',
        'branch',
        'account_number',
        'account_type',
        'ifsc_code',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];


    protected $hidden = ['account_number'];



    public function artistProfile()
    {
        return $this->belongsTo(ArtistProfile::class);
    }




    public function getMaskedAccountNumberAttribute(): string
    {
        return '****' . substr($this->getRawOriginal('account_number'), -4);
    }
}
