<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class dailytask_report
{
    public $modulename = 'Daily Task Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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
        $fields = ['radioprint', 'start', 'end', 'client', 'username', 'dystatus', 'radioreporttype', 'radioreportcustomerfilter'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'client.label', 'Customer');
        data_set($col1, 'client.lookupclass', 'customer');
        data_set($col1, 'client.required', false);
        data_set($col1, 'username.lookupclass', 'lookupusers');
        data_set($col1, 'username.label', 'User');
        data_set($col1, 'dystatus.lookupclass', 'lookupstatus');

        data_set($col1, 'radioreportcustomerfilter.label', 'Group By');
        data_set($col1, 'radioreportcustomerfilter.options', [
            ['label' => 'Group By User', 'value' => 'user', 'color' => 'red'],
            ['label' => 'Group By Customer', 'value' => 'customer', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.label', 'Format');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Default', 'value' => 'standard', 'color' => 'red'],
            ['label' => 'With History', 'value' => 'history', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        '" . $this->othersClass->getCurrentDate() . "' as start,
         '" . $this->othersClass->getCurrentDate() . "' as end, 
        '' as dclientname,
        '' as userid,
        '' as clientid,
        '' as client,
        '' as clientname,
        '' as username,
        '' as statid,
        '' as dystatus,
        'draft' as status,
        '' as center,
        '' as dcentername,
        '' as centername,
        '' as prefix,
        'user' as customerfilter,
        'standard' as reporttype
      ";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {

        $reporttype = $config['params']['dataparams']['reporttype'];
        $customerfilter = $config['params']['dataparams']['customerfilter'];
        switch ($reporttype) {

            case 'standard':
                switch ($customerfilter) {
                    case 'user':
                        return $this->standard_user_layout($config);
                        break;

                    case 'customer':
                        return $this->standard_customer_layout($config);
                        break;
                }
                break;

            case 'history':
                switch ($customerfilter) {
                    case 'user':
                        return $this->history_user_layout($config);
                        break;

                    case 'customer':
                        return $this->history_customer_layout($config);
                        break;
                }
                break;
        }
    }

    public function custom_user_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $customer = $config['params']['dataparams']['userid'];
        $userid = $config['params']['dataparams']['clientid'];
        $filter = '';
        $filter2 = '';
        $option = $config['params']['dataparams']['dystatus'];
        $orderby1 = $config['params']['dataparams']['customerfilter'];

        if ($option == 'On-Going') {
            $filter .= " and dt.statid = 0";
        } elseif ($option == 'Undone') {
            $filter .= " and dt.statid = 2";
        } elseif ($option == 'Completed') {
            $filter .= " and dt.statid = 1";
        } elseif ($option == 'Return') {
            $filter .= " and dt.statid = 6";
        } elseif ($option == 'Cancelled') {
            $filter .= " and dt.statid = 5";
        } elseif ($option == 'Neglect') {
            $filter .= " and dt.statid = 4";
        }

        if ($userid != "") {
            $filter .= " and dt.clientid = $userid";
        }
        if ($customer != "") {
            $filter .= " and dt.userid = $customer";
        }

        if ($orderby1 == "user") {
            $filter2 .= " order by userr, createdate";
        } else {
            $filter2 .= " order by customer, createdate";
        }


        $query = "
        select c.clientname as customer, dt.createdate, userr.clientname as userr, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, dt.isprev, dt.ischecker,dt.statid,dt.rem1, dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.reseller,dt.taskline, case when dt.tasktrno <> '0' then 'TM' else 'DY' end as doctype
        from dailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid=dt.userid
        where  date(dt.createdate) between '$start' and '$end' $filter
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker,dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.reseller,dt.taskline

        union all

        select c.clientname as userr, dt.createdate, userr.clientname as customer, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, isprev, ischecker, dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.reseller,dt.taskline,case when dt.tasktrno <> '0' then 'TM' else 'DY' end as doctype
        from hdailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid=dt.userid
        where  date(dt.createdate) between '$start' and '$end' $filter
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker,dt.statid,dt.rem1,dt.trno,dt.refx,dt.origtrno,dt.tasktrno, dt.reseller,dt.taskline
        $filter2";
        // var_dump($query);
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }


    public function history_detail_query($config, $refx, $trno, $isChecker = 1, $statid = null, $isprev = 0)
    {

        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        if ($refx == 0) {
            if ($isChecker == 1) {
                $whereClause = "(dt.trno = $trno OR dt.refx = $trno)";
            } else {
                $whereClause = "dt.trno = $trno";
            }
        } else {
            if ($statid == 2 || ($statid == 0 && $isprev == 1)) {
                $whereClause = "((dt.origtrno = $refx AND dt.trno <= $trno) OR dt.trno = $refx OR (dt.refx = $refx AND dt.trno <= $trno))";
            } elseif ($statid == 1) {
                $whereClause = "((dt.origtrno = $refx AND dt.trno <= $trno) OR dt.trno = $refx OR (dt.refx = $refx AND dt.trno <= $trno))";
            } else {
                $whereClause = "((dt.refx = $refx AND dt.trno <= $trno) OR dt.trno = $refx)";
            }
        }

        $query = "
        select c.clientname as customer, dt.createdate, userr.clientname as userr, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.refx, dt.origtrno, emp.empfirst
        from dailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid = dt.userid
        left join employee as emp on emp.empid = dt.userid
        where  date(dt.createdate) <= '$end'and  $whereClause
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.refx, dt.origtrno, emp.empfirst

        union all

        select c.clientname as userr, dt.createdate, userr.clientname as customer, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, isprev, ischecker, dt.statid, dt.rem1, dt.trno, dt.refx, dt.origtrno, emp.empfirst
        from hdailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid = dt.userid
        left join employee as emp on emp.empid = dt.userid
        where  date(dt.createdate) <= '$end'and $whereClause
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.refx, dt.origtrno, emp.empfirst
        order by createdate
        ";

        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }



    public function history_detail_task_query($config, $tasktrno, $taskline, $trno, $statid = null, $isprev = 0)
    {
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        if ($statid == 1 || $statid == 2 || ($statid == 0 && $isprev == 1)) {
            $whereClause = "(dt.origtrno = $tasktrno AND dt.taskline = $taskline AND dt.trno <= $trno)
                    OR (dt.tasktrno = $tasktrno AND dt.taskline = $taskline AND dt.trno <= $trno)
                    OR (dt.trno = $tasktrno AND dt.taskline = $taskline)";
        } else {
            $whereClause = "(dt.tasktrno = $tasktrno AND dt.taskline = $taskline AND dt.trno <= $trno)
                    OR (dt.trno = $tasktrno AND dt.taskline = $taskline)";
        }

        $query = "
        select c.clientname as customer, dt.createdate, userr.clientname as userr, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.tasktrno, dt.origtrno, dt.taskline, emp.empfirst
        from dailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid = dt.userid
        left join employee as emp on emp.empid = dt.userid
        where date(dt.createdate) <= '$end'
        and ($whereClause)
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.tasktrno, dt.origtrno, dt.taskline, emp.empfirst

        union all

        select c.clientname as userr, dt.createdate, userr.clientname as customer, dt.rem, dt.donedate,
        round(timestampdiff(second, dt.createdate, dt.donedate) / 3600, 2) as hours, count(userr.clientname) as totalcl, isprev, ischecker, dt.statid, dt.rem1, dt.trno, dt.tasktrno, dt.origtrno, dt.taskline, emp.empfirst
        from hdailytask as dt
        left join client as c on c.clientid = dt.clientid
        left join client as userr on userr.clientid = dt.userid
        left join employee as emp on emp.empid = dt.userid
        where date(dt.createdate) <= '$end'
        and ($whereClause)
        group by c.clientname, dt.createdate, userr.clientname, dt.rem, dt.donedate, dt.isprev, dt.ischecker, dt.statid, dt.rem1, dt.trno, dt.tasktrno, dt.origtrno, dt.taskline, emp.empfirst
        order by createdate
        ";

        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }

    public function history_comment_query($config, $refx, $trno, $tasktrno = 0, $taskline = 0, $statid = null, $isprev = 0, $doctype = 'DY')
    {
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $limitQuery = "select createdate from dailytask where trno = $trno 
               union 
               select createdate from hdailytask where trno = $trno 
               limit 1";
        $limitResult = json_decode(json_encode($this->coreFunctions->opentable($limitQuery)), true);
        $limitDate = !empty($limitResult) ? $limitResult[0]['createdate'] : $end . ' 23:59:59';

        // ---- query 1: dytrno based (regular task comments) - skip for TM ----
        $data1 = array();
        if ($doctype != 'TM') {
            if ($refx == 0) {
                $whereClause = "hp.dytrno = $trno";
            } else {
                if ($statid == 1 || $statid == 2 || ($statid == 0 && $isprev == 1)) {
                    $whereClause = "hp.dytrno in (
                    select dt.trno from dailytask dt
                    where (dt.origtrno = $refx and dt.trno <= $trno) or dt.trno = $refx
                    or (dt.refx = $refx and dt.trno <= $trno)

                    union

                    select dt.trno from hdailytask dt
                    where (dt.origtrno = $refx and dt.trno <= $trno) or dt.trno = $refx
                    or (dt.refx = $refx and dt.trno <= $trno)
                )";
                } else {
                    $whereClause = "hp.dytrno in (
                    select dt.trno from dailytask dt
                    where (dt.refx = $refx and dt.trno <= $trno) or dt.trno = $refx

                    union

                    select dt.trno from hdailytask dt
                    where (dt.refx = $refx and dt.trno <= $trno) or dt.trno = $refx
                )";
                }
            }

            $query1 = "
            select hp.rem as hprem, hp.dytrno, hp.createdate, hp.createby, cl.clientname as createbyname, emp.empfirst,hp.deadline2 
            from headprrem as hp
            left join client as cl on cl.email = hp.createby
            left join employee as emp on emp.empid = cl.clientid
            where $whereClause
            and date(hp.createdate) <= '$end'
            and hp.createdate <= '$limitDate'
            order by hp.createdate asc
            ";

            $data1 = json_decode(json_encode($this->coreFunctions->opentable($query1)), true);
        }

        // ---- query 2: tmtrno/tmline based (task line comments) ----
        $data2 = array();
        if ($tasktrno > 0) {
            if ($statid == 1 || $statid == 2 || ($statid == 0 && $isprev == 1)) {
                $taskWhereClause = "hp.tmtrno in (
            select dt.tasktrno from dailytask dt
            where (dt.origtrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.tasktrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.trno = $tasktrno and dt.taskline = $taskline)

            union

            select dt.tasktrno from hdailytask dt
            where (dt.origtrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.tasktrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.trno = $tasktrno and dt.taskline = $taskline)
             ) and hp.tmline = $taskline";
            } else {
                $taskWhereClause = "hp.tmtrno in (
            select dt.tasktrno from dailytask dt
            where (dt.tasktrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.trno = $tasktrno and dt.taskline = $taskline)

            union

            select dt.tasktrno from hdailytask dt
            where (dt.tasktrno = $tasktrno and dt.taskline = $taskline and dt.trno <= $trno)
            or (dt.trno = $tasktrno and dt.taskline = $taskline)
             ) and hp.tmline = $taskline";
            }

            $query2 = "
            select hp.rem as hprem, hp.tmtrno as dytrno, hp.createdate, hp.createby, cl.clientname as createbyname, emp.empfirst, hp.deadline2
            from headprrem as hp
            left join client as cl on cl.email = hp.createby
            left join employee as emp on emp.empid = cl.clientid
            where $taskWhereClause
            and date(hp.createdate) <= '$end'
            and hp.createdate <= '$limitDate'
            order by hp.createdate asc
            ";

            $data2 = json_decode(json_encode($this->coreFunctions->opentable($query2)), true);
        }

        $merged = $data1;
        foreach ($data2 as $row) {
            $found = false;
            foreach ($merged as $existing) {
                if (
                    $existing['createdate'] == $row['createdate']
                    && $existing['hprem'] == $row['hprem']
                    && $existing['createby'] == $row['createby']
                ) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $merged[] = $row;
            }
        }

        usort($merged, function ($a, $b) {
            return strtotime($a['createdate']) - strtotime($b['createdate']);
        });

        return $merged;
    }
    public function custom_user_header($config)
    {

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $user = $config['params']['dataparams']['username'];
        $userid = $config['params']['dataparams']['userid'];
        $customfilter = $config['params']['dataparams']['customerfilter'];
        $reportfilter = $config['params']['dataparams']['reporttype'];
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $font = 'Tahoma';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY TASK REPORT', '1206', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1000', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        if ($reportfilter == 'standard') {
            $reportfilter = '';
        } else {
            $reportfilter = '(HISTORY)';
        }
        $str .= $this->reporter->col('FORMAT: GROUP BY ' . strtoupper($customfilter) . ' '  . strtoupper($reportfilter), '960', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No.', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('START', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('END', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF HRS DIFF.', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function custom_user_hprem_query($config, $tasktrno, $taskline, $trno, $donedate, $doctype)
    {
        if ($doctype == 'TM') {
            $query = "
        select hp.rem as hprem
        from headprrem hp
        where hp.tmtrno = $tasktrno
        and hp.tmline = $taskline
        and date(hp.createdate) <= date('$donedate')
        order by hp.createdate desc
        limit 1
        ";
        } else {
            $query = "
        select hp.rem as hprem
        from headprrem hp
        where hp.dytrno = $trno
        order by hp.createdate desc
        limit 1
        ";
        }

        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return !empty($data) ? $data[0]['hprem'] : '';
    }

    public function history_user_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->custom_user_header($config);
        $data = $this->custom_user_query($config);

        $subtotal = 0;
        $grandtotal = 0;
        $currentUser = '';

        for ($i = 0; $i < count($data); $i++) {


            if ($data[$i]['userr'] != $currentUser) {
                $currentUser = $data[$i]['userr'];
                $rowNum = 0;

                if ($i > 0) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
                $str .= $this->reporter->col('USER: ' . strtoupper($currentUser), '990', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $rowNum++;

            $statid = $data[$i]['statid'];
            if ($statid == 0) {
                $statLabel = 'On-going ';
            } elseif ($statid == 2) {
                $statLabel = 'Undone ';
            } elseif ($statid == 1) {
                $statLabel = 'Completed ';
            } elseif ($statid == 6) {
                $statLabel = 'Return ';
            } elseif ($statid == 5) {
                $statLabel = 'Cancelled ';
            } elseif ($statid == 4) {
                $statLabel = 'Neglect ';
            } else {
                $statLabel = '';
            }

            if ($data[$i]['ischecker'] == 1) {
                $statLabel .= ' (Checker)';
            }
            if ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 0) {
                $statLabel = 'Checking';
            }

            $displayLabel = $data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

            $hours = ($data[$i]['donedate'] == null)
                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($data[$i]['createdate'])) / 3600, 2)
                : $data[$i]['hours'];

            // for reseller
            $customer = $data[$i]['customer'];
            $reseller = $data[$i]['reseller'];
            $display = $customer;
            if (!empty($reseller)) {
                $display = $customer . '<br/>' .  '/' . $reseller;
            }


            if ($data[$i]['statid'] == 2) {
                $hprem = '';
                if (!empty($data[$i]['donedate'])) {
                    $hprem = $this->custom_user_hprem_query(
                        $config,
                        $data[$i]['tasktrno'],
                        $data[$i]['taskline'],
                        $data[$i]['trno'],
                        $data[$i]['donedate'],
                        $data[$i]['doctype']
                    );
                }
                $remDisplay = !empty($hprem)
                    ? $data[$i]['rem'] . '<br/><span style="font-style:italic;">Solution Remarks: ' . $hprem . '</span>'
                    : $data[$i]['rem'];
            } elseif ($data[$i]['statid'] == 1) {
                $remDisplay = $data[$i]['rem'];
                if (!empty($data[$i]['rem1'])) {
                    $remDisplay .= '<br/><span style="font-style:italic;">Solution Remarks: ' . $data[$i]['rem1'] . '</span>';
                }
            } else {
                $remDisplay = $data[$i]['rem'];
            }


            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
            $str .= $this->reporter->col($rowNum . '.', '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['doctype'], '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['createdate'])), '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($displayLabel, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($display, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($remDisplay, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['createdate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['donedate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($hours . ' hrs', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // start of history
            $refxToUse = ($data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0)
                ? ($data[$i]['origtrno'] > 0 ? $data[$i]['origtrno'] : $data[$i]['refx'])
                : ($data[$i]['refx'] == 0 && $data[$i]['origtrno'] > 0
                    ? $data[$i]['origtrno']
                    : $data[$i]['refx']);

            $data2 = $this->history_detail_query($config, $refxToUse, $data[$i]['trno'], $data[$i]['ischecker'], $data[$i]['statid'], $data[$i]['isprev']);
            $data3 = ($data[$i]['tasktrno'] > 0)
                ? $this->history_detail_task_query($config, $data[$i]['tasktrno'], $data[$i]['taskline'], $data[$i]['trno'], $data[$i]['statid'], $data[$i]['isprev'])
                : array();


            $mergedData = $data2;
            foreach ($data3 as $row) {
                $found = false;
                foreach ($mergedData as $existing) {
                    if ($existing['trno'] == $row['trno']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $mergedData[] = $row;
                }
            }


            usort($mergedData, function ($a, $b) {
                return strtotime($a['createdate']) - strtotime($b['createdate']);
            });

            if (
                $data[$i]['statid'] == 1
                || ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 6)
                || ($data[$i]['statid'] == 2)
                || ($data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0)
                || ($data[$i]['doctype'] == 'TM' && $data[$i]['statid'] == 0)
            ) {


                $filteredData2 = array();
                foreach ($mergedData as $row) {
                    if ($row['trno'] != $data[$i]['trno']) {
                        $filteredData2[] = $row;
                    }
                }


                $dataComments = $this->history_comment_query(
                    $config,
                    $refxToUse,
                    $data[$i]['trno'],
                    $data[$i]['tasktrno'],
                    $data[$i]['taskline'],
                    $data[$i]['statid'],
                    $data[$i]['isprev'],
                    $data[$i]['doctype']
                );


                foreach ($filteredData2 as &$row) {
                    $row['rowtype'] = 'history';
                }
                unset($row);


                foreach ($dataComments as &$comment) {
                    $comment['rowtype'] = 'comment';
                }
                unset($comment);



                foreach ($dataComments as $comment) {
                    $comment['userr'] = $data[$i]['userr'];
                    $filteredData2[] = $comment;
                }


                usort($filteredData2, function ($a, $b) {
                    return strtotime($a['createdate']) - strtotime($b['createdate']);
                });

                // show header kapag may history/comment lang
                if (count($filteredData2) > 0) {

                    // history/comment header
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, true, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('No.', '40', null, true, '1px dotted', 'TLB', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Date', '90', null, true, '1px dotted', 'LTB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Status', '90', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('User', '130', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Solution Remarks', '200', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Create Date', '140', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Done Date', '140', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Hours', '80', null, true, '1px dotted', 'TRB', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('', '40', null, true, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $rowCounter = 0;
                    for ($j = 0; $j < count($filteredData2); $j++) {
                        $isLast = ($j == count($filteredData2) - 1);
                        $bottomBorder = $isLast ? 'B' : '';
                        $rowCounter++;

                        if ($filteredData2[$j]['rowtype'] == 'comment') {
                            $rowColor = '#757575';



                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '50', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($rowCounter, '40', null, false, '1px dotted', $bottomBorder . 'L', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col(date('m/d/Y', strtotime($filteredData2[$j]['createdate'])), '90', null, false, '1px dotted', $bottomBorder . 'L', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('Comment', '90', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['empfirst'], '130', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);

                            $str .= $this->reporter->col($filteredData2[$j]['hprem'], '200', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['deadline2'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['createdate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '80', null, false, '1px dotted', $bottomBorder . 'R', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '40', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                        } else {
                            $rowColor = '#757575';


                            $statid = $filteredData2[$j]['statid'];
                            if ($statid == 0) {
                                $statLabel = 'On-going ';
                            } elseif ($statid == 2) {
                                $statLabel = 'Undone ';
                            } elseif ($statid == 1) {
                                $statLabel = 'Completed ';
                            } elseif ($statid == 6) {
                                $statLabel = 'Return ';
                            } elseif ($statid == 5) {
                                $statLabel = 'Cancelled ';
                            } elseif ($statid == 4) {
                                $statLabel = 'Neglect ';
                            } else {
                                $statLabel = '';
                            }

                            if ($filteredData2[$j]['ischecker'] == 1) {
                                $statLabel .= ' (Checker)';
                            }
                            if ($filteredData2[$j]['ischecker'] == 1 && $filteredData2[$j]['statid'] == 0) {
                                $statLabel = 'Checking';
                            }

                            $displayLabel = $filteredData2[$j]['isprev'] == 1 && $filteredData2[$j]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

                            $hours = ($filteredData2[$j]['donedate'] == null)
                                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($filteredData2[$j]['createdate'])) / 3600, 2)
                                : $filteredData2[$j]['hours'];



                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '50', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($rowCounter, '40', null, false, '1px dotted', $bottomBorder . 'L', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col(date('m/d/Y', strtotime($filteredData2[$j]['createdate'])), '90', null, false, '1px dotted', $bottomBorder . 'L', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($displayLabel, '90', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['empfirst'], '130', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['rem1'], '200', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['createdate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['donedate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($hours . ' hrs', '80', null, false, '1px dotted', $bottomBorder . 'R', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '40', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                        }
                    }

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            }
            // end of history
        } // end of for loop

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '990', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // summary header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('USER', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('No Of Hours', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // group summary by user
        $summaryGroup = array();
        foreach ($data as $row) {
            $key = $row['userr'];
            if (!isset($summaryGroup[$key])) {
                $summaryGroup[$key] = array(
                    'userr' => $row['userr'],
                    'hours' => 0,
                );
            }
            $summaryGroup[$key]['hours'] += $row['hours'];
        }

        $grandtotal = 0;
        foreach ($summaryGroup as $summary) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($summary['userr'], '400', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($summary['hours'], 2) . ' hrs', '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandtotal += $summary['hours'];
        }

        // grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('TOTAL: ' . count($summaryGroup), '400', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2) . ' hrs', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function custom_customer_header($config)
    {

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $user = $config['params']['dataparams']['username'];
        $userid = $config['params']['dataparams']['userid'];
        $customfilter = $config['params']['dataparams']['customerfilter'];
        $reportfilter = $config['params']['dataparams']['reporttype'];
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $font = 'Tahoma';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY TASK REPORT', '1206', null, false, $border, '', 'C', $font, '12', 'B', 'Blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), '1000', null, false, $border, '', 'C', $font, $fontsize, 'I', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FORMAT: GROUP BY ' . strtoupper($customfilter) . ' ' . '(' . strtoupper($reportfilter) . ')', '960', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('No.', '40', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('USER', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '180', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('START', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('END', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NO OF HRS DIFF.', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function history_customer_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->custom_customer_header($config);
        $data = $this->custom_user_query($config);


        $subtotal = 0;
        $grandtotal = 0;

        $currentUser = '';

        for ($i = 0; $i < count($data); $i++) {

            //for reseller 
            $customer = $data[$i]['customer'];
            $reseller = $data[$i]['reseller'];

            $display = $customer;

            if (!empty($reseller)) {
                $display = $customer . ' / Reseller: ' . $reseller;
            }

            // print user header if user changes
            if ($display != $currentUser) {
                $currentUser = $display;
                $rowNum = 0;

                if ($i > 0) { // disable spacing on first user
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
                $str .= $this->reporter->col('CUSTOMER: ' . strtoupper($currentUser), '990', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $rowNum++;

            $statid = $data[$i]['statid'];
            if ($statid == 0) {
                $statLabel = 'On-going ';
            } elseif ($statid == 2) {
                $statLabel = 'Undone ';
            } elseif ($statid == 1) {
                $statLabel = 'Completed ';
            } elseif ($statid == 6) {
                $statLabel = 'Return ';
            } elseif ($statid == 5) {
                $statLabel = 'Cancelled ';
            } elseif ($statid == 4) {
                $statLabel = 'Neglect ';
            } else {
                $statLabel = '';
            }

            if ($data[$i]['ischecker'] == 1) {
                $statLabel .= ' (Checker)';
            }
            if ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 0) {
                $statLabel = 'Checking';
            }

            $displayLabel = $data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

            // $displayLabel = $data[$i]['isprev'] == 1 ? 'Continuation' : $statLabel;


            //if donedate is empty 
            $hours = ($data[$i]['donedate'] == null)
                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($data[$i]['createdate'])) / 3600, 2)
                : $data[$i]['hours'];


            if ($data[$i]['statid'] == 2) {
                $hprem = '';
                if (!empty($data[$i]['donedate'])) {
                    $hprem = $this->custom_user_hprem_query(
                        $config,
                        $data[$i]['tasktrno'],
                        $data[$i]['taskline'],
                        $data[$i]['trno'],
                        $data[$i]['donedate'],
                        $data[$i]['doctype']
                    );
                }
                $remDisplay = !empty($hprem)
                    ? $data[$i]['rem'] . '<br/><span style="font-style:italic;">Solution Remarks: ' . $hprem . '</span>'
                    : $data[$i]['rem'];
            } elseif ($data[$i]['statid'] == 1) {
                $remDisplay = $data[$i]['rem'];
                if (!empty($data[$i]['rem1'])) {
                    $remDisplay .= '<br/><span style="font-style:italic;">Solution Remarks: ' . $data[$i]['rem1'] . '</span>';
                }
            } else {
                $remDisplay = $data[$i]['rem'];
            }

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
            $str .= $this->reporter->col($rowNum . '.', '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['doctype'], '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['createdate'])), '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($displayLabel, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['userr'], '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($remDisplay, '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['createdate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['donedate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(number_format($hours, 2) . ' hrs', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // start of history
            $refxToUse = ($data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0)
                ? ($data[$i]['origtrno'] > 0 ? $data[$i]['origtrno'] : $data[$i]['refx'])
                : ($data[$i]['refx'] == 0 && $data[$i]['origtrno'] > 0
                    ? $data[$i]['origtrno']
                    : $data[$i]['refx']);

            $data2 = $this->history_detail_query($config, $refxToUse, $data[$i]['trno'], $data[$i]['ischecker'], $data[$i]['statid'], $data[$i]['isprev']);
            $data3 = ($data[$i]['tasktrno'] > 0)
                ? $this->history_detail_task_query($config, $data[$i]['tasktrno'], $data[$i]['taskline'], $data[$i]['trno'], $data[$i]['statid'], $data[$i]['isprev'])
                : array();


            $mergedData = $data2;
            foreach ($data3 as $row) {
                $found = false;
                foreach ($mergedData as $existing) {
                    if ($existing['trno'] == $row['trno']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $mergedData[] = $row;
                }
            }


            usort($mergedData, function ($a, $b) {
                return strtotime($a['createdate']) - strtotime($b['createdate']);
            });

            if (
                $data[$i]['statid'] == 1
                || ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 6)
                || ($data[$i]['statid'] == 2)
                || ($data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0)
                || ($data[$i]['doctype'] == 'TM' && $data[$i]['statid'] == 0)
            ) {


                $filteredData2 = array();
                foreach ($mergedData as $row) {
                    if ($row['trno'] != $data[$i]['trno']) {
                        $filteredData2[] = $row;
                    }
                }


                $dataComments = $this->history_comment_query(
                    $config,
                    $refxToUse,
                    $data[$i]['trno'],
                    $data[$i]['tasktrno'],
                    $data[$i]['taskline'],
                    $data[$i]['statid'],
                    $data[$i]['isprev'],
                    $data[$i]['doctype']
                );


                foreach ($filteredData2 as &$row) {
                    $row['rowtype'] = 'history';
                }
                unset($row);


                foreach ($dataComments as &$comment) {
                    $comment['rowtype'] = 'comment';
                }
                unset($comment);



                foreach ($dataComments as $comment) {
                    $comment['userr'] = $data[$i]['userr'];
                    $filteredData2[] = $comment;
                }


                usort($filteredData2, function ($a, $b) {
                    return strtotime($a['createdate']) - strtotime($b['createdate']);
                });

                // show header kapag may history/comment lang
                if (count($filteredData2) > 0) {

                    // history/comment header
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '50', null, true, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('No.', '40', null, true, '1px dotted', 'TLB', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Date', '90', null, true, '1px dotted', 'LTB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Status', '90', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('User', '130', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Solution Remarks', '200', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Create Date', '140', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Done Date', '140', null, true, '1px dotted', 'TB', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('Hours', '80', null, true, '1px dotted', 'TRB', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->col('', '40', null, true, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $rowCounter = 0;
                    for ($j = 0; $j < count($filteredData2); $j++) {
                        $isLast = ($j == count($filteredData2) - 1);
                        $bottomBorder = $isLast ? 'B' : '';
                        $rowCounter++;

                        if ($filteredData2[$j]['rowtype'] == 'comment') {
                            $rowColor = '#757575';



                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '50', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($rowCounter, '40', null, false, '1px dotted', $bottomBorder . 'L', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col(date('m/d/Y', strtotime($filteredData2[$j]['createdate'])), '90', null, false, '1px dotted', $bottomBorder . 'L', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('Comment', '90', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['empfirst'], '130', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);

                            $str .= $this->reporter->col($filteredData2[$j]['hprem'], '200', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['deadline2'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['createdate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '80', null, false, '1px dotted', $bottomBorder . 'R', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '40', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                        } else {
                            $rowColor = '#757575';


                            $statid = $filteredData2[$j]['statid'];
                            if ($statid == 0) {
                                $statLabel = 'On-going ';
                            } elseif ($statid == 2) {
                                $statLabel = 'Undone ';
                            } elseif ($statid == 1) {
                                $statLabel = 'Completed ';
                            } elseif ($statid == 6) {
                                $statLabel = 'Return ';
                            } elseif ($statid == 5) {
                                $statLabel = 'Cancelled ';
                            } elseif ($statid == 4) {
                                $statLabel = 'Neglect ';
                            } else {
                                $statLabel = '';
                            }

                            if ($filteredData2[$j]['ischecker'] == 1) {
                                $statLabel .= ' (Checker)';
                            }
                            if ($filteredData2[$j]['ischecker'] == 1 && $filteredData2[$j]['statid'] == 0) {
                                $statLabel = 'Checking';
                            }

                            $displayLabel = $filteredData2[$j]['isprev'] == 1 && $filteredData2[$j]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

                            $hours = ($filteredData2[$j]['donedate'] == null)
                                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($filteredData2[$j]['createdate'])) / 3600, 2)
                                : $filteredData2[$j]['hours'];



                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '50', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($rowCounter, '40', null, false, '1px dotted', $bottomBorder . 'L', 'CT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col(date('m/d/Y', strtotime($filteredData2[$j]['createdate'])), '90', null, false, '1px dotted', $bottomBorder . 'L', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($displayLabel, '90', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['empfirst'], '130', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['rem1'], '200', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['createdate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($filteredData2[$j]['donedate'], '140', null, false, '1px dotted', $bottomBorder, 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col($hours . ' hrs', '80', null, false, '1px dotted', $bottomBorder . 'R', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->col('', '40', null, false, '', '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, $rowColor);
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                        }
                    }

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            }
            // end of history
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '990', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // summary header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('CUSTOMER', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('No Of Hours', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // group summary by customer
        $summaryGroup = [];
        foreach ($data as $row) {
            $key = $row['customer'];
            if (!isset($summaryGroup[$key])) {
                $summaryGroup[$key] = [
                    'customer' => $row['customer'],
                    'hours'    => 0,
                ];
            }
            $summaryGroup[$key]['hours'] += $row['hours'];
        }

        $grandtotal = 0;
        foreach ($summaryGroup as $summary) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($summary['customer'], '400', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($summary['hours'], 2) . ' hrs', '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandtotal += $summary['hours'];
        }

        // grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('TOTAL: ' . count($summaryGroup), '400', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2) . ' hrs', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }


    public function standard_user_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->custom_user_header($config);
        $data = $this->custom_user_query($config);

        $subtotal = 0;
        $grandtotal = 0;
        $currentUser = '';
        $rowNum = 0;

        for ($i = 0; $i < count($data); $i++) {


            if ($data[$i]['userr'] != $currentUser) {
                $currentUser = $data[$i]['userr'];
                $rowNum = 0;

                if ($i > 0) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
                $str .= $this->reporter->col('USER: ' . strtoupper($currentUser), '990', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $rowNum++;

            $statid = $data[$i]['statid'];
            if ($statid == 0) {
                $statLabel = 'On-going ';
            } elseif ($statid == 2) {
                $statLabel = 'Undone ';
            } elseif ($statid == 1) {
                $statLabel = 'Completed ';
            } elseif ($statid == 6) {
                $statLabel = 'Return ';
            } elseif ($statid == 5) {
                $statLabel = 'Cancelled ';
            } elseif ($statid == 4) {
                $statLabel = 'Neglect ';
            } else {
                $statLabel = '';
            }

            if ($data[$i]['ischecker'] == 1) {
                $statLabel .= ' (Checker)';
            }
            if ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 0) {
                $statLabel = 'Checking';
            }

            $displayLabel = $data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

            $hours = ($data[$i]['donedate'] == null)
                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($data[$i]['createdate'])) / 3600, 2)
                : $data[$i]['hours'];

            $customer = $data[$i]['customer'];
            $reseller = $data[$i]['reseller'];
            $display = $customer;
            if (!empty($reseller)) {
                $display = $customer . '/' . $reseller;
            }

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
            $str .= $this->reporter->col($rowNum . '.', '40', null, false, '1px dotted', '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['createdate'])), '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($displayLabel, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($display, '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['rem'], '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['createdate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['donedate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($hours . ' hrs', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '990', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // summary header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('USER', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('No Of Hours', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // group summary by user
        $summaryGroup = array();
        foreach ($data as $row) {
            $key = $row['userr'];
            if (!isset($summaryGroup[$key])) {
                $summaryGroup[$key] = array(
                    'userr' => $row['userr'],
                    'hours' => 0,
                );
            }
            $summaryGroup[$key]['hours'] += $row['hours'];
        }

        $grandtotal = 0;
        foreach ($summaryGroup as $summary) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($summary['userr'], '400', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($summary['hours'], 2) . ' hrs', '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandtotal += $summary['hours'];
        }

        // grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('TOTAL: ' . count($summaryGroup), '400', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2) . ' hrs', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function standard_customer_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->custom_customer_header($config);
        $data = $this->custom_user_query($config);

        $subtotal = 0;
        $grandtotal = 0;
        $currentUser = '';
        $rowNum = 0; // numbering counter

        for ($i = 0; $i < count($data); $i++) {

            $customer = $data[$i]['customer'];
            $reseller = $data[$i]['reseller'];
            $display = $customer;
            if (!empty($reseller)) {
                $display = $customer . '/' . $reseller;
            }


            if ($display != $currentUser) {
                $currentUser = $display;
                $rowNum = 0;

                if ($i > 0) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '', '25', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
                $str .= $this->reporter->col('CUSTOMER: ' . strtoupper($currentUser), '990', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $rowNum++;

            $statid = $data[$i]['statid'];
            if ($statid == 0) {
                $statLabel = 'On-going ';
            } elseif ($statid == 2) {
                $statLabel = 'Undone ';
            } elseif ($statid == 1) {
                $statLabel = 'Completed ';
            } elseif ($statid == 6) {
                $statLabel = 'Return ';
            } elseif ($statid == 5) {
                $statLabel = 'Cancelled ';
            } elseif ($statid == 4) {
                $statLabel = 'Neglect ';
            } else {
                $statLabel = '';
            }

            if ($data[$i]['ischecker'] == 1) {
                $statLabel .= ' (Checker)';
            }
            if ($data[$i]['ischecker'] == 1 && $data[$i]['statid'] == 0) {
                $statLabel = 'Checking';
            }

            $displayLabel = $data[$i]['isprev'] == 1 && $data[$i]['statid'] == 0 ? 'On-Going (Continuation)' : $statLabel;

            $hours = ($data[$i]['donedate'] == null)
                ? round((strtotime($this->othersClass->getCurrentTimeStamp()) - strtotime($data[$i]['createdate'])) / 3600, 2)
                : $data[$i]['hours'];

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
            $str .= $this->reporter->col($rowNum . '.', '40', null, false, '1px dotted', '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['createdate'])), '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($displayLabel, '90', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['userr'], '180', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['rem'], '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['createdate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['donedate'], '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(number_format($hours, 2) . ' hrs', '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '990', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // summary header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('CUSTOMER', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('No Of Hours', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // group summary by customer
        $summaryGroup = array();
        foreach ($data as $row) {
            $key = $row['customer'];
            if (!isset($summaryGroup[$key])) {
                $summaryGroup[$key] = array(
                    'customer' => $row['customer'],
                    'hours'    => 0,
                );
            }
            $summaryGroup[$key]['hours'] += $row['hours'];
        }

        $grandtotal = 0;
        foreach ($summaryGroup as $summary) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($summary['customer'], '400', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($summary['hours'], 2) . ' hrs', '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandtotal += $summary['hours'];
        }

        // grand total
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('TOTAL: ' . count($summaryGroup), '400', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2) . ' hrs', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}
