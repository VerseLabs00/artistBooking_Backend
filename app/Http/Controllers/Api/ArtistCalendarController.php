<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistCalendarEntry;
use App\Models\ArtistProfile;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ArtistCalendarController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->artistProfile;

        if (!$profile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $month = $request->input('month', now()->format('Y-m'));

        return response()->json([
            'entries' => $this->buildCalendarEntries($profile, $month, detailed: true),
        ]);
    }

    public function store(Request $request)
    {
        $profile = $request->user()->artistProfile;

        if (!$profile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $validated = $request->validate([
            'date'        => 'required|date|after_or_equal:today',
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
        ]);

        $entry = ArtistCalendarEntry::create([
            'artist_profile_id' => $profile->id,
            'entry_date'        => $validated['date'],
            'title'             => $validated['title'],
            'description'       => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Calendar entry added successfully',
            'entry'   => $this->formatManualEntry($entry),
        ], 201);
    }

    public function destroy(string $id, Request $request)
    {
        $profile = $request->user()->artistProfile;

        if (!$profile) {
            return response()->json(['message' => 'Artist profile not found'], 404);
        }

        $entry = ArtistCalendarEntry::where('artist_profile_id', $profile->id)->findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Calendar entry removed successfully']);
    }

    public function publicCalendar(string $artistId, Request $request)
    {
        $artist = ArtistProfile::where('is_onboarded', true)->findOrFail($artistId);
        $month = $request->input('month', now()->format('Y-m'));

        return response()->json([
            'entries' => $this->buildCalendarEntries($artist, $month, detailed: false),
        ]);
    }

    private function buildCalendarEntries(ArtistProfile $profile, string $month, bool $detailed): array
    {
        [$start, $end] = $this->monthRange($month);

        $bookings = Booking::where('artist_profile_id', $profile->id)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('booking_status', ['confirmed', 'pending_payment', 'completed'])
            ->orderBy('event_date')
            ->get();

        $manual = ArtistCalendarEntry::where('artist_profile_id', $profile->id)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('entry_date')
            ->get();

        $entries = [];

        foreach ($bookings as $booking) {
            $entries[] = $this->formatBookingEntry($booking, $detailed);
        }

        foreach ($manual as $entry) {
            $entries[] = $this->formatManualEntry($entry);
        }

        usort($entries, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return $entries;
    }

    private function formatBookingEntry(Booking $booking, bool $detailed): array
    {
        $statusLabel = match ($booking->booking_status) {
            'confirmed'       => 'Confirmed booking',
            'pending_payment' => 'Pending booking',
            'completed'       => 'Completed booking',
            default           => 'Booking',
        };

        $data = [
            'id'          => 'booking-' . $booking->id,
            'date'        => $booking->event_date->toDateString(),
            'title'       => $detailed
                ? "{$statusLabel}: {$booking->event_type}"
                : 'Booked',
            'description' => $detailed
                ? trim("{$booking->venue}" . ($booking->event_start_time ? " at {$booking->event_start_time}" : ''))
                : null,
            'source'      => 'booking',
            'status'      => $booking->booking_status,
            'editable'    => false,
        ];

        if ($detailed) {
            $data['event_type'] = $booking->event_type;
            $data['venue'] = $booking->venue;
            $data['customer_name'] = $booking->customer_name;
        }

        return $data;
    }

    private function formatManualEntry(ArtistCalendarEntry $entry): array
    {
        return [
            'id'          => $entry->id,
            'date'        => $entry->entry_date->toDateString(),
            'title'       => $entry->title,
            'description' => $entry->description,
            'source'      => 'manual',
            'editable'    => true,
        ];
    }

    private function monthRange(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }
}
