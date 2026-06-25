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


    private function generateHash(string $merchantId, string $orderId, string $amount, string $currency, string $merchantSecret): string
    {
        $hashedSecret = strtoupper(md5($merchantSecret));
        return strtoupper(md5($merchantId . $orderId . $amount . $currency . $hashedSecret));
    }




    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'artist_profile_id'    => 'required|uuid|exists:artist_profiles,id',
            'event_date'           => 'required|date|after_or_equal:today',
            'event_start_time'     => 'required|date_format:H:i',
            'event_duration_hours' => 'nullable|numeric|min:0.5|max:24',
            'event_type'           => 'required|string|max:100',
            'venue'                => 'required|string|max:255',
            'customer_phone'       => 'required|string|max:20',
            'venue_lat'            => 'nullable|numeric|between:-90,90',
            'venue_lng'            => 'nullable|numeric|between:-180,180',
            'special_notes'        => 'nullable|string|max:1000',
        ]);

        $user   = $request->user();
        $artist = ArtistProfile::where('is_onboarded', true)
                               ->findOrFail($validated['artist_profile_id']);

        $agreedPrice = (float) $artist->full_price;
        $advanceAmount = (float) $artist->advance;
        
        // Get platform commission rate from settings
        $commissionRate = (float) \App\Models\Setting::getValue('commission_rate', 15);
        $platformFee = round($advanceAmount * ($commissionRate / 100), 2);
        $totalPayment = $advanceAmount + $platformFee;

        $orderId = 'BK-' . strtoupper(Str::random(10));

        $booking = Booking::create([
            'customer_id'          => $user->id,
            'artist_profile_id'    => $artist->id,
            'event_date'           => $validated['event_date'],
            'event_start_time'     => $validated['event_start_time'],
            'event_duration_hours' => $validated['event_duration_hours'] ?? 2.0,
            'event_type'           => $validated['event_type'],
            'venue'                => $validated['venue'],
            'venue_lat'            => $validated['venue_lat'] ?? null,
            'venue_lng'            => $validated['venue_lng'] ?? null,
            'customer_phone'       => $validated['customer_phone'],
            'special_notes'        => $validated['special_notes'] ?? null,
            'agreed_price'         => $agreedPrice,
            'advance_amount'       => $advanceAmount,
            'platform_fee'         => $platformFee,
            'total_payment'         => $totalPayment,
            'commission_rate'      => $commissionRate,
            'booking_status'       => 'pending_payment',
            'payment_status'       => 'pending',
            'payhere_order_id'     => $orderId,
            'customer_name'        => $user->name,
            'customer_email'       => $user->email,
        ]);

        $ph     = $this->payhereConfig();
        $amount = number_format($totalPayment, 2, '.', '');
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
                'platform_fee'   => $platformFee,
                'total_payment'  => $totalPayment,
                'commission_rate'=> $commissionRate,
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




    public function notify(Request $request)
    {
        $ph = $this->payhereConfig();

        $orderId       = $request->input('order_id');
        $statusCode    = $request->input('status_code');
        $payhereAmount = $request->input('payhere_amount');
        $payhereCurrency = $request->input('payhere_currency');
        $md5sig        = $request->input('md5sig');
        $paymentId     = $request->input('payment_id');

        $booking = Booking::where('payhere_order_id', $orderId)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

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


        $booking->update([
            'payhere_payment_id'  => $paymentId,
            'payhere_status_code' => $statusCode,
            'payhere_raw_notify'  => json_encode($request->all()),
        ]);


        if ($statusCode == 2) {
            $booking->update([
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
            ]);


            $customerName = $booking->customer_name ?: ($booking->customer ? $booking->customer->name : 'Customer');
            $artistProfile = $booking->artistProfile;
            $artistName = $artistProfile ? ($artistProfile->stage_name ?: $artistProfile->full_name) : 'Artist';
            \App\Models\Notification::sendToAdmins(
                'booking',
                'New Booking Confirmed',
                "{$customerName} confirmed booking #{$booking->payhere_order_id} with {$artistName}.",
                "/bookings"
            );
        } elseif (in_array($statusCode, [-1, -2, -3])) {
            $booking->update([
                'booking_status' => 'pending_payment',
                'payment_status' => 'failed',
            ]);
        }


        return response()->json(['message' => 'Notification received']);
    }




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




    public function show(string $id, Request $request)
    {
        $booking = Booking::where('customer_id', $request->user()->id)
            ->with('artistProfile:id,stage_name,full_name,avatar_url,category,location,full_price,advance')
            ->findOrFail($id);

        $data = $this->formatBooking($booking, detailed: true);


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


        $customerName = $booking->customer_name ?: ($booking->customer ? $booking->customer->name : 'Customer');
        \App\Models\Notification::sendToAdmins(
            'booking',
            'Booking Cancelled',
            "Booking #{$booking->payhere_order_id} was cancelled by {$customerName}.",
            "/bookings"
        );

        return response()->json(['message' => 'Booking cancelled successfully.']);
    }



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
            'venue_lat'       => $b->venue_lat,
            'venue_lng'       => $b->venue_lng,
            'agreed_price'    => $b->agreed_price,
            'advance_amount'  => $b->advance_amount,
            'platform_fee'    => $b->platform_fee,
            'total_payment'   => $b->total_payment,
            'commission_rate' => $b->commission_rate,
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
