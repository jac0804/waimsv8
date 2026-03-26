<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;
use Symfony\Component\VarDumper\VarDumper;

class entryaddapprovercat
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CATEGORY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'approverrcat';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['appid', 'catid'];
  public $showclosebtn = true;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $logger;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'category']]];

    $stockbuttons = ['delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width: 450px;whiteSpace: normal;min-width:10px;max-width:450px;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addreqcat', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ADD CATEGORY';
    return $obj;
  }

  private function selectqry($config)
  {
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['rows'][0]['appid'];
    $qry = "select cat.line," . $line . " as appid, catid, req.category from approverrcat as cat 
            left join reqcategory as req on req.line=cat.catid";
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data2['createby'] = $config['params']['user'];
        if ($data[$key]['line'] == 0) {
          $this->coreFunctions->sbcinsert($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['stage'], $config, ' CREATE - ' . $data[$key]['substage'], 0, 1);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];

    $this->coreFunctions->sbcinsert($this->table, $data);

    $returnrow = $this->loaddataperrecord($config, $data['appid'], $data['catid']);
    $category = $row['category'];

    $this->coreFunctions->sbcupdate('approverdetails', ['iscat' => 1], ['line' => $data['appid']]);

    $this->logger->sbcmasterlog($category, $config, ' CREATE - ' . $category, 0, 1);
    return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where appid=? and catid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['appid'], $row['catid']]);

    $count = $this->coreFunctions->opentable("select appid from " . $this->table . " where appid=?", [$row['appid']]);
    if (empty($count)) {
      $this->coreFunctions->sbcupdate('approverdetails', ['iscat' => 0], ['line' => $row['appid']]);
    }

    // $this->logger->sbcdelmaster_log($row['category'], $config, 'REMOVE - ' . $row['category'], 1);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $appid, $catid)
  {
    $select = $this->selectqry($config);
    $qry = $select . " where appid=? and catid=?";
    $data = $this->coreFunctions->opentable($qry, [$appid, $catid]);
    return $data;
  }

  public function loaddata($config)
  {
    $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $qry = $this->selectqry($config) . ' where appid=?';
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'addreqcat':
        return $this->addreqcat($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $data = [];
    $returndata = [];

    foreach ($row  as $key2 => $value) {
      $config['params']['row']['appid'] = $row[$key2]['appid'];
      $config['params']['row']['catid'] = $row[$key2]['line'];
      $config['params']['row']['category'] = $row[$key2]['category'];
      $return = $this->save($config);
      if ($return['status']) {
        array_push($returndata, $return['row'][0]);
      }
    }
    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata];
  } // end function

  public function addreqcat($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Request Categories',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'multientry',
      'action' => 'addtogrid'
    );

    // lookup columns
    $cols = array(
      array('name' => 'category', 'label' => 'Category', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;'),
    );


    $appid = $config['params']['sourcerow']['line'];
    $qry = "select line as keyid,line, category, " . $appid . " as appid from reqcategory where iscat=1 order by category";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    // $trno = $config['params']['tableid'];
    $trno = strtoupper($config['params']['sourcerow']['line']);

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and trno = $trno ";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
