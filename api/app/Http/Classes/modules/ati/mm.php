<?php

namespace App\Http\Classes\modules\ati;

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
use App\Http\Classes\sqlquery;

class mm
{
    private $sqlquery;
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Merging Barcode';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $helpClass;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'mmhead';
    public $hhead = 'hmmhead';
    public $stock = 'mmstock';
    public $hstock = 'hmmstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    private $fields = [
        'trno', 'docno', 'dateid', 'rem', 'itemid'
    ];
    private $otherfield = [];
    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    public $rowperpage = 0;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'forposting', 'label' => 'For Posting', 'color' => 'red'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
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
        $this->sqlquery = new sqlquery;
    }
    public function getAttrib()
    {
        $attrib = array(
            'view' => 4437,
            'edit' => 4438,
            'new' => 4439,
            'save' => 4440,
            'delete' => 4441,
            'print' => 4442,
            'lock' => 4443,
            'unlock' => 4444,
            'post' => 4445,
            'unpost' => 4446,
            'additem' => 4447,
            'deleteitem' => 4448,
            'edititem' => 4450
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
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        $buttons['post']['confirm'] = true;
        $buttons['post']['confirmlabel'] = 'Are you sure you want to merge?';
        return $buttons;
    }
    public function createdoclisting($config)
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $itemname = 4;
        $specs = 5;
        $category = 6;
        $subcategory = 7;
        $postdate = 8;
        $rem = 9;
        $listpostedby = 10;
        $listeditby = 11;
        $listviewby = 12;
        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'itemname', 'specs', 'category', 'subcat_name', 'postdate', 'rem', 'listpostedby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$category]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$postdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listpostedby]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$itemname]['type'] = 'input';
        $cols[$itemname]['label'] = 'Item Name';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }
    public function createHeadField($config)
    {
        $fields = ['docno', 'barcode', 'othcode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'barcode.lookupclass', 'lookupitem');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'barcode.required', true);
        data_set($col1, 'barcode.cleartxt', true);

        data_set($col1, 'othcode.label', 'BARCODE NAME');
        data_set($col1, 'othcode.class', 'csbarcode sbccsreadonly');
        $fields = ['dateid', 'itemname', 'uom'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'itemname.label', 'ITEMNAME NAME');
        data_set($col2, 'itemname.type', 'cinput');
        data_set($col2, 'itemname.class', 'csitem sbccsreadonly');
        data_set($col2, 'uom.label', 'UOM');
        data_set($col2, 'uom.type', 'cinput');
        data_set($col2, 'uom.class', 'csuom sbccsreadonly');
        $fields = ['shortname'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'shortname.label', 'SPECS');
        data_set($col3, 'shortname.class', 'csshortname sbccsreadonly');
        data_set($col3, 'shortname.type', 'textarea');
        data_set($col3, 'shortname.maxlength', 1000);
        $fields = ['rem', 'forposting'];
        $col4 = $this->fieldClass->create($fields);
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }
    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];

        $search = $config['params']['search'];
        $condition = "";
        $limit = "limit 150";
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'cat.name', 'subcat.name', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'item.itemname', 'item.shortname'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search, true);
            }
            $limit = "";
        }
        $status = "(case when head.lockdate is not null and num.postdate is null then 'Locked'
                when num.postdate is not null then 'Posted' when num.postdate is null and head.lockdate is null and num.statid = 39 then 'For Posting' else 'DRAFT' end)";
        switch ($itemfilter) {
            case 'draft':
                $status = "ifnull(stat.status,'DRAFT')";
                $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=0';
                break;

            case 'locked':
                $condition .= ' and head.lockdate is not null and num.postdate is null ';
                $status = "'Locked'";
                break;

            case 'posted':
                $condition .= ' and num.postdate is not null ';
                $status = "'Posted'";
                break;
            case 'forposting':
                $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=39';
                $status = "stat.status";
                break;
        }
        $qry = "select head.trno,item.itemid,head.docno,item.itemname,num.postdate,head.editby,num.postedby,head.viewby,
        left(head.dateid,10) as dateid,item.shortname as specs,cat.name as category, subcat.name as subcatname,head.rem,item.uom,$status as stat
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno = head.trno
        left join item on item.itemid = head.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join trxstatus as stat on stat.line=num.statid
        where head.doc = ? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
        union all
        select head.trno,item.itemid,head.docno,item.itemname,num.postdate,head.editby,num.postedby,head.viewby,
        left(head.dateid,10) as dateid,item.shortname as specs,cat.name as category,subcat.name as subcatname,head.rem,item.uom,$status as stat
        from " . $this->hhead . " as head
        left join " . $this->tablenum . " as num on num.trno = head.trno
        left join item on item.itemid = head.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        left join trxstatus as stat on stat.line=num.statid
        where head.doc = ? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
        order by dateid desc,docno desc " . $limit;

        $data = $this->coreFunctions->opentable($qry, [$doc, $date1, $date2, $doc, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function createTab($access, $config)
    {
        $action = 0;
        $barcode = 1;
        $othcode = 2;
        $itemname = 3;
        $shortcode = 4;
        $uom = 5;
        $category = 6;
        $subcategory = 7;
        $genericitem = 8;
        $tab = [
            $this->gridname => [
                'gridcolumns' => [
                    'action', 'barcode', 'othcode', 'itemname', 'specs', 'uom', 'category', 'subcat_name', 'isgeneric'
                ]
            ]

        ];
        $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'viewheaduominfo', 'access' => 'view'], 'label' => 'LIST OF UOM'];
        $stockbuttons = ['delete', 'viewuominfo'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$othcode]['style'] = 'width: 300px;whiteSpace: normal;min-width:100px;max-width:300px';
        $obj[0]['inventory']['columns'][$othcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 0px;whiteSpace: normal;min-width:0px;max-width:0px';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 300px;whiteSpace: normal;min-width:100px;max-width:300px';
        // $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$category]['style'] = 'width: 300px;whiteSpace: normal;min-width:100px;max-width:300px';
        $obj[0]['inventory']['columns'][$category]['type'] = 'label';
        $obj[0]['inventory']['columns'][$uom]['style'] = 'width: 0px;whiteSpace: normal;min-width:100px;max-width:0px';
        $obj[0]['inventory']['columns'][$uom]['type'] = 'label';
        $obj[0]['inventory']['columns'][$subcategory]['style'] = 'width: 300px;whiteSpace: normal;min-width:100px;max-width:300px';
        $obj[0]['inventory']['columns'][$subcategory]['type'] = 'label';
        $obj[0]['inventory']['columns'][$shortcode]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
        $obj[0]['inventory']['columns'][$shortcode]['label'] = 'Specs';
        $obj[0]['inventory']['columns'][$genericitem]['type'] = 'label';

        // $obj[0]['inventory']['columns'][$barcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = ['itemlookup', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'Stockcard';
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
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $statid = $this->othersClass->getstatid($config);
        $head = [];
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;
        $query = "select num.center,
         head.trno,
         head.docno,
         left(head.dateid,10) as dateid,item.itemid,item.barcode,item.othcode,
         item.itemname,item.shortname,item.uom,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem";

        $qry = $query . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join item on item.itemid = head.itemid
        where head.trno = ? and num.center = ?
        union all " . $query . "  from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join item on item.itemid = head.itemid
          where head.trno = ? and num.center= ?";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $hideobj = [];
            $hideobj['forposting'] = false;
            switch ($statid) {
                case 39:
                    $hideobj['forposting'] = true;
                    break;
                default:
                    if ($isposted) {
                        $hideobj['forposting'] = true;
                    }
                    break;
            }
            //, 'islocked' => $islocked, 'isposted' => $isposted,
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
        $data[0]['itemid'] = 0;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['itemname'] = '';
        $data[0]['barcode'] = '';
        $data[0]['othcode'] = '';
        $data[0]['shortname'] = '';
        $data[0]['uom'] = '';
        $data[0]['rem'] = '';

        return $data;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
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
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['itemname'] . ' - ' . $head['rem']);
        }
    }
    private function getstockselect($config)
    {
        $sqlselect =
            "select 
        stock.trno, 
           stock.line, 
        stock.itemid,
        stock.barcode,
        item.itemname,
        item.uom,
        stock.othcode,
        item.shortname as specs,
cat.name as category,
subcat.name as subcategory,
        stock.rem,
        (case when item.isgeneric = 1 then 'YES' else 'NO' end) as isgeneric,
         '' as bgcolor";
        return $sqlselect;
    }
    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join item on item.itemid=stock.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        where stock.trno =?
        UNION ALL
        " . $sqlselect . "
        FROM $this->hstock as stock
        left join item on item.itemid=stock.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
        where stock.trno =? ";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    }
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        $stock = $this->coreFunctions->datareader("select count(trno) as value from " . $this->stock . " where trno=?", [$config['params']['trno']], '', true);

        if ($stock == 0) {
            return ['status' => false, 'msg' => 'Unable to post, Please add item/s first.'];
        }
        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }

        $checkuom = $this->checkuom($config);
        if (!$checkuom['status']) {
            return $checkuom;
        }

        $statid = $this->othersClass->getstatid($config);
        if ($statid != 39) {
            return ['status' => false, 'msg' => 'Posting failed, Kindly tag this transaction as For Posting first.'];
        }

        $qry = "select stock.itemid,stock.editby,stock.editdate,item.uom from mmstock as stock left join item on item.itemid = stock.itemid  where stock.trno = ?";
        $stock = $this->coreFunctions->opentable($qry, [$trno]);
        $headitemid = $this->coreFunctions->datareader('select itemid as value from ' . $this->head . ' where trno=?', [$trno]);
        foreach ($stock as $key => $value) {
            $item = ['itemid' => $headitemid, 'editdate' =>  $value->editdate, 'editby' => $value->editby];

            $this->coreFunctions->execqry("update prstock as s left join stockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update hprstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('prstock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('hprstock', $item, ['itemid' => $value->itemid]);


            $this->coreFunctions->execqry("update cdstock as s left join stockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update hcdstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('cdstock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('hcdstock', $item, ['itemid' => $value->itemid]);

            $this->coreFunctions->execqry("update oqstock as s left join stockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update hoqstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('oqstock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('hoqstock', $item, ['itemid' => $value->itemid]);

            $this->coreFunctions->execqry("update omstock as s left join stockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update homstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('omstock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('homstock', $item, ['itemid' => $value->itemid]);

            $this->coreFunctions->execqry("update postock as s left join stockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update hpostock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('postock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('hpostock', $item, ['itemid' => $value->itemid]);

            $this->coreFunctions->execqry("update lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->execqry("update glstock as s left join hstockinfo as info on info.trno=s.trno and info.line=s.line set info.olditemid=" . $value->itemid . " where s.itemid=?", 'update', [$value->itemid]);
            $this->coreFunctions->sbcupdate('lastock', $item, ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('glstock', $item, ['itemid' => $value->itemid]);

            $this->coreFunctions->sbcupdate('rrstatus', ['itemid' => $headitemid, 'uom' => $value->uom], ['itemid' => $value->itemid]);
            $this->coreFunctions->sbcupdate('costing', ['itemid' => $headitemid], ['itemid' => $value->itemid]);

            $datauom = $this->coreFunctions->opentable("select uom, factor from uom where itemid=" . $value->itemid);
            foreach ($datauom as $key2 => $value2) {
                $exist = $this->coreFunctions->datareader("select uom as value from uom where uom='" . $value2->uom . "' and itemid=" . $headitemid);
                if ($exist == '') {
                    $this->coreFunctions->execqry("insert into uom (itemid,uom,factor) values (" . $headitemid . ",'" . $value2->uom . "'," . $value2->factor . ")");
                }
            }

            $this->coreFunctions->sbcupdate('item', ['isinactive' => 1, 'editdate' =>  $value->editdate, 'editby' => $value->editby], ['itemid' => $value->itemid]);
        }

        $this->coreFunctions->sbcupdate("item", ['mmtrno' => $trno], ['itemid' => $headitemid]);

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,itemid,
      rem,createdate,createby,editby,editdate,lockdate,lockuser)
      SELECT head.trno,head.doc, head.docno,
      head.dateid as dateid,head.itemid,head.rem,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser
      FROM " . $this->head . " as head left join transnum as num on num.trno=head.trno
      where head.trno=? limit 1";

        $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        $qry2 = "insert into " . $this->hstock . "(trno,itemid,line,barcode,othcode,
        encodeddate,encodedby,editdate,editby,rem)
        SELECT trno, itemid,line,barcode,othcode,
        encodeddate,encodedby,editdate,editby,rem
        FROM " . $this->stock . " where trno =?";
        if ($this->coreFunctions->execqry($qry2, 'insert', [$trno])) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
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
    }

    public function checkuom($config)
    {
        $msg = '';
        $trno = $config['params']['trno'];
        $stock = $this->coreFunctions->opentable("select s.itemid, item.barcode, item.itemname from mmstock as s left join item on item.itemid=s.itemid where s.trno=" . $trno);

        $headuom = $this->coreFunctions->opentable("select uom.uom, uom.factor from mmhead as h left join uom on uom.itemid=h.itemid where h.trno=" . $trno);
        foreach ($stock as $key => $value) {

            foreach ($headuom as $key2 => $value2) {

                $bodyuom = $this->coreFunctions->opentable("select uom, factor from uom where itemid=" . $value->itemid . " and uom='" . $value2->uom . "'");
                if (!empty($bodyuom)) {
                    if ($bodyuom[0]->factor != $value2->factor) {
                        $msg = 'Please check item ' . $value->itemname . ' UOM ' . $bodyuom[0]->uom . ' factor ' . $bodyuom[0]->factor . ' is not the same with header item factor ' . $value2->factor;
                        return ['status' => false, 'msg' => $msg];
                    }
                }
            }
        }

        return ['status' => true, 'msg' => $msg];
    }

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        return ['trno' => $trno, 'status' => true, 'msg' => 'Not Allow to Unpost Transaction'];
    } //end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            case 'fiitemlookup':
                return $this->fiitemlookup($config);
                break;
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'saveitem': //save all item
                return $this->updateitem($config);
                break;

                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {

            case 'forposting':
                return $this->forposting($config);
                break;
            case 'navigation':
                return $this->othersClass->navigatedocno($config);
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function fiitemlookup($config)
    {
        $rows = [];
        $msg = '';
        $countitem = 0;
        $trno = $config['params']['trno'];
        foreach ($config['params']['rows'] as $key => $value) {
            $config['params']['trno'] = $trno;
            $config['params']['data'] = $value;
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            } else {
                $msg .= $return['msg'];
            }
        }
        if ($msg == '') {
            $msg = 'Successfully saved.';
        }

        return ['row' => $rows, 'status' => true, 'msg' => $msg, 'count' => $countitem];
    }

    public function additem($action, $config)
    {

        $itemid = $config['params']['data']['itemid'];
        $trno = $config['params']['trno'];
        $othcode = $config['params']['data']['othcode'];
        $barcode = $config['params']['data']['barcode'];

        $data = [
            'trno' => $trno,
            'itemid' => $itemid,
            'othcode' => $othcode,
            'barcode' =>  $barcode,
        ];

        foreach ($data as $key => $value1) {

            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }

        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno, $itemid]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $data['line'] = $line;
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            $config['params']['line'] = $line;
            $data['trno'] = $trno;
            $item = ['mmtrno' => $data['trno']];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->coreFunctions->sbcupdate('item', $item, ['itemid' => $itemid]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - itemid:' . $itemid);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $result = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'itemid' => $itemid]);
            return $result;
        }
    }
    public function openstockline($config)
    {
        $sqlselect = $this->getstockselect($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = $sqlselect . "
   FROM  $this->stock  as stock
     left join item on item.itemid=stock.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
   where stock.trno = ? and stock.line = ?
   union all  " . $sqlselect . " FROM  $this->hstock  as stock
     left join item on item.itemid=stock.itemid
        left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat
   where stock.trno = ? and stock.line = ? ";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
        return $stock;
    }

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

        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function deleteitem($config)
    {
        $trno = $config['params']['row']['trno'];
        $itemid = $config['params']['row']['itemid'];
        $mmrtno = ['mmtrno' => 0];
        $itemid2 = $this->coreFunctions->getfieldvalue($this->stock, 'itemid', 'trno=? and itemid=?', [$trno, $itemid]);
        $qry = "delete from " . $this->stock . " where trno=? and itemid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $itemid2]);
        $this->coreFunctions->sbcupdate('item', $mmrtno, ['itemid' => $itemid]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Itemid:' . $itemid);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    }
    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $stock = $this->openstock($trno, $config);
        $mmrtno = ['mmtrno' => 0];
        foreach ($stock as $key => $value) {
            $itemid = $value->itemid;
            $line = $value->line;
            $itemid2 = $this->coreFunctions->getfieldvalue($this->stock, 'itemid', 'trno=? and itemid = ? and line = ?', [$trno, $itemid, $line]);
            $qry =  "delete from " . $this->stock . " where trno=? and itemid = ?";
            $this->coreFunctions->execqry($qry, 'delete', [$trno, $itemid2]);
            $this->coreFunctions->sbcupdate('item', $mmrtno, ['itemid' => $itemid]);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
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
            if ($data2[$key]['barcode'] == '') {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = 'Barcode is empty';
            } else if ($data2[$key]['othcode'] = '') {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg2 = 'Barcode Name is empty';
            }
        }
        if ($isupdate) {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' =>  'Please check Item (' . $msg1 . ' / ' . $msg2 . ')'];
        }
    }
    public function forposting($config)
    {
        $trno = $config['params']['trno'];
        $msg = "";
        $status = true;

        if ($this->othersClass->isposted2($trno, $this->tablenum)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Already posted.'];
        }

        $stock = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=?", [$trno]);
        if (empty($stock)) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Can`t proceed, must have valid items.'];
        }
        //checking of approved qty
        $stock = $this->coreFunctions->opentable("select itemid,line,barcode,othcode from " . $this->stock . " as stock where stock.trno=?", [$trno]);
        foreach ($stock as $key => $value) {
            if ($value->barcode == '') {
                return ['status' => false, 'msg' => 'Please check stocks; Barcode is empty.'];
            }
            if ($value->othcode == '') {
                return ['status' => false, 'msg' => 'Please check stocks; Barcode Name is empty.'];
            }
        }

        $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $trno]);
        $this->logger->sbcwritelog($trno, $config, 'HEAD', 'DONE CHECKING');

        return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
    }
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
        $this->logger->sbcviewreportlog($config);

        $dataparams = $config['params']['dataparams'];

        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
