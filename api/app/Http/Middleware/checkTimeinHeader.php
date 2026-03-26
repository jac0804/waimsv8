<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;

class checkTimeinHeader {

    public function handle($request, Closure $next)
    {
        if(!isset($_SERVER['HTTP_SBC_KEY'])) {
            return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
        } else {
            $key = explode('sbc', $_SERVER['HTTP_SBC_KEY']);
            $key1 = $key[0];
            $key2 = $key[1];
            if($key1 == md5('sbctimein') && $key2 == md5('solutionbasecorp')) {
                return $next($request);
            } else {
                return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
            }
        }
    }
}
