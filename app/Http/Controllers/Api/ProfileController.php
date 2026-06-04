<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesCloudinaryUploads;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use HandlesCloudinaryUploads;

    public function show(Request $request)
    {
        $user = $request->user();
        
        // If it's an artist, we might want to include their artist profile data
        if ($user->role === 'artist') {
            $user->load('artistProfile');
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // 5MB max
        ]);

        $user = $request->user();

        try {
            $folder = "users/{$user->id}/avatar";
            $upload = $this->uploadFile($request->file('file'), $folder);

            $user->update(['avatar_url' => $upload['secure_url']]);

            return response()->json([
                'message' => 'Avatar updated successfully',
                'url' => $upload['secure_url'],
                'user' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Avatar upload failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Avatar upload failed: ' . $e->getMessage()], 500);
        }
    }
}
