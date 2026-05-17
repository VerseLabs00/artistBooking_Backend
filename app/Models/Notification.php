<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'read',
        'link',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    protected $appends = [
        'time',
    ];

    /**
     * Get relative time for humans (e.g. "2 min ago", "1 hour ago").
     */
    public function getTimeAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    /**
     * Relationship to the recipient user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Send a notification to a specific user and send them an email.
     */
    public static function sendToUser($userId, string $type, string $title, string $message, ?string $link = null)
    {
        $notification = self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'read' => false,
            'link' => $link,
        ]);

        // Load the user to dispatch the email
        $user = $notification->user;
        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new \App\Mail\AdminNotificationMail($notification));
            } catch (\Exception $e) {
                Log::error("Failed to send email notification to {$user->email}: " . $e->getMessage());
            }
        }

        return $notification;
    }

    /**
     * Broadcast a notification and styled email to all system administrators.
     */
    public static function sendToAdmins(string $type, string $title, string $message, ?string $link = null)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            self::sendToUser($admin->id, $type, $title, $message, $link);
        }
    }
}
