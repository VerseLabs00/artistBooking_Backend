<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistMedia;
use App\Models\ArtistProfile;
use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    private $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(config('services.cloudinary.url'));

        // SMART TOGGLE: Bypass SSL on local Windows dev, keep secure in Production
        if (app()->environment('local')) {
            $this->cloudinary->configuration->api->uploadPrefix = 'http://api.cloudinary.com';
            $this->cloudinary->configuration->api->secure = false;
        }
    }

    /**
     * Step 1: Store Basic Information
     */
    public function storeBasicInfo(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'stage_name' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'category' => 'required|string|in:Musician,Producer,DJ',
            'email' => 'required|email|max:255',
        ]);

        $profile = ArtistProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->only(['full_name', 'stage_name', 'location', 'phone_number', 'dob', 'category', 'email'])
        );

        return response()->json([
            'message' => 'Basic information saved successfully',
            'profile' => $profile
        ]);
    }

    /**
     * Step 2: Upload Verification Documents
     */
    public function uploadVerification(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:National ID,Passport,Bank Statement,Driving License',
            'front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'selfie' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $user) {

                $this->uploadToCloudinary($request->file('front'), $user->id, 'verification_front', 'document');

                if ($request->hasFile('back')) {
                    $this->uploadToCloudinary($request->file('back'), $user->id, 'verification_back', 'document');
                }

                $this->uploadToCloudinary($request->file('selfie'), $user->id, 'selfie', 'image');

                $user->artistProfile()->update(['verification_status' => 'pending']);
            });

            return response()->json(['message' => 'Verification documents uploaded successfully']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 3: Store Talent Showcase
     */
    public function storeTalent(Request $request)
    {
        $request->validate([
            'media_file' => 'nullable|file|mimes:mp4,mov,avi,jpg,jpeg,png|max:51200', // 50MB max
            'external_link' => 'nullable|url',
        ]);

        if (!$request->hasFile('media_file') && !$request->external_link) {
            return response()->json(['message' => 'Either a media file or an external link is required'], 422);
        }

        $user = $request->user();

        try {
            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';
                $this->uploadToCloudinary($file, $user->id, 'talent_media', $type);
            }

            if ($request->external_link) {
                ArtistMedia::create([
                    'user_id' => $user->id,
                    'media_type' => 'video',
                    'purpose' => 'talent_media',
                    'url' => $request->external_link,
                    'is_external_link' => true,
                ]);
            }


            $user->artistProfile()->update(['is_onboarded' => true]);

            return response()->json(['message' => 'Talent showcase saved successfully']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Upload File to Cloudinary
     */
    private function uploadToCloudinary($file, $userId, $purpose, $type)
    {
        $resourceType = ($type === 'video') ? 'video' : (($type === 'document' && $file->getClientOriginalExtension() === 'pdf') ? 'raw' : 'image');

        $upload = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => "artists/{$userId}/{$purpose}",
            'resource_type' => $resourceType
        ]);

        return ArtistMedia::create([
            'user_id' => $userId,
            'media_type' => $type,
            'purpose' => $purpose,
            'url' => $upload['secure_url'],
            'cloudinary_public_id' => $upload['public_id'],
            'is_external_link' => false,
        ]);
    }
}
