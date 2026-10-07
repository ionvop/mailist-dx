<?php

namespace App\Http\Middleware;

use App\Support\DevAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareCurrentUser
{
    /**
     * Share the current (placeholder) user with every view.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('currentUser', DevAuth::user());

        return $next($request);
    }
}
