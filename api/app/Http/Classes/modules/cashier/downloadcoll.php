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



class downloadcoll
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DOWNLOADING UTILITY';
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
      'view' => 5093
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $columns = [
      'dateid',
      'checkdate',
      'checkinfo',
      'amount',
      'docno',
      'clientname',
      'rem'
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
  }

    $tab = [$this->gridname => [
      'gridcolumns' => $columns,  'totalfield' => 'amount',
    ]];
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = true;
    $obj[0][$this->gridname]['label'] = 'TRANSACTIONS';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$checkdate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$checkinfo]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['start', 'end'];

    $col1 = $this->fieldClass->create($fields);

    $fields = ['refresh','dlsales','dlpurchase']; //,'dlpurchret'

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'Load Data');
    data_set($col2, 'dlsales.action', 'download');
    data_set($col2, 'dlsales.label', 'Download Collections from WAIMS');
    data_set($col2, 'dlpurchase.action', 'dlclient');
     data_set($col2, 'dlpurchase.label', 'Download Customers from WAIMS');

    // $fields = ['dlpurchase'];
    // $col3 = $this->fieldClass->create($fields);
    // data_set($col3, 'dlpurchase.action', 'dlclient');
    // data_set($col3, 'dlpurchase.label', 'Download Customers from WAIMS');


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
    select adddate(left(now(),10),-360) as start,
      left(now(),10) as end
    ");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'download':
        return $this->downloadmcdx($config);
        break;
      case 'dlclient':
        return $this->downloadclient($config);
        break;
      case 'load':
        return $this->loaddata($config, $config['params']['dataparams']['start'], $config['params']['dataparams']['end']);
        break;
      
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loaddata($config, $start, $end)
  {
    $center = $config['params']['center'];
    $start = $this->othersClass->sbcdateformat($start);
    $end = $this->othersClass->sbcdateformat($end);
    
    $qry = "select h.doc,h.trno,num.dstrno,left(h.dateid,10) as dateid,left(h.checkdate,10) as checkdate,h.checkinfo,h.docno,h.clientid,h.clientname,h.rem,
    format(h.amount,2) as amount,h.yourref as crno,'' as orno, h.sicsino,h.drno,h.trnxtype,h.modeofpayment,num.center,c.client,h.createby,'' as bank, 0 as mpid
    from hmchead as h  left join transnum as num on num.trno = h.trno left join client as c on c.clientid = h.clientid 
    where num.isdownloaded =0 and num.center = ? and date(h.dateid) between ? and ?
    union all
    select h.doc,h.trno,0 as dstrno,left(h.dateid,10) as dateid,null as checkdate,h.checkinfo,h.docno,0 as clientid,'' as clientname,h.rem,
    format(h.amount,2) as amount,'' as crno,'' as orno,'' as sicsino,'' as drno,'' as trnxtype,'' as modeofpayment,num.center,'' as client,h.createby,
    coa.acnoname as bank, h.mpid
    from hdxhead as h  left join transnum as num on num.trno = h.trno
    left join coa on coa.acnoid = h.bank
    where num.isdownloaded =0 and num.center = ? and date(h.dateid) between ? and ?";

    $data = $this->coreFunctions->opentable($qry,[$center,$start,$end,$center,$start,$end],'mysql2');
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function downloadmcdx($config)
  {
    $date1 = $this->othersClass->sbcdateformat($config['params']['dataparams']['start']);
    $date2 =  $this->othersClass->sbcdateformat($config['params']['dataparams']['end']);
    $center = $config['params']['center'];

//----add checking/updating for dstrno ------------
//add checking ng unposted trans
    // $qry = "select d.trno,d.dateid,c.acnoname as bank from dxhead as d left join transnum as num on num.trno = d.trno left join coa as c on c.acnoid = d.bank where num.center=? and date(d.dateid) between ? and ? 
    // union all
    // select d.trno,d.dateid,c.acnoname as bank from hdxhead as d left join transnum as num on num.trno = d.trno left join coa as c on c.acnoid = d.bank where center = ? and date(d.dateid) between ? and ?";
    // $uds = $this->coreFunctions->opentable($qry,[$center,$date1,$date2,$center,$date1,$date2],'mysql2');
    // $uds = json_decode(json_encode($uds), true);
    // $this->coreFunctions->LogConsole($qry);
    // foreach($uds as $m => $u){
    //     $ctrno =  $this->coreFunctions->opentable("select trno from transnum where dstrno=?",[$uds[$m]['trno']],'mysql2');
    //     if(!empty($ctrno)){
    //       foreach($ctrno as $c=>$l){
    //         $this->coreFunctions->execqry("update tcoll set dstrno = ".$uds[$m]['trno'].",depodate ='".$uds[$m]['dateid']."',bank = '".$uds[$m]['bank']."' where trno = ". $ctrno[$c]->trno,'update');
    //       }
            
    //     }
    // }

    $qry = "select h.doc,h.trno,num.dstrno,left(h.dateid,10) as dateid,left(h.checkdate,10) as checkdate,h.checkinfo,h.docno,h.clientid,h.clientname,h.rem,
    format(h.amount,2) as amount,h.yourref as crno,'' as orno, h.sicsino,h.drno,h.trnxtype,h.modeofpayment,num.center,c.client,h.createby,'' as bank, 0 as mpid
    from hmchead as h  left join transnum as num on num.trno = h.trno left join client as c on c.clientid = h.clientid 
    where num.isdownloaded =0 and num.center = ? and date(h.dateid) between ? and ?
    union all
    select h.doc,h.trno,0 as dstrno,left(h.dateid,10) as dateid,null as checkdate,h.checkinfo,h.docno,0 as clientid,'' as clientname,h.rem,
    format(h.amount,2) as amount,'' as crno,'' as orno,'' as sicsino,'' as drno,'' as trnxtype,'' as modeofpayment,num.center,'' as client,h.createby,
    coa.acnoname as bank, h.mpid
    from hdxhead as h  left join transnum as num on num.trno = h.trno
    left join coa on coa.acnoid = h.bank
    where num.isdownloaded =0 and num.center = ? and date(h.dateid) between ? and ?";

    $data = $this->coreFunctions->opentable($qry,[$center,$date1,$date2,$center,$date1,$date2],'mysql2');
    $data = json_decode(json_encode($data), true);
    $d = [];
    $insert =[];
    ini_set('max_execution_time', 0);
    
    //downloading mc
    if (!empty($data)) {
      foreach ($data as $k => $v) {
        $this->coreFunctions->execqry("delete from tcoll where trno = ". $data[$k]['trno'],'delete');

        $d['trno']= $data[$k]['trno'];
        $d['docno']= $data[$k]['docno'];
        $d['center']= $data[$k]['center'];
        $d['doc']= $data[$k]['doc'];
        $d['dateid']= $data[$k]['dateid'];
        $d['checkdate']= $data[$k]['checkdate'];
        $d['checkinfo']= $data[$k]['checkinfo'];
        $d['client']= $data[$k]['client'];
        $d['clientname']= $data[$k]['clientname'];
        $d['createby']= $data[$k]['createby'];
        $d['amount']= $this->othersClass->sanitizekeyfield("amt",$data[$k]['amount']);
        $d['yourref']= $data[$k]['crno'];
        $d['sicsino']= $data[$k]['sicsino'];
        $d['drno']= $data[$k]['drno'];
        $d['dstrno']= $data[$k]['dstrno'];
        $d['bank']= $data[$k]['bank'];
        $d['mpid'] =  ($data[$k]['mpid'] == 0) ? $this->coreFunctions->getfieldvalue("reqcategory","line","category = '". $data[$k]['modeofpayment']."' and ispaymode =1") : $data[$k]['mpid'];
        
        // if($data[$k]['dstrno']!=0){
        //   $isposted = $this->othersClass->isposted2($data[$k]['dstrno'],"transnum",'mysql2');
        //   if($isposted == 1){
        //     $tbl = 'hdxhead';
        //   }else{
        //     $tbl = 'dxhead';
        //   }
        //   $depdate = $this->coreFunctions->datareader("select dateid as value from ".$tbl." where trno=?",[$data[$k]['dstrno']],'mysql2');
        //   $bank = $this->coreFunctions->datareader("select c.acnoname as value from ".$tbl." as d left join coa as c on c.acnoid = d.bank  where d.trno=?",[$data[$k]['dstrno']],'mysql2');
        //   $d['depodate'] = $depdate;
        //   $d['bank'] = $bank;
        // }       
        
        //get trnxid, purpose, mode base on reference of MC
        $reftrno = $this->coreFunctions->datareader("select ifnull(refx,0) as value from hmcdetail where trno=?",[$data[$k]['trno']],'mysql2');

        if($reftrno == 0){
            $d['ppid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'downpayment' and ispaytype =1");
            $d['trnxtid'] =0;
        }else{
            $qry = "select num.doc,h.docno,m.name as modeofsales from glhead as h left join cntnum as num on num.trno = h.trno left join mode_masterfile as m on m.line = h.modeofsales where num.trno =  ".$reftrno;
            $this->coreFunctions->LogConsole($qry);
            $ref = $this->coreFunctions->opentable($qry,[],'mysql2');
            if(!empty($ref)){
                if($ref[0]->doc == 'MJ'){                
                    switch (strtoupper($ref[0]->modeofsales)){
                        case 'CASH':
                            $d['trnxtid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'cash collection' and isttype =1");
                            $d['ppid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'CASH' and ispaytype =1");
                            break;
                        case 'INHOUSE INSTALLMENT':
                            $d['trnxtid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'Inhouse Collection' and isttype =1");
                            $d['ppid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'Monthly Payment' and ispaytype =1");
                            break;
                        default:
                            $d['trnxtid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'Bank Financing' and isttype =1");
                            $d['ppid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'Monthly Payment' and ispaytype =1");
                        break;
                    }
                    $d['trnxtype'] ='MC UNIT';
                }else{
                    $d['ppid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'spareparts' and ispaytype =1");
                    $d['trnxtid'] = $this->coreFunctions->getfieldvalue("reqcategory","line","category = 'Spareparts' and isttype =1");
                    $d['trnxtype'] ='SPAREPARTS';
                }
            }
        }

        $this->coreFunctions->LogConsole($qry);

        if($this->coreFunctions->sbcinsert("tcoll",$d) == 1){
            $qry="update transnum set isdownloaded = 1 where trno = ". $data[$k]['trno'];
            $this->coreFunctions->LogConsole($qry);
            $this->coreFunctions->execqry($qry,'update',[],'mysql2');
        }
        
      }
    }
    $this->loaddata($config,$date1,$date2);
    return ['status' => 'true', 'msg' => 'Successfully downloaded', 'action' => 'load'];
  }

  private function downloadclient(){
    $qry = "select c.clientid,c.client,c.clientname,c.addr,c.bday,cl.bplace,cl.citizenship,cl.civilstatus,cl.father,cl.mother,c.sex,c.position,c.tel2,c.accountid,c.terms,c.province,c.region,c.zipcode,c.status,
    c.agent,c.tin,c.email,c.iscustomer,c.isfp,cl.bplace,cl.citizenship,cl.civilstatus,cl.father,cl.mother,cl.height,cl.weight,cl.fname,cl.mname,cl.lname from client as c left join clientinfo as cl on cl.clientid = c.clientid where c.iscustomer =1 or c.isfp =1  and c.isdownloaded = 0";
    $uds = $this->coreFunctions->opentable($qry,[],'mysql2');
    $uds = json_decode(json_encode($uds), true);
    $this->coreFunctions->LogConsole($qry);
    $cl = [];
    $info = [];
    foreach($uds as $m => $u){
      $cl['client']=$uds[$m]['client'];
      $cl['clientname']=$uds[$m]['clientname'];
      $cl['addr'] =$uds[$m]['addr'];
      $cl['bday'] = $uds[$m]['bday'];
      $cl['sex'] = $uds[$m]['sex'];
      $cl['position'] = $uds[$m]['position'];
      $cl['tel2'] = $uds[$m]['tel2'];
      $cl['accountid'] = $uds[$m]['accountid'];
      $cl['terms'] = $uds[$m]['terms'];
      $cl['province'] =$uds[$m]['province'];
      $cl['region'] = $uds[$m]['region'];
      $cl['zipcode'] = $uds[$m]['zipcode'];
      $cl['status'] = $uds[$m]['status'];
      $cl['agent'] = $uds[$m]['agent'];
      $cl['tin'] =$uds[$m]['tin'];
      $cl['email'] = $uds[$m]['email'];
      $cl['iscustomer'] = $uds[$m]['iscustomer'];
      $cl['isfp'] = $uds[$m]['isfp'];

      $info['bplace'] = $uds[$m]['bplace'];
      $info['citizenship'] = $uds[$m]['citizenship'];
      $info['civilstatus'] = $uds[$m]['civilstatus'];
      $info['father'] = $uds[$m]['father'];
      $info['mother'] = $uds[$m]['mother'];
      $info['height'] = $uds[$m]['height'];
      $info['weight'] = $uds[$m]['weight'];
      $info['fname'] = $uds[$m]['fname'];
      $info['mname'] = $uds[$m]['mname'];
      $info['lname'] = $uds[$m]['lname'];

      $ins = $this->coreFunctions->insertGetId("client",$cl);
      if($ins !=0){
        $this->coreFunctions->execqry("update client set isdownloaded = 1 where clientid = ?","update",[$uds[$m]['clientid']],'mysql2');
        $info['clientid'] = $ins;
        $this->coreFunctions->sbcinsert("clientinfo",$info);
      }
      
      
    }
    return ['status' => 'true', 'msg' => 'Successfully downloaded', 'action' => 'load'];

  }






} //end class


