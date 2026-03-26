<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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

class project_cost_analysis
{
    public $modulename = 'Project Cost Analysis';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



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
        $fields = ['radioprint', 'end', 'project', 'subprojectname', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'end.required', true);
        data_set($col1, 'project.name', "projectname");

        data_set($col1, 'subprojectname.type', "lookup");
        data_set($col1, 'subprojectname.action', "lookupsubproject");
        data_set($col1, 'subprojectname.addedparams', ['projectid']);
        data_set($col1, 'subprojectname.lookupclass', 'default');
        data_set($col1, 'subprojectname.required', true);
        data_set($col1, 'end.label', 'As Of');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
    'default' as print,
     left(now(),10) as end,
    '0' as subproject,
    '' as subprojectname,
    '' as project,
    '' as projectcode,
    '0' as projectid,
    '' as projectname,
    '0' as reporttype
    ");
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
            case 0: // summarized
                $result = $this->summary_layout($config);
                break;
            case 1: //detailed
                $result = $this->default_layout($config);
                break;
        }


        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {
        $filter = "";
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $projectid = $config['params']['dataparams']['projectid'];
        $projectname = $config['params']['dataparams']['projectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];
        $subprojectid = $config['params']['dataparams']['subproject'];


        if ($subprojectname != "") {
            $filter .= " and sp.line  = $subprojectid";
        }

        $query = "              
              select pm.projectid, sp.line as subprojectid,pm.tcp,pm.cost as estimatecost,pm.trno from pmhead as pm
              left join subproject as sp on sp.trno = pm.trno
              where pm.projectid = $projectid $filter
              union all
              select pm.projectid, sp.line as subprojectid,pm.tcp,pm.cost as estimatecost,pm.trno from hpmhead as pm
              left join subproject as sp on sp.trno = pm.trno
              where pm.projectid = $projectid $filter
              ";

        return $query;
    }

    private function generateReportHeader($center, $username)
    {
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $str = '';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        return $str;
    } //end function generate report header

    public function default_header($config, $result)
    {
        $mdc = URL::to('/images/reports/mdc.jpg');
        $tuv = URL::to('/images/reports/tuv.jpg');

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $subprojectname  = $config['params']['dataparams']['subprojectname'];
        $projectname  = $config['params']['dataparams']['projectname'];
        $projectcode  = $config['params']['dataparams']['projectcode'];
        $str = "";
        $layoutsize = '1950';
        $font =  "Century Gothic";
        $fontsize = "12";
        $border = "1px solid ";

        $str .= "<div style='position: relative;'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->generateReportHeader($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<div style='position:absolute; top: 60px;'>";
        $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');

        $str .= "</div>";

        $str .= "</div>";

        $str .= "<br>";

        $pbamt = 0;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PROJECT COST ANALYSIS', '1700', null, false, $border, '', 'C', $font, '14', 'B', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Project Code: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($projectcode, '1600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Project Name: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($projectname, '1600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Subproject: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subprojectname, '1600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("As of : ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($end, '1600', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br>";
        $estcontractprice = 0;
        $rrjcpercentage = 0;
        $pbpercentage = 0;
        $crpercentage = 0;

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '76', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '562', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '294', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Estimated Construction', '178', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('Construction Actual Cost', '320', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('Billing Accomplishment (WAC)', '160', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Billing Accomplishment
        $str .= $this->reporter->col('Progress Billing', '120', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Progress Billing
        $str .= $this->reporter->col('Collection', '120', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Collection
        $str .= $this->reporter->col('Uncollected', '120', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Uncollected
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '76', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '562', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Contract', '294', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cost', '184', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('(Material Receiving & Job Completion from BOQ)', '314', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('(Billing Accomplishment)', '160', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Billing Accomplishment
        $str .= $this->reporter->col('', '120', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Progress Billing
        $str .= $this->reporter->col('', '120', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Collection
        $str .= $this->reporter->col('', '120', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Uncollected

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Subact ID', '76', null, false, $border, 'LRB', 'C', $font, 10, 'B', '', '');
        $str .= $this->reporter->col('Subactivity', '282', null, false, $border, 'LRBT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Description', '280', null, false, $border, 'LRBT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Qty', '89', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Unit', '87', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Contract Amount', '118', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '105', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '79', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('Qty', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Unit', '79', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '75', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('Amount', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', ''); //PB Amount
        $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', ''); //Collection Amount
        $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', ''); //Uncollected Amount

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    public function default_layout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $subprojectid = $config['params']['dataparams']['subproject'];
        $projectid = $config['params']['dataparams']['projectid'];

        $count = 38;
        $page = 38;

        $str = '';
        $layoutsize = '1950';
        $font =  "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config, $result);
        $totalamt = 0;
        $subtotal = 0;
        $prjname = "";
        $acnoname = "";

        $stage = "";
        $substage = "";
        $subactivityid = "";

        $totalcontractqty = 0;
        $tottotalamt = 0;
        $tottotalcost = 0;
        $totalestweight = 0;

        $rrqty = 0;
        $rramt = 0;
        $pbdb = 0;
        $str .= $this->reporter->begintable($layoutsize);

        foreach ($result as $key => $value) {
            //get all stages per project/subproject
            $qry = "select s.stage as stagename,st.stage as stageid from stages as st left join stagesmasterfile as s on s.line = st.stage where st.projectid = ? and st.subproject =? order by st.stage,st.line";
            $stages = $this->coreFunctions->opentable($qry, [$value->projectid, $value->subprojectid]);

            $pbqry = "select projectid,subproject,sum(cr) as pbamt,sum(cramt) as cramt,sum(cr)-sum(cramt) as uncollected from (
                        select detail.trno,detail.projectid,detail.subproject,sum(detail.db) as db, sum(detail.cr) as cr,
                        (select sum(cr) from gldetail as d left join glhead as h on h.trno=d.trno
                        where h.doc='CR' and d.projectid=detail.projectid and d.refx=detail.trno and h.dateid <= '" . $end . "') as cramt
                        from ladetail as detail
                        left join lahead as head on head.trno=detail.trno
                        where head.doc='PB' and detail.projectid = ? and head.dateid <= '" . $end . "'
                        group by detail.trno,detail.projectid,detail.subproject
                        union all
                        select detail.trno,detail.projectid,detail.subproject,sum(detail.db) as db, sum(detail.cr) as cr,
                        (select sum(cr) from gldetail as d left join glhead as h on h.trno=d.trno
                        where h.doc='CR' and d.projectid=detail.projectid and d.refx=detail.trno and h.dateid <= '" . $end . "') as cramt
                        from gldetail as detail
                        left join glhead as head on head.trno=detail.trno
                        where head.doc='PB' and detail.projectid = ? and head.dateid <='" . $end . "'
                        group by detail.trno,detail.projectid,detail.subproject) as k
                        group by projectid,subproject";

            $pb = $this->coreFunctions->opentable($pbqry, [$value->projectid, $value->projectid]);

            //loop through stages and get details     
            foreach ($stages as $s => $v) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($v->stagename, '300', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '300', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col("", '90', null, false, $border, 'LRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($pb[0]->pbamt, 2), '120', null, false, $border, 'LRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($pb[0]->cramt, 2), '120', null, false, $border, 'LRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($pb[0]->uncollected, 2), '120', null, false, $border, 'LRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                //get activity per stage
                $qry = "select s.substage as actname,s.line as actid from substages as s left join activity as a on a.line = s.line and a.stage = s.stage where a.trno = ? and a.subproject =? and a.stage =? order by a.line";
                $act = $this->coreFunctions->opentable($qry, [$value->trno, $value->subprojectid, $v->stageid]);
                if (!empty($act)) {
                    foreach ($act as $a => $c) {
                        $tconamt = $this->coreFunctions->datareader("select sum(ps.ext) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $testamt = $this->coreFunctions->datareader("select sum(ps.totalcost) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('&nbsp&nbsp' . $c->actname, '300', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '300', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('1', '90', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('lot', '90', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($tconamt, 2), '90', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($testamt, 2), '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');

                        $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->endrow();
                        //get subactivity
                        $qry = "select ps.subactid,s.subactivity as sactname,s.line as sactid,s.description,ps.uom,ps.rrqty as conqty,ps.qty as esqty,ps.ext as conamt,ps.totalcost as esamt 
                                from subactivity as s 
                                left join psubactivity as ps on ps.line = s.line 
                                where ps.ext <> 0 and ps.trno = ? and ps.subproject=? and ps.stage =? and ps.substage = ?  
                                order by s.line";
                        $tesamt = $this->coreFunctions->datareader("select sum(ps.totalcost) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $subact = $this->coreFunctions->opentable($qry, [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);

                        $wconamt = 0;

                        $wrrjc = 0;
                        $wba = 0;
                        if (!empty($subact)) {
                            foreach ($subact as $b => $d) {
                                //plot details of subactivity
                                if (floatval($tesamt) != 0) {
                                    $wconamt = ($d->esamt / $tesamt) * 100;
                                }
                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col($d->subactid, '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $d->sactname, '300', null, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col($d->description, '300', null, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($d->conqty, 2), '90', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col($d->uom, '90', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($d->conamt, 2), '90', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($d->esamt, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($wconamt, 2) . '%', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');

                                //get rr/jc of each subactivity base on BOQ
                                //qry boq of subactivity then connect to pr->po->rr / jr->jo->jc base on trno and line ng boq 
                                //tpos yun yun iplot sa susunod na cols.

                                $qry = "select s.trno,s.line
                                        from hsostock as s
                                        left join hsohead as h on h.trno=s.trno
                                        where s.qa > 0 and h.projectid=? and h.subproject=? and s.stageid=? and s.substage=? and s.subactivity=?";

                                $subactboq = $this->coreFunctions->opentable($qry, [$value->projectid, $value->subprojectid, $v->stageid, $c->actid, $d->sactid]);

                                $ba = "select sum(s.ext) as ext
                                       from hbastock as s
                                       left join hbahead as h on h.trno=s.trno
                                       where h.projectid=? and h.subproject=? and s.stage =? and s.activity=? 
                                             and s.subactid=? and h.dateid <= '" . $end . "'";
                                $baresult = $this->coreFunctions->opentable($ba, [$value->projectid, $value->subprojectid, $v->stageid, $c->actid, $d->subactid]);

                                $rrqry = "select sum(qty) as qty,sum(amt) as amt
                                        from (select sum(rr.qty) as qty,sum(rr.ext) as amt,rr.docno as rrdocno
                                              from (select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno,s.itemid
                                                    from lahead as h
                                                    left join lastock as s on s.trno = h.trno
                                                    left join cntnuminfo as info on info.trno=h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='RR' and h.projectid = $value->projectid and h.subproject = $value->subprojectid
                                                        and s.stageid = $v->stageid and ps.substage=$c->actid and ps.line = $d->sactid and ps.subactid='" . $d->subactid . "'
                                                        and info.transtype ='Project Cost' and h.dateid <= '" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno,s.itemid
                                                    union all
                                                    select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno,s.itemid
                                                    from glhead as h
                                                    left join glstock as s on s.trno = h.trno
                                                    left join hcntnuminfo as info on info.trno=h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='RR' and h.projectid = $value->projectid and h.subproject = $value->subprojectid
                                                        and s.stageid = $v->stageid and ps.substage=$c->actid and ps.line = $d->sactid and ps.subactid='" . $d->subactid . "'
                                                        and info.transtype ='Project Cost'
                                                        and h.dateid <= '" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno,s.itemid) as rr
                                            group by rrdocno
                                            union all
                                            select sum(rr.qty) as qty,sum(rr.ext) as amt,rr.docno as rrdocno
                                            from (
                                            select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno
                                                    from jchead as h
                                                    left join jcstock as s on s.trno = h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='JC' and h.projectid = $value->projectid and h.subproject = $value->subprojectid
                                                        and s.stageid = $v->stageid and ps.substage=$c->actid and ps.line = $d->sactid and ps.subactid='" . $d->subactid . "'
                                                        and h.dateid <='" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno
                                                    union all
                                                    select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno
                                                    from hjchead as h
                                                    left join hjcstock as s on s.trno = h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='JC' and h.projectid = $value->projectid and h.subproject = $value->subprojectid
                                                        and s.stageid = $v->stageid and ps.substage=$c->actid and ps.line = $d->sactid
                                                        and ps.subactid='" . $d->subactid . "'
                                                        and h.dateid <= '" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno
                                            ) as rr
                                            group by rrdocno
                                            union all
                                            select sum(rr.qty) as qty,sum(rr.ext) as amt,rr.docno as rrdocno
                                            from (select s.trno,s.line,s.refx,s.linex,s.isqty as qty,s.uom,s.ext,h.docno
                                                    from lahead as h
                                                    left join lastock as s on s.trno = h.trno
                                                    left join cntnuminfo as info on info.trno=h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='MT' and h.projectto = $value->projectid and h.subprojectto = $value->subprojectid
                                                        and ps.subactid='" . $d->subactid . "' and ps.substage=$c->actid and ps.line = $d->sactid and ps.stage=$v->stageid
                                                        and h.dateid <= '" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.isqty,s.uom,s.ext,h.docno
                                                    union all
                                                    select s.trno,s.line,s.refx,s.linex,s.isqty as qty,s.uom,s.ext,h.docno
                                                    from glhead as h
                                                    left join glstock as s on s.trno = h.trno
                                                    left join hcntnuminfo as info on info.trno=h.trno
                                                    left join subitems as it on it.itemid=s.itemid and it.stage=s.stageid
                                                    left join psubactivity as ps on ps.line=it.subactivity
                                                    where h.doc='MT' and h.projectto =  $value->projectid and h.subprojectto = $value->subprojectid
                                                        and ps.subactid='" . $d->subactid . "' and ps.substage=$c->actid and ps.line = $d->sactid and ps.stage=$v->stageid
                                                        and h.dateid <= '" . $end . "' and ps.trno= $value->trno
                                                    group by s.trno,s.line,s.refx,s.linex,s.isqty,s.uom,s.ext,h.docno) as rr
                                            group by rrdocno
                                            ) as a
                                        where qty is not null";

                                $rrdata = $this->coreFunctions->opentable($rrqry);

                                // foreach ($rrdata as $s => $w) {
                                //     // $wrrjc = ($w->amt / $tesamt) * 100;
                                //     // $wba = ($baresult[0]->ext / $tesamt) * 100;

                                //     $str .= $this->reporter->col(number_format($w->qty, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col($d->uom, '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col('', '79', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col(number_format($baresult[0]->ext, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');


                                //     $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                //     $str .= $this->reporter->endrow();
                                // }

                                /////test end

                                // $wrrjc = ($rrdata[0]->amt / $tesamt) * 100;
                                // $wba = ($baresult[0]->ext / $tesamt) * 100;

                                // if ($rrdata[0]->amt == 0) {
                                //     $wrrjc = 0;
                                // }
                                $str .= $this->reporter->col(number_format($rrdata[0]->qty, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col($d->uom, '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($rrdata[0]->amt, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($baresult[0]->ext, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');


                                $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->endrow();
                                // $str .= $this->reporter->endtable();
                                $rrqty = 0;
                                $rramt = 0;


                                // $wrrjc = ($rramt / $tesamt) * 100;
                                // $wba = ($baresult[0]->ext / $tesamt) * 100;
                                // $str .= $this->reporter->col(number_format($rrqty, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col($d->uom, '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col(number_format($rramt, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col(number_format($wrrjc, 2) . '%', '79', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col(number_format($baresult[0]->ext, 2), '80', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col(number_format($wba, 2) . '%', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');


                                // $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->col('', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                // $str .= $this->reporter->endrow();
                                // $rrqty = 0;
                                // $rramt = 0;
                            }
                        }
                    }
                }
            }
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }


    //summary
    public function summary_header($config, $result)
    {
        $mdc = URL::to('/images/reports/mdc.jpg');
        $tuv = URL::to('/images/reports/tuv.jpg');

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $subprojectname  = $config['params']['dataparams']['subprojectname'];
        $projectname  = $config['params']['dataparams']['projectname'];
        $projectcode  = $config['params']['dataparams']['projectcode'];
        $str = "";
        $layoutsize = '1590';
        $font =  "Century Gothic";
        $fontsize = "12";
        $border = "1px solid ";

        $str .= "<div style='position: relative;'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->generateReportHeader($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<div style='position:absolute; top: 60px;'>";
        $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
        $str .= "</div>";

        $str .= "</div>";

        $str .= "<br>";

        $pbamt = 0;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PROJECT COST ANALYSIS', '1700', null, false, $border, '', 'C', $font, '14', 'B', '', '5px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Project Code: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($projectcode, '1430', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Project Name: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($projectname, '1430', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Subproject: ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($subprojectname, '1430', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("As of : ", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($end, '1430', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br>";
        $estcontractprice = 0;
        $rrjcpercentage = 0;
        $pbpercentage = 0;
        $crpercentage = 0;

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //subactid
        $str .= $this->reporter->col('', '350', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //description
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //contract
        $str .= $this->reporter->col('', '200', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('Construction Actual Cost', '280', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('Billing Accomplishment (WAC)', '280', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Billing Accomplishment

        $str .= $this->reporter->col('', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Progress Billing
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Collection
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Uncollected
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '350', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Contract', '100', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Estimated Construction Cost', '200', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('(Material Receiving,Job Completion & Stock Issuance from BOQ)', '280', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('(Billing Accomplishment)', '280', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Billing Accomplishment

        $str .= $this->reporter->col('Progress Billing', '100', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Progress Billing
        $str .= $this->reporter->col('Collection', '100', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Collection
        $str .= $this->reporter->col('Uncollected', '100', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', ''); //Uncollected
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Subact ID', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Description', '350', null, false, $border, 'LRBT', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', ''); //Estimated Construction Cost
        $str .= $this->reporter->col('Amount', '180', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', ''); // Construction Actual Cost
        $str .= $this->reporter->col('Amount', '180', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Weighted %', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', ''); //BIlling Accomplishment

        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Progress Billing
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Collection
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', ''); //Uncollected

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }

    public function summary_layout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $subprojectid = $config['params']['dataparams']['subproject'];
        $projectid = $config['params']['dataparams']['projectid'];

        $count = 38;
        $page = 38;

        $str = '';
        $layoutsize = '1590';
        $font =  "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->summary_header($config, $result);
        $totalamt = 0;
        $subtotal = 0;
        $prjname = "";
        $acnoname = "";

        $stage = "";
        $substage = "";
        $subactivityid = "";

        $totalcontractqty = 0;
        $tottotalamt = 0;
        $tottotalcost = 0;
        $totalestweight = 0;

        $t1 = 0;
        $t2 = 0;
        $t3 = 0;
        $t4 = 0;
        $t5 = 0;

        $rrqty = 0;
        $rramt = 0;
        $pbdb = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        foreach ($result as $key => $value) {
            //get all stages per project/subproject
            $qry = "select s.stage as stagename,st.stage as stageid from stages as st left join stagesmasterfile as s on s.line = st.stage where st.projectid = ? and st.subproject =? order by st.stage,st.line";
            $stages = $this->coreFunctions->opentable($qry, [$value->projectid, $value->subprojectid]);


            $pbqry = "select projectid,subproject,sum(cr) as pbamt,sum(cramt) as cramt,sum(cr)-sum(cramt) as uncollected from (
                        select detail.trno,detail.projectid,detail.subproject,sum(detail.db) as db, sum(detail.cr) as cr,
                        (select sum(cr) from gldetail as d left join glhead as h on h.trno=d.trno
                        where h.doc='CR' and d.projectid=detail.projectid and d.refx=detail.trno and h.dateid <= '" . $end . "') as cramt
                        from ladetail as detail
                        left join lahead as head on head.trno=detail.trno
                        where head.doc='PB' and detail.projectid = ? and head.dateid <= '" . $end . "'
                        group by detail.trno,detail.projectid,detail.subproject
                        union all
                        select detail.trno,detail.projectid,detail.subproject,sum(detail.db) as db, sum(detail.cr) as cr,
                        (select sum(cr) from gldetail as d left join glhead as h on h.trno=d.trno
                        where h.doc='CR' and d.projectid=detail.projectid and d.refx=detail.trno and h.dateid <= '" . $end . "') as cramt
                        from gldetail as detail
                        left join glhead as head on head.trno=detail.trno
                        where head.doc='PB' and detail.projectid = ? and head.dateid <='" . $end . "'
                        group by detail.trno,detail.projectid,detail.subproject) as k
                        group by projectid,subproject";
            $pb = $this->coreFunctions->opentable($pbqry, [$value->projectid, $value->projectid]);


            //loop through stages and get details   

            foreach ($stages as $s => $v) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($v->stagename, '350', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col("", '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '180', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '180', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($pb[0]->pbamt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($pb[0]->cramt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($pb[0]->uncollected, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();


                //get activity per stage
                $qry = "select s.substage as actname,s.line as actid from substages as s left join activity as a on a.line = s.line and a.stage = s.stage where a.trno = ? and a.subproject =? and a.stage =? order by a.line";

                $act = $this->coreFunctions->opentable($qry, [$value->trno, $value->subprojectid, $v->stageid]);
                if (!empty($act)) {
                    foreach ($act as $a => $c) {
                        $tconamt = $this->coreFunctions->datareader("select sum(ps.ext) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $testamt = $this->coreFunctions->datareader("select sum(ps.totalcost) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('&nbsp&nbsp' . $c->actname, '350', null, false, $border, 'LRB', 'L', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($tconamt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($testamt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '180', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '180', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');

                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                        $str .= $this->reporter->endrow();

                        //get subactivity

                        $qry = "select s.description,sum(ps.ext) as conamt,sum(ps.totalcost) as esamt
                                from subactivity as s
                                left join psubactivity as ps on ps.line = s.line
                                where ps.ext <> 0 and ps.trno = ? and ps.subproject=? and ps.stage =? and ps.substage = ?
                                group by s.description
                                order by s.description";

                        $tesamt = $this->coreFunctions->datareader("select sum(ps.totalcost) as value from psubactivity as ps where ps.trno =? and ps.subproject=? and ps.stage=?  and ps.substage =? ", [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);
                        $subact = $this->coreFunctions->opentable($qry, [$value->trno, $value->subprojectid, $v->stageid, $c->actid]);

                        $wconamt = 0;

                        $wrrjc = 0;
                        $wba = 0;
                        $tconamt = 0;
                        if (!empty($subact)) {
                            foreach ($subact as $b => $d) {
                                //plot details of subactivity
                                if (floatval($tesamt) != 0) {
                                    $wconamt = ($d->esamt / $tesamt) * 100;
                                }

                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col('', '80', null, false, $border, 'LRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $d->description, '350', null, false, $border, 'LRB', 'L', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($d->conamt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($d->esamt, 2), '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($wconamt, 2) . '%', '100', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');

                                $qry = "select s.trno,s.line,sub.description
                                        from hsostock as s
                                        left join hsohead as h on h.trno=s.trno
                                        left join subactivity as sub on sub.line=s.subactivity
                                        where s.qa > 0 and h.projectid=? and h.subproject=? and s.stageid=? and s.substage=? and sub.description =?";
                                $subactboq = $this->coreFunctions->opentable($qry, [$value->projectid, $value->subprojectid, $v->stageid, $c->actid, $d->description]);

                                $ba = "select sub.description,sum(s.ext) as ext
                                       from hbastock as s
                                       left join hbahead as h on h.trno=s.trno
                                       left join subactivity as sub on sub.line=s.subactivity
                                       where h.projectid=? and h.subproject=? and s.stage =? and s.activity=? and sub.description=? and h.dateid <= '" . $end . "'
                                       group by sub.description";
                                $baresult = $this->coreFunctions->opentable($ba, [
                                    $value->projectid, $value->subprojectid, $v->stageid, $c->actid, $d->description
                                ]);


                                foreach ($subactboq as $k => $boq) {

                                    $prqry = "select trno, line from hprstock as prstock 
                                        where refx = $boq->trno and linex = $boq->line and qa>0 
                                        ";

                                    $prdata = $this->coreFunctions->opentable($prqry);
                                    if (!empty($prdata)) {
                                        foreach ($prdata as $pr => $x) {
                                            $rrqry = "select qty,uom,amt 
                                            from (select sum(rr.qty) as qty,rr.uom,sum(rr.ext) as amt,head.docno as podocno,po.refx,rr.docno as rrdocno 
                                                  from hpostock as po 
                                                  left join hpohead as head on head.trno=po.trno
                                                  left join (select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno
                                                            from lahead as h 
                                                            left join lastock as s on s.trno = h.trno 
                                                            left join cntnuminfo as info on info.trno=h.trno
                                                            where h.doc='RR' and h.projectid = $value->projectid and h.subproject = $value->subprojectid 
                                                                and s.stageid = $v->stageid and info.transtype ='Project Cost' and h.dateid <= '" . $end . "'
                                                            union all
                                                            select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno 
                                                            from glhead as h 
                                                            left join glstock as s on s.trno = h.trno 
                                                            left join cntnuminfo as info on info.trno=h.trno
                                                            where h.doc='RR' and h.projectid = $value->projectid and h.subproject = $value->subprojectid 
                                                                  and s.stageid = $v->stageid and info.transtype ='Project Cost' and h.dateid <= '" . $end . "') as rr
                                                    on rr.refx = po.trno and rr.linex = po.line 
                                                  where po.qa>0  and  po.refx = " . $x->trno . " and po.linex =" . $x->line . " and po.refx <> 0 and rr.docno is not null
                                                  group by rr.uom,head.docno,po.refx,rr.docno
                                                  union all
                                                  select sum(rr.qty) as qty,rr.uom,sum(rr.ext) as amt,head.docno as podocno,po.refx,rr.docno as rrdocno 
                                                  from hjostock as po 
                                                  left join hjohead as head on head.trno= po.trno
                                                  left join (select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno 
                                                            from jchead as h 
                                                            left join jcstock as s on s.trno = h.trno 
                                                            where h.doc='JC' and h.projectid = $value->projectid and h.subproject = $value->subprojectid 
                                                                  and s.stageid = $v->stageid and h.dateid <= '" . $end . "'
                                                            union all
                                                            select s.trno,s.line,s.refx,s.linex,s.qty,s.uom,s.ext,h.docno 
                                                            from hjchead as h 
                                                            left join hjcstock as s on s.trno = h.trno 
                                                            where h.doc='JC' and h.projectid = $value->projectid and h.subproject = $value->subprojectid 
                                                        and s.stageid = $v->stageid and h.dateid <='" . $end . "') as rr
                                                    on rr.refx = po.trno and rr.linex = po.line 
                                                  where po.qa>0 and po.refx = " . $x->trno . " and po.linex =" . $x->line . " and po.qa>0 and po.refx <> 0 and rr.docno is not null 
                                                  group by rr.uom,head.docno,po.refx,rr.docno
                                                  union all
                                                select sum(qty) as qty,uom,sum(ext) as amt,'' as podocno,refx,'' rrdocno from (
                                                            select s.trno,s.line,s.refx,s.linex,s.isqty as qty,s.uom,s.ext,h.docno
                                                            from lahead as h
                                                            left join lastock as s on s.trno = h.trno
                                                            left join cntnuminfo as info on info.trno=h.trno
                                                            where h.doc='MT' and h.projectto =  $value->projectid and h.subprojectto = $value->subprojectid
                                                                and s.stageid =$v->stageid and s.refx = " . $x->trno . " and s.linex =" . $x->line . " and h.dateid <= '" . $end . "'
                                                            union all
                                                            select s.trno,s.line,s.refx,s.linex,s.isqty as qty,s.uom,s.ext,h.docno
                                                            from glhead as h
                                                            left join glstock as s on s.trno = h.trno
                                                            left join cntnuminfo as info on info.trno=h.trno
                                                            where h.doc='MT' and h.projectto =  $value->projectid and h.subprojectto = $value->subprojectid
                                                                and s.stageid = $v->stageid and s.refx = " . $x->trno . " and s.linex =" . $x->line . " and h.dateid <= '" . $end . "' ) as k
                                                group by uom,refx) as a 
                                              where qty is not null group by qty,uom,amt";
                                            $rrdata = $this->coreFunctions->opentable($rrqry);
                                            foreach ($rrdata as $i => $rr) {
                                                $rrqty += $rr->qty;
                                                $rramt += $rr->amt;
                                            }
                                        }
                                    }
                                }

                                if ($rramt == 0 || $tesamt == 0) {
                                    $wrrjc = 0;
                                } else {
                                    $wrrjc = ($rramt / $tesamt) * 100;
                                }

                                $baext = 0;
                                if (!empty($baresult[0]->ext)) {
                                    $baext = $baresult[0]->ext;
                                }

                                if ($baext == 0 || $tesamt == 0) {
                                    $wba = 0;
                                } else {
                                    $wba = ($baext / $tesamt) * 100;
                                }

                                $str .= $this->reporter->col(number_format($rramt, 2), '180', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($wrrjc, 2) . '%', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($baext, 2), '180', null, false, $border, 'TLRB', 'R', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col(number_format($wba, 2) . '%', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');

                                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
                                $str .= $this->reporter->endrow();
                                $rrqty = 0;
                                $rramt = 0;
                            }
                        }
                    }
                }
            }
        }

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class