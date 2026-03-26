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

class othercharges_ledgdergrid
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
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'bmonth',
    'byear', 'clientid', 'amount',
    'remarks', 'cline'
  ];
  private $except = [];
  private $blnfields = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;
  private $reporter;


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
      'deleteitem' => 2752,
      'post' => 2491,
      'additem' => 2045,
      'saveallentry' => 1847
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'client', 'clientname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[2]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[1]['label'] = 'Tenant Code';
    $cols[2]['label'] = 'Tenant Name';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $emplvl = $this->othersClass->checksecuritylevel($config, true);

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['c. clientid', 'c.client', 'c.clientname'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "
        select
        c. clientid,
        c.client, c.clientname,monthname(now()) as month,year(now()) as byear        
        from client as c 
        where c.istenant=1 and c.isinactive=0 " . $filtersearch . "
        order by clientname";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      // 'new',
      'save',
      // 'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'mallentry', 'lookupclass' => 'entryothercharges', 'label' => 'CHARGES']];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
    $fields = ['client', 'clientname', 'month', 'byear'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.required', false);
    data_set($col1, 'client.label', 'Tenant');
    data_set($col1, 'client.lookupclass', 'tenant');
    data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');
    data_set($col1, 'year.readonly', false);
    data_set($col1, 'year.name', 'byear');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['clientname'] = '';
    $data[0]['month'] = '';
    $data[0]['byear'] = '0';
    return $data;
  }


  public function loadheaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $qryselect = "
        select
          clientid,
          client.client,
          clientname,monthname(now()) as month,year(now()) as byear
        ";

    $qry = $qryselect . " from
        client 
        where clientid = ?";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      //$stock = $this->openstock($clientid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $config['params']['addedparams'] = $head;

      return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head = $this->resetdata();
      return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];

    $data = [];

    $clientid = 0;
    $msg = "";

    if ($head['dateeffect'] == 'Invalid date') {
      return ['status' => false, 'msg' => 'Invalid effectivity date', 'clientid' => $clientid];
    };

    foreach ($this->fields as $key) {
      if (isset($head[$key])) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }

    $empid      = $head['clientid'];
    $effdate    = date('Y-m-d', strtotime($head['dateeffect']));
    $basicrate  = $head['basicrate'];
    $type = $this->coreFunctions->datareader("select ifnull(classrate, '') as value from employee where empid = '" . $empid . "'");

    if ($type == '') {
      return ['status' => false, 'msg' => 'Please setup CLASS RATE first.', 'clientid' => $clientid];
    }

    if ($basicrate != 0) {
      $sql = "update ratesetup  set dateend='" . $effdate . "'
            where empid='" . $empid . "' and date(dateend)='9999-12-31'";
      $sqlResult = $this->coreFunctions->execqry($sql, "update");
    }

    $data['type'] = $type;
    $data['dateid'] = date('Y-m-d');
    $data['dateend'] = '9999-12-31';

    $clientid = $this->coreFunctions->insertGetId($this->head, $data);
    if ($clientid != 0) {
      $clientid = $empid;

      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $head['client'] . ' - ' . $head['clientname'] . ' - ' . 'REMARKS: ' . $head['remarks']
      );
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    return '';
  }
  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, description from jobtdesc where trno=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$clientid]);
    return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  // -> print function
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  // public function reportsetup($config)
  // {
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  // }

  // public function reportdata($config)
  // {
  //   $data = $this->report_default_query($config);
  //   $str = $this->rpt_ratesetup_masterfile_layout($data, $config);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  // public function createreportfilter()
  // {
  //   $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
  //   $col1 = $this->fieldClass->create($fields);
  //   return array('col1' => $col1);
  // }

  // public function reportparamsdata($config)
  // {
  //   return $this->coreFunctions->opentable(
  //     "select
  //       'default' as print,
  //       '' as prepared,
  //       '' as approved,
  //       '' as received
  //       "
  //   );
  // }

  // private function report_default_query($config)
  // {
  //   $trno = $config['params']['dataid'];
  //   $query = "select e.empid, cl.client as empcode,
  //               CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
  //               r.remarks, r.basicrate, date(r.dateeffect) as dateeffect, date(r.dateend) as dateend, r.type
  //               from employee as e
  //               left join ratesetup as r ON r.empid = e.empid
  //               left join client as cl on cl.clientid = e.empid
  //               where e.empid = '$trno'
  //               order by e.empid";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // } //end fn


  // private function rpt_default_header($data, $filters)
  // {

  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $companyid);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center, $username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('RATE SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
  //   $str .= $this->reporter->col('Employee Code:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col($data[0]['empcode'], '680', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
  //   $str .= $this->reporter->col('Employee Name:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col($data[0]['empname'], '680', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Date Effect', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Date End', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Basic Rate', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Type', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
  //   $str .= $this->reporter->endrow();
  //   return $str;
  // }

  // private function rpt_ratesetup_masterfile_layout($data, $filters)
  // {
  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $companyid);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $count = 35;
  //   $page = 35;

  //   $str .= $this->reporter->beginreport();
  //   $str .= $this->rpt_default_header($data, $filters);
  //   $totalext = 0;
  //   for ($i = 0; $i < count($data); $i++) {
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->addline();
  //     $str .= $this->reporter->col($data[$i]['dateeffect'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['dateend'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col(number_format($data[$i]['basicrate'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['type'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     // $str .= $this->reporter->col($data[$i]['rate'],'300',null,false,$border,'','L',$font,$fontsize,'','','3px');
  //     $str .= $this->reporter->endrow();

  //     if ($this->reporter->linecounter == $page) {
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();
  //       $str .= $this->rpt_default_header($data, $filters);
  //       $str .= $this->reporter->printline();
  //       $page = $page + $count;
  //     }
  //   }

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();
  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->endreport();
  //   return $str;
  // } //end fn
























} //end class
