<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // Toggle favorite for a customer
    public function toggle(Request $request)
    {
        $request->validate([
            'artist_profile_id' => 'required|uuid|exists:artist_profiles,id',
        ]);

        $customer = $request->user();
        $artistProfileId = $request->input('artist_profile_id');

        $existing = Favorite::where('customer_id', $customer->id)
            ->where('artist_profile_id', $artistProfileId)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorited = false;
        } else {
            Favorite::create([
                'customer_id' => $customer->id,
                'artist_profile_id' => $artistProfileId,
            ]);
            $isFavorited = true;
        }

        return response()->json([
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to favorites' : 'Removed from favorites',
        ]);
    }

    // Get current customer's favorites
    public function index(Request $request)
    {
        $customer = $request->user();

        $favorites = Favorite::where('customer_id', $customer->id)
            ->with(['artistProfile' => function ($q) {
                $q->where('is_onboarded', true)
                  ->select('id', 'user_id', 'stage_name', 'full_name', 'category', 
                           'location', 'avatar_url', 'cover_url', 'full_price',
                           'advance', 'tags', 'short_bio', 'verification_status');
            }])
            ->latest()
            ->get();

        $artists = $favorites->map(function ($fav) {
            $profile = $fav->artistProfile;
            if (!$profile) return null;

            return [
                'id' => $profile->id,
                'name' => $profile->stage_name,
                'category' => $profile->category,
                'location' => $profile->location,
                'avatar_url' => $profile->avatar_url,
                'full_price' => $profile->full_price,
                'advance' => $profile->advance,
                'verification_status' => $profile->verification_status,
            ];
        })->filter()->values();

        return response()->json([
            'favorites' => $artists,
        ]);
    }

    // Get customers who favorited a specific artist (for artist view)
    public function customers(Request $request, $artistProfileId)
    {
        $artistProfile = ArtistProfile::where('id', $artistProfileId)->firstOrFail();

        // Only the artist who owns this profile can see who favorited them
        $user = $request->user();
        if ($user->role !== 'artist' || $artistProfile->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $favoritedBy = Favorite::where('artist_profile_id', $artistProfileId)
            ->with(['customer' => function ($q) {
                $q->select('id', 'name', 'email', 'avatar_url', 'created_at');
            }])
            ->latest()
            ->get();

        $customers = $favoritedBy->map(function ($fav) {
            $customer = $fav->customer;
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'avatar_url' => $customer->avatar_url,
                'favorited_at' => $fav->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'customers' => $customers,
            'total' => $customers->count(),
        ]);
    }
}
