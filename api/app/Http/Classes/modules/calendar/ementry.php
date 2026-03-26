<?php

namespace App\Http\Classes\modules\calendar;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\htbuttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class ementry
{
  private $htbtnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Data Entry';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;



  public function __construct()
  {
    $this->htbtnClass = new htbuttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array('view' => 169);
    return $attrib;
  }

  public function createHeadbutton($config)
  {
    $btns = [];
    $buttons = $this->htbtnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($config)
  {
    $tab = [
      'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryattendancestudents', 'label' => 'STUDENTS']
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['tableentry']['addedparams'] = ['date' => $config['params']['timestamp']['date']];
    // $tab = [$this->gridname => ['gridcolumns' => ['action', 'client', 'attendancetype', 'attendancecolor']]];
    // $stockbuttons = [];
    // $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['label'] = 'STUDENTS';
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

    // $fields = ['docno','client','clientname','shipto'];
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    // $fields = [['dateid','terms'],'due','dwhname'];
    $col2 = $this->fieldClass->create($fields);

    // $fields = [['yourref','ourref'],['cur','forex'],'rem'];
    $col3 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
  }

  public function openstock()
  {

  }


  public function data()
  {
    $qryselect = "select 
    num.center,
    head.trno, 
    head.docno,
    client.client,
    head.terms,
    head.cur,
    head.forex,
    head.yourref,
    head.ourref,
    left(head.dateid,10) as dateid, 
    head.clientname,
    head.address, 
    head.shipto, 
    date_format(head.createdate,'%Y-%m-%d') as createdate,
    head.rem,
    head.agent, 
    agent.clientname as agentname,
    head.wh as wh,
    warehouse.clientname as whname,
    '' as dwhname, 
    left(head.due,10) as due, 
    client.groupid  ";

    $qry = $qryselect . " from pohead as head
    left join transnum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as warehouse on warehouse.client = head.wh
    left join client as agent on agent.client = head.agent
    where head.trno = ? 
    union all " . $qryselect . " from hpohead as head
    left join transnum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as warehouse on warehouse.client = head.wh
    left join client as agent on agent.client = head.agent
      where head.trno = ? ";

    $head = $this->coreFunctions->opentable($qry, [49, 49]);
    return $head;
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = $config['params']['dataparams']['dateid'];

    $qry = "select trno, line, doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 
            FORMAT(db,2) as db, 
            FORMAT(cr,2) as cr,
            FORMAT(bal,2) as bal,
            ref, rem, status from (select `cntnum`.`doc` as `doc`,apledger.`docno`,`apledger`.`trno` as `trno`,
            `apledger`.`line` as `line`,
            `apledger`.`dateid` as `dateid`,`apledger`.`db` as `db`,`apledger`.`cr` as `cr`,apledger.bal,
            `apledger`.`clientid` as `clientid`,`apledger`.`ref` as `ref`,'' as agent,
            (`detail`.`rem`) as `rem`,((case when (`apledger`.`cr` > 0) then 1 else -(1) end) * `apledger`.`bal`) as `balance`,
            0 as `fbal`,`head`.`ourref` as `reference`,'POSTED' as `status` from ((((`apledger`
            left join `cntnum` on((`cntnum`.`trno` = `apledger`.`trno`))) left join `gldetail` as detail
            on(((`detail`.`trno` = `apledger`.`trno`) and (`detail`.`line` = `apledger`.`line`))))
            left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`)))) where apledger.clientid= $clientid  and apledger.dateid>='$date'
            and cntnum.center = '$center'
            union all
            select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
            0 as `db`,sum(detail.ext) as `cr`,sum(detail.ext) as `bal`,
            `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
            sum(detail.ext) as `balance`,0 as `fbal`,'' as `reference`,'UNPOSTED' as `status`
            from `lahead` as head
            left join `lastock` as detail on `detail`.`trno` = `head`.`trno`
            left join `client` on `client`.`client` = `head`.`client`
            left join cntnum on cntnum.trno = head.trno 
            where cntnum.doc = 'RR' and client.clientid= $clientid and head.dateid>='$date' and cntnum.center = '$center'
            group by head.docno) as t  order by dateid desc,docno";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }


  public function headtablestatus($config)
  {
    return ['status' => true, 'msg' => 'ok', 'data' => []];
  }
} //end class
