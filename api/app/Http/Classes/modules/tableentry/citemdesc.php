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

class citemdesc
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CUSTOMER ITEM DESCRIPTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'itemothname';
  private $othersClass;
  public $style = 'width:70%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['itemid', 'itemdesc'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct($params = null)
  {
    $this->fieldClass = new txtfieldClass($params);
    $this->tabClass = new tabClass($params);;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
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
    $columns = ['action', 'itemdesc'];
    foreach ($columns as $key => $value) {
      $$value = $key;
    }
    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Description';
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['itemid'] = $config['params']['tableid'];
    $data['itemdesc'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];

    $dateTables = ['itemothname'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
        }
        $data2['itemid'] = $tableid;
        if ($data[$key]['line'] == 0) {
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];
          $status = $this->coreFunctions->insertGetId($this->table, $data2);
          $config['params']['doc'] = strtoupper("customeritemdesc_tab");
          $this->logger->sbcmasterlog($tableid, $config, ' CREATE - ' . ' LINE: ' . $status . ', ITEMDESC: ' . $data2['itemdesc']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['itemid' => $data2['itemid'], 'line' => $data[$key]['line']]);
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
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];

    $dateTables = ['itemothname'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
    }

    $data['itemid'] = $tableid;
    if ($row['line'] == 0) {

      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $config['params']['doc'] = strtoupper("customeritemdesc_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $config,
          ' CREATE - ' . ' LINE: ' . $line . ', ITEMDESC: ' . $data['itemdesc']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['itemid' => $row['itemid'], 'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];
    $config['params']['doc'] = 'customeritemdesc_tab';
    $exist = $this->coreFunctions->datareader("
                select stock.rem as value from lastock as stock where rem=?
                union all
                select stock.rem as value from glstock as stock where rem=? 
                union all
                select stock.rem as value from sostock as stock where rem=?
                union all
                select stock.rem as value from hsostock  as stock where rem=? limit 1", [$row['itemdesc'], $row['itemdesc'], $row['itemdesc'], $row['itemdesc']]);
    if ($exist) {
      return ['status' => false, 'msg' => 'Cannot delete, already used in transaction'];
    }

    $qry = "delete from " . $this->table . " where  itemid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['itemid'], $row['line']]);
    $this->logger->sbcmasterlog($tableid, $config, ' REMOVED : ' . $row['line'] . ''   . ' , ITEMDESC: ' . $row['itemdesc']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $select = ", nitem.line as line, nitem.itemid as itemid, nitem.itemdesc";
    return $select;
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = "select '' as bgcolor " . $select . "
    from itemothname as nitem  where nitem.itemid = ? and nitem.line = ?";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = "select '' as bgcolor" . $select . "
    from itemothname as nitem where nitem.itemid = ?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }



  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];
    $doc = strtoupper("CUSTOMERITEMDESC_TAB");
    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
