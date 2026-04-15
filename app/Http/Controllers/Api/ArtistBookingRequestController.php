<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ArtistBookingRequestController extends Controller
{
    /**
     * List bookings mapped to the authenticated artist's profile.
     * 
     * GET /api/artist/bookings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ensure user has an artist profile
        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        // Fetch bookings for this artist profile
        // Filtering out pending_payment might be necessary if you only want to show them fully paid ones,
        // but often artists want to see pending requests too.
        $bookings = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->with('customer:id,name,email') // Load customer basic info if relationship exists (needs to be defined in Booking model)
            ->orderByDesc('event_date')
            ->paginate(10);

        return response()->json([
            'data' => $bookings->map(fn ($b) => $this->formatForArtist($b)),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * Show a single booking request.
     * 
     * GET /api/artist/bookings/{id}
     */
    public function show(string $id, Request $request)
    {
        $user = $request->user();

        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $booking = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatForArtist($booking, true),
        ]);
    }

    /**
     * (Optional) Update Booking Status
     * Artists might need to accept, mark as completed, or reject.
     * 
     * PUT /api/artist/bookings/{id}/status
     */
    public function updateStatus(string $id, Request $request)
    {
        $user = $request->user();

        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:rejected,completed' // Artist can reject or complete, but cannot change from pending -> confirmed (that's via webhook)
        ]);

        $booking = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->findOrFail($id);
            
        // Basic logic: only allow certain transitions
        if ($booking->booking_status === 'cancelled' || $booking->booking_status === 'completed') {
            return response()->json(['message' => 'Cannot update a completed or cancelled booking.'], 400);
        }

        $booking->update(['booking_status' => $validated['status']]);

        return response()->json([
            'message' => 'Booking status updated successfully',
            'data'    => $this->formatForArtist($booking, true)
        ]);
    }

    /**
     * Helper to format booking data for the artist.
     */
    private function formatForArtist(Booking $b, bool $detailed = false): array
    {
        $data = [
            'id'              => $b->id,
            'booking_status'  => $b->booking_status,
            'payment_status'  => $b->payment_status,
            'event_date'      => $b->event_date?->toDateString(),
            'event_start_time'=> $b->event_start_time,
            'event_type'      => $b->event_type,
            'venue'           => $b->venue,
            'venue_lat'       => $b->venue_lat,
            'venue_lng'       => $b->venue_lng,
            'agreed_price'    => $b->agreed_price,
            'advance_amount'  => $b->advance_amount,
            'balance_due'     => (float) $b->agreed_price - (float) $b->advance_amount,
            'customer_name'   => $b->customer_name,
            'created_at'      => $b->created_at->toDateTimeString(),
        ];

        if ($detailed) {
            $data['event_duration_hours'] = $b->event_duration_hours;
            $data['special_notes']        = $b->special_notes;
            $data['customer_email']       = $b->customer_email;
            $data['customer_phone']       = $b->customer_phone;
            $data['order_id']             = $b->payhere_order_id;
        }

        return $data;
    }
}
