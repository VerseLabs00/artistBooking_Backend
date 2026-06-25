<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistMedia;

use App\Traits\HandlesCloudinaryUploads;
use Illuminate\Http\Request;

class ArtistProfileController extends Controller
{
    use HandlesCloudinaryUploads;


    public function show(Request $request)
    {
        $user    = $request->user();
        $profile = $user->artistProfile()->with('user.artistMedia')->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }


        $reviewsQuery = $profile->reviews()->approved();

        $totalReviews = (clone $reviewsQuery)->count();
        $avgRating    = (clone $reviewsQuery)->avg('rating');

        $distribution = (clone $reviewsQuery)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $recentReviews = (clone $reviewsQuery)
            ->with('customer:id,name,avatar_url')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'rating'          => $r->rating,
                'title'           => $r->title,
                'body'            => $r->body,
                'reviewer_name'   => $r->reviewer_name ?? ($r->customer?->name ?? 'Anonymous'),
                'reviewer_avatar' => $r->customer?->avatar_url,
                'created_at'      => $r->created_at->toDateString(),
            ]);

        return response()->json([
            'profile' => $profile,
            'media'   => $user->artistMedia,
            'rating'  => [
                'average'        => $avgRating ? round((float) $avgRating, 1) : null,
                'total'          => $totalReviews,
                'distribution'   => [
                    5 => (int) ($distribution[5] ?? 0),
                    4 => (int) ($distribution[4] ?? 0),
                    3 => (int) ($distribution[3] ?? 0),
                    2 => (int) ($distribution[2] ?? 0),
                    1 => (int) ($distribution[1] ?? 0),
                ],
                'recent_reviews' => $recentReviews,
            ],
        ]);
    }


    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->artistProfile;

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'stage_name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'category' => 'sometimes|string',
            'email' => 'sometimes|email|max:255',
            'tags' => 'sometimes|array',
            'short_bio' => 'sometimes|string',

            'bio_1' => 'sometimes|string',
            'bio_2' => 'sometimes|string',
            'paragraph' => 'sometimes|string',

            'full_price' => 'sometimes|numeric|min:0',
            'advance' => 'sometimes|numeric|min:0',

            'youtube_link' => 'sometimes|nullable|url',
            'facebook_link' => 'sometimes|nullable|url',
            'instagram_link' => 'sometimes|nullable|url',
            'spotify_link' => 'sometimes|nullable|url',
        ]);

        $profile->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh()
        ]);
    }


    public function updateMedia(Request $request)
    {
        $request->validate([
            'type' => 'required|in:avatar,cover',
            'file' => 'required|image|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->artistProfile;
        $type = $request->type;

        try {
            $folder = "artists/{$user->id}/profile";
            $upload = $this->uploadFile($request->file('file'), $folder);

            $column = ($type === 'avatar') ? 'avatar_url' : 'cover_url';
            $profile->update([$column => $upload['secure_url']]);

            return response()->json([
                'message' => ucfirst($type) . ' updated successfully',
                'url' => $upload['secure_url']
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Media upload failed: ' . $e->getMessage()], 500);
        }
    }


    public function addMedia(Request $request)
    {
        $request->validate([
            'purpose' => 'required|in:talent_media,performance',
            'file' => 'nullable|file|mimes:mp4,mov,avi,jpg,jpeg,png|max:51200',
            'external_link' => 'nullable|url',
        ]);

        $user = $request->user();

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';

                $upload = $this->uploadFile($file, "artists/{$user->id}/showcase");

                $media = ArtistMedia::create([
                    'user_id' => $user->id,
                    'media_type' => $type,
                    'purpose' => $request->purpose,
                    'url' => $upload['secure_url'],
                    'cloudinary_public_id' => $upload['public_id'],
                    'is_external_link' => false,
                ]);
            } else {
                $media = ArtistMedia::create([
                    'user_id' => $user->id,
                    'media_type' => 'video',
                    'purpose' => $request->purpose,
                    'url' => $request->external_link,
                    'is_external_link' => true,
                ]);
            }

            return response()->json(['message' => 'Media added successfully', 'media' => $media]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add media: ' . $e->getMessage()], 500);
        }
    }


    public function deleteMedia($id, Request $request)
    {
        $media = $request->user()->artistMedia()->findOrFail($id);

        if (!$media->is_external_link && $media->cloudinary_public_id) {
            $cloudinary = $this->getCloudinaryInstance();
            $resourceType = ($media->media_type === 'video') ? 'video' : 'image';
            $cloudinary->uploadApi()->destroy($media->cloudinary_public_id, ['resource_type' => $resourceType]);
        }

        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }


    public function syncExternalLinks(Request $request)
    {
        $request->validate([
            'links'         => 'array',
            'links.*.url'   => 'nullable|url',
            'links.*.title' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $user->artistMedia()->where('is_external_link', true)->where('purpose', 'talent_media')->delete();

        foreach ($request->links as $item) {
            $url = is_array($item) ? ($item['url'] ?? null) : $item;
            $title = is_array($item) ? ($item['title'] ?? null) : null;
            if ($url) {
                ArtistMedia::create([
                    'user_id'          => $user->id,
                    'media_type'       => 'video',
                    'purpose'          => 'talent_media',
                    'url'              => $url,
                    'title'            => $title,
                    'is_external_link' => true,
                ]);
            }
        }

        return response()->json(['message' => 'Media links synced successfully']);
    }
}
