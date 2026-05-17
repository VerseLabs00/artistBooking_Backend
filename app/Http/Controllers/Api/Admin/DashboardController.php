<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function stats()
    {
        $totalArtists = ArtistProfile::count();
        $verifiedArtists = ArtistProfile::where('verification_status', 'approved')->count();
        $pendingVerifications = ArtistProfile::where('verification_status', 'pending')->count();

        $totalCustomers = User::where('role', 'client')->count();

        $bookings = Booking::selectRaw('COUNT(*) as total, SUM(agreed_price) as revenue, SUM(advance_amount) as advance_revenue')
            ->first();

        $recentBookings = Booking::with('artistProfile:id,stage_name,full_name', 'customer:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'artists' => [
                    'total' => $totalArtists,
                    'verified' => $verifiedArtists,
                    'pending' => $pendingVerifications,
                ],
                'customers' => [
                    'total' => $totalCustomers,
                ],
                'bookings' => [
                    'total' => (int) $bookings->total,
                    'total_revenue' => round((float) $bookings->revenue, 2),
                    'advance_revenue' => round((float) $bookings->advance_revenue, 2),
                ],
            ],
            'recent_bookings' => $recentBookings->map(fn($b) => [
                'id' => $b->id,
                'customer_name' => $b->customer_name ?? $b->customer?->name,
                'artist_name' => $b->artistProfile?->stage_name ?? $b->artistProfile?->full_name,
                'event_date' => $b->event_date?->toDateString(),
                'agreed_price' => $b->agreed_price,
                'status' => $b->booking_status,
                'created_at' => $b->created_at->toDateTimeString(),
            ]),
        ]);
    }
}
