<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use App\Http\Classes\Logger;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\common\commonsbc;
use App\Http\Classes\modules\calendar\ementry;
use Illuminate\Support\Facades\Storage;
use Datetime;
use DateInterval;
use Illuminate\Support\Str;
use Exception;
use PDO;

use Mail;
use App\Mail\SendMail;
use PHP_Token_ELSE;

use Illuminate\Support\Arr; //v2.1

use GuzzleHttp\Client;

class othersClass
{

  private $coreFunctions;
  private $logger;
  private $companysetup;
  private $commonsbc;
  private $othersClass;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->logger = new Logger;
    $this->companysetup = new companysetup;
    $this->commonsbc = new commonsbc;
  } //end fn

  public function array_only($array1, $val)
  {
    //return array_only($array1, $val);
    return Arr::only($array1, $val); //v2.1
  }

  // @param float $apr   Interest rate.
  // @param integer $term  Loan length in years. 
  // @param float $loan   The loan amount.

  function calPMT($apr, $term, $loan)
  {
    $term = $term * 12;
    $apr = $apr / 1200;
    $amount = $apr * -$loan * pow((1 + $apr), $term) / (1 - pow((1 + $apr), $term));
    return round($amount, 2);
  }

  function loanEMI()
  {
    echo $this->calPMT(16, 1, 1020000);
  }

  public function checkip($params)
  {
    $iplist = $this->coreFunctions->opentable("select ipaddress as value from ipsetup where ipaddress ='" . $params['ip'] . "'");
    if (count($iplist) > 0) {
      return true;
    } else {
      return false;
    }
  }

  public function getstatid($config, $tablenum = '')
  {
    $trno = $config['params']['trno'];
    if ($tablenum != '') {
      $table = $tablenum;
    } else {
      $table = $config['docmodule']->tablenum;
    }
    return $this->coreFunctions->datareader("select statid as value from $table where trno = ?", [$trno]);
  }

  public function islocked($config)
  {
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'KWHRATESETUP': //headtabletemplate - stock delete
      case 'TIMEREC':
      case 'POSTINGPDC':
      case 'POSTINGSD':
        return false;
        break;
    }

    if (isset($config['params']['trno'])) {
      $trno = $config['params']['trno'];
    } else {
      return false;
    }
    $table = $config['docmodule']->head;
    if (isset($config['docmodule']->hhead)) {
      $htable = $config['docmodule']->hhead;
      $islocked = $this->coreFunctions->datareader("select lockdate as value from $table where trno = ? union all select lockdate as value from $htable where trno = ?", [$trno, $trno]);
    } else {
      $islocked = $this->coreFunctions->datareader("select lockdate as value from $table where trno = ?", [$trno]);
    }
    if ($islocked === '' || $islocked === null) {
      return false;
    } else {
      return true;
    }
  } //end fn

  public function isposted($config)
  {
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'KWHRATESETUP': //headtabletemplate - stock delete
      case 'TIMEREC':
        return false;
        break;
      case 'TACRF': //headtabletemplate - stock delete
      case 'POSTINGSD':
      case 'POSTINGPDC':
        return false;
        break;
    }

    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
    } else {
      if (isset($config['params']['trno'])) {
        $trno = $config['params']['trno'];
      } else {
        return false;
      }
    }
    $table = $config['docmodule']->tablenum;
    $document = $this->coreFunctions->datareader("select postdate as value from $table where trno = ? limit 1", [$trno]);
    if ($document === '' || $document === null) {
      return false;
    } else {
      return true;
    }
  } //end fn

  public function isposted2($trno, $table, $connection = '')
  {
    $document = $this->coreFunctions->datareader("select postdate as value from $table where trno = ? limit 1", [$trno], $connection);

    if ($document === '' || $document === null) {
      return false;
    } else {
      return true;
    }
  } //end fn
  public function isapproved($trno, $table)
  {
    $document = $this->coreFunctions->datareader("select receivedate as value from $table where trno = ? limit 1", [$trno]);
    if ($document === '' || $document === null) {
      return false;
    } else {
      return true;
    }
  } //end fn

  public function approvedsetup($config, $tablenum)
  {
    //get all userid from transnumtodo || cntnumtodo base trno
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $posted = $this->isposted($config);
    $locked = $this->islocked($config);
    switch ($tablenum) {
      case 'transnum':
        $todo = 'transnumtodo';
        break;
      case 'cntnum':
        $todo = 'cntnumtodo';
        break;
    }
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }
    if ($locked) {
      return ['status' => false, 'msg' => 'Unlock the Transaction First.'];
    }
    $btnfirst = true;
    nextapp:
    $qry = "select users.userid, d.approver, d.appline, d.line, d.ordernum,todo.trno
        from approversetup as s
        left join approverdetails as d on d.appline=s.line
        left join useraccess as users on users.username=d.approver
        left join $todo as todo on todo.userid = users.userid
        where s.doc= '" . $doc . "' and todo.trno = ? and todo.donedate is null order by d.ordernum limit 1";
    $napprover = $this->coreFunctions->opentable($qry, [$trno]);
    $donedate = $this->getCurrentTimeStamp();
    if (!empty($napprover)) {
      if ($user == $napprover[0]->approver) {
        if ($btnfirst) {
          $this->coreFunctions->sbcupdate($todo, ['donedate' => $donedate], ['userid' => $napprover[0]->userid, 'trno' =>  $napprover[0]->trno]);
          $btnfirst = false;
          goto nextapp;
        }
      } else {
        if (!$btnfirst) {
          $this->coreFunctions->sbcupdate($tablenum, ['appuser' => $napprover[0]->approver], ['trno' => $trno]);
        } else {
          return ['status' => false, 'msg' => 'Failed to approved, must approved by user ' . $napprover[0]->approver];
        }
      }
    } else {
      $this->coreFunctions->sbcupdate($tablenum, ['statid' => 36, 'appuser' => ''], ['trno' => $trno, 'statid' => 10]);
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'STATUS', 'APPROVED');
    }
    return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
  }


  public function forapproval($config, $tablenum)
  {
    // fortesting
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $posted = $this->isposted($config);
    $locked = $this->islocked($config);
    $msg = "";
    $btnapproved = true;
    switch ($tablenum) {
      case 'transnum':
        $todo = 'transnumtodo';
        break;
      case 'cntnum':
        $todo = 'cntnumtodo';
        break;
    }
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }
    if ($locked) {
      return ['status' => false, 'msg' => 'Unlock the Transaction First.'];
    }
    $appdetail = $this->coreFunctions->datareader("select count(appline) as value from approverdetails as appdetail left join approversetup 
    as appset on appset.line = appdetail.appline where appset.doc=?", [$doc]);
    $qry = "select users.userid, d.approver, d.appline, d.line, d.ordernum
        from approversetup as s 
        left join approverdetails as d on d.appline=s.line 
        left join useraccess as users on users.username=d.approver 
        where s.isapprover=1 and s.doc= '" . $doc . "' order by d.ordernum asc";
    $approver = $this->coreFunctions->opentable($qry);

    if (!empty($approver)) {
      if ($appdetail == 0) {
        return ['status' => false, 'msg' => 'There is no approver set in this module. Kindly add the approver first.'];
      }
      foreach ($approver as $key => $value) {
        $insert = [
          'userid' => $value->userid,
          'trno' => $trno,
          'createby' => $user,
          'createdate' => $this->getCurrentTimeStamp()
        ];
        if ($this->coreFunctions->sbcinsert($todo, $insert) == 1) {
          if ($btnapproved) {
            $this->coreFunctions->sbcupdate($tablenum, ['appuser' => $approver[0]->approver], ['trno' => $config['params']['trno']]);
            $btnapproved = false;
          }
        }
      }

      if ($this->coreFunctions->sbcupdate($tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
        $this->logger->sbcstatlog($config['params']['trno'], $config, 'STATUS', 'FOR APPROVAL');
        return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
      } else {
        return ['status' => false, 'msg' => 'Failed to tag for approval'];
      }
    } else {
      // no approver set
      return ['status' => false, 'msg' => "Failed to tag for approval,  $doc is not yet tagged for approval"];
    }
    return ['status' => true, 'msg' => $msg, 'reloadhead' => true];
  }

  public function getportalaccess($username)
  {
    $qry = "select u.attributes from users as u
    left join client as us on us.userid = u.idno
    where md5(us.email) = md5('$username') and us.userid<>0";
    $access = $this->coreFunctions->opentable($qry);
    return $access;
  } //end fn


  public function getAccess($username)
  {
    $qry = "select u.attributes from users as u
    left join useraccess as us on us.accessid = u.idno
    where md5(us.username) = md5('$username')";


    $access = $this->coreFunctions->opentable($qry);
    if (empty($access)) {
      $qry = "select u.attributes from users as u
      left join client as us on us.userid = u.idno
      where md5(us.email) = md5('$username') and us.userid<>0";
      $access = $this->coreFunctions->opentable($qry);
      if (empty($access)) {
        $qry = "select u.attributes from users as u
        left join app on app.userid=u.idno
        where md5(app.username)=md5('$username') and app.userid<>0";
        $access = $this->coreFunctions->opentable($qry);
      }
    }
    return $access;
  } //end fn

  public function getAccessLevel($username)
  {
    $qry = "select u.idno as value from users as u
    left join useraccess as us on us.accessid = u.idno
    where md5(us.username) = md5('$username')";
    $access = $this->coreFunctions->datareader($qry);
    if ($access == '') {
      $qry = "select u.idno as value from users as u
      left join client as us on us.userid = u.idno
      where md5(us.email) = md5('$username') and us.userid<>0";
      $access = $this->coreFunctions->datareader($qry);
      if ($access == '') {
        $qry = "select u.idno as value from users as u
        left join app on app.userid=u.idno
        where md5(app.username)=md5('$username') and app.userid<>0";
        $access = $this->coreFunctions->datareader($qry);
      }
    }
    return $access;
  } //end fn

  public function checkUseraccess($username, $password)
  {

    $password = str_replace("'", "", $password);
    $data = $this->coreFunctions->opentablelogin("select md5(md5(concat(u.userid,u.username))) as username, u.username as username2, md5(concat(accessid,u.password)) as password, u.name, md5(u.userid) as userid, themer.themecode as theme, u.picture as userpic,ifnull(client.clientid,0) as clientid, u.starttime, u.endtime, u.istime from useraccess as u left join user_themer as themer on themer.userid = u.userid left join client on client.client=u.supplier where u.isinactive =0 and md5(u.username) = md5('$username') and u.password = '$password' limit 1");
    if (empty($data)) {
      return ['status' => false, 'msg' => 'Error Login 4'];
    } else {
      if (count($data) > 0) {
        if ($data[0]->username2 === $username) {

          if ($data[0]->istime) {
            // if($data[0]->starttime != '00:00' && $data[0]->endtime != '00:00'){
            $stime = date('Y-m-d H:i:s', strtotime($this->getCurrentDate() . ' ' . $data[0]->starttime));
            $etime = date('Y-m-d H:i:s', strtotime($this->getCurrentDate() . ' ' . $data[0]->endtime));

            if ($this->getCurrentTimeStamp() >= $stime && $this->getCurrentTimeStamp() <= $etime) {
            } else {
              return ['status' => false, 'errmsg_time' => 'Your are not allowed to login at this moment'];
            }

            // }

          }

          if ($data[0]->userpic != '') {
            $pic = str_replace('/images', '', $data[0]->userpic);
            if (Storage::disk('public')->exists($pic)) {
              $data[0]->userpic = env('APP_PUBLIC') . $data[0]->userpic;
            } else {
              $data[0]->userpic = '';
            }
          }
          return ['status' => true, 'data' => $data];
        } else {
          return ['status' => false, 'msg' => 'Error Login 5'];
        }
      } else {
        return ['status' => false, 'msg' => 'Error Login 6'];
      }
    }
  }

  public function istimechecking($params)
  {
    //$this->logConsole('istimechecking');
    $data = $this->coreFunctions->opentablelogin("select u.starttime, u.endtime, u.istime from useraccess as u where u.isinactive =0 and md5(u.username) = md5('" . $params['user'] . "') limit 1");

    if (!empty($data)) {
      if ($data[0]->istime) {

        $stime = date('Y-m-d H:i:s', strtotime($this->getCurrentDate() . ' ' . $data[0]->starttime));
        $etime = date('Y-m-d H:i:s', strtotime($this->getCurrentDate() . ' ' . $data[0]->endtime));

        if ($this->getCurrentTimeStamp() >= $stime && $this->getCurrentTimeStamp() <= $etime) {
          return ['status' => true, 'loginexpired' => true];
        } else {
          return ['status' => true, 'loginexpired' => false];
        }
      } else {
        return ['status' => true, 'loginexpired' => true];
      }
    } else {
      return ['status' => false, 'loginexpired' => false];
    }
  }

  public function checkclientaccess($username, $password)
  {
    $password = str_replace("'", "", $password);
    $qry = "select md5(md5(concat(client.clientid,client.email))) as username, client.email as username2,
    md5(concat(client.clientid,client.password)) as password, client.clientname as name, md5(client.clientid) as userid, themer.themecode as theme, client.picture as userpic,ifnull(client.clientid,0) as clientid
    from client left join user_themer as themer on themer.userid = client.clientid where client.isinactive =0 and md5(client.email) = md5('" . $username . "') and md5(client.password) = '" . $password . "' limit 1";

    $data = $this->coreFunctions->opentable($qry);
    if (count($data) > 0) {
      if ($data[0]->userpic != '') {
        $pic = str_replace('/images', '', $data[0]->userpic);
        if (Storage::disk('public')->exists($pic)) {
        } else {
          $data[0]->userpic = '';
        }
      }
      return ['status' => true, 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'Error Login 7'];
    }
  }

  public function checkAccess($user, $accessid)
  {
    $access = $this->getAccess($user);
    $access = json_decode(json_encode($access), true);
    $isvalid = $access[0]['attributes'][$accessid - 1];
    return $isvalid;
  }

  //return 0 if 0
  //avoid division by zero problem
  //can customize return value via $default variable, string, number etc
  public function calculatePercentage($numerator, $denominator, $default = 0)
  {
    if ($denominator == 0) {
      return $default;
    }

    $percentage = ($numerator / $denominator) * 100;

    return $percentage;
  }


  public function createfilter($fields, $search)
  {
    if ($search === '') {
      return '';
    }
    $searches = explode(',', $search);
    $condition = ' ';
    foreach ($searches as $key => $value) {
      $tmp = '';
      foreach ($fields as $key2 => $value2) {
        if ($tmp === '') {
          $tmp .= ' and (' . $value2 . " like '%" . $value . "%' ";
        } else {
          $tmp .= ' or ' . $value2 . " like '%" . $value . "%' ";
        }
      }
      $tmp .= ')';
      $condition .= ' ' . $tmp;
    }
    return $condition;
  }

  function sanitizekeyfield($key, $str, $doc = '', $companyid = 0, $exceptpcase = [], $exceptnum = false)
  {
    $acctcode = ['contra', 'acno', 'tfaccount', 'asset', 'liability', 'revenue', 'expense', 'rev', 'waybill', 'salesreturn', 'ass'];
    if ($doc != 'AGENT') {
      array_push($acctcode, 'parent');
    }

    if ($exceptnum) goto StringOnlyHere;

    $number = [];
    array_push($number, 'isqty', 'isqty2', 'isqty3', 'iss', 'rrqty', 'qty', 'ext', 'rrcost', 'cost', 'isamt', 'amt', 'commamt');
    array_push($number, 'range1', 'range2', 'sssee', 'ssser', 'eccer', 'ssstotal', 'mpfee', 'mpfer');
    array_push($number, 'reghrs', 'absdays', 'latehrs', 'underhrs', 'ndiffhrs', 'ndiffot');
    array_push($number, 'commvat', 'forex', 'crlimit', 'rrqty', 'freight', 'oqty', 'rrqty2', 'rrqty3', 'begqty', 'db', 'cr');
    array_push($number, 'fdb', 'fcr', 'damt', 'reqqty', 'qa', 'rate', 'intclient', 'budget', 'forexid', 'critical', 'reorder');
    array_push($number, 'amt2', 'icondition', 'sgdrate', 'appamt', 'famt', 'amt4', 'amt5', 'amt6', 'amt7', 'amt8', 'amt9', 'markup');
    array_push($number, 'tcp', 'orderterm', 'vat', 'amount', 'percentdisc', 'deductpercent', 'months', 'perc1', 'perc2', 'perc3');
    array_push($number, 'perc4', 'perc5', 'perc6', 'perc7', 'perc8', 'perc9', 'perc10', 'deptid', 'noofitems', 'gcsubnoofitems');
    array_push($number, 'addon', 'damt', 'dqty', 'tqty', 'diqty', 'floor', 'subproject', 'ar', 'ap', 'paid', 'boq', 'pr', 'po');
    array_push($number, 'rr', 'jo', 'jc', 'mi', 'bal', 'drate', 'costcenterid', 'rcmonthjan', 'rcmonthfeb', 'rcmonthmar', 'rcmonthapr');
    array_push($number, 'rcmonthmay', 'rcmonthjun', 'rcmonthjul', 'rcmonthaug', 'rcmonthsep', 'rcmonthoct', 'rcmonthnov', 'rcmonthdec');
    array_push($number, 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'amt10', 'amt11', 'amt12', 'amt13', 'amt14', 'leaserate', 'acrate');
    array_push($number, 'cusarate', 'mcharge', 'percentsales', 'ms_freight', 'purchase', 'projectprice', 'purchaserid', 'priolvl');
    array_push($number, 'r', 'w', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'delcharge', 'ocp', 'dollarprice', 'stageid', 'clientid');
    array_push($number, 'dropoffwh', 'whid2', 'intransit', 'truckid', 'weight', 'capacity', 'reservationfee', 'farea', 'fpricesqm');
    array_push($number, 'ftcplot', 'ftcphouse', 'fma1', 'fma2', 'fma3', 'finterestrate', 'fsellingpricegross', 'fdiscount');
    array_push($number, 'fsellingpricenet', 'fmiscfee', 'fcontractprice', 'fmonthlydp', 'fmonthlyamortization', 'ffi', 'fmri');
    array_push($number, 'cash', 'annual', 'semi', 'monthly', 'quarterly', 'processfee', 'dp', 'pf', 'nf', 'qty1', 'qty2', 'tqty');
    array_push($number, 'batchsize', 'yield', 'categoryid', 'surcharge', 'leadfrom', 'leadto', 'nobidtotal', 'branch', 'duration');
    array_push($number, 'distance', 'odostart', 'odoend', 'fuelconsumption', 'mktg', 'dc', 'bo', 'card', 'openingintro', 'e2e');
    array_push($number, 'rebate', 'rtv', 'itemid', 'downpayment', 'citrno', 'projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid');
    array_push($number, 'subamenityid', 'ordernum', 'appline', 'valamt', 'cumsmt', 'delivery', 'consigneeid', 'shipperid', 'depdb');
    array_push($number, 'depcr', 'catrno', 'shiftid', 'consignid', 'waivedspecs', 'fline', 'pitrno', 'petrno', 'maxqty', 'penalty');
    array_push($number, 'batchid', 'customerid', 'acnoid', 'interest', 'pfnf', 'value', 'value2', 'mincome', 'mexp', 'branchid');
    array_push($number, 'whid', 'othrs', 'earlyothrs', 'apothrs', 'apothrsextra', 'apndiffhrs', 'apndiffothrs', 'collection', 'deposit', 'begbal', 'endingbal');
    array_push($number, 'balance', 'petty', 'avecost', 'prevamt', 'amount2', 'supervisorid', 'runtime', 'gp', 'roleid', 'divid');
    array_push($number, 'isbrgy', 'price', 'empstatusid', 'rctrno', 'rcline', 'notedbyid', 'recappid', 'appdisid', 'tobranchid');
    array_push($number, 'supid', 'ndesid', 'sectid', 'todeptid', 'countsupervisor', 'countapprover', 'count', 'charge1');
    array_push($number, 'namt', 'namt2', 'nfamt', 'namt4', 'namt5', 'namt6', 'namt7', 'iseq', 'first', 'last', 'jobid', 'jobid2', 'branchid2', 'roleid2', 'loanlimit');
    array_push($number, 'lengthstay', 'mealamt', 'mealnum', 'texpense', 'gas', 'lodgeexp', 'misc', 'crate', 'amortization', 'contricompid');
    array_push($number, 'rrrefx', 'rrlinex', 'apamt', 'apamortization', 'salary', 'tbasicrate', 'mealdeduc', 'original_qty', 'counterline', 'serviceline', 'istaskcat');

    if ($companyid == 8 && $doc == 'PM') { //maxipro
      array_push($number, 'wac', 'jr');
    }
    if ($doc == 'STOCKCARD') {
      array_push($number, 'insurance');
    }

    if ($companyid == 34) { //evergreen
      if ($doc == 'AF' || $doc == 'CP') {
        unset($number['ext']);
      }
    }


    StringOnlyHere:

    $boolean = [];

    array_push($boolean, 'void', 'isewt', 'istax', 'isvat', 'isvewt', 'ismain', 'isinactive', 'isactive', 'isnopay', 'issynced', 'isdriver', 'ispayroll');
    array_push($boolean, 'ispassenger', 'isshow', 'iscompute', 'isparenttotal', 'isgeneric', 'isexisted', 'isnsi', 'ists', 'iscldetails');
    array_push($boolean, 'waivedqty', 'isstudent', 'isold', 'isnew', 'isforeign', 'isadddrop', 'iscrossenrollee', 'istransferee');
    array_push($boolean, 'islateenrollee', 'issubmitted', 'isapprove', 'isofficesupplies', 'ispaid', 'isreturn', 'ispartial', 'ispa');
    array_push($boolean, 'ispa2', 'isreturned', 'isrefunded', 'inactive', 'issmoking', 'isdefault', 'isdefault2', 'w1', 'w2', 'w3', 'w4', 'w5');
    array_push($boolean, 'halt', 'w13', 'earnded', 'isadmin', 'isimport', 'fg_isfinishedgood', 'isexpedite', 'isprefer', 'subinv', 'ispermanent');
    array_push($boolean, 'isloc', 'istrip', 'fg_isequipmenttool', 'ishired', 'issy', 'isgradeschool', 'ishighschool', 'isho', 'isoracle');
    array_push($boolean, 'forreturn', 'isapprover', 'islabor', 'isserial', 'isbilling', 'isshipping', 'isbranch', 'isfa', 'isassetwh');
    array_push($boolean, 'isgeneratefa', 'isenconvertgrade', 'ischiconvertgrade', 'isconduct', 'ischinese', 'noprint', 'isexhibit', 'isseminar');
    array_push($boolean, 'dropoff', 'isadv', 'iscanvassonly', 'serialized', 'ispickupdate', 'ismanual', 'issales', 'issalesdef', 'isapproved');
    array_push($boolean, 'ismon', 'ismon_am', 'ismon_pm', 'istue', 'istue_am', 'istue_pm', 'iswed', 'iswed_am', 'iswed_pm', 'isthu');
    array_push($boolean, 'isthu_am', 'isthu_pm', 'isfri', 'isfri_am', 'isfri_pm', 'issat', 'issat_am', 'issat_pm', 'issun', 'issun_am');
    array_push($boolean, 'issun_pm', 'isdp', 'isencashment', 'isonlineencashment', 'isvatzerorated', 'isnotarizedcert', 'lastdp', 'noncomm');
    array_push($boolean, 'invnotrequired', 'isconfirmed', 'isacknowledged', 'ischqreleased', 'ispaid', 'isconsumable', 'isrepair', 'isexcess');
    array_push($boolean, 'isplanholder', 'isnotallow', 'ispartialpaid', 'isactivity', 'issp', 'ismc', 'isinvoice', 'atm', 'isss', 'isprojexp');
    array_push($boolean, 'isorder', 'ischannel', 'default_in', 'default_out', 'uom_inactive', 'isreasoncode', 'ishelper', 'isreassigned', 'ispexp');
    array_push($boolean, 'isonelog', 'isbank', 'isnonserial', 'isbrgyoff', 'isbusiness', 'isallowliquor', 'issupervisor', 'isapprover', 'isdiminishing');
    array_push($boolean, 'isnoentry', 'isliquidation', 'iswithhearing', 'isevaluator', 'iscomm', 'isportalloan');


    $date = [];

    array_push($date, 'dateid', 'start', 'end', 'postdate', 'hired', 'dateeffect', 'prdstart', 'prdend', 'appdate', 'bday', 'date1');
    array_push($date, 'date2', 'agency', 'constart', 'conend', 'resigned', 'effdate', 'feffdate', 'closedate', 'ordate', 'estart', 'eend', 'eext');
    array_push($date, 'sstart', 'send', 'sext', 'astart', 'aend', 'aext', 'regular', 'prob', 'probend', 'posteddate', 'waybilldate', 'returndate');
    array_push($date, 'refunddate', 'reqdate', 'releasedate', 'pdeadline', 'effectdate', 'dateupdated', 'breakin', 'breakout', 'date3');
    array_push($date, 'date4', 'date5', 'date6', 'date7', 'date8', 'date9', 'date10', '13start', '13end', 'renewaldate', 'warrantyend');
    array_push($date, 'dateacquired', 'disposaldate', 'startinsured', 'endinsured', 'invoicedate', 'podate', 'dateneeded', 'ovaliddate');
    array_push($date, 'sentdate', 'pickupdate', 'actualin', 'actualout', 'schedbrkin', 'schedbrkout', 'actualbrkin', 'actualbrkout');
    array_push($date, 'scheddate', 'receivedate', 'schedstarttime', 'schedendtime', 'warranty', 'trainee', 'enddate', 'podate', 'leasedate');
    array_push($date, 'schedin', 'schedout', 'deldate', 'returndate_sup', 'dateclose', 'startdate', 'effectivity', 'createdate', 'seendate');
    array_push($date, 'donedate', 'deadline', 'origdeadline', 'deadline2', 'conndate', 'disconndate', 'expirydate', 'refdate', 'datefrom');
    array_push($date, 'dateto', 'shipdate', 'ordate', 'wbdate', 'dateid2,submitdate', 'expiry2', 'schedin', 'schedout', 'disapprovedate');
    array_push($date, 'approveddate', 'approveddate2', 'disapproveddate', 'disapproveddate2', 'approvedate2', 'disapprovedate2');
    array_push($date, 'approvedate', 'date_approved_disapproved', 'date_approved_disapproved2', 'brk1stin', 'brk1stout', 'brk2ndin');
    array_push($date, 'brk2ndout', 'prevdate', 'checkdate', 'empstatdate', 'jobdate', 'dateend', 'voiddate', 'bday2', 'tdate1');
    array_push($date, 'approvedbuddate', 'disapprovedbuddate', 'whmandate', 'ardate', 'encodeddate', 'sdate1', 'sdate2', 'editdate');
    array_push($date, 'depodate', 'lpaydate', 'pickerstart', 'duedate', 'lockdate', 'crtldate', 'clearday', 'pickerend', 'viewdate', 'receiveddate');
    array_push($date, 'regdate');

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        break;
      default:
        array_push($date, 'starttime', 'endtime', 'leadfrom', 'leadto');
        break;
    }

    $dateonly = ['dateonly'];
    switch ($companyid) {
      case 16: //ati
        $toupper = [];
        $propercase = ['salutation', 'fname', 'mname', 'lname', 'uom'];
        break;
      case 6: //mitsukoshi
        $toupper = ['itemname', 'clientname'];
        $propercase = [];
        break;
      case 37: //mega crystal
        $toupper = [];
        $propercase = [];
        if ($doc == 'STOCKCARD') {
          $toupper = ['itemname', 'uom', 'partno', 'body', 'sizeid', 'color'];
        }
        if ($doc == 'CUSTOMER' || $doc == 'SUPPLIER') {
          $toupper = ['clientname', 'addr', 'ship'];
        }
        break;
      case 46: //morningsteel
        $toupper = [];
        $propercase = [];

        if ($doc == 'CUSTOMER' || $doc == 'SUPPLIER') {
          $toupper = ['clientname'];
        }
        break;
      case 47: //kstar
        if ($doc != 'AGENT') {
          $toupper = ['clientname'];
        }
        $propercase = [];
        break;
      case 45: //pdpi payroll
        $toupper = [];
        $propercase = [];
        if ($doc == 'EMPLOYEE') {
          $toupper = ['emplast', 'empfirst', 'empmiddle'];
        }
        break;
      case 48: //seastar
        $toupper = [];
        $propercase = [];
        switch ($doc) {
          case 'CUSTOMER':
          case 'SUPPLIER':
          case 'WAREHOUSE':
            $toupper = ['clientname', 'addr', 'ship', 'email', 'tel', 'fax', 'tel2', 'tin', 'contact', 'rem', 'type', 'groupid'];
            break;
          case 'LL':
            $toupper = ['ourref', 'vessel', 'plateno', 'voyageno', 'sealno', 'yourref', 'unit', 'loadedby', 'rem'];
            break;
          case 'SJ':
            $toupper = ['client', 'clientname', 'address', 'yourref', 'ourref', 'rem', 'itemdesc', 'unit'];
            break;
        }
        break;

      default:
        //2025.01.27 [KIM] - kinomment ko po muna eto. wag pong ireremove muna.
        // $toupper = [];
        // if ($doc != 'WAREHOUSE') {
        //   $propercase = ['emplast', 'empfirst', 'empmiddle', 'maidname', 'contact1', 'contact2', 'clientname', 'itemname'];
        // } else {
        //   $propercase = ['emplast', 'empfirst', 'empmiddle', 'maidname', 'contact1', 'contact2'];
        // }

        $toupper = [];
        $propercase = [];
        break;
    }

    if (!empty($exceptpcase)) {
      foreach ($exceptpcase as $expc) {
        $index = array_search($expc, $propercase);
        if ($index !== FALSE) {
          unset($propercase[$index]);
        }
      }
    }

    $except_striptag = ['addr', 'rem', 'itemdesc', 'creditinfo', 'accessories', 'itemdescription', 'poterms', 'itemrem', 'task'];

    $str = str_replace("'", "`", $str); //FMM 2020.05.16 - old replace ´
    $str = str_replace("’", "`", $str); //
    $str = stripslashes($str);

    if (in_array($key, $except_striptag)) {
    } else {
      $str = strip_tags($str);
      $str = str_replace('"', "”", $str);
    }

    if (in_array($key, $acctcode)) {
      if ($str != '') {
        $str =  '\\' . $str;
      } else {
        if ($doc == 'COA' && $key == 'parent') {
          $str =  '\\';
        }
      }
    }

    if (!$exceptnum) {
      if (in_array($key, $number)) {
        $str =  str_replace(',', '', $str);
        $str =  str_replace(' ', '', $str);

        // if (strspn(str_replace($str,"","-"), "0123456789") == 0) {
        //   $str = 0;
        // }      
        if (!(is_numeric($str))) {
          $str = 0;
        }

        if ($str == '' || $str == null || empty($str)) {
          $str = 0;
        }

        if ($key == 'db' || $key == 'cr') {
          $str = number_format($str, 2, '.', '');
        }
      }
    }

    if (in_array($key, $date)) {
      $str =  str_replace(',', '', $str);
      if ($str == '' || $str == null || $str == '0000-00-00 00:00:00' || $str == '0000/00/00' || $str == 'Invalid date' || $str == '0000-00-00') {
        $str = null;
      } else {
        if (is_numeric($str)) { // if date is from excel
          $UNIX_DATE = ($str - 25569) * 86400;
          // $str = gmdate("Y-m-d H:i:s", $UNIX_DATE);
          $tempstr = new DateTime("@$UNIX_DATE");
          $str = $tempstr->format('Y-m-d H:i:s');
        } else {
          $str = date_format(date_create($str), "Y-m-d H:i:s");
        }
      }
    }

    if (in_array($key, $dateonly)) {
      $str =  str_replace(',', '', $str);
      if ($str == '' || $str == null || $str == '0000-00-00 00:00:00' || $str == '0000/00/00' || $str == 'Invalid date') {
        $str = null;
      } else {
        $str = date_format(date_create($str), "Y-m-d");
      }
    }

    if (in_array($key, $boolean)) {
      if ($this->is_true($str)) {
        $str = 1;
      } else {
        $str = 0;
      }
    }

    if (in_array($key, $propercase)) {
      $result = "";
      $words = explode(" ", $str);
      for ($i = 0; $i < count($words); $i++) {
        $s = strtolower($words[$i]);
        $s = substr_replace($s, strtoupper(substr($s, 0, 1)), 0, 1);
        $result .= "$s ";
      }
      $str = trim($result);
    }

    if (in_array($key, $toupper)) {
      $result = strtoupper($str);
      $str = trim($result);
    }
    return $str;
  } //end function sanitize

  function sbcdateformat($date, $c = '-', $f = 'Y-m-d')
  {
    // $newdate = date_create($date);
    // return date_format($newdate, "Y" . $c . "m" . $c . "d");
    $newf = str_replace('-', $c, $f);
    return (new Datetime($date))->format($newf);
  }

  function datefilter($date)
  {
    return
      date_format(date_create_from_format('Y-m-d', $date), 'Y-m-d');
  }

  public function in_arrayi($needle, $haystack)
  {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
  }

  function is_true($val, $return_null = false)
  {
    $boolval = (is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val);
    return ($boolval === null && !$return_null ? false : $boolval);
  } //end function

  function sanitize($str, $strtype)
  { //THIS FUNCTIONS STRIPS HTML TAGGING CHARATERS,STRIPS SLASHES AND ALSO REPLACES QUOTES WITH `
    switch ($strtype) {
      case 'ARRAY':
        $acnocodes = array('contra', 'acno', 'asset', 'liability', 'revenue', 'expense');
        $qty = array('isqty', 'isqty2', 'iss', 'rrqty', 'qty');

        foreach ($str as $key => $strval) {
          if (!in_array($key, $acnocodes)) {
            $str[$key] = strip_tags($str[$key]);
            $str[$key] = stripslashes($str[$key]);
            $str[$key] = str_replace("'", "´", $str[$key]);
            $str[$key] = str_replace('"', "”", $str[$key]);
          }
          if (in_array($key, $qty)) {
            if ($str[$key] == '' || empty($str[$key])) {
              $str[$key] = 0;
            }
          }
        } //end foreach
        return $str;
        break;
      default:
        $str = strip_tags($str);
        $str = stripslashes($str);
        $str = str_replace("'", "´", $str);
        $str = str_replace('"', "”", $str);
        return $str;
        break;
    } //END SWITCH CASE
  } //end function sanitize  


  public function GetPrefix($PadString)
  {
    return $this->commonsbc->GetPrefix($PadString);
  }


  public function Getsuffix($PadString)
  {
    return $this->commonsbc->Getsuffix($PadString);
  }

  public function isnumber($prefix)
  {
    return $this->commonsbc->isnumber($prefix);
  }

  public function SearchPosition($Search)
  {
    for ($i = 0; $i < strlen($Search); $i++) {
      if (strspn(substr($Search, $i, 1), '1234567890')) {
        return $i;
      }
    }
    return strlen($Search);
  }

  public function last_bref($config)
  {
    return $this->commonsbc->last_bref($config);
  }

  public function getPrefixes($doc, $config)
  {
    return $this->commonsbc->getPrefixes($doc, $config);
  }

  public function Prefixes($pref, $config)
  {
    return $this->commonsbc->Prefixes($pref, $config);
  }

  public function getlastseq($prefix, $config, $tablenum = '', $moduledoc = '', $pyear = 0)
  {
    return $this->commonsbc->getlastseq($prefix, $config, $tablenum, $moduledoc, $pyear);
  }


  public function PadJ($PadString, $Len, $yr = 0)
  {
    return $this->commonsbc->PadJ($PadString, $Len, $yr);
  }


  public function getacnoname($acno)
  {
    $qry = "select acnoname from coa where acno='$acno'";
    $data = $this->coreFunctions->opentable($qry);
    return $data[0]->acnoname;
  } //end if


  public function computestock($amt, $disc, $qty, $uomfactor, $vat = 0, $cur = 'P', $kgs = 0, $isround = 0, $perqty = 0)
  {
    if (empty($disc)) {
      $disc = 0;
    }

    if ($kgs == 0) {
      $kgs = 1;
    }

    if ($qty == 0) {
      $hiddenqty = 0 * $uomfactor; //[ISS / QTY]
      $hiddenamt = 0;
    } elseif ($qty < 0) {
      $hiddenqty = abs($qty) * $uomfactor; //[ISS / QTY].
      if ($perqty == 1) {
        $hiddenamt = $this->Discount($amt, $disc) * $qty; //discount per qty
        // $this->coreFunctions->LogConsole($hiddenamt . ' 1st');
      } else {
        $hiddenamt = $this->Discount($amt * $qty * $kgs, $disc);
      }

      $hiddenamt = ($hiddenamt / $uomfactor) / $qty;
    } //END IF
    else {
      $hiddenqty = abs($qty) * $uomfactor; //[ISS / QTY].
      if ($perqty == 1) {
        $hiddenamt = $this->Discount($amt, $disc) * $qty;
        // $this->coreFunctions->LogConsole($hiddenamt . ' 2nd');
      } else {
        $hiddenamt = $this->Discount($amt * $qty * $kgs, $disc);
        // $this->coreFunctions->LogConsole($hiddenamt . ' else');
      }

      $hiddenamt = ($hiddenamt / $uomfactor) / $qty;
    } //END IF
    if ($vat != 0) {
      $hiddenamt = floatval($hiddenamt) / 1.12;
    }
    if ($isround) {

      if ($perqty == 1) {
        $ext = round($this->Discount(floatval($amt * $kgs), $disc), 2) * $qty; //[ISAMT / RRCOST]
      } else {
        $ext = $this->Discount(floatval($amt * $qty * $kgs), $disc); //[ISAMT / RRCOST]
      }
    } else {
      if ($perqty == 1) {
        $ext = $this->Discount(floatval($amt * $kgs), $disc) * $qty; //[ISAMT / RRCOST]
      } else {
        $ext = $this->Discount(floatval($amt * $qty * $kgs), $disc); //[ISAMT / RRCOST]
      }
    }

    $ext = str_replace(',', '', $ext);

    $hiddenamt = $this->sanitizekeyfield('amt', $hiddenamt);
    // $this->coreFunctions->LogConsole($hiddenamt . ' amt pass last');
    $hiddenqty = $this->sanitizekeyfield('qty', $hiddenqty);
    // $this->coreFunctions->LogConsole($hiddenqty . ' qty pass last');
    $ext = $this->sanitizekeyfield('amt', $ext);
    // $this->coreFunctions->LogConsole($hiddenqty . ' ext pass last');
    return array('amt' => $hiddenamt, 'qty' => $hiddenqty, 'ext' => $ext);
  }

  public function Discount($Amt, $Discount)
  {
    return $this->commonsbc->Discount($Amt, $Discount);
  } //end function discount


  private function right($value, $count)
  {
    return substr($value, ($count * -1));
  } //end fn

  private function left($string, $count)
  {
    return substr($string, 0, $count);
  } //end fn


  public function gettrnodocno($docno, $config)
  {
    return $this->commonsbc->gettrnodocno($docno, $config);
  } //end fn


  public function readprofile($section, $config)
  {
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $pvalue = $this->coreFunctions->datareader("select pvalue as value from profile where psection ='" . $section . "' and doc=? and puser=? ", [$doc, $user]);
    return $pvalue;
  }

  public function writeprofile($section, $value, $config)
  {
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $this->coreFunctions->execqry("update profile set pvalue='" . $value . "' where psection ='" . $section . "' and doc=? and puser=? ", "update", [$doc, $user]);
  }

  public function checkprofile($section, $value, $config)
  {
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $pvalue = $this->readprofile($section, $config);
    if ($pvalue == '') {
      $this->coreFunctions->execqry("insert into profile(doc,psection,pvalue,puser)values(?,?,?,?)", "insert", [$doc, $section, $value, $user]);
    } else {
      $this->writeprofile($section, $value, $config);
    }
  }

  public function checkseendate($config, $tablenum)
  {
    $doc = $config['params']['doc'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : 0;
    if ($trno == '') {
      $trno = 0;
    }

    $user = $config['params']['user'];
    $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);

    switch ($tablenum) {
      case 'transnum':
        $todo = 'transnumtodo';
        break;
      case 'cntnum':
        $todo = 'cntnumtodo';
        break;
    }

    $seen = $this->coreFunctions->opentable("select line, seendate from $todo 
                where trno = $trno and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) order by line desc limit 1", [$userid, $userid]);
    if (!empty($seen[0]->line)) {
      $this->coreFunctions->execqry("update $todo set seendate='" . $this->getCurrentTimeStamp() . "' where trno = $trno and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) and line = ? ", "update", [$userid, $userid, $seen[0]->line]);
    }
  }

  public function checkdonetodo($config, $tablenum)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);

    switch ($tablenum) {
      case 'transnum':
        $todo = 'transnumtodo';
        break;
      case 'cntnum':
        $todo = 'cntnumtodo';
        break;
    }

    $chk = $this->coreFunctions->opentable("select * from $todo where trno=? and ((userid = ? and clientid=0) or (userid=0 and clientid = ?))", [$trno, $userid, $userid]);
    if (!empty($chk)) {
      $donedate = $this->coreFunctions->datareader("select donedate as value from $todo where trno=? and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) order by line desc", [$trno, $userid, $userid]);
      $btndonetodo = $donedate == '' ? true : false;
    } else {
      $btndonetodo = false;
    }

    return $btndonetodo;
  }

  public function donetodo($config, $tablenum)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    $user = $config['params']['user'];
    $userid = $this->coreFunctions->datareader("select userid as value from useraccess where username = ? 
              union all select clientid as value from client where email = ?", [$user, $user]);
    switch ($tablenum) {
      case 'transnum':
        $todo = 'transnumtodo';
        break;
      case 'cntnum':
        $todo = 'cntnumtodo';
        break;
        // case 'hrisnum':
        //   $todo = 'hrisnumtodo';
        //   break;
    }

    $donedate = $this->coreFunctions->opentable("select line,donedate from $todo where trno=? and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) and donedate is null ", [$trno, $userid, $userid]);

    // switch ($config['params']['action']) {
    //   case 'hqapprovedby':
    //     $appdisid = $this->coreFunctions->getfieldvalue('personreq', 'appdisid', 'trno=?', [$trno]);

    //     if ($this->companysetup->getistodo($config['params'])) {
    //       $insert = [
    //         'clientid' => $appdisid,
    //         'trno' => $trno,
    //         'createby' => $config['params']['user'],
    //         'createdate' => $this->getCurrentTimeStamp()
    //       ];
    //       $this->coreFunctions->sbcinsert("hrisnumtodo", $insert);
    //     }
    //     break;
    // }

    if (empty($donedate[0]->donedate)) {
      $this->coreFunctions->execqry("update $todo set donedate='" . $this->getCurrentTimeStamp() . "' where trno = $trno and ((userid = ? and clientid=0) or (userid=0 and clientid = ?)) and line = '" . $donedate[0]->line . "' ", "update", [$userid, $userid]);
    }

    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function setDefaultTimeZone()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    date_default_timezone_set('Asia/Singapore');
  } //end function

  public function getLocalTime()
  {
    $this->setDefaultTimeZone();
    $timenow = date('H:i:s A');
    return $timenow;
  }

  public function getCurrentTimeStamp()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    $this->setDefaultTimeZone();
    $current_timestamp = date('Y-m-d H:i:s');
    return $current_timestamp;
  } //end function

  public function getCurrentDate()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    $this->setDefaultTimeZone();
    $current_timestamp = date('Y-m-d');
    return $current_timestamp;
  } //end function

  public function validateDate($date, $format = 'Y-m-d')
  {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
  }

  public function sortarray($arr, $sort)
  {
    $arr2 = [];
    foreach ($sort as $key => $value) {
      $arr2[$key] = $arr[$value];
    }
    return $arr2;
  }


  public function postinghead($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $trno = $config['params']['trno'];

    if ($config['params']['companyid'] == 21) $this->logger->sbcwritelog($trno, $config, 'POST', 'Posting head'); //Kinggeorge

    $addedfield = "";
    $selectaddedfield = "";
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
    }

    switch ($systemtype) {
      case 'REALESTATE':
        $addedfield = " ,modelid, phaseid, blklotid,rctrno,rcline, amenityid, subamenityid";
        $selectaddedfield = " ,modelid, phaseid, blklotid,rctrno,rcline, amenityid, subamenityid";
        break;
      case 'LENDING':
        $addedfield = " ,rctrno,rcline,purposeid,acctname";
        $selectaddedfield = " ,rctrno,rcline,purposeid,acctname";
        break;
      case 'BMS':
        $addedfield = " ,contact,bstype,ownername,ownertype,owneraddr";
        $selectaddedfield = " ,head.contact,head.bstype,head.ownername,head.ownertype,head.owneraddr";
        break;
    }
    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        switch ($config['params']['doc']) {
          case 'AI':
          case 'SJ';
            $addedfield = ", sotrno";
            $selectaddedfield = ", head.sotrno";
            break;
          case 'CV':
            $addedfield = ",isencashment, isonlineencashment";
            $selectaddedfield = ",head.isencashment, head.isonlineencashment";
            break;
        }
        break;
      case 8: //maxipro
        switch ($config['params']['doc']) {
          case 'CV':
            $addedfield = ",paymode,hacno,hacnoname";
            $selectaddedfield = ",head.paymode,head.hacno,head.hacnoname";
            break;
          case 'PB':
            $addedfield = ",rem2";
            $selectaddedfield = ",head.rem2";
            break;
          case 'MT':
            $addedfield = ",rqtrno";
            $selectaddedfield = ",head.rqtrno";
            break;
        }
        break;
      case 16: //ati
        switch ($config['params']['doc']) {
          case 'PR':
            $addedfield = ",svsno, pono";
            $selectaddedfield = ",head.svsno, head.pono";
            break;
          case 'RM':
            $addedfield = ",pdtrno, stageid";
            $selectaddedfield = ",head.pdtrno, head.stageid";
            break;
          case 'FG':
            $addedfield = ",pdtrno,stageid";
            $selectaddedfield = ",head.pdtrno,head.stageid";
            break;
          case 'CV':
            $addedfield = ",modeofpayment";
            $selectaddedfield = ",head.modeofpayment";
            break;
        }
        break;

      case 26: //bee healthy
        switch ($config['params']['doc']) {
          case 'CV':
            $addedfield = ",costcodeid";
            $selectaddedfield = ",head.costcodeid";
            break;
        }
        break;
      case 40: //cdo
        switch ($config['params']['doc']) {
          case 'CI':
            $addedfield = ",modeofpayment,rem2";
            $selectaddedfield = ",head.modeofpayment,head.rem2";
            break;
          case 'MJ':
            $addedfield = ",modeofsales";
            $selectaddedfield = ",head.modeofsales";
            break;
        }
        break;
      case 48: //SEASTAR
        switch ($config['params']['doc']) {
          case 'SJ':
            $addedfield = ",consigneeid,shipperid,conaddr,whto";
            $selectaddedfield = ",head.consigneeid,head.shipperid,head.conaddr,head.whto";
            break;
        }
        break;
      case 47: //kstar
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ",freight,agentfee";
            $selectaddedfield = ",head.freight,head.agentfee";
            break;
        }
        break;

      case 56: //homeworks
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ",longitude";
            $selectaddedfield = ",head.longitude";
            break;
        }
        break;
      case 50: //unitech
        $addedfield = ",prdtrno";
        $selectaddedfield = ",head.prdtrno";
        break;
      case 59: //roosevelt
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ",ied, bankcharges, interest, brokerfee, arrastre";
            $selectaddedfield = ", head.ied, head.bankcharges, head.interest, head.brokerfee, head.arrastre";
            break;
          case 'SJ':
            $addedfield = ",bpo, ctnsno";
            $selectaddedfield = ", head.bpo, head.ctnsno";
            break;
        }
        break;
      case 60: //transpower
        switch ($config['params']['doc']) {
          case 'SJ':
            $addedfield = ",cmtrno";
            $selectaddedfield = ",head.cmtrno";
            break;
        }
        break;
      default:
        switch ($config['params']['doc']) {
          case 'RM':
            $addedfield = ",pdtrno, stageid";
            $selectaddedfield = ",head.pdtrno, head.stageid";
            break;
          case 'FG':
            $addedfield = ",pdtrno,stageid";
            $selectaddedfield = ",head.pdtrno,head.stageid";
            break;
        }
        break;
    }

    $add = "";
    $select = "";
    if ($config['params']['companyid'] != 22) { //not eipi
      $add = ",ajtrno";
      $select = ",head.trno";
    }

    $qry = "insert into " . $config['docmodule']->hhead . "(trno,doc,docno,clientid,clientname,address,shipto,dateid,
                    terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,
                    whid,due,cur,tax,vattype,contra,deptid,project,ewt,ewtrate,agentid,creditinfo,crline,
                    overdue,projectid,subproject,pltrno,deliverytype,customername,projectto,subprojectto,
                    partreqtypeid,waybill,brtrno,shipid,billid,tel,branch,statid,taxdef,
                    shipcontactid,billcontactid,invoiceno,invoicedate,qttrno,whref,ms_freight,mlcp_freight,
                    salestype, sano,pono, deldate, crref, returndate, refunddate, sdate1,sdate2,empid,driver,
                    plateno,excess,excessrate,aftrno,checkno,checkdate,amount,refdate,istrip,voiddate,voidby,
                    orderno,strdate1,strdate2,trnxtype,cur2, forex2,fpid,crno, rfno,chsino,swsno,cotrno,petrno,
                    ista,layref,isfa,isnoentry,rrfactor " . $add . $addedfield  . ")
            SELECT head.trno,head.doc, head.docno,ifnull(client.clientid,0), ifnull(head.clientname,''), head.address,head.shipto,
                    head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
                    head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,
                    ifnull(wh.clientid,0),head.due,head.cur,head.tax,head.vattype,head.contra,head.deptid,
                    head.project,head.ewt,head.ewtrate,ifnull(agent.clientid,0),head.creditinfo,head.crline,
                    head.overdue,head.projectid,head.subproject,head.pltrno,head.deliverytype,head.customername,
                    head.projectto,head.subprojectto,head.partreqtypeid,head.waybill,head.brtrno,head.shipid,
                    head.billid,head.tel,head.branch,head.statid,head.taxdef,head.shipcontactid,head.billcontactid,
                    head.invoiceno,head.invoicedate,head.qttrno,head.whref,head.ms_freight,head.mlcp_freight,
                    head.salestype,head.sano,head.pono,head.deldate,head.crref,head.returndate, head.refunddate,
                    sdate1,sdate2,head.empid,head.driver,head.plateno,head.excess,head.excessrate,head.aftrno,
                    head.checkno,head.checkdate,head.amount,head.refdate,head.istrip,head.voiddate,head.voidby,
                    head.orderno,head.strdate1,head.strdate2,head.trnxtype,head.cur2,head.forex2,head.fpid,head.crno,head.rfno,head.chsino,head.swsno,head.cotrno,
                    head.petrno,head.ista,head.layref,head.isfa,head.isnoentry,head.rrfactor " . $select . $selectaddedfield  . "    
            FROM " . $config['docmodule']->head . " as head 
            left join cntnum on cntnum.trno=head.trno 
            left join client on client.client=head.client
            left join client as wh on wh.client=head.wh 
            left join client as agent on agent.client = head.agent
            where head.trno=? limit 1";

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingstock($config)
  {
    $trno = $config['params']['trno'];

    switch ($config['params']['companyid']) {
      case 39: //cbbsi
        if ($config['params']['doc'] == 'SM') {
          $qry = "insert into " . $config['docmodule']->hstock . "(trno,line,itemid,rrqty,qty,uom,disc,rrcost,cost,lastcost,ext,charges,wh,whid,refx,linex,ref)
                  SELECT stock.trno, stock.line, item.itemid,stock.rrqty,stock.qty,stock.uom,stock.disc,stock.rrcost,stock.cost,stock.lastcost,stock.ext,stock.charges,
                  stock.wh,stock.whid,stock.refx,stock.linex,stock.ref
                  FROM " . $config['docmodule']->stock . " as stock left join item on item.itemid=stock.itemid
                  where stock.trno =?";
        } else {
          goto defaultqry;
        }
        break;
      case 59: //roosevelt
        if ($config['params']['doc'] == 'BE' || $config['params']['doc'] == 'RE') {
          $qry = "insert into " . $config['docmodule']->hstock . "(trno,line,checkno,amount,editby,editdate,
                          refx,linex,rctrno,rcline,acnoid,rcchecks,rem,bank,branch,checkdate,clientid)
                  SELECT trno, line, checkno,amount,editby,editdate,refx,linex,rctrno,rcline,acnoid,rcchecks,rem,
                  bank,branch,checkdate,clientid
                  FROM " . $config['docmodule']->stock . " 
                  where trno =?";
        } else {
          goto defaultqry;
        }
        break;
      case 63: //ericco
        if ($config['params']['doc'] == 'CH') {
          $qry = "insert into " . $config['docmodule']->hstock . "(trno,line,itemid,isqty,iss,uom,isamt,amt,ext,whid,rem,qa,void,sortline,noprint,encodeddate,encodedby,editdate,editby)
                  SELECT trno,line,itemid,isqty,iss,uom,isamt,amt,ext,whid,rem,qa,void,sortline,noprint,encodeddate,encodedby,editdate,editby
                  FROM " . $config['docmodule']->stock . " as stock 
                  where stock.trno =?";
        } else {
          goto defaultqry;
        }
        break;
      default:
        defaultqry:
        $qry = "insert into " . $config['docmodule']->hstock . "(trno,line ,itemid,uom,whid,loc,loc2,expiry,ref,disc,cost,qty,void,rrcost,rrqty,ext,
                encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,isamt,amt,isqty,iss,tstrno,
                tsline,fcost,rebate,rem,stageid,locid,palletid,locid2,palletid2,isextract,pickerid,pickerstart,pickerend,whmanid,whmandate,forkliftid,suppid,itemstatus, 
                projectid,sorefx,solinex,sgdrate,poref, podate,isqty2,original_qty,reqtrno,reqline,agentid,kgs,insurance,sortline,freight,invid,expid,iscomponent,isqty3,
                prevqty,ckrefx,cklinex,ckqa,color,rtrefx,rtlinex,phaseid,modelid,blklotid,amenityid,subamenityid,reasonid,
                charges,noprint,agentamt,startwire, endwire, porefx, polinex,cline)

                SELECT stock.trno, stock.line ,ifnull(item.itemid,0) as itemid, stock.uom,stock.whid,stock.loc,stock.loc2,stock.expiry,stock.ref,stock.disc,stock.cost,
                stock.qty,stock.void,stock.rrcost, stock.rrqty, stock.ext, stock.encodeddate,stock.qa,
                stock.encodedby,stock.editdate,stock.editby,stock.sku,stock.refx,stock.linex,stock.isamt,
                stock.amt,stock.isqty,stock.iss ,stock.tstrno,stock.tsline,stock.fcost,stock.rebate,stock.rem,stock.stageid,
                stock.locid,stock.palletid,stock.locid2,stock.palletid2,stock.isextract,stock.pickerid,stock.pickerstart,stock.pickerend,
                stock.whmanid,stock.whmandate,stock.forkliftid,stock.suppid,stock.itemstatus, stock.projectid,stock.sorefx,stock.solinex,stock.sgdrate,stock.poref, 
                stock.podate,stock.isqty2,stock.original_qty,stock.reqtrno,stock.reqline,stock.agentid,stock.kgs,stock.insurance,stock.sortline,stock.freight,stock.invid,stock.expid,stock.iscomponent,isqty3,prevqty,ckrefx,cklinex,ckqa,stock.color,stock.rtrefx,stock.rtlinex,
                stock.phaseid,stock.modelid,stock.blklotid,stock.amenityid,stock.subamenityid,stock.reasonid,stock.charges,
                stock.noprint,stock.agentamt,stock.startwire, stock.endwire, stock.porefx, stock.polinex,stock.cline
                FROM " . $config['docmodule']->stock . " as stock left join item on item.itemid=stock.itemid
                where stock.trno =?";
        break;
    }


    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingrrstatus($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $doc =  $config['params']['doc'];

    $addedfield = ", ifnull(stock.loc,''), ifnull(stock.expiry,''), stock.locid, stock.palletid";
    $otherfields = " , loc,expiry,locid,palletid";
    $condition = " and stock.iss=0";

    if ($companyid == 39) { //cbbsi
      $addedfield = "";
      $condition = "";
      $otherfields = "";
    }

    if ($companyid == 39 && $doc == 'SM') { //cbbsi
      return 1;
    }
    if ($companyid == 59 && ($doc == 'BE' || $doc == 'RE')) { //roosevelt
      return 1;
    }

    if ($companyid == 63 && $doc == 'CH') { //ericco
      return 1;
    }

    $qry = "
        insert into rrstatus(trno,line,clientid,itemid,cost,qty,bal,dateid,whid,uom,disc,docno,cur,forex,receiveddate $otherfields)
        select stock.trno,stock.line,ifnull(client.clientid,0),stock.itemid,stock.cost,stock.qty,stock.qty,head.dateid,stock.whid,stock.uom,stock.disc,head.docno,head.cur,head.forex,head.dateid $addedfield
        from " . $config['docmodule']->head . " as head
        left join " . $config['docmodule']->stock . "  as stock on stock.trno=head.trno
        left join client on client.client=head.client
        where head.trno=? and stock.qty<>0 $condition
  ";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function postingstockinfo($config)
  {
    $companyid = $config['params']['companyid'];
    $add = "";
    $select = "";

    switch ($companyid) {
      case 22: //eipi
        break;
      default:
        $add = ",weight,weight2,itemdesc,unit,consignid,wbdate";
        $select = ",weight,weight2,itemdesc,unit,consignid,wbdate";
        break;
    }

    $trno = $config['params']['trno'];
    $qry = "insert into hstockinfo (trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, 
                                    leaddur, advised, validity,nvat,vatamt,vatex,sramt,pwdamt,discamt,
                                    lessvat,vipdisc,oddisc,smacdisc,empdisc,pickerid,checkerid,status1,
                                    status2,checkstat,qty1,qty2,tqty,paytrno,payrem,isapproved,isbo,ctrlno,
                                    intransit,waivedspecs,channel,banktype,bankrate,terminalid,modepayamt,comm1,comap,cardcharge,comm2,comap2,netap,gcno,prodcycle,pricetype,comrate,ispromo,ispa,isbuy1,isoverride,promoref,overrideby,promoby,promodesc,serialno $add  ) 
            select trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, 
                   validity,nvat,vatamt,vatex,sramt,pwdamt,discamt,lessvat,vipdisc,oddisc,smacdisc,
                   empdisc,pickerid,checkerid,status1,status2,checkstat,qty1,qty2,tqty,paytrno,
                   payrem,isapproved,isbo,ctrlno,intransit,waivedspecs,channel,banktype,bankrate,terminalid,modepayamt,comm1,comap,cardcharge,comm2,comap2,netap,gcno,prodcycle,pricetype,comrate,ispromo,ispa,isbuy1,isoverride,promoref,overrideby,promoby,promodesc,serialno $select 
            from stockinfo where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function postingcntclient($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hcntclient (trno,line,createby,createdate,clientid,rem,ishelper)
                  SELECT trno,line, createby,createdate,clientid,rem,ishelper
                  FROM cntclient 
                  where trno=?";

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function unpostingcntclient($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into cntclient (trno,line,createby,createdate,clientid,rem,ishelper)
                   SELECT trno,line, createby,createdate,clientid,rem,ishelper
                   FROM hcntclient
                   where trno=?";

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function unpostingstockinfo($config)
  {
    $companyid = $config['params']['companyid'];
    $add = "";
    $select = "";

    switch ($companyid) {
      case 22: //eipi
        break;
      default:
        $add = ",weight,weight2,itemdesc,unit,consignid,wbdate";
        $select = ",weight,weight2,itemdesc,unit,consignid,wbdate";
        break;
    }

    $trno = $config['params']['trno'];
    $qry = "insert into stockinfo (trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity,nvat,vatamt,vatex,sramt,pwdamt,discamt,lessvat,vipdisc,oddisc,smacdisc,empdisc,pickerid,checkerid,status1,status2,checkstat,qty1,qty2,tqty,paytrno,payrem,isapproved,isbo,ctrlno,intransit,waivedspecs,channel,banktype,bankrate,terminalid,modepayamt,comm1,comap,cardcharge,comm2,comap2,netap,gcno,prodcycle,pricetype,comrate,ispromo,ispa,isbuy1,isoverride,promoref,overrideby,promoby,promodesc,serialno $add  ) 
            select trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity,nvat,vatamt,vatex,sramt,pwdamt,discamt,lessvat,vipdisc,oddisc,smacdisc,empdisc,pickerid,checkerid,status1,status2,checkstat,qty1,qty2,tqty,paytrno,payrem,isapproved,isbo,ctrlno,intransit,waivedspecs,channel,banktype,bankrate,terminalid,modepayamt,comm1,comap,cardcharge,comm2,comap2,netap,gcno,prodcycle,pricetype,comrate,ispromo,ispa,isbuy1,isoverride,promoref,overrideby,promoby,promodesc,serialno $select   from hstockinfo where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingstockinfotrans($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hstockinfotrans (trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity, itemdesc, itemdesc2, unit, purpose, requestorname, dateneeded, specs, specs2, otherleadtime, durationid, sono, ovaliddate, leadtimesettings, isvalid, deadline,customercur,vendorcur,vendorcostprice,quantity,freight,markup,exchangerate,ctrlno, isasset,waivedqty,uom2,uom3,prevamt,prevdate) 
            select trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity, itemdesc, itemdesc2, unit, purpose, requestorname, dateneeded, specs, specs2, otherleadtime, durationid, sono, ovaliddate, leadtimesettings, isvalid, deadline,customercur,vendorcur,vendorcostprice,quantity,freight,markup,exchangerate,ctrlno, isasset,waivedqty,uom2,uom3,prevamt,prevdate from stockinfotrans where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function unpostingstockinfotrans($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into stockinfotrans (trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity, itemdesc, itemdesc2, unit, purpose, requestorname, dateneeded, specs, specs2, durationid, sono, ovaliddate, leadtimesettings, isvalid, deadline,customercur,vendorcur,vendorcostprice,quantity,freight,markup,exchangerate,ctrlno, isasset,waivedqty,uom2,uom3,prevamt,prevdate) 
            select trno, line, rem, amt1, amt2, amt3, amt4, amt5, leadfrom, leadto, leaddur, advised, validity, itemdesc, itemdesc2, unit, purpose, requestorname, dateneeded, specs, specs2, durationid, sono, ovaliddate, leadtimesettings, isvalid, deadline,customercur,vendorcur,vendorcostprice,quantity,freight,markup,exchangerate,ctrlno, isasset,waivedqty,uom2,uom3,prevamt,prevdate from hstockinfotrans where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingdetail($config)
  {
    $trno = $config['params']['trno'];
    if ($config['params']['companyid'] == 21) $this->logger->sbcwritelog($trno, $config, 'POST', 'Posting detail'); //Kinggeorge

    $qry = "
      insert into " . $config['docmodule']->hdetail . " (postdate,trno,line,acnoid,clientid,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
      editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid,
      poref, podate, agentid,storetrno,station,qttrno,lastdp,sortline,isexcept,phaseid,modelid,blklotid,type,isexcess,appamt, amenityid, subamenityid, dpref)
      select d.postdate,d.trno,d.line,d.acnoid,
      ifNull(client.clientid,0),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
      d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
      d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid,
      d.poref, d.podate, d.agentid,d.storetrno,d.station,d.qttrno,d.lastdp,d.sortline,d.isexcept,d.phaseid,d.modelid,d.blklotid,d.type,d.isexcess,d.appamt, d.amenityid, d.subamenityid, d.dpref
      from " . $config['docmodule']->head . " as h
      left join " . $config['docmodule']->detail . " as d on d.trno=h.trno
      left join client on client.client=d.client
      where  d.trno=?  ";

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingvoiddetail($config)
  {
    $trno = $config['params']['trno'];
    $qry = "
      insert into hvoiddetail (postdate,trno,line,acnoid,clientid,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
      editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid)
      select d.postdate,d.trno,d.line,d.acnoid,
      ifNull(client.clientid,0),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
      d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
      d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid
      from " . $config['docmodule']->head . " as h
      left join voiddetail as d on d.trno=h.trno
      left join client on client.client=d.client
      where  d.trno=?
  ";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function postingdetailinfo($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hdetailinfo (trno, line, rem, ref, si1, si2,fi,mri,interest,principal,lotbal,housebal,hlbal,ortrno,checkno,payment,principalcol,percentage,paymentdate) 
            select trno, line, rem, ref, si1, si2,fi,mri,interest,principal,lotbal,housebal,hlbal,ortrno,checkno,payment,principalcol,percentage,paymentdate from detailinfo where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingparticulars($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hparticulars (trno, line, rem,amount,createby,createdate,editby,editdate,station,serial,remarks,others) 
            select trno, line, rem,amount,createby,createdate,editby,editdate,station,serial,remarks,others from particulars where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function unpostingparticulars($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into particulars (trno, line, rem,amount,createby,createdate,editby,editdate,station,serial,remarks,others) 
            select trno, line, rem,amount,createby,createdate,editby,editdate,station,serial,remarks,others from hparticulars where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function postingpvitem($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hpvitem (trno, line, refx,linex,itemid,poref,ref,amt,createby,createdate,editby,editdate) 
            select trno, line, refx,linex,itemid,poref,ref,amt,createby,createdate,editby,editdate from pvitem where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function unpostingpvitem($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into pvitem (trno, line, refx,linex,itemid,poref,ref,amt,createby,createdate,editby,editdate) 
    select trno, line, refx,linex,itemid,poref,ref,amt,createby,createdate,editby,editdate from hpvitem where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  private function unpostingdetailinfo($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into detailinfo (trno, line, rem, ref, si1, si2,fi,mri,interest,principal,lotbal,housebal,hlbal,ortrno,checkno,payment,principalcol,percentage,paymentdate) 
            select trno, line, rem, ref, si1, si2,fi,mri,interest,principal,lotbal,housebal,hlbal,ortrno,checkno,payment,principalcol,percentage,paymentdate from hdetailinfo where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingapledger($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    if ($doc == 'ER') {
      $qry = "
      insert into apledger(dateid,trno,line,acnoid,clientid,db,cr,bal,fdb,fcr,docno,ref,cur,forex)
      select d.postdate,d.trno,line,d.acnoid,ifNull(head.clientid,0),round(db,2),
      round(cr,2),round(db,2)+round(cr,2) as bal,d.fdb,d.fcr,head.docno,d.ref,d.cur,d.forex
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      where left(coa.alias,2)='AP' and d.trno=? and d.refx=0
      ";
    } else {
      $qry = "
      insert into apledger(dateid,trno,line,acnoid,clientid,db,cr,bal,fdb,fcr,docno,ref,cur,forex)
      select d.postdate,d.trno,line,d.acnoid,ifNull(client.clientid,0),round(db,2),
      round(cr,2),round(db,2)+round(cr,2) as bal,d.fdb,d.fcr,head.docno,d.ref,d.cur,d.forex
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='AP' and d.trno=? and d.refx=0
      ";
    }

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingarledger($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];


    switch ($doc) {
      case 'ER':
        $qry = "
        insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
        select d.postdate,d.trno,line,coa.acnoid,ifNull(head.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
        head.docno,d.ref,ifnull(head.agentid,0),d.fdb,d.fcr,d.forex
        from " . $config['docmodule']->head . " as head
        left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
        left join coa on coa.acnoid=d.acnoid
        where left(coa.alias,2)='AR' and d.trno=? and d.refx=0
        ";
        break;
      case 'PB':
        $qry = "
        insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
        select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
        head.docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex
        from " . $config['docmodule']->head . " as head
        left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
        left join coa on coa.acnoid=d.acnoid
        left join client on client.client=head.client
        left join client as agent on agent.client=head.agent
        where left(coa.alias,2)='AR' and d.trno=? and d.linex=0
        ";
        break;
      case 'AR':
        $qry = "
        insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
        select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
        head.docno,d.ref,d.agentid,d.fdb,d.fcr,d.forex
        from " . $config['docmodule']->head . " as head
        left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
        left join coa on coa.acnoid=d.acnoid
        left join client on client.client=d.client
        left join client as agent on agent.client=d.agent
        where left(coa.alias,2)='AR' and d.trno=? and d.refx=0
        ";
        break;
      case 'FS':
        $qry = "
          insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
          select head.dateid,d.trno,case coa.alias when 'ARRF' then 1 when 'ARDP' then 2 else 3 end as line,coa.acnoid,ifNull(client.clientid,0),round(sum(d.db),2),round(sum(d.cr),2),round(sum(d.db+d.cr),2) as bal,
          head.docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex
          from " . $config['docmodule']->head . " as head
          left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
          left join coa on coa.acnoid=d.acnoid
          left join client on client.client=d.client
          left join client as agent on agent.client=head.agent
          where left(coa.alias,2)='AR' and d.trno=? and d.refx=0 group by head.dateid,d.trno,coa.acnoid,ifNull(client.clientid,0),
          head.docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex,coa.alias
          ";
        break;
      default:
        $qry = "
          insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
          select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
          head.docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex
          from " . $config['docmodule']->head . " as head
          left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
          left join coa on coa.acnoid=d.acnoid
          left join client on client.client=d.client
          left join client as agent on agent.client=head.agent
          where left(coa.alias,2)='AR' and d.trno=? and d.refx=0
          ";
        if ($config['params']['companyid'] == 55) { //afli
          if ($doc == 'CV') {
            $qry = "
          insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
          select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
          case  when left(d.ref,2) in ('LE','HL','VL','SL') then d.ref else head.docno end as docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex
          from " . $config['docmodule']->head . " as head
          left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
          left join coa on coa.acnoid=d.acnoid
          left join client on client.client=d.client
          left join client as agent on agent.client=head.agent
          where left(coa.alias,2)='AR' and d.trno=? and d.refx=0
          ";
          }
        }

        break;
    }

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }


  public function postingcrledger($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    if ($doc == 'ER') {
      $qry = "
      insert into crledger(checkdate,trno,line,acnoid,clientid,db,cr,docno,checkno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(head.clientid,0),round(d.db,2),round(d.cr,2),head.docno,d.checkno
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      where left(coa.alias,2)='CR' and d.trno=? and d.refx=0
      ";
    } else {
      $qry = "
      insert into crledger(checkdate,trno,line,acnoid,clientid,db,cr,docno,checkno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),head.docno,d.checkno
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='CR' and d.trno=? and d.refx=0
      ";
    }
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingcaledger($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    if ($doc == 'ER') {
      $qry = "
      insert into caledger(dateid,trno,line,acnoid,clientid,db,cr,docno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(head.clientid,0),round(d.db,2),round(d.cr,2),head.docno
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      where left(coa.alias,2)='CA' and d.trno=? and d.refx=0
      ";
    } else {
      $qry = "
      insert into caledger(dateid,trno,line,acnoid,clientid,db,cr,docno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),head.docno
      from " . $config['docmodule']->head . " as head
      left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='CA' and d.trno=? and d.refx=0
      ";
    }
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }


  public function postingcbledger($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    if ($doc == 'ER') {
      $qry = "insert into cbledger(checkdate,trno,line,acnoid,clientid,db,cr,docno,checkno)
            select d.postdate,d.trno,line,coa.acnoid,ifNull(head.clientid,0),round(d.db,2),round(d.cr,2),head.docno,d.checkno
            from " . $config['docmodule']->head . " as head
            left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
            left join coa on coa.acnoid=d.acnoid
            where left(coa.alias,2)='CB' and d.trno=? and d.refx=0
            ";
    } else {
      $qry = "insert into cbledger(checkdate,trno,line,acnoid,clientid,db,cr,docno,checkno)
            select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),head.docno,d.checkno
            from " . $config['docmodule']->head . " as head
            left join " . $config['docmodule']->detail . " as d on head.trno=d.trno
            left join coa on coa.acnoid=d.acnoid
            left join client on client.client=d.client
            where left(coa.alias,2)='CB' and d.trno=? and d.refx=0
            ";
    }
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }


  public function hasbeendeposited($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('crledger', 'trno', 'trno=? and depodate is not null', [$trno]);
    $b = $this->coreFunctions->getfieldvalue('caledger', 'trno', 'trno=? and depodate is not null', [$trno]);
    if ($a !== '' || $b !== '') {
      return 'This Transaction cannot be UNPOSTED, It has already been DEPOSITED ';
    } else {
      return '';
    }
  }


  public function hasbeenreleased($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('cbledger', 'trno', 'trno=? and releasedate is not null and releaseby is not null', [$trno]);

    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Check has already been RELEASED ';
    } else {
      return '';
    }
  }

  public function hasbeenreconstruct($config)
  {
    $trno = $config['params']['trno'];

    $b = $this->coreFunctions->getfieldvalue('gldetail', 'group_concat(distinct refx)', 'trno=? and refx<>0', [$trno]);

    $a = $this->coreFunctions->datareader('select refrecon as value from cntnum where trno in (' . $b . ')');

    $this->coreFunctions->LogConsole($a);

    if (floatval($a) <> 0) {
      return 'This Transaction cannot be UNPOSTED, reference already reconstructed. ';
    } else {
      return '';
    }
  }

  public function hasbeenitemissue($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('rrstatus', 'trno', 'trno=? and bal<>qty', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, It has already been SERVED';
    } else {
      return '';
    }
  }

  public function hasbeenitemreturn($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $status = 'RETURNED';
    if ($companyid == 48) { //seastar
      $status = 'LOADED';
    }

    $msg = 'This Transaction cannot be UNPOSTED, It has already been ' . $status;
    $a = $this->coreFunctions->getfieldvalue('glstock', 'trno', 'trno=? and qa<>0', [$trno]);
    if ($a !== '') {
      return $msg;
    } else {
      $a = $this->coreFunctions->getfieldvalue('rrstatus', 'trno', 'trno=? and qa<>0', [$trno]);
      if ($a !== '') {
        return $msg;
      } else {
        return '';
      }
    }
  }

  public function hasbeenserialout($config)
  {
    $trno = $config['params']['trno'];
    $msg = 'This Transaction cannot be UNPOSTED, some serials already been issued';
    $a = $this->coreFunctions->getfieldvalue('serialin', 'serial', 'trno=? and outline<>0', [$trno]);
    if ($a !== '') {
      return $msg;
    } else {
      return '';
    }
  }

  public function hasbeeninvoice($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('cntnum', 'trno', 'trno=? and svnum<>0', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, An INVOICE has already been Issued';
    } else {
      return '';
    }
  }

  public function hasbeenapv($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('glhead', 'trno', 'trno=? and pvtrno<>0', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, A PAYABLE VOUCHER has already been created';
    } else {
      return '';
    }
  }

  public function hasbeencountered($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('arledger', 'trno', 'trno=? and kr<>0', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, A COUNTER RECEIPT has already been Issued';
    } else {
      return '';
    }
  }

  public function hasbeenappaid($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('apledger', 'trno', 'trno=? and bal<>abs(db+cr)', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Already have a payment';
    } else {
      return '';
    }
  }

  public function hasbeenarpaid($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('arledger', 'trno', 'trno=? and bal<>abs(db+cr)', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Already have a payment';
    } else {
      return '';
    }
  }

  public function hasbeenmcpaid($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('gldetail', 'trno', 'trno=? and mctrno <>0', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Already have a MC Collection';
    } else {
      return '';
    }
  }


  public function hasbeenvalidatedreplenish($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('replenishstock', 'trno', 'trno=? and validate is not null', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, The location was already validated';
    } else {
      return '';
    }
  }


  public function unpostinghead($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];

    $addedfield = "";
    $selectaddedfield = "";

    switch ($systemtype) {
      case 'REALESTATE':
        $addedfield = " ,modelid, phaseid, blklotid,rctrno,rcline, amenityid, subamenityid";
        $selectaddedfield = " ,modelid, phaseid, blklotid,rctrno,rcline, amenityid, subamenityid";
        break;

      case 'LENDING':
        $addedfield = " ,rctrno,rcline,purposeid";
        $selectaddedfield = " ,rctrno,rcline,purposeid";
        break;
      case 'BMS':
        $addedfield = " ,contact,bstype,ownername,ownertype,owneraddr";
        $selectaddedfield = " ,head.contact,head.bstype,head.ownername,head.ownertype,head.owneraddr";
        break;
    }

    switch ($config['params']['companyid']) {
      case 19: //housegem
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ", driver, plateno";
            $selectaddedfield = ", head.driver,head.plateno";
            break;
        }
        break;
      case 10: //afti
      case 12: //afti usd
        switch ($config['params']['doc']) {
          case 'AI':
            $addedfield = ", sotrno";
            $selectaddedfield = ", head.sotrno";
            break;
          case 'CV':
            $addedfield = ",isencashment, isonlineencashment";
            $selectaddedfield = ",head.isencashment, head.isonlineencashment";
            break;
        }
        break;
      case 8: //maxipro
        switch ($config['params']['doc']) {
          case 'CV':
            $addedfield = ",paymode,hacno,hacnoname";
            $selectaddedfield = ",head.paymode,head.hacno,head.hacnoname";
            break;
          case 'PB':
            $addedfield = ",rem2";
            $selectaddedfield = ",head.rem2";
            break;
          case 'MT':
            $addedfield = ",rqtrno";
            $selectaddedfield = ",head.rqtrno";
            break;
        }
        break;
      case 16: //ati
        switch ($config['params']['doc']) {
          case 'PR':
            $addedfield = ",svsno, pono";
            $selectaddedfield = ",head.svsno, head.pono";
            break;
          case 'RM':
            $addedfield = ",pdtrno, stageid";
            $selectaddedfield = ",head.pdtrno, head.stageid";
            break;
          case 'FG':
            $addedfield = ",pdtrno,stageid";
            $selectaddedfield = ",head.pdtrno,head.stageid";
            break;
        }
        break;
      case 26: //bee healthy
        switch ($config['params']['doc']) {
          case 'CV':
            $addedfield = ",costcodeid";
            $selectaddedfield = ",head.costcodeid";
            break;
        }
        break;
      case 24: //goodfound
        switch ($config['params']['doc']) {
          case 'SJ':
            $addedfield = ", driver";
            $selectaddedfield = ", head.driver";
            break;
        }
        break;
      case 40: //cdo
        switch ($config['params']['doc']) {
          case 'MJ':
            $addedfield = ",modeofsales";
            $selectaddedfield = ", head.modeofsales";
            break;
          case 'CI':
            $addedfield = ",rem2";
            $selectaddedfield = ",head.rem2";
            break;
        }
        break;
      case 48: //SEASTAR
        switch ($config['params']['doc']) {
          case 'SJ':
            $addedfield = ",consigneeid,shipperid,conaddr,whto";
            $selectaddedfield = ",head.consigneeid,head.shipperid,head.conaddr,head.whto";
            break;
        }
        break;
      case 47: //kstar
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ",freight,agentfee";
            $selectaddedfield = ",head.freight,head.agentfee";
            break;
        }
        break;

      case 56: //homeworksa
        switch ($config['params']['doc']) {
          case 'RR':
            $addedfield = ", longitude";
            $selectaddedfield = ", head.longitude";
            break;
        }
        break;
      case 59: //roosevelt
        switch ($config['params']['doc']) {
          case 'RR';
            $addedfield = ", ied, bankcharges, interest, brokerfee, arrastre";
            $selectaddedfield = ", head.ied, head.bankcharges, head.interest, head.brokerfee, head.arrastre";
            break;
          case 'SJ':
            $addedfield = ", bpo, ctnsno";
            $selectaddedfield = ", head.bpo, head.ctnsno";
            break;
        }
        break;
      case 60: //transpower
        switch ($config['params']['doc']) {
          case 'SJ':
            $addedfield = ",cmtrno";
            $selectaddedfield = ",head.cmtrno";
            break;
        }
        break;
      default:
        switch ($config['params']['doc']) {
          case 'RM':
            $addedfield = ", pdtrno, stageid";
            $selectaddedfield = ", head.pdtrno, head.stageid";
            break;
          case 'FG':
            $addedfield = ",pdtrno,stageid";
            $selectaddedfield = ",head.pdtrno,head.stageid";
            break;
        }
        break;
    }

    $add = "";
    $select = "";
    if ($config['params']['companyid'] != 22) { //NOT EIPI
      $add = ",ajtrno";
      $select = ",head.ajtrno";
    }

    $qry = "insert into " . $config['docmodule']->head . "(
                  trno,doc,docno,client,clientname,address,shipto,dateid,terms,wh,rem,forex,yourref,ourref,
                  contra,agent,tax,createdate,createby,editdate,editby,lockuser,lockdate,viewdate,
                  viewby,modeofpayment,acctname,acctno,cardtype,waybilldate,billlading,voyage,due,cur,vattype,salestype,
                  checked,trpricegrp,trroute,project,cmtrans,pickby,checkby,gm_purchasetype,ms_arastre,
                  ms_freight,ms_wharffage,picker,checker,ewt,ewtrate,mlcp_jonum,mlcp_freight,
                  invoiceno,invoicedate,uv_transtype,deptid,creditinfo,crline,overdue,projectid,subproject,
                  pltrno,deliverytype,customername,projectto,subprojectto,partreqtypeid,waybill,brtrno,shipid,
                  billid,tel,branch,statid,taxdef,shipcontactid,billcontactid,qttrno,whref, sano, pono, 
                  deldate, crref, returndate,refunddate,sdate1,sdate2,empid,excess,excessrate,aftrno,
                  checkno,checkdate,amount,refdate,istrip,voiddate,voidby,orderno,strdate1,strdate2,
                  trnxtype,cur2, forex2,fpid,crno, rfno,chsino,swsno,cotrno,petrno,ista,layref,
                  isfa,isnoentry,rrfactor " . $add . $addedfield  . ")
            select head.trno,head.doc, head.docno, ifnull(client.client,'') as client, head.clientname,
                  head.address, head.shipto, head.dateid, head.terms, ifnull(warehouse.client,'') as wh, head.rem, head.forex,
                  head.yourref, head.ourref, head.contra, ifNull(agent.client,'') as agent, head.tax , head.createdate,head.createby,
                  head.editdate, head.editby, head.lockuser, head.lockdate, head.viewdate, head.viewby,head.modeofpayment,
                  head.acctname,head.acctno,head.cardtype,head.waybilldate,head.billlading,head.voyage,head.due,head.cur,
                  head.vattype,head.salestype,head.checked,head.trpricegrp,head.trroute,head.project,head.cmtrans,
                  head.pickby,head.checkby,head.gm_purchasetype,head.ms_arastre,head.ms_freight,
                  head.ms_wharffage,head.picker,head.checker,head.ewt,head.ewtrate,head.mlcp_jonum,
                  head.mlcp_freight,head.invoiceno,head.invoicedate,head.uv_transtype,head.deptid,
                  head.creditinfo,head.crline,head.overdue,head.projectid,head.subproject,head.pltrno,
                  head.deliverytype, head.customername ,head.projectto,head.subprojectto,head.partreqtypeid,
                  head.waybill,head.brtrno,head.shipid,head.billid,head.tel,head.branch,head.statid,
                  head.taxdef,head.shipcontactid,head.billcontactid,head.qttrno,head.whref, head.sano, 
                  head.pono, head.deldate, head.crref, head.returndate,head.refunddate,head.sdate1,
                  head.sdate2,head.empid,head.excess,head.excessrate,head.aftrno,head.checkno,
                  head.checkdate,head.amount,head.refdate,head.istrip,head.voiddate,head.voidby,
                  head.orderno,head.strdate1,head.strdate2,head.trnxtype,head.cur2,head.forex2,
                  head.fpid,head.crno, head.rfno,head.chsino,head.swsno,head.cotrno,head.petrno,
                  head.ista,head.layref,head.isfa,head.isnoentry,head.rrfactor " . $select . $selectaddedfield  . "
            from glhead as head left join cntnum on cntnum.trno=head.trno
            left join client  on head.clientid=client.clientid
            left join client  as warehouse on head.whid=warehouse.clientid
            left join client  as agent on head.agentid=agent.clientid
            where head.trno=? limit 1";

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }


  public function unpostingstock($config)
  {
    $trno = $config['params']['trno'];

    switch ($config['params']['companyid']) {
      case 39: //cbbsi
        if ($config['params']['doc'] == 'SM') {
          $qry = "insert into " . $config['docmodule']->stock . "(trno,line,itemid,rrqty,qty,uom,disc,rrcost,cost,lastcost,ext,charges,wh,whid,refx,linex,ref)
                  SELECT stock.trno, stock.line, item.itemid,stock.rrqty,stock.qty,stock.uom,stock.disc,stock.rrcost,stock.cost,stock.lastcost,stock.ext,stock.charges,
                  stock.wh,stock.whid,stock.refx,stock.linex,stock.ref
                  FROM hsnstock as stock left join item on item.itemid=stock.itemid
                  where stock.trno =?";
        } else {
          goto defaultqry;
        }
        break;
      case 59: //roosevelt
        if ($config['params']['doc'] == 'BE' || $config['params']['doc'] == 'RE') {
          $qry = "insert into " . $config['docmodule']->stock . "(trno,line,checkno,amount,editby,editdate,
          refx,linex,rctrno,rcline,acnoid,rcchecks,rem,bank,branch,checkdate,clientid)
                  SELECT trno, line, checkno,amount,editby,editdate,refx,linex,rctrno,rcline,acnoid,rcchecks,rem,
                  bank,branch,checkdate,clientid
                  FROM " . $config['docmodule']->hstock . " 
                  where trno =?";
        } else {
          goto defaultqry;
        }
        break;
      case 63: //ericco
        if ($config['params']['doc'] == 'CH') {
          $qry = "insert into " . $config['docmodule']->stock . "(trno,line,itemid,isqty,iss,uom,isamt,amt,ext,whid,rem,qa,void,sortline,noprint,encodeddate,encodedby,editdate,editby)
                  SELECT trno,line,itemid,isqty,iss,uom,isamt,amt,ext,whid,rem,qa,void,sortline,noprint,encodeddate,encodedby,editdate,editby
                  FROM " . $config['docmodule']->hstock . " as stock 
                  where stock.trno =?";
        } else {
          goto defaultqry;
        }
        break;
      default:
        defaultqry:
        $qry = "insert into " . $config['docmodule']->stock . "(trno,line,refx,linex ,itemid,uom,whid,loc,loc2,expiry,
                disc,cost,qty,rrcost,rrqty,ext,isqty,iss,amt,isamt,qa,ref,encodeddate,encodedby,editdate,editby,
                rem,comm,icomm,tstrno,tsline,iss2,isqty2,iscomponent,outputid,msako,tsako,itemhandling,itemcomm,
                agent,kgs,isfromjo,fcost,rebate,stageid,palletid,locid,palletid2,locid2,isextract,pickerid,pickerstart,pickerend,whmanid,whmandate,forkliftid,suppid,itemstatus, projectid,sorefx,solinex,sgdrate,
                poref, podate,original_qty,reqtrno,reqline,agentid,insurance,sortline,freight,invid,expid,isqty3,prevqty,color,rtrefx,rtlinex,
                phaseid,modelid,blklotid,amenityid,subamenityid,reasonid,charges,noprint,agentamt,startwire, endwire, porefx, polinex,cline)
                SELECT stock.trno, stock.line, stock.refx, stock.linex ,ifnull(item.itemid,0) as itemid,stock.uom, stock.whid,stock.loc,stock.loc2,stock.expiry,
                stock.disc, stock.cost, stock.qty, stock.rrcost, stock.rrqty, stock.ext, stock.isqty, stock.iss, stock.amt,
                stock.isamt, stock.qa, stock.ref, encodeddate, encodedby, stock.editdate,stock.editby,stock.rem,stock.comm,stock.icomm,stock.tstrno,stock.tsline,
                stock.iss2,stock.isqty2,stock.iscomponent,stock.outputid,stock.msako,stock.tsako, stock.itemhandling,stock.itemcomm,
                ifnull(agent.client,'') as agent,stock.kgs,stock.isfromjo,stock.fcost,stock.rebate,stock.stageid,stock.palletid,stock.locid,stock.palletid2,stock.locid2,
                stock.isextract,stock.pickerid,stock.pickerstart,stock.pickerend,stock.whmanid,stock.whmandate,stock.forkliftid,stock.suppid,stock.itemstatus, stock.projectid,stock.sorefx,stock.solinex,stock.sgdrate,
                stock.poref, stock.podate,stock.original_qty,stock.reqtrno,stock.reqline,stock.agentid,stock.insurance,stock.sortline,stock.freight,stock.invid,stock.expid,isqty3,prevqty,stock.color,stock.rtrefx,stock.rtlinex,
                stock.phaseid,stock.modelid,stock.blklotid,stock.amenityid,stock.subamenityid,stock.reasonid,stock.charges,
                stock.noprint,stock.agentamt,stock.startwire, stock.endwire, stock.porefx, stock.polinex,stock.cline
                FROM glstock as stock
                left join item on item.itemid=stock.itemid
                left join client on client.clientid=stock.whid
                left join client as agent on agent.clientid=stock.agentid
                where trno =? and tstrno=0 ";
        break;
    }

    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function unpostingdetail($config)
  {
    $trno = $config['params']['trno'];
    $qry = "
    insert into " . $config['docmodule']->detail . "
    (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,
    ref,encodeddate,encodedby,editdate,editby,checkno,rem,clearday,pdcline,projectid,
    isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void, 
    poref, podate, agentid,storetrno,station,qttrno,lastdp,deptid,branch,sortline,isexcept,phaseid,modelid,blklotid,type,isexcess,appamt, amenityid, subamenityid, dpref)
    select d.postdate, d.trno, d.line, d.acnoid, ifNull(client.client,''),
    d.db, d.cr, d.fdb, d.fcr, d.refx, d.linex, d.ref, d.encodeddate, d.encodedby,
    d.editdate, d.editby,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
    d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void, 
    d.poref, d.podate, d.agentid,d.storetrno,d.station,d.qttrno,d.lastdp,d.deptid,d.branch,d.sortline,d.isexcept,d.phaseid,d.modelid,d.blklotid,d.type,d.isexcess,d.appamt, d.amenityid, d.subamenityid, d.dpref
    from glhead as h
    left join gldetail as d on h.trno=d.trno
    left join client on d.clientid=client.clientid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
  ";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function unpostingvoiddetail($config)
  {
    $trno = $config['params']['trno'];
    $qry = "
    insert into voiddetail
    (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,
    ref,encodeddate,encodedby,editdate,editby,checkno,rem,clearday,pdcline,projectid,
    isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void)
    select d.postdate, d.trno, d.line, d.acnoid, ifNull(client.client,''),
    d.db, d.cr, d.fdb, d.fcr, d.refx, d.linex, d.ref, d.encodeddate, d.encodedby,
    d.editdate, d.editby,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
    d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void
    from glhead as h
    left join hvoiddetail as d on h.trno=d.trno
    left join client on d.clientid=client.clientid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
  ";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }


  public function computecosting($itemid, $whid, $loc, $expiry, $trno, $line, $qty, $doc, $companyid)
  {
    return $this->commonsbc->computecosting($itemid, $whid, $loc, $expiry, $trno, $line, $qty, $doc, $companyid);
  } //end compute costing

  public function computecostingmi($itemid, $whid, $loc, $expiry, $trno, $line, $refx, $linex, $qty, $doc)
  {
    return $this->commonsbc->computecostingmi($itemid, $whid, $loc, $expiry, $trno, $line, $refx, $linex, $qty, $doc);
  } //end compute costingmi

  public function computecostingpallet($itemid, $whid, $locid, $palletid, $trno, $line, $qty, $doc, $params)
  {
    return $this->commonsbc->computecostingpallet($itemid, $whid, $locid, $palletid, $trno, $line, $qty, $doc, $params);
  } //end compute costingpallet

  public function computecostingserial($itemid, $whid,  $trno, $line, $qty, $doc, $rrref, $sline, $loc)
  {
    return $this->commonsbc->computecostingserial($itemid, $whid, $trno, $line, $qty, $doc, $rrref, $sline, $loc);
  } //end compute costing

  public function upsertdetail($acctg, $params, $config)
  {
    if (empty($params)) {
      $this->logConsole('empty params.');
      return $acctg;
    }

    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
    } else {
      $trno = $config['params']['trno'];
    }

    $q = [];
    $line = 0;

    if (isset($params['line'])) {
      $line = $params['line'];
    } else {
      if (!empty($acctg)) {
        $q = collect($acctg)->sortByDesc('line');
        $line = $q[count($q) - 1]['line'] + 1;
      } else {
        $line = 1;
      }
    }
    $stageid = 0;
    $subproject = 0;
    $projectid = 0;
    $deptid = 0;
    $branch = 0;
    $poref = '';
    $podate = '';

    //for pos extraction
    $storetrno = 0;
    $station = '';
    $ref = '';
    $refx = 0;
    $linex = 0;
    $ewtcode = '';
    $ewtrate = '';
    $isewt = 0;
    $isvat = 0;
    $rem = '';

    //realestate
    $phaseid = 0;
    $modelid = 0;
    $blklotid = 0;
    $amenityid = 0;
    $subamenityid = 0;


    if (isset($params['subproject'])) {
      $subproject = $params['subproject'];
    }

    if (isset($params['projectid'])) {
      $projectid = $params['projectid'];
    }

    if (isset($params['stageid'])) {
      $stageid = $params['stageid'];
    }

    if (isset($params['branch'])) {
      $branch = $params['branch'];
    }

    if (isset($params['deptid'])) {
      $deptid = $params['deptid'];
    }

    if (isset($params['poref'])) {
      $poref = $params['poref'];
    }

    if (isset($params['podate'])) {
      $podate = $params['podate'];
    }

    if (isset($params['storetrno'])) {
      $storetrno = $params['storetrno'];
    }

    if (isset($params['station'])) {
      $station = $params['station'];
    }

    if (isset($params['ref'])) {
      $ref = $params['ref'];
    }

    if (isset($params['refx'])) {
      $refx = $params['refx'];
    }

    if (isset($params['linex'])) {
      $linex = $params['linex'];
    }

    if (isset($params['ewtcode'])) {
      $ewtcode = $params['ewtcode'];
    }

    if (isset($params['ewtrate'])) {
      $ewtrate = $params['ewtrate'];
    }

    if (isset($params['isewt'])) {
      $isewt = $params['isewt'];
    }

    if (isset($params['isvat'])) {
      $isvat = $params['isvat'];
    }

    if (isset($params['rem'])) {
      $rem = $params['rem'];
    }

    if ($systype == 'REALESTATE') {
      if (isset($params['phaseid'])) {
        $phaseid = $params['phaseid'];
      }

      if (isset($params['modelid'])) {
        $modelid = $params['modelid'];
      }

      if (isset($params['blklotid'])) {
        $blklotid = $params['blklotid'];
      }

      if (isset($params['amenityid'])) {
        $amenityid = $params['amenityid'];
      }

      if (isset($params['subamenityid'])) {
        $subamenityid = $params['subamenityid'];
      }
    }

    $isExist = false;

    foreach ($acctg as $key => $value) {

      if (floatval($params['db']) != 0) {
        switch ($companyid) {
          case 10: //afti
            if ($doc == 'PV' or $doc == 'CV') {
              if (
                $acctg[$key]['acnoid'] == $params['acnoid'] && $acctg[$key]['db'] <> 0 &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['postdate'] == $params['postdate'] && $acctg[$key]['projectid'] == $projectid
                && $acctg[$key]['subproject'] == $subproject && $acctg[$key]['stageid'] == $stageid && $acctg[$key]['branch'] == $branch && $acctg[$key]['deptid'] == $deptid && $acctg[$key]['ewtcode'] == $ewtcode
              ) {
                $acctg[$key]['db'] += $params['db'];
                $acctg[$key]['cr'] += $params['cr'];
                if (isset($params['fdb'])) {
                  $acctg[$key]['fdb'] += $params['fdb'];
                  $acctg[$key]['fcr'] += $params['fcr'];
                }
                $isExist = true;
              }
            } else {
              goto defaultconditiondb;
            }
            break;

          case 59: //roosevelt
            if ($doc == 'BE' || $doc == 'RE') {
              if ($acctg[$key]['acnoid'] == $params['acnoid'] &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['checkno'] == $params['checkno'] && $acctg[$key]['db'] <> 0 && $acctg[$key]['postdate'] == $params['postdate']) {
                $acctg[$key]['db'] += $params['db'];
                $acctg[$key]['cr'] += $params['cr'];
                if (isset($params['fdb'])) {
                  $acctg[$key]['fdb'] += $params['fdb'];
                  $acctg[$key]['fcr'] += $params['fcr'];
                }
                $isExist = true;
              }
            } else {
              goto defaultconditiondb;
            }
            break;
          case 55: //afli
            if ($doc == 'CV') {
              if (
                $acctg[$key]['acnoid'] == $params['acnoid'] &&  $acctg[$key]['db'] <> 0 &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['postdate'] == $params['postdate'] && $acctg[$key]['projectid'] == $projectid
                && $acctg[$key]['rem'] == $rem  && $acctg[$key]['subproject'] == $subproject && $acctg[$key]['stageid'] == $stageid && $acctg[$key]['branch'] == $branch && $acctg[$key]['deptid'] == $deptid && $acctg[$key]['refx'] == $refx && $acctg[$key]['linex'] == $linex
              ) {
                $acctg[$key]['db'] += $params['db'];
                $acctg[$key]['cr'] += $params['cr'];
                if (isset($params['fdb'])) {
                  $acctg[$key]['fdb'] += $params['fdb'];
                  $acctg[$key]['fcr'] += $params['fcr'];
                }
                $isExist = true;
              }
            } else {
              goto defaultconditiondb;
            }

            break;


          default:
            defaultconditiondb:
            if (
              $acctg[$key]['acnoid'] == $params['acnoid'] &&  $acctg[$key]['db'] <> 0 &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['postdate'] == $params['postdate'] && $acctg[$key]['projectid'] == $projectid
              && $acctg[$key]['subproject'] == $subproject && $acctg[$key]['stageid'] == $stageid && $acctg[$key]['branch'] == $branch && $acctg[$key]['deptid'] == $deptid && $acctg[$key]['refx'] == $refx && $acctg[$key]['linex'] == $linex
            ) {
              $acctg[$key]['db'] += $params['db'];
              $acctg[$key]['cr'] += $params['cr'];
              if (isset($params['fdb'])) {
                $acctg[$key]['fdb'] += $params['fdb'];
                $acctg[$key]['fcr'] += $params['fcr'];
              }
              $isExist = true;
            }
            break;
        }
      } else {
        switch ($companyid) {
          case 10: //afti
            if ($doc == 'PV' or $doc == 'CV') {
              if (
                $acctg[$key]['acnoid'] == $params['acnoid'] &&  $acctg[$key]['cr'] <> 0 &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['postdate'] == $params['postdate'] && $acctg[$key]['projectid'] == $projectid
                && $acctg[$key]['subproject'] == $subproject && $acctg[$key]['stageid'] == $stageid && $acctg[$key]['branch'] == $branch && $acctg[$key]['deptid'] == $deptid && $acctg[$key]['ewtcode'] == $ewtcode
              ) {
                $acctg[$key]['db'] += $params['db'];
                $acctg[$key]['cr'] += $params['cr'];
                if (isset($params['fcr'])) {
                  $acctg[$key]['fdb'] += $params['fdb'];
                  $acctg[$key]['fcr'] += $params['fcr'];
                }
                $isExist = true;
              }
            } else {
              goto defautconditioncr;
            }
            break;
          case 59: //roosevelt
            if ($doc == 'BE' || $doc == 'RE') {
              if (
                $acctg[$key]['acnoid'] == $params['acnoid']
                &&  $acctg[$key]['client'] == $params['client']
                && $acctg[$key]['checkno'] == $params['checkno'] &&  $acctg[$key]['cr'] <> 0 &&
                $acctg[$key]['postdate'] == $params['postdate']
              ) {
                $acctg[$key]['db'] += $params['db'];
                $acctg[$key]['cr'] += $params['cr'];
                if (isset($params['fcr'])) {
                  $acctg[$key]['fdb'] += $params['fdb'];
                  $acctg[$key]['fcr'] += $params['fcr'];
                }
                $isExist = true;
              }
            } else {
              goto defautconditioncr;
            }
            break;
          default:
            defautconditioncr:
            if (
              $acctg[$key]['acnoid'] == $params['acnoid'] &&  $acctg[$key]['cr'] <> 0 &&  $acctg[$key]['client'] == $params['client'] && $acctg[$key]['postdate'] == $params['postdate'] && $acctg[$key]['projectid'] == $projectid
              && $acctg[$key]['subproject'] == $subproject && $acctg[$key]['stageid'] == $stageid && $acctg[$key]['branch'] == $branch && $acctg[$key]['deptid'] == $deptid && $acctg[$key]['refx'] == $refx && $acctg[$key]['linex'] == $linex
            ) { // && floatval($acctg[$key]['cr'])!=0  remove for items with zero amount
              $acctg[$key]['db'] += $params['db'];
              $acctg[$key]['cr'] += $params['cr'];
              if (isset($params['fcr'])) {
                $acctg[$key]['fdb'] += $params['fdb'];
                $acctg[$key]['fcr'] += $params['fcr'];
              }
              $isExist = true;
            }
            break;
        }
      }
    }


    //not roosevelt
    if (!$isExist) {
      if ($systype == 'REALESTATE') {
        array_push($acctg, [
          'acnoid' => $params['acnoid'],
          'line' => $line,
          'client' => $params['client'],
          'db' => $params['db'],
          'cr' => $params['cr'],
          'postdate' => $params['postdate'],
          'cur' => isset($params['cur']) ? $params['cur'] : 'P',
          'forex' => isset($params['forex']) ? $params['forex'] : 1,
          'fdb' => isset($params['fdb']) ? $params['fdb'] : 0,
          'fcr' => isset($params['fcr']) ? $params['fcr'] : 0,
          'rem' => isset($params['rem']) ? $params['rem'] : '',
          'projectid' => isset($params['projectid']) ? $params['projectid'] : 0,
          'subproject' => isset($params['subproject']) ? $params['subproject'] : 0,
          'stageid' => isset($params['stageid']) ? $params['stageid'] : 0,
          'branch' => isset($params['branch']) ? $params['branch'] : 0,
          'deptid' => isset($params['deptid']) ? $params['deptid'] : 0,
          'poref' => isset($params['poref']) ? $params['poref'] : 0,
          'podate' => isset($params['podate']) ? $params['podate'] : '',
          'storetrno' => $storetrno,
          'station' => $station,
          'ref' => $ref,
          'refx' => $refx,
          'linex' => $linex,
          'ewtcode' =>  $ewtcode,
          'ewtrate' => $ewtrate,
          'isewt' => $isewt,
          'isvat' => $isvat,
          'phaseid' => isset($params['phaseid']) ? $params['phaseid'] : 0,
          'modelid' => isset($params['modelid']) ? $params['modelid'] : 0,
          'blklotid' => isset($params['blklotid']) ? $params['blklotid'] : 0,
          'amenityid' => isset($params['amenityid']) ? $params['amenityid'] : 0,
          'subamenityid' => isset($params['subamenityid']) ? $params['subamenityid'] : 0

        ]);
      } else {
        if ($doc == 'BE' || $doc == 'RE') { //roosevelt
          array_push($acctg, [
            'acnoid' => $params['acnoid'],
            'line' => $line,
            'client' => isset($params['client']) ? $params['client'] : '',
            'db' => $params['db'],
            'cr' => $params['cr'],
            'postdate' => $params['postdate'],
            'cur' => isset($params['cur']) ? $params['cur'] : 'P',
            'forex' => isset($params['forex']) ? $params['forex'] : 1,
            'fdb' => isset($params['fdb']) ? $params['fdb'] : 0,
            'fcr' => isset($params['fcr']) ? $params['fcr'] : 0,
            'rem' => isset($params['rem']) ? $params['rem'] : '',
            'checkno' => $params['checkno']
          ]);
        } else {
          $arrdata = [
            'acnoid' => $params['acnoid'],
            'line' => $line,
            'client' => isset($params['client']) ? $params['client'] : '',
            'db' => $params['db'],
            'cr' => $params['cr'],
            'postdate' => $params['postdate'],
            'cur' => isset($params['cur']) ? $params['cur'] : 'P',
            'forex' => isset($params['forex']) ? $params['forex'] : 1,
            'fdb' => isset($params['fdb']) ? $params['fdb'] : 0,
            'fcr' => isset($params['fcr']) ? $params['fcr'] : 0,
            'rem' => isset($params['rem']) ? $params['rem'] : '',
            'projectid' => isset($params['projectid']) ? $params['projectid'] : 0,
            'subproject' => isset($params['subproject']) ? $params['subproject'] : 0,
            'stageid' => isset($params['stageid']) ? $params['stageid'] : 0,
            'branch' => isset($params['branch']) ? $params['branch'] : 0,
            'deptid' => isset($params['deptid']) ? $params['deptid'] : 0,
            'poref' => isset($params['poref']) ? $params['poref'] : 0,
            'podate' => isset($params['podate']) ? $params['podate'] : '',
            'dpref' => isset($params['dpref']) ? $params['dpref'] : '',
            'storetrno' => $storetrno,
            'station' => $station,
            'ref' => $ref,
            'refx' => $refx,
            'linex' => $linex,
            'ewtcode' =>  $ewtcode,
            'ewtrate' => $ewtrate,
            'isewt' => $isewt,
            'isvat' => $isvat

          ];

          if (isset($params['clientid'])) {
            $arrdata['clientid'] = $params['clientid'];
          }

          array_push($acctg, $arrdata);
        }
      }
    }

    return $acctg;
  }

  public function isacctgbalance($trno, $config)
  {
    if ($this->companysetup->isinvonly($config['params'])) return true;

    $bal = $this->coreFunctions->getfieldvalue('ladetail', 'sum(db-cr)', 'trno=?', [$trno]);
    if ($bal == '' || $bal == null) {
      $bal = 0;
    }
    if ($bal == 0) {
      if ($config['params']['companyid'] == 11) { //summit
        switch ($config['params']['doc']) {
          case 'SJ':
            $ext = $this->coreFunctions->getfieldvalue("lastock", "sum(ext)", "trno=?", [$trno]);
            if ($ext == '') {
              $ext = 0;
            }
            $ar = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from ladetail as d left join coa on coa.acnoid=d.acnoid where d.trno=" . $trno . " and (left(coa.alias,2)='AR' or coa.alias='DC1')");
            if ($ar == '') {
              $ar = 0;
            }
            if ($ext != $ar) {
              $this->logger->sbcwritelog($trno, $config, 'POSTING', 'Posting failed. Total stock ' . $ext . ' - Total AR ' . $ar);
              return false;
            }
            break;
        }
      }

      return true;
    } else {
      return false;
    }
  } //end function



  public function posttransacctg($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    switch ($config['params']['companyid']) {
      case 16: //ati
        break;
      case 10: //afti
        if ($doc != 'CV') {
          $qry = "select trno from " . $config['docmodule']->detail . " where trno=? and db=0 and cr=0 limit 1";
          $this->coreFunctions->logConsole($qry);
          $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
          if (!empty($isitemzeroqty)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Both debit and credit amounts are zero. Please check.'];
          }
        }
        break;
      default:
        $qry = "select trno from " . $config['docmodule']->detail . " where trno=? and db=0 and cr=0 limit 1";
        $this->coreFunctions->logConsole($qry);
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($isitemzeroqty)) {
          return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Both debit and credit amounts are zero. Please check.'];
        }
        break;
    }

    $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);

    if ($this->isposted($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    if (!$this->isacctgbalance($trno, $config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Accounting entries are not balance.'];
    }

    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser == '') {
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set tmpuser='" . $user . "' where trno=" . $trno . " and tmpuser=''");
    }
    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser != $user) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This document is currently being posted by user ' . $tmpuser . '.'];
    }

    $msg = '';
    //for glhead
    if ($this->postinghead($config) === 1) {
      if ($this->postingdetail($config) === 1) {
        if ($this->postingvoiddetail($config) === 1) {
          if ($this->postingdetailinfo($config) === 1) {
            if ($this->postingparticulars($config) === 1) {
              if ($this->postingapledger($config) === 1) {
                if ($this->postingarledger($config) === 1) {
                  if ($this->postingcbledger($config) === 1) {
                    if ($this->postingcrledger($config) === 1) {
                      if ($this->postingcaledger($config) === 1) {

                        switch ($config['params']['companyid']) {
                          case 6:
                            if (!$this->updateardepodate($config, false)) {
                              $msg = "Posting Failed, updating depodate in arledger failed";
                            }
                            break;
                        }
                      } else {
                        $msg = "Posting Failed, please check detail(CA).";
                      }
                    } else {
                      $msg = "Posting Failed, please check detail(CR).";
                    }
                  } else {
                    $msg = "Posting Failed, please check detail(CB).";
                  }
                } else {
                  $msg = "Posting Failed, please check detail(AR).";
                }
              } else {
                $msg = "Posting Failed, please check detail(AP).";
              }
            } else {
              $msg = "Posting Failed, please check particulars.";
            }
          } else {
            $msg = "Posting Failed, please check detail(Info).";
          }
        } else {
          $msg = "Posting Failed, please check detail(Void).";
        }
      } else {
        $msg = "Posting Failed, please check detail.";
      }
    } else {
      $msg = "Posting Failed, please check head.";
    }

    if ($doc == 'CR' || $doc == 'DS' || $doc == 'GJ' || $doc == 'GC') {
      if ($config['params']['companyid'] == 10) { //afti - update to incentivetable
        if ($this->posttoincentivetable($config, false) === 0) {
          $msg = "Posting Failed.";
        };
      }
    }

    if ($doc == 'PV') {
      if ($config['params']['companyid'] == 10) { //afti
        if ($this->postingpvitem($config, false) === 0) {
          $msg = "Posting Failed.(PV Items)";
        };
      }
    }

    $cntnuminfo = $this->postcntnuminfo($config, true);
    if (!$cntnuminfo['status']) {
      $msg = $cntnuminfo['msg'];
    }

    if ($config['params']['companyid'] == 55) {
      if ($doc == 'CV') {
        $loantrno = $this->coreFunctions->datareader("select dptrno as value from " . $config['docmodule']->tablenum . " where trno=?", [$trno]);
        if ($loantrno != 0) {
          $this->coreFunctions->execqry("insert into loansum (trno,dateid,amount,loantype) 
          select h.trno,h.dateid,h.amount,le.planid from glhead as h left join cntnum as c on c.trno = h.trno 
          left join heahead as le on le.trno = c.dptrno  where c.trno = " . $trno, 'insert');
        }
      }
    }

    if ($msg == '') {

      try {
        $laheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->head . " where trno=?", [$trno]);
        $glheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hhead . " where trno=?", [$trno]);
        if ($laheadcount == '') $laheadcount = 0;
        if ($glheadcount == '') $glheadcount = 0;
        if ($laheadcount != $glheadcount) {
          $msg = 'Posting failed, header count doesnt match. LA:' . $laheadcount . ' = GL:' . $glheadcount;

          return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }

        $ladetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->detail . " where trno=?", [$trno]);
        $gldetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hdetail . " where trno=?", [$trno]);
        if ($ladetailcount == '') $ladetailcount = 0;
        if ($gldetailcount == '') $gldetailcount = 0;
        if ($ladetailcount != $gldetailcount) {
          $msg = 'Posting failed, detail count doesnt match. LA:' . $ladetailcount . ' = GL:' . $gldetailcount;

          return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }

        $date = $this->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $user, 'tmpuser' => ''];
        $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from voiddetail where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from detailinfo where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from particulars where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from pvitem where trno=?", "delete", [$trno]);

        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->sbctransferlog($trno, $config, $config['docmodule']->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } catch (Exception $e) {
        return ['trno' => $trno, 'status' => false, 'msg' => $e->getMessage()];
      }
    } else {
      deletepostedtablehere:
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $msg);

      $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
      if ($tmpuser != $user) {
        $this->logger->sbcwritelog($trno, $config, 'POSTED', "Deleting posted tables failed. Post user: " . $tmpuser);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This document is currently being posted by user ' . $tmpuser . '.'];
      } else {
        $this->logger->sbcwritelog($trno, $config, 'POSTED', "Deleting posted tables");
        $data = ['tmpuser' => ''];
        $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      }

      $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvoiddetail where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hdetailinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cbledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from crledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from caledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hparticulars where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hcntnuminfo where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttransacctg($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    if (!$this->isposted($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, Already unposted...'];
    }

    $msg = $this->hasbeendeposited($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
    $msg = $this->hasbeenappaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
    $msg = $this->hasbeenarpaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->hasbeenreleased($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    if ($this->companysetup->getsystemtype($config['params']) == 'REALESTATE') {
      $msg = $this->hasnextcr($config);
      if ($msg !== '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'This Transaction cannot be UNPOSTED, ' . $msg];
      }
    }

    if ($config['params']['companyid'] == 10) { //afti
      if ($doc == 'CR') {
        $crtrno = $this->coreFunctions->getfieldvalue("gldetail", "qttrno", "trno = ? and qttrno<>0", [$trno]);
        $isposted = $this->isposted2($crtrno, "transnum");
        if ($isposted) {
          $voidqs = $this->coreFunctions->getfieldvalue("hqsstock", "(iss-voidqty)", "trno=?", [$crtrno]);
          $voidqt = $this->coreFunctions->getfieldvalue("hqtstock", "(iss-voidqty)", "trno=?", [$crtrno]);
          if (($voidqs + $voidqt) > 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => "Cannot be unposted, QTN referenced is already posted."];
          }
        }
      }
    }

    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser == '') {
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set tmpuser='" . $user . "' where trno=" . $trno . " and tmpuser=''");
    }
    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser != $user) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, User ' . $tmpuser . ' is currently unposting this document'];
    }

    $msg = '';
    if ($this->unpostinghead($config) === 1) {
      if ($this->unpostingdetail($config) === 1) {
        if ($this->unpostingvoiddetail($config) === 1) {
          if ($this->unpostingdetailinfo($config) === 1) {
            if ($this->unpostingparticulars($config) === 1) {
              switch ($config['params']['companyid']) {
                case 6: //mitsukoshi
                  if (!$this->updateardepodate($config, true)) {
                    $msg = "Unposting Failed, updating depodate in arledger failed or incentives already released";
                  }
                  break;
              }
            } else {
              $msg = 'Unposting failed. Please check particulars.';
            }
          } else {
            $msg = 'Unposting failed. Please check detail info.';
          }
        } else {
          $msg = 'Unposting failed. Please check void detail.';
        }
      } else {
        $msg = 'Unposting failed. Please check detail.';
      }
    } else {
      $msg = 'Unposting failed. Please check head.';
    }

    if ($config['params']['companyid'] == 10) { //afti
      if (!$this->posttoincentivetable($config, true)) {
        $msg = "Unposting Failed, incentives already released.";
      }

      if ($this->unpostingpvitem($config, false) === 0) {
        $msg = "Unposting Failed.(PV Items)";
      };
    }

    $cntnuminfo = $this->postcntnuminfo($config, false);
    if (!$cntnuminfo['status']) {
      $msg = $cntnuminfo['msg'];
    }

    if ($config['params']['companyid'] == 55) {
      if ($doc == 'CV') {
        $loantrno = $this->coreFunctions->datareader("select dptrno as value from " . $config['docmodule']->tablenum . " where trno=?", [$trno]);
        if ($loantrno != 0) {
          $this->coreFunctions->execqry("delete from loansum where trno=?", "delete", [$trno]);
        }
      }
    }

    if ($msg === '') {
      $laheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->head . " where trno=?", [$trno]);
      $glheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hhead . " where trno=?", [$trno]);
      if ($laheadcount == '') $laheadcount = 0;
      if ($glheadcount == '') $glheadcount = 0;
      if ($laheadcount != $glheadcount) {
        $msg = 'Unposting failed, header count doesnt match. LA:' . $laheadcount . ' = GL:' . $glheadcount;

        return ['trno' => $trno, 'status' => false, 'msg' => $msg];
      }

      $ladetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->detail . " where trno=?", [$trno]);
      $gldetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hdetail . " where trno=?", [$trno]);
      if ($ladetailcount == '') $ladetailcount = 0;
      if ($gldetailcount == '') $gldetailcount = 0;
      if ($ladetailcount != $gldetailcount) {
        $msg = 'Unposting failed, detail count doesnt match. LA:' . $ladetailcount . ' = GL:' . $gldetailcount;

        return ['trno' => $trno, 'status' => false, 'msg' => $msg];
      }

      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null,tmpuser='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cbledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from crledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from caledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvoiddetail where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hdetailinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hparticulars where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hcntnuminfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hpvitem where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      deletelocaltablehere:
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $msg);

      $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
      if ($tmpuser != $user) {
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', "Deleting local tables failed. Post user: " . $tmpuser);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This document is presently being unposted by user ' . $tmpuser . '.'];
      } else {
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', "Deleting local tables");
      }

      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from voiddetail where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from detailinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from particulars where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function


  public function posttranstock($config)
  {
    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
      $config['params']['trno'] = $trno;
    } elseif (isset($config['params']['tableid'])) {
      $trno = $config['params']['tableid'];
      $config['params']['trno'] = $trno;
    } else {
      $trno = $config['params']['trno'];
    }
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    //delete acctg entries with zero debit/credit
    $this->coreFunctions->execqry("delete from ladetail where trno=" . $trno . " and db=0 and cr=0");

    switch ($doc) {
      case 'JC':
        $qry = "select trno from " . $config['docmodule']->stock . " where trno=? and qty=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

        break;
      case 'BE':
      case 'RE': //for roosevelt -> Bounced Check Entry 
      case 'CH': //ericco
        $isitemzeroqty = 0;
        break;
      default:
        $qry = "select s.trno from " . $config['docmodule']->stock . " as s left join item on item.itemid=s.itemid where s.trno=? and s.qty=0 and s.iss=0 and item.isnoninv=0 limit 1";
        $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
        break;
    }


    if (!empty($isitemzeroqty)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        if ($doc == 'RR' || $doc == 'AC') {
          $qry = "select trno from " . $config['docmodule']->head . " where trno=? and invoiceno<>'' and invoicedate<>'' limit 1";

          $isinvoiceno_and_invoicedate = $this->coreFunctions->opentable($qry, [$trno]);

          if (empty($isinvoiceno_and_invoicedate)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Check carefully, Invoice fields should not be empty.'];
          }
        }
        break;
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    if ($this->isposted($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Posted already.'];
    }

    if (!$this->isacctgbalance($trno, $config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Accounting entries are not balance.'];
    }

    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser == '') {
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set tmpuser='" . $user . "' where trno=" . $trno . " and tmpuser=''");
    }
    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser != $user) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This document is currently being posted by user ' . $tmpuser . '.'];
    }

    $msg = '';
    //for glhead
    $postinghead = $this->postinghead($config);

    // 2025.02.05 - may posting na ng SJ sa baba
    // if ($config['params']['companyid'] == 21 && $doc == 'SJ') { //kinggeorge
    //   $cntnuminfo = $this->postcntnuminfo($config, true);
    // }

    if ($config['params']['companyid'] == 21) { //Kinggeorge
      $this->logger->sbcwritelog($trno, $config, 'POST', 'Status Head: ' . $postinghead);
    }


    if ($postinghead === 1) {
      switch ($doc) {
        case 'TS':
        case 'ST':
        case 'POSTINGST':
        case 'CO':
        case 'MT':
        case 'REPLENISHPALLET';
        case 'REPLENISHITEM';
          $ts = $this->tsreverse($config);
          if (!$ts['status']) {
            $msg = $ts['msg'];
            goto exithere;
          }
          break;
      }

      if ($this->postingstock($config) === 1) {

        if ($this->postingrrstatus($config) === 1) {

          switch ($doc) {
            case 'TS':
            case 'ST':
            case 'CO':
            case 'MT':
            case 'REPLENISHPALLET';
            case 'REPLENISHITEM';
              $ts = $this->updatereceivedate($config);
              if (!$ts['status']) {
                $msg = $ts['msg'];
                goto exithere;
              }
              break;

            case 'RP':
              $rp = $this->postrppallet($config, true);
              if (!$rp['status']) {
                $msg = $rp['msg'];
                goto exithere;
              }
              break;

            case 'WAREHOUSECONTROLLER':
            case 'WAREHOUSEPICKER':
            case 'LOGISTICS':
              $void = $this->postvoid($config, true);
              if (!$void['status']) {
                $msg = $void['msg'];
                goto exithere;
              }
              $cntnuminfo = $this->postcntnuminfo($config, true);
              if (!$cntnuminfo['status']) {
                $msg = $cntnuminfo['msg'];
                goto exithere;
              }
              $boxinfo = $this->postboxinginfo($config, true);
              if (!$boxinfo['status']) {
                $msg = $boxinfo['msg'];
                goto exithere;
              }
              break;

            case 'SJ':
            case 'JP':
            case 'RELEASED':
            case 'RU':
            case 'DM':
            case 'SN':
            case 'DR':
            case 'SK':
            case 'RR':
            case 'SS':
            case 'SM':
            case 'MJ':
            case 'LL':
            case 'FA':
            case 'WO':
            case 'MI':
              $cntnuminfo = $this->postcntnuminfo($config, true);
              if (!$cntnuminfo['status']) {
                $msg = $cntnuminfo['msg'];
                goto exithere;
              }
              break;
          }

          if ($this->postingcntclient($config) !== 1) {
            $msg = "Posting failed. Kindly check the tabs.";
            goto exithere;
          }


          if ($this->postingstockinfo($config) !== 1) {
            $msg = "Posting failed. Kindly check the stockinfo.";
            goto exithere;
          }

          if ($this->postingrrfams($config) !== 1) {
            $msg = "Posting failed. Kindly check the rrfams. ";
            goto exithere;
          }

          if ($config['params']['companyid'] == 43) { //mighty
            if ($this->postingtripdetail($config) !== 1) {
              $msg = "Posting failed. Kindly check the tripdetail. ";
              goto exithere;
            }
          }

          if ($config['params']['companyid'] == 29) { //sbc
            if ($this->postingparticulars($config) !== 1) {
              $msg = "Unposting failed. Kindly check particulars";
              goto exithere;
            }
          }


          if ($this->postingdetail($config) === 1) {
            if ($this->postingdetailinfo($config) === 1) {
              if ($this->postingapledger($config) === 1) {
                if ($this->postingarledger($config) === 1) {
                  if ($this->postingcrledger($config) === 1) {
                    if ($this->postingcaledger($config) === 1) {
                      if ($this->postingcbledger($config) === 1) {
                      } else {
                        $msg = "Posting failed. Kindly check the detail(CB).";
                      }
                    } else {
                      $msg = "Posting failed. Kindly check the detail(CA).";
                    }
                  } else {
                    $msg = "Posting failed. Kindly check the detail(CR).";
                  }
                } else {
                  $msg = "Posting failed. Kindly check the detail(AR).";
                }
              } else {
                $msg = "Posting failed. Kindly check the detail(AP).";
              }
            } else {
              $msg = "Posting failed. Kindly check the detail info.";
            }
          } else {
            $msg = "Posting failed. Kindly check the detail.";
          }
        } else {
          $msg = "Posting failed. Kindly check the rrstatus.";
        }
      } else {
        $msg = "Posting failed. Kindly check the stock.";
      }
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    exithere:
    if ($msg === '') {
      if ($config['params']['companyid'] == 21) { //Kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'POST', 'Checking post head records');
      }
      // checking of records head, stock, detail
      $laheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->head . " where trno=?", [$trno]);
      $glheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hhead . " where trno=?", [$trno]);
      if ($laheadcount == '') $laheadcount = 0;
      if ($glheadcount == '') $glheadcount = 0;
      if ($laheadcount != $glheadcount) {
        $msg = 'Posting failed, header count doesnt match. LA:' . $laheadcount . ' = GL:' . $glheadcount;
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. ' . $msg];
      }

      if ($config['params']['companyid'] == 21) { //Kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'POST', 'Checking post stock records');
      }
      $lastockcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->stock . " where trno=?", [$trno]);
      $glstockcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hstock . " where trno=?", [$trno]);
      if ($lastockcount == '') $lastockcount = 0;
      if ($glstockcount == '') $glstockcount = 0;
      if ($lastockcount != $glstockcount) {
        $msg = 'Posting failed, stock count doesnt match. LA:' . $lastockcount . ' = GL:' . $glstockcount;

        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. ' . $msg];
      }

      if ($config['params']['companyid'] == 21) { //Kinggeorge
        $this->logger->sbcwritelog($trno, $config, 'POST', 'Checking post detail records');
      }
      $ladetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->detail . " where trno=?", [$trno]);
      $gldetailcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hdetail . " where trno=?", [$trno]);
      if ($ladetailcount == '') $ladetailcount = 0;
      if ($gldetailcount == '') $gldetailcount = 0;
      if ($ladetailcount != $gldetailcount) {
        $msg = 'Posting failed, detail count doesnt match. LA:' . $ladetailcount . ' = GL:' . $gldetailcount;

        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. ' . $msg];
      }

      $date = $this->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user, 'statid' => 12, 'tmpuser' => ''];


      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->stock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from detailinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from stockinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry('delete from particulars where trno=? ', 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from cntclient where trno=?", "delete", [$trno]);

      //warehousing local tables
      $this->coreFunctions->execqry("delete from rppallet where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from voidstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from boxinginfo where trno=?", "delete", [$trno]);

      if ($config['params']['companyid'] == 6) { //mitsukoshi
        switch ($doc) {
          case 'LOGISTICS':
          case 'SD':
          case 'SE':
          case 'SF':
            $ardate = $this->getCurrentDate();
            $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, ['ardate' => $ardate], ['trno' => $trno, 'ardate' => null]);
            $headsj = $this->coreFunctions->opentable("select cntnum.ardate, glhead.terms, glhead.due from cntnum left join glhead on glhead.trno=cntnum.trno where cntnum.trno=? and cntnum.ardate is not null", [$trno]);
            if (!empty($headsj)) {
              $newdue = $this->computeterms($headsj[0]->ardate, $headsj[0]->due, $headsj[0]->terms);
              $this->coreFunctions->sbcupdate("glhead", ['due' => $newdue], ['trno' => $trno]);
              $this->coreFunctions->execqry("update arledger as ar left join gldetail as d on d.trno=ar.trno and d.line=ar.line set ar.dateid = date('" . $headsj[0]->ardate . "'), d.postdate = date('" . $headsj[0]->ardate . "') where ar.trno=" . $trno);
            }
            break;
        }
      }
      $logistics = '';
      switch ($doc) {
        case 'LOGISTICS':
          $logistics = ' - LOGISTICS';
          break;
        case 'WAREHOUSECONTROLLER':
          $logistics = ' - INVENTORY CONTROLLER';
          break;
        case 'WAREHOUSEPICKER':
          $logistics = ' - WAREHOUSE PICKER';
          break;
        default:
          $logistics = '';
          break;
      }

      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno . $logistics);

      $this->sbctransferlog($trno, $config, $config['docmodule']->htablelogs);

      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      deletepostedtablehere:
      $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
      if ($tmpuser != $user) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. This document is currently being posted by user ' . $tmpuser . '.'];
      }

      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, ['tmpuser' => ''], ['trno' => $trno]);

      $this->logger->sbcwritelog($trno, $config, 'POSTED', $msg);



      $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from caledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cbledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from crledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from rrstatus where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hstock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hstockinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hdetailinfo where trno=?", "delete", [$trno]);

      //warehousing local tables
      $this->coreFunctions->execqry("delete from hrppallet where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hcntnuminfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvoidstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hboxinginfo where trno=?", "delete", [$trno]);

      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno . ' POSTED FAILED');

      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttranstock($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = $this->hasbeendeposited($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
    $msg = $this->hasbeenitemissue($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    if ($config['params']['companyid'] == 48 && $config['params']['doc'] == 'SJ') { // seastar
    } else {
      $msg = $this->hasbeenitemreturn($config);
      if ($msg !== '') {
        return ['trno' => $trno, 'status' => false, 'msg' => $msg];
      }
    }

    if ($this->companysetup->getserial($config['params'])) {
      $msg = $this->hasbeenserialout($config);
      if ($msg !== '') {
        return ['trno' => $trno, 'status' => false, 'msg' => $msg];
      }
    }

    $msg = $this->hasbeeninvoice($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->hasbeenmcpaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->hasbeenarpaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->hasbeenappaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->hasbeenvalidatedreplenish($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $isgenerateapv = $this->companysetup->isgenerateapv($config['params']);
    if ($isgenerateapv) {
      $msg = $this->hasbeenapv($config);
      if ($msg !== '') {
        return ['trno' => $trno, 'status' => false, 'msg' => $msg];
      }
    }

    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser == '') {
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set tmpuser='" . $user . "' where trno=" . $trno . " and tmpuser=''");
    }
    $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
    if ($tmpuser != $user) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, User ' . $tmpuser . ' is currently unposting this document'];
    }


    $msg = '';
    if ($this->unpostinghead($config) === 1) {
      if ($this->unpostingstock($config) === 1) {
        switch ($doc) {
          case 'RP':
            $rp = $this->postrppallet($config, false);
            if (!$rp['status']) {
              $msg = $rp['msg'];
            }
            break;

          case 'LOGISTICS':
          case 'SD':
            $void = $this->postvoid($config, false);
            if (!$void['status']) {
              $msg = $void['msg'];
            }
            $cntnuminfo = $this->postcntnuminfo($config, false);
            if (!$cntnuminfo['status']) {
              $msg = $cntnuminfo['msg'];
            }
            $boxinfo = $this->postboxinginfo($config, false);
            if (!$boxinfo['status']) {
              $msg = $boxinfo['msg'];
            }
            break;

          case 'SJ':
          case 'JP':
          case 'RELEASED':
          case 'RU':
          case 'DM':
          case 'SN':
          case 'SK':
          case 'DR':
          case 'RR':
          case 'SS':
          case 'MI':
          case 'SM':
          case 'MJ':
          case 'LL':
          case 'FA':
          case 'WO':
          case 'MI':
            $cntnuminfo = $this->postcntnuminfo($config, false);
            if (!$cntnuminfo['status']) {
              $msg = $cntnuminfo['msg'];
            }
            break;
          case 'ST':
          case 'TS':
            $this->coreFunctions->execqry("delete from serialin where trno=?", "delete", [$trno]);
            break;
        }

        if ($this->unpostingcntclient($config) !== 1) {
          $msg = "Unposting failed. Please check stockinfo. ";
        }

        if ($this->unpostingstockinfo($config) !== 1) {
          $msg = "Unposting failed. Please check stockinfo. ";
        }

        if ($this->postingrrfams($config, true) !== 1) {
          $msg = "Unposting failed. Please check rrfams. ";
        }

        if ($config['params']['companyid'] == 43) { //mighty
          if ($this->postingtripdetail($config, true) !== 1) {
            $msg = "Unposting failed. Please check tripdetail. ";
          }
        }
        if ($config['params']['companyid'] == 29) { // sbc
          if ($this->unpostingparticulars($config) !== 1) {
            $msg = "Unposting failed. Please check particulars.";
          }
        }

        if ($this->unpostingdetailinfo($config) !== 1) {
          $msg = "Unposting failed. Please check detail info.";
        }
      } else {
        $msg = 'Unposting failed. Please check stock.';
      }
    } else {
      $msg = 'Unposting failed. Please check head.';
    }

    if ($msg === '') {
      $laheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->head . " where trno=?", [$trno]);
      $glheadcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hhead . " where trno=?", [$trno]);

      if ($laheadcount == '') $laheadcount = 0;
      if ($glheadcount == '') $glheadcount = 0;
      if ($laheadcount != $glheadcount) {
        $msg = 'Unposting failed, header count doesnt match. LA:' . $laheadcount . ' = GL:' . $glheadcount;

        return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, ' . $msg];
      }

      $tstrno = "tstrno=0 and";
      if ($config['params']['companyid'] == 39 && $config['params']['doc'] == 'SM') { //CBBSI
        $tstrno = "";
      }

      if ($config['params']['companyid'] == 59 && ($config['params']['doc'] == 'BE' || $config['params']['doc'] == 'RE')) { //Roosevelt
        $tstrno = "";
      }

      if ($config['params']['companyid'] == 63 && $config['params']['doc'] == 'CH') { //ericco
        $tstrno = "";
      }

      $lastockcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->stock . " where trno=?", [$trno]);
      $glstockcount = $this->coreFunctions->datareader("select count(trno) as value from " . $config['docmodule']->hstock . " where $tstrno trno=?", [$trno]);
      if ($lastockcount == '') $lastockcount = 0;
      if ($glstockcount == '') $glstockcount = 0;
      if ($lastockcount != $glstockcount) {
        $msg = 'Unposting failed, stock count doesnt match. LA:' . $lastockcount . ' = GL:' . $glstockcount;

        return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, ' . $msg];
      }

      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      if ($config['params']['companyid'] == 59) {
        $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='', tmpuser='', statid=0, iscsv=0 where trno=?", 'update', [$trno]);
      } else {
        $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='', tmpuser='', statid=0 where trno=?", 'update', [$trno]);
      }
      $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from crledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cbledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from caledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from rrstatus where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hstock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hdetail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hdetailinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hstockinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hcntclient where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry('delete from hparticulars where trno=? ', 'delete', [$trno]);
      if ($doc == 'FA') $this->coreFunctions->execqry('delete from fasched where rrtrno=? ', 'delete', [$trno]);

      //warehousing local tables
      $this->coreFunctions->execqry("update hcntnuminfo set logisticdate=null, logisticby='' where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hrppallet where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hcntnuminfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hvoidstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hboxinginfo where trno=?", "delete", [$trno]);

      if ($config['params']['companyid'] == 40) { //cdo
        if ($doc == 'ST') {
          $gjtrno = $this->coreFunctions->getfieldvalue("cntnum", "trno", "dptrno=?", [$trno]);
          if ($gjtrno != 0) {
            $this->coreFunctions->execqry("delete from glhead  where trno=?", "delete", [$gjtrno]);
            $this->coreFunctions->execqry("delete from gldetail where trno=?", "delete", [$gjtrno]);
            $this->coreFunctions->execqry("delete from cntnum where trno=?", "delete", [$gjtrno]);
          }
        }
      }

      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      deletelocaltablehere:
      $tmpuser = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "tmpuser", "trno=?", [$trno]);
      if ($tmpuser != $user) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost FAILED, User ' . $tmpuser . ' is currently unposting this document'];
      }

      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, ['tmpuser' => ''], ['trno' => $trno]);

      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $msg);

      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->stock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->detail . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from stockinfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from detailinfo where trno=?", "delete", [$trno]);

      //warehousing local tables
      $this->coreFunctions->execqry("delete from rppallet where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from voidstock where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from boxinginfo where trno=?", "delete", [$trno]);

      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function getlatestcost($itemid, $dateid, $config, $pwh = '', $field = 'cost')
  {
    $dateid =  date('Y-m-d', strtotime($dateid));
    if ($pwh) {
      $wh = $pwh;
    } else {
      $wh = $config['params']['data']['wh'];
    }
    $qry = "select ifnull($field,0) as value from(select head.docno,head.dateid,
          stock.cost,stock.uom,stock.disc,case head.forex when 0 then 1 else ifnull(head.forex,1) end as forex
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','IS','AJ','CM','TS') and wh.client = ?
          and stock.itemid = ? and stock.cost <> 0 and head.dateid<= ?
          UNION ALL
          select head.docno,head.dateid,stock.cost,
          stock.uom,stock.disc,case head.forex when 0 then 1 else ifnull(head.forex,1) end as forex from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join client on client.clientid = head.clientid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('RR','IS','AJ','CM','TS') and wh.client = ?
          and stock.itemid = ?  and stock.cost <> 0 and head.dateid<= ?
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->datareader($qry, [$wh, $itemid, $dateid, $wh, $itemid, $dateid], '', true);
    return $data;
  } // end function


  public function tsreverse($config)
  {
    $status = 1;
    $msg = '';
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $loc = 'stock.loc2';

    $qry = "delete from " . $config['docmodule']->stock . " where tstrno<>0 and trno=?";
    if ($this->coreFunctions->execqry($qry, "delete", [$trno])) {

      $qry = "select trno,stock.line,refx,linex,tstrno,tsline,stock.itemid, stock.uom,stock.whid,stock.loc,
        stock.disc, stock.cost, stock.qty,stock.rrcost,stock.rrqty, stock.ext, stock.isqty,stock.iss,stock.amt,stock.isamt,
        stock.qa, stock.ref,stock.encodeddate,stock.encodedby,stock.editdate,stock.editby,stock.rem,stock.comm,stock.icomm,stock.expiry,stock.palletid,stock.locid,stock.stageid
         FROM lastock as stock
       where trno=? and tstrno=0";

      $data = $this->coreFunctions->opentable($qry, [$trno]);
      $last_line = $this->coreFunctions->datareader("select line as value from " . $config['docmodule']->stock . " where trno=? order by line desc limit 1", [$trno]);
      $last_line++;

      if ($config['params']['companyid'] == 52) { //technolab
        $loc = 'stock.loc';
      }

      foreach ($data as $itmindex => $itmdata) {
        $qryc = "insert into lastock(trno,line,refx,linex,tstrno,tsline,itemid, uom,whid,
        loc,disc,cost,qty,rrcost,
          rrqty,ext,isqty,iss,amt,isamt,qa,ref,encodeddate,encodedby,editdate,editby,rem,comm,icomm,expiry,palletid,locid,stageid,projectid)
          SELECT stock.trno," . $last_line . ",0,0,stock.trno,stock.line,stock.itemid, stock.uom,dest.clientid,
          " . $loc . ",
          stock.disc, stock.cost, stock.iss,stock.rrcost,stock.isqty, stock.ext, 0,0,stock.amt,stock.isamt,
          0, stock.ref,stock.encodeddate,stock.encodedby,stock.editdate,stock.editby,stock.rem,stock.comm,stock.icomm,stock.expiry,stock.palletid2,stock.locid2,stock.stageid,stock.projectid
          FROM lastock as stock
          left join lahead as head on head.trno=stock.trno
          left join client on client.clientid=stock.whid
          left join client as dest on dest.client=head.client
          where stock.trno=? and stock.line=?";

        if ($this->coreFunctions->execqry($qryc, "insert", [$trno, $itmdata->line])) {
          if ($this->companysetup->getserial($config['params'])) {
            $serialout = $this->coreFunctions->opentable('select serial,chassis,color from serialout where trno=? and line=?', [$itmdata->trno, $itmdata->line]);
            foreach ($serialout as $key => $value) {
              $serial['trno'] = $trno;
              $serial['line'] = $last_line;
              $serial['outline'] = 0;
              $serial['serial'] = $value->serial;
              $serial['chassis'] = $value->chassis;
              $serial['color'] = $value->color;
              $this->coreFunctions->sbcinsert('serialin', $serial);
            }
          }
          $last_line++;;
        } else {
          $status = 0;
          $msg .= 'Failed to insert reverse stock.<br>';

          $qry = "delete from " . $config['docmodule']->stock . " where tstrno<>0 and trno=?";
          if (!$this->coreFunctions->execqry($qry, "delete", [$trno])) {
            $msg .= 'Failed to delete reverse stock after insert stock failed.<br>';
          }
          if ($this->companysetup->getserial($config['params'])) {
            $this->coreFunctions->execqry('delete from serialin where trno=?', "delete", [$trno]);
          }
          break;
        }
      } //end for each


    } else {
      $status = 0;
      $msg = 'Failed to delete reverse stock';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  public function getstockline($tablename, $trno)
  {
    $qry = "select line as value from " . $tablename . " where trno=? order by line desc limit 1";
    return $this->coreFunctions->datareader($qry, [$trno]);
  }

  private function updatereceivedate($config)
  {
    $status = 1;
    $msg = '';
    $trno = $config['params']['trno'];
    $rec = '';

    $qry = "select r.receiveddate,r.cur,r.forex,g.trno,g.line FROM costing as c left join rrstatus as r on r.trno = c.refx and r.line = c.linex
  left join glstock as g on g.tstrno = c.trno and g.tsline = c.line where c.trno = ?  order by trno,line,receiveddate";

    $data = $this->coreFunctions->opentable($qry, [$trno]);
    foreach ($data as $itmindex => $itmdata) {
      $upqry = "update rrstatus set cur='" . $itmdata->cur . "',forex=" . $itmdata->forex . ",receiveddate = '" . $itmdata->receiveddate . "' where trno =? and line =?";
      if ($this->coreFunctions->execqry($upqry, "update", [$trno, $itmdata->line])) {
        $status = 1;
      } else {
        $status = 0;
        $msg = 'Failed to update actual received date';
      }
    }
    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  private function postrppallet($config, $post)
  {
    $trno = $config['params']['trno'];
    $status = 1;
    $msg = '';

    $table = 'rppallet';
    $htable = 'hrppallet';

    if (!$post) {
      $table = 'hrppallet';
      $htable = 'rppallet';
    }

    $qry = "insert into " . $htable . " (trno, palletid, dateid, user, statid, forkliftid, forkliftminedate, forkliftdate, dropoffdate, whmanid, whmanminedate)
    select trno, palletid, dateid, user, statid, forkliftid, forkliftminedate, forkliftdate, dropoffdate, whmanid, whmanminedate from " . $table . " where trno=?";

    if (!$this->coreFunctions->execqry($qry, "insert", [$trno])) {
      $status = 0;
      $msg = 'Failed to post pallet details';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  private function postvoid($config, $post)
  {
    $trno = $config['params']['trno'];
    $status = 1;
    $msg = '';

    $table = 'voidstock';
    $htable = 'hvoidstock';

    if (!$post) {
      $table = 'hvoidstock';
      $htable = 'voidstock';
    }

    $qry = "insert into " . $htable . " (trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, void, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate, voidby, voidddate, returnid, returndate)
    select trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, void, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate, voidby, voidddate, returnid, returndate from " . $table . " where trno=?";

    if (!$this->coreFunctions->execqry($qry, "insert", [$trno])) {
      $status = 0;
      $msg = 'Failed to post void details';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  public function postcntnuminfo($config, $post)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];
    $status = 1;
    $msg = '';

    $table = 'cntnuminfo';
    $htable = 'hcntnuminfo';

    if (!$post) {
      $table = 'hcntnuminfo';
      $htable = 'cntnuminfo';
    }

    $qry = "insert into " . $htable . " (trno, status, checkerdate, checkerby, dispatchdate, dispatchby, logisticdate, logisticby, checkerid, checkerlocid, truckid, receivedate, receiveby, scheddate, checkerrcvdate, forloadby, forloaddate, checkerdone,
    editby, editdate, rem2, releasedate, sono,instructions, itemid, uom2, batchsize, yield, lotno,dropoffwh,trnxtype, plateno, driverid, helperid, packdate, licenseno, hauler,termsyear,batchno, cwano, cwatime,
    weightin, weightintime,weightout,weightouttime,assignedlane,haulersupplier,haulerrate,ispartial,pdeadline,cptrno, kilo, expirydate, isdeductible, incidentid,isapproved, isreturned, isrefunded,ordate,orno,
    freight, reportedby, reportedby2, sdate1, sdate2, rem3,odometer,carrier,waybill,interestrate,downpayment, finterestrate, termsmonth, termspercentdp, termspercent, reservationdate, dueday, 
    reservationfee, farea, fpricesqm, ftcplot, ftcphouse, fsellingpricegross, fdiscount, fsellingpricenet, fcontractprice, fmiscfee, fmonthlydp, fmonthlyamortization, ffi, fmri, fma1, fma2, fma3,loanamt,transtype,
    isconfirmed,isacknowledged,ischqreleased,ispaid,penalty,rebate, strdate1, strdate2, tripdate, jotrno,
    whfromid, whtoid, loadedby, vessel, voyageno, sealno, unit,weight, valamt,cumsmt,delivery,depcr,depdb,commamt,commvat)

    select trno, status, checkerdate, checkerby, dispatchdate, dispatchby, logisticdate, logisticby, checkerid, checkerlocid, truckid, receivedate, receiveby, scheddate, checkerrcvdate, forloadby, forloaddate, checkerdone, 
    editby, editdate, rem2, releasedate, sono,instructions, itemid, uom2, batchsize, yield, lotno,dropoffwh,trnxtype, plateno, driverid, helperid, packdate, licenseno, hauler,termsyear, batchno,cwano,cwatime,
    weightin,weightintime,weightout,weightouttime,assignedlane,haulersupplier,haulerrate, ispartial,pdeadline ,cptrno, kilo, expirydate, isdeductible, incidentid ,isapproved, isreturned, isrefunded,
    ordate,orno,freight, reportedby, reportedby2, sdate1, sdate2, rem3,odometer,carrier,waybill,interestrate,downpayment,finterestrate, termsmonth, termspercentdp, termspercent, reservationdate, dueday, 
    reservationfee, farea, fpricesqm, ftcplot, ftcphouse, fsellingpricegross, fdiscount, fsellingpricenet, fcontractprice, fmiscfee, fmonthlydp, fmonthlyamortization, ffi, fmri, fma1, fma2, fma3,loanamt,transtype,
    isconfirmed,isacknowledged,ischqreleased,ispaid,penalty,rebate, strdate1, strdate2, tripdate, jotrno,
    whfromid, whtoid, loadedby, vessel, voyageno, sealno, unit,weight, valamt,cumsmt,delivery,depcr,depdb,commamt,commvat
    from " . $table . " where trno=?";


    if (!$this->coreFunctions->execqry($qry, "insert", [$trno])) {
      $status = 0;
      $msg = 'Failed to post cntnuminfo details';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  public function postingrrfams($config, $unposting = false)
  {
    $trno = $config['params']['trno'];

    $table = 'rrfams';
    $htable = 'hrrfams';
    if ($unposting) {
      $table = 'hrrfams';
      $htable = 'rrfams';
    }

    $qry = "insert into " . $htable . " (trno, line, itemid, ajtrno, ajline, qty, serialno, isnsi,barcode,sku) 
            select trno, line, itemid, ajtrno, ajline, qty, serialno, isnsi,barcode,sku from " . $table . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result) {
      $result =  $this->coreFunctions->execqry('delete from ' . $table . ' where trno=?', 'delete', [$trno]);
    }
    return $result;
  }

  public function postingtripdetail($config, $unposting = false)
  {
    $trno = $config['params']['trno'];

    $table = 'tripdetail';
    $htable = 'htripdetail';
    if ($unposting) {
      $table = 'htripdetail';
      $htable = 'tripdetail';
    }

    $qry = "insert into " . $htable . " (trno, line, itemid, clientid, activity, rate, encodeddate, encodedby, editby, editdate, titrno, batchid) 
            select trno, line, itemid, clientid, activity, rate, encodeddate, encodedby, editby, editdate, titrno, batchid from " . $table . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result) {
      $result =  $this->coreFunctions->execqry('delete from ' . $table . ' where trno=?', 'delete', [$trno]);
    }
    return $result;
  }

  private function postboxinginfo($config, $post)
  {
    $trno = $config['params']['trno'];
    $status = 1;
    $msg = '';

    $table = 'boxinginfo';
    $htable = 'hboxinginfo';

    if (!$post) {
      $table = 'hboxinginfo';
      $htable = 'boxinginfo';
    }

    $qry = "insert into " . $htable . " (line, trno, itemid, qty, boxno, groupid, groupid2, scandate, scanby)
    select line, trno, itemid, qty, boxno, groupid, groupid2, scandate, scanby from " . $table . " where trno=?";

    if (!$this->coreFunctions->execqry($qry, "insert", [$trno])) {
      $status = 0;
      $msg = 'Failed to post boxinginfo details';
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg];
  }

  function checkcoaacct($alias = [])
  {
    $msg = '';
    $exist = '';
    for ($i = 0; $i < count($alias); $i++) {
      $exist = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='" . $alias[$i] . "'");
      if ($exist == '') {
        if ($msg == '') {
          $msg = $alias[$i];
        } else {
          $msg = $msg . "," . $alias[$i];
        }
      }
    }

    return $msg;
  } //end function


  function checkuomtransaction($itemid, $uom, $line = 0)
  {
    if ($line == 0) {
      $uom2 = $uom;
    } else {
      $uom2 = $this->coreFunctions->getfieldvalue('uom', 'uom', 'itemid=? and line=?', [$itemid, $line]);
    }

    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);

    $qry = "
         select stock.trno from lastock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from prstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'
         union all
         select stock.trno from hprstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'    
         union all
         select stock.trno from cdstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'
         union all
         select stock.trno from hcdstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'               
         union all
         select stock.trno from postock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from hpostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from sostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from hsostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from qsstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from hqsstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from qtstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from hqtstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from trstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from htrstock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'         
         union all
         select stock.trno from glstock as stock  where stock.itemid=" . $itemid . " and stock.uom='" . $uom2 . "'                                   
     ";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }

  function checkcosting($stock, $companyid = 0)
  {
    $msg = '';
    $exist = 0;
    $serve = 0;


    foreach ($stock as $i => $value) {
      if (floatval($value->iss) != 0) {
        $noninv = $this->coreFunctions->getfieldvalue("item", "isnoninv", "itemid=?", [$value->itemid]);
        if ($noninv == 0) {
          $serve = $this->coreFunctions->getfieldvalue("costing", "ifnull(sum(served),0)", "trno=? and line=?", [$value->trno, $value->line]);
          if (floatval($serve) != 0) {
            if (floatval($serve) != floatval($value->iss)) {
              $exist += 1;
              $this->coreFunctions->execqry("update lastock set isqty =0,iss =0,ext =0,rrqty =0 where trno =? and line =?", "update", [$value->trno, $value->line]);
              $this->coreFunctions->execqry("delete from costing where trno=? and line =?", "delete", [$value->trno, $value->line]);
            }
            if ($companyid != 56) { //homeworks
              if ($value->cost == 0) {
                $cost = $this->coreFunctions->datareader("select ifnull(sum(rs.cost),0) as value from costing as c left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex where c.trno=" . $value->trno . " and c.line=" . $value->line, [], "", true);
                if (number_format(($cost / $value->iss), 6) != 0) {
                  if ($msg == '') {
                    $msg = "Re-encode Qty of " . $value->itemname . '. ';
                  } else {
                    $msg .= " Re-encode Qty of " . $value->itemname . '. ';
                  }
                }
              }
            }
          } else {
            $exist += 1;
            $this->coreFunctions->execqry("update lastock set isqty =0,iss =0,ext =0,rrqty =0 where trno =? and line =?", "update", [$value->trno, $value->line]);
            $this->coreFunctions->execqry("delete from costing where trno=? and line =?", "delete", [$value->trno, $value->line]);
          }
        }
      }
    }

    if (floatval($exist) <> 0) {
      $msg .= " Reencode Quantity of 0 items";
    }

    return $msg;
  } //end function

  function getitemminmax($barcode, $wh, $qty)
  {
    $sumbal = 0;
    $min = 0;
    $max = 0;
    $title = 'WARNING';
    $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$barcode]);
    $iname = '';
    $uom = '';
    $whcode = '';
    $whname = '';
    $message = "";

    $qryminmax = "select i.min,i.max,item.uom,item.itemname from itemlevel as i left join item on item.itemid = i.itemid where i.center=? and i.itemid =?";
    $minmax = $this->coreFunctions->opentable($qryminmax, [$wh, $itemid]);
    $minmax = json_decode(json_encode($minmax), true);
    $qry = "select  (SUM(qty)-SUM(iss)) as balance
      from (select '' as posted,
      ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) as qty,
      ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) as iss
      from lahead as head left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join client as wh on wh.clientid=stock.whid
      where  item.barcode=? and wh.client=?
      union all
      select 'POSTED' as posted,
      ifnull((stock.qty / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) as qty,
      ifnull((stock.iss / (case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)),0) as iss
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join client as wh on wh.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
      where  item.barcode=? and wh.client=?
      ) as sk";

    $data = $this->coreFunctions->opentable($qry, [$barcode, $wh, $barcode, $wh]);
    $data = json_decode(json_encode($data), true);
    if (!empty($minmax)) {
      $min = $minmax[0]['min'];
      $max = $minmax[0]['max'];
      $iname = $minmax[0]['itemname'];
      $uom = $minmax[0]['uom'];
    }


    if (!empty($dt)) {
      $sumbal = $dt[0]['balance'];
    } else {
      $sumbal = 0;
    }

    $sumbal = floatval($sumbal) + floatval($qty);

    if ($max != 0) {
      if ($sumbal >= $max) {

        $message = ' Warning : You Reached the MAXIMUM level of ' . number_format($max);
      } //end if
    } //end if

    if ($min != 0) {
      if ($sumbal <= $min) {

        $message = ' Warning : You Reached the MINIMUM level of ' . number_format($min);
      } //end if
    } //end if

    return $message;
  }


  public function computeterms($dateid, $due, $terms)
  {
    if ($terms != '') {
      $days = $this->coreFunctions->getfieldvalue("terms", "ifnull(days,0)", "terms=?", [$terms]);
      if ($days < 0) {
        $days = 0;
      }
      $newDate = new DateTime($dateid);
      $term = new DateInterval('P' . $days . 'D');
      $newDate->add($term);
      return $newDate->format('Y-m-d');
    } else {
      // return $due;
      return null;
    }
  }

  public function getitemname($itemid)
  {
    $qry = "select itemname, barcode,uom, isnoninv,model from item where itemid = ?";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    return $data;
  }

  public function getlastclient($pref, $type)
  {
    return $this->commonsbc->getlastclient($pref, $type);
  }

  public function getcreditinfo($config, $head)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $resellerid = $config['params']['resellerid'];
    $return = true;
    $sofilter = "";
    $srfilter = "";
    $s = "";

    if ($companyid == 10) { //afti
      if ($config['params']['doc'] == 'SJ') {
        $refx = $this->coreFunctions->getfieldvalue("lastock", "refx", "trno=?", [$trno]);
        if ($refx <> 0) {
          $sofilter = " and hqsstock.trno <>$refx";
          $srfilter = " and hsrstock.trno <>$refx";
        }
      }

      if ($config['params']['doc'] == 'QS') {
        $sofilter = " and hqshead.trno <>$trno";
        $srfilter = " and hsrhead.trno <>$trno";
        $s = " and h.trno <>$trno";
      }

      $client = $this->coreFunctions->getfieldvalue($head, "client", "trno=?", [$trno]);
      $qry = "select sohead.trno,sohead.docno,sohead.client,sohead.clientname,client.status,client.crlimit,client.isnocrlimit,sohead.terms,client.clearing,
      ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center = ? and  a.clientid=client.clientid),0) as bal,
      ifnull((select sum(a.db-cr) from crledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center=? and a.clientid=client.clientid and a.depodate is null),0) as pdc,
      ifnull((select sum(ext) from (select sum(hqsstock.ext) as ext from hsqhead left join hqshead on hqshead.sotrno = hsqhead.trno left join hqsstock on hqsstock.trno = hqshead.trno where hqshead.client = '" . $client . "' and hqshead.sotrno<>0 and hqsstock.iss<>hqsstock.sjqa and hqsstock.void =0 " . $sofilter . "
      union all select sum(hsrstock.ext) as ext from hsrstock left join hsrhead on hsrhead.trno = hsrstock.trno where hsrhead.client = '" . $client . "' and hsrstock.qa<>hsrstock.sjqa and hsrstock.void =0 " . $srfilter . ") as a),0) as soamt,
      ifnull((select sum(ext) from (select sum(s.ext) as ext from hqshead as h left join hqsstock as s on s.trno = h.trno left join item on item.itemid = s.itemid where h.client ='" . $client . "' and  h.sotrno =0 and item.islabor =0 " . $s . "
      union all
      select sum(s.ext) as ext from hqshead as h left join hqtstock as s on s.trno = h.trno left join item on item.itemid = s.itemid
      left join (select qtrno,0 as sotrno from srhead  union all select qtrno,sotrno from hsrhead) as sr on sr.qtrno = h.trno
      where h.client ='" . $client . "' and  s.qa = 0 and item.islabor =1 and sr.sotrno=0 " . $s . ") as a),0) as qtnamt,
      ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join glhead as h on h.trno = a.trno
      left join cntnum on cntnum.trno = a.trno left join delstatus as dl on dl.trno = a.trno left join terms on terms.terms = h.terms where a.clientid=client.clientid and cntnum.center = ? and dl.receivedate is not null and datediff(now(),(case cntnum.doc when 'AR' then a.dateid else ifnull(dl.receivedate,a.dateid) end))>(ifnull(terms.days,0)+client.clearing)),0) as overdue,ifnull((select datediff(now(),(case cntnum.doc when 'AR' then a.dateid else ifnull(dl.receivedate,a.dateid) end)) from arledger as a
      left join glhead as h on h.trno = a.trno left join cntnum on cntnum.trno = a.trno left join delstatus as dl on dl.trno = a.trno left join terms on terms.terms = h.terms where  a.clientid=client.clientid and cntnum.center = ? and datediff(now(),(case cntnum.doc when 'AR' then a.dateid else ifnull(dl.receivedate,a.dateid) end))>(ifnull(terms.days,0)+client.clearing) order by (case cntnum.doc when 'AR' then a.dateid else ifnull(dl.receivedate,a.dateid)  end) limit 1),'') as oldestage,
      client.isnocrlimit from " . $head . " as sohead left join client on client.client=sohead.client where sohead.trno=?";

      $data = $this->coreFunctions->opentable($qry, [$center, $center, $center, $center, $trno]);
    } else {

      switch ($companyid) {
        case 21: //kinggeorge
          $qry = "select sohead.trno,sohead.docno,sohead.client,sohead.clientname,client.status,client.crlimit,client.isnocrlimit,sohead.terms,client.clearing,
            ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center = ? and  a.clientid=client.clientid and a.bal>0),0) as bal,
            ifnull((select sum(a.db-cr) from crledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center=? and a.clientid=client.clientid and a.depodate is null),0) as pdc,
            ifnull((select sum(hsostock.ext) from hsostock left join hsohead on hsohead.trno = hsostock.trno left join transnum as num on num.trno=hsohead.trno where hsohead.client = client.client and num.statid in (5,6) and hsostock.qa<>hsostock.iss and hsostock.void =0),0) as soamt,
            ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join glhead as h on h.trno = a.trno
            left join cntnum on cntnum.trno = a.trno  left join terms on terms.terms = h.terms where a.clientid=client.clientid and cntnum.center = ? and a.bal>0 and datediff(now(),a.dateid)>(ifnull(terms.days,0)+client.clearing)),0) as overdue,
            client.isnocrlimit from " . $head . " as sohead left join client on client.client=sohead.client where sohead.trno=?";

          $data = $this->coreFunctions->opentable($qry, [$center, $center, $center, $trno]);
          break;

        default:
          $qry = "select sohead.trno,sohead.docno,sohead.client,sohead.clientname,client.status,client.crlimit,client.isnocrlimit,sohead.terms,client.clearing,
            ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center = ? and  a.clientid=client.clientid and a.bal>0),0) as bal,
            ifnull((select sum(a.db-cr) from crledger as a left join cntnum on cntnum.trno = a.trno where cntnum.center=? and a.clientid=client.clientid and a.depodate is null),0) as pdc,
            ifnull((select sum(hsostock.ext) from hsostock left join hsohead on hsohead.trno = hsostock.trno where hsohead.client = client.client and hsostock.qa<>hsostock.iss and hsostock.void =0),0) as soamt,ifnull((select sum(case a.db when 0 then a.bal*-1 else a.bal end) from arledger as a left join glhead as h on h.trno = a.trno
            left join cntnum on cntnum.trno = a.trno  left join terms on terms.terms = h.terms where a.clientid=client.clientid and cntnum.center = ? and a.bal>0 and datediff(now(),a.dateid)>(ifnull(terms.days,0)+client.clearing)),0) as overdue,
            ifnull((select datediff(now(),a.dateid) from arledger as a left join cntnum on cntnum.trno = a.trno  left join terms on terms.terms = a.terms 
            where a.clientid=client.clientid and cntnum.center = ? and a.bal>0 and datediff(now(),a.dateid)>(ifnull(terms.days,0)+client.clearing) order by a.dateid limit 1),'') as oldestage,
            client.isnocrlimit from " . $head . " as sohead left join client on client.client=sohead.client where sohead.trno=?";

          $data = $this->coreFunctions->opentable($qry, [$center, $center, $center, $center, $trno]);
          break;
      }
    }

    $info = '';
    $creditline = 0;

    if (!empty($data)) {
      if ($resellerid == 2) {
        if (floatval($data[0]->isnocrlimit) == 0) {
          $creditline = $data[0]->crlimit - ($data[0]->bal + $data[0]->pdc + $data[0]->soamt);

          $info = 'Total Overdue : ' . number_format(round($data[0]->overdue, 2), 2) .
            '\nOldest Age : ' . $data[0]->oldestage .
            '\nCredit Limit : ' . number_format(round($data[0]->crlimit, 2), 2) .

            '\nOutstanding Balance : ' . number_format(round($data[0]->bal, 2), 2) .
            '\nUndeposited Checks : ' . number_format(round($data[0]->pdc, 2), 2) .
            '\nTotal Unserved Posted SO`s : ' . number_format(round($data[0]->soamt, 2), 2) .


            '\nAvailable Credit Line : ' . number_format(round($creditline, 2), 2);
        }
      } else {
        if (floatval($data[0]->isnocrlimit) == 0) {
          if (floatval($data[0]->crlimit) != 0) {

            switch ($companyid) {
              case 10: //afti
                $creditline = $data[0]->crlimit - ($data[0]->bal + $data[0]->pdc + $data[0]->soamt + $data[0]->qtnamt);

                $info = 'Total Overdue : ' . number_format(round($data[0]->overdue, 2), 2) .
                  '\nOldest Age : ' . $data[0]->oldestage .
                  '\nCredit Limit : ' . number_format(round($data[0]->crlimit, 2), 2) .

                  '\nOutstanding Balance : ' . number_format(round($data[0]->bal, 2), 2) .
                  '\nUndeposited Checks : ' . number_format(round($data[0]->pdc, 2), 2) .
                  '\nTotal Unserved Posted SO`s : ' . number_format(round($data[0]->soamt, 2), 2) .
                  '\nTotal Unserved Posted Quotation`s : ' . number_format(round($data[0]->qtnamt, 2), 2) .

                  '\nAvailable Credit Line : ' . number_format(round($creditline, 2), 2);
                break;
              case 21: //kinggeorge
                $creditline = $data[0]->crlimit - ($data[0]->bal + $data[0]->pdc);

                $info = 'Total Overdue : ' . number_format(round($data[0]->overdue, 2), 2) .
                  '\nCredit Limit : ' . number_format(round($data[0]->crlimit, 2), 2) .

                  '\nOutstanding Balance : ' . number_format(round($data[0]->bal, 2), 2) .
                  '\nUndeposited Checks : ' . number_format(round($data[0]->pdc, 2), 2) .

                  '\nAvailable Credit Line : ' . number_format(round($creditline, 2), 2);
                break;

              default:
                $creditline = $data[0]->crlimit - ($data[0]->bal + $data[0]->pdc + $data[0]->soamt);

                $info = 'Total Overdue : ' . number_format(round($data[0]->overdue, 2), 2) .
                  '\nOldest Age : ' . $data[0]->oldestage .
                  '\nCredit Limit : ' . number_format(round($data[0]->crlimit, 2), 2) .

                  '\nOutstanding Balance : ' . number_format(round($data[0]->bal, 2), 2) .
                  '\nUndeposited Checks : ' . number_format(round($data[0]->pdc, 2), 2) .
                  '\nTotal Unserved Posted SO`s : ' . number_format(round($data[0]->soamt, 2), 2) .


                  '\nAvailable Credit Line : ' . number_format(round($creditline, 2), 2);
                break;
            }
          }
        }
      }
    }


    if ($info != '') {
      $this->coreFunctions->execqry("update " . $head . " set creditinfo ='" . $info . "',crline = " . $creditline . ",overdue=" . round($data[0]->overdue, 2) . " where trno = " . $trno, "update");
    }

    return $info;
  }

  public function deleteserialout($trno, $line)
  {
    $qry = "select sline from serialout where trno=? and line=?";
    $serialout = $this->coreFunctions->opentable($qry, [$trno, $line]);
    if (!empty($serialout)) {
      foreach ($serialout as $key => $value) {
        $qry = "update serialin set outline=0 where outline=?";
        $this->coreFunctions->execqry($qry, 'update', [$serialout[$key]->sline]);
        $this->coreFunctions->execqry('delete from serialout where sline=?', 'delete', [$serialout[$key]->sline]);
      }
    }
  }

  public function insertserialout($inline, $trno, $line, $serial)
  {
    $dinsert['trno'] = $trno;
    $dinsert['line'] = $line;
    $dinsert['serial'] = $serial;
    $outline = $this->coreFunctions->insertGetId('serialout', $dinsert);
    if ($outline != 0) {
      $qry = "update serialin set outline=? where sline=? and outline=0";
      $this->coreFunctions->execqry($qry, 'update', [$outline, $inline]);
    }
  }

  public function checkbelowcost($trno, $line, $config)
  {
    $belowcost = $this->checkAccess($config['params']['user'], 1736);
    $amt = $this->coreFunctions->getfieldvalue("lastock", "amt", "trno=? and line =?", [$trno, $line]);
    $qty = $this->coreFunctions->getfieldvalue("lastock", "iss", "trno=? and line =?", [$trno, $line]);
    $cost = $this->coreFunctions->getfieldvalue("lastock", "cost", "trno=? and line =?", [$trno, $line]);

    if (floatval($qty) != 0) {
      if (floatval($amt) == 0) {
        return 1;
      } else if (floatval($amt) < floatval($cost)) {
        if ($belowcost != '1') {
          return 2;
        }
      }
    }
  }


  public function checktotalext($trno, $table)
  {
    // $belowcost = $this->checkAccess($config['params']['user'], 1736);
    $amt = $this->coreFunctions->getfieldvalue($table, "ext", "trno=?", [$trno]);

    if (floatval($amt) == 0) {
      return 0;
    } else {
      return $amt;
    }
  }

  public function setserveditemsRR($refx, $linex, $qtyfield)
  {
    $qry1 = "select stock." . $qtyfield . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='RR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $qtyfield . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='RR' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry1 = $qry1 . " union all select plstock." . $qtyfield . " from plhead left join plstock on plstock.trno=
    plhead.trno where plhead.doc='PL' and plstock.refx=" . $refx . " and plstock.linex=" . $linex;

    $qry1 = $qry1 . " union all select hplstock." . $qtyfield . " from hplhead left join hplstock on hplstock.trno=
    hplhead.trno where hplhead.doc='PL' and hplstock.refx=" . $refx . " and hplstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $qtyfield . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    $result = $this->coreFunctions->execqry("update hpostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qty>qa", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
    }
    return $result;
  } //end function

  public function setserveditemsTempRR($refx, $linex, $qtyfield)
  {
    $qry1 = "select stock." . $qtyfield . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='RR' and stock.rtrefx=" . $refx . " and stock.rtlinex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $qtyfield . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='RR' and glstock.rtrefx=" . $refx . " and glstock.rtlinex=" . $linex;


    $qry2 = "select ifnull(sum(" . $qtyfield . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    // $this->coreFunctions->LogConsole($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    // $this->coreFunctions->LogConsole('QTY:' . $qty);
    $result = $this->coreFunctions->execqry("update hpostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qty>qa", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
    }
    return $result;
  } //end function

  public function checkversioncount($trno)
  {
    $qry = "select count(seq) as value from
          (select seq from transnum where ltrno=?
          union all
          select seq from transnum where trno=?) as t";
    $count = $this->coreFunctions->datareader($qry, [$trno, $trno]);
    return $count;
  }

  public function assignversion($count)
  {
    $a = ['', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z'];
    return $a[$count];
  }

  public function createversion($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $docno = $config['params']['docno'];
    $center = $config['params']['center'];
    if (empty($center) || $center == '') {
      return ['status' => false, 'msg' => 'Cannot continue, No center selected...'];
    }
    $insertcntnum = 0;
    $docno = $this->sanitize($docno, 'STRING');
    $docnolength = $this->companysetup->getdocumentlength($config['params']);
    $table = $config['docmodule']->tablenum;
    $ltrno = $this->coreFunctions->getfieldvalue($table, 'ltrno', "trno=?", [$trno]);
    if ($ltrno == 0) {
      $ltrno = $trno;
    }
    $count = $this->checkversioncount($ltrno);

    $docnoorig = $this->coreFunctions->getfieldvalue($table, 'docno', "trno=?", [$ltrno]);
    $seq = $this->coreFunctions->getfieldvalue($table, 'seq', "trno=?", [$ltrno]);
    while ($insertcntnum == 0) {
      $suff = $this->assignversion($count);
      $poseq = $docnoorig;

      $newdocno = $poseq . $suff;  // $this->PadJ($poseq, $docnolength);
      $col = [];
      $col = ['doc' => $config['params']['doc'], 'docno' => $newdocno, 'seq' => $seq, 'bref' => $config['params']['doc'], 'center' => $center, 'ltrno' => $ltrno];

      $insertcntnum =  $this->coreFunctions->insertGetId($table, $col);
      $count = $count + 1;
    }
    $newtrno = $insertcntnum;
    $newdocno = $this->coreFunctions->getfieldvalue($table, 'docno', "trno=?", [$newtrno]);
    $data = $config['docmodule']->loadheaddata($config);
    $head = json_decode(json_encode($data['head'][0]), true);
    $inserthead = [];
    foreach ($config['docmodule']->fields as $key) {
      $inserthead[$key] = $head[$key];
      if (!in_array($key, $config['docmodule']->except)) {
        $inserthead[$key] = $this->sanitizekeyfield($key, $head[$key]);
      } //end if
    }
    $inserthead['trno'] = $newtrno;
    $inserthead['docno'] = $newdocno;
    $inserthead['doc'] = $config['params']['doc'];
    $inserthead['dateid'] = date('Y-m-d');
    $inserthead['due'] = date('Y-m-d');
    $inserthead['createdate'] = $this->getCurrentTimeStamp();
    $inserthead['createby'] = $config['params']['user'];
    $this->coreFunctions->sbcinsert($config['docmodule']->head, $inserthead);

    $qry = " insert into " . $config['docmodule']->stock . " (
          trno,line,itemid,isamt,amt,isqty,iss,ext,disc,whid,loc,void,uom,rem,expiry,encodeddate,encodedby
           ) select " . $newtrno . ",line,itemid,isamt,amt,isqty,iss,ext,disc,whid,loc,void,uom,ifnull(rem,''),expiry,'" . $this->getCurrentTimeStamp() . "','" . $config['params']['user'] . "' from " . $config['docmodule']->hstock . "
         where trno =" . $trno;
    $this->coreFunctions->execqry($qry);
    $this->coreFunctions->execqry("update " . $config['docmodule']->hstock . " set void=1 where trno=?", "update", [$trno]);
    return ['status' => true, 'msg' => 'Successfully created.', 'trno' => $newtrno];
  } // end function

  public function getDueDate($dateid, $days)
  {
    $newDate = new DateTime($dateid);

    $days = preg_replace('/\D/', '', $days);
    $terms = new DateInterval('P' . $days . 'D');
    $newDate->add($terms);

    return $newDate->format('Y-m-d');
  }

  public function updateprojcompletion($config, $proj, $sub, $stage, $trno)
  {
    $cost = $this->coreFunctions->datareader("select cost as value from stages where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage);
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'MR':
      case 'MI':
      case 'WC':
        $path = 'App\Http\Classes\modules\construction\\' . strtolower($doc);
        break;
      default:
        $path = 'App\Http\Classes\modules\production\\' . strtolower($doc);
        break;
    }

    if (floatval($cost) == 0) {
      $this->logger->sbcwritelog($trno, $config, 'UPDATE', 'Unable to update project completion, 0 cost on stages.', app($path)->tablelogs);
    } else {
      $this->coreFunctions->execqry("update stages set completed=concat(round((((jc+mi)/cost)*100),2),'%') where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');

      $data = $this->coreFunctions->opentable("select projpercent,completed from stages where completed <> '' and projectid =? and subproject=?", [$proj, $sub]);
      $stagepercent = 0;

      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $stagepercent = $stagepercent + (floatval($data[$key]->projpercent) * (floatval($data[$key]->completed) / 100));
        }

        $this->coreFunctions->execqry("update subproject set completed=concat(round(" . $stagepercent . ",2),'%') where projectid = " . $proj . " and line=" . $sub, 'update');
      }

      $data2 = $this->coreFunctions->opentable("select completed,projpercent from subproject where completed<> '' and projectid =? and line=?", [$proj, $sub]);
      $subpercent = 0;

      if (!empty($data2)) {
        foreach ($data2 as $key2 => $value2) {
          $subpercent = $subpercent + (floatval($data2[$key2]->projpercent) * (floatval($data2[$key2]->completed) / 100));
        }

        $this->coreFunctions->execqry("update pmhead set completed=concat(round(" . $subpercent . ",2),'%') where projectid =" . $proj, 'update');
      }
    }
  }

  public function checkLockDate($transdate)
  {
    $lockdate = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='SYSL'");
    if ($lockdate != '') {
      $datetoday = strtotime($lockdate);
      $transdate = strtotime($transdate);
      if ($datetoday == '') {
        return true;
      } else {
        if ($transdate < $datetoday) {
          return false;
        } else {
          return true;
        }
      }
    } else {
      return true;
    }
  } //end function

  public function checkInvCutOffDate($transdate)
  {
    $cutoff = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='RX' and psection='INVCUTOFF'");
    if ($cutoff != '') {
      $datetoday = strtotime($cutoff);
      $transdate = strtotime($transdate);
      if ($datetoday == '') {
        return true;
      } else {
        if ($transdate < $datetoday) {
          return false;
        } else {
          return true;
        }
      }
    } else {
      return true;
    }
  } //end function

  public function checkDefaultWH($config)
  {
    if ($config['params']['companyid'] == 16 && $config['params']['doc'] == 'PR' || $config['params']['doc'] == 'RR') { //ati
      return true;
    }

    if ($config['params']['companyid'] == 48 && $config['params']['doc'] == 'SJ') { //seastar
      return true;
    }
    if ($config['params']['companyid'] == 50 && $config['params']['doc'] == 'PE') { // UNITECH, PRODUCTION REQUEST
      return true;
    }
    if (isset($config['params']['head']['wh'])) {
      $exist = $this->coreFunctions->getfieldvalue("client", "client", "client=?", [$config['params']['head']['wh']]);
      if (!$exist) {
        return false;
      }
    }

    return true;
  }

  public function checkserialin($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select stock.trno,stock.line,stock.qty from lastock as stock left join item on item.itemid=stock.itemid where stock.trno=" . $trno . " and item.isserial=1");
    $serialcount = 0;
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        $serialcount = $this->coreFunctions->datareader("select count(serial) as value from serialin where trno=? and line=?", [$data[$key]->trno, $data[$key]->line]);
        if ($serialcount != '' && $serialcount != 0) {
          if ($serialcount != $data[$key]->qty) {
            return false;
          }
        } else {
          return false;
        }
      }
    }
    return true;
  } //end function

  public function checkisacknowledged($config)
  {
    $trno = $config['params']['trno'];

    $acknowledged = $this->coreFunctions->datareader("select isacknowledged as value from cntnuminfo where trno=" . $trno);

    if ($acknowledged == 1) {
      return true;
    }
    return false;
  } //end function

  public function checkserialout($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $fields = " stock.iss";
    $table = "serialout";
    if ($doc == 'AJ') {
      $fields = " case when stock.iss = 0 then stock.qty else stock.iss end as iss";
    }
    $data = $this->coreFunctions->opentable("select stock.trno,stock.line,stock.iss as oqty, $fields from lastock as stock left join item on item.itemid=stock.itemid where stock.trno=" . $trno . " and item.isserial=1");
    $serialcount = 0;
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        if ($data[$key]->oqty == 0) {
          $table = "serialin";
        }
        $serialcount = $this->coreFunctions->datareader("select count(serial) as value from " . $table . " where trno=? and line=?", [$data[$key]->trno, $data[$key]->line]);
        if ($serialcount != '' && $serialcount != 0) {
          if ($serialcount != $data[$key]->iss) {
            return false;
          }
        } else {
          return false;
        }
      }
    }
    return true;
  } //end function

  public function checkserialoutrf($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select stock.trno,stock.line,stock.iss,stock.serialno from rfstock as stock left join item on item.itemid=stock.itemid where stock.trno=" . $trno . " and item.isserial=1");
    $serialcount = 0;
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        $serialcount = $this->coreFunctions->datareader("select count(serial) as value from serialout where rftrno=? and rfline=?", [$data[$key]->trno, $data[$key]->line]);
        if ($serialcount != '' && $serialcount != 0) {
          if ($serialcount != $data[$key]->iss) {
            return false;
          }
        } else {
          if ($data[$key]->serialno != '') {
            return false;
          } else {
            return false;
          }
        }
      }
    }
    return true;
  } //end function


  public function checksecuritylevel($config, $blnuser = false)
  {
    $lvl = [];

    $arrlvl = [2410, 2411, 2412, 2413, 2414, 2415, 2416, 2417, 2418, 2419];

    if ($blnuser) {
      $attr = $this->getaccess($config['params']['user']);
      $access = $attr[0]->attributes;
    } else {
      $access = $config['access'][0]['attributes'];
    }

    foreach ($arrlvl as $key => $val) {
      if ($access[$val - 1]) {
        array_push($lvl, $key + 1);
      }
    }
    if (!empty($lvl)) {
      $r = implode(",", $lvl);
      $r = "(" . $r . ")";


      return $r;
    }
    return " (0)";
  }
  public function checkapproversetup($config, $approverid, $doc, $alias, $isdashbord = false)
  {

    $dataparams = isset($config['params']['dataparams']);
    $viewaccess = $this->checkAccess($config['params']['user'], 5228);
    $companyid = $config['params']['companyid'];
    $clientid = 0;
    $filterdataparams = "";
    $approverlist = [];
    $roleidlist = [];
    $filter = "";
    $filtersup = "";
    $leftjoin = "";
    $filterself = "";
    $posttype = "";
    $exist = false;
    $showall = false;
    $self = false;
    $no_setup = true;

    if ($dataparams) {
      if (isset($config['params']['dataparams']['clientid'])) {
        $clientid = $config['params']['dataparams']['clientid'];
        $clientname = $config['params']['dataparams']['clientname'];
        if ($clientname != "") {
          if ($clientid != 0) {
            $self = true;
            $filterdataparams .= " and cl.clientid = '$clientid' ";
          }
        }
      }
      if (isset($config['params']['dataparams']['divid'])) {
        $divid = $config['params']['dataparams']['divid'];
        $division = $config['params']['dataparams']['division'];
        if ($division != "") {
          if ($divid != 0) {
            $self = true;
            $filterdataparams .= " and " . $alias . ".divid = '$divid' ";
          }
        }
      }
      if (isset($config['params']['dataparams']['posttype'])) {
        $posttype = $config['params']['dataparams']['posttype'];
      }
    }

    $undersup = $this->coreFunctions->opentable("select * from employee where  supervisorid = '" . $approverid . "'");
    if (!empty($undersup)) {
      if ($undersup[0]->supervisorid != 0) {
        $filtersup = " or (" . $alias . ".supervisorid = '" . $approverid . "' $filterdataparams )";
      }
    }
    // multiapp tagging
    $addjoin = "";
    $status = "";
    switch ($doc) {
      case 'LEAVE':
        $addjoin = " and lt.empid = mul.empid";
        $posttype = ($posttype == 'approved') ? 'A' : 'E';
        $status = " lt.status = '$posttype' or ";
        break;
      case 'OB':
        $addjoin = " and ob.empid = mul.empid";
        $posttype = ($posttype == 'approved') ? 'A' : 'E';
        $status = " ob.status = '$posttype' or ";
        break;
      case 'OT':
        $addjoin = " and ot.empid = mul.empid";
        $posttype = ($posttype === 'approved') ? '2' : '1';
        $status = " ot.otstatus = '$posttype' or ";
        break;
      case 'LOAN':
        $addjoin = " and loan.empid = mul.empid";
        $posttype = ($posttype == 'approved') ? 'A' : 'E';
        $status = " loan.status = '$posttype' or ";
        break;
      case 'CHANGESHIFT':
        $addjoin = " and csapp.empid = mul.empid";
        $posttype = ($posttype == 'approved') ? '1' : '0';
        $status = " csapp.status = '$posttype' or ";
        break;
    }

    if (!empty($doc)) {
      if (!$isdashbord && !$self) {
        if ($doc != 'PORTAL SCHEDULE') {
          $filterself = " or (" . $alias . ".empid = '" . $approverid . "')";
        }
      }

      $mulapp = $this->coreFunctions->opentable("select distinct approverid  from multiapprover where approverid = '" . $approverid . "' and doc = '" . $doc . "' ");
    } else {
      $mulapp = $this->coreFunctions->opentable("select distinct approverid  from multiapprover where approverid = '" . $approverid . "'");
    }
    if ($viewaccess) {
      $showall = true;
      $exist = true;
      goto skipapprover;
    }

    if (!empty($mulapp)) {
      $exist = true;
      $no_setup = false;
      if (!empty($doc)) {
        #Use 
        # Dashboard all application approved
        #Module
        # Create Schedule
        #Report list

        if (!empty($mulapp)) {
          foreach ($mulapp as $mlapp) {
            array_push($approverlist, $mlapp->approverid);
          }
          $approverlist = array_unique($approverlist);
          $approver = implode(",", $approverlist);
          $filter = " and ((mul.approverid in  (" . $approver . ") and mul.doc = '" . $doc . "' $filterdataparams) $filtersup $filterself)";
          $leftjoin = " left join multiapprover as mul on mul.approverid = $approverid and mul.doc = '" . $doc . "' $addjoin ";
        }
      } else {
        //reports na hindi kailangan ng doc
        #Use
        # Employee listing

        $leftjoin = "
        left join (
        select distinct approverid,empid
        from multiapprover ) as mul on mul.empid = " . $alias . ".empid and mul.approverid = '" . $approverid . "'";

        foreach ($mulapp as $mlapp) {
          array_push($approverlist, $mlapp->approverid);
        }
        $approverlist = array_unique($approverlist);
        $approver = implode(",", $approverlist);
        if ($filtersup != "") {
          $filter = " and (((mul.approverid in  (" . $approver . ") or " . $alias . ".supervisorid = '" . $approverid . "')) $filterdataparams )";
        } else {
          $filter = " and (mul.approverid in  (" . $approver . ") $filterdataparams )";
        }
      }
    }

    $role = $this->coreFunctions->opentable("select roleid from emprole where empid=" . $approverid);
    if ($companyid != 58) {
      if (!empty($role)) {
        foreach ($role as $roleid) {
          array_push($roleidlist, $roleid->roleid);
        }
        $exist = true;
        $no_setup = false;
        $role_id = implode(",", $roleidlist);
        if ($filter != "") {
          $filter .= " or (" . $alias . ".roleid in (" . $role_id . ") $filterdataparams $filterself) ";
        } else {
          $filter = " and (( " . $alias . ".roleid in (" . $role_id . ") $filterdataparams ) $filtersup $filterself) ";
        }
      }
    }

    if ($isdashbord && empty($role) && empty($mulapp)) {
      if (!empty($doc)) {
        $moduleapproval = $this->coreFunctions->opentable("select distinct(appro.clientid) as approverid,mval.approverseq,appro.isapprover,appro.issupervisor from moduleapproval as mval 
	              left join approvers as appro on appro.trno = mval.line
	              where mval.modulename = '$doc' and appro.clientid = '" . $approverid . "' ");
        if (!empty($moduleapproval)) {
          $exist = true;
          $both = false;
          if (str_contains($moduleapproval[0]->approverseq, ' or ')) {
            $approversetup = explode(' or ', $moduleapproval[0]->approverseq);
            $both = true;
          } else {
            $approversetup = explode(',', $moduleapproval[0]->approverseq);
          }

          foreach ($approversetup as $appkey => $appsetup) {
            $approversetup[$appkey] = $appsetup == 'Supervisor' ? 'issupervisor' : 'isapprover';
          }

          if (count($approversetup) == 1 || $both) {
            $showall = true;
          } else {
            if ($approversetup[1] == 'isapprover') {
              $showall  = $moduleapproval[0]->isapprover == 1 ? true : false;
            } else {
              $showall = $moduleapproval[0]->issupervisor == 1 ? true : false;
            }
          }
        }
      }
    }
    skipapprover:
    if (empty($filter) || $viewaccess) {
      if ($filterself != "") {
        if ($status != "") {
          $filter .= " and ( $status " . $alias . ".empid = '" . $approverid . "')";
        } else {
          $filter .= " or (" . $alias . ".empid = '" . $approverid . "')";
        }
      }
      if ($filtersup != "") {
        $filter .= $filtersup;
      } else {
        if ($filterdataparams != "") {
          $filter .= $filterdataparams;
        }
      }
    } else {
      if (!$exist) {
        $filter = " and 1=0 ";
      }
    }

    return ['filter' => $filter, 'leftjoin' => $leftjoin, 'exist' => $exist, 'ishowall' => $showall];
  }

  public function generatebarcode($config, $folder)
  {
    return $this->commonsbc->generatebarcode($config, $folder);
  }

  public function generatecntnum($config, $tablename, $doc, $pref, $doclength = 0, $fixseq = 0, $moduledoc = '', $posextraction = false, $pyear = 0, $center = '')
  {
    return $this->commonsbc->generatecntnum($config, $tablename, $doc, $pref, $doclength, $fixseq, $moduledoc, $posextraction, $pyear, $center);
  }

  public function generateShortcutTransaction($config, $fixseq = 0, $sourcedoc = '', $fixprefix = '', $pyear = 0)
  {
    if ($sourcedoc == '') {
      $doc = $config['params']['doc'];
    } else {
      $doc = $sourcedoc;
    }

    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $action = strtoupper($config['params']['action']);
    $user = $config['params']['user'];
    $qry = '';
    $pref = '';
    $referencemodule = '';

    //path
    switch ($doc) {
      case 'PX':
        $path = 'App\Http\Classes\modules\pcf\px';
        $referencemodule = 'Project Costing';
        $pref = 'PCF';
        $doc = "PX";
        break;
      case 'MR':
        $path = 'App\Http\Classes\modules\realestate\pr';
        $referencemodule = 'MATERIAL REQUEST';
        $pref = 'PR';
        $doc = "PR";
        break;
      case 'MI':
        $path = 'App\Http\Classes\modules\construction\\' . strtolower($doc);
        $referencemodule = 'MATERIAL REQUEST';
        $pref = 'MI';
        break;
      case 'DN':
        $path = 'App\Http\Classes\modules\cbbsi\\' . strtolower($doc);
        $referencemodule = 'DELIVERY RECEIPT';
        $pref = 'DN';
        break;
      case 'ST':
        $path = 'App\Http\Classes\modules\cbbsi\\' . strtolower($doc);
        $referencemodule = 'STOCK REQUEST';
        $pref = 'ST';
        break;
      case 'CK':
        $path = 'App\Http\Classes\modules\cbbsi\\' . strtolower($doc);
        $referencemodule = 'DELIVERY RECEIPT';
        $pref = 'CK';
        break;
      case 'SK':
        $path = 'App\Http\Classes\modules\cbbsi\\' . strtolower($doc);
        $referencemodule = 'DELIVERY RECEIPT';
        $prefixes = $this->getPrefixes($doc, $config);
        $pref = $prefixes[0];
        if (isset($config['params']['sjprefix'])) {
          if ($config['params']['sjprefix'] != '') {
            $pref = $config['params']['sjprefix'];
          }
        }
        if (isset($config['params']['sjseq'])) {
          if (floatval($config['params']['sjseq']) != 0) {
            $fixseq = $config['params']['sjseq'];
          }
        }
        break;
      case 'CR':
        $path = 'App\Http\Classes\modules\receivable\\' . strtolower($doc);
        $referencemodule = 'LIFE PLAN AGREEMENT';
        $pref = 'CR';
        break;
      case 'CP':
        $path = 'App\Http\Classes\modules\operation\\' . strtolower($doc);
        $referencemodule = 'APPLICATION FORM';
        $pref = 'LA';
        break;
      case 'SJCR':
        $path = 'App\Http\Classes\modules\sales\sj';
        $referencemodule = 'SALES JOURNAL';
        $pref = 'CR';
        break;
      case 'BSCR':
        $path = 'App\Http\Classes\modules\sales\ai';
        $referencemodule = 'SERVICE INVOICE';
        $pref = 'CR';
        break;
      case 'RRCV':
        $path = 'App\Http\Classes\modules\purchase\rr';
        $referencemodule = 'RECEIVING REPORT';
        $pref = 'CV';
        break;
      case 'PO':
        if ($systemtype == 'ATI') {
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
          $referencemodule = 'CANVASS SHEET';
        } else {
          $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
          $referencemodule = 'CANVASS SHEET';
          if ($companyid == 39) $referencemodule = 'PURCHASE REQUISITION'; //cbbsi
        }
        break;
      case 'RR':
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        $referencemodule = 'PURCHASE ORDER';
        if ($companyid == 39) $referencemodule = 'PO/RT'; //cbbsi
        break;
      case 'SO':
        $path = 'App\Http\Classes\modules\proline\\' . strtolower($doc);
        $referencemodule = 'QUOTATION';
        if ($companyid == 39) { //cbbsi
          $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
          $pref = "SO";
        } else {
          $pref = "JO";
        }


        break;
      case 'SJ':
      case 'DR':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $referencemodule = 'SALES ORDER';
        switch ($companyid) {
          case 15: //nathina
            $pref = "OS";
            break;
          case 17: //unihome
          case 27: //nte
          case 28: //xcomp
          case 36: //rozlab
          case 39: //CBBSI
            if ($doc == 'DR') {
              $path = 'App\Http\Classes\modules\cbbsi\\' . strtolower($doc);
            }
            if (isset($config['params']['sjprefix'])) {
              if ($config['params']['sjprefix'] != '') {
                $pref = $config['params']['sjprefix'];
              }
            }
            if (isset($config['params']['sjseq'])) {
              if (floatval($config['params']['sjseq']) != 0) {
                $fixseq = $config['params']['sjseq'];
              }
            }
            break;
          case 47: //kitchenstar     
            $path = 'App\Http\Classes\modules\kitchenstar\\' . strtolower($doc);
            $pref = 'DR';
            //$fixseq = $this->coreFunctions->getfieldvalue("transnum", "seq", "trno=?", [$config['params']['trno']]); 'remove copy seq from SO

            break;
          case 59: //roosevelt
            $pref = 'DR';
            break;
        }
        break;
      case 'QS':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $referencemodule = 'SALES ACTIVITY';
        $pref = 'QTN';
        break;
      case 'QT':
        switch ($companyid) {
          case 20: //proline
            $path = 'App\Http\Classes\modules\proline\\' . strtolower($doc);
            $referencemodule = 'QUOTATION';
            $pref = 'QTN';
            break;
          default:
            $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
            $referencemodule = 'SALES ACTIVITY';
            break;
        }
        break;
      case 'AC':
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        $referencemodule = 'JOB COMPLETION';
        $pref = "JC";
        $doc = "AC";
        break;
      case 'SR':
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        $referencemodule = 'SERVICE QOUTATION';
        break;
      case 'SV':
        $path = 'App\Http\Classes\modules\payable\\' . strtolower($doc);
        $referencemodule = 'PETTY CASH VOUCHER';
        $this->logConsole(json_encode($config['params']));
        if ($companyid == 43) { //mighty
          if (isset($config['params']['svprefix'])) {
            if ($config['params']['svprefix'] != '') {
              $pref = $config['params']['svprefix'];
            }
          }
        }
        break;
      case 'JP':
        $path = 'App\Http\Classes\modules\production\\' . strtolower($doc);
        $referencemodule = 'FINISHED GOODS - BOM';
        break;
      case 'PG':
        $path = 'App\Http\Classes\modules\production\\' . strtolower($doc);
        $referencemodule = 'JOB ORDER';
        break;
      case 'SQ':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $referencemodule = 'PURCHASE ORDER';
        switch ($companyid) {
          case 12: //afti usd
            $pref = "POD";
            break;
          case 10: //afti
            $pref = "POP";
            break;
          default:
            $pref = "PO";
            break;
        }
        $doc = "PO";
        break;
      case 'AO':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $referencemodule = 'JOB ORDER';
        switch ($companyid) {
          case 12: //afti usd
            $pref = "JOJ";
            break;
          case 10: //afti
            $pref = "JO";
            break;
          default:
            $pref = "JO";
            break;
        }
        $doc = "JB";
        break;
      case 'PR':
        switch ($systemtype) {
          case 'ATI':
            $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
            $referencemodule = 'MFILES UPLOADING';
            if ($fixseq != 0) {
              if ($fixprefix != '') {
                $pref = $fixprefix;
              } else {
                $pref = "PRM";
              }
            } else {
              $pref = "PR";
            }
            break;
          default:
            $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
            $referencemodule = 'PURCHASE ORDER';
            if ($action == 'MAKEJO') {
              $referencemodule = 'JOB ORDER';
              switch ($companyid) {
                case 12: //afti usd
                  $pref = "JOJ";
                  break;
                case 10: //afti
                  $pref = "JO";
                  break;
                default:
                  $pref = "JO";
                  break;
              }
              $doc = "JB";
            } else {
              switch ($companyid) {
                case 12: //afti usd
                  $pref = "POD";
                  break;
                case 10: //afti
                  $pref = "POP";
                  break;
                default:
                  $pref = "PO";
                  break;
              }
              $doc = "PO";
            }

            break;
        }

        break;
      case 'CM':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $referencemodule = 'DELIVERY RECEIPT';

        break;
      case 'CV':
        if ($action == 'GETLOANAPPLICATION') {
          $path = 'App\Http\Classes\modules\lending\\' . strtolower($doc);
          $referencemodule = 'LOAN APPLICATION';
        } else {
          $path = 'App\Http\Classes\modules\payable\\' . strtolower($doc);
          $referencemodule = 'PAYMENT LISTING';
        }

        break;
      case 'CD':
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        $referencemodule = 'PURCHASE REQUISITION';
        break;
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\\' . strtolower($doc);
        $referencemodule = 'TRANSFER REQUEST';
        break;
      case 'STJV': //cdo JV entry
        $path = 'App\Http\Classes\modules\cdo\st';
        $referencemodule = 'STOCK TRANSFER';
        $pref = $fixprefix;
        break;
      case 'ON':
        $path = 'App\Http\Classes\modules\e4c3fe3674108174825a187099e7349f6\on';
        $referencemodule = 'OUTRIGHT INVOICE';
        $prefixes = $this->getPrefixes($doc, $config);
        $pref = $prefixes[0];
        break;
    }

    //qry
    switch ($doc) {
      case 'PX':
        $qry = app($path)->getqssummaryqry($config);
        break;
      case 'STJV':
        $qry = app($path)->getstsummaryqry($config);
        break;
      case 'ST':
        $qry = app($path)->gettrsummaryqry($config);
        break;
      case 'SK':
      case 'CK':
      case 'DN':
        $qry = app($path)->getdrsummaryqry($config);
        break;
      case 'AC':
        $qry = app($path)->getjbsummaryqry($config);
        break;
      case 'SICR':
      case 'ARCR':
        $qry = app($path)->getpaysummaryqry($config);
        break;
      case 'RRCV':
      case 'SJCR':
      case 'BSCR':
        $qry = app($path)->getpaysummaryqry($config);
        break;
      case 'JB':
        if ($config['params']['doc'] == 'PR') {
          $qry = app($path)->getjosummaryqry($config);
        } else {
          $qry = app($path)->getposummaryqry($config);
        }

        break;
      case 'CP':
        $qry = app($path)->getapplicationform($config);
        break;
      case 'CR':
        $qry = app($path)->getcontract($config);
        break;
      case 'CM':
        $qry = app($path)->getrfsummaryqry($config);
        break;
      case 'PO':
        if ($companyid == 39) { //cbbsi
          $qry = app($path)->getprsummaryqry($config);
        } else {
          $qry = app($path)->getposummaryqry($config);
        }
        break;
      case 'CV':
        if ($action == 'GETLOANAPPLICATION') {
          $qry = app($path)->getloanapplication($config);
        } else {
          $qry = app($path)->getplsummaryqry($config);
        }

        break;
      case 'CD':
        $qry = app($path)->getprsummaryqry($config);
        break;
      case 'PR':
        if ($this->companysetup->isconstruction) {
          $qry = app($path)->getmrsummaryqry($config);
        } else {
          $qry = app($path)->getposummaryqry($config);
        }
        break;
      case 'TS':
        $qry = app($path)->gettrsummaryqry($config);
        break;
      case 'SJ':
        $qry = app($path)->getposummaryqry($config);
        if ($companyid == 59) $qry = app($path)->getsosummaryqry($config);
        break;
      case 'ON':
        $qry = app($path)->getdrsummaryqry($config);
        break;
      default:
        $qry = app($path)->getposummaryqry($config);
        break;
    }

    //data
    switch ($doc) {
      case 'PR':
        if ($systemtype == 'ATI') {
          $data = $config['params']['data'];
        } else {
          goto defaultdatahere;
        }
        break;
      case 'PX':
        $data = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno'], $config['params']['trno'], $config['params']['trno']]);
        break;
      default:
        defaultdatahere:
        $data = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        break;
    }

    $headinfo = [];
    $trno = 0;

    try {
      if (!empty($data)) {
        if ($pref == '') {
          $pref = $doc;
        }

        if ($fixseq != 0) {
          $poseq = $pref . $fixseq;
          $checkdocno  = $this->PadJ($poseq, $this->companysetup->getdocumentlength($config['params']), $pyear);
          $existtrno = $this->coreFunctions->getfieldvalue(app($path)->tablenum, "trno", "docno=?", [$checkdocno]);
          if ($existtrno != '') {
            if (floatval($existtrno) != 0) {

              return ['status' => true, 'msg' => $checkdocno . ' already exist.', 'action' => 'showmsg', 'trno' => $existtrno, 'access' => 'view', 'lookupclass' => ''];
            }
          }

          $trno = $this->generatecntnum($config, app($path)->tablenum, $doc, $pref, 0, $fixseq, '', false, $pyear);
        } else {
          switch ($doc) {
            case 'RRCV':
              $trno = $this->generatecntnum($config, app($path)->tablenum, 'CV', $pref, 0, 0, 'CV');
              break;
            case 'SJCR':
            case 'BSCR':
              $trno = $this->generatecntnum($config, app($path)->tablenum, 'CR', $pref, 0, 0, 'CR');
              break;
            case 'STJV':
              $trno = $this->generatecntnum($config, app($path)->tablenum, 'GJ', $pref, 0, 0, 'GJ', false, 0, $data[0]->center);
              $this->coreFunctions->sbcupdate("cntnum", ["dptrno" => $config['params']['trno']], ["trno" => $trno]);
              break;
            default:
              $trno = $this->generatecntnum($config, app($path)->tablenum, $doc, $pref);
              break;
          }
        }

        if ($trno != -1) {
          $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);
          switch ($config['params']['doc']) {
            case 'PX':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'dateid' => date('Y-m-d'), 'potrno' => $config['params']['trno'], 'clientid' => $data[0]->clientid, 'clientname' => $data[0]->clientname];
              break;
            case 'SQ':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'dateid' => date('Y-m-d'), 'sotrno' => $config['params']['trno']];
              break;
            case 'PR':
              switch ($systemtype) {
                case 'ATI':
                  $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'dateid' => date('Y-m-d')];
                  break;
                default:
                  $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'dateid' => date('Y-m-d'), 'sotrno' => $config['params']['trno']];
                  break;
              }
              break;
            case 'AO':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'dateid' => date('Y-m-d')];
              break;
            case 'CP':
              $head =  ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'dateid' => date('Y-m-d'), 'aftrno' => $data[0]->aftrno, 'terms' => $data[0]->terms, 'agent' => $data[0]->agent, 'vattype' => $data[0]->vattype, 'tax' => $data[0]->tax, 'due' => $data[0]->due];
              break;
            case 'CR':
              $head = ['trno' => $trno, 'doc' => $pref, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'dateid' => date('Y-m-d'), 'agent' => $data[0]->agentcode, 'yourref' => $data[0]->docno];
              break;
            case 'SK':
            case 'ON':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'terms' => $data[0]->terms, 'agent' => $data[0]->agent, 'due' => $data[0]->due, 'projectid' => $data[0]->projectid, 'wh' => $data[0]->wh, 'contra' => $data[0]->contra, 'trnxtype' => $data[0]->trnxtype, 'yourref' => $data[0]->yourref, 'ourref' => $data[0]->docno];
              break;
            case 'CK':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'terms' => $data[0]->terms, 'agent' => $data[0]->agent, 'vattype' => $data[0]->vattype, 'tax' => $data[0]->tax, 'due' => $data[0]->due, 'projectid' => $data[0]->projectid, 'wh' => $data[0]->wh];
              break;
            case 'CM':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'terms' => $data[0]->terms, 'agent' => $data[0]->agent, 'vattype' => $data[0]->vattype, 'tax' => $data[0]->tax, 'due' => $data[0]->due, 'projectid' => $data[0]->projectid, 'wh' => $data[0]->wh];
              break;
            case 'DN':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'terms' => $data[0]->terms, 'agent' => $data[0]->agent, 'vattype' => $data[0]->vattype, 'tax' => $data[0]->tax, 'due' => $data[0]->due, 'projectid' => $data[0]->projectid, 'wh' => $data[0]->wh, 'contra' => $data[0]->contra, 'ourref' => $data[0]->docno];
              break;
            case 'ST':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->wh2, 'clientname' => $data[0]->clientname, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'wh' => $data[0]->wh, 'yourref' => $data[0]->docno, 'ourref' => $data[0]->ourref, 'deptid' => $data[0]->deptid, 'trnxtype' => $data[0]->trnxtype];
              break;
            case 'CD':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d'), 'branch' => 0];
              break;
            case 'TS':
              $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d')];
              break;
            case 'SJ':
              if ($companyid == 47) { //kitchenstar
                $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => $data[0]->dateid];
              } else {
                $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d')];
              }
              break;
            default:
              switch ($doc) {
                case 'RRCV':
                case 'SJCR':
                case 'BSCR':
                  $head = ['trno' => $trno, 'doc' => $pref, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex, 'yourref' => $data[0]->yourref, 'branch' => $data[0]->branch];
                  break;
                case 'CV':
                  $head = ['trno' => $trno, 'doc' => $pref, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'dateid' => date('Y-m-d'), 'cur' => $data[0]->cur, 'forex' => $data[0]->forex];
                  break;
                case 'STJV':
                  $head = ['trno' => $trno, 'doc' => 'GJ', 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'dateid' => $data[0]->dateid, 'yourref' => $data[0]->docno, 'rem' => 'Receive Stocks from ' . $data[0]->sourcewh];
                  break;
                default:
                  $head = ['trno' => $trno, 'doc' => $doc, 'docno' => $docno, 'client' => $data[0]->client, 'clientname' => $data[0]->clientname, 'address' => $data[0]->address, 'rem' => $data[0]->rem, 'dateid' => date('Y-m-d')];

                  if ($doc == 'MI') {
                    $head = array_merge($head, [
                      'projectid' => $data[0]->projectid,
                      'wh' => $data[0]->wh
                    ]);
                  }

                  break;
              }
          }

          $config['params']['trno'] = $trno;

          switch ($config['params']['doc']) {
            case 'PX':
              if ($data[0]->yourref == '') {
                $head['poref'] = 'NO PO YET';
              } else {
                $head['poref'] = $data[0]->yourref;
              }

              if ($config['params']['adminid'] != 0) {
                $salesperson_qry = "
                select 
                  ifnull(ag.client, '') as agent, 
                  ifnull(ag.clientname, '') as agentname, 
                  ifnull(ag.clientid, 0) as agentid
                from client as ag
                where ag.clientid = ?";
                $salesperson_res = $this->coreFunctions->opentable($salesperson_qry, [$config['params']['adminid']]);
                if (!empty($salesperson_res)) {
                  $agentid = $salesperson_res[0]->agentid;
                }
                $head['agentid'] = $agentid;
              }

              $date = date("Y-m-d", strtotime($this->getCurrentDate()));
              $datacur = $this->coreFunctions->opentable("select oandaphpusd,oandausdphp from pcfcur where left(dateid,10)='" . $date . "' order by dateid desc limit 1");
              $osphpusd = $this->coreFunctions->datareader("select ifnull(osphpusd,0) as value from pcfcur where osphpusd <> 0 order by dateid desc limit 1", [], '', '', true);

              if (empty($datacur)) {
                $head['oandaphpusd'] = 0;
                $head['oandausdphp'] = 0;
              } else {
                $head['oandaphpusd'] = $datacur[0]->oandaphpusd;
                $head['oandausdphp'] = $datacur[0]->oandausdphp;
              }


              if (empty($osphpusd)) {
                $head['osphpusd'] = 0;
              } else {
                $head['osphpusd'] = $osphpusd;
              }


              break;
            case 'AO':
              $head['yourref'] = $data[0]->yourref;
              $head['client'] = $config['params']['client'];
              $head['clientname'] = $config['params']['clientname'];
              $head['cur'] = $config['params']['cur'];
              $head['forex'] = $config['params']['forex'];
              $head['vattype'] = $config['params']['vattype'];
              $head['tax'] = $config['params']['tax'];
              $head['terms'] = $config['params']['terms'];
              $head['branch'] = $data[0]->branch;
              $head['deptid'] = $data[0]->deptid;
              $head['wh'] = $data[0]->wh;
              $head['due'] = $this->computeterms(date('Y-m-d'), date('Y-m-d'), $config['params']['terms']);
              $head['billcontactid'] = $config['params']['billcontactid'];
              $head['shipcontactid'] = $config['params']['shipcontactid'];
              $head['shipid'] = $config['params']['shipid'];
              $head['billid'] = $config['params']['billid'];
              break;
            case 'SQ':
              $head['yourref'] = $data[0]->yourref;
              $head['wh'] = $this->companysetup->getwh($config['params']);
              $head['client'] = $config['params']['client'];
              $head['clientname'] = $config['params']['clientname'];
              if ($companyid == 12) { //afti usd
                $head['cur'] = $data[0]->cur;
                $head['forex'] = $data[0]->forex;
                $head['vattype'] = $data[0]->vattype;
                $head['tax'] = $data[0]->tax;
              } else {
                $head['cur'] = $config['params']['cur'];
                $head['forex'] = $config['params']['forex'];
                $head['vattype'] = $config['params']['vattype'];
                $head['tax'] = $config['params']['tax'];
              }

              $head['terms'] = $data[0]->terms;
              $head['branch'] = $data[0]->branch;
              $head['deptid'] = $data[0]->deptid;
              $head['due'] = $data[0]->deldate;
              break;
            case 'PR':
              switch ($systemtype) {
                case 'ATI':
                  $requestdate = isset($config['params']['data'][0]['Date Requested']) ? $config['params']['data'][0]['Date Requested'] : '';
                  if ($requestdate != '') {
                    $UNIX_DATE = ($requestdate - 25569) * 86400;
                    $head['dateid'] = gmdate("Y-m-d", $UNIX_DATE);
                  }

                  $head['wh'] = "";
                  $head['yourref'] = $config['params']['data'][0]['Category'];
                  $head['createby'] = $config['params']['user'];
                  $head['client'] = isset($config['params']['client']) ? $config['params']['client'] : '';
                  $head['clientname'] = isset($config['params']['clientname']) ? $config['params']['clientname'] : '';
                  $head['sano'] = isset($config['params']['sano']) ? $config['params']['sano'] : 0;
                  $head['svsno'] = isset($config['params']['svsno']) ? $config['params']['svsno'] : 0;

                  $headinfo['trno']  = $trno;
                  $headinfo['prepared'] =  isset($config['params']['data'][0]['Prepared by']) ? $config['params']['data'][0]['Prepared by'] : '';
                  $headinfo['department'] = isset($config['params']['data'][0]['Department of Requestor']) ? $config['params']['data'][0]['Department of Requestor'] : '';
                  $headinfo['tmpref'] = isset($config['params']['data'][0]['IRF Reference No']) ? $config['params']['data'][0]['IRF Reference No'] : '';
                  break;
                default:
                  $head['wh'] = $this->companysetup->getwh($config['params']);
                  $head['client'] = $config['params']['client'];
                  $head['clientname'] = $config['params']['clientname'];
                  $head['cur'] = $data[0]->cur;
                  $head['forex'] = $data[0]->forex;
                  $head['vattype'] = $config['params']['vattype'];
                  $head['tax'] = $config['params']['tax'];
                  $head['terms'] = $config['params']['terms'];
                  break;
              }

              break;
            case 'SV':
              $head['vattype'] = $data[0]->vattype;
              $head['yourref'] = $data[0]->yourref;
              $head['ourref'] = $data[0]->ourref;
              $head['contra'] = $data[0]->contra;
              $head['projectid'] = $data[0]->headprjid;
              break;
            case 'MR': //realestate
              $head['cur'] = $data[0]->cur;
              $head['forex'] = $data[0]->forex;
              $head['due'] = date('Y-m-d');
              $head['terms'] = $data[0]->terms;
              $head['wh'] = $this->companysetup->getwh($config['params']);
              $head['branch'] = isset($data[0]->branch) ? $data[0]->branch : '';
              $head['projectid'] = $data[0]->projectid;
              $head['phaseid'] = $data[0]->phaseid;
              $head['modelid'] = $data[0]->modelid;
              $head['blklotid'] = $data[0]->blklotid;
              break;
            case 'TS':
              $head['cur'] = $data[0]->cur;
              $head['forex'] = $data[0]->forex;
              $head['yourref'] = $data[0]->yourref;
              $head['ourref'] = $data[0]->docno;
              $head['deptid'] = $data[0]->deptid;
              $head['projectid'] = $data[0]->projectid;
              $head['wh'] = $data[0]->wh2;
              break;
            default:
              switch ($doc) {
                case 'RRCV':
                  $head['vattype'] = $data[0]->vattype;
                  $head['tax'] = $data[0]->tax;
                  $head['ewt'] = $data[0]->ewt;
                  $head['ewtrate'] = $data[0]->ewtrate;
                  break;
                case 'SJCR':
                case 'BSCR':
                  $head['agent'] = $data[0]->agent;
                  break;
                case 'JP':
                case 'PG':
                  $head['clientname'] = $data[0]->clientname;
                  $head['wh'] = $this->companysetup->getwh($config['params']);
                  $head['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
                  if ($doc == 'PG') {
                    $head['ourref'] = $data[0]->docno;
                  }
                  break;
                case 'CV':
                  if ($action == 'GETLOANAPPLICATION') {
                    $head['yourref'] = $data[0]->docno;
                    $head['amount'] = $this->sanitizekeyfield('amount', $data[0]->amount);
                  }

                  break;
                case 'CP':
                case 'SK':
                case 'CM':
                case 'ST':
                case 'DN':
                case 'MI':
                case 'STJV':
                case 'LA':
                case 'ON':
                  break;
                case 'CR':
                  if ($companyid == 34) { //evergreen
                    $q = "select count(distinct trno) as value from(select h.trno from lahead as h left join client as c on c.client = h.client left join cntnuminfo as cnt on cnt.trno = h.trno
                    where h.doc ='CR' and c.clientid = " . $data[0]->clientid . " and cnt.cptrno = " . $data[0]->cptrno . "
                    union all select h.trno from glhead as h left join hcntnuminfo as cnt on cnt.trno = h.trno where h.doc='CR' and h.clientid  =  " . $data[0]->clientid . " and cnt.cptrno = " . $data[0]->cptrno . ") as a";

                    $countcr = $this->coreFunctions->datareader($q);

                    if ($countcr == 0) {
                      $q = "select format(dp+pf,2) as value from heainfo where trno = " . $data[0]->aftrno;
                      $dppf = $this->coreFunctions->datareader($q);
                      $head['amount'] = $dppf;
                    } else {
                      $head['amount'] = 0;
                    }
                  }
                  $head['amount'] = $this->sanitizekeyfield('amount', $head['amount']);
                  break;
                default:
                  $head['cur'] = $data[0]->cur;
                  $head['forex'] = $data[0]->forex;
                  $head['due'] = date('Y-m-d');
                  $head['terms'] = $data[0]->terms;

                  if ($companyid == 47) { //kitchenstar
                    $head['wh'] = $data[0]->wh;
                  } else {
                    $head['wh'] = $this->companysetup->getwh($config['params']);
                  }

                  $head['branch'] = isset($data[0]->branch) ? $data[0]->branch : '';
                  if ($doc == 'PO') if ($head['branch'] == '') $head['branch'] = 0;
                  if ($doc == 'CD') {
                    if ($head['branch'] == '') $head['branch'] = 0;
                    $head['ourref'] = $data[0]->ourref;
                    $head['yourref'] = $data[0]->docno;
                  }
                  if ($doc == 'PO') {
                    if ($companyid == 3) { //conti
                      $head['yourref'] = $data[0]->docno;
                    } else {
                      $head['yourref'] = $data[0]->yourref;
                    }
                    $head['ourref'] = $data[0]->ourref;
                    $head['wh'] = $data[0]->swh;
                  }
                  break;
              }
              break;
          }

          switch ($doc) {
            case 'RR':
            case 'AC':
              $head['yourref'] = $data[0]->yourref;
              $head['ourref'] = $data[0]->ourref;
              $head['shipto'] = $data[0]->shipto;

              if ($companyid == 43) { //mighty
                $head['rem'] = $data[0]->hrem;
              }

              if ($companyid == 39) { //cbbsi
                $head['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
                $head['rem'] = $data[0]->hrem;
              } else {
                $head['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AP1']);
              }

              $head['billcontactid'] = $data[0]->billcontactid;
              $head['shipcontactid'] = $data[0]->shipcontactid;
              $head['deptid'] = $data[0]->deptid;

              if (isset($data[0]->shipid)) {
                $head['shipid'] = $data[0]->shipid;
              }

              if (isset($data[0]->billid)) {
                $head['billid'] = $data[0]->billid;
              }

              if (isset($data[0]->vattype)) {
                $head['vattype'] = $data[0]->vattype;
              } else {
                $head['vattype'] = 'NON-VATABLE';
              }

              if (isset($data[0]->tax)) {
                $head['tax'] = $data[0]->tax;
              } else {
                $head['tax'] = 0;
              }

              if ($config['params']['companyid'] == 3) {
                $head['wh'] = $data[0]->wh;
              }

              switch ($companyid) {
                case 36: //rozlab
                case 39: //cbbsi
                case 43: //mighty
                  $head['ourref'] = $data[0]->docno;
                  break;
              }

              break;
            case 'QS':
            case 'QT':
              switch ($config['params']['companyid']) {
                case 20: //proline
                  break;
                default:
                  $head['agent'] = $data[0]->agent;
                  $head['position'] = $data[0]->position;
                  $head['industry'] = $data[0]->industry;
                  $head['optrno'] = $data[0]->trno;
                  $head['billid'] = $data[0]->billid;
                  $head['shipid'] = $data[0]->shipid;
                  $head['billcontactid'] = $data[0]->billcontactid;
                  $head['shipcontactid'] = $data[0]->shipcontactid;
                  $head['deldate'] = date('Y-m-d');
                  break;
              }
              break;
            case 'SR':
              $head['yourref'] = $data[0]->yourref;
              $head['agent'] = $data[0]->agent;
              $head['deptid'] = $data[0]->deptid;
              $head['qtrno'] = $data[0]->qtrno;
              $head['shipid'] = $data[0]->shipid;
              $head['billid'] = $data[0]->billid;
              $head['billcontactid'] = $data[0]->billcontactid;
              $head['shipcontactid'] = $data[0]->shipcontactid;
              $head['vattype'] = $data[0]->vattype;
              $head['tax'] =  $data[0]->tax;
              $head['due'] =  $data[0]->deldate;
              $head['sgdrate'] =  $data[0]->qssgd;

              $headinfo['trno']  = $trno;
              $headinfo['termsdetails'] =  $data[0]->termsdetails;
              $headinfo['taxdef'] =  $data[0]->taxdef;
              break;
            case 'SJ':
            case 'DR':
              $head['yourref'] = $data[0]->yourref;
              $head['ourref'] = $data[0]->ourref;
              $head['agent'] = $data[0]->agent;
              $head['shipto'] = isset($data[0]->shipto) ? $data[0]->shipto : '';
              $head['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [app($path)->defaultContra]);
              $head['mlcp_freight'] = $data[0]->mlcp_freight;
              $head['ms_freight'] = $data[0]->ms_freight;
              $head['projectid'] = $data[0]->hprojectid;

              if ($companyid == 36) { //rozlab
                $head['tax'] = 12;
                $head['vattype'] = 'VATABLE';
              }

              if ($companyid == 39) { //cbbsi
                $head['vattype'] = 'NON-VATABLE';
                $head['tax'] = 0;
                $head['trnxtype'] =  $data[0]->trnxtype;
              }
              break;

            case 'SO':
            case 'MI':
              switch ($companyid) {
                case 20: //proline
                case 43: //mighty
                  $head['ourref'] = $data[0]->docno;
                  break;
                case 39: //cbbsi
                  $head['ourref'] = $data[0]->docno;
                  $head['agent'] = $data[0]->agent;

                  break;
              }
              break;
          }

          $isproject = $this->companysetup->getisproject($config['params']);

          if ($isproject) {
            $viewall = $this->checkAccess($config['params']['user'], 2232);
            if ($viewall == '0') {
              $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
              $head['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
            }
          }

          switch ($config['params']['doc']) {
            case 'SQ':
            case 'PR':
              switch ($systemtype) {
                case 'ATI':
                  $url = '/module/purchase/po';
                  break;
                default:
                  $path = 'App\Http\Classes\modules\purchase\\' . strtolower("PO");
                  $url = '/module/purchase/po';
                  if ($action == 'MAKEJO') {
                    $path = 'App\Http\Classes\modules\purchase\\' . strtolower("JB");
                    $url = '/module/purchase/jb';
                  }
                  break;
              }
              break;
            case 'AO':
              $path = 'App\Http\Classes\modules\purchase\\' . strtolower("JB"); //path for serveitem
              break;
            default:
              switch ($doc) {
                case 'RRCV':
                case 'CV':
                  if ($action == 'GETLOANAPPLICATION') {
                    $path = 'App\Http\Classes\modules\lending\cv'; //path for serveitem
                    $url = '/module/payable/cv';
                  } else {
                    $path = 'App\Http\Classes\modules\payable\cv'; //path for serveitem
                    $url = '/module/payable/cv';
                  }
                  break;
                case 'SJCR':
                case 'BSCR':
                  $path = 'App\Http\Classes\modules\receivable\cr'; //path for serveitem
                  $url = '/module/receivable/cr';
                  break;
                case 'STJV':
                  $path = 'App\Http\Classes\modules\accounting\gj';
                  $url = '/module/payable/gj';
                  break;
              }

              break;
          }

          $head['createby'] = $user;
          $head['createdate'] = $this->getCurrentTimeStamp();
          $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);

          if ($inserthead) {
            //info inserting
            switch ($config['params']['doc']) {
              case 'SJ':
              case 'DR':
                switch ($companyid) {
                  case 15: //nathina
                  case 17: //unihome
                  case 28: //xcomp
                  case 39: //CBBSI
                    $savesj = $viewall = $this->checkAccess($config['params']['user'], 172);
                    if ($savesj) {
                      $this->coreFunctions->sbcinsert('cntnuminfo', ['trno' => $trno]);
                    }
                    break;
                }
                break;
              case 'JP':
                $cntnuminfodata = [
                  'trno' => $trno,
                  'itemid' => $data[0]->bitemid,
                  'batchsize' => $data[0]->batchsize,
                  'yield' => $data[0]->yield,
                  'uom2' => $data[0]->uom2
                ];
                if ($companyid == 36) { //rozlab
                  $cntnuminfodata['yield'] = $config['params']['qty'];
                }
                $this->coreFunctions->sbcinsert('cntnuminfo', $cntnuminfodata);
                break;
              case 'CR':
                $cntnuminfodata = [
                  'trno' => $trno,
                  'cptrno' => $data[0]->cptrno
                ];
                $this->coreFunctions->sbcinsert('cntnuminfo', $cntnuminfodata);
                break;
              case 'QS':
                $this->getcreditinfo($config, app($path)->head);
                $currentdate = $this->getCurrentDate();
                $currenttime = date('H:i:s', strtotime($this->getCurrentTimeStamp()));
                //calllogs 
                $checkingcalllogs = $this->coreFunctions->datareader("select trno as value from qscalllogs where trno = ? ", [$trno], '', true);
                if ($checkingcalllogs == 0) {
                  $data3 = [
                    'trno' => $trno,
                    'line' => 1,
                    'contact' => $data[0]->contactname,
                    'probability' => '25%',
                    'dateid' => $currentdate,
                    'starttime' =>   $currenttime
                  ];
                  $this->coreFunctions->sbcinsert('qscalllogs', $data3);
                }
                break;
              case 'RR':
                $cntnuminfodata['trno'] = $trno;
                if ($companyid == 43) { //mighty
                  $cntnuminfodata['tripdate'] = $head['dateid'];
                }
                $this->coreFunctions->sbcinsert('cntnuminfo', $cntnuminfodata);
                break;
              case 'MI':
                $this->coreFunctions->sbcinsert('cntnuminfo', ['trno' => $trno]);
            }

            //stock insert
            switch ($config['params']['doc']) {
              case 'PX':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE PCF ' . $referencemodule, app($path)->tablelogs);
                foreach ($data as $key2 => $value) {
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['qty'] = $data[$key2]->rrqty;
                  $config['params']['data']['srp'] = $data[$key2]->rrcost;
                  $config['params']['barcode'] =  $data[$key2]->barcode;
                  $config['params']['data']['amt'] = 0;
                  // $latestcost = app($path)->getlatestprice($config);
                  // if (!empty($latestcost['data'])) {
                  //   $config['params']['data']['amt'] = $latestcost['data'][0]->amt;
                  // } else {
                  //   $config['params']['data']['amt'] = 0;
                  // }
                  $return = app($path)->additem('insert', $config, true);
                  if ($return['status']) {
                    $tbl = 'headinfotrans';
                    $isposted = $this->isposted2($data[$key2]->trno, 'transnum');
                    if ($isposted) {
                      $tbl = 'hheadinfotrans';
                    }
                    $this->coreFunctions->sbcupdate($tbl, ['dtctrno' => $trno], ['trno' => $data[$key2]->trno]);
                  }
                } // end foreach
                break;
              case 'ST':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE ST ' . $referencemodule, app($path)->tablelogs);
                foreach ($data as $key2 => $value) {
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['disc'] = $data[$key2]->disc;
                  $config['params']['data']['qty'] = $data[$key2]->rrqty;
                  $config['params']['data']['wh'] = $data[$key2]->wh;
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  $config['params']['data']['amt'] = $data[$key2]->rrcost;
                  $return = app($path)->additem('insert', $config, true);
                  if ($return['status']) {
                    if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                      $data2 = ['isqty' => 0, 'iss' => 0, 'ext' => 0];
                      $line = $return['row'][0]->line;
                      $config['params']['trno'] = $trno;
                      $config['params']['line'] = $line;
                      $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                      app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line);
                    }
                  }
                } // end foreach
                break;
              case 'AO':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE JO ' . $referencemodule, app($path)->tablelogs);
                foreach ($data as $key2 => $value) {
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['disc'] = '';
                  $config['params']['data']['qty'] = $data[$key2]->isqty;
                  $config['params']['data']['wh'] = $data[$key2]->wh;
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  $config['params']['data']['poref'] = $data[$key2]->yourref;
                  if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
                    if (floatval($head['forex']) != 1) {
                      $config['params']['data']['amt'] = $data[$key2]->tpdollar;
                    } else {
                      $config['params']['data']['amt'] = $data[$key2]->tpphp;
                    }
                  }

                  $return = app($path)->additem('insert', $config);

                  if ($msg = '') {
                    $msg = $return['msg'];
                  } else {
                    $msg = $msg . $return['msg'];
                  }


                  if ($return['status']) {
                    if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                      $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                      $line = $return['row'][0]->line;
                      $config['params']['trno'] = $trno;
                      $config['params']['line'] = $line;
                      $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                      app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line);
                    }
                  }
                } // end foreach
                break;
              case 'SQ':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE PO ' . $referencemodule, app($path)->tablelogs);
                foreach ($data as $key2 => $value) {
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['data']['disc'] = '';
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['qty'] = $data[$key2]->pending;
                  $config['params']['data']['wh'] = $head['wh'];
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['sorefx'] = $data[$key2]->trno;
                  $config['params']['data']['solinex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  if (floatval($head['forex']) != 1) {
                    $config['params']['data']['amt'] = $data[$key2]->famt;
                  } else {
                    $config['params']['data']['amt'] = $data[$key2]->tpphp;
                  }

                  if ($config['params']['companyid'] == 12) { //afti usd
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                  }

                  $config['params']['data']['projectid'] = $data[$key2]->projectid;
                  $config['params']['data']['poref'] = $data[$key2]->yourref;
                  $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
                  $config['params']['data']['sortline'] = $data[$key2]->sortline;

                  $return = app($path)->additem('insert', $config, true);
                  if ($return['status']) {
                    if (app($path)->setservedsqitems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                      $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                      $line = $return['row'][0]->line;
                      $config['params']['trno'] = $trno;
                      $config['params']['line'] = $line;
                      $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                      app($path)->setservedsqitems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                    }
                  }
                } //end for loop
                break;
              case 'SO':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE ' . $pref . ' ' . $referencemodule, app($path)->tablelogs);

                foreach ($data as $key2 => $value) {

                  $config['params']['data']['amt'] = $data[$key2]->isamt;
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['disc'] = $data[$key2]->disc;
                  $config['params']['data']['qty'] = $data[$key2]->isqty;
                  $config['params']['data']['wh'] = $this->companysetup->getwh($config['params']);
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  $config['params']['data']['poref'] = $data[$key2]->yourref;
                  $return = app($path)->additem('insert', $config, true);

                  // add item failed
                  if ($msg = '') {
                    $msg = $return['msg'];
                  } else {
                    $msg = $msg . $return['msg'];
                  }

                  if ($return['status']) {
                    $this->coreFunctions->sbcupdate('hqthead', ['sotrno' => $trno], ['trno' => $data[$key2]->trno]);
                  }
                } // end foreach
                break;

              case 'PR':
                $logdesc = 'PO';
                if ($systemtype == 'ATI') {
                  $logdesc = 'REQUEST FROM ';
                }
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . " - MAKE " . $logdesc . " " . $referencemodule, app($path)->tablelogs);

                switch ($systemtype) {
                  case 'ATI':
                    $this->coreFunctions->sbcinsert("headinfotrans", $headinfo);

                    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$this->companysetup->getwh($config['params'])]);
                    if ($whid == '') {
                      return ['status' => false, 'msg' => 'Failed to generate request, invalid default wh', 'trno' => $trno];
                    }
                    foreach ($data as $key2 => $value) {

                      $qty = $this->sanitizekeyfield("amt", isset($value['Qty Needed']) ? $value['Qty Needed'] : 0);
                      $computedata = $this->computestock(0, '', $qty, 1);

                      $qry = "select line as value from " . app($path)->stock . " where trno=? order by line desc limit 1";
                      $line = $this->coreFunctions->datareader($qry, [$trno]);
                      if ($line == '') {
                        $line = 0;
                      }
                      $line = $line + 1;
                      $stock = [
                        'trno' => $trno,
                        'line' => $line,

                        'rrqty' => $qty,
                        'qty' => $computedata['qty'],
                        'ext' => $computedata['ext'],
                        'encodeddate' => $this->getCurrentTimeStamp(),
                        'encodedby' => $config['params']['user'],
                        'ismanual' => 0
                      ];
                      if ($fixseq != 0) {
                        $ctrlno = $fixseq . '-' . $line;
                      } else {
                        $ctrlno = $this->coreFunctions->getfieldvalue(app($path)->tablenum, "seq", "trno=?", [$trno]) . '-' . $line;
                      }

                      $stockinfo = [
                        'trno' => $trno,
                        'line' => $line,
                        'itemdesc' => $value['Item'],
                        'itemdesc2' => $value['Item'],
                        'unit' => isset($value['UOM']) ? $value['UOM'] : '',
                        'specs' => isset($value['Specifications']) ? $value['Specifications'] : '',
                        'specs2' => isset($value['Specifications']) ? $value['Specifications'] : '',
                        'purpose' => isset($value['Purpose']) ? $value['Purpose'] : '',
                        'requestorname' => isset($value['Requestor']) ? $value['Requestor'] : '',
                        'rem' => isset($value['Remarks']) ? $value['Remarks'] : '',
                        'ctrlno' => $ctrlno,
                        'isasset' => isset($value['Asset?']) ? strtoupper($value['Asset?']) : '',
                      ];

                      $dateneeded = isset($value['Date Needed']) ? $value['Date Needed'] : '';
                      if ($dateneeded != '') {
                        $UNIX_DATE = ($dateneeded - 25569) * 86400;
                        $stockinfo['dateneeded'] = gmdate("Y-m-d", $UNIX_DATE);
                      }

                      foreach ($stock as $key => $value) {
                        $stock[$key] = $this->sanitizekeyfield($key, $value);
                      }
                      foreach ($stockinfo as $key => $value) {
                        $stockinfo[$key] = $this->sanitizekeyfield($key, $value);
                      }
                      $result = $this->coreFunctions->sbcinsert(app($path)->stock, $stock);
                      if ($result) {
                        $result = $this->coreFunctions->sbcinsert("stockinfotrans", $stockinfo);
                        if (!$result) {
                          return ['status' => false, 'msg' => 'Failed to input stockinfotrans', 'trno' => $trno];
                        }
                      } else {
                        return ['status' => false, 'msg' => 'Failed to input stock', 'trno' => $trno];
                      }
                    } // end for loop
                    break;
                  default:
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . " - PICK " . $referencemodule, app($path)->tablelogs);
                    foreach ($data as $key2 => $value) {
                      $config['params']['trno'] = $trno;
                      $config['params']['data']['itemid'] = $data[$key2]->itemid;
                      $config['params']['data']['disc'] = '';
                      $config['params']['data']['uom'] = $data[$key2]->uom;
                      $config['params']['data']['qty'] = $data[$key2]->pending;
                      $config['params']['data']['wh'] = $head['wh'];
                      $config['params']['data']['loc'] = '';
                      $config['params']['data']['expiry'] = '';
                      $config['params']['data']['rem'] = '';
                      if ($action == 'MAKEJO') {
                        $config['params']['data']['prrefx'] = $data[$key2]->refx;
                        $config['params']['data']['prlinex'] = $data[$key2]->linex;
                      } else {
                        $config['params']['data']['refx'] = $data[$key2]->refx;
                        $config['params']['data']['linex'] = $data[$key2]->linex;
                      }

                      $config['params']['data']['ref'] = $data[$key2]->ref;
                      if (floatval($head['forex']) != 1) {
                        $config['params']['data']['amt'] = $data[$key2]->famt;
                      } else {
                        $config['params']['data']['amt'] = $data[$key2]->rrcost;
                      }
                      $config['params']['data']['poref'] = $data[$key2]->yourref;
                      $return = app($path)->additem('insert', $config, true);
                      if ($return['status']) {
                        if ($data[$key2]->refx != 0) {
                          if ($action == 'MAKEJO') {
                            if (app($path)->setprserveditems($data[$key2]->refx, $data[$key2]->linex, 'qty') == 0) {
                              $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                              $line = $return['row'][0]->line;
                              $config['params']['trno'] = $trno;
                              $config['params']['line'] = $line;
                              $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                              app($path)->setprserveditems($data[$key2]->refx, $data[$key2]->linex, app($path)->hqty);
                            }
                          } else {
                            if (app($path)->setserveditems($data[$key2]->refx, $data[$key2]->linex, 'qty') == 0) {
                              $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                              $line = $return['row'][0]->line;
                              $config['params']['trno'] = $trno;
                              $config['params']['line'] = $line;
                              $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                              app($path)->setserveditems($data[$key2]->refx, $data[$key2]->linex, app($path)->hqty);
                            }
                          }
                        }
                      }
                    } //end for loop
                    break;
                }
                break;
              case 'MR':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE ' . $pref . ' ' . $referencemodule, app($path)->tablelogs);

                foreach ($data as $key2 => $value) {
                  $config['params']['data']['amt'] = $data[$key2]->isamt;
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['disc'] = $data[$key2]->disc;
                  $config['params']['data']['qty'] = $data[$key2]->prqty;
                  $config['params']['data']['wh'] = $this->companysetup->getwh($config['params']);
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['projectid'] = $data[$key2]->projectid;
                  $config['params']['data']['blklotid'] = $data[$key2]->blklotid;
                  $config['params']['data']['phaseid'] = $data[$key2]->phaseid;
                  $config['params']['data']['modelid'] = $data[$key2]->modelid;
                  $config['params']['data']['amenity'] = $data[$key2]->amenity;
                  $config['params']['data']['subamenity'] = $data[$key2]->subamenity;
                  $return = app($path)->additem('insert', $config, true);

                  if ($return['status']) {
                    if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                      $data2 = ['rrqty' => 0, 'qty' => 0, 'ext' => 0];
                      $line = $return['row'][0]->line;
                      $config['params']['trno'] = $trno;
                      $config['params']['line'] = $line;
                      $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                      app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line);
                    }
                  }
                } // end foreach
                break;
              case 'SV': //ladetail
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);
                $total = 0;
                foreach ($data as $key2 => $value) {
                  $config['params']['data']['acno'] = $data[$key2]->acno;
                  $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
                  $config['params']['data']['db'] = $data[$key2]->amt;
                  $config['params']['data']['cr'] = 0;
                  $config['params']['data']['postdate'] = $data[$key2]->postdate;
                  $config['params']['data']['rem'] = $data[$key2]->rem;
                  $config['params']['data']['project'] = $data[$key2]->projectid;
                  $config['params']['data']['client'] = $data[$key2]->client;
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  $config['params']['data']['rem'] = $data[$key2]->drem;
                  $return = app($path)->additem('insert', $config, true);
                  $total = $total + $data[$key2]->amt;
                  if ($data[$key2]->trno != 0) {
                    $this->coreFunctions->execqry("update hpqdetail set isok =1 where trno =? and line =?", "update", [$data[$key2]->trno, $data[$key2]->line]);
                  }
                }

                if ($total != 0) { //credit account auto-balance
                  $config['params']['data']['acno'] = $data[0]->contra;
                  $config['params']['data']['acnoname'] = $data[0]->contraname;
                  $config['params']['data']['db'] = 0;
                  $config['params']['data']['cr'] = $total;
                  $config['params']['data']['postdate'] = $data[0]->dateid;
                  $config['params']['data']['rem'] = '';
                  $config['params']['data']['project'] = $data[0]->headprjid;
                  $config['params']['data']['client'] = $data[0]->client;
                  $config['params']['data']['refx'] = 0;
                  $config['params']['data']['linex'] = 0;
                  $config['params']['data']['ref'] = '';
                  $return = app($path)->additem('insert', $config, true);
                }
                $this->coreFunctions->execqry("update " . app($path)->head . " set ref ='" . $data[0]->docno . "' where trno = " . $trno, "update");
                break;

              case 'SJ':
              case 'JP':
              case 'DR':
                $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);

                foreach ($data as $key2 => $value) {
                  if ($fifoexpiration) {
                    $return_result = app($path)->insertfifoexpiration($config, $value, $head['wh'], true);
                    if (empty($return_result)) {
                      goto defaultsjentryhere;
                    }
                  } else {
                    defaultsjentryhere:
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    if ($doc == 'JP') {
                      $config['params']['data']['qty2'] = $data[$key2]->isqty;
                    }
                    $config['params']['data']['wh'] = $data[$key2]->swh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';

                    if ($doc == 'SJ' || $doc == 'DR') {
                      $config['params']['data']['refx'] = $data[$key2]->trno;
                      $config['params']['data']['linex'] = $data[$key2]->line;
                      $config['params']['data']['ref'] = $data[$key2]->docno;
                    }

                    $config['params']['data']['amt'] = $data[$key2]->isamt;
                    $config['params']['data']['projectid'] = $data[$key2]->projectid;
                    if (isset($data[$key2]->itemdesc)) $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;

                    $return = app($path)->additem('insert', $config, true);
                    if ($return['status']) {
                      if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                        $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                        $line = $return['row'][0]->line;
                        $config['params']['trno'] = $trno;
                        $config['params']['line'] = $line;
                        $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                        app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                      }
                    }
                  }
                } //end for loop
                break;
              case 'TS':
                $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE ' . $pref . ' ' . $referencemodule, app($path)->tablelogs);
                foreach ($data as $key2 => $value) {
                  $config['params']['data']['uom'] = $data[$key2]->uom;
                  $config['params']['data']['itemid'] = $data[$key2]->itemid;
                  $config['params']['trno'] = $trno;
                  $config['params']['data']['disc'] = $data[$key2]->disc;
                  $config['params']['data']['qty'] = $data[$key2]->rrqty;
                  $config['params']['data']['wh'] = $data[$key2]->wh2;
                  $config['params']['data']['loc'] = '';
                  $config['params']['data']['expiry'] = '';
                  $config['params']['data']['rem'] = $data[$key2]->rem2;
                  $config['params']['data']['refx'] = $data[$key2]->trno;
                  $config['params']['data']['linex'] = $data[$key2]->line;
                  $config['params']['data']['ref'] = $data[$key2]->docno;
                  $config['params']['data']['amt'] = $data[$key2]->rrcost;
                  $return = app($path)->additem('insert', $config, true);
                } // end foreach
                break;
                defaulthere:
              default: //lastock
                switch ($doc) {
                  case 'STJV':
                    $this->coreFunctions->logconsole('pasok naman sa STJV');
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - RECEIVE TRANSFERS ' . $referencemodule, app($path)->tablelogs);

                    foreach ($data as $key2 => $value) {
                      //inventory
                      $dueacct = $data[$key2]->sourceass;
                      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
                      if (strtoupper($data[$key2]->category) != "MC UNIT") {
                        $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN2']);
                        $dueacct = $data[$key2]->sourcerev;
                      }

                      //inventory
                      $config['params']['data']['acno'] = $invacct;
                      $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$invacct]);
                      $config['params']['data']['db'] = $data[$key2]->ext;
                      $config['params']['data']['cr'] = 0;
                      $config['params']['data']['fdb'] = 0;
                      $config['params']['data']['fcr'] = 0;
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['postdate'] = $data[$key2]->dateid;
                      $config['params']['data']['client'] = $data[$key2]->client;
                      $return = app($path)->additem('insert', $config, true);

                      //due
                      $config['params']['data']['acno'] = $dueacct;
                      $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$dueacct]);
                      $config['params']['data']['cr'] = $data[$key2]->ext;
                      $config['params']['data']['db'] = 0;
                      $config['params']['data']['fdb'] = 0;
                      $config['params']['data']['fcr'] = 0;
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['postdate'] = $data[$key2]->dateid;
                      $config['params']['data']['client'] = $data[$key2]->wh;
                      $return = app($path)->additem('insert', $config, true);
                    }

                    $return = app($path)->posttrans($config);

                    break;
                  case 'CV':
                    if ($action == 'GETLOANAPPLICATION') {
                      $loantrno = $data[0]->trno;
                      //entries
                      $data2 = $this->distributeloanentry($config, $loantrno, $trno);
                      // var_dump($data2);
                      // return 0;

                      $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK LOAN ' . $referencemodule, app($path)->tablelogs);
                      foreach ($data2 as $key2 => $value) {
                        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid = ?", [$data2[$key2]['acnoid']]);
                        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acnoid = ?", [$data2[$key2]['acnoid']]);
                        $config['params']['data']['db'] = $data2[$key2]['db'];
                        $config['params']['data']['cr'] = $data2[$key2]['cr'];
                        $config['params']['data']['fdb'] = 0;
                        $config['params']['data']['fcr'] = 0;
                        $config['params']['data']['postdate'] = $data2[$key2]['postdate'];
                        $config['params']['data']['project'] = 0;
                        $config['params']['data']['client'] = $data2[$key2]['client'];
                        $config['params']['data']['refx'] = 0;
                        $config['params']['data']['linex'] = 0;
                        $config['params']['data']['ref'] =  $data2[$key2]['ref'];
                        $config['params']['data']['rem'] = $data2[$key2]['rem'];

                        $return = app($path)->additem('insert', $config, true);
                        if ($return['status']) {
                          $this->coreFunctions->sbcupdate('transnum', ['cvtrno' => $trno], ['trno' => $loantrno]);
                          $this->coreFunctions->sbcupdate('cntnum', ['dptrno' => $loantrno], ['trno' => $trno]);
                        }
                      }
                    } else {
                      $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK PL ' . $referencemodule, app($path)->tablelogs);
                      foreach ($data as $key2 => $value) {
                        $config['params']['data']['acno'] = $data[$key2]->acno;
                        $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
                        if (floatval($data[$key2]->db) != 0) {
                          $config['params']['data']['db'] = 0;
                          $config['params']['data']['cr'] = $data[$key2]->bal;
                          $config['params']['data']['fdb'] = 0;
                          $config['params']['data']['fcr'] = abs($data[$key2]->fdb);
                        } else {
                          $config['params']['data']['db'] = $data[$key2]->bal;
                          $config['params']['data']['cr'] = 0;
                          $config['params']['data']['fdb'] = $data[$key2]->fdb;
                          $config['params']['data']['fcr'] = 0;
                        }
                        $config['params']['data']['postdate'] = $data[$key2]->dateid;
                        $config['params']['data']['project'] = $data[$key2]->projectid;
                        $config['params']['data']['client'] = $data[$key2]->client;
                        $config['params']['data']['refx'] = $data[$key2]->trno;
                        $config['params']['data']['linex'] = $data[$key2]->line;
                        $config['params']['data']['ref'] = $data[$key2]->ref;
                        if ($companyid == 39) { //cbbsi
                          $config['params']['data']['rem'] = $data[$key2]->rem;
                        } else {
                          $config['params']['data']['rem'] = '';
                        }

                        $return = app($path)->additem('insert', $config, true);
                        if ($return['status']) {
                          $pltrno = $data[0]->pltrno;
                          $this->coreFunctions->sbcupdate('transnum', ['cvtrno' => $trno], ['trno' => $pltrno]);
                        }
                      }
                    }

                    break;
                  case 'RRCV':
                  case 'SJCR':
                  case 'BSCR':
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - MAKE PAYMENT ' . $referencemodule, app($path)->tablelogs);
                    foreach ($data as $key2 => $value) {
                      $config['params']['data']['acno'] = $data[$key2]->acno;
                      $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
                      if (floatval($data[$key2]->db) != 0) {
                        $config['params']['data']['db'] = 0;
                        $config['params']['data']['cr'] = $data[$key2]->bal;
                        $config['params']['data']['fdb'] = 0;
                        $config['params']['data']['fcr'] = abs($data[$key2]->fdb);
                      } else {
                        $config['params']['data']['db'] = $data[$key2]->bal;
                        $config['params']['data']['cr'] = 0;
                        $config['params']['data']['fdb'] = $data[$key2]->fdb;
                        $config['params']['data']['fcr'] = 0;
                      }
                      $config['params']['data']['postdate'] = $data[$key2]->postdate;
                      $config['params']['data']['project'] = 0; //$data[$key2]->projectid;
                      $config['params']['data']['deptid'] = 0; // $data[$key2]->deptid;
                      $config['params']['data']['branch'] = 0; //$data[$key2]->branch;
                      $config['params']['data']['client'] = $data[$key2]->client;
                      $config['params']['data']['refx'] = $data[$key2]->trno;
                      $config['params']['data']['linex'] = $data[$key2]->line;
                      $config['params']['data']['ref'] = $data[$key2]->docno;
                      $config['params']['data']['rem'] = $data[$key2]->drem;
                      $return = app($path)->additem('insert', $config, true);
                    }
                    break;
                  case 'CK':
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);

                    foreach ($data as $key2 => $value) {
                      $config['params']['data']['uom'] = $data[$key2]->uom;
                      $config['params']['data']['itemid'] = $data[$key2]->itemid;
                      $config['params']['trno'] = $trno;
                      $config['params']['data']['disc'] = $data[$key2]->disc;
                      $config['params']['data']['qty'] = $data[$key2]->isqty;
                      $config['params']['data']['wh'] = $data[$key2]->wh;
                      $config['params']['data']['loc'] = '';
                      $config['params']['data']['expiry'] = '';
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['refx'] = $data[$key2]->trno;
                      $config['params']['data']['linex'] = $data[$key2]->line;
                      $config['params']['data']['ref'] = $data[$key2]->docno;
                      $config['params']['data']['amt'] = $data[$key2]->isamt;
                      $config['params']['data']['projectid'] = $data[$key2]->projectid;
                      $config['params']['data']['cost'] = $data[$key2]->cost;

                      $return = app($path)->additem('insert', $config, true);
                      if ($return['status']) {
                        if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                          $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                          $line = $return['row'][0]->line;
                          $config['params']['trno'] = $trno;
                          $config['params']['line'] = $line;
                          $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                          app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line);
                        }
                      }
                    } // end foreach

                    break;
                  case 'DN':
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);

                    foreach ($data as $key2 => $value) {
                      $config['params']['data']['uom'] = $data[$key2]->uom;
                      $config['params']['data']['itemid'] = $data[$key2]->itemid;
                      $config['params']['trno'] = $trno;
                      $config['params']['data']['disc'] = $data[$key2]->disc;
                      $config['params']['data']['qty'] = $data[$key2]->isqty;
                      $config['params']['data']['wh'] = $data[$key2]->wh;
                      $config['params']['data']['loc'] = '';
                      $config['params']['data']['expiry'] = '';
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['refx'] = $data[$key2]->trno;
                      $config['params']['data']['linex'] = $data[$key2]->line;
                      $config['params']['data']['ref'] = $data[$key2]->docno;
                      $config['params']['data']['amt'] = $data[$key2]->isamt;
                      $config['params']['data']['projectid'] = $data[$key2]->projectid;
                      $config['params']['data']['cost'] = 0;

                      $return = app($path)->additem('insert', $config, true);
                      if ($return['status']) {
                        if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                          $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                          $line = $return['row'][0]->line;
                          $config['params']['trno'] = $trno;
                          $config['params']['line'] = $line;
                          $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                          app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line);
                        }
                      }
                    } // end foreach

                    break;
                  case 'CM':
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);

                    foreach ($data as $key2 => $value) {
                      $config['params']['data']['uom'] = $data[$key2]->uom;
                      $config['params']['data']['itemid'] = $data[$key2]->itemid;
                      $config['params']['trno'] = $trno;
                      $config['params']['data']['disc'] = $data[$key2]->disc;
                      $config['params']['data']['qty'] = $data[$key2]->isqty;
                      $config['params']['data']['wh'] = $data[$key2]->wh;
                      $config['params']['data']['loc'] = '';
                      $config['params']['data']['expiry'] = '';
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['refx'] = $data[$key2]->refx;
                      $config['params']['data']['linex'] = $data[$key2]->linex;
                      $config['params']['data']['ckrefx'] = $data[$key2]->trno;
                      $config['params']['data']['cklinex'] = $data[$key2]->line;
                      $config['params']['data']['ref'] = $data[$key2]->ref;
                      $config['params']['data']['amt'] = $data[$key2]->isamt;
                      $config['params']['data']['projectid'] = $data[$key2]->projectid;
                      $config['params']['data']['cost'] = 0;

                      $return = app($path)->additem('insert', $config, true);
                      if ($return['status']) {
                        if (app($path)->setservedrqitems($data[$key2]->refx, $data[$key2]->linex, $data[$key2]->trno, $data[$key2]->line) == 0) {
                          $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                          $line = $return['row'][0]->line;
                          $config['params']['trno'] = $trno;
                          $config['params']['line'] = $line;
                          $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                          app($path)->setservedrqitems($data[$key2]->refx, $data[$key2]->linex, $data[$key2]->trno, $data[$key2]->line);
                        }
                      }
                    } // end foreach

                    break;
                  case 'SR':
                    $this->coreFunctions->sbcinsert("headinfotrans", $headinfo);
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);
                    foreach ($data as $key2 => $value) {
                      $config['params']['trno'] = $trno;
                      $config['params']['data']['itemid'] = $data[$key2]->itemid;
                      $config['params']['data']['uom'] = $data[$key2]->uom;
                      $config['params']['data']['disc'] = $data[$key2]->disc;
                      $config['params']['data']['qty'] = $data[$key2]->rrqty;
                      $config['params']['data']['wh'] = $head['wh'];
                      $config['params']['data']['loc'] = '';
                      $config['params']['data']['expiry'] = '';
                      $config['params']['data']['rem'] = '';
                      $config['params']['data']['refx'] = $data[$key2]->trno;
                      $config['params']['data']['linex'] = $data[$key2]->line;
                      $config['params']['data']['insurance'] = $data[$key2]->insurance;
                      $config['params']['data']['delcharge'] = $data[$key2]->delcharge;
                      if ($doc == 'AC') {
                        $config['params']['data']['sorefx'] = $data[$key2]->refx;
                        $config['params']['data']['solinex'] = $data[$key2]->linex;
                      }
                      $config['params']['data']['ref'] = $data[$key2]->docno;
                      $config['params']['data']['amt'] = $data[$key2]->rrcost;
                      $config['params']['data']['stageid'] = $data[$key2]->stageid;
                      $config['params']['data']['projectid'] = $data[$key2]->projectid;
                      $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;

                      $return = app($path)->additem('insert', $config, true);
                      if ($return['status']) {
                        if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                          $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                          $line = $return['row'][0]->line;
                          $config['params']['trno'] = $trno;
                          $config['params']['line'] = $line;
                          $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                          app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                        }
                      }
                    } //end for loop
                    break;
                  case 'CP':
                  case 'LA':
                    $aftrno = $data[0]->aftrno;
                    $this->coreFunctions->sbcupdate('heahead', ['catrno' => 0], ['trno' => $aftrno]);
                    $this->coreFunctions->sbcupdate('heahead', ['catrno' => $trno], ['trno' => $aftrno]);
                    $qry = "insert into cntnum_picture select $trno,line,title,picture,encodeddate,encodedby from transnum_picture where trno =" . $aftrno;
                    $this->coreFunctions->execqry($qry, "insert");
                    break;
                  case 'SK':
                    $drtrno = $data[0]->trno;
                    $this->coreFunctions->sbcupdate('cntnum', ['svnum' => $trno], ['trno' => $drtrno]);
                    $dn = $this->coreFunctions->opentable("select distinct trno from glstock where refx = " . $drtrno);
                    if (!empty($dn)) {
                      foreach ($dn as $k => $v) {
                        $this->coreFunctions->sbcupdate('cntnum', ['svnum' => $trno], ['trno' => $dn[$k]->trno]);
                      }
                    }
                    break;
                  case 'ON':
                    $drtrno = $data[0]->trno;
                    $this->coreFunctions->sbcupdate('cntnum', ['svnum' => $trno], ['trno' => $drtrno]);
                    break;
                  case 'CR':
                    break;
                  //
                  //default doc here for insert stock
                  //
                  default:
                    $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - PICK ' . $referencemodule, app($path)->tablelogs);

                    foreach ($data as $key2 => $value) {
                      if (($config['params']['companyid'] == 20 && $doc == 'QT') || ($config['params']['companyid'] == 43 && $doc == 'MI')) { //proline, mighty
                        $config['params']['trno'] = $trno;
                        $config['params']['data']['itemid'] = $data[$key2]->itemid;
                        $config['params']['data']['uom'] = $data[$key2]->uom;
                        $config['params']['data']['disc'] = $data[$key2]->disc;
                        $config['params']['data']['qty'] = $data[$key2]->isqty;
                        $config['params']['data']['amt'] = $data[$key2]->isamt;

                        if ($companyid == 43) { //mighty
                          $config['params']['data']['wh'] = $data[$key2]->wh;
                          $config['params']['data']['refx'] = $data[$key2]->trno;
                          $config['params']['data']['linex'] = $data[$key2]->line;
                          $config['params']['data']['ref'] = $data[$key2]->docno;
                        }

                        $return = app($path)->additem('insert', $config, true);
                        if ($return['status']) {
                          if ($companyid == 20) app($path)->copyQTDetails($data[$key2]->trno, $trno); //proline
                          if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                            $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                          }
                        }
                      } else {
                        $config['params']['trno'] = $trno;
                        $config['params']['data']['itemid'] = $data[$key2]->itemid;
                        $config['params']['data']['uom'] = $data[$key2]->uom;
                        $config['params']['data']['disc'] = $data[$key2]->disc;
                        $config['params']['data']['qty'] = $data[$key2]->rrqty;


                        if ($companyid == 40) { //cdo
                          $config['params']['data']['loc'] = $this->coreFunctions->getfieldvalue("rrstatus", "loc", "itemid =? and whid=?", [$data[$key2]->itemid, $data[$key2]->whid], "dateid desc");;
                        } else {
                          $config['params']['data']['loc'] = '';
                        }

                        $config['params']['data']['expiry'] = '';
                        $config['params']['data']['rem'] = '';

                        if ($companyid == 3 && $doc == 'PO') { //conti - pick canvass
                          $config['params']['data']['cdrefx'] = $data[$key2]->trno;
                          $config['params']['data']['cdlinex'] = $data[$key2]->line;
                        } else {
                          if ($companyid == 39) { //cbbsi
                            if (strtoupper($data[$key2]->doc) == 'RT') {
                              $config['params']['data']['rtrefx'] = $data[$key2]->trno;
                              $config['params']['data']['rtlinex'] = $data[$key2]->line;
                            } else {
                              $config['params']['data']['refx'] = $data[$key2]->trno;
                              $config['params']['data']['linex'] = $data[$key2]->line;
                            }
                          } else {
                            $config['params']['data']['refx'] = $data[$key2]->trno;
                            $config['params']['data']['linex'] = $data[$key2]->line;
                          }
                        }

                        if ($doc == 'AC') {
                          $config['params']['data']['sorefx'] = $data[$key2]->refx;
                          $config['params']['data']['solinex'] = $data[$key2]->linex;
                        }
                        if ($doc == 'PG') {
                          $config['params']['data']['loc'] = $data[$key2]->lotno;
                          $config['params']['data']['rem'] = $data[$key2]->rem;
                        }
                        if ($doc == 'PO' && $companyid == 3) { //conti
                          $config['params']['data']['rem'] = (isset($data[$key2]->rem)) ? $data[$key2]->rem : '';
                        }
                        switch ($companyid) {
                          case 36: //rozlab
                            if ($doc == 'PG') {
                              $config['params']['data']['expiry'] = $data[$key2]->expirydate;
                            }
                            break;
                        }

                        $config['params']['data']['ref'] = $data[$key2]->docno;
                        $config['params']['data']['amt'] = $data[$key2]->rrcost;
                        $config['params']['data']['stageid'] = $data[$key2]->stageid;
                        $config['params']['data']['projectid'] = $data[$key2]->projectid;

                        switch ($config['params']['doc']) {
                          case 'RR':
                            if ($companyid == 3) { //conti
                              $config['params']['data']['wh'] = $data[$key2]->swh;
                            } else {
                              goto defaultstockwhhere;
                            }
                            break;
                          default:
                            defaultstockwhhere:
                            $config['params']['data']['wh'] = $head['wh'];
                            break;
                        }

                        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
                          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
                          if ($doc == 'AC' || $doc == 'RR') {
                            $config['params']['data']['poref'] = $data[$key2]->yourref;
                          }
                        }

                        $config['params']['data']['itemdesc'] = (isset($data[$key2]->itemdesc)) ? $data[$key2]->itemdesc : '';

                        $return = app($path)->additem('insert', $config, true);
                        if ($return['status']) {

                          switch ($doc) {
                            case 'RR':
                              if ($companyid == 39) { //cbbsi
                                if (strtoupper($data[$key2]->doc) == 'RT') {
                                  if ($this->setserveditemsTempRR($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                                    $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                                    $line = $return['row'][0]->line;
                                    $config['params']['trno'] = $trno;
                                    $config['params']['line'] = $line;
                                    $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                                    $this->setserveditemsTempRR($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                                  }
                                } else {
                                  if ($this->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                                    $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                                    $line = $return['row'][0]->line;
                                    $config['params']['trno'] = $trno;
                                    $config['params']['line'] = $line;
                                    $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                                    $this->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                                  }
                                }
                              } else {
                                if ($this->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                                  $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                                  $line = $return['row'][0]->line;
                                  $config['params']['trno'] = $trno;
                                  $config['params']['line'] = $line;
                                  $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                                  $this->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                                }
                              }

                              break;
                            case 'JP':
                              break;
                            case 'PG':
                              $this->coreFunctions->execqry("update glhead set invtagging = ? where trno =?", 'update', [$trno, $data[$key2]->trno]);
                              break;
                            default:
                              $line = $return['row'][0]->line;
                              if (app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                                $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                                $config['params']['trno'] = $trno;
                                $config['params']['line'] = $line;
                                $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                                app($path)->setserveditems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                              }

                              if ($doc == 'PO' && $companyid == 3) { //conti
                                if (app($path)->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, 'qty') == 0) {
                                  $data2 = [app($path)->dqty => 0, app($path)->hqty => 0, 'ext' => 0];
                                  $config['params']['trno'] = $trno;
                                  $config['params']['line'] = $line;
                                  $this->coreFunctions->sbcupdate(app($path)->stock, $data2, ['trno' => $trno, 'line' => $line]);
                                  app($path)->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line, app($path)->hqty);
                                }
                              }

                              if ($doc == 'QS') {
                                $this->coreFunctions->execqry("update attendee set optrno = ? where optrno =?", 'update', [$trno, $data[$key2]->trno]);
                                $rem = $this->coreFunctions->getfieldvalue("hstockinfotrans", "rem", "trno=? and line =?", [$data[$key2]->trno, $data[$key2]->line]);
                                if (strlen($rem) != 0) {
                                  $this->coreFunctions->sbcinsert("stockinfotrans", ["rem" => $rem, "trno" => $trno, "line" => $line]);
                                }
                              }
                              break;
                          }
                        }
                      }
                    } //end for loop
                    break;
                }

                break;
            }
          } else {
            $this->coreFunctions->execqry('delete from ' . app($path)->tablenum . " where trno=?", 'delete', [$trno]);
          }

          switch ($config['params']['doc']) {
            case 'SQ':
              return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module'];
              break;
            case 'PR':
              if ($systemtype == 'ATI') {
                return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'loaddocument' => true];
              } else {
                return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module'];
              }
              break;
            case 'SO':
              return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'loaddocument' => true];
              break;
            case 'AO':
              return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => '/module/purchase/jb', 'moduletype' => 'module'];
              break;
            case 'CR':
              return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => ''];
              break;
            case 'MR':
              return ['status' => true, 'msg' => $docno . ' successfully created.'];
              break;
            default:
              switch ($doc) {
                case 'RRCV':
                case 'SJCR':
                case 'BSCR':
                  return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module'];
                  break;
                default:
                  if ($companyid == 20 && $config['params']['doc'] == 'QT') { //proline
                    return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => '/module/proline/so', 'moduletype' => 'module'];
                  } else {
                    return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => ''];
                  }
                  break;
              }

              break;
          }
        }
      } else {
        return ['status' => false, 'msg' => 'No data found'];
      }
    } catch (Exception $ex) {
      $this->coreFunctions->LogConsole(substr($ex, 0, 1000));
      if ($trno != 0) {
        switch ($doc) {
          case 'RRCV':
          case 'SJCR':
          case 'BSCR':
          case 'CV':
          case 'STJV':
            $this->coreFunctions->execqry('delete from ' . app($path)->tablenum . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->head . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->detail . " where trno=?", 'delete', [$trno]);
            break;
          case 'CP':
          case 'LA':
            $this->coreFunctions->execqry('delete from ' . app($path)->tablenum . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->head . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('update heahead set catrno = 0 where catrno=?', 'update', [$trno]);
            break;
          case 'CR':
            $this->coreFunctions->execqry('delete from ' . app($path)->tablenum . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->head . " where trno=?", 'delete', [$trno]);
            break;
          default:
            $this->coreFunctions->execqry('delete from ' . app($path)->tablenum . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->head . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from ' . app($path)->stock . " where trno=?", 'delete', [$trno]);
            $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
            break;
        }

        if (app($path)->tablenum == 'cntnum') {
          $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        } else {
          $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        }
      }
      return ['status' => false, 'msg' => ' ' . substr($ex, 0, 1000)];
    }
  }

  public function updatetranstype($data)
  {
    foreach ($data as $key => $value) {
      switch ($value->doc) {
        case 'SD':
          $value->transtype = 'SJ - DEALER';
          break;
        case 'SE':
          $value->transtype = 'SJ - BRANCH';
          break;
        case 'SF':
          $value->transtype = 'SJ - ONLINE';
          break;
        case 'SH':
          $value->transtype = 'SPECIAL PARTS ISSUANCE';
          break;
      }
    }
    return $data;
  }

  public function   updateardepodate($config, $unpost)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    $status = false;

    if ($unpost) {
      switch ($doc) {
        case 'DS':
          $check = $this->coreFunctions->datareader("select ds.refx as value from gldetail as ds left join incentives as i on i.ptrno=ds.refx where ds.trno=? and ds.refx<>0 and (i.ag2release is not null or i.agrelease is not null or i.clientrelease is not null) limit 1", [$trno]);
          if ($check) {
            return false;
          }
          break;

        case 'CR':
          $check = $this->coreFunctions->datareader("select cr.trno as value from glhead as cr left join incentives as i on i.ptrno=cr.trno where cr.trno=? and (i.ag2release is not null or i.agrelease is not null or i.clientrelease is not null)", [$trno]);
          if ($check) {
            return false;
          }
          break;
      }
    }

    $delete_qty = "delete from incentives where ptrno=?";

    $insert_qry = "insert into incentives (ptrno, trno, line, depodate, acnoid, clientid, agentid, agentcom, agentid2, amt, doc)
    select d.trno as ptrno, ar.trno, ar.line, h.dateid, ar.acnoid, ifnull(ar.clientid,0), ifnull(ar.agentid,0), ag.comm, ifnull(ag2.clientid,0), ar.db-ar.cr as amt, arh.doc
    from gldetail as d left join arledger as ar on ar.trno=d.refx and ar.line=d.linex
    left join glhead as h on h.trno=d.trno
    left join glhead as arh on arh.trno=ar.trno
    left join client as ag on ag.clientid=ar.agentid
    left join client as ag2 on ag2.clientid=ag.parent
    where d.trno=? and d.refx<>0 and ar.bal=0 and arh.doc in ('SD','SF') and ifnull(ar.agentid,0)<>0";

    switch ($doc) {
      case 'CR':
        if ($unpost) {
          $status = $this->coreFunctions->execqry($delete_qty, 'delete', [$trno]);
        } else {
          $check = $this->coreFunctions->datareader("select d.trno as value from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=" . $trno . " and left(coa.alias,2) = 'CR'");
          if (!$check) {
            $status = $this->coreFunctions->execqry($insert_qry, 'insert', [$trno]);
          } else {
            return true;
          }
        }
        break;

      case 'DS':
        if ($unpost) {
          $cr = $this->coreFunctions->opentable("select distinct refx from gldetail where trno=? and refx<>0", [$trno]);
          foreach ($cr as $k => $v) {
            $status = $this->coreFunctions->execqry($delete_qty, 'delete', [$v->refx]);
          }
        } else {
          $arr_cr = [];
          $cr = $this->coreFunctions->opentable("select distinct refx from gldetail where trno=? and refx<>0", [$trno]);
          foreach ($cr as $k => $v) {
            $check = $this->coreFunctions->datareader("select ifnull(count(cr.trno),0) as value from gldetail as d left join coa on coa.acnoid=d.acnoid left join crledger as cr on cr.trno=d.trno and cr.line=d.line where d.trno=? and left(coa.alias,2)='CR' and cr.depodate is null", [$v->refx]);
            if ($check) {
            } else {
              $unposted_check = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from ladetail where refx=? and trno<>?", [$v->refx, $trno]);
              if (!$unposted_check) {
                array_push($arr_cr, $v->refx);
              }
            }
          }

          if (!empty($arr_cr)) {
            foreach ($arr_cr as $ar => $av) {
              $status = $this->coreFunctions->execqry($insert_qry, 'insert', [$av]);
            }
          }
          return true;
        }
        break;
    }

    return $status;
  }

  public function getlatestcostTS($config, $barcode, $client, $center, $trno, $location = '')
  {

    $companyid = $config['params']['companyid'];
    $filterloc = '';
    if ($location != '') {
      $filterloc = " and stock.loc='" . $location . "'";
    }

    if ($this->companysetup->getisdefaultuominout($config['params'])) {
      if ($config['params']['doc'] == 'PC') { // remove filter ng trno
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,'' as disc,uom from(
          select head.docno,head.dateid,
            (stock.cost/uom.factor) as amt,uom.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 $filterloc
            UNION ALL
            select head.docno,head.dateid,(stock.cost/uom.factor) as amt,
            uom.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 $filterloc
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);
      } else {
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,'' as disc,uom from(
          select head.docno,head.dateid,
          (stock.cost*uom.factor) as amt,uom.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 and cntnum.trno <> ? $filterloc
            UNION ALL
            select head.docno,head.dateid,(stock.cost*uom.factor) as amt,
            uom.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 and cntnum.trno <> ? $filterloc
            order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
      }
    } else {
      switch ($config['params']['doc']) {
        case 'PC':
          $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt, round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,'' as disc,uom from(
            select head.docno,head.dateid,
              stock.cost as amt,item.uom,stock.disc
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where cntnum.center = ? and item.barcode = ?
              and stock.rrcost <> 0 $filterloc
              UNION ALL
              select head.docno,head.dateid,stock.cost as amt,
              item.uom,stock.disc from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno
              where cntnum.center = ? and item.barcode = ?
              and stock.rrcost <> 0 $filterloc
              order by dateid desc limit 5) as tbl order by dateid desc limit 1";
          $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);
          break;


        default:
          if ($companyid == 21 && $config['params']['doc'] == 'TS') { //kinggeorge
            $data = $this->coreFunctions->opentable("select '' as docno, null as dateid, 0 as amt, 0 as defamt,'' as disc, uom from item where barcode=?", [$barcode]);
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
          }
          $defaultamt = 'stock.cost';
          if ($companyid == 39 && $config['params']['doc'] == 'ST') { //cbbsi
            $defaultamt = 'item.amt9';
          }

          $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,'' as disc,uom from(
            select head.docno,head.dateid,
              $defaultamt as amt,item.uom,stock.disc
              from lahead as head
              left join lastock as stock on stock.trno = head.trno
              left join cntnum on cntnum.trno=head.trno
              left join item on item.itemid = stock.itemid
              where cntnum.center = ? and item.barcode = ?
              and stock.rrcost <> 0 and cntnum.trno <> ? $filterloc
              UNION ALL
              select head.docno,head.dateid,
              $defaultamt as amt,
              item.uom,stock.disc from glhead as head
              left join glstock as stock on stock.trno = head.trno
              left join item on item.itemid = stock.itemid
              left join client on client.clientid = head.clientid
              left join cntnum on cntnum.trno=head.trno
              where cntnum.center = ? and item.barcode = ?
              and stock.rrcost <> 0 and cntnum.trno <> ? $filterloc
              order by dateid desc limit 5) as tbl order by dateid desc limit 1";
          $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
          break;
      }
    }

    if (!empty($data)) {

      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function opendtstock($trno)
  {
    $qry = "select * from (select stock.trno, stock.line, stock.docstatusid,
          stock.issueid, issue.issues, stock.detailid, detail.details, stock.rem, stock.dateid,
          users.username as usertype, useraccess.username, '' as bgcolor, statuslist.status as statusdoc
          from dt_dtstock as stock
          left join dt_status on dt_status.id=stock.docstatusid
          left join dt_statuslist as statuslist on statuslist.id=dt_status.statusdoc
          left join users on users.idno=stock.usertypeid
          left join useraccess on useraccess.userid=stock.userid
          left join dt_issues as issue on issue.id=stock.issueid
          left join dt_details as detail on detail.id=stock.detailid
          where stock.trno=?
          union all
          select stock.trno, stock.line, stock.docstatusid,
          stock.issueid, issue.issues, stock.detailid, detail.details, stock.rem, stock.dateid,
          users.username as usertype, useraccess.username, '' as bgcolor, statuslist.status as statusdoc
          from hdt_dtstock as stock
          left join dt_status on dt_status.id=stock.docstatusid
          left join dt_statuslist as statuslist on statuslist.id=dt_status.statusdoc
          left join users on users.idno=stock.usertypeid
          left join useraccess on useraccess.userid=stock.userid
          left join dt_issues as issue on issue.id=stock.issueid
          left join dt_details as detail on detail.id=stock.detailid
          where stock.trno=?) tbl order by dateid desc";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  }

  public function unpostingheadinfotrans($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into headinfotrans (trno, inspo, deldate, ispartial, instructions, period, isvalid, ovaliddate, termsdetails, proformainvoice, proformadate,leadfrom,leadto,leaddur,advised,taxdef, department, prepared, tmpref,dp,cod,outstanding,isadv,paymentid,reqtypeid, mop1, mop2, deadline, sentdate, pickupdate, pdeadline,truckid, plateno, helperid, checkerid, driverid, isro, printdate, rem2, categoryid,isshipmentnotif,shipmentnotif,trnxtype,approvalreason,wh2,waybill,carrier,isinvoice,sdate1,sdate2,strdate1,strdate2,assessedid,nodays,mileage,itemid,gendercaller,loaddate,dtctrno) 
    select trno, inspo, deldate, ispartial, instructions, period, isvalid, ovaliddate, termsdetails, proformainvoice, proformadate,leadfrom,leadto,leaddur,advised,taxdef, department, prepared, tmpref,dp,cod,outstanding,isadv,paymentid,reqtypeid, mop1, mop2, deadline, sentdate, pickupdate, pdeadline,truckid, plateno, helperid, checkerid, driverid, isro, printdate, rem2, categoryid,isshipmentnotif,shipmentnotif,trnxtype,approvalreason,wh2,waybill,carrier,isinvoice,sdate1,sdate2,strdate1,strdate2,assessedid,nodays,mileage,itemid,gendercaller,loaddate,dtctrno from hheadinfotrans where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingheadinfotrans($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hheadinfotrans (trno, inspo, deldate, ispartial, instructions, period, isvalid, ovaliddate, termsdetails, proformainvoice, proformadate,leadfrom,leadto,leaddur,advised,taxdef, department, prepared, tmpref,dp,cod,outstanding,isadv,paymentid,reqtypeid, mop1, mop2, deadline, sentdate, pickupdate, pdeadline,truckid, plateno, helperid, checkerid, driverid, isro, printdate, rem2, categoryid,isshipmentnotif,shipmentnotif,trnxtype,approvalreason,wh2,waybill,carrier,isinvoice,sdate1,sdate2,strdate1,strdate2,assessedid,nodays,mileage,itemid,gendercaller,loaddate,dtctrno) 
    select trno, inspo, deldate, ispartial, instructions, period, isvalid, ovaliddate, termsdetails, proformainvoice, proformadate,leadfrom,leadto,leaddur,advised,taxdef, department, prepared, tmpref,dp,cod,outstanding,isadv,paymentid, reqtypeid, mop1, mop2, deadline, sentdate, pickupdate, pdeadline,truckid, plateno, helperid, checkerid, driverid, isro, printdate, rem2, categoryid,isshipmentnotif,shipmentnotif,trnxtype,approvalreason,wh2,waybill,carrier,isinvoice,sdate1,sdate2,strdate1,strdate2,assessedid,nodays,mileage,itemid,gendercaller,loaddate,dtctrno from headinfotrans where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function getclientid($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    $head = 'lahead';
    $hhead = 'glhead';
    switch ($config['params']['doc']) {
      case 'PO':
      case 'QS':
      case 'QT':
      case 'SR':
      case 'OP':
      case 'RF':
      case 'SO':
        $head = strtolower($config['params']['doc']) . 'head';
        $hhead = 'h' . strtolower($config['params']['doc']) . 'head';

        $clientid = $this->coreFunctions->datareader("
        select cl.clientid as value from " . $head . " as head
        left join client as cl on cl.client = head.client
        where trno = ?
        union all 
        select cl.clientid as value from " . $hhead . "  as head
        left join client as cl on cl.client = head.client
        where trno = ?", [$trno, $trno]);
        break;

      case 'JB': // JOB ORDER
        $head = 'johead';
        $hhead = 'hjohead';

        $clientid = $this->coreFunctions->datareader("
        select cl.clientid as value from " . $head . " as head
        left join client as cl on cl.client = head.client
        where trno = ?
        union all 
        select cl.clientid as value from " . $hhead . "  as head
        left join client as cl on cl.client = head.client
        where trno = ?", [$trno, $trno]);
        break;

      default:
        $clientid = $this->coreFunctions->datareader("
        select cl.clientid as value from " . $head . " as head
        left join client as cl on cl.client = head.client
        where trno = ?
        union all 
        select cl.clientid as value from " . $hhead . "  as head
        left join client as cl on cl.clientid = head.clientid
        where trno = ?", [$trno, $trno]);
        break;
    }



    return $clientid;
  }

  public function unpostingqscalllogs($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into qscalllogs (trno, line, dateid, starttime, endtime, rem, calltype,contact,probability,editby,editdate) 
            select trno, line, dateid, starttime, endtime, rem, calltype,contact,probability,editby,editdate from hqscalllogs where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingqscalllogs($config)
  {
    $trno = $config['params']['trno'];
    $qry = "insert into hqscalllogs (trno, line, dateid, starttime, endtime,status, rem, calltype,contact,probability,editby,editdate) 
            select trno, line, dateid, starttime, endtime,status, rem, calltype,contact,probability,editby,editdate from qscalllogs where trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function getexchangerate($curfrom, $curto)
  {
    $data = $this->coreFunctions->opentable("select rate from exchangerate where curfrom = '$curfrom' and curto = '$curto'  ");
    if ($data) {
      return floatval($data[0]->rate);
    } else {
      return 0;
    }
  }

  public function checkreportdate($date, $format)
  {
    $date = preg_replace('/[\W\s\/]+/', '-', $date);
    $d = DateTime::createFromFormat($format, $date);
    if ($d && $d->format($format) === $date) {
      return true;
    } else {
      return false;
    }
  }

  public function invaliddatereport()
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute;top;150px;left;400px;'>
          <div><i class='far fa-frown' style='font-size:120px;color:#1E1E1E;'></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E;'>INVALID DATE.</div>
        </div>
      </div>
    ";
  }

  public function emptydata($config)
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>NO TRANSACTION.</div>
        </div>
      </div>
    ";
  }

  public function withoutcontractprice($config)
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>Input Latest Contract Price in Project Management module.</div>
        </div>
      </div>
    ";
  }

  public function custommsgreport($config, $msg)
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>" . $msg . "</div>
        </div>
      </div>
    ";
  }

  public function parameterRequired($config, $parameter = '', $layout = '')
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>$parameter is required for $layout layout.</div>
        </div>
      </div>
    ";
  }

  public function notapplicable()
  {
    return "
      <div style='position:relative;'>
        <div class='text-center' style='position:absolute; top:150px; left:400px;'>
          <div><i class='far fa-frown' style='font-size:120px; color: #1E1E1E';></i></div>
          <br>
          <div style='font-size:32px; color:#1E1E1E'>NOT APPLICABLE.</div>
        </div>
      </div>
    ";
  }

  public function validatePassword($string)
  {
    if (preg_match('/[A-Z]/', $string) && preg_match('/[0-9]/', $string) && preg_match('/[!@#$%^&*]/', $string) && strlen($string) >= 8) {
      return true; // valid
    } else {
      return false; // invalid
    }
  }

  public function duplicateTransaction($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $qry = '';
    $dqry = '';
    $pref = '';
    $url = '';
    $sourcetrno = $config['params']['row']['trno'];

    switch ($doc) {
      case 'PV':
        $path = 'App\Http\Classes\modules\payable\\' . strtolower($doc);
        $particularspath = 'App\Http\Classes\modules\tableentry\entryparticulars';
        $url = '/module/payable/' . strtolower($doc);
        break;
      case 'GJ':
      case 'GD':
      case 'GC':
        $path = 'App\Http\Classes\modules\accounting\\' . strtolower($doc);
        $url = '/module/accounting/' . strtolower($doc);
        $particularspath = 'App\Http\Classes\modules\tableentry\entryparticulars';
        break;
      case 'QT':
        $path = 'App\Http\Classes\modules\proline\\' . strtolower($doc);
        $url = '/module/proline/' . strtolower($doc);
        break;
      case 'CD':
        $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
        $url = '/module/ati/' . strtolower($doc);
        break;
      case 'SO':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $url = '/module/sales/' . strtolower($doc);
        break;
    }
    $config['params']['trno'] =  $config['params']['row']['trno'];
    $isposted = $this->isposted($config);

    switch ($doc) {
      case 'CD';
      case 'SO':
        break;
      default:
        if (!$isposted) {
          return ['status' => false, 'msg' => 'The data cannot be duplicated. Not yet posted.'];
        }
        break;
    }

    switch ($doc) {
      case 'QT':
      case 'CD':
      case 'SO':
        break;
      default:
        $withref  = $this->coreFunctions->datareader("select trno as value from gldetail where trno =? and refx <>0 limit 1", [$config['params']['trno']]);

        if (floatval($withref) != 0) {
          return ['status' => false, 'msg' => 'Data cannot be duplicated with a reference document.'];
        }
        break;
    }

    $referencedocno = $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$config['params']['trno']]);
    $pref = $this->GetPrefix($referencedocno);

    $datahead = app($path)->openhead($config);
    $datadetail = [];
    $qtinfo = [];

    switch ($doc) {
      case 'QT':
        break;
      case 'CD':
      case 'SO':
        $datadetail = app($path)->openstock($config['params']['trno'], $config);
        break;
      default:
        $datadetail = app($path)->opendetail($config['params']['trno'], $config);
        break;
    }

    if ($doc == 'PV') {
      $config['params']['tableid'] = $config['params']['trno'];
      $dataparticulars = app($particularspath)->loaddata($config);
    }

    if (!empty($datahead)) {
      if ($pref == '') {
        $pref = $doc;
      }

      $trno = $this->generatecntnum($config, app($path)->tablenum, $doc, $pref);

      if ($trno != -1) {

        $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);

        $head = [
          'trno' => $trno,
          'doc' => $doc,
          'docno' => $docno,
          'client' => $datahead[0]->client,
          'clientname' => $datahead[0]->clientname,
          'address' => isset($datahead[0]->address) ? $datahead[0]->address : '',
          'rem' => $datahead[0]->rem,
          'dateid' => date('Y-m-d'),

          'yourref' => $datahead[0]->yourref,
          'ourref' => $datahead[0]->ourref,
          'createby' => $config['params']['user'],
          'createdate' => $this->getCurrentTimeStamp()
        ];

        if (isset($datahead[0]->projectid)) $head['projectid'] = $datahead[0]->projectid;

        $headinfo = [];
        switch ($doc) {
          case 'PV':
            $head['ewt'] = $datahead[0]->ewt;
            $head['ewtrate'] = $datahead[0]->ewtrate;
            $head['cur'] = $datahead[0]->cur;
            $head['forex'] = $datahead[0]->forex;
            $head['branch'] = $datahead[0]->branch;
            $head['deptid'] = $datahead[0]->deptid;
            $head['invoiceno'] = $datahead[0]->invoiceno;
            $head['invoicedate'] = $datahead[0]->invoicedate;
            break;
          case 'QT':
            break;
          case 'CD':
            $head['wh'] = $datahead[0]->wh;
            $head['cur'] = $datahead[0]->cur;
            $head['forex'] = $datahead[0]->forex;
            $head['deptid'] = $datahead[0]->deptid;
            $head['terms'] = $datahead[0]->terms;
            $head['due'] = $datahead[0]->due;
            $head['procid'] = $datahead[0]->procid;
            $head['iscanvassonly'] = $datahead[0]->iscanvassonly;

            $headinfo['paymentid'] = $datahead[0]->paymentid;
            $headinfo['isadv'] = $datahead[0]->isadv;
            $headinfo['trno'] = $trno;

            $head['client'] = '';
            $head['clientname'] = '';
            $head['address'] = '';
            break;
          case 'SO':
            $head['wh'] = $datahead[0]->wh;
            $head['cur'] = $datahead[0]->cur;
            $head['forex'] = $datahead[0]->forex;
            $head['terms'] = $datahead[0]->terms;
            $head['due'] = $datahead[0]->due;
            $head['agent'] = $datahead[0]->agent;
            $head['shipto'] = $datahead[0]->shipto;
            $head['projectid'] = $datahead[0]->projectid;

            $headinfo['trno'] = $trno;
            $headinfo['plateno'] = $datahead[0]->plateno;
            $headinfo['checkerid'] = $datahead[0]->checkerid;
            $headinfo['truckid'] = $datahead[0]->truckid;
            $headinfo['helperid'] = $datahead[0]->helperid;
            $headinfo['driverid'] = $datahead[0]->driverid;
            $headinfo['tmpref'] = $referencedocno;
            break;
        }

        $config['params']['trno'] = $trno;

        $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);

        if ($inserthead) {
          $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - DUPLICATE ' . $referencedocno, app($path)->tablelogs);

          switch ($doc) {
            case 'CD':
              $this->coreFunctions->sbcinsert("headinfotrans", $headinfo);
              foreach ($datadetail as $key2 => $value) {
                $config['params']['data']['itemid'] = $datadetail[$key2]->itemid;
                $config['params']['data']['uom'] = $datadetail[$key2]->uom;
                $config['params']['data']['qty'] = $this->sanitizekeyfield('qty', $datadetail[$key2]->rrqty);
                $config['params']['data']['disc'] = $datadetail[$key2]->disc;
                $config['params']['data']['amt'] = $this->sanitizekeyfield('amt', $datadetail[$key2]->rrcost);
                $config['params']['data']['whid'] = $datadetail[$key2]->whid;
                $config['params']['data']['ismanual'] = $datadetail[$key2]->ismanual == "true" ? 1 : 0;
                $config['params']['data']['refx'] = $datadetail[$key2]->refx;
                $config['params']['data']['linex'] = $datadetail[$key2]->linex;
                $config['params']['data']['reqtrno'] = $datadetail[$key2]->reqtrno;
                $config['params']['data']['reqline'] = $datadetail[$key2]->reqline;
                $config['params']['data']['suppid'] = $datadetail[$key2]->suppid;
                $config['params']['data']['deptid'] = $datadetail[$key2]->deptid;
                $config['params']['data']['sano'] = $datadetail[$key2]->sano;
                $config['params']['data']['rrqty2'] = $datadetail[$key2]->rrqty2;
                $config['params']['data']['isprefer'] = $datadetail[$key2]->isprefer == "true" ? 1 : 0;
                $config['params']['data']['ref'] = $datadetail[$key2]->ref;
                $config['params']['data']['wh'] = $datadetail[$key2]->wh;
                $config['params']['data']['loc'] = $datadetail[$key2]->loc;
                $config['params']['data']['amt1'] = $this->sanitizekeyfield('amt', $datadetail[$key2]->amt1);
                $config['params']['data']['amt2'] = $this->sanitizekeyfield('amt', $datadetail[$key2]->amt2);
                $config['params']['data']['sano'] = $datadetail[$key2]->sano;
                $config['params']['data']['catid'] = $datadetail[$key2]->catid;
                $config['params']['data']['uom2'] = $datadetail[$key2]->uom2;
                $config['params']['data']['uom3'] = $datadetail[$key2]->uom3;
                $return = app($path)->additem('insert', $config, true);
              }
              break;
            case 'SO':
              $this->getcreditinfo($config, app($path)->head);

              $this->coreFunctions->sbcinsert("headinfotrans", $headinfo);
              foreach ($datadetail as $key2 => $value) {
                $config['params']['data']['itemid'] = $datadetail[$key2]->itemid;
                $config['params']['data']['uom'] = $datadetail[$key2]->uom;
                $config['params']['data']['qty'] = $this->sanitizekeyfield('qty', $datadetail[$key2]->isqty);
                $config['params']['data']['disc'] = $datadetail[$key2]->disc;
                $config['params']['data']['amt'] = $this->sanitizekeyfield('amt', $datadetail[$key2]->isamt);
                $config['params']['data']['whid'] = $datadetail[$key2]->whid;
                $config['params']['data']['wh'] = $datadetail[$key2]->wh;
                $config['params']['data']['loc'] = $datadetail[$key2]->loc;
                $config['params']['data']['kgs'] = $datadetail[$key2]->kgs;
                $config['params']['data']['weight'] = $datadetail[$key2]->weight;
                $return = app($path)->additem('insert', $config, true);
              }

              break;
            default:
              foreach ($datadetail as $key2 => $value) {
                $config['params']['data']['acno'] = $datadetail[$key2]->acno;
                $config['params']['data']['acnoname'] = $datadetail[$key2]->acnoname;
                $config['params']['data']['db'] = $datadetail[$key2]->db;
                $config['params']['data']['cr'] = $datadetail[$key2]->cr;
                $config['params']['data']['fdb'] = $datadetail[$key2]->fdb;
                $config['params']['data']['fcr'] = $datadetail[$key2]->fcr;
                $config['params']['data']['postdate'] = date('Y-m-d');
                $config['params']['data']['rem'] = $datadetail[$key2]->rem;
                $config['params']['data']['project'] = $datadetail[$key2]->projectid;
                $config['params']['data']['client'] = $datadetail[$key2]->client;
                $config['params']['data']['rem'] = $datadetail[$key2]->rem;
                $config['params']['data']['projectid'] = $datadetail[$key2]->projectid;
                $config['params']['data']['deptid'] = $datadetail[$key2]->deptid;
                $config['params']['data']['branch'] = $datadetail[$key2]->branch;
                $config['params']['data']['isewt'] = $datadetail[$key2]->isewt;
                $config['params']['data']['isvat'] = $datadetail[$key2]->isvat;
                $config['params']['data']['isvewt'] = $datadetail[$key2]->isvewt;
                $config['params']['data']['ewtrate'] = $datadetail[$key2]->ewtrate;
                $config['params']['data']['ewtcode'] = $datadetail[$key2]->ewtcode;
                $config['params']['data']['damt'] = $datadetail[$key2]->damt;

                switch ($doc) {
                  case 'GJ':
                  case 'GC':
                  case 'GD':
                    $config['params']['data']['poref'] = $datadetail[$key2]->poref;
                    $config['params']['data']['podate'] = $datadetail[$key2]->podate;
                    break;
                }
                $return = app($path)->additem('insert', $config, true);
              }
              break;
          }

          if ($doc == "QT") {
            $result = app($path)->copyotherinfo($sourcetrno, $config);
            if ($result['status']) {
            } else {
              return ['status' => false, 'msg' => $result['msg']];
            }
          }

          if ($doc == 'PV') {
            $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' DUPLICATE PARTICULARS', app($particularspath)->tablelogs);
            foreach ($dataparticulars as $key3 => $value) {
              $config['params']['row']['trno'] = $config['params']['trno'];
              $config['params']['row']['line'] = 0;
              $config['params']['row']['rem'] = $dataparticulars[$key3]->rem;
              $config['params']['row']['amount'] = $dataparticulars[$key3]->amount;
              $config['params']['row']['bgcolor'] = $dataparticulars[$key3]->bgcolor;
              $config['params']['row']['quantity'] = $dataparticulars[$key3]->quantity;
              $return = app($particularspath)->save($config);
            }
          }

          switch ($doc) {
            case 'QT':
            case 'CD':
            case 'SO':
              break;

            default:
              $dinfo = [];
              $detailinfo = $this->coreFunctions->opentable("select trno,line,rem from hdetailinfo where trno = " . $config['params']['row']['trno']);
              if (!empty($detailinfo)) {
                foreach ($detailinfo as $key3 => $val) {
                  $dinfo['trno'] = $trno;
                  $dinfo['line'] = $detailinfo[$key3]->line;
                  $dinfo['rem'] = $detailinfo[$key3]->rem;

                  foreach ($dinfo as $key4 => $v) {
                    $dinfo[$key4] = $this->sanitizekeyfield($key4, $v);
                    $result = $this->coreFunctions->sbcinsert("detailinfo", $dinfo);
                  }
                }
              }

              $parti = [];
              $particulars = $this->coreFunctions->opentable("select trno,line,rem,amount,quantity from hparticulars where trno = " . $config['params']['row']['trno']);
              if (!empty($particulars)) {
                foreach ($particulars as $k => $val1) {
                  $parti['trno'] = $trno;
                  $parti['line'] = $particulars[$k]->line;
                  $parti['rem'] = $particulars[$k]->rem;
                  $parti['amount'] = $particulars[$k]->amount;
                  $parti['quantity'] = $particulars[$k]->quantity;
                  $parti['createby'] =  $config['params']['user'];
                  $parti['createdate'] =  $this->getCurrentTimeStamp();

                  foreach ($parti as $m => $b) {
                    $parti[$m] = $this->sanitizekeyfield($m, $b);
                    $result = $this->coreFunctions->sbcinsert("particulars", $parti);
                  }
                }
              }
              break;
          }
        }

        switch ($companyid) {
          case 19: //housegem
            return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module', 'loaddocument' => true];
            break;
          default:
            return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module'];
            break;
        }
      }
    } else {
      return ['status' => false, 'msg' => 'No data found'];
    }
  }

  public function duplicateTransnum($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $qry = '';
    $dqry = '';
    $pref = '';
    $url = '';
    $head = '';
    $stock = '';

    $config['params']['trno'] =  $config['params']['row']['trno'];
    switch ($doc) {
      case 'QS':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $url = '/module/sales/' . strtolower($doc);
        $head = app($path)->head;
        $stock = app($path)->stock;
        break;
    }

    $isposted = $this->isposted($config);

    if (!$isposted) {
      return ['status' => false, 'msg' => 'The data cannot be duplicated. Not yet posted.'];
    }

    $referencedocno = $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$config['params']['trno']]);
    $pref = $this->GetPrefix($referencedocno);

    $datahead = app($path)->openhead($config);
    $datastock = app($path)->openstock($config['params']['trno'], $config);


    if (!empty($datahead)) {
      if ($pref == '') {
        $pref = $doc;
      }

      $trno = $this->generatecntnum($config, app($path)->tablenum, $doc, $pref);

      if ($trno != -1) {

        $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);

        $head = [
          'trno' => $trno,
          'doc' => $doc,
          'docno' => $docno,
          'client' => $datahead[0]->client,
          'clientname' => $datahead[0]->clientname,
          'rem' => $datahead[0]->rem,
          'dateid' => date('Y-m-d'),
          'wh' => $datahead[0]->wh,
          'yourref' => $datahead[0]->yourref,
          'ourref' => $datahead[0]->ourref,
          'branch' => $datahead[0]->branch,
          'deptid' => $datahead[0]->deptid,
          'billid' => $datahead[0]->billid,
          'shipid' => $datahead[0]->shipid,
          'billcontactid' => $datahead[0]->billcontactid,
          'shipcontactid' => $datahead[0]->shipcontactid,
          'cur' => $datahead[0]->cur,
          'forex' => $datahead[0]->forex,
          'tax' => $datahead[0]->tax,
          'terms' => $datahead[0]->terms,
          'vattype' => $datahead[0]->vattype,
        ];

        switch ($doc) {
          case "QS":
            $head['due'] = $datahead[0]->due;
            $head['deldate'] = $datahead[0]->deldate;
            $head['agent'] = $datahead[0]->agent;
            $head['agentcno'] = $datahead[0]->agentcno;
            $head['position'] = $datahead[0]->position;
            $head['industry'] = $datahead[0]->industry;
            break;
        }

        $config['params']['trno'] = $trno;

        $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);
        $result = true;
        if ($inserthead) {
          $hinfo = [];
          $headinfo = $this->coreFunctions->opentable("select trno,inspo,ispartial,instructions,period,termsdetails,isvalid,ovaliddate,leadfrom,leadto,leaddur,advised,taxdef from hheadinfotrans where trno = " . $config['params']['row']['trno']);
          if (!empty($headinfo)) {
            foreach ($headinfo as $key1 => $val) {
              $hinfo['trno'] = $trno;
              $hinfo['inspo'] = $headinfo[$key1]->inspo;
              $hinfo['ispartial'] = $headinfo[$key1]->ispartial;
              $hinfo['instructions'] = $headinfo[$key1]->instructions;
              $hinfo['isvalid'] = $headinfo[$key1]->isvalid;
              $hinfo['period'] = $headinfo[$key1]->period;
              $hinfo['leadfrom'] = $headinfo[$key1]->leadfrom;
              $hinfo['leadto'] = $headinfo[$key1]->leadto;
              $hinfo['leaddur'] = $headinfo[$key1]->leaddur;
              $hinfo['advised'] = $headinfo[$key1]->advised;
              $hinfo['taxdef'] = $headinfo[$key1]->taxdef;
              $hinfo['termsdetails'] = $headinfo[$key1]->termsdetails;
            }

            foreach ($hinfo as $key5 => $v) {
              $hinfo[$key5] = $this->sanitizekeyfield($key5, $v, '', $companyid);
            }

            $result = $this->coreFunctions->sbcinsert("headinfotrans", $hinfo);
          }

          if ($result) {
            $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - DUPLICATE ' . $referencedocno, app($path)->tablelogs);
            foreach ($datastock as $key2 => $value) {
              $config['params']['data']['itemid'] = $datastock[$key2]->itemid;
              $config['params']['data']['amt'] = $datastock[$key2]->isamt;
              $config['params']['data']['qty'] = $datastock[$key2]->isqty;
              $config['params']['data']['ext'] = $datastock[$key2]->ext;
              $config['params']['data']['disc'] = $datastock[$key2]->disc;
              $config['params']['data']['whid'] = $datastock[$key2]->whid;
              $config['params']['data']['wh'] = $datastock[$key2]->wh;
              $config['params']['data']['loc'] = $datastock[$key2]->loc;
              $config['params']['data']['void'] = $datastock[$key2]->void;
              $config['params']['data']['uom'] = $datastock[$key2]->uom;
              $config['params']['data']['ref'] = '';
              $config['params']['data']['sgdrate'] =  $this->getexchangerate('PHP', 'SGD');
              $config['params']['data']['noprint'] = $datastock[$key2]->noprint;

              $return = app($path)->additem('insert', $config, true);
            }

            if ($return['status']) {
              $dinfo = [];
              $stockinfo = $this->coreFunctions->opentable("select trno,line,rem,leadfrom,leadto,leaddur,advised,validity from hstockinfotrans where trno = " . $config['params']['row']['trno']);
              $chkinfo = $this->coreFunctions->getfieldvalue('stockinfotrans', 'trno', 'trno=?', [$trno]);
              if (empty($chkinfo)) {
                if (!empty($stockinfo)) {
                  foreach ($stockinfo as $key3 => $val) {
                    $dinfo['trno'] = $trno;
                    $dinfo['line'] = $stockinfo[$key3]->line;
                    $dinfo['rem'] = $stockinfo[$key3]->rem;
                    $dinfo['leadfrom'] = $stockinfo[$key3]->leadfrom;
                    $dinfo['leadto'] = $stockinfo[$key3]->leadto;
                    $dinfo['leaddur'] = $stockinfo[$key3]->leaddur;
                    $dinfo['advised'] = $stockinfo[$key3]->advised;
                    $dinfo['validity'] = $stockinfo[$key3]->validity;
                  }

                  foreach ($dinfo as $key4 => $vl) {
                    $dinfo[$key4] = $this->sanitizekeyfield($key4, $vl);
                  }

                  $result = $this->coreFunctions->sbcinsert("stockinfotrans", $dinfo);

                  if (!$result) {
                    $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno);
                    $this->coreFunctions->execqry("delete from " .  app($path)->head . " where trno=" . $trno);
                    $this->coreFunctions->execqry("delete from headinfotrans where trno=" . $trno);
                    $this->coreFunctions->execqry("delete from stockinfotrans where trno=" . $trno);
                    $this->coreFunctions->execqry("delete from " .  app($path)->stock . " where trno=" . $trno);
                    return ['status' => false, 'msg' => 'Error on creating other stock details.'];
                  }
                }
              }
            } else {
              $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno);
              $this->coreFunctions->execqry("delete from " .  app($path)->head . " where trno=" . $trno);
              $this->coreFunctions->execqry("delete from headinfotrans where trno=" . $trno);
              $this->coreFunctions->execqry("delete from " .  app($path)->stock . " where trno=" . $trno);
              return ['status' => false, 'msg' => 'Error on creating stock details.'];
            }
          } else {
            $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno);
            $this->coreFunctions->execqry("delete from " .  app($path)->head . " where trno = " . $trno);
            return ['status' => false, 'msg' => 'Error on creating other header details.'];
          }
        } else {
          $this->coreFunctions->execqry("delete from cntnum where trno = " . $trno);
          return ['status' => false, 'msg' => 'Error on creating header details.'];
        }

        return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'showmsg', 'trno' => $trno, 'access' => 'view', 'lookupclass' => '', 'url' => $url, 'moduletype' => 'module'];
      }
    } else {
      return ['status' => false, 'msg' => 'No data found'];
    }
  }

  public function posttoincentivetable($config, $unpost)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    $status = true;

    //checking if already release, do not allow to unpost
    if ($unpost) {
      switch ($doc) {
        case 'DS':
          $check = $this->coreFunctions->datareader("select ds.refx as value from gldetail as ds left join incentives as i on i.ptrno=ds.refx where ds.trno=? and ds.refx<>0 and i.agrelease is not null limit 1", [$trno]);
          if ($check) {
            return false;
          }
          break;

        case 'CR':
        case 'GJ':
        case 'GC':
          $check = $this->coreFunctions->datareader("select cr.trno as value from glhead as cr left join incentives as i on i.ptrno=cr.trno where cr.trno=? and i.agrelease is not null", [$trno]);
          if ($check) {
            return false;
          }
          break;
      }
    }

    $delete_qty = "delete from incentives where ptrno=?";

    $insert_qry = "insert into incentives (ptrno, trno, line, depodate, acnoid, clientid, agentid, agentcom, agentid2, amt, doc)
    select d.trno as ptrno, ar.trno, ar.line, h.dateid, ar.acnoid, ifnull(ar.clientid,0), ifnull(ar.agentid,0), ag.comm, 
    case sg.isoverride when 1 then ifnull(ag2.agentid,0) else 0 end,case arh.doc when 'AR' then  ar.db-ar.cr else (select ifnull(sum(s.ext),0) as t from glstock as s left join item on item.itemid =s.itemid where item.noncomm<>1 and s.trno = arh.trno) end as amt, arh.doc
    from gldetail as d left join arledger as ar on ar.trno=d.refx and ar.line=d.linex
    left join glhead as h on h.trno=d.trno
    left join glhead as arh on arh.trno=ar.trno
    left join client as ag on ag.clientid=ar.agentid
    left join salesgroup as ag2 on ag2.line=ag.salesgroupid
    left join client as sg on sg.clientid = ag2.agentid
    where d.trno=? and ar.bal=0 and arh.doc in ('SJ','AI','AR') and ifnull(ar.agentid,0)<>0";


    $arrefx = [];
    $filter = '';
    $cond = '';
    switch ($doc) {
      case "CR":
      case "GJ":
      case "GC":
        if ($unpost) {
          $fordel = $this->coreFunctions->opentable("select trno from incentives where ptrno = " . $trno);
          foreach ($fordel as $a => $b) {
            $this->coreFunctions->execqry("delete from pheadincentive where trno = ?", 'delete', [$b->trno]);
          }
          $status = $this->coreFunctions->execqry($delete_qty, 'delete', [$trno]);
        } else {
          $check = $this->coreFunctions->datareader("select d.trno as value from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=" . $trno . " and left(coa.alias,2) = 'CR'");
          if (!$check) {
            //ar paid
            $refx = $this->coreFunctions->opentable("select gldetail.trno,gldetail.line,gldetail.refx,gldetail.linex from gldetail left join glhead as ar on ar.trno = gldetail.refx where gldetail.trno=? and gldetail.refx<>0 and ar.doc in ('SJ','AI','AR') ", [$trno]);

            foreach ($refx as $i => $v) {
              //checking if exist on incentive table - for reposting payments
              $exist = $this->coreFunctions->datareader("select trno as value from incentives where trno = ? and line =? limit 1", [$v->refx, $v->linex]);
              if (floatval($exist) != 0) {
                array_push($arrefx, $v->refx . '~' . $v->linex);
              }

              $unpostedcr = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from ladetail where refx=? and linex=? and trno<>?", [$v->refx, $v->linex, $trno]);
              if (floatval($unpostedcr) != 0) {
                array_push($arrefx, $v->refx . '~' . $v->linex);
              }
            }
            if (!empty($arrefx)) {
              foreach ($arrefx as $r => $s) {
                if ($filter == '') {
                  $filter = "'$arrefx[$r]'";
                } else {
                  $filter .= ",'$arrefx[$r]'";
                }
              }
              $cond =  " and concat(d.refx,'~',d.linex) not in (" . $filter . ")";
            }
            $status = $this->coreFunctions->execqry($insert_qry . $cond, 'insert', [$trno]);
          } else {
            return true;
          }
        }

        break;
      case "DS":
        if ($unpost) {
          $cr = $this->coreFunctions->opentable("select distinct refx from gldetail where trno=? and refx<>0", [$trno]);
          foreach ($cr as $k => $v) {
            // $this->coreFunctions->LogConsole($v->refx);
            $fordel = $this->coreFunctions->opentable("select trno from incentives where ptrno = " . $v->refx);
            foreach ($fordel as $a => $b) {
              $this->coreFunctions->execqry("delete from pheadincentive where trno = ?", 'delete', [$b->trno]);
            }
            $status = $this->coreFunctions->execqry($delete_qty, 'delete', [$v->refx]);
          }
        } else {
          $arr_cr = [];
          $cr = $this->coreFunctions->opentable("select distinct refx,postdate from gldetail where trno=? and refx<>0", [$trno]); //get all cr
          foreach ($cr as $k => $v) {
            $check = $this->coreFunctions->datareader("select ifnull(count(cr.trno),0) as value from gldetail as d left join coa on coa.acnoid=d.acnoid left join crledger as cr on cr.trno=d.trno and cr.line=d.line where d.trno=? and left(coa.alias,2)='CR' and cr.depodate is null", [$v->refx]);
            if ($check) {
            } else {
              $unposted_check = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from ladetail where refx=? and trno<>?", [$v->refx, $trno]);
              if (!$unposted_check) {
                array_push($arr_cr, array('refx' => $v->refx, 'postdate' => $v->postdate));
              }
            }
          }

          if (!empty($arr_cr)) {
            foreach ($arr_cr as $ar => $av) {
              $insert_qry = "insert into incentives (ptrno, trno, line, depodate, acnoid, clientid, agentid, agentcom, agentid2, amt, doc)
                    select d.trno as ptrno, ar.trno, ar.line, '" . $arr_cr[$ar]['postdate'] . "', ar.acnoid, ifnull(ar.clientid,0), ifnull(ar.agentid,0), ag.comm,  case sg.isoverride when 1 then ifnull(ag2.agentid,0) else 0 end,case arh.doc when 'AR' then  ar.db-ar.cr else (select sum(s.ext) as t from glstock as s left join item on item.itemid =s.itemid where item.noncomm<>1 and s.trno = arh.trno) end as amt, arh.doc
                    from gldetail as d left join arledger as ar on ar.trno=d.refx and ar.line=d.linex
                    left join glhead as h on h.trno=d.trno
                    left join glhead as arh on arh.trno=ar.trno
                    left join client as ag on ag.clientid=ar.agentid
                    left join salesgroup as ag2 on ag2.line=ag.salesgroupid
                    left join client as sg on sg.clientid = ag2.agentid
                    where d.trno=? and ar.bal=0 and arh.doc in ('SJ','AI','AR') and ifnull(ar.agentid,0)<>0";
              $status = $this->coreFunctions->execqry($insert_qry, 'insert', [$arr_cr[$ar]['refx']]);
            }
          }
        }

        return true;
        break;
    }
    return $status;
  }

  public function navigatedocno($config)
  {
    $moduletype = $config['params']['moduletype'];
    $docno = $config['params']['docno'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $return_module = 'module';

    $qry = '';
    $filter = '';

    switch ($doc) {
      case 'CUSTOMER':
      case 'SUPPLIER':
      case 'AGENT':
      case 'EMPLOYEE':
      case 'WAREHOUSE':
        $return_module = 'ledgergrid';

        $blnInt = false;
        if ($config['params']['companyid'] == 51 && $doc == 'EMPLOYEE') {
          $blnInt = true;
        }

        if ($blnInt) {
          switch ($config['params']['lookupclass']) {
            case 'first':
              $filter .= " order by cast(client as unsigned) limit 1";
              break;
            case 'prev':
              $filter .= " and client<" . $docno . " order by cast(client as unsigned) desc limit 1";
              break;
            case 'next':
              $filter .= " and client>" . $docno . " order by cast(client as unsigned) limit 1";
              break;
            case 'last':
              $filter .= " order by cast(client as unsigned) desc limit 1";
              break;
          }
        } else {
          switch ($config['params']['lookupclass']) {
            case 'first':
              $filter .= " order by client limit 1";
              break;
            case 'prev':
              $filter .= " and client<'" . $docno . "' order by client desc limit 1";
              break;
            case 'next':
              $filter .= " and client>'" . $docno . "' order by client limit 1";
              break;
            case 'last':
              $filter .= " order by client desc limit 1";
              break;
          }
        }



        $tablenum = $config['docmodule']->head;
        $qry = "select clientid as value from " . $tablenum . " where " . $config['docmodule']->tagging . "=1" . $filter;
        $newtrno = $this->coreFunctions->datareader($qry);
        break;

      case 'STOCKCARD':
        $return_module = 'ledgergrid';

        switch ($config['params']['lookupclass']) {
          case 'first':
            $filter .= " order by barcode limit 1";
            break;
          case 'prev':
            $filter .= " and barcode<'" . $docno . "' order by barcode desc limit 1";
            break;
          case 'next':
            $filter .= " and barcode>'" . $docno . "' order by barcode limit 1";
            break;
          case 'last':
            $filter .= " order by barcode desc limit 1";
            break;
        }

        $tablenum = $config['docmodule']->head;
        $qry = "select itemid as value from " . $tablenum . " where 1=1 and isreserved=0 " . $filter; //isreserved = reserved barcodes used in POS
        $newtrno = $this->coreFunctions->datareader($qry);
        break;

      default:
        if ($config['params']['companyid'] == 21) { //kinggeorge
          switch ($doc) {
            case 'CR':
            case 'CV':
            case 'GJ':
              if (!$this->isacctgbalance($trno, $config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Accounting entries are not balance.'];
              }
              break;
          }
        }

        if ($doc == 'SJ' && $moduletype == 'SALES') {
          $filter = " and bref<>'SJS'";
        }

        if ($doc == 'CM' && $moduletype == 'SALES') {
          $filter = " and left(bref,3)<>'SRS'";
        }

        switch ($config['params']['lookupclass']) {
          case 'first':
            $filter .= " order by docno limit 1";
            break;
          case 'prev':
            $filter .= " and docno<'" . $docno . "' order by docno desc limit 1";
            break;
          case 'next':
            $filter .= " and docno>'" . $docno . "' order by docno limit 1";
            break;
          case 'last':
            $filter .= " order by docno desc limit 1";
            break;
        }

        $tablenum = $config['docmodule']->tablenum;
        $qry = "select trno as value from " . $tablenum . " where doc=? and center=?" . $filter;
        $newtrno = $this->coreFunctions->datareader($qry, [$doc, $center]);
        break;
    }

    if ($newtrno == '') {
      $newtrno = $trno;
    }

    return ['status' => true, 'msg' => '', 'trno' => $newtrno, 'moduletype' => $return_module];
  }

  public function uploadexcel($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];
    $companyid = $config['params']['companyid'];

    $uniquefield = 'itemcode';
    $barcode = '';

    $msg = '';
    $msg2 = '';
    $msgOfStock = '';
    $status = true;

    $path = '';
    switch ($config['params']['doc']) {
      case 'IS':
        $path = 'App\Http\Classes\modules\inventory\is';
        break;

      case 'AJ':
        $path = 'App\Http\Classes\modules\inventory\aj';
        break;

      case 'PC':
        $path = 'App\Http\Classes\modules\inventory\pc';
        break;
      case 'AT':
        $path = 'App\Http\Classes\modules\inventory\at';
        break;
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\ts';
        break;
      case 'DM':
        $path = 'App\Http\Classes\modules\purchase\dm';
        break;
      case 'SJ':
        $path = 'App\Http\Classes\modules\sales\sj';
        break;
      case 'CM':
        $path = 'App\Http\Classes\modules\sales\cm';
        break;
      case 'ST':
        $path = 'App\Http\Classes\modules\issuance\st';
        break;
      case 'PA':
        $path = 'App\Http\Classes\modules\pos\pa';
        break;
      case 'PP':
        $path = 'App\Http\Classes\modules\pos\pp';
        break;
    }

    $wh = $this->coreFunctions->getfieldvalue(app($path)->head, "wh", "trno = ?", [$trno]);

    if ($companyid == 40) { //cdo
      $uniquefield = 'partno';
    }

    $blnUploaded = false;

    foreach ($rawdata as $key => $value) {

      try {
        $qty = 0;
        switch ($config['params']['doc']) {
          case "AJ":
            if (floatval($rawdata[$key]['qty']) <> 0) {
              $qty = floatval($rawdata[$key]['qty']);
            }
            break;
          default:
            if (floatval($rawdata[$key]['qty']) > 0) {
              $qty = floatval($rawdata[$key]['qty']);
            }
            break;
        }

        if ($qty <> 0) {
          if ($companyid == 40) { //cdo
            $config['params']['data']['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "partno = '" . $rawdata[$key][$uniquefield] . "'");
          } else {
            $config['params']['data']['itemid'] = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key][$uniquefield] . "'");
          }

          if ($config['params']['data']['itemid'] == '') {
            $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' does not exist. ';
          } else {
            if ($companyid == 40) { //cdo
              $barcode = $this->coreFunctions->getfieldvalue("item", "barcode", "partno = '" . $rawdata[$key][$uniquefield] . "'");
            } else {
              $barcode = $rawdata[$key][$uniquefield];
            }
            $config['params']['data']['barcode'] = $barcode;
            $uom_exist = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid = " . $config['params']['data']['itemid'] . " and uom = '" . $rawdata[$key]['uom'] . "'");
            if ($uom_exist == '') {
              $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' uom does not exist. ';
              continue;
            }


            if ($companyid == 40) {  //cdo
              $itemexist = $this->coreFunctions->getfieldvalue(app($path)->stock, "itemid", "trno = ? and itemid = ?", [$trno, $config['params']['data']['itemid']], '', true);
              if ($itemexist != 0) {
                $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' already exist on this transaction. ';
                continue;
              }
            }

            if ($rawdata[$key]['uom'] == '') {
              $msg .= 'Invalid uom for ' . $rawdata[$key][$uniquefield] . ' ';
            } else {
              $config['params']['data']['uom'] = $rawdata[$key]['uom'];
              $config['params']['trno'] = $trno;
              $config['params']['data']['qty'] = $rawdata[$key]['qty'];

              if (isset($rawdata[$key]['wh'])) {
                $swh = $this->coreFunctions->getfieldvalue("client", "client", "client=?", [$rawdata[$key]['wh']]);
                if ($swh == '') {
                  $msg .= 'Failed to upload ' . $rawdata[$key]['itemcode'] . ', warehouse code ' . $rawdata[$key]['wh'] . ' does not exist. ';
                } else {
                  $config['params']['data']['wh'] =  $swh;
                }
              } else {
                $config['params']['data']['wh'] =  $wh;
              }

              $amtfield = 'cost';

              if (isset($rawdata[$key][$amtfield])) {
                setcosthere:
                $config['params']['data']['amt'] = $rawdata[$key][$amtfield];
              } else {
                if (isset($rawdata[$key]['amt'])) {
                  $amtfield = 'amt';
                  goto setcosthere;
                }

                $config['params']['barcode'] = $barcode;
                $config['params']['client'] = '';
                $latestcost = json_encode(app($path)->getlatestprice($config));
                $this->coreFunctions->LogConsole('after latest cost ' . $barcode);
                if (!empty($latestcost['data'])) {
                  $config['params']['data']['amt'] = $latestcost['data'][0]['amt'];
                } else {
                  $config['params']['data']['amt'] = 0;
                }
              }

              $loc = '';
              if (isset($rawdata[$key]['location'])) {
                $config['params']['data']['loc'] = $rawdata[$key]['location'];
                $loc = $rawdata[$key]['location'];
              }

              if (isset($rawdata[$key]['reasoncode'])) {
                $config['params']['data']['reasonid'] = $this->coreFunctions->getfieldvalue("reqcategory", "line", "isreasoncode=1 and code=?", [$rawdata[$key]['reasoncode']], '', true);;
              }

              $expiry = '';
              if (isset($rawdata[$key]['expiry'])) {
                $expiry = $rawdata[$key]['expiry'];
                if ($expiry != '') {
                  if (is_numeric($expiry)) {
                    $UNIX_DATE = ($expiry - 25569) * 86400;
                    $expiry = gmdate("Y-m-d", $UNIX_DATE);
                  }
                  $config['params']['data']['expiry'] = $expiry;
                }
              }

              if ($config['params']['doc'] == 'PC' && $companyid == 14) { //majesty
                $filterwh = '';
                if (isset($config['params']['data']['wh'])) {
                  $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$config['params']['data']['wh']]);
                  $filterwh = " and whid=" . $whid;
                }
                $laexist = $this->coreFunctions->opentable("select trno,line,rrcost,disc from pcstock where trno=" . $trno . " and itemid=" . $config['params']['data']['itemid'] . " and loc='" . $loc . "' and expiry='" . $expiry . "' " . $filterwh);
                if (empty($laexist)) {
                  $return = app($path)->additem('insert', $config);
                } else {
                  $config['params']['data']['line'] = $laexist[0]->line;
                  $config['params']['data']['rrcost'] = $laexist[0]->rrcost;
                  $config['params']['data']['disc'] = $laexist[0]->disc;
                  $config['params']['data']['rrqty'] = $config['params']['data']['qty'];
                  $return = app($path)->additem('update', $config);
                }
              } else {
                $return = app($path)->additem('insert', $config);
              }
              if (!$return['status']) {
                $status = false;
                $msg .= 'Failed to upload ' . $rawdata[$key]['itemcode'] . '. ' . $return['msg'];
                goto exithere;
              } else {
                $blnUploaded = true;
              }
            }
          }
        }

        if ($blnUploaded) {
          if ($companyid == 56 && $config['params']['doc'] == 'AJ') { //cdo
            $data = app($path)->openstock($trno, $config);
            $data2 = json_decode(json_encode($data), true);


            foreach ($data2 as $key => $value) {
              if ($data2[$key][app($path)->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $status = false;
                $msgOfStock = ' / Please check; some items are out of stock.';
              }
              if ($config['params']['companyid'] == 56) {
                if ($data2[$key]['reasonid'] == 0) {
                  $data[$key]->errcolor = 'bg-red-2';
                  $status = false;
                  $msg2 = ' / Some reasons are blank.';
                }
              }
            }
          }
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    } else {
      $status = false;
    }
    $config['params']['trno'] =  $trno;
    app($path)->loadheaddata($config);
    return ['status' => $status, 'msg' => $msg  . $msgOfStock . $msg2, 'reloadhead' => true, 'trno' => $trno];
  }

  public function getamtfieldbygrp($p)
  {
    switch ($p) {
      case 'R':
        $fieldamt = 'amt';
        $fielddisc = 'disc';
        $label = 'Retail Price';
        break;
      case 'W':
        $fieldamt = 'amt2';
        $fielddisc = 'disc2';
        $label = 'Wholesale Price';
        break;
      case 'A':
        $fieldamt = 'famt';
        $fielddisc = 'disc3';
        $label = 'Price Group A';
        break;
      case 'B':
        $fieldamt = 'amt4';
        $fielddisc = 'disc4';
        $label = 'Price Group B';
        break;
      case 'C':
        $fieldamt = 'amt5';
        $fielddisc = 'disc5';
        $label = 'Price Group C';
        break;
      case 'D':
        $fieldamt = 'amt6';
        $fielddisc = 'disc6';
        $label = 'Price Group D';
        break;
      case 'E':
        $fieldamt = 'amt7';
        $fielddisc = 'disc7';
        $label = 'Price Group E';
        break;
      case 'F':
        $fieldamt = 'amt8';
        $fielddisc = 'disc8';
        $label = 'Price Group F';
        break;
      case 'G':
        $fieldamt = 'amt9';
        $fielddisc = 'disc9';
        $label = 'Price Group G';
        break;
      default: //for walkin
        $fieldamt = 'amt';
        $fielddisc = 'disc';
        $label = 'Retail Price';
        break;
    }

    return ['amt' => $fieldamt, 'disc' => $fielddisc, 'label' => $label];
  }


  public function distributeloanentry($config, $loantrno, $trno)
  {
    $status = true;
    $entry = [];
    $pf = 0;
    $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);

    $qry = 'select app.docno, app.dateid,app.client,dinfo.principal,dinfo.interest,dinfo.pfnf,dinfo.mri,dinfo.dst,dinfo.nf,terms.days,app.tax,r.acnoid,r.isdiminishing
    from heahead as app 
    left join transnum as num on num.trno = app.trno
    left join client on client.client = app.client
    left join heainfo as info on info.trno = app.trno
    left join htempdetailinfo as dinfo on dinfo.trno=app.trno
    left join terms on terms.terms = app.terms
    left join reqcategory as r on r.line = app.planid
    where num.cvtrno =0 and app.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$loantrno]);
    $tax = 0;
    if (!empty($stock)) {
      $aracct = $stock[0]->acnoid; //$this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']); //principal
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP1']);
      $mriacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['UE5']);
      $arintacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR2']); //interest
      $revintacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['UE1']);
      $arpfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']); //others
      $armriacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR7']); //mri
      $revpfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['UE2']); //
      $dstfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['UE4']);
      $vatacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $vat = 0; //floatval($stock[0]->tax);
      $tax1 = 1;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      $cvdate = $stock[0]->dateid;
      $cr = 0;
      $day = date("d", strtotime($cvdate));
      $mnth = date("m", strtotime($cvdate));
      $yr = date("Y", strtotime($cvdate));

      $tprincipal = 0;
      $tinterest = 0;
      $tpfnf = 0;
      $tdst = 0;
      $tmri = 0;

      $balmons = $stock[0]->days;
      $rdate = strtotime($cvdate);
      $i = 1;
      $y = 1;
      $pdate = $cvdate;
      foreach ($stock as $k => $v) {
        //$pdate = date("Y-m-d", strtotime("+$y month", $rdate));
        if ($stock[$k]->principal != 0) {
          $d['trno'] = $trno;
          $d['line'] = $i;
          $d['acnoid'] = $aracct;
          $d['client'] = $stock[$k]->client;
          $d['postdate'] = $pdate;
          $d['db'] = $stock[$k]->principal;
          $d['cr'] = 0;
          $d['ref'] = $stock[$k]->docno;

          $locale = 'en_US';
          $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
          $d['rem'] = $nf->format($y) . ' MA';
          $entry = $this->upsertdetail($entry, $d, $config);
          $i += 1;
          $tprincipal = $tprincipal + $stock[$k]->principal;
        }

        if ($stock[$k]->interest != 0) {
          $d['trno'] = $trno;
          $d['line'] = $i;
          $d['acnoid'] = $arintacct;
          $d['client'] = $stock[$k]->client;
          $d['postdate'] = $pdate;
          $d['db'] = $stock[$k]->interest;
          $d['cr'] = 0;
          $d['ref'] = $stock[$k]->docno;

          $locale = 'en_US';
          $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
          $d['rem'] = $nf->format($y) . ' Interest';
          $entry = $this->upsertdetail($entry, $d, $config);
          $i += 1;
          $tinterest = $tinterest + $stock[$k]->interest;
        }

        if ($stock[$k]->mri != 0) {
          $d['trno'] = $trno;
          $d['line'] = $i;
          $d['acnoid'] = $armriacct;
          $d['client'] = $stock[$k]->client;
          $d['postdate'] = $pdate;
          $d['db'] = $stock[$k]->mri;
          $d['cr'] = 0;
          $d['ref'] = $stock[$k]->docno;

          $locale = 'en_US';
          $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
          $d['rem'] = $nf->format($y) . ' MRI';
          $entry = $this->upsertdetail($entry, $d, $config);
          $i += 1;
          $tmri = $tmri + $stock[$k]->mri;
        }

        if (($stock[$k]->pfnf + $stock[$k]->nf) != 0) {
          $d['trno'] = $trno;
          $d['line'] = $i;
          $d['acnoid'] = $arpfacct;
          $d['client'] = $stock[$k]->client;
          $d['postdate'] = $pdate;
          $d['db'] = $stock[$k]->pfnf + $stock[$k]->nf;
          $d['cr'] = 0;
          $d['ref'] = $stock[$k]->docno;

          $locale = 'en_US';
          $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
          if ($stock[$k]->mri != 0) {
            $d['rem'] = $nf->format($y) . ' PF & NF';
          } else {
            $d['rem'] = $nf->format($y) . ' PF & NF';
          }

          $entry = $this->upsertdetail($entry, $d, $config);
          $i += 1;
          $tpfnf = $tpfnf + $stock[$k]->pfnf + $stock[$k]->nf;
        }

        if ($stock[$k]->dst != 0) {
          $d['trno'] = $trno;
          $d['line'] = $i;
          $d['acnoid'] = $arpfacct;
          $d['client'] = $stock[$k]->client;
          $d['postdate'] = $pdate;
          $d['db'] = $stock[$k]->dst;
          $d['cr'] = 0;
          $d['ref'] = $stock[$k]->docno;

          $locale = 'en_US';
          $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
          $d['rem'] = $nf->format($y) . ' DST';
          $entry = $this->upsertdetail($entry, $d, $config);
          $i += 1;
          $tdst = $tdst + $stock[$k]->dst;
        }

        $pdate = date("Y-m-d", strtotime("+$y month", $rdate));
        $y += 1;
      }

      //output entry  
      if ($stock[0]->tax != 0) {
        $tax = (($tprincipal + $tinterest + $tpfnf) / 1.12) * .12;
      }

      if ($tax != 0) {
        //tax
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $vatacct;
        $d['client'] = $stock[$k]->client;
        $d['postdate'] = $stock[0]->dateid;
        $d['db'] = 0;
        $d['cr'] = $tax;
        $d['rem'] = '';
        $entry = $this->upsertdetail($entry, $d, $config);
        $i += 1;
      }


      //principal
      // if($tprincipal!=0){
      //     $d['trno'] = $trno;
      //     $d['line'] = $i;
      //     $d['acnoid'] = $revacct;
      //     $d['client'] =  $stock[$k]->client;
      //     $d['postdate'] = $stock[0]->dateid;
      //     $d['db'] = 0;
      //     $d['cr'] = $tprincipal/$tax1;
      //     $d['rem'] = '';
      //     //array_push($det, $d);
      //     $entry = $this->upsertdetail($entry, $d, $config);
      //     $i += 1;
      // }        

      //unearned int.
      if ($tinterest != 0) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $revintacct;
        $d['client'] = $stock[$k]->client;
        $d['postdate'] = $stock[0]->dateid;
        $d['db'] = 0;
        $d['cr'] = $tinterest / $tax1;
        $d['rem'] = '';
        //array_push($det, $d);
        $entry = $this->upsertdetail($entry, $d, $config);
        $i += 1;
      }


      //pfnf
      if ($tpfnf != 0) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $revpfacct;
        $d['client'] =  $stock[$k]->client;
        $d['postdate'] = $stock[0]->dateid;
        $d['db'] = 0;
        $d['cr'] = $tpfnf / $tax1;
        $d['rem'] = '';
        //array_push($det, $d);
        $entry = $this->upsertdetail($entry, $d, $config);
        $i += 1;
      }

      //mri
      if ($tmri != 0) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $mriacct;
        $d['client'] =  $stock[$k]->client;
        $d['postdate'] = $stock[0]->dateid;
        $d['db'] = 0;
        $d['cr'] = $tmri / $tax1;
        $d['rem'] = '';
        //array_push($det, $d);
        $entry = $this->upsertdetail($entry, $d, $config);
        $i += 1;
      }

      //dst
      if ($tdst != 0) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $dstfacct;
        $d['client'] =  $stock[$k]->client;
        $d['postdate'] = $stock[0]->dateid;
        $d['db'] = 0;
        $d['cr'] = $tdst / $tax1;
        $d['rem'] = '';
        //array_push($det, $d);
        $entry = $this->upsertdetail($entry, $d, $config);
        $i += 1;
      }
    }

    return $entry;
  }

  public function logConsole($txt)
  {
    $this->coreFunctions->logConsole($txt);
  }

  public function getSignatories($config)
  {
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    return $this->coreFunctions->opentable('select fieldname, fieldvalue from signatories where doc=? and userid=?', [$doc, $user]);
  }

  public function writeSignatories($config, $field, $value)
  {
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $signatories = ['userid' => $user, 'doc' => $doc, 'fieldname' => $field, 'fieldvalue' => $value];

    $exist = $this->coreFunctions->getfieldvalue("signatories", "fieldname", "userid=? and doc=? and fieldname=?", [$user, $doc, $field]);
    if ($exist == '') {
      $this->coreFunctions->sbcinsert('signatories', $signatories);
    } else {
      $this->coreFunctions->sbcupdate('signatories', $signatories, ['userid' => $user, 'doc' => $doc, 'fieldname' => $field]);
    }
  }


  public function getmaxcolumn($arr = [])
  {
    $max[] = 1;
    foreach ($arr as $key => $value) {
      $max[] = count($value);
    }
    return max($max);
  }


  public function sbcsendemail($config, $emailinfo)
  {
    $dataparams = [];
    if (isset($config['params']['dataparams'])) {
      $dataparams = json_decode($config['params']['dataparams']);
    }

    try {
      if (isset($emailinfo['cc'])) {
        if (empty($emailinfo['cc'])) {
          Mail::to($emailinfo['email'])->send(new SendMail($emailinfo));
        } else {
          Mail::to($emailinfo['email'])->cc($emailinfo['cc'])->send(new SendMail($emailinfo));
        }
      } else {
        Mail::to($emailinfo['email'])->send(new SendMail($emailinfo));
      }
    } catch (Exception $ex) {
      $errorLine = 'File:' . $ex->getFile() . ' Line:' . $ex->getLine() . " -> " . $ex->getMessage();
      $this->othersClass->logConsole($errorLine);
      return ['status' => false, 'msg' => 'Failed to send email.<br> ' . $errorLine];
    }

    return ['status' => true, 'msg' => 'Email send Success'];
  }

  public function sbcsendemail2($emailinfo)
  {
    //$dataparams = json_decode($config['params']['dataparams']);

    if (isset($emailinfo['cc'])) {
      if (empty($emailinfo['cc'])) {
        Mail::to($emailinfo['email'])->send(new SendMail($emailinfo));
      } else {
        Mail::to($emailinfo['email'])->cc($emailinfo['cc'])->send(new SendMail($emailinfo));
      }
    } else {
      Mail::to($emailinfo['email'])->send(new SendMail($emailinfo));
    }

    return ['status' => true, 'msg' => 'Email send Success'];
  }

  public function deleteattachments($config)
  {
    switch ($config['params']['doc']) {
      case 'PO':
      case 'PR':
      case 'SR':
      case 'OS':
      case 'OP':
      case 'QS':
      case 'SQ':
      case 'SO':
      case 'RO':
      case 'AO':
      case 'TE':
      case 'VT':
      case 'VS':
      case 'RF':
      case 'PC':
      case 'CD':
      case 'OQ':
      case 'OM':
      case 'BL':
      case 'BR':
      case 'JC':
      case 'JO':
      case 'JR':
      case 'MR':
      case 'RQ':
      case 'WC':
      case 'GP':
      case 'TR':
      case 'PA':
      case 'CD':
      case 'PF':
      case 'QT':
      case 'SA':
      case 'SB':
      case 'SC':
      case 'SG':
      case 'WA':
      case 'AF':
      case 'WN':
      case 'PW':
      case 'FI':
      case 'DI':
      case 'RT':
      case 'DP':
      case 'CI':
      case 'CK':
      case 'TI':
      case 'OI':
      case 'SV':
      case 'MC':
      case 'PI':
      case 'PE':
      case 'PN':
      case 'AT':
      case 'RG':
      case 'LE':
      case 'CE';
      case 'DX':
      case 'TC':
      case 'CE':
      case 'RC':
      case 'RD':
      case 'PX':
        $table = 'transnum_picture';
        $trno = $config['params']['trno'];
        break;
      case 'DT':
        $table = 'docunum_picture';
        break;
      case 'RR':
      case 'AC':
      case 'DM':
      case 'SJ':
      case 'CM':
      case 'AI':
      case 'SU':
      case 'AJ':
      case 'TS':
      case 'IS':
      case 'CV':
      case 'SP':
      case 'SS':
      case 'CS':
      case 'MI':
      case 'MT':
      case 'PB':
      case 'PM':
      case 'ST':
      case 'RA':
      case 'SN':
      case 'RP':
      case 'SD':
      case 'SE':
      case 'SF':
      case 'SH':
      case 'SI':
      case 'WB':
      case 'DS':
      case 'JP':
      case 'PG':
      case 'CR':
      case 'GJ':
      case 'AR':
      case 'AP':
      case 'PV':
      case 'FS':
      case 'CP':
      case 'WM':
      case 'GD':
      case 'GC':
      case 'MB':
      case 'SK':
      case 'DN';
      case 'DR':
      case 'MJ':
      case 'FA':
      case 'LA':
      case 'BC':
      case 'BE':
      case 'KR':
      case 'LL':
      case 'CH':
      case 'ON':
        $table = 'cntnum_picture';
        $trno = $config['params']['trno'];
        break;
      case 'CUSTOMER':
      case 'SUPPLIER':
      case 'EMPLOYEE':
      case 'DEPARTMENT':
      case 'AGENT':
      case 'WAREHOUSE':
      case 'BRANCH':
      case 'BG':
        $table = 'client_picture';
        $trno = $config['params']['clientid'];
        break;
    }
    $mainfolder = '/images/';
    $attachments = $this->coreFunctions->opentable("select picture from " . $table . " where trno=" . $trno);
    if (!empty($attachments)) {
      foreach ($attachments as $a) {
        $filename = str_replace($mainfolder, '', $a->picture);
        if (Storage::disk('sbcpath')->exists($filename)) {
          Storage::disk('sbcpath')->delete($filename);
        }
      }
      $this->coreFunctions->execqry("delete from " . $table . " where trno=" . $trno);
    }
  }

  public function downloadapi($config, $url, $doc, $params = [])
  {
    $apicode = 'BEPLUSAPI202200001';
    $code = '1d0037018ee72d073641635';
    $mainurl = 'http://192.168.0.100/api/'; //'https://openzn.com/projects/beeplus/api/';// 'https://openzn.com/projects/beeplus/api/sales_list/';

    switch ($doc) {
      case 'SJ':
      case 'CM':
      case 'RR':
      case 'DM':
        $url = $mainurl . $url . '/' . $apicode . '/' . $code . '?date_from=' . $params['date1'] . '&date_to=' . $params['date2'] . '&posting=Unposted';
        break;
      case 'TAG':
        $url = $mainurl . 'update_marking/' . $apicode . '/' . $code . '?id=' . $params['id'] . '&type=' . $params['type'];
        break;
      case 'EDIT':
        $url = $mainurl . $url . '/' . $apicode . '/' . $code . '?date_from=' . $params['date1'] . '&date_to=' . $params['date2'] . '&updated=1&api_mark=1';
        break;
      case 'UPDATE_READ':
        $url = $mainurl . 'update_edited_so/' . $apicode . '/' . $code . '?id=' . $params['id'];
        break;
      default:
        $url = $mainurl . $url . '/' . $apicode . '/' . $code;
        break;
    }

    //$url = $mainurl.'BEPLUSAPI202200001/1d0037018ee72d073641635?date_from=01/01/2022&date_to=12/31/2023&updated=1';
    $this->coreFunctions->Logconsole($url);
    //url = 'https://openzn.com/projects/beeplus/api/'.$url.'/BEPLUSAPI202200001/1d0037018ee72d073641635?id=57583&type=sales';
    $param = array(
      'http' => array(
        'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
        'method' => 'POST',
      ),
    );
    $context = stream_context_create($param);
    $content = file_get_contents($url, false, $context);
    $json = json_decode($content, true);

    return $json;
  }

  /* MULTI SEARCH FUNCTION ADD SPACE BEFORE AND AFTER COMMA */
  public function multisearch($searchfield = [], $search = "", $verticalsearch = false)
  {

    $filtersearch = "";
    $multisearch = explode(",", $search);

    $operator = 'and';
    $return = '';

    if ($verticalsearch) $operator = 'or';

    $index = 0;
    if ($searchfield != null && $search != "") {
      foreach ($multisearch as $key => $searchval) {
        $filtersearch2 = "";

        foreach ($searchfield as $key => $sfield) {
          if ($filtersearch2 == "") {
            if ($index == 0) {
              $filtersearch2 .= " (" . $sfield . " like '%" . trim($searchval) . "%'";
            } else {
              $filtersearch2 .= " " . $operator . " (" . $sfield . " like '%" . trim($searchval) . "%'";
            }
          } else {
            $filtersearch2 .= " or " . $sfield . " like '%" . trim($searchval) . "%'";
          } //end if
        }
        $filtersearch .=  $filtersearch2 . ")";
        $index += 1;
      }

      $return =  " and (" . $filtersearch . ")";
    }

    // $this->coreFunctions->LogConsole($return);
    return $return;
  }

  public function financecalc($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $x = [];
    $status = true;
    $msg = 'Successfully updated.';

    $data = $this->coreFunctions->opentable("select hinfo.dueday,hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
    ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
    ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp, ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
    ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, ifnull(hinfo.fdiscount,0) as fdiscount,
    ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
   ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,ifnull(hinfo.loanamt,0) as loanamt from lahead as head left join cntnuminfo as hinfo on hinfo.trno = head.trno  where head.trno = ?", [$trno]);

    if (!empty($data)) {
      $finterestrate = $data[0]->finterestrate;
      $termsmonth = $data[0]->termsmonth;
      $termspercentdp = $data[0]->termspercentdp;
      $termsyear = $data[0]->termsyear;
      $termspercent = $data[0]->termspercent;
      $reservationdate = $data[0]->reservationdate;
      $dueday = $data[0]->dueday;
      $reservationfee = $data[0]->reservationfee;
      $ftcplot = $data[0]->ftcplot;
      $ftcphouse = $data[0]->ftcphouse;
      $fsellingpricegross = $data[0]->fsellingpricegross;
      $fdiscount = $data[0]->fdiscount;
      $fsellingpricenet = $data[0]->fsellingpricenet;
      $fcontractprice = $data[0]->fcontractprice;
      $fmiscfee = $data[0]->fmiscfee;
      $ffi = $data[0]->ffi;
      $fmri = $data[0]->fmri;

      $fi = 0.00024;
      $mri = 0.001;
      $ftcplot = round($ftcplot, 2);
      $fsellingpricegross = round($ftcplot + $ftcphouse, 2);
      $fsellingpricenet = round($fsellingpricegross - $fdiscount, 2);
      $fmiscfee = round($fsellingpricenet * .10, 2);
      $fcontractprice = round($fsellingpricenet + $fmiscfee, 2);
      $loanamt = $fcontractprice * ($termspercent / 100);
      $loandepo = round($fcontractprice * ($termspercentdp / 100), 2);

      $fma1 = $this->calPMT($finterestrate, $termsyear, $loanamt);
      $fma2 = round($fma1 / $loanamt, 8);
      $factorratewithinsurance = $fi + $mri + $fma2;

      $ffi = round($fi * $loanamt, 2);
      $fmri = round($mri * $loanamt, 2);
      $fma3 = round($fma1 + $ffi + $fmri, 2);
      $fmonthlydp = round(($loandepo - $reservationfee) / $termsmonth, 2);
      $fmonthlyamortization = $fma3; //round($loanamt * $factorratewithinsurance,2);


      //update data
      $x = [
        'fsellingpricegross' => $fsellingpricegross,
        'fsellingpricenet' => $fsellingpricenet,
        'fmiscfee' => $fmiscfee,
        'fcontractprice' => $fcontractprice,
        'loanamt' => $loanamt,
        'fmonthlydp' => $fmonthlydp,
        'fmonthlyamortization' => round($fmonthlyamortization, 1),
        'fma1' => $fma1,
        'fma2' => $fma2,
        'fma3' => $fma3,
        'ffi' => $ffi,
        'fmri' => $fmri
      ];
    }

    if (!$this->coreFunctions->sbcupdate("cntnuminfo", $x, ["trno" => $trno])) {
      $status = false;
      $msg = "Update failed.";
    }
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function recomputeschedule($trno, $artrno, $checkno, $checkdate, $amount, $aralias, $isreverse = 0)
  {
    $qry = "select detail.trno,detail.line,coa.alias,detail.db,detail.postdate,detail.rem,head.projectid,head.phaseid,head.modelid,head.blklotid,hinfo.dueday,hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
    ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
    ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp, ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
    ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, ifnull(hinfo.fdiscount,0) as fdiscount,
    ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
   ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,ifnull(hinfo.loanamt,0) as loanamt,di.fi,di.mri,di.interest,di.principal,di.lotbal,di.housebal,di.hlbal,di.ortrno,di.checkno,di.payment,di.principalcol,di.percentage,di.paymentdate
    from glhead as head left join hcntnuminfo as hinfo on hinfo.trno = head.trno left join gldetail as detail on detail.trno = head.trno left join coa on coa.acnoid = detail.acnoid
    left join hdetailinfo as di on di.trno = detail.trno and di.line = detail.line where head.trno = ?  ";

    if ($isreverse == 0) {
      $data = $this->coreFunctions->opentable($qry . " and di.ortrno=0  and detail.acnoid = " . $aralias . " order by trno,line limit 1", [$artrno]);
    } else {
      $data = $this->coreFunctions->opentable($qry . " and di.ortrno=" . $trno . "  and detail.acnoid = " . $aralias . " order by trno,line ", [$artrno]);
    }

    $pline = 0;
    $update = [];
    $aralias = $this->coreFunctions->getfieldvalue("coa", "alias", "acnoid=" . $aralias);

    if (!empty($data)) {
      $this->coreFunctions->execqry("update hdetailinfo set  ortrno = " . $trno . ",checkno = '" . $checkno . "', paymentdate = '" . $checkdate . "' where trno =? and line =? ", "update", [$artrno, $data[0]->line]);

      if ($isreverse == 1) {
        $amount = $data[0]->db;
      }

      if ($amount != $data[0]->payment) {
        $pline = $data[0]->line - 1;
        if ($pline == 0) {
          $pline = $data[0]->line;
        }

        $prevball = $this->coreFunctions->getfieldvalue("hdetailinfo", "lotbal", "trno = ? and line = ?", [$artrno, $pline]);
        $prevbalh = $this->coreFunctions->getfieldvalue("hdetailinfo", "housebal", "trno = ? and line = ?", [$artrno, $pline]);
        $prevbal = $this->coreFunctions->getfieldvalue("hdetailinfo", "hlbal", "trno = ? and line = ?", [$artrno, $pline]);
        $prevprincipalcol = $this->coreFunctions->getfieldvalue("hdetailinfo", "principalcol", "trno = ? and line = ?", [$artrno, $pline]);

        $this->coreFunctions->LogConsole($prevprincipalcol . 'x');

        if ($aralias == 'ARDP') {
          $principal = $amount;
          $lotbal = $prevball / $prevbal * ($prevbal - $amount);
          $housebal = $prevbalh / $prevbal * ($prevbal - $amount);
          $hlbal = $prevbal - $amount;
          $principalcol =  $prevprincipalcol + $amount;

          $percentage = ($principalcol / $data[0]->fcontractprice) * 100;

          $update = [
            'principal' => $principal,
            'lotbal' => $lotbal,
            'housebal' => $housebal,
            'hlbal' => $hlbal,
            'payment' => $amount,
            'principalcol' =>  $principalcol,
            'percentage' => $percentage
          ];

          $this->coreFunctions->sbcupdate("hdetailinfo", $update, ['trno' => $artrno, 'line' => $data[0]->line]);
        }

        if ($aralias == 'AR1') {
          $interest = ($prevbal * ($data[0]->finterestrate / 100)) / 12;
          $principal = $amount - $data[0]->ffi - $data[0]->fmri - $interest;

          $interest = round($interest, 2);
          $principal = round($principal, 2);
          $lotbal = $prevball / $prevbal * ($prevbal - $principal);
          $housebal = $prevbalh / $prevbal * ($prevbal - $principal);
          $hlbal = $prevbal - $principal;

          $principalcol =  $prevprincipalcol + $principal;

          $percentage = ($principalcol / $data[0]->fcontractprice) * 100;

          $update = [
            'interest' => $interest,
            'principal' => $principal,
            'lotbal' => $lotbal,
            'housebal' => $housebal,
            'hlbal' => $hlbal,
            'payment' => $amount,
            'principalcol' =>  $principalcol,
            'percentage' => $percentage
          ];

          $this->coreFunctions->sbcupdate("hdetailinfo", $update, ['trno' => $artrno, "line" => $data[0]->line]);
        }

        //update remaining rows
        $prevball = $this->coreFunctions->getfieldvalue("hdetailinfo", "lotbal", "trno = ? and line = ?", [$artrno, $data[0]->line]);
        $prevbalh = $this->coreFunctions->getfieldvalue("hdetailinfo", "housebal", "trno = ? and line = ?", [$artrno, $data[0]->line]);
        $prevbal = $this->coreFunctions->getfieldvalue("hdetailinfo", "hlbal", "trno = ? and line = ?", [$artrno, $data[0]->line]);
        $prevprincipalcol = $this->coreFunctions->getfieldvalue("hdetailinfo", "principalcol", "trno = ? and line = ?", [$artrno, $data[0]->line]);

        $data2 = $this->coreFunctions->opentable($qry . " and detail.line>" . $data[0]->line . " order by trno,line", [$artrno]);

        foreach ($data2 as $k => $v) {
          if ($data2[$k]->alias == 'ARDP') {
            $principal = $data2[$k]->principal;
            $lotbal = $prevball / $prevbal * ($prevbal - $data2[$k]->payment);
            $housebal = $prevbalh / $prevbal * ($prevbal - $data2[$k]->payment);
            $hlbal = $prevbal - $data2[$k]->payment;
            $principalcol =  $prevprincipalcol + $data2[$k]->payment;

            $percentage = ($principalcol / $data2[$k]->fcontractprice) * 100;

            $update = [
              'principal' => $principal,
              'lotbal' => $lotbal,
              'housebal' => $housebal,
              'hlbal' => $hlbal,
              'principalcol' =>  $principalcol,
              'percentage' => $percentage,
              'payment' => $data2[$k]->db
            ];

            $this->coreFunctions->sbcupdate("hdetailinfo", $update, ["trno" => $artrno, "line" => $data2[$k]->line]);
          }

          if ($data2[$k]->alias == 'AR1') {
            $interest = ($prevbal * ($data2[$k]->finterestrate / 100)) / 12;
            $principal = $data2[$k]->db - $data2[$k]->ffi - $data2[$k]->fmri - $interest;

            $interest = round($interest, 2);
            $principal = round($principal, 2);
            $lotbal = $prevball / $prevbal * ($prevbal - $principal);
            $housebal = $prevbalh / $prevbal * ($prevbal - $principal);
            $hlbal = $prevbal - $principal;

            $principalcol =  $prevprincipalcol + $principal;

            $percentage = ($principalcol / $data2[$k]->fcontractprice) * 100;

            $update = [
              'interest' => $interest,
              'principal' => $principal,
              'lotbal' => $lotbal,
              'housebal' => $housebal,
              'hlbal' => $hlbal,
              'principalcol' =>  $principalcol,
              'percentage' => $percentage,
              'payment' => $data2[$k]->db
            ];

            $this->coreFunctions->sbcupdate("hdetailinfo", $update, ["trno" => $artrno, "line" => $data2[$k]->line]);
          }

          $prevball = $this->coreFunctions->getfieldvalue("hdetailinfo", "lotbal", "trno = ? and line = ?", [$artrno, $data2[$k]->line]);
          $prevbalh = $this->coreFunctions->getfieldvalue("hdetailinfo", "housebal", "trno = ? and line = ?", [$artrno, $data2[$k]->line]);
          $prevbal = $this->coreFunctions->getfieldvalue("hdetailinfo", "hlbal", "trno = ? and line = ?", [$artrno, $data2[$k]->line]);
          $prevprincipalcol = $this->coreFunctions->getfieldvalue("hdetailinfo", "principalcol", "trno = ? and line = ?", [$artrno, $data2[$k]->line]);
        }
      }
    }
  }

  public function hasnextcr($config)
  {
    $trno = $config['params']['trno'];
    $line = $this->coreFunctions->getfieldvalue('hdetailinfo', 'line', 'ortrno=?', [$trno]);
    $artrno = $this->coreFunctions->getfieldvalue('hdetailinfo', 'trno', 'ortrno=?', [$trno]);
    $a = $this->coreFunctions->getfieldvalue('hdetailinfo', 'line', 'ortrno<>0 and trno = ? and line > ?', [$artrno, $line]);
    if ($a !== '') {
      return ' Already have current payments applied to its reference transaction.';
    } else {
      return '';
    }
  }

  public function repeatstring($string, $len)
  {
    return str_repeat($string, $len);
  }

  public function getplanlimit($plangrpid, $amt, $trno)
  {
    $status = true;
    $plangrpbal = $this->coreFunctions->datareader("select bal as value from plangrp where line = " . $plangrpid);
    $lockedapp = $this->coreFunctions->datareader("select sum(pt.amount) as value from eahead as head left join transnum as t on t.trno = head.trno 
    left join plantype as pt on pt.line = head.planid and pt.plangrpid = head.plangrpid where head.lockdate is  not null and head.trno<>" . $trno . " and head.plangrpid = " . $plangrpid);

    if (floatval($plangrpbal - $lockedapp) < floatval($amt)) {
      $status = false;
    }

    return $status;
  }

  public function encryptString($string)
  {
    // Store the cipher method
    $ciphering = "AES-128-CTR";

    // Use OpenSSl Encryption method
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;

    // Non-NULL Initialization Vector for encryption
    $encryption_iv = '1234567891011121';

    // Store the encryption key
    // $encryption_key = "SolutionbaseCorp";
    $encryption_key = env('ENCRYPTION_KEY', 'SolutionbaseCorp');

    // Use openssl_encrypt() function to encrypt the data
    $encryption = openssl_encrypt($string, $ciphering, $encryption_key, $options, $encryption_iv);

    return $encryption;
  }

  public function decryptString($string)
  {
    // Store the cipher method
    $ciphering = "AES-128-CTR";

    // Non-NULL Initialization Vector for decryption
    $decryption_iv = '1234567891011121';

    // Store the decryption key
    // $decryption_key = "SolutionbaseCorp";
    $decryption_key = env('ENCRYPTION_KEY', 'SolutionbaseCorp');

    // Use openssl_decrypt() function to decrypt the data
    $decryption = openssl_decrypt($string, $ciphering, $decryption_key, 0, $decryption_iv);

    return $decryption;
  }

  public function getOrdinal($number)
  {
    $suffix = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
      $ordinal = $number . 'th';
    } else {
      $ordinal = $number . $suffix[$number % 10];
    }

    return $ordinal;
  }

  public function sbctransferlog($trno, $config, $htablelogs = '')
  {
    $tablelogs = isset($config['docmodule']) ? $config['docmodule']->tablelogs : '';
    $qry = "insert into " . $htablelogs . " (trno, field, oldversion, userid, dateid)
      select trno, field, oldversion, userid, dateid from $tablelogs where trno = " . $trno;

    if ($this->coreFunctions->execqry($qry, 'insert')) {
      $this->coreFunctions->execqry("delete from " . $tablelogs . " where trno = ?", 'delete', [$trno]);
      return true;
    } else {
      $this->coreFunctions->create_Elog($config['params']['doc'] . ' Trno: ' . $trno);
      return false;
    }
  }

  public function generateAJFAMS($config, $modulename = 'receiving')
  {
    if ($config['params']['companyid'] == 16) { //ati
      $pathrr = 'App\Http\Classes\modules\ati\rr';
    } else {
      $pathrr = 'App\Http\Classes\modules\purchase\rr';
    }


    $status = true;
    $msg = '';
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $rrdocno = $this->coreFunctions->getfieldvalue(app($pathrr)->head, "docno", "trno=?", [$trno]);
    $rrwh = $this->coreFunctions->getfieldvalue(app($pathrr)->head, "wh", "trno=?", [$trno]);

    $data = [];

    $ajtrno = 0;

    try {
      $faitems = $this->coreFunctions->opentable("select rr.trno, rr.line, rr.itemid, rr.qty, rr.ajtrno, rr.ajline, s.uom, s.whid, s.rrcost, s.disc
      from rrfams as rr left join lastock as s on s.trno=rr.trno and s.line=rr.line 
      where rr.trno=? and rr.ajtrno=0", [$trno]);
      if (!empty($faitems)) {

        foreach ($faitems as $key => $value) {
          $uom = $value->uom;
          $itemid =  $value->itemid;
          $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
          $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);

          $barcode = '';
          $qty = $value->qty;

          $qty = $this->sanitizekeyfield('qty', $qty);
          $amt = $this->sanitizekeyfield('amt', $value->rrcost);
          $factor = 1;
          if (!empty($item)) {
            $barcode = $item[0]->barcode;
            $item[0]->factor = $this->val($item[0]->factor);
            if ($item[0]->factor !== 0) {
              $factor = $item[0]->factor;
            }
          }
          $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
          $computed = $this->computestock($amt, $value->disc, $qty, $factor);
          $arr = [
            'trno' => $value->trno,
            'line' => $value->line,
            'ajtrno' => 0,
            'ajline' => 0,
            'itemid' => $itemid,
            'whid' => $value->whid,
            'uom' => $uom,
            'disc' => $value->disc,
            'rrqty' => $qty,
            'qty' => $computed['qty'],
            'rrcost' => $amt,
            'cost' => $computed['amt'],
            'ext' => $computed['ext']
          ];
          array_push($data, $arr);
        }

        if (!empty($data)) {
          $ajtrno = $this->generatecntnum($config, app($pathrr)->tablenum, 'AJ', 'AJ');
          if ($ajtrno != -1) {
            $docno =  $this->coreFunctions->getfieldvalue(app($pathrr)->tablenum, 'docno', "trno=?", [$ajtrno]);

            $head = [
              'trno' => $ajtrno,
              'doc' => 'AJ',
              'docno' => $docno,
              'dateid' => date('Y-m-d'),
              'contra' => $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IS1']),
              'wh' => $rrwh,
              'rem' => 'Adjustment for auto-created assets from ' . $modulename .  ' '  . $rrdocno
            ];

            if ($this->coreFunctions->sbcinsert('lahead', $head)) {
              $this->logger->sbcwritelog($ajtrno, $config, 'CREATE', 'AUTO-GENERATED ' . $docno, app($pathrr)->tablelogs);

              $line = 0;
              foreach ($data as $d => $dataval) {
                $qry = "select line as value from lastock where trno=? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$ajtrno]);

                if ($line == '') {
                  $line = 0;
                }

                $line = $line + 1;

                $stock = [
                  'trno' => $ajtrno,
                  'line' => $line,
                  'itemid' => $dataval['itemid'],
                  'whid' => $dataval['whid'],
                  'uom' => $dataval['uom'],
                  'disc' => $dataval['disc'],
                  'rrcost' => $dataval['rrcost'],
                  'cost' => $dataval['cost'],
                  'rrqty' => $dataval['rrqty'],
                  'qty' => $dataval['qty'],
                  'ext' => $dataval['ext']
                ];

                foreach ($stock as $skey => $sval) {
                  $stock[$skey] = $this->sanitizekeyfield($skey, $stock[$skey]);
                }

                if ($this->coreFunctions->sbcinsert('lastock', $stock)) {
                  $this->coreFunctions->sbcupdate('rrfams', ['ajtrno' => $ajtrno, 'ajline' => $line], ['trno' => $dataval['trno'], 'line' => $dataval['line'], 'itemid' => $dataval['itemid']]);
                } else {
                  $msg = 'Failed to insert AJ stock';
                  $status = false;
                  goto exithere;
                }
              } //end foreach items

              //postAJ
              $path = 'App\Http\Classes\modules\inventory\aj';
              $config['params']['trno'] = $ajtrno;
              $config['params']['doc'] = 'AJ';
              $return = app($path)->posttrans($config);
              if ($return['status']) {
                $status = true;
              } else {
                $msg = 'Failed to post AJ';
                $status = false;
                goto exithere;
              }
            } else {
              $msg = 'Failed to insert AJ head';
              $status = false;
              goto exithere;
            } //end insert glhead
          }
        }
      }
    } catch (Exception $e) {
      $msg = $e;
      $status = false;
    }

    exithere:
    if (!$status) {
      if ($ajtrno != 0) {
        $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$ajtrno]);
        $this->coreFunctions->execqry('delete from lastock where trno=?', 'delete', [$ajtrno]);
        $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$ajtrno]);
        $this->coreFunctions->execqry('update rrfams set ajtrno=0, ajline=0 where ajtrno=?', 'delete', [$ajtrno]);
      }
    }

    return ['status' => $status, 'msg' => $msg];
  }

  public function checkapplicantaccess($username, $password)
  {
    $password = str_replace("'", "", $password);
    $qry = "select md5(md5(app.username)) as username, app.username as username2, md5(app.password) as password,
      concat(app.empfirst, ' ', app.empmiddle, ' ', app.emplast) as name, md5(app.empid) as userid, 'DEFAULT' as theme,
      '' as userpic, 0 as clientid
      from app where md5(app.username)=md5('" . $username . "') and md5(app.password)='" . $password . "' limit 1";
    $data = $this->coreFunctions->opentable($qry);
    if (count($data) > 0) {
      return ['status' => true, 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'Error Login 8'];
    }
  }

  public function voidtransaction($config)
  {
    $status = false;
    $msg = '';

    $table = $config['docmodule']->hhead;
    $now = $this->getCurrentTimeStamp();
    $enddate = $this->getCurrentDate();
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, "docno", "trno=?", [$trno]);

    $due = $this->coreFunctions->getfieldvalue($config['docmodule']->hhead, "due", "trno=?", [$trno]);
    $this->logConsole($due . '  ' . $enddate);
    if ($due < $this->getCurrentDate()) {
      return ['status' => false, 'msg' => 'Promotion already ended.'];
    }

    if ($this->coreFunctions->execqry("update " . $table . " set voiddate='" . $now . "', voidby='" . $user . "', due='" . $enddate . "' where trno=" . $trno)) {
      $status = true;
      $msg = 'Successfully voided.';
      $this->logger->sbcwritelog($trno, $config, 'STATUS', 'VOID ' . $docno);
    } else {
      $msg = 'Failed to void.';
    }

    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function downloadexcel($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $messages = [
      'IS' => 'IS template ready to Download',
      'AJ' => 'AJ template ready to Download',
      'PC' => 'PC template ready to Download',
      'TS' => 'TS template ready to Download',
      'DM' => 'DM template ready to Download',
      'SJ' => 'SJ template ready to Download',
      'CM' => 'CM template ready to Download',
      'ST' => 'ST template ready to Download',
      'RR' => 'RR template ready to Download',
      'PG' => 'PG template ready to Download',
      'PO' => 'PO template ready to Download',
      'PI' => 'PI template ready to Download',
      'PA' => 'PA template ready to Download',
      'PP' => 'PP template ready to Download'
    ];

    if (!isset($messages[$doc])) {
      return [
        'status' => false,
        'msg' => $doc . ' template does not exist'
      ];
    }

    $data = array(
      'itemcode' => '',
      'qty'      => '',
      'uom'      => '',
      'location' => '',
      'expiry'   => '',
      'cost'     => '',
      'wh'       => ''
    );


    switch ($doc) {
      case 'IS':
      case 'AJ':
      case 'PC':
      case 'TS':
      case 'DM':
      case 'SJ':
      case 'CM':
      case 'ST':
        if ($companyid == 40) {
          unset($data['itemcode']);
          $data['partno'] = '';
        }
        break;
      case 'RR':
        switch ($companyid) {
          case 40: //cdo
            $data['partno'] = '';
            $data['disc'] = '';
            unset($data['wh']);
            unset($data['itemcode']);
            break;
          case 47: //kitchestr
          case 14: //majesty
          case 56: // homeworks
            $data['disc'] = '';
            unset($data['wh']);
            break;
        }
        break;
      case 'PG':
        switch ($companyid) {
          case 10: //afti
          case 14: //majesty
          case 17: //unihome
          case 19: //housegem
          case 28: //xcomp
          case 39: //CBBSI
            unset($data['location']);
            unset($data['expiry']);
            unset($data['disc']);
            unset($data['wh']);
            break;
        }
        break;
      case 'PO':
        switch ($companyid) {
          case 47: // kitchenstar
          case 56: // homeworks
            unset($data['location']);
            unset($data['expiry']);
            unset($data['wh']);
            break;
        }
        break;
      case 'PI':
        switch ($companyid) {
          case 50: //unitech
            $data['notes'] = '';
            $data['addnotes'] = '';
            unset($data['location']);
            unset($data['expiry']);
            unset($data['wh']);
            unset($data['disc']);
            break;
        }
        break;
      case 'PA':
        switch ($companyid) {
          case 56: // homeworks
            unset($data['location']);
            unset($data['expiry']);
            unset($data['wh']);
            unset($data['cost']);
            unset($data['uom']);
            unset($data['qty']);
            $data['itemdescription'] = '';
            $data['price'] = '';
            $data['disc'] = '';
            break;
        }
        break;
      case 'PP':
        switch ($companyid) {
          case 56: // homeworks
            $data['itemdescription'] = '';
            $data['startqty'] = '';
            $data['endqty'] = '';
            $data['promocountqty'] = '';
            unset($data['location']);
            unset($data['expiry']);
            unset($data['wh']);
            unset($data['cost']);
            unset($data['uom']);
            unset($data['qty']);
            break;
        }
        break;
    }

    return array(
      'status'   => true,
      'msg'      => $messages[$doc],
      'name'     => 'item',
      'data'     => array($data),
      'filename' => $doc . 'Template'
    );
  }

  public function val($input)
  {
    // Remove all non-numeric characters except digits, decimal point, and +-
    $cleaned = preg_replace('/[^0-9\.\+\-]/', '', (string)$input);

    // Convert to float if it contains a decimal point, otherwise to int
    if (strpos($cleaned, '.') !== false) {
      return (float)$cleaned;
    } else {
      return (int)$cleaned;
    }
  }

  public function checkApproverAccess($approver, $level)
  {
    $levelattr = [2410, 2411, 2412, 2413, 2414, 2415, 2416, 2417, 2418, 2419];

    $levelaccess = $levelattr[$level - 1];
    $lattr = $this->getAccess($approver->email);
    if (!empty($lattr)) {
      if (substr($lattr[0]->attributes, $levelaccess - 1, 1) == 1) {
        return true;
      }
      return false;
    }
    return false;
  }

  public function insertUpdatePendingapp($trno, $line, $doc, $data, $url, $config, $userid = 0, $checkrolelevel = false, $isfirstapp = false, $approvernote = '', $notesender = '')
  {
    $index = 1;
    if ($isfirstapp) $index = 0;
    $both = false;
    $admin = $config['params']['adminid'];
    $companyid = $config['params']['companyid'];
    $skiprole = false;
    if ($companyid == 58) { // cdohris
      $skiprole = true;
    }
    $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
    $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);

    $module = $this->coreFunctions->opentable("select line, approverseq from moduleapproval where modulename='" . $doc . "'");

    //for socket message
    $socketmsg = '';
    $socket_username = '';
    if ($this->companysetup->getsocketserver($config['params']) != '') {
      $socket_username = $this->coreFunctions->getfieldvalue("client", "email", "clientid=?", [$userid]);
      $socket_name = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$userid]);

      $_notesender = ($notesender != '' ? ' from ' . $notesender . '<br>' : '');

      switch ($doc) {
        case 'TM':
          $tasktitle = $this->coreFunctions->datareader("select title as value from tmdetail where trno=? and line=?", [$trno, $line]);
          $socketmsg = "Assigned task " . $tasktitle . " to " . $socket_name;

          if ($approvernote == 'COMMENT') {
            $tasktitle = $this->coreFunctions->datareader("select d.title as value from headprrem as rem left join tmdetail as d on d.trno=rem.tmtrno and d.line=rem.tmline where rem.tmtrno=? and rem.line=?", [$trno, $line]);
            $taskcomment = $this->coreFunctions->datareader("select rem as value from headprrem where tmtrno=? and line=?", [$trno, $line]);
            if ($taskcomment != '')  $socketmsg = " New comment: " . $taskcomment . ' for task ' . $tasktitle;
          }

          if ($approvernote == 'RETURN') {
            $taskcomment = $this->coreFunctions->datareader("select rem as value from headprrem where tmtrno=? and line=?", [$trno, $line]);
            if ($taskcomment != '') $socketmsg = "Return task " . $tasktitle . " to " . $socket_name;
          }

          if ($approvernote == 'FOR CHECKING') {
            $socketmsg = "For Checking: " . $tasktitle;
          }

          break;
        case 'DY':
          if ($approvernote == 'FOR CHECKING') {
            $tasktitle = $this->coreFunctions->datareader("select rem as value from dailytask where trno=? union all select rem as value from hdailytask where trno=?", [$trno, $trno]);
            if ($tasktitle != '') $socketmsg = "For checking task: " . $tasktitle;
          }

          if ($approvernote == 'RETURN') {
            $tasktitle = $this->coreFunctions->datareader("select rem as value from dailytask where trno=? union all select rem as value from hdailytask where trno=?", [$trno, $trno]);
            if ($tasktitle != '') $socketmsg = "Return task: " . $tasktitle . " to " . $socket_name;
          }

          if ($approvernote == 'COMMENT') {
            $tasktitle = $this->coreFunctions->datareader("select rem as value from dailytask where trno=? union all select rem as value from hdailytask where trno=?", [$trno, $trno]);
            $taskcomment = $this->coreFunctions->datareader("select rem as value from headprrem where dytrno=? and line=?", [$trno, $line]);
            if ($taskcomment != '')  $socketmsg = " New comment" . $_notesender . ": " . $taskcomment . ' for task ' . $tasktitle;
          }
          break;
      }
    }
    //end for socket message

    if ($userid != 0) {
      $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $userid, $approvernote]);
      if ($socketmsg != '' && $socket_username != '') $this->socketmsg($config, $socketmsg, '', $socket_username);
      return ['status' => true, 'msg' => ''];
    } else {
      if (!empty($module)) {
        if ($module[0]->approverseq != '' && $module[0]->approverseq != null) {
          if (str_contains($module[0]->approverseq, ' or ')) {
            $approverseq = explode(' or ', $module[0]->approverseq);
            $both = true;
          } else {
            $approverseq = explode(',', $module[0]->approverseq);
          }
          foreach ($approverseq as $akey => $apseq) $approverseq[$akey] = $approverseq[$akey] == 'Approver' ? 'isapprover' : 'issupervisor';
        } else {
          $approverseq = app($url)->approvers($config['params']);
        }
        if ($checkrolelevel) {
          $empid = $data['empid'];
          if ($both) {
            $checksupervisor = $checkapprover = ['status' => true, 'msg' => ''];
            $supervisor = $this->coreFunctions->getfieldvalue("employee", "supervisorid", "empid=?", [$empid]);
            if ($supervisor != 0) {
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $supervisor, 'issupervisor']);
            } else {

              $multiapp = $this->coreFunctions->opentable("select approverid as clientid from multiapprover where empid = '$empid' and  doc = '$doc' and (issupervisor = 1 or isapprover = 1)");
              if (!empty($multiapp)) {
                foreach ($multiapp as $apps) {
                  $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->clientid, 'issupervisor']);
                }
              } else {
                // goto contcheckrolelevel;
                $checksupervisor = $this->checkrolelevel('issupervisor', $empid, $trno, $line, $doc, $skiprole);
                if (!$checkapprover['status']) {
                  return  $checkapprover;
                }
              }
            }

            // $approver1 = $this->coreFunctions->getfieldvalue("employee", "approver1", "empid=?", [$empid]);
            // if ($approver1 != 0) {
            //   $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $approver1, 'isapprover']);
            // } else {
            //   $multiapp = $this->coreFunctions->opentable("select approverid as clientid from multiapprover where empid = '$empid' and  doc = '$doc' and isapprover = 1");

            //   if (!empty($multiapp)) {
            //     foreach ($multiapp as $apps) {
            //       $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->clientid, 'isapprover']);
            //     }
            //   } else {
            //     // goto contcheckrolelevel;
            //     $checkapprover = $this->checkrolelevel('isapprover', $empid, $trno, $line, $doc);
            //     if (!$checkapprover['status']) {
            //       return  $checkapprover;
            //     }
            //   }
            // }



            if ($checksupervisor['status'] && $checkapprover['status']) {
              return ['status' => true, 'msg' => ''];
            } else {
              $msg1 = $checksupervisor['msg'] . ' ' . $checkapprover['msg'];
              return ['status' => false, 'msg' => $msg1];
            }
          } else {
            if ($approverseq[$index] == 'issupervisor') {
              $supervisor = $this->coreFunctions->getfieldvalue("employee", "supervisorid", "empid=?", [$empid]);
              if ($supervisor != 0) {
                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $supervisor, $approverseq[$index]]);
                return ['status' => true, 'msg' => ''];
              } else {

                $multiapp = $this->coreFunctions->opentable("select approverid as clientid from multiapprover where empid = '$empid' and  doc = '$doc' and issupervisor = 1");
                if (!empty($multiapp)) {
                  foreach ($multiapp as $apps) {

                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->clientid, $approverseq[$index]]);
                  }
                  return ['status' => true, 'msg' => ''];
                }
                // goto contcheckrolelevel;

                if (isset($approverseq[$index])) {
                  $resultrole = $this->checkrolelevel($approverseq[$index], $empid, $trno, $line, $doc, $skiprole);
                  if (!$resultrole['status']) {
                    return  $resultrole;
                  }
                }
              }
            } else {
              $approver1 = $this->coreFunctions->getfieldvalue("employee", "approver1", "empid=?", [$empid]);
              if ($approver1 != 0) {
                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $approver1, $approverseq[$index]]);
                return ['status' => true, 'msg' => ''];
              } else {
                $multiapp = $this->coreFunctions->opentable("select approverid as clientid from multiapprover where empid = '$empid' and  doc = '$doc' and isapprover = 1");
                if (!empty($multiapp)) {
                  foreach ($multiapp as $apps) {

                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->clientid, $approverseq[$index]]);
                  }
                  return ['status' => true, 'msg' => ''];
                }
              }
              // contcheckrolelevel:
              if (isset($approverseq[$index])) {
                $resultrole = $this->checkrolelevel($approverseq[$index], $empid, $trno, $line, $doc, $skiprole);
                if (!$resultrole['status']) {
                  return  $resultrole;
                }
              }
            }
          }
        } else {
          checkOther:
          $approvers = $this->coreFunctions->opentable("select p.clientid from approvers as p left join moduleapproval as m on m.line=p.trno where m.modulename='" . $doc . "' and p." . $approverseq[$index] . "=1");
          if (!empty($approvers)) {
            foreach ($approvers as $a) {
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $a->clientid, $approverseq[$index]]);
            }
          }
        }
      } else {
        return ['status' => false, 'msg' => 'Module approval not set.'];
      }
      return ['status' => true, 'msg' => ''];
    }
  }

  public function checkrolelevel($type, $empid, $trno, $line, $doc, $skiprole)
  {
    $hasapprovers = false;
    $level = $this->coreFunctions->datareader("select level as value from employee where empid=" . $empid, [], '', true);
    $roleid = $this->coreFunctions->datareader("select roleid as value from employee where empid=" . $empid, [], '', true);

    if ($skiprole) goto checklevel;

    if ($roleid != 0) {
      $approver = $this->coreFunctions->opentable("select er.empid, client.email from emprole as er left join employee as emp on emp.empid=er.empid left join client on client.clientid=emp.empid where er.roleid=" . $roleid . " and emp." . $type . "=1 and er.empid<>" . $empid);
      if (!empty($approver)) {
        if ($level != 0) {
          $hasapprovers = false;
          foreach ($approver as $apps) {
            if ($this->checkApproverAccess($apps, $level)) {
              $hasapprovers = true;
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->empid, $type]);
            }
          }
          if (!$hasapprovers) {
            return ['status' => false, 'msg' => 'No approvers from role setup for this employee.'];
          }
        } else {
          return ['status' => false, 'msg' => 'No level tagged for this employee.'];
        }

        return ['status' => true, 'msg' => ''];
      } else {
        //return ['status' => false, 'msg' => 'No approvers for this employee.'];
        goto checklevel;
      }
    } else {
      checklevel:
      if ($level != 0) {
        $hasapprovers = false;
        $approvers = $this->coreFunctions->opentable("select a.clientid, client.email from approvers as a left join moduleapproval as m on m.line=a.trno left join client on client.clientid=a.clientid where m.modulename='" . $doc . "' and a." . $type . "=1");
        if (!empty($approvers)) {
          foreach ($approvers as $apps) {
            if ($this->checkApproverAccess($apps, $level)) {
              $hasapprovers = true;
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->clientid, $type]);
            }
          }
          if (!$hasapprovers) {
            return ['status' => false, 'msg' => 'No approvers for this employee level.'];
          }
          return ['status' => true, 'msg' => ''];
        } else {
          return ['status' => false, 'msg' => 'No approvers for this employee.'];
        }
      } else {
        return ['status' => false, 'msg' => 'No level tagged for this employee.'];
      }
    }
    return ['status' => true, 'msg' => []];
  }

  public function updatePendingapp($trno, $line, $doc, $data, $url, $config, $userid = 0, $checkrolelevel = false)
  {
    $admin = $config['params']['adminid'];
    $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$admin]);
    $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$admin]);
    $module = $this->coreFunctions->opentable("select line, approverseq from moduleapproval where modulename='" . $doc . "'");
    if ($userid != 0) {
      $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid) values(?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $userid]);
    } else {
      if (!empty($module)) {
        if ($module[0]->approverseq != '' && $module[0]->approverseq != null) {
          $approverseq = explode(',', $module[0]->approverseq);
          foreach ($approverseq as $akey => $apseq) $approverseq[$akey] = $approverseq[$akey] == 'Approver' ? 'isapprover' : 'issupervisor';
        } else {
          $approverseq = app($url)->approvers($config['params']);
        }
        if ($checkrolelevel) {
          $empid = $data['empid'];
          if (isset($approverseq[1]) && $approverseq[1] == 'issupervisor') {
            $supervisor = $this->coreFunctions->datareader("select supervisorid as value from employee where empid=" . $empid, [], '', true);
            if ($supervisor != 0) {
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $supervisor, $approverseq[1]]);
              return ['status' => true, 'msg' => ''];
            } else {
              goto contcheckrolelevel;
            }
          } else {
            contcheckrolelevel:
            $level = $this->coreFunctions->datareader("select level as value from employee where empid=" . $empid, [], '', true);
            $levelattr = [2410, 2411, 2412, 2413, 2414, 2415, 2416, 2417, 2418, 2419];
            $roleid = $this->coreFunctions->datareader("select roleid as value from employee where empid=" . $empid, [], '', true);
            if ($roleid != 0) {
              if (isset($approverseq[1])) {
                $approver = $this->coreFunctions->opentable("select er.empid, client.email from emprole as er left join employee as emp on emp.empid=er.empid left join client on client.clientid=emp.empid where er.roleid=" . $roleid . " and emp." . $approverseq[1] . "=1");
                if (!empty($approver)) {
                  if ($level != 0) {
                    $levelaccess = $levelattr[$level - 1];
                    $hasapprovers = false;
                    foreach ($approver as $apps) {
                      $lattr = $this->getAccess($apps->email);
                      if (!empty($lattr)) {
                        if (substr($lattr[0]->attributes, $levelaccess - 1, 1) == 1) {
                          $hasapprovers = true;
                          $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->empid, $approverseq[1]]);
                        }
                      }
                    }
                    if (!$hasapprovers) {
                      return ['status' => false, 'msg' => 'No approvers for this employee.'];
                    }
                  } else {
                    return ['status' => false, 'msg' => 'No level tagged for this employee.'];
                  }
                  return ['status' => true, 'msg' => ''];
                } else {
                  return ['status' => false, 'msg' => 'No approvers for this employee.'];
                }
              }
            } else {
              checklevel:
              if ($level != 0) {
                if (isset($approverseq[1])) {
                  $levelaccess = $levelattr[$level - 1];
                  $hasapprovers = false;
                  $approvers = $this->coreFunctions->opentable("select emp.empid, client.email from employee as emp left join client on client.clientid=emp.empid where emp." . $approverseq[1] . "=1");
                  if (!empty($approvers)) {
                    foreach ($approvers as $apps) {
                      $lattr = $this->getAccess($apps->email);
                      if (!empty($lattr)) {
                        if (substr($lattr[0]->attributes, $levelaccess - 1, 1) == 1) {
                          $hasapprovers = true;
                          $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->empid, $approverseq[1]]);
                        }
                      }
                    }
                    if (!$hasapprovers) {
                      return ['status' => false, 'msg' => 'No approvers for this employee level.'];
                    }
                  } else {
                    return ['status' => false, 'msg' => 'No approvers for this employee.'];
                  }
                }
              } else {
                return ['status' => false, 'msg' => 'No level tagged for this employee.'];
              }
            }
          }
        } else {
          if ($supervisor && ($approverseq[0] == 'issupervisor' || $approverseq[0] == 'isotapprover')) {
            if (isset($approverseq[1])) {
              if ($approverseq[1] == 'isapprover') {
                $approvers = $this->coreFunctions->opentable("select p.clientid from approvers as p left join moduleapproval as m on m.line=p.trno where m.modulename='" . $doc . "' and p.isapprover=1");
                if (!empty($approvers)) {
                  foreach ($approvers as $a) {
                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $a->clientid, $approverseq[1]]);
                  }
                } else {
                  $approvers2 = $this->coreFunctions->opentable("select empid from employee where isapprover=1");
                  if (!empty($approvers2)) {
                    foreach ($approvers2 as $a2) {
                      $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $a2->empid, $approverseq[1]]);
                    }
                  }
                }
              }
            }
          } else if ($approver && $approverseq[0] == 'isapprover') {
            if (isset($approverseq[1])) {
              if ($approverseq[1] == 'issupervisor') {
                $supervisors = $this->coreFunctions->opentable("select p.clientid from approvers as p left join moduleapproval as m on m.line=p.trno where m.modulename='" . $doc . "' and p.issupervisor=1");
                if (!empty($supervisors)) {
                  foreach ($supervisors as $s) {
                    $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $s->clientid, $approverseq[1]]);
                  }
                } else {
                  $supervisors2 = $this->coreFunctions->opentable("select empid from employee where isapprover=1");
                  if (!empty($supervisors2)) {
                    foreach ($supervisors2 as $a2) {
                      $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $a2->empid, $approverseq[1]]);
                    }
                  }
                }
              }
            }
          }
          return ['status' => true, 'msg' => ''];
        }
      }
    }
    return ['status' => true, 'msg' => ''];
  }

  public function insertPendingapp($trno, $line, $doc, $data, $url, $config, $userid = 0, $checkrolelevel = false)
  {
    $module = $this->coreFunctions->opentable("select line, approverseq from moduleapproval where modulename='" . $doc . "'");
    if ($userid != 0) {
      $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid) values(?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $userid]);
    } else {
      if (!empty($module)) {
        if ($module[0]->approverseq != '' && $module[0]->approverseq != null) {
          $approverseq = explode(',', $module[0]->approverseq);
          foreach ($approverseq as $akey => $apseq) $approverseq[$akey] = $approverseq[$akey] == 'Approver' ? 'isapprover' : 'issupervisor';
        } else {
          $approverseq = app($url)->approvers($config['params']);
        }
        if ($checkrolelevel) {
          $empid = $config['params']['adminid'];
          if ($approverseq[0] == 'issupervisor') {
            $supervisor = $this->coreFunctions->datareader("select supervisorid as value from employee where empid=" . $empid, [], '', true);
            if ($supervisor != 0) {
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $supervisor, $approverseq[0]]);
              return ['status' => true, 'msg' => ''];
            } else {
              goto contcheckrolelevel;
            }
          } else {
            contcheckrolelevel:
            $level = $this->coreFunctions->datareader("select level as value from employee where empid=" . $empid, [], '', true);
            $levelattr = [2410, 2411, 2412, 2413, 2414, 2415, 2416, 2417, 2418, 2419];
            $roleid = $this->coreFunctions->datareader("select roleid as value from employee where empid=" . $empid, [], '', true);
            if ($roleid != 0) {
              $approver = $this->coreFunctions->opentable("select er.empid, client.email from emprole as er left join employee as emp on emp.empid=er.empid left join client on client.clientid=emp.empid where er.roleid=" . $roleid . " and emp." . $approverseq[0] . "=1");
              if (!empty($approver)) {
                if ($level != 0) {
                  $levelaccess = $levelattr[$level - 1];
                  $hasapprovers = false;
                  foreach ($approver as $apps) {
                    $lattr = $this->getAccess($apps->email);
                    if (!empty($lattr)) {
                      if (substr($lattr[0]->attributes, $levelaccess - 1, 1) == 1) {
                        $hasapprovers = true;
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->empid, $approverseq[0]]);
                      }
                    }
                  }
                  if (!$hasapprovers) {
                    return ['status' => false, 'msg' => 'No approvers for this employee.'];
                  }
                } else {
                  return ['status' => false, 'msg' => 'No level tagged for this employee.'];
                }
                return ['status' => true, 'msg' => ''];
              } else {
                goto checklevel;
              }
            } else {
              checklevel:
              if ($level != 0) {
                $levelaccess = $levelattr[$level - 1];
                $hasapprovers = false;
                $approvers = $this->coreFunctions->opentable("select emp.empid, client.email from employee as emp left join client on client.clientid=emp.empid where emp." . $approverseq[0] . "=1");
                if (!empty($approvers)) {
                  foreach ($approvers as $apps) {
                    $lattr = $this->getAccess($apps->email);
                    if (!empty($lattr)) {
                      if (substr($lattr[0]->attributes, $levelaccess - 1, 1) == 1) {
                        $hasapprovers = true;
                        $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $apps->empid, $approverseq[0]]);
                      }
                    }
                  }
                  if (!$hasapprovers) {
                    return ['status' => false, 'msg' => 'No approvers for this employee level.'];
                  }
                } else {
                  return ['status' => false, 'msg' => 'No approvers for this employee.'];
                }
              } else {
                return ['status' => false, 'msg' => 'No level tagged for this employee.'];
              }
              return ['status' => true, 'msg' => ''];
            }
          }
        } else {
          $supapprovers = $this->coreFunctions->opentable("select clientid from approvers where trno=" . $module[0]->line . " and " . $approverseq[0] . "=1");
          if (!empty($supapprovers)) {
            foreach ($supapprovers as $sa) {
              $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $sa->clientid, $approverseq[0]]);
            }
          } else {
            if ($approverseq[0] == 'issupervisor' && $checkrolelevel) {
              $checksupervisor = $this->coreFunctions->datareader("select supervisorid as value from employee where empid=" . $data['empid'], [], '', true);
              if ($checksupervisor != 0) {
                $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $checksupervisor, $approverseq[0]]);
              }
            } else if ($approverseq[0] == 'isapprover' && $checkrolelevel) {
              $checkapprovers = $this->coreFunctions->opentable("select empid from employee where isapprover=1");
              if (!empty($checkapprovers)) {
                foreach ($checkapprovers as $approver) {
                  $this->coreFunctions->execqry("insert into pendingapp(trno, line, doc, clientid, approver) values(?, ?, ?, ?, ?)", 'insert', [$trno, $line, $doc, $approver->empid, $approverseq[0]]);
                }
              } else {
                return ['status' => false, 'msg' => 'No approvers for this user.'];
              }
            } else {
              return ['status' => false, 'msg' => 'No approvers for this user..'];
            }
          }
          return ['status' => true, 'msg' => ''];
        }
      }
    }
  }

  public function array_add($array, $key, $val)
  {
    if (!isset($array[$key])) {
      $array[$key] = $val;
    }
    return $array;
  }

  public function sbcround($formatted_number, $precision = 0)
  {
    $clean_string = str_replace(',', '', $formatted_number);
    return round((float)$clean_string, $precision, PHP_ROUND_HALF_UP);
  }

  public function getmultiitem($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $path = '';
    switch ($config['params']['doc']) {
      case 'IS':
        $path = 'App\Http\Classes\modules\inventory\is';
        break;

      case 'AJ':
        $path = 'App\Http\Classes\modules\inventory\aj';
        break;

      case 'PC':
        $path = 'App\Http\Classes\modules\inventory\pc';
        break;
      case 'AT':
        $path = 'App\Http\Classes\modules\inventory\at';
        break;
      case 'TS':
        $path = 'App\Http\Classes\modules\inventory\ts';
        break;
      case 'DM':
        $path = 'App\Http\Classes\modules\purchase\dm';
        break;
      case 'SJ':
        $path = 'App\Http\Classes\modules\sales\sj';
        if ($companyid == 63) { //ericco
          $path = 'App\Http\Classes\modules\e4c3fe3674108174825a187099e7349f6\sj';
        }
        break;
      case 'CH':
        $path = 'App\Http\Classes\modules\e4c3fe3674108174825a187099e7349f6\ch';
        break;
      case 'CM':
        $path = 'App\Http\Classes\modules\sales\cm';
        break;
      case 'ST':
        $path = 'App\Http\Classes\modules\issuance\st';
        break;
      case 'PA':
        $path = 'App\Http\Classes\modules\pos\pa';
        break;
      case 'PP':
        $path = 'App\Http\Classes\modules\pos\pp';
        break;
      case 'PO':
        $path = 'App\Http\Classes\modules\purchase\po';
        break;
      case 'RR':
        $path = 'App\Http\Classes\modules\purchase\rr';
        break;
    }

    $data = $config['params']['rows'];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['data']['uom'] = $data[$key]['uom'];
      $config['params']['data']['itemid'] = $data[$key]['itemid'];
      $config['params']['trno'] = $trno;
      $config['params']['data']['qty'] = 1;
      $config['params']['data']['wh'] = $wh;
      $config['params']['data']['disc'] = '';
      $config['params']['data']['amt'] = $data[$key]['amt'];
      $return = app($path)->additem('insert', $config);
      if ($msg = '') {
        $msg = $return['msg'];
      } else {
        $msg = $msg . $return['msg'];
      }

      if ($return['status']) {
        $line = $return['row'][0]->line;
        $config['params']['trno'] = $trno;
        $config['params']['line'] = $line;
        $row = app($path)->openstockline($config);
      }
      array_push($rows, $return['row'][0]);
    } //end foreach
    return ['row' => $rows, 'status' => true,  'msg' => 'Items were successfully added. ' . $msg];
  } //end function

  public function socketmsg($config, $msg, $api = '', $user = '')
  {
    $socketserver = $this->companysetup->getsocketserver($config['params']);
    $companyname = $this->companysetup->getcompanyname($config['params']);
    if (!$socketserver == '') {
      $client = new Client([
        'base_uri' => $socketserver,
        'timeout' => 5.0,
      ]);

      $user2 = $config['params']['user'];
      $sendtype = 'exclude';
      if ($user != '') {
        $sendtype = 'specific';
        $user2 = $user;
      }

      $apilink = '/api/send-message';
      switch ($api) {
        default:
          $apilink = '/api/send-message';
          break;
      }
      $response = $client->post($apilink, [
        'json' => [
          'message' => $msg,
          'user' => $user2,
          'group' => $companyname,
          'sendtype' => $sendtype, // all or exclude or specific
          'timestamp' => date('Y-m-d H:i:s'),
          'sender' => $config['params']['user']
        ]
      ]);
    }
  } //end function


  public function socketqueuing($params, $msg, $api = '', $user = '')
  {
    $socketserver = $this->companysetup->getsocketserver($params);
    $companyname = $this->companysetup->getcompanyname($params);
    if (!$socketserver == '') {
      $client = new Client([
        'base_uri' => $socketserver,
        'timeout' => 30.0,
      ]);

      $user2 = $params['user'];
      $sendtype = 'exclude';
      if ($user != '') {
        $sendtype = 'specific';
        $user2 = $user;
      }

      $apilink = '/api/send-queuing';
      switch ($api) {
        default:
          $apilink = '/api/send-queuing';
          break;
      }
      $response = $client->post($apilink, [
        'json' => [
          'message' => $msg,
          'user' => $user2,
          'group' => $companyname,
          'sendtype' => $sendtype, // all or exclude or specific
          'timestamp' => date('Y-m-d H:i:s'),
          'sender' => $params['user']
        ]
      ]);
    }
  } //end function
































































  //******************************************************
  // function above already used




}//end fn class other class
