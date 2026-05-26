<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private array $allowedProviders = ['google', 'facebook'];

    /**
     * Redirect to provider OAuth page (for web-based flow).
     */
    public function redirect(string $provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        return response()->json([
            'url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    /**
     * Handle provider callback (web-based flow).
     */
    public function callback(string $provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json(['message' => 'Unsupported provider'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to get user from ' . $provider], 422);
        }

        return $this->loginOrCreateUser($socialUser, $provider);
    }

    private function loginOrCreateUser($socialUser, string $provider, string $defaultRole = 'client')
    {
        $user = User::where('social_id', $socialUser->getId())
            ->where('social_provider', $provider)
            ->first();

        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'social_id'       => $socialUser->getId(),
                    'social_provider' => $provider,
                ]);
            } else {
                $user = User::create([
                    'name'            => $socialUser->getName(),
                    'email'           => $socialUser->getEmail(),
                    'social_id'       => $socialUser->getId(),
                    'social_provider' => $provider,
                    'role'            => $defaultRole,
                    'email_verified_at' => now(),
                ]);
            }
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'Your account has been banned.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login successful',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }
}