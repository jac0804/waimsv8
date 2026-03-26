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

class le
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'APPLICATION FORM';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'eahead';
    public $hhead = 'heahead';
    public $info = 'eainfo';
    public $hinfo = 'heainfo';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'purpose',
        'planid',
        'client',
        'lname',
        'fname',
        'mname',
        'mmname',
        'address',
        'province',
        'addressno',
        'contactno',
        'street',
        'civilstatus',
        'dependentsno',
        'nationality',
        'employer',
        'subdistown',
        'country',
        'brgy',
        'sname',
        'ename',
        'terms',
        'zipcode',
        'yourref',
        'email',
        'current1',
        'current2',
        'others1',
        'others2',
        'mincome',
        'mexp',
        'num',
        'voiddate',
        'pliss',
        'tin',
        'sssgsis',
        'contactno2',
        'ourref',
        'city',
        'interest',
        'monthly',
        'clientname',
        'releasedate'

    ];

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



    private $except = ['trno'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;


    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'For Review', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Approved', 'color' => 'orange'],
        ['val' => 'all', 'label' => 'All', 'color' => 'blue']
    ];

    // public $labelposted = 'Approved';
    // public $labellocked = 'For Review';

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
            'view' => 4957,
            'edit' => 4958,
            'new' => 4959,
            'save' => 4960,
            'delete' => 4961,
            'print' => 4962,
            'lock' => 4963,
            'unlock' => 4964,
            'post' => 4965,
            'unpost' => 4966
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $releasedate =  3;
        $listdate = 4;
        $listclientname = 5;
        $amt = 6;
        $cvno = 7;
        $postdate = 8;
        $getcols = ['action', 'liststatus', 'listdocument', 'releasedate', 'listdate', 'listclientname', 'amt', 'cvno', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$listclientname]['label'] = 'Borrower';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$releasedate]['label'] = 'Application Date';
        $cols[$liststatus]['name'] = 'statuscolor';
        return $cols;
    }

    public function loaddoclisting($config)
    {
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
        $orderby =  "order by  releasedate2 desc, docno desc";


        if (isset($searchfilter)) {
            $searchfield = ['head.docno', 'head.clientname', 'head.lname', 'head.mname', 'head.fname', 'cv.docno', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

            if ($searchfilter != "") {
                $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
            }
            $limit = "";
        }

        $qry = "select head.trno,head.docno,concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
        $dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end as status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,left(head.releasedate,10) as releasedate,head.releasedate as releasedate2,
        case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor,ifnull(concat(cv.bref,cv.seq),'') as cvno,format(i.amount,2) as amt
        from " . $this->head . " as head left join " . $this->tablenum . " as num 
        on num.trno=head.trno  left join cntnum as cv on cv.trno = num.cvtrno left join eainfo as i on i.trno = head.trno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
        union all
        select head.trno,head.docno,concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,$dateid,'APPROVED' as status,
        head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,left(head.releasedate,10) as releasedate,head.releasedate as releasedate2,
        'blue' as statuscolor,ifnull(concat(cv.bref,cv.seq),'') as cvno,format(i.amount,2) as amt
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num  on num.trno=head.trno left join cntnum as cv on cv.trno = num.cvtrno  left join heainfo as i on i.trno = head.trno
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition .  " 
        $orderby $limit";


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
            'post',
            'unpost',
            'lock',
            'unlock',
            'logs',
            'edit',
            'backlisting',
            'toggleup',
            'toggledown',
            'help',
            'others'
        );

        $buttons = $this->btnClass->create($btns);
        $buttons['post']['label'] = 'APPROVE';
        //$buttons['unpost']['label'] = 'CANCEL';
        $buttons['print']['label'] = 'PRINT';
        $buttons['lock']['label'] = 'FOR REVIEW';
        //$buttons['unlock']['label'] = 'REVISE';

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
        $tab = [
            'tableentry' =>
            ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']
        ];


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
        data_set($col1, 'issenior.readonly', false);
        data_set($col1, 'issenior.label', 'Certificate of Employment w/ Salary Information and Certificate of Appointment or Employment Contract w/Allotment Slip');

        data_set($col1, 'tenthirty.readonly', false);
        data_set($col1, 'tenthirty.label', 'Photocopy of government issued IDs and any valid Identification');

        data_set($col1, 'thirtyfifty.readonly', false);
        data_set($col1, 'thirtyfifty.label', 'Ownership of Collateral/Post-dated Checks/ATM/ Passbook or Withdrawal Slip of Borrower');

        data_set($col1, 'fiftyhundred.readonly', false);
        data_set($col1, 'fiftyhundred.label', 'Bank Certification that the current account used is active and properly Handled or proof of pension record');

        data_set($col1, 'hundredtwofifty.readonly', false);
        data_set($col1, 'hundredtwofifty.label', 'Picture of Collateral, Billing, Statement, Brgy. Clearance, Marriage Contract or Birth Certificate');

        data_set($col1, 'fivehundredup.readonly', false);
        data_set($col1, 'fivehundredup.label', 'Special Power of Attorney (Roxas City based)');

        data_set($col1, 'lblreceived.label', 'For Company Use Only');
        data_set($col1, 'lblreceived.style', 'font-weight:bold; font-size:13px;');

        data_set($col1, 'lblattached.label', 'Conditions/Recommendation :');
        data_set($col1, 'lblattached.style', 'font-weight:bold; font-size:13px;');

        data_set($col1, 'isemployed.readonly', false);
        data_set($col1, 'isemployed.label', 'Approved with PDC');

        data_set($col1, 'isselfemployed.readonly', false);
        data_set($col1, 'isselfemployed.label', ' Approved Salary Deduction');

        data_set($col1, 'isplanholder.readonly', false);
        data_set($col1, 'isplanholder.label', 'Approved with REM');

        data_set($col1, 'lblitemdesc.label', 'Credit Committee');
        data_set($col1, 'credits.maxlength', '150');
        data_set($col1, 'credits.type', 'textarea');
        data_set($col1, 'credits.label', '');

        $fields = ['payrolltype', 'employeetype', 'expiration', 'loanlimit', 'loanamt'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'loanlimit.readonly', false);
        data_set($col2, 'loanamt.readonly', false);

        data_set($col2, 'loanlimit.class', 'csloanlimit');
        data_set($col2, 'loanamt.class', 'csloanamt');
        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1], 'label' => 'CHECK LIST'],
            'multiinput2' => ['inputcolumn' => ['col2' => $col2], 'label' => 'APPROVING BODY'],

        ];

        // $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'leloancomputation', 'access' => 'edit'], 'label' => 'LOAN COMPUTATION'];

        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'leloan', 'access' => 'edit'], 'label' => 'LOAN COMPUTATION'];

        // $serial = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewserialhistory']];

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
            ['releasedate', 'dateid'],
            'categoryname',
            'purpose',
            ['amount', 'terms'],
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
            'sbu',
            'employer',
            'subdistown',
            ['country', 'brgy']


        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Effectivity Date');
        data_set($col1, 'amount.type', 'cinput');
        data_set($col1, 'amount.label', 'Loan Amount*');
        data_set($col1, 'amount.maxlength', 12);
        data_set($col1, 'amount.required', true);
        data_set($col1, 'amount.error', false);

        data_set($col1, 'releasedate.label', 'Application Date');

        data_set($col1, 'terms.type', 'lookup');
        data_set($col1, 'terms.action', 'lookupterms');
        data_set($col1, 'terms.lookupclass', 'ledgerterms');
        data_set($col1, 'terms.label', 'Terms');
        data_set($col1, 'terms.label', 'Terms *');
        data_set($col1, 'terms.required', true);
        data_set($col1, 'terms.error', false);

        data_set($col1, 'city.type', 'cinput');
        data_set($col1, 'city.label', 'Address & Tel. #');
        data_set($col1, 'city.maxlength', '50');

        data_set($col1, 'categoryname.label', 'Type of Loan');
        data_set($col1, 'categoryname.lookupclass', 'lookuploan');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');

        data_set($col1, 'purpose.type', 'cinput');
        data_set($col1, 'purpose.label', 'Purpose of Loan *');
        data_set($col1, 'purpose.maxlength', '50');
        data_set($col1, 'purpose.required', true);
        data_set($col1, 'purpose.error', false);

        data_set($col1, 'lblgrossprofit.label', 'Borrower Information:');
        data_set($col1, 'lblgrossprofit.style', 'font-weight:bold; font-size:13px;');
        //borrower
        data_set($col1, 'client.label', 'Borrower');
        data_set($col1, 'client.required', false);
        // customer_borrower
        data_set($col1, 'client.lookupclass', 'customer_borrower');

        data_set($col1, 'lname.type', 'cinput');
        data_set($col1, 'lname.label', 'Surname * ');
        data_set($col1, 'lname.maxlength', '50');
        data_set($col1, 'lname.error', false);

        data_set($col1, 'fname.type', 'cinput');
        data_set($col1, 'fname.label', 'Given Name * ');
        data_set($col1, 'fname.maxlength', '50');
        data_set($col1, 'fname.error', false);

        data_set($col1, 'mname.type', 'cinput');
        data_set($col1, 'mname.maxlength', '50');
        data_set($col1, 'mname.label', 'Middle Name * ');
        data_set($col1, 'mname.error', false);


        data_set($col1, 'mmname.type', 'cinput');
        data_set($col1, 'mmname.maxlength', '50');
        data_set($col1, 'mmname.required', false);

        data_set($col1, 'address.type', 'cinput');
        data_set($col1, 'address.label', 'Present Address *');
        data_set($col1, 'address.maxlength', '150');
        data_set($col1, 'address.required', true);
        data_set($col1, 'address.error', false);


        data_set($col1, 'province.type', 'cinput');
        data_set($col1, 'province.label', 'Provincial Address *');
        data_set($col1, 'province.maxlength', '150');
        data_set($col1, 'province.required', true);
        data_set($col1, 'province.error', false);

        data_set($col1, 'addressno.type', 'cinput');
        data_set($col1, 'addressno.label', 'Mailing Address *');
        data_set($col1, 'addressno.maxlength', '150');
        data_set($col1, 'addressno.required', true);
        data_set($col1, 'addressno.error', false);

        data_set($col1, 'lblcostuom.label', 'House');
        data_set($col1, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');
        data_set($col1, 'issameadd.label', 'Owned');
        data_set($col1, 'isbene.label', 'Rented');
        data_set($col1, 'ispf.label', 'Free');

        data_set($col1, 'contactno.type', 'cinput');
        data_set($col1, 'contactno.label', 'Telephone# * ');
        data_set($col1, 'contactno.maxlength', '25');
        data_set($col1, 'contactno.required', true);
        data_set($col1, 'contactno.error', false);

        data_set($col1, 'street.type', 'cinput');
        data_set($col1, 'street.label', 'Date & Place of Birth * ');
        data_set($col1, 'street.maxlength', '150');
        data_set($col1, 'street.required', true);
        data_set($col1, 'street.error', false);

        data_set($col1, 'civilstatus.label', 'Civil Status * ');
        data_set($col1, 'civilstatus.lookupclass', 'lookuples');
        data_set($col1, 'civilstatus.addedparams', ['sname', 'companyaddress', 'ename', 'city', 'pcity', 'pcountry', 'pprovince', 'monthly']);
        data_set($col1, 'civilstatus.required', true);
        data_set($col1, 'civilstatus.error', false);


        data_set($col1, 'dependentsno.type', 'cinput');
        data_set($col1, 'dependentsno.maxlength', '50');
        data_set($col1, 'dependentsno.required', false);

        data_set($col1, 'nationality.type', 'cinput');
        data_set($col1, 'nationality.maxlength', '20');

        data_set($col1, 'employer.type', 'cinput');
        data_set($col1, 'employer.maxlength', '150');
        data_set($col1, 'employer.label', 'Employer Name/Business Name *');
        data_set($col1, 'employer.required', true);
        data_set($col1, 'employer.error', false);

        data_set($col1, 'subdistown.type', 'cinput');
        data_set($col1, 'subdistown.label', 'Address & Tel. # *');
        data_set($col1, 'subdistown.maxlength', '150');
        data_set($col1, 'subdistown.required', true);
        data_set($col1, 'subdistown.error', false);

        data_set($col1, 'country.type', 'cinput');
        data_set($col1, 'country.label', 'Position Held *');
        data_set($col1, 'country.maxlength', '50');
        data_set($col1, 'country.required', true);
        data_set($col1, 'country.error', false);

        data_set($col1, 'brgy.action', 'lookuprandom');
        data_set($col1, 'brgy.lookupclass', 'lookup_staylength1');
        data_set($col1, 'brgy.label', 'Length of Stay *');
        data_set($col1, 'brgy.readonly', true);
        data_set($col1, 'brgy.required', true);
        data_set($col1, 'brgy.error', false);

        $fields = [
            'lbltotalkg',
            'sname',
            'companyaddress',
            'ename',
            'city',
            ['pcity', 'pcountry'],
            'monthly',
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
            ['current1', 'current2'],
            ['others1', 'others2'],
            ['mincome', 'mexp'],
            'pob',
            'otherplan',
            'lblrem',
            'num',
            ['voiddate', 'pliss'],
            'tin',
            'sssgsis'

        ];
        $col2 = $this->fieldClass->create($fields);

        //borrower spouse
        data_set($col2, 'lbltotalkg.label', 'Spouse Data (If Married)');
        data_set($col2, 'lbltotalkg.style', 'font-weight:bold; font-size:13px;');


        data_set($col2, 'sname.type', 'cinput');
        data_set($col2, 'sname.required', false);
        data_set($col2, 'sname.maxlength', '50');
        data_set($col2, 'companyaddress.label', 'Date & Place of Birth'); //paddress
        data_set($col2, 'companyaddress.maxlength', '150');

        data_set($col2, 'ename.type', 'cinput');
        data_set($col2, 'ename.label', 'Name of Employer/Business');
        data_set($col2, 'ename.maxlength', '50');

        data_set($col2, 'city.type', 'cinput');
        data_set($col2, 'city.maxlength', '150');
        data_set($col2, 'city.label', 'Address & Tel. #');

        data_set($col2, 'pcity.type', 'cinput');
        data_set($col2, 'pcity.label', 'Position Held');
        data_set($col2, 'pcity.maxlength', '50');

        // data_set($col2, 'pf.label', 'Monthly Income');
        data_set($col2, 'pcountry.type', 'lookup');
        data_set($col2, 'pcountry.action', 'lookuprandom');
        data_set($col2, 'pcountry.lookupclass', 'lookup_staylength3');
        data_set($col2, 'pcountry.label', 'Length of Stay');
        data_set($col1, 'pcountry.readonly', true);

        data_set($col2, 'pprovince.type', 'cinput');
        data_set($col2, 'pprovince.label', 'Immediate Superior');
        data_set($col2, 'pprovince.maxlength', '150');

        data_set($col2, 'monthly.type', 'cinput');
        data_set($col2, 'monthly.label', 'Monthly Income');
        data_set($col2, 'monthly.maxlength', 12);

        data_set($col2, 'lblshipping.label', 'Properties');
        data_set($col2, 'lblshipping.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lblbilling.label', 'Real Estate');
        data_set($col2, 'lblbilling.style', 'font-size:13px;');

        data_set($col2, 'idno.type', 'cinput');
        data_set($col2, 'idno.label', 'Location');
        data_set($col2, 'idno.maxlength', '50');

        data_set($col2, 'value.maxlength', 50);

        data_set($col2, 'isdp.label', 'Not Mortgaged');
        data_set($col2, 'isotherid.label', 'Mortgage');
        data_set($col2, 'lblacquisition.label', 'Vehicle');
        data_set($col2, 'lblacquisition.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'zipcode.type', 'cinput');
        data_set($col2, 'zipcode.label', 'Year');
        data_set($col2, 'zipcode.maxlength', '4');

        data_set($col2, 'yourref.type', 'cinput');
        data_set($col2, 'yourref.label', 'Model');
        data_set($col2, 'yourref.maxlength', '50');

        //bank
        data_set($col2, 'lbldepreciation.label', 'Bank Deposit');
        data_set($col2, 'lbldepreciation.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lbllocation.label', 'Account#');
        data_set($col2, 'lbllocation.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'lblvehicleinfo.label', 'Bank');
        data_set($col2, 'lblvehicleinfo.style', 'font-weight:bold; font-size:13px;');

        data_set($col2, 'email.type', 'cinput');
        data_set($col2, 'email.label', 'Savings account# *');
        data_set($col2, 'email.maxlength', '100');
        data_set($col2, 'email.required', true);
        data_set($col2, 'email.error', false);

        data_set($col2, 'pemail.type', 'cinput');
        data_set($col2, 'pemail.maxlength', '50');
        data_set($col2, 'pemail.label', 'Savings Bank *');
        data_set($col2, 'pemail.required', true);
        data_set($col2, 'pemail.error', false);

        data_set($col2, 'current1.type', 'cinput');
        data_set($col2, 'current1.label', 'Current account# *');
        data_set($col2, 'current1.maxlength', '50');
        // data_set($col2, 'customername.required', true);
        // data_set($col2, 'customername.error', false);

        data_set($col2, 'current2.type', 'cinput');
        data_set($col2, 'current2.label', 'Current Bank *');
        data_set($col2, 'current2.maxlength', '50');
        // data_set($col2, 'prref.required', true);
        // data_set($col2, 'prref.error', false);

        data_set($col2, 'others1.type', 'cinput');
        //data_set($col2, 'others1.name', 'others1');
        data_set($col2, 'others1.label', 'Others Account# *');
        data_set($col2, 'others1.maxlength', '50');
        // data_set($col2, 'checkinfo.required', true);
        // data_set($col2, 'checkinfo.error', false);


        data_set($col2, 'others2.type', 'cinput');
        //data_set($col2, 'others2.name', 'others2');
        data_set($col2, 'others2.label', 'Others Bank *');
        data_set($col2, 'others2.maxlength', '50');
        // data_set($col2, 'entryndiffot.required', true);
        // data_set($col2, 'entryndiffot.error', false);

        //monthly income

        data_set($col2, 'mincome.type', 'cinput');
        data_set($col2, 'mincome.label', 'Monthly Income (Applicant) *');
        data_set($col2, 'mincome.maxlength', 12);
        data_set($col2, 'mincome.required', true);
        data_set($col2, 'mincome.error', false);

        data_set($col2, 'mexp.type', 'cinput');
        data_set($col2, 'mexp.label', 'Monthly Expenses *');
        data_set($col2, 'mexp.maxlength', 12);
        data_set($col2, 'mexp.required', true);
        data_set($col2, 'mexp.error', false);

        data_set($col2, 'pob.type', 'cinput');
        //data_set($col2, 'registername.name', 'pob');
        data_set($col2, 'pob.label', 'Personal References 1');
        data_set($col2, 'pob.maxlength', '150');

        data_set($col2, 'otherplan.type', 'cinput');
        //data_set($col2, 'shipto.name', 'otherplan');
        data_set($col2, 'otherplan.label', 'Personal References 2');
        data_set($col2, 'otherplan.maxlength', '150');

        //Residence Certificate

        data_set($col2, 'lblrem.label', 'Residence Certificate');
        data_set($col2, 'lblrem.style', 'font-weight:bold; font-size:13px;');
        //data_set($col2, 'revisionref.name', 'num');
        data_set($col2, 'num.type', 'cinput');
        data_set($col2, 'num.label', 'Number');
        data_set($col2, 'num.maxlength', '50');

        data_set($col2, 'voiddate.type', 'date');
        data_set($col2, 'voiddate.label', 'Date of Issue');

        data_set($col2, 'pliss.type', 'cinput');
        //data_set($col2, 'mlcp_freight.name', 'pliss');
        data_set($col2, 'pliss.label', 'Place of Issue');
        data_set($col2, 'pliss.maxlength', '50');

        data_set($col2, 'tin.type', 'cinput');
        data_set($col2, 'tin.maxlength', '15');
        data_set($col2, 'tin.required', true);
        data_set($col2, 'tin.error', false);

        data_set($col2, 'sssgsis.type', 'cinput');
        data_set($col2, 'sssgsis.maxlength', '15');
        data_set($col2, 'sssgsis.label', 'S.S.S/G.S.I.S # * ');
        data_set($col2, 'sssgsis.required', true);
        data_set($col2, 'sssgsis.error', false);


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
                'civilstat',
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

        data_set($col3, 'lname2.type', 'cinput');
        data_set($col3, 'lname2.label', 'Last Name *');
        data_set($col3, 'lname2.maxlength', '50');
        data_set($col3, 'lname2.required', true);
        data_set($col3, 'lname2.error', false);

        data_set($col3, 'fname2.type', 'cinput');
        data_set($col3, 'fname2.maxlength', '50');
        data_set($col3, 'fname2.label', 'First Name *');
        data_set($col3, 'fname2.required', true);
        data_set($col3, 'fname2.error', false);

        data_set($col3, 'mname2.type', 'cinput');
        data_set($col3, 'mname2.maxlength', '50');
        data_set($col3, 'mname2.label', 'Middle Name *');
        data_set($col3, 'mname2.required', true);
        data_set($col3, 'mname2.error', false);

        data_set($col3, 'maidname.type', 'cinput');
        data_set($col3, 'maidname.label', 'Mothers Maiden Name');
        data_set($col3, 'maidname.maxlength', '50');
        data_set($col3, 'maidname.required', false);

        data_set($col3, 'truckno.type', 'cinput');
        data_set($col3, 'truckno.label', 'Present Address');
        data_set($col3, 'truckno.maxlength', '150');

        data_set($col3, 'rprovince.type', 'cinput');
        data_set($col3, 'rprovince.readonly', false);
        data_set($col3, 'rprovince.label', 'Provincial Address');
        data_set($col3, 'rprovince.maxlength', '150');

        data_set($col3, 'raddressno.label', 'Mailing Address');
        data_set($col3, 'raddressno.maxlength', '150');

        data_set($col3, 'lbldestination.label', 'House');
        data_set($col3, 'lbldestination.style', 'font-weight:bold; font-size:13px;');
        data_set($col3, 'iswife.label', 'Owned');
        data_set($col3, 'isretired.label', 'Rented');
        data_set($col3, 'isofw.label', 'Free');

        data_set($col3, 'contactno2.type', 'cinput');
        data_set($col3, 'contactno2.label', 'Telephone #');
        data_set($col3, 'contactno2.maxlength', '25');

        data_set($col3, 'rstreet.label', 'Date & Place of Birth');
        data_set($col3, 'rstreet.maxlength', '150');

        //data_set($col3, 'mstatus.name', 'civilstat');
        data_set($col3, 'civilstat.lookupclass', 'lookuplestatus');
        data_set($col3, 'civilstat.addedparams', ['empfirst', 'pstreet', 'emplast', 'rcity',  'paddressno',  'dp',  'psubdistown', 'othersource']);
        data_set($col3, 'civilstat.label', 'Civil Status');

        data_set($col3, 'mobile.label', 'No. Of Dependents');
        data_set($col3, 'mobile.maxlength', '50');
        data_set($col3, 'mobile.required', false);

        data_set($col3, 'citizenship.type', 'cinput');
        data_set($col3, 'citizenship.label', 'Nationality');
        data_set($col3, 'citizenship.maxlength', '50');

        data_set($col3, 'owner.label', 'Name of Employer/Business *');
        data_set($col3, 'owner.maxlength', '150');
        data_set($col3, 'owner.required', true);
        data_set($col3, 'owner.error', false);

        data_set($col3, 'rsubdistown.label', 'Address & Tel. # *');
        data_set($col3, 'rsubdistown.maxlength', '150');
        data_set($col3, 'rsubdistown.required', true);
        data_set($col3, 'rsubdistown.error', false);

        data_set($col3, 'rcountry.type', 'cinput');
        data_set($col3, 'rcountry.label', 'Position Held *');
        data_set($col3, 'rcountry.maxlength', '50');
        data_set($col3, 'rcountry.required', true);
        data_set($col3, 'rcountry.error', false);

        data_set($col3, 'rbrgy.type', 'lookup');
        data_set($col3, 'rbrgy.action', 'lookuprandom');
        data_set($col3, 'rbrgy.lookupclass', 'lookup_staylength4');
        data_set($col3, 'rbrgy.label', 'Length of Stay *');
        data_set($col3, 'rbrgy.required', true);
        data_set($col3, 'rbrgy.error', false);

        //spouse
        data_set($col3, 'lblpassbook.label', 'Spouse Data (If Married)');
        data_set($col3, 'lblpassbook.style', 'font-weight:bold; font-size:13px;');
        data_set($col3, 'empfirst.readonly', false);

        // data_set($col3, 'empfirst.type', 'lookup');
        data_set($col3, 'empfirst.label', 'Name of Spouse');

        data_set($col3, 'pstreet.label', 'Date & Place of Birth');
        data_set($col3, 'pstreet.maxlength', '150');


        data_set($col3, 'emplast.type', 'cinput');
        data_set($col3, 'emplast.readonly', false);
        data_set($col3, 'emplast.label', 'Name of Employer/Business');
        data_set($col3, 'emplast.maxlength', '50');

        data_set($col3, 'rcity.type', 'cinput');
        data_set($col3, 'rcity.readonly', false);
        data_set($col3, 'rcity.label', 'Address & Tel. #');
        data_set($col3, 'rcity.maxlength', '150');


        data_set($col3, 'paddressno.label', 'Position Held');
        data_set($col3, 'paddressno.maxlength', '50');

        data_set($col3, 'dp.type', 'cinput');
        data_set($col3, 'dp.label', 'Monthly Income');
        data_set($col3, 'dp.maxlength', 12);

        data_set($col3, 'psubdistown.type', 'lookup');
        data_set($col3, 'psubdistown.action', 'lookuprandom');
        data_set($col3, 'psubdistown.lookupclass', 'lookup_staylength2');
        data_set($col3, 'psubdistown.label', 'Length of Stay');
        data_set($col3, 'psubdistown.maxlength', '150');
        data_set($col3, 'othersource.type', 'cinput');
        data_set($col3, 'othersource.label', 'Immediate Superior');
        data_set($col3, 'othersource.maxlength', '50');


        $fields = [
            'lblapproved',
            'lbllocked',
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
            'apothrs',
            'lblaccessories',
            'cvno',
            'attorneyinfact',
            'attorneyaddress'

        ];
        $col4 = $this->fieldClass->create($fields);

        data_set($col4, 'lblaccessories.label', 'Other Information');
        data_set($col4, 'lblreconcile.label', 'Properties');
        data_set($col4, 'lblreconcile.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'lblearned.label', 'Real Estate');
        data_set($col4, 'lblearned.style', 'font-size:13px;');
        // data_set($col4, 'ndiffot.readonly', false);
        data_set($col4, 'ext2.type', 'cinput');
        data_set($col4, 'ext2.label', 'Location');
        data_set($col4, 'ext2.maxlength', '50');


        data_set($col4, 'rem.type', 'cinput');
        data_set($col4, 'rem.readonly', false);
        data_set($col4, 'rem.label', 'Value');
        data_set($col4, 'rem.maxlength', 12);

        data_set($col4, 'isprc.label', 'Not Morgaged');
        data_set($col4, 'isdriverlisc.label', 'Morgaged');
        data_set($col4, 'lblcleared.label', 'Vehicle');
        data_set($col4, 'lblcleared.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'minimum.type', 'cinput');
        data_set($col4, 'minimum.label', 'Year');
        data_set($col4, 'minimum.maxlength', '4');

        data_set($col4, 'ourref.label', 'Model');
        data_set($col4, 'ourref.maxlength', '50');

        //bank deposit
        data_set($col4, 'lblrecondate.label', 'Bank Deposit');
        data_set($col4, 'lblrecondate.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'lblendingbal.label', 'Account#');
        data_set($col4, 'lblendingbal.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'lblunclear.label', 'Bank');
        data_set($col4, 'lblunclear.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'recondate.type', 'cinput');
        data_set($col4, 'recondate.readonly', false);
        data_set($col4, 'recondate.label', 'Savings account#');
        data_set($col4, 'recondate.maxlength', '50');

        data_set($col4, 'endingbal.type', 'cinput');
        data_set($col4, 'endingbal.readonly', false);
        data_set($col4, 'endingbal.label', 'Savings Bank');
        data_set($col4, 'endingbal.maxlength', '50');

        data_set($col4, 'unclear.type', 'cinput');
        data_set($col4, 'unclear.readonly', false);
        data_set($col4, 'unclear.label', 'Current account#');
        data_set($col4, 'unclear.maxlength', '50');

        data_set($col4, 'revision.type', 'cinput');
        data_set($col4, 'revision.readonly', false);
        data_set($col4, 'revision.label', 'Current Bank');
        data_set($col4, 'revision.maxlength', '50');

        data_set($col4, 'ftruckname.type', 'cinput');
        data_set($col4, 'ftruckname.class', 'csftruckname');
        data_set($col4, 'ftruckname.label', 'Others Account#');
        data_set($col4, 'ftruckname.maxlength', '50');

        data_set($col4, 'frprojectname.type', 'cinput');
        data_set($col4, 'frprojectname.class', 'csfrproject');
        data_set($col4, 'frprojectname.label', 'Others Bank');
        data_set($col4, 'frprojectname.maxlength', '50');

        data_set($col4, 'poref.type', 'cinput');
        data_set($col4, 'poref.label', 'Monthly Income (Co-Maker)*');
        data_set($col4, 'poref.maxlength', 12);
        data_set($col4, 'poref.required', true);
        data_set($col4, 'poref.error', false);

        data_set($col4, 'soref.type', 'cinput');
        data_set($col4, 'soref.label', 'Monthly Expenses');
        data_set($col4, 'soref.maxlength', 12);

        data_set($col4, 'pbrgy.type', 'cinput');
        data_set($col4, 'pbrgy.readonly', false);
        data_set($col4, 'pbrgy.label', 'Personal References 1');
        data_set($col4, 'pbrgy.maxlength', '150');

        data_set($col4, 'appref.type', 'cinput');
        data_set($col4, 'appref.label', 'Personal References 2');
        data_set($col4, 'appref.maxlength', '150');

        data_set($col4, 'lblbranch.label', 'Residence Certificate');
        data_set($col4, 'lblbranch.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'numdays.type', 'cinput');
        data_set($col4, 'numdays.class', 'csnumdays');
        data_set($col4, 'numdays.readonly', false);
        data_set($col4, 'numdays.label', 'Number');
        data_set($col4, 'numdays.maxlength', '50');

        data_set($col4, 'bday.type', 'date');
        data_set($col4, 'bday.label', 'Date of Issue');

        data_set($col4, 'entryot.type', 'cinput');
        data_set($col4, 'entryot.readonly', false);
        data_set($col4, 'entryot.label', 'Place of Issue');
        data_set($col4, 'entryot.maxlength', '50');

        data_set($col4, 'othrs.type', 'cinput');
        data_set($col4, 'othrs.readonly', false);
        data_set($col4, 'othrs.label', 'TIN*');
        data_set($col4, 'othrs.maxlength', '15');
        data_set($col4, 'othrs.required', true);
        data_set($col4, 'othrs.error', false);

        data_set($col4, 'apothrs.type', 'cinput');
        data_set($col4, 'apothrs.readonly', false);
        data_set($col4, 'apothrs.class', 'csapothrs');
        data_set($col4, 'apothrs.label', 'SSS/GSIS No.*');
        data_set($col4, 'apothrs.maxlength', '15');
        data_set($col4, 'apothrs.required', true);
        data_set($col4, 'apothrs.error', false);

        data_set($col4, 'cvno.class', 'sbccsreadonly');


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        //col1
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['releasedate'] = $this->othersClass->getCurrentDate();
        // $data[0]['categoryname'] = '';
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
        $data[0]['nationality'] = 'FILIPINO';
        $data[0]['employer'] = '';
        $data[0]['subdistown'] = '';
        $data[0]['country'] = '';
        $data[0]['brgy'] = '';
        $data[0]['sbuid'] = '0';

        //col2
        $data[0]['sname'] = '';
        $data[0]['companyaddress'] = '';
        $data[0]['ename'] = '';
        $data[0]['city'] = '';
        $data[0]['pcity'] = '';
        $data[0]['pcountry'] = '';
        $data[0]['pprovince'] = '';
        $data[0]['amount'] = '';
        $data[0]['terms'] = '';
        $data[0]['idno'] = '';
        $data[0]['value'] = 0;
        $data[0]['isdp'] = '0';
        $data[0]['isotherid'] = '0';
        $data[0]['zipcode'] = '';
        $data[0]['yourref'] = '';
        $data[0]['email'] = '';
        $data[0]['pemail'] = '';
        $data[0]['bank1'] = '';
        $data[0]['current1'] = '';
        $data[0]['current2'] = '';
        $data[0]['others1'] = '';
        $data[0]['others2'] = '';
        $data[0]['mincome'] = 0;
        $data[0]['mexp'] = 0;
        $data[0]['pob'] = '';
        $data[0]['otherplan'] = '';
        $data[0]['num'] = 0;
        $data[0]['voiddate'] = $this->othersClass->getCurrentDate();
        $data[0]['pliss'] = '';
        $data[0]['tin'] = '';
        $data[0]['sssgsis'] = '';

        //col3
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
        $data[0]['civilstat'] = '';
        $data[0]['mobile'] = '';
        $data[0]['citizenship'] = 'FILIPINO';
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


        //col4
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
        $data[0]['nf'] = 0;

        $data[0]['monthly'] = 0;

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
        $data[0]['payrolltype'] = '';
        $data[0]['employeetype'] = '';
        $data[0]['expiration'] = '';

        $data[0]['loanlimit'] = 0;
        $data[0]['loanamt'] = 0;

        $data[0]['amortization'] = 0;
        $data[0]['penalty'] = 0;
        $data[0]['comakername'] = '';

        $data[0]['attorneyinfact'] = '';
        $data[0]['attorneyaddress'] = '';



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
        //  ifnull(head.civilstat,'') as civilstat, '' as civilstatus, 
        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $info = $this->info;
        $hinfo = $this->hinfo;
        $qryselect = "select num.center,  head.trno, 
         head.docno,left(head.dateid,10) as dateid,left(head.releasedate,10) as releasedate,     
         ifnull(r.reqtype,'') as categoryname,        
         head.interest,head.planid, info.pf, info.nf,
         head.purpose, head.client,head.lname,head.fname,head.mname,head.mmname,
         concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
         head.address,head.province,head.addressno,info.issameadd, info.isbene,info.ispf,head.contactno,head.street,    
         ifnull(head.civilstatus,'') as civilstatus, 
         head.dependentsno,head.nationality,head.employer,
         head.subdistown,head.country,head.brgy,
    
         ifnull(head.sname,'') as sname,

         ifnull(info.paddress,'')  as companyaddress,

         ifnull(head.ename,'')  as ename,
         
          ifnull(head.city,'')  as city,

          ifnull(info.pcity,'')  as pcity,

          ifnull(info.pcountry,'')  as pcountry,

          format(head.monthly, 2)  as monthly,

         ifnull(info.pprovince,'')  as pprovince,
         
         ifnull(info.pf,'') as pf, ifnull(info.nf,'') as nf, 
         format(info.amount,2) as amount,  head.terms,         
         info.idno,format(info.value,2) as value,info.isdp,info.isotherid,
         head.zipcode,head.yourref,head.email,         
         ifnull(info.bank1,'') as pemail ,
         ifnull(head.current1,'') as current1 ,
         ifnull(head.current2,'') as current2 ,
         ifnull(head.others1,'') as others1 ,
         ifnull(head.others2,'') as others2, 
         ifnull(format(head.mincome,2),'') as mincome,
         ifnull(format(head.mexp,2),'') as mexp,
         ifnull(info.pob,'') as pob, 
         ifnull(info.otherplan,'') as otherplan,
         ifnull(head.num,'') as num,
         ifnull(head.voiddate,'') as voiddate,
         ifnull(head.pliss,'') as pliss, 
         ifnull(head.tin,'') as tin,ifnull(head.sssgsis,'') as sssgsis,
         info.lname as lname2, info.fname as fname2, info.mname as mname2,info.mmname as maidname,
         info.address as truckno,
         info.province as rprovince,info.addressno as raddressno,
         info.iswife,info.isretired,info.isofw,head.contactno2,info.street as rstreet,
         ifnull(info.civilstat,'') as civilstat, 
         info.dependentsno as mobile,info.nationality as citizenship,
         info.employer as owner,info.subdistown as rsubdistown,info.country as rcountry, info.brgy as rbrgy,
       
    
         ifnull(info.sname,'')  as empfirst,

         ifnull(info.pstreet,'')  as pstreet,

          ifnull(info.ename,'')  as emplast,

         ifnull(info.city,'')  as rcity,

          ifnull(info.paddressno,'')  as paddressno,


          format(info.dp,2)  as dp,

         ifnull(info.psubdistown,'')  as psubdistown,
        
           ifnull(info.othersource,'')  as othersource,
        
        
         info.ext as ext2,format(info.value2,2) as rem, info.isprc,
         info.isdriverlisc,info.zipcode as minimum,head.ourref,
         info.savings1 as recondate,info.savings2 as endingbal, 
         info.current1 as unclear,info.current2 as revision,
         info.others1 as ftruckname,info.others2 as frprojectname,
         format(info.mincome,2) as poref, format(info.mexp,2) as soref,
         info.pbrgy,info.appref,info.num as numdays,
         info.bday,info.pliss as entryot, info.tin as othrs, 
         info.sssgsis as apothrs,
         info.issenior,info.tenthirty,info.thirtyfifty,
         info.fiftyhundred,
         info.hundredtwofifty,info.fivehundredup,
         info.isemployed,info.isselfemployed,info.isplanholder,
         info.credits,
         info.payrolltype,info.employeetype,info.expiration,info.loanlimit,info.loanamt,
         info.amortization,info.penalty, concat(info.lname,', ',info.fname,' ',info.mname)  as comakername,ifnull(cv.docno,'') as cvno,
         case ifnull(num.postdate,'') when '' then case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end else 'APPROVED' end as lblstatus,
         info.attorneyinfact,info.attorneyaddress,info.sbuid,sb.category as sbu
         ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sb on sb.line=info.sbuid
        left join cntnum as cv on cv.trno = num.cvtrno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $hinfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sb on sb.line=info.sbuid
        left join cntnum as cv on cv.trno = num.cvtrno
        where head.trno = ? and num.doc=? and num.center=? ";
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

            $acount = $this->coreFunctions->opentable("select count(*) as acount from transnum_picture where trno=?", [$head[0]->trno]);
            $hideobj = [];

            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }


            $locked = $this->coreFunctions->datareader("select ifnull(lockdate,'') as value from " . $this->head . "  where trno=?", [$trno]);
            $posted = $this->coreFunctions->datareader("select ifnull(postdate,'') as value from " . $this->tablenum . "  where trno=?", [$trno]);

            if ($posted == '') {
                if ($locked <> '') {
                    $hideobj['lblapproved'] = true;
                    $hideobj['lbllocked'] = false;
                } else {
                    $hideobj['lblapproved'] = true;
                    $hideobj['lbllocked'] = true;
                }
            } else {
                $hideobj['lblapproved'] = false;
                $hideobj['lbllocked'] = true;
            }



            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }
            return  ['head' => $head, 'griddata' => ['accounting' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or you are not allowed to view the information...'];
        }
    }


    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        $info = [];

        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }

        foreach ($this->fields as $key) {
            $data[$key] = $head[$key];
            if (!in_array($key, $this->except)) {
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            } //end if    
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['clientname'] = $head['lname'] . ', ' . $head['fname'] . ' ' . $head['mname'];

        $info['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $info['editby'] = $config['params']['user'];

        $info['trno'] = $head['trno'];

        $info['issameadd'] = $head['issameadd']; //owned
        $info['isbene'] = $head['isbene']; //rented
        $info['ispf'] = $head['ispf']; //free

        $info['paddress'] = $head['companyaddress'];
        $info['city'] = $head['city'];
        $info['pcity'] = $head['pcity'];
        $info['pcountry'] = $head['pcountry'];
        $info['pprovince'] = $head['pprovince'];

        $info['amount'] = $this->othersClass->sanitizekeyfield('amount',  $head['amount']);

        $info['idno'] = $head['idno'];
        $info['value'] = $this->othersClass->sanitizekeyfield('value',  $head['value']);
        $info['expiration'] = $head['expiration'];
        $info['isdp'] = $head['isdp'];
        $info['isotherid'] = $head['isotherid'];

        $info['pob'] = $head['pob'];
        $info['otherplan'] = $head['otherplan'];

        $info['lname'] = $head['lname2'];
        $info['fname'] = $head['fname2'];
        $info['mname'] = $head['mname2'];
        $info['mmname'] = $head['maidname'];
        $info['address'] = $head['truckno'];

        $info['province'] = $head['rprovince'];
        $info['addressno'] = $head['raddressno'];
        $info['iswife'] = $head['iswife'];
        $info['isretired'] = $head['isretired'];
        $info['isofw'] = $head['isofw'];
        $info['street'] = $head['rstreet'];
        $info['civilstat'] = $head['civilstat'];
        $info['dependentsno'] = $head['mobile'];
        $info['nationality'] = $head['citizenship'];
        $info['employer'] = $head['owner'];
        $info['subdistown'] = $head['rsubdistown'];
        $info['country'] = $head['rcountry'];
        $info['brgy'] = $head['rbrgy'];
        $info['sname'] = $head['empfirst'];
        $info['pstreet'] = $head['pstreet'];
        $info['ename'] = $head['emplast'];
        $info['city'] = $head['rcity'];
        $info['paddressno'] = $head['paddressno'];
        $info['dp'] = $this->othersClass->sanitizekeyfield('dp',  $head['dp']);
        $info['psubdistown'] = $head['psubdistown'];
        $info['othersource'] = $head['othersource'];

        $info['bank1'] = $head['pemail'];
        $info['ext'] = $head['ext2'];
        $info['value2'] = $this->othersClass->sanitizekeyfield('amt',  $head['rem']);
        // $info['value2'] = $head['rem'];
        $info['isprc'] = $head['isprc'];
        $info['isdriverlisc'] = $head['isdriverlisc'];
        $info['zipcode'] = $head['minimum'];

        $info['savings1'] = $head['recondate'];
        $info['savings2'] = $head['endingbal'];
        $info['current1'] = $head['unclear'];
        $info['current2'] = $head['revision'];

        $info['others1'] = $head['ftruckname'];
        $info['others2'] = $head['frprojectname'];
        $info['mincome'] = $this->othersClass->sanitizekeyfield('mincome',  $head['poref']);
        // $info['mincome'] = $head['poref'];
        // $info['mexp'] = $head['soref'];
        $info['mexp'] = $this->othersClass->sanitizekeyfield('mexp',  $head['soref']);

        $info['pbrgy'] = $head['pbrgy'];
        $info['appref'] = $head['appref'];
        $info['num'] = $head['numdays'];
        $info['bday'] = $this->othersClass->sanitizekeyfield('bday',  $head['bday']);
        $info['pliss'] = $head['entryot'];

        $info['tin'] = $head['othrs'];
        $info['sssgsis'] = $head['apothrs'];

        $info['issenior'] = $head['issenior'];
        $info['tenthirty'] = $head['tenthirty'];


        $info['thirtyfifty'] = $head['thirtyfifty'];
        $info['fiftyhundred'] = $head['fiftyhundred'];
        $info['hundredtwofifty'] = $head['hundredtwofifty'];
        $info['fivehundredup'] = $head['fivehundredup'];
        $info['isemployed'] = $head['isemployed'];
        $info['isselfemployed'] = $head['isselfemployed'];
        $info['isplanholder'] = $head['isplanholder'];
        $info['credits'] = $head['credits'];

        $info['payrolltype'] = $head['payrolltype'];
        $info['employeetype'] = $head['employeetype'];
        $info['expiration'] = $head['expiration'];
        $info['loanlimit'] = $head['loanlimit'];
        $info['loanamt'] = $head['loanamt'];

        $info['amortization'] = $head['amortization'];
        $info['penalty'] = $head['penalty'];
        $info['clientname'] = $head['lname2'] . ', ' . $head['fname2'] . ' ' . $head['mname2'];
        $info['pf'] = $this->othersClass->sanitizekeyfield('pf',  $head['pf']);
        $info['nf'] = $this->othersClass->sanitizekeyfield('nf',  $head['nf']);
        $data['dateid'] = $this->othersClass->sanitizekeyfield('dateid',  $head['dateid']);

        $info['attorneyinfact'] = $head['attorneyinfact'];
        $info['attorneyaddress'] = $head['attorneyaddress'];
        $info['sbuid'] = $head['sbuid'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            // for info table
            $exist = $this->coreFunctions->getfieldvalue($this->info, "trno", "trno=?", [$head['trno']]);
            if (floatval($exist) <> 0) {
                $this->coreFunctions->sbcupdate($this->info, $info, ['trno' => $head['trno']]);
            } else {
                $this->coreFunctions->sbcinsert($this->info, $info);
            }
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);

            // for info table
            $this->coreFunctions->sbcinsert($this->info, $info);
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
        $this->coreFunctions->execqry('delete from ' . $this->info . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    private function createclient($config)
    {
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $data = [];
        $cinfo = [];

        $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

        if ($client != '') {
            return ['status' => false, 'msg' => 'Already have customer profile.'];
        }

        $client = $this->coreFunctions->getfieldvalue($this->head, "concat(lname,fname,mname)", "trno=?", [$trno]);
        if ($client == '') {
            return ['status' => false, 'msg' => 'Please complete details for payor.'];
        }

        $client = $this->coreFunctions->getfieldvalue("eainfo", "concat(lname,fname,mname)", "trno=?", [$trno]);
        if ($client == '') {
            return ['status' => false, 'msg' => 'Please complete details for plan holder.'];
        }

        $clientcode = $this->getnewclient($config); // create customer



        $qry = "select 
        head.clientname, head.address, head.contactno, head.terms, 
        head.agent,head.email,head.fname,head.lname,head.mname,head.addressno,
        head.street,head.subdistown,head.city,head.country,head.zipcode,info.isplanholder,info.tin,head.ext,
        info.issenior,

        head.mmname,

        head.planid,
        head.purpose,
        head.address,
        head.province,
        head.addressno,
        head.contactno,
        head.street,
        head.civilstatus,
        head.sname,
        head.ename,
        head.city,
        head.terms,
        head.zipcode,
        head.yourref,
        head.email,
        head.pemail,
        head.current1,
        head.current2,
        head.others1,
        head.others2,
        head.mincome,
        head.mexp,
        head.num,
        head.voiddate,
        head.pliss,
        head.tin as htin,
        head.sssgsis,
        head.monthly,

        head.employer,
        head.country,
        head.brgy,
        head.subdistown,

        head.dependentsno,

        info.amount,
        info.issameadd,
        info.isbene,
        info.ispf,
        
        info.nationality,
        
        info.paddress,
        info.pcity,
        info.pcountry,
        info.pprovince,
        info.idno,
        info.value,
        info.isdp,
        info.isotherid,
        info.bank1,
        info.pob,
        info.otherplan,info.sbuid
        

        from " . $this->head . " as head 
        left join eainfo as info on info.trno = head.trno 
        left join reqcategory as r on r.line=head.planid
        where head.trno = ? limit 1 ";
        $res = $this->coreFunctions->opentable($qry, [$trno]);


        $data['client'] = $clientcode;
        $data['clientname'] = $res[0]->lname . ', ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->ext;
        $data['addr'] = $res[0]->address;
        $data['tel'] = $res[0]->contactno;
        $data['terms'] = $res[0]->terms;
        $data['status'] = 'ACTIVE';
        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];
        $data['iscustomer'] = 1;
        $data['center'] = $center;
        $data['tin'] = $res[0]->tin;
        //create client
        $clientid = $this->coreFunctions->insertGetId('client', $data);
        $this->logger->sbcwritelog($clientid, $config, 'CREATE', 'Application - ' . $clientid . ' - ' . $clientcode . ' - ' . $res[0]->fname . ' ' . $res[0]->mname . ' ' . $res[0]->lname);

        $cinfo['clientid'] = $clientid;
        $cinfo['lname'] = $res[0]->lname;
        $cinfo['fname'] = $res[0]->fname;
        $cinfo['mname'] = $res[0]->mname;
        $cinfo['addr'] = $res[0]->address;

        $cinfo['mmname'] = $res[0]->mmname;

        $cinfo['planid'] = $res[0]->planid;
        $cinfo['purpose'] = $res[0]->purpose;
        $cinfo['address'] = $res[0]->address;
        $cinfo['province'] = $res[0]->province;
        $cinfo['addressno'] = $res[0]->addressno;
        $cinfo['contactno'] = $res[0]->contactno;
        $cinfo['street'] = $res[0]->street;
        $cinfo['civilstatus'] = $res[0]->civilstatus;
        $cinfo['sname'] = $res[0]->sname;
        $cinfo['ename'] = $res[0]->ename;
        $cinfo['city'] = $res[0]->city;
        $cinfo['terms'] = $res[0]->terms;
        $cinfo['zipcode'] = $res[0]->zipcode;
        $cinfo['yourref'] = $res[0]->yourref;
        $cinfo['email'] = $res[0]->email;
        $cinfo['pemail'] = $res[0]->pemail;
        $cinfo['current1'] = $res[0]->current1;
        $cinfo['current2'] = $res[0]->current2;
        $cinfo['others1'] = $res[0]->others1;
        $cinfo['others2'] = $res[0]->others2;
        $cinfo['mincome'] = $res[0]->mincome;
        $cinfo['mexp'] = $res[0]->mexp;
        $cinfo['num'] = $res[0]->num;
        $cinfo['voiddate'] = $res[0]->voiddate;
        $cinfo['pliss'] = $res[0]->pliss;
        $cinfo['tin'] = $res[0]->htin;
        $cinfo['sssgsis'] = $res[0]->sssgsis;
        $cinfo['monthly'] = $res[0]->monthly;


        $cinfo['employer'] = $res[0]->employer;
        $cinfo['country'] = $res[0]->country;
        $cinfo['brgy'] = $res[0]->brgy;
        $cinfo['subdistown'] = $res[0]->subdistown;

        $cinfo['amount'] = $res[0]->amount;
        $cinfo['issameadd'] = $res[0]->issameadd;
        $cinfo['isbene'] = $res[0]->isbene;
        $cinfo['ispf'] = $res[0]->ispf;
        $cinfo['dependentsno'] = $res[0]->dependentsno;
        $cinfo['nationality'] = $res[0]->nationality;
        $cinfo['companyaddress'] = $res[0]->paddress;
        $cinfo['pcity'] = $res[0]->pcity;
        $cinfo['pcountry'] = $res[0]->pcountry;
        $cinfo['pprovince'] = $res[0]->pprovince;
        $cinfo['idno'] = $res[0]->idno;
        $cinfo['value'] = $res[0]->value;
        $cinfo['isdp'] = $res[0]->isdp;
        $cinfo['isotherid'] = $res[0]->isotherid;
        $cinfo['bank1'] = $res[0]->bank1;
        $cinfo['pob'] = $res[0]->pob;
        $cinfo['otherplan'] = $res[0]->otherplan;
        $cinfo['sbuid'] = $res[0]->sbuid;

        if ($clientid != 0) {
            $this->coreFunctions->sbcinsert('clientinfo', $cinfo);
        }

        $this->coreFunctions->execqry("update " . $this->head . " set client = ? where trno = ?", 'update', [$clientcode, $trno]);

        return true;
    }

    private function getnewclient($config)
    {
        $pref = 'CL';
        $docnolength =  $this->companysetup->getclientlength($config['params']);
        $last = $this->othersClass->getlastclient($pref, 'customer');
        $start = $this->othersClass->SearchPosition($last);
        $seq = substr($last, $start) + 1;
        $poseq = $pref . $seq;
        $newclient = $this->othersClass->PadJ($poseq, $docnolength);
        return $newclient;
    }

    //  to follow posting
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->head . ' where trno=? and lockdate is null', [$trno]);

        if ($docno != "") {
            return ['status' => false, 'msg' => 'Posting failed. Not yet submitted for Review.'];
        }

        $exist = $this->coreFunctions->datareader('select trno as value from tempdetailinfo where trno=?', [$trno]);

        if ($exist == "") {
            return ['status' => false, 'msg' => 'Posting failed. Please generate Loan Schedule.'];
        }

        $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

        if ($client == '') {
            $this->createclient($config);
        }

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,planid,
         purpose,client,lname,fname,mname,mmname,address,province,addressno,contactno,street,
         civilstatus,dependentsno,nationality,employer,subdistown,country,brgy,
         sname,ename,city,terms,zipcode,yourref,email,current1,current2,others1,others2,
         mincome,mexp,num,voiddate,pliss,tin,sssgsis,contactno2,ourref,monthly,interest,clientname,
         lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,releasedate)

         select trno,doc,docno,dateid,planid,
         purpose,client,lname,fname,mname,mmname,address,province,addressno,contactno,street,
         civilstatus,dependentsno,nationality,employer,subdistown,country,brgy,
         sname,ename,city,terms,zipcode,yourref,email,current1,current2,others1,others2,
         mincome,mexp,num,voiddate,pliss,tin,sssgsis,contactno2,ourref,monthly,interest,clientname,
         lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,releasedate
         from " . $this->head . " where trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $qry = "insert into " . $this->hinfo . "(trno,issameadd,isbene,ispf,
            paddress,pf,nf,pcity,pcountry,pprovince,amount,idno,
            isdp,isotherid,pob, otherplan,
            lname,fname,mname,mmname,address,province,addressno,
            iswife,isretired,isofw,street,civilstat,dependentsno,nationality,employer,
            subdistown,country,brgy,sname,pstreet,ename,city,paddressno,dp,psubdistown,
            othersource,ext,value2,isprc,isdriverlisc,zipcode,savings1,savings2,
            current1,current2,others1,others2,mincome,mexp,pbrgy,appref,num,bday,pliss,
            tin,sssgsis,
            issenior,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,
            fivehundredup,isemployed,isselfemployed,isplanholder,
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid)
            select trno,issameadd,isbene,ispf,paddress,pf,nf,
            pcity,pcountry,pprovince,amount,idno,
            isdp,isotherid,pob, otherplan,
            lname,fname,mname,mmname,address,province,addressno,
            iswife,isretired,isofw,street,civilstat,dependentsno,nationality,employer,
            subdistown,country,brgy,sname,pstreet,ename,city,paddressno,dp,psubdistown,
            othersource,ext,value2,isprc,isdriverlisc,zipcode,savings1,savings2,
            current1,current2,others1,others2,mincome,mexp,pbrgy,appref,num,bday,pliss,
            tin,sssgsis,
            issenior,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,
            fivehundredup,isemployed,isselfemployed,isplanholder,
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid
            from " . $this->info . " where trno=? limit 1";
            $postinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

            if ($postinfo) {
                $qry = "insert into htempdetailinfo (trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid) select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid from tempdetailinfo where trno = ?";
                $postdets = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
                if ($postdets) {
                    $date = $this->othersClass->getCurrentTimeStamp();
                    $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                    $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                    $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from " . $this->info . " where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from tempdetailinfo where trno=?", "delete", [$trno]);
                    $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                    $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
                } else {
                    $this->coreFunctions->execqry("delete from " . $this->hinfo . " where trno=?", "delete", [$trno]);
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Schedule'];
                }
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Info'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];

        $lplan = $this->coreFunctions->datareader('select cvtrno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if (floatval($lplan) != 0) {
            return ['status' => false, 'msg' => 'Unpost FAILED, Already issued...'];
        }

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);


        if (!$this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Unpost FAILED, Already unposted...'];
        }

        $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,planid,
         purpose,client,lname,fname,mname,mmname,address,province,addressno,contactno,street,
         civilstatus,dependentsno,nationality,employer,subdistown,country,brgy,
         sname,ename,city,terms,zipcode,yourref,email,current1,current2,others1,others2,
         mincome,mexp,num,voiddate,pliss,tin,sssgsis,contactno2,ourref,monthly,interest,clientname,
         lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,releasedate)

         select trno,doc,docno,dateid,planid,
         purpose,client,lname,fname,mname,mmname,address,province,addressno,contactno,street,
         civilstatus,dependentsno,nationality,employer,subdistown,country,brgy,
         sname,ename,city,terms,zipcode,yourref,email,current1,current2,others1,others2,
         mincome,mexp,num,voiddate,pliss,tin,sssgsis,contactno2,ourref,monthly,interest,clientname,
         lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate,releasedate
         from " . $this->hhead . " where trno=? limit 1";
        $unposthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($unposthead) {
            $qry = "insert into " . $this->info . "(trno,issameadd,isbene,ispf,
            paddress,pcity,pf,nf,pcountry,pprovince,amount,idno,
            isdp,isotherid,pob, otherplan,
            lname,fname,mname,mmname,address,province,addressno,
            iswife,isretired,isofw,street,civilstat,dependentsno,nationality,employer,
            subdistown,country,brgy,sname,pstreet,ename,city,paddressno,dp,psubdistown,
            othersource,ext,value2,isprc,isdriverlisc,zipcode,savings1,savings2,
            current1,current2,others1,others2,mincome,mexp,pbrgy,appref,num,bday,pliss,
            tin,sssgsis,
            issenior,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,
            fivehundredup,isemployed,isselfemployed,isplanholder,
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid)
            select  trno,issameadd,isbene,ispf,
            paddress,pcity,pf,nf,pcountry,pprovince,amount,idno,
            isdp,isotherid,pob, otherplan,
            lname,fname,mname,mmname,address,province,addressno,
            iswife,isretired,isofw,street,civilstat,dependentsno,nationality,employer,
            subdistown,country,brgy,sname,pstreet,ename,city,paddressno,dp,psubdistown,
            othersource,ext,value2,isprc,isdriverlisc,zipcode,savings1,savings2,
            current1,current2,others1,others2,mincome,mexp,pbrgy,appref,num,bday,pliss,
            tin,sssgsis,
            issenior,tenthirty,thirtyfifty,fiftyhundred,hundredtwofifty,
            fivehundredup,isemployed,isselfemployed,isplanholder,
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid
            from " . $this->hinfo . " where trno=? limit 1";
            $unpostinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

            if ($unpostinfo) {
                $qry = "insert into tempdetailinfo (trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid) select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid from htempdetailinfo where trno = ?";
                $postdets = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
                if ($postdets) {
                    $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                    $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from " . $this->hinfo . " where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from htempdetailinfo where trno=?", "delete", [$trno]);
                    $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);

                    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
                } else {
                    $this->coreFunctions->execqry("delete from " . $this->info . " where trno=?", "delete", [$trno]);
                    $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                    return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Schedule'];
                }
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Info'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error on Unposting Head'];
        }
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

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function reportsetup($config)
    {
        $isposted = $this->othersClass->isposted($config);
        if (!$isposted) return ['status' => false, 'msg' => 'Application not yet posted.'];
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
        $companyid = $config['params']['companyid'];

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
