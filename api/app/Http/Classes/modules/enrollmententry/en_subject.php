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

class en_subject
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SUBJECT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'en_subject';
  public $prefix = '';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $stockselect;

  private $fields = [
    'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'hours', 'level',
    'prereq1', 'prereq2', 'prereq3', 'prereq4', 'coreq', 'ischinese'
  ];
  private $except = ['clientid', 'client'];
  private $blnfields = [];
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
      'view' => 1309,
      'edit' => 1308,
      'new' => 1310,
      'save' => 1311,
      'change' => 1317,
      'delete' => 1312,
      'print' => 1313,
      'load' => 920,

      'additem' => 1324,
      'edititem' => 1325,
      'deleteitem' => 1326
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'subjectcode', 'subjectname', 'rrqty'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:150px;whiteSpace:normal;';
    $cols[2]['style'] = 'width:200px;whiteSpace:normal;';
    $cols[3]['label'] = 'Units';
    $cols[3]['style'] = 'text-align:right;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['trno', 'subjectcode', 'subjectname', 'units'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $qry = "select trno as clientid, subjectcode, subjectname, units as rrqty from en_subject 
        where 1=1 " . $filtersearch . "
        order by subjectcode";
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
      'tableentry' => [
        'action' => 'enrollmententry', // directory folder where table entry file is located 
        'lookupclass' => 'entrysubject', // table entry file
        'label' => 'EQUIVALENT SUBJECT'
      ]
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
      'client', 'subjectname', ['units', 'lecture'], ['laboratory', 'hours']
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.lookupclass', 'lookupledgersubject');

    data_set($col1, 'subjectname.type', 'cinput');
    data_set($col1, 'units.type', 'cinput');
    data_set($col1, 'units.required', true);
    data_set($col1, 'lecture.type', 'cinput');
    data_set($col1, 'laboratory.type', 'cinput');
    data_set($col1, 'hours.type', 'cinput');
    data_set($col1, 'hours.required', true);

    $fields = ['dlevel', 'dprereq1', 'dprereq2', 'dprereq3', 'dprereq4'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dlevel.class', 'csdlevel sbccsreadonly');
    data_set($col2, 'dlevel.required', true);
    data_set($col2, 'dprereq1.class', 'csdprereq1 sbccsreadonly');
    data_set($col2, 'dprereq2.class', 'dprereq2 sbccsreadonly');
    data_set($col2, 'dprereq3.class', 'dprereq3 sbccsreadonly');
    data_set($col2, 'dprereq4.class', 'dprereq4 sbccsreadonly');

    $fields = ['ischinese'];
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
    $data[0]['subjectcode'] = '';
    $data[0]['subjectname'] = '';
    $data[0]['units'] = '0';
    $data[0]['lecture'] = '0';
    $data[0]['laboratory'] = '0';
    $data[0]['hours'] = '0';
    $data[0]['level'] = 0;
    $data[0]['coreq'] = '';
    $data[0]['prereq1'] = '';
    $data[0]['prereq2'] = '';
    $data[0]['prereq3'] = '';
    $data[0]['prereq4'] = '';
    $data[0]['ischinese'] = '0';

    return $data;
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->coreFunctions->datareader("select trno as value from en_subject order by trno desc limit 1");
    }
    $fields = "s.trno as clientid,s.subjectcode as client,
        lvl.levels as dlevel, coreq.subjectname as dcoreq, pre1.subjectname as dprereq1,
        pre2.subjectname as dprereq2, pre3.subjectname as dprereq3, pre4.subjectname as dprereq4, s.ischinese";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',s.' . $value;
    }
    $qryselect = "select " . $fields;
    $qry = $qryselect . " from en_subject as s 
        left join en_levels as lvl on lvl.line=s.level 
        left join en_subject as coreq on coreq.trno=s.coreq
        left join en_subject as pre1 on pre1.trno=s.prereq1
        left join en_subject as pre2 on pre2.trno=s.prereq2
        left join en_subject as pre3 on pre3.trno=s.prereq3
        left join en_subject as pre4 on pre4.trno=s.prereq4
        where s.trno = ? ";

    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      // $stock = $this->openstock($clientid, $config);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      if ($head[0]->ischinese == 0) {
        $head[0]->ischinese = '0';
      } else {
        $head[0]->ischinese = '1';
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
      unset($this->fields['subjectcode']);
    } else {
      $data['subjectcode'] = $head['client'];
      $head['subjectcode'] = $head['client'];
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
    if ($data['ischinese'] == '1') {
      $data['ischinese'] = 1;
    } else {
      $data['ischinese'] = 0;
    }
    if ($isupdate) {
      $check = $this->checkSubject($head);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate Subject entry for '.$data['subjectcode'].' - '.$data['subjectname'], 'clientid'=>$clientid];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['clientid']]);
        $clientid = $head['clientid'];
      }
    } else {
      $check = $this->checkSubject($head, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate Subject entry for '.$data['subjectcode'].' - '.$data['subjectname'], 'clientid'=>$clientid];
      } else {
        $clientid = $this->coreFunctions->insertGetId($this->head, $data);
        $this->logger->sbcmasterlog($clientid, $config, ' CREATE SUBJECT - ' . $data['subjectname']); // 
      }
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function checkSubject($data, $type = 'update')
  {
    $qry = "select trno as value from en_subject where subjectcode='".$data['subjectcode']."'";
    if ($type == 'update') {
      $qry = "select trno as value from en_subject where subjectcode='".$data['subjectcode']."' and trno<>".$data['clientid'];
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
    $qry = 'select  e.line, s.subjectcode, s.subjectname from en_subjectequivalent as e left join en_subject as s on s.trno=e.subjectid where e.subjectmain=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $subjectname = $this->coreFunctions->getfieldvalue($this->head, "subjectname", "trno=?", [$clientid]);
    $qry = "select line as val, 'Equivalent Subject' as t from en_subjectequivalent where subjectmain=? ";
    $eqexist = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($eqexist)) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Unable to delete, Delete Equivalent First! '];
    } else {
      $qry = "select val from (
        select trno as val from en_adsubject where subjectid=? union all
        select trno as val from en_ccsubject where subjectid=? union all
        select trno as val from en_glsubject where subjectid=? union all
        select trno as val from en_sarchive where subjectid=? union all
        select trno as val from en_scsubject where subjectid=? union all
        select trno as val from en_sgssubject where subjectid=? union all
        select trno as val from en_sjsubject where subjectid=? union all
        select trno as val from en_sosubject where subjectid=? union all
        select line as val from en_subjectequivalent where subjectid=?
      ) as v";
      $exist = $this->coreFunctions->opentable($qry, [$clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid]);
      // $qry = "select line as val, 'Equivalent Subject' as t from en_subjectequivalent where subjectmain=? union all
      //       select trno as val, 'Co/Pre-Requisite Subject' from en_subject where coreq=? or prereq1=?  or prereq2=?  or prereq3=?  or prereq4=?
      //       union all
      //       select trno as val, 'Curriculum Subject' from en_ccsubject where subjectid=? or coreqid=? or pre1id=?  or pre2id=?  or pre3id=?  or pre4id=?
      //       union all
      //       select trno as val, 'Schedule Subject' from en_scsubject where subjectid=?
      //       union all
      //       select trno as val, 'Assessment Subject' from en_sosubject where subjectid=?
      //       union all 
      //       select trno as val, 'Registration Subject' from en_sjsubject where subjectid=?
      //       union all
      //       select trno as val, 'Posted Trans Subject' from en_glsubject where subjectid=?";
      // $exist = $this->coreFunctions->opentable($qry, [$clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid, $clientid]);
      if (!empty($exist)) {
        return ['clientid' => $clientid, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
      } else {
        $this->coreFunctions->execqry('delete from en_subject where trno=?', 'delete', [$clientid]);
        $this->logger->sbcdelmaster_log($clientid, $config, 'REMOVE SUBJECT - ' . $subjectname);
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
      // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
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
    $query = "select subjectcode, subjectname, units, lecture from en_subject where trno=".$trno;
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn

  private function getSubjectequiv($config)
  {
    $qry = "select e.line, e.subjectid, e.subjectmain, s.subjectcode, s.subjectname, s.units , s.lecture
    from en_subjectequivalent as e left join en_subject as s on s.trno=e.subjectid where e.subjectmain=".$config['params']['dataid'];
    return $this->coreFunctions->opentable($qry);
  }

  public function reportdata($config)
  {

    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function PDF_header($data, $filters)
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
    PDF::MultiCell(800, 20, $this->modulename.' REPORT', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'Subject Code: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(280, 0, $data[0]->subjectcode, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'Units: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(280, 0, $data[0]->units, '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'Name: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(280, 0, $data[0]->subjectname, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'Lecture: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(280, 0, $data[0]->lecture, '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(175, 20, "SUBJECT CODE", 'B', 'L', false, 0);
    PDF::MultiCell(250, 20, "SUBJECT NAME", 'B', 'L', false, 0);
    PDF::MultiCell(160, 20, "UNITS", 'B', 'L', false, 0);
    PDF::MultiCell(175, 20, "LECTURE", 'B', 'L', false);
  }

  private function PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 65;
    $page = 65;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_header($data, $filters);
    $i = 0;
    $subjequiv = $this->getSubjectequiv($filters);
    if (!empty($subjequiv)) {
      foreach($subjequiv as $se) {
        $i++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(175, 0, (isset($se->subjectcode) ? $se->subjectcode : ''), '', 'L', false, 0);
        PDF::MultiCell(250, 0, (isset($se->subjectname) ? $se->subjectname : ''), '', 'L', false, 0);
        PDF::MultiCell(160, 0, (isset($se->units) ? $se->units : ''), '', 'L', false, 0);
        PDF::MultiCell(175, 0, (isset($se->lecture) ? $se->lecture : ''), '', 'L', false);

        if (intVal($i) + 1 == $page) {
          $this->PDF_header($data, $filters);
          $page += $count;
        }
      }
    }
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

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('SUBJECT REPORT', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Building : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    // $str .= $this->reporter->col((isset($data[0]->bldgname) ? $data[0]->bldgname : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<b>Subject Code: </b>'.(isset($data[0]->subjectcode) ? $data[0]->subjectcode : ''), '500', null, false, '', 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('<b>Units: </b>'.(isset($data[0]->units) ? $data[0]->units : ''), '300', null, false, '', 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<b>Name: </b>'.(isset($data[0]->subjectname) ? $data[0]->subjectname : ''), '500', null, false, '', 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('<b>Lecture: </b>'.(isset($data[0]->lecture) ? $data[0]->lecture : ''), '300', null, false, '', 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '500', null, false, '', 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUBJECT CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SUBJECT NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNITS', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('LECTURE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function rpt_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
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
    $str .= $this->default_header($filters, $data);

    $str .= $this->reporter->begintable('800');
    $subjequiv = $this->getSubjectequiv($filters);
    foreach ($subjequiv as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->subjectcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->subjectname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->units, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->lecture, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
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
