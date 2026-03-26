<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class profit_and_loss_per_product_line
{
    public $modulename = 'Profit and Loss per Product Line';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $xdept = [];
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['project', 'branchname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'project.name', "projectname");
        
        switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'project.label', 'Item Group');
                break;
            default:
                $col1 = $this->fieldClass->create($fields);
                break;
        }

        data_set($col2, 'project.required', false);
        data_set($col2, 'branchname.style', '');
        data_set($col2, 'branchname.lookupclass', 'branch');
        data_set($col2, 'branchname.action', 'lookupclient');
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
          'default' as print,
           adddate(left(now(),10),-360) as start,
          left(now(),10) as `end`,
          0 as clientid,
          '' as clientname,
          '' as branchname,
          '' as project,
          '' as projectcode,
          0 as projectid,
          '' as projectname
          ");
    }

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
        $result = $this->default_layout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {
        $query = "select 0 as line,'' as code,'' as name union all select line, code, name from projectmasterfile order by name";
        return $query;
    }

    public function default_header($config)
    {
        // $center     = $config['params']['center'];
        // $username   = $config['params']['user'];
        // $companyid  = $config['params']['companyid'];
        // $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $branchname  = $config['params']['dataparams']['branchname'];
        $projectname  = $config['params']['dataparams']['projectname'];

        $str = "";
        $layoutsize = '800';
        $font =  "cambria";
        // $fontsize = "10";
        $border = "1px solid ";
        $str .= "<br>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As Of ' . date('F d, Y', strtotime($end)), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Project: " . ($projectname == '' ? "ALL" : $projectname), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Branch: " . ($branchname == '' ? 'ALL' : $branchname), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= "<br>";

        $str .= $this->reporter->begintable($layoutsize);
        return $str;
    }

    public function default_layout($config)
    {
        $result = $this->reportDefault($config);
        // $center     = $config['params']['center'];
        // $username   = $config['params']['user'];
        // $companyid  = $config['params']['companyid'];
        // $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $project   = $config['params']['dataparams']['project'];
        $branch   = $config['params']['dataparams']['branchname'];
        // $count = 38;
        // $page = 38;

        $str = '';
        $layoutsize = '800';
        $font =  "cambria";
        // $fontstyle = "";
        $fontsize = "10";
        $border = "1px solid ";
        $b = "";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);
        // $totalamt = 0;
        // $grandtotal = 0;
        // $prjname = "";
        // $acnoname = "";
        // $pacnoname = "";

        // $cols = "";
        // $acnos = "";
        // $gtotalcols = "";
        $headerwidth = count($result) * 200;
        $wpercol = $headerwidth / count($result);
        $counter = count($result);

        //plot departments
        $str .= $this->reporter->begintable($headerwidth);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col(($data->name == '' ? 'No Item Group' : $data->name), $wpercol, null, false, $border, $b, 'C', $font, $fontsize, 'B', '', '');
            $xdept[$key] = $data->line . "~" . $data->name;
        }
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, $b, 'R', $font, $fontsize, 'B', '', '');
        //end plotting dept

        $filter = "";
        if ($project != "") {
            $projectid = $config['params']['dataparams']['projectid'];
            $filter .= " and detail.projectid=" . $projectid;
        }
        if ($branch != "") {
            $branchid = $config['params']['dataparams']['clientid'];
            $filter .= " and detail.branch=" . $branchid;
        }

        $str .= $this->plotting('R', $xdept, $start, $end, $filter, $counter, $wpercol);
        $str .= $this->plotting('E', $xdept, $start, $end, $filter, $counter, $wpercol);
        $str .= $this->plotting('C', $xdept, $start, $end, $filter, $counter, $wpercol);

        $str .= "<br>";
        $str .= "<br>";
        $str .= "<br>";
        $str .= "<br>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->endreport();

        return $str;
    }

    private function getaccqry($acnoid, $start, $end, $filter, $addfilters = '')
    {
        $accqry = "select acnoid,acno, acnoname, levelid, cat, parent, detail, case when cat ='E' then sum(db-cr) else sum(cr-db) end as amt,
        ifnull(projectname,'') as branchname,ifnull(projectcode,0) as branch,ifnull(deptid,0) as deptid
        from (
        select coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,ifnull(sum(tb.db),0)as db , ifnull(sum(tb.cr),0) as cr,tb.projectcode,tb.projectname,tb.deptid
        from coa left join (
        select detail.acnoid,sum(detail.db) as db,sum(detail.cr) as cr,detail.branch,b.clientname as branchname,p.line as deptid,p.code as projectcode,p.name as projectname from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client as b on b.clientid = detail.branch
        left join projectmasterfile as p on p.line = detail.projectid
        left join cntnum on cntnum.trno=head.trno
        where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by detail.acnoid, head.dateid,detail.branch,b.clientname,p.line,p.code,p.name
        ) as tb on tb.acnoid=coa.acnoid where coa.acnoid =" . $acnoid . $addfilters . " 
        group by coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.projectname, tb.projectcode,tb.deptid
        ) as inc group by acnoid,acno, acnoname, levelid, cat, parent, detail, branch, branchname,deptid";
        return $accqry;
    }

    private function getsumaccqry($cat, $start, $end, $filter, $addfilters = '')
    {
        $accqry = "select case when cat ='E' then sum(db-cr) else sum(cr-db) end as amt
        from (
        select coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,ifnull(sum(tb.db),0)as db , ifnull(sum(tb.cr),0) as cr,projectcode,projectname,deptid
        from coa left join (
        select detail.acnoid,sum(detail.db) as db,sum(detail.cr) as cr,p.code as projectcode,ifnull(p.name,'') as projectname,p.line as deptid from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client as b on b.clientid = detail.branch
        left join projectmasterfile as p on p.line = detail.projectid
        left join cntnum on cntnum.trno=head.trno
        where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
        group by detail.acnoid, head.dateid,p.code,p.name,p.line
        ) as tb on tb.acnoid=coa.acnoid where coa.cat ='" . $cat . "' " . $addfilters . " 
        group by coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail, tb.projectcode, tb.projectname,tb.deptid
        ) as inc group by inc.cat";
        return $accqry;
    }

    private function plotting($cat, $xdept, $start, $end, $filter, $counter, $wpercol)
    {
        $accounts = $this->coreFunctions->opentable("select acno,acnoid,acnoname,parent,detail,cat,levelid from coa where cat in ('" . $cat . "') order by acno");
        // $levelid = 0;
        $font =  "cambria";
        $fontstyle = "";
        $fontsize = "10";
        $border = "1px solid ";
        $b = "";
        $acnoname = '';
        $str = '';

        foreach ($accounts as $k => $vaccount) {
            $fontstyle = '';
            if ($vaccount->detail == 0) {
                $fontstyle = "B";
                // $pacnoname = $vaccount->acnoname;
            }

            $indent = '5' * ($vaccount->levelid * 3);
            if ($acnoname != $vaccount->acnoname) {
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($vaccount->acnoname, '200', null, false, $border, $b, 'L', $font, $fontsize, $fontstyle, '', '0px 0px 0px ' . $indent . 'px');
            }
            $xx = 0;
            // $yy = 0;
            $rowtotal = 0;

            foreach ($xdept as $value) { // dept array
                $depts = explode("~", $value);
                $addfilters = " and tb.deptid = " . $depts[0];

                $accqry = $this->getaccqry($vaccount->acnoid, $start, $end, $filter, $addfilters);
                $trans = $this->coreFunctions->opentable($accqry);
                if (count($trans) == 0) {
                    $str .= $this->reporter->col('-', $wpercol, null, false, $border, $b, 'R', $font, $fontsize, $fontstyle, '', '');
                    $xx++;
                } else {
                    foreach ($trans as $kk => $vvdata) {
                        if ($vvdata->detail != 0) {
                            if ($depts[0] == $vvdata->deptid) {
                                $print_amt = $vvdata->amt == 0 ? '-' : number_format($vvdata->amt, 2);
                                $str .= $this->reporter->col($print_amt, $wpercol, null, false, $border, $b, 'R', $font, $fontsize, '', '', '');
                                $rowtotal += $vvdata->amt;
                                $xx++;
                            }
                        }
                    }
                }
                if ($xx == $counter) {
                    $print_rowtotal = $rowtotal == 0 ? '-' : number_format($rowtotal, 2);
                    $str .= $this->reporter->col($print_rowtotal, $wpercol, null, false, $border, $b, 'R', $font, $fontsize, '', '', '');
                }
            }
            // $levelid = $vaccount->levelid;
            $acnoname = $vaccount->acnoname;
        } //accounts

        $str .= $this->reporter->startrow();
        switch ($cat) {
            case 'R':
                $str .= $this->reporter->col('TOTAL REVENUE', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
                break;
            case 'E':
                $str .= $this->reporter->col('TOTAL EXPENSES', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
                break;
        }

        $rowtotal = 0;
        $xx = 0;

        $str .= $this->reporter->col('GRAND TOTAL', 200, null, false, $border, 'T', 'l', $font, $fontsize, 'B', '', '');
        foreach ($xdept as $value) { // dept array
            $depts = explode("~", $value);
            $addfilters = " and tb.deptid = " . $depts[0];
            $sumqry = $this->coreFunctions->opentable($this->getsumaccqry($cat, $start, $end, $filter, $addfilters));
            if (count($sumqry) == 0) {
                $str .= $this->reporter->col('-', $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, $fontstyle, '', '');
                $xx++;
            } else {
                foreach ($sumqry as $kk => $sumdata) {

                    $str .= $this->reporter->col(number_format($sumdata->amt, 2), $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
                    $rowtotal += $sumdata->amt;
                    $xx++;
                }
            }

            if ($xx == $counter) {
                $print_rowtotal = $rowtotal == 0 ? '-' : number_format($rowtotal, 2);
                $str .= $this->reporter->col($print_rowtotal, $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
            }
        }

        return $str;
    }
}
