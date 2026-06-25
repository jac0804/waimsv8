<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use App\Http\Classes\companysetup;
use Illuminate\Http\Request;
use App\Http\Requests;
use DateTime;

class autoservlookup
{
    private $othersClass;
    private $sqlquery;
    private $coreFunctions;
    private $companysetup;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->companysetup = new companysetup;
    }

    public function lookupcarmake($config)
    {
        $systemtype =  $this->companysetup->getsystemtype($config['params']);
        $lookupclass = $config['params']['lookupclass'];
        $addedparams = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : 0;

        switch ($lookupclass) {
            case 'lookupcarmodel':
                $plotting = array('modelid' => 'modelid', 'modelname' => 'modelname', 'year' => 'cryear', 'type' => 'crtype', 'submodel' => 'sub_model');
                break;
            default:
                $plotting = array('clientid' => 'clientid', 'client' => 'carname');
                if ($systemtype == 'AUTOSERV' && $config['params']['doc'] == 'AM') {
                    $plotting = array('carid' => 'clientid', 'vehicle' => 'carname');
                }
                break;
        }

        // $plotting = array('clientid' => 'clientid', 'client' => 'carname');

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List Of Car Make',
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => 'plothead',
            'action' => '',
            'plotting' => $plotting
        );

        // lookup columns
        switch ($lookupclass) {
            case 'lookupcarmodel':
                $cols = [
                    ['name' => 'carname', 'label' => 'Car Make', 'align' => 'left', 'field' => 'carname', 'sortable' => true, 'style' => 'font-size:16px;'],
                    ['name' => 'modelname', 'label' => 'Car Model', 'align' => 'left', 'field' => 'modelname', 'sortable' => true, 'style' => 'font-size:16px;'],
                    ['name' => 'year', 'label' => 'Year', 'align' => 'left', 'field' => 'cryear', 'sortable' => true, 'style' => 'font-size:16px;'],
                    ['name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'crtype', 'sortable' => true, 'style' => 'font-size:16px;'],
                    ['name' => 'sub_model', 'label' => 'Sub Model', 'align' => 'left', 'field' => 'sub_model', 'sortable' => true, 'style' => 'font-size:16px;'],
                ];
                break;
            default:
                $cols = [['name' => 'carname', 'label' => 'Car Make', 'align' => 'left', 'field' => 'carname', 'sortable' => true, 'style' => 'font-size:16px;']];
                break;
        }


        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch(['carname'], $search);
            }
        }


        $join = "";
        $addfields = "";
        $condition = " where 1=1";
        if ($lookupclass == 'lookupcarmodel') {
            $join = " left join cmodel as model on model.carid=cmake.id ";
            $addfields = ", model.model as modelname,model.cryear, model.crtype,model.sub_model,model.line as modelid";
            if ($addedparams != 0) {
                $condition = " where cmake.id = $addedparams";
            }
        }

        $qry = "select cmake.id as clientid, cmake.carname $addfields from cmake $join $condition " . $filtersearch . " order by carname";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupledgercarmake($config)
    {
        $lookupclass = $config['params']['lookupclass'];
        $addedparams = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : 0;

        $plotting = array('clientid' => 'clientid', 'client' => 'carname');

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List Of Car Make',
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'loadledgerdata',
            'plotting' => $plotting
        );

        $cols = [['name' => 'carname', 'label' => 'Car Make', 'align' => 'left', 'field' => 'carname', 'sortable' => true, 'style' => 'font-size:16px;']];

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch(['carname'], $search);
            }
        }

        $qry = "select cmake.id as clientid, cmake.carname from cmake where 1=1 " . $filtersearch . " order by carname";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function getautojob($config)
    {
        $title = 'List of Auto Job Setup';

        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'keyid',
            'title' => $title,
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'getautojob',
        );
        // lookup columns
        $cols = array(
            array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'description', 'label' => 'Job Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        $query = "select line as keyid,line,docno as code,jobtitle as description from jobthead";
        $data = $this->coreFunctions->opentable($query);


        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function getcvehicle($config)
    {
        $title = 'List of Customer Vehicle';
        $client = $config['params']['client'];

        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'keyid',
            'title' => $title,
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'getvehicle',
        );
        // lookup columns
        $cols = array(
            ['name' => 'cmake', 'label' => 'Car Make', 'align' => 'left', 'field' => 'cmake', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'model', 'label' => 'Car Model', 'align' => 'left', 'field' => 'model', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'cryear', 'label' => 'Year', 'align' => 'left', 'field' => 'cryear', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'crtype', 'label' => 'Type', 'align' => 'left', 'field' => 'crtype', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'sub_model', 'label' => 'Sub Model', 'align' => 'left', 'field' => 'sub_model', 'sortable' => true, 'style' => 'font-size:16px;'],
        );

        $filter = "";
        $filter = " and c.client = '" . $client ."'"; 

        $qry = "select cv.line as keyid, cv.carid, cv.cmodelline, cv.clientid, c.clientname, cv.cmake, cm.model, cm.cryear, cm. crtype, cm.sub_model, cv.licenseno, cv.carengine, cv.chassis,
                cv.transmission, cv.mileage, cv.motorno, cv.mvno, cv.insurance, cv.labor
                from
                cvehicle as cv
                left join cmodel as cm on cm.line = cv.cmodelline
                left join client as c on c.clientid = cv.clientid
                where 1= 1 $filter order by cmake";

        $data = $this->coreFunctions->opentable($qry);


        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookuppackage($config)
    {
        $title = 'List of Package Kits';

        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'keyid',
            'title' => $title,
            'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addpackage',
        );
        // lookup columns
        $cols = array(
            array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'description', 'label' => 'Package', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;')

        );
        $query = "select head.trno as keyid, head.docno as code, head.rem as description
        from pthead as head
        where head.doc = 'AK'

        union all

        select head.trno as keyid, head.docno as code, head.rem as description
        from hpthead as head
        where head.doc = 'AK'

        order by code";

        $data = $this->coreFunctions->opentable($query);


        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}
