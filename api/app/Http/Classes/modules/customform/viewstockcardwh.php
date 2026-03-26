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

class viewstockcardwh
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BALANCE PER WAREHOUSE';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  private $sbcscript;



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
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['clientid'];

    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname . ' ~ ' . $item[0]->uom;
    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila & technolab
      $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
    }


    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $action = 0;
    $listclientname = 1;
    $loc = 2;
    $expiry = 3;
    $bal = 4;
    $itemname = 5;
    if ($companyid == 27 || $companyid == 36) { //nte & rozlab
      $tab = [$this->gridname => [
        'gridcolumns' => ['action', 'listclientname', 'loc', 'expiry', 'bal2', 'itemname']
      ]];
    } else {
      $tab = [$this->gridname => [
        'gridcolumns' => ['action', 'listclientname', 'loc', 'expiry', 'bal', 'itemname']
      ]];
    }


    $check = 0;
    if ($isexpiry) {
      $stockbuttons = ['referencemodule'];
    } else {
      $check = 1;
      $stockbuttons = ['serialloc'];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = ['bal'];
    $obj[0][$this->gridname]['columns'][$listclientname]['label'] = 'Warehouse Name';
    $obj[0][$this->gridname]['columns'][$listclientname]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'text-align:right; width:120px;whiteSpace: normal;min-width:120px;';

    if (!$isexpiry) {
      if ($check == 0) {
        $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      }
    } else {
      $obj[0][$this->gridname]['columns'][$action]['btns']['referencemodule']['color'] = 'orange';
      $obj[0][$this->gridname]['columns'][$action]['btns']['referencemodule']['label'] = 'View expiration details';
    }

    $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
    $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
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
    $companyid = $config['params']['companyid'];
    $moduletype = $config['params']['moduletype'];

    if ($companyid == 17 && $moduletype == 'INQUIRY') { //unihome
      $fields = ['itemname', 'refresh'];
    } elseif ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila & technolab
      $fields = ['luom', 'refresh'];
    } else {
      $fields = ['refresh'];
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'refresh.action', 'history');
    data_set($col1, 'itemname.type', 'lookup');
    data_set($col1, 'itemname.lookupclass', 'lookupitem');
    data_set($col1, 'itemname.action', 'lookupbarcode');
    data_set($col1, 'luom.lookupclass', 'uomledger');

    $fields = ['bal'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'bal.label', 'Total Balance');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $itemid = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['itemid'];
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
    return $this->coreFunctions->opentable("select
			'" . $itemid . "' as itemid, '' as barcode, '' as itemname , 0.0 as bal,'" . $uom . "' as uom
		");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['dataparams']['uom'];
    if (isset($config['params']['dataparams']['itemid'])) {
      $itemid = $config['params']['dataparams']['itemid'];
    } else {
      $itemid = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['itemid'];
    }

    $center = $config['params']['center'];
    $filtercenter = " and cntnum.center='" . $center . "' ";

    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }
    $expiryfield = '';

    $isexpiry = false;
    if ($isexpiry) {
      $expiryfield = ',rrstatus.loc,rrstatus.expiry';
    }

    $bal = ",round(sum(rrstatus.bal),2) as bal";
    $join = "";
    $group = "";
    if ($companyid == 27 || $companyid == 36) { //nte & rozlab
      $bal = ",format(round(sum(rrstatus.bal)/uom.factor,2)," . $this->companysetup->getdecimal('price', $config['params']) . ") as bal2,round(sum(rrstatus.bal),2) as bal";
      $join = "left join uom on uom.itemid=rrstatus.itemid and uom.uom=rrstatus.uom";
      $group = ",factor";
    }

    switch ($companyid){
      case 23: case 41: case 52: //labsol cebu, labsol manila & technolab
        $qry = "select client.clientname as clientname,rrstatus.whid,rrstatus.itemid,rrstatus.itemid as trno,'BALANCEWH' as tabtype" . $expiryfield . ", 
           item.itemname, item.barcode ,round(sum(rrstatus.bal/uom.factor),2) as bal
            from rrstatus
              left join client on client.clientid = rrstatus.whid
              left join cntnum on cntnum.trno=rrstatus.trno
              left join item on item.itemid=rrstatus.itemid
              left join uom on uom.uom = '" . $uom . "' and uom.itemid = item.itemid 
              $join
              where rrstatus.itemid ='" . $itemid . "' " . $filtercenter . "
              group by client.clientname,rrstatus.whid,rrstatus.itemid,item.itemname, item.barcode $group" . $expiryfield . " having sum(rrstatus.bal)<>0";
        break;
      case 60://transpower
        $year = date('Y');
        $qry = "select rrstatus.clientname ,rrstatus.whid,rrstatus.itemid,rrstatus.itemid as trno,'BALANCEWH' as tabtype,
           rrstatus.itemname, rrstatus.barcode ,round((sum(rrstatus.qty-rrstatus.iss)/rrstatus.factor),2) as bal
            from (select sum(stock.iss) as iss,sum(stock.qty) as qty,stock.itemid,stock.whid,client.clientname,item.itemname,item.barcode,uom.factor
            from lastock as stock left join lahead as head on head.trno = stock.trno
              left join client on client.clientid = stock.whid
              left join cntnum on cntnum.trno=stock.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.uom = '" . $uom . "' and uom.itemid = stock.itemid 
              where stock.itemid ='" . $itemid . "' and year(head.dateid)= $year " . $filtercenter . " 
              group by client.clientname,stock.whid,stock.itemid,item.itemname, item.barcode,uom.factor
              union all
              select sum(stock.iss) as iss,sum(stock.qty) as qty,stock.itemid,stock.whid,client.clientname,item.itemname,item.barcode,uom.factor
            from glstock as stock left join glhead as head on head.trno = stock.trno
              left join client on client.clientid = stock.whid
              left join cntnum on cntnum.trno=stock.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.uom = '" . $uom . "' and uom.itemid = stock.itemid
              where stock.itemid ='" . $itemid . "' and year(head.dateid)= $year " . $filtercenter . " 
                group by client.clientname,stock.whid,stock.itemid,item.itemname, item.barcode,uom.factor) as rrstatus              
              group by rrstatus.clientname ,rrstatus.whid,rrstatus.itemid,rrstatus.itemid,
           rrstatus.itemname, rrstatus.barcode,rrstatus.factor having sum(rrstatus.qty-rrstatus.iss)<>0";
        break;
      
      default:
        $qry = "select client.clientname as clientname,rrstatus.whid,rrstatus.itemid,rrstatus.itemid as trno,'BALANCEWH' as tabtype" . $expiryfield . ", 
        item.itemname, item.barcode $bal
          from rrstatus
          left join client on client.clientid = rrstatus.whid
          left join cntnum on cntnum.trno=rrstatus.trno
          left join item on item.itemid=rrstatus.itemid
          $join
          where rrstatus.itemid ='" . $itemid . "' " . $filtercenter . "
          group by client.clientname,rrstatus.whid,rrstatus.itemid,item.itemname, item.barcode $group" . $expiryfield . " having sum(rrstatus.bal)<>0";
      break;

    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function

  public function sbcscript($config){
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->skcustomform($config);
    } else {
      return true;
    }   
  }

} //end class
