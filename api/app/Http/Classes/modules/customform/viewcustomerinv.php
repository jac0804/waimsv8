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
use App\Http\Classes\sbcscript\sbcscript;

class viewcustomerinv
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INVENTORY';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sbcscript;
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
    $this->sbcscript = new sbcscript;
  }

  public function createTab($config)
  {

    $clientid = $config['params']['clientid'];
    $companyid = $config['params']['companyid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;

    $cols = ['dateid', 'docno', 'barcode', 'itemdesc', 'isqty', 'uom', 'isamt', 'agentamt', 'loc'];
    if ($companyid == 60) {
      $cols = ['docno', 'ourref', 'dateid', 'barcode', 'itemdesc', 'rem', 'uom', 'disc', 'cost', 'isamt', 'qty', 'iss', 'agentamt', 'ref', 'user'];
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $cols
      ]
    ];

    $stockbuttons = [];

    foreach ($cols as $key => $value) {
      $$value = $key;
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][$barcode]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$isamt]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][$isamt]['style'] = 'text-align:right; width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'text-align:center; width:90px;whiteSpace: normal;min-width:90px;';


    if ($companyid == 60) { //transpower
      $obj[0][$this->gridname]['columns'][$ourref]['label'] = 'REFERENCE';
      $obj[0][$this->gridname]['columns'][$rem]['label'] = 'NOTES';
      $obj[0][$this->gridname]['columns'][$uom]['label'] = 'UNIT';
      $obj[0][$this->gridname]['columns'][$disc]['label'] = 'DISCOUNT';
      $obj[0][$this->gridname]['columns'][$cost]['label'] = 'UNIT COST';
      $obj[0][$this->gridname]['columns'][$isamt]['label'] = 'PRICE';
      $obj[0][$this->gridname]['columns'][$qty]['label'] = 'IN';
      $obj[0][$this->gridname]['columns'][$iss]['label'] = 'OUT';
      $obj[0][$this->gridname]['columns'][$agentamt]['label'] = 'AGENT AMT';
      $obj[0][$this->gridname]['columns'][$ref]['label'] = 'STOCK REF#';
      $obj[0][$this->gridname]['columns'][$user]['label'] = 'USERS';
      $obj[0][$this->gridname]['columns'][$dateid]['label'] = 'DATE';
      $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'BARCODE';
      $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'ITEM NAME';

      $obj[0][$this->gridname]['columns'][$rem]['style'] = 'text-align:left; width:100px;whiteSpace: normal;min-width:100px;';
      $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
      $obj[0][$this->gridname]['columns'][$uom]['style'] = 'text-align:center; width:80px;whiteSpace: normal;min-width:80px;';
      $obj[0][$this->gridname]['columns'][$disc]['style'] = 'text-align:center; width:100px;whiteSpace: normal;min-width:100px;';
      $obj[0][$this->gridname]['columns'][$cost]['style'] = 'text-align:right; width:80px;whiteSpace: normal;min-width:80px;';
      $obj[0][$this->gridname]['columns'][$qty]['style'] = 'text-align:left; width:80px;whiteSpace: normal;min-width:80px;';
      $obj[0][$this->gridname]['columns'][$iss]['style'] = 'text-align:left; width:80px;whiteSpace: normal;min-width:80px;';
    } else {
      $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'right';
      $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-align:right; width:90px;whiteSpace: normal;min-width:90px;';
      $obj[0][$this->gridname]['columns'][$loc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
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
    $date = $this->othersClass->sbcdateformat($config['params']['dataparams']['dateid']);
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);

    $orderby = "";
    $centerfilter = "";
    if ($config['params']['companyid'] != 60) {
      $centerfilter = " and cntnum.center ='$center'";
    }

    if ($config['params']['companyid'] == 60) { //transpower
      $orderby = " order by dateid desc";
    }

    $qry = "select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`glhead`.`clientid` as `clientid`,
          `glhead`.`docno` as `docno`,left(`glhead`.`dateid`,10) as `dateid`,
          `item`.`barcode` as `barcode`,`item`.`itemname` as `itemdesc`,`glstock`.`uom` as `uom`,
          `glstock`.`disc` as `disc`,format(`glstock`.`cost`,2) as `cost`,FORMAT(`glstock`.`isamt`," . $amtdec . ") as `isamt`,
          FORMAT(if(`glstock`.`isqty` <> 0, `glstock`.`isqty`, `glstock`.`rrqty` *-1)," . $qtydec . ") as `isqty`,glstock.loc as `loc`,
          client.client,client.clientname,client.addr,client.tel,client.email,client.tin,
          client.mobile,client.contact,client.rem,client.fax, glstock.kgs, 
          (glstock.kgs * glstock.isamt) AS unitprice, (glstock.kgs * glstock.cost) AS unitcost,
          format(glstock.agentamt,2) as agentamt,glhead.ourref, glstock.ref,glhead.rem,glstock.disc,format(glstock.iss,2) as iss,format(glstock.qty,2) as qty,glhead.users as user
          from ((`glstock` 
          left join `glhead` on((`glstock`.`trno` = `glhead`.`trno`)))
          left join `item` on((`item`.`itemid` = `glstock`.`itemid`)))
          left join client on client.clientid = glhead.clientid
          left join cntnum on cntnum.trno = glhead.trno
          where 
           client.clientid =$clientid and glhead.dateid>='$date' $centerfilter
          UNION ALL
          select `lahead`.`trno` as `trno`,`lahead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
          `lahead`.`docno` as `docno`,left(`lahead`.`dateid`,10) as `dateid`,`item`.`barcode` as `barcode`,
          `item`.`itemname` as `itemdesc`,`lastock`.`uom` as `uom`,`lastock`.`disc` as `disc`,
          format(`lastock`.`cost`,2) as `cost`,FORMAT(`lastock`.`isamt`," . $amtdec . ") as `isamt`,
          FORMAT(if(`lastock`.`isqty` <> 0, `lastock`.`isqty`, `lastock`.`rrqty` *-1)," . $qtydec . ") as `isqty`,lastock.loc as `loc`,
          client.client,client.clientname,client.addr,client.tel,client.email,
          client.tin,client.mobile,client.contact,client.rem,client.fax, lastock.kgs, 
          (lastock.kgs * lastock.isamt) AS unitprice, (lastock.kgs * lastock.cost) AS unitcost,
          format(lastock.agentamt,2) as agentamt,lahead.ourref, lastock.ref,lahead.rem,lastock.disc,format(lastock.iss,2) as iss,format(lastock.qty,2) as qty,lahead.users as user
          from ((`lastock` 
          left join `lahead` on((`lastock`.`trno` = `lahead`.`trno`)))
          left join `client` on((`client`.`client` = `lahead`.`client`))) 
          left join item on item.itemid=lastock.itemid 
          left join cntnum on cntnum.trno = lahead.trno
          where 
           client.clientid =$clientid and lahead.dateid>='$date' $centerfilter $orderby";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }

  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->skcustomform($config);
    } else {
      return true;
    }
  }
} //end class
