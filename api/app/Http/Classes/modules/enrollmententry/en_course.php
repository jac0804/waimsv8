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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_course
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COURSE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'en_course';
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'masterfile_log';
  private $stockselect;

  private $fields = [
    'coursecode', 'coursename', 'level', 'levelid',
    'tfaccount', 'deanname', 'deptcode',
    'isdegree', 'isundergraduate', 'ischinese'
  ];
  private $except = ['clientid', 'client'];
  private $blnfields = ['isdegree', 'isundergraduate', 'ischinese'];
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1459,
      'edit' => 1458,
      'new' => 1460,
      'save' => 1461,
      'change' => 1314,
      'delete' => 1462,
      'print' => 1463,
      'load' => 918,

      'additem' => 1330,
      'edititem' => 1331,
      'deleteitem' => 1332
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'coursecode', 'coursename'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:150px;whiteSpace:normal;';
    $cols[2]['style'] = 'width:250px;whiteSpace:normal;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['line', 'coursecode', 'coursename'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $qry = "select line as clientid, coursecode, coursename 
              from " . $this->head . " 
              where 1=1 " . $filtersearch . "
              order by line";
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

  public function createTab($access, $config)
  {
    $tab = [
      'tableentry' => ['action' => 'enrollmententry', 'lookupclass' => 'entrysection', 'label' => 'SECTION']
    ];
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
    $fields = [
      'client', 'coursename', 'deptcode', 'deanname'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupledgercourse');

    data_set($col1, 'coursename.type', 'cinput');

    data_set($col1, 'deanname.type', 'lookup');
    data_set($col1, 'deanname.lookupclass', 'coursedeanlookup');
    data_set($col1, 'deptcode.lookupclass', 'coursedeptlookup');

    $fields = ['level', 'tfaccount', 'acnoname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'level.required', true);
    data_set($col2, 'level.action', 'lookuplevel');
    data_set($col2, 'acnoname.readonly', true);
    data_set($col2, 'acnoname.class', 'csacnoname sbccsreadonly');
    data_set($col2, 'tfaccount.readonly', true);
    data_set($col2, 'tfaccount.required', true);
    data_set($col2, 'tfaccount.lookupclass', 'coursetfaccountlookup');

    $fields = ['isdegree', 'isundergraduate', 'ischinese'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
    $data[0]['coursecode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['level'] = '';
    $data[0]['tfaccount'] = '';
    $data[0]['acnoname'] = '';
    $data[0]['deanname'] = '';
    $data[0]['deptcode'] = '';
    $data[0]['isdegree'] = '0';
    $data[0]['isundergraduate'] = '0';
    $data[0]['ischinese'] = '0';

    return $data;
  }


  public function loadheaddata($config)
  {

    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $fields = "s.line as clientid, s.coursecode as client, coa.acno as tfaccount, coa.acnoname as acnoname,
          dept.client as deptcode, s.coursecode, s.coursename, l.levels as level, s.tfaccount, s.deanname, s.deptcode,s.isdegree, s.isundergraduate, s.levelid, s.ischinese";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',s.' . $value;
    }
    $qryselect = "select " . $fields;
    $qry = $qryselect . " from " . $this->head . " as s 
        left join coa as coa on coa.acno=s.tfaccount
        left join client as dept on dept.client=s.deptcode
        left join en_levels as l on l.line=s.level
        where s.line = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      // $stock = $this->openstock($clientid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['coursecode']);
    } else {
      $data['coursecode'] = $head['client'];
      $head['coursecode'] = $head['client'];
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
      $check = $this->checkCourse($data, $head['clientid']);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate record for '.$data['coursecode'], 'clientid' => $head['clientid']];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['clientid']]);
        $clientid = $head['clientid'];
      }
    } else {
      $check = $this->checkCourse($data, $head['clientid'], 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate record for '.$data['coursecode'], 'clientid' => $clientid];
      } else {
        $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        $this->logger->sbcmasterlog($clientid, $config, ' CREATE COURSE - ' . $data['coursecode'] . ' - ' . $data['coursename']);
      }
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function checkCourse($data, $line, $type = 'update')
  {
    $qry = "select line as value from en_course where coursecode='".$data['coursecode']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_course where coursecode='".$data['coursecode']."' and line<>".$line;
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') return true;
    return false;
  }

  public function getlastclient($pref)
  {
    return '';
  }

  public function openstock($trno, $config)
  {
    
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $qry = "select line as val, 'Section' as t from en_section where courseid=?
        ";
    $exist = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($exist)) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {


      $line = $clientid;
      $qry = "select v.trno as val, 'Course' as t  from 
            (select trno from en_adhead where courseid=? union all
            select trno from en_atfees where courseid=? union all
            select trno from en_athead where courseid=?) as v";
      $exist = $this->coreFunctions->opentable($qry, [$line, $line, $line, $line, $line, $line, $line, $line]);
      if (!empty($exist)) {
        return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where line=?", 'delete', [$clientid]);
        $this->coreFunctions->execqry("delete from en_section where courseid=?", 'delete', [$clientid]);
        return ['clientid' => $clientid, 'status' => true, 'msg' => 'Successfully deleted.'];
      }
    }
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

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
      select
      course.line, course.coursecode as code, course.coursename as description, course.tfaccount, course.isinactive,
      course.level, course.deanname, course.deptcode, course.isdegree, course.isundergraduate, coa.acnoname as tfaccountname,
      section.line, section.section, section.isterms, section.coursecode, section.coursename, section.courseid
      from en_course as course
      left join coa on coa.acno=course.tfaccount
      left join en_section as section on course.line = section.courseid
      where course.line = '" . $trno . "'
    ";
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn

  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_course_LAYOUT($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_course_LAYOUT($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function PDF_default_header($data, $filters)
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
    PDF::MultiCell(800, 20, 'COURSE REPORT', '', 'L', false);

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Course :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->description) ? $data[0]->description : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Level :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->level) ? $data[0]->level : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Dean :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->deanname) ? $data[0]->deanname : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Account Code :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->tfaccount) ? $data[0]->tfaccount : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Department :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->deptcode) ? $data[0]->deptcode : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 20, 'Account Name :', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(280, 20, (isset($data[0]->tfaccountname) ? $data[0]->tfaccountname : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 20, "SECTION", '', 'L', false, 0);
    // PDF::MultiCell(300, 20, "ORDER", '', 'L', false, 0);
    PDF::MultiCell(660, 20, "", '', 'L', false);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
    // PDF::MultiCell(660, 0, "", 'T', 'L', false);
  }

  private function PDF_course_LAYOUT($data, $filters)
  {
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
    $this->PDF_default_header($data, $filters);
    $i = 0;
    foreach ($data as $key => $value) {
      $i++;

      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(700, 10, $value->section, '', 'L', 0, 0, '', '', true, 0, true, false);
      //   PDF::MultiCell(300, 10, $value->orderscheme, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->PDF_default_header($data, $filters);
        $page += $count;
      }
    }

    PDF::MultiCell(760, 0, "", 'T', 'L', false);
    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function default_header($filters, $data)
  {
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = "";
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('COURSE REPORT', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Course : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]->description) ? $data[0]->description : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Dean : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]->deanname) ? $data[0]->deanname : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]->deptcode) ? $data[0]->deptcode : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SECTION', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function rpt_course_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '800';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->default_header($filters, $data);

    $str .= $this->reporter->begintable('800');
    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->section, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

} //end class
