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


    /**
     * Returns the onboarding completion status for the authenticated artist.
     * Used by the frontend guard to redirect to the correct step.
     */
    public function status(Request $request)
    {
        $user    = $request->user();
        $profile = $user->artistProfile()->first();

        $basicInfoDone = $profile !== null;

        $verificationDone = $basicInfoDone && $user->artistMedia()
            ->where('purpose', 'verification_front')
            ->exists();

        $talentDone = $basicInfoDone && $user->artistMedia()
            ->where('purpose', 'talent_media')
            ->exists();

        return response()->json([
            'basic_info_done'   => $basicInfoDone,
            'verification_done' => $verificationDone,
            'talent_done'       => $talentDone,
        ]);
    }


    public function storeBasicInfo(Request $request)
    {
        $request->validate([
            'full_name'    => 'required|string|max:255',
            'stage_name'   => 'nullable|string|max:255',
            'location'     => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'dob'          => 'nullable|date',
            'category'     => 'required|string|in:Singer,Rapper,Live Band,Dance Group,Producer,DJ,Sound System,Lighting System,Photographer,Videographer',
            'email'        => 'required|email|max:255',
        ]);

        $existing = ArtistProfile::where('user_id', $request->user()->id)->first();

        $profile = ArtistProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->only(['full_name', 'stage_name', 'location', 'phone_number', 'dob', 'category', 'email'])
        );

        // If this is a brand-new profile, ensure status stays null (not pending)
        // so it does not appear in the admin queue until talent step is complete.
        if (!$existing) {
            $profile->verification_status = null;
            $profile->save();
        }

        return response()->json([
            'message' => 'Basic information saved successfully',
            'profile' => $profile,
        ]);
    }


    public function uploadVerification(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:National ID,Passport,Bank Statement,Driving License',
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
                    ['file' => $request->file('front'),  'purpose' => 'verification_front', 'type' => 'document', 'label' => 'Front side document'],
                    ['file' => $request->file('selfie'), 'purpose' => 'selfie',             'type' => 'image',    'label' => 'Selfie'],
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

                // NOTE: We do NOT set verification_status here.
                // The admin notification and pending status are set only after
                // the artist completes the final talent step (storeTalent).
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
            'video'    => 'nullable|file|mimes:mp4,mov,avi,mkv,webm,flv,wmv|max:102400',
        ], [
            'video.mimes'       => 'The video must be MP4, MOV, AVI, MKV, WebM, FLV, or WMV.',
            'video.max'         => 'The video must be under 100 MB.',
        ]);

        $user = $request->user();

        try {
            if ($request->hasFile('video')) {
                $this->uploadToCloudinary($request->file('video'), $user->id, 'talent_media', 'video');
            }

            // All three onboarding steps are now done.
            // Mark the profile as pending review so the admin can see it.
            $user->artistProfile()->update([
                'verification_status' => 'pending',
                'is_onboarded'        => true,
            ]);

            // Notify admins that a complete application is ready for review.
            $profile    = $user->artistProfile()->first();
            $artistName = $profile ? ($profile->stage_name ?: $profile->full_name) : 'An artist';

            \App\Models\Notification::sendToAdmins(
                'verification',
                'New Verification Request',
                "{$artistName} submitted a complete verification application.",
                '/verification'
            );

            return response()->json(['message' => 'Talent showcase saved successfully']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Delete ALL data for the currently authenticated artist who has not
     * completed onboarding, then log them out.  Called when the artist
     * abandons registration and tries to log in again.
     */
    public function cancelRegistration(Request $request)
    {
        $user = $request->user();

        // Only allow cancellation if onboarding is not yet finished
        $talentDone = $user->artistMedia()
            ->where('purpose', 'talent_media')
            ->exists();

        if ($talentDone) {
            return response()->json(['message' => 'Onboarding already complete.'], 400);
        }

        // Delete artist profile and all media (cascade handles media via user delete)
        $user->currentAccessToken()->delete();
        $user->delete(); // cascade deletes artist_profiles + artist_media

        return response()->json(['message' => 'Incomplete registration removed.']);
    }


    private function uploadToCloudinary($file, $userId, $purpose, $type)
    {
        $resourceType = ($type === 'video')
            ? 'video'
            : (($type === 'document' && $file->getClientOriginalExtension() === 'pdf') ? 'raw' : 'image');

        $upload = $this->uploadFile($file, "artists/{$userId}/{$purpose}", $resourceType);

        return ArtistMedia::create([
            'user_id'              => $userId,
            'media_type'           => $type,
            'purpose'              => $purpose,
            'url'                  => $upload['secure_url'],
            'cloudinary_public_id' => $upload['public_id'],
            'is_external_link'     => false,
        ]);
    }
}
