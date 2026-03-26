<?php

namespace App\Http\Classes\modules\customform;

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

class viewar
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ACCOUNT RECEIVABLE';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;height:100%;max-height:100%;';
  public $issearchshow = true;
  public $showclosebtn = true;



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
    $this->modulename = 'ACCOUNT RECEIVABLE - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);

    $action = 0;
    $status = 1;
    $dateid = 2;
    $due = 3;
    $receivedate = 4;
    $docno = 5;
    $db = 6;
    $cr = 7;
    $bal = 8;
    $ref = 9;
    $rem = 10;
    $krdoc = 11;
    $kadoc = 12;
    $rem1 = 13;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'due', 'receivedate', 'docno', 'db', 'cr', 'bal', 'ref', 'rem', 'krdoc', 'kadoc', 'rem1']]];

    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $companyid = $config['params']['companyid'];
    // 4 = checkdate
    if ($companyid == 17) { //unihome
      $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Doc no';
    }

    // 6 = db
    $obj[0][$this->gridname]['columns'][$db]['align'] = 'text-right';
    // 7 = cr
    $obj[0][$this->gridname]['columns'][$cr]['align'] = 'text-right';
    // 8 = bal
    $obj[0][$this->gridname]['columns'][$bal]['align'] = 'text-right';

    if ($companyid != 10) { //not afti
      $obj[0][$this->gridname]['columns'][$receivedate]['type'] = 'coldel';
    }

    if ($companyid != 39) { //not cbbsi
      $obj[0][$this->gridname]['columns'][$kadoc]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
    if ($config['params']['moduletype'] == 'ENROLLMENTENTRY') {
      $this->modulename  = 'HISTORY';
    }

    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'ar');

    $fields = [['db', 'cr']];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['bal'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {

    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='ViewAR' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($date == '') {
      $date = "DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
    } else {
      $date = "'" . $date . "'";
    }

    return $this->coreFunctions->opentable("select " . $date . " as dateid, 0.0 as db, 0.0 as cr,0.0 as bal");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10: //afti
        $qry = "select date_format(postdate,'%m/%d/%y') as postdate, trno, line, doc, docno, date_format(dateid,'%m/%d/%y') as 
          dateid, FORMAT(ifnull(db,2),2) as db,
          FORMAT(ifnull(cr,0),2) as cr,
          FORMAT(ifnull(balance,0),2) as bal,
          ref, agent, rem1,rem,status,krdoc,date_format(due,'%m/%d/%y') as due, 'ARTAB' as tabtype,receivedate from
          (select `cntnum`.`postdate`, `cntnum`.`doc` as `doc`,arledger.`docno`,`arledger`.`trno` as `trno`,`arledger`.`line` as `line`,
          `arledger`.`dateid` as `dateid`,`arledger`.`db`,`arledger`.`cr`,arledger.bal,
          `arledger`.`clientid` as `clientid`,`arledger`.`ref` as `ref`,`agent`.`client` as `agent`,
          (`detail`.`rem`) as `rem1`,(`head`.`rem`) as `rem`,((case when (`arledger`.`db` > 0) then 1 else -(1) end) * `arledger`.`bal`) as `balance`,
          0 as `fbal`,`head`.`ourref` as `reference`,'POSTED' as `status`,ifnull(`kr`.`docno`,'') as `krdoc`,case cntnum.doc when 'AR' then ifnull(`head`.`due`,'') else date_add(ifnull(dl.receivedate,head.dateid), interval terms.days day) end as `due`,ifnull(date_format(dl.receivedate,'%m/%d/%y'),'') as receivedate from ((((`arledger`
          left join `cntnum` on((`cntnum`.`trno` = `arledger`.`trno`))) left join `gldetail` as detail
          on(((`detail`.`trno` = `arledger`.`trno`) and (`detail`.`line` = `arledger`.`line`))))
          left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`))) left join `client` `agent`
          on((`agent`.`clientid` = `arledger`.`agentid`))) left join `transnum` as `kr` on `kr`.`trno` = `arledger`.`kr` 
          left join terms on terms.terms = head.terms
          left join delstatus as dl on dl.trno = head.trno
          where arledger.clientid= $clientid  and arledger.dateid>='$date'
          and cntnum.center = '$center'
          UNION ALL
          select '' as postdate, `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
          ifnull(sum(detail.ext),0) as db,0 as `cr`,ifnull(sum(detail.ext),0) as `bal`,
          `client`.`clientid` as `clientid`,'' as `ref`,head.agent as `agent`,`detail`.`rem` as `rem1`,`head`.`rem` as `rem`,
          sum(detail.ext) as `balance`,0 as `fbal`,'' as `reference`,'UNPOSTED' as `status`,'' as krdoc,ifnull(`head`.`due`,'') as `due`,'' as receivedate
          from `lahead` as head
          left join `lastock` as detail on `detail`.`trno` = `head`.`trno`
          left join `client` on `client`.`client` = `head`.`client`
          left join cntnum on cntnum.trno = head.trno
          where cntnum.doc = 'SJ' and client.clientid= $clientid and head.dateid>='$date' and cntnum.center = '$center' and detail.line is not null
          group by 
          head.doc,head.docno,head.trno,detail.line,head.dateid,client.clientid,head.agent,detail.rem,head.rem,head.due
          ) as t  order by dateid desc, docno";
        break;
      default:
        $qry = "select date_format(postdate,'%m/%d/%y') as postdate, trno, line, doc, docno, date_format(dateid,'%m/%d/%y') as 
          dateid, dateid as transdate, FORMAT(ifnull(db,2),2) as db,
          FORMAT(ifnull(cr,0),2) as cr,
          FORMAT(ifnull(balance,0),2) as bal,
          ref, agent, rem1,rem,status,krdoc,kadoc,due, 'ARTAB' as tabtype from
          (
            select cntnum.postdate, cntnum.doc as doc,arledger.docno,arledger.trno as trno,arledger.line as line,
            arledger.dateid as dateid,arledger.db,arledger.cr,arledger.bal,
            arledger.clientid as clientid,arledger.ref as ref,agent.client as agent,
            detail.rem as rem1,head.rem as rem,
            ((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,
            0 as fbal,head.ourref as reference,'POSTED' as status,
            ifnull(kr.docno,'') as krdoc,
            ifnull(ka.docno,'') as kadoc,
            ifnull(head.due,'') as due 
            from arledger
            left join cntnum on cntnum.trno = arledger.trno
            left join gldetail as detail on detail.trno = arledger.trno and detail.line = arledger.line
            left join glhead as head on head.trno = cntnum.trno
            left join client agent on agent.clientid = arledger.agentid
            left join transnum as kr on kr.trno = arledger.kr 
            left join transnum as ka on ka.trno = arledger.ka 
            where arledger.clientid= $clientid  and arledger.dateid>='$date'
            and cntnum.center = '$center'
            UNION ALL
            select '' as postdate, `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
            ifnull(sum(detail.ext),0) as db,0 as `cr`,ifnull(sum(detail.ext),0) as `bal`,
            `client`.`clientid` as `clientid`,'' as `ref`,head.agent as `agent`,`detail`.`rem` as `rem1`,`head`.`rem` as `rem`,
            sum(detail.ext) as `balance`,0 as `fbal`,'' as `reference`,'UNPOSTED' as `status`,'' as krdoc,'' as kadoc,ifnull(`head`.`due`,'') as `due`
            from `lahead` as head
            left join `lastock` as detail on `detail`.`trno` = `head`.`trno`
            left join `client` on `client`.`client` = `head`.`client`
            left join cntnum on cntnum.trno = head.trno
            where cntnum.doc = 'SJ' and client.clientid= $clientid and head.dateid>='$date' and cntnum.center = '$center' and detail.line is not null
            group by 
            head.doc,head.docno,head.trno,detail.line,head.dateid,client.clientid,head.agent,detail.rem,head.rem,head.due
          ) as t  order by transdate desc, docno";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);

    $profile = ['doc' => 'ViewAR', 'psection' => 'StartDate', 'pvalue' => $date, 'puser' => $config['params']['user']];
    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='ViewAR' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($date == '') {
      $this->coreFunctions->sbcinsert("profile", $profile);
    } else {
      $this->coreFunctions->sbcupdate("profile", $profile, ['doc' => 'ViewAR', 'psection' => 'StartDate', 'puser' => $config['params']['user']]);
    }


    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'qry' => $qry];
  }
} //end class
