<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\modules\customerservice\ca;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class entryboxdetails
{
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $logger;

  public $modulename = 'BOX DETAILS';
  public $gridname = 'inventory';
  private $fields = ['rrqty', 'qty'];
  private $table = 'boxinginfo';
  private $htable = 'hboxinginfo';

  private $stock = 'lastock';
  private $hstock = 'glstock';

  public $style = 'width:100%;';
  public $showclosebtn = true;

  public function __construct()
  {
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2037, 'edit' => 2037);
    return $attrib;
  }

  public function createTab($config)
  {
    $trno = isset($config['params']['tableid']) ? $config['params']['tableid'] : 0;
    if ($trno) {
      $checkerdone = $this->coreFunctions->datareader("select checkerdone as value from cntnuminfo where trno=?", [$trno]);
    } else {
      $checkerdone = true;
    }

    $action = 0;
    $barcode = 1;
    $itemdesc = 2;
    $isqty = 3;
    $boxno = 4;

    $cols = ['action', 'barcode', 'itemdesc', 'isqty', 'boxno'];

    $tab = [$this->gridname => ['gridcolumns' => $cols]];
    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$isqty]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$isqty]['align'] = "left";

    if ($checkerdone) {
      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['showtotal'] = false;

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry($config)
  {
    $qry = "b.boxno,  b.itemid, item.barcode, item.itemname as itemdesc, b.trno, b.line,
        round(b.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty";
    return $qry;
  }

  public function loaddata($config)
  {
    $boxfilter = '';
    $trno = $config['params']['row']['trno'];
    if (isset($config['params']['row']['boxno'])) {
      $box = $config['params']['row']['boxno'];
      $boxfilter = ' and b.boxno=' . $box;
    }

    $table = $this->table;
    $stock = $this->stock;
    if (isset($config['params']['row']['isposted'])) {
      if ($config['params']['row']['isposted'] == 'true') {
        $table = $this->htable;
        $stock = $this->hstock;
      }
    }

    $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
    if ($posted) {
      $table = $this->htable;
      $stock = $this->hstock;
    }

    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $table . " as b
      left join " . $stock . " as s on s.trno=b.trno and s.itemid=b.itemid
      left join item on item.itemid=b.itemid where b.trno=? " . $boxfilter . "
      group by b.boxno,  b.itemid, item.barcode, item.itemname, b.trno, b.qty, b.line
      order by b.boxno";

    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function delete($config)
  {
    $trno = $config['params']['tableid'];
    $row = $config['params']['row'];

    $checkerdone = $this->coreFunctions->datareader("select checkerdone as value from cntnuminfo where trno=?", [$trno]);
    if ($checkerdone) {
      return ['status' => false, 'msg' => 'Cannot remove from the box, already done by the checker'];
    }

    $line = $row['line'];
    $itemid = $row['itemid'];
    $barcode = $row['barcode'];
    $qty = $row['isqty'];
    $boxno = $row['boxno'];

    $status = false;
    $msg = '';

    $this->coreFunctions->execqry("delete from boxinginfo where line=?", "delete", [$line]);
    $status = true;
    $msg = 'Successfully removed from the box.';

    $this->logger->sbcwritelog($trno, $config, 'CHECKER', 'Removed from box ' .  $boxno . ', Item: ' . $barcode . ', Qty: ' . $qty, "table_log");

    return ['status' => $status, 'msg' => $msg];
  }
}
