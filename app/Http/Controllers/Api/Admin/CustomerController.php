<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all customers (users with role 'client').
     * 
     * GET /api/admin/customers
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'client');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                  ->orWhere('email', 'LIKE', $search);
            });
        }

        $customers = $query->paginate($request->integer('per_page', 15));

        return response()->json($customers);
    }

    /**
     * Show customer details and their booking stats.
     * 
     * GET /api/admin/customers/{id}
     */
    public function show($id)
    {
        $customer = User::where('role', 'client')->findOrFail($id);

        $bookingStats = [
            'total' => \App\Models\Booking::where('customer_id', $id)->count(),
            'completed' => \App\Models\Booking::where('customer_id', $id)->where('booking_status', 'completed')->count(),
            'total_spent' => \App\Models\Booking::where('customer_id', $id)->where('payment_status', 'paid')->sum('agreed_price'),
        ];

        return response()->json([
            'customer' => $customer,
            'stats' => $bookingStats,
        ]);
    }
}
