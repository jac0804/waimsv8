<?php

namespace App\Http\Classes\modules\dashboard;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class obapplicationinitial
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'OB APPLICATION INITIAL';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:1000px;max-width:1000px;';
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
        $this->linkemail = new linkemail;
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
        $companyid = $config['params']['companyid'];
        $url = 'App\Http\Classes\modules\payroll\\' . 'otapplicationadv';
        $approversetup = app($url)->approvers($config['params']);
        $approveby = $config['params']['row']['approvedby2'];
        $fapprover = $this->coreFunctions->getfieldvalue("client", "clientname", "client=?", [$approveby]);

        $fields = ['clientname', 'lblmessage', 'rem'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'lblmessage.label', 'Employee Remarks: ');
        data_set($col1, 'rem.label', 'Remarks');
        data_set($col1, 'lblmessage.style', 'font-size:11px;font-weight:bold;');
        if ($companyid == 53) { // camera
            data_set($col1, 'lblmessage.label', 'Purpose/s');
            data_set($col1, 'rem.label', 'Purpose/s');
        }

        $fields = ['createdate', 'lblrem', 'remarks'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'remarks.readonly', false);
        data_set($col2, 'createdate.style', 'padding:0px');

        data_set($col2, 'lblrem.label', 'Initial Remarks');
        data_set($col2, 'lblrem.style', 'font-size:11px;font-weight:bold;');

        if ($companyid == 53) {
            data_set($col2, 'lblrem.label', 'Initial Reason');
            data_set($col2, 'remarks.label', 'Reason');
        }

        $fields = ['schedin', 'type', 'location'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'type.type', 'input');
        data_set($col3, 'schedin.type', 'input');
        data_set($col3, 'schedin.label', 'Applied Date Time');

        $fields = [['refresh', 'disapproved']];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'refresh.label', 'APPROVE');
        data_set($col4, 'refresh.confirm', true);
        data_set($col4, 'refresh.confirmlabel', 'Approved this OB application?');
        data_set($col4, 'refresh.color', 'blue');
        data_set($col4, 'disapproved.confirm', true);
        data_set($col4, 'disapproved.confirmlabel', 'Disapproved this OB application?');
        data_set($col4, 'disapproved.color', 'red');

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select ob.line,  client.client, client.clientname, date(ob.dateid) as dateid, date(ob.dateid) as datetime,
        ob.type,ob.approverem as remark,ob.rem,ob.disapproved_remarks2 as remarks,date(ob.createdate) as createdate,date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as schedin,
        date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,emp.email,ob.location
        from obapplication as ob 
        left join client on client.clientid=ob.empid
        left join employee as emp on emp.empid = client.clientid
        where approvedate is null and disapprovedate is null and ob.line=?", [$config['params']['row']['line']]);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {

        $empname = $config['params']['dataparams']['clientname'];
        $scheddate = $config['params']['dataparams']['scheddate'];
        $dayname = $config['params']['dataparams']['dayname'];
        $datetime = $config['params']['dataparams']['datetime'];
        $dateid = $config['params']['dataparams']['dateid'];
        $type = $config['params']['dataparams']['type'];
        $emprem = $config['params']['dataparams']['rem'];
        $email = $config['params']['dataparams']['email'];
        $location = $config['params']['dataparams']['location'];
        $remarks = $config['params']['dataparams']['remarks'];
        $remarks2 = $config['params']['dataparams']['remark'];

        $action = $config['params']['action2'];

        $companyid = $config['params']['companyid'];
        if (isset($config['params']['dataparams']['line'])) {
            $line = $config['params']['dataparams']['line'];

            switch ($action) {
                case 'ar':
                    $obstatus = 'A';
                    $label = 'Approved ';
                    $status = $this->coreFunctions->datareader("select initialapp as value from obapplication where line=? and submitdate is not null", [$line]);
                    break;

                default:
                    $obstatus = 'D';
                    $label = 'Disapproved ';
                    $status = $this->coreFunctions->datareader("select initialapp as value from obapplication where line=? and submitdate is not null", [$line]);
                    break;
            }

            if (!$status) {
                $label_reason = 'Remarks';
                if ($companyid == 53) { // camera
                    $label_reason = 'Reason';
                }
                if ($obstatus == 'D') {
                    if ($remarks == '') {
                        return ['status' => false, 'msg' => "Initial " . $label_reason . " is empty.", 'data' => []];
                    }
                }
                $data = [
                    'initialstatus' => $obstatus,
                    'initialappdate' => $this->othersClass->getCurrentTimeStamp(),
                    'initialapprovedby' => $config['params']['user'],
                    'disapproved_remarks2' => $remarks
                ];
                $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data['editby'] = $config['params']['user'];
                $update = $this->coreFunctions->sbcupdate("obapplication", $data, ['line' => $line]);
                if ($update) {
                    $appname = $this->coreFunctions->datareader("select clientname as value from client where email=?", [$config['params']['user']]);
                    if ($companyid == 53) { // camera
                        $params = [];

                        // $blnSuccess = true;
                        if (!empty($email)) {
                            $params['title'] = $this->modulename . ' INITIAL RESULT';
                            $params['clientname'] = $empname;
                            $params['line'] = $line;
                            $params['scheddate'] = $scheddate . " (" . $dayname . ")";
                            $params['dateid'] = $dateid;
                            $params['rem'] = $emprem;
                            $params['reason1'] = $remarks;
                            $params['reason2'] = $remarks2;
                            $params['datetime'] = $datetime;
                            $params['type'] = $type;
                            $params['location'] = $location;
                            $params['approvedstatus'] = $label;
                            $params['approver'] = $appname;
                            $params['email'] = $email;
                            $result = $this->linkemail->createOBInitialEmail($params);
                            if (!$result['status']) {
                                return ['status' => false, 'msg' => '' . $result['msg']];
                            }
                        }


                        // if (!$blnSuccess) {
                        //     $obinitialapp = [
                        //         'initialstatus' => '',
                        //         'initialapprovedby' => ''
                        //     ];

                        //     $update = $this->coreFunctions->sbcupdate("obapplication", $obinitialapp, ['line' => $line]);
                        //     return ['status' => false, 'msg' => 'Sending email failed: email was empty.'];
                        // }
                    }


                    $config['params']['doc'] = 'OBAPPLICATIONINITIAL';
                    $this->logger->sbcmasterlog($config['params']['dataparams']['line'], $config, $label . $config['params']['dataparams']['type'] . ' (' . $config['params']['dataparams']['client'] . ') - ' . $config['params']['dataparams']['dateid']);
                    return ['status' => true, 'msg' => 'Successfully approved.', 'data' => [], 'reloadsbclist' => true, 'action' => 'obapplicationinitial'];
                }
            } else {
                return ['status' => false, 'msg' => 'Already approved.', 'data' => []];
            }
        }

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => []];
    }
} //end class
