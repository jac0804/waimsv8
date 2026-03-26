<?php

namespace App\Http\Classes;

use App\Http\Classes\coreFunctions;

class Logger
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end construct

  public function sbcwritelogimage($trno, $table, $user, $field, $oldvalue, $doc)
  { // added user encoded
    $current_timestamp = $this->getCurrentTimeStamp();
    $data = ['trno' => $trno, 'field' => $field, 'oldversion' => $oldvalue, 'userid' => $user, 'dateid' => $current_timestamp];

    switch ($table) {
      case 'payroll_log':
      case 'masterfile_log':
        $data = ['trno' => $trno, 'doc' => $doc, 'task' => $oldvalue, 'user' => $user, 'dateid' => $current_timestamp];
        break;
      default:
        $data = ['trno' => $trno, 'field' => $field, 'oldversion' => $oldvalue, 'userid' => $user, 'dateid' => $current_timestamp];
        break;
    }

    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END WRITE LOG


  public function sbcwritelog($trno, $config, $field, $oldvalue, $tablelogs = '', $istemp = 0)
  { // added user encoded
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];

    if ($tablelogs != '') {
      $table = $tablelogs;
    } else {
      $table = isset($config['docmodule']) ? $config['docmodule']->tablelogs : '';
    }

    $current_timestamp = $this->getCurrentTimeStamp();

    switch ($table) {
      case 'payroll_log':
      case 'masterfile_log':
        $data = ['trno' => $trno, 'doc' => $doc, 'task' => $oldvalue, 'user' => $user, 'dateid' => $current_timestamp];
        break;
      default:
        $data = ['trno' => $trno, 'field' => $field, 'oldversion' => $oldvalue, 'userid' => $user, 'dateid' => $current_timestamp];
        break;
    }

    if ($istemp != 0) $data['istemp'] = $istemp;

    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END WRITE LOG

  public function sbcstatlog($trno, $config, $field, $oldvalue, $statlogs = '', $arr1 = [], $arr2 = [], $arr3 = [])
  { // added user encoded
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $current_timestamp = $this->getCurrentTimeStamp();


    if ($statlogs != '') {
      $table = $statlogs;
    } else {
      $table = isset($config['docmodule']) ? $config['docmodule']->statlogs : '';
    }

    $data = [
      'trno' => $trno,
      'field' => $field,
      'oldversion' => $oldvalue,
      'userid' => $user,
      'dateid' => $current_timestamp
    ];

    if (!empty($arr1)) {
      $data['dateid2'] = $arr1;
    }

    if (!empty($arr2)) {
      $data['dateid3'] = $arr2;
    }

    if (!empty($arr3)) {
      $data['remarks'] = $arr3;
    }
    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END STAT LOG

  public function sbcmasterlog($trno, $config, $task, $isedit = 0, $ismultigrid = 0, $trno2 = 0, $istemp = 0)
  { // added user encoded

    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $table = $config['docmodule']->tablelogs;
    $current_timestamp = $this->getCurrentTimeStamp();

    if ($ismultigrid) {
      $doc = strtoupper($config['params']['lookupclass']);
    }

    $data = [
      'trno' => $trno,
      'doc' => $doc,
      'task' => $task,
      'dateid' => $current_timestamp,
      'user' => $user
    ];

    if ($isedit != 0) {
      $data['user'] = $user;
      $data['dateid'] = $current_timestamp;
    }

    if ($trno2 != 0) {
      $data['trno2'] = $trno2;
    }

    if ($istemp != 0) $data['istemp'] = $istemp;

    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END WRITE LOG

  public function sbcmasterlog2($trno, $config, $task, $ptable, $istemp = 0)
  { // added user encoded

    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    if ($ptable != '') {
      $table = $ptable;
    } else {
      $table = $config['docmodule']->tablelogs;
    }
    $current_timestamp = $this->getCurrentTimeStamp();
    $data = ['trno' => $trno, 'doc' => $doc, 'task' => $task, 'dateid' => $current_timestamp, 'user' => $user];
    if ($istemp != 0) $data['istemp'] = $istemp;
    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END WRITE LOG

  public function sbcwritelog2($trno, $user, $field, $oldvalue, $table, $istemp = 0)
  { // added user encoded
    $current_timestamp = $this->getCurrentTimeStamp();
    $data = ['trno' => $trno, 'field' => $field, 'oldversion' => $oldvalue, 'userid' => $user, 'dateid' => $current_timestamp];
    if ($istemp != 0) $data['istemp'] = $istemp;
    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  } //END WRITE LOG    

  public function sbcviewreportlog($config, $msg = 'View Printing...')
  {
    $this->sbcwritelog($config['params']['dataid'], $config, 'PRINT', $msg);
  }

  public function sbcdelmaster_log($trno, $config, $task, $ismultigrid = 0, $trno2 = 0)
  {
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $company = $config['params']['companyid'];
    $table = $config['docmodule']->tablelogs_del;
    $this->coreFunctions->LogConsole($table);

    $current_timestamp = $this->getCurrentTimeStamp();

    if ($ismultigrid) {
      $doc = strtoupper($config['params']['lookupclass']);
    }

    switch ($company) {
      case 39: //cbbsi
        switch ($table) {
          case 'del_table_log':
            $data = [
              'trno' => $trno,
              'field' => $task,
              'dateid' => $current_timestamp,
              'userid' => $user
            ];
            break;

          default:
            $data = [
              'trno' => $trno,
              'doc' => $doc,
              'task' => $task,
              'dateid' => $current_timestamp,
              'user' => $user
            ];
            break;
        }

        break;

      default:
        $data = [
          'trno' => $trno,
          'doc' => $doc,
          'task' => $task,
          'dateid' => $current_timestamp,
          'user' => $user
        ];
        break;
    }

    if ($trno2 != 0) {
      $data['trno2'] = $trno2;
    }

    $this->coreFunctions->LogConsole(json_encode($data));

    $test = $this->coreFunctions->sbcinsert($table, $data);
    $this->coreFunctions->LogConsole(json_encode($test));
    return true;
  }

  public function sbcdel_log($trno, $config, $docno, $field = 'TRANSACTION')
  {
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];
    $table = $config['docmodule']->tablelogs_del;
    $current_timestamp = $this->getCurrentTimeStamp();
    $data = ['trno' => $trno, 'docno' => $docno, 'field' => $field, 'userid' => $user, 'dateid' => $current_timestamp];
    $this->coreFunctions->sbcinsert($table, $data);
    return true;
  }


  public function sbciplog($doc, $ip, $user)
  {
    //doc list
    //LOGIN
    //LOG-FAIL
    //XX-FAIL
    $data = ['doc' => $doc, 'ip' => $ip, 'username' => $user, 'accessdate' => $this->getCurrentTimeStamp()];
    $this->coreFunctions->sbcinsert('iplog', $data);
  } //end function

  public function setDefaultTimeZone()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    date_default_timezone_set('Asia/Singapore');
  } //end function

  public function getCurrentTimeStamp()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    $this->setDefaultTimeZone();
    $current_timestamp = date('Y-m-d H:i:s');
    return $current_timestamp;
  } //end function

































  //**********************************************************************
  // function above used only


  public function writelog($doc, $trno, $field, $oldvalue, $userencoded)
  { // added user encoded
    switch ($doc) {
      case 'PO':
      case 'PC':
      case 'SO':
      case 'KR':
      case 'JO':
      case 'EX':
      case 'PR':
      case 'quotation':
      case 'QT':
      case 'JB':
      case 'SP':
      case 'PW':
        $table = 'transnum_log';
        break;

      case 'customer':
      case 'supplier':
      case 'warehouse':
      case 'agent':
        $table = 'client_log';
        break;

      case 'stockcard':
      case 'posstockcard':
        $table = 'item_log';
        break;

      case 'TERMS':
        $table = 'terms_log';
        break;

      case 'USERACCESS':
        $table = 'useraccess_log';
        break;

      case 'CENTER':
        $table = 'center_log';
        break;

      default:
        $table = 'table_log';
        break;
    } //end switch

    $current_timestamp = $this->getCurrentTimeStamp();

    $qry = "INSERT into $table (trno,field,oldversion,userid,dateid) values('$trno','$field','$oldvalue','$userencoded','" . $current_timestamp . "')";
    $this->coreFunctions->execqry($qry, 'insert');

    return true;
  } //END WRITE LOG

  public function del_log($doc, $trno, $docno, $user, $field = 'TRANSACTION')
  {
    switch (strtoupper($doc)) {
      case 'PO':
      case 'PC':
      case 'SO':
      case 'KR':
      case 'EX':
      case 'JO':
      case 'PR':
      case 'SP':
        $table = ' del_transnum_log ';
        break;

      case 'CUSTOMER':
      case 'SUPPLIER':
      case 'WAREHOUSE':
      case 'AGENT':
        $table = ' del_client_log ';
        break;

      case 'ITEMS':
        $table = ' del_item_log ';
        $field = 'STOCKCARD';
        break;

      case 'TERMS':
        $table = 'del_terms_log';
        break;

      case 'USERACCESS':
        $table = 'del_useraccess_log';
        break;

      case 'CENTER':
        $table = 'del_center_log';
        break;

      default:
        $table = ' del_table_log ';
        break;
    } //end switch

    $current_timestamp = $this->getCurrentTimeStamp();
    $qry = "insert into $table (trno,docno,field,userid,dateid) values('$trno','$docno','$field','$user','" . $current_timestamp . "')";
    $insert_log = $this->coreFunctions->execqry($qry, 'insert');

    if ($insert_log == 1) {
      return 1;
    } else {
      return 0;
    } ///end if
  } //end fn


  public function getlogsterms()
  {
    $sql = "
            select trno, field, oldversion, log.userid, dateid, if(pic='','blank_user.png',pic) as pic
            from terms_log as log
            left join useraccess as u on u.username=log.userid                
            union all
            select trno, concat('DELETE',' ',field), docno, log.userid, dateid, if(pic='','blank_user.png',pic) as pic
            from  del_terms_log as log
            left join useraccess as u on u.username=log.userid
            union all
            select line,'CREATE',terms,createby,createdate, if(pic='','blank_user.png',pic) as pic
            from terms as log left join useraccess as u on u.username=log.createby";
    $sql = $sql . " order by dateid desc";

    $data = $this->coreFunctions->opentable($sql);

    if (!empty($data)) {
      return $data;
    } else {
      return false;
    } //end if status     
  } //end terms

  public function getlogs($doc, $trno, $level = 'ALL')
  {
    switch ($level) {
      case 'H': {
          $filter = ' field = HEAD';
          break;
        }
      case 'S': {
          $filter = ' field = STOCK';
          break;
        }
      case 'D': {
          $filter = ' field = DETAIL';
          break;
        }
      case 'C': {
          $filter = ' field = CREATE';
          break;
        }
      case 'P': {
          $filter = ' field = POST';
          break;
        }
      case 'U': {
          $filter = ' field = UNPOST';
          break;
        }
      default: {
          $filter = ' 1 ';
          break;
        }
    } //end switch

    switch (strtoupper($doc)) {
      case 'PO':
      case 'PC':
      case 'SO':
      case 'KR':
      case 'PR':
        $table = 'transnum_log';
        $del_table = ' del_transnum_log';
        break;

      case 'CUSTOMER':
      case 'SUPPLIER':
      case 'WAREHOUSE':
      case 'AGENT':
      case 'LOCATION':
      case 'VENDOR':
        $table = 'client_log';
        $del_table = ' del_client_log';
        break;

      case 'STOCKCARD':
        $table = 'item_log';
        $del_table = 'del_item_log';
        break;

      case 'TERMS':
        $table = 'terms_log';
        $del_table = 'del_terms_log';
        break;

      case 'CENTER':
        $table = 'center_log';
        $del_table = 'del_center_log';
        break;

      default:
        $table = 'table_log';
        $del_table = ' del_table_log';
        break;
    } //end switch

    $sql = "select trno, field, oldversion, log.userid, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from $table as `log`
        left join useraccess as u on u.username=`log`.userid
        where trno='$trno' and $filter
        union all
        select trno, concat('DELETE',' ',field), docno, log.userid, 
        dateid, if(pic='','blank_user.png',pic) as pic
        from  $del_table as `log`
        left join useraccess as u on u.username=`log`.userid
        where trno='$trno' and $filter";

    switch (strtoupper($doc)) {

      case 'SP':
      case 'PO':
      case 'PR':
      case 'SO':
      case 'PC':
      case 'KR':
      case 'RR':
      case 'CA':
      case 'DM':
      case 'SJ':
      case 'CM':
      case 'IS':
      case 'AJ':
      case 'TS':
      case 'PV':
      case 'CV':
      case 'CR':
      case 'DS':
      case 'AP':
      case 'AR':
      case 'GJ':
      case 'quotation':
      case 'QT':
        $sql = $sql . "";
        break;

      case 'CUSTOMER':
      case 'SUPPLIER':
      case 'WAREHOUSE':
      case 'AGENT':
      case 'quotation':
      case 'QT':
        $sql = $sql . " union all select s.clientid,'CREATE',concat(s.client,'-',s.clientname),s.createby,
          s.createdate,if(u.pic='','blank_user.png',pic) as pic from client as s 
          left join useraccess as u on u.username=s.createby where s.clientid='$trno'";
        break;
    } //end switich

    $sql = $sql . " order by dateid desc";

    return $this->coreFunctions->opentable($sql);
  } //end fn

  public function openUserlog($date1, $date2, $doc = '', $userid = '')
  {
    $sql = "";
    $module = "";
    $username = "";

    if ($userid == "") {
      $username = "";
    }
    if ($doc != "") {
      $module = " and cntnum.doc ='$doc'";
    }
    if ($userid != "") {
      $username = " and tl.userid ='$userid'";
    }

    switch ($doc) {
      case "ALL":
        $sql = "select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client
            from table_log as tl left join cntnum on cntnum.trno = tl.trno
            where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
            and date_format('$date2','%Y-%m-%d') $username
            union all
            select 'CL' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,client.clientname,client.client
            from client_log as tl left join client on client.clientid = tl.trno
            where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
            and date_format('$date2','%Y-%m-%d')  $username
            union all
            select 'SK' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,item.itemname as clientname,item.barcode as client
            from item_log as tl left join item on item.itemid = tl.trno
            where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
            and date_format('$date2','%Y-%m-%d')  $username
            union all
            select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client
            from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno
            where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
            and date_format('$date2','%Y-%m-%d') $username
            order by dateid";
        break;

      case "PO":
      case "SO":
      case "JO":
      case "PC":
      case "KR":
      case "EX":
      case "JO":
        $sql = "select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid
            from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username order by dateid";
        break;

      case "SJ":
      case "CM":
      case "DM":
      case "RR":
      case "IS":
      case "CV":
      case "CR":
      case "AR":
      case "AP":
      case "TS":
      case "PV":
      case "AJ":
      case "DS":
      case "GJ":
        $sql = "select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid
            from table_log as tl left join cntnum on cntnum.trno = tl.trno where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username order by dateid";
        break;

      case "CL":
        $sql = "select 'CL' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,client.clientname,client.client
            from client_log as tl left join client on client.clientid = tl.trno where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d')  $username order by tl.dateid";
        break;

      case "SK":
        $sql = "select 'SK' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,item.itemname as clientname,item.barcode as client
            from item_log as tl left join item on item.itemid = tl.trno where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d')  $username order by tl.dateid";
        break;

      default:
        $sql = "select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid
            from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username
            union all
            select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid
            from table_log as tl left join cntnum on cntnum.trno = tl.trno where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username order by dateid";
        break;
    } //end switch

    $data = $this->coreFunctions->opentable($sql);

    return array('data' => $data, 'doc' => $doc);
  } //end fn

  public function getlogsuseraccess()
  {
    $sql = "select trno, field, oldversion, log.userid, dateid, if(pic='','blank_user.png',pic) as pic
            from useraccess_log as log
            left join useraccess as u on u.username=log.userid                
            union all
            select trno, concat('DELETE',' ',field), docno, log.userid, dateid, if(pic='','blank_user.png',pic) as pic
            from  del_useraccess_log as log
            left join useraccess as u on u.username=log.userid 
            union all
            select userid,'CREATE',username,createby,createdate,if(pic='','blank_user.png',pic) as pic from useraccess";
    $sql = $sql . " order by dateid desc";

    $data = $this->coreFunctions->opentable($sql);

    if (!empty($data)) {
      return $data;
    } else {
      return false;
    } //end if            
  } //end fn

}//end class logger
