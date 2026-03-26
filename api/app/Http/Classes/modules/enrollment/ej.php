<?php

namespace App\Http\Classes\modules\enrollment;

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
use App\Http\Classes\SBCPDF;

class ej
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Report Card Setup';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_rchead';
  public $stock = 'en_rcdetail';
  public $hstock = 'en_rcdetail';

  public $hhead = 'en_rchead';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'courseid', 'levelid', 'ischinese'];

  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
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
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2522,
      'edit' => 2521,
      'new' => 2523,
      'save' => 2524,
      'change' => 2526,
      'delete' => 2525,
      'print' => 2531,
      'lock' => 2527,
      'unlock' => 2528,

      'additem' => 2530,
      'edititem' => 2531,
      'deleteitem' => 2532
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listdocument', 'listdate', 'listcourse', 'listlevels'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'course.coursecode', 'level.levels', 'p.code'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }


    $condition = '';

    $qry = "select head.trno, head.docno, left(head.dateid,10) as dateid, 'DRAFT' as status, head.courseid, head.levelid, head.ischinese, course.coursecode, level.levels from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join en_course as course on course.line=head.courseid left join en_levels as level on level.line=head.levelid 
    where num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? 
    " . $filtersearch . "
    order by dateid desc, docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2]);
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
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($access, $config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'code', 'title', 'year', 'section', 'times', 'order']]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'CARD DETAIL';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace:normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][2]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['showtotal'] = false;
    // $obj[0][$this->gridname]['descriptionrow'] = ['code', 'title'];
    $obj[0][$this->gridname]['descriptionrow'] = [];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'lcoursecode'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'coursename'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'coursename.class', 'cscoursename sbccsreadonly');

    $fields = ['levels', 'ischinese'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'levels.class', 'sbccsreadonly');

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['courseid'] = 0;
    $data[0]['coursecode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['levels'] = '';
    $data[0]['ischinese'] = '0';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select head.trno, head.docno, head.dateid, head.courseid, head.levelid, head.ischinese, course.coursename, course.coursecode, l.levels";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join en_course as course on course.line=head.courseid
      left join en_levels as l on l.line=head.levelid
      where head.trno=? and num.center=?";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center]);

    if (!empty($head)) {
      if ($head[0]->ischinese) {
        $head[0]->ischinese = '1';
      } else {
        $head[0]->ischinese = '0';
      }
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      // $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode']);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno, stock.line, stock.code, stock.title, stock.yr as year, stock.sectionid, stock.times, stock.order, s.section, '' as bgcolor, '' as errcolor";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . " from " . $this->stock . " as stock left join en_section as s on s.line=stock.sectionid where stock.trno=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " from " . $this->stock . " as stock left join en_section as s on s.line=stock.sectionid where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line,]);
    return $stock;
  } // end function

  public function addrow($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['trno'] = $config['params']['trno'];
    $data['code'] = '';
    $data['title'] = '';
    $data['year'] = '';
    $data['sectionid'] = 0;
    $data['section'] = '';
    $data['times'] = '';
    $data['order'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function saveitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->saveperitem($config, 'all');
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function saveperitem($config, $type = '')
  {
    if ($type == '') {
      $config['params']['data'] = $config['params']['row'];
      $trno = $config['params']['data']['trno'];
    }
    $data = [
      'trno' => $config['params']['data']['trno'],
      'line' => $config['params']['data']['line'],
      'code' => $config['params']['data']['code'],
      'title' => $config['params']['data']['title'],
      'yr' => $config['params']['data']['year'],
      'sectionid' => $config['params']['data']['sectionid'],
      'times' => $config['params']['data']['times'],
      'order' => $config['params']['data']['order']
    ];
    if ($data['times'] == '') $data['times'] = 0;
    if ($data['order'] == '') $data['order'] = 0;
    if ($data['line'] == 0) {
      $line = $this->coreFunctions->datareader("select line as value from " . $this->stock . " where trno=? order by line desc limit 1", [$config['params']['data']['trno']]);
      if ($line == '') $line = 0;
      $line += 1;
      $data['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($config['params']['data']['trno'], $config, 'DETAILS', 'ADD - Line:' . $line . ' Code:' . $data['code'] . ' Title:' . $data['title']);
        if ($type == '') {
          $config['params']['line'] = $data['line'];
          $row = $this->openstockline($config);
          return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
        } else {
          return true;
        }
      } else {
        if ($type == '') {
          return ['status' => false, 'msg' => 'Add item failed'];
        } else {
          return true;
        }
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $data['trno'], 'line' => $data['line']]);
      if ($type == '') {
        $row = $this->openstockline($config);
        return ['status' => true, 'row' => $row, 'msg' => 'Update item successfully'];
      } else {
        return true;
      }
    }
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'saveperitem':
        return $this->saveperitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->saveitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
      $this->setservedsubject($config['params']['data']['refx'], $config['params']['data']['linex']);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $code = $config['params']['row']['code'];
    $title = $config['params']['row']['title'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'REMOVED - Line: ' . $line . ' Code: ' . $code . ' Title: ' . $title);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end 

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
    $eiassessment = $config['params']['dataparams']['eiassessment'];
    $eischedule = $config['params']['dataparams']['eischedule'];

    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    switch ($eiassessment) {
      case 'withassess':
        switch ($eischedule) {
          case 'withsched':
            $assess = app($this->companysetup->getreportpath($config['params']))->assessment_query($config);
            $str = app($this->companysetup->getreportpath($config['params']))->assessment_report_plotting($config, $data, $assess);
            break;
          default:
            $assess = app($this->companysetup->getreportpath($config['params']))->assessment_query($config);
            $str = app($this->companysetup->getreportpath($config['params']))->assessment2_report_plotting($config, $data, $assess);
            break;
        }

        break;
      default:
        switch ($eischedule) {
          case 'withsched':
            $str = app($this->companysetup->getreportpath($config['params']))->withschedplotting($config, $data);
            break;

          default:
            $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
            break;
        }

        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
