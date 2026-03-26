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

class entrysqcomment
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COMMENTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'sqcomments';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $fields = ['trno', 'userid', 'createdate', 'comment'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


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
      'load' => 2469,
      'save' => 2472
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'comment', 'username', 'createdate', 'itemname']
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:90px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:550px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:180px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['type'] = 'textarea';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $trno = $config['params']['tableid'];
    $userid = $config['params']['adminid'];
    if ($userid == 0) {
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username =?", [$config['params']['user']]);
    }

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['comment'] = '';
    $data['userid'] = $userid;
    $data['username'] = $this->getclientname($userid);
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "hi.line,hi.trno,hi.userid,hi.createdate,hi.comment ";
    return $qry;
  }

  public function saveallentry($config)
  {
    return [];
  } // end function

  public function save($config)
  {
    $userid = $config['params']['adminid'];
    if ($userid == 0) {
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username =?", [$config['params']['user']]);
    }
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line, $config);
        $config['params']['doc'] = strtoupper("entrysqcomment");
        $this->logger->sbcwritelog(
          $data['trno'],
          $config,
          'CREATE',
          ' COMMENTS: ' . $data['comment']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $returnrow = $this->loaddataperrecord($row['line'], $config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $userid = $config['params']['adminid'];
    if ($userid == 0) {
      $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username =?", [$config['params']['user']]);
    }

    if ($row['userid'] == $userid) {
      $qry = "delete from " . $this->table . " where trno =? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
      $config['params']['doc'] = strtoupper("entrysqcomment");
      $this->logger->sbcwritelog(
        $row['trno'],
        $config,
        'REMOVE',
        ' COMMENTS: ' . $row['comment']
      );
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    } else {
      return ['status' => false, 'msg' => 'Comments is not yours'];
    }
  }


  private function loaddataperrecord($line, $config)
  {
    $userid = $config['params']['adminid'];
    $trno = $config['params']['tableid'];

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ", ifnull(c.clientname,(select u.username from useraccess as u where u.userid = hi.userid)) as username
    from " . $this->table . " as hi 
    left join client as c on c.clientid = hi.userid
    where hi.trno = ? and hi.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $userid = $config['params']['adminid'];
    $trno = $config['params']['tableid'];

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ", ifnull(c.clientname,(select u.username from useraccess as u where u.userid = hi.userid)) as username
    from " . $this->table . "  as hi
    left join client as c on c.clientid = hi.userid
    where hi.trno = ?
    order by hi.line";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = strtoupper($config['params']['lookupclass']);
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
    where log.doc = '" . $doc . "' and log.trno =" . $trno . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno =" . $trno;

    $qry = $qry . " order by dateid desc";
    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function getclientname($clientid)
  {
    $qry = "select clientname as value from client where clientid = ? limit 1";
    return $this->coreFunctions->datareader($qry, [$clientid]);
  }
} //end class
