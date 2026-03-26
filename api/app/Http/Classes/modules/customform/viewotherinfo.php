<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewotherinfo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Quotation Validity';
    public $gridname = 'tableentry';
    private $fields = ['period', 'isvalid', 'ovaliddate'];
    private $table = 'headinfotrans';

    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

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
        $attrib = array('load' => 22, 'edit' => 23);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $doc = $config['params']['doc'];
        switch ($config['params']['companyid']) {
            case 19: //housegem
                $trno = $config['params']['trno'];
                $this->modulename = '';
                $isposted = $this->othersClass->isposted2($trno, 'cntnum');
                if ($doc == 'MI') {
                    $fields = ['driver', 'rem2', ['refresh']];
                    $col1 = $this->fieldClass->create($fields);
                    data_set($col1, 'driver.label', 'Driver');
                    data_set($col1, 'driver.type', 'lookup');
                    data_set($col1, 'driver.action', 'lookupdriver');
                    data_set($col1, 'driver.lookupclass', 'driver');
                    data_set($col1, 'refresh.label', 'SAVE');
                    if ($isposted) {
                        data_set($col1, 'refresh.type', 'hidden');
                    }
                    $fields = [];
                    $col2 = $this->fieldClass->create($fields);
                } else {
                    $fields = ['driver', 'helpername', 'truck', 'plateno', 'checker'];

                    $isposted = $this->othersClass->isposted2($trno, "transnum");

                    $isro = $this->coreFunctions->datareader('select isro as value from headinfotrans where trno=?', [$trno]);
                    if ($isro == '') $isro = 0;
                    if ($doc == 'SO') {
                        if (!$isposted) {
                            array_push($fields, 'refresh');
                        }
                    }
                    $col1 = $this->fieldClass->create($fields);
                    data_set($col1, 'driver.label', 'Driver');
                    data_set($col1, 'driver.type', 'lookup');
                    data_set($col1, 'driver.action', 'lookupclient');
                    data_set($col1, 'driver.lookupclass', 'employeedriver');
                    data_set($col1, 'plateno.type', 'input');
                    data_set($col1, 'plateno.readonly', false);

                    data_set($col1, 'refresh.label', 'Save Changes');
                    if ($isposted || $doc == 'SJ') {
                        data_set($col1, 'checker.type', 'input');
                        data_set($col1, 'truck.type', 'input');
                        data_set($col1, 'helpername.type', 'input');
                        data_set($col1, 'driver.type', 'input');
                    }
                    $fields = [];
                    $col2 = $this->fieldClass->create($fields);
                }
                return array('col1' => $col1, 'col2' => $col2);
                break;
            default:
                $trno = $config['params']['clientid'];
                $fields = [['period', 'isvalid', 'ovaliddate']];
                $isposted = $this->othersClass->isposted2($trno, "transnum");
                if (!$isposted) {
                    array_push($fields, 'refresh');
                }
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'period.type', 'input');
                data_set($col1, 'period.label', 'Validity Period');
                data_set($col1, 'period.readonly', false);
                data_set($col1, 'refresh.label', 'save');

                if ($isposted) {
                    data_set($col1, 'period.readonly', true);
                    data_set($col1, 'isvalid.readonly', true);
                    data_set($col1, 'ovaliddate.readonly', true);
                }

                $fields = [];
                $col2 = $this->fieldClass->create($fields);

                return array('col1' => $col1, 'col2' => $col2);
                break;
        }
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];

        switch ($config['params']['companyid']) {
            case 19: //housegem
                switch ($doc) {
                    case 'SO':
                        $head = 'headinfotrans';
                        $hhead = 'hheadinfotrans';
                        break;
                    case 'SJ':
                    case 'MI':
                        $head = 'cntnuminfo';
                        $hhead = 'hcntnuminfo';
                        break;
                }

                if (isset($config['params']['dataparams']['trno'])) {
                    $trno = $config['params']['dataparams']['trno'];
                } else {
                    $trno = $config['params']['trno'];
                }
                $qry = "select info.trno, info.plateno, d.clientname as driver, h.clientname as helpername, 
                               info.checkerid, c.clientname as checker, t.clientname as truck, info.driverid, 
                               info.helperid, info.truckid,info.rem2
                        from " . $head . " as info 
                        left join client as d on d.clientid=info.driverid 
                        left join client as h on h.clientid=info.helperid
                        left join client as c on c.clientid=info.checkerid 
                        left join client as t on t.clientid=info.truckid
                        where info.trno=?
                        union all
                        select info.trno, info.plateno, d.clientname as driver, h.clientname as helpername, 
                                info.checkerid, c.clientname as checker, t.clientname as truck, info.driverid, 
                                info.helperid, info.truckid,info.rem2
                        from " . $hhead . " as info 
                        left join client as d on d.clientid=info.driverid 
                        left join client as h on h.clientid=info.helperid
                        left join client as c on c.clientid=info.checkerid 
                        left join client as t on t.clientid=info.truckid
                        where info.trno=?";
                $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
                break;
            default:
                if (!isset($config['params']['dataparams']['trno'])) {
                    $trno = $config['params']['clientid'];
                } else {
                    $trno = $config['params']['dataparams']['trno'];
                }
                $data =  $this->coreFunctions->opentable("select $trno as trno, '' as period,'0' as isvalid, left(now(),10) as ovaliddate");
                $isposted = $this->othersClass->isposted2($trno, "transnum");
                if ($isposted) {
                    $tablename = 'hheadinfotrans';
                } else {
                    $tablename = 'headinfotrans';
                }

                $qry = "select trno, ifnull(period,'') as period, ifnull(isvalid,'0') as isvalid,ifnull(ovaliddate,left(now(),10)) as ovaliddate from " . $tablename . " where trno=? ";
                $res = $this->coreFunctions->opentable($qry, [$trno]);
                if (!empty($res)) {
                    $data[0]->trno = $res[0]->trno;
                    $data[0]->period = $res[0]->period;
                    $data[0]->isvalid = $res[0]->isvalid;
                    $data[0]->ovaliddate = $res[0]->ovaliddate;
                }
                break;
        }


        return $data;
    }

    public function getheaddata($config, $doc)
    {
        return [];
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

    public function loaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['dataparams']['trno'];

        if ($this->othersClass->isposted2($trno, 'transnum')) {
            return ['status' => false, 'msg' => 'Failed to save; already posted.'];
        }
        switch ($config['params']['companyid']) {
            case 19: //housegem
                $trno = $config['params']['dataparams']['trno'];
                if ($doc == 'MI') {
                    $data = [];
                    $trno = $config['params']['dataparams']['trno'];
                    $driverid = $config['params']['dataparams']['driverid'];
                    $rem2 = $config['params']['dataparams']['rem2'];

                    $isposted = $this->othersClass->isposted2($trno, $this->table);
                    $tablenum = "cntnuminfo";
                    if ($isposted) {
                        $tablenum = "hcntnuminfo";
                    }

                    $data['rem2'] = $rem2;
                    $data['driverid'] = $this->othersClass->sanitizekeyfield("driverid", $driverid);

                    $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);
                } else {
                    $data = [
                        'editby' => $config['params']['user'],
                        'editdate' => $this->othersClass->getCurrentTimeStamp(),
                        'checkerid' => $config['params']['dataparams']['checkerid'],
                        'driverid' => $config['params']['dataparams']['driverid'],
                        'helperid' => $config['params']['dataparams']['helperid'],
                        'truckid' => $config['params']['dataparams']['truckid'],
                        'plateno' => $config['params']['dataparams']['plateno']
                    ];
                    $this->coreFunctions->sbcupdate('headinfotrans', $data, ['trno' => $trno]);
                }
                break;
            default:
                $trno = $config['params']['dataparams']['trno'];
                $period = $config['params']['dataparams']['period'];
                $isvalid = $config['params']['dataparams']['isvalid'];
                $ovaliddate = $config['params']['dataparams']['ovaliddate'];
                $editby = $config['params']['user'];
                $editdate = $this->othersClass->getCurrentTimeStamp();

                $data = [
                    'trno' => $trno,
                    'period' => $period,
                    'isvalid' => $isvalid,
                    'ovaliddate' => $ovaliddate,
                    'editby' => $editby,
                    'editdate' => $editdate
                ];

                $tablename = 'headinfotrans';

                if (!$this->checkdata($trno, $tablename)) {
                    $this->coreFunctions->sbcinsert($tablename, $data);
                    $this->logger->sbcwritelog(
                        $trno,
                        $config,
                        'CREATE QUOTATION VALIDITY',
                        ' VALIDITY PERIOD: ' . $data['period']
                            . ', OTHER VALIDITY DATE: ' . $data['ovaliddate']
                            . ', VALID: ' . $data['isvalid']
                    );
                } else {
                    $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno]);
                }
                break;
        }

        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata];
    }

    public function checkdata($trno, $tablename)
    {
        $data =  $this->coreFunctions->opentable("select trno from " . $tablename . " where trno = ? ", [$trno]);
        if ($data) {
            return true;
        } else {
            return false;
        }
    }
}
