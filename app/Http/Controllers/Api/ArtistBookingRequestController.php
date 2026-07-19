<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ArtistBookingRequestController extends Controller
{

    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $artistProfileId = $user->artistProfile->id;

        $stats = Booking::where('artist_profile_id', $artistProfileId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN booking_status = 'pending_payment' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN booking_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN payment_status = 'paid' THEN advance_amount ELSE 0 END) as total_earned
            ")
            ->first();

        $upcoming = Booking::where('artist_profile_id', $artistProfileId)
            ->with('customer:id,name,email,avatar_url')
            ->where('booking_status', 'confirmed')
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $recent = Booking::where('artist_profile_id', $artistProfileId)
            ->with('customer:id,name,email,avatar_url')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total'       => (int) $stats->total,
                'confirmed'   => (int) $stats->confirmed,
                'pending'     => (int) $stats->pending,
                'completed'   => (int) $stats->completed,
                'rejected'    => (int) $stats->rejected,
                'cancelled'   => (int) $stats->cancelled,
                'total_earned' => round((float) $stats->total_earned, 2),
            ],
            'upcoming_bookings' => $upcoming->map(fn($b) => $this->formatForArtist($b)),
            'recent_bookings'   => $recent->map(fn($b) => $this->formatForArtist($b)),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();


        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $bookings = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->with('customer:id,name,email,avatar_url')
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


    public function show(string $id, Request $request)
    {
        $user = $request->user();

        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $booking = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->with('customer:id,name,email,avatar_url')
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatForArtist($booking, true),
        ]);
    }


    public function updateStatus(string $id, Request $request)
    {
        $user = $request->user();

        if (!$user->artistProfile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:rejected,completed,confirmed'
        ]);

        $booking = Booking::where('artist_profile_id', $user->artistProfile->id)
            ->with('customer:id,name,email,avatar_url')
            ->findOrFail($id);


        if (in_array($booking->booking_status, ['cancelled', 'completed'])) {
            return response()->json(['message' => 'Cannot update a completed or cancelled booking.'], 400);
        }
        // Artist can confirm a request that is awaiting confirmation
        if ($booking->booking_status === 'awaiting_confirmation' && $validated['status'] === 'confirmed') {
            // Set to confirmed — customer will now see the Complete Payment button
        } elseif ($booking->booking_status === 'awaiting_confirmation' && $validated['status'] === 'rejected') {
            // Artist declines — no payment was taken
        } elseif ($booking->booking_status === 'awaiting_confirmation') {
            return response()->json(['message' => 'Can only confirm or reject a pending request.'], 422);
        }

        $booking->update(['booking_status' => $validated['status']]);

        $artistName = $user->artistProfile ? ($user->artistProfile->stage_name ?: $user->artistProfile->full_name) : 'The artist';
        
        $notificationTitle = 'Booking Status Updated';
        $notificationBody = "Your booking #{$booking->payhere_order_id} with {$artistName} has been updated to {$validated['status']}.";

        if ($validated['status'] === 'rejected') {
            $notificationTitle = 'Booking Rejected';
            $notificationBody = "Your booking #{$booking->payhere_order_id} was rejected by {$artistName}.";
        } elseif ($validated['status'] === 'confirmed') {
            $notificationTitle = 'Booking Accepted';
            $notificationBody = "Your booking #{$booking->payhere_order_id} was accepted by {$artistName}.";
        } elseif ($validated['status'] === 'completed') {
            $notificationTitle = 'Booking Completed';
            $notificationBody = "Your booking #{$booking->payhere_order_id} with {$artistName} has been completed.";
        }

        \App\Models\Notification::sendToUser(
            $booking->customer_id,
            'booking',
            $notificationTitle,
            $notificationBody,
            "/bookings"
        );

        return response()->json([
            'message' => 'Booking status updated successfully',
            'data'    => $this->formatForArtist($booking, true)
        ]);
    }


    private function formatForArtist(Booking $b, bool $detailed = false): array
    {
        $data = [
            'id'              => $b->id,
            'booking_status'  => $b->booking_status,
            'payment_status'  => $b->payment_status,
            'event_date'      => $b->event_date?->toDateString(),
            'event_start_time'=> $b->event_start_time,
            'event_end_time'  => $b->event_end_time,
            'event_type'      => $b->event_type,
            'venue'           => $b->venue,
            'venue_lat'       => $b->venue_lat,
            'venue_lng'       => $b->venue_lng,
            'agreed_price'    => $b->agreed_price,
            'advance_amount'          => $b->advance_amount,
            'advance_payment_status'  => $b->advance_payment_status ?? 'pending',
            'balance_due'     => (float) $b->agreed_price - (float) $b->advance_amount,
            'customer'        => $b->customer,
            'customer_name'   => $b->customer_name,
            'customer_avatar' => $b->customer?->avatar_url,
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


