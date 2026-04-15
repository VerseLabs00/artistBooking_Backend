<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistBankDetail;
use Illuminate\Http\Request;

class ArtistBankController extends Controller
{
    /**
     * Get the authenticated artist's bank details.
     * account_number is shown as masked (****6789) for display safety.
     * Use ?reveal=1 to get the full account number (e.g., for the edit form).
     *
     * GET /api/profile/bank
     */
    public function show(Request $request)
    {
        $profile = $request->user()->artistProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $bankDetails = $profile->bankDetails;

        if (!$bankDetails) {
            return response()->json(['bank_details' => null]);
        }

        // Build response — always include masked number
        $data = [
            'id'                    => $bankDetails->id,
            'account_holder_name'   => $bankDetails->account_holder_name,
            'bank_name'             => $bankDetails->bank_name,
            'branch'                => $bankDetails->branch,
            'account_number_masked' => $bankDetails->masked_account_number,
            'account_type'          => $bankDetails->account_type,
            'ifsc_code'             => $bankDetails->ifsc_code,
            'is_verified'           => $bankDetails->is_verified,
        ];

        // Optionally reveal full number only when artist explicitly requests it
        if ($request->boolean('reveal')) {
            $data['account_number'] = $bankDetails->getRawOriginal('account_number');
        }

        return response()->json(['bank_details' => $data]);
    }

    /**
     * Save or update the authenticated artist's bank details.
     * Creates a new record if none exists; updates existing record otherwise.
     *
     * POST /api/profile/bank
     */
    public function upsert(Request $request)
    {
        $profile = $request->user()->artistProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $validated = $request->validate([
            'account_holder_name' => 'required|string|max:255',
            'bank_name'           => 'required|string|max:255',
            'branch'              => 'nullable|string|max:255',
            'account_number'      => 'required|string|max:50',
            'account_type'        => 'sometimes|in:savings,current,fixed_deposit',
            'ifsc_code'           => 'nullable|string|max:20',
        ]);

        $bankDetails = ArtistBankDetail::updateOrCreate(
            ['artist_profile_id' => $profile->id],
            $validated
        );

        return response()->json([
            'message'      => 'Bank details saved successfully.',
            'bank_details' => [
                'id'                    => $bankDetails->id,
                'account_holder_name'   => $bankDetails->account_holder_name,
                'bank_name'             => $bankDetails->bank_name,
                'branch'                => $bankDetails->branch,
                'account_number_masked' => $bankDetails->masked_account_number,
                'account_type'          => $bankDetails->account_type,
                'ifsc_code'             => $bankDetails->ifsc_code,
                'is_verified'           => $bankDetails->is_verified,
            ],
        ]);
    }

    /**
     * Return bank details for a confirmed booking (used by booking system).
     * Exposes the FULL account number — only accessible with a valid auth token.
     * The booking controller should call this internally or gate it to the booking owner.
     *
     * GET /api/profile/bank/booking-view/{artistProfileId}
     */
    public function bookingView(string $artistProfileId)
    {
        $bankDetails = ArtistBankDetail::where('artist_profile_id', $artistProfileId)
            ->where('is_verified', true)           // only show verified bank details
            ->firstOrFail();

        return response()->json([
            'bank_details' => [
                'account_holder_name' => $bankDetails->account_holder_name,
                'bank_name'           => $bankDetails->bank_name,
                'branch'              => $bankDetails->branch,
                'account_number'      => $bankDetails->getRawOriginal('account_number'),
                'account_type'        => $bankDetails->account_type,
                'ifsc_code'           => $bankDetails->ifsc_code,
            ],
        ]);
    }
}
