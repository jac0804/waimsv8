<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;
use DB;

use App\Http\Classes\coreFunctions;

class checkHeader
{

    private $coreFunctions;

    public function __construct() {
        $this->coreFunctions = new coreFunctions;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!isset($_SERVER['HTTP_SBC_KEY'])) {
            return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
        } else {
            $key = explode('sbc', $_SERVER['HTTP_SBC_KEY']);
            $username = $key[0];
            $password = $key[1];
            $logintype = $key[2];
            if($logintype === 'client') {
                $c = $this->coreFunctions->opentable("select md5(email), password from client where md5(md5(concat(clientid,email))) = ? and md5(concat(clientid,password)) = ?",[$username,$password]);
            } else if ($logintype === 'applicant') {
                $c = $this->coreFunctions->opentable("select md5(username), password from app where md5(md5(username))=? and md5(password)=?",[$username,$password]);
            } else {
                $c = $this->coreFunctions->opentable("select md5(username), password from useraccess where md5(md5(concat(userid,username))) = ? and md5(concat(accessid,password)) = ?",[$username,$password]);
            }
            if(count($c) > 0) {
                return $next($request);
            } else {
                return Response::json(array('status'=>'invalid', 'msg'=>'Invalid credentials'));
            }
        }
        // if(!isset($_SERVER['HTTP_X_USERNAME']) && !isset($_SERVER['HTTP_X_PASSWORD'])) {
        //     return Response::json(array('status'=>false, 'msg'=>'Invalid credentials'));
        // } else {
        //     $username = $_SERVER['HTTP_X_USERNAME'];
        //     $password = $_SERVER['HTTP_X_PASSWORD'];
        //     $c = DB::select("select accessid, md5(username), password, name, '' as center from useraccess where md5(username) = '$username' and password = '$password'");
        //     if(count($c) > 0) {
        //         return $next($request);
        //     } else {
        //         return Response::json(array('status'=>false, 'msg'=>'Invalid credentials'));
        //     }
        // }

        // if($_SERVER['HTTP_X_SBCKEY'] != '123456') {
        //     return Response::json(array('error'=>'wrong custom header'.$_SERVER['HTTP_X_HARDIK']));
        // }
    }
}
