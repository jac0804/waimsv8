<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;

class hs
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EMPLOYMENT STATUS CHANGE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sbcscript;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'eschange';
  public $hhead = 'heschange';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = [
    'trno',
    'docno',
    'empid',
    'dateid',
    'jobcode',
    'statcode',
    'description',
    'constart',
    'feffdate',
    'effdate',
    'conend',
    'resigned',
    'remarks',
    'ftype',
    'flevel',
    'fjobcode',
    'fempstatcode',
    'frank',
    'fjobgrade',
    'flocation',
    'fpaymode',
    'fpaygroup',
    'fpayrate',
    'fallowrate',
    'fbasicrate',
    'fcola',
    'ttype',
    'tlevel',
    'tjobcode',
    'tempstatcode',
    'trank',
    'tjobgrade',
    'tlocation',
    'tpaymode',
    'tpaygroup',
    'tpayrate',
    'tallowrate',
    'tbasicrate',
    'isactive',
    'frprojectid',
    'ftrucknameid',
    'fsalarytype',
    'toprojectid',
    'totrucknameid',
    'salarytype',
    'fhsperiod',
    'hsperiod',
    'tcola',
    'chkcopy'
  ];
  private $except = ['trno'];
  private $blnfields = ['isactive', 'chkcopy'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];


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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1169,
      'edit' => 1170,
      'new' => 1171,
      'save' => 1172,
      'change' => 1173,
      'delete' => 1174,
      'print' => 1175,
      'post' => 1176,
      'unpost' => 1177,
      'lock' => 1701,
      'unlock' => 1702
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 53: //camera
        $this->modulename = 'EMPLOYMENT STATUS CHANGE';
        break;
      case 58: //cdo
        $this->modulename = 'PERSONNEL ACTION FORM';
        break;
    }
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[4]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[1]['align'] = 'text-left';

    return $cols;
  }

  // public function sbcscript($config)
  // {
  //   // return $this->sbcscript->hq($config);
  // }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['h.docno', 'c.clientname', 'c.client'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, 'DRAFT' as status
    from " . $this->head . " as h 
    left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and 
    num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, 'POSTED' as status
    from " . $this->hhead . " as h 
    left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and 
    num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    order by docno desc";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    // if ($config['params']['companyid'] == 58) { //cdo
    //   $btns = array('load', 'new', 'save', 'delete', 'cancel', 'print', 'logs', 'edit', 'backlisting', 'toggledown');
    // } else {
    //   $btns = array('load', 'new', 'save', 'delete', 'cancel', 'print', 'post', 'unpost', 'lock', 'unlock', 'logs', 'edit', 'backlisting', 'toggledown');
    // }

    $btns = array('load', 'new', 'save', 'delete', 'cancel', 'print', 'post', 'unpost', 'lock', 'unlock', 'logs', 'edit', 'backlisting', 'toggledown');

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton 

  public function createTab($access, $config)
  {
    $multiallow = $this->companysetup->multiallow($config['params']);
    $tab = [];
    $stockbuttons = [];

    if ($multiallow) {
      $tab = [
        'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrymultiallowance', 'label' => 'ALLOWANCE'],
        'tableentry2' => ['action' => 'payrollentry', 'lookupclass' => 'entrymultiallowanceprevious', 'label' => 'PREVIOUS ALLOWANCE']
      ];
    }
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdo
      $fields = ['docno', 'empcode', 'empname', 'jobtitle', 'dateid'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'empcode.action', 'lookupemployee');
      data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');

      $fields = ['lblrem', 'fsalarytype', 'feffdate', 'fhsperiod', 'fbasicrate', 'fcola'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'feffdate.class', 'csfeffdate sbccsreadonly');
      data_set($col2, 'feffdate.readonly', true);
      data_set($col2, 'lblrem.label', 'PREVIOUS');
      data_set($col2, 'lblrem.style', 'font-weight:bold;font-size:20px');
      data_set($col2, 'fhsperiod.label', 'Class Rate');

      $fields = [['chkcopy', 'lblsource'], 'salarytype', 'effectdate', 'hsperiod', 'tbasicrate', 'tcola'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'effectdate.name', 'effdate');
      data_set($col3, 'effectdate.label', 'Effectivity Date');
      data_set($col3, 'remarks.type', 'ctextarea');
      data_set($col3, 'tbasicrate.type', 'input');
      data_set($col3, 'hsperiod.label', 'Class Rate');
      data_set($col3, 'lblsource.label', 'NEW');
      data_set($col3, 'lblsource.style', 'font-weight:bold;font-size:20px');

      $fields = [];
      $col4 = $this->fieldClass->create($fields);
    } else {
      switch ($companyid) {
        case 3: // conti
          $fields = ['docno', 'empcode', 'empname', 'jobtitle', 'dept', 'dattention', 'dateid', 'statdesc', 'description'];
          break;
        default:
          $fields = ['docno', 'empcode', 'empname', 'jobtitle', 'dept', 'dateid', 'statdesc', 'description'];
          break;
      }
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'empcode.action', 'lookupemployee');

      data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');

      data_set($col1, 'description.label', 'Description');
      data_set($col1, 'description.type', 'ctextarea');
      data_set($col1, 'dept.type', 'input');
      data_set($col1, 'dept.class', 'csdept sbccsreadonly');

      $fields = ['effectdate', 'start', 'end', 'resigned', 'remarks', 'isactive'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'start.name', 'constart');
      data_set($col2, 'start.label', 'Contract Start');

      data_set($col2, 'effectdate.name', 'effdate');
      data_set($col2, 'effectdate.label', 'Effectivity Date of Rate/Allowance');

      data_set($col2, 'end.name', 'conend');
      data_set($col2, 'end.label', 'Contract End');

      data_set($col2, 'isactive.label', 'Active Employee');

      data_set($col2, 'remarks.type', 'ctextarea');

      $fields = [
        'ftype',
        'flevel',
        'fjobname',
        'fempstatname',
        'frank',
        'fjobgrade',
        'flocation',
        'fpaymode',
        'fpaygroupname',
        'fpayrate',
        'fallowrate',
        'fbasicrate'
      ];

      if ($companyid == 43) { //mighty
        array_push($fields, 'frprojectname', 'ftruckname');
      }

      $col3 = $this->fieldClass->create($fields);

      switch ($companyid) {
        case 3: // conti
          data_set($col3, 'flocation.label', 'From Warehouse');
          break;
      }

      data_set($col3, 'fpayrate.label', 'Class Rate');

      $fields = [
        'ttype',
        'tlevel',
        'tjobname',
        'tempstatname',
        'trank',
        'tjobgrade',
        'tlocation',
        'tpaymode',
        'tpaygroupname',
        'tpayrate',
        'tallowrate',
        'tbasicrate'
      ];

      if ($companyid == 43) { //mighty
        array_push($fields, 'toprojectname', 'totruckname');
      }
      $col4 = $this->fieldClass->create($fields);
      data_set($col4, 'ttype.label', 'To Type');
      data_set($col4, 'ttype.action', 'lookupatype');
      data_set($col4, 'ttype.lookupclass', 'ttypelookup');
      data_set($col4, 'tjobgrade.type', 'input');
      data_set($col4, 'tpayrate.label', 'To Class Rate');

      switch ($companyid) {
        case 3: // conti
          data_set($col4, 'tlocation.label', 'To Warehouse');
          data_set($col4, 'tlocation.type', 'lookup');
          data_set($col4, 'tlocation.class', 'cstlocation sbccsreadonly');
          break;
        default:
          data_set($col4, 'tlocation.type', 'input');
          break;
      }
      data_set($col4, 'tbasicrate.type', 'input');
      data_set($col4, 'tallowrate.type', 'input');
    }




    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    return $this->resetdata($docno);
  }

  public function resetdata($docno = '')
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['empid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['jobtitle'] = '';
    $data[0]['jobcode'] = '';
    $data[0]['statdesc'] = '';
    $data[0]['statcode'] = '';
    $data[0]['description'] = '';
    $data[0]['effdate'] = $this->othersClass->getCurrentDate();
    $data[0]['feffdate'] = null;
    $data[0]['constart'] = '';
    $data[0]['conend'] = '';
    $data[0]['resigned'] = '';
    $data[0]['remarks'] = '';

    $data[0]['ftype'] = '';
    $data[0]['flevel'] = '';
    $data[0]['fjobcode'] = '';
    $data[0]['fjobname'] = '';
    $data[0]['fempstatcode'] = '';
    $data[0]['fempstatname'] = '';
    $data[0]['fjobgrade'] = '';
    $data[0]['frank'] = '';
    $data[0]['fjobgrade'] = '';
    $data[0]['fdeptcode'] = '';
    $data[0]['fdeptname'] = '';
    $data[0]['flocation'] = '';
    $data[0]['fpaymode'] = '';
    $data[0]['fpaygroup'] = 0;
    $data[0]['fpaygroupname'] = '';
    $data[0]['fpayrate'] = '';
    $data[0]['fallowrate'] = '0.00';
    $data[0]['fbasicrate'] = '0.00';

    $data[0]['ttype'] = '';
    $data[0]['tlevel'] = '';
    $data[0]['tjobcode'] = '';
    $data[0]['tjobname'] = '';
    $data[0]['tempstatcode'] = '';
    $data[0]['tempstatname'] = '';
    $data[0]['trank'] = '';
    $data[0]['tjobgrade'] = '';
    $data[0]['tlocation'] = '';
    $data[0]['tpaymode'] = '';
    $data[0]['tpaygroup'] = 0;
    $data[0]['tpaygroupname'] = '';
    $data[0]['tpayrate'] = '';
    $data[0]['tallowrate'] = '0.00';
    $data[0]['tbasicrate'] = '0.00';
    $data[0]['isactive'] = '1';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();

    $data[0]['constart'] = $this->othersClass->getCurrentDate();
    $data[0]['conend'] = $this->othersClass->getCurrentDate();
    $data[0]['resigned'] = $this->othersClass->getCurrentDate();

    $data[0]['attentionid'] = 0;
    $data[0]['attention_code'] = '';
    $data[0]['attention_name'] = '';
    $data[0]['dattention'] = '';

    //mighty
    $data[0]['frprojectname'] = '';
    $data[0]['frprojectid'] = 0;
    $data[0]['ftruckname'] = '';
    $data[0]['ftrucknameid'] = 0;

    $data[0]['toprojectname'] = '';
    $data[0]['toprojectid'] = 0;

    $data[0]['totrucknameid'] = 0;
    $data[0]['totruckname'] = '';

    $data[0]['salaryrate'] = '';

    $data[0]['fhsperiod'] = '';
    $data[0]['hsperiod'] = '';

    $data[0]['fsalarytype'] = '';
    $data[0]['salarytype'] = '';

    $data[0]['fcola'] = 0;
    $data[0]['tcola'] = 0;
    $data[0]['chkcopy'] = "0";

    return $data;
  }

  public function sbcscript($config)
  {
    if ($config['params']['companyid'] == 58) { //cdo
      return $this->sbcscript->hs($config);
    } else {
      return true;
    }
  }

  public function loadheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];

    if ($trno == 0) {
      $trno = $this->getlastclient();
    }

    $config['params']['trno'] = $trno;

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;


    // if ($companyid == 58) { //cdo
    //   $qry = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
    //                 concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,c.client as empcode,
    //                 jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,date(head.feffdate) as feffdate,date(head.effdate) as effdate,
    //                 head.fbasicrate, head.tbasicrate,fempstat.empstatus as empstat,head.isactive,head.fsalarytype,head.salarytype,head.fhsperiod,head.hsperiod,head.fcola,head.tcola,head.chkcopy
    //           from eschange as head
    //           left join employee as emp on emp.empid=head.empid
    //           left join client as c on c.clientid=emp.empid
    //           left join hrisnum as num on num.trno = head.trno
    //           left join jobthead as jt on jt.line = emp.jobid
    //           left join empstatentry as fempstat on fempstat.line = head.fempstatcode
    //           where num.trno = ? and num.doc='" . $doc . "' and num.center=?";
    //   $head = $this->coreFunctions->opentable($qry, [$trno, $center]);
    // } else {
    switch ($companyid) {
      case 3: // conti
        $addedfields = ", 
        ifnull(attention.clientid, 0) as attentionid, 
        ifnull(attention.client, '') as attention_code, 
        ifnull(attention.clientname, '') as attention_name
        ";

        $addedleftjoin = " 
        left join client as attention on attention.clientid = head.attentionid
        ";
        break;
      case 43: //mighty
        $addedfields = " ,ifnull(frpro.name,'') as frprojectname,ifnull(head.frprojectid,0) as frprojectid,
                          ifnull(frtruck.barcode,'') as  ftruckname, ifnull(head.ftrucknameid,0) as ftrucknameid,
                          ifnull(topro.name,'') as toprojectname, ifnull(head.toprojectid, 0) as toprojectid,
                          ifnull(totruck.barcode,'') as totruckname, ifnull(head.totrucknameid, 0) as totrucknameid";

        $addedleftjoin = "left join projectmasterfile as frpro on frpro.line=head.frprojectid
                          left join item as frtruck on frtruck.itemid=head.ftrucknameid
                          left join projectmasterfile as topro on topro.line=head.toprojectid
                          left join item as totruck on totruck.itemid=head.totrucknameid ";
        break;
      default:
        $addedfields = ", 0 as attentionid, '' as attention_code,'' as attention_name,
                        '' as frprojectname,0 as frprojectid,'' as ftruckname,0 as ftrucknameid,
                        '' as toprojectname, 0 as toprojectid, '' as totruckname, 0 as totrucknameid
                        ";
        $addedleftjoin = "";
        break;
    }


    $qryselect = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
                        concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,c.client as empcode,
                        jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,stat.code as statcode, 
                        stat.stat as statdesc,head.description, date(head.feffdate) as feffdate, date(head.effdate) as effdate, 
                        date(head.constart) as constart, date(head.conend) as conend,date(head.resigned) as resigned, 
                        head.remarks,head.ftype, head.flevel, head.fjobcode,fjobtitle.jobtitle as fjobname, 
                        head.fempstatcode,head.frank, head.fjobgrade, head.flocation,
                        head.fpaygroup, head.fallowrate, head.fbasicrate,head.ttype, head.tlevel, head.tjobcode, 
                        tjobtitle.jobtitle as tjobname,head.tempstatcode, head.trank, head.tjobgrade,
                        head.tlocation,head.tpaymode,head.tpayrate,head.tallowrate,head.tbasicrate, 
                        head.isactive,fempstat.empstatus as fempstatname,tempstat.empstatus as tempstatname,head.fcola,head.tcola,
                        head.fsalarytype,head.salarytype,head.fhsperiod,head.hsperiod,head.fcola,head.tcola,head.chkcopy,
                        case when head.fpaymode = 'S' then 'Semi-monthly' 
                             when head.fpaymode = 'W' then 'Weekly' 
                             when head.fpaymode = 'M' then 'Monthly' 
                             when head.fpaymode = 'D' then 'Daily' 
                             when head.fpaymode = 'P' then 'Piece Rate' 
                        else '' end as fpaymode,
                        head.fpaygroup,fpgroup.paygroup as fpaygroupname, head.tpaygroup,tpgroup.paygroup as tpaygroupname,
                        case when head.fpayrate = 'D' then 'Daily' 
                             when head.fpayrate = 'M' then 'Monthly' 
                        else '' end as fpayrate " . $addedfields . "";

    $qry = $qryselect . " from " . $table . " as head
            left join employee as emp on emp.empid=head.empid
            left join client as c on c.clientid=emp.empid
            left join app as ap on ap.empid=emp.aplid
            left join statchange as stat on head.statcode=stat.code
            left join $tablenum as num on num.trno = head.trno
            left join jobthead as jt on jt.line = emp.jobid
            left join empstatentry as fempstat on fempstat.line = head.fempstatcode
            left join jobthead as fjobtitle on fjobtitle.docno = head.jobcode
            left join jobthead as tjobtitle on tjobtitle.docno = head.tjobcode
            left join client as tdept on tdept.client = head.tdeptcode
            left join empstatentry as tempstat on tempstat.code = head.tempstatcode
            left join paygroup as fpgroup on fpgroup.line = head.fpaygroup
            left join paygroup as tpgroup on tpgroup.line = head.tpaygroup
            " . $addedleftjoin . "
            where num.trno = ? and num.doc='" . $doc . "' and num.center=? 
            union all
            " . $qryselect . " from " . $htable . " as head
            left join employee as emp on emp.empid=head.empid
            left join client as c on c.clientid=emp.empid
            left join app as ap on ap.empid=emp.aplid
            left join statchange as stat on head.statcode=stat.code
            left join $tablenum as num on num.trno = head.trno
            left join jobthead as jt on jt.line = emp.jobid
            left join empstatentry as fempstat on fempstat.line = head.fempstatcode
            left join jobthead as fjobtitle on fjobtitle.docno = head.jobcode
            left join jobthead as tjobtitle on tjobtitle.docno = head.tjobcode
            left join empstatentry as tempstat on tempstat.code = head.tempstatcode
            left join paygroup as fpgroup on fpgroup.line = head.fpaygroup
            left join paygroup as tpgroup on tpgroup.line = head.tpaygroup
            " . $addedleftjoin . "
            where num.trno = ? and num.doc='" . $doc . "' and num.center=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    // }



    if (!empty($head)) {
      $stock = [];
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }

      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("
        select trno as value 
        from " . $this->head . " 
        union all
        select trno as value 
        from " . $this->hhead . " 
        order by value DESC LIMIT 1");

    return $last_id;
  }

  public function updatehead($config, $isupdate)
  {
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }

    if ($companyid == 58) { //cdo
      if (isset($head['feffdate'])) {
        if (empty($head['feffdate'])) {
          goto getlasteffdate;
        }
      } else {
        getlasteffdate:
        $head['feffdate'] = $this->coreFunctions->datareader("select dateeffect as value from ratesetup where empid=" . $head['empid'] . " order by dateeffect desc");
      }
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    switch ($companyid) {
      case 3: //conti
        $data['attentionid'] = $config['params']['head']['attentionid'];
        break;
      default:
        $data['attentionid'] = 0;
        break;
    }

    if ($companyid != 58) { //not cdo
      $data['fpayrate'] = substr($head['fpayrate'], 0, 1);
      $data['fpaymode'] = substr($head['fpaymode'], 0, 1);
    }

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
    }
  } // end function  

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from allowsetup where refx=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into " . $this->hhead . " (trno, docno, empid, dateid, jobcode, statcode,
                  description, feffdate, effdate, constart, conend, resigned,remarks,ftype,flevel,
                  fjobcode,fempstatcode,frank,fjobgrade,flocation,fpaymode,fpaygroup,
                  fpayrate,fallowrate,fbasicrate,ttype,tlevel,tjobcode, tempstatcode, trank, tjobgrade,
                  tlocation,tpaymode,tpaygroup,tpayrate,tallowrate,tbasicrate,isactive,createby, 
                  createdate, editby, editdate,lockdate, lockuser, viewdate,viewby, doc, attentionid, 
                  toprojectid,totrucknameid,ftrucknameid,frprojectid,fcola,tcola,fsalarytype,salarytype,fhsperiod,hsperiod,chkcopy)
            select trno, docno, empid, dateid, jobcode, statcode,
                  description, feffdate, effdate, constart,conend, resigned,remarks,ftype,flevel,
                  fjobcode,fempstatcode,frank,fjobgrade,
                  flocation,fpaymode,fpaygroup,fpayrate,fallowrate,fbasicrate,ttype,tlevel,tjobcode, 
                  tempstatcode, trank, tjobgrade,tlocation,tpaymode,tpaygroup,tpayrate,tallowrate,
                  tbasicrate,isactive,createby, createdate, editby, editdate,lockdate, lockuser, viewdate,
                  viewby, doc, attentionid,toprojectid,totrucknameid,ftrucknameid,frprojectid,fcola,tcola,fsalarytype,salarytype,fhsperiod,hsperiod,chkcopy
            from " . $this->head . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);

      $qry = "select client.client as empcode, head.* 
        from " . $config['docmodule']->hhead . " as head left join employee as emp on emp.empid=head.empid left join client on client.clientid=emp.empid where doc=? and trno =?";

      $qryResult = $this->coreFunctions->opentable($qry, [$doc, $trno]);

      if (!empty($qryResult)) {

        $updaterow = [];

        $empid   = $qryResult[0]->empid;
        $empcode = $qryResult[0]->empcode;

        $docno        = $qryResult[0]->docno;
        $description  = $qryResult[0]->description;
        $ttype        = $qryResult[0]->ttype;
        $tlevel       = $qryResult[0]->tlevel;
        $tjobcode     = $qryResult[0]->tjobcode;
        $tempstatcode = $qryResult[0]->tempstatcode;
        $trank        = $qryResult[0]->trank;
        $tjobgrade    = $qryResult[0]->tjobgrade;
        $tlocation    = $qryResult[0]->tlocation;
        $tpaymode     = $qryResult[0]->tpaymode;
        $tpaygroup    = $qryResult[0]->tpaygroup;
        $salarytype    = $qryResult[0]->salarytype;
        $tpayrate     = $qryResult[0]->tpayrate;
        $resigned     = $qryResult[0]->resigned;
        $tbasicrate   = $qryResult[0]->tbasicrate;
        $classrate   = $qryResult[0]->hsperiod;
        $tcola   = $qryResult[0]->tcola;
        $tallowrate   = $qryResult[0]->tallowrate;
        $isactive     = $qryResult[0]->isactive;
        $dateid       = date('Y-m-d', strtotime($qryResult[0]->dateid));
        $effdate      = date('Y-m-d', strtotime($qryResult[0]->effdate));
        $constart     = date('Y-m-d', strtotime($qryResult[0]->constart));
        $conend       = date('Y-m-d', strtotime($qryResult[0]->conend));
        $resigned     = date('Y-m-d', strtotime($qryResult[0]->resigned));
        $empstart     = date('Y-m-d', strtotime($qryResult[0]->empstart));
        $toprojectid    = $qryResult[0]->toprojectid;
        $totrucknameid    = $qryResult[0]->totrucknameid;


        if ($toprojectid != "") {
          $updaterow['projectid'] = $toprojectid;
        }

        if ($totrucknameid != "") {
          $updaterow['itemid'] = $totrucknameid;
        }

        if ($ttype != "") {
          $updaterow['emptype'] = $ttype;
        }

        if ($tlevel != "") {
          $updaterow['level'] = $tlevel;
        }
        if ($salarytype != "") {
          $updaterow['salarytype'] = $salarytype;
        }
        if ($effdate != '') {
          $updaterow['effectdate'] = $effdate;
        }
        //effectdate
        if ($tjobcode != "") {
          $jobid = $docno = $this->coreFunctions->datareader('select line as value from jobthead where docno = ?', [$tjobcode]);
          $updaterow['jobid'] = $jobid;
          $updaterow['jobdate'] = $this->othersClass->getCurrentTimeStamp();
        }
        if ($tempstatcode != "") {
          $empstatid = $docno = $this->coreFunctions->datareader('select line as value from empstatentry where code = ?', [$tempstatcode]);
          $updaterow['empstatus'] = $empstatid;
          $updaterow['empstatdate'] = $this->othersClass->getCurrentTimeStamp();
        }
        if ($trank != "") {

          $updaterow['emprank'] = $trank;
        }
        if ($tjobgrade != "") {
          $updaterow['jgrade'] = $tjobgrade;
        }
        if ($tcola != "") {
          $updaterow['cola'] = $tcola;
        }

        $type = '';
        if ($companyid == 58) { //cdo
          if ($classrate != "") {
            switch (strtoupper($classrate)) {
              case "DAILY":
              case "DAILY RATE":
                $type = 'D';
                break;
              case "MONTHLY":
                $type = 'M';
                break;
              case "HOURLY":
                $type = 'H';
                break;
              case "PACKAGE RATE":
                $type = 'P';
                break;
            }

            $updaterow['classrate'] =  $type;
          }
        } else {
          $type = $qryResult[0]->fpayrate;
          if ($tpayrate != "") {
            if (strtoupper($tpayrate) == "MONTHLY") {
              $type = "M";
            } else {
              $type = "D";
            }
            $updaterow['classrate'] =  $type;
          }
        }

        if ($tlocation != "") {
          $updaterow['emploc'] =  $tlocation;
        }
        if ($tpaymode != "") {
          switch ($tpaymode) {
            case 'Weekly':
              $tpaymode = "W";
              break;
            case 'Semi-Monthly':
              $tpaymode = "S";
              break;
            case 'Monthly':
              $tpaymode = "M";
              break;
            case 'Daily':
              $tpaymode = "D";
              break;
            case 'Pierce Rate':
              $tpaymode = "P";
              break;
          }
          $updaterow['paymode'] =  $tpaymode;
        }
        if ($tpaygroup != 0) {
          $updaterow['paygroup'] =  $tpaygroup;
        }

        //not used in createheadfields
        // if ($empstart != "") {
        //   $updaterow['hired'] =  $empstart;
        // }

        if ($resigned != "") {
          $updaterow['resigned'] = $resigned;
        }
        if ($isactive == 1) {
          $updaterow['isactive'] = $isactive;
          $updaterow['resigned'] = null;
        } else {
          $resigned = $this->othersClass->getCurrentTimeStamp();
          $updaterow['isactive'] = $isactive;
          $updaterow['resigned'] = $resigned;
        }

        if (!empty($updaterow)) {
          if ($this->coreFunctions->sbcupdate("employee", $updaterow, ['empid' => $empid])) {
            if ($tbasicrate != 0) {
              $sql = "update ratesetup  set dateend='" . $effdate . "' where empid='" . $empid . "' and date(dateend)='9999-12-31'";
              $this->coreFunctions->execqry($sql, "update");

              $sql = "insert into ratesetup (dateid,dateeffect, dateend, empid, remarks, basicrate, type, createby, createdate, hstrno) 
                  values ('" . $dateid . "', '" . $effdate . "', '9999-12-31', " . $empid . ", '" . $docno . "', '" . $tbasicrate . "', '" . $type . "','" . $user . "','" . $this->othersClass->getCurrentTimeStamp() . "'," . $trno . ")";
              $this->coreFunctions->execqry($sql, "insert");
            }
            if ($tallowrate != "") {
              if (is_numeric($tallowrate)) {
                if ($tallowrate > 0) {
                  $sql = "update allowsetup set dateend='" . $effdate . "' where empid='" . $empid . "' and date(dateend)='9999-12-31'";
                  $this->coreFunctions->execqry($sql, "update");

                  $allowacnoid = $this->coreFunctions->getfieldvalue("paaccount", "acnoid", "code=?", ['PT31'], '', true);

                  $sql = "insert into allowsetup (dateid,dateeffect, dateend, empid, remarks, basicrate, allowance, type, acno, hstrno, acnoid) 
                  values ('" . $dateid . "', '" . $effdate . "', '9999-12-31', " . $empid . ", '" . $docno . "', '" . $tbasicrate . "', '" . $tallowrate . "', '" . $type . "', 'PT31'," . $trno . "," . $allowacnoid . ")";
                  $this->coreFunctions->execqry($sql, "insert");
                }
              }
            }
            if ($constart != "" && $conend != "") {
              $sql = "insert into contracts (empid,contractn,descr,datefrom,dateto) values ('" . $empid . "', '" . $docno . "', '" . $description . "', '" . $constart . "', '" . $conend . "')";
              $this->coreFunctions->execqry($sql, "insert");
            }

            //checking tempallowance table
            $allowancetemp = $this->coreFunctions->opentable("select dateid, dateeffect, dateend, empid, remarks, basicrate, type, acnoid, allowance, refx, isliquidation from allowsetuptemp where refx=" . $trno);
            foreach ($allowancetemp as $key => $valAll) {
              $sql = "update allowsetup set dateend='" . $valAll->dateeffect . "',voiddate='" . $this->othersClass->getCurrentTimeStamp() . "',voidby='" . $user . "' where empid=" . $valAll->empid . " and acnoid=" . $valAll->acnoid . " and date(dateend)='9999-12-31'";
              if ($this->coreFunctions->execqry($sql)) {
                $sql = "insert into allowsetup (dateid, dateeffect, dateend, empid, remarks, basicrate, type, acnoid, allowance, refx, isliquidation) 
                  select dateid, dateeffect, dateend, empid, remarks, basicrate, type, acnoid, allowance, refx, isliquidation from allowsetuptemp where empid=" . $valAll->empid . " and acnoid=" . $valAll->acnoid . " and refx=" . $valAll->refx;
                $this->coreFunctions->execqry($sql);
              }
            }
          } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Failed to update employee records'];
          }
        }
      }

      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);

      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);

      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $msg = '';
    $msg = "Unposting is not available.";
    return ['status' => false, 'msg' => $msg];
  } //end function


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
    $dataparams = $config['params']['dataparams'];
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdo
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
}
