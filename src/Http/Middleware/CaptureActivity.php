<?php
namespace System\Agent\Http\Middleware;

use Closure;
use System\Agent\Models\ActivityEvent;

class CaptureActivity
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (int)((microtime(true)-$start)*1000);

        // prepare payload (mask fields)
        $payload = [
            'method' => $request->method(),
            'path'   => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'body' => $request->except(config('agent.exclude_fields')),
        ];

        // save to local buffer table or dispatch job
        ActivityEvent::create(['payload' => $payload, 'status' => 'pending']);

        return $response;
    }
}
