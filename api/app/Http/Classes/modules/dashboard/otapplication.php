<?php

namespace App\Http\Classes\modules\dashboard;

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

class otapplication
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'OT APPLICATION';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $tablelogs = 'payroll_log';
    public $style = 'width:900px;max-width:900px;';
    public $issearchshow = true;
    public $showclosebtn = true;



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function createTab($config)
    {
        $obj = [];
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {

        $fields = ['clientname', 'remarks'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'remarks.readonly', false);
        data_set($col1, 'remarks.style', 'width:405;whiteSpace: normal;min-width:405px;');
        data_set($col1, 'remarks.readonly', true);

        $fields = ['dateid'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.type', 'input');

        $fields = ['othrs', 'ndiffot', 'entryot', 'entryndiffot'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'type.type', 'input');
        data_set($col3, 'othrs.label', 'Computed OT Hrs');
        data_set($col3, 'ndiffot.label', 'Computed N-Diff OT Hrs');
        data_set($col3, 'entryot.readonly', true);
        data_set($col3, 'entryndiffot.readonly', true);

        $fields = [['refresh', 'disapproved']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.confirmlabel', 'Approved this OT application?');
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this OT application?');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {

        return $this->coreFunctions->opentable('select ot.line, client.client, client.clientname, ot.dateid, ot.othrs, ot.ndiffhrs as ndiffot, ot.entryremarks as remarks, ot.entryot, ot.entryndiffot from timecard as ot left join client on client.clientid=ot.empid where ot.line=?', [$config['params']['row']['line']]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {

        $action = $config['params']['action2'];
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplication';
        $approversetup = app($url)->approvers($config['params']);
        if (isset($config['params']['dataparams'])) {

            if (isset($config['params']['dataparams']['line'])) {
                $line = $config['params']['dataparams']['line'];
                $rem = $config['params']['dataparams']['remarks'];

                switch ($action) {
                    case 'ar':
                        $otstatus = 2;
                        $label = 'Approved ';
                        $status = $this->coreFunctions->datareader("select otstatus as value from timecard where line=? and otstatus = 2 ", [$line]);
                        break;

                    default:
                        $otstatus = 3;
                        $label = 'Disapproved ';
                        $status = $this->coreFunctions->datareader("select otstatus as value from timecard where line=? and otstatus = 3 ", [$line]);
                        break;
                }

                $companyid = $config['params']['companyid'];
                $approver = $this->coreFunctions->getfieldvalue("employee", "isapprover", "empid=?", [$config['params']['adminid']]);
                $supervisor = $this->coreFunctions->getfieldvalue("employee", "issupervisor", "empid=?", [$config['params']['adminid']]);

                if (!$status) {

                    foreach ($approversetup as $key => $value) {

                        if (count($approversetup) > 1) {
                            if ($key == 0) {
                                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                                    $data = [
                                        'otstatus2' => $otstatus,
                                        'approvedby_disapprovedby2' => $config['params']['user'],
                                        'date_approved_disapproved2' => $this->othersClass->getCurrentTimeStamp()
                                    ];

                                    if ($otstatus == 2) { // approved
                                        $data['othrs'] = $config['params']['dataparams']['entryot'];
                                        $data['ndiffot'] = $config['params']['dataparams']['entryndiffot'];
                                    } else { // disapproved
                                        $data['otstatus'] = $otstatus;
                                        $data['entryremarks'] = $config['params']['dataparams']['entryndiffot'];
                                    }
                                    break;
                                }
                            } else {
                                if ($value == 'issupervisor' && $supervisor || $value == 'isapprover' && $approver) {
                                    if ((count($approversetup) - 1) == $key) {
                                        goto approved;
                                    }
                                }
                            }
                        } else {
                            if (count($approversetup) == 1) {
                                approved:

                                if ($otstatus == 2) { // approved
                                    $data = [
                                        'otstatus' => $otstatus,
                                        'otapproved' => 1,
                                        'othrs' => $config['params']['dataparams']['entryot'],
                                        'ndiffot' => $config['params']['dataparams']['entryndiffot'],

                                    ];
                                } else {  // disapproved
                                    $data = [
                                        'otstatus' => $otstatus,
                                        'entryremarks' => $rem
                                    ];
                                }
                                break;
                            }
                        }
                    }

                    $data['isok'] = 0;
                    $update = $this->coreFunctions->sbcupdate("timecard", $data, ['line' => $line]);
                    if ($update) {
                        $config['params']['doc'] = 'OTAPPLICATION';
                        $this->logger->sbcmasterlog($line, $config, $label . $config['params']['dataparams']['remarks'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid'] .
                            ". Computed OT Hrs: " . $config['params']['dataparams']['othrs'] . " -  Approved OT Hrs: " . $config['params']['dataparams']['entryot'] .
                            ". Computed N-Diff OT Hrs: " . $config['params']['dataparams']['ndiffot'] . " -  Approved N-Diff OT Hrs: " . $config['params']['dataparams']['entryndiffot']);
                        return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'otapplication'];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
                }
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
