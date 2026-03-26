<?php

namespace App\Http\Classes\modules\customform;

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

class showheaditembalance
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SHOW BALANCE';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:800px;max-width:800px;';
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
    $isproject = $this->companysetup->getisproject($config['params']);
    $companyid = $config['params']['companyid'];

    $wh = 0;
    $itemname = 1;
    $bal = 2;
    $column = ['wh', 'itemname', 'bal'];

    $stockbuttons = [];

    $tab = [$this->gridname => ['gridcolumns' => $column]];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // totalfield
    $obj[0][$this->gridname]['totalfield'] = ['bal'];
    $obj[0][$this->gridname]['columns'][$bal]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$wh]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name';

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
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select '' as wh,0.0 as bal");
  }

  public function data($config)
  {
    $addedfield = "";

    $trno = $config['params']['trno'];
    $qry = "select w.clientid as value 
            from lahead as head 
            left join client as w on w.client=head.wh
            where head.trno=$trno
            union all
            select w.clientid as value 
            from glhead as head 
            left join client as w on w.clientid=head.whid
            where head.trno=$trno
            ";
    $whid = $this->coreFunctions->datareader($qry);

    $qry = "select ib.wh,ib.itemname,sum(bal) as bal
    from (
    select head.docno,head.dateid,head.wh,c.clientname,i.itemname,stock.qty,stock.iss,(stock.qty-stock.iss) as bal
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as c on c.client=head.client
    left join client as wh on wh.client=head.wh
    left join item as i on i.itemid=stock.itemid
    where stock.itemid in (
      select itemid from lastock where trno=7073
      union all
      select itemid from glstock where trno=7073
    ) and wh.clientid=$whid
    union all
    select head.docno,head.dateid,wh.client as wh,c.clientname,i.itemname,stock.qty,stock.iss,(stock.qty-stock.iss) as bal
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as c on c.clientid=head.clientid
    left join client as wh on wh.clientid=head.whid
    left join item as i on i.itemid=stock.itemid
    where stock.itemid in (
      select itemid from lastock where trno=7073
      union all
      select itemid from glstock where trno=7073
    ) and wh.clientid=$whid
    ) as ib
    group by wh,itemname";

    return $this->coreFunctions->opentable($qry, [$trno]);
  }
} //end class
