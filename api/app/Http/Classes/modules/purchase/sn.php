<?php

namespace App\Http\Classes\modules\purchase;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class sn
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SUPPLIER INVOICE';
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
    private $stockselect;
    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';
    public $defaultContra = 'AP1';

    private $fields = [
        'trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur',
        'wh', 'address', 'contra', 'tax', 'vattype', 'projectid', 'subproject', 'waybill', 'ewt', 'ewtrate'
    ];
    private $except = ['trno', 'dateid', 'due'];
    private $acctg = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary']
    ];

    private $barcode;

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
        $this->barcode = new  DNS1D;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2240,
            'edit' => 2241,
            'new' => 2242,
            'save' => 2243,
            // 'change' => 2244, remove change doc
            'delete' => 2245,
            'print' => 2246,
            'lock' => 2247,
            'unlock' => 2248,
            'acctg' => 2251,
            'post' => 2249,
            'unpost' => 2250,
            'additem' => 2252,
            'deleteitem' => 2253
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $isproject = $this->companysetup->getisproject($config['params']);
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];

        $condition = '';
        $projectfilter = '';
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $limit = "limit 150";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
            $limit = "";
        }

        if ($isproject) {
            $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
            $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
            $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
            if ($viewall == '0') {
                $projectfilter = " and head.projectid = " . $projectid . " ";
            }
        }
        $status = "'DRAFT'";

        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                if ($companyid == 3) { //conti
                    $condition = ' and num.postdate is null and head.lockdate is null ';
                }
                break;

            case 'locked':
                $condition = ' and num.postdate is null and head.lockdate is not null ';
                $status = "'LOCKED'";
                break;

            case 'posted':
                $condition = ' and num.postdate is not null ';
                break;
        }
        $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  
                from " . $this->head . " as head left join " . $this->tablenum . " as num 
                on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . " " . $filtersearch . "
                union all
                select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
                from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
                on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . " " . $filtersearch . "
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
            'help'
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

        return $buttons;
    } // createHeadbutton

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);

        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];
        $isexpiry = $this->companysetup->getisexpiry($config['params']);
        $isproject = $this->companysetup->getisproject($config['params']);
        $ispallet = $this->companysetup->getispallet($config['params']);
        $isfa = $this->companysetup->getisfixasset($config['params']);
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

        $headgridbtns = ['viewdistribution', 'viewref'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => ['action', 'docno', 'dateid', 'ext'],
                'totalfield' => 'ext',
                'headgridbtns' => $headgridbtns
            ],
            'multigrid2' => ['action' => 'tableentry', 'lookupclass' => 'viewsupplierinvoicerritems', 'label' => 'Inventory']
        ];

        $stockbuttons = ['delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][2]['type'] = 'label';

        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = 'RR Documents';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['pendingrrsn'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        if ($config['params']['companyid'] == 39) array_push($fields, 'freight'); //cbbsi
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Vendor');
        data_set($col1, 'docno.label', 'Transaction#');

        $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dexpacnoname', 'dwhname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dacnoname.label', 'AP Account');
        data_set($col2, 'dwhname.condition', ['checkstock']);
        data_set($col2, 'dexpacnoname.lookupclass', 'CG');

        $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname'];
        if ($config['params']['companyid'] == 3) { //conti
            array_push($fields, 'dewt');
        }
        if ($config['params']['companyid'] == 39) array_push($fields, 'dewt', 'trnxtype'); //cbbsi
        $col3 = $this->fieldClass->create($fields);
        if ($this->companysetup->getisproject($config['params'])) {
            $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);

            if ($viewall) {
                data_set($col3, 'dprojectname.lookupclass', 'projectcode');
                data_set($col3, 'dprojectname.addedparams', []);
                data_set($col3, 'dprojectname.required', true);
                data_set($col3, 'dprojectname.condition', ['checkstock']);
                $fields = ['rem', 'subprojectname'];
                $col4 = $this->fieldClass->create($fields);
                data_set($col4, 'rem.style', 'height: 130px; max-width: 400px');
                data_set($col4, 'subprojectname.required', true);
            } else {
                data_set($col3, 'dprojectname.type', 'input');
                $fields = ['rem', 'subprojectname'];
                $col4 = $this->fieldClass->create($fields);
                data_set($col4, 'rem.style', 'height: 130px; max-width: 400px');
                data_set($col4, 'subprojectname.type', 'lookup');
                data_set($col4, 'subprojectname.lookupclass', 'lookupsubproject');
                data_set($col4, 'subprojectname.action', 'lookupsubproject');
                data_set($col4, 'subprojectname.addedparams', ['projectid']);
                data_set($col4, 'subprojectname.required', true);
            }
        } else {
            $fields = ['rem'];
            $col4 = $this->fieldClass->create($fields);
        }

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['due'] = $this->othersClass->getCurrentDate();
        $data[0]['client'] = '';
        $data[0]['clientname'] = '';
        $data[0]['yourref'] = '';
        $data[0]['shipto'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['tax'] = 0;
        $data[0]['vattype'] = 'NON-VATABLE';
        $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        $data[0]['wh'] = $this->companysetup->getwh($params);
        $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
        $data[0]['whname'] = $name;

        $data[0]['waybill'] = '';
        $data[0]['expacnoname'] = '';
        if ($params['companyid'] == 39) { //cbbsi
            $data[0]['contra2'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
            $data[0]['waybill'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG1']);
            $data[0]['acnoname2'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra2']]);
            $data[0]['freight'] = '';
            $data[0]['trnxtype'] = '';
        }

        $isproject = $this->companysetup->getisproject($params);

        if ($isproject) {
            $viewall = $this->othersClass->checkAccess($params['user'], 2232);
            $data[0]['projectid'] = '0';
            $data[0]['projectname'] = '';
            $data[0]['projectcode'] = '';

            if ($viewall == '0') {
                $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
                $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
                $data[0]['projectcode'] =  $pid;
                $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);
            }
        } else {
            $data[0]['projectid'] = '0';
            $data[0]['projectname'] = '';
            $data[0]['projectcode'] = '';
        }

        $data[0]['dprojectname'] = '';
        $data[0]['subproject'] = '0';
        $data[0]['subprojectname'] = '';
        $data[0]['address'] = '';
        $data[0]['ewtrate'] = 0;
        $data[0]['ewt'] = '';
        if ($params['companyid'] == 39) { //cbbsi
            $data[0]['ewt'] = $this->coreFunctions->getfieldvalue('ewtlist', 'code', 'rate=1');
            $data[0]['ewtrate'] = $this->coreFunctions->getfieldvalue('ewtlist', 'rate', 'rate=1');
        }
        return $data;
    }

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $center = $config['params']['center'];
        $isproject = $this->companysetup->getisproject($config['params']);
        $projectfilter = "";

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
        $tablenum = $this->tablenum;

        if ($isproject) {
            $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
            $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
            $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
            if ($viewall == '0') {
                $projectfilter = " and head.projectid = " . $projectid . " ";
            }
        }


        $qryselect = "select 
      num.center,
      head.trno, 
      head.docno,
      client.client,
      head.terms,
      head.cur,
      head.forex,
      head.yourref,
      head.ourref,
      head.contra,
      coa.acnoname,
      '' as dacnoname,
      head.waybill,
      coa2.acnoname as expacnoname,
      coa2.acnoname as acnoname2,
      coa2.acno as contra2,
      '' as dexpacnoname,
      left(head.dateid,10) as dateid, 
      head.clientname,
      head.address, 
      head.shipto, 
      date_format(head.createdate,'%Y-%m-%d') as createdate,
      head.rem,
      head.tax,
      head.vattype,
      '' as dvattype,
      warehouse.client as wh,
      warehouse.clientname as whname, 
      '' as dwhname,
      head.projectid,
      '' as dprojectname,
      left(head.due,10) as due, 
      client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
      head.ewt,head.ewtrate,'' as dewt,head.trnxtype, numinfo.freight   ";


        $qry = $qryselect . " from $table as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as warehouse on warehouse.client = head.wh
    left join coa on coa.acno=head.contra
    left join coa as coa2 on coa2.acno=head.waybill
    left join projectmasterfile as p on p.line=head.projectid         
    left join subproject as s on s.line = head.subproject
    left join cntnuminfo as numinfo on numinfo.trno=head.trno
    where head.trno = ? and num.doc=? and num.center = ? " . $projectfilter . "
    union all " . $qryselect . " from $htable as head
    left join $tablenum as num on num.trno = head.trno
    left join client on head.clientid = client.clientid
    left join client as warehouse on warehouse.clientid = head.whid
    left join coa on coa.acno=head.contra 
    left join coa as coa2 on coa2.acno=head.waybill
    left join projectmasterfile as p on p.line=head.projectid         
    left join subproject as s on s.line = head.subproject
    left join hcntnuminfo as numinfo on numinfo.trno=head.trno
    where head.trno = ? and num.doc=? and num.center=? " . $projectfilter;

        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
        if (!empty($head)) {
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $stock = $this->openstock($config);
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
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

        $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($config['params']['companyid'] == 39) $data['trnxtype'] = $head['trnxtype']; //cbbsi
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            if ($config['params']['companyid'] == 39) { //cbbsi
                $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
                if (floatval($exist) != 0) {
                    $cdata = ['freight' => $this->othersClass->sanitizekeyfield('freight', $head['freight'])];
                    $this->coreFunctions->sbcupdate('cntnuminfo', $cdata, ['trno' => $head['trno']]);
                } else {
                    $cdata = [
                        'trno' => $head['trno'],
                        'freight' => $this->othersClass->sanitizekeyfield('freight', $head['freight'])
                    ];
                    $this->coreFunctions->sbcinsert('cntnuminfo', $cdata);
                }
            }
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            if ($config['params']['companyid'] == 39) { //cbbsi
                $exist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
                if (floatval($exist) != 0) {
                    $cdata = ['freight' => $this->othersClass->sanitizekeyfield('freight', $head['freight'])];
                    $this->coreFunctions->sbcupdate('cntnuminfo', $cdata, ['trno' => $head['trno']]);
                } else {
                    $cdata = [
                        'trno' => $head['trno'],
                        'freight' => $this->othersClass->sanitizekeyfield('freight', $head['freight'])
                    ];
                    $this->coreFunctions->sbcinsert('cntnuminfo', $cdata);
                }
            }
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);

        $this->coreFunctions->execqry('update cntnum set svnum=0 where svnum=?', 'update', [$trno]);
        $this->othersClass->deleteattachments($config);

        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'getrrsummary':
                return $this->getrrsummary($config);
                break;

            case 'deleteitem':
                return $this->deleteitem($config);
                break;

            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getrrsummary($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $docno = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$trno]);
        foreach ($config['params']['rows'] as $key => $value) {
            $this->coreFunctions->sbcupdate('cntnum', ['svnum' => $trno], ['trno' => $value['trno']]);
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'ADD RR - ' . $value['docno']);
            $this->logger->sbcwritelog($value['trno'], $config, 'DETAILS', 'TAGGED INVOICE - ' . $docno);
        } //end foreach
        $rows = $this->openstock($config);
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Successfull...'];
    } //end function    


    public function openstock($config)
    {
        $trno = $config['params']['trno'];
        $qry = "select " . $trno . "  as trno, cntnum.docno, cntnum.trno as rrtrno, glhead.dateid, '' as bgcolor, 
        round(ifnull(sum(s.ext),0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as ext
        from cntnum left join glhead on glhead.trno=cntnum.trno left join glstock as s on s.trno=glhead.trno 
        where cntnum.svnum=? group by cntnum.docno, cntnum.trno, glhead.dateid order by cntnum.docno";
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function deleteitem($config)
    {
        $trno = $config['params']['trno'];
        $docno = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$trno]);

        $row = $config['params']['row'];
        $this->coreFunctions->execqry('update cntnum set svnum=0 where trno=?', 'update', [$row['rrtrno']]);

        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'DELETE RR - ' . $row['docno']);
        $this->logger->sbcwritelog($row['trno'], $config, 'DETAILS', 'UNTAGGED INVOICE - ' . $docno);

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $trnxtype = $this->coreFunctions->getfieldvalue($this->head, "trnxtype", "trno=?", [$trno]);
        $systemtype = $this->companysetup->getsystemtype($config['params']);

        if ($this->companysetup->isinvonly($config['params'])) {
            return $this->othersClass->posttranstock($config);
        } else {
            $checkacct = $this->othersClass->checkcoaacct(['AP1', 'IN1', 'PD1', 'TX1']);

            if ($checkacct != '') {

                return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
            }

            if (!$this->createdistribution($config)) {
                return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
            } else {
                $return =  $this->othersClass->posttranstock($config);
                if ($companyid == 39 && strtoupper($trnxtype) == 'REGULAR') { //cbbsi
                    if ($return['status']) {
                        $this->updateitemcost($config);
                    }
                }
                return $return;
            }
        }
    } //end function    

    private function updateitemcost($config)
    {
        $trno = $config['params']['trno'];
        $qry = "select stock.line,
        item.itemid,        
        stock.cost        
        FROM cntnum left join
        $this->hstock as stock on stock.trno=cntnum.trno
        left join item on item.itemid=stock.itemid where cntnum.svnum =?  and stock.cost<>0 order by line";
        $data = $this->coreFunctions->opentable($qry, [$trno]);

        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if ($data[$k]->cost != 0) {
                    $this->coreFunctions->execqry("update item set amt8 = amt9 where itemid =" . $data[$k]->itemid);
                    $this->coreFunctions->execqry("update item set amt9 = " . $data[$k]->cost . " where itemid =" . $data[$k]->itemid);
                }
            }
        }
    }
    public function createdistribution($config)
    {
        $trno = $config['params']['trno'];
        $companyid = $config['params']['companyid'];
        $status = true;
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

        $headexp = $this->coreFunctions->datareader("select ifnull(coa.acno,'') as value from lahead as h left join coa on coa.acno=h.waybill where trno=?", [$trno]);
        if ($headexp == '') {
            $headexp = " ifnull(item.expense,'')";
        } else {
            $headexp = "'\\" . $headexp . "'";
        }

        if ($companyid == 39) { //cbbsi
            $qry = 'select snhead.dateid,client.client,snhead.tax, snhead.contra, head.cur,head.forex,stock.ext,wh.client as wh,' . $headexp . ' as expense,
            stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid, snhead.ewt, snhead.ewtrate,snhead.rem
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client as wh on wh.clientid=stock.whid 
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client on client.clientid=head.clientid 
            left join lahead as snhead on snhead.trno = cntnum.svnum
            where cntnum.svnum=?';
        } else {
            $qry = 'select head.dateid,client.client,head.tax, snhead.contra, head.cur,head.forex,stock.ext,wh.client as wh,' . $headexp . ' as expense,
            stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid, head.ewt, head.ewtrate,head.rem
            from glhead as head 
            left join glstock as stock on stock.trno=head.trno
            left join client as wh on wh.clientid=stock.whid 
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid=stock.itemid 
            left join client on client.clientid=head.clientid 
            left join lahead as snhead on snhead.trno = cntnum.svnum
            where cntnum.svnum=?';
        }

        $stock = $this->coreFunctions->opentable($qry, [$trno]);

        $tax = 0;
        if (!empty($stock)) {
            $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['CG2']);

            $vat = intval($this->coreFunctions->datareader("select tax as value from lahead where trno = ?
            union all
            select tax as value from glhead where trno = ?
            ", [$trno, $trno]));

            $tax1 = 0;
            $tax2 = 0;
            $ewtvalue = 0;
            if ($vat != 0) {
                $tax1 = 1 + ($vat / 100);
                $tax2 = $vat / 100;
            }
            foreach ($stock as $key => $value) {
                $params = [];
                $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
                if ($vat != 0) {
                    $tax = round(($stock[$key]->ext / $tax1), 2);
                    $tax = round($stock[$key]->ext - $tax, 2);
                }

                if ($value->ewt != '') {
                    if ($vat != 0) {
                        $amt = round(($stock[$key]->ext / $tax1), 2);
                        $ewtvalue = ($amt * ($stock[$key]->ewtrate / 100));
                        $this->coreFunctions->LogConsole($stock[$key]->ext . '-' . $ewtvalue);
                    } else {
                        $amt = round(($stock[$key]->ext), 2);
                        $ewtvalue = ($amt * ($stock[$key]->ewtrate / 100));
                        $this->coreFunctions->LogConsole($stock[$key]->ext . '-' . $ewtvalue);
                    }
                }

                $params = [
                    'client' => $stock[$key]->client,
                    'acno' => $stock[$key]->contra,
                    'ext' => $stock[$key]->ext,
                    'wh' => $stock[$key]->wh,
                    'date' => $stock[$key]->dateid,
                    'inventory' => $stock[$key]->expense !== '' ? $stock[$key]->expense : $invacct,
                    'tax' =>  $tax,
                    'discamt' => $disc * $stock[$key]->rrqty,
                    'cur' => $stock[$key]->cur,
                    'forex' => $stock[$key]->forex,
                    'cost' => $stock[$key]->ext - $tax,
                    'projectid' => $stock[$key]->projectid,
                    'subproject' => $stock[$key]->subproject,
                    'stageid' => $stock[$key]->stageid,
                    'ewt' => $stock[$key]->ewt,
                    'ewtrate' => $stock[$key]->ewtrate,
                    'ewtvalue' => $ewtvalue,
                    'rem' => $stock[$key]->rem
                ];
                $this->distribution($params, $config);
            }
        }

        $freight = $this->coreFunctions->getfieldvalue("cntnuminfo", "freight", "trno=?", [$trno]);
        if ($freight == '') {
            $freight = 0;
        }
        if ($freight != 0) {
            $qry = "select client,forex,dateid,cur,branch,deptid,contra,rem from " . $this->head . " where trno = ?";
            $d = $this->coreFunctions->opentable($qry, [$trno]);
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['FH1']);
            $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => 0, 'db' => $freight * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $freight, 'fcr' => 0];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
            $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($freight * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $freight, 'fdb' => 0, 'rem' => $d[0]->rem];

            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        if (!empty($this->acctg)) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            foreach ($this->acctg as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                }
                $this->acctg[$key]['editdate'] = $current_timestamp;
                $this->acctg[$key]['editby'] = $config['params']['user'];
                $this->acctg[$key]['encodeddate'] = $current_timestamp;
                $this->acctg[$key]['encodedby'] = $config['params']['user'];
                $this->acctg[$key]['trno'] = $config['params']['trno'];
                $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
                $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
                $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
                $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
            }
            if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                $status =  true;
            } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                $status = false;
            }
        }

        return $status;
    } //end function

    public function generateewt($config)
    {
        $trno = $config['params']['trno'];
        $data = $config['params']['row'];
        $status = true;
        $msg = '';
        $entry = [];
        $vatrate = 0;
        $vatrate2 = 0;
        $vatvalue = 0;
        $ewtvalue = 0;
        $dbval = 0;
        $crval = 0;
        $db = 0;
        $cr = 0;
        $damt = 0;
        $line = 0;
        $forex = $data[0]['forex'];
        $cur = $data[0]['cur'];
        $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
        $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
        $project = $this->coreFunctions->getfieldvalue($this->head, 'projectid', 'trno=?', [$trno]);

        if (empty($ewtacno) || empty($taxacno)) {
            $status = false;
            $msg = "Please setup account for EWT and Input VAT";
        } else {

            $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
            $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

            foreach ($data as $key => $value) {
                if ($value['isvat'] == 'true' or $value['isewt'] == 'true' or $value['isvewt'] == 'true') {
                    $damt   = $value['damt'];

                    if ($value['isvewt'] == 'true') { //for vewt
                        if (floatval($value['db']) != 0) {
                            $dbval = $damt;
                            $crval = 0;
                            $ewtvalue = $ewtvalue + (($dbval / 1.12) * ($value['ewtrate'] / 100));
                        } else {
                            $dbval = 0;
                            $crval = $damt;
                            $ewtvalue = $ewtvalue + ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
                        }
                    }

                    if ($value['isvat']  == 'true') { //for vat computation
                        $vatrate = 1.12;
                        $vatrate2 = .12;

                        if (floatval($value['db']) != 0) {
                            $dbval = $damt / $vatrate;
                            $crval  = 0;
                            $vatvalue = $vatvalue + ($dbval * $vatrate2);
                        } else {
                            $dbval = 0;
                            $crval = $damt / $vatrate;
                            $vatvalue =  $vatvalue + (($crval * $vatrate2) * -1);
                        }
                    }

                    if ($value['isewt']  == 'true') { //for ewt
                        if (floatval($value['db']) != 0) {
                            if ($value['isvat'] == 'true') {
                                $dbval = $damt / $vatrate;
                                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
                            } else {
                                $dbval = $damt;
                                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
                            }
                            $crval = 0;
                        } else {
                            if ($value['isvat'] == 'true') {
                                $crval = $damt / $vatrate;
                                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
                            } else {
                                $crval = $damt;
                                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
                            }
                            $dbval = 0;
                        }
                    }


                    $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
                    if ($value['refx'] != 0) {
                        if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
                            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
                            $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
                            $msg = "Payment Amount is greater than Amount Setup";
                            $status = false;
                            $vatvalue = 0;
                            $ewtvalue = 0;
                        }
                    }
                }
            }

            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;


            if ($vatvalue != 0) {
                $entry = [
                    'line' => $line, 'acnoid' => $taxacno, 'client' => $data[0]['client'], 'cr' => ($vatvalue < 0 ? abs(round($vatvalue, 2)) : 0), 'db' => ($vatvalue < 0 ? 0 : abs(round($vatvalue, 2))), 'postdate' => $data[0]['dateid'], 'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex, 'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project
                ];

                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                $line = $line + 1;
            }



            if ($ewtvalue != 0 && $status == true) {
                $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'projectid' => $project];

                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }

            if (!empty($this->acctg)) {
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                foreach ($this->acctg as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
                    }

                    $this->acctg[$key]['editdate'] = $current_timestamp;
                    $this->acctg[$key]['editby'] = $config['params']['user'];
                    $this->acctg[$key]['encodeddate'] = $current_timestamp;
                    $this->acctg[$key]['encodedby'] = $config['params']['user'];
                    $this->acctg[$key]['trno'] = $config['params']['trno'];
                }

                if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
                    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
                    $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
                    $status = true;
                    //return true;
                } else {
                    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
                    $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
                    $status = false;
                }
            }
        } //if (empty($ewtacno) || empty($taxacno)){

        $data = $this->opendetail($trno, $config);
        return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
    } //end function

    public function distribution($params, $config)
    {
        //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
        $entry = [];
        $forex = $params['forex'];
        if ($forex == 0) {
            $forex = 1;
        }
        $suppinvoice = $this->companysetup->getsupplierinvoice($config['params']);

        $cur = $params['cur'];
        $invamt = $params['cost']; //round(($params['ext']-$params['tax']) + $params['discamt'],2);
        $ap = floatval($params['ext']);

        //AP
        if (floatval($params['ext']) != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($ap * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $ap, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid'], 'rem' => $params['rem']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        //disc      
        if (floatval($params['discamt']) != 0) {
            $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
            $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        if (floatval($params['tax']) != 0) {
            // input tax
            $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
            $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        //INV
        if (floatval($invamt) != 0) {
            if (floatval($params['discamt']) != 0) {
                $invamt  = $invamt + ($params['discamt'] * $forex);
            }
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
            $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        if (floatval($params['ewtvalue']) != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
            $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($params['ewtvalue'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ewtvalue'], 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            if ($config['params']['companyid'] == 39) { //cbbsi
                $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
                $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['ewtvalue'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['ewtvalue'], 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'rem' => 'EWT', 'stageid' => $params['stageid']];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
        }
    } //end function

    public function unposttrans($config)
    {
        return $this->othersClass->unposttranstock($config);
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
        $companyid = $config['params']['companyid'];
        $this->logger->sbcviewreportlog($config);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);


        $dataparams = $config['params']['dataparams'];
        if ($companyid == 3 || $companyid == 39) { //conti,cbbsi
            if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
            if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
            if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        }
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
