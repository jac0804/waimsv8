<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewappuseraccount
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'USER ACCOUNT';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createTab($config)
  {
    $clientid = $config['params']['clientid'];
    $customername = $this->coreFunctions->datareader("select concat(empfirst, ' ', empmiddle, ' ', emplast) as value from app where empid=? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;

    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['userlevel', 'username', 'password'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'username.label', 'Username');
    data_set($col1, 'username.type', 'input');
    data_set($col1, 'username.readonly', false);
    data_set($col1, 'username.class', 'csusername');
    data_set($col1, 'password.type', 'password');

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.label', 'UPDATE');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $adminid = $config['params']['adminid'];
    $clientid = $config['params']['clientid'];

    $administrator = $this->othersClass->checkAccess($config['params']['user'], 2580);

    if ($administrator) {
      return $this->coreFunctions->opentable("select app.empid, ifnull(users.username,'') as userlevel, app.username, app.password from app left join users on users.idno=app.userid where app.empid=" . $clientid);
    } else {
      return $this->coreFunctions->opentable("select 0 as userid, '' as userlevel, '' as username, '' as password");
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $adminid = $config['params']['adminid'];
    $clientid = $config['params']['clientid'];

    $administrator = $this->othersClass->checkAccess($config['params']['user'], 2580);

    if ($administrator) {
      if (!$this->othersClass->validatePassword($config['params']['dataparams']['password'])) {
        return ['status' => false, 'msg' => 'The password entered is too weak. For maximum security, your password should contain minimum of 8 characters, mixed case letters, numbers and special characters (!@#$%^&*)', 'data' => []];
      }

      $dlock = $this->othersClass->getCurrentTimeStamp();
      $data = [
        'username' => $config['params']['dataparams']['username'],
        'password' => $config['params']['dataparams']['password'],
        'userid' => $config['params']['dataparams']['userid']
      ];
      $old_email = $this->coreFunctions->getfieldvalue("app", "username", "empid=?", [$clientid]);
      $this->coreFunctions->sbcupdate("app", $data, ['empid' => $clientid]);
      return ['status' => true, 'msg' => 'Successfully updated.', 'data' => []];
    } else {
      return ['status' => false, 'msg' => 'Unable to update. Only users with access of Manage Masterfile User Accounts and selected employee are allowed to do an update', 'data' => []];
    }
  }
}
