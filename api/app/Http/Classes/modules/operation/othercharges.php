<?php

namespace App\Http\Classes\modules\operation;

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


class othercharges
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Other Charges';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $fields = [];

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2556,
      'save' => 1847,
      'edit' => 773,
      'edititem' => 773,
      'delete' => 2752,
      'deleteitem' => 2752,
      'post' => 2491,
      'additem' => 2045,
      'saveallentry' => 1847
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $description = 0;
    $amt = 1;
    $rem = 2;
    $status = 3;


    $tab = [$this->gridname => [
      'gridcolumns' => [
        'description', 'amt', 'rem', 'status'
      ]
    ]];

    $stockbuttons = [];
    // $stockbuttons=[];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    // $obj[0][$this->gridname]['label'] = 'TENANTS';

    $obj[0][$this->gridname]['columns'][$description]['label'] = 'Billable Item';
    $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][$status]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$status]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$description]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$description]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['label'] = 'OTHER CHARGES';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[2]['action'] = "lookuplogs";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['month', 'year'];
    $col1 = $this->fieldClass->create($fields);
    // sbccsenablealways
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');
    data_set($col1, 'year.readonly', false);
    data_set($col1, 'year.name', 'byear');

    $fields = ['client', ['refresh', 'post']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'client.label', 'Tenant');
    data_set($col2, 'client.lookupclass', 'tenant');
    data_set($col2, 'client.name', 'clientname');
    data_set($col2, 'refresh.action', 'load');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select 
      '' as month,
      '' as bmonth,
      '' as year,
      '' as byear,
      '' as client,
      '' as clientname,
      '0' as clientid,'' as status,0 as isposted
    ");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case 'load':
        return $this->loadgrid($config);
        break;
      case 'print':
        return $this->setupreport($config);
        break;

      case 'saveallentry':
      case 'update':
        $this->save($config);
        return $this->loadgrid($config);
        break;
      case 'post':
        if ($this->post($config)) {
          return $this->loadgrid($config);
        } else {
          return ['status' => false, 'msg' => 'Error in Posting', 'data' => []];
        }

        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config)
  {

    $center = $config['params']['center'];
    $bmonth  = $config['params']['dataparams']['bmonth'];
    $byear  = $config['params']['dataparams']['byear'];
    $clientid  = $config['params']['dataparams']['clientid'];
    $clientname  = $config['params']['dataparams']['clientname'];

    if ($clientid == 0) {
      return ['status' => false, 'msg' => 'Tenant is Required', 'data' => []];
    }

    if ($bmonth == 0) {
      return ['status' => false, 'msg' => 'month is Required', 'data' => []];
    }

    if ($byear == 0) {
      return ['status' => false, 'msg' => 'Year is Required', 'data' => []];
    }

    $qry = "select distinct * from (select ifnull(c.line,0) as line,ifnull(o.line,0) as cline,ifnull(o.description,'') as description,ifnull(c.amt,0) as amt,
    ifnull(c.rem,'') as rem,'' as bgcolor,$bmonth as bmonth,$byear as byear,$clientid as clientid,'" . $center . "' as center,'" . $clientname . "' as clientname,
    case ifnull(c.isposted,0) when 1 then 'Posted' else '' end as status,ifnull(c.isposted,0) as isposted
    from ocharges as o left join chargesbilling as c on o.line = c.cline and (c.clientid = ? and c.bmonth =?
    and c.byear =? and c.center =?)
    where o.description<> ''      
    order by line) as a";

    //union all
    // select ifnull(c.line,0) as line,ifnull(o.line,0) as cline,ifnull(o.description,'') as description,ifnull(c.amt,0) as amt,
    // ifnull(c.rem,'') as rem,'' as bgcolor,$bmonth as bmonth,$byear as byear,$clientid as clientid,'".$center."' as center,'".$clientname."' as clientname,
    // case ifnull(c.isposted,0) when 1 then 'Posted' else '' end as status,ifnull(c.isposted,0) as isposted
    // from ocharges as o left join hchargesbilling as c on o.line = c.cline and (c.clientid = ? and c.bmonth =?
    // and c.byear =? and c.center =?)
    // where o.description<> ''
    $data = $this->coreFunctions->opentable($qry, [$clientid, $bmonth, $byear, $center, $clientid, $bmonth, $byear, $center]);

    $billed = $this->coreFunctions->datareader("select trno as value from glhead where doc='MB' and month(dateid)= " . $bmonth . " and year(dateid)=" . $byear . " and clientid = " . $clientid);
    if (floatval($billed) <> 0) {
      return ["status" => false, "msg" => "Already Billed", "data" => []];
    }

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function save($config)
  {
    $rows = isset($config['params']['rows']) ? $config['params']['rows'] : $config['params']['row'];
    $center = $config['params']['center'];
    $user = $config['params']['user'];
    $d = [];


    foreach ($rows as $key => $val) {
      if ($val["bgcolor"] != "") {
        foreach ($this->fields as $k) {
          $val[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
        }

        if ($val['bgcolor'] != "" && $val["isposted"] != 1) {
          $d['cline'] = $val['cline'];
          $d['amt'] = $val['amt'];
          $d['bmonth'] = $val['bmonth'];
          $d['byear'] = $val['byear'];
          $d['clientid'] = $val['clientid'];
          $d['center'] = $center;
          $d['rem'] = $val['rem'];

          //$exist = $this->coreFunctions->getfieldvalue("chargesbilling","clientid","clientid =? and cline=? and byear =? and bmonth =? and byear=? and center =?",[$val['clientid'],$val['cline'],$val['byear'],$val['bmonth'],$center]);
          if (floatval($val['line']) == 0) {
            //$this->coreFunctions->sbcinsert("chargesbilling",$d);
            $line = $this->coreFunctions->insertGetId("chargesbilling", $d);
            $this->logger->sbcmasterlog($line, $config, ' CREATE - Tenant : ' . $val['clientname'] . ' | Charges : ' . $val['description'] . ' | Amount : ' . $val['amt'] . ' | Month: ' . $val['bmonth'] . ' | Year: ' . $val['byear']);
          } else {
            $this->coreFunctions->sbcupdate("chargesbilling", ["amt" => $d["amt"], "rem" => $d["rem"], "editby" => $user, "editdate" => $this->othersClass->getCurrentTimeStamp()], ["clientid" => $val["clientid"], "line" => $val["line"]]);
          }
        }
      }
    }
  }

  private function post($config)
  {
    $center = $config['params']['center'];
    $user = $config['params']['user'];
    $clientid = $config['params']['dataparams']['clientid'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $byear = $config['params']['dataparams']['byear'];
    $d = [];

    return $this->coreFunctions->sbcupdate("chargesbilling", ["isposted" => 1, "postby" => $user, "postdate" => $this->othersClass->getCurrentTimeStamp()], ["clientid" => $clientid, "bmonth" => $bmonth, "byear" => $byear]);
  }
} //end class
