<?php

namespace App\Http\Classes\modules\customform;

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
use Illuminate\Support\Facades\Storage;

class viewloandetail
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'LOAN DETAILS';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'payroll_log';
    public $style = 'width:80%;max-width:80%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $fields = ['cashadv', 'sssploan', 'saldedpurchase', 'chgduelosses', 'uniforms', 'otherchgloan', 'termfrom', 'termto'];


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
        $this->modulename .= ' - ' . $config['params']['row']['codename'] . ' - ' . $config['params']['row']['clientname'];
        return [];
    }

    public function createtabbutton($config)
    {
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $code = $config['params']['row']['code'];
        $fields = ['lblrem', ['cashadv', 'saldedpurchase'], ['loanamt', 'duelosses'], ['uniforms', 'others1']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'duelosses.readonly', false);
        data_set($col1, 'loanamt.readonly', false);
        data_set($col1, 'loanamt.label', 'SSS/Pag-ibig Loan');
        data_set($col1, 'others1.label', 'Other Charges/Loans');

        data_set($col1, 'lblrem.label', "Employee's Outstanding Loans:");
        data_set($col1, 'lblrem.style', 'font-size:12px;font-weight:bold;');

        $fields = ['lblrem', 'startdate', ['termfrom', 'termto']];
        $col2 = $this->fieldClass->create($fields);

        data_set($col2, 'startdate.style', 'padding:0px;');
        data_set($col2, 'startdate.readonly', false);
        data_set($col2, 'startdate.required', true);
        data_set($col2, 'startdate.label', 'Payroll Date: ');

        data_set($col2, 'lblrem.label', "Payment Term: ");
        data_set($col2, 'lblrem.style', 'font-size:12px;font-weight:bold;');

        if ($code == 'PT119') {
            data_set($col2, 'startdate.readonly', true);
            data_set($col2, 'startdate.required', false);

            data_set($col2, 'termfrom.readonly', true);
            data_set($col2, 'termto.readonly', true);
        }
        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.label', 'UPDATE DETAILS');
        data_set($col3, 'lblrem.label', "");
        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['row']['trno'];
        $qry = "select acc.code,loan.trno,loan.empid,loan.cashadv,loan.saldedpurchase,loan.sssploan as loanamt,loan.chgduelosses as duelosses,loan.uniforms,loan.otherchgloan as others1,
         loan.payrolldate as startdate,loan.termfrom,loan.termto
         from loanapplication as loan
         left join paccount as acc on acc.line = loan.acnoid
          where trno = ?";
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function data($config)
    {
        return [];
    }

    public function loaddata($config)
    {
        $data = $config['params']['dataparams'];

        if ($data['code'] != 'PT119') {
            if ($data['startdate'] == null) {
                return ['status' => false, 'msg' => 'Payrll Date is empty.', 'data' => []];
            }
            if ($data['termfrom'] == null) {
                return ['status' => false, 'msg' => 'From Date is empty .', 'data' => []];
            }
            if ($data['termto'] == null) {
                return ['status' => false, 'msg' => 'To Date is empty.', 'data' => []];
            }
        }

        $update_detail = [
            'cashadv' => $data['cashadv'],
            'sssploan' => $data['loanamt'],
            'saldedpurchase' => $data['saldedpurchase'],
            'chgduelosses' => $data['duelosses'],
            'uniforms' => $data['uniforms'],
            'otherchgloan' => $data['others1']
        ];

        if ($data['code'] != 'PT119') {
            $update_detail['payrolldate'] = $data['startdate'];
            $update_detail['termfrom'] = $data['termfrom'];
            $update_detail['termto'] = $data['termto'];
        }

        $update = $this->coreFunctions->sbcupdate("loanapplication", $update_detail, ['trno' => $data['trno'], 'empid' => $data['empid']]);
        $status = true;
        if ($update) {
            $msg = 'Outstanding loans have been successfully updated.';
        } else {
            $status = false;
            $msg = 'Failed to update Outstanding Loans.';
        }
        -$this->logger->sbcmasterlog($data['trno'], $config,  $msg . '  Cash Advance: ' . $data['cashadv'] . ' Salary Deduction Purchase:  ' . $data['saldedpurchase'] .
            ' SSS/Pag-ibig Loan: ' . $data['loanamt'] . ' Charges due to Losses: ' . $data['duelosses'] . ' Uniform: ' . $data['uniforms'] . ' Other Charges/Loans: ' . $data['others1']);
        return ['status' => $status, 'msg' => $msg, 'data' => []];
    }
} //end class
