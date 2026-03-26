<?php

namespace App\Http\Classes\modules\payrollcustomform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\common\payrollcommon;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

use Carbon\Carbon;

class timeadj
{
    private $fieldClass;
    private $tabClass;
    public $modulename = "TIME ADJUSTMENT";
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $payrollcommon;
    private $btnClass;
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;
    public $table = 'timeadj';
    public $fields = ['acnoid', 'rem', 'qty', 'batchid'];
    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->payrollcommon = new payrollcommon;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5218,
            'save' => 5219,
            'saveallentry' => 5219,
            'deleteitem' => 5219,
            'edititem' => 5220,
            'edit' => 5220

        );
        return $attrib;
    }

    public function createHeadbutton($config)
    {
        $btns = [];
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createTab($config)
    {

        $column = [
            'action',
            'client',
            'clientname',
            'acnoname',
            'qty',
            'batch',
            'rem'

        ];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => [
            'gridcolumns' => $column
        ]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['descriptionrow'] = [];

        $obj[0][$this->gridname]['columns'][$client]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$client]['label'] = "Employee Code";
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Employee Name";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:150px;whiteSpace:normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:80px;whiteSpace:normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$qty]['label'] = "Minutes";
        $obj[0][$this->gridname]['columns'][$rem]['label'] = "Remarks";


        $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "input";
        $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Account";
        $obj[0][$this->gridname]['columns'][$acnoname]['readonly'] = true;
        $obj[0][$this->gridname]['label'] = "Employee List";

        $obj[0][$this->gridname]['columns'][$batch]['readonly'] = true;
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {

        $fields = ['empcode', 'empname', ['acno', 'qty'], 'acnoname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'client.label', 'Employee Code');
        data_set($col1, 'client.style', 'padding:0px;');
        data_set($col1, 'clientname.style', 'padding:0px;');

        data_set($col1, 'empcode.style', 'padding:0px;');
        data_set($col1, 'empname.style', 'padding:0px;');


        data_set($col1, 'empname.action', 'lookupclient');
        data_set($col1, 'empname.lookupclass', 'employee');


        data_set($col1, 'qty.type', 'input');
        data_set($col1, 'qty.label', 'Minutes');
        data_set($col1, 'qty.readonly', false);
        data_set($col1, 'qty.style', 'padding:0px;');

        data_set($col1, 'acnoname.readonly', true);
        data_set($col1, 'acnoname.class', 'csacnoname sbccsreadonly');
        data_set($col1, 'acnoname.style', 'padding:0px;');
        data_set($col1, 'acno.style', 'padding:0px;');

        $fields = ['rem', 'create'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'rem.label', 'Remarks');
        data_set($col2, 'rem.readonly', false);
        data_set($col2, 'create.style', 'width:50%');
        data_set($col2, 'create.label', 'Create');

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['lblrem', ['start', 'end'], 'refresh'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.style', 'width:50%');
        data_set($col4, 'start.style', 'padding:0px;');
        data_set($col4, 'end.style', 'padding:0px;');
        data_set($col4, 'refresh.action', 'load');
        data_set($col4, 'lblrem.label', 'View: ');
        data_set($col4, 'lblrem.style', 'font-size:12px;font-weight:bold;');
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("
      select       date_add(date_add(last_day(curdate()), interval 1 DAY), interval -1 month) as start,
      adddate(date_add(date_add(last_day(curdate()), interval 1 DAY), interval -1 month), 14) as end
      ,0 as qty,0 as empid,0 as acnoid,0 as batchid,'' as rem");

        if (!empty($data)) {
            return $data[0];
        } else {
            return [];
        }
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

    public function headtablestatus($config)
    {
        // should return action
        $action = $config['params']["action2"];

        switch ($action) {
            case 'create':
                $this->create($config);
                return  $this->loaddetails($config);
                break;
            case 'load':
                return $this->load($config);
                break;
            default:
                return ['status' => false, 'msg' => $action . ' not yet setup in the headtablestatus.'];
                break;
        }
    }

    public function selectqry()
    {
        return "select adj.line as trno , cl.client , cl.clientname,pa.codename,adj.qty,adj.amt,adj.rem,adj.acnoid,adj.empid,adj.batchid,pa.codename as acnoname";
    }

    public function loaddetails($config)
    {
        $current = date('Y-m-d');
        $select = $this->selectqry();
        $qry =  $select . ",'' as bgcolor from timeadj as adj
        left join client as cl on cl.clientid = adj.empid  
        left join paccount as pa on pa.line = adj.acnoid
        where date(dateid) = '$current' ";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
    public function loadperdetails($config, $line)
    {
        $select = $this->selectqry();
        $qry = $select . ",'' as bgcolor from timeadj as adj
        left join client as cl on cl.clientid = adj.empid  
        left join paccount as pa on pa.line = adj.acnoid
        where adj.line = $line ";
        return $this->coreFunctions->opentable($qry);
    }
    public function load($config)
    {
        $filter = "";
        if (isset($config['params']['dataparams'])) {
            $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
            $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
            $filter = " where date(dateid) between '$start' and '$end' ";
        }
        $select = $this->selectqry();
        $qry = $select . ",'' as bgcolor from timeadj as adj
        left join client as cl on cl.clientid = adj.empid  
        left join paccount as pa on pa.line = adj.acnoid
        $filter";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
    public function create($config)
    {
        $user  = $config['params']['user'];
        $data = $config['params']['dataparams'];
        $acnoid = $config['params']['dataparams']['acnoid'];
        $empid = $config['params']['dataparams']['empid'];

        $tempdata = [];
        if ($empid != 0 && $acnoid != 0) {

            foreach ($data as $key => $val) {
                foreach ($this->fields as $key => $value) {
                    foreach ($this->fields as $key2 => $value) {
                        $tempdata[$value] = $this->othersClass->sanitizekeyfield($value, $data[$value]);
                    }
                    if ($key == 'qty') {
                        $tempdata['amt'] = $data['qty'] / 60;
                        $tempdata['empid'] = $empid;
                        $tempdata['acnoid'] = $acnoid;
                    }
                }
            }
            $tempdata['dateid'] = $this->othersClass->getCurrentTimeStamp();;
            $tempdata['createtime'] = $this->othersClass->getCurrentTimeStamp();;
            $tempdata['createdby'] = $user;
            $this->coreFunctions->sbcinsert("timeadj", $tempdata);
        }
    }
    public function saveperitem($config)
    {
        $trno = $config['params']['row']['trno'];
        $row = $config['params']['row'];
        $config['params']['data'] = $config['params']['row'];

        $data = [];
        foreach ($row as $key => $val) { //first array
            foreach ($this->fields as $key2 => $value) {
                $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
            }
        }

        $this->coreFunctions->sbcupdate($this->table, $data, ['line' => $trno]);
        $data = $this->loadperdetails($config, $trno);
        return ['status' => true, 'msg' => 'Successfully saved.', 'action' => 'load', 'row' => $data];
    }
    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where trno=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno']]);
        $this->logger->sbcdelmaster_log($row['trno'], $config, 'DELETE - ' . $row['acnoname'] . ' Minutes: ' . $row['minute']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
    public function stockstatus($config)
    {

        // should return action
        $action = $config['params']["action"];

        switch ($action) {
            case "deleteitem":
                return $this->delete($config);
                break;
            case 'saveperitem':
                return $this->saveperitem($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the stockstatus.'];
                break;
        }
    }
} //end class
