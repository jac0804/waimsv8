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

class summary_of_quarterly_average_report
{
  public $modulename = 'Summary of Quarterly Average Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    $fields = ['radioprint', 'sy', 'course', 'yr'];
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
    '' as yr
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

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $quarter = 0;
    $studname = '';
    $studdata = [];
    $studsorted = [];
    $g1 = 0;
    $g2 = 0;
    $g3 = 0;
    $g4 = 0;
    $gav = 0;

    foreach ($result as $skey => $sdata) {
      foreach ($sdata as $r => $c) {
        switch ($c['quarterid']) {
          case 1:
            $studdata[$c['quarterid'] . '-' . $c['studentname']] = ['q1' => $c['rcardtotal'], 'q2' => 0, 'q3' => 0, 'q4' => 0, 'name' => $c['studentname'], 'coursename' => $c['coursename'], 'section' => $c['section'], 'quarterid' => $c['quarterid']];
            break;
          case 2:
            $studdata[$c['quarterid'] . '-' . $c['studentname']] = ['q1' => 0, 'q2' => $c['rcardtotal'], 'q3' => 0, 'q4' => 0, 'name' => $c['studentname'], 'coursename' => $c['coursename'], 'section' => $c['section'], 'quarterid' => $c['quarterid']];
            break;
          case 3:
            $studdata[$c['quarterid'] . '-' . $c['studentname']] = ['q1' => 0, 'q2' => 0, 'q3' => $c['rcardtotal'], 'q4' => 0, 'name' => $c['studentname'], 'coursename' => $c['coursename'], 'section' => $c['section'], 'quarterid' => $c['quarterid']];
            break;
          case 4:
            $studdata[$c['quarterid'] . '-' . $c['studentname']] = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => $c['rcardtotal'], 'name' => $c['studentname'], 'coursename' => $c['coursename'], 'section' => $c['section'], 'quarterid' => $c['quarterid']];
            break;
        }
      }
    }
    foreach ($studdata as $key => $data) {
      if ($studname != '' && $studname != $data['name']) {
        $gav = ($g1 + $g2 + $g3 + $g4) / 4;
        $studsorted[$studname] = ['q1' => $g1, 'q2' => $g2, 'q3' => $g3, 'q4' => $g4, 'avg' => $gav, 'name' => $data['name'], 'coursename' => $data['coursename'], 'section' => $data['section'], 'quarterid' => $data['quarterid'], 'grade' => $data['coursename'] . ' - ' . $data['section']];
        $g1 = 0;
        $g2 = 0;
        $g3 = 0;
        $g4 = 0;
        $gav = 0;
        $g1 += $data['q1'];
        $g2 += $data['q2'];
        $g3 += $data['q3'];
        $g4 += $data['q4'];
      } else {
        $g1 += $data['q1'];
        $g2 += $data['q2'];
        $g3 += $data['q3'];
        $g4 += $data['q4'];
      }
      $studname = $data['name'];
    }

    // $gav = ($g1 + $g2 + $g3 + $g4) / 4;
    // $studsorted[$studname] = ['q1' => $g1, 'q2' => $g2, 'q3' => $g3, 'q4' => $g4, 'avg' => $gav, 'name' => $data['name'], 'coursename' => $data['coursename'], 'section' => $data['section'], 'quarterid' => $data['quarterid'], 'grade' => $data['coursename'] . ' - ' . $data['section']];

    //For sorting Rank
    $ranksort = [];
    $sorted = [];
    $rank = [];

    foreach ($studsorted as $sorts) {
      $ranksort[] = $sorts['avg'];
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



    $cline  = $config['params']['dataparams']['courseid'];
    $yr  = $config['params']['dataparams']['yr'];
    $sy  = $config['params']['dataparams']['syid'];


    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config, $layoutsize);

    $i = 0;

    $grade = '';
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($studsorted as $key => $data) {

      if ($grade == '') {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No', '50', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['grade'], '250', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('1st', '75', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('2nd', '75', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('3rd', '75', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('4th', '75', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Average', '100', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rank', '100', null, false, $border, 'BTR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }

      if ($grade != '' && $grade != $data['grade']) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No', '50', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['grade'], '250', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('1st', '75', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('2nd', '75', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('3rd', '75', null, false, $border, 'Rb', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('4th', '75', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Average', '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rank', '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($i + 1, '100', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($key, '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['q1'], '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['q2'], '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['q3'], '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data['q4'], '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($data['avg'], 2), '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($rank[$i], '100', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }
      $i++;
      $grade = $data['grade'];
    }


    return $str;
  }

  public function reportDefault($config)
  {

    $cline  = $config['params']['dataparams']['courseid'];
    $yr  = $config['params']['dataparams']['yr'];
    $sy  = $config['params']['dataparams']['syid'];

    $data2 = [];
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

    $query = "select c.coursename,sec.section,q.name as quartername,client.clientname,client.client,qg.quarterid,
    avg(qg.rcardtotal) as rcardtotal,
    q.code as quartercode, qg.clientid,
    head.syid,head.sectionid,sy.sy,c.coursecode,head.yr
    from en_gequartergrade as qg
    left join client on client.clientid=qg.clientid
    left join en_subject on en_subject.trno=qg.subjectid
    left join en_quartersetup as q on q.line=qg.quarterid
    left join en_glsubject as sub on sub.trno=qg.schedtrno and sub.line=qg.schedline
    left join en_rcdetail as rc on rc.trno=sub.rctrno and rc.line=sub.rcline
    left join en_glhead as head on head.trno=qg.schedtrno
    left join en_schoolyear as sy on sy.line=head.syid
    left join en_section as sec on sec.line=head.sectionid
    left join en_course as c on c.line=head.courseid
    where en_subject.ischinese=0 $filter
    group by head.yr,qg.quarterid,q.name,q.code,qg.clientid,
    head.syid,head.sectionid,sy.sy,sec.section,c.coursecode,c.coursename,client.clientname,client.client
    order by coursename,section,clientname,quartername
    ";
    $data = $this->coreFunctions->opentable($query);

    foreach ($data as $d) {
      $data2[][] = ['header' => $d->coursename . ' - ' . $d->section, 'studentname' => $d->clientname, 'coursename' => $d->coursename, 'section' => $d->section, 'quarterid' => $d->quarterid, 'rcardtotal' => $d->rcardtotal, 'clientid' => $d->clientid, 'syid' => $d->syid, 'sectionid' => $d->sectionid, 'sy' => $d->sy, 'coursecode' => $d->coursecode, 'yr' => $d->yr];
    }

    return $data2;
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
    $str .= $this->reporter->col('Summary of Quarterly Average Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }
}
