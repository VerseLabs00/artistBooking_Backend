<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated customer or artist.
     * 
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     * 
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->update(['read' => true]);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications for the authenticated user as read.
     * 
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read.'
        ]);
    }

    /**
     * Delete/dismiss a notification.
     * 
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification dismissed successfully.'
        ]);
    }
}
