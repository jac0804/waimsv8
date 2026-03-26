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

class entryhistoricalcomments
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'HISTORICAL COMMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'LIST';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;';
  public $showclosebtn = false;
  public $showsearch = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $isposted = $this->othersClass->isposted2($config['params']['tableid'], "cntnum");

    $rem = 0;
    $createby = 1;
    $createdate = 2;
    $seendate = 3;
    if ($config['params']['companyid'] == 29) {
      $gridcolumns = ['rem', 'createby', 'createdate', 'seendate'];
    } else {
      $gridcolumns = ['rem', 'createby', 'createdate'];
    }

    $stockbuttons = [];
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$rem]['style'] =  'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
    $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$createby]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$createdate]['readonly'] = true;
    if ($config['params']['companyid'] == 29) {
      $obj[0][$this->gridname]['columns'][$seendate]['readonly'] = true;
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function loaddata($config)
  {
    if ($config['params']['moduletype'] == 'dashboard') {
      $doc = 'TM';
    } else {
      $doc = $config['params']['doc'];
    }
    $userid = 0;
    switch ($doc) {
      case 'RR':
        $trno = isset($config['params']['ledgerdata']['rrtrno']) ? $config['params']['ledgerdata']['rrtrno'] : 0;
        $line = isset($config['params']['ledgerdata']['rrline']) ? $config['params']['ledgerdata']['rrline'] : 0;

        if (isset($config['params']['dataparams'])) {
          $trno = isset($config['params']['dataparams']['rrtrno']) ? $config['params']['dataparams']['rrtrno'] : 0;
          $line = isset($config['params']['dataparams']['rrline']) ? $config['params']['dataparams']['rrline'] : 0;
        }
        break;
      case 'TM':
      case 'TK':
        if (isset($config['params']['dataparams'])) {
          $trno = isset($config['params']['dataparams']['tmtrno']) ? $config['params']['dataparams']['tmtrno'] : 0;
          $line = isset($config['params']['dataparams']['tmline']) ? $config['params']['dataparams']['tmline'] : 0;
        }

        if (isset($config['params']['row'])) {
          $trno = isset($config['params']['row']['tmtrno']) ? $config['params']['row']['tmtrno'] : 0;
          $line = isset($config['params']['row']['tmline']) ? $config['params']['row']['tmline'] : 0;
        }
        break;
      case 'DY':
        if (isset($config['params']['dataparams'])) {
          $trno = isset($config['params']['dataparams']['dytrno']) ? $config['params']['dataparams']['dytrno'] : 0;
          $line = isset($config['params']['dataparams']['dytrno']) ? $config['params']['dataparams']['dytrno'] : 0;
          $userid = isset($config['params']['dataparams']['userid']) ? $config['params']['dataparams']['userid'] : 0;
        }

        if (isset($config['params']['row'])) {
          $trno = isset($config['params']['row']['dytrno']) ? $config['params']['row']['dytrno'] : 0;
          $line = isset($config['params']['row']['dytrno']) ? $config['params']['row']['dytrno'] : 0;
          $userid = isset($config['params']['row']['userid']) ? $config['params']['row']['userid'] : 0;
        }
        break;
      default:
        $trno = isset($config['params']['ledgerdata']['cvtrno']) ? $config['params']['ledgerdata']['cvtrno'] : 0;
        $line = isset($config['params']['ledgerdata']['cvline']) ? $config['params']['ledgerdata']['cvline'] : 0;

        if (isset($config['params']['dataparams'])) {
          $trno = isset($config['params']['dataparams']['cvtrno']) ? $config['params']['dataparams']['cvtrno'] : 0;
          $line = isset($config['params']['dataparams']['cvline']) ? $config['params']['dataparams']['cvline'] : 0;
        }
        break;
    }

    $this->coreFunctions->LogConsole("Trno:" . $trno . ' - Line:' . $line);

    if ($trno != 0 && $line != 0) {
      switch ($doc) {
        case 'CV':

          $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate from headprrem as pr where pr.cvtrno=$trno and pr.cvline=$line
            order by pr.line desc";
          break;
        case 'RR':
          $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate from headprrem as pr where pr.rrtrno=$trno and pr.rrline=$line
            order by pr.line desc";
          break;
        case 'TM':
        case 'TK':
          $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate,pr.seendate from headprrem as pr where pr.tmtrno=$trno and pr.tmline=$line
            order by pr.line desc";
          break;
        case 'DY':
          $qry = "select ifnull(pr.rem,'') as rem,pr.createby,pr.createdate,pr.seendate,pr.touser from headprrem as pr where pr.dytrno=$trno order by pr.line desc";
          break;
      }

      $data = $this->coreFunctions->opentable($qry);
      return $data;
    } else {
      return [];
    }
  }

  public function lookupsetup($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'additemcomponent':
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
          return ['status' => false, 'msg' => 'Transaction has already been posted.', 'data' => []];
        }

        return $this->lookupitem($config);
        break;
      case 'lookupemployee':
        return $this->lookupemployee($config);
        break;
      case 'saveallentry':
        return $this->saveallentry($config);
        break;
    }
  } //end function

  public function saveallentry($config)
  {

    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    if ($isposted) {
      return ['status' => false, 'msg' => 'Transaction has already been posted.', 'data' => []];
    }

    $row = $config['params']['data'];
    foreach ($config['params']['data'] as $key => $row) {
      $data = [];
      foreach ($this->fields as $key => $value) {
        $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
      }

      if ($row['line'] == 0) {
      } else {
        if ($row['bgcolor'] != '') {
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data['encodedby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate("tripdetail", $data, ['trno' => $data['trno'], 'line' => $data['line']]);
        }
      }
    }

    $returnrow = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $returnrow, 'reloadhead' => true];
  }

  private function loaddataperrecord($config, $trno, $line)
  {
    $qry = "select d.trno, d.line, d.itemid, item.barcode, item.itemname, '' as bgcolor from tripdetail as d left join item on item.itemid=d.itemid where d.trno=? and d.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchitem2',
      'title' => 'List of Products',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array();
    $col = array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    return ['status' => true, 'msg' => 'ok', 'data' => [], 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function> 

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = [];
        $data['trno'] = $trno;
        $data['line'] = 0;
        $data['clientid'] = 0;
        $data['itemid'] = $row['itemid'];
        $data['barcode'] = $row['barcode'];
        $data['itemname'] = $row['itemname'];
        $data['activity'] = '';
        $data['rate'] = "0.00";
        $data['empcode'] = '';
        $data['empname'] = '';

        $insertdata = [];
        foreach ($this->fields as $key => $value) {
          $insertdata[$value] = $this->othersClass->sanitizekeyfield($value, $data[$value]);
        }

        $qry = "select line as value from tripdetail where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$insertdata['trno']]);
        if ($line == '') {
          $line = 0;
        }
        $line = $line + 1;
        $data['line'] = $line;
        $insertdata['line'] = $data['line'];
        $insertdata['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
        $insertdata['encodedby'] = $config['params']['user'];
        $this->coreFunctions->sbcinsert("tripdetail", $insertdata);

        return ['status' => true, 'msg' => 'New data was added.', 'data' => $data];
        break;
    }
  } // end function

  public function lookupemployee($config)
  {
    //default
    $plotting = array('clientid' => 'clientid', 'empcode' => 'client',  'empname' => 'clientname');
    $plottype = 'plotgrid';
    $title = 'List of Employe';
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
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select clientid,client,clientname from client where isemployee=1 order by clientname";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function
} //end class
