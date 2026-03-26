<?php

namespace App\Http\Classes\modules\receivable;

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

class be
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BOUNCED CHEQUE ENTRY';
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
    public $stock = 'particulars';
    public $hstock = 'hparticulars';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';
    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';
    public $dqty = '';
    public $hqty = '';
    public $damt = 'amount';
    public $hamt = 'amount';
    public $defaultContra = 'CB1';
    private $stockselect;
    private $fields = [
        'trno',
        'docno',
        'dateid',
        'yourref',
        'ourref',
        'rem',
        'contra',
        'agent'
    ];
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
            'view' => 5317,
            'edit' => 5318,
            'new' => 5319,
            'save' => 5320,
            'delete' => 5321,
            'print' => 5322,
            'lock' => 5323,
            'unlock' => 5324,
            'post' => 5325,
            'unpost' => 5326,
            'additem' => 5327,
            'edititem' => 5328,
            'deleteitem' => 5329
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $userid = $config['params']['adminid'];

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'yourref', 'ourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function loaddoclisting($config)
    {
        ini_set('memory_limit', '-1');

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

        $rem = '';
        $join = '';
        $hjoin = '';
        $addparams = '';

        $userid = $config['params']['adminid'];
        $dept = '';

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
            $searchfield = ['head.docno', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        if ($linkstock) {
            if ($group == '') {
                $group = 'group by head.trno,head.docno,head.dateid,head.createby,head.editby,head.viewby,num.postedby,
                            head.yourref, head.ourref';
            }
        }
        $qry = "select head.dateid as date2,head.trno,head.docno,$dateid, $lstat as status, 
                        $lstatcolor as statuscolor,head.createby,head.editby,head.viewby,num.postedby,
                        head.yourref, head.ourref 
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join trxstatus as stat on stat.line=num.statid
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                        and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
                        and num.bref <> 'SJS' 
                $group
                union all
                select head.dateid as date2,head.trno,head.docno,$dateid,$gstat as status,
                        $gstatcolor as statuscolor,head.createby,head.editby,head.viewby, num.postedby,
                        head.yourref, head.ourref
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join trxstatus as stat on stat.line=num.statid
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                        and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
                        and num.bref <> 'SJS' 
                $group
                $orderby $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        $prefix = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'SJ']);
        if ($prefix != '') {
            $prefixes = explode(",", $prefix);
            $list = array();
            foreach ($prefixes as $key) {
                array_push($list, ['label' => $key, 'value' => $key]);
            }
            data_set($col2, 'selectprefix.options', $list);
        }
        $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
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
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
        $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
        $step6 = $this->helpClass->getFields(['btndelete']);


        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'additem' => ['label' => 'How to add item/s', 'action' => $step3],
            'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
            'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
            'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
        ];

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }


    public function createTab($access, $config)
    {
        $columns = ['action', 'clientname', 'bank', 'branch', 'checkno',  'amount', 'checkdate', 'ref'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        $fields = ['totalamount'];
        $gridheadinput = ['col0' => [], 'col1' => $col1];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns,
                'headgridbtns' => ['viewdistribution', 'viewref'],
                'gridheadinput' => $gridheadinput,
            ],
        ];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
        $obj[0][$this->gridname]['columns'][$bank]['style'] = 'width:360px;whiteSpace: normal;min-width:360px;max-width:360px;';
        $obj[0][$this->gridname]['columns'][$branch]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
        $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$checkdate]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';

        $obj[0][$this->gridname]['columns'][$ref]['type'] = 'input';

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$clientname]['field'] = 'clientname';
        $obj[0][$this->gridname]['columns'][$clientname]['lookupclass'] = 'beclient';
        $obj[0][$this->gridname]['columns'][$clientname]['action'] = 'lookupclient';
        $obj[0][$this->gridname]['columns'][$branch]['readonly'] = 'false';

        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0]['inventory']['totalfield'] = 'amount';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = "SAVE CHECKS";
        $obj[2]['label'] = "DELETE CHECKS";
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'dacnoname', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col3, 'dacnoname.lookupclass', 'CB');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['dateid', 'yourref', 'ourref'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function defaultheaddata($params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = '';
        $data[0]['dateid'] = date('Y-m-d');
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['dacnoname'] = '';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        $data[0]['dagentname'] = '';
        $data[0]['agent'] = '';
        $data[0]['agentname'] = '';
        return $data;
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        $data[0]['dagentname'] = '';
        $data[0]['agent'] = '';
        $data[0]['agentname'] = '';
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
                        where doc=? and center=? and bref <> 'SJS'
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

        $qryselect = "select head.trno,head.docno,head.yourref,head.ourref,head.contra,coa.acnoname,'' as dacnoname,
                          left(head.dateid,10) as dateid,date_format(head.createdate,'%Y-%m-%d') as createdate,
                          head.rem,ifnull(agent.client,'') as agent,ifnull(agent.clientname,'') as agentname,'' as dagentname ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra
         left join client as agent on agent.client = head.agent
        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra
        left join client as agent on agent.clientid = head.agentid
         where head.trno = ? and num.doc=? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            $gridheaddata = $this->gridheaddata($config);

            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }

            $hideheadergridbtns = [];

            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'gridheaddata' => $gridheaddata,
                'islocked' => $islocked,
                'isposted' => $isposted,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'hideobj' => $hideobj,
                'hideheadgridbtns' => $hideheadergridbtns
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed', 'gridheaddata' => []];
        }
    }


    public function gridheaddata($config)
    {
        $trno = $config['params']['trno'];

        $amount = $this->coreFunctions->datareader("
              select sum(totalamount) as value
              from (select sum(p.amount) as totalamount
                    from " . $this->head . " as head
                    left join particulars as p on p.trno=head.trno
                    where head.trno=?
                    union all
                    select sum(p.amount) as totalamount
                    from " . $this->hhead . " as head
                    left join hparticulars as p on p.trno=head.trno
                    where head.trno=?) as k", [$trno, $trno]);


        if ($amount == '') $amount = 0;

        return $this->coreFunctions->opentable("select FORMAT(" . $amount . ",2) as totalamount");
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
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
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
        }
    } // end function

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
        $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];

        if ($this->companysetup->isinvonly($config['params'])) {
            return $this->othersClass->posttranstock($config);
        } else {
            $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);
            if ($checkacct != '') {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
            }

            $stock = $this->openstock($trno, $config);


            $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

            $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
            $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);
            if (floatval($islimit) == 0) {
                if ($override == '0') {
                    $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
                    $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
                    $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
                    $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

                    if ($cstatus <> 'ACTIVE') {
                        $this->logger->sbcwritelog(
                            $trno,
                            $config,
                            'POST',
                            'Customer Status is not Active'
                        );
                        return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
                    }

                    if (floatval($crline) < floatval($totalso)) {
                        $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit.');
                        return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
                    }
                }
            }
            if (!$this->createdistribution($config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
            } else {
                $return = $this->othersClass->posttranstock($config);
                return $return;
            }
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $qry = "select trno from " . $this->hdetail . " where trno=? and retrno <> 0";
        $data = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($data)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed; check(s) was already served.'];
        }
        return $this->othersClass->unposttranstock($config);
    } //end function

    private function getstockselect($config)
    {
        $sqlselect = "select stock.trno,stock.line,stock.bank,stock.branch,date(stock.checkdate) as checkdate,
                            stock.checkno,stock.clientid,stock.amount,c.clientname,'' as bgcolor,'' as errcolor ";

        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $select_u = ",'' as ref";
        $select_p = ",(select docno from lahead as re where re.trno=stock.retrno
               union all select docno from glhead as re where re.trno=stock.retrno) as ref";

        $qry = $sqlselect . " $select_u FROM $this->stock as stock
                left join $this->head as head on head.trno = stock.trno
                left join client as c on c.clientid=stock.clientid
                where stock.trno =?
                UNION ALL
                " . $sqlselect . " $select_p FROM $this->hstock as stock
                left join $this->hhead as head on head.trno = stock.trno
                left join client as c on c.clientid=stock.clientid
                where stock.trno =?";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    } //end function

    public function openstockline($config)
    {
        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = $sqlselect . "
                FROM $this->stock as stock
                left join $this->head as head on head.trno = stock.trno
                left join client as c on c.clientid=stock.clientid
                where stock.trno =? and stock.line = ?";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'addrow':
                return $this->addrow($config);
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

    public function diagram($config)
    {
        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1650;
        $startx = 100;
        $a = 0;

        $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so
     left join hsostock as s on s.trno = so.trno
     left join glstock as sstock on sstock.refx = s.trno and sstock.linex = s.line
     where sstock.trno = ?
     group by so.trno,so.docno,so.dateid";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($t)) {
            $startx = 550;
            foreach ($t as $key => $value) {
                //SO
                data_set(
                    $nodes,
                    $t[$key]->docno,
                    [
                        'align' => 'right',
                        'x' => 200,
                        'y' => 50 + $a,
                        'w' => 250,
                        'h' => 80,
                        'type' => $t[$key]->docno,
                        'label' => $t[$key]->rem,
                        'color' => 'blue',
                        'details' => [$t[$key]->dateid]
                    ]
                );
                array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
                $a = $a + 100;
            }
        }

        //SJ
        $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), if(head.ms_freight<>0,concat('\rOther Charges: ',round(head.ms_freight,2)),''),'\r\r', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where head.trno=?
    group by head.docno, head.dateid, head.trno, ar.bal, head.ms_freight";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($t)) {
            data_set(
                $nodes,
                'sj',
                [
                    'align' => 'left',
                    'x' => $startx,
                    'y' => 100,
                    'w' => 400,
                    'h' => 80,
                    'type' => $t[0]->docno,
                    'label' => $t[0]->rem,
                    'color' => 'green',
                    'details' => [$t[0]->dateid]
                ]
            );

            foreach ($t as $key => $value) {
                //CR
                $sjtrno = $t[$key]->trno;
                $crqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
                $crdata = $this->coreFunctions->opentable($crqry, [$sjtrno, $sjtrno]);
                if (!empty($crdata)) {
                    foreach ($crdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            'cr',
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
                        array_push($links, ['from' => 'sj', 'to' => 'cr']);
                        $a = $a + 100;
                    }
                }

                //CM
                $cmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
                $cmdata = $this->coreFunctions->opentable($cmqry, [$sjtrno, $sjtrno]);
                if (!empty($cmdata)) {
                    foreach ($cmdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            $cmdata[$key2]->docno,
                            [
                                'align' => 'left',
                                'x' => $startx + 400,
                                'y' => 200,
                                'w' => 250,
                                'h' => 80,
                                'type' => $cmdata[$key2]->docno,
                                'label' => $cmdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$cmdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'sj', 'to' => $cmdata[$key2]->docno]);
                        $a = $a + 100;
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

    public function addrow($config)
    {
        $data = [];

        $data['line'] = 0;
        $data['trno'] = $config['params']['trno'];
        $data['clientname'] = '';
        $data['clientid'] = 0;
        $data['checkno'] = '';
        $data['amount'] = 0;
        $data['checkdate'] = null;
        $data['bank'] = '';
        $data['branch'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
    }

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        if ($config['params']['line'] != 0) {
            $this->additem('update', $config);
            $data = $this->openstockline($config);
            $gridheaddata = $this->gridheaddata($config);
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
        } else {

            $exist = $this->coreFunctions->datareader("select checkno as value from 
                        (select s.checkno from particulars as s
                        where s.checkno = '" . $config['params']['data']['checkno'] . "' and s.bank = '" . $config['params']['data']['bank'] . "'
                            and s.branch = '" . $config['params']['data']['branch'] . "'
                        union all 
                        select s.checkno from hparticulars as s
                        where s.checkno = '" . $config['params']['data']['checkno'] . "' and s.bank = '" . $config['params']['data']['bank'] . "'
                            and s.branch = '" . $config['params']['data']['branch'] . "') as a limit 1");

            if ($exist != '') {
                $stats['status'] = false;
                $stats['msg'] = 'Duplicate Bank, Branch and Check #.';
            } else {
                $stats = $this->additem('insert', $config);
            }
            $data = $this->openstockline($config);
            $gridheaddata = $this->gridheaddata($config);
            if ($stats['status'] == true) {
                return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata, 'reloadhead' => true];
            } else {
                return ['row' => $data, 'status' => false, 'msg' => $stats['msg']];
            }
        }
    }


    public function updateitem($config)
    {
        $msg1 = '';
        $msg2 = '';
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            if ($value['line'] != 0) {
                $this->additem('update', $config);
            } else {
                $exist = $this->coreFunctions->datareader("select checkno as value 
                from (select s.checkno from particulars as s 
                      where s.checkno = '" . $config['params']['data']['checkno'] . "' 
                            and s.bank = '" . $config['params']['data']['bank'] . "'
                            and s.branch = '" . $config['params']['data']['branch'] . "'
                      union all 
                      select s.checkno from hparticulars as s  
                      where s.checkno = '" . $config['params']['data']['checkno'] . "' 
                            and s.bank = '" . $config['params']['data']['bank'] . "'
                            and s.branch = '" . $config['params']['data']['branch'] . "') as a limit 1");
                // var_dump($exist);
                if ($exist != '') {
                    $msg1 = 'Duplicate Bank, Branch and Check #.';
                } else {
                    $msg1 = 'Successfully saved.';
                    $this->additem('insert', $config);
                }
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;


        foreach ($data2 as $key => $value) {
            if ($data2[$key]['checkno'] == "") {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = 'Check # required. ';
            }
        }

        $gridheaddata = $this->gridheaddata($config);

        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => $msg1, 'gridheaddata' => $gridheaddata];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check the following errors : ' . $msg1 . $msg2];
        }
    } //end function



    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $line = $config['params']['data']['line'];
        $checkno = $config['params']['data']['checkno'];
        $amount = $config['params']['data']['amount'];
        $checkdate = $config['params']['data']['checkdate'];
        $bank = $config['params']['data']['bank'];
        $branch = $config['params']['data']['branch'];
        $clientid = $config['params']['data']['clientid'];

        $data = [
            'trno' => $trno,
            'line' => $line,
            'checkno' => $checkno,
            'amount' => $amount,
            'checkdate' => $checkdate,
            'bank' => $bank,
            'clientid' => $clientid
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $data['branch'] = $branch;

        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if (
                $line == ''
            ) {
                $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
                $config['params']['line'] = $line;
                $data =  $this->openstockline($config);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Check #: ' . $checkno);
                return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
            }
        } else if ($action == 'update') {
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
            $return = true;
        }
        return $return;
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $status = true;
        $msg = 'Successfully deleted.';

        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL CHECKS');

        ExitHere:
        $gridheaddata = $this->gridheaddata($config);
        return ['status' => $status, 'msg' => $msg, 'inventory' => [], 'gridheaddata' => $gridheaddata];
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];

        $data = $this->openstockline($config);

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $msg = 'Item was successfully deleted.';
        $status = true;

        if ($line != 0) {
            $qry = "delete from " . $this->stock . " where trno=? and line=?";
            $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Check #: ' . $data[0]->checkno);
        }

        ExitHere:
        $gridheaddata = $this->gridheaddata($config);
        return ['status' => $status, 'msg' => $msg, 'gridheaddata' => $gridheaddata];
    } // end function 

    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];
        $status = true;
        $totalar = 0;
        $ewt = 0;
        $ewtamt = 0;
        $isvatexsales = $this->companysetup->getvatexsales($config['params']);

        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

        $qry = "select head.dateid,head.tax,head.contra,head.cur,head.forex,
                    concat(p.bank,' - ',p.branch,' , ',p.checkno) as checkno,
                    p.amount as ext,'' as asset, '' as revenue,'' as expense,
                    head.taxdef,head.deldate,head.ewt,head.ewtrate,p.clientid,client.client
          from lahead as head 
          left join particulars as p on p.trno=head.trno
          left join client on client.clientid=p.clientid
          where head.trno=?
          group by head.dateid,head.tax,head.contra,head.cur,head.forex,
                    head.taxdef,head.deldate,head.ewt,head.ewtrate,p.amount,p.bank,p.branch,p.checkno,p.clientid,client.client";
        $stock = $this->coreFunctions->opentable($qry, [$trno]);

        $acno = $this->coreFunctions->getfieldvalue($this->head, 'contra', 'trno=?', [$trno]);
        if (!empty($stock)) {
            foreach ($stock as $key => $value) {
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
                $entry = ['acnoid' => $acnoid, 'client' => $stock[$key]->client, 'checkno' => $stock[$key]->checkno, 'db' => 0, 'cr' => $stock[$key]->ext, 'postdate' => $stock[$key]->dateid, 'cur' =>  $stock[$key]->cur, 'forex' =>  $stock[$key]->forex, 'fcr' => 0, 'fdb' => 0];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["ARB"]);
                $entry = ['acnoid' => $acnoid, 'client' => $stock[$key]->client, 'checkno' => $stock[$key]->checkno, 'db' => $stock[$key]->ext, 'cr' => 0, 'postdate' => $stock[$key]->dateid, 'cur' =>  $stock[$key]->cur, 'forex' =>  $stock[$key]->forex, 'fdb' => 0, 'fcr' => 0];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
                $this->acctg[$key]['trno'] = $config['params']['trno'];
                $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
                $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
                $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
                $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
            }
            if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                $status = true;
            } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                $status = false;
            }
        }


        return $status;
    } //end function

    public function getpaysummaryqry($config)
    {
        return "
    select arledger.docno,arledger.trno,arledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,arledger.db,arledger.cr, arledger.bal ,left(arledger.dateid,10) as dateid,
    abs(arledger.fdb-arledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,glhead.vattype,glhead.ewt,glhead.ewtrate,a.client as agent from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    left join client as a on a.clientid = glhead.agentid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and arledger.bal<>0";
    }


    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];

        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
    }

    public function reportdata($config)
    {
        $dataparams = $config['params']['dataparams'];
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
    }
} //end class