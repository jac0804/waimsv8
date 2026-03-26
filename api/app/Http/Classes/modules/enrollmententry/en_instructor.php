<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_instructor
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INSTRUCTOR';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  private $head = 'client';
  private $headOther = 'en_instructor';
  public $prefix = 'IN';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;
  public $style = 'width:100%;';
  private $fields = ['client', 'clientname', 'addr', 'isinstructor'];
  private $otherfields = ['instructorid', 'deptcode', 'department', 'telno', 'callname', 'deancode', 'deanname', 'rank', 'levels'];
  private $except = ['clientid', 'instructorid'];
  private $blnfields = ['isinstructor'];
  private $enrollmentlookup;
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
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
    $this->enrollmentlookup = new enrollmentlookup;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1727,
      'edit' => 1721,
      'new' => 1722,
      'save' => 1723,
      'change' => 1724,
      'delete' => 1725,
      'print' => 1726,
      'load' => 917,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.clientid', 'client.client', 'client.clientname', 'client.addr'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr from client where isinstructor =1 " . $filtersearch . "
     order by client";

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

  public function createTab($config)
  {
    $tab = [];

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
    //'deptcode', 'department', 'address', 'telno', 'callname', 'deancode', 'deanname', 'rank', 'levels'
    $fields = ['client', 'clientname', 'addr', ['deptcode', 'department']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Instructor Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'instructor');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'clientname.type', 'cinput');
    data_set($col1, 'addr.type', 'cinput');

    data_set($col1, 'deptcode.lookupclass', 'insdeptlookup');

    $fields = ['telno', 'callname', ['deancode', 'deanname'], ['rank', 'levels']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'telno.label', 'Tel No.');

    data_set($col2, 'telno.type', 'cinput');
    data_set($col2, 'callname.type', 'cinput');
    data_set($col2, 'rank.type', 'cinput');

    data_set($col2, 'levels.class', 'cslevels sbccsreadonly');

    return array('col1' => $col1, 'col2' => $col2);
  }


  public function newclient($config)
  {
    $data = [];
    $data[0]['line'] = 0;
    $data[0]['instructorid'] = 0;
    $data[0]['isinstructor'] = 1;
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['clientname'] = '';
    $data[0]['department'] = '';
    $data[0]['deptcode'] = '';
    $data[0]['addr'] = '';
    $data[0]['telno'] = '';
    $data[0]['callname'] = '';
    $data[0]['deancode'] = '';
    $data[0]['deanname'] = '';
    $data[0]['rank'] = '';
    $data[0]['levels'] = '';
    $data[0]['bgcolor'] = 'bg-blue-2';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from " . $this->head . " where  isinstructor=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client,clientid," . $this->headOther . ".instructorid,'' as ddeptname";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',' . $this->head . '.' . $value;
    }

    foreach ($this->otherfields as $key => $value) {
      $fields = $fields . ',' . $this->headOther . '.' . $value;
    }

    $qryselect = "select " . $fields;

    $qry = $qryselect . " from " . $this->head . " left join " . $this->headOther . " on " . $this->headOther . ".instructorid = " . $this->head . ".clientid 
        where " . $this->head . ".isinstructor=1 and " . $this->head . ".clientid = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head[0]['instructorid'] = 0;
      $head[0]['clientid'] = 0;
      $head[0]['empcode'] = '';
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where isinstructor =1  order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isinstructor =1  and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $dataOther = [];
    if ($isupdate) {
      unset($this->fields['client']);
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
    foreach ($this->otherfields as $key) {
      if (isset($head[$key])) {
        $dataOther[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key]);
        } //end if  
      }
    }


    if ($isupdate) {
      $dataOther['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $dataOther['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['clientid' => $head['instructorid']]);
      $exist = $this->coreFunctions->getfieldvalue($this->headOther, "instructorid", "instructorid=?", [$head['instructorid']]);
      if (floatval($exist) != 0) {
        $this->coreFunctions->sbcupdate($this->headOther, $dataOther, ['instructorid' => $head['instructorid']]);
      } else {
        $dataOther['instructorid'] = $head['instructorid'];
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
      }

      $clientid = $head['instructorid'];
      $instructorid = $head['instructorid'];
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);
      if ($clientid) {
        $dataOther['instructorid'] = $clientid;
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
      }
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $data['clientname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $qry = "select value from (
      select trno as value from en_scsubject where instructorid=".$clientid." union all
      select trno as value from en_sosubject where instructorid=".$clientid." union all
      select trno as value from en_sjsubject where instructorid=".$clientid." union all
      select trno as value from en_glsubject where instructorid=".$clientid." union all
      select trno as value from en_adsubject where instructorid=".$clientid." union all
      select trno as value from en_athead where adviserid=".$clientid." union all
      select trno as value from en_glhead where adviserid=".$clientid." union all
      select trno as value from en_gehead where adviserid=".$clientid." union all
      select trno as value from en_gshead where adviserid=".$clientid." union all
      select trno as value from en_srchead where adviserid=".$clientid."
    ) as v";
    $exist = $this->coreFunctions->datareader($qry);

    if (floatval($exist) != 0) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Unable to delete,already has transaction...'];
    }

    $this->coreFunctions->execqry('delete from en_instructor where instructorid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);

    $qry = "select clientid as value from client where clientid<? and isemployee=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);

    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  private function report_default_query($trno)
  {
    $query = "
      select
      cl.client, 
      cl.clientname,
      ins.instructorid,
      ins.deptcode,
      ins.department,
      ins.telno,
      ins.callname,
      ins.deancode,
      ins.deanname,
      ins.rank,
      ins.levels
      from en_instructor as ins
      left join client as cl on ins.instructorid = cl.clientid
      where ins.instructorid = '" . $trno . "'
    ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  // public function reportdata($config)
  // {
  //   $data = $this->report_default_query($config['params']['dataid']);
  //   $str = $this->reportplotting($config, $data);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  public function reportdata($config)
  {
    

    $data = $this->report_default_query($config['params']['dataid']);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->reportplotting($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_reportplotting($config, $data);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function PDF_header($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];
    $companyid = $filters['params']['companyid'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    if ($companyid == 3) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, 'INSTRUCTOR REPORT ', '', 'L', false);

    PDF::SetFont($font, '', 9);
    // PDF::MultiCell(800, 20, 'Applicant Name :' . '(' . (isset($data[0]['client']) ? $data[0]['client'] : '') . ')' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(70, 20, 'Name:', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(730, 20, '(' . (isset($data[0]['client']) ? $data[0]['client'] : '') . ') ' .  (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(70, 20, 'Department :', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(730, 20, (isset($data[0]['deptcode']) ? '('.$data[0]['deptcode'].')' : '') .  (isset($data[0]['department']) ? $data[0]['department'] : ''), '', 'L', false);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(70, 20, 'Dean :', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(730, 20, (isset($data[0]['deancode']) ? '('.$data[0]['deancode'].')' : '') .  (isset($data[0]['deanname']) ? $data[0]['deanname'] : ''), '', 'L', false);

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(800, 20, 'Dean :' .'(' . (isset($data[0]['deancode']) ? $data[0]['deancode'] : '') . ')' . (isset($data[0]['deanname']) ? $data[0]['deanname'] : ''), '', 'L', false);


  }

  public function PDF_reportplotting($filters, $data)
  {
    // $data     = $this->generateResult($filters);
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_header($filters, $data);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INSTRUCTOR REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['client']) ? $data[0]['client'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]['deptcode']) ? '('.$data[0]['deptcode'].')' : ''). '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['department']) ? $data[0]['department'] : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Dean :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]['deancode']) ? '('.$data[0]['deancode'].')' : ''). '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['deanname']) ? $data[0]['deanname'] : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];

    
    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn
























} //end class
