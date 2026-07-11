<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingPdfPermission
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Authentication is required.'
        );

        $isAdministrator =
            method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole([
                'Administrator',
                'Admin',
            ]);

        $isAllowed =
            $isAdministrator
            || $user->can(
                'download accounting pdf reports'
            );

        abort_unless(
            $isAllowed,
            403,
            'You do not have permission to download '
            . 'or print accounting reports.'
        );

        return $next($request);
    }
}
