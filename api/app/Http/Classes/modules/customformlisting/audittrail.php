<?php

namespace App\Http\Classes\modules\customformlisting;

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



class audittrail
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AUDIT TRAIL';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $reporter;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 22,
      'edit' => 23,
      'new' => 24,
      'save' => 25,
      'change' => 26,
      'delete' => 27,
      'print' => 28
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {

    $tab = [$this->gridname => ['gridcolumns' => ['userid', 'task', 'oldversion', 'dateid']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;border-style: solid ;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;border-style: solid ;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:250px;border-style: solid ;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:100px;whiteSpace: normal;min-width:120px;border-style: solid ;";

    $obj[0][$this->gridname]['columns'][0]['disable'] = true;
    $obj[0][$this->gridname]['columns'][1]['disable'] = true;
    $obj[0][$this->gridname]['columns'][2]['disable'] = true;
    $obj[0][$this->gridname]['columns'][3]['disable'] = true;

    $obj[0][$this->gridname]['columns'][0]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';

    $obj[0][$this->gridname]['label'] = 'AUDIT TRAIL LOGS';
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
    $fields = ['start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['username', 'moduledesc'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'username.lookupclass', 'getusername');
    data_set($col2, 'moduledesc.lookupclass', 'modulelist');

    $fields = ['refresh', 'print'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'audittrail');
    data_set($col3, 'refresh.style', 'width:100px;whiteSpace: normal;min-width:100px;');
    data_set($col3, 'print.style', 'width:100px;whiteSpace: normal;min-width:100px;');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select 
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as username,
      '' as moduledoc,
      '' as modulename,'' as moduledesc, '' as pdoc");

    if (!empty($data)) {
      return $data[0];
    } else {
      return [];
    }
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];
    switch ($action) {
      case 'audittrail':
        return $this->loaddata($config);
        break;
      case 'print':
        return $this->setupreport($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    }
  }

  public function thisquery($config) //headtablestatus
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', -1);
    $center = $config['params']['center'];
    $user  = $config['params']['dataparams']['username'];
    $date1 = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $date2 = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $doc   = $config['params']['dataparams']['pdoc'];
    $moduledoc   = $config['params']['dataparams']['moduledoc'];
    $modulename   = $config['params']['dataparams']['moduledesc'];

    $module = "";
    $username = "";
    if ($moduledoc != "") {
      $module = " and cntnum.doc ='$doc'";
    }

    if ($user != "") {
      $username = " and tl.userid ='$user'";
    }

    $doc_filter = "and profile.doc='SED'";

    if ($doc == '') {
      $doc = 'ALL';
    }

    switch ($doc) {
      case "ALL":
        return  "select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client,profile.master
        from table_log as tl left join cntnum on cntnum.trno = tl.trno
        left join profile on profile.psection=cntnum.doc
        where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username $doc_filter
        union all
        select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client,profile.master
        from htable_log as tl left join cntnum on cntnum.trno = tl.trno
        left join profile on profile.psection=cntnum.doc
        where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username $doc_filter
        union all
        select 'CL' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,client.clientname,client.client,'' as master
        from client_log as tl left join client on client.clientid = tl.trno
        where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d')  $username
        union all
        select 'SK' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,item.itemname as clientname,item.barcode as client, '' as master
        from item_log as tl left join item on item.itemid = tl.trno
        where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d')  $username
        union all
        select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client, profile.master
        from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username $doc_filter
        union all
        select cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid, '' as clientname, '' as client, profile.master
        from htransnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username $doc_filter
        union all        
        select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
        from del_transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $module $username $doc_filter
        union all
        select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
        from del_table_log as tl left join cntnum as cntnum on cntnum.trno = tl.trno
        left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $module $username $doc_filter
        union all
        select '' as clientname, '' as client, '' as doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,'' as master
        from del_table_log as tl
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
      case "AO":
      case "SQ":
      case "QS":
        return
          "select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') 
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all
         select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from htransnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') 
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all
         select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
         from del_transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all 
        select '' as clientname, '' as client, '' as doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,'' as master
        from del_table_log as tl
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username
         
         order by dateid";
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
      case "JP":
      case "PG":
      case "DR":
        return
          "select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from table_log as tl left join cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') 
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all
         select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from htable_log as tl left join cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') 
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all
         select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
         from del_table_log as tl left join cntnum on cntnum.trno = tl.trno 
         left join profile on profile.psection=cntnum.doc
         where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
         and date_format('$date2','%Y-%m-%d') $module $username 
         union all 
        select '' as clientname, '' as client, '' as doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,'' as master
        from del_table_log as tl
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username
         order by dateid";
        break;
      case "CL":
        return
          "select 'CL' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,client.clientname,client.client, 'Customer' as master
        from client_log as tl left join client on client.clientid = tl.trno where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d')  $username order by tl.dateid";
        break;
      case "SK":
        return
          "select 'SK' as doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,item.itemname as clientname,item.barcode as client, '' as master
        from item_log as tl left join item on item.itemid = tl.trno where  date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d')  $username order by tl.dateid";
        break;
      case 'customer':
      case 'supplier':
      case 'warehouse':
      case 'agent':
        $filter = "";
        $filter1 = "";
        if ($user != '') {
          $filter = "and log.userid = '$user'";
          $filter1 = "and log.createby = '$user'";
        }

        return
          "select '' as clientname, '' as client, trno, field as task, oldversion, log.userid, dateid, if(pic='','blank_user.png',pic) as pic, '' as master
          from client_log as log
          left join useraccess as u on u.username=log.userid
          where date_format(dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $filter
          union all
          select '' as clientname, '' as client,  trno, concat('DELETE',' ',field) as task, docno, log.userid, dateid, if(pic='','blank_user.png',pic) as pic, '' as master
          from  del_client_log as log
          left join useraccess as u on u.username=log.userid
          where date_format(dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $filter
          union all 
          select '' as clientname, '' as client, log.clientid as trno,'CREATE' as task,concat(log.client,'-',log.clientname) as oldversion,log.createby as userid,log.createdate as dateid,if(u.pic='','blank_user.png',pic) as pic , '' as master
          from client as log
          left join useraccess as u on u.username=log.createby
          where date_format(log.createdate,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $filter1 ";
        break;
      case 'stockcard':
        $filter = "";
        if ($user != '') {
          $filter = "and log.userid = '$user'";
        }

        return "select '' as client, '' as clientname, trno, field as task, oldversion, log.userid, dateid, if(pic='','blank_user.png',pic) as pic, '' as master
            from item_log as log
            left join useraccess as u on u.username=log.userid
            where date_format(dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $filter
            union all
            select '' as client, '' as clientname, trno, concat('DELETE',' ',field) as task, docno, log.userid, dateid, if(pic='','blank_user.png',pic) as pic, '' as master
            from  del_item_log as log
            left join useraccess as u on u.username=log.userid
            where date_format(dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $filter";
        break;
      default:
        return
          "select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username $doc_filter
        union all
        select '' as clientname, '' as client, cntnum.doc,tl.field as task,tl.oldversion,tl.userid,tl.dateid,profile.master
        from table_log as tl left join cntnum on cntnum.trno = tl.trno 
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d') and date_format('$date2','%Y-%m-%d') $module $username $doc_filter
        union all
        select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
        from del_transnum_log as tl left join transnum as cntnum on cntnum.trno = tl.trno 
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $module $username $doc_filter
        union all
        select '' as clientname, '' as client, cntnum.doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,profile.master
        from del_table_log as tl left join cntnum on cntnum.trno = tl.trno 
        left join profile on profile.psection=cntnum.doc
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $module $username  $doc_filter 
        union all 
        select '' as clientname, '' as client, '' as doc,CONCAT('DELETE ',tl.field) as task,tl.docno as oldversion,tl.userid,tl.dateid,'' as master
        from del_table_log as tl
        where date_format(tl.dateid,'%Y-%m-%d') between date_format('$date1','%Y-%m-%d')
        and date_format('$date2','%Y-%m-%d') $username
        order by dateid";
        break;
    }
    // $data = $this->coreFunctions->opentable($qry);
    // return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data];
  }


  public function setupreport($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'action' => 'print'];
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'start', 'end', 'username', 'moduledesc', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.type', 'hidden');
    data_set($col1, 'end.type', 'hidden');
    data_set($col1, 'username.type', 'hidden');
    data_set($col1, 'moduledesc.type', 'hidden');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $user  = $config['params']['dataparams']['username'];
    $doc   = $config['params']['dataparams']['pdoc'];
    $modulename   = $config['params']['dataparams']['moduledesc'];

    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '" . $start . "' as start,
        '" . $end . "' as end,
        '" . $user . "' as username,
        '' as moduledoc,
        '' as modulename,'" . $modulename . "' as moduledesc, '" . $doc . "' as pdoc"
    );
  }

  public function reportdata($config)
  {
    $str = $this->reportdefaultlayout($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'directprint' => false, 'action' => 'print'];
  }

  public function loaddata($config)
  {
    $qry = $this->thisquery($config);
    $data = $this->coreFunctions->opentable($qry);
    return [
      'status' => true,
      'msg' => 'Successfully loaded.',
      'action' => 'load',
      'griddata' => ['entrygrid' => $data]
    ];
  }


  public function default_header($config)
  {
    $user  = $config['params']['dataparams']['username'];
    $date1 = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $date2 = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $doc   = $config['params']['dataparams']['pdoc'];

    if ($user == '') {
      $username = 'ALL';
    } else {
      $username = $user;
    }

    if ($doc == '') {
      $docx = 'ALL';
    } else {
      $docx = $doc;
    }

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = 800;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AUDIT TRAIL REPORT', null, null, false, $border, '', 'L', $font, 18, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($date1, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('USER:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($username, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page', '100');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TO:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($date2, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('DOC:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($docx, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Module Name', '100', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Username', '100', null, false, $border, 'BTRL', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Task', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Activity', '200', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BTRL', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportdefaultlayout($config)
  {
    $str = "";
    $data = json_decode(json_encode($this->coreFunctions->opentable($this->thisquery($config))), true);
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 13;
    $page = 14;
    $this->reporter->linecounter = 0;
    $layoutsize = 800;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($config);
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['master'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['userid'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['task'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['oldversion'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
} //end class
