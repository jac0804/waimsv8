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

class entryempassets
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ASSETS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'whdoc';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'wh_log';
  public $tablelogs_del = 'del_wh_log';
  private $fields = ['docno', 'issued', 'expiry', 'dateid', 'oic1', 'oic2', 'status', 'whid'];
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
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    $action = 0;
    $itemname = 1;
    $serialno = 2;
    $ispermanent = 3;
    $returndate = 4;
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'itemname', 'serialno', 'ispermanent', 'returndate']
      ]
    ];
    $stockbuttons = ['assetin', 'assetout'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name';

    $obj[0][$this->gridname]['columns'][$serialno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$serialno]['style'] = 'width: 400px;whiteSpace: normal;min-width:400px;max-width:400px';

    $obj[0][$this->gridname]['columns'][$ispermanent]['type'] = 'label';

    if ($doc != "EMPLOYEE") {
      $obj[0][$this->gridname]['columns'][$returndate]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$returndate]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$returndate]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['docno'] = '';
    $data['issued'] = date('Y-m-d');
    $data['expiry'] = date('Y-m-d');
    $data['dateid'] = date('Y-m-d');
    $data['oic1'] = '';
    $data['oic2'] = '';
    $data['rem'] = '';
    $data['status'] = '';
    $data['whid'] = $config['params']['tableid'];
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry($config, $itemid = 0)
  {
    $tableid = $config['params']['tableid'];

    $filterA = " and i.returndate is null ";
    $filterB = " and s.returndate is null ";

    if ($config['params']['doc'] == "EMPLOYEE") {
      $filterA = "";
      $filterB = "";
    }

    $filter = "";
    if ($itemid) $filter = " and item.itemid=" . $itemid;

    $qry = "select i.clientid, s.itemid, item.barcode, item.itemname, info.serialno, if(i.ispermanent=1,'YES','NO') as ispermanent, i.returndate         
          from issueitem as i left join issueitemstock as s on s.trno=i.trno left join item on item.itemid=s.itemid left join iteminfo as info on info.itemid=s.itemid
          where i.clientid=" . $tableid . " " . $filter .  $filterA . "
          union all
          select emp.clientid, s.itemid, item.barcode, item.itemname, info.serialno, 'GATE PASS' as ispermanent, s.returndate
          from hgphead as h left join hgpstock as s on s.trno=h.trno left join client as emp on emp.client=h.client
          left join item on item.itemid=s.itemid left join iteminfo as info on info.itemid=s.itemid
          where emp.clientid=" . $tableid . " " .  $filter .  $filterB;

    return $qry;
  }

  public function saveallentry($config)
  {
    $returndata = [];
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    return [];
  } //end function

  public function delete($config)
  {
    return [];
  }


  private function loaddataperrecord($config, $itemid)
  {
    $data = $this->coreFunctions->opentable($this->selectqry($config, $itemid));
    return $data;
  }

  public function loaddata($config)
  {
    $data = $this->coreFunctions->opentable($this->selectqry($config));
    return $data;
  }


  public function tableentrystatus($config)
  {
    $row = $config['params']['row'];
    $data = ['clientid' => $row['clientid'], 'itemid' => $row['itemid'], 'type' => $config['params']['action2'] == 'assetin' ? 'IN' : 'OUT', 'createby' => $config['params']['user'], 'dateid' => $this->othersClass->getCurrentTimeStamp()];
    $this->coreFunctions->sbcinsert("assetgplogs", $data);

    $returnrow = $this->loaddataperrecord($config, $row['itemid']);
    return ['status' => true, 'msg' => 'Successfully logged', 'row' => $returnrow];
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
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Asset Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'createby', 'label' => 'User', 'align' => 'left', 'field' => 'createby', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Item Name', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];
    $qry = "select a.createby, a.type, a.dateid, item.itemname from assetgplogs as a left join item on item.itemid=a.itemid where a.clientid=? order by a.dateid desc";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
