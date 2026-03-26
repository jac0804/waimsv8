<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class viewshowstockitems_whchecker
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
    $uom = 3;
    $subcode = 4;
    $partno = 5;
    $dqty = 6;

    $cols = ['barcode', 'itemname', 'qty', 'uom', 'subcode', 'partno', 'dqty'];

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

    $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:150px;;whiteSpace: normal;min-width:150px;; text-align:left";
    $obj[0][$this->gridname]['columns'][$uom]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$uom]['type'] = "label";

    $obj[0]['inventory']['columns'][$partno]['label'] = 'Part No.';
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
    $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$subcode]['label'] = 'Old SKU';
    $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
    $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
    $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$dqty]['label'] = 'Per Box QTY';
    $obj[0]['inventory']['columns'][$dqty]['type'] = 'label';
    $obj[0]['inventory']['columns'][$dqty]['align'] = 'left';
    $obj[0]['inventory']['columns'][$dqty]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

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
      picker.clientname as picker, loc.loc as location, pallet.name as pallet,
      item.subcode, item.partno, stock.uom, round(item.dqty, " . $qtydec . ") as dqty
      from lastock as stock
      left join item as item on item.itemid = stock.itemid
      left join client as picker on picker.clientid = stock.pickerid
      left join location as loc on loc.line = stock.locid 
      left join pallet on pallet.line=stock.palletid
      where stock.trno = ?
      union all 
      select item.barcode, item.itemname, FORMAT(stock.iss, " . $qtydec . ") as qty, 
      picker.clientname as picker, loc.loc as location, pallet.name as pallet,
      item.subcode, item.partno, stock.uom, round(item.dqty, " . $qtydec . ") as dqty
      from glstock as stock
      left join item as item on item.itemid = stock.itemid
      left join client as picker on picker.clientid = stock.pickerid
      left join location as loc on loc.line = stock.locid 
      left join pallet on pallet.line=stock.palletid
      where stock.trno = ?";

    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }
}
