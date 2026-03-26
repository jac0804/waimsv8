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

class viewuseraccount
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'USER ACCOUNT';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:500px;max-width:500px;';
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
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
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
    $doc = $config['params']['doc'];
    $fields = ['userlevel', 'email', 'password'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'email.label', 'Email/Username');
    switch ($config['params']['companyid']) {
      case 14: //majesty
      case 58: //cdo
        break;
      default:
        data_set($col1, 'password.type', 'password');
        break;
    }

    // if ($doc == 'MYINFO') {
    //   data_set($col1, 'userlevel.type', 'input');
    //   data_set($col1, 'userlevel.readonly', true);
    //   data_set($col1, 'email.readonly', true);
    //   data_set($col1, 'password.readonly', true);
    // }

    // $fields = [];
    // if ($doc != 'MYINFO') {
    $fields = ['refresh'];
    // }
    $col2 = $this->fieldClass->create($fields);
    // if ($doc != 'MYINFO') {
    data_set($col2, 'refresh.label', 'UPDATE');
    // }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $adminid = $config['params']['adminid'];
    $clientid = $config['params']['clientid'];

    $administrator = $this->othersClass->checkAccess($config['params']['user'], 2580);

    if ($adminid == $clientid || $administrator) {
      return $this->coreFunctions->opentable("select client.userid, ifnull(users.username,'') as userlevel, client.email, client.password, client.category 
      from client left join users on users.idno=client.userid where client.clientid=" . $clientid);
    } else {
      return $this->coreFunctions->opentable("select 0 as userid, '' as userlevel, '' as email, '' as password, 0 as category");
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

    switch ($config['params']['companyid']) {
      case 6: //mitsukoshi
        if ($config['params']['doc'] == 'CUSTOMER') {
          $category = $this->coreFunctions->datareader("select cat.cat_name as value from client left join category_masterfile as cat on cat.cat_id=client.category where client.clientid=? and cat.cat_name='BRANCH'", [$clientid]);
          if ($category == '') {
            return ['status' => false, 'msg' => 'User Account is for all BRANCH category only', 'data' => []];
          }
        }
        break;
    }

    if (($adminid != 0 && $adminid == $clientid) || $administrator) {


      switch ($config['params']['companyid']) {
        case 14: //majesty
        case 58: //cdo
          break;
        default:
          if (!$this->othersClass->validatePassword($config['params']['dataparams']['password'])) {
            return ['status' => false, 'msg' => 'The password entered is too weak. For maximum security, your password should contain minimum of 8 characters, mixed case letters, numbers and special characters (!@#$%^&*)', 'data' => []];
          }
          break;
      }

      $dlock = $this->othersClass->getCurrentTimeStamp();
      $data = [
        'email' => $config['params']['dataparams']['email'],
        'password' => $config['params']['dataparams']['password'],
        'userid' => $config['params']['dataparams']['userid'],
        'dlock' => $dlock
      ];
      $old_email = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$clientid]);
      $this->coreFunctions->sbcupdate("client", $data, ['clientid' => $clientid]);
      if ($config['params']['companyid'] == 16) { //ati
        if ($old_email != '') {
          $this->coreFunctions->sbcupdate("approverdetails", ['approver' => $config['params']['dataparams']['email']], ['approver' => $old_email]);
        }
      }
      return ['status' => true, 'msg' => 'Successfully updated.', 'data' => []];
    } else {
      return ['status' => false, 'msg' => 'Unable to update. Only users with access of Manage Masterfile User Accounts and selected employee are allowed to do an update', 'data' => []];
    }
  }
}
