<?php

namespace App\Overrides;

use Closure;
use Illuminate\Http\Request;

class ConvertEmptyStringsToNull
{
    public function handle(Request $request, Closure $next)
    {
        // 👇 Skip converting empty strings to null
        return $next($request);
    }
}