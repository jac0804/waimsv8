<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\builder\lookupclass;

class entryapproverlist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'APPROVER LIST';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = [];
    public $showclosebtn = false;
    private $lookupclass;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        // $this->lookupclass = new lookupclass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }


    public function createTab($config)
    {
        $doc = $config['params']['doc'];
        $columns = ['clientname', 'approver'];
        if ($doc == 'LEAVEAPPLICATIONPORTAL') {
            $columns = ['effdate', 'clientname', 'approver'];
        }

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $columns]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:40%;whiteSpace: normal;min-width:40%;";

        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Name";
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;

        $obj[0][$this->gridname]['columns'][$approver]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$approver]['label'] = "Approver";
        if ($doc == 'LEAVEAPPLICATIONPORTAL') {
            $obj[0][$this->gridname]['columns'][$effdate]['type'] = "label";
        }
        // $obj[0][$this->gridname]['columns'][$moduletype]['action'] = "lookupsetup";
        // $obj[0][$this->gridname]['columns'][$moduletype]['lookupclass'] = "lookupapproverdoc";


        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function add($config)
    {
        $id = $config['params']['sourcerow']['line'];
        $data = [];
        $data['approver'] = '';
        $data['clientname'] = '';
        return $data;
    }

    public function save($config)
    {
        $data = [];
        return ['status' => true, 'msg' => 'Successfully saved.'];
    } //end function

    public function delete($config)
    {
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }

    private function loaddataperrecord($config, $line)
    {
        return $this->loaddata($config);
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['adminid'];
        $tableid = isset($config['params']['tableid']) ? $config['params']['tableid'] : $config['params']['clientid'];
        $companyid = $config['params']['companyid'];
        $doc = $config['params']['doc'];

        $labelapprover = "if(app.approver = 'isapprover','FOR APPROVER','FOR SUPERVISOR')";
        switch ($doc) {
            case 'OBAPPLICATION':
                $qry = "select '' as effdate,approver.clientname,$labelapprover as approver from obapplication as ob 
             left join pendingapp as app on app.line = ob.line
             left join client approver on approver.clientid = app.clientid
             where ob.line = " . $tableid . " and ob.empid = " . $clientid . " and app.doc = 'OB'";
                break;

            case 'OTAPPLICATIONADV':
                $qry = "select '' as effdate,approver.clientname,$labelapprover as approver from otapplication as ot 
             left join pendingapp as app on app.line = ot.line
             left join client approver on approver.clientid = app.clientid
             where ot.line = " . $tableid . " and ot.empid = " . $clientid . " and app.doc = 'OT'";
                break;
            case 'ITINERARY':
                $qry = "
             select '' as effdate,approver.clientname,$labelapprover as approver from itinerary as it 
             left join pendingapp as app on app.trno = it.trno 
             left join client approver on approver.clientid = app.clientid
             where it.trno = " . $tableid . " and it.empid = " . $clientid . " and app.doc = 'TRAVEL'";
                break;

            case 'UNDERTIME':
                $qry = "select '' as effdate,approver.clientname,$labelapprover as approver from undertime as under
			left join pendingapp as app on app.line = under.line
			left join client approver on approver.clientid = app.clientid
			WHERE under.line =  " . $tableid . "  and under.empid =  " . $clientid . " and app.doc = 'UNDERTIME'";
                break;
            case 'RESTDAY':
                $qry = "select '' as effdate,approver.clientname,$labelapprover as approver from changeshiftapp cs
			left join pendingapp as app on app.line = cs.line 
			left join client approver on approver.clientid = app.clientid
			where cs.line = " . $tableid . "  and cs.empid = " . $clientid . " and app.doc = 'RESTDAY'";
                break;
            case 'WORD':
                $qry = "select '' as effdate,approver.clientname,$labelapprover as approver from changeshiftapp cs
			left join pendingapp as app on app.line = cs.line 
			left join client approver on approver.clientid = app.clientid
			where cs.line = " . $tableid . "  and cs.empid = " . $clientid . " and app.doc = 'WORKONRESTDAY'";
                break;
            case 'LEAVEAPPLICATIONPORTAL':
                $qry = "select date(effectivity) as effdate,approver.clientname,$labelapprover as approver from leavetrans as lt
			  left join pendingapp as app on app.trno = lt.trno 
			  left join client approver on approver.clientid = app.clientid
			  where lt.trno = " . $tableid . " and lt.empid = " . $clientid . " and app.doc = 'LEAVE'";
                break;
        }
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function saveallentry($config)
    {
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => []];
    } // end function

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
    }
} //end class
