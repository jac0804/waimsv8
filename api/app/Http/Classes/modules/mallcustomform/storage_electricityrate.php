<?php

namespace App\Http\Classes\modules\mallcustomform;

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

class storage_electricityrate
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STORAGE ELECTRICITY RATE';
  public $gridname = 'entrygrid';
  public $head = 'selectricrate';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = ['rate', 'user', 'dateid', 'categoryid'];
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
      'view' => 2209,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $amt = 0;
    $username = 1;
    $dateid = 2;
    $category = 3;

    $tab = [$this->gridname => [
      'gridcolumns' => [
        'amt', 'username', 'dateid', 'category'
      ]
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'HISTORY';

    $obj[0][$this->gridname]['columns'][$amt]['label'] = 'Rate';
    $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$username]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$amt]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$username]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$dateid]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$category]['align'] = 'text-left';

    $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align: right; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[0][$this->gridname]['columns'][$username]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[0][$this->gridname]['columns'][$category]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';


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
    $fields = ['amt', 'ratecategory'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'amt.readonly', false);
    data_set($col1, 'amt.label', 'Rate');
    data_set($col1, 'ratecategory.lookupclass', 'lookup_ratecategory_electricity');

    $fields = ['create', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'create.label', 'SAVE');
    data_set($col2, 'refresh.action', 'load');
    data_set($col2, 'refresh.label', 'LOAD HISTORY');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $user = $config['params']['user'];
    $data = $this->coreFunctions->opentable("
      select 
      '' as dateid,
      '0' as amt,
      '' as ratecategory,
      '' as categoryid,
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
    $qry = "elec.line, elec.amt, elec.dateid as dateid, elec.username, 
    elec.categoryid, cat.category as category";

    return $qry;
  }

  public function loaddata($config, $checkings = 0)
  {
    $select = $this->selectqry();
    $qry = "select '' as bgcolor, " . $select . " 
    from " . $this->head . " as elec
    left join ratecategory as cat on cat.line = elec.categoryid
    where cat.iselec = 1
    order by elec.line";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];

    switch ($action) {
      case "load":
        return $this->loaddata($config);
        break;
      case 'create':
        $data = [];
        $head = $config['params']['dataparams'];

        $data['amt'] = $this->othersClass->sanitizekeyfield('amt', $head['amt']);
        $data['username'] = $this->othersClass->sanitizekeyfield('username', $head['username']);

        $data['dateid'] = $this->othersClass->getCurrentTimeStamp();
        $data['categoryid'] = $head['categoryid'];
        $this->coreFunctions->sbcinsert($this->head, $data);
        return $this->loaddata($config);
        break;
      case 'print':
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
