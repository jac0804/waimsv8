<?php

namespace App\Http\Classes\modules\brgy;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class bk
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CREATING ID CLEARANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
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
    public $tablepicture = 'cntnum_picture';
    public $defaultContra = 'AR1';
    private $acctg = [];

    private $fields = [
        'trno',
        'docno',
        'client',
        'clientname',
        'dateid',
        'address',
        'amount',
        'due'
    ];

    private $except = ['trno'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5699,
            'view' => 5700,
            'edit' => 5701,
            'new' => 5702,
            'save' => 5703,
            'delete' => 5704,
            'print' => 5705,
            'lock' => 5717,
            'unlock' => 5718,
            'post' => 5719,
            'unpost' => 5720
        );

        return $attrib;
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
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        return $buttons;
    }
    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Barangay ID');
        // data_set($col1, 'client.action', 'lookuptruledger');
        data_set($col1, 'client.action', 'lookupbrgyclient');
        data_set($col1, 'client.lookupclass', 'lookupbrgyidclearance');
        data_set($col1, 'clientname.class', 'csclientname sbccsreadonly');
        data_set($col1, 'address.label', 'Address');
        data_set($col1, 'address.class', 'csaddressno sbccsreadonly');

        data_set($col1, 'clientname.label', 'Full Name');
        $fields = ['bday', 'sex', 'civilstatus', 'settlertype', ['height', 'weight']];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'bday.type', 'input');
        data_set($col2, 'bday.class', 'csbday sbccsreadonly');
        data_set($col2, 'sex.type', 'input');
        data_set($col2, 'sex.class', 'cssex sbccsreadonly');
        data_set($col2, 'civilstatus.class', 'cscivilstatus sbccsreadonly');
        data_set($col2, 'civilstatus.type', 'input');

        data_set($col2, 'settlertype.type', 'input');
        data_set($col2, 'settlertype.class', 'cssettlertype sbccsreadonly');
        data_set($col2, 'settlertype.required', false);

        data_set($col2, 'height.class', 'csheight sbccsreadonly');
        data_set($col2, 'weight.class', 'csweight sbccsreadonly');

        $fields = ['lblrem', 'names', 'addressno', 'relation', 'contactno'];
        $col3 = $this->fieldClass->create($fields); #IN CASE OF EMERGENCY, PLEASE NOTIFY

        data_set($col3, 'lblrem.label', 'IN CASE OF EMERGENCY, PLEASE NOTIFY');
        data_set($col3, 'lblrem.style', 'font-weight:bold; font-size:12px;');
        data_set($col3, 'addressno.label', 'Address');
        data_set($col3, 'addressno.class', 'csaddressno sbccsreadonly');
        data_set($col3, 'names.class', 'csnames sbccsreadonly');
        data_set($col3, 'relation.class', 'csrelation sbccsreadonly');
        data_set($col3, 'contactno.class', 'cscontactno sbccsreadonly');

        data_set($col3, 'contactno.label', 'Contact No.#');
        $fields = ['dateid', 'due', 'amount'];

        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'amount.label', 'Amount');
        data_set($col4, 'dateid.label', 'Issue');
        data_set($col4, 'due.label', 'Expiry');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }


    public function createTab($config)
    {
        $column = [];
        $tab = [$this->gridname => [
            'gridcolumns' => $column,
            'headgridbtns' => ['viewref']
        ]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'Reference';
        $obj[0][$this->gridname]['totalfield'] = '';
        $obj[0][$this->gridname]['showtotal'] = false;
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'ref', 'amount'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$listdocument]['label'] = 'Record No.';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdate]['label'] = 'Transaction Date';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['align'] = 'text-left';
        $cols[$listclientname]['label'] = 'Client Name';
        $cols[$ref]['type'] = 'label';
        $cols[$ref]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        $cols[$listclientname]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
        $cols[$amount]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
        return $cols;
    }
    public function loaddoclisting($config)
    {
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }
        $query = "
        select head.trno,head.docno,date(head.dateid) as dateid,cl.clientname,
        head.doc,head.createby, 'DRAFT' as status,format(head.amount,2) as amount,
        '' as ref                                              
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        where num.doc = '$doc' $condition
        union all
        select  head.trno,head.docno,date(head.dateid) as dateid,cl.clientname,
        head.doc,head.createby,'POSTED' as status,format(head.amount,2) as amount,
        (select hh.docno from lahead as hh
         left join ladetail as d on d.trno=hh.trno where d.refx=head.trno
         union all
         select hh.docno from glhead as hh
         left join gldetail as d on d.trno=hh.trno where d.refx=head.trno) as ref
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.clientid=head.clientid
        where num.doc = '$doc' $condition ";
        $data = $this->coreFunctions->opentable($query);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];

        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $query = "
        select head.trno,head.docno,head.dateid,head.due,head.client,head.clientname,
        head.address,cl.clientid,head.rem,info.height,info.weight,info.settlertype,
        info.civilstatus,format(head.amount,2) as amount,date(cl.bday) as bday,cl.sex,ifnull(info.relation,'') as relation,
        ifnull(info.names,'') as names,ifnull(info.contactno,'') as contactno,ifnull(info.address,'') as addressno
        
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        left join clientinfo as info on info.clientid = cl.clientid
        where num.doc = '$doc' and head.trno = ?
        union all
        select head.trno,head.docno,head.dateid,head.due,cl.client,cl.clientname,
        head.address,cl.clientid,head.rem,info.height,info.weight,info.settlertype,
        info.civilstatus,format(head.amount,2) as amount,date(cl.bday) as bday,cl.sex,ifnull(info.relation,'') as relation,
        ifnull(info.names,'') as names,ifnull(info.contactno,'') as contactno,ifnull(info.address,'') as addressno

        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.clientid=head.clientid
        left join clientinfo as info on info.clientid = cl.clientid
        where num.doc = '$doc' and head.trno = ? ";
        $head = $this->coreFunctions->opentable($query, [$trno, $trno]);
        if (!empty($head)) {
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    public function createnewtransaction($docno, $config)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';
        $data[0]['bday'] = '';
        $data[0]['sex'] = '';
        $data[0]['civilstatus'] = '';
        $data[0]['height'] = '';
        $data[0]['weight'] = '';
        $data[0]['settlertype'] = '';

        $data[0]['names'] = '';
        $data[0]['contactno'] = '';
        $data[0]['addressno'] = '';
        $data[0]['relation'] = '';
        $data[0]['amount'] = '0.0';
        return $data;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];

        $isfee = false;

        if ($isupdate) {
            unset($this->fields['docno']);
        }
        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '');
            }
        }

        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }

        $this->createdistribution($config);
    }
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $checkacct = $this->othersClass->checkcoaacct(['AR1', 'SA1']);
        if ($checkacct != '') {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
        }
        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        if (!$this->createdistribution($config)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
        } else {

            $msg = "";
            if (!$this->othersClass->postingdetail($config)) {
                $msg = "Posting Failed, please check detail.";
            }
            if (!$this->othersClass->postingarledger($config)) {
                $msg = "Posting failed. Kindly check the detail(AR).";
            }

            if ($msg == '') {
                $qry = "insert into " . $this->hhead . "(
                    trno,doc,docno,dateid,clientid,clientname,address,amount,due,createdate,createby,editby,editdate,lockdate,lockuser,viewby,viewdate)
                    select 
                    head.trno,head.doc,head.docno,head.dateid,client.clientid,head.clientname,head.address,head.amount,head.due,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,head.viewby,head.viewdate
                    from " . $this->head . " as head
                    left join client on client.client=head.client
                    left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
                    where head.trno=? limit 1";
                $posted = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

                if ($posted) {
                    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$trno]);
                    $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);

                    //delete acctg entries with zero debit/credit
                    $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=" . $trno . " and db=0 and cr=0");
                    $date = $this->othersClass->getCurrentTimeStamp();
                    $data = ['postdate' => $date, 'postedby' => $user, 'tmpuser' => ''];
                    $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
                }
                $msg = "Error on Posting Head";
                goto end;
            } else {
                end:
                // $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
                return ['status' => false, 'msg' => $msg];
            }
        }
    }
    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];
        $entry = [];
        $status = true;
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        $amount = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno]);
        $query = "select trno,client, docno as ref,amount from lahead where trno = ?";
        $data = $this->coreFunctions->opentable($query, [$trno]);
        $postdate = $this->othersClass->getCurrentDate();
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($amount != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$this->defaultContra]);
            $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client,  'ref' => $data[0]->ref, 'db' => $data[0]->amount, 'cr' => 0, 'postdate' => $postdate, 'line' => 1];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);

            $entry = ['acnoid' => $acnoid, 'client' => $data[0]->client,  'ref' => $data[0]->ref, 'db' => 0, 'cr' => $data[0]->amount, 'postdate' => $postdate, 'line' => 2];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);


            foreach ($this->acctg as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                }
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
        } else {
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'NO FEE ,NO ACCOUNTING DISTRIBUTION');
        }
        end:
        return $status;
    }
    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $msg = "";
        $msgpaid = $this->othersClass->hasbeenarpaid($config);
        if ($msgpaid != '') {
            $msg = $msgpaid;
        }
        if ($msg == '') {
            if (!$this->othersClass->unpostingdetail($config)) {
                $msg = 'Unposting failed. Please check detail.';
            }

            $qry = "insert into " . $this->head . "(
                trno,doc,docno,dateid,client,clientname,address,amount,due,createdate,createby,editby,editdate,lockdate,lockuser,viewby,viewdate)
                select 
                head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
                head.address,head.amount,head.due,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,head.viewby,head.viewdate
                from " . $this->hhead . " as head
                left join client on client.clientid=head.clientid
                left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
                where head.trno=? limit 1";
            $unposted = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

            if ($unposted) {
                $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry('delete from ' . $this->hhead . ' where trno=?', 'delete', [$trno]);
                $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
                $data = ['postdate' => null, 'postedby' => '', 'tmpuser' => ''];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            }
            $msg = 'Error on Unposting Head';
            goto end;
        } else {
            end:
            $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
            return ['status' => false, 'msg' => $msg];
        }
    }
    public function stockstatusposted($config)
    {
        $action = $config['params']['action'];
        if ($action == 'stockstatusposted') {
            $action = $config['params']['lookupclass'];
        }

        switch ($action) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
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
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
