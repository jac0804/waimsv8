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

class ob_filling_reports
{
    public $modulename = 'OB Filling Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1500'];

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

        data_set($col1, 'radioposttype.options', [
            ['label' => 'ENTRY', 'value' => 'entry', 'color' => 'red'],
            ['label' => 'APPROVED', 'value' => 'approved', 'color' => 'red']
        ]);
        data_set($col1, 'dateid.label', 'Date');
        data_set($col1, 'dateid.readonly', false);
        data_set($col1, 'divname.type', 'lookup');
        data_set($col1, 'divname.lookupclass', 'lookupempdivision');
        data_set($col1, 'divname.action', 'lookupempdivision');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        // data_set($col1, 'radioprint.options', [
        //     ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        // ]);


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
                    '0' as reporttype,
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
            case 53:
                $result = $this->report_camera_Layout_ob($config);
                break;
            case 51:
                if ($reporttype != 0) {
                    $result = $this->report_detailed_Layout_ob($config);
                } else {
                    goto def;
                }
                break;
            default:
                def:
                $result = $this->reportDefaultLayout_ob($config);
                break;
        }
        return $result;
    }

    public function reportDefault($config)
    {

        $query = $this->ob_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function ob_QUERY($config)
    {
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $divid = $config['params']['dataparams']['divid'];
        $client     = $config['params']['dataparams']['client'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $adminid = $config['params']['adminid'];
        $companyid = $config['params']['companyid'];

        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
        $user = $config['params']['user'];

        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
        $data = app($url)->approvers($config['params']);
        $filter = "";
        $status = "";

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
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
        $filteremp = "";
        $leftjoin = "";

        $check = $this->othersClass->checkapproversetup($config, $adminid, 'OB', 'emp');
        if ($check['filter'] != "") {
            $filteremp .= $check['filter'];
        }
        if ($check['leftjoin'] != "") {
            $leftjoin .= $check['leftjoin'];
        }


        switch ($posttype) {
            case 'entry': //ulit
                $status = " ob.status = 'E' and ";
                break;
            case 'approved': //ulit
                $status = " ob.status = 'A' and ";
                break;
        }

        $addfields = "";
        $jobtitle = ""; //ob.
        $jobtitle = ' jt.jobtitle,';
        switch ($companyid) {
            case 53: //camera
                $jobtitle = 'emp.jobtitle,';
                $addfields = "
                  ,case
                  when ob.status2 = 'E' and ob.initialapp is null then 'ENTRY'
                  when ob.status2 = 'E' and ob.initialapp is not null then 'FOR APPROVAL'               
                  when ob.status2 = 'D' then 'DISAPPROVED'
                  when ob.status2 = 'A' then 'APPROVED'
                  end as status2,
                  case
                  when ob.initialstatus = '' and ob.initialapp is null then 'ENTRY'
                  when ob.initialstatus = '' and ob.initialapp is not null then 'FOR APPROVAL'              
                  when ob.initialstatus = 'A' then 'INITIAL APPROVED'
                  when ob.initialstatus = 'D' then 'INITIAL DISAPPROVED'
                  end as inistatus,
                  case
                  when ob.initialstatus2 = '' and ob.initialapp is null then 'ENTRY'
                  when ob.initialstatus2 = '' and ob.initialapp is not null then 'FOR APPROVAL'              
                  when ob.initialstatus2 = 'A' then 'INITIAL APPROVED'
                  when ob.initialstatus2 = 'D' then 'INITIAL DISAPPROVED'
                  end as inistatus2,
                  date(ob.initialappdate) as iniappdate,date(ob.initialappdate2) as iniappdate2,
                  iniapp.clientname as iniappname,iniapp2.clientname as iniappname2, ob.initial_remarks as inireason,ob.initial_remarks2 as inireason2
                  ";
                break;
            default:
                $addfields = ",
          case when ob.status2 = 'E' and ob.submitdate is null then 'ENTRY'
               when ob.status2 = 'E' and ob.submitdate is not null then 'FOR APPROVAL'
               when ob.status2 = 'A' then 'APPROVED'
               when ob.status2 = 'D' then 'DISAPPROVED' end as status2 ";
                break;
        }
        $query = "
      select
      cl.client, cl.clientname,
      if(ob.type = 'Official Business',concat(time_format(ob.dateid, '%H:%i '),' - ',time_format(ob.dateid2, '%H:%i ')),time_format(ob.dateid, '%H:%i ')) as time,

      time_format(ob.dateid2, '%H:%i ') as timeout
      ,ob.line,$jobtitle dept.clientname as department,date(ob.scheddate) as scheddate,
      ob.type, date(ob.dateid) as dateid, ob.rem as remarks,
      case
      when ob.status = 'A' then 'APPROVED'
      when ob.status = 'E' then 'ENTRY'
      when ob.status = 'D' then 'DISAPPROVED'
      end as status,
      date(ob.createdate) as createdate,
      ifnull(approver.clientname,appdis.clientname) as approvedby,ifnull(approver2.clientname,appdis2.clientname) as approvedby2,
      ob.approverem,date(ob.approvedate) as appdate,date(ob.approvedate2) as appdate2,ob.disapproved_remarks2 as reason2,ob.location
      
      $addfields
      from obapplication as ob
      left join employee as emp on emp.empid = ob.empid
      left join jobthead as jt on jt.line = emp.jobid 
      left join client as cl on cl.clientid = emp.empid
      left join client as dept on dept.clientid = emp.deptid

      left join client as approver on approver.email = ob.approvedby and approver.email <> ''
      left join client as approver2 on approver2.email = ob.approvedby2 and approver2.email <> ''

      left join client as appdis on appdis.email = ob.disapprovedby and appdis.email <> ''
      left join client as appdis2 on appdis2.email = ob.disapprovedby2 and appdis2.email <> ''

      left join client as iniapp on iniapp.email = ob.initialapprovedby and iniapp.email <> ''
      left join client as iniapp2 on iniapp2.email = ob.initialapprovedby2 and iniapp2.email <> ''
      $leftjoin
      where  $status date(ob.dateid) between '" . $start . "' and '" . $end . "' $filter $filteremp ";
        return $query;
    }
    public function ob_detailed($config, $line)
    {
        $qry = "
        select detail.trno,detail.line,detail.purpose,detail.destination,
        date_format(detail.leadfrom,'%H:%i') as leadfrom,date_format(detail.leadto,'%H:%i') as leadto,contact
        from obdetail as detail 
        where detail.trno = ? order by line desc";

        return  $this->coreFunctions->opentable($qry, [$line]);
    }

    public function header_DEFAULT($config, $seqcount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1100';
        if ($seqcount > 1) {
            $layoutsize = '1500';
        }
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
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
        $str .= $this->reporter->col('OB Filling', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
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
        $layoutsize = '1100';

        if ($seqcount > 1) {
            $layoutsize = '1500';
        }
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Create Date', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Applied', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('Status', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Time', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Type', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        if ($seqcount > 1) {
            $str .= $this->reporter->col('First Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Approved By', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->col('Last Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved By', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout_ob($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";
        $stime = '';

        $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename='OB'");
        $url = 'App\Http\Classes\modules\payroll\\' . 'obapplication';
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
            $layoutsize = '1100';
        }
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config, $seqcount);
        $str .= $this->tableheader($layoutsize, $config, $seqcount);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->createdate, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                // $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->time, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->type, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                if ($seqcount > 1) {
                    $str .= $this->reporter->col($data->status2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->appdate2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->approvedby2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->reason2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                }
                $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approverem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function camera_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '2620';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OB Filling', null, null, false, $border, '', '', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range Date : ' . $start . 'to ' . $end, $layoutsize, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Applied Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Schedule Date', '90', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Type', '60', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Location', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Purpose', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('First Initial Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Initail Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('initial Reason', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Last Initial Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Initail Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('initial Reason', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('First Approved Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Last Approved Status', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Approved/Disapproved', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved/ Disapproved By', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved Reason', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function report_camera_Layout_ob($config)
    {


        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "1px solid ";
        $stime = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $layoutsize = 2620;
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10;margin-left:15px;');
        $str .= $this->camera_header($config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->createdate, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->type, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->location, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->remarks, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');


                $str .= $this->reporter->col($data->inistatus2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->iniappdate2, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->iniappname2, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->inireason2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->inistatus, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->iniappdate, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->iniappname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->inireason, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->status2, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate2, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby2, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->reason2, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->appdate, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approvedby, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->approverem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
            }
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function detailed_header_ob($config, $empname, $position, $department, $date, $offdate)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "1px solid ";
        $layoutsize = 1000;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $logo = URL::to('/images/ulitc/united_limsun.png');
        $str .= '<div style="position: relative;">';
        $str .= "<div style='position:absolute; margin:-8px 0 0 -805px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mbc" width="190px" height ="90px">', '250', null, false, '1px solid', '', 'R', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "</div>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HUMAN RESOURCES ADMIN. DEPARTMENT', '650', null, false, $border, 'TL', 'C', $font, 11, 'B', '', '');
        $str .= $this->reporter->col('Copies', '150', null, false, $border, 'TLR', 'LB', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'LR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REQEUST FOR AN OFFICIAL BUSSINESS TRIP', '650', null, false, $border, 'T', 'C', $font, 12, 'B', '', '');
        $str .= $this->reporter->col('HRAD', '150', null, false, $border, 'LR', 'BL', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '650', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Requestor', '150', null, false, $border, 'BLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Name: ', '45', null, false, $border, 'LB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $empname, '355', null, false, $border, 'B', 'B', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Department: ', '75', null, false, $border, 'LB', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $department, '325', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date: ', '45', null, false, $border, 'LB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $date, '155', null, false, $border, 'RB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Position: ', '50', null, false, $border, 'LB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $position, '350', null, false, $border, 'B', 'B', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date of Official Business: ', '140', null, false, $border, 'LB', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $offdate, '460', null, false, $border, 'BR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '', null, false, $border, 'LR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Purpose of Travel', '200', 20, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Destination', '200', 20, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Time', '200', 20, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Contact Person', '200', 20, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Client Signature', '200', 20, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', 20, false, $border, 'BL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', 20, false, $border, 'BL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('From', '100', 20, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('To', '100', 20, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', 20, false, $border, 'BL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', 20, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_detailed_Layout_ob($config)
    {
        $result = $this->reportDefault($config);
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "1px solid ";
        $layoutsize = '1000';
        $stime = '';
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $maxline = 6;
        $count = 0;
        $remarks = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $line = 0;
                $str .= $this->reporter->addline();
                $str .= $this->detailed_header_ob($config, $data->clientname, $data->jobtitle, $data->department, $data->createdate, $data->scheddate);
                $detail = $this->ob_detailed($config, $data->line);
                if (!empty($detail)) {
                    foreach ($detail as $k => $val) {
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col($val->purpose, '200', 30, false, $border, 'LB', '', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($val->destination, '200', 30, false, $border, 'LB', '', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($val->leadfrom, '100', 30, false, $border, 'LB', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($val->leadto, '100', 30, false, $border, 'LB', 'CT', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col($val->contact, '200', 30, false, $border, 'LB', '', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '200', 30, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                        $line++;
                    }
                }


                if ($maxline > $line) {
                    $str .= $this->addline($maxline, $line);
                }
                if (!empty($data->remarks)) {
                    // $remarks = strlen($data->remarks);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Remarks: ', '40', 30, false, $border, 'LB', '', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col($data->remarks, '960', 30, false, $border, 'RB', '', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } else {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Remarks: ', null, 30, false, $border, 'LRB', '', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Guidelines: ', '', 30, false, $border, 'LR', '', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(' 1. Filling of ROBT should be at least one (1) day in advance and must be approved Department Head', '', 30, false, $border, 'LR', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(' 2. Unfiled ROBT will be considered absent and subject to salary deduction', '', 30, false, $border, 'LR', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(' 3. Late filling ROBT will be subject for approval of the COO with the justification from concerned employee noted by the Dept. Head.', '', 30, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '', 10, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Request by: ', '400', 15, false, $border, 'L', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Approved by: ', '300', 15, false, $border, 'LR', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Received by: ', '300', 15, false, $border, 'LR', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->clientname, '400', 20, false, $border, 'LB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data->approvedby, '300', 20, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', 20, false, $border, 'LRB', '', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Employee', '400', 15, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('Department Head', '300', 15, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('HRAD Department', '300', 15, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                if (count($result) != $count + 1) {
                    $str .= $this->reporter->page_break();
                }
                $count++;
            }
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function addline($maxline, $line)
    {
        $str = '';
        // $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1000';
        $maxline = $maxline - $line;
        for ($i = 0; $i < $maxline; $i++) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '200', 30, false, $border, 'LB', '', '', $fontsize, '', '', '');
            $str .= $this->reporter->col('', '200', 30, false, $border, 'LB', '', '', $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', 30, false, $border, 'LB', 'CT', '', $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', 30, false, $border, 'LB', 'CT', '', $fontsize, '', '', '');
            $str .= $this->reporter->col('', '200', 30, false, $border, 'LB', '', '', $fontsize, '', '', '');
            $str .= $this->reporter->col('', '200', 30, false, $border, 'LRB', '', '', $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        return  $str;
    }
}//end class