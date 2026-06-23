<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistMedia;
use App\Models\ArtistProfile;
use App\Traits\HandlesCloudinaryUploads;
use App\Traits\ValidatesUploadFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    use HandlesCloudinaryUploads, ValidatesUploadFiles;


    public function storeBasicInfo(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'stage_name' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'category' => 'required|string|in:Singer,Rapper,Live Band,Dance Group,Producer,DJ,Sound System,Lighting System,Photographer,Videographer',
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


    public function uploadVerification(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:National ID,Passport,Bank Statement,Driving License',

            'front' => 'required|file|mimes:jpg,jpeg,png,pdf,heic,heif|max:10240',
            'back' => 'nullable|file|mimes:jpg,jpeg,png,pdf,heic,heif|max:10240',
            'selfie' => 'required|file|mimes:jpg,jpeg,png,heic,heif|max:10240',

            'front'  => ['required', 'file', 'max:10240', $this->verificationFileRule(allowPdf: true)],
            'back'   => ['nullable', 'file', 'max:10240', $this->verificationFileRule(allowPdf: true)],
            'selfie' => ['required', 'file', 'max:10240', $this->verificationFileRule(allowPdf: false)],
        ], [
            'front.required'  => 'Please upload the front side of your document.',
            'selfie.required' => 'Please upload a selfie with your document.',
            'front.max'       => 'Front side document exceeds the 10MB size limit.',
            'back.max'        => 'Back side document exceeds the 10MB size limit.',
            'selfie.max'      => 'Selfie exceeds the 10MB size limit.',

        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $user) {
                $uploads = [
                    ['file' => $request->file('front'), 'purpose' => 'verification_front', 'type' => 'document', 'label' => 'Front side document'],
                    ['file' => $request->file('selfie'), 'purpose' => 'selfie', 'type' => 'image', 'label' => 'Selfie'],
                ];

                if ($request->hasFile('back')) {
                    $uploads[] = ['file' => $request->file('back'), 'purpose' => 'verification_back', 'type' => 'document', 'label' => 'Back side document'];
                }

                foreach ($uploads as $upload) {
                    try {
                        $this->uploadToCloudinary($upload['file'], $user->id, $upload['purpose'], $upload['type']);
                    } catch (\Exception $e) {
                        throw ValidationException::withMessages([
                            $upload['purpose'] => "{$upload['label']} ({$upload['file']->getClientOriginalName()}) failed to upload: {$e->getMessage()}",
                        ]);
                    }
                }

                $user->artistProfile()->update(['verification_status' => 'pending']);

                $profile = $user->artistProfile()->first();
                $artistName = $profile ? ($profile->stage_name ?: $profile->full_name) : 'An artist';
                \App\Models\Notification::sendToAdmins(
                    'verification',
                    'New Verification Request',
                    "{$artistName} submitted a verification application.",
                    '/verification'
                );
            });

            return response()->json(['message' => 'Verification documents uploaded successfully']);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }


    public function storeTalent(Request $request)
    {
        $request->validate([
            'media_file' => 'nullable|file|mimes:mp4,mov,avi,jpg,jpeg,png|max:51200',
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


    private function uploadToCloudinary($file, $userId, $purpose, $type)
    {
        $resourceType = ($type === 'video') ? 'video' : (($type === 'document' && $file->getClientOriginalExtension() === 'pdf') ? 'raw' : 'image');

        $upload = $this->uploadFile($file, "artists/{$userId}/{$purpose}", $resourceType);

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
