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

class entrycustomersa
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SA #';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'clientsano';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['clientid', 'sano', 'issa'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
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
    $action = 0;
    $sano = 1;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'sano', 'itemname']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$sano]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
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
    $data['clientid'] = $config['params']['tableid'];
    $data['sano'] = '';
    $data['issa'] = 1;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2], '', $companyid);
        }
        $data2['clientid'] = $tableid;
        if ($data[$key]['line'] == 0) {
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $config['params']['doc'] = strtoupper("customersa_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $config,
            ' CREATE - '
              . 'LINE: ' . $status
              . ', SA #: ' . $data2['sano']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
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
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $companyid);
    }

    $data['clientid'] = $tableid;
    if ($row['line'] == 0) {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $config['params']['doc'] = strtoupper("customersa_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $config,
          ' CREATE - '
            . 'LINE: ' . $line
            . ',SA #: ' . $data['sano']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
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
    $data = $this->loaddataperrecord($config, $row['line']);

    $qry = "select sano as value from lahead where sano=?
            union all 
            select sano as value from glhead where sano=?
            union all 
            select sano as value from prhead where sano=?
            union all 
            select sano as value from hprhead where sano=?
    ";
    $count = $this->coreFunctions->datareader($qry, [$row['line'], $row['line'], $row['line'], $row['line']]);

    if ($count != '') {
      return ['clientid' => $row['line'], 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $config['params']['doc'] = strtoupper("customersa_tab");
    $this->logger->sbcdelmaster_log(
      $tableid,
      $config,
      ' REMOVE - '
        . 'LINE: ' . $row['line']
        . ', SA #: ' . $row['sano']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function selectqry()
  {
    $select = "sa.line, sa.clientid, sa.sano,sa.issa";
    return $select;
  }

  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();

    $qry = "select  " . $select . ", '' as bgcolor
    from clientsano as sa
    where sa.clientid = ? and sa.line = ? and issa = 1";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();

    $qry = "select " . $select . ", '' as bgcolor 
    from clientsano as sa
    where sa.clientid = ? and issa = 1";
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
    $doc = strtoupper("customersa_tab");

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
