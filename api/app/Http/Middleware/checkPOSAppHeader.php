<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;

use App\Http\Classes\coreFunctions;

class checkPOSAppHeader {

    private $coreFunctions;

    public function __construct() {
        $this->coreFunctions = new coreFunctions;
    }

    public function handle($request, Closure $next)
    {
        if(!isset($_SERVER['HTTP_SBC_KEY'])) {
            return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
        } else {
            $key = explode('sbc', $_SERVER['HTTP_SBC_KEY']);
            $username = $key[0];
            $password = $key[1];
            $c = $this->coreFunctions->opentable("select userid from branchusers where md5(username)='".$username."' and md5(md5(password))='".$password."'");
            if(count($c) > 0) {
                return $next($request);
            } else {
                return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
            }
        }
    }
}
