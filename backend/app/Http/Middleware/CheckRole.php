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

        if (!$user || $user->role?->slug !== $role) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 'error',
            ], 403);
        }

        return $next($request);
    }
}
