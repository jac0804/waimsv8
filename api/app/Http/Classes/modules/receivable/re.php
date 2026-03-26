<?php

namespace App\Http\Classes\modules\receivable;

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

class re
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'REPLACEMENT CHEQUE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
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
    private $stockselect;
    public $damt = 'amount';
    public $hamt = 'amount';
    public $defaultContra = 'CR1';

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
    private $otherfields = [];
    private $except = ['trno', 'dateid'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

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
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5331,
            'edit' => 5332,
            'new' => 5333,
            'save' => 5334,
            'delete' => 5335,
            'print' => 5336,
            'lock' => 5337,
            'unlock' => 5338,
            'post' => 5339,
            'unpost' => 5340,
            'additem' => 5341,
            'edititem' => 5344,
            'deleteitem' => 5342
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

    public function paramsdatalisting($config)
    {
        $fields = [];
        $col1 = [];

        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
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
        $columns = ['action', 'client', 'acnoname', 'amount', 'checkno', 'ref', 'rcchecks', 'rem'];
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
        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$client]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
        $obj[0][$this->gridname]['columns'][$acnoname]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$rcchecks]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;max-width:350px;';


        //text-align:left
        $obj[0][$this->gridname]['columns'][$ref]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$client]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer';
        $obj[0][$this->gridname]['columns'][$acnoname]['label'] = 'Account Name';
        $obj[0][$this->gridname]['columns'][$checkno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amount]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$acnoname]['readonly'] = true;

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0]['inventory']['totalfield'] = 'amount';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['getbouncedar', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = "SAVE ALL";
        $obj[2]['label'] = "DELETE ALL";
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'dagentname']; //'dacnoname', 
        $col1 = $this->fieldClass->create($fields);
        // data_set($col3, 'dacnoname.lookupclass', 'CB');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = ['dateid', 'yourref', 'ourref'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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

        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
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
                // if ($return) {
                //     var_dump($config['params']);
                // }
                return $return;
            }
        }
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttranstock($config);
    } //end function


    private function getstockselect($config)
    {
        $sqlselect = "select stock.trno,stock.line,stock.acnoid,stock.checkno,stock.amount,stock.refx,stock.linex,
                    stock.rctrno,stock.rcline,coa.acnoname,beh.docno as ref,stock.rcchecks,
                    stock.rem,be.trno as betrno,be.line as beline,stock.clientid,c.clientname as client,'' as bgcolor,'' as errcolor ";
        return $sqlselect;
    }


    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . " FROM $this->stock as stock
                left join $this->head as head on head.trno = stock.trno
                left join gldetail as be on be.trno=stock.refx and be.line=stock.linex
                left join glhead as beh on beh.trno=be.trno 
                left join coa on coa.acnoid=stock.acnoid
                left join client as c on c.clientid=stock.clientid
                where stock.trno =?
                UNION ALL
                " . $sqlselect . " FROM $this->hstock as stock
                left join $this->hhead as head on head.trno = stock.trno
                left join gldetail as be on be.trno=stock.refx and be.line=stock.linex
                left join glhead as beh on beh.trno=be.trno 
                left join coa on coa.acnoid=stock.acnoid
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
                left join gldetail as be on be.trno=stock.refx and be.line=stock.linex
                left join glhead as beh on beh.trno=be.trno 
                left join coa on coa.acnoid=stock.acnoid
                left join client as c on c.clientid=stock.clientid
                where stock.trno =? and stock.line = ?";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
                // case 'adddetail':
                //     return $this->additem('insert', $config);
                //     break;
                // case 'addallitem':
                //     return $this->addallitem($config);
                //     break;
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
            case 'getbouncedardetail':
                return $this->getbouncedardetail($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function getbouncedardetail($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $total = 0;
        $acno = $this->coreFunctions->getfieldvalue($this->head, "contra", "trno=?", [$trno]);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', "acnoid", "acno=?", [$acno]);
        $acnoname = $this->coreFunctions->getfieldvalue('coa', "acnoname", "acnoid=?", [$acnoid]);
        $data = $config['params']['rows'];
        foreach ($data as $key => $value) {
            $config['params']['data']['acnoid'] = $acnoid;
            $config['params']['data']['acno'] = $acno;
            $config['params']['data']['acnoname'] = $acnoname;
            $config['params']['data']['checkno'] = $data[$key]['checkno'];
            $config['params']['data']['amount'] = $data[$key]['amount'];
            // $config['params']['data']['checkdate'] = $data[$key]['checkdate'];
            // $config['params']['data']['postdate'] = $data[$key]['dateid'];

            $config['params']['data']['refx'] = $data[$key]['trno'];
            $config['params']['data']['linex'] = $data[$key]['line'];
            $config['params']['data']['clientid'] = $data[$key]['clientid'];

            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['data'][0]);
            }
        } //end foreach

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts successfully.'];
    } //end function

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
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
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
            $data = $this->additem('insert', $config);
            $gridheaddata = $this->gridheaddata($config);
            if ($data['status'] == true) {
                return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata, 'reloadhead' => true];
            } else {
                return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
            }
        }
    }


    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            if ($value['line'] != 0) {
                $this->additem('update', $config);
            } else {
                $this->additem('insert', $config);
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';

        $gridheaddata = $this->gridheaddata($config);
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.', 'gridheaddata' => $gridheaddata];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
        }
    } //end function

    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $checkno = $config['params']['data']['checkno'];
        $amount = $config['params']['data']['amount'];
        $refx = 0;
        $linex = 0;
        $rctrno = 0;
        $rcline = 0;
        $line = 0;
        $rem = '';
        $rcchecks = '';
        $clientid = 0;

        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }

        if (isset($config['params']['data']['rctrno'])) {
            $rctrno = $config['params']['data']['rctrno'];
        }
        if (isset($config['params']['data']['rcline'])) {
            $rcline = $config['params']['data']['rcline'];
        }

        if (isset($config['params']['data']['acnoid'])) {
            $acnoid = $config['params']['data']['acnoid'];
        }

        if (isset($config['params']['data']['line'])) {
            $line = $config['params']['data']['line'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }

        if (isset($config['params']['data']['rcchecks'])) {
            $rcchecks = $config['params']['data']['rcchecks'];
        }

        if (isset($config['params']['data']['clientid'])) {
            $clientid = $config['params']['data']['clientid'];
        }


        $data = [
            'trno' => $trno,
            'checkno' => $checkno,
            'amount' => $amount,
            'refx' => $refx,
            'linex' => $linex,
            'rctrno' => $rctrno,
            'rcline' => $rcline,
            'acnoid' => $acnoid,
            'rem' => $rem,
            'rcchecks' => $rcchecks,
            'clientid' => $clientid
        ];


        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];


        $acnoidAR = $this->coreFunctions->getfieldvalue('arledger', 'acnoid', 'trno=?', [$data['refx']]);
        $acno = $this->coreFunctions->getfieldvalue('coa', 'acno', 'acnoid=?', [$acnoidAR]);

        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
                $config['params']['line'] = $line;

                if ($refx != 0) {
                    $this->coreFunctions->execqry("update hparticulars set retrno = " . $trno . " where trno =? and line =? ", "update", [$refx, $linex]);
                }

                $data =  $this->openstockline($config);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Check #: ' . $checkno);
                return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
            }
        } else if ($action == 'update') {

            $return = true;

            $prevrctrno = $this->coreFunctions->getfieldvalue($this->stock, 'rctrno', 'trno=? and line =?', [$trno, $line]);
            $prevrcline = $this->coreFunctions->getfieldvalue($this->stock, 'rcline', 'trno=? and line =?', [$trno, $line]);

            if ($this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'refx' => $data['refx'], 'linex' => $data['linex']]) == 1) {
                $this->coreFunctions->execqry("update hrcdetail set retrno = 0 where trno =? and line =? ", "update", [$prevrctrno, $prevrcline]);
                $this->coreFunctions->execqry("update hrcdetail set retrno = " . $trno . " where trno =? and line =? ", "update", [$data['rctrno'], $data['rcline']]);
            } else {
                $return = false;
            }

            return ['status' => $return, 'msg' => ''];
        }
        // return $return;
    }


    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];

        $data = $this->coreFunctions->opentable('select coa.acnoid,t.refx,t.linex,t.line,t.rctrno,rcline 
        from ' . $this->stock . ' as t left join coa on coa.acnoid=t.acnoid 
        where t.trno=? ', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

        foreach ($data as $key => $value) {

            if ($data[$key]->refx <> 0) {
                $this->coreFunctions->sbcupdate('hparticulars', ['retrno' => 0], ['trno' => $data[$key]->refx]);
            }
            if ($data[$key]->rctrno != 0) {
                $this->coreFunctions->sbcupdate('hrcdetail', ['retrno' => 0], ['trno' => $data[$key]->rctrno, 'line' => $data[$key]->rcline]);
            }
        }
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
    }

    public function deleteitem($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $msg = 'Item was successfully deleted.';
        $status = true;

        if ($line != 0) {

            $qry = "delete from " . $this->stock . " where trno=? and line=?";
            $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

            if ($data[0]->refx != 0) {
                $this->coreFunctions->sbcupdate('hparticulars', ['retrno' => 0], ['trno' => $data[0]->refx, 'line' => $data[0]->linex]);

                $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $acno, $config, 1);
            }
            if ($data[0]->rctrno != 0) {
                $this->coreFunctions->sbcupdate('hrcdetail', ['retrno' => 0], ['trno' => $data[0]->rctrno, 'line' => $data[0]->rcline]);
            }
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

        $qry = 'select p.refx,head.dateid,head.tax,head.contra,head.cur,head.forex,p.checkno,p.rcchecks,
                    p.amount as ext,
                    "" as asset, "" as revenue,"" as expense,
                    head.taxdef,head.deldate,head.ewt,head.ewtrate,p.clientid,client.client
          from ' . $this->head . ' as head 
          left join particulars as p on p.trno=head.trno
          left join client on client.clientid=p.clientid
          where head.trno=?
          group by head.dateid,head.tax,head.contra,head.cur,head.forex,
                    head.taxdef,head.deldate,head.ewt,head.ewtrate,p.checkno,p.amount,p.rcchecks,p.clientid,client.client,p.refx';

        $stock = $this->coreFunctions->opentable($qry, [$trno]);

        $acno = $this->coreFunctions->getfieldvalue($this->head, 'contra', 'trno=?', [$trno]);
        if (!empty($stock)) {
            foreach ($stock as $key => $value) {
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
                $entry = ['acnoid' => $acnoid, 'client' => $stock[$key]->client, 'checkno' => $stock[$key]->rcchecks, 'db' => $stock[$key]->ext, 'cr' => 0, 'postdate' => $stock[$key]->dateid, 'cur' =>  $stock[$key]->cur, 'forex' =>  $stock[$key]->forex, 'fcr' => 0, 'fdb' => 0];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["ARB"]);
                $entry = ['acnoid' => $acnoid, 'client' => $stock[$key]->client, 'checkno' => $stock[$key]->checkno, 'db' => 0, 'cr' => $stock[$key]->ext, 'postdate' => $stock[$key]->dateid, 'cur' =>  $stock[$key]->cur, 'forex' =>  $stock[$key]->forex, 'fdb' => 0, 'fcr' => 0];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                $this->coreFunctions->execqry("update arledger set bal=(bal-'" . $stock[$key]->ext . "') where trno=" . $stock[$key]->refx . " ");
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
} //end class
