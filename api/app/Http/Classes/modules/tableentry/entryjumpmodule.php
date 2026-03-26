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

class entryjumpmodule
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LIST OF REFERENCE';
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

  public function getAttrib($config)
  {
    $attrib = array(
      'load' => 2736
    );

    $tabtype = $config['params']['row']['tabtype'];
    switch ($tabtype) {
      case 'ARTAB':
        $attrib['load'] = 2735;
        break;
      case 'APTAB':
        $attrib['load'] = 2736;
        break;
      case 'UNPAIDTAB':
        $attrib['load'] = 2992;
        break;
      case 'PDCTAB':
        $attrib['load'] = 2737;
        break;
      case 'RCTAB':
        $attrib['load'] = 2738;
        break;
      case 'RRTAB':
      case 'LEDGERTAB':
        $attrib['load'] = 12;
        break;
      case 'SUPPINVOICE':
        $attrib['load'] = 2240;
        break;
      case 'BALANCEWH':
        $attrib['load'] = 12;
        break;
    }

    return $attrib;
  }

  public function createTab($config)
  {

    $tabtype = $config['params']['row']['tabtype'];

    $action = 0;
    $docno = 1;
    $loc = 2;
    $expiry = 3;
    $bal = 4;


    $tab = [$this->gridname => ['gridcolumns' => ['action', 'docno', 'loc', 'expiry', 'bal']]];
    $stockbuttons = ['jumpmodule'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action

    if ($tabtype == 'BALANCEWH') {
      $this->modulename = $config['params']['row']['clientname'] . " - " . $config['params']['row']['barcode'] . " > " .  $config['params']['row']['itemname'];

      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';

      $obj[0][$this->gridname]['columns'][$action]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$docno]['type'] = 'coldel';
    } else {
      $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
      $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

      $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$bal]['type'] = 'coldel';
    }

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
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];

    $tabtype = $config['params']['row']['tabtype'];

    switch ($tabtype) {
      case 'BALANCEWH':
        $center = $config['params']['center'];
        $itemid = $config['params']['row']['itemid'];
        $whid = $config['params']['row']['whid'];
        $bal = ",round(sum(bal),2) as bal";
        $join = "";
        $group = "";
        if ($companyid == 27 || $companyid == 36) { //NTE,ROZLAB
          $bal = ",format(round(sum(bal)/uom.factor,2)," . $this->companysetup->getdecimal('price', $config['params']) . ") as bal";
          $join = "left join uom on uom.itemid=rrstatus.itemid and uom.uom=rrstatus.uom";
          $group = ",factor";
        }
        $qry = "select client.clientname as clientname,whid,rrstatus.itemid,rrstatus.itemid as trno,'BALANCEWH' as tabtype,loc,expiry, 
           '' as itemname $bal
              from rrstatus
              left join client on client.clientid = rrstatus.whid
              left join cntnum on cntnum.trno=rrstatus.trno
              $join
              where cntnum.center='" . $center . "' and rrstatus.itemid ='" . $itemid . "' and rrstatus.whid=" . $whid . "
              group by client.clientname,whid,rrstatus.itemid,loc $group ,expiry having sum(bal)<>0 ";

        return $this->coreFunctions->opentable($qry);
        break;
      default:
        $doc = $this->coreFunctions->getfieldvalue('cntnum', 'doc', 'trno=?', [$row['trno']]);
        $url = $this->checkdoc($doc, $companyid);
        $qry = "select trno,doc,docno,'' as bgcolor,'" . $url . "' as url,'module' as moduletype from cntnum where trno=? ";

        $qry2 = " select trno,doc from cntnum where trno in (select trno from ladetail where refx=? and linex=?) ";
        $data2 = $this->coreFunctions->opentable($qry2, [$row['trno'], $row['line']]);
        if (!empty($data2)) {
          foreach ($data2 as $key => $value) {
            $url = $this->checkdoc($value->doc, $companyid);
            if ($url !== '') {
              $qry = $qry . " union all ";
              $qry = $qry . " select trno,doc,docno,'' as bgcolor,'" . $url . "' as url,'module' as moduletype from cntnum where trno=" . $value->trno;
            }
          }
        }

        $qry2 = " select trno,doc from cntnum where trno in (select trno from gldetail where refx=? and linex=?) ";
        $data2 = $this->coreFunctions->opentable($qry2, [$row['trno'], $row['line']]);
        if (!empty($data2)) {
          foreach ($data2 as $key => $value) {
            $url = $this->checkdoc($value->doc, $companyid);
            if ($url !== '') {
              $qry = $qry . " union all ";
              $qry = $qry . " select trno,doc,docno,'' as bgcolor,'" . $url . "' as url,'module' as moduletype from cntnum where trno=" . $value->trno;
            }
          }
        }

        if (isset($row['itemid'])) {
          $qry2 = " select c.trno, cntnum.doc from costing as c left join cntnum on cntnum.trno=c.trno where refx=? and itemid=?";
          $data2 = $this->coreFunctions->opentable($qry2, [$row['trno'], $row['itemid']]);
          if (!empty($data2)) {
            foreach ($data2 as $key => $value) {
              $url = $this->checkdoc($value->doc, $companyid);
              if ($url !== '') {
                $qry = $qry . " union all ";
                $qry = $qry . " select trno,doc,docno,'' as bgcolor,'" . $url . "' as url,'module' as moduletype from cntnum where trno=" . $value->trno;
              }
            }
          }
        }
        break;
    }

    $data = $this->coreFunctions->opentable($qry, [$row['trno']]);
    return $data;
  } //end function

  public function checkdoc($doc, $companyid)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'sj':
      case 'cm':
      case 'ai':
        $url = "/module/sales/";
        break;
      case 'mj':
        $url = "/module/cdo/";
        break;
      case 'ar':
      case 'cr':
        $url = "/module/receivable/";
        break;
      case 'dm':
      case 'rr':
      case 'cd':
      case 'po':
      case 'pr':
        $folderloc = 'purchase';
        if ($companyid == 16) $folderloc = 'ati'; //ati
        $url = "/module/" . $folderloc . "/";
        break;
      case 'ac':
      case 'jb':
        $url = "/module/purchase/";
        break;
      case 'ap':
      case 'cv':
      case 'pv':
        $folderloc = 'payable';
        if ($companyid == 16 && $doc == 'cv') $folderloc = 'ati'; //ati
        $url = "/module/" . $folderloc . "/";
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
        $url = "/module/issuance/";
        break;
      case 'su':
        if ($companyid == 10) { //afti
          $url = "/module/sales/";
        } else {
          $url = "/module/issuance/";
        }
        break;
      case 'mi':
        $url = "/module/construction/";
        break;
      case 'pg':
      case 'jp':
        $url = "/module/production/";
        break;
      case 'wm':
        $url = "/module/waterbilling/";
        break;
    }
    return $url;
  }
} //end class
