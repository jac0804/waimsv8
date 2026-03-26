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

class en_attendancesetup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ATTENDANCE SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_attendancesetup';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['line', 'syid', 'levelid', 'rcmonthjan', 'rcmonthfeb', 'rcmonthmar', 'rcmonthapr', 'rcmonthmay', 'rcmonthjun', 'rcmonthjul', 'rcmonthaug', 'rcmonthsep', 'rcmonthoct', 'rcmonthnov', 'rcmonthdec', 'atstartmonth', 'atendmonth'];
  public $showclosebtn = false;
  private $reporter;
  private $enrollmentlookup;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->enrollmentlookup = new enrollmentlookup;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2730);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'sy', 'levels', 'rcmonthjan', 'rcmonthfeb', 'rcmonthmar', 'rcmonthapr', 'rcmonthmay', 'rcmonthjun', 'rcmonthjul', 'rcmonthaug', 'rcmonthsep', 'rcmonthoct', 'rcmonthnov', 'rcmonthdec', 'attotaldays', 'atstartmonth', 'atendmonth']]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][1]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupschoolyear";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][2]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog', 'print'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['syid'] = 0;
    $data['sy'] = '';
    $data['levelid'] = 0;
    $data['levels'] = '';
    $data['rcmonthjan'] = 0;
    $data['rcmonthfeb'] = 0;
    $data['rcmonthmar'] = 0;
    $data['rcmonthapr'] = 0;
    $data['rcmonthmay'] = 0;
    $data['rcmonthjun'] = 0;
    $data['rcmonthjul'] = 0;
    $data['rcmonthaug'] = 0;
    $data['rcmonthsep'] = 0;
    $data['rcmonthoct'] = 0;
    $data['rcmonthnov'] = 0;
    $data['rcmonthdec'] = 0;
    $data['attotaldays'] = 0;
    $data['atstartmonth'] = date('Y') . '-01-01';
    $data['atendmonth'] = date('Y') . '-12-01';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data3 = [
          'line' => $data2['line'],
          'syid' => $data2['syid'],
          'levelid' => $data2['levelid'],
          'jan' => $data2['rcmonthjan'],
          'feb' => $data2['rcmonthfeb'],
          'mar' => $data2['rcmonthmar'],
          'apr' => $data2['rcmonthapr'],
          'may' => $data2['rcmonthmay'],
          'jun' => $data2['rcmonthjun'],
          'jul' => $data2['rcmonthjul'],
          'aug' => $data2['rcmonthaug'],
          'sep' => $data2['rcmonthsep'],
          'oct' => $data2['rcmonthoct'],
          'nov' => $data2['rcmonthnov'],
          'dec' => $data2['rcmonthdec'],
          'totaldays' => ($data2['rcmonthjan'] + $data2['rcmonthfeb'] + $data2['rcmonthmar'] + $data2['rcmonthapr'] + $data2['rcmonthmay'] + $data2['rcmonthjun'] + $data2['rcmonthjul'] + $data2['rcmonthaug'] + $data2['rcmonthsep'] + $data2['rcmonthoct'] + $data2['rcmonthnov'] + $data2['rcmonthdec']),
          'startmonth' => $data2['atstartmonth'],
          'endmonth' => $data2['atendmonth']
        ];
        if ($data3['syid'] != 0 && $data3['levelid'] != 0) {
          if ($data[$key]['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data3);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['syid']);
          } else {
            $data3['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data3['editby'] = $config['params']['user'];
            unset($data3['line']);
            $this->coreFunctions->sbcupdate($this->table, $data3, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function  

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $data2 = [];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data2 = [
      'line' => $data['line'],
      'syid' => $data['syid'],
      'levelid' => $data['levelid'],
      'jan' => $data['rcmonthjan'],
      'feb' => $data['rcmonthfeb'],
      'mar' => $data['rcmonthmar'],
      'apr' => $data['rcmonthapr'],
      'may' => $data['rcmonthmay'],
      'jun' => $data['rcmonthjun'],
      'jul' => $data['rcmonthjul'],
      'aug' => $data['rcmonthaug'],
      'sep' => $data['rcmonthsep'],
      'oct' => $data['rcmonthoct'],
      'nov' => $data['rcmonthnov'],
      'dec' => $data['rcmonthdec'],
      'totaldays' => ($data['rcmonthjan'] + $data['rcmonthfeb'] + $data['rcmonthmar'] + $data['rcmonthapr'] + $data['rcmonthmay'] + $data['rcmonthjun'] + $data['rcmonthjul'] + $data['rcmonthaug'] + $data['rcmonthsep'] + $data['rcmonthoct'] + $data['rcmonthnov'] + $data['rcmonthdec']),
      'startmonth' => $data['atstartmonth'],
      'endmonth' => $data['atendmonth']
    ];
    if ($data2['syid'] != 0 && $data2['levelid'] != 0) {
      if ($row['line'] == 0) {
        $line = $this->coreFunctions->insertGetId($this->table, $data2);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['syid']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      } else {
        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['editby'] = $config['params']['user'];
        unset($data2['line']);
        if ($this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      return ['status' => false, 'msg' => 'School year and level required'];
    }
  } //end function


  public function delete($config)
  {
    $row = $config['params']['row'];
    $line = $row['line'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['syid']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  public function selectqry()
  {
    $qry = "select at.line, scy.sy, level.levels, at.syid, at.levelid, at.jan as rcmonthjan, at.feb as rcmonthfeb, at.mar as rcmonthmar, at.apr as rcmonthapr, at.may as rcmonthmay, at.jun as rcmonthjun, at.jul as rcmonthjul, at.aug as rcmonthaug, at.sep as rcmonthsep, at.oct as rcmonthoct, at.nov as rcmonthnov, at.dec as rcmonthdec, at.totaldays as attotaldays, date(at.startmonth) as atstartmonth, date(at.endmonth) as atendmonth, '' as bgcolor";
    return $qry;
  }

  private function loaddataperrecord($line)
  {
    $selectqry = $this->selectqry();
    $qry = $selectqry . " from " . $this->table . " as at left join en_schoolyear as scy on scy.line=at.syid left join en_levels as level on level.line=at.levelid where at.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $selectqry = $this->selectqry();
    $qry = $selectqry . " from " . $this->table . " as at left join en_schoolyear as scy on scy.line=at.syid left join en_levels as level on level.line=at.levelid order by at.line";
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
      case 'lookuplevel':
        return $this->enrollmentlookup->lookuplevel($config);
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
      'title' => 'Attendance Setup Logs',
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
      "select 'PDFM' as print, '' as prepared, '' as approved, '' as received");
  }

  public function reportdata($config)
  {
    $data = $this->loaddata($config);
    $str = $this->PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
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
    PDF::AddPage('l', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(760, 20, 'ATTENDANCE SETUP', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(760, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(85, 20, "SCHOOL YEAR", 'B', 'L', false, 0);
    PDF::MultiCell(80, 20, "LEVEL", 'B', 'L', false, 0);
    PDF::MultiCell(75, 20, "START MONTH", 'B', 'L', false, 0);
    PDF::MultiCell(75, 20, "END MONTH", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "JAN", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "FEB", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "MAR", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "APR", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "MAY", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "JUN", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "JUL", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "AUG", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "SEP", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "OCT", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "NOV", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "DEC", 'B', 'L', false, 0);
    PDF::MultiCell(50, 20, "TOTAL", 'B', 'L', false);
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
    $this->PDF_default_header($data, $filters);
    $i = 0;
    foreach ($data as $key => $value) {
      $i++;
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(85, 0, $value->sy, '', 'L', false, 0);
      PDF::MultiCell(80, 0, $value->levels, '', 'L', false, 0);
      PDF::MultiCell(75, 0, $value->atstartmonth, '', 'L', false, 0);
      PDF::MultiCell(75, 0, $value->atendmonth, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthjan, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthfeb, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthmar, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthapr, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthmay, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthjun, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthjul, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthaug, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthsep, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthoct, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthnov, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->rcmonthdec, '', 'L', false, 0);
      PDF::MultiCell(50, 0, $value->attotaldays, '', 'L', false);

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
} //end class
