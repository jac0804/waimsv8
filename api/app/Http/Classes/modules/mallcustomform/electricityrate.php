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

class electricityrate
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ELECTRICITY RATE';
  public $gridname = 'entrygrid';
  public $gridname2 = 'entrygrid2';
  public $head = 'electricrate';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $fields = ['amt', 'username', 'dateid', 'categoryid'];
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
      'view' => 262,
      'saveallentry' => 4168,
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $amt = 0;
    $username = 1;
    $dateid = 2;
    $category = 3;

    $ratecategory = 0;
    $rateamt = 1;


    $tab = [
      $this->gridname => ['gridcolumns' => ['category', 'amt']],
      $this->gridname2 => ['gridcolumns' => ['amt', 'category', 'username', 'dateid']]
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'RATE CATEGORY';

    $obj[0][$this->gridname]['columns'][$rateamt]['label'] = 'Rate';

    $obj[0][$this->gridname]['columns'][$rateamt]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$rateamt]['style'] = 'text-align: right; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';

    $obj[0][$this->gridname]['columns'][$ratecategory]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ratecategory]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$ratecategory]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';



    $obj[1][$this->gridname2]['descriptionrow'] = [];
    $obj[1][$this->gridname2]['label'] = 'HISTORY';

    $obj[1][$this->gridname2]['columns'][$amt]['label'] = 'Rate';
    $obj[1][$this->gridname2]['columns'][$amt]['type'] = 'label';

    $obj[1][$this->gridname2]['columns'][$dateid]['type'] = 'label';
    $obj[1][$this->gridname2]['columns'][$username]['type'] = 'label';
    $obj[1][$this->gridname2]['columns'][$category]['type'] = 'label';

    $obj[1][$this->gridname2]['columns'][$amt]['align'] = 'text-left';
    $obj[1][$this->gridname2]['columns'][$username]['align'] = 'text-left';
    $obj[1][$this->gridname2]['columns'][$dateid]['align'] = 'text-left';
    $obj[1][$this->gridname2]['columns'][$category]['align'] = 'text-left';

    $obj[1][$this->gridname2]['columns'][$amt]['style'] = 'text-align: right; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[1][$this->gridname2]['columns'][$username]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[1][$this->gridname2]['columns'][$dateid]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';
    $obj[1][$this->gridname2]['columns'][$category]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width: 100px;max-width: 100px;';



    return $obj;
  }

  public function createHeadbutton($config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = [['refresh', 'loadhistory']];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'refresh.action', 'load');
    data_set($col1, 'refresh.label', 'LOAD RATE');


    return array('col1' => $col1);
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

    $qry = "elec.line, FORMAT(ifnull(elec.amt,0),2) as amt, date(elec.dateid) as dateid, elec.username as username,
    cat.line as categoryid, cat.category as category";

    return $qry;
  }

  public function loadratecategorydata($config, $checkings = 0)
  {

    $user = $config['params']['user'];
    $dateid = $this->othersClass->getCurrentTimeStamp();

    $qry = "select '' as bgcolor, 0 as amt, cat.line as categoryid, cat.category as category, '" . $user . "' as username, '" . $dateid . "' as dateid 
    from ratecategory as cat
    where cat.iselec = 1
    order by cat.line";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  public function loadhistorydata($config, $checkings = 0)
  {
    $select = $this->selectqry();


    $qry = "select '' as bgcolor, " . $select . " 
    from ratecategory as cat
    left join electricrate as elec on elec.categoryid = cat.line
    where cat.iselec = 1
    order by elec.line";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid2' => $data]];
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];


    switch ($action) {
      case "load":
        return $this->loadratecategorydata($config);
        break;
      case "history":
        return $this->loadhistorydata($config);
        break;
      case 'create':
        $data = [];
        $head = $config['params']['dataparams'];

        $data['amt'] = $this->othersClass->sanitizekeyfield('amt', $head['amt']);
        $data['username'] = $this->othersClass->sanitizekeyfield('username', $head['username']);

        $data['dateid'] = $this->othersClass->getCurrentTimeStamp();
        $data['categoryid'] = $head['categoryid'];
        $this->coreFunctions->sbcinsert($this->head, $data);
        return $this->loadratecategorydata($config);
        break;
      case 'print':
        break;
      case 'saveallentry':

        $this->saveallentry($config);
        return $this->loadratecategorydata($config);
        break;
    }
  }

  public function saveallentry($config)
  {

    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $this->coreFunctions->sbcinsert('electricrate', $data2);
      } // end if
    } // foreach

    $returndata = $this->loadratecategorydata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

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
