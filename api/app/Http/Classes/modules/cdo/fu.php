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
        'client',
        'clientname',
        'address',
        'branch',
        'contra',
        'tax',
        'vattype',
        'terms',
        'dateid',
        'rem',
        'modeofsales',
        'supplierid'
    ];
    private $otherfields = ['trno', 'downpayment', 'interestrate', 'fma1', 'fma2', 'penalty', 'rebate', 'fmiscfee'];
    private $except = ['trno', 'dateid'];
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
            'financing' => 5922,
            'additem' => 5936,
            'edititem' => 5937,
            'deleteitem' => 5938
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
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

        $action = 0;
        $itemdesc = 1;
        $isqty = 2;
        $uom = 3;
        $serial = 4;
        $color = 5;
        $pnpcsr = 6;
        $isamt = 7;
        $disc = 8;
        $ext = 9;
        $cost = 10;

        $headgridbtns = ['viewdistribution'];

        $column = ['action', 'itemdescription', 'isqty', 'uom', 'serialno', 'color', 'pnp', 'isamt', 'disc', 'ext', 'cost'];
        $sortcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'serialno', 'color', 'pnp', 'isamt', 'disc', 'ext', 'cost'];

        $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'sortcolumns' => $sortcolumn,
                'computefield' => $computefield,
                'headgridbtns' => $headgridbtns
            ]
        ];

        $stockbuttons = ['save', 'delete', 'showbalance'];
        array_push($stockbuttons, 'stockinfo');

        if ($this->companysetup->getiseditsortline($config['params'])) {
            array_push($stockbuttons, 'sortline');
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        if ($viewcost == '0') {
            $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$isqty]['readonly'] = true;

        $obj[0]['inventory']['columns'][$color]['type'] = 'label';
        $obj[0]['inventory']['columns'][$color]['style'] = 'text-align: left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';

        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
        $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
        $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:250px;max-width:2350px;';

        $obj[0]['inventory']['columns'][$serial]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$serial]['lookupclass'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['action'] = 'lookupserialout';
        $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine/Chassis#';
        $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:250px;max-width:2350px;';

        if (!$access['changeamt']) {
            $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
            $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
        }

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function createHeadField($config)
    {
        $inv = $this->companysetup->isinvonly($config['params']);
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);

        $fields = ['docno', 'dbranchname', 'client', 'clientname', 'address', 'modeofsales'];
        // 
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Customer');
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'clientname.label', 'Name');
        data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
        data_set($col1, 'address.class', 'csaddress  sbccsreadonly');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = [['dateid', 'terms'], 'supplier', 'dacnoname', 'dvattype', 'rem'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'terms.lookupclass', 'financeterms');
        data_set($col2, 'supplier.label', 'Supplier');
        data_set($col2, 'supplier.type', 'lookup');
        data_set($col2, 'supplier.action', 'lookupsupplier');
        data_set($col2, 'supplier.class', 'cssupplier sbccsreadonly');
        data_set($col2, 'supplier.lookupclass', 'lookupsupplier');
        data_set($col2, 'dacnoname.label', 'AR Account');
        data_set($col2, 'dacnoname.lookupclass', 'AR');
        data_set($col2, 'dacnoname.type', 'input');
        data_set($col2, 'dacnoname.readonly', false);
        data_set($col2, 'terms.readonly', false);
        data_set($col2, 'rem.readonly', false);

        $fields = ['downpayment', 'interestrate', 'fma2', 'penalty', 'rebate', 'fmiscfee', 'fma1'];

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'downpayment.label', 'Down Payment');
        data_set($col3, 'interestrate.label', 'Interest Rate(%)');
        data_set($col3, 'fma2.label', 'Factor');
        data_set($col3, 'penalty.label', 'Penalty(%)');
        data_set($col3, 'penalty.class', 'cspenalty');
        data_set($col3, 'penalty.readonly', false);
        data_set($col3, 'rebate.class', 'csrebate');
        data_set($col3, 'rebate.readonly', false);
        data_set($col3, 'fma1.label', 'Monthly Amortization');

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
        $data[0]['branchcode'] = '';
        $data[0]['branchname'] = '';
        $data[0]['dbranchname'] = '';
        $data[0]['branch'] = 0;
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';
        $data[0]['modeofsales'] = '';
        $data[0]['line'] = 0;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['terms'] = '';
        $data[0]['supplier'] = '';
        $data[0]['supplierid'] = 0;
        $data[0]['dacnoname'] = '';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        // $data[0]['dacnoname'] = $data[0]['contra'].''. $data[0]['acnoname'];
        $data[0]['dvattype'] = '';
        $data[0]['tax'] = 12;
        $data[0]['vattype'] = 'VATABLE';
        $data[0]['rem'] = '';
        // $data[0]['rem'] = $this->coreFunctions->getfieldvalue('glhead', 'rem', 'rem=?', [$data[0]['rem']]);
        $data[0]['downpayment'] = 0.00;
        $data[0]['interestrate'] = 0;
        $data[0]['fma2'] = 0;
        $data[0]['penalty'] = 0;
        $data[0]['rebate'] = 0.00;
        $data[0]['fmiscfee'] = 0.00;
        $data[0]['fma1'] = 0;
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
        mode.line,
        mode.name as modeofsales,
        left(head.dateid,10) as dateid,
        head.terms,
        head.contra,
        coa.acnoname,
        '' as dacnoname,
        head.rem,
        head.tax,
        head.vattype,
        '' as dvattype,
        head.supplierid,
        ifnull(sup.clientname,'') as supplier,
        format(ifnull(hinfo.downpayment,0),2) as downpayment, 
        ifnull(format(hinfo.interestrate,2),'') as interestrate,
        ifnull(hinfo.fma2,0) as fma2,
        ifnull(hinfo.penalty,0) as penalty,
        format(ifnull(hinfo.rebate,0),2) as rebate,
        format(ifnull(hinfo.fmiscfee,0),2) as fmiscfee,
        format(ifnull(hinfo.fma1,0),2) as fma1
        ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join client as b on b.clientid = head.branch
        left join client as sup on sup.clientid = head.supplierid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra
        left join client as b on b.clientid = head.branch
        left join client as sup on sup.clientid = head.supplierid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join mode_masterfile as mode on mode.line = head.modeofsales and mode.ismc =1
        where head.trno = ? and num.doc=? and num.center=?  ";

        // var_dump($qry);

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);

            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

            return [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'islocked' => $islocked,
                'isposted' => $isposted,
                'isnew' => false,
                'status' => true,
                'msg' => $msg
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
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

        $dateTables = ['lahead', 'cntnum'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    // $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                    $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
                } //end if
            }
        }


        foreach ($this->otherfields as $key) {
            $dataother[$key] = $head[$key];
            if (!in_array($key, $this->except)) {
                // $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
                $dataother[$key] = $this->othersClass->sanitizekeyfieldFast($key, $dataother[$key], $lookups);
            } //end if
        }

        $data['modeofsales'] = $head['line'];
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->othersClass->getcreditinfo($config, $this->head);
            // $this->recomputestock($head, $config);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->othersClass->getcreditinfo($config, $this->head);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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
        $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
        $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
        // $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from delstatus where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];

        $systemtype = $this->companysetup->getsystemtype($config['params']);

        $mode = $this->coreFunctions->getfieldvalue($this->head, "modeofsales", "trno=?", [$trno]);
        $modename = $this->coreFunctions->getfieldvalue("mode_masterfile", "name", "line=?", [$mode]);

        $supplierid = $this->coreFunctions->getfieldvalue($this->head, "supplierid", "trno=?", [$trno]);

        $otherinfo = $this->coreFunctions->opentable(
            "select downpayment, interestrate, fma1, fma2, penalty, rebate, fmiscfee 
         from cntnuminfo where trno=?",
            [$trno]
        );

        $return = $this->othersClass->posttranstock($config);

        if ($return) {
            $this->coreFunctions->sbcupdate($this->hhead, [
                'modeofsales' => $mode,
                'supplierid' => $supplierid
            ], ['trno' => $trno]);

            if (!empty($otherinfo)) {
                $info = (array) $otherinfo[0];
                $exists = $this->coreFunctions->getfieldvalue("hcntnuminfo", "trno", "trno=?", [$trno]);
                if ($exists == '') {
                    $info['trno'] = $trno;
                    $this->coreFunctions->sbcinsert("hcntnuminfo", $info);
                } else {
                    $this->coreFunctions->sbcupdate("hcntnuminfo", $info, ['trno' => $trno]);
                }
            }
        }

        return $return;
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];

        $mode = $this->coreFunctions->getfieldvalue($this->hhead, "modeofsales", "trno=?", [$trno]);
        $supplierid = $this->coreFunctions->getfieldvalue($this->hhead, "supplierid", "trno=?", [$trno]);

        $otherinfo = $this->coreFunctions->opentable(
            "select downpayment, interestrate, fma1, fma2, penalty, rebate, fmiscfee 
         from hcntnuminfo where trno=?",
            [$trno]
        );

        $return = $this->othersClass->unposttranstock($config);

        if ($return) {
            $this->coreFunctions->sbcupdate($this->head, [
                'modeofsales' => $mode,
                'supplierid' => $supplierid
            ], ['trno' => $trno]);

            if (!empty($otherinfo)) {
                $info = (array) $otherinfo[0];
                $exists = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$trno]);
                if ($exists == '') {
                    $info['trno'] = $trno;
                    $this->coreFunctions->sbcinsert("cntnuminfo", $info);
                } else {
                    $this->coreFunctions->sbcupdate("cntnuminfo", $info, ['trno' => $trno]);
                }
            }
        }

        return $return;
    } //end function

    private function getstockselect($config)
    {
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $itemname = 'item.itemname,';
        $serialfield = '';
        $serialfield = ",ifnull(group_concat(concat(rr.serial,'/',rr.chassis) separator '\\n\\r'),'') as serialno ";

        $sqlselect = "select item.brand as brand,
        ifnull(mm.model_name,'') as model,
        item.itemid,
        stock.trno,
        stock.line,
        stock.sortline,
        stock.refx,
        stock.linex,
        item.barcode,
        $itemname
        stock.uom,
        uom.factor*stock.cost as cost,
        stock.kgs,
        stock." . $this->hamt . ",
        stock." . $this->hqty . " as iss,
        FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
        FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as isqty,
        FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as qty,
        FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
        left(stock.encodeddate,10) as encodeddate,
        stock.disc,
        stock.void,
        stock.ref,
        stock.whid,
        warehouse.client as wh,
        warehouse.clientname as whname,
        stock.loc,
        stock.expiry,
        item.brand,
        stock.rem,
        stock.palletid,
        stock.locid,
        ifnull(pallet.name,'') as pallet,
        ifnull(location.loc,'') as location,
        ifnull(uom.factor,1) as uomfactor,
        round(case when (stock.Amt>0 and stock.iss>0 and stock.Cost>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss))/head.forex)*100) else 0 end,2) markup,stock.rebate,
        round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
        '' as bgcolor,
        '' as errcolor,
        prj.name as stock_projectname,
        stock.projectid as projectid,stock.sgdrate,stock.itemstatus,
        case when stock.noprint=0 then 'false' else 'true' end as noprint,
        concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
        " . $serialfield . ",ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr) separator '\\n\\r'),'') as pnp,stock.color";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $leftjoin = '';
        $hleftjoin = '';
        $stockinfogroup = '';

        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join $this->head as head on head.trno = stock.trno
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid
        left join projectmasterfile as prj on prj.line = stock.projectid 
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid 
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        $leftjoin
        where stock.trno =?
        group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
        stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
        stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
        FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
        FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
        FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
        stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
        warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
        pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
        prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color
        UNION ALL
        " . $sqlselect . "
        FROM $this->hstock as stock
        left join $this->hhead as head on head.trno = stock.trno
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join client as warehouse on warehouse.clientid=stock.whid
        left join projectmasterfile as prj on prj.line = stock.projectid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid 
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        $hleftjoin
        where stock.trno =? 
        group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
        stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
        stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
        FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
        FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
        FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
        stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
        warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
        pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
        prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color order by sortline, line";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    } //end function

    public function openstockline($config)
    {
        $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

        $leftjoin = '';
        $stockinfogroup = '';

        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join $this->head as head on head.trno = stock.trno
        left join item on item.itemid=stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as warehouse on warehouse.clientid=stock.whid
        left join projectmasterfile as prj on prj.line = stock.projectid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as i on i.itemid  = item.itemid 
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        $leftjoin
        where stock.trno = ? and stock.line = ? 
        group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
        stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
        stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
        FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
        FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
        FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
        stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
        warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
        pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
        prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                $return =  $this->additem('insert', $config);
                if ($return['status'] == true) {
                    $this->othersClass->getcreditinfo($config, $this->head);
                }
                return $return;
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
            case 'saveitem': //save all item edited
                return $this->updateitem($config);
                break;
            case 'saveperitem':
                return $this->updateperitem($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ') SJ'];
                break;
        }
    }

    public function stockstatusposted($config)
    {
        $action = $config['params']['action'];
        if ($action == 'stockstatusposted') {
            $action = $config['params']['lookupclass'];
        }

        switch ($action) {
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'makepayment':
                return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $this->othersClass->getcreditinfo($config, $this->head);
        $data = $this->openstockline($config);
        $msg = '';
        if ($isupdate['msg'] != '') {
            $msg = $isupdate['msg'];
        }
        if (!$isupdate['status']) {
            $data[0]->errcolor = 'bg-red-2';

            return ['row' => $data, 'status' => true, 'msg' => $msg];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }

    public function updateitem($config)
    {
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $update = $this->additem('update', $config);
            if ($msg != '') {
                $msg = $msg . ' ' . $update['msg'];
            } else {
                $msg = $update['msg'];
            }
        }
        $this->othersClass->getcreditinfo($config, $this->head);
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
            }
        }

        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    } //end function

    public function addallitem($config)
    {
        $msg = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $row = $this->additem('insert', $config);
            if ($msg != '') {
                $msg = $msg . ' ' . $row['msg'];
            } else {
                $msg = $row['msg'];
            }

            if (isset($config['params']['data']['refx'])) {
                if ($config['params']['data']['refx'] != 0) {
                    if ($this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']) == 0) {
                        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
                        $this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']);
                        if ($msg != '') {
                            $msg = $msg . '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
                        } else {
                            $msg = '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
                        }
                    }
                }
            }
        }

        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $status = true;

        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $status = false;
            }
        }

        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    } //end function

    // insert and update item
    public function additem($action, $config, $setlog = false)
    {
        $companyid = $config['params']['companyid'];
        $ispallet = $this->companysetup->getispallet($config['params']);
        $uom = $config['params']['data']['uom'];

        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : $this->companysetup->getwh($config['params']);
        $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
        $expiry = '';
        if (isset($config['params']['data']['expiry'])) {
            $expiry = $config['params']['data']['expiry'];
        }

        if ($this->companysetup->getiskgs($config['params'])) {
            $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
        } else {
            $kgs = 0;
        }

        $dateTables = ['lastock', 'stockinfo'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        $rebate = 0;
        $refx = 0;
        $linex = 0;
        $ref = '';
        $projectid = 0;
        $sgdrate = 0;
        $noprint = 'false';
        $rem = '';
        $poref = '';
        $podate = null;

        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }
        if (isset($config['params']['data']['ref'])) {
            $ref = $config['params']['data']['ref'];
        }

        if (isset($config['params']['data']['rebate'])) {
            $rebate = $config['params']['data']['rebate'];
        }

        if (isset($config['params']['data']['projectid'])) {
            $projectid = $config['params']['data']['projectid'];
        }

        if (isset($config['params']['data']['noprint'])) {
            $noprint = $config['params']['data']['noprint'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }

        if (isset($config['params']['data']['poref'])) {
            $poref = $config['params']['data']['poref'];
        }

        if (isset($config['params']['data']['podate'])) {
            $podate = $config['params']['data']['podate'];
        }

        $itemstatus = '';
        $line = 0;

        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;

            $config['params']['line'] = $line;

            $amt = $config['params']['data']['amt'];

            $qty = $config['params']['data']['qty'];
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $amt = $config['params']['data'][$this->damt];
            $qty = $config['params']['data'][$this->dqty];
            $config['params']['line'] = $line;
        }
        $amt = $this->othersClass->sanitizekeyfieldFast('amt', $amt, $lookups);
        $qty = $this->othersClass->sanitizekeyfieldFast('qty', $qty, $lookups);
        $kgs = $this->othersClass->sanitizekeyfieldFast('qty', $kgs, $lookups);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,item.isserial from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        $isnoninv = 0;
        $isserial = 0;
        if (!empty($item)) {
            $isnoninv = $item[0]->isnoninv;
            $isserial = $item[0]->isserial;
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }
        $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
        $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
        $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));


        if ($isserial == 1 && $action == 'insert') {
            $qty = 0;
        }

        if ($this->companysetup->getisdiscperqty($config['params'])) {
            $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs, 0, 1);
        } else {
            $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
        }

        if (floatval($curtopeso) == 0) {
            $curtopeso = 1;
        }

        $hamt = $computedata['amt'] * $curtopeso;
        $hamt = $this->othersClass->sanitizekeyfieldFast('amt', $hamt, $lookups);

        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            $this->damt => $amt,
            $this->hamt => $hamt,
            $this->dqty => $qty,
            $this->hqty => $computedata['qty'],
            'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
            'kgs' => $kgs,
            'disc' => $disc,
            'whid' => $whid,
            'refx' => $refx,
            'linex' => $linex,
            'rem' => $rem,
            'ref' => $ref,
            'loc' => $loc,
            'expiry' => $expiry,
            'uom' => $uom,
            'rebate' => $rebate,
            'noprint' => $noprint
        ];

        foreach ($data as $key => $value) {
            // $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($uom == '') {
            $msg = 'UOM cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
        }

        //insert item
        if ($action == 'insert') {

            $sjitemlimit = $this->companysetup->getsjitemlimit($config['params']);
            if ($sjitemlimit != 0) {

                $qry = "select ifnull(count(stock.trno),0) as itmcnt from lahead as head
                left join lastock as stock on stock.trno=head.trno
                where head.doc='fu' and head.trno=?";
                $count = $this->coreFunctions->opentable($qry, [$config['params']['doc'], $trno]);

                if ($count[0]->itmcnt >= $sjitemlimit) {
                    return ['status' => false, 'msg' => 'Item Records Limit Reached(' . $sjitemlimit . 'max)'];
                }
            }


            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            if (isset($config['params']['data']['sortline'])) {
                $data['sortline'] =  $config['params']['data']['sortline'];
            } else {
                $data['sortline'] =  $data['line'];
            }

            $trno = $this->othersClass->val($trno);
            if ($trno == 0) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (SJ)');
                return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
            }

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $havestock = true;
                $msg = 'Item was successfully added.';

                if ($isserial == 1) {
                    $msg = 'Item was successfully added. Please enter Engine #';
                }

                $stockinfo_data = [
                    'trno' => $trno,
                    'line' => $line,
                    'rem' => $rem
                ];
                $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
                if ($isnoninv == 0) {
                    if ($ispallet) {
                        $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
                    } else {
                        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
                    }

                    if ($cost != -1) {
                        $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

                        //CHECK BELOW COST
                        if ($this->companysetup->checkbelowcost($config['params'])) {
                            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                            if ($belowcost == 1) {
                                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
                            } elseif ($belowcost == 2) {
                                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
                            }
                        }
                    } else {
                        $havestock = false;
                        $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                    }
                }
                if ($this->setserveditems($refx, $linex) == 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($refx, $linex);
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                    $return = false;
                    $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
                }

                $this->othersClass->getcreditinfo($config, $this->head);
                $row = $this->openstockline($config);
                if (!$havestock) {
                    $row[0]->errcolor = 'bg-red-2';
                    $msg = '(' . $item[0]->barcode . ') Out of Stock.';
                }
                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $msg = '';
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            if ($isnoninv == 0) {

                if ($isserial == 0) {
                    $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
                } else {
                    $rrref = $this->coreFunctions->datareader("select group_concat(rr.sline separator ',') as value from serialout as sj left join serialin as rr on rr.outline = sj.sline where sj.trno = " . $trno . " and sj.line =" . $line);
                    if ($rrref <> '') {
                        $cost = $this->othersClass->computecostingserial($data['itemid'], $data['whid'], $trno, $line, $data['iss'], $config['params']['doc'], '', $rrref, $loc);
                    } else {
                        $cost = -1;
                        $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'ENTER SERIAL', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'NO SERIAL - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                        $msg = "(" . $item[0]->barcode . ") Please select Engine#!!!";
                        $return = false;
                    }
                }

                if ($cost != -1) {
                    $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

                    //CHECK BELOW COST
                    if ($this->companysetup->checkbelowcost($config['params'])) {
                        $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                        if ($belowcost == 1) {
                            $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
                        } elseif ($belowcost == 2) {
                            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                            $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
                            $return = false;
                        }
                    }
                } else {
                    $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                    $this->setserveditems($refx, $linex);
                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                    $return = false;
                    $msg = "(" . $item[0]->barcode . ") Out of Stock.";
                }
            }
            if ($this->setserveditems($refx, $linex) == 0) {
                $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                $this->setserveditems($refx, $linex);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $return = false;
                $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
            }


            return ['status' => $return, 'msg' => $msg];
        }
    } // end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        if ($this->companysetup->getserial($config['params'])) {
            $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
            foreach ($data2 as $key => $value) {
                $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
            }
        }

        $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
            $this->setserveditems($data[$key]->refx, $data[$key]->linex);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function setserveditems($refx, $linex)
    {
        if ($refx == 0) {
            return 1;
        }
        $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
        stock on stock.trno=head.trno where head.doc in ('SJ','BO') and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
        glhead.trno where glhead.doc in ('SJ','BO') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty == '') {
            $qty = 0;
        }
        $result = $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

        $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and iss>qa", [$refx]);
        if ($status) {
            $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and qa<>0", [$refx]);
            if ($status) {
                $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
            } else {
                $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
            }
        } else {
            $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
        }

        return $result;
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];

        $data = $this->openstockline($config);

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        if ($this->companysetup->getserial($config['params'])) {
            $this->othersClass->deleteserialout($trno, $line);
        }

        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
        $this->logger->sbcwritelog(
            $trno,
            $config,
            'STOCKINFO',
            'DELETE - Line:' . $line
                . ' Notes:' . $config['params']['row']['rem']
        );

        $this->setserveditems($data[0]->refx, $data[0]->linex);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function

    // public function getlatestprice($config)
    // {
    //     $barcode = $config['params']['barcode'];
    //     $client = $config['params']['client'];
    //     $center = $config['params']['center'];
    //     $trno = $config['params']['trno'];

    //     $pricetype = $this->companysetup->getpricetype($config['params']);
    //     $pricegrp = '';
    //     $data = [];

    //     switch ($pricetype) {
    //         case 'Stockcard':
    //             goto itempricehere;
    //             break;

    //         case 'CustomerGroup':
    //         case 'CustomerGroupLatest':
    //             $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);
    //             if ($pricegrp != '') {
    //                 $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
    //                 $this->coreFunctions->LogConsole($pricefield);
    //                 $qry = "select '" . $pricefield['label'] . "' as docno, left(now(),10) as dateid," . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom from item where barcode=? 
    //         union all
    //         select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
    //         stock.isamt as amt,stock.uom,stock.disc
    //         from lahead as head
    //         left join lastock as stock on stock.trno = head.trno
    //         left join cntnum on cntnum.trno=head.trno
    //         left join item on item.itemid = stock.itemid
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and head.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         UNION ALL
    //         select head.docno,head.dateid,stock.isamt as computeramt,
    //         stock.uom,stock.disc from glhead as head
    //         left join glstock as stock on stock.trno = head.trno
    //         left join item on item.itemid = stock.itemid
    //         left join client on client.clientid = head.clientid
    //         left join cntnum on cntnum.trno=head.trno
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and client.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         order by dateid desc limit 5) as tbl order by dateid desc";

    //                 $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    //                 if (!empty($data)) {
    //                     goto setpricehere;
    //                 }
    //             } else {
    //                 if ($pricetype == 'CustomerGroupLatest') {
    //                     goto getCustomerLatestPriceHere;
    //                 } else {
    //                     goto setpricehere;
    //                 }
    //             }
    //             break;

    //         default:
    //             getCustomerLatestPriceHere:
    //             $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
    //         stock.isamt as amt,stock.uom,stock.disc
    //         from lahead as head
    //         left join lastock as stock on stock.trno = head.trno
    //         left join cntnum on cntnum.trno=head.trno
    //         left join item on item.itemid = stock.itemid
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and head.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         UNION ALL
    //         select head.docno,head.dateid,stock.isamt as computeramt,
    //         stock.uom,stock.disc from glhead as head
    //         left join glstock as stock on stock.trno = head.trno
    //         left join item on item.itemid = stock.itemid
    //         left join client on client.clientid = head.clientid
    //         left join cntnum on cntnum.trno=head.trno
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and client.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         order by dateid desc limit 5) as tbl order by dateid desc";

    //             $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    //             break;
    //     }


    //     if (!empty($data)) {

    //         return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    //     } else {
    //         itempricehere:
    //         $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom from item where barcode=? union all
    //         select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
    //         stock.isamt as amt,stock.uom,stock.disc
    //         from lahead as head
    //         left join lastock as stock on stock.trno = head.trno
    //         left join cntnum on cntnum.trno=head.trno
    //         left join item on item.itemid = stock.itemid
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and head.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         UNION ALL
    //         select head.docno,head.dateid,stock.isamt as computeramt,
    //         stock.uom,stock.disc from glhead as head
    //         left join glstock as stock on stock.trno = head.trno
    //         left join item on item.itemid = stock.itemid
    //         left join client on client.clientid = head.clientid
    //         left join cntnum on cntnum.trno=head.trno
    //         where head.doc = 'SJ' and cntnum.center = ?
    //         and item.barcode = ? and client.client = ?
    //         and stock.isamt <> 0 and cntnum.trno <> ?
    //         order by dateid desc limit 5) as tbl";

    //         $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    //         setpricehere:
    //         $usdprice = 0;
    //         $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    //         $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    //         $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
    //         $defuom = '';

    //         if ($this->companysetup->getisdefaultuominout($config['params'])) {
    //             if (empty($data)) {
    //                 $data[0]->docno = 'UOM';
    //             }
    //             $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
    //             $this->coreFunctions->LogConsole('def' . $defuom . $data[0]->amt);
    //             if ($defuom != "") {
    //                 $data[0]->uom = $defuom;
    //                 if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
    //                     if (floatval($data[0]->amt) != 0) {
    //                         $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
    //                     } else {
    //                         $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
    //                     }
    //                 }
    //             } else {
    //                 if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
    //                     if (floatval($data[0]->amt) != 0) {
    //                         $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
    //                     } else {
    //                         $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
    //                     }
    //                 }
    //             }
    //         } else {
    //             if ($this->companysetup->getisuomamt($config['params'])) {
    //                 $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
    //                 $data[0]->docno = 'UOM';
    //                 $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom." . $pricefield['amt'] . ",0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
    //             }
    //         }

    //         if (floatval($forex) <> 1) {
    //             $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
    //             if ($cur == '$') {
    //                 $data[0]->amt = $usdprice;
    //             } else {
    //                 $data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
    //             }
    //         }

    //         if (isset($data[0]->amt)) {
    //             if (floatval($data[0]->amt) == 0) {
    //                 return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
    //             } else {
    //                 return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    //             }
    //         } else {
    //             return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
    //         }
    //     }
    // } // end function

    public function getlatestprice($config)
    {
        return ['status' => false, 'msg' => 'Not applicable for Financing.', 'data' => []];
    }
}//end class