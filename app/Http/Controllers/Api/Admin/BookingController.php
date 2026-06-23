<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{

    public function index(Request $request)
    {
        $query = Booking::with([
            'customer:id,name,email,avatar_url', 
            'artistProfile:id,stage_name,full_name,avatar_url'
        ]);

        if ($request->filled('status')) {
            $query->where('booking_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('payhere_order_id', 'LIKE', $search)
                  ->orWhere('customer_name', 'LIKE', $search)
                  ->orWhere('customer_email', 'LIKE', $search);
            });
        }

        $bookings = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return response()->json($bookings);
    }


    public function show($id)
    {
        $booking = Booking::with(['customer', 'artistProfile'])->findOrFail($id);
        return response()->json($booking);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending_payment,confirmed,rejected,cancelled,completed',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['booking_status' => $request->status]);


        \App\Models\Notification::sendToUser(
            $booking->customer_id,
            'booking',
            'Booking Status Updated',
            "Your booking #{$booking->payhere_order_id} status has been updated to " . ucfirst($request->status) . " by the platform.",
            "/bookings"
        );


        if ($booking->artistProfile && $booking->artistProfile->user_id) {
            \App\Models\Notification::sendToUser(
                $booking->artistProfile->user_id,
                'booking',
                'Booking Status Updated',
                "Your booking #{$booking->payhere_order_id} with client {$booking->customer_name} has been updated to " . ucfirst($request->status) . " by the platform.",
                "/bookings"
            );
        }

        return response()->json([
            'message' => 'Booking status updated by admin.',
            'booking' => $booking
        ]);
    }
}
