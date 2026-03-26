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

class viewap
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ACCOUNT PAYABLE';
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
    $companyid = $config['params']['companyid'];
    $this->modulename = 'ACCOUNT PAYABLE - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);

    switch ($companyid) {
      case 28: //xcomp
        $action = 0;
        $status = 1;
        $dateid = 2;
        $postdate = 3;
        $docno = 4;
        $db = 5;
        $cr = 6;
        $bal = 7;
        $ref = 8;
        $rem = 9;
        $ourref = 10;
        $rem1 = 11;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'postdate', 'docno', 'db', 'cr', 'bal', 'ref', 'rem', 'ourref', 'rem1']]];
        break;

      case 39: //cbbsi
        $action = 0;
        $status = 1;
        $dateid = 2;
        $postdate = 3;
        $docno = 4;
        $db = 5;
        $cr = 6;
        $bal = 7;
        $ref = 8;
        $rem = 9;
        $pydocno = 10;
        $rem1 = 11;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'postdate', 'docno', 'db', 'cr', 'bal', 'ref', 'rem', 'pydocno', 'ourref', 'yourref', 'rem1']]];
        break;
      case 43: //mighty
      case 17: //unihome
        $action = 0;
        $status = 1;
        $dateid = 2;
        $postdate = 3;
        $docno = 4;
        $db = 5;
        $cr = 6;
        $bal = 7;
        $ref = 8;
        $ourref = 9;
        $yourref = 10;
        $rem = 11;
        $rem1 = 12;
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'postdate', 'docno', 'db', 'cr', 'bal', 'ref', 'ourref', 'yourref', 'rem', 'rem1']]];
        break;
      default:
        $action = 0;
        $status = 1;
        $dateid = 2;
        $postdate = 3;
        $docno = 4;
        $db = 5;
        $cr = 6;
        $bal = 7;
        $ref = 8;
        $rem = 9;
        $rem1 = 10;

        $tab = [$this->gridname => ['gridcolumns' => ['action', 'status', 'dateid', 'postdate', 'docno', 'db', 'cr', 'bal', 'ref', 'rem', 'rem1']]];
        break;
    }


    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$status]['style'] = 'text-align:center;width:100px;whiteSpace: normal;min-width:100px';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'text-align:center;width:100px;whiteSpace: normal;min-width:100px';
    $obj[0][$this->gridname]['columns'][$postdate]['style'] = 'text-align:center;width:100px;whiteSpace: normal;min-width:100px';
    $obj[0][$this->gridname]['columns'][$postdate]['label'] = 'Checkdate';
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'text-align:center;width:150px;whiteSpace: normal;min-width:150px';
    $obj[0][$this->gridname]['columns'][$db]['style'] = 'text-align:right;width:150px;whiteSpace: normal;min-width:150px';
    $obj[0][$this->gridname]['columns'][$cr]['style'] = 'text-align:right;width:150px;whiteSpace: normal;min-width:150px';
    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'text-align:right;width:150px;whiteSpace: normal;min-width:150px';

    if ($companyid == 28) { //xcomp
      $obj[0][$this->gridname]['columns'][$ourref]['style'] = 'text-align:center;width:100px;whiteSpace: normal;min-width:100px';
    }
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
    data_set($col1, 'dateid.label', 'Start Date');

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'ap');

    $fields = [['db', 'cr']];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['bal'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='ViewAP' and psection='StartDate' and puser=?", [$config['params']['user']]);
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
    $companyid = $config['params']['companyid'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $field = '';
    $dateidfield = ",apledger.dateid";
    $docnofield = '';
    $empdocnofield = '';
    $hjoins = '';
    $add1 = '';
    $add2 = '';
    if ($companyid == 39) { //cbbsi
      $field = ',pydocno,ourref,yourref';
      $dateidfield = ",head.dateid";
      $docnofield = ',py.docno as pydocno';
      $empdocnofield = ",'' as pydocno";
      $hjoins = 'left join hpyhead as py on py.trno=apledger.py';
      $add1 = ",(case when cntnum.doc = 'PY' then  py.ourref else head.ourref end) as ourref,
                (case when cntnum.doc = 'PY' then  py.yourref else head.yourref end) as yourref";
      $add2 = ",'' as ourref, '' as yourref";
    }

    if ($companyid == 43 || $companyid == 17) { //mighty and unihome
      $field = ',ourref,yourref';
      $add1 = ",head.ourref,head.yourref";
      $add2 = ",'' as ourref, '' as yourref";
    }

    $with_sn_qry = "";
    $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);
    if ($companyid <> 39) { //not cbbsi
      if (!$suppinvoice) {
        $with_sn_qry = "
        union all
        select '' as postdate, head.doc as doc,head.docno,head.trno as trno,detail.line as line,head.dateid as dateid $empdocnofield , 
        0 as db,FORMAT(ifnull(sum(detail.ext),0),2) as cr,FORMAT(ifnull(sum(detail.ext),0),2) as bal,
        client.clientid as clientid,'' as ref,'' as agent,detail.rem as rem,
        sum(detail.ext) as balance,0 as fbal,'' as reference,'UNPOSTED' as status, head.dateid as sortdate $add2
        from lahead as head
        left join lastock as detail on detail.trno = head.trno
        left join client on client.client = head.client
        left join cntnum on cntnum.trno = head.trno
        where client.clientid= $clientid and head.dateid>='$date' and cntnum.center = '$center' and cntnum.doc = 'RR'
        group by
        head.doc,head.docno,head.trno,detail.line,head.dateid,client.clientid,detail.rem";
      }
    }

    $qry = "
    select date_format(postdate,'%m/%d/%y') as postdate, trno, line, doc, docno,
    date_format(dateid,'%m/%d/%y') as dateid, 
    FORMAT(ifnull(db,0),2) as db, 
    FORMAT(ifnull(cr,0),2) as cr,
    FORMAT(ifnull(balance,0),2) as bal,
    ref, rem , status, 'APTAB' as tabtype $field
    from (
        select detail.postdate, cntnum.doc as doc,apledger.docno,apledger.trno as trno,
        apledger.line as line $dateidfield $docnofield,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,
        (detail.rem) as rem,((case when (apledger.cr > 0) then 1 else -(1) end) * apledger.bal) as balance,
        0 as fbal,head.ourref as reference,'POSTED' as status, apledger.dateid as sortdate $add1
        from apledger
        left join cntnum on cntnum.trno = apledger.trno
        left join gldetail as detail on detail.trno = apledger.trno and detail.line = apledger.line
        left join glhead as head on head.trno = cntnum.trno
        $hjoins
        where apledger.clientid= $clientid  and apledger.dateid>='$date'
        and cntnum.center = '$center'
        $with_sn_qry

    ) as t
    order by sortdate desc,docno desc";

    $data = $this->coreFunctions->opentable($qry);

    $profile = ['doc' => 'ViewAP', 'psection' => 'StartDate', 'pvalue' => $date, 'puser' => $config['params']['user']];
    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='ViewAP' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($date == '') {
      $this->coreFunctions->sbcinsert("profile", $profile);
    } else {
      $this->coreFunctions->sbcupdate("profile", $profile, ['doc' => 'ViewAP', 'psection' => 'StartDate', 'puser' => $config['params']['user']]);
    }

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
