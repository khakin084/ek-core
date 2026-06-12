<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditBffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only audit mutating requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        // capture sanitized payload from request (what user attempted)
        $hidden = ['password', 'password_confirmation', 'token', '_token'];
        $payload = Arr::except($request->all(), $hidden);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // try parse JSON response (downstream proxied response should be json)
        $content = $response->getContent();
        $json = null;

        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        // Only log on successful operations (tweak if you want to also log failures)
        $isOk = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

        // Downstream-provided audit info
        $audit = $json['meta']['audit'] ?? null;

        // If downstream didn't supply meta.audit, you can choose to skip or still log minimal info
        if (!$isOk || !$audit) {
            return $response;
        }

        // module/entity headers sent by JS (optional but helpful)
        $module = $request->header('X-AUDIT-MODULE', $audit['module'] ?? 'General');
        $entity = $request->header('X-AUDIT-ENTITY', $audit['entity'] ?? null);

        $desc = $audit['description'] ?? null;
        if (!$desc) {
            // fallback: infer from event + entity
            $verb = match ($audit['event'] ?? null) {
                'created' => 'Recorded',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
                default => 'Action',
            };
            $desc = $entity ? "{$verb} {$entity}" : $verb;
        }

        AccessLog::create([
            'user_id' => auth()->id(),
            'module' => $module,
            'description' => $desc,

            'model_type' => $audit['model_type'] ?? null,
            'model_id' => $audit['model_id'] ?? $request->header('X-AUDIT-RECORD-ID'),

            'event' => $audit['event'] ?? strtolower($request->method()),

            // store both request payload + downstream changes
            'payload' => $payload ?: null,
            'changes' => $audit['changes'] ?? null,

            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }
}