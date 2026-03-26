<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;

use App\Http\Classes\coreFunctions;

class checkFinedineAppHeader {

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
            $c = $this->coreFunctions->opentable("select password from useraccess where md5(username)='".$username."'");
            if(count($c) > 0) {
                foreach($c as $cc) {
                    $pass = base64_decode($cc->password);
                    $pass = utf8_decode($pass);
                    if(md5(md5($pass)) == $password) {
                        return $next($request);
                    }
                }
                return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
            } else {
                return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
            }
        }
    }
}
