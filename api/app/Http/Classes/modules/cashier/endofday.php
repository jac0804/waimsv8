<?php

namespace App\Http\Classes\modules\cashier;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;



class endofday
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'End of Day';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $fields = [];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5109
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    // $columns = [
    //     'category',
    //     'amount',
    //     'deduction'
    //   ];
  
    //   foreach ($columns as $key => $value) {
    //     $$value = $key;
    // }
  
    //   $tab = [$this->gridname => [
    //     'gridcolumns' => $columns
    //   ]];
    //   $stockbuttons = [];
  
    //   $obj = $this->tabClass->createtab($tab, $stockbuttons);
    //   $obj[0][$this->gridname]['descriptionrow'] = [];
    //   $obj[0][$this->gridname]['label'] = 'TRANSACTIONS';
    //   $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Collection';  
    //   $obj[0][$this->gridname]['columns'][$deduction]['label'] = 'Deposit';  
    //   $obj[0][$this->gridname]['columns'][$deduction]['type'] = 'label';
    //   $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
    //   $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
    //return $obj;
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['dateid','begbal','totalcoll'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    $fields = ['totaldep','endingbal',['refresh','dlsales']]; 

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'Load Data');
    data_set($col2, 'dlsales.action', 'close');
    data_set($col2, 'dlsales.label', 'Close');
    data_set($col2, 'endingbal.label', 'Ending Balance');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select adddate(left(now(),10),-360) as dateid
    ");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    //return $this->paramsdata($config);
    $data = $this->coreFunctions->opentable("
    select curdate() as dateid, 
    0.00 as begbal, 0.00 as endingbal, 0.00 as totalcoll, 0.00 as totaldep
  ");
      if (!empty($data)) {
          return $data[0];
      } else {
          return [];
      }
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];
    $center = $config['params']['center'];
   

    switch ($action) {
      case 'close':
        $d = $config['params']['dataparams'];
        $dateid = date('Y-m-d', strtotime($d['dateid']));

        if($d['totalcoll'] ==0 || $d['totaldep'] == 0 || $d['endingbal'] ==0 ){
          return  ['status' => 'false', 'msg' => 'No data to check. Please Load data first.','action' => 'load'];
        }

        $ins['dateid'] = $d['dateid'];
        $ins['begbal'] = $d['begbal'];
        $ins['collection'] = $d['totalcoll'];
        $ins['deposit'] = $d['totaldep'];
        $ins['endingbal'] = $d['endingbal'];
        $ins['closeby'] = $config['params']['user'];
        $ins['center'] = $config['params']['center'];

        $qry ="select category, sum(amount) as amount from (select r.category,h.amount
        from tcoll as h left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 where h.center = ?  and date(h.dateid) <= ? and h.dstrno=0 and r.category in ('cash','check')
        union all
        select r.category, h.amount
        from hcehead as h left join transnum as num on num.trno = h.trno
        left join reqcategory as r on r.line = h.mpid and r.ispaymode =1
        left join reqcategory as rr on rr.line = h.trnxtid and rr.isttype =1 where num.center = ? and date(h.dateid) <= ?  and r.category in ('cash','check') and rr.category not in ('refund','subsidy')
        union all
        select r.category, h.amount*-1
        from hcehead as h left join transnum as num on num.trno = h.trno
        left join reqcategory as r on r.line = h.mpid and r.ispaymode =1
        left join reqcategory as rr on rr.line = h.trnxtid and rr.isttype =1 where num.center = ? and date(h.dateid) <= ?
        and rr.category  in ('refund','subsidy')
        union all
        select r.category, h.amount*-1 as amount
        from hdxhead as h left join transnum as num on num.trno = h.trno
        left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 where num.center = ? and date(h.dateid) <= ?  and r.category in ('cash','check')) as a group by category";

        $col = $this->coreFunctions->opentable($qry,[$center,$dateid,$center,$dateid,$center,$dateid,$center,$dateid]);
        
        $cash = 0;
        $checks =0;
        
        foreach($col as $c =>$v){
          if(strtoupper($col[$c]->category) =='CASH'){
            $cash += $col[$c]->amount;
          }else{
            $checks +=$col[$c]->amount;
          }
        }

        $ins['cash'] = $cash;
        $ins['checks'] =$checks;

        foreach ($ins as $key => $value) {
            $ins[$key] = $this->othersClass->sanitizekeyfield($key, $ins[$key]);
        }     
        
        if($this->coreFunctions->sbcinsert('eod',$ins)==1){
            $dateid = date('Y-m-d', strtotime($d['dateid']));
            $status = $this->coreFunctions->sbcupdate("profile", ["pvalue" => $dateid], ['doc' => 'SYSL']);
            if($status){
              return  ['status' => 'true', 'msg' => 'Closed Successfully','action' => 'load'];//$this->loaddata($config,$config['params']['dataparams']['dateid']);
            }else{
              return  ['status' => 'false', 'msg' => 'Error on closing.','action' => 'load'];
            }
            
        }
        //return $this->downloadmcdx($config);
        break;
      case 'load':
        return $this->loaddata($config, $config['params']['dataparams']['dateid']);
        break;
      
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loaddata($config, $dateid)
  {
    //add checking if close na
    $center = $config['params']['center'];
    $user = $config['params']['user'];
    $dateid = $this->othersClass->sbcdateformat($dateid);

    $closed = $this->coreFunctions->getfieldvalue("eod","line","date(dateid) = ? and center=? and closeby = ?",[$dateid,$center],$user,'',true);
    if($closed !=0){
      return ['status' => false, 'msg' => 'This date is already close.', 'action' => 'load'];
    }

    $unposted = $this->coreFunctions->datareader("select trno  as value  from (select num.trno from cehead as ce left join transnum as num on num.trno = ce.trno where num.center ='".$center."' and date(ce.dateid)='".$dateid."' and ce.createby = '".$user."' union all 
    select ce.trno from dxhead as ce left join transnum as num on num.trno = ce.trno  where num.center ='".$center."' and date(ce.dateid)='".$dateid."' and dx.createby = '".$user."') as a limit 1");
    if($unposted !=0){
      return ['status' => false, 'msg' => 'There are unposted transactions.', 'action' => 'load'];
    }

    $qry = "select category,format(sum(amount),2) as amount,format(sum(deduction),2) as deduction from (select r.category,h.amount,0 as deduction
    from tcoll as h left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 where h.center = ? and date(h.dateid) = ? and h.createby = '".$user."'
    union all
    select r.category, h.amount,0  as deduction
    from hcehead as h left join transnum as num on num.trno = h.trno
    left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 
    left join reqcategory as rr on rr.line = h.trnxtid and rr.isttype =1
    where rr.category not in ('REFUND','SUBSIDY') and num.center = ? and date(h.dateid) = ? and h.createby = '".$user."'
    union all
     select r.category, 0 as amount,h.amount as deduction
    from hcehead as h left join transnum as num on num.trno = h.trno
    left join reqcategory as r on r.line = h.mpid and r.ispaymode =1
    left join reqcategory as rr on rr.line = h.trnxtid and rr.isttype =1 where rr.category  in ('refund','subsidy')  and num.center = ?   and date(h.dateid) = ? and h.createby = '".$user."'
    union all
    select r.category,0 as amount,h.amount as deduction
    from tcoll as h left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 where h.dstrno<>0 and h.center = ?   and date(h.depodate) = ? and h.createby = '".$user."'
    union all
select r.category, 0 as amount,h.amount as deduction
    from hcehead as h left join transnum as num on num.trno = h.trno
    left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 where r.category not in ('cash','check') and num.center = ? and date(h.dateid) = ? and h.createby = '".$user."'
    union all
    select r.category, 0 as amount,h.amount as deduction
    from hdxhead as h left join transnum as num on num.trno = h.trno 
    left join reqcategory as r on r.line = h.mpid and r.ispaymode =1 
    where  r.category  in ('cash','check') and  num.center = ? and date(h.dateid) = ? and h.createby = '".$user."'
    ) as h group by category";
    $data = $this->coreFunctions->opentable($qry,[$center,$dateid,$center,$dateid,$center,$dateid,$center,$dateid,$center,$dateid,$center,$dateid]);

    $totalcoll =0;
    $totaldep =0;
    $endingbal=0;
    $begbal =0;
    if($config['params']['dataparams']['begbal'] !=0){
      $begbal = $this->othersClass->sanitizekeyfield("amt",$config['params']['dataparams']['begbal']);
    }else{
      $begbal = $this->coreFunctions->getfieldvalue("eod","ifnull(endingbal,0)","date(dateid)<? and closeby = ?",[$dateid,$user],"dateid desc",true);
    }
   
    
    foreach ($data as $key => $value) {        
        $value->amount = $this->othersClass->sanitizekeyfield('amt',$value->amount);
        $value->deduction = $this->othersClass->sanitizekeyfield('amt',$value->deduction);
        $totalcoll = $totalcoll + $value->amount;
        $totaldep = $totaldep + $value->deduction;       
    }

    $endingbal = ($begbal + $totalcoll) - $totaldep;

    $ret['dateid']= $dateid;
    $ret['begbal'] = number_format($begbal,2);
    $ret['totalcoll'] = number_format($totalcoll,2);
    $ret['totaldep'] = number_format($totaldep,2);
    $ret['endingbal'] = number_format($endingbal,2);
    
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data],'data'=>$ret];
  }


 


} //end class
