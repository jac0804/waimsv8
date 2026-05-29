<?php

namespace App\Http\Classes\modules\masterfile;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\stockClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\clientClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\headClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\builder\lookupClass;
use App\Http\Classes\builder\txtfieldClass;

use App\Http\Classes\sbcscript\sbcscript;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class locationinventory
{
    private $othersClass;
    private $coreFunctions;
    private $headClass;
    private $logger;
    private $lookupClass;
    public $gridname = 'customformacctg';
    public $modulename = 'Location Inventory';
    private $companysetup;
    private $config = [];
    private $sqlquery;
    private $tabClass;
    private $fieldClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';

    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = false;
    public $showclosebtn = false;
    public $issearchshow = true;
    private $sbcscript;


    private $totalEmployees = 0;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->headClass = new headClass;
        $this->logger = new Logger;
        $this->lookupClass = new lookupClass;
        $this->companysetup = new companysetup;
        $this->sqlquery = new sqlquery;
        $this->tabClass = new tabClass;
        $this->fieldClass = new txtfieldClass;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5829
        );
        return $attrib;
    }
    public function createTab($config)
    {
        $columns = ['dtstatus', 'location', 'clientname', 'startdate', 'enddate'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$dtstatus]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dtstatus]['style'] =  'text-align:left; width: 70px;whiteSpace: normal;min-width:70px;max-width:70px;';
        $obj[0][$this->gridname]['columns'][$location]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$location]['style'] =  'text-align:left; width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] =  'text-align:left; width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';
        $obj[0][$this->gridname]['columns'][$startdate]['style'] =  'text-align:left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'End Date';
        $obj[0][$this->gridname]['columns'][$enddate]['style'] =  'text-align:left; width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['totalfield'] = '';
        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }
    public function createHeadbutton($config)
    {
        return [];
    }
    public function createHeadField($config)
    {
        $fields = [['db', 'cr']];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'db.label', 'Availble');
        data_set($col1, 'cr.label', 'Rented');

        $fields = ['refresh']; //'refresh'
        $col2 = $this->fieldClass->create($fields);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        $fields = [];
        $col4 = $this->fieldClass->create($fields);
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }
    public function paramsdata($config)
    {

        $query = "select count(case when ((tl.isinactive = 1 and loc.isserve = 1) or loc.isserve = 0) then 1 end) as `db`,count(case when (tl.isinactive = 0 and loc.isserve = 1) then 1 end) as `cr`
            from loc
			left join tenantloc as tl on tl.locid = loc.line
			left join client as cl on cl.clientid = tl.clientid";
        return $this->coreFunctions->opentable($query);
    }
    public function loaddata($config) // refresh btn
    {
        $query = "select case when ((tl.isinactive = 1 and loc.isserve = 1) or loc.isserve = 0) then 'AVAILABLE' else 'RENTED' end as `dtstatus`, loc.code as location,cl.clientname,
            date_format(cl.`start`,'%m/%d/%Y') as startdate,
            date_format(cl.enddate,'%m/%d/%Y') as enddate
			
            from loc
			left join tenantloc as tl on tl.locid = loc.line
			left join client as cl on cl.clientid = tl.clientid";

        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
    }

    public function data($config)
    {
        return [];
    }
    public function sbcscript($config)
    {
        return $this->sbcscript->skcustomform($config);
    }
}
