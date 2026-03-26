<?php

namespace App\Http\Classes\modules\crm;

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

class salesgroup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Sales Group';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'salesgroup';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['groupname', 'agentid','leader'];
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
    $attrib = [
      'load' => 2593,
      'additem' => 2843,
      'saveallentry' => 2844,
      'print' => 2845,
      'save' => 2846,
      'delete' => 2847,
    ];
    return $attrib;
  }

  public function createTab($config)
  {
    $save = $this->othersClass->checkAccess($config['params']['user'], 2846);
    $delete = $this->othersClass->checkAccess($config['params']['user'], 2847);

    $action = 0;
    $groupname = 1;
    $leader = 2;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'groupname', 'leader']]];
    $stockbuttons = ['viewsalesgroupagent'];

    if ($save) {
      array_push($stockbuttons, 'save');
    }
    if ($delete) {
      array_push($stockbuttons, 'delete');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$groupname]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$leader]['align'] = "text-left";
    $obj[0][$this->gridname]['columns'][$leader]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$leader]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$leader]['lookupclass'] = "lookup_agent_stockgroup";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $additem = $this->othersClass->checkAccess($config['params']['user'], 2843);
    $saveallentry = $this->othersClass->checkAccess($config['params']['user'], 2844);
    $print = $this->othersClass->checkAccess($config['params']['user'], 2845);

    $tbuttons = [];
    if ($additem) {
      array_push($tbuttons, 'addrecord');
    }
    if ($saveallentry) {
      array_push($tbuttons, 'saveallentry');
    }
    if ($print) {
      array_push($tbuttons, 'print');
    }
    array_push($tbuttons, 'masterfilelogs');

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['groupname'] = '';
    $data['leader'] = '';
    $data['agentid'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "s.line,a.clientname as leader";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $qry = "select groupid as value from client where groupid = '" . $data['groupname'] . "'";
    $checking = $this->coreFunctions->datareader($qry);

    if (!empty($checking)) {
      return ['status' => false, 'msg' => 'Code Already Used. - ' . $data['groupname'], 'data' => $data];
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - GROUP NAME: ' . $data['groupname'] . ', LEADER: ' . $data['agentid']);
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

    $this->logger->sbcmasterlog($row['line'], $config, ' DELETE - GROUP NAME: ' . $row['groupname'] . ', LEADER: ' . $row['leader']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as s left join client as a on a.clientid=s.agentid where s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $limit = '';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';
    if ($company == 10 || $company == 12) { //afti, afti usd
      $limit = '25';
    }

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    if ($search != "") {
      $l = '';
    } else {
      $l = " limit " . $limit;
    }
    $qry = "select " . $select . " from " . $this->table . "  as s left join client as a on a.clientid=s.agentid 
    where 1=1 " . $filtersearch . " 
    order by s.line $l";
    $data = $this->coreFunctions->opentable($qry);
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
        if ($data[$key]['line'] == 0) {
          $id = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($id, $config, ' CREATE - GROUP NAME: ' . $data[$key]['groupname'] . ', LEADER: ' . $data[$key]['leader']);
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


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      case 'lookup_agent_stockgroup':
        return $this->lookup_agent_stockgroup($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
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

  public function lookup_agent_stockgroup($config)
  {
    $plotting = array('agentid' => 'clientid', 'agentname' => 'clientname', 'leader' => 'clientname');
    $plottype = 'plotgrid';
    $title = 'List of Agent';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select client, clientname, clientid from client where isagent = '1'";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
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
    $fields = ['radioprint', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
  'PDFM' as print"
    );
  }

  public function reportdata($config)
  {
    $data = $this->report_default_query($config);

    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->reportDefaultLayout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->default_salesgroup_PDF($config, $data);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }


  public function report_default_query($config)
  {
    $filter   = "";
    $query = "select line, groupname, leader from salesgroup";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  private function displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '11';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES GROUP LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Group Name', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Leader', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data['groupname'], '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data['leader'], '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_salesgroup_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(360, 0, "GROUP NAME", 'B', 'L', false, 0);
    PDF::MultiCell(360, 0, "LEADER", 'B', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
  }

  private function default_salesgroup_PDF($params, $data)
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
    $this->default_salesgroup_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $section_height = PDF::GetStringHeight(360, $data[$i]['groupname']);
      $section_desc_height = PDF::GetStringHeight(360, $data[$i]['leader']);
      $max_height = max($section_height, $section_desc_height);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(360, 0, $data[$i]['groupname'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(360, $max_height, $data[$i]['leader'], '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_salesgroup_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
} //end class
