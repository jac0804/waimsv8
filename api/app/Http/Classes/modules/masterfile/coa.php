<?php

namespace App\Http\Classes\modules\masterfile;

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

class coa
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CHART OF ACCOUNTS';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $head = 'coa';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';

  private $fields = ['acnoid', 'acno', 'acnoname', 'alias', 'levelid', 'parent', 'cat', 'detail'];
  private $except = [];
  private $blnfields = [];
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 3,
      'edit' => 4,
      'new' => 5,
      'save' => 6,
      'delete' => 7,
      'print' => 8,
      'load' => 2
    );
    return $attrib;
  }


  public function loaddata($config)
  {
    $qry = "select acnoid,acno,acnoname,parent,parentname,levelid,parentid,detail,
    alias,cat,sum(transcount) as transcount from
    (select mcoa.acnoid,mcoa.acno,mcoa.acnoname,mcoa.parent,
    ifnull(pcoa.acnoname,'') as parentname,
    mcoa.levelid,ifnull(pcoa.acnoid,0) as parentid,mcoa.detail,
    mcoa.alias,mcoa.cat,ifnull((select trno from gldetail where gldetail.acnoid=mcoa.acnoid limit 1),0) as transcount 
    from coa as mcoa
    left join coa as pcoa on pcoa.acno = mcoa.parent
    union all
    select mcoa.acnoid,mcoa.acno,mcoa.acnoname,mcoa.parent,
    ifnull(pcoa.acnoname,'') as parentname,
    mcoa.levelid,ifnull(pcoa.acnoid,0) as parentid,mcoa.detail,
    mcoa.alias,mcoa.cat,ifnull((select trno from ladetail where ladetail.acnoid=mcoa.acnoid limit 1),0) as transcount 
    from coa as mcoa
    left join coa as pcoa on pcoa.acno = mcoa.parent) as A
    group by acnoid,acno,acnoname,parent,parentname,levelid,parentid,detail,
    alias,cat
    order by acno,levelid";

    return $this->coreFunctions->opentable($qry);
  } // end function


  public function status($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'addparent':
        return $this->addparent($config);
        break;
      case 'addchild':
        return $this->addchild($config);
        break;
      case 'edit':
        break;
      case 'delete':
        return $this->delete($config);
        break;
      case 'savedata':
        return $this->savedata($config);
        break;
    }
  } //end function

  public function addparent($config)
  {
    $query = "select acno,acnoname,acnoid,cat,levelid,parent as pcode from coa where levelid=1 order by acnoid desc limit 1";
    $data = $this->coreFunctions->opentable($query);

    if (empty($data)) {
      $pcode = '\\';
      $newacno = 1;
      $levelid = 1;
    } else {
      $pcode = $data[0]->pcode;

      $newacno = $data[0]->acno;
      $newacno = str_replace('\\', '', $newacno);
      $newacno += 1;
      $newacno = '\\' . $newacno;

      $levelid = $data[0]->levelid;
    } //end if data  
    return ['acnoid' => '', 'acno' => $newacno, 'acnoname' => '', 'alias' => '', 'cat' => '', 'parent' => $pcode, 'levelid' => $levelid, 'detail' => 1];
  } //end function

  public function addchild($config)
  {
    $acno = $config['params']['data']['acno'];
    $query = "select head.acno,head.cat,parentinfo.acnoname as pname,
        head.parent as pcode,head.levelid from coa as head
        left join coa as parentinfo on parentinfo.acno = head.parent
        where head.parent = '\\\\" . $acno . "' order by head.acnoid desc limit 1";

    $data = $this->coreFunctions->opentable($query);

    if ($data == null) {
      $query = "select acno,acnoname,acnoid,cat,levelid from coa where acno = '\\" . $acno . "'";
      $data = $this->coreFunctions->opentable($query);
      $cat = $data[0]->cat;
      $pname = $data[0]->acnoname;
      $pcode = $data[0]->acno;
      $newacno = $acno . "01";
      $newacno = str_replace('\\', '', $newacno);
      $newacno = '\\' . $newacno;
      $levelid = $data[0]->levelid + 1;
    } else {
      $cat = $data[0]->cat;
      $pname = $data[0]->pname;
      $pcode = $data[0]->pcode;
      $newacno = $data[0]->acno;
      $newacno = str_replace('\\', '', $newacno);
      $newacno += 1;
      $newacno = '\\' . $newacno;
      $levelid = $data[0]->levelid;
    } //END IF 
    return ['acnoid' => '', 'acno' => $newacno, 'acnoname' => '', 'alias' => '', 'cat' => $cat, 'parent' => $pcode, 'levelid' => $levelid, 'detail' => 1];
  }

  public function delete($config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoid = $config['params']['data']['acnoid'];
    $qry = "select pcoa.detail,ifnull(pcoa.acnoid,'') as parentid,pcoa.acno as parent from coa as mcoa
    left join coa as pcoa on pcoa.acno = mcoa.parent
    where mcoa.acno = '\\" . $acno . "'";

    $data = $this->coreFunctions->opentable($qry);

    if (!empty($data)) {
      $parent_ = $data[0]->parent;
      $parent = $data[0]->parentid;
      $isdetail = $data[0]->detail;
    } else {
      $parent_ = '';
      $parent = 0;
      $isdetail = 0;
    } //end if
    if (!$this->getAccountChildren($acno)) { //IF PARENT DOES NOT HAVE CHILDREN
      if ($config['params']['data']['transcount'] != 0) { //with transaction
        $msg = 'Removing failed, Account has transaction';
        $status = false;
      } else {
        $query = "delete from coa where acnoid = " . $acnoid . "";
        $numrows = $this->coreFunctions->execqry($query, 'delete');

        if ($numrows != 0) {
          if ($parent_ != '') {
            if (!$this->getAccountChildren($parent_)) {
              $updateqry = "update coa set detail = 1 where acnoid = " . $parent;
              $statusupdate = $this->coreFunctions->execqry($updateqry, 'update');
            }
          }
          $status = true;
          $msg = 'Account successfully removed';
        } else {
          $msg = 'Removing failed, Please try again';
          $status = false;
        } //END IF ERROR DATABASE
      }
    } else {
      $status = false;
      $msg = 'Cannot delete a parent account with child accounts. Please Try again.';
    } //END IF PARENT CHECKING HAS CHILDREN
    $data = $this->loaddata($config);
    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  }

  private function getAccountChildren($acno)
  {
    $query = "select acnoid,acno,acnoname,alias,parent,cat,detail from coa 
               where parent = '\\" . $acno . "' order by acnoid";
    $data = $this->coreFunctions->opentable($query);

    if (empty($data)) {
      return false;
    } else {
      return true;
    } //END IF
  } //END GET CHILDREN

  public function savedata($config)
  {
    $head = $config['params']['data'];
    $data = [];

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc']);
        } //end if          
      }
    }
    $data['acnoid'] = $this->othersClass->val($data['acnoid']);
    if ($data['acnoid'] == 0) {
      unset($data['acnoid']);
      $acnoid = $this->coreFunctions->insertGetId('coa', $data);
      $data['acnoid'] = $acnoid;
      $this->logger->sbcwritelog($acnoid, $config, 'CREATE', $acnoid . ' - ' . $head['acno'] . ' - ' . $head['acnoname']);
    } else {
      $this->coreFunctions->sbcupdate('coa', $data, ['acnoid' => $head['acnoid']]);
      $acnoid = $head['acnoid'];
    }

    if ($this->getAccountChildren($head['parent'])) {
      $updateqry = "update coa set detail = 0 where acno = '\\" . $head['parent'] . "'";
    } else {
      $updateqry = "update coa set detail = 1 where acno = '\\" . $head['parent'] . "'";
    }
    $statusupdate = $this->coreFunctions->execqry($updateqry, 'update');
    if ($this->getAccountChildren($head['acno'])) {
      $updateqry = "update coa set detail = 0 where acno = '\\" . $head['acno'] . "'";
    } else {
      $updateqry = "update coa set detail = 1 where acno = '\\" . $head['acno'] . "'";
    }
    $statusupdate = $this->coreFunctions->execqry($updateqry, 'update');
    $data = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data, 'acnoid' => $acnoid];
  } //end function





















} //end class
