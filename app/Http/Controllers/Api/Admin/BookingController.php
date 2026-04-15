<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * List all bookings on the platform.
     * 
     * GET /api/admin/bookings
     */
    public function index(Request $request)
    {
        $query = Booking::with(['customer:id,name,email', 'artistProfile:id,stage_name,full_name']);

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

    /**
     * Show detailed view of any booking.
     * 
     * GET /api/admin/bookings/{id}
     */
    public function show($id)
    {
        $booking = Booking::with(['customer', 'artistProfile'])->findOrFail($id);
        return response()->json($booking);
    }

    /**
     * Manually update a booking status.
     * 
     * PUT /api/admin/bookings/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending_payment,confirmed,rejected,cancelled,completed',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update(['booking_status' => $request->status]);

        return response()->json([
            'message' => 'Booking status updated by admin.',
            'booking' => $booking
        ]);
    }
}
