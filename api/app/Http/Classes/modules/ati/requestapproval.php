<?php

namespace App\Http\Classes\modules\ati;

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

class requestapproval
{
    public $modulename = 'REQUEST APPROVAL';
    public $gridname = 'inventory';

    public $tablenum = 'transnum';
    public $head = 'vrhead';
    public $stock = 'vrstock';

    public $tablelogs = 'transnum_log';

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;

    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = false;

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
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 2911
        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = [];
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function loaddoclisting($config)
    {
        $center = $config['params']['center'];
        $userid = $config['params']['adminid'];

        if ($userid == 0) {
            return ['data' => [], 'status' => false, 'msg' => 'Please advice your administrator, only the approver can allow to access this module'];
        }

        $isapprover = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$userid]);
        if ($isapprover != "1") {
            return ['data' => [], 'status' => false, 'msg' => 'Please advice your administrator, current user is not approver'];
        }

        $deptid =   $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$userid]);
        if ($deptid == "") {
            $deptid = 0;
        }

        $data = $this->coreFunctions->opentable($this->selectqry($config, $deptid));
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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

    public function createdoclisting($config)
    {
        $action = 0;
        $docno = 1;
        $dateid = 2;
        $schedin = 3;
        $schedout = 4;
        $clientname = 5;
        $customer = 6;
        $passengername = 7;
        $itemdesc = 8;

        $cols = ['action', 'docno', 'dateid', 'schedin', 'schedout', 'clientname', 'customer', 'passengername', 'itemdesc'];
        $stockbuttons = ['approverequest', 'disapproverequest'];
        $cols = $this->tabClass->createdoclisting($cols, $stockbuttons);

        $cols[$clientname]['label'] = 'Requestor';

        $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$dateid]['style'] = 'width:130px;whiteSpace: normal;min-width:130px; max-width:130px;';
        $cols[$schedin]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$schedout]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$customer]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[$passengername]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[$itemdesc]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';

        $cols[$customer]['type'] = 'textarea';
        $cols[$customer]['readonly'] = true;

        $cols[$passengername]['type'] = 'textarea';
        $cols[$passengername]['readonly'] = true;

        $cols[$itemdesc]['type'] = 'textarea';
        $cols[$itemdesc]['readonly'] = true;

        $cols[$schedin]['label'] = 'Start Time';
        $cols[$schedout]['label'] = 'End Time';

        $cols[$action]['btns']['approverequest']['label'] = "Approve Request";
        $cols[$action]['btns']['approverequest']['confirm'] = true;
        $cols[$action]['btns']['approverequest']['confirmlabel'] = "Approve Request?";

        $cols[$action]['btns']['disapproverequest']['confirm'] = true;
        $cols[$action]['btns']['disapproverequest']['confirmlabel'] = "Disapprove Request?";
        return $cols;
    }

    private function selectqry($config, $deptid = '')
    {
        $center = $config['params']['center'];


        // " . $filtersearch . "
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'emp.clientname', 'dept.clientname', 'driver.clientname', 'vehicle.clientname'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
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
    (select group_concat(concat(client.clientname, ifnull(concat(' - Addr: ', addr.addr),'')) order by client.clientname separator '\r\r')  from vrstock as s left join client on client.clientid=s.clientid left join billingaddr as addr on addr.line=s.shipid where s.trno=head.trno) as customer,
    (select group_concat(concat(client.clientname,' - ',i.itemname,' - (',round(i.qty),i.uom,')') order by client.clientname SEPARATOR '\r') as itemdesc from vritems as i left join vrstock as s on s.trno=i.trno and s.line=i.line  left join client on client.clientid=s.clientid where i.trno=head.trno) as itemdesc,
    (select group_concat(concat(client.clientname,' - ',pass.clientname) order by client.clientname, pass.clientname SEPARATOR '\r') as passenger from vrpassenger as i left join vrstock as s on s.trno=i.trno and s.line=i.line left join client on client.clientid=s.clientid left join client as pass on pass.clientid=i.passengerid where i.trno=head.trno) as passengername,
    'VEHICLE SCHEDULE REQUEST' as itemname,
    'viewvrapproval' as lookupclass,
    'customform' as action,
    '' as brand
    ";
        $qry = $qry . " from " . $this->head . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno 
    left join client as emp on emp.clientid = head.clientid
    left join client as driver on driver.clientid = head.driverid
    left join client as vehicle on vehicle.clientid = head.vehicleid
    left join client as dept on dept.clientid = head.deptid 
    where num.doc='VR' and num.center = '" . $center . "' and num.statid=10 " . $filtersearch . "";

        if ($deptid != '') {
            $qry = $qry . " and emp.deptid=" . $deptid;
        }

        return $qry;
    }

    public function loadheaddata($config)
    {
        return  ['head' => [], 'isnew' => false, 'status' => true, 'msg' => '', 'islocked' => false, 'isposted' => false];
    }

    public function stockstatusposted($config)
    {
        $trno = 0;
        switch ($config['params']['action']) {
            case 'approverequest':
            case 'disapproverequest':
                $row = $config['params']['row'];
                $trno = $row['trno'];
                $docno = $row['docno'];

                $user = $config['params']['user'];
                $approveddate = $this->othersClass->getCurrentTimeStamp();

                $status = 'APPROVED';
                if ($config['params']['action'] == 'approverequest') {
                    $approvaldata = [
                        'approvedby' => $user,
                        'approveddate' => $approveddate
                    ];
                    $statid = 11;
                } else {
                    $approvaldata = [
                        'approvedby' => '',
                        'approveddate' => null
                    ];
                    $statid = 14;
                    $status = 'DISAPPROVED';
                }

                $this->coreFunctions->sbcupdate($this->head, $approvaldata, ['trno' => $trno]);
                $this->coreFunctions->sbcupdate("transnum", ['statid' => $statid], ['trno' => $trno]);
                $this->logger->sbcwritelog($trno, $config, $status, $docno); // status logs

                return ['status' => true, 'msg' => 'Successfully approved.', 'action' => 'reloadlisting'];
                break;
            default:
                return ['status' => false, 'msg' => 'Not setup in stockstatusposted ' . $config['params']['action']];
                break;
        }
    }
}
