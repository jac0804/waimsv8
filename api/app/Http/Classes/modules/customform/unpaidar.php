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

class unpaidar
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'UNPAID AR/AP';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
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
    $this->modulename = 'UNPAID AR/AP - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);
    $company = $config['params']['companyid'];
    $yourref = '';
    switch ($company) {
      case 28: //xcomp
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $due = 3;
        $bal = 4;
        $yourref = 5;
        $ref = 6;
        $rem = 7;
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'dateid', 'due', 'bal', 'yourref', 'ref', 'rem']]];
        break;

      default:
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $due = 3;
        $bal = 4;
        $ref = 5;
        $rem = 6;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'dateid', 'due', 'bal', 'ref', 'rem']]];
        break;
    }

    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$bal]['align'] = 'text-left';

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$due]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    if ($company == 28) $obj[0][$this->gridname]['columns'][$yourref]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
    $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:120px;whiteSpace: normal;min-width:120px; text-align:left;';
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

    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'ar');

    $fields = ['bal'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $date = date('Y-m-d', strtotime($this->othersClass->getCurrentTimeStamp()));

    return $this->coreFunctions->opentable('select "' . $date . '" as dateid, 0.0 as bal');
  }

  public function data($config)
  {

    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $qry = "select date_format(postdate,'%m/%d/%y') as postdate, trno, line, doc, docno,
    date_format(dateid,'%m/%d/%y') as dateid, date_format(due,'%m/%d/%y') as due,
    FORMAT(ifnull(db,2),2) as db,
    FORMAT(ifnull(cr,0),2) as cr,
    FORMAT(ifnull(balance,0),2) as bal,
    ref, agent,rem,status, 'UNPAIDTAB' as tabtype,yourref from
    (
        select head.due,cntnum.postdate, cntnum.doc as doc,arledger.docno,arledger.trno as trno,arledger.line as line,
        arledger.dateid as dateid,arledger.db,arledger.cr,arledger.bal,
        arledger.clientid as clientid,arledger.ref as ref,agent.client as agent, (head.rem) as rem,((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,
        0 as fbal,head.ourref as reference,'POSTED' as status,head.yourref
        from arledger
        left join cntnum on cntnum.trno = arledger.trno 
        left join gldetail as detail on detail.trno = arledger.trno and detail.line = arledger.line
        left join glhead as head on head.trno = cntnum.trno 
        left join client agent on agent.clientid = arledger.agentid
        where arledger.clientid= $clientid and date(arledger.dateid) <= '$date'
        and cntnum.center = '$center' and arledger.bal <> 0
        union all
        select head.due,cntnum.postdate, cntnum.doc as doc,apledger.docno,apledger.trno as trno,
        apledger.line as line,
        apledger.dateid as dateid,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,
        (detail.rem) as rem,((case when (apledger.cr > 0) then 1 else -(1) end) * apledger.bal) as balance,
        0 as fbal,head.ourref as reference,'POSTED' as status ,head.yourref
        from apledger
        left join cntnum on cntnum.trno = apledger.trno 
        left join gldetail as detail on detail.trno = apledger.trno and detail.line = apledger.line
        left join glhead as head on head.trno = cntnum.trno
        where apledger.clientid= $clientid and date(apledger.dateid) <= '$date' and apledger.bal <> 0
        and cntnum.center = '$center'
    ) as t  order by dateid desc, docno";

    $data = $this->coreFunctions->opentable($qry);


    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
