<?php

namespace App\Http\Classes\modules\accounting;

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
use App\Http\Classes\builder\helpClass;

class dx
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DEPOSIT SLIP';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'dxhead';
    public $hhead = 'hdxhead';
    public $hcehead = 'hcehead';
    public $tablelogs = 'transnum_log';
    public $htablelogs = 'htransnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $defaultContra = 'CB';
    private $fields = ['trno', 'docno', 'dateid', 'yourref', 'ourref', 'rem', 'amount', 'bank','mpid','checkinfo'];
    private $except = ['trno', 'dateid'];
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
            'view' => 5056,
            'edit' => 5057,
            'new' => 5058,
            'save' => 5059,
            'delete' => 5060,
            'print' => 5061,
            'lock' => 5062,
            'unlock' => 5063,
            'post' => 5064,
            'unpost' => 5065,
            'additem' => 5066,
            'edititem' => 5067,
            'deleteitem' => 5068
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $acnoname = 4;
        $amount = 5;
        $postdate = 6;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'acnoname', 'amount', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$acnoname]['align'] = 'text-left';
        $cols[$listdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$acnoname]['type'] = 'input';
        $cols[$acnoname]['label'] = 'Bank';
        $cols[$acnoname]['style'] = 'width:220px;whiteSpace: normal;min-width:220px;';
        $cols[$amount]['align'] = 'text-left';
        $cols[$amount]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$postdate]['label'] = 'Posted By';
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

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head2.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        if ($searchfilter == "") $limit = 'limit 150';

        $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, 'DRAFT' as status,
            head.createby,head.editby,head.viewby,num.postedby,
            coa.acnoname, format(head.amount,2) as amount              
            from " . $this->head . " as head left join " . $this->tablenum . " as num 
            on num.trno=head.trno
            left join coa on coa.acnoid=head.bank
             where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
            union all
            select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status,
            head.createby,head.editby,head.viewby, num.postedby,
            coa.acnoname, format(head.amount,2) as amount             
            from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
            on num.trno=head.trno
            left join coa on coa.acnoid=head.bank 
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
            order by  dateid desc, docno desc $limit";

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
        $step1 = $this->helpClass->getFields(['btnnew', 'dcontra', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'dcontra', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnundepositeddscol', 'amount', 'rem']);
        $step4 = $this->helpClass->getFields(['amount', 'rem']);
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

        if ($this->companysetup->getistodo($config['params'])) {
            $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
            $objtodo = $this->tabClass->createtab($tab, []);
            $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
        }
        return $return;
    }


    public function createtabbutton($config)
    {
        $companyid = $config['params']['companyid'];
        if($companyid==57){
            return [];
        }else{
            $tbuttons = ['undepositeddscollection', 'deleteallitem'];
            foreach ($tbuttons as $key => $value) {
                $$value = $key;
            }
            $obj = $this->tabClass->createtabbutton($tbuttons);
            $obj[$deleteallitem]['label'] = "DELETE";

            return $obj;
        }
        
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'dacnoname','modeofpayment2'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'dacnoname.label', 'Bank');
        data_set($col1, 'dacnoname.lookupclass', 'CB');
        data_set($col1, 'dacnoname.required', true);


        $fields = ['dateid', 'amount','checkinfo'];
        $col2 = $this->fieldClass->create($fields);

        
        data_set($col2, 'checkinfo.action', 'lookupcashiercheck');
        data_set($col2, 'checkinfo.label', 'Check #');

        
        data_set($col2, 'checkinfo.type', 'lookup');
        data_set($col2, 'checkinfo.lookupclass', 'lookupcashiercheck');

        // data_set($col2, 'amount.class', 'sbccsreadonly');
        $fields = [['yourref', 'ourref'], 'rem'];
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
        $data[0]['checkinfo'] = '';
        $data[0]['rem'] = '';
        $data[0]['amount'] = 0;
        $data[0]['bank'] = 0;
        
        $data[0]['mpid'] = '';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
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
        head.doc,
        head.yourref,
        head.ourref,
        head.checkinfo,head.mpid,m.category as modeofpayment2,
        left(head.dateid,10) as dateid, 
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem, format(head.amount,2) as amount,  head.bank,  coa.acnoname,'' as dacnoname,coa.acno as contra";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acnoid=head.bank
        left join reqcategory as m on m.line=head.mpid
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acnoid=head.bank
        left join reqcategory as m on m.line=head.mpid
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
            return  ['head' => $head, 'griddata' => ['inventory' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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

        if($head['modeofpayment2']=='CHECK'){
            $cetrno = $this->coreFunctions->datareader("select trno as value from hcehead where checkinfo  =?",[$head['checkinfo']]);
            $this->coreFunctions->sbcupdate('transnum', ['dstrno' => $head['trno']], ['trno' => $cetrno]);    
        }

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
        $this->coreFunctions->sbcupdate('transnum', ['dstrno' => 0], ['dstrno' => $trno]);   
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        // if($companyid!=57){
        //     $qry = "select dstrno from transnum where dstrno=? ";
        //     $collection = $this->coreFunctions->opentable($qry, [$trno]);
        //     if (empty($collection)) {
        //         return ['status' => false, 'msg' => 'Posting failed. No collections to post.'];
        //     }
        // }
        
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,rem,yourref,ourref,bank,amount,mpid,checkinfo,
        createdate,createby,editby,editdate,lockdate,lockuser)
        SELECT head.trno,head.doc, head.docno, head.dateid as dateid, head.rem, head.yourref, head.ourref,head.bank,head.amount,head.mpid,head.checkinfo,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Posting'];
        }
    } //end function



    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $dateid = $this->coreFunctions->datareader('select dateid as value from ' . $this->hhead . ' where trno=?', [$trno]);
        $close = $this->coreFunctions->datareader("select dateid as value from eod where center = '".$center."' order by dateid desc limit 1");
       

        if($close !=''){
            $dateid = $this->othersClass->sbcdateformat($dateid);
            $close = $this->othersClass->sbcdateformat($close);
            if($dateid<=$close){
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Date already close.'];   
            }
    
        }
        
        $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,rem,yourref,ourref,bank,amount,
        mpid,checkinfo,
        createdate,createby,editby,editdate,lockdate,lockuser)
        select  head.trno,head.doc, head.docno, head.dateid as dateid, head.rem, head.yourref, head.ourref,head.bank,head.amount,head.mpid,head.checkinfo,
        head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
        from " . $this->hhead . " as head 
        where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED'];
        }
    } //end function


    public function stockstatus($config)
    {

        $type = 'insert';
        switch ($config['params']['action']) {
            case 'deleteallitem':
                return $this->deleteallitem($config, $type);
                break;
            case 'deleteitem':
                return $this->deleteitem($config, $type);
                break;
            case 'getdsundepositedcol':
                return $this->getdsundepositedcol($config, $type);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function getdsundepositedcol($config, $type)
    {
        $trno = $config['params']['trno'];

        $rows = [];
        $data = $config['params']['rows'];
        $status = true;
        foreach ($data as $key => $value) {
            $qry = "update transnum set dstrno = " . $trno . " where trno = ? ";
            $return = $this->coreFunctions->execqry($qry, "update", [$data[$key]['trno']]);
            $this->getupdateamount($config, $type);
            if ($return == 1) {
                $row = $this->opendetail($data[$key]['trno'], $config);
                if (!empty($row)) {
                    array_push($rows, $row[0]);
                }
            }
        } //end foreach
        return ['rows' => $rows, 'status' => true, 'reloadhead' => true, 'msg' => 'Added Accounts Successfull...'];
    } //end function


    public function getupdateamount($config, $type)
    {
        $trno = $config['params']['trno'];
        if ($type == 'insert') {
            $data = $this->coreFunctions->opentable('select trno from transnum where dstrno = ?', [$trno]);
            if (empty($data)) {
                return;
            }
            $trnoList = [];
            foreach ($data as $row) {
                $trnoList[] = (int) $row->trno;
            }
            $trnoString = implode(',', $trnoList);
            $qry = "select amount from hcehead where trno IN ($trnoString)";
            $res = $this->coreFunctions->opentable($qry);
            $totalAmount = 0;
            foreach ($res as $item) {
                $amount = (float) str_replace(',', '', $item->amount);
                $totalAmount += $amount;
            }
            $this->coreFunctions->sbcupdate($this->head, ['amount' => $totalAmount], ['trno' => $trno]);
        } elseif ($type == 'deleteperitem') {
            $config['params']['trno'] = $config['params']['row']['line'];
            $trno = $config['params']['row']['dstrno']; //trno ng dx
            $line = $config['params']['trno']; //trno ng hce
            $amount = $this->coreFunctions->getfieldvalue("dxhead", "amount", "trno=?", [$trno]);
            $qry = "select ce.amount from hcehead  as ce 
                   left join transnum as num  on num.trno=ce.trno
                    where num.trno= $line and num.dstrno=$trno";
            $test = $this->coreFunctions->opentable($qry);
            $testamount = $amount - $test[0]->amount;
            $this->coreFunctions->sbcupdate($this->head, ['amount' => $testamount], ['trno' => $trno]);
        } else {
            $this->coreFunctions->sbcupdate($this->head, ['amount' => 0], ['trno' => $trno]);
        }
        return ['status' => true, 'msg' => 'Successfully updated amount '];
    }

    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        return [];
        // if($companyid==57){
        //     return [];
        // }else{
            // $columns = [
            //     'action',
            //     'dateid',
            //     'checkdate',
            //     'checkinfo',
            //     'amount',
            //     'docno',
            //     'clientname',
            //     'rem'
            // ];

            // foreach ($columns as $key => $value) {
            //     $$value = $key;
            // }

            // $tab = [$this->gridname => ['gridcolumns' => $columns]];
            // $stockbuttons = ['delete'];

            // $obj = $this->tabClass->createtab($tab, $stockbuttons);
            // $obj[0][$this->gridname]['label'] = 'COLLECTIONS';
            // $obj[0][$this->gridname]['totalfield'] = 'amount';
            // $obj[0][$this->gridname]['descriptionrow'] = '';
            // $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'NAME';
            // $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
            // $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

            // $obj[0][$this->gridname]['columns'][$checkdate]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$checkdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

            // $obj[0][$this->gridname]['columns'][$checkinfo]['readonly'] = true;

            // $obj[0][$this->gridname]['columns'][$amount]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

            // $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

            // $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
            // $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

            // return $obj;
        //}
        
    }

    public function opendetailline($trno, $config)
    {
        $qry = " select num.dstrno as trno, ce.trno as line,left(ce.dateid,10) as dateid, left(ce.checkdate,10) as checkdate,
        ce.checkinfo,ce.docno,ce.clientname,ce.yourref as crno,ce.ourref as orno,ce.sicsino,ce.drno,
        ce.rem,num.dstrno,format(ce.amount,2) as amount,'' as bgcolor,'' as errcolor 
        from " . $this->hcehead . " as ce  left join transnum as num on num.trno= ce.trno 
        where num.dstrno = $trno 
        union all
        select num.dstrno as trno, ce.trno as line,left(ce.dateid,10) as dateid, left(ce.checkdate,10) as checkdate,
        ce.checkinfo,ce.docno,ce.clientname,ce.yourref as crno,'' as orno,ce.sicsino,ce.drno,
        ce.rem,num.dstrno,format(ce.amount,2) as amount,'' as bgcolor,'' as errcolor 
        from hmchead as ce  left join transnum as num on num.trno= ce.trno 
        where num.dstrno = $trno ";
        $detail = $this->coreFunctions->opentable($qry);
        return $detail;
    }


    public function opendetail($trno)
    {
        $qry = "select num.dstrno as trno, ce.trno as line,left(ce.dateid,10) as dateid,left(ce.checkdate,10) as checkdate,
        ce.checkinfo,ce.docno,ce.clientname,ce.yourref as crno,ce.ourref as orno,ce.sicsino,ce.drno,
        ce.rem,num.dstrno,format(ce.amount,2) as amount,'' as bgcolor,'' as errcolor 
        from  " . $this->hcehead . " as ce left join transnum as num on num.trno= ce.trno where num.dstrno = $trno 
         union all
        select num.dstrno as trno, ce.trno as line,left(ce.dateid,10) as dateid, left(ce.checkdate,10) as checkdate,
        ce.checkinfo,ce.docno,ce.clientname,ce.yourref as crno,'' as orno,ce.sicsino,ce.drno,
        ce.rem,num.dstrno,format(ce.amount,2) as amount,'' as bgcolor,'' as errcolor 
        from hmchead as ce left join transnum as num on num.trno= ce.trno
        where num.dstrno = $trno ";
        $detail = $this->coreFunctions->opentable($qry);
        return $detail;
    }

    public function deleteallitem($config)
    {
        $type = 'deleteallitem';
        $trno = $config['params']['trno'];
        $updateResult = $this->getupdateamount($config, $type);
        error_log("getupdateamount() result: " . json_encode($updateResult));
        $data = $this->coreFunctions->opentable('select trno, dstrno from  transnum where dstrno=? ', [$trno]);
        foreach ($data as $key => $value) {
            $this->coreFunctions->execqry('update transnum set dstrno =0  where dstrno=?', 'update', [$trno]);
        }
        $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'DELETED ALL COLLECTION ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => [], 'update_result' => $updateResult, 'reloadhead' => true];
    }

    public function deleteitem($config, $type)
    {
        $type = 'deleteperitem';
        $config['params']['trno'] = $config['params']['row']['line'];
        $trno = $config['params']['row']['dstrno']; //trno ng dx
        $line = $config['params']['trno']; //trno ng hce
        $updateResult = $this->getupdateamount($config, $type);
        error_log("getupdateamount() result: " . json_encode($updateResult));
        $hcetrno = $this->coreFunctions->getfieldvalue("transnum", "dstrno", "trno=? and dstrno=?", [$line, $trno]);
        $data = $this->opendetailline($trno, $config);
        $qry = "update transnum set dstrno =0 where trno = ? and dstrno= ? ";
        $this->coreFunctions->execqry($qry, 'update', [$line, $trno]);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($hcetrno, $config, 'DETAIL', 'REMOVED - Clientname:' . $data[0]['clientname'] . ' Date:' . $data[0]['dateid'] . ' Amt:' . $data[0]['amount']);
        return ['status' => true, 'msg' => 'Collection was successfully deleted.', 'update_result' => $updateResult, 'reloadhead' => true];
    } // end function

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
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
