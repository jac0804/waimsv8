<?php

namespace App\Http\Classes\modules\masterfile;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Illuminate\Support\Facades\Storage;


use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class useraccess
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'USER ACCESS';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $head = 'users';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';

  private $fields = ['idno', 'username'];
  private $except = [];
  private $blnfields = [];
  private $reporter;
  private $attribLength = 10000;


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
      'load' => 362
    );
    return $attrib;
  }


  public function loaddata($config)
  {
    $levels = $this->coreFunctions->opentable("select md5(idno) as idno,username,attributes,class,ifnull((select count(username) from useraccess where useraccess.accessid=users.idno),0) as usercrt from users order by username");
    return $levels;
  } // end function



  public function status($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'savenewlevel':
        return $this->savenewlevel($config);
        break;
      case 'getuseraccess':
        return $this->getuseraccess($config);
        break;
      case 'saveuseraccess':
        return $this->saveuseraccess($config);
        break;
      case 'deleteuseraccess':
        return $this->deleteuseraccess($config);
        break;
      case 'getattribute':
        return $this->getattribute($config);
        break;
      case 'getattributedetail':
        return $this->getattributedetail($config);
        break;
      case 'getattributedetailaccess':
        return $this->getattributedetailaccess($config);
        break;
      case 'updatemoduleaccess':
        return $this->updatemoduleaccess($config);
        break;
      case 'deletelevel':
        return $this->deletelevel($config);
        break;
      case 'editlevel':
        return $this->editlevel($config);
        break;
      case 'duplicatelevel':
        return $this->duplicatelevel($config);
        break;
    }
  } //end function

  private function getuseraccess($config)
  {
    $idno = $config['params']['idno'];
    $users = $this->coreFunctions->opentable("select userid, accessid, username, password, name, pwd, pic, createdate, createby, editdate, editby, viewdate, viewby, position, administrator_pass, administrator_enc, isinactive, endtime, starttime, istime, supplier, picture, project, pincode, pincode2 from useraccess where md5(accessid)=?", [$idno]);
    if (count($users) > 0) {

      $attributes = $this->coreFunctions->getfieldvalue('users', 'attributes', 'md5(idno)=?', [$idno]);
      if ($this->attribLength > strlen($attributes)) {
        $this->coreFunctions->execqry("update users as u set u.attributes=RPAD(u.attributes," . $this->attribLength . ",'0') where md5(idno) = '$idno'");
      }

      foreach ($users as $u) {
        if ($u->picture != '') {
          $pic = str_replace('/images', '', $u->picture);
          if (Storage::disk('public')->exists($pic)) {
            $u->picture = env('APP_PUBLIC') . $u->picture;
          } else {
            $u->picture = '';
          }
        } else {
          $u->picture = '';
        }

        $u->isinactive = $u->isinactive == 1 ? true : false;
        $u->istime = $u->istime == 1 ? true : false;
      }
    }
    $attr = $this->getattribute($config);
    return ['status' => true, 'msg' => 'Loading data...', 'data' => $users, 'attr' => $attr];
  } // end function

  private function savenewlevel($config)
  {
    $level = $config['params']['level'];
    $level = $this->othersClass->sanitizekeyfield('username', $level);
    $data['username'] = $level;
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $config['params']['user'];
    $data['attributes'] = '';

    $idno = $this->coreFunctions->insertGetId('users', $data);
    $qry = "update users set attributes = LPAD(attributes," . $this->attribLength . ",0) where idno=" . $idno;
    $this->coreFunctions->execqry($qry);
    $level = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $level];
  } // end function


  private function saveuseraccess($config)
  {
    $data = $config['params']['data'];
    $img = $config['params']['img'];
    $msg = 'Successfully saved.';
    $data['password'] = md5($data['pwd']);
    $data['administrator_enc'] = md5($data['administrator_pass']);
    if ($data['userid'] == 0) {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $idno2 = $this->coreFunctions->getfieldvalue('users', 'idno', 'md5(idno)=?', [$config['params']['idno']]);
      $data['accessid'] = $idno2;
      $exist = $this->coreFunctions->getfieldvalue('useraccess', 'userid', 'username=?', [$data['username']]);

      if ($exist == '') {
        $idno = $this->coreFunctions->insertGetId('useraccess', $data);
        if($config['params']['companyid']==55){//afli
          $this->coreFunctions->execqry("insert into profile(doc,psection,pvalue,puser)values('theme','default','#35548c,#156aab,#223f73','".$data['username']."')");
          $this->coreFunctions->execqry("insert into user_themer(userid, themecode) values($idno,'DEFAULT')");
        }
      } else {
        return ['status' => false, 'msg' => 'Username already exist', 'data' => []];
      }

      if ($idno == 0) {
        $msg = 'Failed to Add User...';
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $suff = 0;
      $filename = '';
      $pic = $this->coreFunctions->opentable("select picture from useraccess where userid=?", [$data['userid']]);
      if ($pic[0]->picture != '') {
        $filename = $pic[0]->picture;
        $pic = explode('/', $pic[0]->picture);
        $pic = end($pic);
        $pic = explode('.', $pic);
        $pic = $pic[0];
        $pic = explode('-', $pic);
        $suff = end($pic);
      }

      // FTP sample
      //$fileContents = Storage::disk('ftp')->get('280-3.jpeg');
      //Storage::disk('ftp')->put('xxxx.jpeg', $fileContents);
      //Storage::disk('ftp')->copy('xxxx.jpeg', 'download/xx.jpeg');
      //Storage::disk('ftp')->copy('download/xx.jpeg', 'dl2/xx.jpeg');
      //$files = Storage::disk('ftp')->files('/');


      if ($img['imgChanged']) {
        preg_match("/data:image\/(.*?);/", $img['userpic'], $img['userpicext']); // extract the image extension
        $image = preg_replace('/data:image\/(.*?);base64,/', '', $img['userpic']); // remove the type part
        $image = str_replace(' ', '+', $image);
        $filename = $data['userid'] . '-' . $suff . '.' . $img['userpicext'][1];
        if (Storage::disk('public')->exists('users/' . $filename)) {
          Storage::disk('public')->delete('users/' . $filename);
        }
        $filename = $data['userid'] . '-' . ($suff + 1) . '.' . $img['userpicext'][1];
        Storage::disk('public')->put('users/' . $filename, base64_decode($image));
        $data['picture'] = '/images/users/' . $filename;
      } else {
        $data['picture'] = $filename;
      }
      if ($this->coreFunctions->sbcupdate('useraccess', $data, ['userid' => $data['userid']]) == 0) {
        $msg = 'Failed to update User...';
      }
    }
    $idno = $config['params']['idno'];
    $users = $this->coreFunctions->opentable("select * from useraccess where md5(accessid)=?", [$idno]);
    if (count($users) > 0) {
      foreach ($users as $u) {
        if ($u->picture != '') {
          $pic = str_replace('/images', '', $u->picture);
          if (Storage::disk('public')->exists($pic)) {
            $u->picture = env('APP_PUBLIC') . $u->picture;
          } else {
            $u->picture = '';
          }
        }
      }
    }
    return ['status' => true, 'msg' => $msg, 'data' => $users];
  } //end function


  private function deleteuseraccess($config)
  {
    $userid = $config['params']['userid'];
    $qry = "delete from useraccess where userid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$userid]);
    $idno = $config['params']['idno'];
    $users = $this->coreFunctions->opentable("select * from useraccess where md5(accessid)=?", [$idno]);
    return ['status' => true, 'msg' => 'Delete user Successfully', 'data' => $users];
  } // end function


  private function getattribute($config)
  {
    if ($config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
      $levelid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$config['params']['user']]);
    } else {
      $levelid = $this->coreFunctions->getfieldvalue("client", "userid", "email=?", [$config['params']['user']]);
    }

    $qry = "select description as label,md5(code) as value from attributes where parent='\\\\' and levelid=" . $levelid . " order by code";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  private function getattributedetail($config)
  {
    $code = $config['params']['selectedmodule'];

    if ($config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
      $levelid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$config['params']['user']]);
    } else {
      $levelid = $this->coreFunctions->getfieldvalue("client", "userid", "email=?", [$config['params']['user']]);
    }

    $qry = "select description as label,md5(code) as value from attributes where md5(parent)=? and levelid=" . $levelid . ' order by code';
    $data = $this->coreFunctions->opentable($qry, [$code]);
    return ['status' => true, 'msg' => 'Load Module List Successfully', 'data' => $data];
  } //end function


  private function getattributedetailaccess($config)
  {
    $selectedlevel = $config['params']['selectedlevel'];
    $code = $config['params']['selectedmodule'];

    if ($config['params']['logintype'] == '9bc65c2abec141778ffaa729489f3e87') {
      $levelid = $this->coreFunctions->getfieldvalue("useraccess", "accessid", "username=?", [$config['params']['user']]);
    } else {
      $levelid = $this->coreFunctions->getfieldvalue("client", "userid", "email=?", [$config['params']['user']]);
    }

    $qry = "select idno,attribute from moduleaccess where md5(idno)=? group by idno,attribute having count(attribute)>1";
    $module = $this->coreFunctions->opentable($qry, [$selectedlevel]);

    foreach ($module as $key) {
      $this->coreFunctions->execqry("delete from moduleaccess where idno=? and attribute=?", "delete", [$key->idno, $key->attribute]);
    }


    //$qry = "select md5(a.attribute) as attribute,a.description,
    //          md5((select m.idno from moduleaccess as m where 
    //        m.attribute=a.attribute and md5(m.idno)=?)) as idno,md5(a.parent) as 
    //        code from attributes as a where md5(a.parent)=? or md5(a.code)=? or md5(a.alias)=? ";

    $qry = "select distinct md5(a.attribute) as attribute,a.description,
          md5((select m.idno from moduleaccess as m where 
          m.attribute=a.attribute and md5(m.idno)=?)) as idno from attributes as a where (md5(a.parent)=? or md5(a.code)=? or md5(a.alias)=?) and a.levelid=" . $levelid;
    $data = $this->coreFunctions->opentable($qry, [$selectedlevel, $code, $code, $code]);
    $qry = "select distinct md5(a.attribute) as attribute,a.description,
          md5((select m.idno from moduleaccess as m where 
          m.attribute=a.attribute and md5(m.idno)='$selectedlevel')) as idno from attributes as a where md5(a.parent)='$code' or md5(a.code)='$code' or md5(a.alias)='$code' ";

    return ['status' => true, 'msg' => 'Load Module List Successfully', 'data' => $data, 'XXX' => $qry];
  } //end function

  private function updatemoduleaccess($config)
  {
    $userid = $this->coreFunctions->getfieldvalue('users', 'idno', 'md5(idno)=?', [$config['params']['selectedlevel']]);
    $attrib = $this->coreFunctions->getfieldvalue('attributes', 'attribute', 'md5(attribute)=?', [$config['params']['attrib']]);

    $left = $attrib - 1;
    $right = $attrib + 1;
    if (!isset($config['params']['idno'])) {
      $qry = "insert into moduleaccess (idno,attribute) values (?,?)";
      $rows = $this->coreFunctions->execqry($qry, "insert", [$userid, $attrib]);

      $qry = "update users set attributes = concat(left(attributes," . $left . "), '1',
      substr(attributes," . $right . ",length(attributes))) where idno=" . $userid;
      $row = $this->coreFunctions->execqry($qry, "update");
    } else {
      $qry = "delete from moduleaccess where idno = " . $userid . " and attribute = " . $attrib;
      $rows = $this->coreFunctions->execqry($qry, "delete");
      $qry = "update users set attributes = concat(left(attributes," . $left . "), '0',substr(attributes," . $right . ",length(attributes))) where idno=" . $userid;
      $row = $this->coreFunctions->execqry($qry, "update");
    }
    return $this->getattributedetailaccess($config);
  } //end function


  private function deletelevel($config)
  {

    $qry = "select ifnull(count(clientid),0) as value from client where md5(userid)=?";
    $clientexist = $this->coreFunctions->datareader($qry, [$config['params']['selectedlevel']]);
    if ($clientexist) {
      return ['status' => false, 'msg' => 'Can`t delete level, it was already used', 'data' => []];
    }

    $qry = "delete from users where md5(idno)=?";
    $this->coreFunctions->execqry($qry, 'delete', [$config['params']['selectedlevel']]);
    $qry = "delete from moduleaccess where md5(idno)=?";
    $this->coreFunctions->execqry($qry, 'delete', [$config['params']['selectedlevel']]);

    $level = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'data' => $level];
  } //end function

  private function editlevel($config)
  {

    $qry = "update users set username = ? where md5(idno)=?";
    $this->coreFunctions->execqry($qry, 'update', [$config['params']['editlevel'], $config['params']['selectedlevel']]);
    $level = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $level];
  } //end function

  private function duplicatelevel($config)
  {
    $date = $this->othersClass->getCurrentTimeStamp();
    $createby = $config['params']['user'];

    $qry = "insert into users(username,attributes,createdate,createby) select ?,attributes,?,? from users where md5(idno)=?";
    $this->coreFunctions->execqry($qry, 'insert', [$config['params']['editlevel'], $date, $createby, $config['params']['selectedlevel']]);
    $id = $this->coreFunctions->datareader("select idno as value from users where username=?", [$config['params']['editlevel']]);
    if ($id != 0) {
      $qry = "insert into moduleaccess(idno,attribute) select ?,attribute from moduleaccess where md5(idno)=?";
      $this->coreFunctions->execqry($qry, 'insert', [$id, $config['params']['selectedlevel']]);
      $msg = 'Duplicate Successfully';
    } else {
      $msg = 'Duplicate Failed';
    }
    $level = $this->loaddata($config);
    return ['status' => true, 'msg' => $msg, 'data' => $level];
  } //end function
















} //end class
