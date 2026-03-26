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

use Carbon\Carbon;


class viewitemstockinfo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ITEM LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $column = ['action', 'barcode', 'itemname'];
    $companyid = $config['params']['companyid'];
    $allowview = $this->othersClass->checkAccess($config['params']['user'], 5488);
    // $allowview = 0;



    if ($config['params']['companyid'] == 10) { //afti
      if ($config['params']['doc'] == "SQ") {
        array_push($column, 'leaddur');
      }
    }

    if ($config['params']['companyid'] == 60) { //transpower
      if ($config['params']['doc'] == "SJ" || $config['params']['doc'] == "SO" || $config['params']['doc'] == "RR" || $config['params']['doc'] == "DM" || $config['params']['doc'] == "PO") {
        $column = [  'action','barcode', 'itemname', 'isamt',  'disc','agentamt', 'namt5', 'namt7',  'amt2','disc2', 'namt2', 'amt4','disc4','namt4' ];
      }
    }



    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $column]];


    $stockbuttons = ['stockinfo'];

    if ($config['params']['companyid'] == 60) { //transpower
      if ($config['params']['doc'] == "SJ" || $config['params']['doc'] == "SO" || $config['params']['doc'] == "RR" || $config['params']['doc'] == "DM" || $config['params']['doc'] == "PO") {
        array_push($stockbuttons, 'showbalance');
      }
    }

    if ($config['params']['companyid'] == 63) { //ericco
      if ($config['params']['doc'] == "SJ") {
        array_push($stockbuttons, 'showcomponent');
      }
    }


    if ($this->companysetup->getisiteminfo($config['params'])) {
      $stockbuttons = [];
      array_push($stockbuttons, 'iteminfo');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";
    // $obj[0][$this->gridname]['columns'][$leadfrom]['readonly'] = true;
    // $obj[0][$this->gridname]['columns'][$leadto]['readonly'] = true;

    if ($config['params']['companyid'] == 60) { //transpower

      $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";


      $obj[0][$this->gridname]['columns'][$namt7]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$namt5]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$amt2]['type'] = "label";
      // $obj[0][$this->gridname]['columns'][$amt]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";

      $obj[0][$this->gridname]['columns'][$namt7]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";

      $obj[0][$this->gridname]['columns'][$namt5]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
      $obj[0][$this->gridname]['columns'][$amt2]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
      // $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";

      // $obj[0][$this->gridname]['columns'][$amt]['label'] = "Base Price";


      $obj[0][$this->gridname]['columns'][$amt2]['label'] = "Wholesale Base";
      $obj[0][$this->gridname]['columns'][$disc2]['label'] = "Wholesale Disc";

      $obj[0][$this->gridname]['columns'][$disc]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$disc2]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$namt2]['type'] = "label";

      if ($allowview) {
        $obj[0][$this->gridname]['columns'][$amt4]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$disc4]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$namt4]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$amt4]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
        $obj[0][$this->gridname]['columns'][$disc4]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
        $obj[0][$this->gridname]['columns'][$namt4]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
      } else {
        $obj[0][$this->gridname]['columns'][$amt4]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$disc4]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$namt4]['type'] = "coldel";
      }

      $obj[0][$this->gridname]['columns'][$disc4]['label'] = "Cost Disc";
      $obj[0][$this->gridname]['columns'][$amt4]['label'] = "Cost Base";

      $obj[0][$this->gridname]['columns'][$action]['btns']['showbalance']['name'] = 'lookup';
      $obj[0][$this->gridname]['columns'][$action]['btns']['showbalance']['action'] = 'showbalance';
      $obj[0][$this->gridname]['columns'][$action]['btns']['showbalance']['lookupclass'] = 'showbalance';
      $obj[0][$this->gridname]['columns'][$isamt]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$isamt]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";


      switch ($config['params']['doc']) {
        case 'SJ':
          $obj[0][$this->gridname]['columns'][$agentamt]['type'] = "label";
          $obj[0][$this->gridname]['columns'][$agentamt]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
          $obj[0][$this->gridname]['columns'][$isamt]['label'] = "SJ Amount";
          $obj[0][$this->gridname]['columns'][$disc]['label'] = "SJ Discount";
          break;
        case 'SO':
          $obj[0][$this->gridname]['columns'][$agentamt]['type'] = "label";
          $obj[0][$this->gridname]['columns'][$disc]['label'] = "Discount";
          $obj[0][$this->gridname]['columns'][$agentamt]['style'] = "width:70px;whiteSpace: normal;min-width:70px;  text-align:right;";
          break;
        case 'DM':
        case 'RR':
        case 'PO':
          $obj[0][$this->gridname]['columns'][$isamt]['label'] = "Trans Amount";
          $obj[0][$this->gridname]['columns'][$disc]['label'] = "Trans Discount";
          $obj[0][$this->gridname]['columns'][$agentamt]['type'] = "coldel";
          break;
      }
    }

    if ($companyid == 63) { //ericco
      $obj[0][$this->gridname]['columns'][$action]['btns']['showcomponent']['name'] = 'lookup';
      $obj[0][$this->gridname]['columns'][$action]['btns']['showcomponent']['action'] = 'showcomponent';
      $obj[0][$this->gridname]['columns'][$action]['btns']['showcomponent']['lookupclass'] = 'showcomponent';
    }

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  private function selectqry($config)
  {
    $hstock = 'glstock';
    switch ($config['params']['doc']) {
      case 'PO':
      case 'QS':
      case 'QT':
      case 'SR':
      case 'OP':
      case 'OS':
      case 'PR':
      case 'RF':
      case 'SO':
        $hstock = 'h' . strtolower($config['params']['doc']) . 'stock';
        break;
    }

    switch ($config['params']['doc']) {
      case 'SQ':
        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno,
        ifnull(sinfo.leadfrom, '') as leadfrom, ifnull(sinfo.leadto, '') as leadto,
        ifnull(sinfo.leaddur, '') as leaddur
        from hqshead as h 
        left join hqsstock as s on s.trno=h.trno 
        left join item on item.itemid=s.itemid
        left join hstockinfo as sinfo on sinfo.trno = s.trno and sinfo.line = s.line 
        where h.sotrno=?";
        break;
      case 'QS':
        $filter = "";
        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno
        from " . $hstock . " as s 
        left join item on item.itemid=s.itemid 
        where s.trno=?";
        break;
      case 'AO':
        $trno = $this->coreFunctions->datareader("select trno as value from hsrhead where sotrno = '" . $config['params']['tableid'] . "'");
        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno
        from hsrstock as s 
        left join item on item.itemid=s.itemid 
        where s.trno='" . $trno . "'";
        break;
      default:
        $filter = " and s.cline = 0 ";
        $addf = "";
        $companyid = $config['params']['companyid'];
        if ($config['params']['doc'] == "TS") {
          $filter .= " and s.tstrno = 0";
        }

        if ($companyid == 60) { //transpower
          if ($config['params']['doc'] == "SJ" || $config['params']['doc'] == "SO" || $config['params']['doc'] == "RR" || $config['params']['doc'] == "DM" || $config['params']['doc'] == "PO") {
            $amtfield = "";
            if ($config['params']['doc'] == "RR" || $config['params']['doc'] == "PO") {
              $amtfield = ",format(s.rrcost,2) as isamt";
            } else {
              $amtfield = ",format(s.isamt,2) as isamt,format(s.agentamt,2) as agentamt";
            }

            $addf = ",s.disc,format(item.namt7,2) as namt7,format(item.namt5,2) as namt5,
            format(item.amt2,2) as amt2,item.disc2,format(item.namt2,2) as namt2,
            format(item.amt,2) as amt ,
            format(item.amt4,2) as amt4, item.disc4,format(item.namt4,2) as namt4 $amtfield";
          }
          if ($config['params']['doc'] == "SO" || $config['params']['doc'] == "PO") {
            $filter = '';
          }
        }

        $sql = "select item.itemid, item.barcode, item.itemname, s.line, s.trno $addf
        from " . $hstock . " as s 
        left join item on item.itemid=s.itemid 
        where s.trno=? " . $filter . "";
        break;
    }
    return $sql;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);

    switch ($config['params']['doc']) {
      case 'AO':
        $data = $this->coreFunctions->opentable($qry);
        break;
      default:
        $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
        break;
    }
    return $data;
  }
} //end class
