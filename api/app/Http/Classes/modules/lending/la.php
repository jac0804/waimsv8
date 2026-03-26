<?php

namespace App\Http\Classes\modules\lending;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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
use App\Http\Classes\builder\helpClass;

class la
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'LOAN APPROVAL';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $acctg = [];
    public $hhead = 'glhead';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    private $fields = ['trno', 'docno', 'client', 'clientname', 'dateid', 'aftrno', 'vattype', 'tax'];
    private $blnfields = [
        'ispf',
        'isbene',
        'issameadd',
        'isdp',
        'isotherid',
        'iswife',
        'isretired',
        'isofw',
        'isprc',
        'isdriverlisc',
        'issenior',
        'tenthirty',
        'thirtyfifty',
        'fiftyhundred',
        'hundredtwofifty',
        'fivehundredup',
        'isemployed',
        'isselfemployed',
        'isplanholder'
    ];
    private $except = ['trno', 'dateid', 'duedate'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Approved', 'color' => 'orange'],
        ['val' => 'all', 'label' => 'All', 'color' => 'blue']
    ];

    public $labelposted = 'Approved';
    public $labellocked = 'For Review';


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
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {

        $attrib = array(
            'view' => 4987,
            'edit' => 4988,
            'new' => 4989,
            'save' => 4990,
            'delete' => 4991,
            'print' => 4992,
            'lock' => 4993,
            'unlock' => 4994,
            'post' => 4995,
            'unpost' => 4996
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
        $listcomakername = 5;
        $reqtype = 6;
        $terms = 7;

        $postdate = 8;

        $getcols = [
            'action',
            'liststatus',
            'listdocument',
            'listdate',
            'listclientname',
            'listcomakername',
            'reqtype',
            'terms',
            'postdate',
            'listpostedby',
            'listcreateby',
            'listeditby',
            'listviewby'
        ];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listdate]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
        $cols[$listclientname]['label'] = 'Borrower';
        $cols[$listcomakername]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$reqtype]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
        $cols[$reqtype]['label'] = 'Loan Type';
        $cols[$terms]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$terms]['label'] = 'Terms';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$liststatus]['name'] = 'statuscolor';
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $allownew = $this->othersClass->checkAccess($config['params']['user'], 4989);
        if ($allownew == '1') {
            array_push($fields, 'pickpo');
        }

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'pickpo.label', 'APPLICATION FORM');
        data_set($col1, 'pickpo.action', 'pendingloan');
        data_set($col1, 'pickpo.lookupclass', 'pendingloanshortcut');
        data_set($col1, 'pickpo.confirmlabel', 'Proceed to Create Loan?');

        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
    }

    public function loaddoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $searchfilter = $config['params']['search'];
        $limit = '';
        $condition = '';


        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null';
                break;
            case 'locked':
                $condition .= ' and num.postdate is null and head.lockdate is not null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";

        if (isset($searchfilter)) {
            $searchfield = ['head.docno', 'head.clientname',  'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            if ($searchfilter != "") {
                $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
            }
            $limit = "";
        }

        $qry = "select head.trno,head.docno,head.clientname,$dateid,case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end as status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
        i.clientname  as comakername,
        ifnull(r.reqtype,'') as reqtype,hhd.terms,
        case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num  on num.trno=head.trno 
        left join heahead as hhd on hhd.trno = head.aftrno 
        left join heainfo as i on i.trno = hhd.trno
        left join reqcategory as r on r.line=hhd.planid
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
        union all
        select head.trno,head.docno,head.clientname,$dateid,'APPROVED' as status,
        head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
        i.clientname  as comakername,
        ifnull(r.reqtype,'') as reqtype,hhd.terms,
       'blue' as statuscolor
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num  on num.trno=head.trno 
        left join heahead as hhd on hhd.trno = head.aftrno 
        left join heainfo as i on i.trno = hhd.trno 
        left join reqcategory as r on r.line=hhd.planid
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
        $orderby $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'save',
            'lock',
            'delete',
            'cancel',
            'print',
            'post',
            'unpost',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown',
            'help',
            'others'
        );
        $buttons = $this->btnClass->create($btns);

        $buttons['save']['label'] = 'SAVE';
        $buttons['post']['label'] = 'APPROVE';
        $buttons['unpost']['label'] = 'DISAPPROVE';
        $buttons['print']['label'] = 'PRINT';

        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
        $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
        $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
        $step6 = $this->helpClass->getFields(['btndelete']);


        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'additem' => ['label' => 'How to add account/s', 'action' => $step3],
            'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
            'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
            'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
        ];
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];


        return $buttons;
    } // createHeadbutton


    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $accounting = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewdistribution']];
        $return['Accounting'] = ['icon' => 'fa fa-coins', 'customform' => $accounting];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        return $return;
    }


    public function createTab($access, $config)
    {
        $fields = [
            'issenior',
            'tenthirty',
            'thirtyfifty',
            'fiftyhundred',
            'hundredtwofifty',
            'fivehundredup',
            'lblreceived',
            'lblattached',
            'isemployed',
            'isselfemployed',
            'isplanholder',
            'lblitemdesc',
            'credits'
        ];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'issenior.label', 'Certificate of Employment w/ Salary Information and Certificate of Appointment or Employment Contract w/Allotment Slip');
        data_set($col1, 'issenior.class', 'csisseniorid sbccsreadonly');

        data_set($col1, 'tenthirty.label', 'Photocopy of government issued IDs and any valid Identification');
        data_set($col1, 'tenthirty.class', 'cstenthirty sbccsreadonly');

        data_set($col1, 'thirtyfifty.label', 'Ownership of Collateral/Post-dated Checks/ATM/ Passbook or Withdrawal Slip of Borrower');
        data_set($col1, 'thirtyfifty.class', 'csthirtyfifty sbccsreadonly');

        data_set($col1, 'fiftyhundred.label', 'Bank Certification that the current account used is active and properly Handled or proof of pension record');
        data_set($col1, 'fiftyhundred.class', 'csfiftyhundred sbccsreadonly');

        data_set($col1, 'hundredtwofifty.label', 'Picture of Collateral, Billing, Statement, Brgy. Clearance, Marriage Contract or Birth Certificate');
        data_set($col1, 'hundredtwofifty.class', 'cshundredtwofifty sbccsreadonly');

        data_set($col1, 'fivehundredup.label', 'Special Power of Attorney (Roxas City based)');
        data_set($col1, 'fivehundredup.class', 'csfivehundredup sbccsreadonly');

        data_set($col1, 'lblreceived.label', 'For Company Use Only');
        data_set($col1, 'lblreceived.style', 'font-weight:bold; font-size:13px;');

        data_set($col1, 'lblattached.label', 'Conditions/Recommendation :');
        data_set($col1, 'lblattached.style', 'font-weight:bold; font-size:13px;');


        data_set($col1, 'isemployed.label', 'Approved with PDC');
        data_set($col1, 'isemployed.class', 'csisemployed sbccsreadonly');


        data_set($col1, 'isselfemployed.label', ' Approved Salary Deduction');
        data_set($col1, 'isselfemployed.class', 'csisselfemployed sbccsreadonly');

        data_set($col1, 'isplanholder.label', 'Approved with REM');
        data_set($col1, 'isplanholder.class', 'csisplanholder sbccsreadonly');
        data_set($col1, 'credits.type', 'textarea');
        data_set($col1, 'lblitemdesc.label', 'Credit Committee');
        data_set($col1, 'credits.label', '');
        data_set($col1, 'credits.class', 'cscredits sbccsreadonly');

        $fields = ['payrolltype', 'employeetype', 'expiration', 'loanlimit', 'loanamt'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'payrolltype.class', 'cscontra sbccsreadonly');
        data_set($col2, 'employeetype.class', 'cscontra sbccsreadonly');
        data_set($col2, 'expiration.class', 'csexpiration sbccsreadonly');
        data_set($col2, 'loanlimit.class', 'csloanlimit sbccsreadonly');
        data_set($col2, 'loanamt.class', 'csloanamt sbccsreadonly');

        $fields = ['interest', 'pf', 'amortization', 'penalty'];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'penalty.class', 'cspenalty');
        data_set($col3, 'interest.class', 'csinterest sbccsreadonly');
        data_set($col3, 'pf.class', 'cspf sbccsreadonly');
        data_set($col3, 'amortization.class', 'csamortization sbccsreadonly');
        data_set($col3, 'penalty.class', 'cspenalty sbccsreadonly');

        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1], 'label' => 'CHECK LIST'],
            'multiinput2' => ['inputcolumn' => ['col2' => $col2], 'label' => 'APPROVING BODY']
        ];

        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'leloan', 'access' => 'edit'], 'label' => 'LOAN COMPUTATION'];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {

        return [];
    }

    public function createHeadField($config)
    {

        $fields = [

            'docno',
            'categoryname',
            'purpose',
            ['amount', 'terms'],
            'dvattype',
            'lblgrossprofit',
            'client',
            'lname',
            'fname',
            'mname',
            'mmname',
            'address',
            'province',
            'addressno',
            'lblcostuom',
            ['issameadd', 'isbene'],
            'ispf',
            'contactno',
            'street',
            ['civilstatus', 'dependentsno'],
            'nationality',
            'employer',
            'subdistown',
            ['country', 'brgy']

        ];
        $col1 = $this->fieldClass->create($fields);


        data_set($col1, 'categoryname.label', 'Type of Loan');
        data_set($col1, 'categoryname.class', 'cscategory sbccsreadonly');
        data_set($col1, 'purpose.label', 'Purpose of Loan');
        data_set($col1, 'purpose.class', 'cspurpose sbccsreadonly');

        data_set($col1, 'lblgrossprofit.label', 'Borrower Information:');
        data_set($col1, 'lblgrossprofit.style', 'font-weight:bold; font-size:13px;');
        //borrower
        data_set($col1, 'client.label', 'Borrower');
        data_set($col1, 'client.class', 'csclient sbccsreadonly');

        data_set($col1, 'lname.label', 'Surname');
        data_set($col1, 'lname.class', 'cslname sbccsreadonly');


        data_set($col1, 'fname.label', 'Given Name');
        data_set($col1, 'fname.class', 'csfname sbccsreadonly');

        data_set($col1, 'mname.class', 'csmname sbccsreadonly');
        data_set($col1, 'mname.class', 'csmname sbccsreadonly');
        data_set($col1, 'mmname.class', 'csmmname sbccsreadonly');


        data_set($col1, 'address.label', 'Present Address');
        data_set($col1, 'address.class', 'csaddress sbccsreadonly');

        data_set($col1, 'province.label', 'Provincial Address');
        data_set($col1, 'province.class', 'csprovince sbccsreadonly');

        data_set($col1, 'addressno.label', 'Mailing Address');
        data_set($col1, 'addressno.class', 'csaddressno sbccsreadonly');

        data_set($col1, 'lblcostuom.label', 'House');
        data_set($col1, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');

        data_set($col1, 'issameadd.label', 'Owned');
        data_set($col1, 'issameadd.class', 'csissameadd sbccsreadonly');

        data_set($col1, 'isbene.label', 'Rented');
        data_set($col1, 'isbene.class', 'csisbene sbccsreadonly');

        data_set($col1, 'ispf.label', 'Free');
        data_set($col1, 'ispf.class', 'csispf sbccsreadonly');

        data_set($col1, 'contactno.label', 'Telephone#');
        data_set($col1, 'contactno.class', 'cscontactno sbccsreadonly');

        data_set($col1, 'street.label', 'Date & Place of Birth');
        data_set($col1, 'street.class', 'csstreet sbccsreadonly');

        data_set($col1, 'dependentsno.class', 'csdependentsno sbccsreadonly');

        data_set($col1, 'nationality.type', 'input');
        data_set($col1, 'nationality.maxlength', '20');

        data_set($col1, 'nationality.class', 'csnationality sbccsreadonly');

        data_set($col1, 'employer.class', 'csemployer sbccsreadonly');

        data_set($col1, 'subdistown.label', 'Address & Tel. #');
        data_set($col1, 'subdistown.class', 'cssubdistown sbccsreadonly');

        data_set($col1, 'country.label', 'Position Held');
        data_set($col1, 'country.class', 'cscountry sbccsreadonly');

        data_set($col1, 'brgy.class', 'csbrgy sbccsreadonly');
        //borrower spouse

        data_set($col2, 'amount.label', 'Loan Amount');
        data_set($col2, 'amount.class', 'csamount sbccsreadonly');

        data_set($col2, 'terms.label', 'Terms');
        data_set($col2, 'terms.class', 'csterms sbccsreadonly');


        $fields = [

            'lbltotalkg',
            'sname',
            'companyaddress',
            'mmoq',
            'city',
            ['pcity', 'pcountry'],
            'leasecontract',
            'pprovince',
            'lblshipping',
            'lblbilling',
            ['idno', 'value'],
            ['isdp', 'isotherid'],
            'lblacquisition',
            ['zipcode', 'yourref'],
            'lbldepreciation',
            ['lbllocation', 'lblvehicleinfo'],
            ['email', 'pemail'],
            ['customername', 'prref'],
            ['checkinfo', 'entryndiffot'],
            ['regnum', 'purchaser'],
            'registername',
            'shipto',
            'lblrem',
            'revisionref',
            ['returndate', 'mlcp_freight'],
            'tin',
            'sssgsis'

        ];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'lbltotalkg.label', 'Spouse Data (If Married)');
        data_set($col2, 'lbltotalkg.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'sname.class', 'cssname sbccsreadonly');

        data_set($col2, 'companyaddress.label', 'Date & Place of Birth'); //paddress
        data_set($col2, 'companyaddress.maxlength', '150');
        data_set($col2, 'companyaddress.class', 'cscompanyaddress sbccsreadonly');

        data_set($col2, 'mmoq.name', 'ename');
        data_set($col2, 'mmoq.label', 'Name of Employer/Business');
        data_set($col2, 'mmoq.class', 'csamtmmoq sbccsreadonly');

        data_set($col2, 'city.label', 'Address & Tel. #');
        data_set($col2, 'city.class', 'cscity sbccsreadonly');



        data_set($col2, 'pcity.label', 'Position Held');
        data_set($col2, 'pcity.class', 'cscity sbccsreadonly');

        data_set($col2, 'pcountry.label', 'Length of Stay');
        data_set($col2, 'pcountry.class', 'cscountry sbccsreadonly');

        data_set($col2, 'pprovince.label', 'Immediate Superior');
        data_set($col2, 'pprovince.class', 'csprovince sbccsreadonly');

        data_set($col2, 'leasecontract.label', 'Monthly Income');
        data_set($col2, 'leasecontract.class', 'csleasecontract sbccsreadonly');
        data_set($col2, 'leasecontract.name', 'monthly');
        // data_set($col2, 'leasecontract.maxlength', 12);


        data_set($col2, 'lblshipping.label', 'Properties');
        data_set($col2, 'lblshipping.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lblbilling.label', 'Real Estate');
        data_set($col2, 'lblbilling.style', 'font-size:13px;');

        data_set($col2, 'idno.label', 'Location');
        data_set($col2, 'idno.class', 'csidno sbccsreadonly');

        data_set($col2, 'value.class', 'csvalue sbccsreadonly');

        data_set($col2, 'isdp.label', 'Not Morgaged');
        data_set($col2, 'isdp.class', 'csisdp sbccsreadonly');

        data_set($col2, 'isotherid.label', 'Morgaged');
        data_set($col2, 'isotherid.class', 'csisotherid sbccsreadonly');

        data_set($col2, 'lblacquisition.label', 'Vehicle');
        data_set($col2, 'lblacquisition.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'zipcode.label', 'Year');
        data_set($col2, 'zipcode.class', 'cszipcode sbccsreadonly');

        data_set($col2, 'yourref.label', 'Model');
        data_set($col2, 'yourref.class', 'csyourref sbccsreadonly');

        //bank
        data_set($col2, 'lbldepreciation.label', 'Bank Deposit');
        data_set($col2, 'lbldepreciation.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lbllocation.label', 'Account#');
        data_set($col2, 'lbllocation.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lblvehicleinfo.label', 'Bank');
        data_set($col2, 'lblvehicleinfo.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'email.label', 'Savings account#');
        data_set($col2, 'email.class', 'csemail sbccsreadonly');


        data_set($col2, 'pemail.label', 'Savings Bank');
        data_set($col2, 'pemail.class', 'csemail sbccsreadonly');

        data_set($col2, 'customername.name', 'current1');
        data_set($col2, 'customername.label', 'Current account#');
        data_set($col2, 'customername.class', 'cscustomername sbccsreadonly');

        data_set($col2, 'prref.name', 'current2');
        data_set($col2, 'prref.label', 'Current Bank');
        data_set($col2, 'prref.class', 'csprref sbccsreadonly');

        data_set($col2, 'checkinfo.name', 'others1');
        data_set($col2, 'checkinfo.label', 'Others Account#');
        data_set($col2, 'checkinfo.class', 'cscheckinfo sbccsreadonly');

        data_set($col2, 'entryndiffot.name', 'others2');
        data_set($col2, 'entryndiffot.label', 'Others Bank');
        data_set($col2, 'entryndiffot.class', 'csentryndiffot sbccsreadonly');

        //monthly income

        data_set($col2, 'regnum.name', 'mincome');
        data_set($col2, 'regnum.label', 'Monthly Income (Applicant)');
        data_set($col2, 'regnum.class', 'csregnum sbccsreadonly');

        data_set($col2, 'purchaser.name', 'mexp');
        data_set($col2, 'purchaser.label', 'Monthly Expenses');
        data_set($col2, 'purchaser.class', 'cspurchaser sbccsreadonly');

        data_set($col2, 'registername.name', 'pob');
        data_set($col2, 'registername.label', 'Personal References 1');
        data_set($col2, 'registername.class', 'csregistername sbccsreadonly');

        data_set($col2, 'shipto.name', 'otherplan');
        data_set($col2, 'shipto.label', 'Personal References 2');
        data_set($col2, 'shipto.class', 'csshipto sbccsreadonly');

        //Residence Certificate

        data_set($col2, 'lblrem.label', 'Residence Certificate');
        data_set($col2, 'lblrem.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'revisionref.name', 'num');
        data_set($col2, 'revisionref.label', 'Number');
        data_set($col2, 'revisionref.class', 'csrevisionref sbccsreadonly');

        data_set($col2, 'returndate.type', 'date');
        data_set($col2, 'returndate.name', 'voiddate');
        data_set($col2, 'returndate.label', 'Date of Issue');
        data_set($col2, 'returndate.class', 'csreturndate sbccsreadonly');

        data_set($col2, 'mlcp_freight.name', 'pliss');
        data_set($col2, 'mlcp_freight.label', 'Place of Issue');
        data_set($col2, 'mlcp_freight.class', 'csms_freight sbccsreadonly');

        data_set($col2, 'tin.maxlength', '15');
        data_set($col2, 'tin.class', 'cstin sbccsreadonly');

        data_set($col2, 'sssgsis.maxlength', '15');
        data_set($col2, 'sssgsis.class', 'cssssgsis sbccsreadonly');


        $fields = [
            'lblsource',
            'lname2',
            'fname2',
            'mname2',
            'maidname',
            'truckno',
            'rprovince',
            'raddressno',
            'lbldestination',
            ['iswife', 'isretired'],
            'isofw',
            'contactno2',
            'rstreet',
            [
                'mstatus',
                'mobile'
            ],
            'citizenship',
            'owner',
            'rsubdistown',
            [
                'rcountry',
                'rbrgy'
            ],
            'lblpassbook',
            'empfirst',
            'pstreet',
            'emplast',
            'rcity',
            [
                'paddressno',
                'dp'
            ],
            [
                'psubdistown',
                'othersource'
            ]
        ];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblsource.label', 'Co Maker Information: ');
        data_set($col3, 'lblsource.style', 'font-weight:bold; font-size:13px;');

        data_set($col3, 'lname2.class', 'cslname2 sbccsreadonly');
        data_set($col3, 'fname2.class', 'csfname2 sbccsreadonly');
        data_set($col3, 'mname2.class', 'csmname2 sbccsreadonly');
        data_set($col3, 'maidname.label', 'Mothers Maiden Name');
        data_set($col3, 'maidname.class', 'csmaidname sbccsreadonly');

        data_set($col3, 'truckno.label', 'Present Address');
        data_set($col3, 'truckno.class', 'csaddress sbccsreadonly');

        data_set($col3, 'rprovince.label', 'Provincial Address');
        data_set($col3, 'rprovince.class', 'rprovince sbccsreadonly');

        data_set($col3, 'raddressno.label', 'Mailing Address');
        data_set($col3, 'raddressno.class', 'csaddressno sbccsreadonly');

        data_set($col3, 'lbldestination.label', 'House');
        data_set($col3, 'lbldestination.style', 'font-weight:bold; font-size:13px;');

        data_set($col3, 'iswife.label', 'Owned');
        data_set($col3, 'iswife.class', 'csiswife sbccsreadonly');
        data_set($col3, 'isretired.label', 'Rented');
        data_set($col3, 'isretired.class', 'csisretired sbccsreadonly');
        data_set($col3, 'isofw.label', 'Free');
        data_set($col3, 'isofw.class', 'csisofw sbccsreadonly');

        data_set($col3, 'contactno2.label', 'Telephone #');
        data_set($col3, 'contactno2.class', 'cscontactno2 sbccsreadonly');

        data_set($col3, 'rstreet.label', 'Date & Place of Birth');
        data_set($col3, 'rstreet.class', 'csstreet sbccsreadonly');

        data_set($col3, 'mstatus.name', 'civilstat');
        data_set($col3, 'mstatus.lookupclass', 'lookuplestatus');
        data_set($col3, 'mstatus.label', 'Civil Status');
        data_set($col3, 'mstatus.class', 'csjmstatus sbccsreadonly');


        data_set($col3, 'mobile.label', 'No. Of Dependents');
        data_set($col3, 'mobile.class', 'csmobile sbccsreadonly');

        data_set($col3, 'citizenship.label', 'Nationality');
        data_set($col3, 'citizenship.class', 'cscitizenship sbccsreadonly');

        data_set($col3, 'owner.label', 'Name of Employer/Business');
        data_set($col3, 'owner.class', 'csowner sbccsreadonly');

        data_set($col3, 'rsubdistown.label', 'Address & Tel. #');
        data_set($col3, 'rsubdistown.class', 'csrsubdistown sbccsreadonly');

        data_set($col3, 'rcountry.label', 'Position Held');
        data_set($col3, 'rcountry.class', 'cscountry sbccsreadonly');


        data_set($col3, 'rbrgy.label', 'Length of Stay');
        data_set($col3, 'rbrgy.class', 'csbrgy sbccsreadonly');

        //spouse
        data_set($col3, 'lblpassbook.label', 'Spouse Data (If Married)');
        data_set($col3, 'lblpassbook.style', 'font-weight:bold; font-size:13px;');

        data_set($col3, 'empfirst.label', 'Name of Spouse');
        data_set($col3, 'empfirst.class', 'csempfirst sbccsreadonly');

        data_set($col3, 'pstreet.label', 'Date & Place of Birth');
        data_set($col3, 'pstreet.class', 'csstreet sbccsreadonly');

        data_set($col3, 'emplast.label', 'Name of Employer/Business');
        data_set($col3, 'emplast.class', 'csemplast sbccsreadonly');


        data_set($col3, 'rcity.label', 'Address & Tel. #');
        data_set($col3, 'rcity.class', 'cscity sbccsreadonly');

        data_set($col3, 'paddressno.label', 'Position Held');
        data_set($col3, 'paddressno.class', 'csaddressno sbccsreadonly');

        data_set($col3, 'dp.label', 'Monthly Income');
        data_set($col3, 'dp.class', 'csdp sbccsreadonly');


        data_set($col3, 'psubdistown.label', 'Length of Stay');
        data_set($col3, 'psubdistown.class', 'csrsubdistown sbccsreadonly');

        data_set($col3, 'othersource.label', 'Immediate Superior');
        data_set($col3, 'othersource.class', 'csothersource sbccsreadonly');


        $fields = [

            'lblreconcile',
            'lblearned',
            'ext2',
            'rem',
            ['isprc', 'isdriverlisc'],
            'lblcleared',
            'minimum',
            'ourref',
            'lblrecondate',
            ['lblendingbal', 'lblunclear'],
            ['recondate', 'endingbal'],
            ['unclear', 'revision'],
            ['ftruckname', 'frprojectname'],
            [
                'poref',
                'soref'
            ],
            'pbrgy',
            'appref',
            'lblbranch',
            'numdays',
            [
                'bday',
                'entryot'
            ],
            'othrs',
            'apothrs'

        ];
        $col4 = $this->fieldClass->create($fields);


        data_set($col4, 'lblreconcile.label', 'Properties');
        data_set($col4, 'lblreconcile.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'lblearned.label', 'Real Estate');
        data_set($col4, 'lblearned.style', 'font-size:13px;');

        data_set($col4, 'ext2.label', 'Location');
        data_set($col4, 'ext2.class', 'csext2 sbccsreadonly');


        data_set($col4, 'rem.type', 'cinput');
        data_set($col4, 'rem.label', 'Value');
        data_set($col4, 'rem.class', 'csrem sbccsreadonly');

        data_set($col4, 'isprc.label', 'Not Morgaged');
        data_set($col4, 'isprc.class', 'csisprc sbccsreadonly');

        data_set($col4, 'isdriverlisc.label', 'Morgaged');
        data_set($col4, 'isdriverlisc.class', 'csisdriverlisc sbccsreadonly');

        data_set($col4, 'lblcleared.label', 'Vehicle');
        data_set($col4, 'lblcleared.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'minimum.label', 'Year');
        data_set($col4, 'minimum.class', 'csminimum sbccsreadonly');

        data_set($col4, 'ourref.label', 'Model');
        data_set($col4, 'ourref.class', 'csourref sbccsreadonly');

        //bank deposit
        data_set($col4, 'lblrecondate.label', 'Bank Deposit');
        data_set($col4, 'lblrecondate.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'lblendingbal.label', 'Account#');
        data_set($col4, 'lblendingbal.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'lblunclear.label', 'Bank');
        data_set($col4, 'lblunclear.style', 'font-weight:bold; font-size:13px;');


        data_set($col4, 'recondate.label', 'Savings account#');
        data_set($col4, 'recondate.class', 'csrecondate sbccsreadonly');


        data_set($col4, 'endingbal.label', 'Savings Bank');
        data_set($col4, 'endingbal.class', 'csendingbal sbccsreadonly');



        data_set($col4, 'unclear.label', 'Current account#');
        data_set($col4, 'unclear.class', 'csunclear sbccsreadonly');



        data_set($col4, 'revision.label', 'Current Bank');
        data_set($col4, 'revision.class', 'csrevision sbccsreadonly');


        data_set($col4, 'ftruckname.class', 'csftruckname');
        data_set($col4, 'ftruckname.label', 'Others Account#');
        data_set($col4, 'ftruckname.class', 'csftruckname sbccsreadonly');


        data_set($col4, 'frprojectname.class', 'csfrproject');
        data_set($col4, 'frprojectname.label', 'Others Bank');
        data_set($col4, 'frprojectname.class', 'csfrproject sbccsreadonly');


        data_set($col4, 'poref.label', 'Monthly Income (Applicant)');
        data_set($col4, 'poref.class', 'csporef sbccsreadonly');

        data_set($col4, 'soref.label', 'Monthly Expenses');
        data_set($col4, 'soref.class', 'cssoref sbccsreadonly');


        data_set($col4, 'pbrgy.label', 'Personal References 1');
        data_set($col4, 'pbrgy.class', 'csbrgy sbccsreadonly');

        data_set($col4, 'appref.label', 'Personal References 2');
        data_set($col4, 'appref.class', 'csappref sbccsreadonly');

        data_set($col4, 'lblbranch.label', 'Residence Certificate');
        data_set($col4, 'lblbranch.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'numdays.class', 'csnumdays');
        data_set($col4, 'numdays.label', 'Number');
        data_set($col4, 'numdays.class', 'csnumdays sbccsreadonly');

        data_set($col4, 'bday.type', 'date');
        data_set($col4, 'bday.label', 'Date of Issue');
        data_set($col4, 'bday.class', 'csbday sbccsreadonly');


        data_set($col4, 'entryot.label', 'Place of Issue');
        data_set($col4, 'entryot.class', 'csentryot sbccsreadonly');


        data_set($col4, 'othrs.label', 'TIN');
        data_set($col4, 'othrs.class', 'csothrs sbccsreadonly');

        data_set($col4, 'apothrs.class', 'csapothrs');
        data_set($col4, 'apothrs.label', 'SSS/GSIS No.');
        data_set($col4, 'apothrs.class', 'csapothrs sbccsreadonly');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();

        $data[0]['purpose'] = '';
        $data[0]['lname'] = '';
        $data[0]['fname'] = '';
        $data[0]['mname'] = '';
        $data[0]['mmname'] = '';
        $data[0]['address'] = '';
        $data[0]['province'] = '';
        $data[0]['addressno'] = '';
        $data[0]['issameadd'] = '0';
        $data[0]['isbene'] = '0';
        $data[0]['ispf'] = '0';
        $data[0]['contactno'] = '';
        $data[0]['street'] = '';


        $data[0]['civilstatus'] = ''; //stat



        $data[0]['dependentsno'] = '';
        $data[0]['nationality'] = 'Filipino';
        $data[0]['employer'] = '';
        $data[0]['subdistown'] = '';
        $data[0]['country'] = '';
        $data[0]['brgy'] = '';

        $data[0]['sname'] = '';
        $data[0]['companyaddress'] = '';
        $data[0]['mmoq'] = '';
        $data[0]['ename'] = '';
        $data[0]['city'] = '';
        $data[0]['pcity'] = '';

        $data[0]['pcountry'] = '';
        $data[0]['pprovince'] = '';

        $data[0]['amount'] = 0;
        $data[0]['terms'] = '';

        $data[0]['idno'] = '';
        $data[0]['value'] = 0;
        $data[0]['isdp'] = '0';
        $data[0]['isotherid'] = '0';
        $data[0]['zipcode'] = '';
        $data[0]['yourref'] = '';
        $data[0]['email'] = '';

        $data[0]['bank1'] = '';

        $data[0]['current1'] = '';
        $data[0]['customername'] = '';

        $data[0]['current2'] = '';
        $data[0]['prref'] = '';

        $data[0]['others1'] = '';
        $data[0]['checkinfo'] = '';

        $data[0]['others2'] = '';
        $data[0]['entryndiffot'] = '';

        $data[0]['mincome'] = 0;
        $data[0]['regnum'] = 0;

        $data[0]['mexp'] = 0;
        $data[0]['purchaser'] = 0;

        $data[0]['pob'] = '';
        $data[0]['registername'] = '';

        $data[0]['otherplan'] = '';
        $data[0]['shipto'] = '';


        $data[0]['num'] = 0;
        $data[0]['revisionref'] = 0;

        $data[0]['voiddate'] = $this->othersClass->getCurrentDate();

        $data[0]['returndate'] = $this->othersClass->getCurrentDate();


        $data[0]['pliss'] = '';
        $data[0]['mlcp_freight'] = '';

        $data[0]['tin'] = '';
        $data[0]['sssgsis'] = '';

        //co maker
        $data[0]['lname2'] = '';
        $data[0]['fname2'] = '';
        $data[0]['mname2'] = '';
        $data[0]['maidname'] = '';
        $data[0]['truckno'] = '';
        $data[0]['rprovince'] = '';
        $data[0]['raddressno'] = '';
        $data[0]['iswife'] = '0';
        $data[0]['isretired'] = '0';
        $data[0]['isofw'] = '0';
        $data[0]['contactno2'] = '';
        $data[0]['rstreet'] = '';

        $data[0]['mstatus'] = '';  //statuss
        $data[0]['civilstat'] = '';

        $data[0]['mobile'] = '';
        $data[0]['citizenship'] = 'Filipino';
        $data[0]['owner'] = '';
        $data[0]['rsubdistown'] = '';
        $data[0]['rcountry'] = '';
        $data[0]['rbrgy'] = '';
        $data[0]['empfirst'] = '';
        $data[0]['pstreet'] = '';
        $data[0]['emplast'] = '';
        $data[0]['rcity'] = '';
        $data[0]['paddressno'] = '';
        $data[0]['dp'] = 0;
        $data[0]['psubdistown'] = '';
        $data[0]['othersource'] = '';


        $data[0]['ext2'] = '';
        $data[0]['rem'] = 0;
        $data[0]['isprc'] = '0';
        $data[0]['isdriverlisc'] = '0';
        $data[0]['minimum'] = '';
        $data[0]['ourref'] = '';
        $data[0]['recondate'] = '';
        $data[0]['endingbal'] = '';
        $data[0]['unclear'] = '';
        $data[0]['revision'] = '';
        $data[0]['ftruckname'] = '';
        $data[0]['frprojectname'] = '';
        $data[0]['poref'] = 0;
        $data[0]['soref'] = 0;
        $data[0]['pbrgy'] = '';
        $data[0]['appref'] = '';
        $data[0]['numdays'] = 0;
        $data[0]['bday'] = $this->othersClass->getCurrentDate();
        $data[0]['entryot'] = '';
        $data[0]['othrs'] = '';
        $data[0]['apothrs'] = '';

        $data[0]['interest'] = 0;
        $data[0]['pf'] = 0;

        $data[0]['monthly'] = 0;
        $data[0]['leasecontract'] = 0;


        $data[0]['planid'] = 0;
        $data[0]['categoryname'] = '';

        $data[0]['issenior'] = '0';
        $data[0]['tenthirty'] = '0';

        $data[0]['thirtyfifty'] = '0';
        $data[0]['fiftyhundred'] = '0';
        $data[0]['hundredtwofifty'] = '0';
        $data[0]['fivehundredup'] = '0';
        $data[0]['isemployed'] = '0';
        $data[0]['isselfemployed'] = '0';
        $data[0]['isplanholder'] = '0';
        $data[0]['credits'] = '';

        $data[0]['client'] = '';
        $data[0]['clientname'] = '';

        $data[0]['vattype'] = 'NON-VATABLE';
        $data[0]['tax'] = 0;



        return $data;
    }


    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $tablenum = $this->tablenum;

        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            } else {
                $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
                if ($t == '') {
                    $trno = 0;
                }
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $center = $config['params']['center'];

        if ($this->companysetup->getistodo($config['params'])) {
            $this->othersClass->checkseendate($config, $tablenum);
        }

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;

        $eahead = 'heahead';
        $info = 'heainfo';

        $qryselect = "select
             num.center, 
             cp.trno, 
             cp.docno,
             head.docno as afdocno,
             cp.aftrno, 
             left(head.dateid,10) as dateid,
         ifnull(r.reqtype,'') as categoryname,  format(head.monthly,2) as monthly, '' as leasecontract,
         head.interest,
         head.planid, info.pf, 
         cp.tax,
         cp.vattype,
         '' as dvattype,

         head.purpose, head.client,head.lname,head.fname,head.mname,head.mmname,
         concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
         head.address,head.province,head.addressno,info.issameadd, info.isbene,  info.ispf,head.contactno,head.street,
    
         ifnull(head.civilstatus,'') as civilstatus, 
         head.dependentsno,head.nationality,head.employer,
         head.subdistown,head.country,head.brgy,

         ifnull(head.sname,'') as sname,ifnull(info.paddress,'') as companyaddress,
         ifnull(head.ename,'') as ename, '' as mmoq,
         
         ifnull(head.city,'') as city, ifnull(info.pcity,'') as pcity,
         ifnull(info.pf,'') as pf, ifnull(info.pcountry,'') as pcountry, ifnull(info.pprovince,'') as pprovince,
          format(info.amount,2) as amount,  head.terms,
         
         info.idno,format(info.value,2) as value,info.isdp,info.isotherid,
         head.zipcode,head.yourref,head.email,
         
         
         ifnull(info.bank1,'')  as pemail,
         ifnull(head.current1,'') as current1 ,'' as customername,
         ifnull(head.current2,'') as current2 ,'' as prref,
         ifnull(head.others1,'') as others1 ,'' as checkinfo,

        
         ifnull(head.others2,'') as others2, '' as entryndiffot,
          ifnull(format(head.mincome,2),'') as mincome, '' as regnum,
         ifnull(format(head.mexp,2),'') as mexp, '' as purchaser,
         ifnull(info.pob,'') as pob, '' as registername,
         ifnull(info.otherplan,'') as otherplan, '' as shipto,
         ifnull(head.num,'') as num, '' as revisionref,
         ifnull(date(head.voiddate),'') as voiddate, '' as returndate,
         ifnull(head.pliss,'') as pliss, '' as mlcp_freight,
         ifnull(head.tin,'') as tin,ifnull(head.sssgsis,'') as sssgsis,



         info.lname as lname2, info.fname as fname2, info.mname as mname2,info.mmname as maidname,
         info.address as truckno,
         info.province as rprovince,info.addressno as raddressno,
         info.iswife,info.isretired,info.isofw,head.contactno2,info.street as rstreet,
         ifnull(info.civilstat,'') as civilstat, '' as mstatus,
         
         info.dependentsno as mobile,info.nationality as citizenship,
         info.employer as owner,info.subdistown as rsubdistown,info.country as rcountry, info.brgy as rbrgy,
         info.sname as empfirst, info.pstreet, info.ename as emplast, info.city as rcity,
         info.paddressno, format(info.dp,2)as dp,info.psubdistown,info.othersource,

         info.ext as ext2,format(info.value2,2) as rem, info.isprc,
         info.isdriverlisc,info.zipcode as minimum,head.ourref,
         info.savings1 as recondate,info.savings2 as endingbal, 
         info.current1 as unclear,info.current2 as revision,
         info.others1 as ftruckname,info.others2 as frprojectname,
         format(info.mincome,2) as poref, format(info.mexp,2) as soref,
         info.pbrgy,info.appref,info.num as numdays,
        date(info.bday) as bday,info.pliss as entryot, info.tin as othrs, 
         info.sssgsis as apothrs,
         info.issenior,info.tenthirty,info.thirtyfifty,
         info.fiftyhundred,
         info.hundredtwofifty,info.fivehundredup,
         info.isemployed,info.isselfemployed,info.isplanholder,
         info.credits,info.payrolltype,info.employeetype,info.expiration,info.loanlimit,info.loanamt,
         info.amortization,info.penalty, concat(info.lname,', ',info.fname,' ',info.mname)  as comakername
         ";

        $qry = $qryselect . " from $table as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where cp.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where cp.trno = ? and num.doc=? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            foreach ($this->blnfields as $key => $value) {
                if ($head[0]->$value) {
                    $head[0]->$value = "1";
                } else
                    $head[0]->$value = "0";
            }
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hideobj = [];

            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }
            return  ['head' => $head, 'griddata' => ['accounting' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }


    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];

        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
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

        if ($isupdate) {
            $aftrno = $this->coreFunctions->datareader('select aftrno as value from ' . $this->head . " where trno=?", [$head['trno']]);
            $this->coreFunctions->sbcupdate('heahead', ['catrno' => 0], ['trno' => $aftrno]);
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->coreFunctions->sbcupdate('heahead', ['catrno' => $head['trno']], ['trno' => $head['aftrno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->coreFunctions->sbcupdate('heahead', ['catrno' => $head['trno']], ['trno' => $head['aftrno']]);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

        $aftrno = $this->coreFunctions->datareader('select aftrno as value from ' . $this->head . " where trno=?", [$trno]);
        $this->coreFunctions->sbcupdate('heahead', ['catrno' => 0], ['trno' => $aftrno]);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $entry = [];
    $pf = 0;

    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,dinfo.principal,dinfo.interest,dinfo.pfnf,terms.days,head.tax
    from ' . $this->head . ' as head
    left join client on client.client = head.client
    left join heahead as app on app.trno=head.aftrno
    left join heainfo as info on info.trno = app.trno
    left join htempdetailinfo as dinfo on dinfo.trno=app.trno
    left join terms on terms.terms = app.terms
    where head.trno=?';


    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $aracct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR1']);//principal
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP1']);
      $arintacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR2']);//interest
      $revintacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA2']);
      $arpfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);//others
      $revpfacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA3']);
      $vatacct = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }

      $cr = 0;
      $day = date("d", strtotime($stock[0]->dateid));
      $mnth = date("m", strtotime($stock[0]->dateid));
      $yr = date("Y", strtotime($stock[0]->dateid));

      $tprincipal=0;
      $tinterest =0;
      $tpfnf =0;
   
        $balmons = $stock[0]->days;
        $rdate = strtotime($stock[0]->dateid);  
        $i=1;
        $y=1;
        foreach ($stock as $k => $v) {
            $pdate = date("Y-m-d", strtotime("+$y month", $rdate));
            if($stock[$k]->principal){
                $d['trno'] = $trno;
                $d['line'] = $i;
                $d['acnoid'] = $aracct;
                $d['client'] = $stock[$k]->client;
                $d['postdate'] = $pdate;
                $d['db'] = $stock[$k]->principal;
                $d['cr'] = 0;

                $locale = 'en_US';
                $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
                $d['rem'] = $nf->format($y) . ' MA';
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
                $i += 1;
                $tprincipal = $tprincipal + $stock[$k]->principal;
            }  

            if($stock[$k]->interest){
                $d['trno'] = $trno;
                $d['line'] = $i;
                $d['acnoid'] = $arintacct;
                $d['client'] = $stock[$k]->client;
                $d['postdate'] = $pdate;
                $d['db'] = $stock[$k]->interest;
                $d['cr'] = 0;

                $locale = 'en_US';
                $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
                $d['rem'] = $nf->format($y) . ' Interest';
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
                $i += 1;
                $tinterest = $tinterest + $stock[$k]->interest;
            }
            
            if($stock[$k]->pfnf){
                $d['trno'] = $trno;
                $d['line'] = $i;
                $d['acnoid'] = $arpfacct;
                $d['client'] = $stock[$k]->client;
                $d['postdate'] = $pdate;
                $d['db'] = $stock[$k]->pfnf;
                $d['cr'] = 0;

                $locale = 'en_US';
                $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
                $d['rem'] = $nf->format($y) . ' PF & NF';
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
                $i += 1;
                $tpfnf = $tpfnf + $stock[$k]->pfnf;
            }
          $y+=1;
        }
  
        //output entry  
        if ($stock[0]->tax != 0) {
          $tax = (($tprincipal+$tinterest+$tpfnf) / 1.12) * .12;
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
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
          $i += 1;
        }
  
        
        //principal
        if($tprincipal!=0){
            $d['trno'] = $trno;
            $d['line'] = $i;
            $d['acnoid'] = $revacct;
            $d['client'] =  $stock[$k]->client;
            $d['postdate'] = $stock[0]->dateid;
            $d['db'] = 0;
            $d['cr'] = $tprincipal/$tax1;
            $d['rem'] = '';
            //array_push($det, $d);
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
            $i += 1;
        }
        
  
        //unearned int.
        if($tinterest!=0){
            $d['trno'] = $trno;
            $d['line'] = $i;
            $d['acnoid'] = $revintacct;
            $d['client'] = $stock[$k]->client;
            $d['postdate'] = $stock[0]->dateid;
            $d['db'] = 0;
            $d['cr'] = $tinterest/$tax1;
            $d['rem'] = '';
            //array_push($det, $d);
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
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
            $d['cr'] = $tpfnf/$tax1;
            $d['rem'] = '';
            //array_push($det, $d);
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $d, $config);
            $i += 1;
          }
      }
  
    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $config['params']['trno'];
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }

      //checking for 0.01 discrepancy
      $variance = $this->coreFunctions->datareader("select sum(db-cr) as value from " . $this->detail . " where trno=?", [$trno], '', true);
      if (abs($variance) == 0.01) {
        $taxamt = $this->coreFunctions->datareader("select d.cr as value from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and coa.alias='TX2'", [$trno], '', true);
        if ($taxamt != 0) {
          $salesentry = $this->coreFunctions->opentable("select d.line from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and left(coa.alias,2)='SA'  order by d.line desc limit 1", [$trno]);
          if ($salesentry) {
            $this->coreFunctions->execqry("update " . $this->detail . " set cr=cr+" . $variance . " where trno=" . $trno . " and line=" . $salesentry[0]->line);
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'FORCE BALANCE WITH 0.01 VARIANCE');
          }
        }
      }
    }

    return $status;
  } //end function

    //  to follow posting
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        
        if (!$this->createdistribution($config)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
          } else {
            return $this->othersClass->posttransacctg($config);
          }
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttransacctg($config);
    } //end function




    public function stockstatus($config)
    {
        return [];
    }

    public function updateperitem($config)
    {
        return [];
    }


    public function updateitem($config)
    {
        return [];
    } //end function

    public function addallitem($config)
    {
        return [];
    } //end function


    // insert and update detail
    public function additem($action, $config)
    {
        return [];
    } // end function

    public function deleteallitem($config)
    {
        return [];
    }

    public function deleteitem($config)
    {
        return [];
    } //end function


    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;

            case 'generatecert':
                return $this->setupreport($config);

                break;

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getloanapplication($config)
    {
        $qryselect = "select   head.trno as aftrno, 
         head.docno,left(head.dateid,10) as dateid,
         ifnull(r.reqtype,'') as categoryname,  format(head.monthly,2) as monthly, '' as leasecontract,
         head.interest,
         head.planid, info.pf,
         head.purpose, head.client,head.clientname,head.lname,
         head.fname,head.mname,head.mmname,
         head.address,head.province,head.addressno,
         case when info.issameadd=0 then '0' else '1' end as issameadd,  
         case when info.isbene=0 then '0' else '1' end as isbene,  
         case when info.ispf=0 then '0' else '1' end as ispf,
         head.contactno,head.street,
    
         ifnull(head.civilstatus,'') as civilstatus, 
         head.dependentsno,head.nationality,head.employer,
         head.subdistown,head.country,head.brgy,

         ifnull(head.sname,'') as sname,ifnull(info.paddress,'') as companyaddress,
         ifnull(head.ename,'') as ename, '' as mmoq,
         
         ifnull(head.city,'') as city, ifnull(info.pcity,'') as pcity,
         ifnull(info.pf,'') as pf, ifnull(info.pcountry,'') as pcountry, ifnull(info.pprovince,'') as pprovince,
         format(info.amount,2) as amount,  head.terms,
         
         info.idno,format(info.value,2) as value,
         
         case when info.isdp=0 then '0' else '1' end as isdp,
         case when info.isotherid=0 then '0' else '1' end as isotherid,
         
         head.zipcode,head.yourref,head.email,
         
         
         ifnull(info.bank1,'')  as pemail,
         ifnull(head.current1,'') as current1 ,'' as customername,
         ifnull(head.current2,'') as current2 ,'' as prref,
         ifnull(head.others1,'') as others1 ,'' as checkinfo,

        
         ifnull(head.others2,'') as others2, '' as entryndiffot,
         ifnull(format(head.mincome,2),'') as mincome, '' as regnum,
         ifnull(format(head.mexp,2),'') as mexp, '' as purchaser,
         ifnull(info.pob,'') as pob, '' as registername,
         ifnull(info.otherplan,'') as otherplan, '' as shipto,
         ifnull(head.num,'') as num, '' as revisionref,
          ifnull(date(head.voiddate),'') as voiddate, '' as returndate,
         ifnull(head.pliss,'') as pliss, '' as mlcp_freight,
         ifnull(head.tin,'') as tin,ifnull(head.sssgsis,'') as sssgsis,



         info.lname as lname2, info.fname as fname2, info.mname as mname2,info.mmname as maidname,
         info.address as truckno,
         info.province as rprovince,info.addressno as raddressno,

         case when info.iswife=0 then '0' else '1' end as iswife,
         case when info.isretired=0 then '0' else '1' end as isretired,
         case when info.isofw=0 then '0' else '1' end as isofw,

         
         
         head.contactno2,info.street as rstreet,
         ifnull(info.civilstat,'') as civilstat, '' as mstatus,
         
         info.dependentsno as mobile,info.nationality as citizenship,
         info.employer as owner,info.subdistown as rsubdistown,info.country as rcountry, info.brgy as rbrgy,
         info.sname as empfirst, info.pstreet, info.ename as emplast, info.city as rcity,
         info.paddressno,  format(info.dp,2)as dp,info.psubdistown,info.othersource,

         info.ext as ext2,format(info.value2,2) as rem, 
        
         case when info.isprc=0 then '0' else '1' end as isprc,
         case when info.isdriverlisc=0 then '0' else '1' end as isdriverlisc,
         
         info.zipcode as minimum,head.ourref,
         info.savings1 as recondate,info.savings2 as endingbal, 
         info.current1 as unclear,info.current2 as revision,
         info.others1 as ftruckname,info.others2 as frprojectname,
          format(info.mincome,2) as poref, format(info.mexp,2) as soref,
         info.pbrgy,info.appref,info.num as numdays,
        date(info.bday) as bday,info.pliss as entryot, info.tin as othrs, 
         info.sssgsis as apothrs,
        
         case when info.issenior=0 then '0' else '1' end as issenior,
         case when info.tenthirty=0 then '0' else '1' end as tenthirty,
         case when info.thirtyfifty=0 then '0' else '1' end as thirtyfifty,
         case when info.fiftyhundred=0 then '0' else '1' end as fiftyhundred,
         case when info.hundredtwofifty=0 then '0' else '1' end as hundredtwofifty,
         case when info.fivehundredup=0 then '0' else '1' end as fivehundredup,
         case when info.isemployed=0 then '0' else '1' end as isemployed,
         case when info.isselfemployed=0 then '0' else '1' end as isselfemployed,
         case when info.isplanholder=0 then '0' else '1' end as isplanholder,
         info.credits,info.payrolltype,info.employeetype,info.expiration,info.loanlimit,info.loanamt,
         info.amortization,info.penalty,info.clientname  as comakername";

        $qryselect = $qryselect . " from heahead as head
        left join heainfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where head.catrno = 0  and head.trno =?";
        return $qryselect;
    }

    public function reportsetup($config)
    {

        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config, 1);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';


        //$this->posttrans($config);

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
    }

    public function setupreport($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config, 2);
        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';

        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
    }

    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);
        $companyid = $config['params']['companyid'];

        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
