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
use App\Http\Classes\lookup\enrollmentlookup;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_period
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PERIOD';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_period';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['code', 'name', 'isactive', 'sy', 'semid', 'sstart', 'send', 'principalid', 'sext', 'estart', 'eend', 'eext', 'astart', 'aend', 'aext'];
  public $showclosebtn = false;
  private $enrollmentlookup;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 916
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'code', 'name', 'isactive', 'sy', 'term', 'sstart', 'send', 'instructorcode', 'instructorname', 'sext', 'estart', 'eend', 'eext', 'astart', 'aend', 'aext']]];
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = "Period";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace:normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Name";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace:normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:100px;whiteSpace:normal;min-width:100px;text-align:center";
    $obj[0][$this->gridname]['columns'][4]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][4]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][4]['lookupclass'] = "lookupschoolyear";
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][5]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][5]['label'] = 'Semester';
    $obj[0][$this->gridname]['columns'][5]['class'] = 'cssemester sbccsreadonly';
    $obj[0][$this->gridname]['columns'][5]['lookupclass'] = 'lookupsemestergrid';
    $obj[0][$this->gridname]['columns'][5]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][6]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][7]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][8]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][8]['label'] = 'Principal Code';
    $obj[0][$this->gridname]['columns'][8]['class'] = 'csinstructor sbccsreadonly';
    $obj[0][$this->gridname]['columns'][8]['lookupclass'] = 'lookupprincipal';
    $obj[0][$this->gridname]['columns'][8]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][8]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][8]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][9]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][9]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][10]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][11]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][12]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][13]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][14]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['code'] = '';
    $data['name'] = '';
    $data['isactive'] = 'false';
    $data['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'issy=1');
    $data['semid'] = 0;
    $data['principalid'] = 0;
    $data['term'] = '';
    $data['sstart'] = '';
    $data['send'] = '';
    $data['sext'] = '';
    $data['estart'] = '';
    $data['eend'] = '';
    $data['eext'] = '';
    $data['astart'] = '';
    $data['aend'] = '';
    $data['aext'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    $qry = $qry . ",case when isactive=0 then 'false' else 'true' end as isactive";
    return $qry;
  }

  public function checkPeriod ($data, $type = 'update')
  {
    $qry = "select line as value from en_period where code='".$data['code']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_period where code='".$data['code']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check == '') return false;
    return true;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $intActiveLine = 0;
    $msg = '';
    foreach ($data as $key => $chkvalue) {
      if ($data[$key]['isactive'] != 0 || $data[$key]['isactive'] == 'true') {
        $intActiveLine = $intActiveLine + 1;
      }
    }

    if ($intActiveLine > 1) {
      $returndata = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Not allowed 2 active Period', 'data' => $returndata];
    }

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data2['estart'] == '') $data2['estart'] = null;
        if ($data2['eend'] == '') $data2['eend'] = null;
        if ($data2['eext'] == '') $data2['eext'] = null;
        if ($data2['sstart'] == '') $data2['sstart'] = null;
        if ($data2['send'] == '') $data2['send'] = null;
        if ($data2['sext'] == '') $data2['sext'] = null;
        if ($data2['astart'] == '') $data2['astart'] = null;
        if ($data2['aend'] == '') $data2['aend'] = null;
        if ($data2['aext'] == '') $data2['aext'] = null;
        if ($data[$key]['line'] == 0) {
          $check = $this->checkPeriod($data[$key], 'new');
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['code'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['code']);
          }
        } else {
          $check = $this->checkPeriod($data[$key]);
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['code'];
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully. '.$msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];

    $intActiveLine = $this->coreFunctions->datareader("select line as value from en_period where isactive=1 and line <>?", [$row['line']]);
    if ($row['isactive'] > 0 || $row['isactive'] == 'true') {
      if ($intActiveLine > 0) {
        $returndata = $this->loaddata($config);
        return ['status' => false, 'msg' => 'Not allowed 2 active Period', 'data' => $returndata];
      }
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($data['estart'] == '') $data['estart'] = null;
    if ($data['eend'] == '') $data['eend'] = null;
    if ($data['eext'] == '') $data['eext'] = null;
    if ($data['sstart'] == '') $data['sstart'] = null;
    if ($data['send'] == '') $data['send'] = null;
    if ($data['sext'] == '') $data['sext'] = null;
    if ($data['astart'] == '') $data['astart'] = null;
    if ($data['aend'] == '') $data['aend'] = null;
    if ($data['aext'] == '') $data['aext'] = null;
    if ($row['line'] == 0) {
      $check = $this->checkPeriod($row, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['code'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['code']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkPeriod($row);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['code'], 'row' => []];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function



  public function delete($config)
  {
    $row = $config['params']['row'];

    $line = $row['line'];
    $qry = "select v.trno as val, 'Period' as t  from 
        (select trno from en_atfees where periodid=? union all
        select trno from en_athead where periodid=? union all
        select trno from en_adhead where periodid=? union all
        select trno from en_cchead where periodid=?) as v";
    $exist = $this->coreFunctions->opentable($qry, [$line, $line, $line, $line]);
    if (!empty($exist)) {
      return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['code']);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }


  private function loaddataperrecord($line)
  {
    $qry = "select s.line, s.code, s.name, s.sy, s.semid, s.sstart, s.send, s.sext, s.estart, s.eend, s.eext, s.astart,
      s.aend, s.aext, sem.term, '' as bgcolor, case when s.isactive=0 then 'false' else 'true' end as isactive, s.principalid, c.client as instructorcode, c.clientname as instructorname
      from " . $this->table . " as s left join en_term as sem on sem.line=s.semid left join client as c on c.clientid=s.principalid where s.line=? order by s.line";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = "select s.line, s.code, s.name, s.sy, s.semid, s.sstart, s.send, s.sext, s.estart, s.eend, s.eext, s.astart,
      s.aend, s.aext, sem.term, '' as bgcolor, case when s.isactive=0 then 'false' else 'true' end as isactive, s.principalid,c.client as instructorcode, c.clientname as instructorname
      from " . $this->table . " as s left join en_term as sem on sem.line=s.semid left join client as c on c.clientid=s.principalid  order by s.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupschoolyear':
        return $this->enrollmentlookup->lookupschoolyear($config);
        break;
      case 'lookupsemestergrid':
        $config['params']['lookupclass'] = $lookupclass;
        return $this->enrollmentlookup->lookupsemester($config);
        break;
      case 'lookupprincipal':
        $config['params']['lookupclass'] = $lookupclass;
        return $this->enrollmentlookup->lookupinstructor($config);
        break;
      case 'whlog':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Period Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

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
      select code,
      name, isactive, sy, year, terms, date(sstart) as sstart, date(send) as send, 
      date(sext) as sext, date(estart) as estart, date(eend) as eend, date(eext) as eext, date(astart) as astart, date(aend) as aend, date(aext) as aext 
      from en_period
    ";
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn

  // public function reportdata($config){
  //   $data = $this->report_default_query($config);
  //   $str = $this->rpt_DEFAULT_STATUS_MASTER_LAYOUT($data,$config);
  //   return ['status'=>true,'msg'=>'Generating report successfully.','report'=>$str];
  // }

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
    PDF::AddPage('p', [1000, 1000]);
    PDF::SetMargins(10, 10);

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
    PDF::MultiCell(800, 20, 'PERIOD LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(71, 20, "PERIOD", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "NAME", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "SCHOOL YEAR", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "YEAR", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "TERMS", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "START DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "END DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "EXT DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ENROLLMENT START DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ENROLLMENT END DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ENROLLMENT EXT DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ADD/DROP START DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ADD/DROP END DATE", '', 'L', false, 0);
    PDF::MultiCell(71, 20, "ADD/DROP EXT DATE", '', 'L', false, 0);
    // PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
  }

  private function PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
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

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
    foreach ($data as $key => $value) {
      $i++;

      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(71, 10, $value->code, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->name, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->sy, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->year, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->terms, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->sstart, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->send, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->sext, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->estart, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->eend, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->eext, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->astart, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->aend, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(71, 10, $value->aext, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->PDF_default_header($data, $filters);
        $page += $count;
      }
    }

    for ($i = 0; $i < count($data); $i++) {
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

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERIOD LIST', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERIOD', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SCHOOL YEAR', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('YEAR', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('START DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('END DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('EXT DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ENROLLMENT START DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ENROLLMENT END DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ENROLLMENT EXT DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ADD/DROP START DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ADD/DROP END DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ADD/DROP EXT DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    return $str;
  }

  private function rpt_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->code, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->name, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->sy, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->year, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->sstart, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->send, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->sext, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->estart, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->eend, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->eext, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->astart, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->aend, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->aext, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($data, $filters);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn






























} //end class
