<?php

namespace App\Http\Classes\modules\cashier;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class ce
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'CASHIER ENTRY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'cehead';
    public $hhead = 'hcehead';
    public $detail = 'cedetail';
    public $hdetail = 'hcedetail';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'clientid',
        'clientname',
        'yourref',
        'ourref',
        'amount',
        'bank',
        'rem',
        'address',
        'checkinfo',
        'checkdate',
        'rem2',
        'sicsino',
        'drno',
        'trnxtid',
        'mpid',
        'ppid',
        'rctrno',
        'rcline',
        'rslip',
        'contra',
        'acnoname'
    ];

    private $except = ['trno', 'dateid'];

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
        ['val' => 'all', 'label' => 'All', 'color' => 'green']
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
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    } //end function

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5042,
            'edit' => 5043,
            'new' => 5044,
            'save' => 5045,
            'delete' => 5046,
            'print' => 5047,
            'lock' => 5048,
            'unlock' => 5049,
            'post' => 5050,
            'unpost' => 5051,
            'additem' => 5052,
            'deleteitem' => 5053
        );
        return $attrib;
    } //end function

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
            'help'
        );

        if ($this->companysetup->getclientlength($config['params']) != 0) {
            array_push($btns, 'others');
        }

        $buttons = $this->btnClass->create($btns);
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];

        return $buttons;
    } //end function

    public function createHeadField($config)
    {
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);
        $fields = ['docno', 'client', 'clientname', 'address', 'purposeofpayment', 'trnxtype2', 'modeofpayment2'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.lookupclass', 'lookupclient');
        data_set($col1, 'purposeofpayment.required', true);
        data_set($col1, 'purposeofpayment.error', false);
        data_set($col1, 'trnxtype2.required', true);
        data_set($col1, 'trnxtype2.error', false);
        data_set($col1, 'modeofpayment2.required', true);
        data_set($col1, 'modeofpayment2.error', false);

        $fields = ['dateid', ['yourref', 'ourref'], ['sicsino', 'drno'],['rslip','dacnoname'], 'amount', 'bank', ['checkinfo', 'checkdate']];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'yourref.label', 'CR #');
        data_set($col2, 'ourref.label', 'OR#');
        data_set($col2, 'bank.label', 'Check Bank');
        data_set($col2, 'amount.required', true);
        data_set($col2, 'amount.error', false);
        data_set($col2, 'dacnoname.lookupclass', 'CB');
        data_set($col2, 'dacnoname.label', 'Transfer to Bank');
       

        data_set($col2, 'checkinfo.type', 'lookup');
        data_set($col2, 'checkinfo.lookupclass', 'lookupdcchecks');
        data_set($col2, 'checkinfo.action', 'lookupdcchecks');
        data_set($col2, 'checkinfo.addedparams', ['client']);

        if ($noeditdate) data_set($col2, 'dateid.class', 'sbccsreadonly');
        $fields = ['rem', 'rem2','deposit'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'rem2.type', 'input');
        data_set($col3, 'rem2.label', 'MC Unit');
        data_set($col3, 'deposit.label', 'Deposit Slip');
        data_set($col3, 'deposit.class', 'sbccsreadonly');

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
    } //end function 

    public function createTab()
    {
        return [];
    } //end function

    public function createtab2()
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    } //end function

    public function createtabbutton($config)
    {
        return [];
    } //end function

    public function createdoclisting()
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname','amount','modeofpayment', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$modeofpayment]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';
        $cols[$amount]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$yourref]['label'] = 'CR#';
        $cols[$ourref]['label'] = 'OR#';

        return $cols;
    } //end function

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $limit = 'limit 150';
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'cl.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        $status = "
        (case when head.lockdate is not null and num.postdate is null then 'Locked'
        when num.postdate is not null then 'Posted' else 'DRAFT' end)";

        switch ($itemfilter) {
            case 'draft':
                $status = "'DRAFT'";
                $condition .= ' and num.postdate is null and head.lockdate is null ';
                break;
            case 'locked':
                $status = "'Locked'";
                $condition .= ' and num.postdate is null and head.lockdate is not null ';
                break;
            case 'posted':
                $status = "'Posted'";
                $condition .= ' and num.postdate is not null ';
                break;
        }

        $qry = "select head.trno,head.doc,head.docno,head.clientname,head.rem,head.yourref,head.ourref,
        $status as status,format(head.amount,2) as amount,
        left(head.dateid,10) as dateid,date(num.postdate) as postdate,
        head.createby,head.editby,num.postedby,left(head.createdate,10)  as createdate,rc2.category as modeofpayment
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join client as cl on cl.clientid = head.clientid
        left join reqcategory as rc2 on rc2.line = head.mpid
        where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
        or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . " 

        UNION ALL

        select head.trno,head.doc,head.docno,head.clientname,head.rem,head.yourref,head.ourref,
        $status as status,format(head.amount,2) as amount,
        left(head.dateid,10) as dateid,date(num.postdate) as postdate,
        head.createby,head.editby,num.postedby,left(head.createdate,10)  as createdate,rc2.category as modeofpayment
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join client as cl on cl.clientid = head.clientid
        left join reqcategory as rc2 on rc2.line = head.mpid
        where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
        or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . " order by docno desc $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $date1, $date2, $doc, $center, $date1, $date2, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    } //end function

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['checkdate'] = $this->othersClass->getCurrentDate();
        $data[0]['clientid'] = $this->coreFunctions->getfieldvalue("client","clientid","client ='WALK-IN'");
        $data[0]['clientname'] = 'WALK-IN';
        $data[0]['client'] = 'WALK-IN';
        $data[0]['checkinfo'] = '';
        $data[0]['amount'] = '';
        $data[0]['bank'] = '';
        $data[0]['contra'] = '';
        $data[0]['dacnoname'] = '';
        $data[0]['acnoname'] = '';
        $data[0]['address'] = '';
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['rem2'] = '';
        $data[0]['sicsino'] = '';
        $data[0]['drno'] = '';
        $data[0]['ppid'] = '';
        $data[0]['trnxtid'] = '';
        $data[0]['deposit'] = '';
        $data[0]['rslip'] = '';
        $data[0]['mpid'] = '';
        $data[0]['modeofpayment2'] = '';        
        $data[0]['purposeofpayment'] = '';
        $data[0]['trnxtype2'] = '';
        $data[0]['rctrno'] = 0;
        $data[0]['rcline'] = 0;
        return $data;
    } //end function

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

        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $rcref = $this->coreFunctions->datareader("select concat(rctrno,'~',rcline) as value from ".$this->head." where trno  =?",[$head['trno']]);
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);

            if($rcref !=""){
                $rc = explode("~",$rcref);
                if($rc[0]!=0){
                    if($head['rctrno']!=0){
                        if($rc[0] != $head['rctrno'] && $rc[1] != $head['rcline']){
                            $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $head['trno']], ['trno' => $head['rctrno'], 'line' => $head['rcline']]);
                        }
                        
                    }
                }else{
                    $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $head['trno']], ['trno' => $head['rctrno'], 'line' => $head['rcline']]);
                }
                
            }
            
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            if($head['rctrno']!=0){
                $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => $head['trno']], ['trno' => $head['rctrno'], 'line' => $head['rcline']]);
            }
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    } // end function

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];

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

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;

        $qryselect = "
        select head.trno,head.doc,head.docno,left(head.dateid,10) as dateid,head.clientname,cl.clientid,
        cl.client,head.rem,head.yourref,head.ourref,head.address,head.checkinfo,format(head.amount,2) as amount,head.bank,
        head.checkdate,head.rem2,head.sicsino,head.drno,head.trnxtid,head.mpid,head.ppid,rc1.category as trnxtype2, 
        rc2.category as modeofpayment2, rc3.category as purposeofpayment,ifnull(ds.docno,'') as deposit,head.rctrno,head.rcline,head.rslip,head.contra,'' as dacnoname,head.acnoname";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $tablenum as ds on ds.trno = num.dstrno
        left join client as cl on cl.clientid = head.clientid
        left join reqcategory as rc1 on rc1.line = head.trnxtid
        left join reqcategory as rc2 on rc2.line = head.mpid
        left join reqcategory as rc3 on rc3.line = head.ppid
        where head.trno = ? and num.center=? 

        UNION ALL

        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $tablenum as ds on ds.trno = num.dstrno
        left join client as cl on cl.clientid = head.clientid
        left join reqcategory as rc1 on rc1.line = head.trnxtid
        left join reqcategory as rc2 on rc2.line = head.mpid
        left join reqcategory as rc3 on rc3.line = head.ppid
        where head.trno = ? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'griddata' => [$this->gridname => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => [$this->gridname => []], 'msg' => 'Data Head Fetched Failed'];
        }
    } //end function

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,clientid,clientname,address,dateid,rem,amount,bank,checkinfo,
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,checkdate,rem2,sicsino,drno,trnxtid,mpid,ppid,rctrno,rcline,rslip,contra,acnoname)
        SELECT head.trno,head.doc, head.docno,head.clientid,head.clientname,head.address,head.dateid as dateid, head.rem,head.amount,head.bank,
        head.checkinfo,head.yourref, head.ourref,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,
        head.checkdate,head.rem2,head.sicsino,head.drno,head.trnxtid,head.mpid,head.ppid,head.rctrno,head.rcline,head.rslip,head.contra,head.acnoname
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
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $dstrno = $this->coreFunctions->datareader('select dstrno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if($dstrno !=0){
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Already deposited.'];   
        }

        $dateid = $this->coreFunctions->datareader('select dateid as value from ' . $this->hhead . ' where trno=?', [$trno]);
        $close = $this->coreFunctions->datareader("select ifnull(dateid,'') as value from eod where center ='".$center."' order by dateid desc limit 1");

        
        if($close !=''){
            $dateid = $this->othersClass->sbcdateformat($dateid);
            $close = $this->othersClass->sbcdateformat($close);

            if($dateid <= $close){
                return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Date already close.'];   
           }
        }

        $qry = "insert into " . $this->head . "(trno,doc,docno,clientid,clientname,address,dateid,rem,amount,bank,checkinfo,
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,checkdate,rem2,sicsino,drno,trnxtid,mpid,ppid,rctrno,rcline,rslip,contra,acnoname)
        SELECT head.trno,head.doc, head.docno,head.clientid,head.clientname,head.address,head.dateid as dateid, head.rem,head.amount,head.bank,head.checkinfo,
        head.yourref, head.ourref,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,
        head.checkdate,head.rem2,head.sicsino,head.drno,head.trnxtid,head.mpid,head.ppid,head.rctrno,head.rcline,head.rslip,head.contra,head.acnoname
        FROM " . $this->hhead . " as head 
        where head.trno=? limit 1";

        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,postedby='' where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting head'];
        }
    } //end function

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

        $rctrno = $this->coreFunctions->datareader("select rctrno as value from " . $this->head . ' where trno=?', [$trno]);
        $rcline = $this->coreFunctions->datareader("select rcline as value from " . $this->head . ' where trno=?', [$trno]);

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);

        
        if (floatval($rctrno) != 0) {
          $this->coreFunctions->sbcupdate('hrcdetail', ['ortrno' => 0], ['trno' => $rctrno, 'line' => $rcline]);
        }
        $this->logger->sbcdel_log($trno, $config, $docno);

        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    } //end function

    public function reportdata($config)
    {
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['noted'])) $this->othersClass->writeSignatories($config, 'noted', $dataparams['noted']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    } //end function
} //end class
