<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewpdc
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'POSTDATED CHECKS';
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

    $clientid = $config['params']['clientid'];
    $companyid = $config['params']['companyid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;


    $action = 0;
    $checkdate = 1;
    $docno = 2;
    $checkno = 3;
    $db = 4;
    $cr = 5;
    $depodate = 6;
    $clearday = 7;
    $rem = 8;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'checkdate', 'docno', 'checkno', 'db', 'cr', 'depodate', 'clearday', 'rem']
      ]
    ];

    $stockbuttons = ['referencemodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = ['db', 'cr'];

    // 3 = db
    $obj[0][$this->gridname]['columns'][$db]['align'] = 'right';
    // 4 = cr
    $obj[0][$this->gridname]['columns'][$cr]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$depodate]['label'] = 'DS Date';
    $obj[0][$this->gridname]['columns'][$clearday]['label'] = 'Clear Date';


    if ($companyid != 39) { //not cbbsi
      $obj[0][$this->gridname]['columns'][$depodate]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$clearday]['type'] = 'coldel';
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

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'pdc');

    $fields = ['db'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['cr'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid, 0.0 as db, 0.0 as cr');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = $config['params']['dataparams']['dateid'];

    $qry = "select trno, doc, docno, checkno, checkdate, line,
        FORMAT(ifnull(db,0),2) as db,
        FORMAT(ifnull(cr,0),2) as cr,ifnull(rem,'') as rem, 'PDCTAB' as tabtype,depodate,clearday from (
          select glhead.doc,coa.alias,glhead.trno, glhead.docno, gldetail.checkno,
          left(gldetail.postdate,10) as checkdate, gldetail.line, gldetail.db,
          gldetail.cr,left(crledger.depodate,10) as depodate,case when crledger.depodate = null then concat(`gldetail`.`rem`) else concat(`gldetail`.`rem`,'  ',`deposit`.`docno`) end as rem,
          client.clientid,  ifnull(gldetail.clearday,'') as clearday
          from glhead 
          left join gldetail on gldetail.trno=glhead.trno  
          left join crledger on crledger.trno=gldetail.trno and crledger.line = gldetail.line
          left join client on client.clientid = gldetail.clientid 
          left join coa on coa.acnoid=gldetail.acnoid 
          left join cntnum on cntnum.trno=glhead.trno
          left join deposit on deposit.refx = crledger.trno and deposit.linex = crledger.line
          where  glhead.doc='CR' and cntnum.center='" . $center . "' and left(coa.alias,2)='cr' and client.clientid=$clientid and left(gldetail.postdate,10)>='$date'
          union all
          select lahead.doc,coa.alias,lahead.trno, lahead.docno, ladetail.checkno, left(ladetail.postdate,10) as checkdate,ladetail.line, ladetail.db as db,
          ladetail.cr as cr,null as depodate,ladetail.rem as rem,client.clientid, ifnull(ladetail.clearday,'') as clearday
          from lahead 
          left join ladetail on ladetail.trno=lahead.trno 
          left join client on client.client = ladetail.client
          left join cntnum on cntnum.trno=lahead.trno 
          left join coa on coa.acnoid=ladetail.acnoid 
          where lahead.doc='CR' and cntnum.center='" . $center . "' and  client.clientid=$clientid and left(coa.alias,2)='cr' and ladetail.refx=0 and ladetail.linex=0 
          and left(ladetail.postdate,10)>='$date'
        ) as customerpdc ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
