<?php

namespace App\Http\Classes\modules\barangay;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;

class bt
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'T.R.U CLEARANCE';
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
        'truid',
        'bonafideid',
        'address',
        'amount'
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
            'load' => 5617,
            'view' => 5618,
            'edit' => 5619,
            'new' => 5620,
            'save' => 5621,
            'delete' => 5622,
            'print' => 5623,
            'lock' => 5624,
            'unlock' => 5625,
            'post' => 5626,
            'unpost' => 5627
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
        data_set($col1, 'client.label', 'TRU Ledger');
        data_set($col1, 'client.action', 'lookuptruledger');

        data_set($col1, 'clientname.class', 'cspurpose sbccsreadonly');
        data_set($col1, 'address.label', 'Address');
        data_set($col1, 'address.class', 'csaddressno sbccsreadonly');

        data_set($col1, 'clientname.label', 'Full Name');
        $fields = ['dateid', 'tldocno', 'bonafide', 'trutype', 'amount']; // last move
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'amount.label', 'Amount');
        $fields = ['make', 'motorno', 'chassisno', 'color'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'chassisno.label', 'Chassis No.');
        data_set($col3, 'chassisno.class', 'cschassisno sbccsreadonly');
        data_set($col3, 'chassisno.readonly', true);

        data_set($col3, 'make.class', 'csmake sbccsreadonly');
        data_set($col3, 'make.readonly', true);

        data_set($col3, 'motorno.class', 'csmotorno sbccsreadonly');
        data_set($col3, 'motorno.readonly', true);

        data_set($col3, 'color.class', 'cscolor sbccsreadonly');
        data_set($col3, 'color.readonly', true);


        $fields = ['sidecarno', 'plateno'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'plateno.label', 'Plate No.');

        data_set($col4, 'plateno.class', 'csplateno sbccsreadonly');
        data_set($col4, 'plateno.readonly', true);

        data_set($col4, 'sidecarno.class', 'cssidecarno sbccsreadonly');
        data_set($col4, 'sidecarno.readonly', true);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname'];
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
        head.doc,head.createby, 'DRAFT' as status
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        where num.doc = '$doc' $condition
        union all
        select  head.trno,head.docno,date(head.dateid) as dateid,cl.clientname,
        head.doc,head.createby,'POSTED' as status
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
        select head.trno,head.docno,head.dateid,head.client,head.clientname,
        head.address,cl.clientid,head.rem,head.bonafideid,head.truid,
        cp.picture,tru.description as trutype,bona.description as bonafide,
        cl.plateno,cl.make,cl.motorno,cl.color,info.chassisno,info.sidecarno,
        format(head.amount,2) as amount
        
        from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.client=head.client
        left join cntnum_picture as cp on cp.trno=head.trno
        left join reqcategory as tru on tru.line = head.truid
        left join reqcategory as bona on bona.line = head.bonafideid
        left join clientinfo as info on info.clientid = cl.clientid
        where num.doc = '$doc' and head.trno = ?
        union all
        select head.trno,head.docno,head.dateid,cl.client,cl.clientname,
        head.address,cl.clientid,head.rem,head.bonafideid,head.truid,
        cp.picture,tru.description as trutype,bona.description as bonafide,
        cl.plateno,cl.make,cl.motorno,cl.color,info.chassisno,info.sidecarno,format(head.amount,2) as amount
        from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client as cl on cl.clientid=head.clientid
        left join cntnum_picture as cp on cp.trno=head.trno
        left join reqcategory as tru on tru.line = head.truid
        left join reqcategory as bona on bona.line = head.bonafideid
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
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';
        $data[0]['trutype'] = '';
        $data[0]['truid'] = 0;
        $data[0]['bonafide'] = '';
        $data[0]['bonafideid'] = 0;
        $data[0]['carno'] = '';
        $data[0]['chassisno'] = '';
        $data[0]['motorno'] = '';
        $data[0]['plateno'] = '';
        $data[0]['color'] = '';
        $data[0]['make'] = '';
        $data[0]['sidecarno'] = '';
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
        $data2 = [
            'make' => $head['make'],
            'color' => $head['color'],
            'motorno' => $head['motorno'],
            'plateno' => $head['plateno']
        ];
        $data3 = [
            'chassisno' => $head['chassisno'],
            'sidecarno' => $head['sidecarno']
        ];

        if ($isupdate) {
            $isfee = $this->coreFunctions->datareader("select isfee as value from lahead where trno= '" . $head['trno'] . "' ", [], '', true);
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);

            $this->coreFunctions->sbcupdate('client', $data2, ['clientid' => $head['clientid']]);
            $this->coreFunctions->sbcupdate('clientinfo', $data3, ['clientid' => $head['clientid']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);

            $this->coreFunctions->sbcinsert('client', $data2);
            $this->coreFunctions->sbcinsert('clientinfo', $data3);
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
                    trno,docno,dateid,clientid,clientname,address,amount,truid,bonafideid)
                    select 
                    head.trno,head.docno,head.dateid,client.clientid,head.clientname,head.address,head.amount,head.truid,head.bonafideid
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
                trno,docno,dateid,client,clientname,address,amount,truid,bonafideid)
                select 
                head.trno,head.docno,head.dateid,client.client,head.clientname,
                head.address,head.amount,head.truid,head.bonafideid
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

}
