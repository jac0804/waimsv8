<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;

use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;

class queuingClass
{
   public $modulename = 'Ticket';

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;


        public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getstatus($params)
    {
        switch ($params['action']) {
            case 'getservice':
              return $this->getservice($params);
              break;
            case 'insertticket':
              return $this->insertticket($params);
              break;
            case 'getdisplayperservice':
              return $this->getdisplayperservice($params);
              break;
            case 'getdisplaypercounter':
              return $this->getdisplaypercounter($params);
              break;
            case 'getloadcounter':
              return $this->getloadcounter($params);
              break;
            case 'getcounterdata':
              return $this->getcounterdata($params);
              break;
            case 'updateserve':
              return $this->updateserve($params);
              break;
            case 'getcounterservice':
              return $this->getcounterservice($params);
        }
    }

    public function getservice($params)
    {
        $qry = "select line,code,description,color,picpath from reqcategory where isservice=1";
        $data = $this->coreFunctions->opentable($qry);
        return ['status'=>true, 'msg'=>'Load Successful', 'data'=>$data];
    }

    public function insertticket($params)
    {
        $dateid = $this->othersClass->getCurrentTimeStamp();
        $date = $this->othersClass->getCurrentDate();
        $line = $params['data']['line'];
        $service = $params['data']['code'];
        $users = $params['user'];
        if($params['ispwd']){
          $ispwd = 1;
        } else {
          $ispwd=0;
        }
        $col = [];
        $col = ['serviceline' => $line, 'counterline' => 0, 'dateid'=>$dateid,'ispwd'=>$ispwd,'users'=>$users];
        $table = 'currentservice';
        $newline = $this->coreFunctions->insertGetId($table, $col); 
        $sql = "update currentservice set ctr=(select COALESCE(MAX(ctr), 0) + 1 from (select ctr from currentservice where date(dateid)='$date' and serviceline=$line and ispwd=$ispwd) as temp) where line=$newline";
        $this->coreFunctions->execqry($sql,'update');
        $ctr = $this->coreFunctions->datareader("select ctr as value from currentservice where line=$newline");
        
        $msg2 = $ctr ;

        $printcontent = "
          <html>
          <head>
            <style>
              @page {
                size: 80mm 297mm; /* Thermal paper size */
                margin: 5mm;
              }
              body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                width: 72mm;
                margin: 0 auto;
              }
              .center { text-align: center; }
              .left { text-align: left; }
              .right { text-align: right; }
              .total { font-weight: bold; }
            </style>
          </head>
          <body>
            <div class='center'>
              <div style='font-size: 24px; font-weight: bold;'>".$service."</div>
              <div style='font-size: 72px; font-weight: bold; margin: 15px 0;'>".$ctr."</div>
            </div>
            <div class='center'>
              <p>".date('m/d/Y h:i A')."</p>
            </div>
            <div class='center'>
              <p>Thank you!</p>
              <p>".str_repeat('&nbsp;', 3)."</p>
            </div>
          </body>
        </html>
        ";

       $this->othersClass->socketqueuing($params,$msg2);

      return ['status'=>true,'data'=>['ctr'=>$ctr,'service'=>$service],'printcontent'=>$printcontent];
    }

    public function getdisplayperservice($params)
    {
        $date = $this->othersClass->getCurrentDate();
        $qry = "select b.line,b.code,b.description,b.color from reqcategory as b where b.isservice=1";
        $service = $this->coreFunctions->opentable($qry);

        $qry = "select a.serviceline,a.ctr,ifnull(c.code,'') as counter,a.isdone,a.ishold,a.iscancel,b.code,b.description,b.color from currentservice as a left join reqcategory as b on b.line=a.serviceline left join reqcategory as c on c.line=a.counterline where date(a.dateid)='$date' and a.isdone=0 and a.ishold=0 and a.iscancel=0 order by a.serviceline,a.ctr";
        $data = $this->coreFunctions->opentable($qry);
        return ['status'=>true, 'msg'=>'Load Successful', 'service'=>$service, 'data'=>$data];
    }

    public function getdisplaypercounter($params)
    {
        $date = $this->othersClass->getCurrentDate();
        // $qry = "select b.line,b.code,b.description,b.color from reqcategory as b where b.iscounter=1";
        $qry = "select a.line,a.code,a.description,a.color,(select concat(case q.ispwd when 0 then '(R)' else '(P)' end, q.ctr,' - ',b.code) from currentservice as q left join reqcategory as b on b.line=q.serviceline where date(q.dateid)='$date' and q.counterline=a.line and q.isdone=0 and q.ishold=0 and q.iscancel=0 and q.isskip=0 order by q.dateid desc limit 1) as nowserving,(select concat(case q.ispwd when 0 then '(R)' else '(P)' end, q.ctr,' - ',b.code) from currentservice as q left join reqcategory as b on b.line=q.serviceline where date(q.dateid)='$date' and q.counterline=a.line and (q.isdone<>0 or  q.iscancel<>0 and q.isskip<>0) order by q.dateid desc limit 1) as doneserving  from reqcategory as a where a.iscounter=1 and a.isinactive = 0 order by a.code";
        $service = $this->coreFunctions->opentable($qry);

        // $qry = "select a.serviceline,concat(case a.ispwd when 0 then '(R)' else '(P)' end, a.ctr) as ctr,a.counterline,ifnull(c.code,'') as counter,a.isdone,a.ishold,a.iscancel,b.code,b.description,b.color,(select concat(case q.ispwd when 0 then '(R)' else '(P)' end, q.ctr,'-',b.code) from currentservice as q left join reqcategory as b on b.line=q.serviceline where date(q.dateid)='$date' and q.isdone<>0 and q.counterline=a.counterline and q.serviceline=a.serviceline order by q.dateid desc limit 1) as doneservice from currentservice as a left join reqcategory as b on b.line=a.serviceline left join reqcategory as c on c.line=a.counterline where date(a.dateid)='$date' and a.isdone=0 and a.ishold=0 and a.iscancel=0 order by a.serviceline,a.ctr";
        // $data = $this->coreFunctions->opentable($qry);
        return ['status'=>true, 'msg'=>'Load Successful', 'service'=>$service];
    }


    public function getloadcounter($params)
    {
        $qry = "select line,code from reqcategory where iscounter=1 and isinactive =0";
        $date = $this->othersClass->getCurrentDate();
        $counter = $this->coreFunctions->opentable($qry);

        $checkdate = $this->coreFunctions->datareader("select dateid as value from currentservice where date(dateid)<'".$date."'");
        if($checkdate !=""){
          return ['status'=>false,'msg'=>'Close Previous date', 'counter'=>[]];
        }
        return ['status'=>true,'msg'=>'Load Successful', 'counter'=>$counter];

    }

    public function getcounterdata($params)
    {
        $date = $this->othersClass->getCurrentDate();
        $counterid = $params['counterid'];
        $qry = "select a.line,concat(case a.ispwd when 0 then '(R)' else '(P)' end, a.ctr) as ctr,a.isdone,a.ishold,a.iscancel,b.code,b.color from currentservice as a left join reqcategory as b on b.line=a.serviceline where date(a.dateid)='$date' and a.counterline=$counterid and a.isdone=0 and a.iscancel=0 and a.counterline<>0";
        $nowserving = $this->coreFunctions->opentable($qry);
        $qry = "select a.counterline,a.serviceline,b.code as service,b.color,(select q.ctr from currentservice as q where date(q.dateid)='$date' and q.ispwd=0 and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as next,(select q.line from currentservice as q where date(q.dateid)='$date' and q.ispwd=0 and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as nextline,(select q.ctr from currentservice as q where date(q.dateid)='$date' and q.ispwd=1 and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as nextpwd,(select q.line from currentservice as q where date(q.dateid)='$date' and q.ispwd=1 and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as nextlinepwd from counterservice as a left join reqcategory as b on b.line=a.serviceline where a.counterline=$counterid order by b.code";
        $service = $this->coreFunctions->opentable($qry);
        $qry = "select b.line,b.ctr,c.line as serviceline,c.code,b.ispwd from currentservice as b left  join counterservice as a on a.serviceline=b.serviceline left join reqcategory as c on c.line=a.serviceline where date(b.dateid)='$date' and a.counterline=$counterid and b.isdone=0 and b.ishold=0 and b.iscancel=0 and b.counterline=0 order by c.code,b.ctr"; 
        $waiting = $this->coreFunctions->opentable($qry);
        return ['status'=>true,'msg'=>'Load Successful','serving'=>$nowserving,'service'=>$service,'waiting'=>$waiting];

    }

    public function updateserve($params)
    {
        $counterid = $params['counterid'];
        $type = $params['type'];
        $line = $params['line'];
        $sql = ''; 
        $dateid = $this->othersClass->getCurrentTimeStamp();
        $actions = [
            '8277e0910d750195b448797616e091ad' => ['field' => 'isdone', 'value' => 1],      // done
            '2510c39011c5be704182423e3a695e91' => ['field' => 'ishold', 'value' => 1],      // hold
            '4a8a08f09d37b73795649038408b5f33' => ['field' => 'iscancel', 'value' => 1],     // cancel
            '4b43b0aee35624cd95b910189b3dc231' => ['field' => 'ishold', 'value' => 0],       // resume
            '03c7c0ace395d80182db07ae2c30f034' => ['field' => 'counterline', 'value' => $counterid] // serve
         ];
        if (!isset($actions[$type])) {
          return ['status' => false, 'msg' => 'Invalid action type'];
        }
        $action = $actions[$type];
        if ($type === '03c7c0ace395d80182db07ae2c30f034') {
            $sql = 'UPDATE currentservice SET counterline = ?, startdate = ? WHERE line = ?';
            $params2 = [$counterid, $dateid,$line];
        } else {
            $sql = 'UPDATE currentservice SET ' . $action['field'] . ' = ?, enddate = ? WHERE line = ?';
            $params2 = [$action['value'], $dateid, $line];
        }
        
        $updateResult = $this->coreFunctions->execqry($sql, 'update', $params2);
        $msg2 = 'update';
        // $data = $this->getcounterdata($params);
        $this->othersClass->socketqueuing($params,$msg2,'/api/send-queuing','');
        return ['status'=>true,'msg'=>'Update successful'];
    }

    public function getcounterservice($params)
    {
        $date = $this->othersClass->getCurrentDate();
        $counterid = $params['counterid'];
        $qry = "select a.counterline,a.serviceline,b.code as service,(select q.ctr from currentservice as q where date(q.dateid)='$date' and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as next,(select q.line from currentservice as q where date(q.dateid)='$date' and q.counterline=0 and q.serviceline=a.serviceline order by q.line limit 1) as nextline from counterservice as a left join reqcategory as b on b.line=a.serviceline where date(a.dateid)='$date' and a.counterline=$counterid order by b.code";
        $service = $this->coreFunctions->opentable($qry);
        return ['status'=>true,'msg'=>'Update successful','service'=>$service];
    }
}