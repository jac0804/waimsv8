<?php

namespace App\Http\Classes\modules\waterbilling;

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


class wn
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'WATER CONNECTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'wnhead';
    public $hhead = 'hwnhead';
    public $stock = '';
    public $hstock = '';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';
    private $fields = [
        'trno',
        'docno',
        'dateid',
        'client',
        'clientname',
        'address',
        'itemid',
        'projectid',
        'conndate',
        'disconndate',
        'rem',
        'begqty'
    ];
    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'disconnected', 'label' => 'Disconnected', 'color' => 'primary'],
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
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4113,
            'edit' => 4114,
            'new' => 4115,
            'save' => 4116,
            'delete' => 4117,
            'print' => 4118,
            'lock' => 4119,
            'unlock' => 4120,
            'post' => 4121,
            'unpost' => 4109
        );
        return $attrib;
    }


    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'barcode', 'listprojectname', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view', 'disconnect'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$lblstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$listprojectname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        $cols[$barcode]['label'] = 'Meter No.';

        $cols[$listpostedby]['label'] = 'Post Date';
        $cols[$action]['btns']['disconnect']['checkfield'] = "isposted";

        $cols = $this->tabClass->delcollisting($cols);

        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = ['uploadexcel'];
        $companyid = $config['params']['companyid'];

        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'uploadexcel.label', 'UPLOAD EXCEL');
        $data = [];

        return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1]];
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
        $status = "";
        $ustatus = "";
        $limit = '';
        $filtersearch = "";

        $join = '';
        $hjoin = '';
        $addparams = '';

        $companyid = $config['params']['companyid'];

        $dateid = "left(head.dateid,10) as dateid";
        $status = "stat.status";
        $ustatus = "Draft";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'p.name', 'item.barcode'];
            // $search = $config['params']['search'];
            if ($searchfilter != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $searchfilter);
            }
        } else {
            $limit = 'limit 150';
        }
        // if ($searchfilter != "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";

        // $status = "'DRAFT'";
        $leftjoin = "";
        $leftjoin_posted = "";
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null and head.lockdate is null ';
                break;

            case 'disconnected':
                $condition = ' and num.statid = 76';
                break;

            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                $status = "'LOCKED'";
                $ustatus = "LOCKED";
                break;

            case 'complete':
                $condition = ' and num.statid = 7 ';
                break;

            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }


        $qry = "select head.trno,head.docno,head.clientname,$dateid,case ifnull(head.lockdate,'') when '' then '" . $ustatus . "' else 'Locked' end as stat,head.createby,head.editby,head.viewby,num.postedby, 
        date(num.postdate) as postdate,head.yourref, head.ourref, head.rem,head.projectid, ifnull(p.name,'') as projectname,
        if(num.postdate is not null and head.disconndate is null,'false','true') as isposted, item.barcode
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join trxstatus as stat on stat.line=num.statid 
        left join projectmasterfile as p on p.line = head.projectid
        left join item on item.itemid=head.itemid
        where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . $filtersearch . "
        group by head.trno, head.docno, head.clientname, head.dateid, stat.status,
        head.createby, head.editby, head.viewby, num.postedby,
        num.postdate, head.yourref, head.ourref,stat.line,head.lockdate, head.rem, head.projectid,projectname, head.disconndate, item.barcode
        union all
        select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, head.rem
        ,head.projectid, ifnull(p.name,'') as projectname,
         if(num.postdate is not null and head.disconndate is null,'false','true') as isposted, item.barcode
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join trxstatus as stat on stat.line=num.statid 
        left join projectmasterfile as p on p.line = head.projectid
        left join item on item.itemid=head.itemid
        where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . $filtersearch . "
        group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref,stat.line ,head.lockdate, head.rem, head.projectid, projectname, head.disconndate, item.barcode
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
        $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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


        $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'po', 'title' => 'PO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        return [];
    }


    public function createTab($access, $config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        return [];
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.lookupclass', 'customer');

        $fields = ['dateid', ['barcode', 'begqty'], 'projectname', 'shortname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'barcode.label', 'Meter No');
        data_set($col2, 'barcode.lookupclass', 'meterno');
        data_set($col2, 'barcode.class', 'sbccsreadonly');
        data_set($col2, 'shortname.label', 'Meter Address');
        data_set($col2, 'shortname.class', 'csshortname sbccsreadonly');

        data_set($col2, 'projectname.class', 'csprojectname');
        data_set($col2, 'projectname.type', 'input');
        data_set($col2, 'projectname.class', 'sbccsreadonly');
        data_set($col2, 'projectname.lookupclass', 'default');
        data_set($col2, 'projectname.action', 'lookupproject');

        $fields = ['conndate', 'disconndate'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'disconndate.type', 'input');
        data_set($col3, 'disconndate.class', 'sbccsreadonly');

        $fields = ['rem'];
        $col4 = $this->fieldClass->create($fields);


        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';
        $data[0]['rem'] = '';
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['itemid'] = 0;
        $data[0]['begqty'] = '0.00';

        $data[0]['conndate'] = $this->othersClass->getCurrentDate();
        $data[0]['disconndate'] = null;

        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
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
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         FORMAT(head.begqty,2) as begqty,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid, ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         date(head.conndate) as conndate, date(head.disconndate) as disconndate,
         head.itemid, item.barcode, item.shortname,
         if(num.postdate is not null,'true','false') as isposted
         ";
        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join projectmasterfile as p on p.line = head.projectid
        left join item on item.itemid = head.itemid
        where head.trno = ? and head.doc = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join projectmasterfile as p on p.line = head.projectid
        left join item on item.itemid = head.itemid
        where head.trno = ? and head.doc = ? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            //$hideobj = ['lblreceived' => false];
            $hidetabbtn = ['btndeleteallitem' => false];
            $clickobj = ['button.btnadditem'];
            if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti
                $clickobj = [];
            }

            $hideobj = [];
            if ($this->companysetup->getistodo($config['params'])) {
                $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
                $hideobj = ['donetodo' => !$btndonetodo];
            }

            return  [
                'head' => $head,
                'griddata' => ['inventory' => []],
                'islocked' => $islocked,
                'isposted' => $isposted,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'clickobj' => $clickobj,
                'hidetabbtn' => $hidetabbtn,
                'hideobj' => $hideobj
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function updatehead($config, $isupdate)
    {
        $companyid = $config['params']['companyid'];
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
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                } //end if
            }
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->updatemeter($data['itemid'], $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data['client']]));
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->updatemeter($data['itemid'], $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data['client']]));
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    } // end function

    private function updatemeter($itemid, $clientid)
    {
        return $this->coreFunctions->sbcupdate('item', ['clientid' => $clientid], ['itemid' => $itemid]);
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
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function


    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,dateid,
      rem,createdate,createby,editby,editdate,lockdate,lockuser,projectid,itemid,conndate,disconndate,begqty)
      select head.trno,head.doc,head.docno,head.client,head.clientname,head.address,head.dateid,
      head.rem,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,head.projectid,head.itemid,head.conndate,head.disconndate,head.begqty
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $disconn = $this->coreFunctions->getfieldvalue($this->hhead, "disconndate", "trno=?", [$trno]);

        if ($disconn != "" || $disconn != null) {
            return ['status' => false, 'msg' => 'Cannot unpost; transaction has already been disconnected.'];
        }

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,dateid,
        rem,createdate,createby,editby,editdate,lockdate,lockuser,projectid,itemid,conndate,disconndate,begqty)
        select head.trno,head.doc,head.docno,head.client,head.clientname,head.address,head.dateid,
        head.rem,head.createdate,head.createby,head.editby,head.editdate,head.lockdate,head.lockuser,head.projectid,head.itemid,head.conndate,head.disconndate,head.begqty
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
        where head.trno=? limit 1";
        //head
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $this->coreFunctions->execqry("update " . $this->tablenum . " set statid = 0, postdate=null where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        }
    } //end function


    public function openstock($trno, $config)
    {
        return [];
    } //end function

    public function openstockline($config)
    {
        return [];
    } // end function

    public function stockstatus($config)
    {
        return [];
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'disconnect':
                return $this->disconnect($config);
                break;
            case 'updateitemvoid':
                return $this->updateitemvoid($config);
                break;
            case 'diagram':
                return $this->diagram($config);
                break;
            case 'jumpmodule':
                return ['status' => true, 'action' => 'loaddocument', 'msg' => 'Open SO', 'doc' => 'SQ', 'trno' => $config['params']['addedparams'][0], 'docno' => $config['params']['addedparams'][1], 'moduletype' => 'module', 'url' => '/module/sales/'];
                break;
            case 'print1':
                return $this->reportsetup($config);
                //return ['status'=>true,'msg'=>'Please check stockstatusposted ('.$config['params']['action'].')'];
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            case 'donetodo':
                $tablenum = $this->tablenum;
                return $this->othersClass->donetodo($config, $tablenum);
                break;
            case 'uploadexcel':
                return $this->generatewn($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    private function generatewn($config)
    {
        $trno = 0;
        $raw = $config['params']['data'];
        if (!empty($raw)) {
            foreach ($raw as $key => $value) {

                $head = [];

                if (isset($value['Customer Code'])) {
                    if ($value['Customer Code'] == '') {
                        return ['status' => false, 'msg' => 'Please input valid customer code'];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Customer Code is required'];
                }

                if (isset($value['Meter No'])) {
                    if ($value['Meter No'] == '') {
                        return ['status' => false, 'msg' => 'Please input valid meter no'];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Meter No is required'];
                }

                $transdate = null;
                if (isset($value['Trans Date'])) {
                    if ($value['Trans Date'] == '') {
                        return ['status' => false, 'msg' => 'Please input valid Trans Date'];
                    }

                    $transdate = $value['Trans Date'];
                    if ($transdate != '') {
                        if (is_numeric($transdate)) {
                            $UNIX_DATE = ($transdate - 25569) * 86400;
                            $transdate = gmdate("Y-m-d", $UNIX_DATE);
                        }
                    }
                } else {
                    return ['status' => false, 'msg' => 'Trans Date is required'];
                }

                $conndate = null;
                if (isset($value['Connection Date'])) {
                    if ($value['Connection Date'] == '') {
                        return ['status' => false, 'msg' => 'Please input valid Connection Date'];
                    }

                    $conndate = $value['Connection Date'];
                    if ($conndate != '') {
                        if (is_numeric($conndate)) {
                            $UNIX_DATE = ($conndate - 25569) * 86400;
                            $conndate = gmdate("Y-m-d", $UNIX_DATE);
                        }
                    }
                } else {
                    return ['status' => false, 'msg' => 'Connection Date is required'];
                }

                if (!isset($value['Beg Reading'])) {
                    return ['status' => false, 'msg' => 'Please input valid Beg Reading'];
                }

                $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$value['Meter No']], '', true);
                if ($itemid == 0) {
                    return ['status' => false, 'msg' => 'Please setup meter no ' . $value['Meter No'] . ' first'];
                }
                $projectid = $this->coreFunctions->getfieldvalue("item", "projectid", "itemid=?", [$itemid], '', true);

                $client = $this->coreFunctions->getfieldvalue("client", "client", "client=?", [$value['Customer Code']]);
                if ($client == '') {
                    return ['status' => false, 'msg' => 'Please setup customer ' . $client . ' first'];
                }

                $clientname = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$client]);
                $address = $this->coreFunctions->getfieldvalue("client", "addr", "client=?", [$client]);

                $data = [
                    'client' => $client,
                    'clientname' => $clientname,
                    'dateid' => $transdate,
                    'address' => $address,
                    'conndate' => $conndate,
                    'begqty' => $value['Beg Reading'],
                    'itemid' => $itemid,
                    'projectid' => $projectid,
                ];

                foreach ($head as $key => $val) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                }

                $exist = $this->coreFunctions->opentable("select trno from wnhead where disconndate is null and client=? and itemid=? union all select trno from hwnhead where disconndate is null and client=? and itemid=?", [$client, $itemid, $client, $itemid]);
                if (!empty($exist)) {
                    return ['status' => false, 'msg' => 'There is an existing connection for customer ' . $value['Customer Code'] . ' with meter no ' . $value['Meter No']];
                }

                $trno = $this->othersClass->generatecntnum($config, $this->tablenum, 'WN', 'WN');
                if ($trno != -1) {
                    $docno =  $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', "trno=?", [$trno]);

                    $data['trno'] = $trno;
                    $data['doc'] = $config['params']['doc'];
                    $data['docno'] = $docno;
                    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['createby'] = $config['params']['user'];

                    $insert = $this->coreFunctions->sbcinsert($this->head, $data);
                    if ($insert) {
                        $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
                        $this->coreFunctions->sbcupdate('item', ['clientid' => $clientid], ['itemid' => $itemid]);

                        $config['params']['trno'] = $trno;
                        $post = $this->posttrans($config);
                        if (!$post['status']) {
                            return $post;
                        } else {
                        }
                    }
                } else {
                    return ['status' => false, 'msg' => 'Failed to generate transaction no., please advice your system provider'];
                }
            }
        }

        return ['status' => true, 'msg' => $docno . ' successfully created.', 'action' => 'loaddocument', 'trno' => $trno, 'access' => 'view', 'lookupclass' => ''];
    }

    private function disconnect($config)
    {
        $trno = $config['params']['row']['trno'];
        $docno = $config['params']['row']['docno'];
        $clientname = $config['params']['row']['clientname'];

        $isposted = $this->othersClass->isposted2($trno, $this->tablenum);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        if (!$isposted) {
            return ['status' => false, 'msg' => 'Transaction must be posted'];
        }
        $disconn = $this->coreFunctions->getfieldvalue($this->hhead, "disconndate", "trno=?", [$trno]);
        $itemid = $this->coreFunctions->getfieldvalue($this->hhead, "itemid", "trno=?", [$trno]);

        if ($disconn != "" || $disconn != null) {
            return ['status' => false, 'msg' => 'Already disconnected.'];
        }

        $this->coreFunctions->sbcupdate($this->hhead, ['disconndate' =>  $current_timestamp], ['trno' => $trno]);
        $this->coreFunctions->sbcupdate('item', ['clientid' =>  0], ['itemid' => $itemid]);
        $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 76], ['trno' => $trno]);

        $this->logger->sbcwritelog($trno, $config, 'DISCONNECTED', $docno . ' - ' . $clientname);
        return ['status' => true, 'msg' => 'Disconnected successful'];
    }

    public function diagram($config)
    {
        $companyid = $config['params']['companyid'];
        $data = [];
        $nodes = [];
        $links = [];
        $data['width'] = 1500;
        $startx = 100;

        $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hpohead as po
       left join hpostock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from pohead as po
       left join postock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($t)) {
            $startx = 550;
            $a = 0;
            foreach ($t as $key => $value) {
                //PO
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
                array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
                $a = $a + 100;

                if ($companyid == 6) { // mitsukoshi
                    // PL
                    $qry = "select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hplhead as head 
          left join hplstock as s on s.trno = head.trno
          left join hpostock as postock on postock.trno = s.refx and postock.line = s.linex
          left join hpohead as pohead on pohead.trno = postock.trno
          where pohead.trno = ?
          group by head.docno,head.dateid";
                    $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
                    $poref = $t[$key]->docno;
                    if (!empty($x)) {
                        foreach ($x as $key2 => $value) {
                            data_set(
                                $nodes,
                                $x[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => 300,
                                    'y' => 250,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $x[$key2]->docno,
                                    'label' => $x[$key2]->rem,
                                    'color' => 'yellow',
                                    'details' => [$x[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
                        }
                    }
                }

                if (floatval($t[$key]->refx) != 0) {
                    //pr
                    $qry = "select po.docno,left(po.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as po left join hprstock as s on s.trno = po.trno
            where po.trno = ?
            group by po.docno,po.dateid";
                    $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
                    $poref = $t[$key]->docno;
                    if (!empty($x)) {
                        foreach ($x as $key2 => $value) {
                            data_set(
                                $nodes,
                                $x[$key2]->docno,
                                [
                                    'align' => 'left',
                                    'x' => 10,
                                    'y' => 50 + $a,
                                    'w' => 250,
                                    'h' => 80,
                                    'type' => $x[$key2]->docno,
                                    'label' => $x[$key2]->rem,
                                    'color' => 'yellow',
                                    'details' => [$x[$key2]->dateid]
                                ]
                            );
                            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
                            $a = $a + 100;
                        }
                    }
                }
            }
        }

        //RR
        $qry = "
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
      head.trno
      from glhead as head
      left join glstock as stock on head.trno = stock.trno
      left join apledger as ap on ap.trno = head.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno, ap.bal
      union all
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
      head.trno
      from lahead as head
      left join lastock as stock on head.trno = stock.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($t)) {
            data_set(
                $nodes,
                'rr',
                [
                    'align' => 'left',
                    'x' => $startx,
                    'y' => 100,
                    'w' => 250,
                    'h' => 80,
                    'type' => $t[0]->docno,
                    'label' => $t[0]->rem,
                    'color' => 'green',
                    'details' => [$t[0]->dateid]
                ]
            );

            foreach ($t as $key => $value) {
                //APV
                $rrtrno = $t[$key]->trno;
                $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'";
                $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
                if (!empty($apvdata)) {
                    foreach ($apvdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            'apv',
                            [
                                'align' => 'left',
                                'x' => $startx + 400,
                                'y' => 100,
                                'w' => 250,
                                'h' => 80,
                                'type' => $apvdata[$key2]->docno,
                                'label' => $apvdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$apvdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'rr', 'to' => 'apv']);
                        $a = $a + 100;
                    }
                }

                //CV
                if (!empty($apvdata)) {
                    $apv_rr_links = "apv";
                    $apvtrno = $apvdata[0]->trno;
                } else {
                    $apvtrno = $rrtrno;
                    $apv_rr_links = "rr";
                }
                $cvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'
        union all
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'";
                $cvdata = $this->coreFunctions->opentable($cvqry, [$apvtrno, $apvtrno]);
                if (!empty($cvdata)) {
                    foreach ($cvdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            $cvdata[$key2]->docno,
                            [
                                'align' => 'left',
                                'x' => $startx + 800,
                                'y' => 100,
                                'w' => 250,
                                'h' => 80,
                                'type' => $cvdata[$key2]->docno,
                                'label' => $cvdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$cvdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => $apv_rr_links, 'to' => $cvdata[$key2]->docno]);
                        $a = $a + 100;
                    }
                }

                //DM
                $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid";
                $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
                if (!empty($dmdata)) {
                    foreach ($dmdata as $key2 => $value2) {
                        data_set(
                            $nodes,
                            $dmdata[$key2]->docno,
                            [
                                'align' => 'left',
                                'x' => $startx + 400,
                                'y' => 200,
                                'w' => 250,
                                'h' => 80,
                                'type' => $dmdata[$key2]->docno,
                                'label' => $dmdata[$key2]->rem,
                                'color' => 'red',
                                'details' => [$dmdata[$key2]->dateid]
                            ]
                        );
                        array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
                        $a = $a + 100;
                    }
                }
            }
        }

        $data['nodes'] = $nodes;
        $data['links'] = $links;

        return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
    }

    private function updateitemvoid($config)
    {
        $trno = $config['params']['trno'];
        $rows = $config['params']['rows'];

        foreach ($rows as $key) {
            $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
        }
    } //end function

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->openstockline($config);
        $data2 = json_decode(json_encode($data), true);

        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Qty PO is Greater than SO Qty ';
                } else {
                    $msg2 = ' Qty PO is Greater than PR Qty ';
                }
            }
        }

        if (!$isupdate) {
            return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }

    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $isupdate = true;
        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            if ($data2[$key][$this->dqty] == 0) {
                $data[$key]->errcolor = 'bg-red-2';
                $isupdate = false;
                if ($data[$key]->refx == 0) {
                    $msg1 = ' Out of stock ';
                } else {
                    $msg2 = ' Qty Received is Greater than PO Qty ';
                }
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
        }
    } //end function

    public function addallitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $msg = 'Successfully saved.';
            $config['params']['data'] = $value;
            $return = $this->additem('insert', $config);
            if ($return['status'] == false) {
                $msg = $return['msg'];
                break;
            }
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
    } //end function

    public function quickadd($config)
    {
        $barcodelength = $this->companysetup->getbarcodelength($config['params']);
        $config['params']['barcode'] = trim($config['params']['barcode']);
        if ($barcodelength == 0) {
            $barcode = $config['params']['barcode'];
        } else {
            $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
        }
        $wh = $config['params']['wh'];
        $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,famt from item where barcode=?", [$barcode]);
        if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti
            $trno = $config['params']['trno'];
            $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
            if (floatval($forex) != 1) {
                $item = $this->coreFunctions->opentable("select item.itemid,case " . $forex . " when 1 then 0 else famt end as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
            }
        }

        $item = json_decode(json_encode($item), true);
        $defuom = '';
        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
                $item[0]['amt'] = $lprice['data'][0]['amt'];
                $item[0]['disc'] = $lprice['data'][0]['disc'];
            }

            if ($this->companysetup->getisdefaultuominout($config['params'])) {
                $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
                if ($defuom != "") {
                    $item[0]['uom'] = $defuom;
                }
            }

            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
        }
    }

    // insert and update item
    public function additem($action, $config)
    {
        $companyid = $config['params']['companyid'];
        $isproject = $this->companysetup->getisproject($config['params']);
        $uom = $config['params']['data']['uom'];
        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $disc = $config['params']['data']['disc'];
        $wh = $config['params']['data']['wh'];
        $loc = $config['params']['data']['loc'];
        $itemdesc = '';
        $ref = '';
        $void = 'false';
        if (isset($config['params']['data']['void'])) {
            $void = $config['params']['data']['void'];
        }
        if (isset($config['params']['data']['ref'])) {
            $ref = $config['params']['data']['ref'];
        }

        $refx = 0;
        $linex = 0;
        $cdrefx = 0;
        $cdlinex = 0;
        $sorefx = 0;
        $solinex = 0;
        $osrefx = 0;
        $oslinex = 0;
        $rem = '';
        $stageid = 0;
        $projectid = 0;
        $poref = '';
        $sgdrate = 0;
        $ext = 0;

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }
        if (isset($config['params']['data']['linex'])) {
            $linex = $config['params']['data']['linex'];
        }
        if (isset($config['params']['data']['cdrefx'])) {
            $cdrefx = $config['params']['data']['cdrefx'];
        }
        if (isset($config['params']['data']['cdlinex'])) {
            $cdlinex = $config['params']['data']['cdlinex'];
        }

        if (isset($config['params']['data']['stageid'])) {
            $stageid = $config['params']['data']['stageid'];
        }

        if (isset($config['params']['data']['solinex'])) {
            $solinex = $config['params']['data']['solinex'];
        }

        if (isset($config['params']['data']['sorefx'])) {
            $sorefx = $config['params']['data']['sorefx'];
        }

        if (isset($config['params']['data']['oslinex'])) {
            $oslinex = $config['params']['data']['oslinex'];
        }

        if (isset($config['params']['data']['osrefx'])) {
            $osrefx = $config['params']['data']['osrefx'];
        }
        if (isset($config['params']['data']['poref'])) {
            $poref = $config['params']['data']['poref'];
        }

        if (isset($config['params']['data']['itemname'])) {
            $itemdesc = $config['params']['data']['itemname'];
        }

        if (isset($config['params']['data']['sgdrate'])) {
            $sgdrate = $config['params']['data']['sgdrate'];
        } else {
            $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
        }

        if ($companyid == 8) {
            if (isset($config['params']['data']['ext'])) {
                $ext = $config['params']['data']['ext'];
            }
        }

        $line = 0;
        //itemprice
        if ($companyid == 10 || $companyid == 12) {
            $itempriceqry = "select amt from itemprice where itemid = ? and ? between startqty and endqty";
        }

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
            // $qty = round($config['params']['data']['qty'],$this->companysetup->getdecimal('qty', $config['params']));

            if ($companyid == 10 || $companyid == 12) {
                $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
                $itemprice = $this->coreFunctions->opentable($itempriceqry, [$itemid, $qty]);
                if (!empty($itemprice)) {
                    $amt = $itemprice[0]->amt;
                }
            }
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $amt = $config['params']['data'][$this->damt];
            $qty = $config['params']['data'][$this->dqty];
            // $qty = round($config['params']['data'][$this->dqty],$this->companysetup->getdecimal('qty', $config['params']));
            $config['params']['line'] = $line;

            if ($companyid == 10 || $companyid == 12) {
                $projectid = $config['params']['data']['projectid'];
                $itemprice = $this->coreFunctions->opentable($itempriceqry, [$itemid, $qty]);
                if (!empty($itemprice)) {
                    $amt = $itemprice[0]->amt;
                }
            }
        }
        $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
        $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";

        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $factor = 1;
        if (!empty($item)) {
            $item[0]->factor = $this->othersClass->val($item[0]->factor);
            if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }

        $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
        $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
        $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

        switch ($companyid) {
            case 28: // xcomp disc per unit
                $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', 0, 1);
                break;

            default:
                $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
                break;
        }

        if ($companyid <> 8) {
            $ext = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');
        }


        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'rrcost' => $amt,
            'cost' => number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', ''),
            'rrqty' => $qty,
            'qty' => $computedata['qty'],
            'ext' => $ext,
            'disc' => $disc,
            'whid' => $whid,
            'loc' => $loc,
            'uom' => $uom,
            'void' => $void,
            'refx' => $refx,
            'linex' => $linex,
            'cdrefx' => $cdrefx,
            'cdlinex' => $cdlinex,
            'sorefx' => $sorefx,
            'solinex' => $solinex,
            'osrefx' => $osrefx,
            'oslinex' => $oslinex,
            'rem' => $rem,
            'ref' => $ref,
            'stageid' => $stageid
        ];

        if ($companyid == 10 || $companyid == 12) {
            $data['projectid'] = $projectid;
            $data['poref'] = $poref;
            $data['sgdrate'] = $sgdrate;
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        if ($uom == '') {
            $msg = 'UOM cannot be blank -' . $item[0]->barcode;
            return ['status' => false, 'msg' => $msg];
        }

        if ($action == 'insert') {
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            if (isset($config['params']['data']['sortline'])) {
                $data['sortline'] =  $config['params']['data']['sortline'];
            } else {
                $data['sortline'] =  $data['line'];
            }


            if ($isproject) {
                if ($data['stageid'] == 0) {
                    $msg = 'Stage cannot be blank -' . $item[0]->barcode;
                    return ['status' => false, 'msg' => $msg];
                }
            }

            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                switch ($this->companysetup->getsystemtype($config['params'])) {
                    case 'AIMS':
                        if ($companyid == 0 || $companyid == 10 || $companyid == 12) {
                            $stockinfo_data = [
                                'trno' => $trno,
                                'line' => $line,
                                'rem' => $rem
                            ];
                            $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
                        }
                        break;
                    case 'AIMSPOS':
                        if ($companyid == 17) {
                            $stockinfo_data = [
                                'trno' => $trno,
                                'line' => $line,
                                'rem' => $rem,
                                'itemdesc' => $itemdesc
                            ];
                            $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
                        }
                        break;
                }

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext']);
                if ($isproject) {
                    $this->updateprojmngmt($config, $stageid);
                }
                $this->loadheaddata($config);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            if ($isproject) {
                $this->updateprojmngmt($config, $stageid);
            }
            if ($refx != 0) {
                if ($this->setserveditems($refx, $linex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setserveditems($refx, $linex);
                    $return = false;
                }
            }
            if ($cdrefx != 0) {
                if ($this->setservedcanvassitems($cdrefx, $cdlinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedcanvassitems($cdrefx, $cdlinex);
                    $return = false;
                }
            }

            if ($sorefx != 0) {
                if ($this->setservedsoitems($sorefx, $solinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedsoitems($sorefx, $solinex);
                    $return = false;
                }
            }
            if ($sorefx != 0) {
                if ($this->setservedsqitems($sorefx, $solinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedsqitems($sorefx, $solinex);
                    $return = false;
                }
            }

            if ($osrefx != 0) {
                if ($this->setservedositems($osrefx, $oslinex) === 0) {
                    $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                    $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                    $this->setservedositems($osrefx, $oslinex);
                    $return = false;
                }
            }
            return $return;
        }
    } // end function

    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select refx,linex,cdrefx,cdlinex,stageid,sorefx,solinex,osrefx,oslinex from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or sorefx<>0 or osrefx<>0)', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

        foreach ($data as $key => $value) {
            if ($data[$key]->refx != 0) {
                $this->setserveditems($data[$key]->refx, $data[$key]->linex);
            } elseif ($data[$key]->cdrefx != 0) {
                $this->setservedcanvassitems($data[$key]->cdrefx, $data[$key]->cdlinex);
            }

            if (floatval($data[$key]->sorefx) != 0) {
                $this->setservedsoitems($data[$key]->sorefx, $data[$key]->solinex);
                $this->setservedsqitems($data[$key]->sorefx, $data[$key]->solinex);
            }

            if (floatval($data[$key]->osrefx) != 0) {
                $this->setservedositems($data[$key]->osrefx, $data[$key]->oslinex);
            }
            $this->updateprojmngmt($config, $data[$key]->stageid);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $config['params']['stageid'] = $config['params']['row']['stageid'];
        $data = $this->openstockline($config);
        //if(($data[0]->qa == $data[0]->qty)){
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
        if ($data[0]->refx !== 0) {
            $this->setserveditems($data[0]->refx, $data[0]->linex);
        }
        if ($data[0]->cdrefx !== 0) {
            $this->setservedcanvassitems($data[0]->cdrefx, $data[0]->cdlinex);
        }
        if ($data[0]->sorefx !== 0) {
            $this->setservedsoitems($data[0]->sorefx, $data[0]->solinex);
            $this->setservedsqitems($data[0]->sorefx, $data[0]->solinex);
        }
        if ($data[0]->osrefx !== 0) {
            $this->setservedositems($data[0]->osrefx, $data[0]->oslinex);
        }
        $this->updateprojmngmt($config, $config['params']['stageid']);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
        //} else {
        //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
        //}
    } // end function

    public function getcdsummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['cdrefx'] = $data[$key2]->trno;
                    $config['params']['data']['cdlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function



    public function getprsummary($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-(stock.qa+stock.cdqa)) as qty,stock.rrcost,
        round((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid
        FROM hprhead as head left join hprstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and transnum.center=? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $center]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['cdrefx'] = 0;
                    $config['params']['data']['cdlinex'] = 0;
                    $config['params']['data']['stageid'] =  $data[$key2]->stageid;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($companyid == 8) {
                            $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
                        }
                        if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function


    public function getcddetails($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join transnum on transnum.trno=head.trno
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>stock.qa and stock.void=0 and stock.status=1
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['refx'] = 0;
                    $config['params']['data']['linex'] = 0;
                    $config['params']['data']['cdrefx'] = $data[$key2]->trno;
                    $config['params']['data']['cdlinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($companyid == 8) {
                            $this->coreFunctions->sbcupdate($this->head, ['yourref' => $data[0]->docno], ['trno' => $trno]);
                        }
                        if ($this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedcanvassitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }

                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function

    public function getprdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,st.line as stageid,stock.rem,stock.ext
        FROM hprhead as head left join hprstock as stock on stock.trno=head.trno
        left join transnum on transnum.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join stagesmasterfile as st on st.line = stock.stageid where stock.trno = ? and stock.line=? and transnum.center=? and stock.qty>(stock.qa+stock.cdqa) and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line'], $center]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = $data[$key2]->rem;
                    $config['params']['data']['refx'] = $data[$key2]->trno;
                    $config['params']['data']['linex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['ext'] = $data[$key2]->ext;
                    $config['params']['data']['stageid'] =  $data[$key2]->stageid;
                    $return = $this->additem('insert', $config);
                    if ($return['status']) {
                        if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function

    public function setserveditems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $refx . " and hpostock.linex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hprstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedcanvassitems($cdtrno, $cdline)
    {
        $qty = 0;
        $prqty = 0;
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.cdrefx=" . $cdtrno . " and stock.cdlinex=" . $cdline;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.cdrefx=" . $cdtrno . " and hpostock.cdlinex=" . $cdline;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        $prtrno = 0;
        $prline = 0;
        $prtrno = $this->coreFunctions->getfieldvalue('hcdstock', 'refx', 'trno=? and line=?', [$cdtrno, $cdline]);
        if ($prtrno === '') {
            $prtrno = 0;
        }

        if ($prtrno != 0) {
            $prline = $this->coreFunctions->getfieldvalue('hcdstock', 'linex', 'trno=? and line=?', [$cdtrno, $cdline]);
            $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.refx=" . $prtrno . " and stock.linex=" . $prline;

            $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.refx=" . $prtrno . " and hpostock.linex=" . $prline;

            $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
            $prqty = $this->coreFunctions->datareader($qry2);
            if ($prqty === '') {
                $prqty = 0;
            }
            if ($this->coreFunctions->execqry("update hprstock set cdqa=" . $qty . ",qa=" . $prqty . " where trno=" . $prtrno . " and line=" . $prline, 'update') == 1) {
                return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
            } else {
                return 0;
            }
        } else {
            return $this->coreFunctions->execqry("update hcdstock set qa=" . $qty . " where trno=" . $cdtrno . " and line=" . $cdline, 'update');
        }
    } //end func

    public function setservedsoitems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.sorefx=" . $refx . " and hpostock.solinex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hsostock set poqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedsqitems($refx, $linex)
    {
        if ($refx == 0) {
            return 1;
        }
        $qryso = "select stock.iss from lahead as head left join lastock as
  stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

        $qryso = $qryso . " union all select glstock.iss from glhead left join glstock on glstock.trno=
  glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

        $qry = "select ifnull(sum(iss),0) as value from (" . $qryso . ") as t";
        $qtysj = $this->coreFunctions->datareader($qry);
        if ($qtysj == '') {
            $qtysj = 0;
        }

        $qrypo = "select stock." . $this->hqty . " from pohead as head left join postock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qrypo = $qrypo . " union all select stock." . $this->hqty . " from hpohead as head left join hpostock as
  stock on stock.trno=head.trno where head.doc='PO' and stock.sorefx=" . $refx . " and stock.solinex=" . $linex;

        $qry = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qrypo . ") as t";
        $qtypo = $this->coreFunctions->datareader($qry);

        if ($qtypo == '') {
            $qtypo = 0;
        }

        return $this->coreFunctions->execqry("update hqsstock set poqa=" . $qtypo . " where trno=" . $refx . " and line=" . $linex, 'update');
    }

    public function setservedositems($refx, $linex)
    {
        $qry1 = "select stock." . $this->hqty . " from pohead as head left join postock as
    stock on stock.trno=head.trno where head.doc='PO' and stock.osrefx=" . $refx . " and stock.oslinex=" . $linex;

        $qry1 = $qry1 . " union all select hpostock." . $this->hqty . " from hpohead left join hpostock on hpostock.trno=
    hpohead.trno where hpohead.doc='PO' and hpostock.osrefx=" . $refx . " and hpostock.oslinex=" . $linex;

        $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
        $qty = $this->coreFunctions->datareader($qry2);
        if (floatval($qty) == 0) {
            $qty = 0;
        }
        return $this->coreFunctions->execqry("update hosstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }


    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $client = $config['params']['client'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        $forex = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) {
            $qry = "select docno,left(dateid,10) as dateid,case " . $forex . " when 1 then round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") else famt end as amt,disc,uom from(select head.docno,head.dateid,
      stock.rrcost as amt,stock.uom,stock.disc,item.famt
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and head.client = ?
      and stock.rrcost <> 0
      UNION ALL
      select head.docno,head.dateid,stock.rrcost as computeramt,
      stock.uom,stock.disc ,item.famt from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join client on client.clientid = head.clientid
      left join cntnum on cntnum.trno=head.trno
      where head.doc = 'RR' and cntnum.center = ?
      and item.barcode = ? and client.client = ?
      and stock.rrcost <> 0
      order by dateid desc limit 5) as tbl order by dateid desc limit 1";
            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
            if (!empty($data)) {
                return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
            } else {
                return ['status' => false, 'msg' => 'No Latest price found...'];
            }
        } else {
            $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,disc,uom from(select head.docno,head.dateid,
    stock.rrcost as amt,stock.uom,stock.disc
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join cntnum on cntnum.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and head.client = ?
    and stock.rrcost <> 0
    UNION ALL
    select head.docno,head.dateid,stock.rrcost as computeramt,
    stock.uom,stock.disc from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join item on item.itemid = stock.itemid
    left join client on client.clientid = head.clientid
    left join cntnum on cntnum.trno=head.trno
    where head.doc = 'RR' and cntnum.center = ?
    and item.barcode = ? and client.client = ?
    and stock.rrcost <> 0
    order by dateid desc limit 5) as tbl order by dateid desc limit 1";
            $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
            if (!empty($data)) {
                return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
            } else {
                return ['status' => false, 'msg' => 'No Latest price found...'];
            }
        }
    } // end function


    private function updateprojmngmt($config, $stage)
    {
        $trno = $config['params']['trno'];
        $data = $this->openstock($trno, $config);
        $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

        $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as
    stock on stock.trno=head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='PO' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

        $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

        $qty = $this->coreFunctions->datareader($qry2);
        if ($qty === '') {
            $qty = 0;
        }

        $editdate = $this->othersClass->getCurrentTimeStamp();
        $editby = $config['params']['user'];

        return $this->coreFunctions->execqry("update stages set po=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
    }

    public function getsosummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $companyid = $config['params']['companyid'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,item.famt as tpdollar,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    if ($companyid == 10 || $companyid == 12) {
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }

                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getsodetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $companyid = $config['params']['companyid'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.poqa) as iss,stock.isamt,
        round((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,item.famt as tpdollar,head.yourref
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.poqa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['expiry'] = $data[$key2]->expiry;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    if ($companyid == 10 || $companyid == 12) {
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }
                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getsqposummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        $sotrno = 0;
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,so.trno as sotrno,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,item.amt4 as tpphp,head.yourref
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=?
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = '';
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    if ($config['params']['companyid'] == 10) {
                        if (floatval($forex) != 1) {
                            $config['params']['data']['amt'] = $data[$key2]->tpdollar;
                        } else {
                            $config['params']['data']['amt'] = $data[$key2]->tpphp;
                        }
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }

                    if ($config['params']['companyid'] == 12) {
                        $config['params']['data']['amt'] = $data[$key2]->isamt;
                        $config['params']['data']['disc'] = $data[$key2]->disc;
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }

                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                    $sotrno = $data[$key2]->sotrno;
                } // end foreach
                $this->coreFunctions->sbcupdate($this->head, ['sotrno' => $sotrno], ['trno' => $trno]);
            } //end if
        } //end foreach
        $this->loadheaddata($config);
        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
    } //end function

    public function getsqdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,item.famt as tpdollar,head.yourref,item.amt4 as tpphp
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and stock.trno=? and stock.line=?
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = '';
                    $config['params']['data']['qty'] = $data[$key2]->isqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = '';
                    $config['params']['data']['expiry'] = '';
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = 0;
                    if ($config['params']['companyid'] == 10) {
                        if (floatval($forex) != 1) {
                            $config['params']['data']['amt'] = $data[$key2]->tpdollar;
                        } else {
                            $config['params']['data']['amt'] = $data[$key2]->tpphp;
                        }
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }

                    if ($config['params']['companyid'] == 12) {
                        $config['params']['data']['amt'] = $data[$key2]->isamt;
                        $config['params']['data']['disc'] = $data[$key2]->disc;
                        $config['params']['data']['poref'] = $data[$key2]->yourref;
                    }

                    $config['params']['data']['sorefx'] = $data[$key2]->trno;
                    $config['params']['data']['solinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getcriticalstocks($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';

        $data = $config['params']['rows'];

        foreach ($data as $key => $value) {

            $latestcost = $this->othersClass->getlatestcostTS($config, $value['barcode'], '', $config['params']['center'], $trno);
            if ($latestcost['status']) {
                $amt = $latestcost['data'][0]->amt;
            } else {
                $amt = 0;
            }

            $config['params']['data']['uom'] = $value['uom'];
            $config['params']['data']['itemid'] = $value['itemid'];
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = '';
            $config['params']['data']['amt'] = $amt;
            $config['params']['data']['qty'] = $value['reorder'] + $value['sobal'] - $value['pobal'];
            $config['params']['data']['wh'] = $wh;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['ref'] = '';
            $config['params']['data']['loc'] = '';
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
                array_push($rows, $return['row'][0]);
            }
        }

        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    }

    private function autocreatestock($config, $data)
    {
        $trno = $config['params']['trno'];
        $sotrno = $data['sotrno'];
        $wh = $data['wh'];
        $rows = [];
        $msg = '';
        $forex  = $this->coreFunctions->getfieldvalue($this->head, "forex", "trno=?", [$trno]);
        $qry = "select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-stock.poqa) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      item.famt as tpdollar,head.yourref,item.amt4 as tpphp
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.poqa) and stock.void = 0 and stock.iscanvass=0 and so.trno=?
    ";
        $data2 = $this->coreFunctions->opentable($qry, [$sotrno]);
        if (!empty($data2)) {
            foreach ($data2 as $key2 => $value) {
                $config['params']['data']['uom'] = $data2[$key2]->uom;
                $config['params']['data']['itemid'] = $data2[$key2]->itemid;
                $config['params']['trno'] = $trno;
                $config['params']['data']['disc'] = '';
                $config['params']['data']['qty'] = $data2[$key2]->isqty;
                $config['params']['data']['wh'] = $wh;
                $config['params']['data']['loc'] = '';
                $config['params']['data']['expiry'] = '';
                $config['params']['data']['rem'] = '';
                $config['params']['data']['amt'] = 0;
                if ($config['params']['companyid'] == 10) {
                    if (floatval($forex) != 1) {
                        $config['params']['data']['amt'] = $data2[$key2]->tpdollar;
                    } else {
                        $config['params']['data']['amt'] = $data[$key2]->tpphp;
                    }
                    $config['params']['data']['poref'] = $data2[$key2]->yourref;
                }

                if ($config['params']['companyid'] == 12) {
                    $config['params']['data']['amt'] = $data2[$key2]->isamt;
                    $config['params']['data']['disc'] = $data2[$key2]->disc;
                    $config['params']['data']['poref'] = $data2[$key2]->yourref;
                }

                $config['params']['data']['sorefx'] = $data2[$key2]->trno;
                $config['params']['data']['solinex'] = $data2[$key2]->line;
                $config['params']['data']['ref'] = $data2[$key2]->docno;
                $return = $this->additem('insert', $config);

                if ($msg = '') {
                    $msg = $return['msg'];
                } else {
                    $msg = $msg . $return['msg'];
                }

                if ($return['status']) {
                    if ($this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line) == 0) {
                        $datax = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                        $line = $return['row'][0]->line;
                        $config['params']['trno'] = $trno;
                        $config['params']['line'] = $line;
                        $this->coreFunctions->sbcupdate($this->stock, $datax, ['trno' => $trno, 'line' => $line]);
                        $this->setservedsqitems($data2[$key2]->trno, $data2[$key2]->line);
                        $row = $this->openstockline($config);
                        $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                    }
                    array_push($rows, $return['row'][0]);
                }
            } // end foreach
            return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
        } //end if

    }

    public function getossummary($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,item.famt as tpdollar,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['osrefx'] = $data[$key2]->trno;
                    $config['params']['data']['oslinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;

                    $return = $this->additem('insert', $config);

                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }

                    if ($return['status']) {
                        if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function

    public function getosdetails($config)
    {
        $trno = $config['params']['trno'];
        $wh = $config['params']['wh'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.loc,item.famt as tpdollar
        FROM hoshead as head left join hosstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
            if (!empty($data)) {
                foreach ($data as $key2 => $value) {
                    $config['params']['data']['uom'] = $data[$key2]->uom;
                    $config['params']['data']['itemid'] = $data[$key2]->itemid;
                    $config['params']['trno'] = $trno;
                    $config['params']['data']['disc'] = $data[$key2]->disc;
                    $config['params']['data']['qty'] = $data[$key2]->rrqty;
                    $config['params']['data']['wh'] = $wh;
                    $config['params']['data']['loc'] = $data[$key2]->loc;
                    $config['params']['data']['rem'] = '';
                    $config['params']['data']['amt'] = $data[$key2]->rrcost;
                    $config['params']['data']['osrefx'] = $data[$key2]->trno;
                    $config['params']['data']['oslinex'] = $data[$key2]->line;
                    $config['params']['data']['ref'] = $data[$key2]->docno;
                    $return = $this->additem('insert', $config);
                    if ($msg = '') {
                        $msg = $return['msg'];
                    } else {
                        $msg = $msg . $return['msg'];
                    }
                    if ($return['status']) {
                        if ($this->setservedositems($data[$key2]->trno, $data[$key2]->line) == 0) {
                            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                            $line = $return['row'][0]->line;
                            $config['params']['trno'] = $trno;
                            $config['params']['line'] = $line;
                            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                            $this->setservedositems($data[$key2]->trno, $data[$key2]->line);
                            $row = $this->openstockline($config);
                            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
                        }
                        array_push($rows, $return['row'][0]);
                    }
                } // end foreach
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    } //end function


    public function recomputecost($head, $config)
    {
        $data = $this->openstock($head['trno'], $config);
        $data2 = json_decode(json_encode($data), true);
        $exec = true;
        foreach ($data2 as $key => $value) {
            $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
            $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));
            // $kgs = $this->othersClass->sanitizekeyfield('qty', $data2[$key]['kgs']);

            if ($this->companysetup->getvatexpurch($config['params'])) {
                $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, 0, 'P');
            } else {
                $computedata = $this->othersClass->computestock($damt * $head['forex'], $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax'], 'P');
            }


            $exec = $this->coreFunctions->execqry("update postock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
        return $exec;
    }

    // start
    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'showemailbtn' => false];
    }

    public function reportdata($config)
    {
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);

        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    // end


    public function sendemail($config)
    {
        $dataparams = json_decode($config['params']['dataparams']);
        $emailinfo = [
            'email' => 'erick0601@yahoo.com',
            'view' => 'emails.welcome',
            'filename' => 'po',
            'title' => 'Purchase Order',
            'subject' => 'Purchase Order',
            'name' => 'Name 1',
            'pdf' => $config['params']['pdf']
        ];

        return $this->othersClass->sbcsendemail($config, $emailinfo);
    }
} //end class
