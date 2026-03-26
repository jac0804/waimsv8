<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class tripdetails
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'TRIP';
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

        $isposted = $this->othersClass->isposted2($config['params']['trno'], "cntnum");
        $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");

        $fields = ['tripdate',  ['strdate1', 'strdate2']];
        $col1 = $this->fieldClass->create($fields);
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
                    data_set($col1, 'strdate1.readonly', false);
                    data_set($col1, 'strdate2.readonly', false);
                    break;
            }
        }

        $fields = ['reportedbyname', 'whname', 'clientname'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'whname.label', 'FROM');
        data_set($col2, 'clientname.label', 'TO');
        data_set($col2, 'reportedbyname.lookupclass', 'employeereportedby');
        if ($isposted) {
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        data_set($col2, 'reportedbyname.readonly', true);
                    }
                    break;

                default:
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
                    data_set($col3, 'rem2.readonly', false);
                    break;
            }
        }

        $fields = [['lblext', 'ext']];
        switch ($config['params']['doc']) {
            case 'RR':
                if (!$isapproved) {
                    array_push($fields, 'refresh');
                }

                break;
            default:
                array_push($fields, 'refresh');
                break;
        }

        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'UPDATE');
        data_set($col4, 'lblext.label', 'TOTAL RATES:');
        data_set($col4, 'ext.label', '');
        data_set($col4, 'ext.align', 'right');
        data_set($col4, 'ext.style', 'font-weight:bold;font-size:20px');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [
            'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytripdetails', 'label' => 'LIST']
        ];
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

        switch ($doc) {
            case 'RR':
                $whname = 'h.clientname';
                $clientname = 'wh.clientname';
                break;
            default:
                $clientname = 'h.clientname';
                $whname = 'wh.clientname';
                break;
        }
        $qry = "select h.trno, info.tripdate, h.strdate1, h.strdate2, info.rem2, " . $clientname . ", " . $whname . " as whname, info.reportedby, emp.clientname as reportedbyname,
                ifnull((select sum(rate) from tripdetail where tripdetail.trno=h.trno),0) as ext
                from lahead as h left join cntnuminfo as info on info.trno=h.trno left join client as wh on wh.client=h.wh left join client as emp on emp.clientid=info.reportedby
                where h.trno=?
                union all 
                select h.trno, info.tripdate, h.strdate1, h.strdate2, info.rem2, " . $clientname . ", " . $whname . " as whname, info.reportedby, emp.clientname as reportedbyname,
                ifnull((select sum(rate) from htripdetail where htripdetail.trno=h.trno),0) as ext
                from glhead as h left join hcntnuminfo as info on info.trno=h.trno left join client as wh on wh.clientid=h.whid left join client as emp on emp.clientid=info.reportedby
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
        $maintab = $this->mtable;
        if ($isposted) {
            $infotab = $this->htableinfo;
            $maintab = $this->hmtable;
            switch ($config['params']['doc']) {
                case 'RR':
                    if ($isapproved) {
                        return ['status' => false, 'msg' => 'Transaction has already been posted and approved.', 'data' => []];
                    }
                    break;
            }
        }
        $data = [];
        $datainfo = [];

        $rem = $config['params']['dataparams']['rem2'];
        $tripdate = $config['params']['dataparams']['tripdate'];
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['strdate1'] = $config['params']['dataparams']['strdate1'];
        $data['strdate2'] = $config['params']['dataparams']['strdate2'];

        $this->coreFunctions->sbcupdate($maintab, $data, ['trno' => $trno]);

        $datainfo['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $datainfo['editby'] = $config['params']['user'];
        $datainfo['rem2'] = $this->othersClass->sanitizekeyfield("rem", $rem);
        $datainfo['tripdate'] = $this->othersClass->sanitizekeyfield("dateid", $tripdate);
        $datainfo['reportedby'] = $config['params']['dataparams']['reportedby'];
        $this->coreFunctions->sbcupdate($infotab, $datainfo, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata];
    }
}
