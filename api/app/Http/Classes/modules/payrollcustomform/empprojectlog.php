<?php

namespace App\Http\Classes\modules\payrollcustomform;

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



class empprojectlog
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Daily Deployment Record - A';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4526,
      'edit' => 4527,
      'save' => 4527,
      'saveallentry' => 4527,
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    $btns = []; //actionload - sample of adding button in header - align with form/module name
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'emplast', 'empfirst', 'empmiddle', 'tothrs'
      ]
    ]];

    $stockbuttons = ['entryempprojectlog'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'Employee List';

    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['vieweempnotimeinout', 'viewempnodeployment'];
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
    data_set($col2, 'refresh.action', 'load');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("select date_format(concat(year(curdate()),'-',month(curdate()),'-01'),'%Y-%m-%d') as dateid");
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
    // should return action
    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loaddetails($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
        break;
    }
  }

  private function loaddetails($config)
  {
    $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $dateno = date('Ymd', strtotime($config['params']['dataparams']['dateid']));
    $data = $this->coreFunctions->opentable("
    select d.dateid, d.empid, d.empfirst, d.emplast, d.empmiddle, d.dateno,  sum(log.tothrs) as tothrs
    from (
    select '" . $dateid . "' as dateid, emp.empid, emp.empfirst, emp.emplast, emp.empmiddle, $dateno as dateno
    from employee as emp
    where emp.isactive=1
    group by emp.empid, emp.empfirst, emp.emplast, emp.empmiddle) as d left join empprojdetail as log on log.empid=d.empid and date(log.dateid)=d.dateid
    group by d.dateid, d.empid, d.empfirst, d.emplast, d.empmiddle, d.dateno
    order by d.emplast,d.empfirst,d.empmiddle");
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }
} //end class
