<?php

namespace App\Http\Classes\modules\pos;

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
use Exception;

class pp
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    private $reporter;
    private $helpClass;
    public $modulename = 'Promo Per Item';
    public $gridname = 'inventory';
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'pphead';
    public $hhead = 'hpphead';
    public $stock = 'ppstock';
    public $hstock = 'hppstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    private $fields = ['trno', 'docno', 'dateid', 'due', 'rem', 'branchid', 'yourref', 'ourref', 'isqty', 'isamt', 'isbuy1', 'promobasis', 'isall'];
    private $except = ['trno', 'dateid', 'due'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;

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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5003,
            'edit' => 5004,
            'new' => 5005,
            'save' => 5006,
            'delete' => 5007,
            'print' => 5008,
            'lock' => 5009,
            'unlock' => 5010,
            'post' => 5011,
            'unpost' => 5012,
            'additem' => 5013,
            'edititem' => 5014,
            'deleteitem' => 5015,
            'void' => 5350,
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
            'others'
        );
        $buttons = $this->btnClass->create($btns);
        $buttons['others']['items']['first'] =  ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['prev'] =  ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['next'] = ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']];
        $buttons['others']['items']['last'] = ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']];

        if ($config['params']['companyid'] == 56) { // homeworks
            $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
            $buttons['others']['items']['downloadexcel'] = ['label' => 'Download Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        }
        return $buttons;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'due', 'yourref', 'ourref', 'rem', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$lblstatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';

        $cols[$listdate]['label'] = 'Start';
        $cols[$due]['label'] = 'End';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $itemfilter = $config['params']['itemfilter'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $filtersearch = "";
        $status = "'DRAFT'";
        switch ($itemfilter) {
            case 'draft':
                $condition = " and num.postdate is null and head.lockdate is null ";
                $status = "'DRAFT'";
                break;
            case 'locked':
                $condition = ' and head.lockdate is not null and num.postdate is null ';
                $status = "'LOCKED'";
                break;
            case 'posted':
                $condition = ' and num.postdate is not null';
                $status = "'POSTED'";
                break;
        }
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }


        $query = "select head.trno,head.docno,date(head.dateid) as dateid,date(head.due) as due,head.rem,head.branchid,head.yourref,head.ourref, if(head.lockdate is not null,'LOCKED','DRAFT') as stat,head.createby,head.editby,head.viewby
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            where head.doc = 'PP' and date(head.dateid) between ? and ? " . $filtersearch . $condition . "
            union all
            select head.trno,head.docno,date(head.dateid) as dateid,date(head.due) as due,head.rem,head.branchid,head.yourref,head.ourref, 'POSTED' as stat,head.createby,head.editby,head.viewby
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            where head.doc = 'PP' and date(head.dateid) between ? and ? " . $filtersearch . $condition . " ";
        // var_dump($query, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);

        $data = $this->coreFunctions->opentable($query, [$date1, $date2, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createnewtransaction($docno, $config)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['ourref'] = '';
        $data[0]['yourref'] = '';
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['branchid'] = 0;
        $data[0]['isbuy1'] = '0';
        $data[0]['isqty'] = '1';
        $data[0]['isamt'] = '0';
        $data[0]['rem'] = '';
        $data[0]['promobasis'] = '0';
        $data[0]['isall'] = '0';
        return $data;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', ['dateid', 'due']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Start Date');
        data_set($col1, 'due.label', 'End Date');

        $fields = ['yourref', 'ourref', 'isall'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['isbuy1', 'lblrem', 'promobasis', 'voidtrans'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'lblrem.label', 'Promo Basis');
        data_set($col4, 'voidtrans.access', 'void');

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    public function createTab($access, $config)
    {

        $column = [
            'action',
            'barcode',
            'itemname',
            'start',
            'end',
            'prqty'
        ];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => ['gridcolumns' => $column],
            'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryppbranch', 'label' => 'BRANCH LIST'],
        ];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "Item Code";
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$prqty]['label'] = "Promo Item Count";
        $obj[0][$this->gridname]['descriptionrow'] = [];
        return $obj;
    }

    public function createtab2($access, $config)
    {
        // $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryppbranch', 'label' => 'Branch Lists']];
        // $branch = $this->tabClass->createtab($tab, []);
        // $return['Branch Lists'] = ['icon' => 'fa fa-code-branch', 'tab' => $branch];
        // return $return;

        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
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
        $query = "select head.trno,head.docno,head.dateid,head.due,head.rem,head.branchid,head.yourref,head.ourref, 
        (case when head.isbuy1=1 then '1' else '0' end) as isbuy1,(case when head.isqty=1 then '1' else '0' end) as isqty, 
        (case when head.isamt=1 then '1' else '0' end) as isamt, 0 as promobasis, (case when head.isall=1 then '1' else '0' end) as isall, null as voiddate, 1 as ended
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        where head.trno = ? 
        union all
        select head.trno,head.docno,head.dateid,head.due,head.rem,head.branchid,head.yourref,head.ourref,
        (case when head.isbuy1=1 then '1' else '0' end) as isby1,(case when head.isqty=1 then '1' else '0' end) as isqty, 
        (case when head.isamt=1 then '1' else '0' end) as isamt, 0 as promobasis, (case when head.isall=1 then '1' else '0' end) as isall, 
        head.voiddate, if(head.due>'" . $this->othersClass->getCurrentDate() . "',0,1) as ended
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno 
        where head.trno = ?";
        $head = $this->coreFunctions->opentable($query, [$trno, $trno]);

        $pa_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 5350);
        $hideobj = ['voidtrans' =>  $pa_btnvoid_access == 1 ? false : true];

        if (!empty($head)) {
            
            if ($head[0]->voiddate != null) {
                $hideobj['voidtrans'] = true;
            }

            if ($head[0]->isqty == 1) {
                $head[0]->promobasis = "0";
            }

            if ($head[0]->isamt == 1) {
                $head[0]->promobasis = "1";
            }

            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if
            }
        }
        $isbuy1 = $head['isbuy1'] == 1 ? 'Yes' : 'NO';
        $isqty = $head['promobasis'] == 0 ? 'Yes' : 'NO';
        $isamt = $head['promobasis'] == 1 ? 'Yes' : 'NO';

        if ($head['promobasis'] == 0) {
            $data['isqty'] = 1;
        }

        if ($head['promobasis'] == 1) {
            $data['isamt'] = 1;
        }

        unset($data['promobasis']);

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->logger->sbcwritelog($head['trno'], $config, 'UPDATE', 'Buy 1 Take 1: ' . $isbuy1 . ' Promo Basis: ' . 'Quantity: ' . $isqty . ' Amount: ' . $isamt);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', 'Buy 1 Take 1: ' . $isbuy1 . ' Promo Basis: ' . 'Quantity: ' . $isqty . ' Amount: ' . $isamt);
        }
    } // end function

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join item on item.itemid = stock.itemid
        where stock.trno =?
        UNION ALL
        " . $sqlselect . "
        FROM $this->hstock as stock
        left join item on item.itemid = stock.itemid
        where stock.trno =? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    }

    public function getstockselect($config)
    {
        $sqlselect = "select  stock.line,stock.trno,stock.itemid,item.itemname,item.barcode,item.uom,stock.pqty as prqty,stock.pend as end,stock.pstart as start,'' as bgcolor ";
        return $sqlselect;
    }

    public function openstockline($config)
    {
        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = $sqlselect . "  
        FROM $this->stock as stock
        left join item on item.itemid=stock.itemid
        where stock.trno = ? and stock.line = ? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function getlatestprice($config)
    {
        $barcode = $config['params']['barcode'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];

        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and stock.isamt <> 0
          UNION ALL
          select head.docno,head.dateid,stock.isamt as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ?  and stock.isamt <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $center, $barcode]);

        $usdprice = 0;
        $forex = 1;
        $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
        $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

        if (!empty($data)) {
            return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
        } else {
            $qry = "select amt,disc,uom from item where barcode=?";
            $data = $this->coreFunctions->opentable($qry, [$barcode]);
            if (floatval($forex) <> 1) {
                $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
                if ($cur == '$') {
                    $data[0]->amt = $usdprice;
                } else {
                    $data[0]->amt = round($usdprice * $dollarrate, 2);
                }
            }

            if (floatval($data[0]->amt) == 0) {
                return ['status' => false, 'msg' => 'No Latest price found...'];
            } else {
                return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
            }
        }
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'addallitem': // save all item selected from lookup
                return $this->addallitem($config);
                break;
            case 'quickadd':
                return $this->quickadd($config);
                break;
            case 'deleteitem': // delete per item
                return $this->deleteitem($config);
                break;
            case 'saveitem': //save all item edited
                return $this->updateitem($config);
                break;
            case 'saveperitem':
                return $this->updateperitem($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
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
            case 'uploadexcel':
                return $this->uploadexcel($config);
                break;
            case 'downloadexcel':
                return $this->othersClass->downloadexcel($config);
                break;
            case 'voidtrans':
                return $this->othersClass->voidtransaction($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $pqty = 0.00;
        $pstart = 0.00;
        $pend = 0.00;
        if (isset($config['params']['data']['itemid'])) {
            $itemid = $config['params']['data']['itemid'];
        }
        if (isset($config['params']['data']['uom'])) {
            $uom = $config['params']['data']['uom'];
        }
        if (isset($config['params']['data']['prqty'])) {
            $pqty = $config['params']['data']['prqty'];
        }
        if (isset($config['params']['data']['start'])) {
            $pstart = $config['params']['data']['start'];
        }
        if (isset($config['params']['data']['end'])) {
            $pend = $config['params']['data']['end'];
        }
        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
        }
        $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
        $data = [
            'trno' => $trno,
            'line' => $line,
            'itemid' => $itemid,
            'pqty' => $pqty,
            'pstart' => $pstart,
            'pend' => $pend
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($action == 'insert') {
            $msg = 'Item was successfully added.';
            $data['createdate'] = $current_timestamp;
            $data['createby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
                $row = $this->openstockline($config);
                $this->loadheaddata($config);
                return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $data['editdate'] = $current_timestamp;
            $data['editby'] = $config['params']['user'];
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            return ['status' => $return, 'msg' => ''];
        }
    } // end function

    public function addallitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('insert', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } //end function

    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' ext:' . $data[0]->ext);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function 

    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $this->additem('update', $config);
        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }

    public function quickadd($config)
    {
        $barcodelength = $this->companysetup->getbarcodelength($config['params']);
        $config['params']['barcode'] = trim($config['params']['barcode']);
        if ($barcodelength == 0) {
            $barcode = $config['params']['barcode'];
        } else {
            $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
        }
        $wh = ''; //$config['params']['wh'];

        $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
        $item = json_decode(json_encode($item), true);

        if (!empty($item)) {
            $config['params']['barcode'] = $barcode;
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
                $item[0]['amt'] = $lprice['data'][0]['amt'];
                $item[0]['disc'] = $lprice['data'][0]['disc'];
            }

            $config['params']['data'] = $item[0];
            return $this->additem('insert', $config);
        } else {
            return ['status' => false, 'msg' => 'Barcode not found.', ''];
        }
    }

    public function updateitem($config)
    {
        foreach ($config['params']['row'] as $key => $value) {
            $config['params']['data'] = $value;
            $this->additem('update', $config);
        }
        $data = $this->openstock($config['params']['trno'], $config);
        return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } //end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - All items');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        //for head
        $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, due, rem, branchid, yourref, ourref, issm, isqty, isamt, isbuy1, createdate, createby, editby, editdate, viewby, viewdate, lockuser, lockdate, isall)
        select head.trno, head.doc, head.docno, head.dateid, head.due, head.rem, head.branchid, head.yourref, head.ourref, head.issm, head.isqty, head.isamt, head.isbuy1, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.lockuser, head.lockdate, head.isall 
        from " . $this->head . " as head
        where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            // for stock
            $qry = "insert into " . $this->hstock . "(trno, line, itemid, pstart, pqty, pend, createdate, createby, editby, editdate)
        select stock.trno, stock.line, stock.itemid, stock.pstart, stock.pqty, stock.pend, stock.createdate, stock.createby, stock.editby, stock.editdate from " . $this->stock . " as stock where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
            }
            //if($posthead){      
        } else {
            return ['status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting is not applicable on this transaction'];

        // $trno = $config['params']['trno'];
        // $user = $config['params']['user'];
        // $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        // $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, due, rem, branchid, yourref, ourref, issm, isqty, isamt, isbuy1, createdate, createby, editby, editdate, viewby, viewdate, lockuser, lockdate, isall)
        // select head.trno, head.doc, head.docno, head.dateid, head.due, head.rem, head.branchid, head.yourref, head.ourref, head.issm, head.isqty, head.isamt, head.isbuy1, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.lockuser, head.lockdate, head.isall
        // from " . $this->hhead . " as head
        // where head.trno=? limit 1";
        // //for head
        // $unposthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        // if ($unposthead) {
        //     $qry = "insert into " . $this->stock . "(trno, line, itemid, pstart, pqty, pend, createdate, createby, editby, editdate)
        //     select stock.trno, stock.line, stock.itemid, stock.pstart, stock.pqty, stock.pend, stock.createdate, stock.createby, stock.editby, stock.editdate from " . $this->hstock . " as stock where trno =?";
        //     //for stock
        //     if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //         $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        //         $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        //         $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        //         $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        //         return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        //     } else {
        //         $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        //         return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
        //     }
        // }
    } //end function

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno <? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function uploadexcel($config)
    {
        $rawdata = $config['params']['data'];
        $trno = $config['params']['dataparams']['trno'];
        $msg = '';
        $status = true;
        $uniquefield = "itemcode";

        if ($trno == 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
        }

        foreach ($rawdata as $key => $value) {
            try {
                $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key][$uniquefield] . "'");
                if ($itemid == '') {
                    $status = false;
                    $msg .= 'Failed to upload. ' . $rawdata[$key][$uniquefield] . ' does not exist. ';
                    continue;
                }

                $uom_exist = $this->coreFunctions->getfieldvalue("uom", "uom", "itemid = " . $itemid);
                if ($uom_exist == '') {
                    $msg .= 'Failed to upload ' . $rawdata[$key][$uniquefield] . ' uom does not exist. ';
                    continue;
                }

                $config['params']['trno'] = $trno;
                $config['params']['data']['itemid'] = $itemid;
                $config['params']['data']['uom'] =  $uom_exist;
                $config['params']['data']['start'] =  $rawdata[$key]['startqty'];
                $config['params']['data']['end'] =  $rawdata[$key]['endqty'];
                $config['params']['data']['prqty'] =  $rawdata[$key]['promocountqty'];
                $return = $this->additem('insert', $config);
                if (!$return['status']) {
                    $status = false;
                    $msg .= 'Failed to upload. ' . $return['msg'];
                    goto exithere;
                }
            } catch (Exception $e) {
                $status = false;
                $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
                goto exithere;
            }
        }

        exithere:
        if ($msg == '') {
            $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
            $msg = 'Successfully uploaded.';
        }

        if (!$status) {
            $this->coreFunctions->execqry("delete from pastock where trno=" . $trno);
        }

        return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
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
        $companyid = $config['params']['companyid'];
        $dataparams = $config['params']['dataparams'];
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
    }
} //end class
