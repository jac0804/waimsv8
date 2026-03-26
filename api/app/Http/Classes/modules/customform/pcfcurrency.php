<?php

namespace App\Http\Classes\modules\customform;

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

class pcfcurrency
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PCF CURRENCY';
  public $gridname = 'entrygrid';
  public $gridname2 = 'entrygrid2';
  public $head = 'pcfcur';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = ['oandaphpusd', 'oandausdphp', 'dateid', 'osphpusd'];
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;

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
    $attrib = array(
      'view' => 5412,
      'saveallentry' => 5412,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $columns = ['oandaphpusd', 'oandausdphp','osphpusd','dateid', 'createby', 'createdate'];

    $tab = [
      $this->gridname => ['gridcolumns' => $columns]
    ];

    foreach ($columns as $key => $value) {
        $$value = $key;
    }


    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'CONVERSION RATES';
    $obj[0][$this->gridname]['columns'][$oandausdphp]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$oandaphpusd]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$osphpusd]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width: 50px';
    $obj[0][$this->gridname]['columns'][$createby]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$createby]['style'] = 'width: 150px';
    $obj[0][$this->gridname]['columns'][$createdate]['type'] = 'label';



    return $obj;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields =['oandaphpusd','oandausdphp','osphpusd'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['refresh','reload'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'SAVE');
    data_set($col2, 'reload.action', 'refresh');


    return array('col1' => $col1,'col2'=>$col2);
  }


  public function paramsdata($config)
  {
    $user = $config['params']['user'];
    $data = $this->coreFunctions->opentable("
    select 
    '' as dateid,
    '0' as oandaphpusd,
    '0' as oandausdphp,
    '0' as osphpusd,
    '" . $user . "' as username,
    '' as bgcolor
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

  private function selectqry()
  {
    $qry = "
    line, oandaphpusd, oandausdphp, osphpusd, date(dateid) as dateid, createby,createdate";

    return $qry;
  }

  public function loaddata($config, $checkings = 0)
  {
    $select = $this->selectqry();
    $qry = "select '' as bgcolor, " . $select . " 
    from " . $this->head . "
    order by createdate desc";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data],'reloadgriddata' =>true];
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];


    switch ($action) {
      case "refresh":
        return $this->loaddata($config);
        break;
      case "load":
        $data = [];
        $head = $config['params']['dataparams'];

        $data['oandaphpusd'] = $this->othersClass->sanitizekeyfield('amt', $head['oandaphpusd']);
        $data['oandausdphp'] = $this->othersClass->sanitizekeyfield('amt', $head['oandausdphp']);
        $data['osphpusd'] = $this->othersClass->sanitizekeyfield('amt', $head['osphpusd']);

        $data['dateid'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];
        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        if($data['oandaphpusd'] !=0){
            $this->coreFunctions->sbcinsert($this->head, $data);    
        }
        
        return $this->loaddata($config);
        break;
      
    }
  }

  

  public function stockstatus($config)
  {
    $action = $config['params']["action"];

    switch ($action) {
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $action . ')'];
        break;
    }
  }
} //end class
