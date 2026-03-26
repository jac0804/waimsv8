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
use Exception;

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

    public $detail = 'ladetail';
    public $hdetail = 'gldetail';

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
        'releasedate',
        'plangrpid'

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
        // $action = 0;
        // $liststatus = 1;
        // $listdocument = 2;
        // $releasedate =  3;
        // $listdate = 4;
        // $listclientname = 5;
        // $amt = 6;
        // $cvno = 7;
        // $postdate = 8;
        $getcols = ['action', 'liststatus', 'listdocument', 'releasedate', 'listdate', 'listclient', 'listclientname', 'amt', 'cvno','loantype', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listclientname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
        $cols[$loantype]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listclientname]['label'] = 'Borrower';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$releasedate]['label'] = 'Application Date';
        $cols[$listdate]['label'] = 'Effectivity Date';
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
            $searchfield = ['head.docno', 'head.clientname', 'head.lname', 'head.mname', 'head.fname', 'cv.docno', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby','r.reqtype'];

            if ($searchfilter != "") {
                $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
            }
            $limit = "";
        }

        $qry = "select head.trno,head.docno,head.client,concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
        $dateid, case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end as status,
        head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,left(head.releasedate,10) as releasedate,head.releasedate as releasedate2,
        case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor,ifnull(concat(cv.bref,cv.seq),'') as cvno,format(i.amount,2) as amt,ifnull(r.reqtype,'') as loantype
        from " . $this->head . " as head left join " . $this->tablenum . " as num 
        on num.trno=head.trno  left join cntnum as cv on cv.trno = num.cvtrno left join eainfo as i on i.trno = head.trno left join reqcategory as r on r.line = head.planid
        where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " 
        union all
        select head.trno,head.docno,head.client,concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,$dateid,'APPROVED' as status,
        head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,left(head.releasedate,10) as releasedate,head.releasedate as releasedate2,
        'blue' as statuscolor,ifnull(concat(cv.bref,cv.seq),'') as cvno,format(i.amount,2) as amt,ifnull(r.reqtype,'') as loantype
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num  on num.trno=head.trno left join cntnum as cv on cv.trno = num.cvtrno  left join heainfo as i on i.trno = head.trno
        left join reqcategory as r on r.line = head.planid
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

        $buttons['others']['items']['generatejv'] = ['label' => 'Generate Takout Fees Entry', 'todo' => ['lookupclass' => 'generatejv', 'action' => 'generatejv', 'access' => 'view', 'type' => 'navigation']];

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
            'lblgrossprofit',
            'client',
            'lname',
            'fname',
            'mname',
            'mmname',
            'address',
            'province',
            'addressno'
        ];
        $col1 = $this->fieldClass->create($fields);

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

        $fields = [
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
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'lblcostuom.label', 'House');
        data_set($col2, 'lblcostuom.style', 'font-weight:bold; font-size:13px;');
        data_set($col2, 'issameadd.label', 'Owned');
        data_set($col2, 'isbene.label', 'Rented');
        data_set($col2, 'ispf.label', 'Free');

        data_set($col2, 'contactno.type', 'cinput');
        data_set($col2, 'contactno.label', 'Telephone# * ');
        data_set($col2, 'contactno.maxlength', '25');
        data_set($col2, 'contactno.required', true);
        data_set($col2, 'contactno.error', false);

        data_set($col2, 'street.type', 'cinput');
        data_set($col2, 'street.label', 'Date & Place of Birth * ');
        data_set($col2, 'street.maxlength', '150');
        data_set($col2, 'street.required', true);
        data_set($col2, 'street.error', false);

        data_set($col2, 'civilstatus.label', 'Civil Status * ');
        data_set($col2, 'civilstatus.lookupclass', 'lookuples');
        data_set($col2, 'civilstatus.addedparams', ['sname', 'companyaddress', 'ename', 'city', 'pcity', 'pcountry', 'pprovince', 'monthly']);
        data_set($col2, 'civilstatus.required', true);
        data_set($col2, 'civilstatus.error', false);


        data_set($col2, 'dependentsno.type', 'cinput');
        data_set($col2, 'dependentsno.maxlength', '50');
        data_set($col2, 'dependentsno.required', false);

        data_set($col2, 'nationality.type', 'cinput');
        data_set($col2, 'nationality.maxlength', '20');

        data_set($col2, 'employer.type', 'cinput');
        data_set($col2, 'employer.maxlength', '150');
        data_set($col2, 'employer.label', 'Employer Name/Business Name *');
        data_set($col2, 'employer.required', true);
        data_set($col2, 'employer.error', false);

        data_set($col2, 'subdistown.type', 'cinput');
        data_set($col2, 'subdistown.label', 'Address & Tel. # *');
        data_set($col2, 'subdistown.maxlength', '150');
        data_set($col2, 'subdistown.required', true);
        data_set($col2, 'subdistown.error', false);

        data_set($col2, 'country.type', 'cinput');
        data_set($col2, 'country.label', 'Position Held *');
        data_set($col2, 'country.maxlength', '50');
        data_set($col2, 'country.required', true);
        data_set($col2, 'country.error', false);

        data_set($col2, 'brgy.action', 'lookuprandom');
        data_set($col2, 'brgy.lookupclass', 'lookup_staylength1');
        data_set($col2, 'brgy.label', 'Length of Stay *');
        data_set($col2, 'brgy.readonly', true);
        data_set($col2, 'brgy.required', true);
        data_set($col2, 'brgy.error', false);

        $fields = [
            'lbltotalkg',
            'sname',
            'companyaddress',
            'ename',
            'city',
            ['pcity', 'pcountry'],
            'monthly',
            'pprovince',
        ];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lbltotalkg.label', 'Spouse Data (If Married)');
        data_set($col3, 'lbltotalkg.style', 'font-weight:bold; font-size:13px;');


        data_set($col3, 'sname.type', 'cinput');
        data_set($col3, 'sname.required', false);
        data_set($col3, 'sname.maxlength', '50');
        data_set($col3, 'companyaddress.label', 'Date & Place of Birth'); //paddress
        data_set($col3, 'companyaddress.maxlength', '150');

        data_set($col3, 'ename.type', 'cinput');
        data_set($col3, 'ename.label', 'Name of Employer/Business');
        data_set($col3, 'ename.maxlength', '50');

        data_set($col3, 'city.type', 'cinput');
        data_set($col3, 'city.maxlength', '150');
        data_set($col3, 'city.label', 'Address & Tel. #');

        data_set($col3, 'pcity.type', 'cinput');
        data_set($col3, 'pcity.label', 'Position Held');
        data_set($col3, 'pcity.maxlength', '50');


        data_set($col3, 'pcountry.type', 'lookup');
        data_set($col3, 'pcountry.action', 'lookuprandom');
        data_set($col3, 'pcountry.lookupclass', 'lookup_staylength3');
        data_set($col3, 'pcountry.label', 'Length of Stay');
        data_set($col3, 'pcountry.readonly', true);

        data_set($col3, 'pprovince.type', 'cinput');
        data_set($col3, 'pprovince.label', 'Immediate Superior');
        data_set($col3, 'pprovince.maxlength', '150');

        data_set($col3, 'monthly.type', 'cinput');
        data_set($col3, 'monthly.label', 'Monthly Income');
        data_set($col3, 'monthly.maxlength', 12);

        $fields = [
            'lblshipping',
            'lblbilling',
            ['idno', 'value'],
            ['isdp', 'isotherid'],
            'lblacquisition',
            ['zipcode', 'yourref']
        ];
        $col4 = $this->fieldClass->create($fields);

        data_set($col4, 'lblshipping.label', 'Properties');
        data_set($col4, 'lblshipping.style', 'font-weight:bold; font-size:13px;');
        data_set($col4, 'lblbilling.label', 'Real Estate');
        data_set($col4, 'lblbilling.style', 'font-size:13px;');

        data_set($col4, 'idno.type', 'cinput');
        data_set($col4, 'idno.label', 'Location');
        data_set($col4, 'idno.maxlength', '50');

        data_set($col4, 'value.maxlength', 50);

        data_set($col4, 'isdp.label', 'Not Mortgaged');
        data_set($col4, 'isotherid.label', 'Mortgage');
        data_set($col4, 'lblacquisition.label', 'Vehicle');
        data_set($col4, 'lblacquisition.style', 'font-weight:bold; font-size:13px;');

        data_set($col4, 'zipcode.type', 'cinput');
        data_set($col4, 'zipcode.label', 'Year');
        data_set($col4, 'zipcode.maxlength', '4');

        data_set($col4, 'yourref.type', 'cinput');
        data_set($col4, 'yourref.label', 'Model');
        data_set($col4, 'yourref.maxlength', '50');


        $fields = [
            'lbldepreciation',
            ['lbllocation', 'lblvehicleinfo'],
            ['email', 'pemail'],
            ['current1', 'current2'],
            ['others1', 'others2'],
            ['mincome', 'mexp'],
            'pob',
            'otherplan'
        ];
        $col5 = $this->fieldClass->create($fields);
        //bank
        data_set($col5, 'lbldepreciation.label', 'Bank Deposit');
        data_set($col5, 'lbldepreciation.style', 'font-weight:bold; font-size:13px;');
        data_set($col5, 'lbllocation.label', 'Account#');
        data_set($col5, 'lbllocation.style', 'font-weight:bold; font-size:13px;');
        data_set($col5, 'lblvehicleinfo.label', 'Bank');
        data_set($col5, 'lblvehicleinfo.style', 'font-weight:bold; font-size:13px;');

        data_set($col5, 'email.type', 'cinput');
        data_set($col5, 'email.label', 'Savings account# *');
        data_set($col5, 'email.maxlength', '100');
        data_set($col5, 'email.required', true);
        data_set($col5, 'email.error', false);

        data_set($col5, 'pemail.type', 'cinput');
        data_set($col5, 'pemail.maxlength', '50');
        data_set($col5, 'pemail.label', 'Savings Bank *');
        data_set($col5, 'pemail.required', true);
        data_set($col5, 'pemail.error', false);

        data_set($col5, 'current1.type', 'cinput');
        data_set($col5, 'current1.label', 'Current account# *');
        data_set($col5, 'current1.maxlength', '50');


        data_set($col5, 'current2.type', 'cinput');
        data_set($col5, 'current2.label', 'Current Bank *');
        data_set($col5, 'current2.maxlength', '50');

        data_set($col5, 'others1.type', 'cinput');

        data_set($col5, 'others1.label', 'Others Account# *');
        data_set($col5, 'others1.maxlength', '50');



        data_set($col5, 'others2.type', 'cinput');

        data_set($col5, 'others2.label', 'Others Bank *');
        data_set($col5, 'others2.maxlength', '50');


        //monthly income

        data_set($col5, 'mincome.type', 'cinput');
        data_set($col5, 'mincome.label', 'Monthly Income (Applicant) *');
        data_set($col5, 'mincome.maxlength', 12);
        data_set($col5, 'mincome.required', true);
        data_set($col5, 'mincome.error', false);

        data_set($col5, 'mexp.type', 'cinput');
        data_set($col5, 'mexp.label', 'Monthly Expenses *');
        data_set($col5, 'mexp.maxlength', 12);
        data_set($col5, 'mexp.required', true);
        data_set($col5, 'mexp.error', false);

        data_set($col5, 'pob.type', 'cinput');

        data_set($col5, 'pob.label', 'Personal References 1');
        data_set($col5, 'pob.maxlength', '150');

        data_set($col5, 'otherplan.type', 'cinput');

        data_set($col5, 'otherplan.label', 'Personal References 2');
        data_set($col5, 'otherplan.maxlength', '150');

        $fields = [
            'lblrem',
            'num',
            ['voiddate', 'pliss'],
            'tin',
            'sssgsis'
        ];
        $col6 = $this->fieldClass->create($fields);
        //Residence Certificate

        data_set($col6, 'lblrem.label', 'Residence Certificate');
        data_set($col6, 'lblrem.style', 'font-weight:bold; font-size:13px;');

        data_set($col6, 'num.type', 'cinput');
        data_set($col6, 'num.label', 'Number');
        data_set($col6, 'num.maxlength', '50');

        data_set($col6, 'voiddate.type', 'date');
        data_set($col6, 'voiddate.label', 'Date of Issue');

        data_set($col6, 'pliss.type', 'cinput');

        data_set($col6, 'pliss.label', 'Place of Issue');
        data_set($col6, 'pliss.maxlength', '50');

        data_set($col6, 'tin.type', 'cinput');
        data_set($col6, 'tin.maxlength', '15');
        data_set($col6, 'tin.required', true);
        data_set($col6, 'tin.error', false);

        data_set($col6, 'sssgsis.type', 'cinput');
        data_set($col6, 'sssgsis.maxlength', '15');
        data_set($col6, 'sssgsis.label', 'S.S.S/G.S.I.S # * ');
        data_set($col6, 'sssgsis.required', true);
        data_set($col6, 'sssgsis.error', false);

        $fields = [
            'lblaccessories',
            'attorneyinfact',
            'attorneyaddress'
        ];
        $col7 = $this->fieldClass->create($fields);

        data_set($col7, 'lblaccessories.label', 'Other Information');

        $fields = [
            'lblsource',
            'lname2',
            'fname2',
            'mname2',
            'maidname',
            'truckno',
            'rprovince',
            'raddressno',
        ];
        $col8 = $this->fieldClass->create($fields);
        data_set($col8, 'lblsource.label', 'Co Maker Information: ');
        data_set($col8, 'lblsource.style', 'font-weight:bold; font-size:13px;');

        data_set($col8, 'lname2.type', 'cinput');
        data_set($col8, 'lname2.label', 'Last Name *');
        data_set($col8, 'lname2.maxlength', '50');
        data_set($col8, 'lname2.required', true);
        data_set($col8, 'lname2.error', false);

        data_set($col8, 'fname2.type', 'cinput');
        data_set($col8, 'fname2.maxlength', '50');
        data_set($col8, 'fname2.label', 'First Name *');
        data_set($col8, 'fname2.required', true);
        data_set($col8, 'fname2.error', false);

        data_set($col8, 'mname2.type', 'cinput');
        data_set($col8, 'mname2.maxlength', '50');
        data_set($col8, 'mname2.label', 'Middle Name *');
        data_set($col8, 'mname2.required', true);
        data_set($col8, 'mname2.error', false);

        data_set($col8, 'maidname.type', 'cinput');
        data_set($col8, 'maidname.label', 'Mothers Maiden Name');
        data_set($col8, 'maidname.maxlength', '50');
        data_set($col8, 'maidname.required', false);

        data_set($col8, 'truckno.type', 'cinput');
        data_set($col8, 'truckno.label', 'Present Address');
        data_set($col8, 'truckno.maxlength', '150');

        data_set($col8, 'rprovince.type', 'cinput');
        data_set($col8, 'rprovince.readonly', false);
        data_set($col8, 'rprovince.label', 'Provincial Address');
        data_set($col8, 'rprovince.maxlength', '150');

        data_set($col8, 'raddressno.label', 'Mailing Address');
        data_set($col8, 'raddressno.maxlength', '150');

        $fields = [
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
        ];
        $col9 = $this->fieldClass->create($fields);

        data_set($col9, 'lbldestination.label', 'House');
        data_set($col9, 'lbldestination.style', 'font-weight:bold; font-size:13px;');
        data_set($col9, 'iswife.label', 'Owned');
        data_set($col9, 'isretired.label', 'Rented');
        data_set($col9, 'isofw.label', 'Free');

        data_set($col9, 'contactno2.type', 'cinput');
        data_set($col9, 'contactno2.label', 'Telephone #');
        data_set($col9, 'contactno2.maxlength', '25');

        data_set($col9, 'rstreet.label', 'Date & Place of Birth');
        data_set($col9, 'rstreet.maxlength', '150');

        //data_set($col9, 'mstatus.name', 'civilstat');
        data_set($col9, 'civilstat.lookupclass', 'lookuplestatus');
        data_set($col9, 'civilstat.addedparams', ['empfirst', 'pstreet', 'emplast', 'rcity',  'paddressno',  'dp',  'psubdistown', 'othersource']);
        data_set($col9, 'civilstat.label', 'Civil Status');

        data_set($col9, 'mobile.label', 'No. Of Dependents');
        data_set($col9, 'mobile.maxlength', '50');
        data_set($col9, 'mobile.required', false);

        data_set($col9, 'citizenship.type', 'cinput');
        data_set($col9, 'citizenship.label', 'Nationality');
        data_set($col9, 'citizenship.maxlength', '50');

        data_set($col9, 'owner.label', 'Name of Employer/Business *');
        data_set($col9, 'owner.maxlength', '150');
        data_set($col9, 'owner.required', true);
        data_set($col9, 'owner.error', false);

        data_set($col9, 'rsubdistown.label', 'Address & Tel. # *');
        data_set($col9, 'rsubdistown.maxlength', '150');
        data_set($col9, 'rsubdistown.required', true);
        data_set($col9, 'rsubdistown.error', false);

        data_set($col9, 'rcountry.type', 'cinput');
        data_set($col9, 'rcountry.label', 'Position Held *');
        data_set($col9, 'rcountry.maxlength', '50');
        data_set($col9, 'rcountry.required', true);
        data_set($col9, 'rcountry.error', false);

        data_set($col9, 'rbrgy.type', 'lookup');
        data_set($col9, 'rbrgy.action', 'lookuprandom');
        data_set($col9, 'rbrgy.lookupclass', 'lookup_staylength4');
        data_set($col9, 'rbrgy.label', 'Length of Stay *');
        data_set($col9, 'rbrgy.required', true);
        data_set($col9, 'rbrgy.error', false);


        $fields = [
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
        $col10 = $this->fieldClass->create($fields);
        //spouse
        data_set($col10, 'lblpassbook.label', 'Spouse Data (If Married)');
        data_set($col10, 'lblpassbook.style', 'font-weight:bold; font-size:13px;');
        data_set($col10, 'empfirst.readonly', false);

        // data_set($col10, 'empfirst.type', 'lookup');
        data_set($col10, 'empfirst.label', 'Name of Spouse');

        data_set($col10, 'pstreet.label', 'Date & Place of Birth');
        data_set($col10, 'pstreet.maxlength', '150');


        data_set($col10, 'emplast.type', 'cinput');
        data_set($col10, 'emplast.readonly', false);
        data_set($col10, 'emplast.label', 'Name of Employer/Business');
        data_set($col10, 'emplast.maxlength', '50');

        data_set($col10, 'rcity.type', 'cinput');
        data_set($col10, 'rcity.readonly', false);
        data_set($col10, 'rcity.label', 'Address & Tel. #');
        data_set($col10, 'rcity.maxlength', '150');


        data_set($col10, 'paddressno.label', 'Position Held');
        data_set($col10, 'paddressno.maxlength', '50');

        data_set($col10, 'dp.type', 'cinput');
        data_set($col10, 'dp.label', 'Monthly Income');
        data_set($col10, 'dp.maxlength', 12);

        data_set($col10, 'psubdistown.type', 'lookup');
        data_set($col10, 'psubdistown.action', 'lookuprandom');
        data_set($col10, 'psubdistown.lookupclass', 'lookup_staylength2');
        data_set($col10, 'psubdistown.label', 'Length of Stay');
        data_set($col10, 'psubdistown.maxlength', '150');
        data_set($col10, 'othersource.type', 'cinput');
        data_set($col10, 'othersource.label', 'Immediate Superior');
        data_set($col10, 'othersource.maxlength', '50');

        $fields = [
            'lblreconcile',
            'lblearned',
            'ext2',
            'rem',
            ['isprc', 'isdriverlisc'],
            'lblcleared',
            'minimum',
            'ourref',
        ];
        $col11 = $this->fieldClass->create($fields);
        data_set($col11, 'lblreconcile.label', 'Properties');
        data_set($col11, 'lblreconcile.style', 'font-weight:bold; font-size:13px;');
        data_set($col11, 'lblearned.label', 'Real Estate');
        data_set($col11, 'lblearned.style', 'font-size:13px;');
        // data_set($col11, 'ndiffot.readonly', false);
        data_set($col11, 'ext2.type', 'cinput');
        data_set($col11, 'ext2.label', 'Location');
        data_set($col11, 'ext2.maxlength', '50');


        data_set($col11, 'rem.type', 'cinput');
        data_set($col11, 'rem.readonly', false);
        data_set($col11, 'rem.label', 'Value');
        data_set($col11, 'rem.maxlength', 12);

        data_set($col11, 'isprc.label', 'Not Morgaged');
        data_set($col11, 'isdriverlisc.label', 'Morgaged');
        data_set($col11, 'lblcleared.label', 'Vehicle');
        data_set($col11, 'lblcleared.style', 'font-weight:bold; font-size:13px;');

        data_set($col11, 'minimum.type', 'cinput');
        data_set($col11, 'minimum.label', 'Year');
        data_set($col11, 'minimum.maxlength', '4');

        data_set($col11, 'ourref.label', 'Model');
        data_set($col11, 'ourref.maxlength', '50');

        $fields = [
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
        ];
        $col12 = $this->fieldClass->create($fields);
        //bank deposit
        data_set($col12, 'lblrecondate.label', 'Bank Deposit');
        data_set($col12, 'lblrecondate.style', 'font-weight:bold; font-size:13px;');

        data_set($col12, 'lblendingbal.label', 'Account#');
        data_set($col12, 'lblendingbal.style', 'font-weight:bold; font-size:13px;');
        data_set($col12, 'lblunclear.label', 'Bank');
        data_set($col12, 'lblunclear.style', 'font-weight:bold; font-size:13px;');

        data_set($col12, 'recondate.type', 'cinput');
        data_set($col12, 'recondate.readonly', false);
        data_set($col12, 'recondate.label', 'Savings account#');
        data_set($col12, 'recondate.maxlength', '50');

        data_set($col12, 'endingbal.type', 'cinput');
        data_set($col12, 'endingbal.readonly', false);
        data_set($col12, 'endingbal.label', 'Savings Bank');
        data_set($col12, 'endingbal.maxlength', '50');

        data_set($col12, 'unclear.type', 'cinput');
        data_set($col12, 'unclear.readonly', false);
        data_set($col12, 'unclear.label', 'Current account#');
        data_set($col12, 'unclear.maxlength', '50');

        data_set($col12, 'revision.type', 'cinput');
        data_set($col12, 'revision.readonly', false);
        data_set($col12, 'revision.label', 'Current Bank');
        data_set($col12, 'revision.maxlength', '50');

        data_set($col12, 'ftruckname.type', 'cinput');
        data_set($col12, 'ftruckname.class', 'csftruckname');
        data_set($col12, 'ftruckname.label', 'Others Account#');
        data_set($col12, 'ftruckname.maxlength', '50');

        data_set($col12, 'frprojectname.type', 'cinput');
        data_set($col12, 'frprojectname.class', 'csfrproject');
        data_set($col12, 'frprojectname.label', 'Others Bank');
        data_set($col12, 'frprojectname.maxlength', '50');

        data_set($col12, 'poref.type', 'cinput');
        data_set($col12, 'poref.label', 'Monthly Income (Co-Maker)*');
        data_set($col12, 'poref.maxlength', 12);
        data_set($col12, 'poref.required', true);
        data_set($col12, 'poref.error', false);

        data_set($col12, 'soref.type', 'cinput');
        data_set($col12, 'soref.label', 'Monthly Expenses');
        data_set($col12, 'soref.maxlength', 12);

        data_set($col12, 'pbrgy.type', 'cinput');
        data_set($col12, 'pbrgy.readonly', false);
        data_set($col12, 'pbrgy.label', 'Personal References 1');
        data_set($col12, 'pbrgy.maxlength', '150');

        data_set($col12, 'appref.type', 'cinput');
        data_set($col12, 'appref.label', 'Personal References 2');
        data_set($col12, 'appref.maxlength', '150');


        $fields = [
            'lblbranch',
            'numdays',
            [
                'bday',
                'entryot'
            ],
            'othrs',
            'apothrs',
        ];
        $col13 = $this->fieldClass->create($fields);

        data_set($col13, 'lblbranch.label', 'Residence Certificate');
        data_set($col13, 'lblbranch.style', 'font-weight:bold; font-size:13px;');

        data_set($col13, 'numdays.type', 'cinput');
        data_set($col13, 'numdays.class', 'csnumdays');
        data_set($col13, 'numdays.readonly', false);
        data_set($col13, 'numdays.label', 'Number');
        data_set($col13, 'numdays.maxlength', '50');

        data_set($col13, 'bday.type', 'date');
        data_set($col13, 'bday.label', 'Date of Issue');

        data_set($col13, 'entryot.type', 'cinput');
        data_set($col13, 'entryot.readonly', false);
        data_set($col13, 'entryot.label', 'Place of Issue');
        data_set($col13, 'entryot.maxlength', '50');

        data_set($col13, 'othrs.type', 'cinput');
        data_set($col13, 'othrs.readonly', false);
        data_set($col13, 'othrs.label', 'TIN*');
        data_set($col13, 'othrs.maxlength', '15');
        data_set($col13, 'othrs.required', true);
        data_set($col13, 'othrs.error', false);

        data_set($col13, 'apothrs.type', 'cinput');
        data_set($col13, 'apothrs.readonly', false);
        data_set($col13, 'apothrs.class', 'csapothrs');
        data_set($col13, 'apothrs.label', 'SSS/GSIS No.*');
        data_set($col13, 'apothrs.maxlength', '15');
        data_set($col13, 'apothrs.required', true);
        data_set($col13, 'apothrs.error', false);

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
        $col14 = $this->fieldClass->create($fields);
        data_set($col14, 'issenior.readonly', false);
        data_set($col14, 'issenior.label', 'Certificate of Employment w/ Salary Information and Certificate of Appointment or Employment Contract w/Allotment Slip');

        data_set($col14, 'tenthirty.readonly', false);
        data_set($col14, 'tenthirty.label', 'Photocopy of government issued IDs and any valid Identification');

        data_set($col14, 'thirtyfifty.readonly', false);
        data_set($col14, 'thirtyfifty.label', 'Ownership of Collateral/Post-dated Checks/ATM/ Passbook or Withdrawal Slip of Borrower');

        data_set($col14, 'fiftyhundred.readonly', false);
        data_set($col14, 'fiftyhundred.label', 'Bank Certification that the current account used is active and properly Handled or proof of pension record');

        data_set($col14, 'hundredtwofifty.readonly', false);
        data_set($col14, 'hundredtwofifty.label', 'Picture of Collateral, Billing, Statement, Brgy. Clearance, Marriage Contract or Birth Certificate');

        data_set($col14, 'fivehundredup.readonly', false);
        data_set($col14, 'fivehundredup.label', 'Special Power of Attorney (Roxas City based)');

        data_set($col14, 'lblreceived.label', 'For Company Use Only');
        data_set($col14, 'lblreceived.style', 'font-weight:bold; font-size:13px;');

        data_set($col14, 'lblattached.label', 'Conditions/Recommendation :');
        data_set($col14, 'lblattached.style', 'font-weight:bold; font-size:13px;');

        data_set($col14, 'isemployed.readonly', false);
        data_set($col14, 'isemployed.label', 'Approved with PDC');

        data_set($col14, 'isselfemployed.readonly', false);
        data_set($col14, 'isselfemployed.label', ' Approved Salary Deduction');

        data_set($col14, 'isplanholder.readonly', false);
        data_set($col14, 'isplanholder.label', 'Approved with REM');

        data_set($col14, 'lblitemdesc.label', 'Credit Committee');
        data_set($col14, 'credits.maxlength', '150');
        data_set($col14, 'credits.type', 'textarea');
        data_set($col14, 'credits.label', '');

        $fields = ['payrolltype', 'employeetype', 'expiration', 'loanlimit', 'loanamt'];
        $col15 = $this->fieldClass->create($fields);

        data_set($col15, 'loanlimit.readonly', false);
        data_set($col15, 'loanamt.readonly', false);

        data_set($col15, 'loanlimit.class', 'csloanlimit');
        data_set($col15, 'loanamt.class', 'csloanamt');

        //housing loan info
        $fields = ['tct','subdivision','blklot', 'area', 'pricesqm', 'tcp', 'disc','outstanding','penaltyamt'];
        $col16 = $this->fieldClass->create($fields);
        data_set($col16, 'blklot.type', 'cinput');
        data_set($col16, 'blklot.label', 'Blk & Lot No./ Address');
        data_set($col16, 'blklot.maxlength', '150');
        data_set($col16, 'blklot.class', '');
        data_set($col16, 'disc.label', 'Discount');
        data_set($col16, 'tcp.label', 'Contract Price');
        data_set($col16, 'area.type', 'input');
        
        $fields = ['lbldateid','entryfee','lrf', 'itfee', 'regfee','docstamp1', 'nf','annotationfee','articles','annotationexp','otransfer','pf','rpt'];
        $col17 = $this->fieldClass->create($fields);
        data_set($col17, 'lbldateid.label', 'REAL ESTATE MORTGAGE');
        data_set($col17, 'nf.label', 'Legal & Notarial Fee');
        data_set($col17, 'pf.label', 'Service Fee');
        // data_set($col17, 'pf.class', 'sbccsreadonly');
        // data_set($col17, 'nf.class', 'sbccsreadonly');
        // data_set($col17, 'regfee.class', 'sbccsreadonly');
        
        
        $fields = ['lblinvreq', 'docstamp','fmri','handling','appraisal','filing','nf2','nf3'];
        $col18 = $this->fieldClass->create($fields);
        data_set($col18, 'lblinvreq.label', 'BANK CHARGES');
        data_set($col18, 'lblinvreq.style', 'font-weight:bold; font-size:13px;');
        
        $fields = ['lblforapproval','ofee','referral','cancellation4','cancellation7','annotationoc1','annotationoc2','cancellationu','totalcharges'];
        $col19 = $this->fieldClass->create($fields);
        data_set($col19, 'lblforapproval.label', 'OTHER CHARGES FOR RD REGISTRATION FEE AND SERVICE FEE ');
        data_set($col19, 'lblforapproval.style', 'font-weight:bold; font-size:13px;');
        //data_set($col19, 'totalcharges.type', 'label');
        data_set($col19, 'totalcharges.style', 'font-weight:bold; font-size:20px;');
        data_set($col19, 'totalcharges.class', 'sbccsreadonly');
       
        
        $tab = [
            'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2], 'label' => 'BORROWER INFO'],
            'multiinput2' => ['inputcolumn' => ['col3' => $col3], 'label' => 'SPOUSE INFO (BORROWER)'],
            'multiinput3' => ['inputcolumn' => ['col4' => $col4, 'col5' => $col5, 'col6' => $col6, 'col7' => $col7], 'label' => 'OTHER INFO (BORROWER)'],
            'multiinput4' => ['inputcolumn' => ['col8' => $col8, 'col9' => $col9], 'label' => 'CO MAKER'],
            'multiinput5' => ['inputcolumn' => ['col10' => $col10], 'label' => 'SPOUSE INFO (CO MAKER)'],
            'multiinput6' => ['inputcolumn' => ['col11' => $col11, 'col12' => $col12, 'col13' => $col13], 'label' => 'OTHER INFO (CO MAKER)'],
            'multiinput7' => ['inputcolumn' => ['col14' => $col14], 'label' => 'CHECK LIST'],
            'multiinput8' => ['inputcolumn' => ['col15' => $col15], 'label' => 'APPROVING BODY'],
            'multiinput9' => ['inputcolumn' => ['col16' => $col16], 'label' => 'FOR HOUSING LOAN ONLY'],
            'multiinput10' => ['inputcolumn' => ['col17' => $col17,'col18'=>$col18,'col19'=>$col19], 'label' => 'TAKEOUT/OTHER FEES'],
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
            ['releasedate', 'dateid']
        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Effectivity Date');
        data_set($col1, 'releasedate.label', 'Application Date');

        $fields = ['categoryname', 'purpose'];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'categoryname.label', 'Type of Loan');
        data_set($col2, 'categoryname.lookupclass', 'lookuploan');
        data_set($col2, 'categoryname.action', 'lookupreqcategory');

        data_set($col2, 'purpose.addedparams', ['planid']);
        data_set($col2, 'purpose.type', 'lookup');
        data_set($col2, 'purpose.lookupclass', 'lookuppurpose');
        data_set($col2, 'purpose.action', 'lookupreqcategory');
        data_set($col2, 'purpose.label', 'Purpose of Loan *');
        data_set($col2, 'purpose.class', 'cspurpose sbccsreadonly');
        // data_set($col2, 'purpose.maxlength', '50');
        // data_set($col2, 'purpose.required', true);
        // data_set($col2, 'purpose.error', false);

        //  'cvno',
        // data_set($col7, 'cvno.class', 'sbccsreadonly');
        $fields = [['amount', 'terms'], ['cvno','gjno']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'amount.type', 'cinput');
        data_set($col3, 'amount.label', 'Loan Amount*');
        data_set($col3, 'amount.maxlength', 12);
        data_set($col3, 'amount.required', true);
        data_set($col3, 'amount.error', false);

        data_set($col3, 'terms.type', 'lookup');
        data_set($col3, 'terms.action', 'lookupterms');
        data_set($col3, 'terms.lookupclass', 'ledgerterms');
        data_set($col3, 'terms.label', 'Terms');
        data_set($col3, 'terms.label', 'Terms *');
        data_set($col3, 'terms.required', true);
        data_set($col3, 'terms.error', false);

        data_set($col3, 'cvno.class', 'sbccsreadonly');
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
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
        $data[0]['plangrpid'] = 0;
        $data[0]['subdivision'] = '';
        $data[0]['blklot'] = '';
        $data[0]['area'] = 0;
        $data[0]['pricesqm'] = 0;
        $data[0]['tcp'] = 0;
        $data[0]['disc'] = 0;
        $data[0]['outstanding'] = 0;
        $data[0]['penaltyamt'] = 0;
        
        $data[0]['entryfee'] = 0;
        $data[0]['lrf'] = 0;
        $data[0]['itfee'] = 0;
        $data[0]['regfee'] = 0;
        $data[0]['docstamp'] = 0;
        $data[0]['nf2'] = 0;
        $data[0]['nf3'] = 0;
        $data[0]['ofee'] = 0;
        $data[0]['tct'] = '';
        
        $data[0]['annotationfee'] = 0;
        $data[0]['docstamp1'] = 0;
        $data[0]['articles'] = 0;
        $data[0]['annotationexp'] = 0;
        $data[0]['otransfer'] = 0;
        $data[0]['rpt'] = 0;
        $data[0]['handling'] = 0;
        $data[0]['appraisal'] = 0;
        $data[0]['filing'] = 0;
        $data[0]['referral'] = 0;
        $data[0]['cancellation4'] = 0;
        $data[0]['cancellation7'] = 0;
        $data[0]['annotationoc1'] = 0;
        $data[0]['annotationoc2'] = 0;
        $data[0]['cancellationu'] = 0;
        $data[0]['fmri'] = 0;
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
         head.interest,head.planid, info.nf,
         ifnull(head.purpose,'') as purpose,  
         head.plangrpid,
         head.client,head.lname,head.fname,head.mname,head.mmname,
         concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
         head.address,head.province,head.addressno,ifnull(info.issameadd,0) as issameadd, ifnull(info.isbene,0) as isbene,ifnull(info.ispf,0) as ispf,head.contactno,head.street,    
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
         
         ifnull(format(info.pf,2),0) as pf, 
         format(info.amount,2) as amount,  head.terms,         
         ifnull(info.idno,'') as idno,format(info.value,2) as value,ifnull(info.isdp,0) as isdp,ifnull(info.isotherid,0) as isotherid,
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
         ifnull(info.lname,'') as lname2, ifnull(info.fname,'')  as fname2, ifnull(info.mname,'') as mname2,ifnull(info.mmname,'') as maidname,
         ifnull(info.address,'') as truckno,
         ifnull(info.province,'') as rprovince,ifnull(info.addressno,'') as raddressno,
         ifnull(info.iswife,0) as iswife,ifnull(info.isretired,0) as isretired,ifnull(info.isofw,0) as isofw,head.contactno2,ifnull(info.street,0) as rstreet,
         ifnull(info.civilstat,'') as civilstat, 
         ifnull(info.dependentsno,'') as mobile,ifnull(info.nationality,'') as citizenship,
         ifnull(info.employer,'') as owner,ifnull(info.subdistown,'') as rsubdistown,ifnull(info.country,'') as rcountry, ifnull(info.brgy,'') as rbrgy,
         ifnull(info.sname,'')  as empfirst,
         ifnull(info.pstreet,'')  as pstreet,
          ifnull(info.ename,'')  as emplast,
         ifnull(info.city,'')  as rcity,
          ifnull(info.paddressno,'')  as paddressno,
          ifnull(format(info.dp,2),0)  as dp,
         ifnull(info.psubdistown,'')  as psubdistown,        
           ifnull(info.othersource,'')  as othersource,
         ifnull(info.ext,0) as ext2,format(info.value2,2) as rem, ifnull(info.isprc,0) as isprc,
         ifnull(info.isdriverlisc,0) as isdriverlisc,ifnull(info.zipcode,'') as minimum,head.ourref,
         ifnull(info.savings1,'') as recondate,ifnull(info.savings2,0) as endingbal, 
         ifnull(info.current1,'') as unclear,ifnull(info.current2,'') as revision,
         ifnull(info.others1,'') as ftruckname,ifnull(info.others2,'') as frprojectname,
         ifnull(format(info.mincome,2),0) as poref, ifnull(format(info.mexp,2),0) as soref,
         ifnull(info.pbrgy,'') as pbrgy,ifnull(info.appref,'') as appref,ifnull(info.num,'') as numdays,
         ifnull(info.bday,'') as bday,ifnull(info.pliss,'') as entryot, ifnull(info.tin,'') as othrs, 
         ifnull(info.sssgsis,'') as apothrs,
         ifnull(info.issenior,0) as issenior,ifnull(info.tenthirty,0) as tenthirty,ifnull(info.thirtyfifty,0) as thirtyfifty,
         ifnull(info.fiftyhundred,0) as fiftyhundred,
         ifnull(info.hundredtwofifty,0) as hundredtwofifty,ifnull(info.fivehundredup,0) as fivehundredup,
         ifnull(info.isemployed,0) as isemployed,ifnull(info.isselfemployed,0) as isselfemployed,ifnull(info.isplanholder,0) as isplanholder,
         ifnull(info.credits,'') as credits, ifnull(info.payrolltype,'') as payrolltype,ifnull(info.employeetype,'') as employeetype,ifnull(info.expiration,'') as expiration,
         ifnull(info.loanlimit,0) as loanlimit,ifnull(info.loanamt,0) as loanamt,
         ifnull(info.amortization,0) as amortization,ifnull(info.penalty,0) as penalty, concat(info.lname,', ',info.fname,' ',info.mname)  as comakername,ifnull(cv.docno,'') as cvno,
         case ifnull(num.postdate,'') when '' then case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'FOR REVIEW' end else 'APPROVED' end as lblstatus,
         ifnull(info.attorneyinfact,'') as attorneyinfact ,ifnull(info.attorneyaddress,'') as attorneyaddress,ifnull(info.sbuid,0) as sbuid,ifnull(sb.category,'')  as sbu,
         ifnull(info.subdivision,'') as subdivision,ifnull(info.blklot,'') as blklot, ifnull(info.area,0) as area, ifnull(info.pricesqm,0) as pricesqm, ifnull(info.tcp,0) as tcp, ifnull(info.disc,0) as disc,ifnull(info.outstanding,0) as outstanding,
         ifnull(info.penaltyamt,0) as penaltyamt,ifnull(info.tct,'') as tct,
         
         ifnull(format(info.entryfee,2),0) as entryfee,
         ifnull(format(info.lrf,2),0) as lrf, 
         ifnull(format(info.itfee,2),0) as itfee, 
         ifnull(format(info.regfee,2),0) as regfee, 
         ifnull(format(info.docstamp1,2),0) as docstamp1,
         ifnull(format(info.nf,2),0) as nf, 
         ifnull(format(info.annotationfee,2),0) as annotationfee,
         ifnull(format(info.articles,2),0) as articles,
         ifnull(format(info.annotationexp,2),0) as annotationexp,
         ifnull(format(info.otransfer,2),0) as otransfer,
         info.pf,
         ifnull(format(info.rpt,2),0) as rpt,

         ifnull(format(info.docstamp,4),0) as docstamp, 
         ifnull(info.mri,0) as fmri,
         ifnull(format(info.handling,2),0) as handling,
         ifnull(format(info.appraisal,2),0) as appraisal,
         ifnull(format(info.filing,2),0) as filing,
         ifnull(format(info.nf2,2),0) as nf2,
         ifnull(format(info.nf3,2),0) as nf3,


         ifnull(format(info.ofee,2),0) as ofee,
         ifnull(format(info.referral,2),0) as referral,
         ifnull(format(info.cancellation4,2),0) as cancellation4,
         ifnull(format(info.cancellation7,2),0) as cancellation7,
         ifnull(format(info.annotationoc1,2),0) as annotationoc1,
         ifnull(format(info.annotationoc2,2),0) as annotationoc2,
         ifnull(format(info.cancellationu,2),0) as cancellationu,
         ifnull(format(info.entryfee + info.lrf + info.itfee + info.regfee+info.docstamp+info.pf + info.nf + info.nf2+info.nf3+info.ofee+info.annotationfee+info.docstamp1+info.articles+info.annotationexp+info.otransfer+info.rpt+info.handling+info.appraisal+info.filing+info.referral+info.cancellation4+info.cancellation7+info.annotationoc1+info.annotationoc2+info.cancellationu+info.mri,2),0) as totalcharges,
         

         ifnull(gj.docno,'') as gjno
         ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sb on sb.line=info.sbuid
        left join cntnum as cv on cv.trno = num.cvtrno
        left join cntnum as gj on gj.trno = num.pstrno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $hinfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sb on sb.line=info.sbuid
        left join cntnum as cv on cv.trno = num.cvtrno
        left join cntnum as gj on gj.trno = num.pstrno
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
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if    
            }
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
        $data['releasedate'] = $this->othersClass->sanitizekeyfield('dateid',  $head['releasedate']);

        $info['attorneyinfact'] = $head['attorneyinfact'];
        $info['attorneyaddress'] = $head['attorneyaddress'];
        $info['sbuid'] = $head['sbuid'];

        //HL info
        $info['subdivision'] = $head['subdivision'];
        $info['blklot'] = $head['blklot'];
        $info['area'] = $head['area'];
        $info['tct'] = $head['tct'];
        $info['pricesqm'] = $this->othersClass->sanitizekeyfield('price',  $head['pricesqm']);
        $info['tcp'] =  $this->othersClass->sanitizekeyfield('price',  $head['tcp']);
        $info['disc'] = $head['disc'];
        $info['outstanding'] = $this->othersClass->sanitizekeyfield('price',  $head['outstanding']);
        $info['penaltyamt'] =  $this->othersClass->sanitizekeyfield('price',  $head['penaltyamt']);
        //takeout fee
        $info['entryfee'] = $this->othersClass->sanitizekeyfield('price',  $head['entryfee']);
        $info['lrf'] = $this->othersClass->sanitizekeyfield('price',  $head['lrf']);
        $info['itfee'] = $this->othersClass->sanitizekeyfield('price',  $head['itfee']);
        $info['regfee'] = $this->othersClass->sanitizekeyfield('price',  $head['regfee']);
        $info['docstamp'] = $this->othersClass->sanitizekeyfield('price',  $head['docstamp']);
        $info['nf2'] = $this->othersClass->sanitizekeyfield('price',  $head['nf2']);
        $info['nf3'] = $this->othersClass->sanitizekeyfield('price',  $head['nf3']);
        $info['ofee'] = $this->othersClass->sanitizekeyfield('price',  $head['ofee']);        
        $info['annotationfee'] = $this->othersClass->sanitizekeyfield('price',  $head['annotationfee']);
        $info['docstamp1'] = $this->othersClass->sanitizekeyfield('price',  $head['docstamp1']);
        $info['articles'] = $this->othersClass->sanitizekeyfield('price',  $head['articles']);
        $info['annotationexp'] = $this->othersClass->sanitizekeyfield('price',  $head['annotationexp']);
        $info['otransfer'] = $this->othersClass->sanitizekeyfield('price',  $head['otransfer']);
        $info['rpt'] = $this->othersClass->sanitizekeyfield('price',  $head['rpt']);
        $info['handling'] = $this->othersClass->sanitizekeyfield('price',  $head['handling']);
        $info['appraisal'] = $this->othersClass->sanitizekeyfield('price',  $head['appraisal']);
        $info['filing'] = $this->othersClass->sanitizekeyfield('price',  $head['filing']);
        $info['referral'] = $this->othersClass->sanitizekeyfield('price',  $head['referral']);
        $info['cancellation4'] = $this->othersClass->sanitizekeyfield('price',  $head['cancellation4']);
        $info['cancellation7'] = $this->othersClass->sanitizekeyfield('price',  $head['cancellation7']);
        $info['annotationoc1'] = $this->othersClass->sanitizekeyfield('price',  $head['annotationoc1']);
        $info['annotationoc2'] = $this->othersClass->sanitizekeyfield('price',  $head['annotationoc2']);
        $info['cancellationu'] = $this->othersClass->sanitizekeyfield('price',  $head['cancellationu']);
        $info['mri'] =  $this->othersClass->sanitizekeyfield('price',  $head['fmri']);

        $isdiminish = $this->coreFunctions->getfieldvalue("reqcategory","isdiminishing","line=?",[$head['planid']],'',true);

        if($isdiminish == 1){
            $days = $this->coreFunctions->getfieldvalue("terms","days","terms = ?",[$head['terms']]);
            if($info['regfee'] == 0){
                $info['regfee'] =   $info['amount'] * (.5325/100);
            }
            
            // if($info['pf'] == 0){
            //     $info['pf'] = 100*$days;
            // }
            
            // if($info['nf'] ==0){
            //     $info['nf'] = 100*$days;
            // }
            
            // if($info['ofee'] ==0){
            //     $info['ofee'] = $info['amount']*.01;
            // }
            
            // if($info['cancellation4'] == 0){
            //     $info['cancellation4'] = $info['amount']*.01;
            // }
            
            // if($info['filing'] == 0){
            //     $info['filing'] = $info['amount']*.002;
            // }
            
            $info['regfee'] = $this->othersClass->sanitizekeyfield('price',  $info['regfee']);
            $info['docstamp'] = $this->othersClass->sanitizekeyfield('price',  $info['docstamp']);
            $info['pf'] = $this->othersClass->sanitizekeyfield('price',  $info['pf']);
            $info['nf'] = $this->othersClass->sanitizekeyfield('price',  $info['nf']);
            $info['nf2'] = $this->othersClass->sanitizekeyfield('price',  $info['nf2']);
            $info['nf3'] = $this->othersClass->sanitizekeyfield('price',  $info['nf3']);
            $info['ofee'] = $this->othersClass->sanitizekeyfield('price',  $info['ofee']);
            $info['filing'] = $this->othersClass->sanitizekeyfield('price',  $info['filing']);
            // $info['cancellation4'] = $this->othersClass->sanitizekeyfield('price',  $info['cancellation4']);
    
        }      

        if ($head['planid'] != $head['plangrpid']) {
            $data['purpose'] = '';
        }

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            // for info table
            $exist = $this->coreFunctions->getfieldvalue($this->info, "trno", "trno=?", [$head['trno']]);
            if (floatval($exist) <> 0) {
                $this->coreFunctions->sbcupdate($this->info, $info, ['trno' => $head['trno']]);
            } else {
                if($info['docstamp'] == 0){
                    $info['docstamp'] = ($info['amount']/200)*1.5;
                }
        
                if($info['mri'] ==0){
                    $info['mri'] = $info['amount']*.01;
                }

                $this->coreFunctions->sbcinsert($this->info, $info);
            }
        } else {
            if($info['docstamp'] == 0){
                $info['docstamp'] = ($info['amount']/200)*1.5;
            }
    
            if($info['mri'] ==0){
                $info['mri'] = $info['amount']*.01;
            }

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
            return ['status' => false, 'msg' => 'Please complete details for borrower.'];
        }
        $client = $this->coreFunctions->getfieldvalue("eainfo", "concat(lname,fname,mname)", "trno=?", [$trno]);
        if ($client == '') {
            return ['status' => false, 'msg' => 'Please complete details for co-maker.'];
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
        info.otherplan
        

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

        if ($clientid != 0) {
            $this->coreFunctions->sbcinsert('clientinfo', $cinfo);
        }

        $this->coreFunctions->execqry("update " . $this->head . " set client = ? where trno = ?", 'update', [$clientcode, $trno]);

        return ['status'=>true,'msg'=>'Ok'];
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
        $this->coreFunctions->LogConsole('new client:'.$newclient);
        return $newclient;
    }

    //  to follow posting
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];

        $qry = "select (i.entryfee+i.lrf+i.itfee+i.regfee+i.docstamp+i.nf2+i.nf3+i.ofee) as value 
        from ".$this->info." as i where i.trno=?";
      
        $takeout = $this->coreFunctions->datareader($qry, [$trno]);
        $planid = $this->coreFunctions->getfieldvalue($this->head,"planid","trno=?",[$trno]);
        $isdim = $this->coreFunctions->getfieldvalue("reqcategory","isdiminishing","line=?", [$planid],'',true);

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
            $borrower = $this->coreFunctions->getfieldvalue($this->head, "trim(concat(lname,fname))", "trno=?", [$trno]);
            $this->coreFunctions->LogConsole($borrower);
            $cexist = $this->coreFunctions->getfieldvalue("clientinfo","clientid","trim(concat(lname,fname)) = ?",[$borrower],'',true);
            $this->coreFunctions->LogConsole($cexist);
            if($cexist <> 0){
                return ['status' => false, 'msg' => 'This Borrower already exist, please select borrower code.'];
            }else{
                $this->coreFunctions->LogConsole('pumasok ba?');
                $stat = $this->createclient($config);
                if(!$stat['status']){
                    return ['status' => false, 'msg' => $stat['msg']];
                }
            }           
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
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid,fmons,fannum,
            frate,intannum,subdivision,blklot,area,pricesqm,tcp,disc,outstanding,penaltyamt,voidint,tct,entryfee,lrf, itfee, regfee, docstamp, nf2,nf3,ofee,annotationfee,docstamp1,articles,annotationexp,otransfer,rpt,handling,appraisal,filing,referral,cancellation4,cancellation7,annotationoc1,annotationoc2,cancellationu,mri)
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
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid,fmons,fannum,frate,intannum,subdivision,
            blklot,area,pricesqm,tcp,disc,outstanding,penaltyamt,voidint,tct,entryfee,lrf, itfee, regfee, docstamp, nf2,nf3,ofee,annotationfee,docstamp1,articles,annotationexp,otransfer,rpt,handling,appraisal,filing,referral,cancellation4,cancellation7,annotationoc1,annotationoc2,cancellationu,mri
            from " . $this->info . " where trno=? limit 1";
            $postinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

            if ($postinfo) {
                $qry = "insert into htempdetailinfo (trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid,bal,dst,mri) select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid,bal,dst,mri from tempdetailinfo where trno = ?";
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
                    if($isdim !=0){
                        $this->generatejv($config);
                    }                    
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
            return ['status' => false, 'msg' => 'Unposting failed; Application was already issued.'];
        }


        $lplan = $this->coreFunctions->datareader('select pstrno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if (floatval($lplan) != 0) {
            return ['status' => false, 'msg' => 'Unposting failed; Please remove GJ for takeout fees.'];
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
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid,fmons,
            fannum,frate,intannum,subdivision,blklot,area,pricesqm,tcp,disc,outstanding,penaltyamt,voidint,tct,entryfee,lrf, itfee, regfee, docstamp, nf2,nf3,ofee,annotationfee,docstamp1,articles,annotationexp,otransfer,rpt,handling,appraisal,filing,referral,cancellation4,cancellation7,annotationoc1,annotationoc2,cancellationu,mri)
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
            credits,payrolltype,employeetype,expiration, loanlimit,loanamt, amortization,penalty,clientname,value,bank1,attorneyinfact,attorneyaddress,sbuid,fmons,fannum,
            frate,intannum,subdivision,blklot,area,pricesqm,tcp,disc,outstanding,penaltyamt,voidint,tct,entryfee,lrf, itfee, regfee, docstamp, nf2,nf3,ofee,annotationfee,docstamp1,
            articles,annotationexp,otransfer,rpt,handling,appraisal,filing,referral,cancellation4,cancellation7,annotationoc1,annotationoc2,cancellationu,mri
            from " . $this->hinfo . " where trno=? limit 1";
            $unpostinfo = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

            if ($unpostinfo) {
                $qry = "insert into tempdetailinfo (trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid,bal,dst,mri) select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,dateid,bal,dst,mri from htempdetailinfo where trno = ?";
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
            case 'generatejv':
                return $this->generateJV($config);
                break;

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    private function generatejv($config)
    {
      $trno = $config['params']['trno'];

      $isposted = $this->othersClass->isposted2($trno,$this->tablenum);

      if(!$isposted){
        return ['status' => false, 'msg' => 'Not yet Approve.'];
      }

      $exist = $this->coreFunctions->getfieldvalue($this->tablenum,"pstrno","trno=?",[$trno],'',true);
      if($exist !=0){
        return ['status' => false, 'msg' => 'Already have GJ.'];
      }

      $path = 'App\Http\Classes\modules\accounting\gj';
      $qry = "select h.docno,h.client,h.clientname,h.dateid,(i.entryfee+i.lrf+i.itfee+i.regfee+i.ofee+i.nf+i.pf+i.annotationfee+i.docstamp1+i.articles+i.annotationexp+i.otransfer+i.rpt+i.handling+i.appraisal+i.filing+i.referral+i.cancellation4+i.cancellation7+i.annotationoc1+i.annotationoc2+i.cancellationu+i.mri) as otherrec,(i.docstamp+i.nf2+i.nf3) as depfee,(i.entryfee+i.lrf+i.itfee+i.regfee+i.docstamp+i.nf2+i.nf3+i.ofee+i.nf+i.pf+i.annotationfee+i.docstamp1+i.articles+i.annotationexp+i.otransfer+i.rpt+i.handling+i.appraisal+i.filing+i.referral+i.cancellation4+i.cancellation7+i.annotationoc1+i.annotationoc2+i.cancellationu+i.mri) as total 
      from ".$this->hhead." as h left join ".$this->hinfo." as i on i.trno = h.trno
      where h.trno=?";
    
      $data = $this->coreFunctions->opentable($qry, [$trno]);
      if (!empty($data)) {
        if($data[0]->total !=0){
            
            try{
                $gjtrno = $this->othersClass->generatecntnum($config, "cntnum", 'GJ','GJ');
                if ($gjtrno != -1) {
                    $docno =  $this->coreFunctions->getfieldvalue("cntnum", 'docno', "trno=?", [$gjtrno]);
            
                    $head = ['trno' => $gjtrno, 
                            'doc' => 'GJ',
                            'docno' => $docno, 
                            'aftrno' => $trno,
                            'client' => $data[0]->client, 
                            'clientname' => $data[0]->clientname, 
                            'dateid' => $data[0]->dateid, //date('Y-m-d'), 
                            'yourref' => $data[0]->docno,
                            'rem' => 'Take out Fees for Loan Application # '.$data[0]->docno.' of '. $data[0]->clientname,
                            'createby' => $config['params']['user'],
                            'createdate'=> $this->othersClass->getCurrentTimeStamp()
                            ];
        
                        foreach($head as $k => $value){
                            $data2[$k] = $this->othersClass->sanitizekeyfield($k,$head[$k]);
                        }
        
                    $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $data2);
                    $config['params']['trno']=$gjtrno;
                    $line = 1;
                    $d =[];
                    $detail =[];
                    if($inserthead){
                        $this->logger->sbcwritelog($gjtrno, $config, 'CREATE', $docno . ' - ' . $data[0]->client . ' - ' . $data[0]->clientname,app($path)->tablelogs);
                        //entries:
                        $otherrec = $this->coreFunctions->getfieldvalue("coa","acnoid","alias = 'AR3'");
                        $otherinc = $this->coreFunctions->getfieldvalue("coa","acnoid","alias = 'UE3'");
                        $depfee = $this->coreFunctions->getfieldvalue("coa","acnoid","alias = 'UE4'");

                        if($data[0]->total != 0){
                            $d['trno'] = $gjtrno;
                            $d['line'] = $line;
                            $d['refx'] = 0;
                            $d['linex'] = 0;
                            $d['client'] = $data[0]->client;
                            $d['acnoid'] = $otherrec;
                            $d['postdate'] = $data[0]->dateid;//date('Y-m-d');
                            $d['checkno'] ='';
                            $d['ref'] = '';
                            $d['db'] = $data[0]->total;
                            $d['cr'] = 0;
                            $d['rem'] = 'Take out fee';
                            array_push($detail, $d);
                            $line +=1;
                        }

                        if($data[0]->otherrec != 0){
                            $d['trno'] = $gjtrno;
                            $d['line'] = $line;
                            $d['refx'] = 0;
                            $d['linex'] = 0;
                            $d['client'] = $data[0]->client;
                            $d['acnoid'] = $otherinc;
                            $d['postdate'] =$data[0]->dateid;// date('Y-m-d');
                            $d['checkno'] ='';
                            $d['ref'] = '';
                            $d['db'] = 0;
                            $d['cr'] = $data[0]->otherrec;
                            $d['rem'] = '';
                            array_push($detail, $d);
                            $line +=1;
                        }

                        if($data[0]->depfee != 0){
                            $d['trno'] = $gjtrno;
                            $d['line'] = $line;
                            $d['refx'] = 0;
                            $d['linex'] = 0;
                            $d['client'] = $data[0]->client;
                            $d['acnoid'] = $depfee;
                            $d['postdate'] = $data[0]->dateid;//date('Y-m-d');
                            $d['checkno'] ='';
                            $d['ref'] = '';
                            $d['db'] = 0;
                            $d['cr'] = $data[0]->depfee;
                            $d['rem'] = '';
                            array_push($detail, $d);
                            $line +=1;
                        }

                        //var_dump($detail);

                        if (!empty($detail)) {
                            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                            foreach ($detail as $key => $value) {
                              foreach ($value as $key2 => $value2) {
                                $detail[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                              }
                              $detail[$key]['editdate'] = $current_timestamp;
                              $detail[$key]['editby'] = $config['params']['user'];
                              $detail[$key]['encodeddate'] = $current_timestamp;
                              $detail[$key]['encodedby'] = $config['params']['user'];
                  
                              if ($this->coreFunctions->sbcinsert(app($path)->detail, $detail[$key]) == 1) {
                                $this->logger->sbcwritelog($gjtrno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS',app($path)->tablelogs);
                                $this->logger->sbcwritelog($gjtrno, $config, 'ACCTG', 'ADD - Line:' . $detail[$key]['line'] . ' Remarks:' . $detail[$key]['rem'] . ' DB:' . $detail[$key]['db'] . ' CR:' . $detail[$key]['cr'] . ' Client:' . $detail[$key]['client'] . ' Date:' . $detail[$key]['postdate'],app($path)->tablelogs);                               
                              } else {
                                $this->logger->sbcwritelog($gjtrno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED',app($path)->tablelogs);
                                return ['accounting' => [], 'status' => false, 'msg' => 'Entry Failed'];
                              }
                            } //for $detail
                  
                          }

                          $config['params']['trno'] = $gjtrno;
                          $this->tablenum = 'cntnum';
                          $this->head = 'lahead';
                          $this->hhead = 'glhead';
                          $this->tablelogs = 'table_log';
                          $this->htablelogs = 'htable_log';
                          $return = $this->othersClass->posttransacctg($config);
                          if ($return['status']) {                         
                            $msg = "Auto entry Successful";
                            $this->coreFunctions->execqry("update transnum set pstrno = ".$gjtrno.". where trno =".$trno,"update");
                            return ['status' => true, 'msg' => $msg];
                          }
                         
                    
                    }
            
                            
                }else{
                    return $gjtrno;
                }
            }catch (Exception $e) {
                return ['status' => false, 'msg' => $e->getMessage()];
            }
      
        }
       
       
  
       
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
