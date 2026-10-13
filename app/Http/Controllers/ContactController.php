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
        \App\Support\InputRules::normalize($request, ['name'], ['phone']);
        $validated = $request->validate([
            'name' => \App\Support\InputRules::name(),
            'email' => \App\Support\InputRules::email(),
            'phone' => \App\Support\InputRules::phone(),
            'message' => ['required', 'string', 'min:5', 'max:3000'],
        ], \App\Support\InputRules::messages());
        /*
         * IF THIS IS USED, MAKE A TABLE FOR COMPANY_SETTINGS AND UNCOMMENT THIS TO USE.
         * Put the dynamic contact form recipient query here after creating the CompanySetting model/table.
         *
         * Example future setup:
         * $recipient = \App\Models\CompanySetting::query()->value('contact_email')
         *     ?: config('services.contact_email');
         */
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
