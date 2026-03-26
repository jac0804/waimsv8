<?php

namespace App\Http\Classes\modules\dashboard;

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

class hrisoverview
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DAILY NOTIF2';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;

  private $config = [];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['status', 'dateid', 'docno', 'db', 'cr', 'bal', 'ref', 'rem', 'rem1']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][3]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][4]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][5]['align'] = 'right';
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
    return [];
  }

  public function paramsdata($config)
  {
    $id = $config['params']['addedparams']['id'];
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid, 0.0 as db, 0.0 as cr,0.0 as bal,? as id, 0 as classid', [$id]);
  }

  public function data()
  {
    return [];
  }

  public function execute()
  {
    if (isset($this->config['allowlogin'])) {
      if (!$this->config['allowlogin']) {
        return response()->json(['status' => 'ipdenied', 'msg' => 'Sorry, Please contact your Network Administrator', 'xx' => $this->config], 200);
      }
    }

    return response()->json($this->config['return'], 200);
  } // end function

  public function loadcustomform($config)
  {
    $this->config = $config;
    // switch ($config['params']['action']) {
    //   case 'loadrr':
    //     $this->config['return'] = $this->loaddata($config);
    //     break;
    // }

    $this->config['return'] = $this->loaddata($config);
    return $this;
  }

  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($this->othersClass->getCurrentDate()));
    $classid = 'posted';
    switch ($classid) {
      case 'posted':
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
            left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`)))) where cntnum.doc='RR' and apledger.bal>0 and apledger.dateid>='$date'
            and cntnum.center = '$center'
            ) as t  order by dateid desc,docno";
        break;
      case 'unposted':
        $qry = "select trno, doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 
            FORMAT(sum(db),2) as db, 
            FORMAT(sum(cr),2) as cr,
            FORMAT(sum(bal),2) as bal,
            ref, rem, status from (
            select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
            0 as `db`,ifnull(sum(detail.ext),0) as `cr`,ifnull(sum(detail.ext),0) as `bal`,
            `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
            sum(detail.ext) as `balance`,0 as `fbal`,'' as `reference`,'UNPOSTED' as `status`
            from `lahead` as head
            left join `lastock` as detail on `detail`.`trno` = `head`.`trno`
            left join `client` on `client`.`client` = `head`.`client`
            left join cntnum on cntnum.trno = head.trno 
            where cntnum.doc = 'RR' and head.dateid>='$date' and cntnum.center = '$center'
            group by head.docno,head.doc,head.trno,detail.line,head.dateid,client.clientid,detail.rem) as t  group by trno,doc,docno,dateid ,ref,rem,status order by dateid desc,docno";
        break;
    }


    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
