<?php

namespace App\Http\Classes\modules\barangay;

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

class cr
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'RECEIVED PAYMENT';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    private $stockselect;
    public $defaultContra = 'CR1';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'client',
        'clientname',
        'yourref',
        'ourref',
        'rem',
        'forex',
        'contra',
        'cur',
        'address',
        'projectid',
        'agent',
        'qttrno',
        'crref',
        'amount',
        'checkno',
        'checkdate',
        'blklotid',
        'phaseid',
        'modelid',
        'rctrno',
        'rcline',
        'invoiceno',
        'refdate',
        'amenityid',
        'subamenityid'
    ];
    private $otherfields = ['cptrno'];
    private $except = ['trno', 'dateid', 'checkdate'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;


    public $labelposted = 'POSTED';
    public $labellocked = 'LOCKED';

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
        ['val' => 'all', 'label' => 'All', 'color' => 'blue']
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
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 224,
            'edit' => 225,
            'new' => 226,
            'save' => 227,
            // 'change' => 228, remove change doc
            'delete' => 229,
            'print' => 230,
            'lock' => 231,
            'unlock' => 232,
            'post' => 233,
            'unpost' => 234,
            'additem' => 235,
            'edititem' => 236,
            'deleteitem' => 237
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];


        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listprojectname', 'yourref', 'ourref', 'rem', 'db', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$rem]['type'] = 'coldel';
        $cols[$db]['type'] = 'coldel';

        $cols[$listprojectname]['type'] = 'coldel';

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $companyid = $config['params']['companyid'];
        $col1 = [];
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = '';

        $yourref = '';
        $ladb = '';
        $gldb = '';
        $leftjoin = '';
        $groupby = '';
        $lstatus = "'DRAFT'";
        $gstatus = "'POSTED'";
        $lstatuscolor = 'red';
        $gstatuscolor = 'grey';

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null  and head.lockdate is null';
                break;
            case 'locked':
                $condition = ' and num.postdate is null and head.lockdate is not null ';
                $lstatuscolor = 'green';
                break;
            case 'void':
                $condition = ' and  head.voiddate is not null ';
                $lstatuscolor = 'grey';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                $lstatuscolor = 'grey';
                break;
        }



        $join = "";
        $hjoin = "";
        $field = "";
        $dateid = "left(head.dateid,10) as dateid";
        $yourref = 'head.yourref';
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";



        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select head.trno,head.docno,head.clientname,$dateid $ladb, $lstatus as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      " . $yourref . " as yourref, head.ourref, group_concat(concat(detail.poref) SEPARATOR ' ') as poref, head.rem,
      ifnull(info.clientname,head.clientname) as planholder,format(head.amount," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amount,
      case ifnull(head.lockdate,'') when '' then (case ifnull(head.voiddate,'') when '' then 'red' else 'grey' end) else (case ifnull(head.voiddate,'') when '' then 'green' else 'grey' end) end as statuscolor   $field
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join " . $this->detail . " as detail on detail.trno = head.trno
     left join cntnuminfo as i on i.trno = head.trno
     left join heahead as ea on ea.catrno = i.cptrno
     left join heainfo as info on info.trno = ea.trno  $leftjoin  $join
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     group by  head.trno,head.docno,head.clientname,head.dateid,status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate),
      head.crref, head.ourref, head.yourref, head.rem,info.clientname,head.amount,head.lockdate,head.voiddate $groupby
     union all
     select head.trno,head.docno,head.clientname,$dateid $gldb,$gstatus as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       " . $yourref . " as yourref, head.ourref, group_concat(concat(detail.poref) SEPARATOR ' ') as poref , 
       head.rem,ifnull(info.clientname,head.clientname) as planholder,format(head.amount," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amount,
       case ifnull(head.voiddate,'') when '' then '" . $gstatuscolor . "' else 'grey' end  as statuscolor       $field
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join " . $this->hdetail . " as detail on detail.trno = head.trno
     left join hcntnuminfo as i on i.trno = head.trno
     left join heahead as ea on ea.catrno = i.cptrno
     left join heainfo as info on info.trno = ea.trno $leftjoin   $hjoin
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     group by  head.trno,head.docno,head.clientname,head.dateid,status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate),
      head.crref, head.ourref, head.yourref, head.rem,info.clientname,head.amount,head.voiddate $groupby
       $orderby  $limit";

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

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'cr', 'title' => 'CR_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        //void
        $allowVoid = $this->othersClass->checkAccess($config['params']['user'], 4501);
        if ($allowVoid) {
            $buttons['others']['items']['void'] = ['label' => 'Void CR', 'todo' => ['lookupclass' => 'voidtrans', 'action' => 'voidtrans', 'access' => 'view', 'type' => 'navigation']];
        }
        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);



        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        if ($this->companysetup->getistodo($config['params'])) {
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
            $objtodo = $this->tabClass->createtab($tab, []);
            $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
        }

        return $return;
    }

    public function createTab($access, $config)
    {

        $systype = $this->companysetup->getsystemtype($config['params']);

        $action = 0;
        $db = 1;
        $cr = 2;
        $postdate = 3;
        $checkno = 4;
        $qtref = 5;
        $lastdp = 6;
        $ref = 7;
        $rem = 8;
        $poref = 9;
        $podate = 10;
        $acnoname = 11;
        $client = 12;

        $column = [
            'action',
            'db',
            'cr',
            'postdate',
            'checkno',
            'ref',
            'type',
            'rem',
            'acnoname',
            'client'
        ];

        foreach ($column as $key => $value) {
            $$value  = $key;
        }


        $companyid = $config['params']['companyid'];
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => ['viewref', 'viewdiagram', 'viewacctginfo']
            ]
        ];

        $stockbuttons = ['save', 'delete'];
        if ($this->companysetup->getiseditsortline($config['params'])) {
            array_push($stockbuttons, 'sortline');
        }

        array_push($stockbuttons, 'detailinfo');


        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['accounting']['columns'][$action]['checkfield'] = 'void';
        $obj[0][$this->gridname]['columns'][$ref]['lookupclass'] = 'refcr';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = 'customerdetail';
        $obj[0][$this->gridname]['columns'][$postdate]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';



        $obj[0][$this->gridname]['columns'][$type]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$type]['label'] = 'Clearance Type';
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'text-align: left;';

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];
        $tbuttons = ['additem', 'saveitem', 'deleteallitem', 'unpaid'];

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[$additem]['label'] = "ADD ACCOUNT";
        $obj[$additem]['action'] = "adddetail";
        $obj[$saveitem]['label'] = "SAVE ACCOUNT";
        $obj[$deleteallitem]['label'] = "DELETE ACCOUNT";

        $obj[$unpaid]['lookupclass'] = "unpaidclearance";
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4853);

        $fields = ['docno', 'client', 'clientname', 'address'];
        if ($this->companysetup->crforcebal($config['params'])) {
            array_push($fields, 'dacnoname');
        }


        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'dacnoname.lookupclass', 'lookupdepositto');

        $fields = ['dateid', ['yourref', 'ourref'], ['cur', 'forex']];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
        }
        $col3 = $this->fieldClass->create($fields);
        $fields = ['lblpaid'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'lblpaid.style', 'font-family:Century Gothic; color:red; font-size:20px;font-weight:bold;');
        data_set($col4, 'lblpaid.label', 'VOID!');


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['yourref'] = '';
        $data[0]['address'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['projectid'] = '0';
        $data[0]['qttrno'] = '0';
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['agent'] = '';
        $data[0]['agentname'] = '';
        $data[0]['dagentname'] = '';
        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        $data[0]['crref'] = '';
        $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
        $data[0]['checkno'] = '';
        $data[0]['amount'] = 0;

        $data[0]['modelid'] = 0;
        $data[0]['phaseid'] = 0;
        $data[0]['blklotid'] = 0;
        $data[0]['phase'] = '';
        $data[0]['housemodel'] = '';
        $data[0]['blklot'] = '';
        $data[0]['lot'] = '';
        $data[0]['rctrno'] = 0;
        $data[0]['cptrno'] = 0;
        $data[0]['rcline'] = 0;
        $data[0]['planholder'] = '';
        $data[0]['invoiceno'] = '';
        $data[0]['refdate'] = $this->othersClass->getCurrentDate();


        $data[0]['amenityid'] = 0;
        $data[0]['amenityname'] = '';

        $data[0]['subamenityid'] = 0;
        $data[0]['subamenityname'] = '';
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
        $qryselect = "select 
        num.center,
        head.trno, 
        head.docno,
        client.client,
        head.terms,
        head.cur,
        head.forex,
        head.yourref,
        head.ourref,
        head.contra,
        coa.acnoname,
        '' as dacnoname,
        left(head.dateid,10) as dateid, 
        head.clientname,
        head.clientname as client2,
        head.address, 
        head.shipto, 
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem,
        head.tax,
        head.vattype,
        '' as dvattype,
        left(head.due,10) as due, 
        head.projectid,
  

        client.groupid,ifnull(agent.client,'') as agent,ifnull(agent.clientname,'') as agentname,'' as dagentname,head.qttrno,
        head.crref,head.checkno,left(head.checkdate,10) as checkdate,format(head.amount,2)as amount ,head.phaseid,
        head.modelid,
        head.rctrno,head.rcline,ifnull(ci.cptrno,0) as cptrno,ifnull(i.clientname,'')  as planholder,
        head.invoiceno,head.refdate,ifnull(pt.name,'') as plantype,ifnull(head.voiddate,'') as voiddate,i.idno";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
            
        left join cntnuminfo as ci on ci.trno = head.trno
        left join heahead as ea on ea.catrno = ci.cptrno
        left join heainfo as i on i.trno = ea.trno  
        left join plantype as pt on pt.line = ea.planid and pt.plangrpid = ea.plangrpid      
        where head.trno = ? and num.doc=? and num.center = ? 
        union all 
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra 
            
        left join hcntnuminfo as ci on ci.trno = head.trno
        left join heahead as ea on ea.catrno = ci.cptrno
        left join heainfo as i on i.trno = ea.trno      
        left join plantype as pt on pt.line = ea.planid and pt.plangrpid = ea.plangrpid   
        where head.trno = ? and num.doc=? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $detail = $this->opendetail($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

            $acount = $this->coreFunctions->opentable("select count(*) as acount from cntnum_picture where trno=?", [$head[0]->trno]);
            $hideobj = [];
            $labelobj = [];
            $hideheadergridbtns = [];

            $voidstat = $head[0]->voiddate == "" ? true : false;
            $hideobj = ['lblpaid' => $voidstat];
            $hideheadergridbtns = ['tagreceived' => !$voidstat, 'untagreceived' => $voidstat];

            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj['donetodo'] = !$btndonetodo;
            }
            return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,  'hideobj' => $hideobj, 'hideheadgridbtns' => $hideheadergridbtns, 'labelobj' => $labelobj];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }


    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
        $data = [];
        $dataother = [];

        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if    
            }
        }


        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $prevqt = $this->coreFunctions->getfieldvalue($this->head, "qttrno", "trno=?", [$head['trno']]);
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);


            if ($this->companysetup->getsystemtype($config['params']) == 'REALESTATE') {

                if ($head['rctrno'] != 0) {
                    $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $head['trno']], ['trno' => $head['rctrno'], 'line' => $head['rcline']]);
                }
            }
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);

            if ($this->companysetup->getsystemtype($config['params']) == 'REALESTATE') {
                if ($data['rctrno'] != 0) {
                    $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $head['trno']], ['trno' => $data['rctrno'], 'line' => $data['rcline']]);
                }
            }

            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }

        if ($head['cptrno'] != 0) {
            $dataother['cptrno'] = $head['cptrno'];
            $dataother['trno'] = $head['trno'];
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
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);


        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        return $this->othersClass->posttransacctg($config);
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttransacctg($config);
    } //end function


    private function getdetailselect($config)
    {
        $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,d.sortline,coa.acno,coa.acnoname,
      client.client,client.clientname,d.rem,
      FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,
      left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
      d.project,d.cur,d.forex,    
      case d.isewt when 0 then 'false' else 'true' end as isewt,case d.isvat when 0 then 'false' else 'true' end as isvat,case d.isvewt when 0 then 'false' else 'true' end as isvewt,
      d.ewtcode,d.ewtrate,d.damt,case d.qttrno when 0 then d.poref else qthead.yourref end as poref,
      case d.qttrno when 0 then left(d.podate,10) else left(qthead.due,10) end as podate,'' as bgcolor,case d.void when 1 then 'bg-red-2' else '' end as  errcolor, qthead.docno as qtref, d.qttrno,case d.void when 0 then 'false' else 'true' end as void,d.type";
        return $qry;
    }


    public function opendetail($trno, $config)
    {
        $sqlselect = $this->getdetailselect($config);

        $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client

    left join coa on coa.acnoid=d.acnoid
    left join (select h.docno,h.due,h.yourref,h.trno from qshead  as h left join terms on terms.terms = h.terms where terms.isdp =1  union all select h.docno,h.due,h.yourref,h.trno from hqshead as h left join terms on terms.terms = h.terms where terms.isdp =1 ) as qthead on qthead.trno = d.qttrno and d.qttrno <>0
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid

    left join coa on coa.acnoid=d.acnoid
    left join (select h.docno,h.due,h.yourref,h.trno from qshead as h left join terms on terms.terms = h.terms where terms.isdp =1  union all select h.docno,h.due,h.yourref,h.trno from hqshead as h left join terms on terms.terms = h.terms where terms.isdp =1 ) as qthead on qthead.trno = d.qttrno  and d.qttrno <>0
    where d.trno=?
  ";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $detail;
    }

    public function opendetailline($config)
    {
        $sqlselect = $this->getdetailselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client

    left join coa on coa.acnoid=d.acnoid
    left join (select h.docno,h.due,h.yourref,h.trno from qshead as h left join terms on terms.terms = h.terms where terms.isdp =1  union all select h.docno,due,h.yourref,h.trno from hqshead as h left join terms on terms.terms = h.terms where terms.isdp =1) as qthead on qthead.trno = d.qttrno and d.qttrno <>0
    where d.trno=? and d.line=?";

        $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $detail;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'adddetail':
                return $this->additem('insert', $config);
                break;
            case 'addallitem':
                return $this->addallitem($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'saveitem': //save all detail edited
                return $this->updateitem($config);
                break;
            case 'saveperitem':
                return $this->updateperitem($config);
                break;
            case 'getunpaidselected':
                return $this->getunpaidselected($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getrc($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $total = 0;
        $fb = $this->coreFunctions->getfieldvalue($this->head, "contra", "trno=?", [$trno]);

        $data = $config['params']['rows'];
        $acno = $this->coreFunctions->getfieldvalue("coa", "acno", "alias=?", ['CR1']);
        $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$acno]);
        foreach ($data as $key => $value) {
            $acno = $this->coreFunctions->getfieldvalue("coa", "acno", "alias=?", [$data[$key]['contra']]);
            $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$acno]);

            $config['params']['data']['acno'] = $acno;
            $config['params']['data']['acnoname'] = $acnoname;
            $config['params']['data']['db'] = $data[$key]['db'];
            $config['params']['data']['cr'] = 0;
            $config['params']['data']['fdb'] = 0;
            $config['params']['data']['fcr'] = 0;
            $total = $total - $data[$key]['db'];
            $config['params']['data']['postdate'] = $data[$key]['dateid'];
            $config['params']['data']['ref'] = $data[$key]['docno'];
            $config['params']['data']['refx'] = $data[$key]['trno'];
            $config['params']['data']['linex'] = $data[$key]['line'];
            $config['params']['data']['checkno'] = $data[$key]['checkno'];

            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            }
        } //end foreach

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts successfully.'];
    }

    public function diagram($config)
    {

        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1500;
        $startx = 100;

        //CR
        $crqry = "
    select  head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ?";
        $crdata = $this->coreFunctions->opentable($crqry, [$config['params']['trno']]);
        if (!empty($crdata)) {
            $startx = 550;
            $a = 0;
            foreach ($crdata as $key2 => $value2) {
                data_set(
                    $nodes,
                    $crdata[$key2]->docno,
                    [
                        'align' => 'left',
                        'x' => $startx + 400,
                        'y' => 100,
                        'w' => 250,
                        'h' => 80,
                        'type' => $crdata[$key2]->docno,
                        'label' => $crdata[$key2]->rem,
                        'color' => 'red',
                        'details' => [$crdata[$key2]->dateid]
                    ]
                );
                array_push($links, ['from' => $crdata[$key2]->docno, 'to' => 'ar']);

                //AR
                $qry = "
        select  arhead.docno, date(arhead.dateid) as dateid,
        CAST(concat('Applied Amount: ', round(ardetail.db+ardetail.cr,2)) as CHAR) as rem
        from glhead as arhead
        left join gldetail as ardetail on arhead.trno = ardetail.trno
        left join gldetail as crdetail on crdetail.refx = ardetail.trno and crdetail.linex = ardetail.line
        left join glhead as crhead on crhead.trno = crdetail.trno
        where crhead.trno = ?";
                $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
                if (!empty($t)) {
                    $starty = 100;
                    foreach ($t as $key3 => $value3) {
                        data_set(
                            $nodes,
                            $t[$key3]->docno,
                            [
                                'align' => 'left',
                                'x' => $startx,
                                'y' => $starty,
                                'w' => 250,
                                'h' => 80,
                                'type' => $t[$key3]->docno,
                                'label' => $t[$key3]->rem,
                                'color' => 'green',
                                'details' => [$t[$key3]->dateid]
                            ]
                        );
                        $starty += 100;
                        array_push($links, ['from' => $t[$key3]->docno, 'to' => $crdata[$key2]->docno]);

                        //DS
                        $qry = "
            select  head.docno, date(head.dateid) as dateid,
            CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from glhead as head
            left join gldetail as detail on head.trno = detail.trno
            where detail.refx = ?
            union all
            select  head.docno, date(head.dateid) as dateid,
            CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
            from lahead as head
            left join ladetail as detail on head.trno = detail.trno
            where detail.refx = ?";
                        $dsdata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
                        if (!empty($dsdata)) {
                            foreach ($dsdata as $key4 => $value4) {
                                data_set(
                                    $nodes,
                                    $dsdata[$key4]->docno,
                                    [
                                        'align' => 'left',
                                        'x' => $startx + 800,
                                        'y' => 100,
                                        'w' => 250,
                                        'h' => 80,
                                        'type' => $dsdata[$key4]->docno,
                                        'label' => $dsdata[$key4]->rem,
                                        'color' => 'orange',
                                        'details' => [$dsdata[$key4]->dateid]
                                    ]
                                );
                                array_push($links, ['from' => $dsdata[$key4]->docno, 'to' => $crdata[$key2]->docno]);
                            }
                        }
                    }
                }
            }
        }



        $data['nodes'] = $nodes;
        $data['links'] = $links;

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            case 'voidtrans':
                return $this->voidtrans($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function updateperitem($config)
    {
        $error_msg = '';
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);

        if ($isupdate['status'] == false) {
            $error_msg .= $isupdate['msg'];
        }

        $data = $this->opendetailline($config);
        if (!$isupdate['status']) {
            $data[0]->errcolor = 'bg-red-2';
            return ['row' => $data, 'status' => false, 'msg' => $error_msg]; //'Payment amount is greater than setup amount.'
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }


    public function updateitem($config)
    {
        $error_msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $isupdate = $this->additem('update', $config);
            if ($isupdate['status'] == false) {
                $error_msg .= $isupdate['msg'];
                break;
            }
        }
        $data = $this->opendetail($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);

        $msg1 = '';
        $msg2 = '';
        $msg3 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Some entries have zero value both debit and credit ';
                } else {
                    $msg2 = ' Reference Amount is lower than encoded amount ';
                }
            }
        }

        $msg3 = 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')';
        if ($error_msg != '') {
            if ($msg1 != '' || $msg2 != '') {
                $error_msg .= ',  ' . $msg3;
            }
        } else {
            $error_msg = $msg3;
        }

        if ($isupdate['status']) {
            return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            if ($isupdate['msg'] == '') {
                return ['accounting' => $data, 'status' => true, 'msg' => $error_msg];
            } else {
                return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
            }
        }
    } //end function

    public function addallitem($config)
    {
        $error_msg = '';
        $status = true;
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $result = $this->additem('insert', $config);
            if (!$result['status']) {
                $error_msg .= ' ' . $result['msg'];
            }
        }
        if ($error_msg != '') {
            $msg = $error_msg;
            $status = false;
        } else {
            $msg = 'Successfully saved.';
        }
        $data = $this->opendetail($config['params']['trno'], $config);
        return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
    } //end function


    // insert and update detail
    public function additem($action, $config)
    {
        $companyid = $config['params']['companyid'];
        $acno = $config['params']['data']['acno'];
        $acnoname = $config['params']['data']['acnoname'];
        $trno = $config['params']['trno'];
        $dateid = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
        $systype = $this->companysetup->getsystemtype($config['params']);
        $db = $config['params']['data']['db'];
        $cr = $config['params']['data']['cr'];
        $fdb = $config['params']['data']['fdb'];
        $fcr = $config['params']['data']['fcr'];
        $postdate = $config['params']['data']['postdate'];
        // $client = $config['params']['data']['client'];
        $refx = 0;
        $linex = 0;
        $ref = '';
        $checkno = '';
        $isewt = false;
        $isvat = false;
        $isvewt = false;
        $ewtcode = '';
        $ewtrate = '';
        $damt = 0;
        $project = 0;
        $subproject = 0;
        $stageid = 0;
        $rem = '';
        $poref = '';
        $podate = null;
        $qttrno = 0;
        $lastdp = false;
        $mcrefx = 0;
        $mclinex = 0;

        $projectid = 0;
        $phaseid = 0;
        $modelid = 0;
        $blklotid = 0;
        $amenityid = 0;
        $subamenityid = 0;


        $type = '';
        $client = '';

        if (isset($config['params']['data']['client'])) {
            $client = $config['params']['data']['client'];
        }

        if (isset($config['params']['data']['mcrefx'])) {
            $mcrefx = $config['params']['data']['mcrefx'];
        }
        if (isset($config['params']['data']['mclinex'])) {
            $mclinex = $config['params']['data']['mclinex'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }
        if (isset($config['params']['data']['ref'])) {
            $ref = $config['params']['data']['ref'];
        }
        if (isset($config['params']['data']['checkno'])) {
            $checkno = $config['params']['data']['checkno'];
        }
        if (isset($config['params']['data']['isvat'])) {
            $isvat = $config['params']['data']['isvat'];
        }
        if (isset($config['params']['data']['isewt'])) {
            $isewt = $config['params']['data']['isewt'];
        }
        if (isset($config['params']['data']['ewtcode'])) {
            $ewtcode = $config['params']['data']['ewtcode'];
        }
        if (isset($config['params']['data']['ewtrate'])) {
            $ewtrate = $config['params']['data']['ewtrate'];
        }

        if (isset($config['params']['data']['isvewt'])) {
            $isvewt = $config['params']['data']['isvewt'];
        }

        if (isset($config['params']['data']['projectid'])) {
            $project = $config['params']['data']['projectid'];
        }

        if (isset($config['params']['data']['subproject'])) {
            $subproject = $config['params']['data']['subproject'];
        }

        if (isset($config['params']['data']['stageid'])) {
            $stageid = $config['params']['data']['stageid'];
        }

        if (isset($config['params']['data']['poref'])) {
            $poref = $config['params']['data']['poref'];
        }

        if (isset($config['params']['data']['podate'])) {
            $podate = $config['params']['data']['podate'];
        }

        if (isset($config['params']['data']['qttrno'])) {
            $qttrno = $config['params']['data']['qttrno'];
        }

        if ($project == '') {
            $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        }


        if (isset($config['params']['data']['lastdp'])) {
            $lastdp = $config['params']['data']['lastdp'];
        }

        if (isset($config['params']['data']['type'])) {
            $type = $config['params']['data']['type'];
        }

        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
            if ($db != 0) {
                $damt = $db;
            } else {
                $damt = $cr;
            }
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;

            if ($db != 0) {
                $ddb = $this->coreFunctions->getfieldvalue($this->detail, 'db', 'trno=? and line =?', [$trno, $line]);

                if ($db != number_format($ddb, 2)) {
                    $damt = $db;
                } else {
                    $damt = $config['params']['data']['damt'];
                }
            } else {
                $dcr = $this->coreFunctions->getfieldvalue($this->detail, 'cr', 'trno=? and line =?', [$trno, $line]);
                if ($cr != number_format($dcr, 2)) {
                    $damt = $cr;
                } else {
                    $damt = $config['params']['data']['damt'];
                }
            }
        }

        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
        $data = [
            'trno' => $trno,
            'line' => $line,
            'acnoid' => $acnoid,
            'client' => $client,
            'db' => $db,
            'cr' => $cr,
            'fdb' => $fdb,
            'fcr' => $fcr,
            'postdate' => $postdate,
            'rem' => $rem,
            'projectid' => $project,
            'refx' => $refx,
            'linex' => $linex,
            'ref' => $ref,
            'checkno' => $checkno,
            'isewt' => $isewt,
            'isvat' => $isvat,
            'isvewt' => $isvewt,
            'ewtcode' => $ewtcode,
            'ewtrate' => $ewtrate,
            'damt' => $damt,
            'poref' => $poref,
            'podate' => $podate,
            'qttrno' => $qttrno,
            'lastdp' => $lastdp,
            'mcrefx' => $mcrefx,
            'mclinex' => $mclinex,
            'type' => $type
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $msg = '';
        $status = true;

        if ($isvewt == "true" && ($isewt == "true" || $isvat == "true")) {
            $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
            return ['status' => false, 'msg' => $msg];
        }

        if ($action == 'insert') {
            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $current_timestamp;
            $data['sortline'] =  $data['line'];
            if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
                $msg = 'Account was successfully added.';
                $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' DB:' . $db . ' CR:' . $cr . ' Client:' . $client . ' Date:' . $postdate . ' Ref:' . $ref);
                if ($refx != 0) {
                    if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
                        $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
                        $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
                        $msg = "Payment Amount is greater than Amount Setup";
                        $this->logger->sbcwritelog($trno, $config, 'ACCTG', $msg . ' - Line:' . $line);
                        $status = false;
                    }
                    $this->coreFunctions->execqry("update hdetailinfo set ortrno = " . $trno . ",checkno = '" . $checkno . "', paymentdate = '" . $dateid . "' where trno =? and line =? ", "update", [$refx, $linex]);
                }

                if ($mcrefx != 0) {
                    $this->coreFunctions->sbcupdate('hmchead', ['crtrno' => $trno], ['trno' => $mcrefx]);
                }

                $row = $this->opendetailline($config);
                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add Account Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
                if ($refx != 0) {

                    if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
                        $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
                        $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
                        $return = false;
                    }
                    $this->coreFunctions->execqry("update hdetailinfo set  ortrno = " . $trno . ",checkno = '" . $checkno . "', paymentdate = '" . $dateid . "' where trno =? and line =? ", "update", [$refx, $linex]);
                }
            } else {
                $return = false;
            }
            return ['status' => $return, 'msg' => ''];
        }
    } // end function

    public function isexept($config, $trno, $line, $checkdate)
    {
        $sj = $this->coreFunctions->opentable("select h.docno, h.terms, terms.days, h.deldate, h.due, cr.dateid
        from ladetail as d left join gldetail as sj on sj.trno=d.refx and sj.line=d.linex 
        left join glhead as h on h.trno=sj.trno left join terms on terms.terms=h.terms
        left join lahead as cr on cr.trno=d.trno
        where d.trno=? and d.refx<>0", [$trno]);

        $checkdate = date('Y-m-d', strtotime($checkdate));
        $this->coreFunctions->LogConsole("check date: " . $checkdate);

        if (!empty($sj)) {
            $isexept = 1;
            foreach ($sj as $key => $value) {
                $value->dateid = date('Y-m-d', strtotime($value->dateid));
                switch ($value->days) {
                    case 0;
                        break;
                    case 7:
                        $this->coreFunctions->LogConsole("7days: ");

                        if ($checkdate > $value->due) {
                            $isexept = 0;
                        }

                        break;
                    case 15:
                        $this->coreFunctions->LogConsole("15days: ");
                        $due = date('Y-m-d', strtotime("+7 day", strtotime($value->deldate)));
                        $this->coreFunctions->LogConsole("due: " . $due . ", dateid: " . $value->dateid);

                        if ($checkdate > $value->due) {
                            $isexept = 0;
                        }

                        break;
                    default:
                        $this->coreFunctions->LogConsole("30+days: ");
                        $due = date('Y-m-d', strtotime("+14 day", strtotime($value->deldate)));
                        $this->coreFunctions->LogConsole("due: " . $due . ", dateid: " . $value->dateid);

                        if ($checkdate > $due) {
                            $isexept = 0;
                        }

                        break;
                }
            }
            $this->coreFunctions->LogConsole("isexcept: " . $isexept);
            $this->coreFunctions->sbcupdate("ladetail", ['isexcept' => $isexept], ['trno' => $trno, 'line' => $line]);
        }
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        if ($this->companysetup->getsystemtype($config['params']) == 'REALESTATE') {
            $msg = $this->othersClass->hasnextcr($config);
            if ($msg !== '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'This Transaction cannot be DELETED,' . $msg];
            }
        }

        $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex,t.qttrno,t.line,t.lastdp from ' . $this->detail . ' as t left join coa on coa.acnoid=t.acnoid where t.trno=? ', [$trno]);

        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
            if ($data[$key]->refx <> 0) {
                if ($this->companysetup->getsystemtype($config['params']) == 'REALESTATE') {
                    $checkno = $this->coreFunctions->getfieldvalue($this->head, "checkno", "trno=?", [$trno]);
                    $checkdate = $this->coreFunctions->getfieldvalue($this->head, "checkdate", "trno=?", [$trno]);
                    $amount = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno]);
                    $ourref = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
                    switch (strtoupper($ourref)) {
                        case 'RESERVATION':
                            $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARRF'");
                            break;
                        case 'DOWN PAYMENT':
                            $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARDP'");
                            break;
                        case 'MONTHLY AMORTIZATION':
                            $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
                            break;
                    }

                    if ($data[$key]->refx <> 0) {
                        $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config, 0, 1);
                        if ($ourref != "" && strtoupper($ourref) != "OTHERS") {
                            $this->othersClass->recomputeschedule($trno, $data[$key]->refx, $checkno, $checkdate, $amount, $aralias, 1);
                            $this->coreFunctions->sbcupdate('hdetailinfo', ['ortrno' => 0, 'paymentdate' => null, 'checkno' => ''], ['ortrno' => $trno]);
                        }
                        $this->coreFunctions->sbcupdate('hdetailinfo', ['ortrno' => 0, 'paymentdate' => null, 'checkno' => ''], ['ortrno' => $trno]); //for cdo

                        if ($config['params']['companyid'] == 59) { //roosevelt
                            $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => 0, 'orline' => 0], ['trno' => $data[$key]->refx, 'line' => $data[$key]->linex]);
                            $this->coreFunctions->sbcupdate('hrhdetail', ['ortrno' => 0, 'orline' => 0], ['trno' => $data[$key]->refx, 'line' => $data[$key]->linex]);
                        }
                    }
                } else {
                    $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config);
                }
            }
        }

        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
    }



    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->opendetailline($config);

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->detail . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        if ($data[0]->refx != 0) {
            $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
        }
        if ($data[0]->qttrno <> 0 && $data[0]->lastdp <> 0) {
            $this->coreFunctions->sbcupdate('hqshead', ['crtrno' => 0], ['trno' => $data[0]->qttrno]);
            $this->coreFunctions->sbcupdate('qshead', ['crtrno' => 0], ['trno' => $data[0]->qttrno]);
        } elseif ($data[0]->qttrno <> 0 && $data[0]->lastdp == 0) {
            $this->coreFunctions->sbcupdate('hqshead', ['crtrno' => 0], ['trno' => $data[0]->qttrno]);
            $this->coreFunctions->sbcupdate('qshead', ['crtrno' => 0], ['trno' => $data[0]->qttrno]);
        }

        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog(
            $trno,
            $config,
            'DETAILINFO',
            'DELETE - Line:' . $line
                . ' Notes:' . $config['params']['row']['rem']
        );
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr'] . ' Client:' . $data[0]['client'] . ' Date:' . $data[0]['postdate'] . ' Ref:' . $data[0]['ref']);
        return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    } // end function

    public function getunpaidselected($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $total = 0;
        $fb = $this->coreFunctions->getfieldvalue($this->head, "contra", "trno=?", [$trno]);

        $data = $config['params']['rows'];
        foreach ($data as $key => $value) {
            $config['params']['data']['acno'] = $data[$key]['acno'];
            $config['params']['data']['acnoname'] = $data[$key]['acnoname'];
            if ($data[$key]['db'] != 0) {
                $config['params']['data']['db'] = 0;
                $config['params']['data']['cr'] = $data[$key]['bal'];
                $config['params']['data']['fdb'] = 0;
                $config['params']['data']['fcr'] = abs($data[$key]['fdb']);
                $total = $total + $data[$key]['bal'];
            } else {
                $config['params']['data']['db'] = $data[$key]['bal'];
                $config['params']['data']['cr'] = 0;
                $config['params']['data']['fdb'] = $data[$key]['fdb'];
                $config['params']['data']['fcr'] = 0;
                $total = $total - $data[$key]['bal'];
            }
            $config['params']['data']['postdate'] = $data[$key]['dateid'];
            $config['params']['data']['rem'] = $data[$key]['rem'];
            $config['params']['data']['client'] = $data[$key]['client'];
            $config['params']['data']['refx'] = $data[$key]['trno'];
            $config['params']['data']['linex'] = $data[$key]['line'];
            $config['params']['data']['type'] = $data[$key]['ctype'];

            if ($data[$key]['doc'] == 'AR') {
                if ($data[$key]['ref'] != '') {
                    $config['params']['data']['ref'] = $data[$key]['ref'];
                } else {
                    $config['params']['data']['ref'] = $data[$key]['docno'];
                }
            } else {
                $config['params']['data']['ref'] = $data[$key]['docno'];
            }

            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            }
        } //end foreach

        //forcebalance
        if ($this->companysetup->crforcebal($config['params'])) {
            $config['params']['data']['acno'] = $fb;
            $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$fb]);

            $config['params']['data']['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
            if ($total < 0) {
                $config['params']['data']['cr'] = abs($total);
                $config['params']['data']['db'] = 0;
            } else {
                $config['params']['data']['db'] = $total;
                $config['params']['data']['cr'] = 0;
            }

            $config['params']['data']['ref'] = '';
            $config['params']['data']['rem'] = '';
            $config['params']['data']['project'] = 0;
            $config['params']['data']['refx'] = 0;
            $config['params']['data']['linex'] = 0;
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            }
        }

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts Successfull...'];
    } //end function

    public function generateautoentry($config)
    {
        $trno = $config['params']['trno'];
        $ourref = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        $contra = $this->coreFunctions->getfieldvalue($this->head, "contra", "trno=?", [$trno]);

        if ($ourref != "" && strtoupper($ourref) != 'OTHERS') {
            //delete existing entry
            $data = $this->opendetail($trno, $config);
            if (!empty($data)) {
                $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
                foreach ($data as $key => $value) {
                    if ($data[$key]->refx <> 0) {
                        $checkno = $this->coreFunctions->getfieldvalue($this->head, "checkno", "trno=?", [$trno]);
                        $checkdate = $this->coreFunctions->getfieldvalue($this->head, "checkdate", "trno=?", [$trno]);
                        $amount = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno]);

                        switch (strtoupper($ourref)) {
                            case 'RESERVATION':
                                $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARRF'");
                                break;
                            case 'DOWN PAYMENT':
                                $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARDP'");
                                break;
                            case 'MONTHLY AMORTIZATION':
                                $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
                                break;
                        }

                        if ($data[$key]->refx <> 0) {
                            $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config, 0, 1);
                            $this->othersClass->recomputeschedule($trno, $data[$key]->refx, $checkno, $checkdate, $amount, $aralias, 1);
                            $this->coreFunctions->sbcupdate('hdetailinfo', ['ortrno' => 0, 'paymentdate' => null, 'checkno' => ''], ['ortrno' => $trno]);
                        }
                    }
                }
            }
            //end delete

            $qry = "select cl.client,cl.clientid, head.dateid,head.phaseid,head.projectid,head.blklotid,head.ourref,head.phaseid,head.modelid,head.amount,head.checkno,head.checkdate from " . $this->head . " as head left join client as cl on cl.client = head.client where head.trno =?";

            $head = $this->coreFunctions->opentable($qry, [$trno]);
            $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
            $gpacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SAX'");
            $cashacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno = '\\" . $contra . "'");
            $saacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SA1'");
            $otheracnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SA2'"); //over
            $interestacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SA3'");
            $frmriacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SA4'");
            $detail = [];
            $d = [];
            $amt = 0;
            $paydiff = 0;

            if (!empty($head)) {
                switch (strtoupper($head[0]->ourref)) {
                    case 'RESERVATION':
                        $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARRF'");
                        break;
                    case 'DOWN PAYMENT':
                        $aralias = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='ARDP'");
                        break;
                }

                $qry = "select ar.trno,ar.line, ar.bal,ar.dateid,head.docno from glhead as head left join arledger as ar on head.trno = ar.trno
        where head.clientid = " . $head[0]->clientid . " and head.projectid =" . $head[0]->projectid . " and 
        head.phaseid =" . $head[0]->phaseid . " and head.modelid = " . $head[0]->modelid . " and head.blklotid = " . $head[0]->blklotid . " and ar.acnoid = " . $aralias;

                $ar = $this->coreFunctions->opentable($qry);

                $di = $this->coreFunctions->opentable("select d.db,di.fi,di.mri,di.interest,d.rem from gldetail as d left join hdetailinfo as di on di.trno = d.trno and di.line = d.line 
        where d.acnoid = " . $aralias . " and d.trno = " . $ar[0]->trno . " and di.ortrno = 0 order by d.trno,d.line limit 1");

                $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$trno]);

                if ($line == '') {
                    $line = 0;
                }
                $line = $line + 1;

                if (!empty($ar)) {
                    if ($ar[0]->bal >= $head[0]->amount) {
                        if ($head[0]->amount > $di[0]->db) {
                            $amt = $di[0]->db;
                            $paydiff = $head[0]->amount - $di[0]->db;
                        } else {
                            $amt = $head[0]->amount;
                        }
                    } else {
                        $amt = $ar[0]->bal;
                        $paydiff = $head[0]->amount - $ar[0]->bal;
                    }

                    //cash
                    $d['trno'] = $trno;
                    $d['line'] = $line;
                    $d['refx'] = 0;
                    $d['linex'] = 0;
                    $d['client'] = $head[0]->client;
                    $d['acnoid'] = $cashacnoid;
                    $d['postdate'] = $head[0]->checkdate;
                    $d['checkno'] = $head[0]->checkno;
                    $d['ref'] = '';
                    $d['db'] = $head[0]->amount;
                    $d['cr'] = 0;
                    $d['projectid'] = $head[0]->projectid;
                    $d['phaseid'] = $head[0]->phaseid;
                    $d['modelid'] = $head[0]->modelid;
                    $d['blklotid'] = $head[0]->blklotid;
                    $d['rem'] = '';
                    array_push($detail, $d);
                    $line += 1;

                    //ar ref
                    $d['trno'] = $trno;
                    $d['line'] = $line;
                    $d['refx'] = $ar[0]->trno;
                    $d['linex'] = $ar[0]->line;
                    $d['client'] = $head[0]->client;
                    $d['acnoid'] = $aralias;
                    $d['postdate'] = $ar[0]->dateid;
                    $d['checkno'] = '';
                    $d['ref'] = $ar[0]->docno;
                    $d['db'] = 0;
                    $d['cr'] = $amt;
                    $d['projectid'] = $head[0]->projectid;
                    $d['phaseid'] = $head[0]->phaseid;
                    $d['modelid'] = $head[0]->modelid;
                    $d['blklotid'] = $head[0]->blklotid;
                    $d['rem'] = $di[0]->rem;
                    array_push($detail, $d);
                    $line += 1;

                    //reversal gp
                    $d['trno'] = $trno;
                    $d['line'] = $line;
                    $d['refx'] = 0;
                    $d['linex'] = 0;
                    $d['client'] = $head[0]->client;
                    $d['acnoid'] = $gpacnoid;
                    $d['postdate'] = $head[0]->dateid;
                    $d['checkno'] = '';
                    $d['ref'] = '';
                    $d['db'] = $amt;
                    $d['cr'] = 0;
                    $d['projectid'] = $head[0]->projectid;
                    $d['phaseid'] = $head[0]->phaseid;
                    $d['modelid'] = $head[0]->modelid;
                    $d['blklotid'] = $head[0]->blklotid;
                    $d['rem'] = '';
                    array_push($detail, $d);
                    $line += 1;



                    if (strtoupper($head[0]->ourref) == 'MONTHLY AMORTIZATION') {
                        //interest
                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $interestacnoid;
                        $d['postdate'] = $head[0]->dateid;
                        $d['checkno'] = '';
                        $d['ref'] = '';
                        $d['db'] = 0;
                        $d['cr'] = $di[0]->interest;
                        $d['projectid'] = $head[0]->projectid;
                        $d['phaseid'] = $head[0]->phaseid;
                        $d['modelid'] = $head[0]->modelid;
                        $d['blklotid'] = $head[0]->blklotid;
                        $d['rem'] = '';
                        array_push($detail, $d);
                        $line += 1;

                        $amt = $amt - $di[0]->interest;

                        //fi & mri
                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $frmriacnoid;
                        $d['postdate'] = $head[0]->dateid;
                        $d['checkno'] = '';
                        $d['ref'] = '';
                        $d['db'] = 0;
                        $d['cr'] = $di[0]->fi + $di[0]->mri;
                        $d['projectid'] = $head[0]->projectid;
                        $d['phaseid'] = $head[0]->phaseid;
                        $d['modelid'] = $head[0]->modelid;
                        $d['blklotid'] = $head[0]->blklotid;
                        $d['rem'] = '';
                        array_push($detail, $d);
                        $line += 1;

                        $amt = $amt - ($di[0]->fi + $di[0]->mri);

                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $saacnoid;
                        $d['postdate'] = $head[0]->dateid;
                        $d['checkno'] = '';
                        $d['ref'] = '';
                        $d['db'] = 0;
                        $d['cr'] = $amt;
                        $d['projectid'] = $head[0]->projectid;
                        $d['phaseid'] = $head[0]->phaseid;
                        $d['modelid'] = $head[0]->modelid;
                        $d['blklotid'] = $head[0]->blklotid;
                        $d['rem'] = '';
                        array_push($detail, $d);
                        $line += 1;
                    } else {
                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $saacnoid;
                        $d['postdate'] = $head[0]->dateid;
                        $d['checkno'] = '';
                        $d['ref'] = '';
                        $d['db'] = 0;
                        $d['cr'] = $amt;
                        $d['projectid'] = $head[0]->projectid;
                        $d['phaseid'] = $head[0]->phaseid;
                        $d['modelid'] = $head[0]->modelid;
                        $d['blklotid'] = $head[0]->blklotid;
                        $d['rem'] = '';
                        array_push($detail, $d);
                        $line += 1;
                    }

                    if ($paydiff != 0) {
                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $otheracnoid;
                        $d['postdate'] = $head[0]->dateid;
                        $d['checkno'] = '';
                        $d['ref'] = '';
                        $d['db'] = 0;
                        $d['cr'] = $paydiff;
                        $d['projectid'] = $head[0]->projectid;
                        $d['phaseid'] = $head[0]->phaseid;
                        $d['modelid'] = $head[0]->modelid;
                        $d['blklotid'] = $head[0]->blklotid;
                        $d['rem'] = '';
                        array_push($detail, $d);
                        $line += 1;
                    }
                }

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

                        if ($this->coreFunctions->sbcinsert($this->detail, $detail[$key]) == 1) {
                            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                            $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $detail[$key]['line'] . ' Remarks:' . $detail[$key]['rem'] . ' DB:' . $detail[$key]['db'] . ' CR:' . $detail[$key]['cr'] . ' Client:' . $detail[$key]['client'] . ' Date:' . $detail[$key]['postdate']);
                            $status = true;
                            if ($detail[$key]['refx'] != 0) {
                                $acno = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid = " . $detail[$key]['acnoid']);
                                if (!$this->sqlquery->setupdatebal($detail[$key]['refx'], $detail[$key]['linex'], $acno, $config, 0, 1)) {
                                    $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $detail[$key]['line']]);
                                    $this->sqlquery->setupdatebal($detail[$key]['refx'], $detail[$key]['linex'], $acno, $config, 0, 1);
                                    $msg = "Payment Amount is greater than Amount Setup";
                                    return ['accounting' => [], 'status' => false, 'msg' => $msg];
                                }

                                //update detailinfo
                                $this->othersClass->recomputeschedule($trno, $detail[$key]['refx'], $head[0]->checkno, $head[0]->checkdate, $head[0]->amount, $aralias);
                            }
                            $msg = "Auto entry Successful";
                        } else {
                            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                            return ['accounting' => [], 'status' => false, 'msg' => 'Entry Failed'];
                        }
                    } //for $detail

                }
            }
        } else {
            $status = true;
            $msg  = "No entry available.";
        }


        $data = $this->opendetail($trno, $config);
        return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
    }

    public function getcontract($config)
    {
        $qry = "select distinct head.trno as cptrno,head.docno,head.clientid,head.clientname,i.clientname as planholder,c.client,sum(ar.bal) as bal,c.addr,ag.client as agentcode,ag.clientname as agentname,head.aftrno from glhead as head
        left join heainfo as i on i.trno = head.aftrno left join arledger as ar
        on ar.trno = head.trno left join client as c on c.clientid = head.clientid
        left join client as ag on ag.clientid = head.agentid where head.doc ='CP' and ar.bal <>0 and ar.trno =?
        group by head.trno,head.docno,head.clientid,head.clientname,i.clientname,c.client,c.addr,ag.client ,ag.clientname,head.aftrno ";
        return $qry;
    }

    public function applytoar($config)
    {
        $trno = $config['params']['trno'];
        $cptrno = $this->coreFunctions->getfieldvalue("cntnuminfo", "cptrno", "trno=?", [$trno]);
        $headamt = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno]);

        if (floatval($headamt) == 0) {
            return ['accounting' => [], 'status' => false, 'msg' => 'Please enter amount received...'];
        }

        $qry = "select sum(ar.bal) as value from glhead as head left join arledger as ar on head.trno = ar.trno
    left join gldetail as detail on detail.trno = ar.trno and detail.line = ar.line
    where head.trno = " . $cptrno . " and ar.bal<>0 ";

        $totalar = $this->coreFunctions->datareader($qry);

        if ($headamt > $totalar) {
            return ['accounting' => [], 'status' => false, 'msg' => 'Payment received bigger than total outstanding receivables...'];
        }

        if ($cptrno != 0) {
            //delete existing entry
            $data = $this->opendetail($trno, $config);
            if (!empty($data)) {
                return ['accounting' => [], 'status' => false, 'msg' => 'Please delete all existing entries first...'];
            }

            $qry = "select cl.client,cl.clientid, head.dateid,head.ourref,head.amount,head.checkno,head.checkdate from " . $this->head . " as head 
      left join client as cl on cl.client = head.client where head.trno =?";

            $head = $this->coreFunctions->opentable($qry, [$trno]);
            $detail = [];
            $d = [];
            $amt = 0;
            $appamt = 0;
            $colamt = 0;
            $paydiff = 0;
            $dateid = '';
            $bal = 0;
            $pf = 0;

            if (!empty($head)) {
                switch (strtoupper($head[0]->ourref)) {
                    case 'CASH':
                        $cashacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'CA1'");
                        $dateid = $head[0]->dateid;
                        break;
                    case 'DEPOSIT':
                        $cashacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'CR2'");
                        $dateid = $head[0]->dateid;
                        break;
                    case 'ONLINE':
                        $cashacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'CR3'");
                        $dateid = $head[0]->dateid;
                        break;
                    default:
                        $cashacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'CR1'");
                        $dateid = $head[0]->checkdate;
                        break;
                }

                $qry = "select ar.trno,ar.line, ar.bal,ar.db,ar.cr,ar.dateid,head.docno,ar.acnoid,detail.rem,coa.alias from glhead as head left join arledger as ar on head.trno = ar.trno
        left join gldetail as detail on detail.trno = ar.trno and detail.line = ar.line left join coa on coa.acnoid = ar.acnoid
        where head.trno = " . $cptrno . " and ar.bal<>0 order by dateid,trno,line";

                $ar = $this->coreFunctions->opentable($qry);

                $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
                $line = $this->coreFunctions->datareader($qry, [$trno]);

                if ($line == '') {
                    $line = 0;
                }
                $line = $line + 1;

                if (!empty($ar)) {
                    $appamt = $head[0]->amount;
                    $colamt = $head[0]->amount;

                    foreach ($ar as $k => $v) {
                        if ($appamt != 0) {
                            if ($ar[$k]->db != 0) {
                                $bal = $ar[$k]->bal;
                            } else {
                                $bal = $ar[$k]->bal * -1;
                            }

                            if ($bal >= $appamt) {
                                $amt = $appamt;
                                $appamt = 0;
                            } else {
                                $amt = $bal;
                                if ($bal < 0) {
                                    $appamt = $appamt + $bal;
                                } else {
                                    $appamt = $appamt - $bal;
                                }
                            }

                            //ar ref
                            $d['trno'] = $trno;
                            $d['line'] = $line;
                            $d['refx'] = $ar[$k]->trno;
                            $d['linex'] = $ar[$k]->line;
                            $d['client'] = $head[0]->client;
                            $d['acnoid'] = $ar[$k]->acnoid;
                            $d['postdate'] = $ar[$k]->dateid;
                            $d['checkno'] = '';
                            $d['ref'] = $ar[$k]->docno;
                            if ($amt > 0) {
                                $d['db'] = 0;
                                $d['cr'] = $amt;
                            } else {
                                $d['cr'] = 0;
                                $d['db'] = $amt;
                            }

                            $d['rem'] = $ar[$k]->rem;
                            if ($ar[$k]->alias == 'AR2') {
                                $pf = $pf + $amt;
                            }
                            array_push($detail, $d);
                            $line += 1;
                        }
                    }

                    $d['trno'] = $trno;
                    $d['line'] = $line;
                    $d['refx'] = 0;
                    $d['linex'] = 0;
                    $d['client'] = $head[0]->client;
                    $d['acnoid'] = $cashacnoid;
                    $d['postdate'] = $dateid;
                    $d['checkno'] = $head[0]->checkno;
                    $d['ref'] = '';
                    $d['db'] = $colamt - $pf;
                    $d['cr'] = 0;
                    $d['rem'] = '';
                    array_push($detail, $d);
                    $line += 1;

                    if ($pf != 0) {
                        $d['trno'] = $trno;
                        $d['line'] = $line;
                        $d['refx'] = 0;
                        $d['linex'] = 0;
                        $d['client'] = $head[0]->client;
                        $d['acnoid'] = $cashacnoid;
                        $d['postdate'] = $dateid;
                        $d['checkno'] = $head[0]->checkno;
                        $d['ref'] = '';
                        $d['db'] = $pf;
                        $d['cr'] = 0;
                        $d['rem'] = 'Processing fee';
                        array_push($detail, $d);
                        $line += 1;
                    }
                }

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

                        if ($this->coreFunctions->sbcinsert($this->detail, $detail[$key]) == 1) {
                            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                            $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $detail[$key]['line'] . ' Remarks:' . $detail[$key]['rem'] . ' DB:' . $detail[$key]['db'] . ' CR:' . $detail[$key]['cr'] . ' Client:' . $detail[$key]['client'] . ' Date:' . $detail[$key]['postdate']);
                            $status = true;
                            if ($detail[$key]['refx'] != 0) {
                                $acno = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid = " . $detail[$key]['acnoid']);
                                if (!$this->sqlquery->setupdatebal($detail[$key]['refx'], $detail[$key]['linex'], $acno, $config, 0, 1)) {
                                    $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $detail[$key]['line']]);
                                    $this->sqlquery->setupdatebal($detail[$key]['refx'], $detail[$key]['linex'], $acno, $config, 0, 1);
                                    $msg = "Payment Amount is greater than Amount Setup";
                                    return ['accounting' => [], 'status' => false, 'msg' => $msg];
                                }
                            }
                            $msg = "Auto entry Successful";
                        } else {
                            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                            return ['accounting' => [], 'status' => false, 'msg' => 'Entry Failed'];
                        }
                    } //for $detail

                }
            } else {
                $status = true;
                $msg  = "No entry available.";
            }
        }

        $ourref = $this->coreFunctions->getfieldvalue($this->head, "ourref", "trno=?", [$trno]);
        if (strtoupper($ourref) == 'CASH') {
            $this->posttrans($config);
        }
        $data = $this->opendetail($trno, $config);
        return ['accounting' => $data, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
    }
    public function getmccollection($config)
    {
        $trno = $config['params']['trno'];
        $headdate = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
        $rows = [];
        $mcrow = $config['params']['rows'][0];
        $status = true;

        if ($mcrow['trnxtype'] == 'Advance Payment') {
            $fb = $this->coreFunctions->getfieldvalue("coa", "acno", "acnoid=?", [$mcrow['acnoid']]);
            $config['params']['data']['acno'] = $fb;
            $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$fb]);
            $config['params']['data']['db'] = 0;
            $config['params']['data']['cr'] = $mcrow['amount'];
            $config['params']['data']['fdb'] = 0;
            $config['params']['data']['fcr'] = 0;
            $config['params']['data']['postdate'] =  $mcrow['dateid'];
            $config['params']['data']['rem'] = '';
            $config['params']['data']['client'] = $mcrow['client'];
            $config['params']['data']['refx'] = 0;
            $config['params']['data']['linex'] = 0;
            $config['params']['data']['mcrefx'] = $mcrow['mctrno'];
            $config['params']['data']['ref'] = $mcrow['docno'];
            $config['params']['data']['projectid'] = 0;
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            }

            $head['yourref'] = $mcrow['yourref'];
            $head['ourref'] = $mcrow['ourref'];
            $head['checkno'] = $mcrow['checkinfo'];
            $head['checkdate'] = $mcrow['checkdate'];
            $head['dateid'] = $mcrow['dateid'];
            $head['rem'] = $mcrow['hrem'];
            $head['amount'] = $this->othersClass->sanitizekeyfield('amt', $mcrow['amount']);
            $this->coreFunctions->sbcupdate($this->head, $head, ["trno" => $trno]);
        } else {
            if ($mcrow['center'] != $mcrow['arcenter']) {
                $fb = $this->coreFunctions->getfieldvalue("center", "accountno", "code=?", [$mcrow['arcenter']]);
                $config['params']['data']['acno'] = $fb;
                $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$fb]);
                $config['params']['data']['db'] = 0;
                $config['params']['data']['cr'] = $mcrow['amount'];
                $config['params']['data']['fdb'] = 0;
                $config['params']['data']['fcr'] = 0;
                $config['params']['data']['postdate'] = $mcrow['dateid'];
                $config['params']['data']['rem'] = '';
                $config['params']['data']['client'] = $mcrow['client'];
                $config['params']['data']['refx'] = 0;
                $config['params']['data']['linex'] = 0;
                $config['params']['data']['mcrefx'] = $mcrow['mctrno'];
                $config['params']['data']['ref'] = $mcrow['docno'];
                $config['params']['data']['projectid'] = 0;
                $return = $this->additem('insert', $config);
                if ($return['status']) {
                    array_push($rows, $return['row'][0]);
                }

                $head['yourref'] = $mcrow['yourref'];
                $head['ourref'] = $mcrow['ourref'];
                $head['checkno'] = $mcrow['checkinfo'];
                $head['checkdate'] = $mcrow['checkdate'];
                $head['dateid'] = $mcrow['dateid'];
                $head['rem'] = $mcrow['hrem'];
                $head['amount'] = $this->othersClass->sanitizekeyfield('amt', $mcrow['amount']);
                $this->coreFunctions->sbcupdate($this->head, $head, ["trno" => $trno]);
            } else {
                $this->mctoar($config);
            }
        }

        $data = $this->opendetail($trno, $config);
        return ['accounting' => $data, 'status' => $status, 'msg' => 'OK', 'reloadhead' => true, 'trno' => $trno, 'moduletype' => 'module'];
    }

    private function mctoar($config)
    {
        $trno = $config['params']['trno'];
        $headdate = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
        $rows = [];
        $mcrow = $config['params']['rows'][0];
        $status = true;

        if ($mcrow['trnxtype'] == 'Downpayment-MC' || $mcrow['trnxtype'] == 'Downpayment-Spareparts') {
            $qry = "select ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,left(ar.dateid,10) as dateid,ar.fdb, ar.fcr,mcd.trno as mcrefx,head.docno as mcdocno,head.dateid as mcdate,sum(mcd.penalty) as penalty,num.center,cnum.center as arcenter
      from  hmchead as head 
      left join hmcdetail as mcd on mcd.trno = head.trno
      left join gldetail as d on d.mctrno = head.trno 
      left join arledger as ar on ar.trno = d.trno and ar.line = d.line
      left join coa on coa.acnoid=ar.acnoid    
      left join transnum as num on num.trno = head.trno
      left join cntnum as cnum on cnum.trno = ar.trno
      left join client as ctbl on ctbl.clientid = ar.clientid
      where ar.bal<>0 and head.isok = 0 and num.trno =? and coa.alias ='ARDP' group by
      ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,ar.dateid,ar.fdb, ar.fcr,mcd.trno,head.docno,head.dateid,num.center,cnum.center      
      order by dateid";
        } else {
            $qry = "select ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,left(ar.dateid,10) as dateid,ar.fdb, ar.fcr,mcd.trno as mcrefx,head.docno as mcdocno,head.dateid as mcdate,
      sum(mcd.penalty) as penalty,num.center,cnum.center as arcenter
      from  hmchead as head 
      left join hmcdetail as mcd on mcd.trno = head.trno
      left join gldetail as d on d.trno = mcd.refx and d.postdate = mcd.dateid
      left join arledger as ar on ar.trno = d.trno and ar.line = d.line
      left join coa on coa.acnoid=ar.acnoid    
      left join transnum as num on num.trno = head.trno
      left join cntnum as cnum on cnum.trno = ar.trno
      left join client as ctbl on ctbl.clientid = ar.clientid
      where ar.bal<>0 and head.isok = 0 and num.trno =? group by
      ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,ar.dateid,ar.fdb, ar.fcr,mcd.trno,head.docno,head.dateid,num.center,cnum.center
      union all
      select ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,left(ar.dateid,10) as dateid,ar.fdb, ar.fcr,mcd.trno as mcrefx,head.docno as mcdocno,head.dateid as mcdate,
      sum(mcd.penalty) as penalty,num.center,cnum.center as arcenter
      from  hmchead as head 
      left join hmcdetail as mcd on mcd.trno = head.trno
      left join gldetail as d on d.trno = mcd.refx and d.postdate = mcd.dateid
      left join apledger as ar on ar.trno = d.trno and ar.line = d.line
      left join coa on coa.acnoid=ar.acnoid    
      left join transnum as num on num.trno = head.trno
      left join cntnum as cnum on cnum.trno = ar.trno
      left join client as ctbl on ctbl.clientid = ar.clientid
      where ar.bal<>0 and head.isok = 0 and num.trno =? and coa.alias = 'AP3' group by
      ar.trno, ar.line,head.rem,head.amount,head.yourref,head.ourref,
      head.doc,ctbl.client,ctbl.clientname,ar.docno,ar.trno,ar.line,ar.acnoid,coa.acno,coa.acnoname,coa.alias,num.center,
      ar.clientid,ar.db, ar.cr, ar.bal ,ar.dateid,ar.fdb, ar.fcr,mcd.trno,head.docno,head.dateid,num.center,cnum.center
      order by dateid";
        }

        $data = $this->coreFunctions->opentable($qry, [$mcrow['mctrno'], $mcrow['mctrno']]);

        if (!empty($data)) {
            foreach ($data as $key2 => $value2) {
                $config['params']['data']['acno'] = $data[$key2]->acno;
                $config['params']['data']['acnoname'] = $data[$key2]->acnoname;
                if ($data[$key2]->db != 0) {
                    $config['params']['data']['db'] = 0;
                    $config['params']['data']['cr'] = $data[$key2]->bal;
                    $config['params']['data']['fdb'] = 0;
                    $config['params']['data']['fcr'] = abs($data[$key2]->fdb);
                } else {
                    $config['params']['data']['db'] = $data[$key2]->bal;
                    $config['params']['data']['cr'] = 0;
                    $config['params']['data']['fdb'] = $data[$key2]->fdb;
                    $config['params']['data']['fcr'] = 0;
                }

                $config['params']['data']['postdate'] = $data[$key2]->dateid;
                $config['params']['data']['rem'] = ''; //$data[$key2]->rem;
                $config['params']['data']['client'] = $data[$key2]->client;
                $config['params']['data']['refx'] = $data[$key2]->trno;
                $config['params']['data']['linex'] = $data[$key2]->line;
                $config['params']['data']['mcrefx'] = $data[$key2]->mcrefx;
                //$config['params']['data']['mclinex'] = $data[$key2]->mclinex;
                $config['params']['data']['ref'] = $data[$key2]->docno;
                $config['params']['data']['projectid'] = 0;
                $return = $this->additem('insert', $config);
                if (!$return['status']) {
                    return ['accounting' => [], 'status' => false, 'msg' => 'Add account failed.', 'reloadhead' => true];
                }

                //other income
                if ($data[$key2]->alias == 'AP3' && $data[$key2]->dateid < $data[$key2]->mcdate) { //rebate
                    $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='SA6'");
                    $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='SA6'");
                    $config['params']['data']['db'] = 0;
                    $config['params']['data']['cr'] = $data[$key2]->bal;
                    $config['params']['data']['fdb'] = 0;
                    $config['params']['data']['fcr'] = 0;
                    $config['params']['data']['postdate'] = $data[$key2]->mcdate;
                    $config['params']['data']['rem'] = 'Unclaimed Rebate';
                    $config['params']['data']['client'] = $data[$key2]->client;
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['mcrefx'] = 0;
                    $config['params']['data']['ref'] = '';
                    $config['params']['data']['projectid'] = 0;
                    $config['params']['data']['type'] = 'R';
                    $config['params']['data']['podate'] =  $data[$key2]->dateid;
                    $return = $this->additem('insert', $config);

                    if (!$return['status']) {
                        return ['accounting' => [], 'status' => false, 'msg' => 'Add account failed.', 'reloadhead' => true];
                    }
                }


                //unearned interest
                if ($data[$key2]->alias == 'AR2') {
                    $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='SA3'");
                    $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='SA3'");
                    $config['params']['data']['db'] = $data[$key2]->bal;
                    $config['params']['data']['cr'] = 0;
                    $config['params']['data']['fdb'] = 0;
                    $config['params']['data']['fcr'] = 0;
                    $config['params']['data']['postdate'] = $data[$key2]->mcdate;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['client'] = $data[$key2]->client;
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['mcrefx'] = 0;
                    $config['params']['data']['ref'] = '';
                    $config['params']['data']['projectid'] = 0;
                    $return = $this->additem('insert', $config);

                    if (!$return['status']) {
                        return ['accounting' => [], 'status' => false, 'msg' => 'Add account failed.', 'reloadhead' => true];
                    }

                    //int income
                    $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='SA8'");
                    $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='SA8'");
                    $config['params']['data']['db'] = 0;
                    $config['params']['data']['cr'] = $data[$key2]->bal;
                    $config['params']['data']['fdb'] = 0;
                    $config['params']['data']['fcr'] = 0;
                    $config['params']['data']['postdate'] = $data[$key2]->mcdate;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['client'] = $data[$key2]->client;
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['mcrefx'] = 0;
                    $config['params']['data']['ref'] = '';
                    $config['params']['data']['projectid'] = 0;
                    $return = $this->additem('insert', $config);

                    if (!$return['status']) {
                        return ['accounting' => [], 'status' => false, 'msg' => 'Add account failed.', 'reloadhead' => true];
                    }
                }
            } //end foreach

            //PENALTY
            $pt = "select mcd.penalty,mc.dateid as mcdate,mcd.dateid from hmcdetail as mcd left join hmchead as mc on mc.trno = mcd.trno where mcd.trno = ? and mcd.penalty<>0";
            $pdata = $this->coreFunctions->opentable($pt, [$mcrow['mctrno']]);
            if (!empty($pdata)) {
                foreach ($pdata as $p => $v) {
                    $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='SA6'");
                    $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='SA6'");
                    $config['params']['data']['db'] = 0;
                    $config['params']['data']['cr'] = $pdata[$p]->penalty;
                    $config['params']['data']['fdb'] = 0;
                    $config['params']['data']['fcr'] = 0;
                    $config['params']['data']['postdate'] = $pdata[$p]->mcdate;
                    $config['params']['data']['rem'] = 'Penalty for AR Due ' . date("m/d/Y", strtotime($pdata[0]->dateid));
                    $config['params']['data']['client'] = $data[0]->client;
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['mcrefx'] = $data[0]->mcrefx;
                    $config['params']['data']['podate'] = $pdata[$p]->dateid;
                    $config['params']['data']['ref'] = $data[0]->mcdocno;
                    $config['params']['data']['projectid'] = 0;
                    $config['params']['data']['type'] = 'P';
                    $return = $this->additem('insert', $config);

                    if (!$return['status']) {
                        return ['accounting' => [], 'status' => false, 'msg' => 'Add account failed.', 'reloadhead' => true];
                    }
                }
            }

            $head['yourref'] = $mcrow['yourref'];
            $head['ourref'] = $mcrow['ourref'];
            $head['dateid'] = $data[0]->mcdate;
            $head['rem'] = $mcrow['hrem'];
            $head['amount'] = $this->othersClass->sanitizekeyfield('amt', $mcrow['amount']);
            $this->coreFunctions->sbcupdate($this->head, $head, ["trno" => $trno]);
            $this->coreFunctions->sbcupdate("hmchead", ["isok" => 1], ["trno" => $mcrow['mctrno']]);
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
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    private function voidtrans($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $path = '';

        $docno = $this->coreFunctions->getfieldvalue($this->tablenum, "docno", "trno=?", [$trno]);
        //check if posted
        $isposted = $this->othersClass->isposted2($trno, $this->tablenum);

        if ($isposted) {
            return ['status' => false, 'msg' => 'Already Posted.'];
        } else {
            $detail = $this->opendetail($trno, $config);
            $thead = $this->head;
            $tdetail = $this->detail;

            if (!empty($detail)) {
                foreach ($detail as $k => $v) {
                    $qry = "insert into voiddetail (postdate,trno,line,acnoid,client,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
          editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid)
          select d.postdate,d.trno,d.line,d.acnoid,
          ifNull(client.client,''),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
          d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
          d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,1,d.branch,d.deptid
          from " . $thead . " as h
          left join " . $tdetail . " as d on d.trno=h.trno
          left join client on client.client=d.client
          where  d.trno=? and d.line =?
          ";
                    $result = $this->coreFunctions->execqry($qry, 'insert', [$detail[$k]->trno, $detail[$k]->line]);
                    if ($result) {
                        $this->coreFunctions->execqry("delete from " . $tdetail . " where trno =? and line =?", 'delete', [$detail[$k]->trno, $detail[$k]->line]);
                        if ($detail[$k]->refx != 0) {
                            if (!$this->sqlquery->setupdatebal($detail[$k]->refx, $detail[$k]->linex, $detail[$k]->acno, $config)) {
                                $this->coreFunctions->sbcupdate($tdetail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $detail[$k]->line]);
                                $this->sqlquery->setupdatebal($detail[$k]->refx, $detail[$k]->linex, $detail[$k]->acno, $config);
                            }
                        }

                        $this->logger->sbcwritelog($trno, $config, 'VOID', 'Document #: ' . $docno);
                    }
                }

                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $this->coreFunctions->execqry("update " . $this->head . " set voiddate = '" . $current_timestamp . "',voidby ='" . $user . "' where trno =? ", 'update', [$trno]);
            } else {
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $this->coreFunctions->execqry("update " . $this->head . " set voiddate = '" . $current_timestamp . "',voidby ='" . $user . "' where trno =? ", 'update', [$trno]);
            }

            $config['params']['trno'] =  $trno;
            $this->loadheaddata($config);
            return ['status' => true, 'msg' => 'Void successfully', 'reloadhead' => true, 'trno' => $trno, 'moduletype' => 'module'];
        }
    }
} //end class
