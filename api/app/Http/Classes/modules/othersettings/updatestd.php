<?php

namespace App\Http\Classes\modules\othersettings;

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
use App\Http\Classes\SBCPDF;



class updatestd
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = "UPDATE SGD RATES";
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $logger;
  public $tablelogs = 'table_log';
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $tablenum = 'cntnum';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs_del = 'del_table_log';
  private $acctg = [];
  public $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array('view' => 3743, 'save' => 2456, 'saveallentry' => 2456);
    return $attrib;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $docno = 0;
    $amt = 1;
    $dateid = 2;
    $clientname = 3;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['docno', 'amt', 'dateid', 'clientname', 'itemname']
      ]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'TRANSACTION LIST';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Customer";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "text-align:left;width:250px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "SGD Rates";
    $obj[0][$this->gridname]['columns'][$amt]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "text-align:right;width:90px;whiteSpace: normal;min-width:90px;";

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $return = [];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "UPDATE SGD RATE";
    $obj[0]['access'] = "save";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['month', 'year', 'amt', 'amt2', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'month.required', true);
    data_set($col1, 'year.required', true);
    data_set($col1, 'refresh.label', 'Refresh');
    data_set($col1, 'amt.label', 'SGD Rates');
    data_set($col1, 'amt.required', true);
    data_set($col1, 'refresh.action', 'refresh');
    data_set($col1, 'amt2.label', 'Actual SGD Rate');
    data_set($col1, 'amt2.readonly', false);
    data_set($col1, 'amt.readonly', false);
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $strmonth = "";
    $stryr = "";
    $sg = "";
    $sg2 = "";

    if (isset($config['params']['dataparams'])) {
      $strmonth = $config['params']['dataparams']['month'];
      $stryr = $config['params']['dataparams']['year'];
      $sg = $config['params']['dataparams']['amt'];
      $sg2 = $config['params']['dataparams']['amt2'];
    }

    $qry = "select '" . $strmonth . "' as `month`, '" . $stryr . "' as `year`, '" . $sg . "' as amt,'" . $sg2 . "' as amt2";
    $data = $this->coreFunctions->opentable($qry);
    return $data[0];
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];
    switch ($action) {
      case 'refresh':
        return $this->loaddata($config);
        break;
      case 'saveallentry':
        return $this->saveall($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  public function stockstatusposted($config)
  {

    $action = $config['params']["action"];
    switch ($action) {
      case 'refresh':
        return $this->loaddata($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $action . ')'];
        break;
    } // end switch
  }


  private function selectqry($branchfilter, $dateid)
  {
  }

  private function loaddata($config)
  {
    $month = $config['params']['dataparams']['month'];
    $yr = $config['params']['dataparams']['year'];
    $sgrate = $config['params']['dataparams']['amt'];
    $sgfilter = "";

    if ($sgrate != '') {
      $sgfilter = " and stock.sgdrate = " . $sgrate;
    }

    if ($month == '' || $yr == '') {
      return ['status' => false, 'msg' => 'Enter month and year', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
    }

    $qry = "select head.trno,so.docno,left(head.dateid,10) as dateid,head.clientname,group_concat(distinct stock.sgdrate) as amt from hsqhead as so left join hqshead as head on head.sotrno = so.trno 
    left join hqsstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid 
    where item.islabor=0 and month(so.dateid) = " . $month . " and year(so.dateid)=" . $yr . $sgfilter . " group by head.trno,so.docno,head.dateid,head.clientname
    union all
    select qs.trno,so.docno,left(head.dateid,10) as dateid,head.clientname,group_concat(distinct stock.sgdrate) as amt from hsshead as so left join hsrhead as head on head.sotrno = so.trno 
    left join hsrstock as stock on stock.trno = head.trno left join hqshead as qs on qs.trno = head.qtrno left join item on item.itemid = stock.itemid where month(qs.due) = " . $month . " and year(qs.due)=" . $yr . $sgfilter . " 
     group by qs.trno,so.docno,head.dateid,head.clientname";

    $trans = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $trans]];
  }

  private function saveall($config)
  {
    $month = $config['params']['dataparams']['month'];
    $yr = $config['params']['dataparams']['year'];
    $sgrate = $config['params']['dataparams']['amt'];
    $sgrate2 = $config['params']['dataparams']['amt2'];
    $rows = $config['params']['rows'];

    if (!empty($rows)) {
      foreach ($rows as $key => $val) {
        $data = [];
        $this->coreFunctions->sbcupdate("hqshead", ['sgdrate' => $sgrate2], ['trno' => $val["trno"]]);
        $this->coreFunctions->sbcupdate("hqsstock", ['sgdrate' => $sgrate2], ['trno' => $val["trno"]]);
        $this->coreFunctions->sbcupdate("hqtstock", ['sgdrate' => $sgrate2], ['trno' => $val["trno"]]);
        $this->coreFunctions->sbcupdate("hsrhead", ['sgdrate' => $sgrate2], ['qtrno' => $val["trno"]]);
        $srtrno = $this->coreFunctions->getfieldvalue("hsrhead", "trno", "qtrno = ?", [$val["trno"]]);
        $this->coreFunctions->sbcupdate("hsrstock", ['sgdrate' => $sgrate2], ['trno' => $srtrno]);
        $this->logger->sbcwritelog($val["trno"], $config, 'RECON', $val['docno'] . ', SGD Rate: ' . $sgrate2);
      }
    }

    return $this->loaddata($config);
  }
} //end class
