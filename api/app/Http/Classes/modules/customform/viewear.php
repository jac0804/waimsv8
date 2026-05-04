<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewear
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'TASK INFO';
    public $gridname = 'inventory';
    private $fields = ['startdate', 'enddate', 'dateid', 'dateid2'];
    private $table = '';

    public $tablelogs = 'payroll_log';
    public $tablelogs_del = '';

    public $style = 'width:100%;max-width:50%;';
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

    public function createHeadField($config)
    {
        $row = $config['params']['row'];
        $adminid = $config['params']['adminid'];
        $this->modulename = strtoupper($config['params']['row']['title']);
        $label = "Date";
        $edit = 0;
        $doc = '';

        switch ($config['params']['row']['title']) {
            case 'Travel Application':
                $fields = ['startdate', 'enddate'];
                $edit = $this->othersClass->checkAccess($config['params']['user'], 5357); //edittravel
                $data = $this->coreFunctions->opentable("select status from itinerary where trno=? and status2 <> 'D'", [$row['line']]);
                $doc = 'TRAVEL';
                break;
            case 'Tracking Application':
                $fields = [['dateid', 'itime'], ['dateid2', 'itime1']];
                $edit = $this->othersClass->checkAccess($config['params']['user'], 2541); //edittracking
                $data = $this->coreFunctions->opentable("select trackingtype as value,status from obapplication where line=? and status2 <> 'D' ", [$row['line']]);
                $doc = 'OB';
                break;
            case 'Restday Application':
                $fields = ['dateid'];
                $label = 'Date';
                $edit = $this->othersClass->checkAccess($config['params']['user'], 5137); // editrestday
                $data = $this->coreFunctions->opentable("
                select
                (case when status = 0 then 'E' when status = 1 then 'A' else 'D' end) as status
                from changeshiftapp where line=? and isrestday = 1 and status2 <> 2 ", [$row['line']]);
                $doc = 'RESTDAY';
                break;
            case 'Working On Rest Day Application':
                $fields = ['dateid'];
                $label = 'Date';
                $edit = $this->othersClass->checkAccess($config['params']['user'], 5149); // editword
                $data = $this->coreFunctions->opentable("
                select
                (case when status = 0 then 'E' when status = 1 then 'A' else 'D' end) as status
                from changeshiftapp where line=? and isword = 1 and status2 <> 2 ", [$row['line']]);
                $doc = 'WORKONRESTDAY';
                break;
            case 'Undertime Application':
                $fields = [['dateid', 'itime'], ['dateid2', 'itime1']];
                $edit = $this->othersClass->checkAccess($config['params']['user'], 4777); //editundertime
                $data = $this->coreFunctions->opentable("select status from undertime where line=? and status2 <> 'D' ", [$row['line']]);
                $doc = 'UNDERTIME';
                break;
            case 'OT Application':
                $fields = [['othrs', 'apothrs'], ['othrsextra', 'apothrsextra'], ['ndiffothrs', 'apndiffothrs']];
                $edit = $this->othersClass->checkAccess($config['params']['user'], 4839); //editot
                $data = $this->coreFunctions->opentable("select case when otstatus = 1 then 'E' when otstatus = 2 then 'A' else 'D' end as status from otapplication where line=? and otstatus2 <> 3 ", [$row['line']]);
                $doc = 'OT';
                break;
        }

        $query = "select app.clientid as value from moduleapproval as ma 
                  left join approvers as app on app.trno = ma.line and ma.modulename = '$doc'
                  where app.isapprover =1 and app.clientid in ($adminid)";
        $approverid =  $this->coreFunctions->datareader($query, [], '', true);

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'dateid.label',  $label);
        data_set($col1, 'dateid2.label', 'Date');
        $fields = [];

        if (!empty($data)) {
            if ($edit) {
                if ($data[0]->status == 'E') {
                    if ($approverid != 0) {
                        array_push($fields, 'refresh');
                        $this->coreFunctions->LogConsole(" approverid: " . $approverid);
                    } else {
                        goto notallow;
                    }
                    data_set($col1, 'itime.label', 'Time In');
                    data_set($col1, 'itime1.label', 'Time Out');
                    switch ($config['params']['row']['title']) {
                        case 'Travel Application':
                            data_set($col1, 'startdate.readonly', false);
                            data_set($col1, 'enddate.readonly', false);
                            break;
                        case 'Restday Application':
                        case 'Working On Rest Day Application':
                            data_set($col1, 'dateid.readonly', false);
                            break;
                        case 'Undertime Application':
                            data_set($col1, 'dateid.readonly', false);
                            data_set($col1, 'itime.readonly', false);
                            data_set($col1, 'dateid2.readonly', false);
                            data_set($col1, 'itime1.readonly', false);
                            break;
                        case 'Tracking Application':
                            switch ($data[0]->value) {
                                case "DIRECT FIELD IN ONLY":
                                case "KEY CUSTODIANS LATE":
                                case "LATE TIME IN":
                                    data_set($col1, 'dateid.readonly', false);
                                    data_set($col1, 'itime.readonly', false);
                                    data_set($col1, 'dateid2.readonly', true);
                                    data_set($col1, 'itime1.readonly', true);


                                    break;
                                case "DIRECT FIELD OUT ONLY":
                                case "EARLY TIME OUT":
                                    data_set($col1, 'dateid.readonly', true);
                                    data_set($col1, 'itime.readonly', true);
                                    data_set($col1, 'dateid2.readonly', false);
                                    data_set($col1, 'itime1.readonly', false);
                                    break;
                                default:
                                    data_set($col1, 'dateid.readonly', false);
                                    data_set($col1, 'itime.readonly', false);
                                    data_set($col1, 'dateid2.readonly', false);
                                    data_set($col1, 'itime1.readonly', false);
                                    if (empty($data[0]->value)) {
                                        data_set($col1, 'dateid.readonly', true);
                                        data_set($col1, 'itime.readonly', true);
                                        data_set($col1, 'dateid2.readonly', true);
                                        data_set($col1, 'itime1.readonly', true);
                                    }
                                    break;
                            }
                            break;
                        case 'OT Application':
                            data_set($col1, 'apothrs.readonly', false);
                            data_set($col1, 'apndiffothrs.readonly', false);
                            data_set($col1, 'apothrsextra.readonly', false);
                            break;
                    }
                } else {
                    goto notallow;
                }
            } else {
                notallow:
                data_set($col1, 'startdate.readonly', true);
                data_set($col1, 'enddate.readonly', true);
                data_set($col1, 'dateid.readonly', true);
                data_set($col1, 'dateid2.readonly', true);
                data_set($col1, 'itime.readonly', true);
                data_set($col1, 'itime1.readonly', true);
            }
        }

        $this->coreFunctions->LogConsole(" edit: " . $edit);

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.label', 'UPDATE');
        return array('col1' => $col1, 'col2' => $col2);
    }
    public function paramsdata($config)
    {
        $line = $config['params']['row']['line'];
        $tablename = '';
        $filter = 'line';

        $title = $config['params']['row']['title'];
        switch ($title) {
            case 'Travel Application':
                $tablename = 'itinerary';
                $column = 'trno as line,date(startdate) as startdate,date(enddate) as enddate';
                $filter = 'trno';
                break;
            case 'Tracking Application':
                $tablename = 'obapplication';
                $column = 'line,date(dateid) as dateid,time(dateid) as itime,date(dateid2) as dateid2,time(dateid2) as itime1';
                break;
            case 'Restday Application':
            case 'Working On Rest Day Application':
                $tablename = 'changeshiftapp';
                $column = 'line,date(dateid) as dateid';
                break;
            case 'Undertime Application':
                $tablename = 'undertime';
                $column = 'line,date(dateid) as dateid,time(dateid) as itime,date(dateid2) as dateid2,time(dateid2) as itime1';
                break;
            case 'OT Application':
                $tablename = 'otapplication';
                $column = 'line,othrs,if(apothrs <> 0,apothrs,othrs) as apothrs,if(apothrsextra <> 0,apothrsextra,othrsextra) as apothrsextra,if(apndiffothrs <> 0,apndiffothrs,ndiffothrs) as apndiffothrs';
                break;
        }

        $qry = "select $column ,'$title' as title  from " . $tablename . " where $filter=?";
        return $this->coreFunctions->opentable($qry, [$line]);
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
        $line = $config['params']['dataparams']['line'];
        $data = [];

        $head = $config['params']['dataparams'];
        $filter = ['line' => $line];
        $condition = 'line=?';
        $fields = 'status';
        switch ($head['title']) {
            case 'Travel Application':
                $config['params']['doc'] = 'ITINERARY';
                $tablename = 'itinerary';
                $filter = ['trno' => $line];
                $condition = 'trno=?';
                $data = [

                    'startdate' => $head['startdate'],
                    'enddate' => $head['enddate']
                ];
                $log = ' STARTDATE: ' . $data['startdate'] . ' ENDDATE: ' . $data['enddate'];
                break;

            case 'Tracking Application':
                $config['params']['doc'] = 'OBAPPLICATION';
                $tablename = 'obapplication';
                $data = [
                    'dateid' =>  $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']),
                    'dateid2' =>  $this->othersClass->sanitizekeyfield('dateid', $head['dateid2'] . " " . $head['itime1'])
                ];
                $log = ' DATE IN: ' . $data['dateid'] . ' DATE OUT: ' . $data['dateid2'];
                break;

            case 'Restday Application':
            case 'Working On Rest Day Application':

                $config['params']['doc'] = 'WORD';
                if ($head['title'] == 'Restday Application') {
                    $config['params']['doc'] = 'RESTDAY';
                }

                $tablename = 'changeshiftapp';
                $data = [
                    'dateid' => $head['dateid']
                ];
                $fields = "cas when status = 0 then 'E' when status = 1 then 'A' else 'D' end";
                $log = ' DATE: ' . $data['dateid'];
                break;

            case 'Undertime Application':
                $config['params']['doc'] = 'UNDERTIME';
                $tablename = 'undertime';
                $data = [
                    'dateid' => $this->othersClass->sanitizekeyfield('dateid', $head['dateid'] . " " . $head['itime']),
                    'dateid2' =>  $this->othersClass->sanitizekeyfield('dateid', $head['dateid2'] . " " . $head['itime1'])
                ];
                $log = ' DATE TIME In: ' . $data['dateid'] . ' DATE TIME Out: ' . $data['dateid2'];
                break;

            case 'OT Application':
                $config['params']['doc'] = 'OTAPPLICATIONADV';
                $tablename = 'otapplication';
                $data = [
                    'apothrs' => $head['apothrs'],
                    'apothrsextra' =>  $head['apothrsextra'],
                    'apndiffothrs' =>  $head['apndiffothrs']
                ];
                $fields = "case when otstatus = 1 then 'E' when otstatus = 2 then 'A' else 'D' end";
                $log = ' Aprroved OT Hours: ' . $data['apothrs'] . ' Approved OT >8 Hours: ' . $data['apothrsextra'] . ' Approved Night Diff OT Hours: ' . $data['apndiffothrs'];
                break;
        }

        foreach ($this->fields as $key => $value) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            }
        }

        $status = $this->coreFunctions->datareader("select $fields as value from " . $tablename . " where $condition ", [$line]);
        if ($status == 'A' || $status == 'D') {
            return ['status' => false, 'msg' => 'Unable to update. Record is already approved or disapproved.', 'data' => []];
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        $this->coreFunctions->sbcupdate($tablename, $data, $filter);
        $this->logger->sbcmasterlog($line, $config, 'UPDATE INFO -' . ' Employee Activity Report ' . $log);
        return ['status' => true, 'msg' => 'Update info Successfully', 'data' => [], 'reloadlisting' => true];
    }
}
