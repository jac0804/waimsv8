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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class paymenttype
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PAYMENT TYPE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'checktypes';
  public $tablelogs = 'masterfile_log';
  private $othersClass;
  private $tableentryClass;
  public $style = 'width:100%;';
  private $fields = ['type', 'clientid', 'acnoid', 'inactive', 'dlock'];
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
      'load' => 4502
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $acno = 0;
    $type = 1;
    $client = 2;
    $clientname = 3;
    $dlock = 4;
    $inactive = 5;


    $columns = ['acno', 'type', 'client', 'clientname', 'dlock', 'inactive'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$acno]['label'] = "Account";
    $obj[0][$this->gridname]['columns'][$acno]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$acno]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$acno]['lookupclass'] = "lookupacno";
    $obj[0][$this->gridname]['columns'][$acno]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$type]['label'] = "Payment Type";
    $obj[0][$this->gridname]['columns'][$type]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$type]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$client]['label'] = "Supplier Code";
    $obj[0][$this->gridname]['columns'][$client]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = "lookupclient";
    $obj[0][$this->gridname]['columns'][$client]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Supplier Name";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$dlock]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";

    $obj[0][$this->gridname]['columns'][$inactive]['label'] = "Inactive";
    $obj[0][$this->gridname]['columns'][$inactive]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$inactive]['name'] = "inactive";
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
    $data['type'] = '';
    $data['acnoid'] = 0;
    $data['acno'] = '';
    $data['clientid'] = 0;
    $data['client'] = '';
    $data['clientname'] = '';
    $data['dlock'] = '';
    $data['inactive'] = 'false';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "pt.line, pt.type, pt.clientid, pt.acnoid,pt.dlock, (case when pt.inactive=1 then 'true' else 'false' end) as inactive";
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

        if ($data2['inactive'] == 'false') {
          $data2['inactive'] = 0;
        } else {
          $data2['inactive'] = 1;
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data2['dlock'] = $current_timestamp;

        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['type']);
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

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ", concat(coa.acno,'~',coa.acnoname) as acno, 
            client.client,client,clientname "
      . " from " . $this->table . " as pt
            left join client on client.clientid = pt.clientid
            left join coa on coa.acnoid = pt.acnoid
            order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupacno':
        return $this->lookupacno($config);
        break;
      case 'lookupclient':
        return $this->lookupclient($config);
        break;
      case 'whlog':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookupacno($config)
  {
    $plotting = array('acnoid' => 'acnoid', 'acno' => 'acno');
    $plottype = 'plotgrid';
    $title = 'Chart of Accounts';

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
      ['name' => 'acnos', 'label' => 'Account No.', 'align' => 'left', 'field' => 'acnos', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'alias', 'label' => 'Alias', 'align' => 'left', 'field' => 'alias', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select acnoid,acno as acnos,acnoname,alias, concat(coa.acno,'~',coa.acnoname) as acno from coa";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupclient($config)
  {
    $plotting = array('clientid' => 'clientid', 'client' => 'client', 'clientname' => 'clientname');
    $plottype = 'plotgrid';
    $title = 'List of Suppliers';

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
      ['name' => 'client', 'label' => 'Supplier Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Supplier Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'addr', 'label' => 'Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select clientid,client,clientname,addr from client where issupplier= 1";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Payment Type Logs',
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
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
