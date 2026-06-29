<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->isBkd()) {
            return $next($request);
        }

        $opdId = $request->route('opd');

        if ($opdId === null) {
            return $next($request);
        }

        if ((int) $opdId !== (int) $user->opd_id) {
            abort(403, 'You can only access data from your own OPD.');
        }

        return $next($request);
    }
}
