<?php

namespace App\Http\Classes\modules\cdo;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use Exception;
use DateTime;
use DateInterval;

class fu
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'FINANCING';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    public $defaultContra = 'AR1';
    private $stockselect;
    private $fields = [
        'trno',
        'docno',
        'dateid',
        'due',
        'client',
        'clientname',
        //'yourref',
        // 'ourref',
        // 'crref',
        'rem',
        'terms',
        'forex',
        'cur',
        'wh',
        'address',
        'contra',
        'tax',
        'vattype',
        'agent',
        'projectid',
        'creditinfo',
        'billid',
        'shipid',
        'branch',
        'deptid',
        'taxdef',
        'billcontactid',
        'shipcontactid',
        'ms_freight',
        'mlcp_freight',
        'shipto',
        'salestype',
        'sotrno',
        'statid',
        'deldate',
        'istrip',
        'ewt',
        'ewtrate',
        'modeofsales',
        'fpid'
        // 'rfno',
        // 'chsino',
        // 'swsno'

    ];
    private $otherfields = ['trno', 'downpayment', 'fmiscfee'];
    private $except = ['trno', 'dateid', 'due', 'creditinfo'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;
    private $headClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
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
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
        $this->headClass = new headClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5908,
            'edit' => 5909,
            'new' => 5910,
            'save' => 5911,
            'delete' => 5912,
            'print' => 5913,
            'lock' => 5914,
            'unlock' => 5915,
            'post' => 5916,
            'unpost' => 5917,
            'changeamt' => 5918,
            'acctg' => 5919,
            'release' => 5920,
            'whinfo' => 5921,
            'financing' => 5922
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $userid = $config['params']['adminid'];
        $dept = '';


        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $listclientname = 4;
        $mode = 5;
        $postedby = 6;
        $createby = 7;
        $editby = 8;
        $viewby = 9;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'modeofsales', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$liststatus]['name'] = 'statuscolor';

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];

        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = '';
        $lfield = '';
        $gfield = '';
        $ljoin = '';
        $gjoin = '';
        $group = '';
        $lstat = "'DRAFT'";
        $gstat = "'POSTED'";
        $lstatcolor = "'blue'";
        $gstatcolor = "'grey'";

        $join = '';
        $hjoin = '';
        $addparams = '';


        switch ($itemfilter) {
            case 'draft':
                $condition = ' and head.lockdate is null and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                break;
        }

        $linkstock = false;

        $dateid = "left(head.dateid,10) as dateid";
        $orderby = "order by dateid desc, docno desc";

        if ($searchfilter == "") $limit = 'limit 150';
        $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
        $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";


        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'mode.name', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        if ($linkstock) {
            if ($group == '') {
                $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby';
            }
        }

        $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,
     head.createby,head.editby,head.viewby,num.postedby,mode.name as modeofsales $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $ljoin
     " . $join . "
     left join trxstatus as stat on stat.line=num.statid
     left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,
     head.createby,head.editby,head.viewby, num.postedby,mode.name as modeofsales $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $gjoin
     " . $hjoin . "
     left join trxstatus as stat on stat.line=num.statid
     left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
     $orderby $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $col1 = [];
        $col1 = $this->fieldClass->create($fields);
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
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
            'lock',
            'unlock',
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
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['btndelete']);


        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'edititem' => ['label' => 'How to edit item details', 'action' => $step3],
            'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step4]
        ];

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'fu', 'title' => 'FU_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createTab($access, $config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        return [];
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
                $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? 
        order by trno desc limit 1", [$doc, $center]);
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
        $qryselect = "
        select
        num.center,
        head.trno,
        head.docno,
        ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,
        client.client,
        head.clientname,
        head.address,
        mode.name as modeofsales,
        left(head.dateid,10) as dateid,
        head.terms,
        head.contra,
        coa.acnoname,
        '' as dacnoname,
        head.rem,
        head.vattype,
        '' as dvattype,
        ifnull(format(hinfo.downpayment,2),'') as downpayment, 
        ifnull(hinfo.fmiscfee,'') as fmiscfee
        ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join client as b on b.clientid = head.branch
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as b on b.clientid = head.branch
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
        where head.trno = ? and num.doc=? and num.center=?  ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

        $head[0]['trno'] = 0;
        $head[0]['docno'] = '';
        return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }

    public function createHeadField($config)
    {
        // $inv = $this->companysetup->isinvonly($config['params']);
        // $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);

        $fields = ['docno', 'branchname', 'client', 'clientname', 'address', 'modeofsales'];
        // 
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'customer');
        data_set($col1, 'client.readonly', true);
        data_set($col1, 'clientname.label', 'Name');
        data_set($col1, 'clientname.readonly', true);
        data_set($col1, 'address.readonly', true);
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = [['dateid', 'terms'], 'dclientname', 'dacnoname', 'dvattype', 'rem'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AR Account');
        data_set($col2, 'dacnoname.lookupclass', 'AR');
        data_set($col2, 'dacnoname.type', 'input');
        data_set($col2, 'dacnoname.readonly', true);
        data_set($col2, 'terms.readonly', true);
        data_set($col2, 'rem.readonly', true);

        $fields = ['downpayment', 'interestrate', 'rrfactor', 'penaltyamt', 'rebate', 'fmiscfee', 'fmonthlyamortization'];

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'downpayment.label', 'Down Payment');
        data_set($col3, 'interestrate.label', 'Interest Rate(%)');
        data_set($col3, 'penaltyamt.label', 'Penalty(%)');
        data_set($col3, 'fmonthlyamortization.label', 'Monthly Amortization');

        if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
        }

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['branchname'] = '';
        $data[0]['dbranchname'] = '';
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';
        $data[0]['modeofsales'] = '';
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['terms'] = '';
        $data[0]['dclientname'] = '';
        $data[0]['dacnoname'] = '';
        $data[0]['dvattype'] = '';
        $data[0]['rem'] = '';
        $data[0]['downpayment'] = 0.00;
        $data[0]['interestrate'] = 0;
        $data[0]['rrfactor'] = 0;
        $data[0]['penaltyamt'] = 0;
        $data[0]['rebate'] = 0.00;
        $data[0]['fmiscfee'] = 0.00;
        $data[0]['fmonthlyamortization'] = 0;
        return $data;
    }
}//end class