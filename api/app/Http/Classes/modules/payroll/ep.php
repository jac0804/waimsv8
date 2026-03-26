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

class ep
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'EMPLOYEE RECORD';
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

    private $fields = [
        'client',
        'clientname',
        'isemployee',
        'addr',
        'picture',
        'isinactive'
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
        'bday',
        'status',
        'zipcode',
        'email',
        'religion',
        'isactive',
        'salarytype',
        'regular',
        'prob',
        'probend',
        'idbarcode',
        'tin',
        'sss',
        'phic',
        'hdmf',
        'bankacct',
        'atm',
        'emprate',
        'bank',

        'blood',
        'paygroup',
        'cola',
        'divid',
        'deptid',
        'sectid',

        'roleid',
        'nochild',
        'biometricid',
        'isbank',
        'permanentaddr',
        'empnoref',
        'callsign',
        'hmoname',
        'hmoaccno',
        'validity',
        'effectdate',
        'homeno3',
        'city2',
        'country2',
        'zipcode2',
        'isapprover',
        'issupervisor',
        'isbudgetapprover',
        'isnobio',
        'chksss',
        'chktin',
        'chkphealth',
        'chkpibig',
        'aplcode',
        'jgrade',
        'emploc',
        'supervisorid',
        'shiftid',
        'jobid',
        'empstatus',
        'branchid',

        'level',
        'lastbatch',
        'dyear',
        'sssdef',
        'philhdef',
        'pibigdef',
        'wtaxdef',
        'branchid2',
        'roleid2',
        'jobid2',
        'emploc2',
        'resignedtype',
        'contricompid'
    ];

    //   'branchid2',
    //     'supervisorid2',
    //     'jobid2',
    //     'emploc2'
    private $except = ['empid', 'age', 'clientid', 'paymode', 'division', 'dept', 'aplcode ', ' jgrade ', 'emploc'];
    private $blnfields = ['isemployee', 'isactive', 'atm',   'isinactive', 'isapprover', 'issupervisor', 'isbudgetapprover', 'chksss', 'chktin', 'chkphealth', 'chkpibig', 'isnobio'];
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
        $attrib = array(
            'view' => 5392,
            'edit' => 5402,
            'new' => 5403,
            'save' => 5404,
            'change' => 5405,
            'delete' => 5406,
            'print' => 5407
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];
        $getcols = ['action', 'listclient', 'emplast', 'empfirst', 'empmiddle', 'listaddr', 'jobtitle', 'hired', 'bday'];
        $stockbuttons = ['view'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$hired]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
        // $cols[$empmiddle]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$hired]['align'] = 'text-left';
        $cols[$bday]['align'] = 'text-left';
        $cols[$jobtitle]['label'] = 'Designation';
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

        if (isset($config['params']['doclistingparam'])) {
            $empstatus = $config['params']['doclistingparam'];
            if ($empstatus['resigned'] == 'ALL') {
                $active = "";
            } else {
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
        }


        $paygroup = 'paygroup';

        if ($id != 0) {
            if ($viewaccess == '0') {
                $condition = " and (emp.supervisorid = $id or emp.empid=$id) ";
            }
        }

        $qry = "select cl.clientid,cl.client,cl.clientname,cl.addr,emp.emplast,emp.empfirst,emp.empmiddle,emp.paymode, paygroup." . $paygroup . " as paygroup, 
        date(emp.hired) as hired, date(emp.bday) as bday,ifnull(job.jobtitle,'') as jobtitle
        from  " . $this->head . " as cl 
        left join employee as emp on emp.empid = cl.clientid
        left join paygroup on paygroup.line = emp.paygroup
        left join jobthead as job on job.line=emp.jobid
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
            'toggledown'
        );

        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton
    public function paramsdatalisting($config)
    {
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
    }

    public function createTab($access, $config)
    {
        $fields = ['lbllocation', 'isactive', 'hired', 'empdesc', 'emploc'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'lbllocation.label', 'Inactive Status');
        data_set($col1, 'lbllocation.style', 'font-weight:bold; font-size:15px;');
        data_set($col1, 'hired.style', 'font-weight:bold; font-size:15px;');
        data_set($col1, 'empdesc.lookupclass', 'empstatus');
        data_set($col1, 'empdesc.label', 'Employment Status');
        data_set($col1, 'emploc.type', 'lookup');
        data_set($col1, 'emploc.action', 'holidaylookuploc');
        data_set($col1, 'emploc.lookupclass', 'loookupholidayemploc');
        data_set($col1, 'emploc.class', 'csemploc sbccsenablealways');

        $fields = ['resignedtype', ['lastbatch', 'resigned']];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['lblrem', 'regular', 'lblsource', ['prob', 'probend']];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'lblrem.label', 'Effective date of Regular');
        data_set($col3, 'lblrem.style', 'font-weight:bold; font-size:15px;');
        data_set($col3, 'regular.style', 'font-weight:bold; font-size:15px;');
        data_set($col3, 'lblsource.label', 'Probationary Date Range');
        data_set($col3, 'lblsource.style', 'font-weight:bold; font-size:15px;');
        data_set($col3, 'prob.label', 'Start Date');
        data_set($col3, 'probend.label', 'End Date');

        $fields = ['lblreconcile', ['tin', 'chktin'], ['sss', 'chksss'], ['phic', 'chkphealth'], ['hdmf', 'chkpibig']];
        $col4 = $this->fieldClass->create($fields);

        data_set($col4, 'lblreconcile.label', 'Government');
        data_set($col4, 'lblreconcile.style', 'font-weight:bold; font-size:25px; display:inline-block; width:100%; text-align:center;');

        data_set($col4, 'tin.type', 'cinput');
        data_set($col4, 'sss.type', 'cinput');
        data_set($col4, 'phic.type', 'cinput');
        data_set($col4, 'hdmf.type', 'cinput');

        $fields = ['lblrecondate', 'hmoname', 'hmoaccno', 'validity'];
        $col5 = $this->fieldClass->create($fields);

        data_set($col5, 'lblrecondate.label', 'HMO');
        data_set($col5, 'lblrecondate.style', 'font-weight:bold; font-size:25px; display:inline-block; width:100%; text-align:center;');


        $fields = ['lblbranch', ['dyear', 'cola'], ['sssdef', 'philhdef'], ['pibigdef', 'wtaxdef']];

        $col6 = $this->fieldClass->create($fields);
        data_set($col6, 'dyear.type', 'cinput');
        data_set($col6, 'cola.type', 'cinput');
        data_set($col6, 'sssdef.type', 'cinput');
        data_set($col6, 'philhdef.type', 'cinput');
        data_set($col6, 'pibigdef.type', 'cinput');
        data_set($col6, 'wtaxdef.type', 'cinput');

        data_set($col6, 'lblbranch.label', '.');
        data_set($col6, 'lblbranch.style', 'font-weight:bold; font-size:22px; display:inline-block; width:100%; text-align:center; color:white;'); // color:white;


        $fields = ['lblearned',   'paymode',   'classrate',   'effectdate', 'salarytype',  'basicrate', 'tpaygroupname'];
        $col7 = $this->fieldClass->create($fields);

        data_set($col7, 'lblearned.label', 'Compensation');
        data_set($col7, 'lblearned.style', 'font-weight:bold; font-size:25px; display:inline-block; width:100%; text-align:center;');

        data_set($col7, 'salarytype.type', 'input');
        data_set($col7, 'salarytype.class', 'cssalarytpe');

        data_set($col7, 'classrate.type', 'lookup');
        data_set($col7, 'classrate.action', 'lookupclassrate');
        data_set($col7, 'classrate.lookupclass', 'lookupclassrate');
        data_set($col7, 'tpaygroupname.name', 'paygroupname');

        data_set($col7, 'tpaygroupname.type', 'lookup');
        data_set($col7, 'tpaygroupname.action', 'paygrouplookup');
        data_set($col7, 'tpaygroupname.lookupclass', 'tpaygrouplookup');

        $fields = ['lblbank', 'bank', ['bankacct', 'atm'], 'divrep'];
        $col8 = $this->fieldClass->create($fields);

        data_set($col8, 'bank.type', 'lookup');
        data_set($col8, 'bank.action', 'lookupbanktype');
        data_set($col8, 'bank.lookupclass', 'lookupbanktype');
        data_set($col8, 'bank.class', 'csbank sbccsreadonly');
        data_set($col8, 'divrep.label', 'Contributing Company');
        data_set($col8, 'divrep.lookupclass', 'lookupcontributecomp');
        data_set($col8, 'lblbank.style', 'font-weight:bold; font-size:25px; display:inline-block; width:100%; text-align:center;');


        $fields = ['rolename', 'divname', 'deptname', 'sectionname'];
        if ($config['params']['user'] != 'sbc') {
            $fields = [];
        } else {
            data_set($col8, 'rolename.type', 'input');
        }
        $col9 = $this->fieldClass->create($fields);

        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2], 'label' => 'EMPLOYMENT STATUS'],
            'multiinput2' => ['inputcolumn' => ['col1' => $col3], 'label' => 'WORK STATUS'],
            'designationtab' => ['action' => 'tableentry', 'lookupclass' => 'tabdesignation', 'label' => 'DESIGNATION'],
            'multiinput4' => ['inputcolumn' => ['col1' => $col4, 'col2' => $col5, 'col6' => $col6], 'label' => 'BENEFITS'],
            'multiinput5' => ['inputcolumn' => ['col1' => $col7, 'col2' => $col8, 'col9' => $col9], 'label' => 'PAYROLL']

        ];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createTab2($access, $config)
    {

        $rate_access = $this->othersClass->checkAccess($config['params']['user'], 5300);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempeducation', 'label' => 'EDUCATION']];
        $education = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappdependents', 'label' => 'FAMILY TREE']];
        $dependants = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'contactinfoentry', 'label' => 'CONTACT INFORMATION']];
        $contactinfo = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrywhdocuments', 'label' => 'LICENCES']];
        $license = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempemployment', 'label' => 'EMPLOYMENT']];
        $empemployment = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemprate', 'label' => 'RATE']];
        $rate = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemptraining', 'label' => 'TRAINING']];
        $training = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryclientpicture', 'label' => 'Attachment', 'access' => 'view']];
        $attach = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempadvances', 'label' => 'ADVANCES']];
        $advances = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemploans', 'label' => 'LOANS']];
        $loans = $this->tabClass->createtab($tab, []);


        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempturnover', 'label' => 'TURN-OVER/RETURN ITEMS']];
        $turnover = $this->tabClass->createtab($tab, []);

        $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewuseraccount']];

        $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytabrole', 'label' => 'ROLE SETUP']];
        $rolesetup = $this->tabClass->createtab($tab, []);

        $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewallowancesetupdetails', 'label' => 'ALLOWANCE']];
        $allowance = $this->tabClass->createtab($tab, []);

        //viewallowancesetupdetails


        // $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewemprate', 'label' => 'RATE']];
        // $rate = $this->tabClass->createtab($tab, []);

        // $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryempcontract', 'label' => 'CONTRACT']];
        // $contract = $this->tabClass->createtab($tab, []);

        // $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'viewempallowances', 'label' => 'ALLOWANCE']];
        // $allowance = $this->tabClass->createtab($tab, []);

        $return = [];
        $return['EDUCATION'] = ['icon' => 'fa fa-book-open', 'tab' => $education];
        $return['FAMILY TREE'] = ['icon' => 'fa fa-house-user', 'tab' => $dependants];

        $return['CONTACT INFORMATION'] = ['icon' => 'fa fa-address-book', 'tab' => $contactinfo];
        $return['LICENSES'] = ['icon' => 'fa fa-id-badge', 'tab' => $license];
        $return['ADVANCES'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $advances];
        $return['LOANS'] = ['icon' => 'fa fa-money-bill-wave', 'tab' => $loans];

        $return['EMPLOYMENT'] = ['icon' => 'fa fa-user-tie', 'tab' => $empemployment];
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];

        if ($rate_access != 0) {
            $return['RATE'] = ['icon' => 'fa fa-money-bill', 'tab' => $rate];
            $return['ALLOWANCE'] = ['icon' => 'fa fa-money-bill', 'tab' => $allowance];
        }

        // $return['CONTRACT'] = ['icon' => 'fa fa-file-signature', 'tab' => $contract];
        // $return['ALLOWANCE'] = ['icon' => 'fa fa-coins', 'tab' => $allowance];
        $return['TRAINING'] = ['icon' => 'fa fa-list-ul', 'tab' => $training];
        $return['TURN-OVER/RETURN ITEMS'] = ['icon' => 'fa fa-exchange-alt', 'tab' => $turnover];
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
        if ($this->getisaprover($config) != 0) {
            $return['ROLE SETUP'] = ['icon' => 'fa fa-users', 'tab' => $rolesetup];
        }
        // $companyid = $config['params']['companyid'];
        // switch ($companyid) {
        //     case 3: // conti remove loans, advances.
        //         unset($return['LOANS']);
        //         unset($return['ADVANCES']);
        //         break;
        // }

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
        // $companyid = $config['params']['companyid'];
        // $systemtype = $this->companysetup->getsystemtype($config['params']);

        // $fields = ['lblgrossprofit', 'client', 'emplast', 'empfirst', 'empmiddle', 'addr', 'permanentaddr', ['city', 'country'], ['zipcode', 'blood'], ['citizenship', 'religion'], ['telno', 'mobileno']];
        $fields = ['lblgrossprofit', 'client', 'emplast', 'empfirst', 'empmiddle', 'lblshipping', ['telno', 'addr'], 'lblbilling', ['htel', 'permanentaddr'], 'supervisorcode'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'lblgrossprofit.label', 'Personal Details');
        data_set($col1, 'lblgrossprofit.style', 'font-weight:bold; font-size:15px;');

        data_set($col1, 'client.class', 'csclient sbccsenablealways');
        data_set($col1, 'client.lookupclass', 'lookupclient');
        data_set($col1, 'client.action', 'lookupledgerclient');
        data_set($col1, 'client.label', 'Employee Code');
        data_set($col1, 'client.name', 'client');

        data_set($col1, 'emplast.type', 'cinput');
        data_set($col1, 'empfirst.type', 'cinput');
        data_set($col1, 'empmiddle.type', 'cinput');

        data_set($col1, 'lblshipping.label', 'Present Address');
        data_set($col1, 'lblshipping.style', 'font-weight:bold; font-size:15px;');


        data_set($col1, 'addr.type', 'cinput');
        data_set($col1, 'addr.label', 'Present Address');
        data_set($col1, 'telno.type', 'cinput');

        data_set($col1, 'lblbilling.label', 'Permanent Address');
        data_set($col1, 'lblbilling.style', 'font-weight:bold; font-size:15px;');

        data_set($col1, 'permanentaddr.type', 'cinput');

        data_set($col1, 'htel.maxlength', '20');
        data_set($col1, 'htel.label', 'Home No.');
        data_set($col1, 'htel.name', 'homeno3');
        data_set($col1, 'supervisorcode.type', 'input');
        data_set($col1, 'supervisorcode.required', false);

        $fields = ['lblcostuom', ['age', 'gender'], 'bday', ['mstatus', 'child'],  'maidname', 'lblacquisition', 'city', 'lbldepreciation', 'rcity', 'supervisor'];

        $col2 = $this->fieldClass->create($fields);


        data_set($col2, 'lblcostuom.label', '.');
        data_set($col2, 'lblcostuom.style', 'font-weight:bold; font-size:15px; color:white;');

        data_set($col2, 'rcity.name', 'city2');

        // data_set($col2, 'age.type', 'input');
        // // data_set($col2, 'age.class', 'csage sbccsreadonly');
        data_set($col2, 'age.readonly', false);

        data_set($col2, 'child.name', 'nochild');
        data_set($col2, 'city.type', 'cinput');

        data_set($col2, 'maidname.type', 'cinput');
        data_set($col2, 'maidname.readonly', false);

        data_set($col2, 'lblacquisition.label', '.');
        data_set($col2, 'lblacquisition.style', 'font-weight:bold; font-size:15px; color:white;');


        data_set($col2, 'lbldepreciation.label', '.');
        data_set($col2, 'lbldepreciation.style', 'font-weight:bold; font-size:15px; color:white;');

        data_set($col2, 'rcity.type', 'cinput');
        data_set($col2, 'rcity.label', 'City/State');


        data_set($col2, 'supervisor.type', 'input');
        data_set($col2, 'supervisor.class', 'cssupervisor sbccsreadonly');
        data_set($col2, 'supervisor.required', false);

        $fields = [
            'lblunclear',
            ['blood', 'mobileno'],
            ['religion', 'idbarcode'],
            ['citizenship', 'empnoref'],
            'email',
            'lblcleared',
            ['country', 'zipcode'],
            'lblendingbal',
            ['rcountry', 'rzipcode'],
            ['level', 'shiftcode']
        ];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'empnoref.label', 'Employee No.');

        data_set($col3, 'lblunclear.label', '.');
        data_set($col3, 'lblunclear.style', 'font-weight:bold; font-size:15px; color:white;');

        data_set($col3, 'blood.type', 'cinput');
        data_set($col3, 'mobileno.type', 'cinput');
        data_set($col3, 'religion.type', 'cinput');
        data_set($col3, 'idbarcode.type', 'cinput');
        data_set($col3, 'citizenship.type', 'cinput');

        data_set($col3, 'country.type', 'cinput');
        data_set($col3, 'lblcleared.label', '.');
        data_set($col3, 'lblcleared.style', 'font-weight:bold; font-size:15px; color:white;');

        data_set($col3, 'lblendingbal.label', '.');
        data_set($col3, 'lblendingbal.style', 'font-weight:bold; font-size:15px; color:white;');

        data_set($col3, 'rcountry.name', 'country2');
        data_set($col3, 'rcountry.type', 'cinput');
        data_set($col3, 'rzipcode.name', 'zipcode2');
        data_set($col3, 'rzipcode.type', 'cinput');

        data_set($col3, 'email.type', 'cinput');
        data_set($col3, 'shiftcode.class', 'csshiftcode sbccsreadonly');

        $fields = ['lbltotalkg', 'picture', 'callsign', 'isapprover', 'issupervisor', 'isbudgetapprover', 'isnobio',  'empjoboffer'];


        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'lbltotalkg.label', '.');
        data_set($col4, 'lbltotalkg.style', 'font-weight:bold; font-size:15px; color:white;');
        data_set($col4, 'picture.lookupclass', 'client');
        data_set($col4, 'picture.folder', 'employee');
        data_set($col4, 'picture.table', 'client');
        data_set($col4, 'picture.fieldid', 'clientid');
        data_set($col4, 'isapprover.label', 'Approver/HR');

        data_set($col4, 'empjoboffer.label', 'Generate ID Number');
        data_set($col4, 'empjoboffer.action', 'generateidno');
        data_set($col4, 'empjoboffer.ico', 'generateidno');
        unset($col4['empjoboffer']['icon']);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function newclient($config)
    {
        $shiftid = $this->coreFunctions->datareader("select line as value from tmshifts where isdefault=1 LIMIT 1");
        $shiftcode = $this->coreFunctions->getfieldvalue('tmshifts', "shftcode", "line=?", [$shiftid]);
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

        $data[0]['salarytype'] = '';
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

        $data[0]['gender'] = '';
        $data[0]['status'] = '';
        $data[0]['nochild'] = 0;

        $data[0]['terminal'] = '';

        $data[0]['bday'] = null;
        $data[0]['bplace'] = '';
        $data[0]['age'] = 0;
        $data[0]['email'] = '';

        $data[0]['zipcode'] = '';
        $data[0]['isactive'] = '1';

        $data[0]['resigned'] = null;
        $data[0]['regular'] = null;
        $data[0]['prob'] = null;
        $data[0]['probend'] = null;

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

        $data[0]['cola'] = 0;
        $data[0]['division'] = '';
        $data[0]['divname'] = '';
        $data[0]['dept'] = '';
        $data[0]['deptname'] = '';

        $data[0]['sectionname'] = '';


        $data[0]['supervisorid'] = 0;
        $data[0]['supervisor'] = '';
        $data[0]['supervisorcode'] = '';

        $data[0]['blood'] = '';
        $data[0]['paygroup'] = 0;
        $data[0]['paygroupname'] = '';
        $data[0]['tpaygroupname'] = '';

        $data[0]['isemployee'] = 1;

        $data[0]['isinactive'] = '0';
        $data[0]['resignedtype'] = '';

        $data[0]['empnoref'] = '';
        $data[0]['callsign'] = '';

        $data[0]['bank'] = '';
        $data[0]['isbank'] = 0;

        $data[0]['hmoname'] = '';
        $data[0]['hmoaccno'] = '';
        $data[0]['validity'] = '';
        $data[0]['effectdate'] = $this->othersClass->getCurrentDate();

        $data[0]['homeno3'] = '';
        $data[0]['city2'] = '';
        $data[0]['country2'] = '';
        $data[0]['zipcode2'] = '';

        $data[0]['isapprover'] = '0';
        $data[0]['issupervisor'] = '0';
        $data[0]['isbudgetapprover'] = '0';
        $data[0]['isbudgetapprover'] = '0';

        $data[0]['chksss'] = '0';
        $data[0]['chktin'] = '0';
        $data[0]['chkphealth'] = '0';
        $data[0]['chkwtax'] = '0';
        $data[0]['chkpibig'] = '0';

        $data[0]['jobid'] = 0;
        $data[0]['jobtitle'] = '';
        $data[0]['jobcode'] = '';
        $data[0]['jobdesc'] = '';
        $data[0]['aplcode'] = '';
        $data[0]['jgrade'] = '';
        $data[0]['emploc'] = '';

        $data[0]['shiftcode'] = $shiftcode;
        $data[0]['shiftid'] =  $shiftid;
        $data[0]['empstatus'] = '';
        $data[0]['empstatusname'] = '';

        $data[0]['branchid'] = 0;
        $data[0]['branchcode'] = '';
        $data[0]['branchname'] = '';


        $data[0]['level'] = 10;

        $data[0]['lastbatch'] = '';

        $data[0]['dyear'] = 0;
        $data[0]['sssdef'] = 0;
        $data[0]['philhdef'] = 0;
        $data[0]['pibigdef'] = 0;
        $data[0]['wtaxdef'] = 0;
        $data[0]['contricompid'] = 0;
        $data[0]['divrep'] = '';

        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'generateidno':
                return $this->generateidno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function generateidno($config)
    {
        $clientid = $config['params']['trno'];
        $user = $config['params']['user'];

        $empidno = $this->coreFunctions->getfieldvalue($this->headOther, "empnoref", "empid=?", [$clientid]);
        if ($empidno != "" && $empidno != null) {
            return ['status' => false, 'msg' => 'Employee No. ' .  $empidno . ' already generated.'];
        }

        $hired = $this->coreFunctions->getfieldvalue($this->headOther, "hired", "empid=?", [$clientid]);
        if ($hired == null || $hired == '0000-00-00') {
            return ['status' => false, 'msg' => 'Please set hired date first before generating Employee No.'];
        }

        $client = $this->coreFunctions->datareader("select right(client,6) as value from client where clientid=?", [$clientid], '', true);
        $prefix = date('ymd', strtotime($hired));

        $newidno = $prefix . '-' . str_pad($client, 6, '0', STR_PAD_LEFT);

        $this->coreFunctions->LogConsole('idno-' . $newidno);

        $existidno = $this->coreFunctions->getfieldvalue($this->headOther, "empnoref", "empnoref=?", [$newidno]);
        if ($existidno != null && $existidno != '') {
            return ['status' => false, 'msg' => 'Generated Employee No. ' .  $newidno . ' already exist. Please contact system administrator.'];
        } else {
            $this->coreFunctions->sbcupdate($this->headOther, ['empnoref' => $newidno, 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $user], ['empid' => $clientid]);
            $this->logger->sbcwritelog($clientid, $config, 'HEAD', "Generate Employee ID No. " . $newidno);
        }

        return ['status' => true, 'msg' => 'Employee No. generated', 'backlisting' => true];
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $clientid = $config['params']['clientid'];
        $center = $config['params']['center'];

        $paygroup = 'paygroup.paygroup';
        $groupbypaygroup = 'paygroup.paygroup';

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
        $fields = "client.client,client.clientid,client.client as empcode," . $this->headOther . ".empid,
               '' as atype, year(now())-year(" . $this->headOther . ".bday) as age,
               
               case when " . $this->headOther . ".paymode = 'S' then 'Semi-monthly' 
                  when " . $this->headOther . ".paymode = 'W' then 'Weekly' 
                  when " . $this->headOther . ".paymode = 'M' then 'Monthly' 
                  when " . $this->headOther . ".paymode = 'D' then 'Daily' 
                  when " . $this->headOther . ".paymode = 'P' then 'Piece Rate' 
                  else '' end as paymode,
               case when " . $this->headOther . ".classrate = 'D' then 'Daily' 
                  when " . $this->headOther . ".classrate = 'M' then 'Monthly' 
                  when " . $this->headOther . ".classrate = 'P' then 'Package Rate' 
                  else '' end as classrate,
               case when YEAR(" . $this->headOther . ".hired) > '1970' then date(" . $this->headOther . ".hired)
                  else '' end as hired,
               case when YEAR(" . $this->headOther . ".resigned) > '1970' then " . $this->headOther . ".resigned
               else '' end as resigned,

               " . $this->headOther . ".roleid2,
               role.name as rolename2,
               dept.client as dept2, dept.clientname as deptname2,
               ifnull(`div`.divcode, '') as division2,ifnull(`div`.divname, '') as divname2,
               ifnull(sect.sectname, '') as sectionname2,
                concat(jt.docno,'~',jt.jobtitle) as fjobtitle,
                ifnull(branch.clientname,'') as detail,ifnull(branch.client,'') as branchcode,

                " . $this->headOther . ".roleid,
               role2.name as rolename,
               dept2.client as dept, dept2.clientname as deptname,
               ifnull(div2.divcode, '') as division,ifnull(div2.divname, '') as divname,
               ifnull(sect2.sectname, '') as sectionname,
                jt2.jobtitle, jt2.docno as jobcode,group_concat(jd.description) as jobdesc,

               ifnull(branch2.clientname,'') as branchname,
               ifnull(" . $this->headOther . ".emploc2,'') as vehiclename,
                
               ts.shftcode as shiftcode,
               supervisor.clientid as supervisorid,supervisor.client as supervisorcode, 
               supervisor.clientname as supervisor, empstat.empstatus as empdesc, 
               " . $this->headOther . ".idbarcode, 
                " . $paygroup . " as paygroupname," . $paygroup . " as tpaygroupname,
               biometric.terminal as biometric,
               employee.bank as radiobank,
                ifnull(" . $this->headOther . ".homeno3,'') as homeno3, 
                ifnull(" . $this->headOther . ".city2,'') as city2, 
                ifnull(" . $this->headOther . ".country2,'') as country2, 
                ifnull(" . $this->headOther . ".zipcode2,'') as zipcode2, 
                ifnull(" . $this->headOther . ".hmoname,'') as hmoname ,
                ifnull(" . $this->headOther . ".hmoaccno,'') as hmoaccno,
                ifnull(" . $this->headOther . ".validity,'') as validity,
                date(" . $this->headOther . ".effectdate) as effectdate,
                (select basicrate from ratesetup where empid=" . $this->headOther . ".empid and year(dateend)= '9999'  order by trno desc limit 1) as basicrate,
               
                ifnull(" . $this->headOther . ".homeno3,'') as homeno3,
                " . $this->headOther . ".bank,contricomp.divname as divrep";


        foreach ($this->fields as $key => $value) {
            $fields = $fields . ',' . $this->head . '.' . $value;
        }

        foreach ($this->fieldsOther as $key => $value) {
            $fields = $fields . ',' . $this->headOther . '.' . $value;
        }

        $qryselect = "select " . $fields;

        $qry = $qryselect . " from " . $this->head . " 
        left join " . $this->headOther . " on " . $this->headOther . ".empid = " . $this->head . ".clientid 
        left join " . $this->contact . " on " . $this->contact . ".empid=" . $this->head . ".clientid 

        left join rolesetup as role on role.line = " . $this->headOther . ".roleid
        left join division as `div` on `div`.divid = role.divid 
        left join client as dept on dept.clientid = role.deptid 
        left join section as sect on sect.sectid = role.sectionid 

        left join rolesetup as role2 on role2.line= " . $this->headOther . ".roleid2
        left join division as div2 on div2.divid = role2.divid 
        left join client as dept2 on dept2.clientid = role2.deptid
        left join section as sect2 on sect2.sectid = role2.sectionid
        left join client as branch2 on branch2.clientid = " . $this->headOther . ".branchid2
        left join client as supervisor2 on supervisor2.clientid = role2.supervisorid
        left join jobthead as jt2 on jt2.line = " . $this->headOther . ".jobid2
       left join jobtdesc as jd on jd.trno =  jt2.line

         left join jobthead as jt on jt.line = " . $this->headOther . ".jobid 
        
         left join tmshifts as ts on ts.line = " . $this->headOther . ".shiftid 
         left join client as supervisor on supervisor.clientid = " . $this->headOther . ".supervisorid
         left join empstatentry as empstat on empstat.line = " . $this->headOther . ".empstatus

        left join client as branch on branch.clientid = " . $this->headOther . ".branchid

        left join paygroup on paygroup.line = " . $this->headOther . ".paygroup
        left join biometric on biometric.line = " . $this->headOther . ".biometricid
        left join division as contricomp on contricomp.divid= " . $this->headOther . ".contricompid

        where " . $this->head . ".clientid = ? 
        group by clientid, employee.empid, atype,  employee.paymode, employee.dept, deptname,
        employee.division, div.divname, sect.sectname, divname, sectionname,jobtitle, employee.jobcode,
        employee.shiftcode,
        dept.client, clientname, employee.bday, employee.email, isemployee, addr, picture,
        emplast, empfirst, empfirst, empmiddle, hired, city, country, telno, mobileno, citizenship,
        maidname, gender, status, zipcode, religion,jobid, isactive,empstat.empstatus, aplcode, jgrade,
        emploc,salarytype, resigned, regular, prob,probend, idbarcode, tin,
        sss, phic, hdmf, bankacct, atm, employee.paymode, employee.classrate, emprate, blood, paygroup, 
        paygroup.paygroup, cola, divid,
        deptid, sectid,  permanentaddr,client.client,div.divcode,sect.sectcode,client.bday,
        isemployee,isinactive,rolename, roleid, nochild,
        biometricid,biometric.terminal,
        isbank,empnoref, callsign,employee.homeno3,employee.city2,employee.country2,
        employee.hmoname,employee.hmoaccno,employee.validity,employee.effectdate,
        employee.zipcode2,isapprover,issupervisor,isbudgetapprover, chksss, chktin, chkphealth,
        chkpibig, branch.clientname,branch.client,employee.branchid,shiftid,
         supervisor.clientid, supervisor.client, supervisor.clientname,jt.docno,ts.shftcode,
         employee.supervisorid,employee.empstatus,employee.roleid2, role2.name,dept2.client,dept2.clientname,
         div2.divcode,div2.divname,sect2.sectname,level,lastbatch, dyear, sssdef, philhdef, pibigdef, wtaxdef,
         branch2.clientname,employee.emploc2,jt2.docno,jt2.jobtitle,role.name,dept.clientname,jt.jobtitle,
         employee.branchid2,employee.bank,employee.contricompid,contricomp.divname,employee.isnobio,
           $groupbypaygroup";

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
        // foreach ($this->contactfields as $key) {
        //     if (array_key_exists($key, $head)) {
        //         $dataContact[$key] = $head[$key];
        //         if (!in_array($key, $this->except)) {
        //             $dataContact[$key] = $this->othersClass->sanitizekeyfield($key, $dataContact[$key]);
        //         } //end if  
        //     }
        // }

        foreach ($this->fieldsOther as $key) {
            if (array_key_exists($key, $head)) {
                $dataOther[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key], 'EMPLOYEE', $companyid);
                } //end if  
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

                // var_dump($dataOther);
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
            $dataOther['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $dataOther['createby'] = $config['params']['user'];

            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $data['center'] = $center;
            $clientid = $this->coreFunctions->insertGetId($this->head, $data);
            if ($clientid) {

                $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $data['clientname']);

                $dataOther['empid'] = $clientid;
                $dataContact['empid'] = $clientid;
                $this->coreFunctions->sbcinsert($this->contact, $dataContact);
                $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
            }
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

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config, $config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    // public function reportsetup($config)
    // {
    //   $txtfield = $this->createreportfilter();
    //   $txtdata = $this->reportparamsdata($config);
    //   $modulename = $this->modulename;
    //   $data = [];
    //   $style = 'width:500px;max-width:500px;';
    //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    // }


    // public function createreportfilter()
    // {
    //   $fields = [
    //     'prepared',
    //     'approved',
    //     'received',
    //     'print'
    //   ];

    //   $col1 = $this->fieldClass->create($fields);
    //   return array('col1' => $col1);
    // }

    // public function reportparamsdata($config)
    // {
    //   return $this->coreFunctions->opentable("
    //   select
    //     'default' as print,
    //     '' as prepared,
    //     '' as approved,
    //     '' as received
    // ");
    // }


    // public function generateResult($config)
    // {
    //   $center   = $config['params']['center'];
    //   $username = $config['params']['user'];
    //   $clientid = md5($config['params']['dataid']);

    //   $prepared   = $config['params']['dataparams']['prepared'];
    //   $approved   = $config['params']['dataparams']['approved'];
    //   $received   = $config['params']['dataparams']['received'];

    //   $query = "select emp.empid, client.client as empcode, CONCAT(UPPER(emplast), ', ', empfirst, ' ', empmiddle, '.') AS employee, emp.address,department.clientname as deptname,division.divname,section.sectname,
    //   emp.city, emp.country, emp.zipcode, emp.telno, emp.mobileno, emp.email, emp.citizenship, emp.religion, emp.status,
    //   emp.gender, emp.alias, emp.picpath, date(emp.bday) as bday, emp.idbarcode, emp.tin, emp.sss, emp.hdmf, emp.bankacct, emp.phic,
    //   emp.atm, emp.paymode, emp.jobtitle, emp.jobcode, emp.jobdesc, date(emp.hired) as hired, emp.regular, emp.resigned, emp.division,
    //   emp.dept, emp.orgsection, emp.supervisor, emp.school, emp.course, emp.yrgrad, emp.yrsattend, emp.gpa, emp.prevcomp,
    //   emp.prevjob, emp.prevjstart, emp.prevjend, emp.yrsexp, emp.teu, emp.nodeps, emp.isactive, emp.classrate,
    //   emp.maidname, emp.isconfidential, emp.shiftcode,  con.contact1, con.relation1, con.addr1, con.homeno1, con.mobileno1, con.officeno1, con.ext1,
    //   con.notes1, con.contact2, con.relation2, con.addr2, con.homeno2, con.mobileno2, con.officeno2, con.ext2,con.notes2,emp.paygroup
    //    FROM employee AS emp  
    //    left join contacts AS con on con.empid=emp.empid
    //    left join client as department on department.clientid=Emp.deptid
    //    left join division on division.divid=emp.divid
    //    left join section on section.sectid=emp.sectid
    //    left join client on client.clientid=emp.empid
    //    where md5(emp.empid)='$clientid'";

    //   return $this->coreFunctions->opentable($query);
    // }


    // public function reportdata($config)
    // {
    //   $str = $this->reportplotting($config);
    //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    // }

    // public function reportplotting($config)
    // {
    //   $data     = $this->generateResult($config);
    //   $center   = $config['params']['center'];
    //   $username = $config['params']['user'];
    //   $companyid = $config['params']['companyid'];

    //   $prepared   = $config['params']['dataparams']['prepared'];
    //   $approved   = $config['params']['dataparams']['approved'];
    //   $received   = $config['params']['dataparams']['received'];

    //   $str = '';
    //   $font =  "Century Gothic";
    //   $fontsize = "11";
    //   $border = "1px solid ";
    //   $count = 55;
    //   $page = 54;
    //   $str .= $this->reporter->beginreport();

    //   if($companyid == 3){
    //     $qry = "select name,address,tel from center where code = '" . $center . "'";
    //     $headerdata = $this->coreFunctions->opentable($qry);
    //     $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    //     $str .= $this->reporter->begintable('800');
    //       $str .= $this->reporter->startrow();
    //         $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
    //       $str .= $this->reporter->endrow();

    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    //       $str .= $this->reporter->endrow();

    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    //       $str .= $this->reporter->endrow();
    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    //       $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //   }else {
    //     $str .= $this->reporter->begintable('800');
    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->letterhead($center, $username);
    //       $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //   }

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('EMPLOYEE MASTERFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('PERSONAL DETAILS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Full Name : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('(' . (isset($data[0]->empcode) ? $data[0]->empcode : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->employee) ? $data[0]->employee : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Alias : ', '40', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->alias) ? $data[0]->alias : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Birthdate : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->bday) ? $data[0]->bday : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->address) ? $data[0]->address : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Gender : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->gender) ? $data[0]->gender : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Citizenship : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->citizenship) ? $data[0]->citizenship : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Civil Status : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->status) ? $data[0]->status : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Religion : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->religion) ? $data[0]->religion : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Tel. No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->telno) ? $data[0]->telno : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->mobileno) ? $data[0]->mobileno : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Email Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('SSS No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->sss) ? $data[0]->sss : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Tin No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Philhealth No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->phic) ? $data[0]->phic : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('HDMF No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->hdmf) ? $data[0]->hdmf : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('BankAccount No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->bankacct) ? $data[0]->bankacct : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->printline();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('JOB & ORGANIZATION', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Job Title : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Date Hired : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->hired) ? $data[0]->hired : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Regular : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->regular) ? $data[0]->regular : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Division : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->divname) ? $data[0]->divname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Department : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->deptname) ? $data[0]->deptname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Section : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->sectname) ? $data[0]->sectname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Payroll Group : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->paygroup) ? $data[0]->paygroup : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    //   if ($data[0]->classrate == 'M') {
    //     $classrate = 'MONTHLY';
    //   } else {
    //     $classrate = 'DAILY';
    //   }
    //   $str .= $this->reporter->col('Class Rate : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($classrate) ? $classrate : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    //   if ($data[0]->paymode == 'M') {
    //     $paymode = 'MONTocreatHLY';
    //   } elseif ($data[0]->paymode == 'W') {
    //     $paymode = 'WEEKLY';
    //   } elseif ($data[0]->paymode == 'D') {
    //     $paymode = 'DAILY';
    //   } elseif ($data[0]->paymode == 'P') {
    //     $paymode = 'PIECE';
    //   } else {
    //     $paymode = '';
    //   }

    //   $str .= $this->reporter->col('Mode of Payment : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($paymode) ? $paymode : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Supervisor : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->supervisor) ? $data[0]->supervisor : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->printline();
    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('CONTACTS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();

    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Contact : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->contact1) ? $data[0]->contact1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Contact : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->contact2) ? $data[0]->contact2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Relation : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->relation1) ? $data[0]->relation1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Relation : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->relation2) ? $data[0]->relation2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->addr1) ? $data[0]->addr1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->addr2) ? $data[0]->addr2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Tel No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->homeno1) ? $data[0]->homeno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Tel No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->homeno2) ? $data[0]->homeno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->mobileno1) ? $data[0]->mobileno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->mobileno2) ? $data[0]->mobileno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();

    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Office No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->officeno1) ? $data[0]->officeno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col('Office No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->col((isset($data[0]->officeno2) ? $data[0]->officeno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->printline();
    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('EDUCATIONAL HISTORY', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();


    //   $str .= $this->reporter->begintable('800');

    //   $qry = "select empid, line, school, address, course, sy, gpa, honor from education where empid= " . $data[0]->empid . " order by line ";
    //   $dataeduc = $this->coreFunctions->opentable($qry);


    //   foreach ($dataeduc as $key => $data1) {
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('School : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->school) ? $data1->school : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->address) ? $data1->address : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Course : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->course) ? $data1->course : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('School Yr : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->sy) ? $data1->sy : ''), '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Honor : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->honor) ? $data1->honor : ''), '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    //     $str .= $this->reporter->endrow();
    //   }
    //   $str .= $this->reporter->endtable();


    //   $str .= $this->reporter->printline();
    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('EMPLOYMENT HISTORY', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();


    //   $str .= $this->reporter->begintable('800');

    //   $qry = "select empid, line, company, jobtitle, period, address, salary, reason from employment where empid= " . $data[0]->empid . " order by line ";
    //   $dataemploy = $this->coreFunctions->opentable($qry);


    //   foreach ($dataemploy as $key => $data1) {
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('Company : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->company) ? $data1->company : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Jobtitle : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->jobtitle) ? $data1->jobtitle : ''), '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Salary : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->salary) ? $data1->salary : ''), '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Period : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->period) ? $data1->period : ''), '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col('Reason of Leaving : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->col((isset($data1->reason) ? $data1->reason : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    //     $str .= $this->reporter->endrow();
    //   }
    //   $str .= $this->reporter->endtable();
    //   $str .= $this->reporter->printline();


    //   $str .= '<br/><br/>';
    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();


    //   $str .= '<br/>';
    //   $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->endtable();

    //   $str .= $this->reporter->endreport();

    //   return $str;
    // }
} //end class
