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
use App\Http\Classes\modules\reportlist\hris_reports\employment_status_entry_or_change;
use App\Http\Classes\sqlquery;

class deliverydate
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Delivery Date';
  public $gridname = 'inventory';
  private $table = 'ddrlogs';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  private $fields = ['trno', 'line', 'deliverydate', 'reason'];
  public $issearchshow = true;
  public $showclosebtn = true;
  private $logger;



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
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $deliverydate = 0;
    $reason = 1;
    $editby = 2;
    $tab = [$this->gridname => ['gridcolumns' => ['deliverydate', 'reason', 'editby']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$reason]['label'] = "Reason";

    $obj[0][$this->gridname]['columns'][$deliverydate]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$reason]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'SAVE';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action', 'editboq');
    data_set($col1, 'refresh.label', 'SAVE');

    return array('col1' => $col1);
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $trno = $config['params']['tableid'];

    if ($data[0]["reason"] == "") {
      return ['status' => false, 'msg' => 'Reason is required.', 'data' => $data, 'reloaddata' => false];
    }

    $data2 = [];
    if ($data[0]['bgcolor'] != '') {
      $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['editby'] = $config['params']['user'];
      $data2['deliverydate'] = $data[0]["deliverydate"];
      $data2['reason'] = $data[0]["reason"];
      $data2['trno'] = $trno;
      if ($data[0]['line'] == 0) {
        $this->coreFunctions->sbcinsert($this->table, $data2);
        $this->logger->sbcmasterlog(
          $trno,
          $config,
          'UPDATE' . ' - DELIVERY DATE: ' . $data[0]['deliverydate'] . ' - REASON: ' . $data[0]['reason']
        );

        if ($config['params']['doc'] == 'SQ') {
          $qttrno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno = '" . $trno . "'");
          $this->coreFunctions->sbcupdate("hqshead", ["deldate" => $data[0]["deliverydate"]], ['trno' => $qttrno]);
        } else {
          $qttrno = $this->coreFunctions->datareader("select trno as value from hsrhead where sotrno = '" . $trno . "'");
          $this->coreFunctions->sbcupdate("hsrhead", ["due" => $data[0]["deliverydate"]], ['trno' => $qttrno]);
          $qttrno = $this->coreFunctions->datareader("select qtrno as value from hsrhead where sotrno = '" . $trno . "'");
          $this->coreFunctions->sbcupdate("hqshead", ["deldate" => $data[0]["deliverydate"]], ['trno' => $qttrno]);
        }
      }
    } // end if
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'reloaddata' => true];
  } // end function 


  public function loaddata($config)
  {
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['tableid'];

    $qttrno = $this->coreFunctions->datareader("select qt.trno as value
    from hsqhead as head
    left join hqshead as qt on qt.sotrno=head.trno
    where head.trno = '" . $trno . "' LIMIT 1");

    $current_date = date("Y-m-d", strtotime($this->othersClass->getCurrentTimeStamp()));

    $qry = "
      select 
      '0' as line, 
      '0' as trno, 
      '" . $current_date . "' as deliverydate, 
      '' as reason, 
      '' as editby,
      '' as bgcolor
      union all
      select 
      ddrlogs.line,
      ddrlogs.trno,
      left(ddrlogs.deliverydate, 10) as deliverydate,
      ddrlogs.reason as reason,
      ddrlogs.editby,
      '' as bgcolor
      FROM ddrlogs as ddrlogs
      where ddrlogs.trno =?";

    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } //end function

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "
      select trno, doc, task, dateid, user
      from " . $this->tablelogs . "
      where doc = ? and trno = ?
      union all 
      select trno, doc, task, dateid, user
      from " . $this->tablelogs_del . "
      where doc = ? and trno = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc, $trno, $doc, $trno]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
