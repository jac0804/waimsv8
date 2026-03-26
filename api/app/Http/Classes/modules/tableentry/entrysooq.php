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

class entrysooq
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SO #';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  public $tablenum = 'transnum';
  private $table = 'oqstock';
  private $htable = 'hoqstock';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $fields = ['line', 'sono', 'rtno', 'ref', 'uom', 'qty', 'rem', 'oraclecode', 'ext', 'rrcost', 'ispartial', 'itemdesc'];
  public $showclosebtn = false;
  private $reporter;


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
      'load' => 3604
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");

    $action = 0;
    $sono = 1;
    $rtno = 2;
    $ispartial = 3;
    $rem = 4;
    $ref = 5;
    $uom = 6;
    $qty = 7;
    $oraclecode = 8;
    $ext = 9;
    $rrcost = 10;
    $itemdesc = 11;
    $specs = 12;
    $requestorname = 13;
    $deptname = 14;
    $supplier = 15;

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'sono', 'rtno', 'ispartial', 'rem', 'ref', 'uom', 'qty', 'oraclecode', 'ext', 'rrcost', 'itemdesc', 'specs', 'requestorname', 'deptname', 'supplier'
        ]
      ]
    ];

    $stockbuttons = ['save'];

    if ($isposted) {
      $stockbuttons = [];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
    $obj[0][$this->gridname]['columns'][$sono]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$rtno]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    if ($isposted) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$sono]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$sono]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

      $obj[0][$this->gridname]['columns'][$rtno]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$rtno]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Reference';

    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$qty]['label'] = 'QTY';
    $obj[0][$this->gridname]['columns'][$qty]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$qty]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$oraclecode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$oraclecode]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ext]['style'] =  'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Temp. Description';
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$itemdesc]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$specs]['label'] = 'Temp. UOM';
    $obj[0][$this->gridname]['columns'][$specs]['style']  = 'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$specs]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$requestorname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$requestorname]['style'] = 'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$requestorname]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$deptname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$deptname]['style'] =  'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$deptname]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$supplier]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$supplier]['style']  = 'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$supplier]['align'] = 'text-align';

    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$rem]['style']  = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }


  public function createtabbutton($config)
  {
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "transnum");
    $tbuttons = ['saveallentry'];

    if ($isposted) {
      $tbuttons = [];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function selectqry()
  {
    $qry = "s.trno ,s.line,s.sono,s.rtno,s.ref,s.uom,
              FORMAT(s.rrcost,2) as rrcost,
              FORMAT(s.rrqty,2)  as qty,
              s.oraclecode,s.ext,rrcost,'' as bgcolor,
            ifnull(info.itemdesc,'') as itemdesc, ifnull(info.unit,'') as unit,
            ifnull(info.specs,'') as specs, ifnull(info.purpose,'') as purpose,
            ifnull(info.requestorname,'') as requestorname,
            dept.clientname as deptname,sup.clientname as supplier,
            if(s.ispartial=1,'true','false') as ispartial, s.rem ";
    return $qry;
  }


  public function save($config)
  {
    if ($config['params']['doc'] == 'OM') {
      $this->table = 'omstock';
      $this->htable = 'homstock';
    }

    $data = [];
    $trno =  $config['params']['tableid'];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['trno'] = $config['params']['tableid'];

    if ($row['line'] == 0) {
      $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;
      $this->coreFunctions->sbcinsert($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($trno, $line);
        $this->logger->sbcwritelog($trno, $config, 'ADD STOCK', "SO # => " . $data['sono']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line']);
        $this->logger->sbcwritelog($trno, $config, "UPDATE STOCK", "SO # => " . $data['sono']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    if ($config['params']['doc'] == 'OM') {
      $this->table = 'omstock';
      $this->htable = 'homstock';
    }

    $data = $config['params']['data'];
    $trno =  $config['params']['tableid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $qry = "select line as value from " . $this->table . " where trno=? order by line desc limit 1";
          $line = $this->coreFunctions->datareader($qry, [$trno]);
          if ($line == '') {
            $line = 0;
          }
          $line = $line + 1;
          $data['line'] = $line;
          $this->coreFunctions->sbcinsert($this->table, $data2);
          $this->logger->sbcwritelog($trno, $config, 'ADD STOCK', "SO # => " . $data[$key]['sono']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data[$key]['trno'], 'line' => $data[$key]['line']]);
          $this->logger->sbcwritelog($trno, $config, "UPDATE STOCK", "SO # => " . $data[$key]['sono']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  private function loaddataperrecord($trno, $line)
  {
    if ($config['params']['doc'] == 'OM') {
      $this->table = 'omstock';
      $this->htable = 'homstock';
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 

    from " . $this->table . " as s
    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
    left join client as dept on dept.clientid=s.deptid
    left join client as sup on sup.clientid=s.suppid
    
    
    where s.trno =  " . $trno . "  and s.line=?
    union all

    select " . $select . "
     from " . $this->htable . " as s
    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
    left join client as dept on dept.clientid=s.deptid
    left join client as sup on sup.clientid=s.suppid
    where s.trno = " . $trno . "  and s.line=?
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {

    $addfilter = '';
    if ($config['params']['doc'] == 'OM') {
      $this->table = 'omstock';
      $this->htable = 'homstock';
      $addfilter = ' and s.rrdate is not null';
    }

    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $limit = '';
    $filtersearch = "";
    $searcfield = $this->fields;
    $search = '';
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
      $l = $limit;
    }

    $qry = "select " . $select . " 
    from " . $this->table . " as s 
    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
    left join client as dept on dept.clientid=s.deptid
    left join client as sup on sup.clientid=s.suppid
    where s.trno = " . $trno . " " . $filtersearch . $addfilter . " 
    union all 
    select " . $select . " 
    from " . $this->htable . " as s
    left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
    left join client as dept on dept.clientid=s.deptid
    left join client as sup on sup.clientid=s.suppid
    where s.trno = " . $trno . " " . $filtersearch . $addfilter . " 
    order by line $l";
    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Part Logs',
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
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 10) { // afti
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
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
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    $paramstr = "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received";

    if ($config['params']['companyid'] == 8) { //maxipro
      $paramstr .= " , '$username' as prepared ";
    } else {
      $paramstr .= " ,'' as prepared ";
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select part_id, part_code, part_name from part_masterfile
      order by part_id";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_part_masterfile_layout($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_part_PDF($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
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
    $str .= $this->reporter->col('PART MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Part Name', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_part_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['part_code'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['part_name'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
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

  private function rpt_part_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    switch ($companyid) {
      case 3: //conti
      case 14: //majesty
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $reporttimestamp = $this->reporter->setreporttimestamp($filters, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        break;
      case 8: //maxipro
        break;
      default:
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        break;
    }
    if ($companyid == 8) { //maxipro
      PDF::Image('public/images/reports/mdc.jpg', '40', '20', 120, 65);
      PDF::Image('public/images/reports/tuv.jpg', '640', '20', 120, 65);

      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
    } else {
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    }


    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(600, 20, "Part Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_part_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

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
    $this->rpt_part_PDF_header_PDF($data, $filters);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {
      $partname = $this->reporter->fixcolumn([$data[$i]['part_name']], '150');
      $maxrow = 1;

      $countarr = count($partname);
      $maxrow = $countarr;

      if ($data[$i]['part_name'] == '') {
      } else {
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(700, 10, isset($partname[$r]) ? $partname[$r] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);
        }
      }

      if (intVal($i) + 1 == $page) {
        $this->rpt_part_PDF_header_PDF($data, $filters);
        $page += $count;
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

} //end class
