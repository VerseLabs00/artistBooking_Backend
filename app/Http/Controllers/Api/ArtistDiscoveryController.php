<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\ArtistReview;
use Illuminate\Http\Request;

class ArtistDiscoveryController extends Controller
{

    public function categories()
    {
        $categories = ArtistProfile::query()
            ->where('is_onboarded', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'categories' => $categories->values(),
        ]);
    }


    public function artists(Request $request)
    {
        $request->validate([
            'category' => 'sometimes|string|max:100',
            'search'   => 'sometimes|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = ArtistProfile::query()
            ->where('is_onboarded', true)
            ->select([
                'id', 'user_id', 'stage_name', 'full_name',
                'category', 'location', 'avatar_url', 'cover_url',
                'starting_price', 'max_price', 'tags', 'short_bio',
            ]);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $keyword = '%' . $request->search . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('stage_name', 'LIKE', $keyword)
                  ->orWhere('full_name', 'LIKE', $keyword)
                  ->orWhere('short_bio', 'LIKE', $keyword);
            });
        }

        $perPage = $request->integer('per_page', 12);
        $artists  = $query->paginate($perPage);

        return response()->json([
            'data' => $artists->map(fn ($a) => $this->formatArtist($a)),
            'meta' => [
                'current_page' => $artists->currentPage(),
                'last_page'    => $artists->lastPage(),
                'per_page'     => $artists->perPage(),
                'total'        => $artists->total(),
            ],
        ]);
    }


    public function nearYou(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $location = $request->location;
        $perPage  = $request->integer('per_page', 12);

        $artists = ArtistProfile::query()
            ->where('is_onboarded', true)
            ->where('location', 'LIKE', '%' . $location . '%')
            ->select([
                'id', 'user_id', 'stage_name', 'full_name',
                'category', 'location', 'avatar_url', 'cover_url',
                'starting_price', 'max_price', 'tags', 'short_bio',
            ])
            ->paginate($perPage);

        return response()->json([
            'location_query' => $location,
            'data' => $artists->map(fn ($a) => $this->formatArtist($a)),
            'meta' => [
                'current_page' => $artists->currentPage(),
                'last_page'    => $artists->lastPage(),
                'per_page'     => $artists->perPage(),
                'total'        => $artists->total(),
            ],
        ]);
    }


    public function show(string $id)
    {
        $artist = ArtistProfile::with([

                'user.artistMedia' => fn ($q) => $q->where('purpose', 'talent_media'),
            ])
            ->where('is_onboarded', true)
            ->findOrFail($id);

        $gallery = $artist->user->artistMedia()
            ->where('purpose', 'performance')
            ->get(['id', 'media_type', 'url', 'is_external_link']);


        $reviewsQuery = $artist->reviews()->approved();

        $totalReviews = (clone $reviewsQuery)->count();
        $avgRating    = (clone $reviewsQuery)->avg('rating');


        $distribution = (clone $reviewsQuery)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');


        $recentReviews = (clone $reviewsQuery)
            ->with('customer:id,name')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'rating'        => $r->rating,
                'title'         => $r->title,
                'body'          => $r->body,
                'reviewer_name' => $r->reviewer_name ?? ($r->customer?->name ?? 'Anonymous'),
                'created_at'    => $r->created_at->toDateString(),
            ]);

        return response()->json([
            'artist' => [
                'id'               => $artist->id,
                'stage_name'       => $artist->stage_name ?: $artist->full_name,
                'full_name'        => $artist->full_name,
                'category'         => $artist->category,
                'location'         => $artist->location,
                'avatar_url'       => $artist->avatar_url,
                'cover_url'        => $artist->cover_url,
                'short_bio'        => $artist->short_bio,
                'bio_1'            => $artist->bio_1,
                'bio_2'            => $artist->bio_2,
                'paragraph'        => $artist->paragraph,
                'tags'             => $artist->tags ?? [],
                'starting_price'   => $artist->starting_price,
                'max_price'        => $artist->max_price,
                'youtube_link'     => $artist->youtube_link,
                'facebook_link'    => $artist->facebook_link,
                'instagram_link'   => $artist->instagram_link,
                'spotify_link'     => $artist->spotify_link,
                'verification_status' => $artist->verification_status,
                'rating' => [
                    'average'      => $avgRating ? round((float) $avgRating, 1) : null,
                    'total'        => $totalReviews,
                    'distribution' => [
                        5 => (int) ($distribution[5] ?? 0),
                        4 => (int) ($distribution[4] ?? 0),
                        3 => (int) ($distribution[3] ?? 0),
                        2 => (int) ($distribution[2] ?? 0),
                        1 => (int) ($distribution[1] ?? 0),
                    ],
                    'recent_reviews' => $recentReviews,
                ],
                'media'   => $artist->user->artistMedia->map(fn ($m) => [
                    'id'               => $m->id,
                    'media_type'       => $m->media_type,
                    'url'              => $m->url,
                    'is_external_link' => $m->is_external_link,
                ]),
                'gallery' => $gallery->map(fn ($m) => [
                    'id'               => $m->id,
                    'media_type'       => $m->media_type,
                    'url'              => $m->url,
                    'is_external_link' => $m->is_external_link,
                ]),
            ],
        ]);
    }


    public function reviews(string $id, Request $request)
    {

        $artist = ArtistProfile::where('is_onboarded', true)->findOrFail($id);

        $perPage = $request->integer('per_page', 10);

        $reviews = $artist->reviews()
            ->approved()
            ->with('customer:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);


        $distribution = $artist->reviews()
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $avg = $artist->reviews()->approved()->avg('rating');

        return response()->json([
            'rating_summary' => [
                'average'      => $avg ? round((float) $avg, 1) : null,
                'total'        => $reviews->total(),
                'distribution' => [
                    5 => (int) ($distribution[5] ?? 0),
                    4 => (int) ($distribution[4] ?? 0),
                    3 => (int) ($distribution[3] ?? 0),
                    2 => (int) ($distribution[2] ?? 0),
                    1 => (int) ($distribution[1] ?? 0),
                ],
            ],
            'data' => $reviews->map(fn ($r) => [
                'id'            => $r->id,
                'rating'        => $r->rating,
                'title'         => $r->title,
                'body'          => $r->body,
                'reviewer_name' => $r->reviewer_name ?? ($r->customer?->name ?? 'Anonymous'),
                'created_at'    => $r->created_at->toDateString(),
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }


    public function submitReview(string $id, Request $request)
    {

        $artist = ArtistProfile::where('is_onboarded', true)->findOrFail($id);

        $request->validate([
            'rating'         => 'required|integer|min:1|max:5',
            'title'          => 'nullable|string|max:150',
            'body'           => 'nullable|string|max:2000',

            'reviewer_name'  => 'nullable|string|max:100',
            'reviewer_email' => 'nullable|email|max:255',
        ]);

        $customerId = null;
        $reviewerName  = $request->reviewer_name;
        $reviewerEmail = $request->reviewer_email;

        if ($request->bearerToken()) {
            $user = $request->user('sanctum');
            if ($user) {
                $customerId    = $user->id;
                $reviewerName  = $reviewerName ?? $user->name;
                $reviewerEmail = $reviewerEmail ?? $user->email;

                $exists = ArtistReview::where('artist_profile_id', $artist->id)
                    ->where('customer_id', $customerId)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'You have already reviewed this artist.',
                    ], 422);
                }
            }
        }

        $review = ArtistReview::create([
            'artist_profile_id' => $artist->id,
            'customer_id'       => $customerId,
            'reviewer_name'     => $reviewerName,
            'reviewer_email'    => $reviewerEmail,
            'rating'            => $request->rating,
            'title'             => $request->title,
            'body'              => $request->body,
            'status'            => 'approved',
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'review'  => [
                'id'            => $review->id,
                'rating'        => $review->rating,
                'title'         => $review->title,
                'body'          => $review->body,
                'reviewer_name' => $review->reviewer_name ?? 'Anonymous',
                'created_at'    => $review->created_at->toDateString(),
            ],
        ], 201);
    }




    private function formatArtist(ArtistProfile $artist): array
    {
        return [
            'id'             => $artist->id,
            'stage_name'     => $artist->stage_name ?: $artist->full_name,
            'full_name'      => $artist->full_name,
            'category'       => $artist->category,
            'location'       => $artist->location,
            'avatar_url'     => $artist->avatar_url,
            'cover_url'      => $artist->cover_url,
            'starting_price' => $artist->starting_price,
            'max_price'      => $artist->max_price,
            'tags'           => $artist->tags ?? [],
            'short_bio'      => $artist->short_bio,
        ];
    }
}
