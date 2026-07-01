<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['admin', 'artist', 'client'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        try {
            Mail::to($request->email)->send(new ResetPasswordMail($token));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send reset email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Password reset token sent to your email.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = \DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return response()->json([
                'message' => 'Invalid token or email.',
            ], 400);
        }

        if (now()->parse($reset->created_at)->addMinutes(60)->isPast()) {
            \DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token has expired.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    public function sendVerification(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'An account with this email already exists. Please log in.',
            ], 422);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        \DB::table('email_verifications')->updateOrInsert(
            ['email' => $request->email],
            [
                'code' => Hash::make($code),
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]
        );

        try {
            Mail::to($request->email)->send(new VerificationCodeMail($code));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send verification email. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Verification code sent to your email.',
            'expires_in_minutes' => 10,
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $record = \DB::table('email_verifications')->where('email', $request->email)->first();

        if (!$record) {
            return response()->json([
                'message' => 'No verification request found. Please request a code first.',
            ], 400);
        }

        if (now()->greaterThan($record->expires_at)) {
            \DB::table('email_verifications')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Code has expired. Please request a new one.',
            ], 400);
        }

        if (!Hash::check($request->code, $record->code)) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 400);
        }

        return response()->json([
            'message' => 'Email verified successfully.',
            'verified' => true,
        ]);
    }
}
