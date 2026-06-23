<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'message'    => 'required|string',
        ]);

        try {
            Mail::to('infoperforma.lk@gmail.com')->send(new ContactFormMail($validated));
            
            return response()->json([
                'message' => 'Your message has been sent successfully!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send message. Please try again later.'
            ], 500);
        }
    }
}
