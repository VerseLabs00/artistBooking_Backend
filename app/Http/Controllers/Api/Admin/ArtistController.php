<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use Illuminate\Http\Request;

class ArtistController extends Controller
{

    public function index(Request $request)
    {
        $query = ArtistProfile::with(['user:id,name,email', 'user.artistMedia']);

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
        
        if ($artist->bankDetails) {
            $artist->bankDetails->makeVisible('account_number');
        }

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

        // Send notification to the artist
        if ($artist->user_id) {
            $title = ($status === 'verified') ? 'Profile Approved' : 'Account Suspended';
            $message = ($status === 'verified') 
                ? "Congratulations! Your artist profile has been approved. You are now visible to potential clients."
                : "Your artist profile has been suspended by the platform administration. To discuss reactivation, please contact us at infoperforma.lk@gmail.com or +94 70 403 5236.";
            
            \App\Models\Notification::sendToUser(
                $artist->user_id,
                'status_change',
                $title,
                $message,
                '/account'
            );
        }

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
            NotificationController::send(
                $artist->user_id,
                $artist->is_onboarded ? 'Account Re-activated' : 'Account Suspended',
                "Your artist profile has been " . ($artist->is_onboarded ? 're-activated' : 'suspended') . " by the platform administration. To discuss reactivation, please contact us at infoperforma.lk@gmail.com or +94 70 403 5236.",
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
        
        // Delete the associated user which will cascade delete the profile and media
        if ($artist->user) {
            $artist->user->delete();
        } else {
            $artist->delete();
        }

        return response()->json(['message' => 'Artist profile and associated user account deleted successfully.']);
    }
}
