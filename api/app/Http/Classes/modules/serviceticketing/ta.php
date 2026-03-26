<?php

namespace App\Http\Classes\modules\serviceticketing;

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
use App\Http\Classes\modules\seastar\fa;
use App\Http\Classes\SBCPDF;

class ta
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TICKET APPLICATION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $tableinfo = 'cntnuminfo';
  public $htableinfo = 'hcntnuminfo';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'client', 'address', 'dateid', 'terms', 'contra', 'vattype', 'due', 'yourref', 'ourref', 'forex', 'cur', 'wh', 'projectid', 'ista', 'deptid'];
  private $except = ['trno', 'dateid'];
  private $otherfields = ['trno', 'rem2', 'rem3'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $defaultContra = 'AR1';
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'submit', 'label' => 'Submitted', 'color' => 'success']
  ];
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
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4927,
      'edit' => 4928,
      'new' => 4929,
      'save' => 4930,
      'change' => 4931,
      'delete' => 4932,
      'print' => 4933,
      'changeamt' => 4936,
      'unpost' => 4938
    );

    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $listpostedby = 5;
    $listcreateby = 6;
    $listeditby = 7;
    $listviewby = 8;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:120px;';
    $cols[1]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    $cols[$liststatus]['name'] = 'statuscolor';
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $limit = "limit 50";

    $condition = '';
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'client.clientname', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $statuscolor = 'red';
    switch ($itemfilter) {
      case 'draft':
        $status = "'DRAFT'";
        $condition .= ' and head.ista=1 and num.postdate is null and num.statid=0';
        break;
      case 'submit':
        $condition .= " and head.ista=1 and num.statid<>0";
        $status = "stat.status";
        $statuscolor = "green";
        break;
    }

    $deptid = $this->coreFunctions->getfieldvalue('client', 'deptid', 'clientid=?', [$config['params']['adminid']], '', true);
    if ($deptid != 0) {
      $condition .= ' and head.deptid=' . $deptid;
    }

    $qry = "select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid, 
            head.createby,head.editby,head.viewby,num.postedby," . $status . " as status, '" . $statuscolor . "' as statuscolor
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join client on client.client=head.client 
            left join trxstatus as stat on stat.line=num.statid
            where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $condition . $filtersearch . "
            union all
            select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid, 
            head.createby,head.editby,head.viewby,num.postedby," . $status . " as status, '" . $statuscolor . "' as statuscolor
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join client on client.clientid=head.clientid 
            left join trxstatus as stat on stat.line=num.statid
            where head.doc=? and num.center=? and convert(head.dateid,date)>=? and convert(head.dateid,date)<=? " . $condition . $filtersearch . "
            order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'unpost',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    return $return;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'clientname.readonly', true);
    data_set($col1, 'clientname.class', 'cs sbccsreadonly');
    data_set($col1, 'address.readonly', true);
    data_set($col1, 'address.class', 'cs sbccsreadonly');

    // $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dprojectname'];
    $fields = ['dateid'];
    $col2 = $this->fieldClass->create($fields);

    // $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dwhname'];
    $fields = ['rem2'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'rem2.label', 'Item Desctiption');

    // $fields = ['rem2', 'rem3', 'submit'];
    $fields = ['rem3', 'submit'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem3.label', 'Remarks');
    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $customerid = $this->coreFunctions->getfieldvalue('client', 'customerid', 'clientid=?', [$params['adminid']]);

    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['address'] = $this->coreFunctions->getfieldvalue('client', 'addr', 'clientid=?', [$customerid]);

    $data[0]['client'] = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$customerid]);
    $data[0]['clientname'] = $this->coreFunctions->getfieldvalue('client', 'clientname', 'clientid=?', [$customerid]);

    $data[0]['terms'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;

    $data[0]['dacnoname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['dvattype'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);

    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';

    $data[0]['projectid'] = '0';
    $data[0]['deptid'] = $this->coreFunctions->getfieldvalue('client', 'deptid', 'clientid=?', [$params['adminid']]);
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';

    $data[0]['rem2'] = '';
    $data[0]['rem3'] = '';
    $data[0]['ista'] = 1;

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $statid = $this->othersClass->getstatid($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $filterdept = '';
    $deptid = $this->coreFunctions->getfieldvalue('client', 'deptid', 'clientid=?', [$config['params']['adminid']], '', true);
    if ($deptid != 0) {
      $filterdept = ' and head.deptid=' . $deptid;
    }

    $qryselect = "select num.center, client.client, head.trno, head.docno, head.terms, head.cur, head.forex, head.yourref, head.ourref, 
                  head.contra, coa.acnoname, '' as dacnoname, left(head.dateid,10) as dateid, left(head.due,10) as due, head.ista,
                  head.address, client.clientname, head.forex, head.cur, head.tax, head.vattype, '' as dvattype,
                  warehouse.client as wh, warehouse.clientname as whname, '' as dwhname,  
                  head.projectid, ifnull(project.name,'') as projectname, '' as dprojectname, ifnull(project.code,'') as projectcode, 
                  info.rem2, info.rem3, date_format(head.createdate,'%Y-%m-%d') as createdate";

    $qry =        $qryselect . " from $table as head
                  left join $tablenum as num on num.trno = head.trno
                  left join client on client.client = head.client
                  left join coa on coa.acno = head.contra
                  left join client as warehouse on warehouse.client = head.wh
                  left join projectmasterfile as project on project.line=head.projectid 
                  left join cntnuminfo as info on info.trno=head.trno
                  where head.trno = ? and num.center = ? and num.postdate is null " . $filterdept . "
                  union all " .
      $qryselect . " from $htable as head
                  left join $tablenum as num on num.trno = head.trno
                  left join client on client.clientid = head.clientid
                  left join coa on coa.acno = head.contra
                  left join client as warehouse on warehouse.clientid = head.whid
                  left join projectmasterfile as project on project.line=head.projectid 
                  left join hcntnuminfo as info on info.trno=head.trno
                  where head.trno = ? and num.center = ? and num.postdate is not null " . $filterdept . "
                  ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $hideobj = [];
      $hideobj['submit'] = true;
      $breadcrumbs = [];
      switch ($statid) {
        case 0: // draft
          $hideobj['submit'] = false;
          break;
        case 95: // submitted
          $hideobj['submit'] = true;
          array_push($breadcrumbs, ['label' => 'Submitted', 'icon' => 'fact_check']);
          break;
      }

      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'breadcrumbsbottom' => $breadcrumbs];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    $dataother = [];

    if ($isupdate) {
      unset($this->fields[1]);
      unset($this->fields['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }

    if ($isupdate) {
      $statid = $this->othersClass->getstatid($config);
      if ($statid == 95) {
        return ['status' => false, 'msg' => 'Edit not allowed. Document is already submitted.'];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select num.trno as value from " . $this->tablenum . " as num left join " . $this->head . " as head on head.trno=num.trno 
    where num.doc=? and head.lockdate is null order by num.trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);

    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function openstock($trno, $config)
  {
    return [];
  } //end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'submit':
        return $this->submit($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function submit($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 95], ['trno' => $config['params']['trno']])) { //open statid update
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Submitted');
      return ['status' => true, 'msg' => 'Successfully submitted.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for submit ticket'];
    }
  }

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
