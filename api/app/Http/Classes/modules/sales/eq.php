<?php

namespace App\Http\Classes\modules\sales;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\lookup\hrislookup;
use Symfony\Component\VarDumper\VarDumper;

class eq
{

    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'EQUIPMENT MONITORING';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'transnum';
    public $head = 'eqhead';
    public $hhead = 'heqhead';
    public $stock = 'eqstock';
    public $hstock = 'heqstock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';

    private $fields = [
        'trno', 'docno', 'dateid', 'empid', 'itemid', 'projectid', 'opincentive', 'whid', 'rem'
    ];
    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'lock', 'label' => 'Lock', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'All', 'color' => 'primary'],
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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4545,
            'edit' => 4546,
            'new' => 4547,
            'save' => 4548,
            'delete' => 4549,
            'print' => 4550,
            'lock' => 4551,
            'unlock' => 4552,
            'post' => 4553,
            'unpost' => 4554,
            'additem' => 4555,
            'edititem' => 4556,
            'deleteitem' => 4557
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
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton 

    public function createHeadField($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $companyid = $config['params']['companyid'];

        $fields = ['docno', 'empcode', 'empname', 'barcode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        data_set($col1, 'client.required', false);
        data_set($col1, 'barcode.label', 'Truck/Asset Code');
        data_set($col1, 'barcode.lookupclass', 'lookupitem');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'barcode.required', true);
        data_set($col1, 'barcode.cleartxt', true);

        $fields = ['dateid', 'dwhname', 'itemname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'itemname.label', 'Truck/Asset');
        data_set($col2, 'itemname.type', 'cinput');
        data_set($col2, 'itemname.class', 'csitem sbccsreadonly');
        $fields = ['project', 'opincentive'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'project.class', 'csproject sbccsreadonly');
        $fields = ['rem'];
        $col4 = $this->fieldClass->create($fields);
        return [
            'col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4
        ];
    }
    public function createTab($access, $config)
    {

        $action = 0;
        $category = 1;
        $starttime = 2;
        $endtime = 3;
        $duration = 4;
        $odostart = 5;
        $odoend = 6;
        $distance = 7;
        $fuelcomsumption = 8;


        $tab = [
            $this->gridname => [
                'gridcolumns' => [
                    'action', 'category', 'starttime', 'endtime', 'duration', 'odostart', 'odoend', 'distance', 'fuelconsumption'
                ],
            ]
        ];


        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
        $obj[0][$this->gridname]['columns'][$starttime]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$endtime]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$starttime]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$endtime]['readonly'] = false;
        $obj[0][$this->gridname]['columns'][$duration]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$duration]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$distance]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$distance]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$category]['type'] = 'label';
        $obj[0][$this->gridname]['totalfield'] = 'fuelconsumption';
        $obj[0][$this->gridname]['descriptionrow'] = [];

        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = ['activitymaster', 'saveitem', 'deleteallitem'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {
        $action = 0;
        $lblstatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $empname = 4;
        $rem = 5;
        $postdate = 6;
        $listpostby = 7;
        $createdate = 8;
        $listcreateby = 9;
        $listesitby = 10;
        $listviewby = 11;

        $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'empname', 'rem', 'postdate', 'listpostedby', 'createdate', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$listdocument]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$empname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:100px; max-width:200px;';
        $cols[$rem]['readonly'] = true;
        $cols[$postdate]['label'] = 'Post Date';
        return $cols;
    }
    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $adminid = $config['params']['adminid'];
        $center = $config['params']['center'];
        $condition = '';
        $limit = "limit 150";
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'wh.clientname', 'head.', 'cl.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }
        $orderby = "order by docno desc";
        $status = "(case when head.lockdate is not null and num.postdate is null then 'Locked'
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
        $qry = "select head.trno,head.doc,head.docno,cl.clientname as empname , cl.client as empcode,wh.clientname ,head.rem,
        $status as stat,
        left(head.dateid,10) as dateid,date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby,num.postedby,left(head.createdate,10)  as createdate
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join employee as emp on emp.empid = head.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as wh on wh.clientid = head.whid

        where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
        or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . "
        union all
        select head.trno,head.doc,head.docno,cl.clientname as empname , cl.client as empcode,wh.clientname ,head.rem,
        $status as stat,
        left(head.dateid,10) as dateid, date(num.postdate) as postdate,
        head.createby,head.editby,head.viewby,num.postedby,left(head.createdate,10)  as createdate
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join employee as emp on emp.empid = head.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as wh on wh.clientid = head.whid 
        where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
        or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . " $orderby  $limit ";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $date1, $date2, $doc, $center, $date1, $date2, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
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

        $qryselect =
            "select head.trno,head.doc,head.docno,left(head.dateid,10) as dateid,cl.clientname as empname , ifnull(cl.clientid,0) as empid,head.opincentive,
         cl.client as empcode,wh.clientname as whname ,wh.client as wh,ifnull(item.barcode,'') as barcode,item.itemname,item.itemid,wh.clientid as whid,ifnull(project.line,0) as projectid,
         project.name as project,head.rem,date_format(head.createdate,'%Y-%m-%d') as createdate";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join eqstock as stock on stock.trno = head.trno
        left join item on item.itemid = head.itemid
        left join employee as emp on emp.empid = head.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as wh on wh.clientid = head.whid
        left join projectmasterfile as project on project.line = head.projectid
         where head.trno = ? and num.center=? 
        union all 
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join heqstock as stock on stock.trno = head.trno
        left join item on item.itemid = head.itemid
        left join employee as emp on emp.empid = head.empid
        left join client as cl on cl.clientid = emp.empid
        left join client as wh on wh.clientid = head.whid
       left join projectmasterfile as project on project.line = head.projectid
         where head.trno = ? and num.center=? ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }
    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['empid'] = 0;
        $data[0]['empcode'] = '';
        $data[0]['empname'] = '';
        $data[0]['whid'] = 0;
        $data[0]['whname'] = '';
        $data[0]['wh'] = '';
        $data[0]['pproject'] = '';
        $data[0]['projectcode'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['itemid'] = 0;
        $data[0]['itemname'] = '';
        $data[0]['barcode'] = '';
        $data[0]['opincentive'] = '0.00';
        $data[0]['rem'] = '';
        return $data;
    }
    private function getstockselect($config)
    {
        $sqlselect =
            "select  cat.category,stock.line,stock.trno,stock.activityid,
           if(stock.starttime <> '' ,time_format(time(stock.starttime) , '%H:%i' ) ,'" . date("H:i", strtotime('00:00')) . "') as starttime , 
           if(stock.endtime <> '' ,time_format(time(stock.endtime) , '%H:%i' ),'" . date("H:i", strtotime('00:00')) . "')  as endtime,stock.duration,format(stock.odostart,0) as odostart,format(stock.odoend,0) as odoend,stock.distance,stock.fuelconsumption,
         '' as bgcolor";
        return $sqlselect;
    }
    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
        FROM $this->stock as stock
        left join reqcategory as cat on cat.line = stock.activityid
        where stock.trno =?
        UNION ALL
        " . $sqlselect . "
        FROM $this->hstock as stock
        left join reqcategory as cat on cat.line = stock.activityid
        where stock.trno =? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
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

        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['empid'] . ' - ' . $head['empname']);
        }
    }
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {

            case 'deleteitem':
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
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'getactivitymaster':
                return $this->getactivity($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function getactivity($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $msg = '';
        foreach ($config['params']['rows'] as $key2 => $value) {
            $config['params']['trno'] = $trno;
            $config['params']['data']['activityid'] = $value['line'];
            $config['params']['data']['duration'] = $value['duration'];
            $config['params']['data']['distance'] = $value['distance'];
            $config['params']['data']['starttime'] = $value['starttime'];
            $config['params']['data']['endtime'] = $value['endtime'];
            $config['params']['data']['odostart'] = $value['odostart'];
            $config['params']['data']['odoend'] = $value['odoend'];
            $config['params']['data']['fuelconsumption'] = $value['fuelconsumption'];
            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
            } else {
                $msg .= $return['msg'];
            }
        }
        // }
        if ($msg == '') {
            $msg = 'Successfully saved.';
        }
        return ['row' => $rows, 'status' => true, 'msg' => $msg];
    }
    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $activityid = $config['params']['data']['activityid'];
        $duration = $config['params']['data']['duration'];
        $distance = $config['params']['data']['distance'];
        $starttime = $config['params']['data']['starttime'];
        $endtime = $config['params']['data']['endtime'];
        $fuelconsumption = $config['params']['data']['fuelconsumption'];
        $odostart = $config['params']['data']['odostart'];
        $odoend = $config['params']['data']['odoend'];


        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == "") {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
        }

        $odostart =  str_replace(',', '', $odostart);
        $odoend =  str_replace(',', '', $odoend);
        $distance = $odoend - $odostart;
        $duration =  (strtotime($endtime) - strtotime($starttime));
        $duration =  ($duration / 3600);
        $data = [
            'trno' => $trno,
            'line' => $line,
            'activityid' => $activityid,
            'duration' => number_format($duration, 2),
            'distance' => $distance,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'fuelconsumption' => $fuelconsumption,
            'odoend' => $odoend,
            'odostart' => $odostart,
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $this->coreFunctions->LogConsole(json_encode($data));
        if ($action == 'insert') {
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
            $data['encodedby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - activityid:' . $activityid);
                $row = $this->openstockline($config);
                return ['row' => $row, 'data' => $data, 'status' => true, 'msg' => 'Activity was successfully added.'];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $result = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
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
   left join reqcategory as cat on cat.line = stock.activityid
   where stock.trno = ? and stock.line = ?
   union all  
   " . $sqlselect . " FROM  $this->hstock  as stock
   left join reqcategory as cat on cat.line = stock.activityid
   where stock.trno = ? and stock.line = ? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
        return $stock;
    }
    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $qry = "delete from " . $this->stock . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Activityid:' . $data[0]['activityid']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function
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
            $odostart =  str_replace(',', '', $data2[$key]['odostart']);
            $odostend =  str_replace(',', '', $data2[$key]['odoend']);
            if ($odostend  < $odostart) {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = "Invalid input meter '" .  $data2[$key]['odoend'] . "' meter end must be greater than start";
            }
            if ($data2[$key]['endtime'] < $data2[$key]['starttime']) {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg2 = "Invalid input time '" .  $data2[$key]['endtime'] . "' end time must be greater than start";
            }
        }
        if (!$isupdate) {
            return ['inventory' => $data, 'status' => false, 'msg' =>  'Please check Activity (' . $msg1 . ' ' . $msg2 . ')'];
        } else {
            return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }
    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $isupdate = $this->additem('update', $config);
        $data = $this->openstockline($config);
        $data2 = json_decode(json_encode($data), true);

        $msg1 = '';
        $msg2 = '';
        foreach ($data2 as $key => $value) {
            $odostart =  str_replace(',', '', $data2[$key]['odostart']);
            $odostend =  str_replace(',', '', $data2[$key]['odoend']);
            if ($odostend  < $odostart) {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg1 = "Invalid input meter '" .  $data2[$key]['odoend'] . "' meter end must be greater than start";
            }
            if ($data2[$key]['endtime'] < $data2[$key]['starttime']) {
                $data[$key]->bgcolor = 'bg-red-2';
                $isupdate = false;
                $msg2 = "Invalid input time '" .  $data2[$key]['endtime'] . "' end time must be greater than start";
            }
        }
        if (!$isupdate) {
            return ['inventory' => $data, 'status' => false, 'msg' =>  'Please check Activity (' . $msg1 . ' ' . $msg2 . ')'];
        } else {
            return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
        }
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $stock = $this->openstock($trno, $config);
        foreach ($stock as $key => $value) {
            $activityid = $value->activityid;
            $line = $value->line;
            $item = $this->coreFunctions->getfieldvalue($this->stock, 'activityid', 'trno=? and line = ?', [$trno, $line]);
            $qry =  "delete from " . $this->stock . " where trno=? and line= ? and activityid = ?";
            $this->coreFunctions->execqry($qry, 'delete', [$trno, $line, $activityid]);
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $qry = "select trno from " . $this->stock . " where trno=? and odostart = 0 and odoend = 0 and fuelconsumption = 0 and starttime is null and endtime is null limit 1";
        $activityzero = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($activityzero)) {
            return ['status' => false, 'msg' => 'Post failed, Please check, some activity have zero.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Post failed; already posted.'];
        }

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,itemid,empid,projectid,opincentive,whid,rem,lockuser,lockdate,createdate,createby,editby,editdate,viewby,viewdate)
      SELECT head.trno,head.doc, head.docno,head.dateid,head.itemid,head.empid,head.projectid,head.opincentive,head.whid,head.rem,head.lockuser,head.lockdate,head.createdate,head.createby,
      head.editby,head.editdate,head.viewby,head.viewdate FROM " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
      where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($posthead) {
            if (!$this->othersClass->postingheadinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting headinfo.'];
            }
            if (!$this->othersClass->postingstockinfotrans($config)) {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting stockinfo.'];
            }
            $qry = "insert into " . $this->hstock . "(trno,line,activityid,starttime,endtime,duration,odostart,odoend,distance,fuelconsumption,encodeddate,encodedby,editby,editdate)
        SELECT trno,line,activityid,starttime,endtime,duration,odostart,odoend,distance,fuelconsumption,encodeddate,encodedby,editby,editdate
        FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);

                $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error in posting stock.'];
            }
        } else {
            return ['status' => false, 'msg' => 'Error in posting head.'];
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

        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);

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
    }


    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];
        $msg = '';

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $qry = "select trno from " . $this->hhead . " where trno=? and oitrno != 0 limit 1";
        $applied = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($applied)) {
            return ['status' => false, 'msg' => 'Is already applied in Operator Incentive'];
        }
        $qry = "select trno from " . $this->hhead . " where trno=? and batchid <> 0 limit 1";
        $alreadyprocess = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($alreadyprocess)) {
            return ['status' => false, 'msg' => 'Is already process in payroll'];
        }
        $qry = "insert into  " . $this->head . " (trno,doc,docno,dateid,itemid,empid,projectid,opincentive,whid,rem,lockuser,lockdate,createdate,createby,editby,editdate,viewby,viewdate)
        select trno,doc,docno,dateid,itemid,empid,projectid,opincentive,whid,rem,lockuser,lockdate,createdate,createby,editby,editdate,viewby,viewdate from " . $this->hhead . " where trno=?";

        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->stock . "(trno,line,activityid,starttime,endtime,duration,odostart,odoend,distance,fuelconsumption,encodeddate,encodedby,editby,editdate)
            select trno,line,activityid,starttime,endtime,duration,odostart,odoend,distance,fuelconsumption,encodeddate,encodedby,editby,editdate
            from " . $this->hstock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $config['docmodule']->hstock . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting'];
            }
        } else {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting'];
        }
    } //end function
}//end class