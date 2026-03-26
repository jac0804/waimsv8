<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class inventoryhistory_supplier_tab
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Inventory History';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $dateacquired = 0;
    $invoiceno = 1;
    $invoicedate = 2;
    $pono = 3;
    $podate = 4;
    $barcode = 5;
    $itemname = 6;
    $serialno = 7;
    $amt = 8;
    $warranty = 9;
    $buyer = 10;
    $location = 11;

    $columns = [
      'dateacquired', 'invoiceno', 'invoicedate', 'pono', 'podate', 'barcode', 'itemname',
      'serialno', 'amt', 'warranty', 'buyer', 'location'
    ];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "text-align:right; width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$dateacquired]['style'] = "text-align:center; width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$invoicedate]['style'] = "text-align:center; width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$podate]['style'] = "text-align:center; width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$warranty]['style'] = "text-align:center; width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$location]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";


    $obj[0][$this->gridname]['columns'][$invoiceno]['label'] = "Invoice #";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Value";

    $obj[0][$this->gridname]['columns'][$location]['type'] = "label";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [['start', 'end']];
    $col1 = $this->fieldClass->create($fields);

    $fields = [['refresh']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'history');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $itemid = $config['params']['clientid'];
    return $this->coreFunctions->opentable("
      select 
      adddate(left(now(), 10),-365) as start,
      left(now(), 10) as end
    ");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $qry = "
      select item.barcode, item.itemname, item.supplier as supplierid, supp.clientname as suppliername,
      left(iteminfo.dateacquired, 10) as dateacquired, iteminfo.invoiceno,
      left(iteminfo.invoicedate, 10) as invoicedate, iteminfo.pono, left(iteminfo.podate, 10) as podate,
      iteminfo.serialno, format(item.amt, " . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
      left(item.warranty, 10) as warranty,
      buyer.clientname as buyer, loc.clientname as location
      from item as item
      left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
      left join client as supp on supp.clientid = item.supplier
      left join client as buyer on buyer.clientid = iteminfo.purchaserid
      left join client as loc on loc.clientid = iteminfo.locid
      where date(iteminfo.dateacquired) between '$start' and '$end' and item.supplier = '" . $clientid . "'
    ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function



  public function getbal($config, $itemid, $wh, $uom)
  {
    $qry = "";


  } //end function




























} //end class
