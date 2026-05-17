<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * List all customers with optional search and status filter.
     *
     * GET /api/admin/customers
     * Query params: search, status (active|banned), per_page
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'client');

        if ($request->filled('status')) {
            match ($request->status) {
                'banned' => $query->where('is_banned', true),
                'active' => $query->where('is_banned', false),
                default  => null,
            };
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                  ->orWhere('email', 'LIKE', $search);
            });
        }

        return response()->json($query->paginate($request->integer('per_page', 15)));
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
            'total'       => \App\Models\Booking::where('customer_id', $id)->count(),
            'completed'   => \App\Models\Booking::where('customer_id', $id)->where('booking_status', 'completed')->count(),
            'total_spent' => \App\Models\Booking::where('customer_id', $id)->where('payment_status', 'paid')->sum('agreed_price'),
        ];

        return response()->json([
            'customer' => $customer,
            'stats'    => $bookingStats,
        ]);
    }

    /**
     * Ban a customer account.
     *
     * PUT /api/admin/customers/{id}/ban
     */
    public function ban($id)
    {
        $customer = User::where('role', 'client')->findOrFail($id);
        $customer->update(['is_banned' => true, 'banned_at' => now()]);

        // Trigger notification and email to administrators
        \App\Models\Notification::sendToAdmins(
            'customer',
            'Customer Banned',
            "{$customer->name} was banned for abusive behaviour.",
            "/customers/{$id}"
        );

        // Notify the customer directly of their account suspension
        \App\Models\Notification::sendToUser(
            $id,
            'customer',
            'Account Suspended',
            'Your account has been suspended by the administrator due to platform policy violations.',
            '/'
        );

        return response()->json([
            'message'  => 'Customer has been banned.',
            'customer' => $customer,
        ]);
    }

    /**
     * Unban a customer account.
     *
     * PUT /api/admin/customers/{id}/unban
     */
    public function unban($id)
    {
        $customer = User::where('role', 'client')->findOrFail($id);
        $customer->update(['is_banned' => false, 'banned_at' => null]);

        return response()->json([
            'message'  => 'Customer has been unbanned.',
            'customer' => $customer,
        ]);
    }

    /**
     * Delete a customer account.
     *
     * DELETE /api/admin/customers/{id}
     */
    public function destroy($id)
    {
        $customer = User::where('role', 'client')->findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Customer account deleted successfully.']);
    }
}