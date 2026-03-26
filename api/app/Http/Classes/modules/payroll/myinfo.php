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

class myinfo
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MY INFORMATION';
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
  private $stockselect;

  private $fields = ['client', 'clientname', 'bday', 'isemployee', 'addr', 'picture'];

  private $fieldsOther = [
        'emplast', 'empfirst', 'empfirst', 'empmiddle', 'hired', 'resigned', 'city', 'country', 'telno', 'mobileno', 'citizenship',
        'maidname', 'gender', 'remarks', 'bday', 'status', 'zipcode', 'email', 'religion', 'alias', 'jobid', 'level', 'isactive', 'lastbatch',
        'mapp', 'agency', 'aplcode', 'jgrade', 'emprank', 'emploc', 'emptype', 'regular', 'prob', 'idbarcode', 'tin', 'sss', 'phic',
        'hdmf', 'bankacct', 'atm', 'emprate', 'teu', 'nodeps', 'chksss', 'chktin', 'chkphealth', 'chkpibig', 'dyear', 'sssdef', 'philhdef',
        'pibigdef', 'wtaxdef', 'supervisorid', 'shiftid', 'blood', 'paygroup', 'cola', 'divid', 'deptid', 'sectid', 'isapprover',
        'roleid', 'nochild', 'trainee', 'permanentaddr'
  ];


  private $contactfields = ['contact1', 'relation1', 'addr1', 'homeno1', 'mobileno1', 'officeno1', 'ext1', 'notes1', 'contact2', 'relation2', 'addr2', 'homeno2', 'mobileno2', 'officeno2', 'ext2', 'notes2'];
  private $except = ['empid', 'age', 'clientid', 'mapp', 'aplcode', 'jgrade', 'emprank', 'emploc', 'emptype', 'paymode', 'division', 'dept', 'orgsection'];
  private $blnfields = ['isemployee', 'isactive', 'atm', 'chksss', 'chktin', 'chkphealth', 'chkpibig', 'isapprover'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;
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
    $attrib = array( // for update
      'view' => 2805,
      'print' => 2806,
      'edit' => 5302,
      'save' => 5302
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listaddr'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $empid = $config['params']['adminid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $emplvl = $this->othersClass->checksecuritylevel($config, true);
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['cl.clientid', 'cl.client', 'cl.clientname', 'cl.addr'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $qry = "select cl.clientid,cl.client,cl.clientname,cl.addr
    from  " . $this->head . " as cl 
    left join employee as emp on emp.empid = cl.clientid
    where cl.clientid = $empid $filtersearch";

    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'edit',
      'save',
      'cancel'
      //   'print'
    );
    $systemtype = $this->companysetup->getiswindowspayroll($config['params']);
    if ($systemtype) {
      $btns = array(
        'load'
      );
    }

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['jobdesc', ['empstatus', 'emploc'], ['aplcode', 'jgrade'], 'branch', 'shiftcode'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empstatus.name', 'empstatusname');
    data_set($col1, 'shiftcode.class', 'csshiftcode sbccsreadonly');
    data_set($col1, 'shiftcode.type', 'input');

    data_set($col1, 'aplcode.type', 'cinput');
    data_set($col1, 'aplcode.type', 'cinput');
    data_set($col1, 'branch.type', 'cinput');

    if ($companyid == 58) { //cdo
      data_set($col1, 'branch.class', 'csbranch sbccsreadonly');
    }

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
    data_set($col2, 'addr.type', 'cinput');
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
    data_set($col3, 'addr.type', 'cinput');
    data_set($col3, 'homeno.type', 'cinput');
    data_set($col3, 'mobileno.type', 'cinput');
    data_set($col3, 'officeno.type', 'cinput');
    data_set($col3, 'ext1.type', 'cinput');
    data_set($col3, 'notes.type', 'cinput');

    //TAB2
    $fields = [['tin', 'chktin'], ['sss', 'chksss'], ['phic', 'chkphealth'], ['hdmf', 'chkpibig'], ['emprank', 'mapp']];
    $col5 = $this->fieldClass->create($fields);

    data_set($col5, 'tin.type', 'cinput');
    data_set($col5, 'sss.type', 'cinput');
    data_set($col5, 'phic.type', 'cinput');
    data_set($col5, 'hdmf.type', 'cinput');
    data_set($col5, 'emprank.type', 'cinput');
    data_set($col5, 'mapp.readonly', true);

    if ($companyid == 58) { //cdo
      data_set($col5, 'chktin.class', 'cschktin sbccsreadonly');
      data_set($col5, 'chksss.class', 'cschksss sbccsreadonly');
      data_set($col5, 'chkphealth.class', 'cschkphealth sbccsreadonly');
      data_set($col5, 'chkpibig.class', 'cschkpibig sbccsreadonly');
      data_set($col5, 'mapp.class', 'csmapp sbccsreadonly');
    }


    $fields = [['dyear', 'cola'], ['sssdef', 'philhdef'], ['pibigdef', 'wtaxdef']];
    switch ($companyid) {
      // case 43: //mighty
      //   array_push($fields, 'project', 'ditemname');
      //   break;
      // case 53: //camera
      //   array_push($fields, 'lblrem', 'obapp1', 'obapp2');
      //   break;
      case 58: //cdo
        array_push($fields, 'lblbank', 'radiobank', 'bankacct');
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

        data_set($col6, 'sssdef.type', 'cinput');
        data_set($col6, 'philhdef.type', 'cinput');
        data_set($col6, 'pibigdef.type', 'cinput');
        data_set($col6, 'wtaxdef.type', 'cinput');
        break;
      case 58: //cdo
        data_set($col6, 'dyear.class', 'csdyear sbccsreadonly');
        data_set($col6, 'cola.class', 'cscola sbccsreadonly');
        data_set($col6, 'sssdef.class', 'cssssdef sbccsreadonly');
        data_set($col6, 'philhdef.class', 'csphilhdef sbccsreadonly');
        data_set($col6, 'pibigdef.class', 'cspibigdef sbccsreadonly');
        data_set($col6, 'wtaxdef.class', 'cswtaxdef sbccsreadonly');
        break;
    }

    $fields = [['agency', 'trainee'], ['prob', 'regular'], ['lastbatch', 'resigned']];
    $col7 = $this->fieldClass->create($fields);
    data_set($col7, 'lastbatch.type', 'cinput');

    if ($companyid == 58) { //cdo
      data_set($col7, 'agency.class', 'csagency sbccsreadonly');
      data_set($col7, 'trainee.class', 'cstrainee sbccsreadonly');
      data_set($col7, 'prob.class', 'csprob sbccsreadonly');
      data_set($col7, 'regular.class', 'csregular sbccsreadonly');
      data_set($col7, 'lastbatch.class', 'cslastbatch sbccsreadonly');
      data_set($col7, 'resigned.class', 'csresigned sbccsreadonly');
    }

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
    $rate_access = $this->othersClass->checkAccess($config['params']['user'], 5343);
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
    $return = [];

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];
    $return['DEPENDENTS'] = ['icon' => 'fa fa-envelope', 'tab' => $dependants];
    $return['EDUCATION'] = ['icon' => 'fa fa-book-open', 'tab' => $education];
    $return['ADVANCES'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $advances];
    $return['LOANS'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $loans];
    $return['EMPLOYMENT'] = ['icon' => 'fa fa-user-tie', 'tab' => $empemployment];
    if ($rate_access) {
      $return['RATE'] = ['icon' => 'fa fa-money-bill', 'tab' => $rate];
    }
    $return['CONTRACT'] = ['icon' => 'fa fa-file-signature', 'tab' => $contract];
    $return['ALLOWANCE'] = ['icon' => 'fa fa-coins', 'tab' => $allowance];
    $return['TRAINING'] = ['icon' => 'fa fa-list-ul', 'tab' => $training];
    $return['TURN-OVER/RETURN ITEMS'] = ['icon' => 'fa fa-exchange-alt', 'tab' => $turnover];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['picture', ['client', 'idbarcode'], 'clientname', 'jobtitle', ['level', 'hired']];

    if ($companyid == 53) { // camera
      array_push($fields,  'approvercode');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'picture.lookupclass', 'client');
    data_set($col1, 'picture.folder', 'employee');
    data_set($col1, 'picture.table', 'client');
    data_set($col1, 'picture.fieldid', 'clientid');

    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.label', 'Employee Code');
    data_set($col1, 'client.name', 'client');

    data_set($col1, 'idbarcode.class', 'csidbarcode sbccsreadonly');
    data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');
    data_set($col1, 'level.type', 'cinput');
    data_set($col1, 'hired.class', 'cshired sbccsreadonly');
    if ($companyid == 53) { // camera
      data_set($col1, 'approvercode.type', 'input');
    }

    $fields = [['paymode', 'classrate'], ['emptype', 'tpaygroupname'], 'rolename', 'divname', 'deptname', 'sectionname', 'lblstatus', 'supervisorcode', 'supervisor'];

    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'paymode.type', 'cinput');
    data_set($col2, 'tpaygroupname.name', 'paygroupname');
    data_set($col2, 'tpaygroupname.type', 'cinput');
    data_set($col2, 'rolename.type', 'cinput');
    data_set($col2, 'divname.label', 'Company');
    data_set($col2, 'sectionname.label', 'Section');
    data_set($col2, 'lblstatus.label', "Supervisor:");
    data_set($col2, 'lblstatus.type', 'label');
    data_set($col2, 'lblstatus.style', 'font-weight:bold;font-size:11px;');
    data_set($col2, 'supervisorcode.type', 'input');
    data_set($col2, 'supervisorcode.class', 'cssupervisorcode sbccsreadonly');
    data_set($col2, 'supervisor.class', 'cssupervisor sbccsreadonly');
    data_set($col2, 'lblrem.label', 'OB Approver:');
    data_set($col2, 'lblrem.style', 'font-weight:bold;font-size:11px;');

    $fields = ['lblTaxStatus', ['radioteu', 'nodeps'], ['mstatus', 'child'], 'maidname', ['bday', 'age'], ['gender', 'alias'], ['mobileno', 'telno'], ['citizenship', 'religion'], 'email'];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'radioteu.label', 'Tax Status');
    data_set($col3, 'nodeps.type', 'cinput');
    data_set($col3, 'radioteu.class', 'csradioteu sbccsreadonly');
    data_set($col3, 'child.name', 'nochild');
    data_set($col3, 'alias.label', 'Nickname');


    $fields = ['addr', 'permanentaddr'];
    if ($companyid == 58) { // cdohris
      array_push($fields, 'lbllocked');
    }
    $col4 = $this->fieldClass->create($fields);

    data_set($col4, 'addr.label', 'Present Address');
    if ($companyid == 58) { // cdohris
      data_set($col4, 'lbllocked.label', 'Locked');
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
    $data[0]['jobtitle'] = '';
    $data[0]['jobcode'] = '';
    $data[0]['jobdesc'] = '';
    $data[0]['remarks'] = '';
    $data[0]['isapprover'] = '0';
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
    $data[0]['paygroup'] = '';
    $data[0]['isemployee'] = '1';
    $data[0]['radioteu'] = 'S';

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
    $data[0]['paygroup'] = 0;
    $data[0]['paygroupname'] = '';


    if ($config['params']['companyid'] == 58) { //cdo
      $data[0]['radiobank'] = 1;
      $data[0]['isbank'] = 0;
    }
    $data[0]['approvercode'] = 0;
    $data[0]['approver'] = '';
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
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
    $hideobj = [];

    $jobtitle = 'jt.jobtitle';
    if ($companyid == 53) {
      $jobtitle = 'employee.jobtitle';
    }

    unset($this->fieldsOther[4]); // hired
    unset($this->fieldsOther[5]); // resigned
    $fields = "client.client,client.clientid,client.clientname,client.client as empcode,
    " . $this->headOther . ".empid,'' as atype, year(now())-year(" . $this->headOther . ".bday) as age,
    '' as djobtitle,employee.teu as radioteu,
    case 
      when " . $this->headOther . ".paymode = 'S' then 'Semi-monthly' 
      when " . $this->headOther . ".paymode = 'W' then 'Weekly' 
      when " . $this->headOther . ".paymode = 'M' then 'Monthly' 
      when " . $this->headOther . ".paymode = 'D' then 'Daily' 
      when " . $this->headOther . ".paymode = 'P' then 'Piece Rate' 
      else '' 
    end as paymode,
    case 
      when " . $this->headOther . ".classrate = 'D' then 'Daily' 
      when " . $this->headOther . ".classrate = 'M' then 'Monthly' 
      else '' 
    end as classrate,
    case 
      when YEAR(" . $this->headOther . ".hired) > '1970' then " . $this->headOther . ".hired
      else '' 
    end as hired,
    case 
      when YEAR(" . $this->headOther . ".resigned) > '1970' then " . $this->headOther . ".resigned
      else '' 
    end as resigned,
    dept.client as dept, dept.clientname as deptname,
    ifnull(`div`.divcode, '') as division,
    ifnull(`div`.divname, '') as divname,ifnull(sect.sectcode, '') as orgsection,ifnull(sect.sectname, '') as sectionname,$jobtitle,
    jt.docno as jobcode,group_concat(jd.description) as jobdesc, ts.shftcode as shiftcode,
    supervisor.clientid as supervisorid, 
    supervisor.client as supervisorcode, 
    supervisor.clientname as supervisor,
    empstat.empstatus as empstatusname, " . $this->headOther . ".idbarcode,
    role.name as rolename, " . $this->headOther . ".roleid,
    paygroup.paygroup as paygroupname , " . $this->headOther . ".isbank as radiobank,ifnull(branch.clientname,'') as branch,
    ifnull(app.clientname,'') as approver,ifnull(app.client,'') as approvercode, " . $this->headOther . ".lockdate";

    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',' . $this->head . '.' . $value;
    }

    foreach ($this->fieldsOther as $key => $value) {
      $fields = $fields . ',' . $this->headOther . '.' . $value;
    }

    foreach ($this->contactfields as $key => $value) {
      $fields = $fields . ',' . $this->contact . '.' . $value;
    }

    $qryselect = "select concat(" . $this->head . ".addr,', ',
    employee.city,', ',employee.country, ' ',employee.zipcode) as address," . $fields;

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
    left join empstatentry as empstat on empstat.line = " . $this->headOther . ".empstatus
    left join rolesetup as role on role.line = " . $this->headOther . ".roleid
    left join paygroup on paygroup.line = " . $this->headOther . ".paygroup
    left join client as branch on branch.clientid = " . $this->headOther . ".branchid
    left join client as app on app.clientid = " . $this->headOther . ".approver1
    where " . $this->head . ".clientid = ? 
    Group by clientid, employee.empid, atype, djobtitle, radioteu, employee.paymode, employee.dept, deptname,
    employee.division, div.divname, employee.orgsection, sect.sectname, divname, sectionname, jobtitle, employee.jobcode,
    employee.shiftcode, dept.client, clientname, employee.bday, employee.email, isemployee, addr, picture,
    emplast, empfirst, empfirst, empmiddle, hired, city, country, telno, mobileno, citizenship,
    maidname, gender, remarks, status, zipcode, religion, alias, jobid, level, isactive, lastbatch, mapp,
    agency, empstat.empstatus, aplcode, jgrade, emprank, emploc, emptype, resigned, regular, prob, idbarcode, tin,
    sss, phic, hdmf, bankacct, atm, employee.paymode, classrate, emprate, teu, nodeps, chksss, chktin, chkphealth,
    chkpibig, dyear, sssdef, philhdef, pibigdef, wtaxdef, shiftid, blood, paygroup, cola, divid,
    deptid, sectid, contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1, notes1, contact2, relation2,
    addr2, homeno2, mobileno2, officeno2, ext2, notes2,client.client,div.divcode,sect.sectcode,jt.docno,ts.shftcode,client.bday,client.email,
    supervisor.clientid, supervisor.client, supervisor.clientname, employee.supervisorid, isapprover, rolename, roleid, nochild,trainee,
    paygroup.paygroup,isbank,branch,employee.permanentaddr,app.client,app.clientname,employee.lockdate";
    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }

      if ($companyid == 58) { //cdohris
        $hideobj['lbllocked'] = true;
        $lock = $head[0]->lockdate;
        if ($lock != null) {
          $hideobj['lbllocked'] = false;
        }
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'qry' => $qry, 'hideobj' => $hideobj];
    } else {
      $head[0]['empid'] = 0;
      $head[0]['clientid'] = 0;
      $head[0]['empcode'] = '';
      $head[0]['client'] = '';
      $head[0]['emplast'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...', 'hideobj' => $hideobj];
    }
  }

  public function updatehead($config, $isupdate)
  {
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
          $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key]);
        } //end if  
      }
    }

    if (isset($head['radioteu'])) {
      $dataOther['teu'] = $head['radioteu'];
    }

    if (isset($head['paymode'])) {
      $dataOther['paymode'] = substr($head['paymode'], 0, 1);
    }

    if (isset($head['classrate'])) {
      $dataOther['classrate'] = substr($head['classrate'], 0, 1);
    }

    $dataOther['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $dataOther['editby'] = $config['params']['user'];
    $dataOther['lockdate'] = $this->othersClass->getCurrentTimeStamp();

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $data['clientname'] = $head['emplast'] . ', ' . $head['empfirst'] . ' ' . $head['empmiddle'];
    $data['addr'] = $head['addr'];

    if ($isupdate) {
      if ($companyid == 58) { //cdohris
        $lockexist = $this->coreFunctions->getfieldvalue($this->headOther, "lockdate", "empid=?", [$head['empid']]);
        if ($lockexist != null) {
          $msg = 'Already Locked';
        }

        return ['status' => false, 'msg' => $msg, 'clientid' => $clientid];
      }
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
      } else {
        $dataOther['empid'] = $head['empid'];
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
      }

      $clientid = $head['empid'];
      $empid = $head['empid'];
    } else {
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
} //end class
