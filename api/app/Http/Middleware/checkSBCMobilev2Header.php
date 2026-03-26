<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;
use DB;

use App\Http\Classes\coreFunctions;

class checkSBCMobilev2Header
{

    private $coreFunctions;
    private $company;
    private $appType;

    public function __construct() {
        $this->coreFunctions = new coreFunctions;
        $this->company = env('appcompany', 'sbc');
        $this->appType = env('apptype', 'ordering');
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
        $key = explode('sbc', $_SERVER['HTTP_SBC_KEY']);
        $username = $key[0];
        $password = $key[1];
        switch($this->appType) {
            case 'collection':
                $c = $this->coreFunctions->opentable("select userid from useraccess where md5(md5(username))='".$username."' and md5(password)='".$password."'");
                break;
            case 'production': case 'sapint': case 'inventoryapp':
                $c = $this->coreFunctions->opentable("select userid from useraccess where md5(username)='".$username."' and md5(password)='".$password."'");
                break;
            case 'timeinadminapp':
                $c = $this->coreFunctions->opentable("select clientid from client where md5(email)='".$username."' and md5(password)='".$password."'");
                break;
            default:
                switch ($this->company) {
                    case 'fastrax': case 'sample': case 'geo':
                        $c = $this->coreFunctions->opentable("select clientid as userid from client where md5(client) = '$username' and md5(ppass) = '$password'");
                    break;
                    case 'qnq': case 'shinzen': case 'marswin': case 'sbc':
                        $c = $this->coreFunctions->opentable("select clientid as userid from client where md5(client) = '$username' and md5(pword) = '$password'");
                    break;
                }
                break;
        }
        if(count($c) > 0) {
            return $next($request);
        } else {
            return Response::json(array('status'=>false, 'msg'=>'Invalid credentials', 'headerError'=>true));
        }
    }
}
