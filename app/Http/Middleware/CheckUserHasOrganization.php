<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserHasOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (auth()->hasUser()) {

            if (getAllValidOrganizationsForUser(auth()->user())->isEmpty()) {
                abort(403, 'Sem permissão para acessar este recurso.');
            }

        }

        return $next($request);
    }
}
