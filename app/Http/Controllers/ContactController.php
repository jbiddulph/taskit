<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Show the contact form
     */
    public function show()
    {
        return Inertia::render('Contact');
    }

    /**
     * Handle contact form submission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            // Send email to support
            Mail::send('emails.contact', $validated, function ($message) use ($validated) {
                $message->to('john.mbiddulph@gmail.com')
                        ->subject('ZapTask Contact Form: ' . $validated['subject'])
                        ->replyTo($validated['email'], $validated['name']);
            });

            // Send confirmation email to user
            Mail::send('emails.contact-confirmation', $validated, function ($message) use ($validated) {
                $message->to($validated['email'], $validated['name'])
                        ->subject('Thank you for contacting ZapTask');
            });

            return response()->json([
                'message' => 'Thank you for your message! We\'ll get back to you soon.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Contact form submission failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ], 500);
        }
    }
}
