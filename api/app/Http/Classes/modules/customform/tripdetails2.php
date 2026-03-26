<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class tripdetails2
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'ARRIVED';
    public $gridname = 'tableentry';
    private $fields = ['rem'];
    private $table = 'cntnum';
    private $tableinfo = 'cntnuminfo';
    private $htableinfo = 'hcntnuminfo';
    private $mtable = 'lahead';
    private $hmtable = 'glhead';
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_tablelog';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = false;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 12, 'save' => 15);
        return $attrib;
    }

    public function createHeadField($config)
    {
        if ($config['params']['doc'] == 'RR') {
            $this->modulename = 'ARRIVED';
        }
        $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
        $isposted = $this->othersClass->isposted2($config['params']['trno'], "cntnum");

        $fields = ['scheddate',  ['strdate1', 'strdate2']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'scheddate.readonly', false);
        data_set($col1, 'strdate1.label', 'ARRIVE');
        data_set($col1, 'strdate2.label', 'DEPART');
        if ($isposted) {
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        data_set($col1, 'strdate1.readonly', true);
                        data_set($col1, 'strdate2.readonly', true);
                    }
                    break;

                default:
                    data_set($col1, 'strdate1.readonly', true);
                    data_set($col1, 'strdate2.readonly', true);
                    break;
            }
        }
        $fields = ['reportedbyname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'reportedbyname.lookupclass', 'employeereportedby');
        if ($isposted) {
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        data_set($col2, 'reportedbyname.type', 'input');
                        data_set($col2, 'reportedbyname.readonly', true);
                    }
                    break;

                default:
                    data_set($col2, 'reportedbyname.type', 'input');
                    data_set($col2, 'reportedbyname.readonly', true);
                    break;
            }
        }

        $fields = ['rem2'];
        $col3 = $this->fieldClass->create($fields);
        if ($isposted) {
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        data_set($col3, 'rem2.readonly', true);
                    }
                    break;

                default:
                    data_set($col3, 'rem2.readonly', true);
                    break;
            }
        }
        $fields = [];
        switch ($config['params']['doc']) {
            case 'RR':
                if (!$isapproved) {
                    array_push($fields, 'refresh');
                }

                break;
            default:
                if (!$isposted) {
                    array_push($fields, 'refresh');
                }
                break;
        }
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'UPDATE');
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];


        $qry = "select h.trno, info.scheddate, info.strdate1, info.strdate2, info.rem3 as rem2, info.reportedby2 as reportedby, emp.clientname as reportedbyname,
                ifnull((select sum(rate) from tripdetail where tripdetail.trno=h.trno),0) as ext
                from lahead as h left join cntnuminfo as info on info.trno=h.trno left join client as wh on wh.client=h.wh left join client as emp on emp.clientid=info.reportedby2
                where h.trno=?
                union all 
                select h.trno, info.scheddate, info.strdate1, info.strdate2, info.rem3 as rem2, info.reportedby2 as reportedby, emp.clientname as reportedbyname,
                ifnull((select sum(rate) from htripdetail where htripdetail.trno=h.trno),0) as ext
                from glhead as h left join hcntnuminfo as info on info.trno=h.trno left join client as wh on wh.clientid=h.whid left join client as emp on emp.clientid=info.reportedby2
                where h.trno=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['dataparams']['trno'];
        $isapproved = $this->othersClass->isapproved($trno, "hcntnuminfo");
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        $infotab = $this->tableinfo;
        if ($isposted) {
            $infotab = $this->htableinfo;
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        return ['status' => false, 'msg' => 'Transaction has already been posted and approved.', 'data' => []];
                    }
                    break;
                default:
                    return ['status' => false, 'msg' => 'Transaction has already been posted.', 'data' => []];
                    break;
            }
        }
        $data = [];
        $datainfo = [];

        $rem = $config['params']['dataparams']['rem2'];

        $datainfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $datainfo['editby'] = $config['params']['user'];
        $datainfo['rem3'] = $this->othersClass->sanitizekeyfield("rem", $rem);
        $datainfo['reportedby2'] = $config['params']['dataparams']['reportedby'];
        $datainfo['strdate1'] = $config['params']['dataparams']['strdate1'];
        $datainfo['strdate2'] = $config['params']['dataparams']['strdate2'];
        $datainfo['scheddate'] = $config['params']['dataparams']['scheddate'];

        $this->coreFunctions->sbcupdate($infotab, $datainfo, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata];
    }
}
