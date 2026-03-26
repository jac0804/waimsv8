<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class viewshowstockitems
{
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;

  public $modulename = 'ITEM DETAILS';
  public $gridname = 'inventory';
  private $fields = [];
  private $table = 'lastock';
  private $htable = 'glstock';
  public $style = 'width:1000px;min-width:1000px;max-width:1000px;';
  public $showclosebtn = true;

  public function __construct()
  {
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $barcode = 0;
    $itemname = 1;
    $qty = 2;
    $picker = 3;
    $location = 4;
    $pallet = 5;

    $cols = ['barcode', 'itemname', 'qty', 'picker', 'location', 'pallet'];

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px; text-align:left";
    $obj[0][$this->gridname]['columns'][$barcode]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:200px;whiteSpace: normal;min-width:200px; text-align:left";
    $obj[0][$this->gridname]['columns'][$itemname]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";

    $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align:center";
    $obj[0][$this->gridname]['columns'][$qty]['align'] = "center";
    $obj[0][$this->gridname]['columns'][$qty]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";

    $obj[0][$this->gridname]['columns'][$picker]['style'] = "width:150px;;whiteSpace: normal;min-width:150px;; text-align:left";
    $obj[0][$this->gridname]['columns'][$picker]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$picker]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$location]['style'] = "width:150px;;whiteSpace: normal;min-width:150px;; text-align:left";
    $obj[0][$this->gridname]['columns'][$location]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$location]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$pallet]['style'] = "width:150px;;whiteSpace: normal;min-width:150px;; text-align:left";
    $obj[0][$this->gridname]['columns'][$pallet]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$pallet]['type'] = "label";

    $obj[0][$this->gridname]['showtotal'] = false;

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
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);

    $boxfilter = '';
    $trno = $config['params']['row']['trno'];

    $qry = "select item.barcode, item.itemname, FORMAT(stock.iss, " . $qtydec . ") as qty, 
      picker.clientname as picker, loc.loc as location, pallet.name as pallet
      from lastock as stock
      left join item as item on item.itemid = stock.itemid
      left join client as picker on picker.clientid = stock.pickerid
      left join location as loc on loc.line = stock.locid 
      left join pallet on pallet.line=stock.palletid
      where stock.trno = ?
      union all 
      select item.barcode, item.itemname, FORMAT(stock.iss, " . $qtydec . ") as qty, 
      picker.clientname as picker, loc.loc as location, pallet.name as pallet
      from glstock as stock
      left join item as item on item.itemid = stock.itemid
      left join client as picker on picker.clientid = stock.pickerid
      left join location as loc on loc.line = stock.locid 
      left join pallet on pallet.line=stock.palletid
      where stock.trno = ?";

    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }
}
