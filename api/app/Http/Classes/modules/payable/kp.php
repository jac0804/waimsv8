<?php

namespace App\Http\Classes\modules\payable;

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

class kp
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'COUNTER RECEIPT';
    public $gridname = 'accounting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'kphead';
    public $hhead = 'hkphead';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $defaultContra = 'AP1';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'client',
        'clientname',
        'yourref',
        'ourref',
        'rem',
        'address'
    ];
    private $except = ['trno', 'dateid'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;


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
            'view' => 5241,
            'edit' => 5242,
            'new' => 5243,
            'save' => 5244,
            // 'change' => 213, remove change doc
            'delete' => 5245,
            'print' => 5246,
            'lock' => 5247,
            'unlock' => 5248,
            'post' => 5249,
            'unpost' => 5250,
            'additem' => 5251,
            'edititem' => 5252,
            'deleteitem' => 5253
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
        $yourref = 5;
        $ourref = 6;
        $postdate = 7;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
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


        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $dateid = "left(head.dateid,10) as dateid";
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

        $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref             
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       head.yourref, head.ourref             
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnunpaidkr', 'db', 'cr', 'rem']);
        $step4 = $this->helpClass->getFields(['db', 'cr', 'rem']);
        $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
        $step6 = $this->helpClass->getFields(['btndelete']);


        $buttons['help']['items'] = [
            'create' => ['label' => 'How to create New Document', 'action' => $step1],
            'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
            'additem' => ['label' => 'How to add account/s', 'action' => $step3],
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
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'kr', 'title' => 'KR_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        if ($this->companysetup->getistodo($config['params'])) {
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
            $objtodo = $this->tabClass->createtab($tab, []);
            $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
        }
        return $return;
    }


    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);

        $action = 0;
        $db = 1;
        $cr = 2;
        $postdate = 3;
        $rem = 4;
        $client = 5;
        $ref = 6;

        $columns =  [
            'action',
            'db',
            'cr',
            'postdate',
            'rem',
            'client',
            'ref'
        ];

        $tab = [
            $this->gridname => ['gridcolumns' => $columns]
        ];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['accounting']['columns'][$db]['readonly'] = true;
        $obj[0]['accounting']['columns'][$cr]['readonly'] = true;
        $obj[0]['accounting']['columns'][$postdate]['readonly'] = true;
        $obj[0]['accounting']['columns'][$rem]['readonly'] = true;
        $obj[0]['accounting']['columns'][$client]['label'] = 'Customer';
        $obj[0]['accounting']['columns'][$client]['type'] = 'input';
        $obj[0]['accounting']['columns'][$ref]['type'] = 'input';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['unpaidkp', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[1]['label'] = "DELETE ACCOUNT";
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $systype = $this->companysetup->getsystemtype($config['params']);

        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.label', 'Vendor');

        $fields = ['dateid', ['yourref', 'ourref'], 'rem'];
        if ($this->companysetup->getistodo($config['params'])) {
            array_push($fields, 'donetodo');
        }
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
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
        left(head.dateid,10) as dateid, 
        head.clientname,
        head.address, 
        head.shipto, 
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem,
        left(head.due,10) as due";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client

        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client   
    
        where head.trno = ? and num.doc=? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $detail = $this->opendetail($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }
            return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
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
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
                    terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur)
                    SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
                    head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
                    head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
                    head.due,head.cur
                    FROM " . $this->head . " as head 
                    where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $user];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,
                    terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur)
                    SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
                    head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
                    head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
                    head.due,head.cur
                    FROM " . $this->hhead . " as head 
                    where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting'];
        }
    } //end function
    public function select()
    {
        $select = "
          ctbl.client,ledger.docno as ref,ledger.trno,
          ledger.line,ledger.acnoid,coa.acno,coa.acnoname,cntnum.center,ledger.clientid,
          FORMAT(ledger.db,2) as db ,FORMAT(ledger.cr,2) as cr,left(ledger.dateid,10) as postdate,
          ifnull(head.ourref,'') as yourref ,head.rem,'' as bgcolor,'' as errcolor ";

        return $select;
    }

    public function opendetail($trno, $config)
    {
        $select = $this->select();
        $qry = "select " . $select . " from apledger as ledger
          left join coa on coa.acnoid = ledger.acnoid
          left join glhead as head on head.trno = ledger.trno
          left join gldetail as detail on detail.trno = ledger.trno and detail.line = ledger.line
          left join cntnum on cntnum.trno = head.trno
          left join client as ctbl on ctbl.clientid = ledger.clientid
          where ledger.kp = ? order by postdate";


        $detail = $this->coreFunctions->opentable($qry, [$trno]);
        return $detail;
    }


    public function opendetailline($trno, $line, $config)
    {
        $select = $this->select();
        $qry = "select " . $select . "  from apledger as ledger
          left join coa on coa.acnoid = ledger.acnoid
          left join glhead as head on head.trno = ledger.trno
          left join gldetail as detail on detail.trno = ledger.trno and detail.line = ledger.line
          left join cntnum on cntnum.trno = head.trno
          left join client as ctbl on ctbl.clientid = ledger.clientid
          where ledger.trno =? and ledger.line =? ";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $detail;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'getunpaidselected':
                return $this->getunpaidselected($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
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

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select trno,line from apledger where kp=? ', [$trno]);

        foreach ($data as $key => $value) {
            $this->coreFunctions->execqry('update apledger set kp =0  where kp=?', 'update', [$trno]);
        }
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $kptrno = $this->coreFunctions->getfieldvalue("apledger", "kp", "trno=? and line=?", [$trno, $line]);
        $data = $this->opendetailline($trno, $line, $config);
        $qry = "update apledger set kp =0 where trno = ? and line =?";
        $this->coreFunctions->execqry($qry, 'update', [$trno, $line]);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($kptrno, $config, 'ACCTG', 'REMOVED KP- Line:' . $line . ' code:' . $data[0]['acno'] . ' db:' . $data[0]['db'] . ' cr:' . $data[0]['cr'] . ' client:' . $data[0]['client'] . ' date:' . $data[0]['postdate'] . ' ref:' . $data[0]['ref']);
        return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    } // end function

    public function getunpaidselected($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $data = $config['params']['rows'];
        foreach ($data as $key => $value) {
            $qry = "update apledger set kp = " . $trno . " where trno = ? and line =?";
            $return = $this->coreFunctions->execqry($qry, "update", [$data[$key]['trno'], $data[$key]['line']]);
            if ($return == 1) {
                $row = $this->opendetailline($data[$key]['trno'], $data[$key]['line'], $config);
                array_push($rows, $row[0]);
            }
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Accounts Successfull...'];
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
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        $companyid = $config['params']['companyid'];
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
