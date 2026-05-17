<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'artist_profile_id',
        'event_date',
        'event_start_time',
        'event_duration_hours',
        'event_type',
        'venue',
        'special_notes',
        'agreed_price',
        'advance_amount',
        'booking_status',
        'payment_status',
        'payhere_order_id',
        'payhere_payment_id',
        'payhere_status_code',
        'payhere_raw_notify',
        'customer_name',
        'customer_email',
        'customer_phone',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'agreed_price'          => 'decimal:2',
        'advance_amount'        => 'decimal:2',
        'event_duration_hours'  => 'decimal:1',
    ];



    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function artistProfile()
    {
        return $this->belongsTo(ArtistProfile::class);
    }



    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('booking_status', 'pending_payment');
    }
}
