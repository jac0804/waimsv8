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

class viewsupplierinv
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INVENTORY HISTORY';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1200px;max-width:1200px;';
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

    $clientid = $config['params']['clientid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);

    $dateid = 0;
    $docno = 1;
    $barcode = 2;
    $itemdesc = 3;
    $rrqty = 4;
    $isqty = 5;
    $uom = 6;
    $cost = 7;
    $isamt = 8;
    $loc = 9;
    $pallet = 10;
    $location = 11;
    $ref = 12;
    $plno = 13;
    $shipmentno = 14;
    $invoiceno = 15;

    $columns = ['dateid', 'docno', 'barcode', 'itemdesc', 'rrqty', 'isqty', 'uom', 'cost', 'isamt', 'loc', 'pallet', 'location', 'ref', 'plno', 'shipmentno', 'invoiceno'];
    $sortcolumn = ['dateid', 'docno', 'barcode', 'itemdesc', 'rrqty', 'isqty', 'uom', 'cost', 'isamt', 'loc', 'pallet', 'location', 'ref', 'plno', 'shipmentno', 'invoiceno'];

    $tab = [$this->gridname => ['gridcolumns' => $columns, 'sortcolumns' => $sortcolumn]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Received Qty';
    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'Return Qty';

    $obj[0][$this->gridname]['columns'][$rrqty]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$cost]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$isamt]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'text-center';
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$cost]['style'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$isamt]['style'] = 'text-right';

    if ($viewcost == '0') {
      $obj[0][$this->gridname]['columns'][$cost]['type'] = 'coldel';
    }

    if (!$isexpiry) {
      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0][$this->gridname]['columns'][$location]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$plno]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$shipmentno]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$invoiceno]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

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
    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'dateid.label', 'Start Date');

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'pdc');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $companyid = $config['params']['companyid'];
    $deccur = $this->companysetup->getdecimal('currency', $config['params']);
    $decqty = $this->companysetup->getdecimal('qty', $config['params']);

    $qry = "select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`glhead`.`clientid` as `clientid`,
          `glhead`.`docno` as `docno`,left(`glhead`.`dateid`,10) as `dateid`,
          `item`.`barcode` as `barcode`,`item`.`itemname` as `itemdesc`,`glstock`.`uom` as `uom`,
          `glstock`.`disc` as `disc`,
          FORMAT(`glstock`.`isamt`,'" . $deccur . "') as `isamt`,
          FORMAT(`glstock`.`isqty`,'" . $decqty . "') as `isqty`,
          FORMAT(`glstock`.`rrqty`,'" . $decqty . "') as `rrqty`,
          FORMAT(`glstock`.`cost`,'" . $deccur . "') as `cost`,
          glstock.loc as `loc`,client.client,client.clientname,client.addr,client.tel,client.email,client.tin,
          client.mobile,client.contact,client.rem,client.fax, glstock.kgs,
          (glstock.kgs * glstock.isamt) AS unitprice, (glstock.kgs * glstock.cost) AS unitcost,
          ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, '' as plno, '' as shipmentno, '' as invoiceno, glstock.ref
          from ((`glstock`
          left join `glhead` on((`glstock`.`trno` = `glhead`.`trno`)))
          left join `item` on((`item`.`itemid` = `glstock`.`itemid`)))
          left join client on client.clientid = glhead.clientid
          left join cntnum on cntnum.trno = glhead.trno
          left join pallet on pallet.line=glstock.palletid
          left join location on location.line=glstock.locid
          where
           client.clientid =$clientid and glhead.dateid>='$date' and cntnum.center ='$center'
          UNION ALL
          select `lahead`.`trno` as `trno`,`lahead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
          `lahead`.`docno` as `docno`,left(`lahead`.`dateid`,10) as `dateid`,`item`.`barcode` as `barcode`,
          `item`.`itemname` as `itemdesc`,`lastock`.`uom` as `uom`,`lastock`.`disc` as `disc`,
          FORMAT(`lastock`.`isamt`,'" . $deccur . "') as `isamt`,
          FORMAT(`lastock`.`isqty`,'" . $decqty . "') as `isqty`,
          FORMAT(`lastock`.`rrqty`,'" . $decqty . "') as `rrqty`,
          FORMAT(`lastock`.`cost`,'" . $deccur . "') as `cost`,
          lastock.loc as `loc`,client.client,client.clientname,client.addr,client.tel,client.email,
          client.tin,client.mobile,client.contact,client.rem,client.fax, lastock.kgs,
          (lastock.kgs * lastock.isamt) AS unitprice, (lastock.kgs * lastock.cost) AS unitcost,
          ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, '' as plno, '' as shipmentno, '' as invoiceno, lastock.ref
          from ((`lastock`
          left join `lahead` on((`lastock`.`trno` = `lahead`.`trno`)))
          left join `client` on((`client`.`client` = `lahead`.`client`)))
          left join item on item.itemid=lastock.itemid
          left join cntnum on cntnum.trno = lahead.trno
          left join pallet on pallet.line=lastock.palletid
          left join location on location.line=lastock.locid
          where
           client.clientid =$clientid and lahead.dateid>='$date' and cntnum.center ='$center' ";

    if ($this->companysetup->getispallet($config['params'])) {
      $qry .= " union all
      select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
      `glhead`.`docno` as `docno`,left(`glhead`.`dateid`,10) as `dateid`,
      `item`.`barcode` as `barcode`,`item`.`itemname` as `itemname`,`glstock`.`uom` as `uom`,
      `glstock`.`disc` as `disc`,
      FORMAT(`glstock`.`isamt`,'" . $deccur . "') as `isamt`,
      FORMAT(`glstock`.`isqty`,'" . $decqty . "') as `isqty`,
      FORMAT(`glstock`.`rrqty`,'" . $decqty . "') as `rrqty`,
      FORMAT(`glstock`.`cost`,'" . $deccur . "') as `cost`,
      glstock.loc as `loc`,client.client,client.clientname,client.addr,client.tel,client.email,client.tin,
      client.mobile,client.contact,client.rem,client.fax, glstock.kgs,
      (glstock.kgs * glstock.isamt) AS unitprice, (glstock.kgs * glstock.cost) AS unitcost,
      ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, plh.plno, plh.shipmentno, plh.invoiceno, glstock.ref
      from `lastock`  as glstock
      left join `lahead` as glhead on `glstock`.`trno` = `glhead`.`trno`
      left join `item` on `item`.`itemid` = `glstock`.`itemid`
      left join hplhead as plh on plh.trno=glstock.refx
      left join client on client.clientid = glstock.suppid
      left join cntnum on cntnum.trno = glhead.trno
      left join pallet on pallet.line=glstock.palletid
      left join location on location.line=glstock.locid
      where glhead.doc='RP' and client.clientid = " . $clientid . " and glhead.dateid>='" . $date . "' and cntnum.center ='" . $center . "'
      union all
      select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`glhead`.`clientid` as `clientid`,
      `glhead`.`docno` as `docno`,left(`glhead`.`dateid`,10) as `dateid`,
      `item`.`barcode` as `barcode`,`item`.`itemname` as `itemname`,`glstock`.`uom` as `uom`,
      `glstock`.`disc` as `disc`,
      FORMAT(`glstock`.`isamt`,'" . $deccur . "') as `isamt`,
      FORMAT(`glstock`.`isqty`,'" . $decqty . "') as `isqty`,
      FORMAT(`glstock`.`rrqty`,'" . $decqty . "') as `rrqty`,
      FORMAT(`glstock`.`cost`,'" . $deccur . "') as `cost`,
      glstock.loc as `loc`,client.client,client.clientname,client.addr,client.tel,client.email,client.tin,
      client.mobile,client.contact,client.rem,client.fax, glstock.kgs,
      (glstock.kgs * glstock.isamt) AS unitprice, (glstock.kgs * glstock.cost) AS unitcost,
      ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, plh.plno, plh.shipmentno, plh.invoiceno, glstock.ref
      from `glstock`  as glstock
      left join `glhead` as glhead on `glstock`.`trno` = `glhead`.`trno`
      left join `item` on `item`.`itemid` = `glstock`.`itemid`
      left join hplhead as plh on plh.trno=glstock.refx
      left join client on client.clientid = glstock.suppid
      left join cntnum on cntnum.trno = glhead.trno
      left join pallet on pallet.line=glstock.palletid
      left join location on location.line=glstock.locid
      where glhead.doc='RP' and client.clientid = " . $clientid . " and glhead.dateid>='" . $date . "' and cntnum.center ='" . $center . "'
      ";
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
