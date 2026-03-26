<?php

namespace App\Http\Classes\modules\customform;

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
use App\Http\Classes\sqlquery;

class viewvrapproval
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'VR APPROVAL FORM';
    public $gridname = 'editgrid';
    private $fields = ['itemdescription', 'accessories'];
    private $table = 'vrhead';
    private $tablenum = 'transnum';

    public $tablelogs = 'transnum_log';

    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = false;
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

        $fields = ['drivername', 'vehiclename', 'ddeptname', ['refresh', 'disapproved']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'ddeptname.type', 'input');
        data_set($col1, 'refresh.label', 'APPROVED');
        data_set($col1, 'refresh.action', 'viewvrapproval');
        data_set($col1, 'refresh.class', 'approved');
        data_set($col1, 'disapproved.action', 'viewvrapproval');
        data_set($col1, 'disapproved.class', 'disapproved');

        $fields = ['dateid', 'schedin', 'schedout'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['rem'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col4, 'remarks.type', 'ctextarea');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];

        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $docno = $config['params']['row']['docno'];
            $dateid = $config['params']['row']['dateid'];
            $schedin = $config['params']['row']['schedin'];
            $schedout = $config['params']['row']['schedout'];
            $drivername = $config['params']['row']['drivername'];
            $clientname = $config['params']['row']['clientname'];
            $vehiclename = $config['params']['row']['vehiclename'];
            $ddeptname = $config['params']['row']['ddeptname'];
            $rem = $config['params']['row']['rem'];

            $data = $this->coreFunctions->opentable("
                select " . $trno . " as trno, 
                '" . $docno . "' as docno, 
                '" . $dateid . "' as dateid, 
                '" . $schedin . "' as schedin, 
                '" . $schedout . "' as schedout, 
                '" . $drivername . "' as drivername, 
                '" . $clientname . "' as clientname, 
                '" . $vehiclename . "' as vehiclename, 
                '" . $ddeptname . "' as ddeptname, 
                '" . $rem . "' as rem
                ");

            $this->modulename = 'VEHICLE SCHEDULE REQUEST DETAILS - ' . $docno . ' - ' . $clientname;
        } else {
            return [];
        }
        return $data;
    }

    public function getheaddata($config, $doc)
    {
        return [];
    }

    public function data($config)
    {
        $center = $config['params']['center'];
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
        }

        $qry = "select 
        head.trno,
        head.docno, 
        left(head.dateid,10) as dateid, 
        head.schedin,
        head.schedout, 
        head.driverid, 
        head.clientid, 
        head.vehicleid, 
        head.deptid, 
        driver.clientname as drivername, 
        driver.client as driver, 
        emp.clientname, 
        emp.client, 
        vehicle.clientname as vehiclename, 
        vehicle.client as vehicle, 
        dept.clientname as ddeptname, 
        dept.client as dept, 
        head.rem,
        head.approvedby,
        'VEHICLE SCHEDULE REQUEST' as itemname,
        'viewvrapproval' as lookupclass,
        'customform' as action
        ";
        $qry = $qry . " from " . $this->table . " as head
        left join " . $this->tablenum . " as num on num.trno = head.trno 
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as dept on dept.clientid = head.deptid 
        where num.doc='VR' and num.center = '" . $center . "' and head.lockdate is not null and head.status = ''
        and head.trno = ?";

        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
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

        $data = $config['params']['dataparams'];

        $status = strtoupper($config['params']['classtype']);
        $trno = $data['trno'];
        $docno = $data['docno'];
        $user = $config['params']['user'];
        $approveddate = $this->othersClass->getCurrentTimeStamp();

        $statid = 0;
        $approvaldata = [];
        if ($status == 'APPROVED') {
            $approvaldata = [
                'approvedby' => $user,
                'approveddate' => $approveddate,
                'status' => $status
            ];
            $statid = 11;
        } else {
            $approvaldata = [
                'status' => '',
                'lockuser' => '',
                'lockdate' => null
            ];
            $statid = 14;
        }

        $this->coreFunctions->sbcupdate($this->table, $approvaldata, ['trno' => $trno]);
        $this->coreFunctions->sbcupdate("transnum", ['statid' => $statid], ['trno' => $trno]);
        $this->logger->sbcwritelog($trno, $config, $status, $docno); // status logs

        $config['params']['row']['trno'] =  $trno;
        $data = $this->data($config);
        return ['status' => true, 'msg' => ucfirst($status) . ' Success', 'data' => $data];
    }
}
