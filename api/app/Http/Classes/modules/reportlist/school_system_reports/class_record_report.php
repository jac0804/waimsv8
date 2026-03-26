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

class class_record_report
{
    public $modulename = 'Class Record Report';
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
        $fields = ['radioprint', 'sy', 'course', 'yr', 'section', 'subject', 'quartercode', 'optionstatus'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'sy.lookupclass', 'report');
        data_set($col1, 'yr.lookupclass', 'report');
        data_set($col1, 'section.lookupclass', 'lookupsection2');
        data_set($col1, 'optionstatus.options', [
            ['label' => 'Posted', 'value' => 0, 'color' => 'red'],
            ['label' => 'Unposted', 'value' => 1, 'color' => 'green'],
            ['label' => 'All', 'value' => 2, 'color' => 'blue']
        ]);

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
    '' as subjectcode,'' as subject, '0' as subjtrno,
    '' as quartercode,'' as quarterid,'' as quartername,
    2 as status
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
        $subject  = $config['params']['dataparams']['subjtrno'];
        $qline  = $config['params']['dataparams']['quarterid'];
        $status = $config['params']['dataparams']['status'];

        $filter = "";
        if ($cline != "") $filter .= " and c.line = '$cline' ";
        if ($yr != "") $filter .= " and head.yr = '$yr' ";
        if ($sy != "") $filter .= " and sy.line= '$sy'";
        if ($section != "") $filter .= " and sec.line = '$section' ";
        if ($subject != "" && $subject != 0) $filter .= " and sub.trno = '$subject' ";
        if ($qline != "") $filter .= " and q.line = '$qline' ";

        switch ($status) {
            case 0:
            case 1:
                $table = 'en_glhead';
                $stable = 'en_glgrades';
                $table2 = 'en_glsubcomponent';
                if ($status == 1) $table = 'en_gehead'; $stable = 'en_gegrades'; $table2 = 'en_gesubcomponent';
                $query = "select client.clientname, g.gccode, g.gcsubcode, c.coursecode, sec.section, gs.quarterid, gcg.scoregrade,
                    gcg.totalgrade, gcg.percentgrade, gc.line as gcline, gc.gcname, gssub.line as gssubline, gssub.topic, g.noofitems,
                    g.points, g.clientid, gssub.noofitems as subpercent, gc.gcpercent, head.subjectid, sub.subjectcode, sub.subjectname,
                    sy.sy, c.coursename, q.name as quartername, info.gender, gssub.trno as gssubtrno, gssub.compid as gssubcompid,
                    gc.trno as gctrno, gq.tentativetotal,gq.finaltotal
                    from ".$table." as head left join ".$stable." as g on head.trno =g.trno
                    left join client on client.clientid=g.clientid
                    left join en_studentinfo as info on info.clientid=client.clientid
                    left join ".$table2." as gs on gs.trno=g.trno and gs.line=g.gsline
                    left join en_gssubcomponent as gssub on gssub.trno=gs.getrno and gs.gccode=gssub.gcsubcode
                    left join en_gscomponent as gc on gssub.trno=gc.trno and gc.line=gssub.compid
                    left join en_subject as sub on sub.trno=head.subjectid
                    left join en_gecomponentgrade as gcg on gcg.trno=head.trno and gcg.clientid=g.clientid and g.gccode=gcg.componentcode and gcg.quarterid=gs.quarterid
                    left join en_schoolyear as sy on sy.line=head.syid
                    left join en_section as sec on sec.line=head.sectionid
                    left join en_course as c on c.line=head.courseid
                    left join en_quartersetup as q on q.line=gs.quarterid
                    left join en_gequartergrade as gq on gq.trno=head.trno
                        and gq.quarterid=gs.quarterid
                        and gq.schedtrno=gcg.schedtrno
                        and gq.schedline=gcg.schedline
                        and g.clientid=gq.clientid
                        and gcg.isconduct=gq.isconduct
                    where head.doc='EH' $filter";
                break;
            default:
                $query = "select client.clientname, g.gccode, g.gcsubcode, c.coursecode, sec.section, gs.quarterid, gcg.scoregrade,
                    gcg.totalgrade, gcg.percentgrade, gc.line as gcline, gc.gcname, gssub.line as gssubline, gssub.topic, g.noofitems,
                    g.points, g.clientid, gssub.noofitems as subpercent, gc.gcpercent, head.subjectid, sub.subjectcode, sub.subjectname,
                    sy.sy, c.coursename, q.name as quartername, info.gender, gssub.trno as gssubtrno, gssub.compid as gssubcompid,
                    gc.trno as gctrno, gq.tentativetotal,gq.finaltotal
                    from en_gehead as head left join en_gegrades as g on head.trno =g.trno
                    left join client on client.clientid=g.clientid
                    left join en_studentinfo as info on info.clientid=client.clientid
                    left join en_gesubcomponent as gs on gs.trno=g.trno and gs.line=g.gsline
                    left join en_gssubcomponent as gssub on gssub.trno=gs.getrno and gs.gccode=gssub.gcsubcode
                    left join en_gscomponent as gc on gssub.trno=gc.trno and gc.line=gssub.compid
                    left join en_subject as sub on sub.trno=head.subjectid
                    left join en_gecomponentgrade as gcg on gcg.trno=head.trno and gcg.clientid=g.clientid and g.gccode=gcg.componentcode and gcg.quarterid=gs.quarterid
                    left join en_schoolyear as sy on sy.line=head.syid
                    left join en_section as sec on sec.line=head.sectionid
                    left join en_course as c on c.line=head.courseid
                    left join en_quartersetup as q on q.line=gs.quarterid
                    left join en_gequartergrade as gq on gq.trno=head.trno
                        and gq.quarterid=gs.quarterid
                        and gq.schedtrno=gcg.schedtrno
                        and gq.schedline=gcg.schedline
                        and g.clientid=gq.clientid
                        and gcg.isconduct=gq.isconduct
                    where head.doc='EH' $filter
                    union all
                    select client.clientname, g.gccode, g.gcsubcode, c.coursecode, sec.section, gs.quarterid, gcg.scoregrade,
                    gcg.totalgrade, gcg.percentgrade, gc.line as gcline, gc.gcname, gssub.line as gssubline, gssub.topic, g.noofitems,
                    g.points, g.clientid, gssub.noofitems as subpercent, gc.gcpercent, head.subjectid, sub.subjectcode, sub.subjectname,
                    sy.sy, c.coursename, q.name as quartername, info.gender, gssub.trno as gssubtrno, gssub.compid as gssubcompid,
                    gc.trno as gctrno, gq.tentativetotal,gq.finaltotal
                    from en_glhead as head left join en_glgrades as g on head.trno =g.trno
                    left join client on client.clientid=g.clientid
                    left join en_studentinfo as info on info.clientid=client.clientid
                    left join en_glsubcomponent as gs on gs.trno=g.trno and gs.line=g.gsline
                    left join en_gssubcomponent as gssub on gssub.trno=gs.getrno and gs.gccode=gssub.gcsubcode
                    left join en_gscomponent as gc on gssub.trno=gc.trno and gc.line=gssub.compid
                    left join en_subject as sub on sub.trno=head.subjectid
                    left join en_gecomponentgrade as gcg on gcg.trno=head.trno and gcg.clientid=g.clientid and g.gccode=gcg.componentcode and gcg.quarterid=gs.quarterid
                    left join en_schoolyear as sy on sy.line=head.syid
                    left join en_section as sec on sec.line=head.sectionid
                    left join en_course as c on c.line=head.courseid
                    left join en_quartersetup as q on q.line=gs.quarterid
                    left join en_gequartergrade as gq on gq.trno=head.trno
                        and gq.quarterid=gs.quarterid
                        and gq.schedtrno=gcg.schedtrno
                        and gq.schedline=gcg.schedline
                        and g.clientid=gq.clientid
                        and gcg.isconduct=gq.isconduct
                    where head.doc='EH' $filter";
                break;
        }


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
            $orderby = " order by gcline,topic,gcsubcode";
        }

        return $this->coreFunctions->opentable("select $field from ($mqry) as x where 1=1 $filter $orderby");
    }

    public function getLayoutsize($mqry, $component, $w)
    {
        $counts = 0;
        $tcounts = 0;
        for ($a = 0; $a < count($component); $a++) {
            $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$a]->gcname . '"', '');
            $counts = 0;
            for ($c = 0; $c < count($topic); $c++) {
                $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
                $counts += ($scope2[0]->count + 1);
            }
            $tcounts += ($counts + 1);
        }
        return ($tcounts * $w) + 400;
    }

    public function reportDefaultLayout($config)
    {
        $mqry = $this->reportDefault($config);
        $data = $this->coreFunctions->opentable($mqry);

        $componentcount = $this->count($mqry, 'gcname', '');
        $topiccount = $this->count($mqry, 'topic', '');
        $codecount = $this->count($mqry, 'gcsubcode', '');

        $component = $this->getcolval($mqry, 'distinct gcname, gcpercent', '', '');

        $test = [];

        $header = $this->getheader($mqry);
        $count = 38;
        $page = 40;

        $str = '';
        $w = 70;

        $layoutsize = $this->getLayoutsize($mqry, $component, $w);
        $lsize = 1200;
        $font =  "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config, $layoutsize);

        $i = 0;

        $grade = '';

        $code = $this->getcolval($mqry, 'distinct gcsubcode', '', '');

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($config['params']['dataparams']['quartername'], 1200, null, false, $border, 'TLR', 'L', $font, $fontsize, 'B', '', '', '', 0, 'max-width:200px;word-wrap;break-word;',0,8);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("LEARNER'S NAME", 200, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
        for ($a = 0; $a < count($component); $a++) {
            $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '"');
            $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$a]->gcname . '"', '');
            $counts = 0;
            for ($c = 0; $c < count($topic); $c++) {
                $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
                $counts += ($scope2[0]->count);
            }
            $cwidth = ($counts + 3) * $w;
            if ($counts != 0) {
                $str .= $this->reporter->col($component[$a]->gcname.' ('.(int)$component[$a]->gcpercent.'%)', $cwidth, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$cwidth.'px;max-width:' . $cwidth . 'px;word-wrap;break-word;');
            }
        }
        $str .= $this->reporter->col('', 100, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap;break-word;');
        $str .= $this->reporter->col('', 100, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap;break-word;');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $subpercent = 0;
        $fieldtotal = 0;
        $subsize = 0;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("", 200, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
        for ($b = 0; $b < count($component); $b++) {
            // Get the # of Topics
            $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$b]->gcname . '"');
            // Formula based on the # of topics to get the size
            $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
            // Formula to get the size of a column per # of topics
            if ($scope[0]->count != 0) {
                $fieldwidth = ($fieldsize / ($scope[0]->count));
            } else {
                $fieldwith = $fieldsize;
            }

            $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$b]->gcname . '"', '');
            for ($c = 0; $c < count($topic); $c++) {
                // Get the # of distinct topics
                $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$b]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
                // Formula to get the size of 1 topic divided by the # of code it has + 1 for total
                $fieldsize2 = $fieldsize / ((count($topic) * 2) + 1);
                // Formula for topicsize display less the total
                $topicsize = ($fieldsize2 * $scope2[0]->count);
                $subsize = ($fieldsize2 / ($scope2[0]->count + 1));
                // $str .= $this->reporter->col($topic[$c]->topic, ($w * $scope2[0]->count), null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . ($w * $scope2[0]->count) . 'px;word-wrap:break-word;');
                $fsize = $w * $scope2[0]->count;
                $str .= $this->reporter->col('', $fsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$fsize.'px;max-width:'.$fsize.'px !important;word-wrap:break-word;');
                // $str .= $this->reporter->col($topic[$c]->subpercent, $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'max-width:' . $w . 'px;word-wrap:break-word;');
                $subpercent += $topic[$c]->subpercent;
                $fieldtotal += $topicsize + $subsize;
            }
            if ($b <= count($component) - 1) {
                if (count($topic) > 0) {
                    $str .= $this->reporter->col('Total', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                    $str .= $this->reporter->col('PS', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                    $str .= $this->reporter->col('WS', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                    $subpercent = 0;
                    $fieldtotal = 0;
                }
            }
        }
        $str .= $this->reporter->col('Initial', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        $str .= $this->reporter->col('Quarter', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', 200, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
        // for ($d = 0; $d < count($component); $d++) {
        //     $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$d]->gcname . '"');
        //     $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
        //     $topic = $this->getcolval($mqry, 'distinct topic', ' and gcname= "' . $component[$d]->gcname . '"', '');
        //     for ($e = 0; $e < count($topic); $e++) {
        //         $code = $this->getcolval($mqry, 'distinct topic,gcsubcode', ' and gcname= "' . $component[$d]->gcname . '" and topic= "' . $topic[$e]->topic . '"', '');

        //         for ($f = 0; $f < count($code); $f++) {
        //             $str .= $this->reporter->col($code[$f]->gcsubcode, $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
        //         }

        //         $str .= $this->reporter->col('Total', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
        //     }
        //     if ($d <= count($component) - 1) {
        //         $str .= $this->reporter->col('TOTAL', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
        //     }
        // }
        // $str .= $this->reporter->col('Grade', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        // $str .= $this->reporter->col('Grade', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        // $str .= $this->reporter->col('70% + 30', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        // $str .= $this->reporter->col('Grade', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);



        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('HIGHEST POSSIBLE SCORE', 200, null, false, $border, 'LTBR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
        for ($h = 0; $h < count($component); $h++) {
            $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$h]->gcname . '"');
            $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
            $topic = $this->getcolval($mqry, 'distinct topic', ' and gcname= "' . $component[$h]->gcname . '"', '');
            $totnum = 0;
            for ($i = 0; $i < count($topic); $i++) {
                $noofitems = $this->getcolval($mqry, 'distinct noofitems,totalgrade,gcsubcode', ' and gcname= "' . $component[$h]->gcname . '" and topic= "' . $topic[$i]->topic . '"', '');
                for ($j = 0; $j < count($noofitems); $j++) {
                    $str .= $this->reporter->col($noofitems[$j]->noofitems, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                    $totnum += $noofitems[$j]->noofitems;
                }
                // $str .= $this->reporter->col($noofitems[0]->totalgrade.'-', $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                if ($i == count($topic) - 1) {
                    $str .= $this->reporter->col($totnum, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                    $str .= $this->reporter->col('100', $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                    $str .= $this->reporter->col((int)$component[$h]->gcpercent.'%', $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                }
            }
        }

        $str .= $this->reporter->col('Grade', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        $str .= $this->reporter->col('Grade', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $pastrec = '';
        $counter = 0;
        $str .= $this->reporter->begintable($layoutsize);


        $client = $this->getcolval($mqry, 'distinct clientname,clientid,gender,tentativetotal,finaltotal', ' and gccode !="CG"', ' order by gender,gcline,topic,gcsubcode');
        for ($g = 0; $g < count($client); $g++) {
            if ($pastrec == '' || $pastrec != $client[$g]->gender) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($client[$g]->gender, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $pastrec = $client[$g]->gender;
                $counter = 0;
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($counter + 1, 50, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:50px;max-width:50px;word-wrap:break-word;');
            $counter++;
            $str .= $this->reporter->col($client[$g]->clientname, 150, null, false, $border, 'BTLR', 'C', $font, $fontsize, '','', '', '', 0, 'min-width:150px;max-width:150px;word-wrap:break-word;');
            $initgrade = 0;
            for ($k = 0; $k < count($component); $k++) {
                $gpercent = 0;
                $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$k]->gcname . '"');
                $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
                $topic = $this->getcolval($mqry, 'distinct topic', ' and gcname= "' . $component[$k]->gcname . '"', '');
                $totpoints = 0;
                $totnum = 0;
                $psnum = 0;
                $wsnum = 0;
                for ($l = 0; $l < count($topic); $l++) {
                    $noofitems = $this->getcolval($mqry, 'distinct noofitems,totalgrade,gcsubcode', ' and gcname= "' . $component[$k]->gcname . '" and topic= "' . $topic[$l]->topic . '"', '');
                    for ($j = 0; $j < count($noofitems); $j++) {
                        $totnum += $noofitems[$j]->noofitems;
                    }
                    $points = $this->getcolval($mqry, 'points,percentgrade', ' and gcname= "' . $component[$k]->gcname . '" and topic= "' . $topic[$l]->topic . '" and clientid=' . $client[$g]->clientid . '', '');

                    for ($m = 0; $m < count($points); $m++) {
                        $str .= $this->reporter->col($points[$m]->points, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, '','', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                        $totpoints += $points[$m]->points;
                    }

                    $gpercent += $points[0]->percentgrade;
                }
                $psnum = number_format(($totpoints/$totnum)*100,2);
                $wsnum = number_format($psnum * ($component[$k]->gcpercent/100),2);
                $initgrade += $wsnum;
                $str .= $this->reporter->col($totpoints, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                $str .= $this->reporter->col($psnum, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                $str .= $this->reporter->col($wsnum, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
            }
            $qGrade = $this->coreFunctions->datareader("select equivalent as value from en_gradeequivalent where '".$initgrade."' between range1 and range2");
            $str .= $this->reporter->col($initgrade, 100, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
            $str .= $this->reporter->col($qGrade, 100, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        }

        $str .= $this->reporter->endrow();
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
        $sy  = $config['params']['dataparams']['sy'];
        $subject  = $config['params']['dataparams']['subjectcode'] . ' - ' . $config['params']['dataparams']['subject'];
        $section  = $config['params']['dataparams']['section'];


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Course: ' . $cline, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Year: ' . $yr, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('School Year: ' . $sy, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Subject Code and Name: ' . $subject, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Section: ' . $section, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Class Record Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();



        return $str;
    }
}
