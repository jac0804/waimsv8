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

class aw
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'WORK ORDER';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'awhead';
    public $hhead = 'awhead';
    public $stock = 'ptjobs';
    public $hstock = 'ptjobs';
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
        'rem',
        'client',
        'clientname',
        'address',
        'tax',
        'ref',
        'kmno',
        'recommend',
        'cryear',
        'licenseno',
        'make',
        'modelname',
        'crtype',
        'submodel',
        'carengine',
        'transmission',
        'mvno',
        'mileage',
        'manufacturer',
        'chassisno'
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
    } // end fn
    public function getAttrib()
    {
        $attrib = array(
            'view' => 5899,
            'edit' => 5901,
            'new' => 5902,
            'save' => 5903,
            // 'change' => 67, remove change doc 5854
            'delete' => 5904,
            'print' => 5905,
            'additem' => 5907,
            'edititem' => 5906,
            'deleteitem' => 5908
        );

        return $attrib;
    } // end getAttrib
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
    } // end createHeadbutton

    public function createHeadField($config)
    {
        $fields = ['docno', 'client', 'clientname', 'address'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'clientname.label', 'Customer Name');

        $fields = [['dateid', 'tax'], ['ref', 'kmno']];
        $col2 = $this->fieldClass->create($fields);

        // tax
        data_set($col2, 'tax.label', 'Tax %');
        data_set($col2, 'tax.readonly', false);
        data_set($col2, 'tax.class', 'cstax sbccsenablealways');

        // dateid
        data_set($col2, 'dateid.label', 'Work Order Date');
        data_set($col2, 'dateid.type', 'date');

        // reference no
        data_set($col2, 'ref.label', 'Reference No');
        data_set($col2, 'kmno.label', 'KM #');
        data_set($col2, 'kmno.required', false);
        data_set($col2, 'kmno.class', 'cskmno sbccsenablealways');

        $fields = [
            ['byear', 'licenseno'],
            ['make', 'modelname'],
            ['crtype', 'submodel'],
            ['carengine', 'manufacturer'],
            ['transmission', 'chassisno'],
            ['mvno', 'mileage']
        ];
        $col3 = $this->fieldClass->create($fields);

        // engine
        data_set($col3, 'carengine.label', 'Engine');

        //year     
        data_set($col3, 'byear.label', 'Year');
        data_set($col3, 'byear.required', false);

        // transmission
        data_set($col3, 'transmission.required', false);

        // mileage
        data_set($col3, 'mileage.label', 'Mileage');

        // mvno
        data_set($col3, 'mvno.required', false);

        // maker no
        data_set($col3, 'manufacturer.label', 'Maker #');

        //license no
        data_set($col3, 'licenseno.readonly', false);
        data_set($col3, 'licenseno.class', 'cslicenseno sbccsenablealways');

        // carmake
        data_set($col3, 'make.readonly', false);
        data_set($col3, 'make.required', false);
        data_set($col3, 'make.class', 'csmake sbccsenablealways');

        // car model
        data_set($col3, 'modelname.label', 'Car Model');
        data_set($col3, 'modelname.type', 'input');
        data_set($col3, 'modelname.readonly', false);
        data_set($col3, 'modelname.class', 'csmodel sbccsenablealways');

        // type 
        data_set($col3, 'crtype.readonly', false);
        data_set($col3, 'crtype.required', false);
        data_set($col3, 'crtype.class', 'cscrtype sbccsenablealways');
        data_set($col3, 'crtype.type', 'input');
        data_set($col3, 'crtype.label', 'Type');

        // submodel
        data_set($col3, 'submodel.readonly', false);
        data_set($col3, 'submodel.required', false);
        data_set($col3, 'submodel.class', 'cssubmodel sbccsenablealways');

        $fields = ['rem', 'recommend'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem.label', 'Customer Notes');
        data_set($col4, 'recommend.label', 'Recommendations');

        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    } // end createHeadField

    public function createTab($access, $config)
    {

        // $tab['tableentry'] = ['action' => 'autoserventry', 'lookupclass' => 'entryjobs', 'label' => 'Jobs'];
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
        $obj[0]['inventory']['columns'][$code]['style'] = 'text-align: left; width: 125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0]['inventory']['columns'][$description]['type'] = 'label';
        $obj[0]['inventory']['columns'][$description]['label'] = 'Job Description';
        $obj[0]['inventory']['columns'][$description]['style'] = 'text-align: left; width: 125px;whiteSpace: normal;min-width:125px;max-width:125px;';
        $obj[0][$this->gridname]['descriptionrow'] = [];
        return $obj;
    } // end createTab

    public function createtabbutton($config)
    {
        $tbuttons = ['addjob', 'addvehicle'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    } // end createtabbutton

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
    } // end createdoclisting
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
        from awhead as head 
        left join transnum as num on num.trno = head.trno
        where head.doc = ? and num.center = ? $filtersearch $orderby $limit";
        $data = $this->coreFunctions->opentable($qry, [$doc, $center]);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    } // end loaddoclisting

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
        $qry = "select head.trno, head.docno, head.doc, head.dateid, head.rem,
            head.client, head.clientname, head.address, head.tax, head.ref, head.kmno, head.recommend,
            head.cryear as byear, head.licenseno, head.make, head.modelname,
            head.crtype, head.submodel, head.carengine, head.transmission,
            head.mvno, head.mileage, head.manufacturer, head.chassisno
            from " . $this->head . " as head
            where head.trno = ?";
        $head = $this->coreFunctions->opentable($qry, [$trno]);

        if (!empty($head)) {
            $stock = $this->openstock($trno, $config);
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return [
                'head'       => $head,
                'griddata'   => ['inventory' => $stock],
                'islocked'   => false,
                'isposted'   => false,
                'isnew'      => false,
                'status'     => true,
                'msg'        => $msg,
                'clickobj'   => [],
                'hidetabbtn' => [],
                'hideobj'    => []
            ];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);
        $query = $sqlselect . " from ptjobs as pt
        left join jobthead as jt on jt.line = pt.jobid 
        where pt.trno = ?";
        $data = $this->coreFunctions->opentable($query, [$trno]);
        return $data;
    }

    public function getstockselect($config)
    {
        $query = "select pt.line,pt.jobid,pt.trno as trno,pt.rem,jt.docno as code,jt.jobtitle as description,'' as bgcolor ";
        return $query;
    }

    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['description'] = '';
        $data[0]['rem'] = '';
        return $data;
    }

    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $companyid = $config['params']['companyid'];
        $data = [];

        if ($isupdate) {
            unset($this->fields[array_search('docno', $this->fields)]);
            unset($head['docno']);
        }

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
                }
            }
        }

        // map byear to cryear
        if (isset($head['byear'])) {
            $data['cryear'] = $this->othersClass->sanitizekeyfield('cryear', $head['byear'], '', $companyid);
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby']   = $config['params']['user'];

        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
            $this->logger->sbcwritelog(
                $head['trno'],
                $config,
                'UPDATE',
                $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']
            );
        } else {
            $data['doc']        = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby']   = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog(
                $head['trno'],
                $config,
                'CREATE',
                $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']
            );
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
}
