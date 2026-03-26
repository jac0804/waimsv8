<?php

namespace App\Http\Classes\modules\tableentry;

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

class entrychecksetup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CHECK SERIES SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'checksetup';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['acnoid', 'start', 'end', 'current'];
  private $except = ['start', 'end'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'acno', 'acnoname', 'start', 'end', 'current']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][1]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][1]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][2]['type'] = "input";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Acnoname";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['acnoid'] = 0;
    $data['acno'] = '';
    $data['acnoname'] = '';
    $data['start'] = 0;
    $data['end'] = 0;
    $data['current'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = $this->table . "." . "line";
    $fields = $this->fields;
    foreach ($fields as $key => $value) {
      $qry = $qry . ',' . $this->table . '.' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          if (!in_array($value2, $this->except)) {
            $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
          } else {
            $data2[$value2] = $data[$key][$value2];
          }
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE LINE: ' . $line . ' - ACCOUNT: ' . $data[$key]['acnoname'] . ', START: ' . $data[$key]['start'] . ' , END: ' . $data[$key]['end'] . ' , END: ' . $data[$key]['current']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
    foreach ($this->fields as $key => $value) {
      if (!in_array($value, $this->except)) {
        $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
      } else {
        $data[$value] = $row[$value];
      }
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE LINE: ' . $line . ' - ACCOUNT: ' . $row['acnoname'] . ', START: ' . $row['start'] . ' , END: ' . $row['end'] . ' , END: ' . $row['current']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
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
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $this->logger->sbcmasterlog($row['line'], $config, ' REMOVE LINE: ' . $row['line'] . ' - ACCOUNT: ' . $row['acnoname'] . ', START: ' . $row['start'] . ' , END: ' . $row['end'] . ' , END: ' . $row['current']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ", coa.acno as acno, coa.acnoname as acnoname, '' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . "
    left join coa on coa.acnoid = " . $this->table . ".acnoid
    where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $company = $config['params']['companyid'];
    $limit = '';
    $select = $select . ", coa.acno as acno, coa.acnoname as acnoname, '' as bgcolor ";
    $search = isset($config['params']['filter']) ? $config['params']['filter'] : '';
    if ($company == 10 || $company == 12) {
      if ($search == '') {
        $limit = 'limit 25';
      }
    }
    $qry = "select " . $select . " from " . $this->table . " 
    left join coa on coa.acnoid = " . $this->table . ".acnoid
    order by line $limit";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      case 'lookupacno':
        return $this->lookupacno($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupacno($config)
  {

    $rowindex = $config['params']['index'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Chart of Accounts',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'acnoid' => 'acnoid',
        'acno' => 'acno',
        'acnoname' => 'acnoname',
      )
    );

    $cols = array(
      array('name' => 'acno', 'label' => 'Acct#', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;')

    );
    $alias = 'CB';
    $qry = "select acnoid, acno, acnoname from coa where left(alias,2)=?";
    $data = $this->coreFunctions->opentable($qry, [$alias]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
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

  // -> print function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti, afti usd
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupsetup');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }
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
    $select = $this->selectqry();
    $select = $select . ", coa.acno as acno, coa.acnoname as acnoname, '' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " 
      left join coa on coa.acnoid = " . $this->table . ".acnoid
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);

    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_forex_masterfile_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->default_forex_masterfile_PDF($config, $data);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Account Code', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Acnoname', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Start', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('End', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Current', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_forex_masterfile_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($filters, $data);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['acno'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['start'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['end'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['current'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($filters, $data);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function default_forex_masterfile_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    switch ($companyid) {
      case 3: //conti
      case 14: //majesty
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        break;

      default:
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        break;
    }

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(150, 0, "ACCOUNT CODE", 'B', 'L', false, 0);
    PDF::MultiCell(150, 0, "ACNONAME", 'B', 'L', false, 0);
    PDF::MultiCell(150, 0, "START", 'B', 'L', false, 0);
    PDF::MultiCell(150, 0, "END", 'B', 'L', false, 0);
    PDF::MultiCell(100, 0, "CURRENT", 'B', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
  }

  private function default_forex_masterfile_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_forex_masterfile_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $section_height = PDF::GetStringHeight(75, $data[$i]['acno']);
      $section_desc_height = PDF::GetStringHeight(75, $data[$i]['acnoname']);
      $max_height = max($section_height, $section_desc_height);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(150, 0, $data[$i]['acno'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(150, $max_height, $data[$i]['acnoname'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(150, $max_height, $data[$i]['start'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(150, $max_height, $data[$i]['end'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(150, $max_height, $data[$i]['current'], '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_forex_masterfile_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
} //end class
