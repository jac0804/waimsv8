<?php

namespace App\Http\Classes\modules\reportlist\student;

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

class student_received_payment_report
{
    public $modulename = 'Student Received Payment Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'ehstudentlookup', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'ehstudentlookup.label', 'Student');

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $qry = "select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as userid,
                '' as username,
                '' as approved,
                '0' as reporttype,
                '" . $defaultcenter[0]['center'] . "' as center,
                '" . $defaultcenter[0]['centername'] . "' as centername,
                '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                '' as reportusers,
               '' as ehstudentlookup, '0' as clientid";

        return $this->coreFunctions->opentable($qry);
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $companyid = $config['params']['companyid'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': // SUMMARIZED
                $query = $this->default_QUERY_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $query = $this->default_QUERY_DETAILED($config);
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY_DETAILED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $fcenter    = $config['params']['dataparams']['center'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];

        $filter_p = "";
        $filter_u = "";
        $filter = "";
        if ($prefix != "") {
            $filter .= " and cntnum.bref = '$prefix' ";
        }
        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }
        if ($fcenter != "") {
            $filter .= " and cntnum.center = '$fcenter'";
        }

        if ($client != "") {
            $filter_u = " and customer.clientid = '$clientid'";
            $filter_p = " and head.clientid = '$clientid'";
        }


        $query = "select head.docno,head.clientname as hclientname,
                        date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                        detail.checkno,coa.acno,coa.acnoname,
                        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                        dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref
                from lahead as head
                left join ladetail as detail on detail.trno=head.trno 
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                left join client as customer on customer.client = head.client
                where head.doc='cr' and head.dateid between '$start' and '$end' $filter $filter_u
                union all
                select head.docno,head.clientname as hclientname,
                        date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                        detail.checkno,coa.acno,coa.acnoname,
                        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                        dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref
                from glhead as head
                left join gldetail as detail on detail.trno=head.trno 
                left join client as dclient on dclient.clientid=detail.clientid 
                left join coa on coa.acnoid=detail.acnoid
                left join cntnum on cntnum.trno=head.trno
                where head.doc='cr' and head.dateid between '$start' and '$end' $filter $filter_p
                order by docno,cr";

        return $query;
    }

    public function default_QUERY_SUMMARIZED($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $fcenter    = $config['params']['dataparams']['center'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];

        $filter_p = "";
        $filter_u = "";
        $filter = "";
        if ($prefix != "") {
            $filter .= " and cntnum.bref = '$prefix' ";
        }
        if ($filterusername != "") {
            $filter .= " and head.createby = '$filterusername' ";
        }
        if ($fcenter != "") {
            $filter .= " and cntnum.center = '$fcenter'";
        }

        if ($client != "") {
            $filter_u = " and customer.clientid = '$clientid'";
            $filter_p = " and head.clientid = '$clientid'";
        }

        $query = "select docno, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
                        sum(db) as debit, sum(cr) as credit, rem
                from(select head.docno,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                            detail.checkno,coa.acno,coa.acnoname,
                            detail.db,detail.cr,head.rem,detail.ref
                    from lahead as head
                    left join ladetail as detail on detail.trno=head.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join cntnum on cntnum.trno=head.trno
                    left join client as customer on customer.client = head.client
                    where head.doc='cr' and head.dateid between '$start' and '$end' $filter $filter_u
                    union all
                    select head.docno,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                            detail.checkno,coa.acno,coa.acnoname,
                            detail.db,detail.cr,head.rem,detail.ref
                    from glhead as head
                    left join gldetail as detail on detail.trno=head.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc='cr' and head.dateid between '$start' and '$end' $filter $filter_p ) as t 
                    group by docno, dateid, rem order by docno";

        return $query;
    }

    public function default_header_detailed($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];

        $str = '';
        $layoutsize = '1200';
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
        if ($filterusername != "") {
            $user = $filterusername;
        } else {
            $user = "ALL USERS";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT RECEIVED PAYMENT REPORT (Detailed)', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];

        $count = 15;
        $page = 14;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header_detailed($config);


        $str .= $this->reporter->printline();
        $i = 0;
        $docno = "";
        $supplier = "";
        $debit = 0;
        $credit = 0;
        $totaldb = 0;
        $totalcr = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Total:', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($debit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($credit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $debit = 0;
                    $credit = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '600', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<b>' . 'Student: ' . '</b>' . $data->hclientname, '600', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Account', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Title', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Student', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Debit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Credit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Notes', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Reference', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->acnoname, '200', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dclient, '120', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->db, 2), '150', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->cr, 2), '150', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem, '150', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ref, '130', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $debit += $data->db;
                    $credit += $data->cr;
                    $totaldb += $data->db;
                    $totalcr += $data->cr;
                }
                $str .= $this->reporter->endtable();

                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('Total: ', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($debit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($credit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->default_header_detailed($config);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Grand Total: ', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($totalcr, 2), '150', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];

        $count = 46;
        $page = 45;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
        $str .= $this->summarized_header_table($config, $layoutsize);

        $i = 0;
        $docno = "";
        $supplier = "";
        $debit = 0;
        $credit = 0;
        $totaldb = 0;
        $totalcr = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $totaldb += $data->debit;
                $totalcr += $data->credit;
                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $checkno = str_replace(',', '<br>', $data->checkno);
                $str .= $this->reporter->col('', '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($checkno, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->debit, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->credit, 2) . '&nbsp&nbsp', '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
                    $str .= $this->summarized_header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", '300', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('Grand Total:', '200', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($totaldb, 2), '160', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($totalcr, 2), '140', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '280', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function summarized_header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];
        $prefix     = $config['params']['dataparams']['approved'];
        $clientid       = $config['params']['dataparams']['clientid'];
        $client       = $config['params']['dataparams']['ehstudentlookup'];

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
        if ($filterusername != "") {
            $user = $filterusername;
        } else {
            $user = "ALL USERS";
        }

        if ($client == "") {
            $client = "ALL";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STUDENT RECEIVED PAYMENT REPORT (Summarized)', 800, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Student: ' . $client, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function summarized_header_table($config, $layoutsize)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";
        $str = "";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check#', '160', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit' . '&nbsp&nbsp', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes', '300', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class