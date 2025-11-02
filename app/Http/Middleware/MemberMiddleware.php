<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MemberMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        
        if(Auth::guard('member')->check()){
            return $next($request);
        }
        return response()->json(['message' => 'Unauthorized. Member only access.'], 403);
    }
}
