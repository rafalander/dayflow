<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $user?->loadMissing('cargo');

        if (! $user || ($user->cargo?->role ?? null) !== $role) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 'error',
            ], 403);
        }

        return $next($request);
    }
}
