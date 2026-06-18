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
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
