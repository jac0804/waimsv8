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

class rd
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
    public $head = 'rdhead';
    public $hhead = 'hrdhead';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $defaultContra = 'CB1';
    private $fields = ['trno', 'docno', 'dateid', 'acnoid', 'yourref', 'ourref', 'rem'];
    private $except = ['trno', 'dateid'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

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
            'view' => 5304,
            'edit' => 5305,
            'new' => 5306,
            'save' => 5307,
            'delete' => 5308,
            'print' => 5309,
            'lock' => 5310,
            'unlock' => 5311,
            'post' => 5312,
            'unpost' => 5313,
            'additem' => 5314,
            'deleteitem' => 5315
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'yourref', 'ourref', 'amount', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$amount]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';

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
        $ladb = '';
        $gldb = '';

        $stat = "'DRAFT'";

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null';
                break;
            case 'locked':
                $condition = ' and num.postdate is null and head.lockdate is not null ';
                $stat = "'LOCKED'";
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
            default:
                $stat = ' (case when num.statid = 0 then "DRAFT" else stat.status end) ';
                break;
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";

        $qry = "select head.trno,head.docno,head.clientname,$dateid $ladb, $stat as status,head.createby,head.editby,
                       head.viewby,num.postedby,head.yourref, head.ourref              
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                left join trxstatus as stat on stat.line=num.statid
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
                union all
                select head.trno,head.docno,head.clientname,$dateid $gldb,'POSTED' as status,head.createby,head.editby,
                       head.viewby, num.postedby,head.yourref, head.ourref              
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno 
                where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                      and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
        $step1 = $this->helpClass->getFields(['btnnew', 'dcontra', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'dcontra', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnrchecks', 'amount', 'btnstocksaveaccount', 'btnsaveaccount']);
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
        $columns = ['action', 'docno', 'bank', 'branch', 'checkno',  'amount', 'checkdate'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
        $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$bank]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;max-width:400px;';
        $obj[0][$this->gridname]['columns'][$branch]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;max-width:400px;';

        $obj[0][$this->gridname]['columns'][$checkno]['style'] = 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;';
        $obj[0][$this->gridname]['columns'][$amount]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$checkdate]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';

        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$bank]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$checkno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$amount]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$checkdate]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$checkdate]['type'] = 'input';

        $obj[0][$this->gridname]['descriptionrow'] = [];
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['rchecks', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = "RECEIVED CHECKS/CASH";
        $obj[1]['label'] = "DELETE ACCOUNT";
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'dacnoname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'dacnoname.lookupclass', 'CB');

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
        $data[0]['acnoid'] = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$this->defaultContra]);
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'acnoid=?', [$data[0]['acnoid']]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acnoid=?', [$data[0]['acnoid']]);

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

        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;

        $qryselect = "select num.center,head.trno,head.docno,head.yourref,head.ourref,head.acnoid,
                            coa.acno as contra,coa.acnoname,
                            '' as dacnoname,left(head.dateid,10) as dateid, 
                            date_format(head.createdate,'%Y-%m-%d') as createdate,head.rem";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acnoid=head.acnoid
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acnoid=head.acnoid
        where head.trno = ? and num.doc=? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $detail = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
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
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['acnoname']);
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
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,rem,yourref,ourref,acnoid,createdate,createby,editby,editdate,lockdate,lockuser)
                SELECT head.trno,head.doc, head.docno,head.dateid, head.rem, head.yourref, head.ourref,head.acnoid,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
                FROM " . $this->head . " as head where head.trno=? limit 1";
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
        $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,rem,yourref,ourref,acnoid,createdate,createby,editby,editdate,
                       lockdate,lockuser)
                    SELECT head.trno,head.doc, head.docno,head.dateid, head.rem, head.yourref, head.ourref,head.acnoid,
                    head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
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


    public function openstock($trno, $config)
    {
        $qry = "select concat(detail.trno,detail.line) as keyid,detail.trno as rctrno,detail.rdtrno as trno,
                    detail.line,head.docno,date(head.dateid) as dateid,
                   ag.clientname as agent,detail.bank,detail.branch,detail.checkno,detail.amount,
                   date(detail.checkdate) as checkdate,'' as bgcolor
            from hrchead as head
            left join hrcdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            where detail.rdtrno =?
            union all
            select concat(detail.trno,detail.line) as keyid,detail.trno as rctrno,detail.rdtrno as trno,
                    detail.line,head.docno,date(head.dateid) as dateid,
                   ag.clientname as agent,detail.bank,detail.branch,'' as checkno,detail.amount,
                   '' as checkdate,'' as bgcolor
            from hrhhead as head
            left join hrhdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            where detail.rdtrno =?";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $detail;
    }


    public function openstockline($trno, $line, $config)
    {
        $qry = "select head.doc,concat(detail.trno,detail.line) as keyid,detail.trno as rctrno,detail.rdtrno as trno,detail.line,
                    head.docno,date(head.dateid) as dateid,
                   ag.clientname as agent,detail.bank,detail.branch,detail.checkno,detail.amount,
                   date(detail.checkdate) as checkdate,'' as bgcolor 
            from hrchead as head
            left join hrcdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            where detail.trno =? and detail.line =?
            union all
            select head.doc,concat(detail.trno,detail.line) as keyid,detail.trno as rctrno,detail.rdtrno as trno,detail.line,
                    head.docno,date(head.dateid) as dateid,
                   ag.clientname as agent,detail.bank,detail.branch,'' as checkno,detail.amount,
                   '' as checkdate,'' as bgcolor 
            from hrhhead as head
            left join hrhdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            where detail.trno =? and detail.line =?";
        $detail = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
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
            case 'getrc':
                return $this->getrc($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
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
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getrc($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $data = $config['params']['rows'];
        foreach ($data as $key => $value) {

            if ($data[$key]['doc'] == 'RC') {
                $qry = "update hrcdetail set rdtrno = " . $trno . " where trno = ? and line =?";
            } else {
                $qry = "update hrhdetail set rdtrno = " . $trno . " where trno = ? and line =?";
            }

            $return = $this->coreFunctions->execqry($qry, "update", [$data[$key]['trno'], $data[$key]['line']]);
            if ($return == 1) {
                $row = $this->openstockline($data[$key]['trno'], $data[$key]['line'], $config);
                array_push($rows, $row[0]);
            }
        } //end foreach

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts successfully.'];
    } //end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select trno,line from hrcdetail where rdtrno=? ', [$trno]);
        $data2 = $this->coreFunctions->opentable('select trno,line from hrhdetail where rdtrno=? ', [$trno]);

        foreach ($data as $key => $value) {
            $this->coreFunctions->execqry('update hrcdetail set rdtrno =0  where rdtrno=?', 'update', [$trno]);
        }
        foreach ($data2 as $key => $value) {
            $this->coreFunctions->execqry('update hrhdetail set rdtrno =0  where rdtrno=?', 'update', [$trno]);
        }

        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL CHECKS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function deleteitem($config)
    {
        $rdtrno = $config['params']['row']['trno'];
        $trno = $config['params']['row']['rctrno'];
        $line = $config['params']['row']['line'];
        $data = $this->openstockline($trno, $line, $config);


        foreach ($data as $key => $value) {
            if ($data[$key]->doc == 'RC') {
                $qry = "update hrcdetail set rdtrno =0 where trno = ? and line =?";
            } else {
                $qry = "update hrhdetail set rdtrno =0 where trno = ? and line =?";
            }
            $this->coreFunctions->execqry($qry, 'update', [$trno, $line]);
            $data = json_decode(json_encode($data), true);
        } //end foreach

        $this->logger->sbcwritelog($rdtrno, $config, 'ACCTG', 'REMOVED Line: ' . $line);
        return ['status' => true, 'msg' => 'Check was successfully deleted.'];
    } // end function

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
        switch ($companyid) {
            case 39:
                if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
                if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
                if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
                break;
        }
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
