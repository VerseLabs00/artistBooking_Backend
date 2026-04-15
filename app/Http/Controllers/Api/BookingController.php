<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistBankDetail;
use App\Models\ArtistProfile;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // ── PayHere configuration ──────────────────────────────────────────────────

    private function payhereConfig(): array
    {
        return [
            'merchant_id'     => (string) config('services.payhere.merchant_id', ''),
            'merchant_secret' => (string) config('services.payhere.merchant_secret', ''),
            'sandbox'         => config('services.payhere.sandbox', true),
            'checkout_url'    => config('services.payhere.sandbox', true)
                                    ? 'https://sandbox.payhere.lk/pay/checkout'
                                    : 'https://www.payhere.lk/pay/checkout',
        ];
    }

    /**
     * Generate PayHere payment hash.
     * Formula: strtoupper(MD5(merchant_id + order_id + amount + currency + strtoupper(MD5(merchant_secret))))
     */
    private function generateHash(string $merchantId, string $orderId, string $amount, string $currency, string $merchantSecret): string
    {
        $hashedSecret = strtoupper(md5($merchantSecret));
        return strtoupper(md5($merchantId . $orderId . $amount . $currency . $hashedSecret));
    }

    // ── Initiate Booking ───────────────────────────────────────────────────────

    /**
     * Create a booking and return PayHere payment data.
     * Customer takes this data and submits the PayHere checkout form on the frontend.
     *
     * POST /api/bookings/initiate
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'artist_profile_id'    => 'required|uuid|exists:artist_profiles,id',
            'event_date'           => 'required|date|after:today',
            'event_start_time'     => 'required|date_format:H:i',
            'event_duration_hours' => 'nullable|numeric|min:0.5|max:24',
            'event_type'           => 'required|string|max:100',
            'venue'                => 'required|string|max:255',
            'special_notes'        => 'nullable|string|max:1000',
        ]);

        $user   = $request->user();
        $artist = ArtistProfile::where('is_onboarded', true)
                               ->findOrFail($validated['artist_profile_id']);

        // Use artist's starting_price as agreed price
        $agreedPrice  = (float) $artist->starting_price;
        $advanceAmount = round($agreedPrice * 0.30, 2);  // 30% deposit

        // Unique order ID for PayHere
        $orderId = 'BK-' . strtoupper(Str::random(10));

        // Create booking in pending_payment state
        $booking = Booking::create([
            'customer_id'          => $user->id,
            'artist_profile_id'    => $artist->id,
            'event_date'           => $validated['event_date'],
            'event_start_time'     => $validated['event_start_time'],
            'event_duration_hours' => $validated['event_duration_hours'] ?? 2.0,
            'event_type'           => $validated['event_type'],
            'venue'                => $validated['venue'],
            'special_notes'        => $validated['special_notes'] ?? null,
            'agreed_price'         => $agreedPrice,
            'advance_amount'       => $advanceAmount,
            'booking_status'       => 'pending_payment',
            'payment_status'       => 'pending',
            'payhere_order_id'     => $orderId,
            'customer_name'        => $user->name,
            'customer_email'       => $user->email,
            'customer_phone'       => $user->phone ?? null,
        ]);

        $ph     = $this->payhereConfig();
        $amount = number_format($advanceAmount, 2, '.', '');
        $currency = 'LKR';

        $hash = $this->generateHash(
            $ph['merchant_id'],
            $orderId,
            $amount,
            $currency,
            $ph['merchant_secret']
        );

        return response()->json([
            'booking' => [
                'id'             => $booking->id,
                'order_id'       => $orderId,
                'agreed_price'   => $agreedPrice,
                'advance_amount' => $advanceAmount,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
            ],
            'payhere' => [
                'checkout_url'  => $ph['checkout_url'],
                'merchant_id'   => $ph['merchant_id'],
                'order_id'      => $orderId,
                'items'         => 'Booking: ' . ($artist->stage_name ?: $artist->full_name),
                'amount'        => $amount,
                'currency'      => $currency,
                'hash'          => $hash,
                'first_name'    => $user->name,
                'last_name'     => '',
                'email'         => $user->email,
                'phone'         => $user->phone ?? '',
                'address'       => $validated['venue'],
                'city'          => '',
                'country'       => 'Sri Lanka',
                'notify_url'    => route('bookings.notify'),
                'return_url'    => env('PAYHERE_RETURN_URL', 'http://localhost:5173/booking/success'),
                'cancel_url'    => env('PAYHERE_CANCEL_URL', 'http://localhost:5173/booking/cancel'),
            ],
        ], 201);
    }

    // ── PayHere Webhook ────────────────────────────────────────────────────────

    /**
     * PayHere sends a POST notification to this endpoint after payment.
     * This route is PUBLIC — must be excluded from CSRF and auth middleware.
     *
     * POST /api/bookings/notify
     */
    public function notify(Request $request)
    {
        $ph = $this->payhereConfig();

        $orderId       = $request->input('order_id');
        $statusCode    = $request->input('status_code');
        $payhereAmount = $request->input('payhere_amount');
        $payhereCurrency = $request->input('payhere_currency');
        $md5sig        = $request->input('md5sig');
        $paymentId     = $request->input('payment_id');

        // Find the booking
        $booking = Booking::where('payhere_order_id', $orderId)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Verify PayHere signature
        // Formula: strtoupper(MD5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + strtoupper(MD5(merchant_secret))))
        $localMd5sig = strtoupper(md5(
            $ph['merchant_id'] .
            $orderId .
            $payhereAmount .
            $payhereCurrency .
            $statusCode .
            strtoupper(md5($ph['merchant_secret']))
        ));

        if ($localMd5sig !== $md5sig) {
            \Log::warning('PayHere signature mismatch', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Store raw notify for debugging
        $booking->update([
            'payhere_payment_id'  => $paymentId,
            'payhere_status_code' => $statusCode,
            'payhere_raw_notify'  => json_encode($request->all()),
        ]);

        // PayHere status codes: 2 = success, 0 = pending, -1 = cancelled, -2 = failed, -3 = chargedback
        if ($statusCode == 2) {
            $booking->update([
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        } elseif (in_array($statusCode, [-1, -2, -3])) {
            $booking->update([
                'booking_status' => 'pending_payment',
                'payment_status' => 'failed',
            ]);
        }
        // status 0 = pending — do nothing, wait for final status

        return response()->json(['message' => 'Notification received']);
    }

    // ── Customer Booking List ──────────────────────────────────────────────────

    /**
     * List the authenticated customer's bookings.
     *
     * GET /api/bookings
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('customer_id', $request->user()->id)
            ->with('artistProfile:id,stage_name,full_name,avatar_url,category,location')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'data' => $bookings->map(fn ($b) => $this->formatBooking($b)),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    // ── Single Booking Detail ──────────────────────────────────────────────────

    /**
     * Get a single booking's full details.
     * If booking is confirmed, also return the artist's bank details for balance payment.
     *
     * GET /api/bookings/{id}
     */
    public function show(string $id, Request $request)
    {
        $booking = Booking::where('customer_id', $request->user()->id)
            ->with('artistProfile:id,stage_name,full_name,avatar_url,category,location,starting_price')
            ->findOrFail($id);

        $data = $this->formatBooking($booking, detailed: true);

        // Expose bank details only for confirmed bookings (balance payment info)
        if ($booking->booking_status === 'confirmed') {
            $bank = ArtistBankDetail::where('artist_profile_id', $booking->artist_profile_id)
                ->first();

            if ($bank) {
                $data['bank_details'] = [
                    'account_holder_name' => $bank->account_holder_name,
                    'bank_name'           => $bank->bank_name,
                    'branch'              => $bank->branch,
                    'account_number'      => $bank->getRawOriginal('account_number'),
                    'account_type'        => $bank->account_type,
                    'ifsc_code'           => $bank->ifsc_code,
                    'balance_due'         => (float) $booking->agreed_price - (float) $booking->advance_amount,
                ];
            }
        }

        return response()->json(['booking' => $data]);
    }

    // ── Cancel Booking ─────────────────────────────────────────────────────────

    /**
     * Cancel a booking (only if still pending_payment).
     *
     * POST /api/bookings/{id}/cancel
     */
    public function cancel(string $id, Request $request)
    {
        $booking = Booking::where('customer_id', $request->user()->id)
            ->findOrFail($id);

        if (!in_array($booking->booking_status, ['pending_payment'])) {
            return response()->json([
                'message' => 'Only pending bookings can be cancelled.',
            ], 422);
        }

        $booking->update(['booking_status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled successfully.']);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function formatBooking(Booking $b, bool $detailed = false): array
    {
        $data = [
            'id'              => $b->id,
            'order_id'        => $b->payhere_order_id,
            'booking_status'  => $b->booking_status,
            'payment_status'  => $b->payment_status,
            'event_date'      => $b->event_date?->toDateString(),
            'event_start_time'=> $b->event_start_time,
            'event_type'      => $b->event_type,
            'venue'           => $b->venue,
            'agreed_price'    => $b->agreed_price,
            'advance_amount'  => $b->advance_amount,
            'balance_due'     => (float) $b->agreed_price - (float) $b->advance_amount,
            'artist'          => $b->artistProfile ? [
                'id'         => $b->artistProfile->id,
                'stage_name' => $b->artistProfile->stage_name ?: $b->artistProfile->full_name,
                'avatar_url' => $b->artistProfile->avatar_url,
                'category'   => $b->artistProfile->category,
                'location'   => $b->artistProfile->location,
            ] : null,
            'created_at'      => $b->created_at->toDateTimeString(),
        ];

        if ($detailed) {
            $data['event_duration_hours'] = $b->event_duration_hours;
            $data['special_notes']        = $b->special_notes;
            $data['customer_name']        = $b->customer_name;
            $data['customer_email']       = $b->customer_email;
            $data['customer_phone']       = $b->customer_phone;
            $data['payhere_payment_id']   = $b->payhere_payment_id;
        }

        return $data;
    }
}
