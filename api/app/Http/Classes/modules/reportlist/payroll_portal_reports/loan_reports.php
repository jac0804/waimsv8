<?php

namespace App\Http\Classes\modules\reportlist\payroll_portal_reports;

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
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class loan_reports
{
    public $modulename = 'Loan Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1140'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint',  'divname', 'dclientname', 'start', 'end'];
        if ($companyid == 51) { // ulitc
            array_push($fields, 'radioreporttype');
        }
        array_push($fields, 'radioposttype');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dateid.label', 'Date');
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

        data_set($col1, 'radioposttype.options', [
            ['label' => 'ENTRY', 'value' => 'entry', 'color' => 'red'],
            ['label' => 'APPROVED', 'value' => 'approved', 'color' => 'red']
        ]);
        if ($companyid == 51) { //ulitc
            array_push($fields, 'radioreporttype');
            data_set($col1, 'radioreporttype.options', [
                ['label' => 'MULTI-PURPOSE LOAN', 'value' => 'multi', 'color' => 'red'],
                ['label' => 'MULTI-PURPOSE LOAN 2', 'value' => 'multi2', 'color' => 'red'],
                ['label' => 'CAR LOAN', 'value' => 'carloan', 'color' => 'red'],
                ['label' => 'Default', 'value' => 'default', 'color' => 'red']
            ]);
            data_set($col1, 'dateid.label', 'Payroll Dates');
            data_set($col1, 'dateid.type', 'input');
            data_set($col1, 'startdate.readonly', false);
            data_set($col1, 'startdate.type', 'input');
            data_set($col1, 'startdate.label', 'From:');
            data_set($col1, 'enddate.label', 'To:');
            data_set($col1, 'enddate.type', 'input');
        }
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                adddate(left(now(), 10),-360) as start,
                left(now(),10) as end,
                0 as divid,
                    'default' as reporttype,
                    'approved' as posttype,
                    '' as client,
                    '' as clientname,
                    '' as dclientname,
                    '0' as divid,
                    '' as divname,
                    '' as division"

        );
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($companyid) {
            case 51: //ulitc

                switch ($reporttype) {
                    case 'multi':
                    case 'multi2':

                        $result = $this->report_multi_loan($config);
                        break;
                    case 'carloan':
                        $result = $this->report_Layout_car_loan($config);
                        break;
                    default:
                        $result = $this->report_ulitc_Layout_loan($config);
                        break;
                }

                break;
            case 53: //camera
                $result = $this->report_camera_Layout_loan($config);
                break;

            default:
                $result = $this->reportDefaultLayout_loan($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {

        $query = $this->loan_query($config);

        return $this->coreFunctions->opentable($query);
    }

    public function loan_query($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $divid = $config['params']['dataparams']['divid'];
        $client     = $config['params']['dataparams']['client'];
        $adminid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $posttype     = $config['params']['dataparams']['posttype'];

        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';

        $filter = "";
        $status = "";

        $filteremp = "";
        $leftjoin = "";

        $check = $this->othersClass->checkapproversetup($config, $adminid, 'LOAN', 'e');
        if ($check['filter'] != "") {
            $filteremp .= $check['filter'];
        }
        if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
        }

        $jobtitle = "e.jobtitle";
        if ($companyid == 51) {
            $jobtitle = "jt.jobtitle";
            switch ($reporttype) {
                case 'multi':
                case 'multi2':
                    $filter .= " and p.code <> 'PT119' ";
                    break;
                case 'carloan':
                    $filter .= " and p.code = 'PT119' ";
                    break;
            }
        }
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
        }
        $status = "";
        switch ($posttype) {
            case 'entry':
                $status = " loan.status = 'E' and ";
                break;
            case 'approved':
                $status = " loan.status = 'A' and ";
                break;
        }

        if ($both) {
            goto setup1;
        }

        $curdate = $this->othersClass->getCurrentDate();
        if (count($approversetup) > 1) {
            $case = " case 
            when loan.status2 = 'E' and loan.submitdate is null then 'ENTRY'
            when loan.status2 = 'E' and loan.submitdate is not null then 'FOR APPROVAL'
            when loan.status2 = 'A' then 'APPROVED'
            when loan.status2 = 'D' then 'DISAPPROVED'
            end  as status2,
            case 
            when loan.status = 'E' and loan.submitdate is not null then 'FOR APPROVAL'
            when loan.status = 'A' then 'APPROVED'
            when loan.status = 'D' then 'DISAPPROVED'
            end  as status ";
        } else {
            setup1:
            $case = " 
            case 
            when loan.status = 'E' and loan.submitdate is not null then 'FOR APPROVAL'
            when loan.status = 'A' then 'APPROVED'
            when loan.status = 'D' then 'DISAPPROVED'
            end  as status";
        }


        $query = "
        select loan.docno,loan.amt,loan.apamt, date(loan.dateid) as dateid,p.codename,cl.clientname,cl.client as empcode,approver.clientname as approvedby,
        loan.date_approved_disapproved as approvedate,dept.clientname as department,TIMESTAMPDIFF(YEAR, e.hired, '" . $curdate . "') as years,
        if(p.code = 'PT119',loan.purpose1,loan.purpose) as purpose,
        $case ,date(loan.effdate) as scheddate,loan.balance,divi.divname as division,loan.licenseno,loan.licensetype,loan.remarks,
        loan.cashadv,loan.saldedpurchase,loan.chgduelosses,loan.uniforms,loan.otherchgloan,loan.sssploan,approver2.clientname as appname2,approver2.email as appemail2,
        date_format(loan.termfrom, '%m-%d-%y') as termfrom,date_format(loan.termto, '%m-%d-%y') as termto,date_format(loan.payrolldate, '%m-%d-%y') as payrolldate,loan.amortization,loan.apamortization,
        date(date_approved_disapproved) as appdate,date(loan.date_approved_disapproved2) as appdate2,loan.disapproved_remarks as reason,loan.disapproved_remarks2 as reason2 ,$jobtitle
        from loanapplication as loan
        left join employee as e on e.empid=loan.empid
        left join paccount as p on p.line=loan.acnoid
        left join client as cl on cl.clientid=e.empid
        left join client as dept on dept.clientid = e.deptid
        left join jobthead as jt on jt.line = e.jobid
        left join division as divi on divi.divid = e.divid
        left join client as approver on approver.email = loan.approvedby_disapprovedby and approver.email <> ''
        left join client as approver2 on approver2.email = loan.approvedby_disapprovedby2 and approver2.email <> ''
        $leftjoin
        where $status date(loan.dateid) between '" . $start . "' and '" . $end . "' $filter  $filteremp ";
        return $query;
    }

    public function header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOAN REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range Date : ' . $start . 'to ' . $end, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config, $seqcount)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Document No.', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Date', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Account Name', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('First Status', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved Date', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved By', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        }


        $str .= $this->reporter->col('Last Status', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Date', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_loan($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $stime = '';

        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
        }
        $layoutsize = '1500';
        $seqcount = count($approversetup);
        if ($seqcount == 1 || $both) {
            $seqcount = 1;
            $layoutsize = $this->reportParams['layoutSize'];
        }

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->tableheader($layoutsize, $config, $seqcount);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->empcode, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->codename, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                if ($seqcount > 1) {
                    $str .= $this->reporter->col($data->status2, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appdate2, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appname2, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                }

                $str .= $this->reporter->col($data->status, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedate, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function camera_header($config, $seqcount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $companyid   = $config['params']['companyid'];

        $str = '';
        $layoutsize = $this->reportParams['layoutSize'];
        if ($seqcount > 1) {
            $layoutsize = '1640';
        }
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";
        $companyid   = $config['params']['companyid'];
        $divid = $config['params']['dataparams']['divid'];
        $str .= $this->reporter->begintable($layoutsize);
        if ($companyid == 51) { //ulitc
            if ($divid != '0') {

                $qry = "select code,name,address,tel from center where code = '" . $center . "'";
                $headerdata = $this->coreFunctions->opentable($qry);
                $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
                $divname = $config['params']['dataparams']['divname'];
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, '9', '', '', '') . '<br />';
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($divname, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
                $str .= $this->reporter->endrow();
            } else {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->letterhead($center, $username, $config);
                $str .= $this->reporter->endrow();
            }
        } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $config);
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOAN REPORTS', null, null, false, $border, '', '', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range Date : ' . $start . 'to ' . $end, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Date', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Account Name', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('First Status', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved/Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved Reason', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('Last Status', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_camera_Layout_loan($config)
    {


        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        $url = 'App\Http\Classes\modules\payroll\\' . 'loanapplicationportal';
        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='LOAN'");
        $both = false;
        if ($approversetup == '') {
            $approversetup = app($url)->approvers($config['params']);
        } else {
            if (str_contains($approversetup, ' or ')) {
                $approversetup = explode(' or ', $approversetup);
                $both = true;
            } else {
                $approversetup = explode(',', $approversetup);
            }
        }

        $layoutsize = '1640';
        $seqcount = count($approversetup);
        if ($seqcount == 1 || $both) {
            $seqcount = 1;
            $layoutsize = $this->reportParams['layoutSize'];
        }


        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->camera_header($config, $seqcount);
        //createdate,clientname,scheddate,codename,appdate,,
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->scheddate, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->codename, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->balance, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                if ($seqcount > 1) {
                    $str .= $this->reporter->col($data->status2, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appdate2, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appname2, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->reason2, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }
                $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->reason, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function header_multi_loan($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $reporttype   = $config['params']['dataparams']['reporttype'];

        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = $this->reportParams['layoutSize'];
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= '<br/><br/>';

        $logopath = URL::to($this->companysetup->getlogopath($config['params']) . 'united_limsun.png');
        $str .= '<div style="position: relative;">';
        $str .= "<div style='position:absolute; margin:-60px 0 0 -805px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('<img src ="' . $logopath . '" alt="united" width="190px" height ="90px">', '250', null, false, '1px solid', '', 'R', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "</div>";

        $title = "EMPLOYEE MULTI-PURPOSE LOAN APPLICATION FORM";

        if ($reporttype == 'carloan') {
            $title = "EMPLOYEE CAR LOAN APPLICATION FORM";
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($title, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        return $str;
    }

    public function report_multi_loan($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $fontsize2 = "9";
        $layoutsize = 1000;
        // $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

        $this->reportParams['layoutSize'] = 1000;
        $this->reportParams['orientation'] = 'p';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_multi_loan($config);
        $j = 0;
        $total_loans = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Employee Name: ', '150', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Date of Appplication:', '150', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '350', null, false, $border, 'TR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->clientname, '350', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '150', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->dateid, '350', 15, false, $border, 'R', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Position Title', '150', 15, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data->jobtitle, '350', 15, false, $border, 'LT', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Employee ID No.', '150', 15, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data->empcode, '350', 15, false, $border, 'LTR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Department', '150', 15, false, $border, 'BTL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->department, '350', 15, false, $border, 'BLT', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Years of Service', '150', 15, false, $border, 'BTL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->years, '350', 15, false, $border, 'BLTR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '150', 15, false, $border, 'BL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '350', 15, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '150', 15, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '350', 15, false, $border, 'BR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Purpose of Loan: (please check appropriate box)', null, 15, false, $border, 'LR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '400', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '50', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '400', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();



                $checktui = '';
                $checkmed = '';
                $checkapp = '';
                $checkpur = '';
                $checkrep = '';
                $checkfin = '';
                $style = 'style=""';
                $disable1 = 'disabled';
                $disable2 = 'disabled';
                $disable3 = 'disabled';
                $disable4 = 'disabled';
                $disable5 = 'disabled';
                $disable6 = 'disabled';

                switch ($data->purpose) {
                    case "Tuition fee of employee`s child":
                        $checktui = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable1 = '';
                        break;
                    case "Medical expenses of employee or his dependents":
                        $checkmed = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable2 = '';
                        break;
                    case "House Appliance/s":
                        $checkapp = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable3 = '';
                        break;

                    case "Purchase of laptop or desk top of computer":
                        $checkpur = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable4 = '';
                        break;
                    case "House Repairs":
                        $checkrep = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable5 = '';
                        break;
                    case "Financial assistance in cases of calamity":
                        $checkfin = 'checked="checked"';
                        $style = 'style="accent-color: black; pointer-events: none;"';
                        $disable6 = '';
                        break;
                }

                $tuition = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checktui . ' ' . $style . $disable1 . '  />';
                $medical = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checkmed . ' ' . $style . $disable2 . '  />';
                $appliance = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checkapp . ' ' . $style . $disable3 . '  />';
                $purchase = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checkpur . ' ' . $style . $disable4 . '  />';
                $repairs = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checkrep . ' ' . $style . $disable5 . '  />';
                $financial = '<input type="checkbox" name="agree1" value="1" readonly="true" ' . $checkfin . ' ' . $style . $disable6 . '  />';

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($tuition, '50', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '', '-10px');
                $str .= $this->reporter->col('Tuition fee of employee`s child', '400', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($medical, '50', 15, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Medical expenses of employee or his dependents', '400', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($appliance, '50', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('House Appliance/s', '400', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($purchase, '50', 15, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Purchase of laptop or desk top of computer', '400', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($repairs, '50', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('House Repairs', '400', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($financial, '50', 15, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Financial assistance in cases of calamity', '400', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '400', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '50', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '400', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Total Price: (please attach copy of quotation)', '480', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '480', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Monthly Amortizations ', '480', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Php ' . number_format($data->amortization, 2), '480', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Payroll Dates: ' . $data->payrolldate, '480', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('From: ' . $data->termfrom, '480', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('To: ' . $data->termto, '480', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '20', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'RB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Endorsed by:', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $data->clientname, '500', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Employee Name & Signature', '500', 15, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("Employee's Immediate Supervisor", '500', 15, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TO BE FILLED UP BY HR', null, 15, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $total_loans += ($data->cashadv + $data->saldedpurchase + $data->chgduelosses + $data->uniforms + $data->otherchgloan + $data->sssploan);

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("Employee's Outstanding Loans:", '480', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Cash Advance', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cashadv != 0 ? number_format($data->cashadv, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Salary Deduction Purchase', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->saldedpurchase != 0 ? number_format($data->saldedpurchase, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('SSS/Pag-Ibig Loan', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sssploan != 0 ? number_format($data->sssploan, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Charges due to Losses', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->chgduelosses != 0 ? number_format($data->chgduelosses, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Uniforms', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->uniforms != 0 ? number_format($data->uniforms, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Other Charges/Loans', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->otherchgloan != 0 ? number_format($data->otherchgloan, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('TOTAL LOAN: ', '480', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($total_loans != 0 ? number_format($total_loans, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'BL', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'BR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $supervisorposition = "";

                if ($data->appemail2 != "") {
                    $query = "select clientid as value from client where email = '" . $data->appemail2 . "'";
                    $clientid = $this->coreFunctions->datareader($query, [$config['params']['adminid']]);
                    $supervisorposition = $this->coreFunctions->datareader("select jt.jobtitle as value from employee as emp 
                left join jobthead as jt on jt.jobid = emp.jobid where emp.empid = ? ", [$clientid]);
                }

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Prepared by:', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Noted by: ', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->appname2, '480', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'BL', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $supervisorposition, '480', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('HR & Administrative Manager', '500', 15, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Approved by: ', '480', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Approved by: ', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();



                $approvedby = '';
                $appdate = '';
                $datedustine = '';
                $dateirish = '';
                $dusposition = '';
                $irisposition = '';

                $fixapp = $this->coreFunctions->opentable("
                 select client.clientname,client.email,jt.jobtitle,emp.empid from employee as emp 
                 left join client on client.clientid = emp.empid
                 left join jobthead as jt on jt.line = emp.jobid 
                 where emp.empid in (259,260)");

                foreach ($fixapp as $key => $app) {
                    if ($app->empid == 259) { //dustine
                        if ($app->email == $approvedby) {
                            $datedustine = $appdate;
                        }
                        $dusposition = $app->jobtitle;
                    } else {
                        if ($app->email == $approvedby) {
                            $dateirish = $appdate;
                        }
                        $irisposition = $app->jobtitle;
                    }
                }

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Irish Sun Lim ' . $dateirish, '480', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Dustin Go Lim ' . $datedustine, '500', 15, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '20', 15, false, $border, 'BL', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($irisposition, '480', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($dusposition, '500', 15, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '</br>';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('AUTHORIZATION FOR VOLUNTARY PAYROLL DEDUCTION', null, 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= '</br>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 25, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('I ', 10, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->clientname, 240, 25, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(' here by authorize ', 150, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->division, 500, 25, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(' to ', 40, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 25, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $amountword = $this->ftNumberToWordsConverter($data->amt);

                $arr_amountword = $this->reporter->fixcolumn([$amountword], '45', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_amountword]);

                $line1 = "";
                $line2 = "";
                for ($r = 0; $r < $maxrow; $r++) {
                    if ($r == 0) {
                        $line1 = (isset($arr_amountword[$r]) ? $arr_amountword[$r] : '');
                    }
                    if ($r == 1) {
                        $line2 = (isset($arr_amountword[$r]) ? $arr_amountword[$r] : '');
                    }
                }


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('deduct from my wage for my Multi-Purpose Loan ', 330, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(' amounting to ', 120, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($line1, null, 25, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($line2, 200, 25, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(" , beginning ", 90, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("" . $data->termfrom, 150, 25, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(" and ending ", 120, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("" . $data->termto, 200, 25, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(",", 5, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("until the amount of", null, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("" . number_format($data->amt, 2), 200, 25, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("has been deducted.", 800, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '<br/>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("", 30, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("In the event that my employment ends for any reason before are final deduction is made, the entire", null, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("balance will be deducted from my final wages.", null, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '<br>';
                // $str .= '<br>';
                //. $data->clientname

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("" . $data->clientname, 450, 25, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col("", 50, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("", 50, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("" . $data->dateid, 450, 25, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Employee's Printed Name above Signature", 450, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("", 50, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("", 50, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("Date Signed", 450, 25, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '<br/>';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Rev.01 071823otm", null, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Copy furnished: Accounting Department and employee's 201 records", null, 25, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if (count($result) != ($j + 1)) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_multi_loan($config);
                }
                $j++;
                $total_loans = 0;
            }
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
    public function report_Layout_car_loan($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $fontsize2 = "9";
        $layoutsize = 1000;
        // $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

        $this->reportParams['layoutSize'] = 1000;
        $this->reportParams['orientation'] = 'p';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_multi_loan($config);
        $j = 0;
        $total_loans = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Employee Name: ', '170', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->clientname, '330', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Date of Appplication:', '170', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('' . $data->dateid, '330', null, false, $border, 'TR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '170', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '330', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '170', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '330', 15, false, $border, 'R', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Position Title', '170', 15, false, $border, 'TL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->jobtitle, '330', 15, false, $border, 'LT', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Employee ID No.', '170', 15, false, $border, 'TL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->empcode, '330', 15, false, $border, 'LTR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Department', '170', 15, false, $border, 'BTL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->department, '330', 15, false, $border, 'BLT', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Years of Service', '170', 15, false, $border, 'BTL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->years, '330', 15, false, $border, 'BLTR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Driver's License no.:", '170', 15, false, $border, 'BTL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->licenseno, '330', 15, false, $border, 'BLT', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col("Type of Driver's License:", '170', 15, false, $border, 'BTL', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $data->licensetype, '330', 15, false, $border, 'BLTR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();


                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '170', 15, false, $border, 'BL', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '330', 15, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '170', 15, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '330', 15, false, $border, 'BR', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();



                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Purpose of Car Loan:', null, 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->endtable();

                $arr_purpose = $this->reporter->fixcolumn([$data->purpose], '98', 0);
                $arr_remarks = $this->reporter->fixcolumn([$data->remarks], '98', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_remarks]);

                $line1 = "";
                $line2 = "";
                $line3 = "";

                $remarks1 = "";
                $remarks2 = "";
                for ($r = 0; $r < $maxrow; $r++) {
                    if ($r == 0) {
                        $line1 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
                        $remarks1 = (isset($arr_remarks[$r]) ? $arr_remarks[$r] : '');
                    }
                    if ($r == 1) {
                        $line2 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
                        $remarks2 = (isset($arr_remarks[$r]) ? $arr_remarks[$r] : '');
                    }
                    if ($r == 2) {
                        $line3 = (isset($arr_purpose[$r]) ? $arr_purpose[$r] : '');
                    }
                }
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 50, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $line1, 930, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 30, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $line2, 950, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 30, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $line3, 950, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Remarks: ', null, 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 50, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $remarks1, 930, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 30, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('' . $remarks2, 950, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 30, 20, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 950, 20, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', 20, 20, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Endorse by:', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $data->clientname, '500', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Employee Name and Signature', '500', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col("Employee's Immediate Head", '500', 15, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('if in case the employee chose a car which is higher than the budget, any excess in amount will be shouldered by the employee.', null, 15, false, $border, 'LRB', 'C', $font, $fontsize, 'Bi', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('TO BE FILLED UP BY HR', null, 15, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Employee's Outstanding Loans:", '500', 15, false, $border, 'L', 'L', $font, $fontsize, 'Bi', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Cash Advance", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cashadv != 0 ? number_format($data->cashadv, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Salary Deduction Purchase", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->saldedpurchase != 0 ? number_format($data->saldedpurchase, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("SSS/Pag-Ibig Loan", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sssploan != 0 ? number_format($data->sssploan, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Charges due to Losses", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->chgduelosses != 0 ? number_format($data->chgduelosses, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Uniforms", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->uniforms != 0 ? number_format($data->uniforms, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Other Charges/Loans", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->otherchgloan != 0 ? number_format($data->otherchgloan, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("", '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $total_loans += ($data->cashadv + $data->saldedpurchase + $data->chgduelosses + $data->uniforms + $data->otherchgloan + $data->sssploan);

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("TOTAL LOAN", '500', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($total_loans != 0 ? number_format($total_loans, 2) : '', '250', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Employee's Disciplinary Action Record:", '500', 15, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col("Employee's Promotion Record:", '250', 15, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '250', 15, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '480', 15, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '10', 15, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $supervisorposition = "";

                if ($data->appemail2 != "") {
                    $query = "select clientid as value from client where email = '" . $data->appemail2 . "'";
                    $clientid = $this->coreFunctions->datareader($query, [$config['params']['adminid']]);
                    $supervisorposition = $this->coreFunctions->datareader("select jt.jobtitle as value from employee as emp 
                left join jobthead as jt on jt.jobid = emp.jobid where emp.empid = ? ", [$clientid]);
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Prepared by: ', '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Noted by: ', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $data->appname2, '500', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $supervisorposition, '500', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('HR & Administrative Manager', '500', 15, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, 15, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Approved by:  ', '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Approved by:  ', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '500', 15, false, $border, 'L', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '500', 15, false, $border, 'LR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $approvedby = '';
                $appdate = '';
                $datedustine = '';
                $dateirish = '';
                $dusposition = '';
                $irisposition = '';

                $fixapp = $this->coreFunctions->opentable("
                 select client.clientname,client.email,jt.jobtitle from employee as emp 
                 left join client on client.clientid = emp.empid
                 left join jobthead as jt on jt.line = emp.jobid 
                 where emp.empid in (259,260)");

                foreach ($fixapp as $key => $app) {
                    if ($app->empid == 259) { //dustine
                        if ($app->email == $approvedby) {
                            $datedustine = $appdate;
                        }
                        $dusposition = $app->jobtitle;
                    } else {
                        if ($app->email == $approvedby) {
                            $dateirish = $appdate;
                        }
                        $irisposition = $app->jobtitle;
                    }
                }

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Irish Sun Lim ' . $dateirish, '500', 15, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('Dustin Go Lim ' . $datedustine, '500', 15, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($irisposition, '500', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($dusposition, '500', 15, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("Copy furnished: Accounting Department and Employees's 201 records.", null, 15, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                if (count($result) != ($j + 1)) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->header_multi_loan($config);
                }
                $j++;
                $total_loans = 0;
            }
        }





        $str .= $this->reporter->endreport();
        return $str;
    }
    public function ftNumberToWordsConverter($number)
    {
        $numberwords = $this->reporter->ftNumberToWordsBuilder($number);

        if (strpos($numberwords, "/") == false) {
            $numberwords .= " PESOS ";
        } else {
            $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
        } //end if

        return $numberwords;
    } //end function convert to words

    public function ulit_header_default($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1400';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "7";
        $border = "1px solid ";
        $companyid   = $config['params']['companyid'];
        $divid = $config['params']['dataparams']['divid'];
        $str .= $this->reporter->begintable($layoutsize);

        if ($divid != '0') {

            $qry = "select code,name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
            $divname = $config['params']['dataparams']['divname'];
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, '9', '', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($divname, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
        } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username, $config);
            $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOAN REPORTS', null, null, false, $border, '', '', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range Date : ' . $start . 'to ' . $end, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Applied', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Purpose of Loan', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Account Name', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Applied Amount', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amortization', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Status(HR)', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/Disapproved(HR)', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/Disapproved By:(HR)', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Status', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/Disapproved', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/Disapproved By', '135', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Approved Amount', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Amortization', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_ulitc_Layout_loan($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "7";
        $border = "1px solid ";
        $layoutsize = '1400';
        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->ulit_header_default($config);
        //createdate,clientname,scheddate,codename,appdate,,
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->scheddate, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->purpose, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->codename, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amortization, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->status2, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate2, '135', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appname2, '135', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


                $str .= $this->reporter->col($data->status, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate, '135', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '135', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


                $str .= $this->reporter->col(number_format($data->apamt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->apamortization, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class