<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.n8n.webhook_secret');

        if (empty($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'N8N webhook secret is not configured.',
            ], 503);
        }

        $provided = $request->header('X-N8N-Webhook-Secret');

        if (! is_string($provided) || ! hash_equals($secret, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid N8N webhook secret. Ensure X-N8N-Webhook-Secret in the HTTP node matches N8N_WEBHOOK_SECRET on ZapTask/Heroku.',
            ], 401);
        }

        return $next($request);
    }
}
