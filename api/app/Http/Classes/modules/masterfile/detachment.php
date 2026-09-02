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

class detachment
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETACHMENT LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'division';

  public $divinfo = 'divinfo';
  public $prefix = 'DH';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $stockselect;

  private $fields=['divcode','divname','address','group','remarks','attention','tel','faxno'];
  private $divinfofields = ['colltype', 'isexcessbasic', 'isnodeductbank', 'isnodeductemp', 'isbasic8', 'isbasic4', 'isndiff', 'isworkingrd', 'isexcessduty', 'isregot', 'isworkingrd', 'isholiday',
                            'noguards','yrdays','wkdays', 'dutyhrs',  'mons',  'days', 'hrs', 'excesshrs',
                            'dailywage', 'salary', 'excessduty', 'cola', 'otamt', 'amt13th', 'incentive', 'uniformamt',
                            'retireamt', 'sssamt', 'phicamt', 'hdmfamt', 'eccamt', 'agencyfee', 'agencyvat'];
  private $except = ['divid'];
  private $blnfields = ['isexcessbasic', 'isnodeductbank', 'isnodeductemp', 'isbasic8', 'isbasic4', 'isndiff', 'isworkingrd', 'isexcessduty', 'isregot', 'isworkingrd', 'isholiday'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
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
      'view' => 5961,
      'edit' => 5962,
      'new' => 5963,
      'save' => 5964,
      'change' => 5965,
      'delete' => 5966,
      'print' => 5967,
      'load' => 5960
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$listclient]['label'] = 'Code';
    $cols[$listclientname]['label'] = 'Name';
    $cols[$listaddr]['label'] = 'Address';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = $config['params']['date1'];
    $date2 = $config['params']['date2'];
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $company = $config['params']['companyid'];
    $search = $config['params']['search'];

    $limit = "limit " . $this->companysetup->getmasterlimit($config['params']);
    $condition = "";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['dv.divcode', 'dv.divname', 'dv.address'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select dv.divid,dv.divcode as client,dv.divname as clientname,dv.address as addr from division as dv where left(divcode,2)='DH' " . $condition . " " . $filtersearch . "
     order by divid " . $limit;

    $data = $this->coreFunctions->opentable($qry);
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
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    if ($this->companysetup->getclientlength($config['params']) != 0) {
      array_push($btns, 'others');
    }

    $buttons = $this->btnClass->create($btns);

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($config['params']['doc']) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $fields = [['lbltotalkg', 'num'],['lblshipping', 'year'],['lblbilling', 'numdays'],['lblacquisition', 'hours'],
               ['lbldepreciation', 'monthsno'],['lbllocation','nodays'],['lblvehicleinfo', 'othrs'],['lblrem', 'workloc']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lbltotalkg.label', 'No. of Guards');
    data_set($col1, 'lbltotalkg.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'num.name', 'noguards');
    data_set($col1, 'num.label', '');

    data_set($col1, 'lblshipping.label', 'Days in a Year');
    data_set($col1, 'lblshipping.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'year.name', 'yrdays');
    data_set($col1, 'year.label', '');

    data_set($col1, 'lblbilling.label', 'Days in a Week');
    data_set($col1, 'lblbilling.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'numdays.name', 'wkdays');
    data_set($col1, 'numdays.label', '');
    data_set($col1, 'numdays.class', 'csnumdays');


    data_set($col1, 'lblacquisition.label', 'Duty Hours');
    data_set($col1, 'lblacquisition.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'hours.name', 'dutyhrs');
    data_set($col1, 'hours.label', '');

    data_set($col1, 'lbldepreciation.label', 'No. of Months');
    data_set($col1, 'lbldepreciation.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'monthsno.name', 'mons');
    data_set($col1, 'monthsno.label', '');


    data_set($col1, 'lbllocation.label', 'No. of Days');
    data_set($col1, 'lbllocation.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'nodays.name', 'days');
    data_set($col1, 'nodays.label', '');
    data_set($col1, 'nodays.readonly', false);


    data_set($col1, 'lblvehicleinfo.label', 'No. of Hours');
    data_set($col1, 'lblvehicleinfo.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'othrs.name', 'hrs');
    data_set($col1, 'othrs.label', '');

    data_set($col1, 'lblrem.label', 'Excess Duty');
    data_set($col1, 'lblrem.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col1, 'workloc.name', 'excesshrs');
    data_set($col1, 'workloc.label', '');

    $fields = ['lblsource',['lbldestination', 'basicrate'],['lblpassbook', 'tbasicrate'],['lblreconcile', 'othrsextra'],
              ['lblearned', 'cola'],['lblcleared', 'apothrs'],['lblrecondate', 'paymentname'],['lblendingbal', 'opincentive'],
              ['lblunclear', 'tallowrate']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'lblsource.label', 'AMOUNT DIRECTLY TO GUARDS');
    data_set($col2, 'lblsource.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');

    data_set($col2, 'lbldestination.label', 'Daily Wage');
    data_set($col2, 'lbldestination.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'basicrate.name', 'dailywage');
    data_set($col2, 'basicrate.label', '');
    data_set($col2, 'basicrate.class', 'csbasicrate');

    data_set($col2, 'lblpassbook.label', 'Basic Salary');
    data_set($col2, 'lblpassbook.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'tbasicrate.name', 'salary');
    data_set($col2, 'tbasicrate.label', '');

    data_set($col2, 'lblreconcile.label', 'Excess Hours Duty');
    data_set($col2, 'lblreconcile.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'othrsextra.name', 'excessduty');
    data_set($col2, 'othrsextra.label', '');
    data_set($col2, 'othrsextra.readonly', false);

    data_set($col2, 'lblearned.label', 'Night Pay / Cola');
    data_set($col2, 'lblearned.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'cola.label', '');
    data_set($col2, 'cola.readonly', false);

    data_set($col2, 'lblcleared.label', 'Overtime Pay');
    data_set($col2, 'lblcleared.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'apothrs.name', 'otamt');
    data_set($col2, 'apothrs.label', '');
    data_set($col2, 'apothrs.class', 'csapothrs');


    data_set($col2, 'lblrecondate.label', '13th Month Pay');
    data_set($col2, 'lblrecondate.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'paymentname.type', 'input');
    data_set($col2, 'paymentname.name', 'amt13th');
    data_set($col2, 'paymentname.class', 'cspaymentname');
    data_set($col2, 'paymentname.label', '');


    data_set($col2, 'lblendingbal.label', '5 Days Incentive');
    data_set($col2, 'lblendingbal.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'opincentive.name', 'incentive');
    data_set($col2, 'opincentive.label', '');

    data_set($col2, 'lblunclear.label', 'Uniform Allowance');
    data_set($col2, 'lblunclear.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col2, 'tallowrate.name', 'uniformamt');
    data_set($col2, 'tallowrate.label', '');

    $fields = ['lblbranch',['lbldateid', 'leadfrom'],['lblreceived', 'sss'],['lblattached', 'phic'],['lblinvreq', 'hdmf'],
              ['lblforapproval', 'escalation'],['lblapproved', 'agentcno'],['lbllocked', 'taxdef'],['lblitemdesc', 'fcontractprice']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'lblbranch.label', 'AMOUNT TO GOV\'T IN FAVOR OF GUARD');
    data_set($col3, 'lblbranch.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');

    data_set($col3, 'lbldateid.label', 'Retirement');
    data_set($col3, 'lbldateid.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'leadfrom.name', 'retireamt');
    data_set($col3, 'leadfrom.label', '');

    data_set($col3, 'lblreceived.label', 'SSS Cont.');
    data_set($col3, 'lblreceived.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'sss.name', 'sssamt');
    data_set($col3, 'sss.label', '');
    data_set($col3, 'sss.readonly', false);

    data_set($col3, 'lblattached.label', 'PHIC Cont.');
    data_set($col3, 'lblattached.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'phic.name', 'phicamt');
    data_set($col3, 'phic.label', '');
    data_set($col3, 'phic.readonly', false);

    data_set($col3, 'lblinvreq.label', 'HDMF Cont.');
    data_set($col3, 'lblinvreq.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'hdmf.name', 'hdmfamt');
    data_set($col3, 'hdmf.label', '');
    data_set($col3, 'hdmf.readonly', false);

    data_set($col3, 'lblforapproval.label', 'ECC Cont.');
    data_set($col3, 'lblforapproval.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'escalation.name', 'eccamt');
    data_set($col3, 'escalation.label', '');

    data_set($col3, 'lblapproved.label', 'AGENCY FEE');
    data_set($col3, 'lblapproved.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'agentcno.name', 'agencyfee');
    data_set($col3, 'agentcno.label', '');
    data_set($col3, 'agentcno.readonly', false);

    data_set($col3, 'lbllocked.label', '12% VAT');
    data_set($col3, 'lbllocked.style', 'font-weight:bold; font-size:15px; position:relative; top:8px;');
    data_set($col3, 'taxdef.name', 'agencyvat');
    data_set($col3, 'taxdef.label', '');
    data_set($col3, 'taxdef.readonly', true);

    data_set($col3, 'lblitemdesc.label', 'TOTAL CONTRACT');
    data_set($col3, 'lblitemdesc.style', 'font-weight:bold; font-size:13px; position:relative; top:8px;');
    data_set($col3, 'fcontractprice.label', '');


    $fields = ['updatenotes'];
    $col4 = $this->fieldClass->create($fields);

    data_set($col4, 'updatenotes.label', 'CONTRACT');
    data_set($col4, 'updatenotes.lookupclass', 'detachmentcontract');

    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1,'col2' => $col2, 'col3' => $col3, 'col4'=>$col4], 'label' => 'CONTRACT RATE']
      // 'multiinput2' => ['inputcolumn' => ['col2' => $col2], 'label' => ''],
      // 'multiinput3' => ['inputcolumn' => ['col4' => $col4, 'col5' => $col5, 'col6' => $col6, 'col7' => $col7], 'label' => '']
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($access, $config)
  {
    return []; 
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    //divsion
    $fields = ['client', 'divname','address','subgroup', 'attention'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'address.maxlength', '80');
    data_set($col1, 'subgroup.label', 'Group');
    data_set($col1, 'subgroup.type', 'input');
    data_set($col1, 'subgroup.readonly', false);
    data_set($col1, 'subgroup.name', 'group');
    data_set($col1, 'client.lookupclass', 'lookupledger_division');
    data_set($col1, 'client.action', 'lookupledger');
    data_set($col1, 'client.label', 'Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'divname.class', 'csdivname');
    data_set($col1, 'divname.label', 'Description');
    data_set($col1, 'attention.label', 'Contact');

  
    $fields = ['tel','faxno','colltype', 'lblgrossprofit', 'istenant', 'iscustomer', 'issupplier'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'faxno.maxlength', '45');
    data_set($col2, 'tel.maxlength', '20');
    data_set($col2, 'tel.label', 'Tel No');
    data_set($col2, 'faxno.label', 'Fax No');

    data_set($col2, 'lblgrossprofit.label', 'Other Setup');
    data_set($col2, 'lblgrossprofit.style', 'font-weight:bold; font-size:15px;');
    data_set($col2, 'istenant.name', 'isexcessbasic');
    data_set($col2, 'istenant.label', 'Include Excess and Basic 4 hrs in COLA');
    data_set($col2, 'iscustomer.name', 'isnodeductbank');
    data_set($col2, 'iscustomer.label', 'No Deduction of BANK CHARGES');
    data_set($col2, 'issupplier.name', 'isnodeductemp');
    data_set($col2, 'issupplier.label', 'No Deduction of EMP');

  

    $fields = ['lblcostuom', ['isagent', 'iswarehouse'], ['istrucking', 'isassetwh'],['isfa', 'isemployee'], 'isstudent'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'lblcostuom.label', 'Basic Amount for SSS Contribution');
    data_set($col3, 'lblcostuom.style', 'font-weight:bold; font-size:15px;');
    data_set($col3, 'isagent.name', 'isbasic8');
    data_set($col3, 'isagent.label', 'Basic 8 hrs');

    data_set($col3, 'iswarehouse.name', 'isndiff');
    data_set($col3, 'iswarehouse.label', 'Night Diff');


    data_set($col3, 'istrucking.name', 'isbasic4');
    data_set($col3, 'istrucking.label', 'Basic 4 hrs');

    data_set($col3, 'isassetwh.name', 'isworkingrd');
    data_set($col3, 'isassetwh.label', 'Working Day Off');

    data_set($col3, 'isfa.name', 'isexcessduty');
    data_set($col3, 'isfa.label', 'Excess Duty');

    data_set($col3, 'isemployee.name', 'isholiday');
    data_set($col3, 'isemployee.label', 'Holiday');

    data_set($col3, 'isstudent.name', 'isregot');
    data_set($col3, 'isstudent.label', 'Regular OT');

    $fields = ['remarks'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = [];

    $data[0]['divid'] = 0;
    $data[0]['divcode'] = $config['newclient'];

    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];

    $data[0]['divname'] = '';
    $data[0]['address'] = '';
    $data[0]['group'] = '';
    $data[0]['remarks'] = '';
    $data[0]['attention'] = ''; //contact
    $data[0]['tel'] = '';
    $data[0]['faxno'] = '';


    $data[0]['colltype'] = '';

    $data[0]['isexcessbasic'] = '0';
    $data[0]['isnodeductbank'] = '0';
    $data[0]['isnodeductemp'] = '0';
    $data[0]['isbasic8'] = '0';
    $data[0]['isbasic4'] = '0';
    $data[0]['isexcessduty'] = '0';
    $data[0]['isregot'] = '0';
    $data[0]['isndiff'] = '0';
    $data[0]['isworkingrd'] = '0';
    $data[0]['isholiday'] = '0';

    //nasa tab
    $data[0]['noguards'] = '0';
    $data[0]['yrdays'] = '0';
    $data[0]['wkdays'] = '0';
    $data[0]['dutyhrs'] = '0';
    $data[0]['mons'] = '0';
    $data[0]['days'] = '0';
    $data[0]['hrs'] = '0';
    $data[0]['excesshrs'] = '0';
    $data[0]['dailywage'] = '0';
    $data[0]['salary'] = '0';
    $data[0]['excessduty'] = '0';
    $data[0]['cola'] = '0';
    $data[0]['otamt'] = '0';
    $data[0]['amt13th'] = '0';
    $data[0]['incentive'] = '0';
    $data[0]['uniformamt'] = '0';
    $data[0]['retireamt'] = '0';
    $data[0]['sssamt'] = '0';
    $data[0]['phicamt'] = '0';
    $data[0]['hdmfamt'] = '0';
    $data[0]['eccamt'] = '0';
    $data[0]['agencyfee'] = '0';
    $data[0]['agencyvat'] = '0';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = isset($config['params']['row']) ? $config['params']['row']['divid'] : $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select divid as value from division where  center=? order by divid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields="";

    foreach ($this->divinfofields as $key => $value) {
      if ($value == 'agencyvat') {
        $fields .= ',format(if(' . $this->divinfo . '.agencyfee <> 0, '
          . '(' . $this->divinfo . '.agencyfee * 0.12), 0), 2) as agencyvat';
      } else {
        $fields = $fields . ',' . $this->divinfo . '.' . $value;
      }
    }

    // Total Contract
      $fields .= ',format((
          ifnull(' . $this->divinfo . '.salary, 0)
          + ifnull(' . $this->divinfo . '.excessduty, 0)
          + ifnull(' . $this->divinfo . '.cola, 0)
          + ifnull(' . $this->divinfo . '.otamt, 0)
          + ifnull(' . $this->divinfo . '.amt13th, 0)
          + ifnull(' . $this->divinfo . '.incentive, 0)
          + ifnull(' . $this->divinfo . '.uniformamt, 0)
          + ifnull(' . $this->divinfo . '.retireamt, 0)
          + ifnull(' . $this->divinfo . '.sssamt, 0)
          + ifnull(' . $this->divinfo . '.phicamt, 0)
          + ifnull(' . $this->divinfo . '.hdmfamt, 0)
          + ifnull(' . $this->divinfo . '.eccamt, 0)
          + ifnull(' . $this->divinfo . '.agencyfee, 0)
          + (ifnull(' . $this->divinfo . '.agencyfee, 0) * 0.12)), 2) as fcontractprice';

    $qry = "select dv.divid as clientid, dv.divcode as client,
            dv.divname,dv.address,dv.group,'' as subgroup,dv.remarks,
            dv.attention,dv.tel,dv.faxno $fields
            from " . $this->head . " as dv 
            left join divinfo on divinfo.divid=dv.divid
            where dv.divid =? ";
    $head = $this->coreFunctions->opentable($qry, [$clientid]);
   
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->divinfo, ['viewdate' => $viewdate, 'viewby' => $viewby], ['divid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $clientid];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['divname'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $dataInfo = [];

    $dateTables = ['division', 'divinfo'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    if ($isupdate) {
      unset($this->fields['divcode']);
    }
    $clientid = 0;
    $msg = '';

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key],$lookups);
        }
      }
      }

    foreach ($this->divinfofields as $key) {
      if (array_key_exists($key, $head)) {
        $dataInfo[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataInfo[$key] = $this->othersClass->sanitizekeyfieldFast($key, $dataInfo[$key], $lookups);
        } //end if  
      }
    }

    if ($isupdate) {
      //division
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      //divinfo
      $dataInfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $dataInfo['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate('division', $data, ['divid' => $head['clientid']]);

      $exist = $this->coreFunctions->getfieldvalue($this->divinfo, "divid", "divid=?", [$head['clientid']]);
      if (floatval($exist) != 0) {
        $this->coreFunctions->sbcupdate($this->divinfo, $dataInfo, ['divid' => $head['clientid']]);
      } else {
        $dataInfo['divid'] = $head['clientid'];
        $this->coreFunctions->sbcinsert($this->divinfo, $dataInfo);
      }
      $clientid = $head['clientid'];
    } else {
      $dataInfo['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $dataInfo['encodedby'] = $config['params']['user'];
     
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('division', $data);

      if($clientid) {
      $dataInfo['divid'] = $clientid;
      $this->coreFunctions->sbcinsert($this->divinfo, $dataInfo);
      }
      $this->logger->sbcmasterlog($clientid, $config, 'CREATE Division ID - ' . $clientid . ' Code - ' . $head['client'] . '  Division Name - ' . $head['divname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select divcode as value from division order by divid desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select divcode as value from division where left(divcode,?)=? order by divcode desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('division', 'divcode', 'divid=?', [$clientid]);
    $qry = "select divid as value from division where divid<? order by divid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from division where divid=?', 'delete', [$clientid]);
    $this->logger->sbcdelmaster_log($clientid, $config, 'REMOVE - ' . $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
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
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->generateResult($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
