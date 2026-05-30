<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use Illuminate\Http\Request;

class ArtistController extends Controller
{

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


    public function show($id)
    {
        $artist = ArtistProfile::with(['user', 'bankDetails', 'user.artistMedia'])->findOrFail($id);
        return response()->json($artist);
    }


    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $status = $request->status === 'approved' ? 'verified' : 'rejected';

        $artist = ArtistProfile::findOrFail($id);
        $artist->update([
            'verification_status' => $status,
            'is_onboarded' => ($status === 'verified')
        ]);

        return response()->json([
            'message' => "Artist profile has been " . ($status === 'verified' ? 'approved' : 'rejected'),
            'artist' => $artist
        ]);
    }


    public function toggleOnboard($id)
    {
        $artist = ArtistProfile::findOrFail($id);
        $artist->update(['is_onboarded' => !$artist->is_onboarded]);


        $artistName = $artist->stage_name ?: $artist->full_name;
        \App\Models\Notification::sendToAdmins(
            'artist',
            $artist->is_onboarded ? 'Artist Re-activated' : 'Artist Suspended',
            "{$artistName} account was " . ($artist->is_onboarded ? 're-activated' : 'suspended') . " due to disputes.",
            "/artists/{$id}"
        );


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


    public function destroy($id)
    {
        $artist = ArtistProfile::findOrFail($id);
        $artist->delete();

        return response()->json(['message' => 'Artist profile deleted successfully.']);
    }
}
