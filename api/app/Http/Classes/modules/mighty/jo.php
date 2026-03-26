<?php

namespace App\Http\Classes\modules\mighty;

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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class jo
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'JOB/REPAIR ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'johead';
    public $hhead = 'hjohead';
    public $stock = 'jostock';
    public $hstock = 'hjostock';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    public $dqty = 'isqty';
    public $hqty = 'iss';
    public $damt = 'isamt';
    public $hamt = 'amt';
    private $fields = [
        'trno', 'docno', 'dateid', 'client', 'clientname', 'rem', 'wh', 'projectid',
    ];
    private $except = ['trno', 'dateid'];
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
            'view' => 1812,
            'edit' => 1813,
            'new' => 1814,
            'save' => 1815,
            // 'change'=>1816, remove change doc
            'delete' => 1817,
            'print' => 1818,
            'lock' => 1819,
            'unlock' => 1820,
            'post' => 1821,
            'unpost' => 1822,
            'additem' => 1823,
            'edititem' => 1824,
            'changeamt' => 1824,
            'deleteitem' => 1825
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $yourref = 3;
        $listdate = 4;
        $listclientname = 5;
        $workloc = 6;
        $workdesc = 7;
        $listpostedby = 8;
        $listcreateby = 9;
        $listeditby = 10;
        $listviewby = 11;

        $getcols = ['action', 'liststatus', 'listdocument', 'yourref', 'listdate', 'listclientname', 'workloc', 'workdesc', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$yourref]['label'] = 'JR#';

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$yourref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';

        switch ($config['params']['companyid']) {
            case 8:
                $cols[$workdesc]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
                break;
            default:
                $cols[$workdesc]['type'] = 'coldel';
                $cols[$workloc]['type'] = 'coldel';
                break;
        }
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
        $limit = "limit 150";

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            if ($config['params']['companyid'] == 8) array_push($searchfield, 'head.workdesc', 'head.workloc');
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        $qry = "select head.trno, head.docno, head.clientname, left(head.dateid, 10) as dateid, 
        'DRAFT' as status, head.createby, head.editby, head.viewby, num.postedby, head.yourref  
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num 
        on num.trno = head.trno where head.doc = ? and num.center = ? and 
        CONVERT(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
        union all
        select head.trno, head.docno, head.clientname, left(head.dateid, 10) as dateid,
        'POSTED' as status, head.createby, head.editby, head.viewby, num.postedby, head.yourref  
        from " . $this->hhead . " as head 
        left join " . $this->tablenum . " as num 
        on num.trno = head.trno where head.doc = ? and num.center = ? and 
        convert(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
        order by dateid desc, docno desc " . $limit;

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

        if ($this->companysetup->getisshowmanual($config['params'])) {
            $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'jo', 'title' => 'Job Order Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
        }
        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        return $buttons;
    } // createHeadbutton


    public function createTab($access, $config)
    {

        $gridcolumns = ['isqty', 'uom', 'cost', 'isamt', 'ext', 'wh', 'loc', 'expiry', 'ref', 'stage', 'itemname', 'barcode'];

        foreach ($gridcolumns as $key => $value) {
            $$value = $key;
        }

        // $headgridbtns = ['itemvoiding', 'viewref'];

        $tab = [
            'stockinfotab' => ['action' => 'tableentry', 'lookupclass' => 'compliantass', 'label' => 'COMPLIANTS/ASSESSMENT', 'checkchanges' => 'tableentry'],
            'tableentry' =>  ['action' => 'tableentry', 'lookupclass' => 'jobdone', 'label' => 'Action/Job Done', 'checkchanges' => 'tableentry'],
            'tableentry2' => ['action' => 'tableentry', 'lookupclass' => 'mitagged', 'label' => 'MI', 'checkchanges' => 'tableentry'],
            $this->gridname => [
                'gridcolumns' => $gridcolumns,
                'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
                // 'headgridbtns' => $headgridbtns
            ],
        ];

        // $tab['stockinfotab'] = ['action' => 'tableentry', 'lookupclass' => 'compliantass', 'label' => 'COMPLIANTS/ASSESSMENT', 'checkchanges' => 'tableentry'];
        // $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'jobdone', 'label' => 'Action/Job Done', 'checkchanges' => 'tableentry'];
        // $tab['tableentry2'] = ['action' => 'tableentry', 'lookupclass' => 'mitagged', 'label' => 'MI', 'checkchanges' => 'tableentry'];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[3][$this->gridname]['columns'][$isqty]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$isamt]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$barcode]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$wh]['type'] = 'label';
        $obj[3][$this->gridname]['columns'][$wh]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$loc]['type'] = 'label';
        $obj[3][$this->gridname]['columns'][$loc]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$expiry]['type'] = 'label';
        $obj[3][$this->gridname]['columns'][$expiry]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$ref]['type'] = 'label';
        $obj[3][$this->gridname]['columns'][$ref]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$stage]['type'] = 'label';
        $obj[3][$this->gridname]['columns'][$stage]['readonly'] = true;
        $obj[3][$this->gridname]['columns'][$uom]['type'] = 'label';

        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        //pendingpr
        $tbuttons = ['pendingmi'];

        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'start', 'end', 'nodays'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.label', 'Code');
        data_set($col1, 'clientname.class', 'sbccsreadonly');
        data_set($col1, 'clientname.label', 'Name');

        data_set($col1, 'end.label', 'Completion Date');
        $fields = ['dateid', 'empname', 'whname', 'assetname', 'ftime', 'ttime'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'empname.label', 'Assessed by');
        data_set($col2, 'empname.required', false);
        data_set($col2, 'empname.type', 'lookup');
        data_set($col2, 'empname.action', 'lookupclient');
        data_set($col2, 'empname.lookupclass', 'assessedby');

        data_set($col2, 'whname.required', true);
        data_set($col2, 'whname.type', 'lookup');
        data_set($col2, 'whname.action', 'lookupclient');
        data_set($col2, 'whname.lookupclass', 'wh');
        data_set($col2, 'whname.class', 'sbccsreadonly');

        data_set($col2, 'ftime.type', 'input');
        data_set($col2, 'ttime.type', 'input');
        data_set($col2, 'ftime.label', 'Start Time');
        data_set($col2, 'ttime.label', 'Completion Time');
        $fields = ['mileage', 'dprojectname'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'yourref.label', 'Mileage/SMR');
        data_set($col3, 'dprojectname.required', true);
        data_set($col3, 'dprojectname.lookupclass', 'projectcode');
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
        $data[0]['rem'] = '';
        $data[0]['rem2'] = '';
        $data[0]['terms'] = '';
        $data[0]['ftime'] = '00:00';
        $data[0]['ttime'] = '00:00';
        $data[0]['project'] = '';
        $data[0]['projectcode'] = '';
        $data[0]['projectname'] = '';
        $data[0]['projectid'] = 0;
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
        $data[0]['whname'] = $name;
        $data[0]['start'] = $this->othersClass->getCurrentDate();
        $data[0]['end'] = $this->othersClass->getCurrentDate();
        $data[0]['empname'] = '';
        $data[0]['empid'] = 0;
        $data[0]['assessedid'] = 0;
        $data[0]['mileage'] = '';
        $data[0]['nodays'] = '';

        // assetname
        $data[0]['itemid'] = 0;
        $data[0]['itemname'] = '';
        $data[0]['barcode'] = '';
        $data[0]['assetname'] = '';
        return $data;
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

        $qryselect = "select head.trno, head.docno, head.dateid, head.rem, head.wh, warehouse.clientname as whname, num.center, 
        head.client, head.clientname, head.projectid, head.projectid, ifnull(p.code, '') as projectcode, ifnull(p.name, '') as projectname, 
        ifnull(p.code, '') as project, info.sdate1 as start, info.sdate2 as end, info.strdate1 as ftime, info.strdate2 as ttime,
        assessedby.clientname as empname, ifnull(assessedby.clientid,0) as assessedid, head.yourref, info.mileage, info.nodays, 
        ifnull(item.itemname,'') as itemname, ifnull(item.barcode,'') as barcode ,ifnull(item.itemid,'') as itemid,'' as assetname";
        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as p on p.line = head.projectid
        left join headinfotrans as info on info.trno = head.trno 
        left join item on item.itemid=info.itemid 
        left join client as assessedby on assessedby.clientid = info.assessedid  
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as p on p.line = head.projectid
        left join hheadinfotrans as info on info.trno = head.trno
        left join item on item.itemid=info.itemid
        left join client as assessedby on assessedby.clientid = info.assessedid  
        where head.trno = ? and num.center = ?";

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
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        $dataothers = [];
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
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
        $dataothers['trno'] = $head['trno'];
        $dataothers['assessedid'] = $head['assessedid'];
        $dataothers['sdate1'] = $head['start'];
        $dataothers['sdate2'] = $head['end'];
        $dataothers['strdate1'] = $head['ftime'];
        $dataothers['strdate2'] = $head['ttime'];
        $dataothers['nodays'] = $head['nodays'];
        $dataothers['itemid'] = $head['itemid'];
        $dataothers['mileage'] = $head['mileage'];
        $arrcols = array_keys($dataothers);
        foreach ($arrcols as $key) {
            $dataothers[$key] = $this->othersClass->sanitizekeyfield($key, $dataothers[$key]);
        }
        $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
        if ($infotransexist == '') {
            $this->coreFunctions->sbcinsert("headinfotrans", $dataothers);
        } else {
            $this->coreFunctions->sbcupdate("headinfotrans", $dataothers, ['trno' => $head['trno']]);
        }
    } // end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $isitemzero = $this->coreFunctions->datareader('select count(trno) as value from hcntnuminfo where jotrno=?', [$trno]);
        if ($isitemzero == 0) {
            return ['status' => false, 'msg' => 'Posting failed. There is no stock.'];
        }
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        if ($this->othersClass->isposted($config)) {
            return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
        }
        // headinfotrans
        if (!$this->othersClass->postingheadinfotrans($config)) {
            return ['status' => false, 'msg' => 'An error occurred while posting head data.'];
        }
        //stockinfotrans
        if (!$this->othersClass->postingstockinfotrans($config)) {
            return ['status' => false, 'msg' => 'Error on Unposting Stockinfo'];
        }
        //for hjohead
        $qry = "insert into " . $this->hhead . "(trno, doc, docno, client, clientname, address, shipto, dateid,
        terms, rem, forex, yourref, ourref, createdate, createby, editby, editdate, lockdate, lockuser, agent, wh, due, cur, 
        projectid, stageid, workloc, workdesc)
        SELECT head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref,
        head.createdate, head.createby, head.editby, head.editdate, head.lockdate, head.lockuser, head.agent, head.wh,
        head.due, head.cur, head.projectid,head.stageid, head.workloc, head.workdesc
        FROM " . $this->head . " as head 
        left join " . $this->tablenum . " as cntnum on cntnum.trno = head.trno
        where head.trno = ? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            // for hjostock
            $qry = "insert into " . $this->hstock . "(trno, line, itemid, uom,
            whid, loc, ref, disc, cost, qty, void, rrcost, rrqty, ext,
            encodeddate, qa, encodedby, editdate, editby, sku, refx, linex, rem, stageid)
            SELECT trno, line, itemid, uom, whid, loc, ref, disc, cost, qty, void, rrcost, rrqty, ext,
            encodeddate, qa, encodedby, editdate, editby, sku, refx, linex, rem, stageid FROM " . $this->stock . " where trno =?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                //update transnum
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);

                $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
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
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        if (!$this->othersClass->unpostingheadinfotrans($config)) {
            return ['status' => false, 'msg' => 'An error occurred while unposting head data.'];
        }
        if (!$this->othersClass->unpostingstockinfotrans($config)) {
            return ['status' => false, 'msg' => 'An error occurred while unposting stock/s.'];
        }
        $qry = "insert into " . $this->head . "(trno, doc, docno, client, clientname, address, shipto, dateid, terms, rem, forex,
        yourref, ourref, createdate, createby, editby, editdate, lockdate, lockuser, wh, due, cur, projectid, stageid, workloc, workdesc)
        select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
        head.dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
        head.createby, head.editby, head.editdate, head.lockdate, head.lockuser, head.wh, head.due, head.cur, head.projectid, head.stageid, head.workloc, head.workdesc
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno = head.trno) left join client on client.client = head.client
        where head.trno = ? limit 1";

        //johead
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->stock . "(
            trno, line, itemid, uom, whid, loc, ref, disc,
            cost, qty, void, rrcost, rrqty, ext, rem, encodeddate, qa, encodedby, editdate, editby, sku, refx, linex, stageid)
            select trno, line, itemid, uom, whid, loc, ref, disc, cost, qty, void, rrcost, rrqty,
            ext, rem, encodeddate, qa, encodedby, editdate, editby, sku, refx, linex, stageid
            from " . $this->hstock . " where trno = ?";
            //jostock
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
            }
        }
    } //end function

    private function getstockselect($config)
    {
        $sqlselect = "select item.brand as brand, ifnull(mm.model_name, '') as model, item.itemid, stock.trno, stock.line, stock.refx, stock.linex, ifnull(item.barcode,'') as barcode, if(ifnull(sit.itemdesc, '') = '', item.itemname, sit.itemdesc) as itemname, item.isnoninv, stock.uom, stock.cost, stock.amt, 
        FORMAT(stock.isamt, " . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt, 
        FORMAT(stock.isqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        FORMAT(stock.ext, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, left(stock.encodeddate, 10) as encodeddate, stock.disc, stock.void, stock.ref, stock.whid, warehouse.client as wh, warehouse.clientname as whname, stock.loc, stock.expiry, item.brand, stock.rem, stock.palletid, stock.locid, ifnull(pallet.name, '') as pallet, ifnull(location.loc, '') as location, ifnull(uom.factor, 1) as uomfactor, round(case when (stock.Amt > 0 and stock.iss > 0 and stock.Cost > 0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss)) / head.forex) * 100) else 0 end, 2) markup, stock.rebate, ifnull(stock.stageid, 0) as stageid, ifnull(st.stage, '') as stage,
        '' as bgcolor,
        '' as errcolor ";
        return $sqlselect;
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry =  $sqlselect . "  
        FROM glstock as stock 
        left join glhead as head on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join model_masterfile as mm on mm.model_id = item.model
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join pallet on pallet.line=stock.palletid 
        left join location on location.line=stock.locid 
        left join client as warehouse on warehouse.clientid=stock.whid 
        left join stagesmasterfile as st on st.line = stock.stageid
        left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
        left join hcntnuminfo as info on info.trno = head.trno
        where head.doc = 'MI' and info.jotrno = $trno order by line";


        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $stock;
    } //end function

    public function openstockline($config, $line, $trno)
    {
        $sqlselect = $this->getstockselect($config);
        $qry = $sqlselect . "  
        FROM glstock as stock 
        left join glhead as head on head.trno = stock.trno
        left join item on item.itemid=stock.itemid 
        left join model_masterfile as mm on mm.model_id = item.model
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join pallet on pallet.line=stock.palletid 
        left join location on location.line=stock.locid 
        left join client as warehouse on warehouse.clientid=stock.whid 
        left join stagesmasterfile as st on st.line = stock.stageid
        left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
        left join hcntnuminfo as info on info.trno = head.trno
        where stock.trno = ? and stock.line = ?";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
        return $stock;
    } // end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'getpendingmi':
                return $this->getpendingmi($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function deleteallitem($config)
    {
        $isallow = true;
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('update hcntnuminfo set jotrno = 0 where jotrno=?', 'update', [$trno]);
        $this->coreFunctions->execqry('delete from jostock where trno=?', 'delete', [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }

    public function getpendingmi($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $rows = [];
        foreach ($config['params']['rows'] as $key => $value) {
            $qry = "select item.brand as brand, ifnull(mm.model_name, '') as model, item.itemid, stock.trno, stock.line, stock.refx, stock.linex, item.barcode, if(ifnull(sit.itemdesc, '') = '', item.itemname, sit.itemdesc) as itemname, item.isnoninv, stock.uom, stock.cost, stock." . $this->hamt . ", stock." . $this->hqty . " as iss,
            FORMAT(stock.isamt, " . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
            FORMAT(stock.isqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
            FORMAT(stock.ext, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
            left(stock.encodeddate, 10) as encodeddate, stock.disc, stock.void, stock.ref, stock.whid, warehouse.client as wh, warehouse.clientname as whname, stock.loc, stock.expiry, item.brand, stock.rem, stock.palletid, stock.locid, ifnull(pallet.name, '') as pallet, ifnull(location.loc, '') as location, ifnull(uom.factor, 1) as uomfactor, round(case when (stock.Amt > 0 and stock.iss > 0 and stock.Cost > 0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss)) / head.forex) * 100) else 0 end, 2) markup, stock.rebate, ifnull(stock.stageid, 0) as stageid, ifnull(st.stage, '') as stage, '' as bgcolor, '' as errcolor
            from glstock as stock 
            left join glhead as head on head.trno = stock.trno
            left join item on item.itemid = stock.itemid 
            left join model_masterfile as mm on mm.model_id = item.model
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom 
            left join pallet on pallet.line = stock.palletid 
            left join location on location.line = stock.locid 
            left join client as warehouse on warehouse.clientid = stock.whid 
            left join stagesmasterfile as st on st.line = stock.stageid
            left join hstockinfo as sit on sit.trno = stock.trno and sit.line = stock.line
            left join hcntnuminfo as info on info.trno = head.trno
            where head.doc = 'MI' and stock.trno = ?";
            $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
            $qry2 = "update hcntnuminfo set jotrno = " . $trno . " where trno = ?";
            $return = $this->coreFunctions->execqry($qry2, "update", [$config['params']['rows'][$key]['trno']]);

            if (!empty($data)) {
                for ($i = 0; $i < count($data); $i++) {
                    if ($return == 1) {
                        array_push($rows, $data[$i]);
                    }
                }
            } //end if
        } //end foreach
        return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
    }
    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $this->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno = ? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
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
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
