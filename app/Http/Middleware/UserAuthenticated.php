<?php

namespace App\Http\Middleware;
use Auth;
use Closure;

class UserAuthenticated
{
    public function handle($request, Closure $next)
    {
        if( Auth::check() )
        {
            // if user is not admin take him to his dashboard
            if ( Auth::user()->isAdmin() ) {
                 return redirect(route('admin.dashboard'));
            }

            // allow admin to proceed with request
            else if ( Auth::user()->isUser() ) {
                 return $next($request);
            }
        }

        abort(404);  // for other user throw 404 error
    }
}