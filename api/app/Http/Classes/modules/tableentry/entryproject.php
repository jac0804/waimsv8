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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryproject
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PROJECT MASTERFILE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'projectmasterfile';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = [
    'code',
    'name',
    'isho',
    'assetid',
    'liabilityid',
    'expenseid',
    'revenueid',
    'agentid',
    'comrate',
    'tin',
    'address',
    'groupid',
    'color',
    'rate',
    'minimum',
    'surcharge',
    'reconfee',
    'empid',
    'paygroupid',
    'isinactive'
  ];
  public $showclosebtn = false;
  private $reporter;
  private $lookupClass;
  private $logger;
  private $reportheader;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
    $this->lookupClass = new lookupClass;
    $this->reportheader = new reportheader;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 859);
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $action = 0;
    $code = 1;
    $group = 3;
    $name = 2;
    $address = 4;
    $isho = 5;
    $agentname = 6;
    $comrate = 7;
    $asset_stockgroup = 8;
    $liability_stockgroup = 9;
    $expense_stockgroup = 10;
    $revenue_stockgroup = 11;
    $tin = 12;
    $color = 13;
    $rate = 14;
    $minimum = 15;
    $surcharge = 16;
    $reconfee = 17;
    $empname = 18;
    $paygroup = 19;
    $isinactive = 20;

    $tab = [$this->gridname => ['gridcolumns' => [
      'action',
      'code',
      'name',
      'groupid',
      'address',
      'isho',
      'agentname',
      'comrate',
      'asset_stockgroup',
      'liability_stockgroup',
      'expense_stockgroup',
      'revenue_stockgroup',
      'tin',
      'color',
      'rate',
      'minimum',
      'surcharge',
      'reconfee',
      'empname',
      'paygroup',
      'isinactive'
    ]]];

    $stockbuttons = ['save', 'delete'];
    if ($systemtype == 'REALESTATE') array_push($stockbuttons, 'addphase');
    if ($systemtype == 'REALESTATE') array_push($stockbuttons, 'addhousemodel');

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $stockbuttons = [];
      }
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    if ($systemtype == 'REALESTATE') $obj[0][$this->gridname]['columns'][0]['btns']['addphase']['checkfield'] = 'newtrans';
    if ($systemtype == 'REALESTATE') $obj[0][$this->gridname]['columns'][0]['btns']['addhousemodel']['checkfield'] = 'newtrans';

    switch ($companyid) {
      case 8: //maxipro
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:30px;whiteSpace: normal;min-width:30px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$address]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';

        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
      case 10: //afti
      case 12: //afti usd
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$agentname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$agentname]['label'] = "Product Head";
        $obj[0][$this->gridname]['columns'][$isho]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$code]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tin]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$address]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$agentname]['lookupclass2'] = "lookup_agent_stockgroup";
        $obj[0][$this->gridname]['columns'][$agentname]['lookupclass'] = "lookup_agent_stockgroup";

        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        break;
      case 26: //bee healthy
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$address]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$tin]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$isho]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';

        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
      case 35: //aquamax
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$minimum]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$address]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tin]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';

        $obj[0][$this->gridname]['columns'][$rate]['label'] = 'Rate/cu.m.';

        $obj[0][$this->gridname]['columns'][$isho]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
      case 43: //mighty
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$address]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tin]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';

        $obj[0][$this->gridname]['columns'][$empname]['label'] = "Engineer";
        $obj[0][$this->gridname]['columns'][$empname]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$empname]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$empname]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$empname]['lookupclass'] = "lookupengineer";

        $obj[0][$this->gridname]['columns'][$paygroup]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "lookup";
        $obj[0][$this->gridname]['columns'][$paygroup]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$paygroup]['lookupclass'] = "lookuppaygroup";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
      case 39: //cbbsi
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$address]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$tin]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$empname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
      default:
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$name]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$agentname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$comrate]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$asset_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$liability_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$expense_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$address]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$tin]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$group]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rate]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$minimum]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$surcharge]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$reconfee]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$empname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$paygroup]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$isinactive]['type'] = "coldel";
        break;
    }

    $obj[0][$this->gridname]['columns'][$agentname]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$asset_stockgroup]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$liability_stockgroup]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$expense_stockgroup]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$revenue_stockgroup]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

    return $obj;
  }


  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $this->modulename = 'ITEM GROUP';
    }

    if ($companyid == 26) { //bee healthy
      $this->modulename = 'BUSINESS UNIT';
    }
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];

    if ($config['params']['companyid'] == 37) { //mega crystal
      $companyname = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", ['001']);
      if ($companyname == 'MULTICRYSTAL') {
        $tbuttons = ['print', 'whlog'];
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['code'] = '';
    $data['name'] = '';
    $data['empname'] = '';
    $data['isho'] = 'false';
    $data['agentid'] = 0;
    $data['comrate'] = 0;
    $data['empid'] = 0;

    $data['assetid'] = 0;
    $data['asset_stockgroup'] = '';
    $data['liabilityid'] = 0;
    $data['liability_stockgroup'] = '';
    $data['expenseid'] = 0;
    $data['expense_stockgroup'] = '';
    $data['revenueid'] = 0;
    $data['revenue_stockgroup'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    $data['tin'] = '';
    $data['address'] = '';
    $data['groupid'] = '';

    $data['rate'] = 0;
    $data['minimum'] = 0;
    $data['surcharge'] = 0;
    $data['reconfee'] = 0;
    $data['newtrans'] = 'true';
    $data['color'] = '';
    $data['paygroupid'] = 0;
    $data['paygroup'] = '';
    $data['isinactive'] = 'false';
    return $data;
  }

  private function selectqry()
  {
    $qry = "
    head.line, head.code, head.name, 
    case when isho=0 then 'false' else 'true' end as isho,
    ifnull(asset.acnoid,0) as assetid, ifnull(asset.acnoname,'') as asset_stockgroup,
    ifnull(lia.acnoid,0) as liabilityid, ifnull(lia.acnoname,'') as liability_stockgroup,
    ifnull(exp.acnoid,0) as expenseid, ifnull(exp.acnoname,'') as expense_stockgroup,
    ifnull(rev.acnoid,0) as revenueid, ifnull(rev.acnoname,'') as revenue_stockgroup,
    head.agentid, ifnull(ag.clientname,'') as agentname, head.comrate,head.tin, head.address,head.groupid, head.color,
    format(head.rate,2) as rate, format(head.minimum,2) as minimum, head.surcharge, format(head.reconfee,2) as reconfee,
    ifnull(emp.clientname,'') as empname,ifnull(head.empid,0) as empid,pg.paygroup,ifnull(pg.line,0) as paygroupid,(case when head.isinactive=0 then 'false' else 'true' end) as isinactive";
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $companyid = $config['params']['companyid'];
    $msg = '';
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0 && $data[$key]['name'] != '') {
          $qry = "select name,code from projectmasterfile where name = '" . $data[$key]['name'] . "' limit 1";
          $opendata = $this->coreFunctions->opentable($qry);
          $resultdata =  json_decode(json_encode($opendata), true);
          if (!empty($resultdata[0]['name']) || !empty($resultdata[0]['code'])) {
            if (trim($resultdata[0]['code']) == trim($data[$key]['code'])) {
              return ['status' => false, 'msg' => ' Code ( ' . $resultdata[0]['code'] . ' )' . ' is already exist', 'data' => [$resultdata]];
            }
            if (trim($resultdata[0]['name']) == trim($data[$key]['name'])) {
              return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata]];
            }
          }
        }

        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
          $data2['code'] = $data[$key]['name'];
          $data[$key]['code'] = $data[$key]['name'];
        }

        if (empty(trim($data[$key]['code'])) && empty(trim($data[$key]['name']))) {
          $msg = 'Code and Name is empty';
          return ['status' => false, 'msg' => $msg];
        }
        if (trim($data[$key]['code'] == '')) {
          $msg = 'Code is empty';
          return ['status' => false, 'msg' => $msg];
        }
        if (trim($data[$key]['name'] == '')) {
          $msg = 'Name is empty';
          return ['status' => false, 'msg' => $msg];
        }
        $data2['isho'] = $this->othersClass->sanitizekeyfield('isho', $data[$key]['isho']);



        if ($data[$key]['line'] == 0) {

          $project_id = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($project_id, $config, ' CREATE - ' . $data[$key]['code'] . ' - ' . $data[$key]['name']);
        } else {
          if ($data[$key]['line'] != 0 && $data[$key]['name'] != '') {
            $qry = "select name,line,code from projectmasterfile where name = '" . $data[$key]['name'] . "'  limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['name']) || !empty($resultdata[0]['code'])) {
              if (trim($resultdata[0]['code']) == trim($data[$key]['code'])) {
                if ($data[$key]['line'] == $resultdata[0]['line']) {
                  goto update;
                }
                return ['status' => false, 'msg' => ' Code ( ' . $resultdata[0]['code'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
              }
              if (trim($resultdata[0]['name']) == trim($data[$key]['name'])) {
                if ($data[$key]['line'] == $resultdata[0]['line']) {
                  goto update;
                }
                return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
              }
            } else {
              update:
              $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
              $data2['editby'] = $config['params']['user'];
              $data2['ismirror'] = 0;

              $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
              $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['name']);
            }
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $msg = '';
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0 && $row['name'] != '') {
      $qry = "select name,code from projectmasterfile where name = '" . $row['name'] . "' or code = '" . $row['code'] . "' limit 1";
      $opendata = $this->coreFunctions->opentable($qry);
      $resultdata =  json_decode(json_encode($opendata), true);
      if (!empty($resultdata[0]['name']) || !empty($resultdata[0]['code'])) {
        if (trim($resultdata[0]['code']) == trim($row['code'])) {
          return ['status' => false, 'msg' => 'Code ( ' . $resultdata[0]['code'] . ' )' . ' is already exist', 'data' => [$resultdata]];
        }
        if (trim($resultdata[0]['name']) == trim($row['name'])) {
          return ['status' => false, 'msg' => 'Code ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata]];
        }
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $data['code'] = $data['name'];
      $row['code'] = $data['name'];
    }


    if (empty(trim($row['code'])) && empty(trim($row['name']))) {
      $msg = 'Code and Name is empty';
      return ['status' => false, 'msg' => $msg];
    }

    if (trim($row['code'] == '')) {
      $msg = 'Code is empty';
      return ['status' => false, 'msg' => $msg];
    }
    if (trim($row['name'] == '')) {
      $msg = 'Name is empty';
      return ['status' => false, 'msg' => $msg];
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['code'] . ' - ' . $data['name']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($row['line'] != 0 && $row['name'] != '') {
        $qry = "select name,line from projectmasterfile where name = '" . $row['name'] . "' or code = '" . $row['code'] . "' limit 1";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata =  json_decode(json_encode($opendata), true);
        if (!empty($resultdata[0]['name']) || !empty($resultdata[0]['code'])) {
          if (trim($resultdata[0]['name']) == trim($row['name']) || trim($resultdata[0]['code']) == trim($row['code'])) {
            if ($row['line'] == $resultdata[0]['line']) {
              goto update;
            } else {
              return ['status' => false, 'msg' => ' Name ( ' . $resultdata[0]['name'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
            }
            if (trim($resultdata[0]['code']) == trim($row['code'])) {
              if ($row['line'] == $resultdata[0]['line']) {
                goto update;
              } else {
                return ['status' => false, 'msg' => ' Code ( ' . $resultdata[0]['code'] . ' )' . ' is already exist', 'data' => [$resultdata], 'rowid' => [$row['line'] . ' -- ' . $resultdata[0]['line']]];
              }
            }
          } else {
            update:
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $data['ismirror'] = 0;

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
              $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['name']);
              $returnrow = $this->loaddataperrecord($row['line']);
              return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
              return ['status' => false, 'msg' => 'Saving failed.'];
            }
          }
        } else {
          goto update;
        }
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $qry = "
      select project as value from glhead where project=?
      union all
      select project as value from lahead where project=?
      union all
      select p.code as value from pmhead left join projectmasterfile as p on p.line = pmhead.projectid where p.code=?
      union all
      select p.code as value from hpmhead left join projectmasterfile as p on p.line = hpmhead.projectid where p.code=?
    ";
    $count = $this->coreFunctions->datareader($qry, [$row['code'], $row['code'], $row['code'], $row['code']]);

    if ($count != '') {
      return ['clientid' => $row['code'], 'status' => false, 'msg' => 'Already Transaction...'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    if ($systemtype == 'REALESTATE') {
      $this->coreFunctions->execqry("delete from phase where projectid=" . $row['line'], 'delete');
      $this->coreFunctions->execqry("delete from blklot where projectid=" . $row['line'], 'delete');
    }
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['code'] . ' - ' . $row['name']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
    from " . $this->table . " as head
    left join coa as asset on asset.acnoid = head.assetid
    left join coa as lia on lia.acnoid = head.liabilityid
    left join coa as exp on exp.acnoid = head.expenseid
    left join coa as rev on rev.acnoid = head.revenueid
    left join client as ag on ag.clientid = head.agentid
    left join client as emp on emp.clientid = head.empid
    left join paygroup as pg on pg.line = head.paygroupid
    where head.line=?";

    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $filtersearch = "";
    $searcfield = ['head.code', 'head.name'];
    $limit = "1000";

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
    from " . $this->table . " as head
    left join coa as asset on asset.acnoid = head.assetid
    left join coa as lia on lia.acnoid = head.liabilityid
    left join coa as exp on exp.acnoid = head.expenseid
    left join coa as rev on rev.acnoid = head.revenueid
    left join client as ag on ag.clientid = head.agentid
    left join client as emp on emp.clientid = head.empid
    left join paygroup as pg on pg.line = head.paygroupid
    where 1=1 " . $filtersearch . "
    order by head.line limit " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'lookup_agent_stockgroup':
        return $this->lookupClass->lookup_agent_stockgroup($config);
        break;
      case 'lookupasset_stockgroup':
        return $this->lookupClass->lookupasset_stockgroup($config);
        break;
      case 'lookupliability_stockgroup':
        return $this->lookupClass->lookupliability_stockgroup($config);
        break;
      case 'lookupexpense_stockgroup':
        return $this->lookupClass->lookupexpense_stockgroup($config);
        break;
      case 'lookuprevenue_stockgroup':
        return $this->lookupClass->lookuprevenue_stockgroup($config);
        break;
      case 'lookupengineer':
        return $this->lookupengineer($config);
        break;
      case 'lookuppaygroup':
        return $this->lookuppaygroup($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Project Master Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookup_agent_stockgroup($config)
  {
    $plotting = array('agentid' => 'clientid', 'agentname' => 'clientname');
    $plottype = 'plotgrid';
    $title = 'List of Agent';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select client, clientname, clientid from client where isagent = '1'";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }

  public function lookupengineer($config)
  {
    //default
    $plotting = array('empname' => 'clientname', 'empid' => 'clientid');
    $plottype = 'plotgrid';
    $title = 'List of Employee';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Employee Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select clientid,client,clientname from client where isemployee = 1 order by clientname";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  } // end function
  public function lookuppaygroup($config)
  {
    //default
    $plotting = array('paygroup' => 'paygroup', 'paygroupid' => 'line');
    $plottype = 'plotgrid';
    $title = 'List of Pay Group';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'paygroup', 'label' => 'Pay Group', 'align' => 'left', 'field' => 'paygroup', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line,code,paygroup from paygroup";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = isset($config['params']['table']) ? $config['params']['table'] : "";
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'table' => $table];
  }

  // -> Print Function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti, afti usd
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    $paramstr = "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received";
    if ($config['params']['companyid'] == 8) { //maxipro
      $paramstr .= " , '$username' as prepared ";
    } else {
      $paramstr .= " ,'' as prepared ";
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select line, code, name from projectmasterfile
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_project_masterfile_layout($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_project_PDF($data, $config);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];


    $str = '';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Project Name', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_project_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['code'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['name'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function rpt_part_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    switch ($companyid) {
      case 3: //conti
      case 14: //majesty
      case 15: //nathina
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $reporttimestamp = $this->reporter->setreporttimestamp($filters, $username, $headerdata);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        break;
      case 8: //maxipro
        break;
      default:
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        break;
    }

    PDF::MultiCell(0, 0, "\n");
    $this->reportheader->getheader($filters);
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
    PDF::MultiCell(300, 20, "Project Name", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_project_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->rpt_part_PDF_header_PDF($data, $filters);

    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(250, 10, $data[$i]['code'], '', 'L', false, 0);
      PDF::MultiCell(490, 10, $data[$i]['name'], '', 'L', false);

      if (intVal($i) + 1 == $page) {
        $this->rpt_part_PDF_header_PDF($data, $filters);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


} //end class
