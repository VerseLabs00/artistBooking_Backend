<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ArtistAdvancePaidMail;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DuePaymentsController extends Controller
{
    /**
     * GET /api/admin/due-payments
     *
     * Returns all confirmed bookings where the admin has NOT yet sent
     * the advance payment to the artist (advance_payment_status = pending).
     * Groups/annotates by artist for easy display.
     */
    public function index(Request $request)
    {
        $query = Booking::with([
                'customer:id,name,email,avatar_url',
                'artistProfile:id,stage_name,full_name,avatar_url,email,user_id',
            ])
            // Only bookings where the customer has paid (advance received by platform)
            ->where('payment_status', 'paid')
            // Only bookings in an active/confirmed state
            ->whereIn('booking_status', ['confirmed', 'completed'])
            // Only ones where admin hasn't sent the artist's advance yet
            ->where('advance_payment_status', 'pending')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'LIKE', $search)
                  ->orWhereHas('artistProfile', fn($q2) =>
                      $q2->where('stage_name', 'LIKE', $search)
                         ->orWhere('full_name',  'LIKE', $search)
                  );
            });
        }

        $bookings = $query->get();

        $formatted = $bookings->map(fn($b) => $this->format($b));

        // Summary: total due grouped by artist
        $byArtist = $formatted->groupBy('artist.id')->map(function ($group) {
            $first = $group->first();
            return [
                'artist'       => $first['artist'],
                'booking_count' => $group->count(),
                'total_due'    => $group->sum('advance_amount_raw'),
            ];
        })->values();

        return response()->json([
            'bookings'  => $formatted,
            'by_artist' => $byArtist,
            'total_due' => $formatted->sum('advance_amount_raw'),
        ]);
    }

    /**
     * POST /api/admin/due-payments/{id}/mark-sent
     *
     * Admin marks the advance payment as sent for a specific booking.
     * Notifies the artist via in-app notification + email.
     */
    public function markSent(string $id)
    {
        $booking = Booking::with([
            'artistProfile:id,stage_name,full_name,email,user_id',
            'customer:id,name,email,avatar_url',
        ])->findOrFail($id);

        if ($booking->advance_payment_status === 'sent') {
            return response()->json(['message' => 'Advance already marked as sent.'], 409);
        }

        if ($booking->payment_status !== 'paid') {
            return response()->json(['message' => 'Customer payment not yet received.'], 422);
        }

        $booking->update(['advance_payment_status' => 'sent']);

        $artist     = $booking->artistProfile;
        $artistName = $artist?->stage_name ?? $artist?->full_name ?? 'Artist';
        $advance    = number_format((float) $booking->advance_amount, 2);

        // In-app notification to artist
        if ($artist?->user_id) {
            Notification::sendToUser(
                $artist->user_id,
                'booking',
                'Advance Payment Sent',
                "Your advance payment of LKR {$advance} for booking #{$booking->payhere_order_id} has been sent to your bank account.",
                '/bookingRequests'
            );
        }

        // Email to artist
        if ($artist?->email) {
            try {
                Mail::to($artist->email)->send(new ArtistAdvancePaidMail($booking));
            } catch (\Exception $e) {
                // Don't fail the request if email sending fails
                \Log::warning('ArtistAdvancePaidMail failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Advance payment marked as sent. Artist notified.',
            'booking' => $this->format($booking->fresh(['artistProfile', 'customer'])),
        ]);
    }

    private function format(Booking $b): array
    {
        $artist   = $b->artistProfile;
        $customer = $b->customer;

        return [
            'id'                     => $b->id,
            'booking_status'         => $b->booking_status,
            'payment_status'         => $b->payment_status,
            'advance_payment_status' => $b->advance_payment_status ?? 'pending',
            'event_date'             => $b->event_date?->toDateString(),
            'event_type'             => $b->event_type ?? '—',
            'venue'                  => $b->venue ?? '—',
            'customer_name'          => $b->customer_name ?? $customer?->name ?? '—',
            'advance_amount_raw'     => (float) $b->advance_amount,
            'advance_amount'         => 'LKR ' . number_format((float) $b->advance_amount, 0),
            'agreed_price'           => 'LKR ' . number_format((float) $b->agreed_price, 0),
            'platform_fee'           => 'LKR ' . number_format((float) $b->platform_fee, 0),
            'order_id'               => $b->payhere_order_id ?? $b->id,
            'created_at'             => $b->created_at?->toDateString(),
            'artist' => [
                'id'     => $artist?->id,
                'name'   => $artist?->stage_name ?? $artist?->full_name ?? '—',
                'email'  => $artist?->email ?? '—',
                'avatar' => $artist?->avatar_url ?? "https://i.pravatar.cc/40?u=a{$artist?->id}",
            ],
        ];
    }
}
