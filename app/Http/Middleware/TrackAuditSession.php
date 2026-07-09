<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogService;
use App\Services\Audit\AuditSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackAuditSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('audit.enabled', true)) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $auditLogService = app(AuditLogService::class);

        if ($auditLogService->shouldSkipRequest($request)) {
            return $next($request);
        }

        app(AuditSessionService::class)->touchCurrentSession($request);

        /** @var Response $response */
        $response = $next($request);

        $status = $response->getStatusCode();

        $auditLogService->logPageView($request, $status);
        $auditLogService->logFailedRequest($request, $status);

        return $response;
    }
}
