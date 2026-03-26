<?php

namespace App\Http\Classes\modules\payroll;

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

class employee
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EMPLOYEE LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $headOther = 'employee';
  public $contact = 'contacts';
  public $prefix = 'EM';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  public $tagging = "isemployee";
  private $stockselect;

  private $fields = [
    'client',
    'clientname',
    'isemployee',
    'addr',
    'picture',
    'iscustomer',
    'isagent',
    'isemployee',
    'isdepartment',
    'issupplier',
    'iswarehouse',
    'isinactive',
    'floor'
  ];

  private $fieldsOther = [
    'emplast',
    'empfirst',
    'empfirst',
    'empmiddle',
    'hired',
    'resigned',
    'city',
    'country',
    'telno',
    'mobileno',
    'citizenship',
    'maidname',
    'gender',
    'remarks',
    'bday',
    'status',
    'zipcode',
    'email',
    'religion',
    'alias',
    'jobid',
    'level',
    'isactive',
    'lastbatch',
    'mapp',
    'agency',
    'aplcode',
    'jgrade',
    'emprank',
    'emploc',
    'emptype',
    'regular',
    'prob',
    'idbarcode',
    'tin',
    'sss',
    'phic',
    'hdmf',
    'bankacct',
    'atm',
    'emprate',
    'teu',
    'nodeps',
    'chksss',
    'chktin',
    'chkphealth',
    'chkpibig',
    'dyear',
    'sssdef',
    'philhdef',
    'pibigdef',
    'wtaxdef',
    'mealdeduc',
    'supervisorid',
    'shiftid',
    'blood',
    'paygroup',
    'cola',
    'divid',
    'deptid',
    'sectid',
    'isapprover',
    'roleid',
    'nochild',
    'trainee',
    'biometricid',
    'projectid',
    'itemid',
    'issupervisor',
    'isbank',
    'branchid',
    'permanentaddr',
    'empnoref',
    'callsign',
    'approver1',
    'empstatus',
    'is13th'
  ];

  private $contactfields = ['contact1', 'relation1', 'addr1', 'homeno1', 'mobileno1', 'officeno1', 'ext1', 'notes1', 'contact2', 'relation2', 'addr2', 'homeno2', 'mobileno2', 'officeno2', 'ext2', 'notes2'];
  private $except = ['empid', 'age', 'clientid', 'mapp', 'aplcode', 'jgrade', 'emprank', 'emploc', 'emptype', 'paymode', 'division', 'dept', 'orgsection','floor'];
  private $blnfields = ['isemployee', 'isactive', 'atm', 'chksss', 'chktin', 'chkphealth', 'chkpibig', 'isapprover', 'issupervisor', 'iscustomer', 'isagent', 'isemployee', 'isdepartment', 'issupplier', 'iswarehouse', 'isinactive', 'is13th'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;
  // public $showfilterlabel = [];

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
    $attrib = array( // for update
      'view' => 1720,
      'edit' => 1302,
      'new' => 1303,
      'save' => 1304,
      'change' => 1305,
      'delete' => 1306,
      'print' => 1307
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $getcols = ['action', 'listclient', 'emplast', 'empfirst', 'empmiddle', 'listaddr', 'paymode', 'paygroup', 'hired', 'bday'];
    $stockbuttons = ['view'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$hired]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$empmiddle]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$hired]['align'] = 'text-left';
    $cols[$bday]['align'] = 'text-left';
    if ($companyid == 44) { // stonepro
      $cols[$listclient]['label'] = 'ID No.';
    }

    return $cols;
  }

  public function loaddoclisting($config)
  {

    $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
    $id = $config['params']['adminid'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $emplvl = $this->othersClass->checksecuritylevel($config, true);
    $check = $this->othersClass->checkapproversetup($config, $id, '', 'emp');
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];


    if (isset($config['params']['search'])) {
      $searchfield = ['cl.client', 'cl.clientname', 'cl.addr', 'emp.emplast', 'emp.empfirst', 'emp.empmiddle'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $addparams = '';
    $filtersearch = "";
    $condition = "";
    $active = " and emp.isactive = 1 ";
    switch ($companyid) {
      case 53: //camera
        if (isset($config['params']['doclistingparam'])) {
          $company = $config['params']['doclistingparam'];
          $section = $config['params']['doclistingparam'];
          $department = $config['params']['doclistingparam'];

          if (isset($company['company']) && $company['company'] != "") {

            $addparams .= " and emp.divid = '" . $company['divid'] . "' ";
          }
          if (isset($section['sectionname']) && $section['sectionname'] != "") {

            $addparams .= " and emp.sectid = '" . $section['sectid'] . "'";
          }
          if (isset($department['department']) && $department['department'] != "") {

            $addparams .= " and emp.deptid = '" . $department['deptid'] . "' ";
          }
        }
        $condition .= " and emp.resigned is null ";

        break;
      case 51: //ulitc
        $condition .= " and emp.resigned is null ";
        break;
      default:
        if (isset($config['params']['doclistingparam'])) {
          $empstatus = $config['params']['doclistingparam'];
          if (isset($empstatus['resigned']) && $empstatus['resigned'] != '') {
            switch ($empstatus['resigned']) {
              case 'Active':
                break;
              case 'Inactive':
                $active = " and emp.isactive = 0";
                break;
              default:
                $active = " and emp.isactive = 0 and emp.resigned is not null";
                $addparams .= " and resignedtype = '" . $empstatus['resigned'] . "' ";
                break;
            }
          }
        }

        break;
    }
    $paygroup = 'paygroup';
    if ($companyid == 43) { //mighty
      $paygroup = 'code';
    }


    $leftjoin = "";

    if ($id != 0) {
      if ($viewaccess == '0') {

        if ($companyid == 51 || $companyid == 53) { // ulitc, camera
          if ($check['filter'] != "") {
            $condition .= $check['filter'];
          }
          if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
          }
        }

        $condition .= " and (emp.supervisorid = $id or emp.empid=$id) ";
      }
    }


    $qry = "select cl.clientid,cl.client,cl.clientname,cl.addr,emp.emplast,emp.empfirst,emp.empmiddle,emp.paymode, paygroup." . $paygroup . " as paygroup, date(emp.hired) as hired, date(emp.bday) as bday
    from  " . $this->head . " as cl 
    left join employee as emp on emp.empid = cl.clientid
    left join paygroup on paygroup.line = emp.paygroup
    $leftjoin
    where cl.isemployee=1 $active $condition and emp.level in " . $emplvl . " " . $filtersearch . " " . $addparams . "
    order by cl.clientname";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $companyid = $config['params']['companyid'];

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
      'toggledown',
      'others'
    );
    $systemtype = $this->companysetup->getiswindowspayroll($config['params']);
    if ($systemtype) { //STONEPRO | CAMERA SOUND | ulitc
      $btns = array(
        'load',
        'logs',
        'backlisting',
        'toggleup',
        'toggledown',
        'others'
      );

      $this->showcreatebtn = false;
    }

    $buttons = $this->btnClass->create($btns);
    $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
    $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];
    return $buttons;
  } // createHeadbutton
  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 53: //camera

        $fields = ['company'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'company.type', 'lookup');
        data_set($col1, 'company.lookupclass', 'lookupcompany');
        data_set($col1, 'company.action', 'lookupcompany');
        data_set($col1, 'company.readonly', true);

        $fields = ['sectionname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'sectionname.type', 'lookup');
        data_set($col2, 'sectionname.lookupclass', 'lookupsections');
        data_set($col2, 'sectionname.action', 'lookupsections');
        data_set($col2, 'sectionname.label', 'Section');

        $fields = ['department'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'department.type', 'lookup');
        data_set($col3, 'department.lookupclass', 'lookupdepartments');
        data_set($col3, 'department.action', 'lookupdepartments');

        $data = $this->coreFunctions->opentable("select '' as company, 0 as divid,0 as sectid,'' as sectionname , '' as department,0 as deptid");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3]];
        break;
      case 44: //stonepro
      case 51: //ulitc
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
        break;
      default:
        $fields = ['resigned'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'resigned.type', 'lookup');
        data_set($col1, 'resigned.lookupclass', 'lookupresigned');
        data_set($col1, 'resigned.action', 'lookupresigned');
        data_set($col1, 'resigned.readonly', true);
        data_set($col1, 'resigned.class', 'csresigned sbccsreadonly');
        data_set($col1, 'resigned.label', 'Employee Status');

        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        $data = $this->coreFunctions->opentable("select 'Active' as resigned");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
        break;
    }
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $fields = [
      ['jobcode', 'jobtitle'],
      'jobdesc',
      ['empdesc', 'emploc'],
      ['aplcode', 'jgrade'],
      'dbranchname',
      'shiftcode'
    ];

    if ($companyid == 53) { //camera
      $fields = [
        'jobtitle',
        ['empdesc', 'emploc'],
        ['aplcode', 'jgrade'],
        'dbranchname',
        'shiftcode'
      ];
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');
    data_set($col1, 'shiftcode.class', 'csshiftcode sbccsreadonly');
    data_set($col1, 'shiftcode.lookupclass', 'lookupshiftcode');
    data_set($col1, 'dbranchname.name', 'branchname');

    data_set($col1, 'empdesc.lookupclass', 'empstatus');
    data_set($col1, 'empdesc.label', 'Employment Status');

    switch ($companyid) {
      case 3: // conti
        data_set($col1, 'emploc.label', 'Warehouse');
        break;
    }

    data_set($col1, 'emploc.type', 'lookup');
    data_set($col1, 'emploc.action', 'holidaylookuploc');
    data_set($col1, 'emploc.lookupclass', 'loookupholidayemploc');
    data_set($col1, 'emploc.class', 'csemploc sbccsenablealways');

    // data_set($col1, 'emploc.readonly', false);



    $fields = ['contact', 'relation', 'addr', ['homeno', 'mobileno'], ['officeno', 'ext1'], 'notes'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.name', 'contact1');
    data_set($col2, 'contact.label', 'Contact Person 1');
    data_set($col2, 'relation.name', 'relation1');
    data_set($col2, 'addr.name', 'addr1');
    data_set($col2, 'homeno.name', 'homeno1');
    data_set($col2, 'mobileno.name', 'mobileno1');
    data_set($col2, 'officeno.name', 'officeno1');
    data_set($col2, 'notes.name', 'notes1');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'relation.type', 'cinput');
    data_set($col2, 'addr.type', 'textarea');
    data_set($col2, 'homeno.type', 'cinput');
    data_set($col2, 'mobileno.type', 'cinput');
    data_set($col2, 'officeno.type', 'cinput');
    data_set($col2, 'ext1.type', 'cinput');
    data_set($col2, 'notes.type', 'cinput');


    $fields = ['contact', 'relation', 'addr', ['homeno', 'mobileno'], ['officeno', 'ext1'], 'notes'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'contact.name', 'contact2');
    data_set($col3, 'contact.label', 'Contact Person 2');
    data_set($col3, 'relation.name', 'relation2');
    data_set($col3, 'addr.name', 'addr2');
    data_set($col3, 'homeno.name', 'homeno2');
    data_set($col3, 'mobileno.name', 'mobileno2');
    data_set($col3, 'officeno.name', 'officeno2');
    data_set($col3, 'notes.name', 'notes2');
    data_set($col3, 'ext1.name', 'ext2');

    data_set($col3, 'contact.type', 'cinput');
    data_set($col3, 'relation.type', 'cinput');
    data_set($col3, 'addr.type', 'textarea');
    data_set($col3, 'homeno.type', 'cinput');
    data_set($col3, 'mobileno.type', 'cinput');
    data_set($col3, 'officeno.type', 'cinput');
    data_set($col3, 'ext1.type', 'cinput');
    data_set($col3, 'notes.type', 'cinput');

    //TAB2
    $fields = [['tin', 'chktin'], ['sss', 'chksss'], ['phic', 'chkphealth'], ['hdmf', 'chkpibig'], ['emprank', 'mapp']];
    $col5 = $this->fieldClass->create($fields);
    data_set($col5, 'mapp.class', 'sbccsreadonly');

    data_set($col5, 'tin.type', 'cinput');
    data_set($col5, 'sss.type', 'cinput');
    data_set($col5, 'phic.type', 'cinput');
    data_set($col5, 'hdmf.type', 'cinput');

    $fields = [['dyear', 'cola'], ['sssdef', 'philhdef'], ['pibigdef', 'wtaxdef']];

    switch ($companyid) {
      case 43: //mighty
        array_push($fields, 'project', 'ditemname');
        break;
      case 53: //camera
        array_push($fields, 'lblrem', 'obapp1', 'obapp2');
        break;
      case 58: //cdo
        array_push($fields, 'lblbank', 'radiobank', ['bankacct', 'atm']);
        break;
      case 62: //onesky
        array_push($fields, ['mealdeduc',]);
        break;
    }



    $col6 = $this->fieldClass->create($fields);
    data_set($col6, 'dyear.type', 'cinput');
    data_set($col6, 'cola.type', 'cinput');
    data_set($col6, 'sssdef.type', 'cinput');
    data_set($col6, 'philhdef.type', 'cinput');
    data_set($col6, 'pibigdef.type', 'cinput');
    data_set($col6, 'wtaxdef.type', 'cinput');


    switch ($companyid) {
      case 30: //RT
        data_set($col6, 'sssdef.label', 'SSS (W4)');
        data_set($col6, 'philhdef.label', 'Philhealth (W4)');
        data_set($col6, 'pibigdef.label', 'HMDF (W4)');
        data_set($col6, 'wtaxdef.label', 'W/tax (W4)');
        break;
      case 43: //mighty
        data_set($col6, 'project.label', 'Project Name');
        data_set($col6, 'project.class', 'cssproject sbccsreadonly');
        data_set($col6, 'project.readonly', false);
        data_set($col6, 'project.required', false);
        data_set($col6, 'ditemname.label', 'Truck/Asset');
        break;
      case 53: //camera
        data_set($col6, 'lblrem.label', 'OB Approver:');
        data_set($col6, 'lblrem.style', 'font-weight:bold;font-size:11px;');
        break;
    }
    $fields = [['agency', 'trainee'], ['prob', 'regular'], ['lastbatch', 'resigned']];
    $col7 = $this->fieldClass->create($fields);

    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col5, 'col3' => $col6, 'col4' => $col7], 'label' => 'ADDITIONAL INFO'],
      'multiinput2' => ['inputcolumn' => ['col1' => $col2, 'col2' => $col3], 'label' => 'CONTACT PERSON INFO']
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($access, $config)
  {
    $rate_access = $this->othersClass->checkAccess($config['params']['user'], 5300);
    $appsetup_access = $this->othersClass->checkAccess($config['params']['user'], 5435);


    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
    $attach = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappdependents', 'label' => 'DEPENDENTS']];
    $dependants = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempeducation', 'label' => 'EDUCATION']];
    $education = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempadvances', 'label' => 'ADVANCES']];
    $advances = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemploans', 'label' => 'LOANS']];
    $loans = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempemployment', 'label' => 'EMPLOYMENT']];
    $empemployment = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemprate', 'label' => 'RATE']];
    $rate = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempcontract', 'label' => 'CONTRACT']];
    $contract = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempallowances', 'label' => 'ALLOWANCE']];
    $allowance = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemptraining', 'label' => 'TRAINING']];
    $training = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempturnover', 'label' => 'TURN-OVER/RETURN ITEMS']];
    $turnover = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytabrole', 'label' => 'ROLE SETUP']];
    $rolesetup = $this->tabClass->createtab($tab, []);

    $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrymultiapprover', 'label' => 'APPROVER SETUP']];
    $multiapprover = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'noticeofdisciplinary', 'label' => 'NOTICE OF DISCIPLINARY ACTION']];
    $notice = $this->tabClass->createtab($tab, []);

    $return = [];
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
    $return['DEPENDENTS'] = ['icon' => 'fa fa-envelope', 'tab' => $dependants];
    $return['EDUCATION'] = ['icon' => 'fa fa-book-open', 'tab' => $education];
    $return['ADVANCES'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $advances];
    $return['LOANS'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $loans];
    $return['EMPLOYMENT'] = ['icon' => 'fa fa-user-tie', 'tab' => $empemployment];
    $return['APPROVER SETUP'] = ['icon' => 'fa fa-user-tag', 'tab' => $multiapprover];
    $return['NOTICE OF DISCIPLINARY ACTION'] = ['icon' => 'fa fa-exclamation', 'tab' => $notice]; //<i class="fa-solid fa-exclamation"></i>

    if ($rate_access != 0) {
      $return['RATE'] = ['icon' => 'fa fa-money-bill', 'tab' => $rate];
    }

    $return['CONTRACT'] = ['icon' => 'fa fa-file-signature', 'tab' => $contract];
    $return['ALLOWANCE'] = ['icon' => 'fa fa-coins', 'tab' => $allowance];
    $return['TRAINING'] = ['icon' => 'fa fa-list-ul', 'tab' => $training];
    $return['TURN-OVER/RETURN ITEMS'] = ['icon' => 'fa fa-exchange-alt', 'tab' => $turnover];
    $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
    if ($this->getisaprover($config) != 0) {
      $return['ROLE SETUP'] = ['icon' => 'fa fa-users', 'tab' => $rolesetup];
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 3: // conti remove loans, advances.
        unset($return['LOANS']);
        unset($return['ADVANCES']);
        break;
      case 51: //ulitc
      case 53: //camera
        unset($return['Attachment']);
        break;
    }



    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    //'viewdep', 'vieweduc','viewemployment','viewemprate','viewemploans','viewempadvances','viewempcontract','viewempallowance','viewemptraining','viewempturnover'
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['client', 'emplast', 'empfirst', 'empmiddle', 'addr', 'permanentaddr', ['city', 'country'], ['zipcode', 'blood'], ['citizenship', 'religion'], ['telno', 'mobileno']];
    if ($companyid == 43) { //mighty
      array_push($fields, 'biometric');
    }
    if ($companyid == 62) { //onesky
      array_push($fields, 'floor');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'lookupclient');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'client.name', 'client');

    data_set($col1, 'emplast.type', 'cinput');
    data_set($col1, 'empfirst.type', 'cinput');
    data_set($col1, 'empmiddle.type', 'cinput');
    data_set($col1, 'addr.type', 'textarea');
    data_set($col1, 'addr.label', 'Present Address');
    data_set($col1, 'city.type', 'cinput');
    data_set($col1, 'country.type', 'cinput');
    data_set($col1, 'zipcode.type', 'cinput');
    data_set($col1, 'blood.type', 'cinput');
    data_set($col1, 'citizenship.type', 'cinput');
    data_set($col1, 'religion.type', 'cinput');
    data_set($col1, 'telno.type', 'cinput');
    data_set($col1, 'mobileno.type', 'cinput');
    if ($companyid == 43) { //mighty
      data_set($col1, 'biometric.class', 'cssbiometic sbccsreadonly');
      data_set($col1, 'biometric.type', 'lookup');
      data_set($col1, 'biometric.action', 'lookupbiometric');
    }
    if ($companyid == 62) { //onesky
      data_set($col1, 'floor.label', 'Floor No.');
      data_set($col1, 'floor.type', 'lookup');
      data_set($col1, 'floor.action', 'lookupfloor');
      data_set($col1, 'floor.lookupclass', 'lookupfloor');
      data_set($col1, 'floor.required', false);
    }
    $fields = [
      'maidname',
      ['mstatus', 'child'],
      'bday',
      ['age', 'gender'],
      'alias',
      'supervisorcode',
      'supervisor',
      'email',
      'lblTaxStatus',
      ['radioteu', 'nodeps']
    ];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'citizenship.type', 'cinput');
    data_set($col2, 'age.type', 'cinput');
    data_set($col2, 'alias.type', 'cinput');
    data_set($col2, 'supervisor.type', 'input');
    data_set($col2, 'supervisor.class', 'cssupervisor sbccsreadonly');
    data_set($col2, 'supervisor.required', false);
    data_set($col2, 'supervisorcode.required', false);
    // data_set($col2, 'supervisorcode.type', 'input');
    // data_set($col2, 'supervisorcode.class', 'cssupervisorcode sbccsreadonly');
    data_set($col2, 'email.type', 'cinput');

    data_set($col2, 'child.name', 'nochild');
    data_set($col2, 'radioteu.label', 'Tax Status');
    data_set($col2, 'nodeps.type', 'cinput');

    $fields = ['paymode', 'classrate', ['level', 'hired'], ['idbarcode', 'isactive']];

    if ($companyid != 58) { //not cdo
      array_push($fields, ['bankacct', 'atm']);
    }

    array_push($fields, ['emptype', 'tpaygroupname'], 'rolename', 'divname', 'deptname', 'sectionname');

    if ($companyid == 53) { // camera
      array_push($fields, 'approvercode');
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'idbarcode.type', 'cinput');
    data_set($col3, 'bankacct.type', 'cinput');
    data_set($col3, 'sectionname.label', 'Section');

    switch ($companyid) {
      case 3: // conti
        data_set($col3, 'divname.label', 'Branch');
        break;
      default:
        data_set($col3, 'divname.label', 'Company');
        break;
    }

    data_set($col3, 'tpaygroupname.type', 'input');

    switch ($systemtype) {
      case 'PAYROLL':
      case 'HRISPAYROLL':
        data_set($col3, 'tpaygroupname.type', 'lookup');
        data_set($col3, 'tpaygroupname.action', 'paygrouplookup');
        data_set($col3, 'tpaygroupname.lookupclass', 'tpaygrouplookup');

        data_set($col3, 'classrate.type', 'lookup');
        data_set($col3, 'classrate.action', 'lookupclassrate');
        data_set($col3, 'classrate.lookupclass', 'lookupclassrate');
        break;
    }

    data_set($col3, 'tpaygroupname.name', 'paygroupname');

    $fields = ['picture', 'remarks', 'callsign', 'empnoref', 'isapprover', 'issupervisor'];
    if ($companyid == 53) { //camera
      array_push($fields, 'is13th');
    }
    if ($companyid == 43) { //mighty
      array_push($fields, ['iscustomer', 'issupplier'], ['isagent', 'iswarehouse'], ['isdepartment', 'isinactive']);
    }
    if ($companyid == 62) { //onesky
      $fields = array_diff($fields, ['empnoref']);
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'employee');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');

    data_set($col4, 'remarks.type', 'ctextarea');

    data_set($col4, 'isapprover.label', 'Approver/HR');

    if ($companyid == 53) { //camera
      data_set($col4, 'isapprover.label', 'HR/ Payroll Approver');
      data_set($col4, 'issupervisor.label', 'Head Dept. Approver');
    }


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['empid'] = 0;
    $data[0]['clientid'] = 0;
    $data[0]['divid'] = 0;
    $data[0]['sectid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['picture'] = '';

    $data[0]['empfirst'] = '';
    $data[0]['emplast'] = '';
    $data[0]['empmiddle'] = '';
    $data[0]['addr'] = '';
    $data[0]['hired'] = $this->othersClass->getCurrentDate();

    $data[0]['emptype'] = '';
    $data[0]['jstatus'] = '';
    $data[0]['addr'] = '';
    $data[0]['permanentaddr'] = '';
    $data[0]['rolename'] = '';
    $data[0]['roleid'] = 0;

    $data[0]['city'] = '';
    $data[0]['country'] = '';
    $data[0]['citizenship'] = '';
    $data[0]['religion'] = '';
    $data[0]['telno'] = '';
    $data[0]['maidname'] = '';
    $data[0]['mobileno'] = '';
    $data[0]['alias'] = '';
    $data[0]['gender'] = '';
    $data[0]['status'] = '';
    $data[0]['nochild'] = 0;

    $data[0]['jobid'] = 0;
    $data[0]['level'] = 10;

    if ($config['params']['companyid'] == 62) { //onesky
      $data[0]['level'] = 0;
    }

    $data[0]['jobtitle'] = '';
    $data[0]['jobcode'] = '';
    $data[0]['jobdesc'] = '';
    $data[0]['remarks'] = '';
    $data[0]['terminal'] = '';
    $data[0]['isapprover'] = '0';
    $data[0]['issupervisor'] = '0';
    $data[0]['bday'] = null;
    $data[0]['bplace'] = '';
    $data[0]['age'] = 0;
    $data[0]['email'] = '';
    $data[0]['nodeps'] = 0;
    $data[0]['zipcode'] = '';
    $data[0]['isactive'] = '1';
    $data[0]['lastbatch'] = '';
    $data[0]['mapp'] = '';
    $data[0]['agency'] = null;
    $data[0]['resigned'] = null;
    $data[0]['regular'] = null;
    $data[0]['prob'] = null;
    $data[0]['trainee'] = null;
    $data[0]['empstatus'] = '';
    $data[0]['empstatusname'] = '';
    $data[0]['aplcode'] = '';
    $data[0]['jgrade'] = '';
    $data[0]['emprank'] = '';
    $data[0]['emploc'] = '';
    $data[0]['idbarcode'] = 0;
    $data[0]['tin'] = '';
    $data[0]['sss'] = '';
    $data[0]['phic'] = '';
    $data[0]['hdmf'] = '';
    $data[0]['bankacct'] = '';
    $data[0]['atm'] = '0';
    $data[0]['paymode'] = '';
    $data[0]['classrate'] = '';
    $data[0]['emprate'] = '';
    $data[0]['teu'] = '';
    $data[0]['chksss'] = '0';
    $data[0]['chktin'] = '0';
    $data[0]['chkphealth'] = '0';
    $data[0]['chkwtax'] = '0';
    $data[0]['chkpibig'] = '0';
    $data[0]['dyear'] = 0;
    $data[0]['sssdef'] = 0;
    $data[0]['philhdef'] = 0;
    $data[0]['pibigdef'] = 0;
    $data[0]['wtaxdef'] = 0;
    $data[0]['mealdeduc'] = 0;
    $data[0]['cola'] = 0;
    $data[0]['division'] = '';
    $data[0]['divname'] = '';
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['orgsection'] = '';
    $data[0]['sectionname'] = '';
    $data[0]['supervisorid'] = 0;
    $data[0]['supervisor'] = '';
    $data[0]['supervisorcode'] = '';
    $data[0]['shiftcode'] = '';
    $data[0]['shiftid'] = 0;
    $data[0]['blood'] = '';
    $data[0]['paygroup'] = 0;
    $data[0]['paygroupname'] = '';
    $data[0]['project'] = '';
    $data[0]['isemployee'] = '1';
    $data[0]['radioteu'] = 'S';

    $data[0]['iscustomer'] = '0';
    $data[0]['isagent'] = '0';
    $data[0]['isemployee'] = 1;
    $data[0]['isdepartment'] = '0';
    $data[0]['issupplier'] = '0';
    $data[0]['iswarehouse'] = '0';
    $data[0]['isinactive'] = '0';

    $data[0]['branchid'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';

    //contact info
    $data[0]['contact1'] = '';
    $data[0]['contact2'] = '';
    $data[0]['relation1'] = '';
    $data[0]['relation2'] = '';
    $data[0]['addr1'] = '';
    $data[0]['addr2'] = '';
    $data[0]['homeno1'] = '';
    $data[0]['homeno2'] = '';
    $data[0]['mobileno1'] = '';
    $data[0]['mobileno2'] = '';
    $data[0]['officeno1'] = '';
    $data[0]['officeno2'] = '';
    $data[0]['ext1'] = '';
    $data[0]['ext2'] = '';
    $data[0]['notes1'] = '';
    $data[0]['notes2'] = '';
    $data[0]['obapp1'] = '';
    $data[0]['obapp2'] = '';
    $data[0]['empnoref'] = '';
    $data[0]['callsign'] = '';

    if ($config['params']['companyid'] == 58) { //cdo
      $data[0]['radiobank'] = 1;
      $data[0]['isbank'] = 0;
    }

    $data[0]['approver1'] = '0';
    $data[0]['approvercode'] = '';
    $data[0]['approver'] = '';
    $data[0]['floor'] = '';
    $data[0]['is13'] = 1;



    // $data[0]['appprovername1'] = '';
    // $data[0]['appprovername2'] = '';


    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $condition = "";
    $paygroup = 'paygroup.paygroup';
    $groupbypaygroup = 'paygroup.paygroup';
    if ($companyid == 43) {
      $paygroup = "concat(paygroup.code,'~',paygroup.paygroup)";
      $groupbypaygroup = "paygroup.code,paygroup.paygroup";
    }
    if ($companyid == 51 || $companyid == 53) { // camera, ulitc
      $condition = " and " . $this->headOther . ".resigned is null ";
    }
    $jobtitle = 'jt.jobtitle';
    if ($companyid == 53) {
      $jobtitle = 'employee.jobtitle';
    }
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from " . $this->head . " where  center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];

    unset($this->fieldsOther[4]); // hired
    unset($this->fieldsOther[5]); // resigned
    $fields = "client.client,client.clientid,client.client as empcode,client.client as docno," . $this->headOther . ".empid,
               '' as atype, year(now())-year(" . $this->headOther . ".bday) as age,'' as djobtitle,
               employee.teu as radioteu,
               case when " . $this->headOther . ".paymode = 'S' then 'Semi-monthly' 
                  when " . $this->headOther . ".paymode = 'W' then 'Weekly' 
                  when " . $this->headOther . ".paymode = 'M' then 'Monthly' 
                  when " . $this->headOther . ".paymode = 'D' then 'Daily' 
                  when " . $this->headOther . ".paymode = 'P' then 'Piece Rate' 
                  else '' end as paymode,
               case when " . $this->headOther . ".classrate = 'D' then 'Daily' 
                  when " . $this->headOther . ".classrate = 'M' then 'Monthly' 
                  else '' end as classrate,
               case when YEAR(" . $this->headOther . ".hired) > '1970' then " . $this->headOther . ".hired
                  else '' end as hired,
               case when YEAR(" . $this->headOther . ".resigned) > '1970' then " . $this->headOther . ".resigned
               else '' end as resigned,
               dept.client as dept, dept.clientname as deptname,
               ifnull(`div`.divcode, '') as division,ifnull(`div`.divname, '') as divname,
               ifnull(sect.sectcode, '') as orgsection,ifnull(sect.sectname, '') as sectionname,$jobtitle,
               jt.docno as jobcode,group_concat(jd.description) as jobdesc, ts.shftcode as shiftcode,
               supervisor.clientid as supervisorid,supervisor.client as supervisorcode, 
               supervisor.clientname as supervisor, empstat.empstatus as empdesc, 
               " . $this->headOther . ".idbarcode,role.name as rolename, 
               " . $this->headOther . ".roleid, " . $paygroup . " as paygroupname,
               biometric.terminal as biometric,project.name as project,item.itemname as ditemname,
               obapp1.clientname as obapp1,obapp2.clientname as obapp2,employee.isbank as radiobank,employee.is13th as is13,
               ifnull(branch.clientname,'') as branchname,ifnull(branch.client,'') as branchcode, 
               " . $this->headOther . ".branchid,ifnull(app.clientname,'') as approver,ifnull(app.client,'') as approvercode";

    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',' . $this->head . '.' . $value;
    }

    foreach ($this->fieldsOther as $key => $value) {
      $fields = $fields . ',' . $this->headOther . '.' . $value;
    }

    foreach ($this->contactfields as $key => $value) {
      $fields = $fields . ',' . $this->contact . '.' . $value;
    }

    $qryselect = "select " . $fields;

    $qry = $qryselect . " from " . $this->head . " 
    left join " . $this->headOther . " on " . $this->headOther . ".empid = " . $this->head . ".clientid 
    left join " . $this->contact . " on " . $this->contact . ".empid=" . $this->head . ".clientid 
    left join client as dept on dept.clientid = " . $this->headOther . ".deptid 
    left join division as `div` on `div`.divid = " . $this->headOther . ".divid 
    left join section as sect on sect.sectid = " . $this->headOther . ".sectid 
    left join jobthead as jt on jt.line = " . $this->headOther . ".jobid 
    left join jobtdesc as jd on jd.trno =  jt.line
    left join tmshifts as ts on ts.line = " . $this->headOther . ".shiftid 
    left join client as supervisor on supervisor.clientid = " . $this->headOther . ".supervisorid
    left join client as obapp1 on obapp1.clientid = " . $this->headOther . ".obapp1
    left join client as obapp2 on obapp2.clientid = " . $this->headOther . ".obapp2
    left join empstatentry as empstat on empstat.line = " . $this->headOther . ".empstatus
    left join rolesetup as role on role.line = " . $this->headOther . ".roleid
    left join paygroup on paygroup.line = " . $this->headOther . ".paygroup
    left join biometric on biometric.line = " . $this->headOther . ".biometricid
    left join projectmasterfile as project on project.line = " . $this->headOther . ".projectid
    left join item on item.itemid = " . $this->headOther . ".itemid
    left join client as branch on branch.clientid = " . $this->headOther . ".branchid
    left join client as app on app.clientid = " . $this->headOther . ".approver1
  
    where " . $this->head . ".clientid = ? $condition
    group by clientid, employee.empid, atype, djobtitle, radioteu, employee.paymode, employee.dept, deptname,
    employee.division, div.divname, employee.orgsection, sect.sectname, sectionname, jobtitle, employee.jobtitle,employee.jobcode,
    employee.shiftcode, dept.client, clientname, employee.bday, employee.email, isemployee, addr, picture,
    emplast, empfirst, empfirst, empmiddle, hired, city, country, telno, mobileno, citizenship,
    maidname, gender, remarks, status, zipcode, religion, alias, jobid, level, isactive, lastbatch, mapp,
    agency, empstat.empstatus, aplcode, jgrade, emprank, emploc, emptype, resigned, regular, prob, idbarcode, tin,
    sss, phic, hdmf, bankacct, atm, employee.paymode, employee.classrate, emprate, teu, nodeps, chksss, chktin, chkphealth,
    chkpibig, dyear, sssdef, philhdef, pibigdef, wtaxdef, shiftid, blood, paygroup, paygroup.paygroup, cola, divid,
    deptid, sectid, contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1, notes1, contact2, relation2,
    permanentaddr,contacts.addr2, homeno2, mobileno2, officeno2, ext2, notes2,client.client,div.divcode,sect.sectcode,jt.docno,ts.shftcode,client.bday,
    supervisor.clientid, supervisor.client, supervisor.clientname, employee.supervisorid, isapprover, issupervisor,iscustomer,isagent,isemployee,isdepartment,
    issupplier,iswarehouse,isinactive,rolename, roleid, nochild,
    trainee,biometricid,biometric.terminal,projectid,project.name,employee.itemid,item.itemname, obapp1.clientname,obapp2.clientname,
    branch.clientname,isbank,branch.client,employee.branchid, empnoref, callsign,app.client,
    app.clientname,otsupervisorid,employee.empstatus,approver1,is13th,mealdeduc,client.floor, $groupbypaygroup";

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
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'qry' => $qry];
    } else {
      $head[0]['empid'] = 0;
      $head[0]['clientid'] = 0;
      $head[0]['empcode'] = '';
      $head[0]['client'] = '';
      $head[0]['emplast'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    $dataOther = [];
    $dataContact = [];
    if ($isupdate) {
      unset($this->fields['client']);
    }
    $clientid = 0;
    $msg = '';
    if ($head['hired'] == 'Invalid date') $head['hired'] = null;
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    foreach ($this->contactfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataContact[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataContact[$key] = $this->othersClass->sanitizekeyfield($key, $dataContact[$key]);
        } //end if  
      }
    }

    foreach ($this->fieldsOther as $key) {
      if (array_key_exists($key, $head)) {
        $dataOther[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key], 'EMPLOYEE', $companyid);
        } //end if  
      }
    }

    if (isset($head['radioteu'])) {
      $dataOther['teu'] = $head['radioteu'];
    }

    if ($companyid == 58) { //cdo
      if (isset($head['radiobank'])) {
        $dataOther['isbank'] = $head['radiobank'];
      }
    }
    if ($companyid == 53) { //camera
      if (isset($head['is13'])) {
        $dataOther['is13th'] = $head['is13'];
      }
    }

    if (isset($head['paymode'])) {
      $dataOther['paymode'] = substr($head['paymode'], 0, 1);
    }

    if (isset($head['classrate'])) {
      $dataOther['classrate'] = substr($head['classrate'], 0, 1);
    }

    $dataOther['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $dataOther['editby'] = $config['params']['user'];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $data['clientname'] = $head['emplast'] . ', ' . $head['empfirst'] . ' ' . $head['empmiddle'];
    $data['addr'] = $head['addr'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['clientid' => $head['empid']]);
      $exist = $this->coreFunctions->getfieldvalue($this->contact, "empid", "empid=?", [$head['empid']]);
      if (floatval($exist) != 0) {
        $this->coreFunctions->sbcupdate($this->contact, $dataContact, ['empid' => $head['empid']]);
      } else {
        $dataContact['empid'] = $head['empid'];
        $this->coreFunctions->sbcinsert($this->contact, $dataContact);
      }

      $exist = $this->coreFunctions->getfieldvalue($this->headOther, "empid", "empid=?", [$head['empid']]);
      if (floatval($exist) != 0) {
        $this->coreFunctions->sbcupdate($this->headOther, $dataOther, ['empid' => $head['empid']]);

        $isinactive = 1;
        if ($dataOther['isactive'] == 1) {
          $isinactive = 0;
        }
        $data2['isinactive'] = $isinactive;
        $this->coreFunctions->sbcupdate($this->head, $data2, ['clientid' => $head['empid']]);
      } else {
        $dataOther['empid'] = $head['empid'];
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
      }

      $clientid = $head['empid'];
      $empid = $head['empid'];
    } else {

      if ($companyid == 62) { //onesky
        if ($dataOther['level'] == 0) $dataOther['level'] = $dataOther['divid'];
      }

      $dataOther['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $dataOther['createby'] = $config['params']['user'];

      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);
      if ($clientid) {
        $dataOther['empid'] = $clientid;
        $dataContact['empid'] = $clientid;
        $this->coreFunctions->sbcinsert($this->contact, $dataContact);
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);

        // $this->logger->sbcmasterlog(
        //   $clientid,
        //   $config,
        //   "CREATE - NAME: ".$head['empfirst']." ".$head['empmiddle'].", ".$head['emplast'].",
        //   SUPERVISOR: ".$head['supervisor'].", 
        //   ROLE: ".$head['rolename'].",
        //   JOBTITLE: ".$head['jobtitle'].",
        //   SHIFTCODE: ".$head['shiftcode'].""); 
      }
      // $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid 
      // . ' - ' . $data['clientname']
      // ." SUPERVISOR: ".$head['supervisor'].", 
      // ROLE: ".$head['rolename'].", 
      // JOBTITLE: ".$head['jobtitle'].", 
      // SHIFTCODE: ".$head['shiftcode']."");
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where isemployee =1  order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isemployee =1  and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $exist = $this->coreFunctions->getfieldvalue('paytrancurrent', 'empid', 'empid=?', [$clientid]);
    $exist2 = $this->coreFunctions->getfieldvalue('paytranhistory', 'empid', 'empid=?', [$clientid]);
    //$ishired = $this->coreFunctions->getfieldvalue('app', 'ishired', 'empid=?', [$clientid]);

    if (floatval($exist) != 0 || floatval($exist2) != 0) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already has a payroll record...'];
    }
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    $this->coreFunctions->execqry('delete from employee where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from contacts where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from dependents where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from education where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from employment where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from contracts where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from ratesetup where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from standardsetup where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from standardtrans where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from standardsetupadv where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from standardtransadv where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from allowsetup where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);

    $qry = "select clientid as value from client where clientid<? and isemployee=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);

    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  private function getisaprover($config)
  {

    $roleaccess = $this->othersClass->checkAccess($config['params']['user'], 2404);

    if ($roleaccess) {
      return $roleaccess;
    }

    if (isset($config['params']['adminid'])) {
      $clientid = $config['params']['adminid'];
      $qry = "select isapprover as value from employee where empid = ? ";
      return $this->coreFunctions->datareader($qry, [$clientid]);
    } else {
      return 0;
    }
  }

  private function getcount($empid)
  {
    return $this->coreFunctions->datareader("select count(line) as value from education where empid=? ", [$empid]);
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
  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {

      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }
} //end class
