<?php

namespace App\Http\Controllers;

use App\Mail\ActivityMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        $recipient = config('services.contact_email');
        abort_unless(is_string($recipient) && filter_var($recipient, FILTER_VALIDATE_EMAIL), 503, 'The contact inbox is not configured.');

        $body = "Website contact request\n\n"
            . "Name: {$validated['name']}\n"
            . "Email: {$validated['email']}\n"
            . 'Phone: ' . ($validated['phone'] ?: 'Not provided') . "\n\n"
            . $validated['message'];

        try {
            Mail::to($recipient)->queue(new ActivityMail(
                'Website inquiry from ' . $validated['name'],
                $body,
                $validated['email'],
                $validated['name'],
            ));
        } catch (\Throwable $exception) {
            Log::error('Website contact message could not be queued.', ['error' => $exception->getMessage()]);

            return back()->withInput()->withErrors(['message' => 'Your message could not be sent right now. Please try again later.']);
        }

        return back()->with('success', 'Thank you. Your inquiry has been sent to the LJS Realty team.');
    }
}