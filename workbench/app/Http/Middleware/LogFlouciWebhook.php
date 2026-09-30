<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogFlouciWebhook
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Flouci webhook raw request.', [
            'content_type' => $request->header('Content-Type'),
            'query' => $request->query(),
            'body' => $request->getContent(),
        ]);

        return $next($request);
    }
}
