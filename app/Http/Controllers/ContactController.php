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
            // Log the contact form submission
            \Log::info('Contact form submission received', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'timestamp' => now()->toISOString()
            ]);

            // Send email to support using Mailable classes
            Mail::to('john.mbiddulph@gmail.com')->send(new \App\Mail\ContactFormMail($validated));

            // Send confirmation email to user
            Mail::to($validated['email'])->send(new \App\Mail\ContactConfirmationMail(
                $validated['name'],
                $validated['email'],
                $validated['subject']
            ));

            return response()->json([
                'message' => 'Thank you for your message! We\'ll get back to you soon.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Contact form submission failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'form_data' => $validated
            ]);
            
            // Return error to help debug
            return response()->json([
                'message' => 'There was an error sending your message. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
