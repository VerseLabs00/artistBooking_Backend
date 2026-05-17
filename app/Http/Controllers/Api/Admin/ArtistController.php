<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    /**
     * List all artists with pagination and filters.
     * 
     * GET /api/admin/artists
     */
    public function index(Request $request)
    {
        $query = ArtistProfile::with('user:id,name,email');

        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', $search)
                  ->orWhere('stage_name', 'LIKE', $search)
                  ->orWhere('email', 'LIKE', $search);
            });
        }

        $artists = $query->paginate($request->integer('per_page', 15));

        return response()->json($artists);
    }

    /**
     * Show details of a specific artist.
     * 
     * GET /api/admin/artists/{id}
     */
    public function show($id)
    {
        $artist = ArtistProfile::with(['user', 'bankDetails', 'user.artistMedia'])->findOrFail($id);
        return response()->json($artist);
    }

    /**
     * Verify (Approve/Reject) an artist profile.
     * 
     * PUT /api/admin/artists/{id}/verify
     */
    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $artist = ArtistProfile::findOrFail($id);
        $artist->update([
            'verification_status' => $request->status,
            'is_onboarded' => ($request->status === 'approved')
        ]);

        return response()->json([
            'message' => "Artist profile has been " . $request->status,
            'artist' => $artist
        ]);
    }

    /**
     * Toggle the onboarded visibility status of an artist.
     * 
     * PUT /api/admin/artists/{id}/toggle-onboard
     */
    public function toggleOnboard($id)
    {
        $artist = ArtistProfile::findOrFail($id);
        $artist->update(['is_onboarded' => !$artist->is_onboarded]);

        // Trigger notification and email to administrators
        $artistName = $artist->stage_name ?: $artist->full_name;
        \App\Models\Notification::sendToAdmins(
            'artist',
            $artist->is_onboarded ? 'Artist Re-activated' : 'Artist Suspended',
            "{$artistName} account was " . ($artist->is_onboarded ? 're-activated' : 'suspended') . " due to disputes.",
            "/artists/{$id}"
        );

        // Notify the artist directly of their profile status change
        if ($artist->user_id) {
            \App\Models\Notification::sendToUser(
                $artist->user_id,
                'artist',
                $artist->is_onboarded ? 'Account Re-activated' : 'Account Suspended',
                "Your artist profile has been " . ($artist->is_onboarded ? 're-activated' : 'suspended') . " by the platform administration.",
                '/profile'
            );
        }

        return response()->json([
            'message' => "Artist visibility toggled.",
            'is_onboarded' => $artist->is_onboarded
        ]);
    }

    /**
     * Delete an artist profile and potentially the user account.
     * 
     * DELETE /api/admin/artists/{id}
     */
    public function destroy($id)
    {
        $artist = ArtistProfile::findOrFail($id);
        $artist->delete();

        return response()->json(['message' => 'Artist profile deleted successfully.']);
    }
}
