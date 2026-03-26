<?php

namespace App\Http\Classes\modules\operation;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\modules\crm\ld;
use Symfony\Component\VarDumper\VarDumper;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class othercharges
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER CHARGES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'chargesbilling';
  public $hhead = 'hchargesbilling';
  // public $prefix = 'ED';
  // public $tablelogs = 'payroll_log';
  // public $tablelogs_del = '';
  // private $stockselect;

  private $fields = [
    'amt', 'byear', 'bmonth', 'center', 'clientid', 'rem', 'isposted', 'clientname'
  ];
  //   private $fields = [
  //     'docno', 'dateid', 'empid', 'remarks', 'acno', 'amt', 'w1', 'w2', 'w3', 'w4', 'w5',
  //     'halt', 'priority', 'amortization', 'effdate', 'balance', 'pament', 'w13', 'acnoid'
  // ];
  // // 'remarks','acno','days','bal',
  // private $except = ['clientid', 'client'];
  // private $blnfields = ['w1', 'w2', 'w3', 'w4', 'w5','halt','w13'];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  public $issearchshow = false;
  public $showclosebtn = false;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'With Balance', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Without Balance', 'color' => 'primary']
  ];

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }


  public function getAttrib()
  {

    $attrib = array(
      'view' => 2556,
      'edit' => 773,
      'delete' => 2752,
      'post' => 2491,
      'additem' => 2045,
      'saveallentry' => 1847
    );

    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $acnoname = 5;
    $bal = 6;

    $getcols = ['action', 'listclient', 'byear', 'bmonth', 'amt'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    // ito kse ung sa doclisting n qry, after nito ung load head na
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.client', 'client.clientid'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select client.client,cb.byear,cb.bmonth,client.clientid
      from " . $this->head . " as cb
      left join client on client.clientid=cb.clientid
      where 1=1 " . $filtersearch;

    $data = $this->coreFunctions->opentable($qry);


    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;

    return [];
  } // createHeadbutton

  public function createTab($access, $config)
  {


    $action = 0;
    $description = 1;
    $amt = 2;


    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'description', 'amt'
      ]
    ]];

    $stockbuttons = ['delete', 'save'];
    // $stockbuttons=[];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'TENANTS';

    $obj[0][$this->gridname]['columns'][$description]['label'] = 'Billable Item';
    $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$description]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$description]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "NEW CHARGE";
    $obj[0]['action'] = "addcharge";
    $obj[0]['addedparams'] = ["clientid", "year", "bmonth"];
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = ['month', 'year'];
    $col1 = $this->fieldClass->create($fields);
    // sbccsenablealways
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', false);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month sbccsenablealways');
    data_set($col1, 'year.readonly', false);
    data_set($col1, 'month.name', 'bmonth');
    data_set($col1, 'year.name', 'byear');

    data_set($col1, 'create.label', 'REFRESH');

    $fields = ['client', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'client.label', 'Tenant');
    data_set($col2, 'client.lookupclass', 'tenant');
    data_set($col2, 'client.name', 'clientname');
    data_set($col2, 'refresh.action', 'load');


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function newclient($config)
  {
    
  }

  private function resetdata($client = '')
  {
    
  }

  public function loadheaddata($config)
  {
    
    $clientid = $config['params']['clientid'];

    if ($clientid == 0) {
      $clientid = $this->getlastclient();
    }

    //ito ata ung after sir ng doclisting
    $qry = "select client.client, ifnull(cb.byear,' ') as byear, ifnull(cb.bmonth,' ') as bmonth, ifnull(client.clientid,'0') as clientid,client.clientname
        from " . $this->head . " as cb
        left join client on client.clientid=cb.clientid
        where cb.clientid= ?
        ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      $stock = $this->openstock($clientid, $config);

      $msg = 'Data Fetched Success';

      return  ['head' => $head, 'status' => true, 'msg' => $msg, 'griddata' => ['inventory' => $stock],];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    } else {
      $data['docno'] = $head['client'];
      $head['docno'] = $head['client'];
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (isset($head[$key])) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    if ($isupdate) {
      if (substr($head['status'], 0, 1) != 'E') {
        return ['status' => false, 'msg' => "Can't Modified", 'clientid' => '0'];
      }

      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
      $clientid = $head['clientid'];
    } else {

      $data['status'] = "E";
      $data['balance'] = $data['amt'];
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);

      $balance = $head['balance'];
      if ($balance == 0) {
        $balance = $head['amt'];
      }

      $config['params']['doc'] = "EARNINGDEDUCTIONSETUP";
      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        "CREATE - NAME: " . $head['empname'] . ", ACCNT: " . $head['acnoname'] . ", AMT: " . $head['amt'] . ", BAL: " . $balance . ""
      );
    }
    
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select clientid as value 
      from " . $this->head . " 
      order by clientid ``DESC LIMIT 1");

    return $last_id;
  }

  public function openstock($trno, $config)
  {

    $qry = "select oc.line,oc.description,cb.amt,t.clientname from " . $this->head . " as cb
        left join ocharges as oc on cb.cline=oc.line
        left join client as t on t.clientid=cb.clientid
        where 1=1 and t.clientid = ?
        order by oc.line";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];

    $qry = "select line as value from standardtrans where line=?";
    $count = $this->coreFunctions->datareader($qry, [$clientid]);

    if ($count != '') {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  // -> print function
  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
