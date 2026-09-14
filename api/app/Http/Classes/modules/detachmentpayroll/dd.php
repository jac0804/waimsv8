<?php

namespace App\Http\Classes\modules\detachmentpayroll;

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
use Exception;

class dd
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DDO ISSUANCE';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
    public $tablenum = 'hrisnum';
    public $statlogs = 'transnum_stat';
    public $head = 'ddhead';
    public $hhead = 'hddhead';
    public $detail = 'dddetail';
    public $hdetail = 'hdddetail';
    // public $firearms = 'ddfirearms';

    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';
    private $stockselect;
    public $fields = ['trno', 'docno', 'divid', 'dateid', 'dateid2'];
    public $except = ['trno', 'dateid', 'dateid2'];
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
        $this->reporter = new SBCPDF;
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5971,
            'edit' => 5972,
            'new' => 5973,
            'save' => 5974,
            'delete' => 5975,
            'print' => 5976,
            'post' => 6006,
            'unpost' => 6007,
            'additem' => 6008,
            'edititem' => 6009,
            'deleteitem' => 6010
        );
        return $attrib;
    }


    public function createdoclisting($config)
    {
        $companyid = $config['params']['companyid'];

        $getcols = ['action',  'listdocument', 'divname', 'listcreateby', 'createdate', 'itemname'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];


        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        // $cols[$action]['style'] = 'width:10px;whiteSpace: normal;min-width:10px; max-width:10px;';
        // $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$divname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$divname]['label'] = 'Detachment';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = [];
        $col1 = [];
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
    }

    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $laext = '';
        $glext = '';

        $lfield = '';
        $gfield = '';

        $orderby = "order by dateid desc, docno desc";

        $limit = "limit 150";
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];
        $lstatus = " case when num.postdate is null then 'DRAFT' else 'POSTED' end";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.divid', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        switch ($itemfilter) {
            case 'draft':
                $condition = 'and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, " . $lstatus . " as status,num.postedby,
        head.createby,date(head.createdate) as createdate, division.divname $lfield
        from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
        left join division  on division.divid = head.divid  
        left join divinfo as info on info.divid = head.divid
        where  num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
        union all
        select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status, num.postedby,
        head.createby,date(head.createdate) as createdate, division.divname $gfield
        from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
        left join division  on division.divid = head.divid  
        left join divinfo as info on info.divid = head.divid
        where  num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
        $orderby " . $limit;
        $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2, $center, $date1, $date2]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }



    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'new',
            'edit',
            'save',
            'post',
            'unpost',
            'delete',
            'cancel',
            'print',
            'logs',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

        return $return;
    }



    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['dateid2'] = $this->othersClass->getCurrentDate();
        $data[0]['dateid3'] = $this->othersClass->getCurrentDate();
        $data[0]['dateid4'] = $this->othersClass->getCurrentDate();
        $data[0]['divid'] = 0;
        $data[0]['division'] = '';
        $data[0]['divname'] = '';
        return $data;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'ddivname', ['rate', 'cola'], 'allowance'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddivname.type', 'lookup');
        data_set($col1, 'ddivname.lookupclass', 'lookupispayrolldetachment');
        data_set($col1, 'ddivname.action', 'lookupempdivision');
        data_set($col1, 'ddivname.class', 'sbccsreadonly');
        data_set($col1, 'ddivname.label', 'Detachment');
        data_set($col1, 'rate.class', 'sbccsreadonly');
        data_set($col1, 'cola.class', 'sbccsreadonly');
        data_set($col1, 'rate.readonly', true);
        data_set($col1, 'cola.readonly', true);
        data_set($col1, 'allowance.type', 'input');
        data_set($col1, 'allowance.readonly', true);
        data_set($col1, 'allowance.class', 'sbccsreadonly');
        data_set($col1, 'rate.label', 'RATE');
        data_set($col1, 'cola.label', 'COLA');
        data_set($col1, 'allowance.label', 'ALLOWANCE');

        $fields = ['lblrem', 'dateid', 'dateid2'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'lblrem.label', 'DDO Date');

        $fields = ['lblrem', 'dateid3', 'dateid4'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'lblrem.label', 'Payroll Month Date');

        $fields = ['updatepostedinfo', 'duplicatedoc'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'updatepostedinfo.label', 'Update Rate');
        data_set($col4, 'updatepostedinfo.icon', 'save');
        data_set($col4, 'updatepostedinfo.style', 'width:220px;');
        data_set($col4, 'updatepostedinfo.lookupclass', 'updaterate');
        data_set($col4, 'duplicatedoc.label', 'Copy Previous DDO');
        data_set($col4, 'duplicatedoc.icon', 'edit');
        data_set($col4, 'duplicatedoc.style', 'width:220px;');
        data_set($col4, 'duplicatedoc.action', 'duplicatedoc');





        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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

        $head = [];
        $qry = "select head.trno, head.docno, head.dateid, head.dateid2, head.paydate as dateid3, head.paydate2 as dateid4,
        division.divid, division.divname, division.divcode as division,
        info.salary as rate, info.cola as cola, info.incentive as allowance
        from ddhead as head
        left join division on division.divid = head.divid
        left join divinfo as info on info.divid = head.divid
        where head.trno = ?
        union all
        select head.trno, head.docno, head.dateid, head.dateid2, head.paydate as dateid3, head.paydate2 as dateid4,
        division.divid, division.divname, division.divcode as division,
        info.salary as rate, info.cola as cola, info.incentive as allowance
        from hddhead as head
        left join division on division.divid = head.divid
        left join divinfo as info on info.divid = head.divid
        where head.trno = ?";

        $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        if (!empty($head)) {
            // $stock = [];
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $postdate = $this->coreFunctions->datareader("select postdate as value from " . $this->tablenum . " where trno = ?", [$trno]);
            $postdate = $postdate != null ? true : false;

            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = [];
            $clickobj = [];

            $hideobj = [];
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'isposted' => $postdate,
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
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields[1]); // removes 'docno'
            unset($head['docno']);
        }

        $companyid = $config['params']['companyid'];
        $dateTables = [$this->head];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
                }
            }
        }

        // Payroll Month Date (form fields dateid3/dateid4) -> DB paydate/paydate2
        if (array_key_exists('dateid3', $head)) {
            $data['paydate'] = $head['dateid3'];
        }
        if (array_key_exists('dateid4', $head)) {
            $data['paydate2'] = $head['dateid4'];
        }

        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['divid']);
        }
    }



    public function createTab($access, $config)
    {
        $fields = ['creditinfo'];
        $col1 = $this->fieldClass->create($fields);
        $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
        $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
        $iskgs = $this->companysetup->getiskgs($config['params']);



        $column = ['action', 'client', 'acnoname',  'clientname', 'barcode', 'rate', 'itemname'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $headgridbtns = ['viewref'];


        $tab = [
            $this->gridname => [
                'gridcolumns' => $column,
                'headgridbtns' => $headgridbtns
            ],
        ];

        $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entryfirearms', 'label' => 'FIREARMS', 'checkchanges' => 'tableentry'];

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        //styling 
        $obj[0]['inventory']['columns'][$client]['label'] = 'Employee Code';
        $obj[0]['inventory']['columns'][$clientname]['label'] = 'Employee Name';
        $obj[0]['inventory']['columns'][$rate]['label'] = 'Rate';
        $obj[0]['inventory']['columns'][$barcode]['label'] = '';
        $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
        $obj[0]['inventory']['columns'][$clientname]['readonly'] = true;
        $obj[0]['inventory']['columns'][$client]['readonly'] = true;
        $obj[0]['inventory']['columns'][$client]['type'] = 'input';

        //style
        $obj[0]['inventory']['columns'][$clientname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0]['inventory']['columns'][$client]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';
        $obj[0]['inventory']['columns'][$rate]['style'] = 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px';
        $obj[0]['inventory']['columns'][$acnoname]['style'] = 'width: 30px;whiteSpace: normal;min-width:30px;max-width:30px';
        $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 30px;whiteSpace: normal;min-width:30px;max-width:30px; text-align:right;';
        // $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px text-align:right';

        // remove the header per row of every employee on grid 
        $obj[0]['inventory']['descriptionrow'] = [];



        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addempgrid', 'saveitem', 'deleteallitem'];

        foreach ($tbuttons as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);

        // Configure the button for employee lookup
        $obj[$addempgrid]['label'] = "ADD EMPLOYEE";
        $obj[$saveitem]['label'] = "SAVE ALL EMPLOYEE";
        $obj[$deleteallitem]['label'] = "DELETE ALL EMPLOYEE";
        $obj[$addempgrid]['lookupclass'] = "lookupempgrids";
        $obj[$addempgrid]['action'] = "addempgrid";


        return $obj;
    }

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'addempgrid':
                return $this->addempgrid($config);
                break;
            case 'saveitem':
                return $this->saveemployee($config);
                break;
            case 'saveperitem':
                return $this->saveperemployee($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'duplicatedoc':
                return $this->duplicatepreviousddo($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }




    public function openstock($trno, $config)
    {
        $qry = "select detail.trno, detail.line, detail.empid, client.client as client, client.clientname, detail.rate, '' as bgcolor
    from " . $this->detail . " as detail
    left join client on client.clientid = detail.empid
    where detail.trno = ?
    union all
    select detail.trno, detail.line, detail.empid, client.client as client, client.clientname, detail.rate, '' as bgcolor
    from " . $this->hdetail . " as detail
    left join client on client.clientid = detail.empid
    where detail.trno = ?
    order by line";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    }

    public function openstockline($config, $lines = [])
    {
        $trno = $config['params']['trno'];

        $qry = "select detail.trno, detail.line, detail.empid, client.client as client, client.clientname, detail.rate, '' as bgcolor
        from " . $this->detail . " as detail
        left join client on client.clientid = detail.empid
        where detail.trno = ?";
        $params = [$trno];

        if (!empty($lines)) {
            $placeholders = implode(',', array_fill(0, count($lines), '?'));
            $qry .= " and detail.line in ($placeholders)";
            $params = array_merge($params, $lines);
        }

        $qry .= " order by detail.line";

        $stock = $this->coreFunctions->opentable($qry, $params);
        return $stock;
    }

    public function addempgrid($config)
    {
        $trno = $config['params']['trno'];
        $rows = isset($config['params']['rows']) ? $config['params']['rows'] : [];

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'No employees selected.'];
        }

        $rate = $this->coreFunctions->datareader(
            "select info.salary as value 
         from " . $this->head . " as head 
         left join divinfo as info on info.divid = head.divid 
         where head.trno = ?",
            [$trno]
        );
        $rate = ($rate === '' || $rate === null) ? 0 : $rate;

        $addedLines = [];
        $duplicates = [];

        foreach ($rows as $key => $value) {
            $empid = $value['clientid'];
            $empname = isset($value['clientname']) ? $value['clientname'] : $empid;

            $exists = $this->coreFunctions->datareader("select trno as value from " . $this->detail . " where trno=? and empid=? limit 1", [$trno, $empid]);
            if ($exists != '') {
                $duplicates[] = $empname; // track skipped employees
                continue; // skip employees already added
            }

            $line = $this->coreFunctions->datareader("select line as value from " . $this->detail . " where trno=? order by line desc limit 1", [$trno]);
            $line = ($line == '') ? 1 : $line + 1;

            $data = [
                'trno' => $trno,
                'line' => $line,
                'empid' => $empid,
                'rate' => $rate,
                'createdate' => $this->othersClass->getCurrentTimeStamp(),
                'createby' => $config['params']['user'],
                'editdate' => $this->othersClass->getCurrentTimeStamp(),
                'editby' => $config['params']['user']
            ];

            if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' empid:' . $empid . ' Rate:' . $rate);
                $addedLines[] = $line;
            }
        }

        // All selected employees were duplicates — nothing added at all
        if (empty($addedLines) && !empty($duplicates)) {
            return [
                'status' => false,
                'msg' => 'Employee(s) already added: ' . implode(', ', $duplicates)
            ];
        }

        // Nothing added and no duplicates either (insert genuinely failed)
        if (empty($addedLines)) {
            return ['status' => false, 'msg' => 'No employees were added.'];
        }

        $row = $this->openstockline($config, $addedLines);

        // Some added, some duplicates — partial success with a warning
        if (!empty($duplicates)) {
            return [
                'row' => $row,
                'status' => true,
                'msg' => 'Saved successfully, but already added: ' . implode(', ', $duplicates)
            ];
        }

        return ['row' => $row, 'status' => true, 'msg' => 'Successfully saved.'];
    }


    public function saveemployee($config)
    {
        $trno = $config['params']['trno'];
        $rows = isset($config['params']['row']) ? $config['params']['row'] : [];


        if (empty($rows)) {
            return ['status' => false, 'msg' => 'No items to save.'];
        }

        $saved = 0;

        foreach ($rows as $key => $value) {
            // only push rows the grid actually marked as edited
            if (!isset($value['bgcolor']) || $value['bgcolor'] == '') {
                continue;
            }

            $line = $value['line'];
            $rate = $value['rate'];

            $data = [
                'rate' => $rate,
                'editdate' => $this->othersClass->getCurrentTimeStamp(),
                'editby' => $config['params']['user']
            ];

            $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'UPDATE - Line:' . $line . ' Rate:' . $rate);
            $saved++;
        }

        if ($saved == 0) {
            return ['status' => false, 'msg' => 'No edited items to save.'];
        }

        $row = $this->openstock($trno, $config);

        return ['inventory' => $row, 'status' => true, 'msg' =>  ' Successfully saved.', 'reloadhead' => true, 'trno' => $config['params']['trno']];
    }

    public function saveperemployee($config)
    {
        $trno = $config['params']['trno'];
        $row = $config['params']['row'];
        $line = $row['line'];
        $rate = $row['rate'];

        $data = [
            'rate' => $rate,
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user']
        ];

        $update = $this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]);

        if ($update) {
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'UPDATE - Line:' . $line . ' empid:' . $row['empid'] . ' Rate:' . $rate);
            $returnrow = $this->openstockline($config, [$line]);
            return ['row' => $returnrow, 'status' => true, 'msg' => 'Successfully saved.'];
        } else {
            return ['status' => false, 'msg' => 'Update failed.'];
        }
    }

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $this->head . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function deleteitem($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=? and line=?", 'delete', [$trno, $line]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' empid:' . $config['params']['row']['empid']);

        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    }

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function duplicatepreviousddo($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];

        $currentQry = "select divid, paydate as dateid3 from " . $this->head . " where trno = ?
    union all
    select divid, paydate as dateid3 from " . $this->hhead . " where trno = ?";
        $current = $this->coreFunctions->opentable($currentQry, [$trno, $trno]);
        $current = isset($current[0]) ? $current[0] : null;

        if (empty($current) || empty($current->divid) || empty($current->dateid3)) {
            return ['status' => false, 'msg' => 'Please select a detachment and payroll month date first.'];
        }

        $divid = $current->divid;
        $dateid3 = $current->dateid3;

        $prevQry = "select trno, paydate from (
        select trno, paydate from " . $this->head . " where divid = ? and paydate < ? and trno != ?
        union all
        select trno, paydate from " . $this->hhead . " where divid = ? and paydate < ? and trno != ?
    ) as t order by paydate desc, trno desc limit 1";
        $prev = $this->coreFunctions->opentable($prevQry, [$divid, $dateid3, $trno, $divid, $dateid3, $trno]);
        $prev = isset($prev[0]) ? $prev[0] : null;

        if (empty($prev) || empty($prev->trno)) {
            return ['status' => false, 'msg' => 'No previous DDO found for this detachment before the selected payroll month.'];
        }

        $prevtrno = $prev->trno;
        $now = $this->othersClass->getCurrentTimeStamp();

        // Copy employees
        $prevDetails = $this->coreFunctions->opentable(
            "select empid, rate from " . $this->detail . " where trno = ?
         union all
         select empid, rate from " . $this->hdetail . " where trno = ?",
            [$prevtrno, $prevtrno]
        );

        $line = $this->coreFunctions->datareader("select line as value from " . $this->detail . " where trno=? order by line desc limit 1", [$trno]);
        $line = ($line == '') ? 0 : $line;

        $empCopied = 0;
        foreach ($prevDetails as $row) {
            $exists = $this->coreFunctions->datareader("select trno as value from " . $this->detail . " where trno=? and empid=? limit 1", [$trno, $row->empid]);
            if ($exists != '') {
                continue;
            }

            $line++;
            $data = [
                'trno' => $trno,
                'line' => $line,
                'empid' => $row->empid,
                'rate' => $row->rate,
                'createdate' => $now,
                'createby' => $user,
                'editdate' => $now,
                'editby' => $user
            ];
            if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
                $empCopied++;
            }
        }

        // Copy firearms
        $prevFirearms = $this->coreFunctions->opentable(
            "select fireid, rate from ddfirearms where trno = ?",
            [$prevtrno]
        );

        $fireCopied = 0;
        foreach ($prevFirearms as $row) {
            $exists = $this->coreFunctions->datareader("select trno as value from ddfirearms where trno=? and fireid=? limit 1", [$trno, $row->fireid]);
            if ($exists != '') {
                continue;
            }

            $data = [
                'trno' => $trno,
                'fireid' => $row->fireid,
                'rate' => $row->rate,
                'createdate' => $now,
                'createby' => $user,
                'editdate' => $now,
                'editby' => $user
            ];
            if ($this->coreFunctions->sbcinsert('ddfirearms', $data) == 1) {
                $fireCopied++;
            }
        }

        $this->logger->sbcwritelog($trno, $config, 'HEAD', 'COPY PREVIOUS DDO - from trno:' . $prevtrno . ' Employees copied:' . $empCopied . ' Firearms copied:' . $fireCopied);

        $stock = $this->openstock($trno, $config);

        return [
            'status' => true,
            'msg' => 'Copied ' . $empCopied . ' employee(s) and ' . $fireCopied . ' firearm(s) from the previous DDO.',
            'inventory' => $stock,
            'reloadhead' => true
        ];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader("select docno as value from " . $this->head . " where trno=?", [$trno]);

        $postdate = $this->coreFunctions->datareader("select postdate as value from " . $this->tablenum . " where trno = ?", [$trno]);
        if ($postdate != null) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }


        // move head to history
        $qry = "insert into " . $this->hhead . " (trno,docno,divid,dateid,dateid2,paydate,paydate2,createdate,createby,editdate,editby,viewdate,viewby)
    select trno,docno,divid,dateid,dateid2,paydate,paydate2,createdate,createby,editdate,editby,viewdate,viewby
    from " . $this->head . " where trno=? limit 1";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        // move employee detail to history
        $qry2 = "insert into " . $this->hdetail . " (trno,line,empid,rate,createdate,createby,editdate,editby)
    select trno,line,empid,rate,createdate,createby,editdate,editby
    from " . $this->detail . " where trno=?";
        $this->coreFunctions->execqry($qry2, 'insert', [$trno]);

        // move firearms to history
        $this->coreFunctions->execqry(
            "insert into hddfirearms (trno,fireid,rate,createdate,createby,editdate,editby)
        select trno,fireid,rate,createdate,createby,editdate,editby from ddfirearms where trno=?",
            'insert',
            [$trno]
        );

        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $user];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from ddfirearms where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);

        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);

        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    }

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader("select docno as value from " . $this->hhead . " where trno=?", [$trno]);

        $postdate = $this->coreFunctions->datareader("select postdate as value from " . $this->tablenum . " where trno = ?", [$trno]);
        if ($postdate == null) {
            return ['status' => false, 'msg' => 'Unposting failed. Transaction is not posted.'];
        }

        // move head back to live
        $qry = "insert into " . $this->head . " (trno,docno,divid,dateid,dateid2,paydate,paydate2,createdate,createby,editdate,editby,viewdate,viewby)
    select trno,docno,divid,dateid,dateid2,paydate,paydate2,createdate,createby,editdate,editby,viewdate,viewby
    from " . $this->hhead . " where trno=? limit 1";
        $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        // move employee detail back to live
        $qry2 = "insert into " . $this->detail . " (trno,line,empid,rate,createdate,createby,editdate,editby)
    select trno,line,empid,rate,createdate,createby,editdate,editby
    from " . $this->hdetail . " where trno=?";
        $this->coreFunctions->execqry($qry2, 'insert', [$trno]);

        // move firearms back to live
        $this->coreFunctions->execqry(
            "insert into ddfirearms (trno,fireid,rate,createdate,createby,editdate,editby)
        select trno,fireid,rate,createdate,createby,editdate,editby from hddfirearms where trno=?",
            'insert',
            [$trno]
        );

        $data = ['postdate' => null, 'postedby' => ''];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from hddfirearms where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);

        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);

        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    }
} //end class
