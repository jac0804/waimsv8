<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Response;
use DB;

use App\Http\Classes\coreFunctions;

class checkSbcATIHeader
{

    private $coreFunctions;

    public function __construct()
    {
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
        if (!isset($_SERVER['HTTP_SBC_KEY'])) {
            return Response::json(array('status' => 'false', 'msg' => 'Invalid credentials'));
        } else {
            if (md5('ati@sbcapi') == $_SERVER['HTTP_SBC_KEY']) {
                return $next($request);
                // return Response::json(array('status'=>'true', 'msg'=>'Success', 'data'=>$_SERVER));
            } else {
                return Response::json(array('status' => 'false', 'msg' => 'Invalid key'));
            }
        }
    }
}
