<?php

namespace App\Http\Classes\modules\mallentry;

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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entryothercharges
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BILLABLE ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'chargesbilling';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['description', 'amt', 'rem'];
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
    $this->tableentryClass = new tableentryClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 138
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $action = 0;
    $description = 1;
    $amt = 2;
    $rem = 3;


    $columns = ['action', 'description', 'amt', 'rem'];
    $sortcolumns =  ['action', 'description', 'amt', 'rem'];

    $tab = [$this->gridname => ['gridcolumns' => $columns, 'sortcolumns' => $sortcolumns]];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";

    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";


    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['cline'] = 0;
    $data['description'] = '';
    $data['amt'] = 0;
    $data['rem'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";

    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $data[$key][$value2];
        }


        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['description']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function delete($config)
  {
    $row = $config['params']['row'];
    $check = $this->coreFunctions->getfieldvalue("chargesbilling", "cline", "cline=?", [$row['line']]);

    if (strlen($check) > 0) {
      return ['status' => false, 'msg' => 'DELETE failed,already used...'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['description']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  public function loaddata($config)
  {
    $clientid = $config['params']['tableid'];

    $qry = "select c.line,c.cline,o.description,c.amt,c.rem,'' as bgcolor
    from ocharges as o left join chargesbilling as c on o.line = c.cline
    where o.description<> '' or (c.clientid = ? and c.bmonth =?
    and c.byear =? and c.center =?)
    order by c.line";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupasset':
        return $this->lookupasset($config);
        break;
      case 'lookuprevenue':
        return $this->lookuprevenue($config);
        break;
      case 'whlog':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookupasset($config)
  {
    $plotting = array('asset' => 'acnoid', 'assetaccount' => 'acno');
    $plottype = 'plotgrid';
    $title = 'Assets';

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
      ['name' => 'acno', 'label' => 'Account No.', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'alias', 'label' => 'Alias', 'align' => 'left', 'field' => 'alias', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select acnoid,acno,acnoname,alias from coa where cat = 'A'";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuprevenue($config)
  {
    $plotting = array('revenue' => 'acnoid', 'revenueaccount' => 'acno');
    $plottype = 'plotgrid';
    $title = 'Revenue';

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
      ['name' => 'acno', 'label' => 'Account No.', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'alias', 'label' => 'Alias', 'align' => 'left', 'field' => 'alias', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select acnoid,acno,acnoname,alias from coa where cat = 'R'";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Billable Item Logs',
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
} //end class
