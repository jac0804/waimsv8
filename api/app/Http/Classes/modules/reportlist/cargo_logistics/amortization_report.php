<?php

namespace App\Http\Classes\modules\reportlist\cargo_logistics;

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

class amortization_report
{
    public $modulename = 'Amortization Report';
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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'dprojectname', 'dacnoname', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dacnoname.label', 'Asset Account');
        data_set($col1, 'dacnoname.lookupclass', 'IN');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,'' as dprojectname, '' as projectname, '' as projectcode, '' as contra,
        '' as acnoname,
        '' as dacnoname";

        return $this->coreFunctions->opentable($paramstr);
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }


    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $filter = "";

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $dprojectname = $config['params']['dataparams']['dprojectname'];
        $acno = $config['params']['dataparams']['contra'];

        if ($dprojectname != "") {
            $projid = $config['params']['dataparams']['projectid'];
            $filter .= " and head.projectid = '$projid' ";
        }

        if ($acno != "") {
            $filter .= " and head.contra =  '\\\\" . $acno . "' ";
        }

        $query = "select doc,docno,dateid,rem,projcode,projname,contra,acnoname,termsyear,sum(ext) as ext, 
                         sum(accdep) as accdep, sum(dep) as dep
                from (select head.doc,head.docno,date(head.dateid) as dateid, head.rem,p.code as projcode,
                             p.name as projname,head.contra,coa.acnoname,cinfo.termsyear,
                             (select sum(ext) from lastock as stock where stock.trno=head.trno) as ext,
                             0 as accdep,0 as dep
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join coa on coa.acno=head.contra
                    left join projectmasterfile as p on p.line=head.projectid
                    left join cntnuminfo as cinfo on cinfo.trno=head.trno
                    where head.doc='FA' and '$start' <= DATE_ADD(date(head.dateid), INTERVAL cinfo.termsyear YEAR) and
                        '$end' >= date(head.dateid) $filter
                union all

                select head.doc,head.docno,date(head.dateid) as dateid, head.rem,p.code as projcode,p.name as projname,
                            head.contra,coa.acnoname,cinfo.termsyear,
                            (select sum(ext) from glstock as stock where stock.trno=head.trno) as ext,
                            0 as accdep, 0 as dep
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join coa on coa.acno=head.contra
                    left join projectmasterfile as p on p.line=head.projectid
                    left join hcntnuminfo as cinfo on cinfo.trno=head.trno
                    where head.doc='FA' and '$start' <= DATE_ADD(date(head.dateid), INTERVAL cinfo.termsyear YEAR) and
                        '$end' >= date(head.dateid) $filter
                    group by head.trno,head.doc,head.docno,head.dateid, head.rem,p.code,p.name,
                            head.contra,coa.acnoname,cinfo.termsyear

                union all
                select head.doc,head.docno,date(head.dateid) as dateid, head.rem,p.code as projcode,p.name as projname,
                            head.contra,coa.acnoname,cinfo.termsyear,0 as ext,sum(sc.amt) as accdep, 0 as dep
                from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join coa on coa.acno=head.contra
                    left join projectmasterfile as p on p.line=head.projectid
                    left join hcntnuminfo as cinfo on cinfo.trno=head.trno
                left join fasched as sc on sc.rrtrno=head.trno
                where sc.jvtrno <> 0 and date(sc.dateid) < '$start' $filter
                group by head.trno,head.doc,head.docno,head.dateid, head.rem,p.code,p.name,
                            head.contra,coa.acnoname,cinfo.termsyear
                union all
                select head.doc,head.docno,date(head.dateid) as dateid, head.rem,p.code as projcode,p.name as projname,
                            head.contra,coa.acnoname,cinfo.termsyear,0 as ext,0 as accdep, sum(sc.amt) as dep
                from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join coa on coa.acno=head.contra
                    left join projectmasterfile as p on p.line=head.projectid
                    left join hcntnuminfo as cinfo on cinfo.trno=head.trno
                left join fasched as sc on sc.rrtrno=head.trno
                where sc.jvtrno <> 0 and date(sc.dateid) between '$start' and '$end' $filter
                group by head.trno,head.doc,head.docno,head.dateid, head.rem,p.code,p.name,
                            head.contra,coa.acnoname,cinfo.termsyear) as k
                group by doc,docno,dateid,rem,projcode,projname,contra,acnoname,termsyear";

        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 71;
        $page = 70;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize12 = 10;
        $fontsize14 = 14;
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->header_table($config, $layoutsize);
        // $str .= '</br>';
        $i = 1;
        $totaldep = 0;
        $bookval = 0;

        $gtotalaccdep = 0;
        $gtotaldep = 0;
        $ggtotaldep = 0;
        $gtotalbookval = 0;

        $ctr = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'CT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'LT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col($data->projname, '120', null, false, $border, '', 'LT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col($data->acnoname, '180', null, false, $border, '', 'LT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col($data->termsyear, '50', null, false, $border, '', 'CT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col(number_format($data->accdep, 2), '100', null, false, $border, '', 'RT', $font, $fontsize12, 'R', '', '');
                $str .= $this->reporter->col(number_format($data->dep, 2), '110', null, false, $border, '', 'RT', $font, $fontsize12, 'R', '', '');

                $totaldep = $data->accdep + $data->dep;
                $str .= $this->reporter->col(number_format($totaldep, 2), '110', null, false, $border, '', 'RT', $font, $fontsize12, 'R', '', '');

                $bookval = $data->ext - $totaldep;
                $str .= $this->reporter->col(number_format($bookval, 2), '110', null, false, $border, '', 'RT', $font, $fontsize12, 'R', '', '');

                $str .= $this->reporter->endrow($layoutsize);

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config, $layoutsize);
                    $str .= $this->header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if

                $ctr = $i++;

                $gtotalaccdep += $data->accdep;
                $gtotaldep += $data->dep;
                $ggtotaldep += $totaldep;
                $gtotalbookval += $bookval;
            }
        }
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font, $fontsize12, 'R', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'L', $font, $fontsize12, 'R', '', '');
        $str .= $this->reporter->col('', '570', null, false, $border, 'T', 'C', $font, $fontsize12, 'R', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize12, 'R', '', '');
        $str .= $this->reporter->pagenumber('Page', '90', null, '', $border, '', 'L', $font, $fontsize12, '', '', '');
        $str .= $this->reporter->col('', '570', null, false, $border, '', 'C', $font, $fontsize12, 'R', '', '');
        $str .= $this->reporter->col(number_format($gtotalaccdep, 2), '100', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotaldep, 2), '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col(number_format($ggtotaldep, 2), '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotalbookval, 2), '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_table($config, $layoutsize)
    {
        $str = "";
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize12 = 10;
        $fontsize14 = 14;
        $border = "1px solid";
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc No', '110', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Date', '90', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Notes', '120', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Project', '120', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Asset Account', '180', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Cost', '100', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Life', '50', null, false, $border, '', 'C', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Acc Dep', '100', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Depreciation', '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Tot Dep', '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->col('Book Value', '110', null, false, $border, '', 'R', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'C', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'B', 'R', $font, 5, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }


    public function header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $dprojectname = $config['params']['dataparams']['dprojectname'];
        $dacnoname = $config['params']['dataparams']['dacnoname'];

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize12 = 10;
        $fontsize14 = 11;
        $fontsize16 = 16;
        $border = "1px solid ";

        if ($dprojectname == '') {
            $dprojectname = 'ALL';
        }

        if ($dacnoname == '') {
            $dacnoname = 'ALL';
        }

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, 10, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, $fontsize14, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AMORTIZATION REPORT', null, null, false, $border, '', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($start . '   -   ' . $end, null, null, false, $border, '', 'C', 14, $fontsize12, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project : ' . $dprojectname, null, null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Asset Account : ' . $dacnoname, null, null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class