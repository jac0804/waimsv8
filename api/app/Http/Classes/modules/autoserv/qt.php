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



class qt
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'QUOTATION'; //Quotation
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'qthead';
    public $hhead = 'hqthead';
    public $stock = 'ptjobs';
    public $hstock = 'hptjobs';
    public $tablelogs = 'transnum_log';
    public $statlogs = 'transnum_stat';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $stockselect;
    private $sbcscript;

    private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'address', 'tax', 'carid', 'recomm'];

    private $except = ['trno', 'dateid', 'due'];
    private $blnfields = [];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

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
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2133,
            'edit' => 2134,
            'new' => 2135,
            'save' => 2136,
            'delete' => 2138,
            'print' => 2139,
            'lock' => 2140,
            'unlock' => 2141,
            'changeamt' => 2142,
            'post' => 2143,
            'unpost' => 2144,
            'additem' => 2145,
            'edititem' => 2146,
            'deleteitem' => 2147
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


        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'client.lookupclass', 'customer');

        $fields = [['dateid', 'terms'], 'due', ['cur', 'forex'], ['yourref', 'ourref']];
        $col2 = $this->fieldClass->create($fields);

        $fields = [['vehicle', 'year'], ['modelname', 'mileage'], ['licenseno', 'type'], ['motorno', 'chassisno'], ['submodel', 'engine'], ['transmission', 'mvno']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'vehicle.readonly', true);
        data_set($col3, 'vehicle.type', 'input');
        data_set($col3, 'vehicle.label', 'Car Make');
        data_set($col3, 'year.readonly', true);
        data_set($col3, 'modelname.readonly', true);
        data_set($col3, 'modelname.type', 'input');

        data_set($col3, 'licenseno.label', 'License');

        data_set($col3, 'mileage.label', 'Mileage');
        data_set($col3, 'mileage.readonly', true);


        data_set($col3, 'type.type', 'input');
        data_set($col3, 'type.readonly', true);

        data_set($col3, 'transmission.required', false);
        data_set($col3, 'mvno.required', false);

        data_set($col3, 'year.class', 'csyear sbccsreadonly');
        data_set($col3, 'licenseno.class', 'cslicenseno sbccsreadonly');
        data_set($col3, 'motorno.class', 'csmotorno sbccsreadonly');
        data_set($col3, 'submodel.class', 'cssubmodel sbccsreadonly');
        data_set($col3, 'transmission.class', 'cstransmission sbccsreadonly');

        data_set($col3, 'type.class', 'cstype sbccsreadonly');
        data_set($col3, 'mileage.class', 'csmileage sbccsreadonly');
        data_set($col3, 'chassisno.class', 'cschassisno sbccsreadonly');
        data_set($col3, 'engine.class', 'csengine sbccsreadonly');
        data_set($col3, 'mvno.class', 'csmvno sbccsreadonly');

        data_set($col3, 'submodel.required', false);
        data_set($col3, 'year.required', false);
        data_set($col3, 'type.required', false);


        $fields = ['kmno', 'rem', 'rem1', 'porem'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.type', 'input');
        data_set($col4, 'rem.label', 'Customer Notes');

        data_set($col4, 'kmno.required', false);



        data_set($col4, 'rem1.label', 'Complaints');
        data_set($col4, 'rem1.type', 'ctextarea');
        data_set($col4, 'rem1.readonly', false);
        data_set($col4, 'rem1.class', 'csrem1');
        data_set($col4, 'porem.label', 'Recommendations');
        data_set($col4, 'porem.readonly', false);


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
        $data[0]['address'] = '';
        $data[0]['ourref'] = '';
        $data[0]['rem'] = '';
        $data[0]['terms'] = '';
        $data[0]['forex'] = 1;
        $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
        $data[0]['tax'] = 0;
        $data[0]['carid'] = 0;
        $data[0]['kmno'] = '';
        $data[0]['rem1'] = '';
        $data[0]['porem'] = '';
        return $data;
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
        $tab['tableentry'] = ['action' => 'autoserventry', 'lookupclass' => 'entrylabor', 'label' => 'TASK/LABOR'];
        $tab['tableentry2'] = ['action' => 'autoserventry', 'lookupclass' => 'entryparts', 'label' => 'ITEM/PARTS'];
        $stockbuttons = ['save', 'delete', 'addtask'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0]['inventory']['columns'][$code]['type'] = 'label';
        $obj[0]['inventory']['columns'][$code]['style'] = 'text-align: left; width: 125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0]['inventory']['columns'][$description]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['label'] = 'Job Description';
        $obj[0]['inventory']['columns'][$description]['style'] = 'text-align: left; width: 125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        return $obj;
    }
    public function createtabbutton($config)
    {

        $tbuttons = ['addvehicle', 'addjob']; //deleteallitem
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting($config)
    {

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$liststatus]['name'] = 'statuscolor';
        // $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
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

        switch ($itemfilter) {
            case 'posted':
                $filter = " and num.postdate is not null ";
                break;
            case 'draft':
                $filter = " and num.postdate is null ";
                break;
            case 'locked':
                $filter = "  and head.lockdate is not null and num.postdate is null ";
                break;
            default:
                $filter = "";
                break;
        }

        $qry = " 
        select head.trno, head.docno, case when lockdate is null then 'draft' else 'Locked' end  as status, head.createby, head.editby, head.viewby,date(head.dateid) as dateid,
        case when head.lockdate is not null then 'green' else 'red' end as statuscolor
        from " . $this->head . " as head 
        left join transnum as num on num.trno = head.trno
        where head.doc = ? and num.center = ? $filter $filtersearch
        union all
        select head.trno, head.docno, case when lockdate is null then 'posted' else 'Locked' end  as status, head.createby, head.editby, head.viewby,date(head.dateid) as dateid,
        case when head.lockdate is not null then 'green' else 'grey' end as statuscolor
        from " . $this->hhead . " as head 
        left join transnum as num on num.trno = head.trno
        where head.doc = ? and num.center = ? $filter $filtersearch
        $orderby $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $doc, $center]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
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
         left(head.due,10) as due,
         client.groupid,
         ifnull(hinfo.kmno,'') as kmno,ifnull(hinfo.complaints,'') as rem1,
         ifnull(hinfo.recomm,'') as porem,
         ifnull(cvh.cmake,'') as vehicle,ifnull(model.model,'') as modelname,
         ifnull(cvh.licenseno,'') as licenseno,ifnull(cvh.motorno,'') as motorno,
         ifnull(model.sub_model,'') as submodel, ifnull(cvh.transmission,'') as transmission,
          model.cryear as year,ifnull(cvh.mileage,0) as mileage,ifnull(model.crtype,'') as type,
          ifnull(cvh.chassis,'') as chassisno,ifnull(cvh.carengine,'') as engine,
          ifnull(cvh.mvno,'') as mvno, cvh.line";

        $qry = $qryselect . " from " . $this->head . " as head
        left join $tablenum as num on num.trno = head.trno
        left join client on client.client = head.client
        left join cvehicle as cvh on cvh.clientid=client.clientid and cvh.line = head.carid
        left join cmodel as model on model.line=cvh.cmodelline
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from " . $this->hhead . " as head
        left join $tablenum as num on num.trno = head.trno
        left join client on client.client = head.client
        left join cvehicle as cvh on cvh.clientid=client.clientid and cvh.line = head.carid
        left join cmodel as model on model.line=cvh.cmodelline
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center=? ";


        $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);

        if (!empty($head)) {

            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $postdate = $this->coreFunctions->datareader("select postdate as value from " . $this->tablenum . " where trno = ?", [$trno]);
            $lockdate = $this->coreFunctions->datareader("
            select lockdate as value from pthead where trno = ?
            union all 
            select lockdate as value from hpthead where trno = ?", [$trno, $trno]);
            $lockdate = $lockdate != null ? true : false;
            $postdate = $postdate != null ? true : false;


            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            $hidetabbtn = [];
            $clickobj = [];

            $hideobj = [];
            return  [
                'head' => $head,
                'griddata' => ['inventory' => $stock],
                'islocked' => $lockdate,
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
    public function getstockselect($config)
    {
        $query = "select pt.line,pt.jobid,pt.trno as trno,pt.rem,jt.docno as code,jt.jobtitle as description,'' as bgcolor ";
        return $query;
    }


    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ?
        union all
        $sqlselect from hptjobs as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ?";

        $data = $this->coreFunctions->opentable($query, [$trno, $trno]);
        return $data;
    }
    public function openstockline($config)
    {
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt 
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ? and pt.line = ?
        union all
        
        $sqlselect from hptjobs as pt 
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ? and pt.line = ?";
        $data = $this->coreFunctions->opentable($query, [$trno, $line, $trno, $line]);
        return $data;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
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


        if ($data['terms'] == '') {
            $data['due'] = $data['dateid'];
        } else {
            $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
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
    } // end function
    public function stockstatus($config)
    {
        switch ($config['params']['action']) {
            case 'additem':
                return $this->additem('insert', $config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'saveperitem':
                return $this->updateperitem($config);
                break;
            // case 'deleteallitem':
            //     return $this->deleteallitem($config);
            //     break;
            case 'getautojob':
                return $this->getautojob($config);
                break;
            case 'getvehicle':
                return $this->getvehicle($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function getautojob($config)
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
    public function getvehicle($config)
    {
        $trno = $config['params']['trno'];
        $vehicleline = $config['params']['rows'][0]['keyid'];
        $data['carid'] = $vehicleline;
        $updatehead = $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);
        $stat = true;
        $msg = "Header updated successfully.";
        $reload = true;

        if ($updatehead != 1) {
            $stat = false;
            $msg = "'Failed to update the header";
            $reload = false;
        }
        return ['status' => $stat, 'msg' => $msg, 'reloadhead' => $reload];
    }
    public function updateperitem($config)
    {
        $config['params']['data'] = $config['params']['row'];
        $this->additem('update', $config);

        $data = $this->openstockline($config);
        return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $this->coreFunctions->execqry('delete from ptjobs where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from pttask where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ptstock where trno=?', 'delete', [$trno]);

        $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'REMOVED ALL: Jobs, Task/labor and Parts/Item');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
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
            'trno' => $trno,
            'jobid' => $jobid,
            'rem' => $rem,
        ];

        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
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
        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $query = "select * from pttask where trno = ? and jobline = ? "; #check stock before delete
        $pttask = $this->coreFunctions->opentable($query, [$trno, $line]);
        if (!empty($pttask)) {
            return ['status' => false, 'msg' => "Cannot delete this Jobs; already have Task/Labor."];
        }
        $this->coreFunctions->execqry('delete from ptjobs where trno=? and line=?', 'delete', [$trno, $line]);
        $data = $this->openstockline($config);
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Job Description:' . $config['params']['row']['description']);
        return ['status' => true, 'msg' => 'Item was successfully deleted.'];
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
        $this->coreFunctions->execqry('delete from pthead where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    }

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $docno = $this->coreFunctions->datareader("select docno as value from pthead where trno = ?", [$trno]);
        try {
            $this->posthead($config, true); // no need na mag return
            $this->postjobs($config, true);
            $this->posttasks($config, true);
            $this->poststock($config, true);

            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from pthead where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from ptjobs where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from pttask where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from ptstock where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } catch (Exception $e) {
            $this->coreFunctions->execqry("delete from hpthead where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hptjobs where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hpttask where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hptstock where trno=?", "delete", [$trno]);
            return ['status' => false, 'msg' => 'Error on Posting: ' . $e->getMessage()];
        }
    }
    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $docno = $this->coreFunctions->datareader("select docno as value from hpthead where trno = ?", [$trno]);
        try {
            $this->posthead($config, false);
            $this->postjobs($config, false);
            $this->posttasks($config, false);
            $this->poststock($config, false);

            $data = ['postdate' => null, 'postedby' => '', 'statid' => 0];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from hpthead where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hptjobs where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hpttask where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from hptstock where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOST', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } catch (Exception $e) {

            $this->coreFunctions->execqry("delete from pthead where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from ptjobs where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from pttask where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from ptstock where trno=?", "delete", [$trno]);
            return ['status' => false, 'msg' => 'Error on UnPosting: ' . $e->getMessage()];
        }
    }
    public function posthead($config, $post)
    {
        $trno = $config['params']['trno'];
        if ($post) {
            $qry = "insert into hqthead (trno,doc,docno,description,rem,dateid,createdate,createby,editdate,editby,viewdate,viewby,lockdate,lockuser)
        select trno,doc,docno,description,rem,dateid,createdate,createby,editdate,editby,viewdate,viewby,lockdate,lockuser
        from qthead as head
        where head.trno=? limit 1";
        } else {
            $qry = "insert into qthead (trno,doc,docno,description,rem,dateid,createdate,createby,editdate,editby,viewdate,viewby,lockdate,lockuser)
        select trno,doc,docno,description,rem,dateid,createdate,createby,editdate,editby,viewdate,viewby,lockdate,lockuser
        from hqthead as head
        where head.trno=? limit 1";
        }
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if (!$posthead) {
            throw new Exception('Failed to insert head');
        }
        return $posthead;
    }
    public function postjobs($config, $post)
    {
        $trno = $config['params']['trno'];
        if ($post) {
            $qry = "insert into hptjobs (trno,line,jobid,rem,encodeddate,encodedby,editdate,editby,packagetrno)
        select trno,line,jobid,rem,encodeddate,encodedby,editdate,editby,packagetrno
        from ptjobs as jobs
        where jobs.trno=?";
        } else {
            $qry = "insert into ptjobs (trno,line,jobid,rem,encodeddate,encodedby,editdate,editby,packagetrno)
        select trno,line,jobid,rem,encodeddate,encodedby,editdate,editby,packagetrno
        from hptjobs as jobs
        where jobs.trno=?";
        }
        $postjobs = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if (!$postjobs) {
            throw new Exception('Failed to insert jobs');
        }
        return $postjobs;
    }
    public function posttasks($config, $post)
    {
        $trno = $config['params']['trno'];
        if ($post) {
            $qry = "insert into hpttask (trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby)
        select trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby
        from pttask as task
        where task.trno=?";
        } else {
            $qry = "insert into pttask (trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby)
        select trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby
        from hpttask as task
        where task.trno=?";
        }
        $posttasks = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if (!$posttasks) {
            throw new Exception('Failed to insert task');
        }
        return $posttasks;
    }
    public function poststock($config, $post)
    {
        $trno = $config['params']['trno'];
        if ($post) {
            $qry = "insert into hptstock (trno,line,uom,disc,rem,amt,isqty,isamt,iss,ext,qa,void,encodeddate,encodedby,editdate,editby,
        loc,expiry,kgs,itemid,whid,refx,linex,ref,projectid,taskline,jobline)
        select trno,line,uom,disc,rem,amt,isqty,isamt,iss,ext,qa,void,encodeddate,encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,refx,linex,ref,projectid,taskline,jobline
        from ptstock where trno=?";
        } else {
            $qry = "insert into ptstock (trno,line,uom,disc,rem,amt,isqty,isamt,iss,ext,qa,void,encodeddate,encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,refx,linex,ref,projectid,taskline,jobline
        )
        select trno,line,uom,disc,rem,amt,isqty,isamt,iss,ext,qa,void,encodeddate,encodedby,editdate,editby,loc,expiry,kgs,itemid,whid,refx,linex,ref,projectid,taskline,jobline
        from hptstock where trno=?";
        }
        $poststock = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if (!$poststock) {
            throw new Exception('Failed to insert stock');
        }
        return $poststock;
    }
}
