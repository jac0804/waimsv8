<?php

namespace App\Http\Classes\modules\autoserv;

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
use App\Http\Classes\headClass;
use Illuminate\Support\Facades\Storage;
use App\Http\Classes\sbcscript\sbcscript;
use Exception;



class ak
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Quotation';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'qthead';
    public $hhead = '';
    public $stock = '';
    public $hstock = '';
    public $tablelogs = 'transnum_log';
    public $statlogs = 'transnum_stat';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    private $sbcscript;

    private $fields = [
        'trno',
        'docno',
        'dateid',
        'due',
        'client',
        'clientname',
        'yourref',
        'ourref',
        'rem',
        'terms',
        'forex',
        'cur',
        'address',
        'contra',
        'tax',
        'vattype',
        'carid',
        'modelid'
    ];

    private $except = ['trno', 'dateid'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary']
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
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5886,
            'edit' => 5887,
            'new' => 5888,
            'save' => 5889,
            // 'change' => 5895
            'delete' => 5890,
            'print' => 5891,
            'additem' => 5893,
            'edititem' => 5892,
            'deleteitem' => 5894
        );

        return $attrib;
    }

    public function createTab($access, $config)
    {


        $column = ['action',  'code', 'description', 'rem'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]

        ];
        $stockbuttons = ['save', 'delete', 'addtask'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$code]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['label'] = 'Job Description';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }
    public function createtabbutton($config)
    {

        $tbuttons = ['jobsetup'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {

        $getcols = ['action', 'listdocument', 'listdate', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        // $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }
    public function loaddoclisting($config)
    {

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $status = "";
        $limit = '';

        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $status = "'draft'";
        if ($search != "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";

        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = " 
        select head.trno, head.docno, $status as lblstatus, head.createby, head.editby, head.viewby
        from pthead as head 
        left join transnum as num on num.trno = head.trno
        where head.doc = ? and num.center = ? $filtersearch $orderby $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center]);
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


        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname',  'address', 'dvattype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Quotation#');

        $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dvattype', ['cur', 'forex'], ['yourref', 'ourref']];
        $col2 = $this->fieldClass->create($fields);

        $fields = [['vehicle', 'year'], ['modelname', 'mileage'], ['licenseno', 'type'], ['motorno', 'chassisno'], ['submodel', 'engine'], ['transmission', 'mvno']];
        $col3 = $this->fieldClass->create($fields);

        data_set($col3, 'vehicle.readonly', true);
        data_set($col3, 'vehicle.label', 'Car Make');
        data_set($col3, 'vehicle.action', 'lookupcarmake');
        data_set($col3, 'vehicle.lookupclass', 'lookupcarmake');
        data_set($col3, 'year.readonly', true);
        data_set($col3, 'modelname.addedparams', ['carid']);
        data_set($col3, 'modelname.action', 'lookupcarmake');
        data_set($col3, 'modelname.lookupclass', 'lookupcarmodel');
        data_set($col3, 'modelname.readonly', true);
        data_set($col3, 'licenseno.label', 'License');

        data_set($col3, 'mileage.label', 'Mileage');


        data_set($col3, 'type.type', 'input');
        data_set($col3, 'type.readonly', true);

        data_set($col3, 'transmission.required', false);
        data_set($col3, 'mvno.required', false);


        data_set($col3, 'licenseno.class', 'cslicenseno sbccsreadonly');
        data_set($col3, 'motorno.class', 'csmotorno sbccsreadonly');
        data_set($col3, 'submodel.class', 'cssubmodel sbccsreadonly');
        data_set($col3, 'transmission.class', 'cstransmission sbccsreadonly');

        data_set($col3, 'type.class', 'cstype sbccsreadonly');
        data_set($col3, 'mileage.class', 'csmileage sbccsreadonly');
        data_set($col3, 'chassisno.class', 'cschassisno sbccsreadonly');
        data_set($col3, 'engine.class', 'csengine sbccsreadonly');
        data_set($col3, 'mvno.class', 'csmvno sbccsreadonly');


        $fields = ['kmno', 'rem', 'rem1', 'porem'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.type', 'input');
        data_set($col4, 'rem.label', 'Customer Notes');

        data_set($col4, 'kmno.required', false);



        data_set($col4, 'rem1.label', 'Complaints');
        data_set($col4, 'rem1.type', 'ctextarea');
        data_set($col4, 'rem1.readonly', false);
        data_set($col4, 'porem.label', 'Recommendations');
        data_set($col4, 'porem.readonly', false);



        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.vattype,
         '' as dvattype,
         left(head.due,10) as due,
         client.groupid,cmake.carname as vehicle,
         model.model as modelname,model.year, model.type,model.sub_model as submodel,
         ifnull(hinfo.kmno,'') as kmno,ifnull(hinfo.complaints,'') as rem1,
         ifnull(hinfo.recomm,'') as porem";

        $qry = $qryselect . " from $head as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join cmake on cmake.id=head.carid
        left join cmodel as model on model.carid=cmake.id
        left join headinfotrans as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ? and left(num.bref,3) <> 'SJS'";
        // union all " . $qryselect . " from $htable as head
        // left join $tablenum as num on num.trno = head.trno
        // left join client on head.clientid = client.clientid
        // left join client as warehouse on warehouse.clientid = head.whid
        // left join client as agent on agent.clientid = head.agentid
        // left join coa on coa.acno=head.contra
        // left join cmake on cmake.id=head.carid
        // left join cmodel as model on model.carid=cmake.id
        // left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        // where head.trno = ? and num.doc=? and num.center=? and left(num.bref,3) <> 'SJS' ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

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
                'islocked' => false,
                'isposted' => false,
                'isnew' => false,
                'status' => true,
                'msg' => $msg,
                'clickobj' => [],
                'hidetabbtn' => [],
                'hideobj' => []
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
        $data[0]['docno'] = '';
        $data[0]['dateid'] = date('Y-m-d');
        $data[0]['due'] = date('Y-m-d');
        $data[0]['client'] = 'AQ0000000000001';
        $data[0]['clientname'] = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['client']]);
        $data[0]['address'] = $this->coreFunctions->getfieldvalue('client', 'addr', 'client=?', [$data[0]['client']]);
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['tax'] = 0;
        // $data[0]['dagentname'] = '';
        $data[0]['dvattype'] = '';
        // $data[0]['dacnoname'] = '';
        // $data[0]['agent'] = '';
        $data[0]['creditinfo'] = '';
        // $data[0]['agentname'] = '';
        $data[0]['vattype'] = 'NON-VATABLE';
        // $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
        // $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
        // $data[0]['wh'] = $this->companysetup->getwh($params);
        // $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
        // $data[0]['whname'] = $name;
        // $data[0]['dwhname'] = '';
        $data[0]['carid'] = 0;
        $data[0]['modelid'] = 0;

        $data[0]['kmno'] = '';
        $data[0]['rem1'] = '';
        $data[0]['porem'] = '';
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
            $insert = $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['description']);
        }
    }
    public function getstockselect($config)
    {
        $query = "select pt.line,pt.jobid,pt.pttrno as trno,pt.rem,jt.docno as code,jt.jobtitle as description,'' as bgcolor ";
        return $query;
    }


    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.pttrno = ?";
        $data = $this->coreFunctions->opentable($query, [$trno]);
        return $data;
    }
    public function openstockline($config)
    {
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt 
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.pttrno = ? and pt.line = ?";
        $data = $this->coreFunctions->opentable($query, [$trno, $line]);
        return $data;
    }


    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            // case 'addallitem': // save all item selected from lookup
            //     return $this->addallitem($config);
            //     break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            // case 'saveitem': //save all item edited
            //     return $this->updateitem($config);
            //     break;
            // case 'saveperitem':
            //     return $this->updateperitem($config);
            //     break;
            // case 'deleteallitem':
            //     return $this->deleteallitem($config);
            //     break;
            case 'getautojobsetup':
                return $this->getautojobsetup($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function getautojobsetup($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];

        foreach ($config['params']['rows'] as $key => $value) {

            $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
            $data = $this->coreFunctions->opentable($query, [$value['line']]);

            foreach ($data as $key2 => $value2) {

                $config['params']['data']['jobid'] = $value2->jobid;
                $config['params']['data']['rem'] = '';
                $config['params']['trno'] = $trno;
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
        } //end foreach
        $this->coreFunctions->LogConsole(json_encode($rows));
        return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
    }
    public function additem($action, $config)
    {

        $trno = $config['params']['trno'];

        if (isset($config['params']['data']['jobid'])) {
            $jobid = $config['params']['data']['jobid'];
        }
        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }


        $data = [
            'pttrno' => $trno,
            'jobid' => $jobid,
            'rem' => $rem,
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where pttrno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
            $data['line'] = $line;



            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
                $job = $this->coreFunctions->opentable($query, [$jobid]);

                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Code:' . $job[0]->code . ' ' . ' Description: ' . $job[0]->description);
                $this->loadheaddata($config);
                $row = $this->openstockline($config);
                return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
            } else {
                return ['status' => false, 'msg' => 'Add item Failed'];
            }
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];

            $data['editdate'] = $current_timestamp;
            $data['editby'] = $config['params']['user'];
            $return = true;
            $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
            return $return;
        }
    }
    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $data = $this->openstockline($config);
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $this->coreFunctions->execqry('delete from ptjobs where trno=? and line=?', 'delete', [$trno, $line]);

        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Job Description:' . $data[0]['description']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    } // end function
}
