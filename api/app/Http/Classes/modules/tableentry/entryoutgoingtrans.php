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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class entryoutgoingtrans
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LIST OF OUTGOING TRANSACTIONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'cntnum';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 12
    );

    return $attrib;
  }

  public function createTab($config)
  {

    $tabtype = $config['params']['row']['tabtype'];

    $action = 0;
    $docno = 1;
    $dateid = 2;
    $qty = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'dateid', 'qty']]];
    $stockbuttons = ['jumpmodule'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:80;whiteSpace: normal;min-width:80;";

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:140px;whiteSpace: normal;min-width:140px;";
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$qty]['label'] = "Quantity";
    $obj[0][$this->gridname]['columns'][$qty]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";


    $obj[0]['params']['trno'] = $config['params']['row']['trno'];

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  private function selectqry()
  {
    $qry = "";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function loaddata($config)
  {
    $tabtype = $config['params']['row']['tabtype'];
    $row = $config['params']['row'];
    $refx = $row['trno'];
    $item = $row['itemid'];

    $qry = "";

    $qry2 = "
      select c.trno,c.line, cntnum.doc,head.docno,head.dateid,
      stock.itemid,stock.isqty,stock.iss,c.served as qty,c.bal
      from costing as c
      left join cntnum on cntnum.trno=c.trno
      left join lastock as stock on stock.trno=c.trno and stock.line=c.line
      left join lahead as head on head.trno=stock.trno
      where head.docno is not null and
      c.refx=" . $refx . " and c.itemid=" . $item . "
      union all
      select c.trno,c.line, cntnum.doc,head.docno,head.dateid,
      stock.itemid,stock.isqty,stock.iss,c.served as qty,c.bal
      from costing as c
      left join cntnum on cntnum.trno=c.trno
      left join glstock as stock on stock.trno=c.trno and stock.line=c.line
      left join glhead as head on head.trno=stock.trno
      where head.docno is not null and
      c.refx=" . $refx . " and c.itemid=" . $item . "
      order by docno,itemid";

    $data2 = $this->coreFunctions->opentable($qry2);
    if (!empty($data2)) {
      foreach ($data2 as $key => $value) {
        $url = $this->checkdoc($value->doc);
        $doc = $value->doc;
        $trno = $value->trno;
        $docno = $value->docno;
        $dateid = $value->dateid;
        $qty = $value->qty;
        if ($url !== '') {
          if ($qry == "") {
            $qry = "select '" . $trno . "' as trno,'" . $doc . "' as doc,'" . $docno . "' as docno, '" . $dateid . "' as dateid, '" . $qty . "' as qty,'' as bgcolor, '" . $url . "' as url,'module' as moduletype";
          } else {
            $qry .= " union all select '" . $trno . "' as trno,'" . $doc . "' as doc,'" . $docno . "' as docno, '" . $dateid . "' as dateid, '" . $qty . "' as qty,'' as bgcolor,'" . $url . "' as url,'module' as moduletype";
          }
        }
      }
    }
    if ($qry == "") {
      $doc = $this->coreFunctions->getfieldvalue('cntnum', 'doc', 'trno=?', [$row['trno']]);
      $url = $this->checkdoc($doc);
      $qry = " select trno,doc,docno,'' as dateid,'' as qty,'' as bgcolor,'" . $url . "' as url,'module' as moduletype from cntnum where trno=? ";

      $data = $this->coreFunctions->opentable($qry, [$row['trno']]);
    } else {
      $data = $this->coreFunctions->opentable($qry);
    }

    return $data;
  } //end function

  public function checkdoc($doc)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'sj':
      case 'cm':
        $url = "/module/sales/";
        break;
      case 'ar':
      case 'cr':
        $url = "/module/receivable/";
        break;
      case 'dm':
      case 'rr':
        $url = "/module/purchase/";
        break;
      case 'ap':
      case 'cv':
      case 'pv':
        $url = "/module/payable/";
        break;
      case 'ds':
      case 'gc':
      case 'gd':
      case 'gj':
        $url = "/module/accounting/";
        break;
      case 'sd':
      case 'se':
      case 'sf':
        $url = "/module/warehousing/";
        break;
      case 'aj':
      case 'is':
        $url = "/module/inventory/";
        break;
      case 'st':
      case 'su':
        $url = "/module/issuance/";
        break;
      case 'mi':
        $url = "/module/construction/";
        break;
    }
    return $url;
  }
} //end class
