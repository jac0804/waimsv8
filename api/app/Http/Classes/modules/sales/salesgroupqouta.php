<?php

namespace App\Http\Classes\modules\sales;

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


class salesgroupqouta
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Sales Person Quota';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $fields = ['yr', 'projectid', 'amt'];

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
      'view' => 3861, 'save' => 3862,
      'saveallentry' => 3862
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['project', 'amt']
      ]
    ];
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['entrygrid']['label'] = 'ITEM GROUP';
    $obj[0]['entrygrid']['columns'][0]['label'] = 'Item Group';
    $obj[0]['entrygrid']['columns'][0]['type'] = 'input';
    $obj[0]['entrygrid']['columns'][0]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0]['entrygrid']['columns'][1]['label'] = 'Monthly Qouta';
    $obj[0]['entrygrid']['columns'][1]['align'] = 'text-left';
    $obj[0]['entrygrid']['columns'][1]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['action'] = "lookuplogs";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['year', 'agentname', ['refresh', 'print']];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'year.type', 'lookup');
    data_set($col1, 'year.class', 'sbccsreadonly');
    data_set($col1, 'year.lookupclass', 'lookupyear');
    data_set($col1, 'year.action', 'lookupyear');
    data_set($col1, 'refresh.action', 'load');


    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select '' as year, '' as agentname, '' as agent, '0' as agentid ");

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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    } // end switch
  }

  private function loadgrid($config)
  {

    $center = $config['params']['center'];
    $year  = $config['params']['dataparams']['year'];
    $agentid  = $config['params']['dataparams']['agentid'];
    $agentname  = $config['params']['dataparams']['agentname'];
    $salesgroup = [];

    $qry = "select ig.line, ig.yr, ig.projectid, format(ig.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt, p.name as project, '' as bgcolor 
    from salesgroupqouta as ig 
    left join projectmasterfile as p on p.line = ig.projectid
    left join client as agent on agent.clientid = ig.agentid
    where ig.yr=? and ig.agentid = ? ";
    $data = $this->coreFunctions->opentable($qry, [$year, $agentid]);

    if (empty($data)) {
      if ($year == '' || $agentname == '') {
        return ['status' => 'false', 'msg' => 'Please select year and Agent', 'griddata' => [], 'action' => 'load'];
      }
    }

    $project = "select line, name from projectmasterfile where line not in (select projectid from salesgroupqouta where yr = ? and agentid = ?) order by name";
    $dataproject  = $this->coreFunctions->opentable($project, [$year, $agentid]);

    if (!empty($dataproject)) {
      foreach ($dataproject as $key => $value) {
        $salesgroup['projectid'] = $dataproject[$key]->line;
        $salesgroup['yr'] = $year;
        $salesgroup['agentid'] = $agentid;
        $salesgroup['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $salesgroup['editby'] = $config['params']['user'];

        $line = $this->coreFunctions->insertGetId("salesgroupqouta", $salesgroup);
        if ($line != 0) {
          $this->logger->sbcmasterlog($line, $config, ' CREATE - YEAR : ' . $year . ' | ITEM GROUP : ' . $dataproject[$key]->name . ' | AGENT NAME : ' . $agentname);
          $data = $this->coreFunctions->opentable($qry, [$year, $agentid]);
        } else {
          return ['status' => false, 'msg' => 'Error getting accounts', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
        }
      }

      return ['status' => true, 'msg' => 'Saved. Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    } else {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
  }


  private function save($config)
  {
    $rows = $config['params']['rows'];

    foreach ($rows as $key => $val) {
      if ($val["bgcolor"] != "") {
        foreach ($this->fields as $k) {
          $val[$k] = $this->othersClass->sanitizekeyfield($k, $val[$k]);
        }

        $this->coreFunctions->sbcupdate("salesgroupqouta", ['amt' => $val['amt'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['line' => $val["line"]]);
      }
    }
  }

  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = $this->reportdata($config);
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => true, 'action' => 'print'];
  }


  public function createreportfilter($config)
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  private function salesgroup_query($config)
  {
    $yr = $config['params']['dataparams']['year'];
    $agentid = $config['params']['dataparams']['agentid'];
    $filter = "";
    if ($yr != '') {
      $filter .= " and ig.yr='$yr'";
    }
    if ($agentid != 0) {
      $filter .= " and agent.clientid=" . $agentid;
    }
    $qry = "
    select ig.yr, agent.clientname, sum(ig.amt) as amt, p.name as project
    from salesgroupqouta as ig 
    left join projectmasterfile as p on p.line = ig.projectid
    left join client as agent on agent.clientid = ig.agentid 
    where ig.amt<>0 $filter
    group by ig.yr, agent.clientname, p.name
    order by ig.yr, agent.clientname, p.name
    ";
    return $qry;
  }


  public function reportdata($config)
  {
    $qry = $this->salesgroup_query($config);
    $str = $this->report_salesgroup_layout($config, $qry);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'directprint' => true, 'action' => 'print'];
  }

  public function report_salesgroup_layout($config, $qry)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $data =  $this->coreFunctions->opentable($qry);
    $datagroup =  $this->coreFunctions->opentable("select distinct project from (" . $qry . ") as x ");
    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 4000;


    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Person Qouta', null, null, false, $border, '', 'L', $font, 18, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year', '250', null, false, $border, 'BTRL', 'C', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '250', null, false, $border, 'BTRL', 'C', $font, $fontsize + 2, 'B', '', '');
    foreach ($datagroup as $key => $value) {
      $str .= $this->reporter->col($value->project, '250', null, false, $border, 'BTRL', 'C', $font, $fontsize + 2, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $clientname = "";
    $i = 0;
    $display = 0;
    foreach ($data as $key => $value) {
      if ($clientname != '' && $clientname != $value->clientname) {
        $str .= $this->reporter->endrow();
        $i = 0;
      }
      if ($clientname == '' || $clientname != $value->clientname) {
        $clientname = $value->clientname;
      }
      if ($i == 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->yr, 250, null, false, $border, 'TLB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($clientname, 250, null, false, $border, 'TBLR', 'L', $font, $fontsize, '', '', '');

        foreach ($datagroup as $key => $count2) {
          $display = '';
          foreach ($data as $key => $displayamt) {
            if ($displayamt->yr == $value->yr && $displayamt->clientname == $clientname && $displayamt->project == $count2->project) {
              $display = $displayamt->amt;
            }
          }

          if ($display != '') {
            $str .= $this->reporter->col(number_format($display, 2), 250, null, false, $border, 'TBR', 'R', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col('0.00', 250, null, false, $border, 'TBR', 'R', $font, $fontsize, '', '', '');
          }
        }

        $i++;
      }
    } //loop end


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
} //end class
