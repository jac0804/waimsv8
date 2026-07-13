<?php

namespace App\Http\Classes\modules\productportal;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\sbcscript\sbcscript;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;


class entrycrbrand
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CAR BRAND SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'carbrand';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['brand'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;
  private $reportheader;

  public function __construct()  //to call functions from other files
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
    $this->reportheader = new reportheader;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 5888
    );
    return $attrib;
  }

  public function createTab($config)
  {

    $columns = ['action', 'description', 'brand', 'code'];

    foreach ($columns as $key => $value) {
      $$value = $key; //declare
    }

    $stockbuttons = ['save', 'delete'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:10px;whiteSpace: normal;min-width:10px;";
    $obj[0][$this->gridname]['columns'][$description]['label'] = "";
    $obj[0][$this->gridname]['columns'][$description]['type'] = "hidden";
    $obj[0][$this->gridname]['columns'][$brand]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$code]['label'] = "";
    $obj[0][$this->gridname]['columns'][$code]['type'] = "hidden";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog']; // tab button
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }
  public function loaddata($config)
  {
    $searcfield = $this->fields;
    $filtersearch = "";
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
    $qry = "select " . $select . " from " . $this->table . " where 1 = 1 " . $filtersearch . " order by id";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function selectqry()
  {
    $qry = "id";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function add($config)
  {
    $data = [];
    $data['id'] = 0;
    $data['brand'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];

    foreach ($data as $key => $value) {
      $data2 = [];
      if (!empty($data[$key]['bgcolor'])) {
        foreach ($this->fields as $key2 => $field) {
          $value = isset($data[$key][$field]) ? $data[$key][$field] : null;
          $data2[$field] = $this->othersClass->sanitizekeyfield($field, $value);
        }

        // Validation
        if (empty(trim($data2['brand']))) {
          return ['status' => false, 'msg' => 'Saving failed. Please complete the empty brand.'];
        }

        if ($data[$key]['id'] == 0) {
          // Tracking fields from V2
          $data2['createby'] = $config['params']['user'];
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();

          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . (isset($data[$key]['brand']) ? $data[$key]['brand'] : ''));
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['id' => $data[$key]['id']]);
          $this->logger->sbcmasterlog($data[$key]['id'], $config, ' UPDATE - ' . (isset($data[$key]['brand']) ? $data[$key]['brand'] : ''));
        }
      }
    }
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved Successfully.', 'data' => $returndata];
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px; max-width:1000px;'
    );
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => 'true', 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => 'true', 'style' => 'font-size:16px;'),
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

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action' . $config['params']['actions'] . 'is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['id'] == 0 && $row['brand'] != '') {
      $qry = "select brand from carbrand where brand = '" . $row['brand'] . "' limit 1";
      $opendata = $this->coreFunctions->opentable($qry);
      $resultdata = json_decode(json_encode($opendata), true);
      if (!empty($resultdata[0]['brand'])) {
        if (trim($resultdata[0]['brand']) == trim($row['brand'])) {
          return ['status' => false, 'msg' => ' CAR BRAND ( ' . $resultdata[0]['brand'] . ' ) already exist', 'data' => [$resultdata]];
        }
      }
    }

    if (trim($row['brand']) == '') {
      return ['status' => false, 'msg' => 'CAR BRAND is empty'];
    }

    if ($row['id'] == 0) {
      // Added create tracking
      $data['createby'] = $config['params']['user'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();

      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['brand']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($row['id'] != 0 && $row['brand'] != '') {
        $qry = "select brand, id from carbrand where brand = '" . $row['brand'] . "' limit 1";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata = json_decode(json_encode($opendata), true);
        if (!empty($resultdata[0]['brand'])) {
          if (trim($resultdata[0]['brand']) == trim($row['brand'])) {
            if ($row['id'] != $resultdata[0]['id']) {
              return ['status' => false, 'msg' => ' CAR BRAND ( ' . $resultdata[0]['brand'] . ' ) already exist', 'data' => [$resultdata], 'rowid' => [$row['id'] . ' -- ' . $resultdata[0]['id']]];
            }
          }
        }
      }

      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['id' => $row['id']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['id']);
        $this->logger->sbcmasterlog($row['id'], $config, ' UPDATE - ' . $row['brand']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  }

  public function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor";
    $qry = "select " . $select . " from " . $this->table . " where id =? ";
    $data = $this->coreFunctions->opentable(
      $qry,
      [$line]
    );
    return $data;
  }

  public function delete($config) // needed restrictions
  {
    $row = $config['params']['row'];

    $qry1 = "select carid as value from item where carid = ? limit 1";
    $count = $this->coreFunctions->datareader($qry1, [$row['id']]);

    if ($count != '') {
        return ['status' => false, 'msg' => 'Car Brand already has Stock Card items attached...'];
    }

    $this->coreFunctions->LogConsole($row);
    $qry = "delete from " . $this->table . " where id = ?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['id']]);

    $this->logger->sbcdelmaster_log($row['id'], $config, 'REMOVE - ' . $row['brand']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
}
