<?php

namespace App\Http\Classes\modules\reportlist\school_system_reports;

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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class summary_of_grades_report
{
    public $modulename = 'Summary Of Grades Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];


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
        $fields = ['radioprint', 'sy', 'course', 'yr', 'section', 'quartercode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'sy.lookupclass', 'report');
        data_set($col1, 'yr.lookupclass', 'report');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as sy,
    '' as course,
    '' as yr,'' as section,'' as sectionid,
    '' as quartercode,'' as quarterid,'' as quartername
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {

        $cline  = $config['params']['dataparams']['courseid'];
        $yr  = $config['params']['dataparams']['yr'];
        $sy  = $config['params']['dataparams']['syid'];
        $section  = $config['params']['dataparams']['sectionid'];

        $qline  = $config['params']['dataparams']['quarterid'];

        $filter = "";
        if ($cline != "") {
            $filter .= " and c.line = '$cline' ";
        }
        if ($yr != "") {
            $filter .= " and head.yr = '$yr' ";
        }
        if ($sy != "") {
            $filter .= " and sy.line= '$sy'";
        }
        if ($section != "") {
            $filter .= " and sec.line = '$section' ";
        }

        if ($qline != "") {
            $filter .= " and q.line = '$qline' ";
        }
        $filter .= " and qg.isconduct=0";
        $query = "select qg.isconduct,qg.quarterid, avg(qg.rcardtotal) as rcardtotal, q.name as quartername, q.code as quartercode, qg.clientid,
      head.syid,head.sectionid,sy.sy,sec.section,c.coursecode,c.coursename,client.clientname,client.client,subj.subjectcode,subj.subjectname
      from en_gequartergrade as qg
      left join client on client.clientid=qg.clientid
      left join en_subject as subj on subj.trno=qg.subjectid
      left join en_quartersetup as q on q.line=qg.quarterid
      left join en_glsubject as sub on sub.trno=qg.subjectid and sub.line=qg.schedline
      left join en_rcdetail as rc on rc.trno=sub.rctrno and rc.line=sub.rcline
      left join en_glhead as head on head.trno=qg.schedtrno
      left join en_schoolyear as sy on sy.line=head.syid
      left join en_section as sec on sec.line=head.sectionid
      left join en_course as c on c.line=head.courseid
      where 1=1 $filter
      group by client.clientname,client.client,qg.isconduct,qg.quarterid,q.name,q.code,qg.clientid,
      head.syid,head.sectionid,sy.sy,sec.section,c.coursecode,c.coursename,subj.subjectcode,subj.subjectname 
      ";



        return $query;
    }

    public function count($mqry, $tocount, $filter)
    {

        return $this->coreFunctions->opentable("select count(distinct $tocount) as count from ($mqry) as x where 1=1 $filter");
    }

    public function getheader($mqry)
    {
        return $this->coreFunctions->opentable("select distinct gcname,topic,gcsubcode from ($mqry) as x order by gcline,topic,gcsubcode");
    }

    public function getcolval($mqry, $field, $filter, $orderby)
    {
        if ($orderby == '') {
            $orderby = " order by clientname,subjectcode";
        }
        return $this->coreFunctions->opentable("select $field from ($mqry) as x where 1=1 $filter $orderby");
    }

    public function getLayoutsize($defaultsize, $subjcount, $fixedextra)
    {
        return ($subjcount * $defaultsize) + $fixedextra;
    }

    public function reportDefaultLayout($config)
    {
        $mqry = $this->reportDefault($config);

        $data = $this->coreFunctions->opentable($mqry);

        $subjectcount = $this->count($mqry, 'subjectcode', '');
        $defsize = 80;
        $namesize = $defsize + 100;
        $layoutsize = $this->getLayoutsize($defsize, $subjectcount[0]->count, 400);

        $subject = $this->getcolval($mqry, 'distinct subjectcode', '', '');


        $str = '';

        $font =  "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config, $layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO.', $defsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
        $str .= $this->reporter->col('NAME', $namesize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $namesize . 'px;word-wrap;break-word;');
        for ($a = 0; $a < count($subject); $a++) {
            $str .= $this->reporter->col($subject[$a]->subjectcode, $defsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
        }
        $str .= $this->reporter->col('Conduct', $defsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
        $str .= $this->reporter->col('Average', $defsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
        $str .= $this->reporter->col('Rank', $defsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
        $str .= $this->reporter->endrow();

        $ranksort = [];
        $sorted = [];
        $rank = [];
        $student = $this->getcolval($mqry, 'distinct clientname,clientid', '', '');
        for ($i = 0; $i < count($student); $i++) {
            $sortavg = 0;

            $grades = $this->getcolval($mqry, 'distinct rcardtotal', 'and clientid=' . $student[$i]->clientid, '');
            for ($c = 0; $c < count($grades); $c++) {
                $sortavg += $grades[$c]->rcardtotal;
            }
            $ranksort[] = $sortavg / count($grades);
        }
        $sorted = $ranksort;
        rsort($ranksort, SORT_REGULAR);
        $p = 0;
        $j = 0;
        for ($p = 0; $p < count($ranksort); $p++) {
            for ($j = 0; $j < count($sorted); $j++) {
                if ($sorted[$p] == $ranksort[$j]) {
                    $rank[$p] = $j + 1;
                }
            }
        }
        for ($b = 0; $b < count($student); $b++) {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($b + 1, $defsize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
            $str .= $this->reporter->col($student[$b]->clientname, $namesize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $namesize . 'px;word-wrap;break-word;');
            $grades = $this->getcolval($mqry, 'distinct rcardtotal', 'and clientid=' . $student[$b]->clientid, '');
            for ($c = 0; $c < count($grades); $c++) {
                $str .= $this->reporter->col(number_format($grades[$c]->rcardtotal, 2), $defsize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
            }
            $str .= $this->reporter->col('', $defsize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
            $str .= $this->reporter->col($sorted[$b], $defsize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
            $str .= $this->reporter->col($rank[$b], $defsize, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $defsize . 'px;word-wrap;break-word;');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();


        return $str;
    }



    public function header_DEFAULT($config, $layoutsize)
    {
        $str = '';
        $font =  "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $cline  = $config['params']['dataparams']['coursename'];

        $yr  = $config['params']['dataparams']['yr'];
        $section  = $config['params']['dataparams']['section'];
        $sy  = $config['params']['dataparams']['sy'];


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Course: ' . $cline, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Year: ' . $yr, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('School Year: ' . $sy, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Section: ' . $section, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Summary of Grades Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        return $str;
    }
}
