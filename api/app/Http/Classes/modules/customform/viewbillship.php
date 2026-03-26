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

class viewbillship
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INVENTORY';
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
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['dateid', 'docno', 'barcode', 'itemname', 'isqty', 'uom', 'isamt', 'loc']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

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
    $date = $config['params']['dataparams']['dateid'];

    $qry = "select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`glhead`.`clientid` as `clientid`,
          `glhead`.`docno` as `docno`,left(`glhead`.`dateid`,10) as `dateid`,
          `item`.`barcode` as `barcode`,`item`.`itemname` as `itemname`,`glstock`.`uom` as `uom`,
          `glstock`.`disc` as `disc`,`glstock`.`cost` as `cost`,`glstock`.`isamt` as `isamt`,
          `glstock`.`isqty` as `isqty`,`glstock`.`rrqty` as `rrqty`,glstock.loc as `loc`,
          client.client,client.clientname,client.addr,client.tel,client.email,client.tin,
          client.mobile,client.contact,client.rem,client.fax, glstock.kgs, 
          (glstock.kgs * glstock.isamt) AS unitprice, (glstock.kgs * glstock.cost) AS unitcost 
          from ((`glstock` 
          left join `glhead` on((`glstock`.`trno` = `glhead`.`trno`)))
          left join `item` on((`item`.`itemid` = `glstock`.`itemid`)))
          left join client on client.clientid = glhead.clientid
          left join cntnum on cntnum.trno = glhead.trno
          where 
           client.clientid =$clientid and glhead.dateid>='$date' and cntnum.center ='$center'
          UNION ALL
          select `lahead`.`trno` as `trno`,`lahead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
          `lahead`.`docno` as `docno`,left(`lahead`.`dateid`,10) as `dateid`,`item`.`barcode` as `barcode`,
          `item`.`itemname` as `itemname`,`lastock`.`uom` as `uom`,`lastock`.`disc` as `disc`,
          `lastock`.`cost` as `cost`,`lastock`.`isamt` as `isamt`,`lastock`.`isqty` as `isqty`,lastock.rrqty as `rrqty`,lastock.loc as `loc`,
          client.client,client.cli entname,client.addr,client.tel,client.email,
          client.tin,client.mobile,client.contact,client.rem,client.fax, lastock.kgs, 
          (lastock.kgs * lastock.isamt) AS unitprice, (lastock.kgs * lastock.cost) AS unitcost
          from ((`lastock` 
          left join `lahead` on((`lastock`.`trno` = `lahead`.`trno`)))
          left join `client` on((`client`.`client` = `lahead`.`client`))) 
          left join item on item.itemid=lastock.itemid 
          left join cntnum on cntnum.trno = lahead.trno
          where 
           client.clientid =$clientid and lahead.dateid>='$date' and cntnum.center ='$center' ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
