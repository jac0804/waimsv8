<?php

namespace App\Http\Classes\modules\rc952c55ab9eb85660b7cab413fa7c803;

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

class pl
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PACKING LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    private $reporter;
    private $helpClass;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];

    public $tablenum = 'transnum';
    public $head = 'plhead';
    public $hhead = 'hplhead';
    public $stock = 'plstock';
    public $hstock = 'hplstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';

    public $dqty = 'rrqty';
    public $hqty = 'qty';
    private $stockselect;

    private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'address', 'shipto', 'waybill', 'amount'];
    private $except = ['trno', 'dateid', 'waybill'];

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
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
            'view' => 1860,
            'edit' => 1861,
            'new' => 1862,
            'save' => 1863,
            // 'change' => 1864, remove change doc
            'delete' => 1865,
            'print' => 1866,
            'lock' => 1867,
            'unlock' => 1868,
            'post' => 1870,
            'unpost' => 1871,
            'additem' => 1872,
            'edititem' => 1873,
            'deleteitem' => 1874
        );
        return $attrib;
    }

    public function createdoclisting()
    {

        $getcols = [
            'action',
            'liststatus',
            'listdocument',
            'listdate',
            'listcreateby',
            'listeditby',
            'listviewby'
        ];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        return $cols;
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
        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }

        return $buttons;
    }
    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'address.label', 'Destination');
        $fields = ['shipto', 'waybill', 'amount'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'waybill.type', 'input');
        data_set($col2, 'waybill.label', 'Waybill No.');
        data_set($col2, 'amount.label', 'Total Carton');

        $fields = ['dateid'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'dateid.label', 'Register Date');

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }
    public function createTab($config)
    {
        $column = ['action', 'docno', 'dateid', 'status', 'amount'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => ['viewdiagram'] // 'viewref', 
            ]
        ];

        $stockbuttons = ['delete']; //'showpackinglist'

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['label'] = 'Inventory';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['showtotal'] = true;

        $obj[0]['inventory']['columns'][$action]['style'] = 'width: 25px;whiteSpace: normal;min-width:25px;max-width:25px';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$docno]['label'] = 'Invoice No.';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$status]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amount]['label'] = 'Total Amount';
        $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:100px;max-width:100px;text-align:center;';

        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = ['pendingsi'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'Invoice';
        return $obj;
    }
    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];

        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $limit = '';
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and head.lockdate is null and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }


        if ($searchfilter == "") $limit = 'limit 150';
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = [
                'head.docno',
                'head.clientname',
                'head.yourref',
                'head.ourref',
                'num.postedby',
                'head.createby',
                'head.editby',
                'head.viewby',
                'head.rem'
            ];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        $qry = "select date(head.dateid) as dateid,head.trno,head.docno,head.clientname, 
        case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end as status, case ifnull(head.lockdate,'') when '' then 'red' else 'green' end as statuscolor,
        head.createby,head.editby,head.viewby,num.postedby
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center = ? and date(head.dateid) between ? and ? " . $condition . " " . $filtersearch . "

        union all

        select date(head.dateid) as dateid,head.trno,head.docno,head.clientname,'POSTED' as status,'grey' as statuscolor,
        head.createby,head.editby,head.viewby, num.postedby
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        where head.doc=? and num.center = ? and date(head.dateid) between ? and ?  " . $condition . " " . $filtersearch . "
        order by dateid desc, docno desc $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }



    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $head = [];
        $tablenum = $this->tablenum;
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);

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

        $select = "select head.trno,head.doc,head.docno,head.dateid,head.trno,
        client.clientid,client.clientname,client.client,head.address,format(head.amount,0) as amount,
        head.shipto,head.waybill
        ";
        $query = $select . " from " . $this->head . " as head 
        left join client on client.client = head.client
        left join " . $this->tablenum . " as num on num.trno = head.trno
        where num.doc = 'PL' and head.trno = ? and num.center=?
        
        union all 
        
        " . $select . "  from " . $this->hhead . " as head
        left join client on client.client = head.client
        left join " . $this->tablenum . " as num on num.trno = head.trno
        where num.doc = 'PL' and head.trno = ? and num.center=?
        ";
        $head = $this->coreFunctions->opentable($query, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = [];
            $clickobj = [];
            $hideobj = [];
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
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
    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['address'] = '';

        $data[0]['waybill'] = '';
        $data[0]['shipto'] = '';
        $data[0]['amount'] = '0';

        return $data;
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
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '');
                }
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
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);

            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    }
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            case 'getsisummary':
                return $this->getinvoice($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getinvoice($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $config['params']['data'] = $value;
            $return = $this->additem('update', $config);
            if ($return['status']) {
                $refx = $return['row'][0]->trno;
                $config['params']['refx'] = $refx;
                $config['params']['trno'] = $trno;
                array_push($rows, $return['row'][0]);
            }
        }
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    } //end function

    public function getstockselect($config)
    {
        $sqlselect = "select head.docno,date(head.dateid) as dateid,sum(stock.ext) as amount,sum(stock.ext) as ext,head.trno as refx ,head.pltrno as trno,'' as bgcolor ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $center = $config['params']['center'];
        $sqlselect = $this->getstockselect($config);
        $qry = $sqlselect . ",'Not Posted' as status
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.pltrno = ? and cntnum.center = ?
        group by head.docno,date(head.dateid),head.trno,head.pltrno
        union all
        " . $sqlselect . " , 'Posted' as status
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.pltrno = ? and cntnum.center = ?
        group by head.docno,date(head.dateid),head.trno,head.pltrno
        ";
        $data = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        return $data;
    }
    public function openstockline($config)
    {
        $trno = $config['params']['trno'];
        $refx = $config['params']['refx'];
        $sqlselect = $this->getstockselect($config);
        $qry = $sqlselect . ",'Not Posted' as status
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.pltrno = '$trno' and head.trno = '$refx'
        group by head.docno,date(head.dateid),head.trno,head.pltrno
        union all
        " . $sqlselect . " , 'Posted' as status
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.pltrno = '$trno' and head.trno = '$refx'
        group by head.docno,date(head.dateid),head.trno,head.pltrno
        ";
        return $this->coreFunctions->opentable($qry);
    } // end function
    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['refx'] = $config['params']['row']['refx'];
        $trno = $config['params']['trno'];
        $data = $this->openstockline($config);

        $table = 'glhead';
        foreach ($data as $key => $value) {
            if ($value->status == 'Not Posted') {
                $table = 'lahead';
            }
            $qry = " update " . $table . " set pltrno = 0 where trno=? ";
            $this->coreFunctions->execqry($qry, 'update', [$value->refx]);

            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - trno:' . $value->refx . ' SI#:' . $value->docno);
        }

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    } // end function
    public function additem($action, $config)
    {
        $return = false;
        $trno = $config['params']['trno'];
        $status = '';
        $refx = 0;
        if (isset($config['params']['data']['trno'])) {
            $refx = $config['params']['data']['trno'];
        }
        if (isset($config['params']['data']['status'])) {
            $status = $config['params']['data']['status'];
        }
        $table = 'glhead';
        if ($status == 'u') { //unposted
            $table = 'lahead';
        }

        $data = [
            'pltrno' => $trno,
        ];
        $config['params']['refx'] = $refx;
        $config['params']['trno'] = $trno;
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        if ($action == 'update') {
            $return = true;
            $this->coreFunctions->sbcupdate($table, $data, ['trno' => $refx]);
            $row = $this->openstockline($config);
            return ['row' => $row, 'status' => true, 'msg' => 'Invoice was successfully added.'];
        }
        return ['status' => $return, 'msg' => ''];
    } // end function


    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
        $modulename = $this->modulename;
        $data = [];
        $isreload = false;
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => $isreload];
    }

    public function reportdata($config)
    {
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for glhead
        $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,amount,shipto,waybill,dateid,createdate,createby,editby,editdate,lockdate,lockuser,viewby,viewdate)
                select head.trno,head.doc, head.docno,head.client, head.clientname,head.address,head.amount,head.shipto,head.waybill,
                head.dateid as dateid,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.viewby,head.viewdate
                FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
                where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    }

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,amount,shipto,waybill,dateid,createdate,createby,editby,editdate,lockdate,lockuser,viewby,viewdate)
                select head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.amount,head.shipto,head.waybill,
                head.dateid as dateid,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.viewby,head.viewdate
                from " . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
                where head.trno=? limit 1";
        $unposthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($unposthead) {
            $data = ['postdate' => null, 'postedby' => ''];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unpost.'];
        } else {
            return ['status' => false, 'msg' => 'Error on Unposting Head'];
        }
    }
    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }
}
