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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entrystockcardwh
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Balance per Warehouse';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  public $tablelogs = '';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
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
      'load' => 3626
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['tableid'];

    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname . ' ~ ' . $item[0]->uom;

    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $clientname = 0;
    $bal = 1;
    $itemname = 2;
    $tab = [$this->gridname => [
      'gridcolumns' => ['clientname', 'bal', 'itemname']
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = ['bal'];

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Warehouse Name';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
    $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'text-align:right; width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$bal]['readonly'] = true;
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'REFRESH';
    $obj[0]['icon'] = 'refresh';
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['acno'] = '';
    $data['alias'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }


  public function saveallentry($config)
  {
    $data = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Data was refresh.', 'data' => $data];
  } // end function 

  public function loaddata($config)
  {
    $itemid =  $config['params']['tableid'];

    $center = $config['params']['center'];

    $filtercenter = " and cntnum.center='" . $center . "' ";
    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }

    $expiryfield = '';

    $isexpiry = false;
    if ($isexpiry) {
      $expiryfield = ',rrstatus.loc,rrstatus.expiry';
    }
    $qry = "select client.clientname as clientname,rrstatus.whid,rrstatus.itemid,rrstatus.itemid as trno,round(sum(rrstatus.bal),2) as bal,'BALANCEWH' as tabtype" . $expiryfield . ", 
           item.itemname, item.barcode    
      from rrstatus
              left join client on client.clientid = rrstatus.whid
              left join cntnum on cntnum.trno=rrstatus.trno
              left join item on item.itemid=rrstatus.itemid
              where rrstatus.itemid ='" . $itemid . "'" . $filtercenter . "
              group by client.clientname,rrstatus.whid,rrstatus.itemid,item.itemname, item.barcode" . $expiryfield . " having sum(rrstatus.bal)<>0";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
