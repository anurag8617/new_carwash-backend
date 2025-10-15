<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
// The "use Illuminate\Http\Request;" line is no longer needed here.

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request) 
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}