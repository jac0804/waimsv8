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

class viewstockcardcd
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Canvass Sheet';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
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
    $itemid = $config['params']['clientid'];
    $item = $this->othersClass->getitemname($itemid);
    $companyid = $config['params']['companyid'];

    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $docno = 0;
    $dateid = 1;
    $listclientname = 2;
    $rrqty = 3;
    $rrcost = 4;
    $disc = 5;
    $ext = 6;
    $wh = 7;
    $pending = 8;
    $status = 9;
    $notes = 10;
    $ref = 11;

    $columns = ['docno', 'dateid', 'listclientname', 'rrqty', 'rrcost', 'disc', 'ext', 'wh', 'qa', 'canvasstatus', 'rem', 'ref'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$docno]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][$dateid]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$listclientname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$listclientname]['align'] = 'left';

    if ($companyid != 3) { //not conti
      $obj[0][$this->gridname]['columns'][$wh]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$pending]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$status]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$notes]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$ref]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Quantity';
    }

    $obj[0][$this->gridname]['columns'][$wh]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$wh]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$pending]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$pending]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][$status]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$notes]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$notes]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$ref]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'text-align:right;width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right;width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'text-align:right;width:60px;whiteSpace: normal;min-width:60px;';





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
    $fields = [['dateid', 'luom']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'luom.lookupclass', 'uomledger');


    $fields = ['wh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'wh.lookupclass', 'whledger');


    $fields = [['refresh']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'history');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $itemid = $config['params']['clientid'];
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
    $wh = $this->companysetup->getwh($config['params']);

    return $this->coreFunctions->opentable("select adddate(left(now(),10),-360) as dateid,'$wh' as wh, '$uom' as uom");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $date = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $uom = $config['params']['dataparams']['uom'];
    $wh = $config['params']['dataparams']['wh'];

    $filter = '';


    if ($wh == '') {
      $filter = "";
    } else {
      $filter = " and wh.client ='$wh' ";
    }

    $qry = "select h.trno, h.doc, h.docno,left(h.dateid,10) as dateid, h.clientname, 
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, s.disc, 
        FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost, 
        FORMAT(s.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, wh.client as wh,
        round((s.qty-s.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
        case when s.status = 0 then 'Pending'
        when s.status = 1 then 'Approved'
        when s.status = 2 then 'Rejected'
        end as canvasstatus,s.rem,s.ref
        from cdstock as s left join cdhead as h on h.trno=s.trno
        left join item on item.itemid=s.itemid
        left join uom on uom.itemid=item.itemid and  uom.uom='$uom'
        left join transnum as cntnum on cntnum.trno = h.trno
        left join client as wh on wh.clientid=s.whid
        where item.itemid='$itemid' and h.dateid>='$date' and cntnum.center ='$center' $filter
        UNION ALL
        select h.trno, h.doc, h.docno,left(h.dateid,10) as dateid, h.clientname, 
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, s.disc, 
        FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost, 
        FORMAT(s.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, wh.client as wh,
        round((s.qty-s.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
        case when s.status = 0 then 'Pending'
        when s.status = 1 then 'Approved'
        when s.status = 2 then 'Rejected'
        end as canvasstatus,s.rem,s.ref
        from hcdstock as s left join hcdhead as h on h.trno=s.trno
        left join item on item.itemid=s.itemid left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = h.trno
        left join client as wh on wh.clientid=s.whid 
        where item.itemid='$itemid'
        and h.dateid>='$date' and cntnum.center ='$center'  $filter   order by dateid;";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function

} //end class
