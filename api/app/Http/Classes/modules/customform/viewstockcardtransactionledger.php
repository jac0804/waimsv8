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
use App\Http\Classes\Logger;
use App\Http\Classes\sbcscript\sbcscript;

class viewstockcardtransactionledger
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Transaction History';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
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
    $this->logger = new Logger;
    $this->sbcscript = new sbcscript;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    $ispos = $this->companysetup->getispos($config['params']);

    switch ($companyid) {
      case 39: //cbbsi
        // $action = 0;
        // $status = 1;
        // $docno = 2;
        // $dateid = 3;
        // $listclientname = 4;
        // $whref = 5;
        // $rrqty = 6;
        // $isqty = 7;
        // $rrcost = 8;
        // $totalcost = 9;
        // $isamt = 10;
        // $rrcost2 = 11;
        // $rrcost3 = 12;
        // $ext = 13;
        // $disc = 14;
        // $yourref = 15;
        // $ourref = 16;
        // $rem = 17;
        // $suppinvno = 18;
        // $siref = 19;
        // $ref = 20;
        // $loc = 21;
        // $expiry = 22;
        // $location = 23;
        // $pallet = 24;
        // $serialno = 25;
        // $moduletype = 26;
        // $center = 27;

        $columns = [
          'action',
          'status',
          'docno',
          'dateid',
          'listclientname',
          'whref',
          'rrqty',
          'isqty',
          'rrcost',
          'totalcost',
          'isamt',
          'rrcost2',
          'rrcost3',
          'ext',
          'disc',
          'yourref',
          'ourref',
          'rem',
          'suppinvno',
          'siref',
          'ref',
          'loc',
          'expiry',
          'location',
          'pallet',
          'serialno',
          'moduletype',
          'center'
        ];
        break;
      case 60://transpower
        //  'isqty2', 
          $columns = [
          'action',
          'status', 'docno', 'dateid', 'listclientname', 'rrqty', 'isqty','baseamt', 'disc', 'amt','cost','ext', 'yourref', 'ourref', 'rem', 'ref', 'center', 'loc', 'expiry', 
          #coldel
          'isamt','rrcost', 'rrcost2', 'rrcost3', 'whname', 'location', 'pallet', 'serialno', 'totalcost', 'moduletype', 'whref', 'isqty2'
        ];
        // Status | Document | Date | Name | IN Qty | OUT Qty |  Base Amount | Discoun | Amount | Cost | Total | Yourref | Ourref | Notes | Reference | Branch | Location | Expiry
        // status | docno | date | name | in | out | base amount | discount | amount | Cost | total | yourref | ourref | notes | reference | branch | location | expiry
        break;  
      default:
        // $action = 0;
        // $status = 1;
        // $docno = 2;
        // $dateid = 3;
        // $listclientname = 4;
        // $whref = 5;
        // $rrqty = 6;
        // $isqty = 7;
        // $rrcost = 8;
        // $totalcost = 9;
        // $isamt = 10;
        // $rrcost2 = 11;
        // $rrcost3 = 12;
        // $ext = 13;
        // $disc = 14;
        // $whname = 15;
        // $yourref = 16;
        // $ourref = 17;
        // $rem = 18;
        // $ref = 19;
        // $loc = 20;
        // $expiry = 21;
        // $location = 22;
        // $pallet = 23;
        // $serialno = 24;
        // $moduletype = 25;
        // $center = 26;

        $columns = [
          'action',
          'status',
          'docno',
          'dateid',
          'listclientname',
          'whref',
          'rrqty',
          'isqty',
          'isqty2',
          'rrcost',
          'totalcost',
          'isamt',
          'rrcost2',
          'rrcost3',
          'ext',
          'disc',
          'whname',
          'yourref',
          'ourref',
          'rem',
          'ref',
          'loc',
          'expiry',
          'location',
          'pallet',
          'serialno',
          'moduletype',
          'center'
        ];
        break;
    }

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = ['referencemodule', 'outgoingtrans'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:center;';
    $obj[0][$this->gridname]['columns'][$listclientname]['style'] = 'width:220px;whiteSpace: normal;min-width:220px;';

    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'IN';
    $obj[0][$this->gridname]['columns'][$rrqty]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'OUT';
    $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    if ($companyid == 39) { //cbbsi
      $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Landed Cost';
      $obj[0][$this->gridname]['columns'][$siref]['label'] = 'Yourref (Supp.Inv)';
    } else {
      $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Cost';
    }

    $obj[0][$this->gridname]['columns'][$rrcost]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    if ($companyid == 49) { //hotmix
      $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Supp Cost';
    }

    $obj[0][$this->gridname]['columns'][$rrcost2]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$rrcost2]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    $obj[0][$this->gridname]['columns'][$rrcost3]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$rrcost3]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    if ($config['params']['doc'] == 'STOCKCARD' && $config['params']['moduletype'] == 'INQUIRY') {
      if (!$viewcost) {
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'hidden';
      }
    } else {
      if (!$viewcost) {
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'coldel';
      }
    }

    if ($companyid == 47) { // kitchenstar
      $obj[0][$this->gridname]['columns'][$whname]['align'] = 'text-left';
      $obj[0][$this->gridname]['columns'][$whname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;text-align:left;';
    } else {
      $obj[0][$this->gridname]['columns'][$whname]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$isamt]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$isamt]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

    $obj[0][$this->gridname]['columns'][$loc]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$expiry]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$location]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$pallet]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';


    if ($this->companysetup->getispallet($config['params'])) {
      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$location]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';
    }

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $obj[0][$this->gridname]['columns'][$location]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$whref]['label'] = 'Warehouse';
      $obj[0][$this->gridname]['columns'][$yourref]['label'] = 'Customer PO';
      $obj[0][$this->gridname]['columns'][$totalcost]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';
      $obj[0][$this->gridname]['columns'][$ext]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';
      $obj[0][$this->gridname]['columns'][$totalcost]['label'] = 'Total Cost';
      $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Total Amount';
      $obj[0][$this->gridname]['columns'][$ourref]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$serialno]['style'] = '
      width:120px;whiteSpace: normal;min-width:120px; max-width:120px; word-wrap: break-word;';
      $obj[0][$this->gridname]['columns'][$moduletype]['style'] = '
      width:100px;whiteSpace: normal;min-width:100px; max-width:100px; ';
    } else {
      $obj[0][$this->gridname]['columns'][$serialno]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$totalcost]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$moduletype]['type'] = 'coldel';
    }

    switch ($companyid) {
      case 11: //summit
        $obj[0][$this->gridname]['columns'][$whref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        break;
      case 43: //mighty
        $obj[0][$this->gridname]['columns'][$whref]['label'] = 'WH';
        $obj[0][$this->gridname]['columns'][$whref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        break;
      default:
        $obj[0][$this->gridname]['columns'][$whref]['type'] = 'coldel';
        break;
    }


    if ($companyid == 24) { // good found cement
      $obj[0][$this->gridname]['columns'][$loc]['label'] = 'Batch';
      $obj[0][$this->gridname]['columns'][$loc]['align'] = 'text-right';
    }

    if ($companyid != 28) { //not xcomp
      $obj[0][$this->gridname]['columns'][$rrcost2]['type'] = 'coldel';
    }

    switch ($companyid) {
      case 27: //nte
      case 36: //rozlab
        break;
      default:
        $obj[0][$this->gridname]['columns'][$rrcost3]['type'] = 'coldel';
        break;
    }

    if (!$this->companysetup->getmultibranch($config['params'])) {
      $obj[0][$this->gridname]['columns'][$center]['type'] = 'coldel';
    }

    if ($companyid == 32) { //3m
      $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-right';
      $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right;whiteSpace:normal;';
    }

    if($companyid == 60){//transpower
      $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
      $obj[0][$this->gridname]['columns'][$cost]['label'] = 'Cost';
      
      $obj[0][$this->gridname]['columns'][$cost]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align:right;';

      $obj[0][$this->gridname]['columns'][$isamt]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
    }

    if (!$ispos) {
      $obj[0][$this->gridname]['columns'][$isqty2]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$isqty2]['label'] = 'OOS Qty';
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
    $moduletype = $config['params']['moduletype'];
    $companyid = $config['params']['companyid'];

    //transaction history button in po module -transpower
     if($companyid == 60 && $config['params']['doc'] == 'PO'){
      $row=$config['params']['row'];
      $itemid=$row['itemid'];
      $item = $this->othersClass->getitemname($itemid);
      $this->modulename .= ' - Itemcode: ' .  $item[0]->barcode . ' ~ ItemName: ' . $item[0]->itemname;
    }else{
      if (isset($config['params']['clientid'])) {
        if ($config['params']['clientid'] != 0) {
          $itemid = $config['params']['clientid'];
          $item = $this->othersClass->getitemname($itemid);
          $this->modulename .= ' - Itemcode: ' .  $item[0]->barcode . ' ~ ItemName: ' . $item[0]->itemname;
        } else {
          return [];
        }
      }
    }


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $fields = [['dateid', 'enddate', 'luom']];
    } else {
      $fields = [['dateid', 'luom']];
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'dateid.label', 'Start Date');
    data_set($col1, 'luom.lookupclass', 'uomledger');

    if($config['params']['companyid'] == 60 && $config['params']['doc'] == 'PO'){ //transaction history po button
        data_set($col1, 'luom.addedparams', ['itemid']);
        }

    $fields = [];
    if ($companyid == 17 && $moduletype == 'INQUIRY') { //unihome
      array_push($fields, 'itemname');
    }

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
    if ($this->companysetup->isrecalc($config['params'])) {
      $allowrecalc = $this->othersClass->checkAccess($config['params']['user'], 3690);
      if ($allowrecalc) {
        if ($config['params']['moduletype'] == 'MASTERFILE') {
          array_push($fields, 'recalc');
        }
      }
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'cr.label', 'OUT');


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

      //transaction history button in po module -transpower
     if($companyid==60 && $config['params']['doc'] == 'PO'){
      $row=$config['params']['row'];
      $itemid=$row['itemid'];
     }else{
      $itemid = $config['params']['clientid'];
      }
   
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
      case 17: //unihome
      case 43: //mighty
        $wh = '';
        break;
      default:
        $wh = $this->companysetup->getwh($config['params']);
        break;
    }
    $data = $this->getbal($config, $itemid, $wh, $uom);
    $item = $this->othersClass->getitemname($itemid);

    $dateexist = 0;
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $uom = $this->coreFunctions->getfieldvalue('uom', 'uom', 'itemid=? and isdefault =1', [$itemid]);
      $data = $this->getbal($config, $itemid, $wh, $uom);
      $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='SK' and psection='StartDate' and puser=?", [$config['params']['user']]);
      $enddate = "left(now(),10)";
      if ($date == '') {
        $date = "left(now(),10)";
      } else {
        $date = $this->othersClass->sanitizekeyfield("dateid", $date);
        $date = "'" . $date . "'";
        $dateexist = 1;
      }
    } else {
      $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='SK' and psection='StartDate' and puser=?", [$config['params']['user']]);
      if ($date == '') {
        $date = "DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
      } else {
        $date = $this->othersClass->sanitizekeyfield("dateid", $date);
        $date = "'" . $date . "'";
        $dateexist = 1;
      }
    }

    if (!empty($data)) {
      if ($companyid == 10 || $companyid == 12) { //afti & afti usd
        return $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, " . $date . " as dateid,'$wh' as wh, '$uom' as uom,'" . round($data[0]->rrqty, 0) . "' as db,'" . round($data[0]->isqty, 0) . "' as cr,'" . round($data[0]->bal, 0) . "' as bal, " . $dateexist . " as dateexist, " . $enddate . " as enddate");
      } else {
        return $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, " . $date . " as dateid,'$wh' as wh, '$uom' as uom,'" . $data[0]->rrqty . "' as db,'" . $data[0]->isqty . "' as cr,'" . $data[0]->bal . "' as bal, " . $dateexist . " as dateexist");
      }
    } else {
      if ($companyid == 10 || $companyid == 12) { //afti & afti usd
        return $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname," . $date . " as dateid,'$wh' as wh, '$uom' as uom,'0' as db,'0' as cr,'0' as bal, " . $dateexist . " as dateexist, " . $enddate . " as enddate");
      } else {
        return $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname," . $date . " as dateid,'$wh' as wh, '$uom' as uom,'0.0' as db,'0.0' as cr,'0.0' as bal, " . $dateexist . " as dateexist");
      }
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {

     $companyid = $config['params']['companyid'];
     $itemid = $config['params']['dataparams']['itemid'];
    // $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $date = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $uom = $config['params']['dataparams']['uom'];
    $wh = $config['params']['dataparams']['wh'];
    $barcode = $config['params']['dataparams']['barcode'];
    $itemname = $config['params']['dataparams']['itemname'];
   
   
    $data = [];

    if ($wh == '') {
      $filterwh = '';
    } else {
      $filterwh = " and wh.client='" . $wh . "'";
    }

    $dateexist = isset($config['params']['dataparams']['dateexist'])  ? $config['params']['dataparams']['dateexist'] : 0;

    switch ($config['params']['action2']) {
      case 'history':

        break;

      case 'recalc':
        $this->recalcitem($config);

        break;
    }

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $enddate = date("Y-m-d", strtotime($config['params']['dataparams']['enddate']));
      $data = $this->loadhistory($itemid, $center, $date, $uom, $filterwh, $config, $enddate);
    } else {
      $data = $this->loadhistory($itemid, $center, $date, $uom, $filterwh, $config);
    }

    $databal = $this->getbal($config, $itemid, $wh, $uom);
    $item = $this->othersClass->getitemname($itemid);

    if (!empty($databal)) {
      if ($companyid == 10 || $companyid == 12) { //afti & afti usd
        $txtdata = $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, '$date' as dateid,'$wh' as wh, '$uom' as uom,'" . round($databal[0]->rrqty, 0) . "' as db,'" . round($databal[0]->isqty, 0) . "' as cr,'" . round($databal[0]->bal, 0) . "' as bal," . $dateexist . " as dateexist, '$enddate' as enddate");
      } else {
        $txtdata = $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, '$date' as dateid,'$wh' as wh, '$uom' as uom,'" . $databal[0]->rrqty . "' as db,'" . $databal[0]->isqty . "' as cr,'" . $databal[0]->bal . "' as bal," . $dateexist . " as dateexist");
      }
    } else {
      if ($companyid == 10 || $companyid == 12) { //afti & afti usd
        $txtdata = $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, '$date' as dateid,'$wh' as wh, '$uom' as uom,'0' as db,'0' as cr,'0' as bal," . $dateexist . " as dateexist, '$enddate' as enddate");
      } else {
        $txtdata = $this->coreFunctions->opentable("select " . $itemid . " as itemid,'" . $item[0]->barcode . "' as barcode , '" . $item[0]->itemname . "' as itemname, '$date' as dateid,'$wh' as wh, '$uom' as uom,'0.0' as db,'0.0' as cr,'0.0' as bal," . $dateexist . " as dateexist");
      }
    }

    $profile = ['doc' => 'SK', 'psection' => 'StartDate', 'pvalue' => $date, 'puser' => $config['params']['user']];
    $date = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc='SK' and psection='StartDate' and puser=?", [$config['params']['user']]);
    if ($date == '') {
      $this->coreFunctions->sbcinsert("profile", $profile);
    } else {
      $this->coreFunctions->sbcupdate("profile", $profile, ['doc' => 'SK', 'psection' => 'StartDate', 'puser' => $config['params']['user']]);
    }

    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
  } //end function


  public function loadhistory($itemid, $center, $date, $uom, $filterwh, $config, $enddate = '')
  {
    $companyid = $config['params']['companyid'];

    $filtercenter = " and cntnum.center='" . $center . "' ";
    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $qry = "select '' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname, FORMAT(ifnull(stock.rrcost,0),2) as rrcost2,wh.client as wh, 
        FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as rrcost,
        FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),0) as rrqty,
        FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as isamt,
        FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),0) as isqty,
        stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
        (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, stock.rem, 0 as balance, item.itemid,
        stock.loc,stock.expiry,cntnum.center, '' as svnum,
        ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,wh.clientname as whref,stock.ref, 'LEDGERTAB' as tabtype,
        concat(
          ifnull(group_concat(sin.serial separator '/'),''),
          ifnull(group_concat(sout.serial separator '/'),'')) as serialno,
          case
            when head.doc='RR' then 'Purchase Receiving'
            when head.doc='AC' then 'Job Completion'
            when head.doc='DM' then 'Purchase Return'
            when head.doc='SJ' then 'Sales Journal'
            when head.doc='CM' then 'Sales Return'
            when head.doc='AI' then 'Service Invoice'
            when head.doc='SU' then 'Stock Issuance'
            when head.doc='AJ' then 'Inventory Adjustment'
            when head.doc='TS' then 'Transfer Slip'
            when head.doc='IS' then 'Inventory Setup'
            else ''
          end as moduletype,FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))*(stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as totalcost,
          FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))*(stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as ext,stock.line,cntnum.center
        from lahead as head  left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
        left join cntnum on cntnum.trno=head.trno
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join client as whref on whref.client = head.whref
        left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
        left join serialout as sout on sout.trno = stock.trno and sout.line = stock.line
        where cntnum.center='" . $center . "' and item.itemid=" . $itemid . " and head.dateid>='" . $date . "' and head.dateid<='" . $enddate . "'" . $filterwh . "
        group by head.trno, head.doc, head.docno, head.dateid, head.clientname, stock.rrcost,wh.client,
        stock.disc, head.yourref, head.ourref,head.cur,head.forex,head.isimport, head.factor, stock.rem,  item.itemid,
        stock.loc,stock.expiry,cntnum.center,uom.factor,stock.cost,stock.qty,stock.amt,stock.iss,pallet.name, location.loc,wh.clientname,stock.ref,stock.line,cntnum.center
        UNION ALL
        select 'POSTED' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname, round(ifnull(stock.rrcost,0),2) as rrcost2,wh.client as wh, 
        FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as rrcost,
        FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),0) as rrqty,
        FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as isamt,
        FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),0) as isqty,
        stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
        (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, stock.rem, 0 as balance, stock.itemid,
        stock.loc,stock.expiry,cntnum.center, svno.docno as svnum,
        ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,wh.clientname as whref,stock.ref, 'LEDGERTAB' as tabtype,
        concat(
          ifnull(group_concat(sin.serial separator '/'),''),
          ifnull(group_concat(sout.serial separator '/'),'')) as serialno,
          case
            when head.doc='RR' then 'Purchase Receiving'
            when head.doc='AC' then 'Job Completion'
            when head.doc='DM' then 'Purchase Return'
            when head.doc='SJ' then 'Sales Journal'
            when head.doc='CM' then 'Sales Return'
            when head.doc='AI' then 'Service Invoice'
            when head.doc='SU' then 'Stock Issuance'
            when head.doc='AJ' then 'Inventory Adjustment'
            when head.doc='TS' then 'Transfer Slip'
            when head.doc='IS' then 'Inventory Setup'
            else ''
          end as moduletype,FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))*(stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as totalcost,
          FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))*(stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0),2) as ext,stock.line,cntnum.center
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join uom on uom.itemid=stock.itemid and uom.uom='" . $uom . "'
        left join client as wh on wh.clientid=stock.whid
        left join cntnum on cntnum.trno=head.trno
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join glhead as svno on head.invtagging = svno.trno
        left join client as whref on whref.client = head.whref
        left join serialin as sin on sin.trno = stock.trno and sin.line = stock.line
        left join serialout as sout on sout.trno = stock.trno and sout.line = stock.line
        where cntnum.center = '" . $center . "' and stock.itemid=" . $itemid . " and head.dateid>='" . $date . "' and head.dateid<='" . $enddate . "'" . $filterwh . "
        group by  head.trno, head.doc, head.docno, head.dateid, head.clientname,stock.rrcost,
        wh.client,stock.disc, head.yourref, head.ourref, head.cur,head.forex,head.isimport, head.factor, stock.rem,stock.itemid,
        stock.loc,stock.expiry,cntnum.center, svno.docno,uom.factor,stock.cost,stock.qty,stock.amt,stock.iss,pallet.name, location.loc,wh.clientname,stock.ref,stock.line,cntnum.center
        order by status,dateid desc,trno desc";
        break;
      case 39: //cbbsi
        $decimalcurr = 2;
        $decimalqty = 2;
        $decimalprice = 2;
        $rrcost = "(stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))";

        $qry = "select '' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname, FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0),2) as rrcost2,wh.client as wh,
                FORMAT(ifnull(stock.ext,0)," . $decimalprice . ") as ext, 
                FORMAT(ifnull($rrcost,0)," . $decimalprice . ") as rrcost,
                FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as rrqty,
                FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalcurr . ") as isamt,
                FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as isqty,
                stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, (case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, item.itemid,
                stock.loc,stock.expiry,cntnum.center, '' as svnum,
                ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,whref.clientname as whref,stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3, '' as suppinvno,'' as siref
                from lahead as head  left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
                left join cntnum on cntnum.trno=head.trno
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join client as whref on whref.client = head.whref
                where item.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                UNION ALL
                select 'POSTED' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname, FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0),2) as rrcost2,wh.client as wh,
                FORMAT(ifnull(stock.ext,0)," . $decimalprice . "), 
                FORMAT(ifnull($rrcost,0)," . $decimalprice . ") as rrcost,
                FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrqty,
                FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as isamt,
                FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as isqty,
                stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, (case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, stock.itemid,
                stock.loc,stock.expiry,cntnum.center, svno.docno as svnum,
                ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,whref.clientname as whref,stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3,'' as suppinvno,'' as siref
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join uom on uom.itemid=stock.itemid and uom.uom='" . $uom . "'
                left join client as wh on wh.clientid=stock.whid
                left join cntnum on cntnum.trno=head.trno
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join glhead as svno on head.invtagging = svno.trno
                left join client as whref on whref.client = head.whref
                where cntnum.doc <>'RR' and stock.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                UNION ALL
                select 'POSTED' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname, FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0),2) as rrcost2,wh.client as wh,
                FORMAT(ifnull(stock.ext,0)," . $decimalprice . "), 
                FORMAT(case ifnull(a.cost,0) when 0 then $rrcost else (a.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)) end," . $decimalprice . ") as rrcost,
                FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrqty,
                FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as isamt,
                FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as isqty,
                stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, (case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, stock.itemid,
                stock.loc,stock.expiry,cntnum.center, svno.docno as svnum,
                ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,whref.clientname as whref,stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3, ifnull(hsh.docno,'') as suppinvno,ifnull(hsh.yourref,'') as siref
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno                
                left join uom on uom.itemid=stock.itemid and uom.uom='" . $uom . "'
                left join client as wh on wh.clientid=stock.whid
                left join cntnum on cntnum.trno=head.trno
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join glhead as svno on head.invtagging = svno.trno
                left join client as whref on whref.client = head.whref
                left join (select trno,refx,linex,cost from snstock union all select trno,refx,linex,cost from hsnstock) as a on a.refx = stock.trno and a.linex=stock.line
                left join (select trno,yourref,docno from lahead where doc ='SM' union all select trno,yourref,docno from glhead where doc='SM') as hsh on hsh.trno = a.trno
                where cntnum.doc ='RR' and stock.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                order by status,dateid desc,trno desc";
        break;

      default;
        switch ($companyid) {
          case 27: //nte
          case 36: //rozlab
            $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
            $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
            $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
            $rrcost = "(stock.rrcost)";

            break;
          default:
            $decimalcurr = 2;
            $decimalqty = 2;
            $decimalprice = 2;
            $rrcost = "(stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))";

            break;
        }

        $addqry = "";
        if ($companyid == 8) { //maxipro
          $addqry = " union all select * from (
                      select '' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid,
                      head.clientname,FORMAT(ifnull(if(stock.rrcost <> 0,stock.ext/stock.qty,0),0)," . $decimalprice . ") as rrcost2,
                      wh.client as wh,wh.clientname as whname,FORMAT(ifnull(stock.ext,0)," . $decimalprice . ") as ext,
                      FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrcost,
                      FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrqty,
                      0 as isamt,0 as isqty,0 as isqty2,stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                      '' as type,head.isimport,'' as factor,(case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, item.itemid,stock.loc,
                      '' as expiry,cntnum.center,'' as svnum,'' as pallet,'' as location,
                      stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3,wh.clientname as whref
                      from jchead as head  left join jcstock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
                      left join cntnum on cntnum.trno=head.trno
                      left join hjostock as jos on jos.trno=stock.refx
                      left join client as wh on wh.clientid = jos.whid
                      where item.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                      UNION ALL
                      select 'POSTED' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, head.clientname,
                      FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0)," . $decimalprice . ") as rrcost2,
                      wh.client as wh,wh.clientname as whname,FORMAT(ifnull(stock.ext,0)," . $decimalprice . ") as ext,
                      FORMAT(ifnull((stock.cost * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrcost,
                      FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrqty,
                      0 as isamt,0 as isqty,0 as isqty2,stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                      '' as type,head.isimport,'' as factor,(case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, stock.itemid,stock.loc,
                      '' as expiry,cntnum.center,'' as svnum,'' as pallet,'' as location,
                      stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3,wh.clientname as whref
                      from hjchead as head left join hjcstock as stock on stock.trno=head.trno
                      left join uom on uom.itemid=stock.itemid and uom.uom='" . $uom . "'
                      left join cntnum on cntnum.trno=head.trno
                      left join hjostock as jos on jos.trno=stock.refx
                      left join client as wh on wh.clientid = jos.whid
                      where stock.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                      ) as a
                      group by trno, doc, docno, dateid, clientname,rrcost2,wh, whname,ext,rrcost, rrqty,disc, yourref, ourref, cur, forex,
                              isimport,rem, itemid,loc,center,whref,ref, line,rrcost3,status,isamt,isqty,type,factor,balance,expiry,svnum,
                              pallet,location,tabtype,isqty2
                      ";
        }

        // if ($companyid == 43) { //mighty
        //   $addfield = ",wh.clientname as whref";
        // }

        $addfield = ",whref.clientname as whref";
        $baseamt="";
        $itemj="";

        switch($companyid){
          case 43://mighty
           $addfield = ",wh.clientname as whref";
           break;
          case 60://transpower
          $amt=" ,FORMAT(ifnull(stock.amt,0)," . $decimalprice . ") as amt ";
          $cost=" ,FORMAT(ifnull(stock.cost,0)," . $decimalprice . ") as cost ";
          $baseamt=", (case when stock.iss<>0 then stock.isamt else stock.rrcost end) as baseamt";
          $itemj="  left join item on item.itemid=stock.itemid";
            break;
        }

        $qry = "
                select '' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, 
                head.clientname, FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0),2) as rrcost2,
                wh.client as wh,wh.clientname as whname,
                FORMAT(ifnull(stock.ext,0)," . $decimalprice . ") as ext, 
                FORMAT(ifnull($rrcost,0)," . $decimalprice . ") as rrcost,
                FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as rrqty,
                FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalcurr . ") as isamt,
                FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as isqty,stock.isqty2,
                stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, (case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, item.itemid,
                stock.loc,stock.expiry,cntnum.center, '' as svnum,
                ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3 $addfield  $amt $baseamt $cost
                from lahead as head  left join lastock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
                left join cntnum on cntnum.trno=head.trno
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join client as whref on whref.client = head.whref
                where item.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                UNION ALL
                select 'POSTED' as status, head.trno, head.doc, head.docno, date(head.dateid) as dateid, 
                head.clientname, FORMAT(ifnull(if(stock.rrcost<>0,stock.ext/stock.qty,0),0),2) as rrcost2,
                wh.client as wh,wh.clientname as whname,
                FORMAT(ifnull(stock.ext,0)," . $decimalprice . "), 
                FORMAT(ifnull($rrcost,0)," . $decimalprice . ") as rrcost,
                FORMAT(ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as rrqty,
                FORMAT(ifnull((stock.amt * (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalcurr . ") as isamt,
                FORMAT(ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalprice . ") as isqty,stock.isqty2,
                stock.disc, head.yourref, head.ourref, ifnull(head.cur,'P') as cur, ifnull(head.forex,1) as forex,
                (case when stock.iss<>0 then 1 else 0 end) as type, head.isimport, head.factor, (case when head.doc='ST' then head.rem else stock.rem end) as rem, 0 as balance, stock.itemid,
                stock.loc,stock.expiry,cntnum.center, svno.docno as svnum,
                ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location,stock.ref, 'LEDGERTAB' as tabtype,stock.line,stock.rrcost as rrcost3 $addfield  $amt $baseamt $cost
                from glhead as head left join glstock as stock on stock.trno=head.trno
                left join uom on uom.itemid=stock.itemid and uom.uom='" . $uom . "'
                left join client as wh on wh.clientid=stock.whid
                left join cntnum on cntnum.trno=head.trno
                left join pallet on pallet.line=stock.palletid
                left join location on location.line=stock.locid
                left join glhead as svno on head.invtagging = svno.trno
                left join client as whref on whref.client = head.whref $itemj
                where stock.itemid=" . $itemid . " and head.dateid>='" . $date . "'" . $filterwh . $filtercenter . "
                " . $addqry . "
                
                order by status,dateid desc,trno desc";

                

        break;
    }

    return $this->coreFunctions->opentable($qry);
  }


  public function getbal($config, $itemid, $wh, $uom)
  {
    $center = $config['params']['center'];
    $wh = isset($config['params']['dataparams']['wh']) ? $config['params']['dataparams']['wh'] : '';

    $filtercenter = " and cntnum.center='" . $center . "' ";
    $isshareinv = $this->companysetup->getisshareinv($config['params']);
    if ($isshareinv) {
      $filtercenter = '';
    }

    if ($wh == '') {
      $filterwh = '';
    } else {
      $filterwh = " and wh.client='" . $wh . "'";
    }

    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);

    $qry1 = "select item.itemid,stock.whid,
        ifnull((stock.qty),0) as qty,
        ifnull((stock.iss),0) as iss
        from lahead as head  left join lastock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        where stock.itemid=" . $itemid . $filtercenter . "
        " . $filterwh . "
        UNION ALL
        select stock.itemid,stock.whid,
        ifnull((stock.qty),0) as qty,
        ifnull((stock.iss),0) as iss
        from glhead as head left join glstock as stock on stock.trno=head.trno
        left join cntnum on cntnum.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        where stock.itemid=" . $itemid . $filtercenter . " " . $filterwh;

    $qry = "select round(ifnull((sum(stock.qty) / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as rrqty,
      round(ifnull((sum(stock.iss) / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") as isqty,
      (round(ifnull((sum(stock.qty) / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ") -
      round(ifnull((sum(stock.iss) / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0)," . $decimalqty . ")) as bal
      from ( " . $qry1 . " ) as stock left join uom on uom.itemid=stock.itemid and uom.uom ='" . $uom . "' 
      left join client as wh on wh.clientid=stock.whid
      where ''='' " . $filterwh . "
      group by stock.itemid,uom.factor,uom.uom";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  public function recalcitem($config)
  {

    ini_set('max_execution_time', -1);

    $itemid = $config['params']['itemid'];
    $this->recalcrrstatus($config, $itemid);

    $this->coreFunctions->execqry("delete from costing where itemid=? and trno not in (select trno from cntnum)", 'delete', [$itemid]);
    $this->coreFunctions->execqry("delete from lastock where itemid=? and trno not in (select trno from cntnum)", 'delete', [$itemid]);
    $this->coreFunctions->execqry("delete from glstock where itemid=? and trno not in (select trno from cntnum)", 'delete', [$itemid]);

    $this->coreFunctions->execqry("delete from costing where itemid=?", 'delete', [$itemid]);
    $this->coreFunctions->execqry("update rrstatus set bal=qty where itemid=?", 'delete', [$itemid]);

    $qry = "select h.dateid, s.trno, s.line, s.itemid, s.uom, s.whid, s.isamt, s.amt, s.isqty, s.rrqty, s.iss, s.ext, h.tax, s.disc, 0 as posted, s.cost, h.cur, h.doc, s.loc, s.expiry,s.ref
          from lastock as s left join lahead as h on h.trno=s.trno where s.itemid=? and s.iss<>0
          union all
          select h.dateid, s.trno, s.line, s.itemid, s.uom, s.whid, s.isamt, s.amt, s.isqty, s.rrqty, s.iss, s.ext, h.tax, s.disc, 1 as posted, s.cost, h.cur, h.doc, s.loc, s.expiry,s.ref
          from glstock as s left join glhead as h on h.trno=s.trno where s.itemid=? and s.iss<>0 order by dateid";
    $this->recalctrans($config, $itemid, $qry);
  }

  public function recalcrrstatus($config, $itemid)
  {
    $qry = "select s.trno, s.line, s.itemid, s.uom, s.whid, s.rrcost, s.cost, s.rrqty, s.qty, s.ext, h.tax, s.disc, 0 as posted
          from lastock as s left join lahead as h on h.trno=s.trno where s.itemid=? and s.qty<>0
          union all
          select s.trno, s.line, s.itemid, s.uom, s.whid, s.rrcost, s.cost, s.rrqty, s.qty, s.ext, h.tax, s.disc, 1 as posted
          from glstock as s left join glhead as h on h.trno=s.trno where s.itemid=? and s.qty<>0";
    $data = $this->coreFunctions->opentable($qry, [$itemid, $itemid]);

    foreach ($data as $key => $value) {
      $qry = "select item.barcode,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$value->uom, $itemid]);
      $factor = 1;
      if (!empty($item)) {
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      }

      $computedata = $this->othersClass->computestock($value->rrcost, $value->disc, $value->rrqty, $factor, $value->tax);
      if ($computedata['qty'] != $value->qty) {
        $logstring = 'Qty: ' . $value->qty . ' - Computed qty: ' . $computedata['qty'] . ' - UOM: ' . $value->uom . ' - Factor: ' . $factor;

        $update = [
          'qty' => $computedata['qty'],
          'cost' => $computedata['amt'],
          'ext' => $computedata['ext']
        ];

        if ($value->posted) {
          $tablestock = 'glstock';
          $tablehead = 'glhead';
        } else {
          $tablestock = 'lastock';
          $tablehead = 'lahead';
        }

        if ($this->coreFunctions->sbcupdate($tablestock, $update, ['trno' => $value->trno, 'line' => $value->line])) {
          if ($value->posted) {
            $this->coreFunctions->execqry("update glstock as s left join rrstatus as rs on rs.trno=s.trno and rs.line=s.line set rs.qty=s.qty, rs.cost=s.cost where s.trno=? and s.line=? and s.qty<>rs.qty", 'update', [$value->trno, $value->line]);
          }
          $this->logger->sbcwritelog($itemid, $config, 'RECALC', $itemid . ' - ' . $item[0]->barcode . '. Update computed qty and cost. ' . $logstring, 'item_log');
        } else {
          $this->coreFunctions->create_Elog('Failed update ' . $logstring);
        }
      }
    }
  }

  public function recalctrans($config, $itemid, $qry)
  {
    $data = $this->coreFunctions->opentable($qry, [$itemid, $itemid]);
    foreach ($data as $key => $value) {
      if ($value->posted) {
        $tablestock = 'glstock';
        $tablehead = 'glhead';
      } else {
        $tablestock = 'lastock';
        $tablehead = 'lahead';
      }

      $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$value->uom, $itemid]);
      $factor = 1;
      $isnoninv = 0;
      if (!empty($item)) {
        $isnoninv = $item[0]->isnoninv;
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      }

      if ($value->doc == 'AJ') {
        $qty = $value->rrqty * -1;
      } else {
        $qty = $value->isqty;
      }

      $iss = 0;

      $computedata = $this->othersClass->computestock($value->isamt, $value->disc, $qty, $factor);
      $iss = round($computedata['qty'], $this->companysetup->getdecimal('qty', $config['params']));

      if ($computedata['qty'] != $value->iss) {
        $logstring = 'Qty: ' . $value->iss . ' - Computed qty: ' . $computedata['qty'] . ' - UOM: ' . $value->uom . ' - Factor: ' . $factor;
        $this->coreFunctions->LogConsole($logstring);

        $update = [
          'iss' => $iss,
          'amt' => round($computedata['amt'], $this->companysetup->getdecimal('price', $config['params'])),
          'ext' => $computedata['ext']
        ];

        if ($this->coreFunctions->sbcupdate($tablestock, $update, ['trno' => $value->trno, 'line' => $value->line])) {
          $this->logger->sbcwritelog($itemid, $config, 'RECALC', $itemid . ' - ' . $item[0]->barcode . '. Update computed qty and cost. Trno:' . $value->trno . ', Line:' . $value->line . ' - ' . $logstring, 'table_log');
        }
      }

      if ($isnoninv == 0) {
        $cost = $this->othersClass->computecosting($itemid, $value->whid, $value->loc, $value->expiry, $value->trno, $value->line, $iss, $value->doc, $config['params']['companyid']);
        $this->coreFunctions->LogConsole($tablestock . '-' . $value->trno . ' - Old Cost: ' . $value->cost . ', New Cost: ' . $cost);
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($tablestock, ['cost' => $cost], ['trno' => $value->trno, 'line' => $value->line]);
          if ($value->cost != $cost) {
            if ($value->posted) {
              $this->coreFunctions->sbcupdate('glhead', ['isreentryinv' => 1], ['trno' => $value->trno]);
              $this->coreFunctions->sbcupdate('cntnum', ['isok' => 0], ['trno' => $value->trno]);
            }
          }
        } else {
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$value->trno, $value->line]);
          $this->logger->sbcwritelog($value->trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $value->line . ' barcode:' . $item[0]->barcode . ' Qty' . $iss, 'table_log');
          $this->coreFunctions->LogConsole('OUT OF STOCK - Line:' . $value->line . ' barcode:' . $item[0]->barcode . ' Qty' . $iss);
        }
      }
    }
  }

  public function redistributeentry($config, $itemid)
  {
    $qry = "select h.trno, h.doc from glstock as s left join glhead as h on h.trno=s.trno where h.isrecalc=1 and s.itemid=? group by h.trno, h.doc";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    foreach ($data as $key => $value) {
      $config['params']['trno'] = $value->trno;
      switch ($value->doc) {
        case 'SJ':
          $this->distributeOut($config);
          break;
      }
    }
  }


  public function distributeOut($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $qry = 'select head.dateid,client.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.taxdef
          from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid 
          left join client on client.clientid = head.clientid left join client as wh on wh.clientid = stock.whid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
  }


  public function sbcscript($config){
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->skcustomform($config);
    } else {
      return true;
    }   
  }

} //end class
