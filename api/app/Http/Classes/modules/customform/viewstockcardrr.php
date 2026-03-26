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

class viewstockcardrr
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'IN-Transaction';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sbcscript;
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
    $this->sbcscript = new sbcscript;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    switch ($companyid) {
      case 11: //summit
        $action = 0;
        $docno = 1;
        $whref = 2;
        $dateid = 3;
        $listclientname = 4;
        $cost = 5;
        $disc = 6;
        $rrqty = 7;
        $status = 8;
        $uom = 9;
        $loc = 10;
        $expiry = 11;
        $rem = 12;
        $center = 13;
        $location = 14;
        $userid = 15;

        $column = [
          'action',
          'docno',
          'whref',
          'dateid',
          'listclientname',
          'cost',
          'disc',
          'rrqty',
          'status',
          'uom',
          'loc',
          'expiry',
          'rem',
          'center',
          'location',
          'userid'
        ];
        break;
      case 39: //cbbsi
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $listclientname = 3;
        $cost = 4;
        $disc = 5;
        $rrqty = 6;
        $status = 7;
        $uom = 8;
        $loc = 9;
        $expiry = 10;
        $yourref = 11;
        $ourref = 12;
        $suppinvno = 13;
        $siref = 14;
        $ref = 15;
        $rem = 16;
        $center = 17;
        $location = 18;
        $userid = 19;

        $column = ['action', 'docno', 'dateid', 'listclientname', 'cost', 'disc', 'rrqty', 'status', 'uom', 'loc', 'expiry', 'yourref', 'ourref', 'suppinvno', 'siref', 'ref', 'rem', 'center', 'location', 'userid'];
        break;
      case 17: //unihome
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $listclientname = 3;
        $cost = 4;
        $disc = 5;
        $rrqty = 6;
        $status = 7;
        $uom = 8;
        $loc = 9;
        $expiry = 10;
        $yourref = 11;
        $ourref = 12;
        $suppinvno = 13;
        $ref = 14;
        $rem = 15;
        $center = 16;
        $location = 17;
        $userid = 18;

        $column = ['action', 'docno', 'dateid', 'listclientname', 'cost', 'disc', 'rrqty', 'status', 'uom', 'loc', 'expiry', 'yourref', 'ourref', 'suppinvno', 'ref', 'rem', 'center', 'location', 'userid'];
        break;
      case 28: //xcomp
      case 49: //hotmix
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $listclientname = 3;
        $cost = 4;
        $disc = 5;
        $rrcost = 6;
        $rrqty = 7;
        $status = 8;
        $uom = 9;
        $loc = 10;
        $expiry = 11;
        $rem = 12;
        $center = 13;
        $location = 14;
        $userid = 15;

        $column = [
          'action',
          'docno',
          'dateid',
          'listclientname',
          'cost',
          'disc',
          'rrcost',
          'rrqty',
          'status',
          'uom',
          'loc',
          'expiry',
          'rem',
          'center',
          'location',
          'userid'
        ];
        break;
      case 27: //NTE
      case 36: //ROZLAB
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $listclientname = 3;
        $cost = 4;
        $disc = 5;
        $rrqty = 6;
        $status = 7;
        $uom = 8;
        $rrcost = 9;
        $loc = 10;
        $expiry = 11;
        $rem = 12;
        $center = 13;
        $location = 14;
        $userid = 15;

        $column = [
          'action',
          'docno',
          'dateid',
          'listclientname',
          'cost',
          'disc',
          'rrqty',
          'status',
          'uom',
          'rrcost',
          'loc',
          'expiry',
          'rem',
          'center',
          'location',
          'userid'
        ];
        break;

      case 60: //TRANSPOWER
        $column = [
          'action',
          'docno', 'dateid', 'listclientname', 'rrcost', 'disc', 'cost', 'rrqty', 'status', 'uom', 'rem', 'center', 'userid', 'loc', 'expiry',   'location'
        ];

        foreach ($column as $key => $value) {
            $$value = $key;
        }

        //Document | Date | Name | Base Amount | Discount | Unit Cost | Quantity | Status | Trans.Uom | Remarks | Branch | Encoded | Location | Expiry

        
        //document# | date | name |              discount | unit cost | quantity | status | transuom | remarks | branch encoded | location  | expiry
        break;

      default:
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $listclientname = 3;
        $cost = 4;
        $disc = 5;
        $rrqty = 6;
        $status = 7;
        $uom = 8;
        $whname = 9;
        $loc = 10;
        $expiry = 11;
        $rem = 12;
        $center = 13;
        $location = 14;
        $userid = 15;

        $column = [
          'action',
          'docno',
          'dateid',
          'listclientname',
          'cost',
          'disc',
          'rrqty',
          'status',
          'uom',
          'whname',
          'loc',
          'expiry',
          'rem',
          'center',
          'location',
          'userid'
        ];

        
        break;
    }


    $tab = [$this->gridname => ['gridcolumns' => $column]];

    if ($companyid == 17) { //unihome
      $stockbuttons = ['referencemodule', 'outgoingtrans'];
    } else {
      $stockbuttons = ['referencemodule'];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:center;';
    $obj[0][$this->gridname]['columns'][$listclientname]['style'] = 'width:90px;whiteSpace: normal;min-width:120px;';
    #cost
    if ($viewcost == '1') {
      $obj[0][$this->gridname]['columns'][$cost]['label'] = 'Unit Cost';
      $obj[0][$this->gridname]['columns'][$cost]['align'] = 'left';
    } else {
      $obj[0][$this->gridname]['columns'][$cost]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Remarks';
    $obj[0][$this->gridname]['columns'][$center]['label'] = 'Branch Encoded';

    $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:90px;whiteSpace: normal;min-width:80px;';

    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Quantity';
    $obj[0][$this->gridname]['columns'][$rrqty]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:90px;whiteSpace: normal;min-width:80px;text-align:right;';
    $obj[0][$this->gridname]['columns'][$status]['align'] = 'right';


    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:center;';

    $obj[0][$this->gridname]['columns'][$loc]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$expiry]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:center;';

    $obj[0][$this->gridname]['columns'][$location]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$userid]['label'] = 'Assigned Loc.';

    switch ($companyid) {
      case 47: //kitchenstar
        $obj[0][$this->gridname]['columns'][$whname]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$whname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;text-align:left;';
        break;
      case 28: //xcomp
      case 27: //nte
      case 36: //rozlab
      case 49: //hotmix
        $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Supp Cost';
        break;
      case 60: //transpower
        $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
        $obj[0][$this->gridname]['columns'][$rrcost]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Base Amount';
        break;
      case 11: //summit
        $obj[0][$this->gridname]['columns'][$whref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        break;
      case 39: //cbbsi
        $obj[0][$this->gridname]['columns'][$siref]['label'] = 'Yourref (Supp.Inv)';
        break;
    }

    if ($companyid != 47) { //not kitchenstar
      if (isset($whname)) {
        $obj[0][$this->gridname]['columns'][$whname]['type'] = 'coldel';
      }
    }

    if ($this->companysetup->getispallet($config['params'])) {
      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
    } else {
      if ($config['params']['companyid'] == 8) { //maxipro
        $obj[0][$this->gridname]['columns'][$loc]['label'] = 'Brand';
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
      } else {
        $obj[0][$this->gridname]['columns'][$location]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$userid]['type'] = 'coldel';
      }
    }

    $obj[0][$this->gridname]['columns'][$uom]['label'] = 'Trans. UOM';
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
    // var_dump($config['params']);
    $moduletype = $config['params']['moduletype'];
    $companyid = $config['params']['companyid'];

    if (isset($config['params']['clientid'])) {
      if ($config['params']['clientid'] != 0) {
        $itemid = $config['params']['clientid'];
        $item = $this->othersClass->getitemname($itemid);
        $this->modulename .= ' - Itemcode: ' .  $item[0]->barcode . ', ItemName: ' . $item[0]->itemname . ($item[0]->isnoninv == 1 ? ' - - - - (Non-Inventory)' : '');
      } else {
        return [];
      }
    }

    $fields = [['dateid', 'luom']];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'luom.lookupclass', 'uomledger');
  
    if($config['params']['companyid'] == 60 && $moduletype=='INQUIRY'){
      data_set($col1, 'luom.addedparams', ['itemid']);
    }

    $fields = [];
    // if ($companyid == 17 && $moduletype == 'INQUIRY') { //unihome
    //   array_push($fields, 'itemname');
    // }

    array_push($fields, 'wh');
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'wh.lookupclass', 'whledger');
    data_set($col2, 'itemname.type', 'lookup');
    data_set($col2, 'itemname.lookupclass', 'lookupitem');
    data_set($col2, 'itemname.action', 'lookupbarcode');

    $fields = [['refresh', 'db']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'history');
    data_set($col3, 'db.label', 'IN');

    $fields = [['cr', 'bal']];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'cr.label', 'OUT');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    if($companyid==60 && $config['params']['moduletype']=='INQUIRY'){//transpower
       $itemid = $config['params']['row']['itemid'];
       $config['params']['itemid'] = $itemid;
    }else{
       $itemid = $config['params']['clientid'];
    }

    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
    $wh = $this->companysetup->getwh($config['params']);
    if ($companyid == 17) { //unihome
      $wh = '';
    }

    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='SKRR' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($date == '') {
      $date = "DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
    } else {
      $date = "'" . $date . "'";
    }

    $item = $this->othersClass->getitemname($itemid);
    $data = $this->getbal($config, $itemid, $wh, $uom);
    if (!empty($data)) {
      return $this->coreFunctions->opentable("select
        " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, 
        " . $date . " as dateid,
        '$wh' as wh,
        '$uom' as uom,
        '" . $data[0]->qty . "' as db,
        '" . $data[0]->iss . "' as cr,
        '" . $data[0]->bal . "' as bal");
    } else {
      return $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, adddate(left(now(),10),-360) as dateid,'$wh' as wh, '$uom' as uom,'0.0' as db,'0.0' as cr,'0.0' as bal");
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    // $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $date = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $uom = $config['params']['dataparams']['uom'];
    $wh = $config['params']['dataparams']['wh'];
    if (isset($config['params']['dataparams']['itemid'])) {
      $itemid = $config['params']['dataparams']['itemid'];
    }else{
      $itemid = $config['params']['itemid'];
    }
    if ($wh == '') {
      $filterwh = '';
    } else {
      $filterwh = " and wh.client='" . $wh . "'";
    }

    $filtercenter = " and cntnum.center='" . $center . "' ";
    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }
    // pacheck po sir
    $cost = "FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as cost,";
    if ($companyid == 49) { // hotmix
      $cost = "FORMAT(case when head.doc = 'TS' then stock.isamt else ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) end," . $decimalprice . ") as cost,";
    }

    $suppinvno2 = '';
    $leftjoin = '';
    $groupsuppinvno = '';
    if ($companyid == 39) { //cbbsi
      $leftjoin = "left join hsnstock as hs on hs.refx = stock.trno and hs.linex = stock.line left join glhead as hsh on hsh.trno = hs.trno";
      $suppinvno2 = ",hsh.docno as suppinvno,hsh.yourref as siref";
      $groupsuppinvno = ",hsh.docno,hsh.yourref";
    }

    $qry = "select (FORMAT(stock.cost * 1.12, 2)) as netprice, cntnum.doc, rrstatus.trno, rrstatus.line, 
    client.clientname,
      $cost
      FORMAT((rrstatus.qty / (case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end))," . $decimalqty . ") as rrqty,
      cast(case when rrstatus.bal = 0 then 'applied' else
        FORMAT((rrstatus.bal / (case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end))," . $decimalqty . ") end as char(50)) as status,
      left(rrstatus.dateid,10) as dateid,
      rrstatus.whid,wh.clientname as whname, rrstatus.uom, rrstatus.disc, rrstatus.docno,
      FORMAT(stock.rrcost," . $decimalprice . ") as rrcost,
      rrstatus.loc,rrstatus.expiry,rrstatus.isimport,stock.rem,cntnum.center,
      ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,
      ifnull((case when stock.whmanid<>0 then wm.clientname else fl.clientname end),'') as userid,whref.clientname as whref,'RRTAB' as tabtype,item.itemid,
      head.yourref,head.ourref,stock.ref $suppinvno2
      from rrstatus
      left join client on client.clientid=rrstatus.clientid
      left join client as wh on wh.clientid=rrstatus.whid
      left join item on item.itemid=rrstatus.itemid
      left join uom on uom.itemid=rrstatus.itemid and uom.uom='" . $uom . "'
      left join cntnum on cntnum.trno=rrstatus.trno
      left join glstock as stock on stock.trno = rrstatus.trno and stock.line = rrstatus.line
      left join glhead as head on head.trno = stock.trno
      $leftjoin
      left join pallet on pallet.line=rrstatus.palletid
      left join location on location.line=rrstatus.locid
      left join client as fl on fl.clientid=stock.forkliftid
      left join client as wm on wm.clientid=stock.whmanid
      left join client as whref on whref.client = head.whref
      where rrstatus.itemid=" . $itemid . " and rrstatus.dateid >= '" . $date . "'" . $filterwh . $filtercenter . "
      group by rrstatus.trno,rrstatus.line,cntnum.doc,client.clientname,stock.cost,uom.factor,rrstatus.qty,rrstatus.bal,
      rrstatus.dateid,rrstatus.whid,rrstatus.uom,rrstatus.disc,rrstatus.docno,stock.rrcost,rrstatus.loc,rrstatus.expiry,
      rrstatus.isimport,stock.rem,cntnum.center,pallet.name,location.loc,stock.whmanid,wm.clientname,fl.clientname,whref.clientname,item.itemid,stock.isamt,head.doc,
      head.yourref,head.ourref,stock.ref,wh.clientname $groupsuppinvno
      order by rrstatus.dateid desc";

    $data = $this->coreFunctions->opentable($qry);

    $item = $this->othersClass->getitemname($itemid);

    $databal = $this->getbal($config, $itemid, $wh, $uom);
    if (!empty($databal)) {
      $txtdata = $this->coreFunctions->opentable("
        select 
        " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, 
        '" . $date . "' as dateid,
        '$wh' as wh,
        '$uom' as uom,
        '" . $databal[0]->qty . "' as db,
        '" . $databal[0]->iss . "' as cr,
        '" . $databal[0]->bal . "' as bal");
    } else {
      $txtdata = $this->coreFunctions->opentable("select
        " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, 
        '" . $date . "' as dateid,
        '$wh' as wh,
        '$uom' as uom,
        '0.0' as db,
        '0.0' as cr,
        '0.0' as bal");
    }

    $dateexist = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='SKRR' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($dateexist == '') {
      $profile = ['doc' => 'SKRR', 'psection' => 'StartDate', 'pvalue' => $date, 'puser' => $config['params']['user']];
      $this->coreFunctions->sbcinsert("profile", $profile);
    } else {
      $this->coreFunctions->sbcupdate("profile", ['pvalue' => $date], ['doc' => 'SKRR', 'psection' => 'StartDate', 'puser' => $config['params']['user']]);
    }

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
  } //end function



  public function getbal($config, $itemid, $wh, $uom)
  {
    $center = $config['params']['center'];

    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);

    if ($wh == '') {
      $filterwh = '';
    } else {
      $filterwh = " and wh.client='" . $wh . "'";
    }

    $filtercenter = " and cntnum.center='" . $center . "' ";
    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }

    $qry = "select rr.itemid,ifnull(FORMAT(sum(rr.qty / (case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)), " . $decimalqty . "), 0) as qty,
    ifnull(FORMAT(sum((rr.qty-rr.bal) / (case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)), " . $decimalqty . "), 0) as iss,
    ifnull(FORMAT(sum(rr.bal / (case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)), " . $decimalqty . "), 0) as bal
    from rrstatus as rr
    left join uom on uom.itemid=rr.itemid and uom.uom='" . $uom . "'
    left join client as wh on wh.clientid=rr.whid
    left join cntnum on cntnum.trno=rr.trno
    where rr.itemid=" . $itemid . $filterwh . $filtercenter . "  group by rr.itemid";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  public function sbcscript($config){
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->skcustomform($config);
    } else {
      return true;
    }   
  }

} //end class
