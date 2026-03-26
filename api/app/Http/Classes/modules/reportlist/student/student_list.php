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

class student_list
{
  public $modulename = 'Student List';
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
    $fields = ['radioprint', 'course', 'section', 'sy'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'section.addedparams', ['courseid']);
    data_set($col1, 'section.required', false);
    data_set($col1, 'sy.required', false);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as course,
    '' as courseid,
    '' as section,
    '' as sectionid,
    '' as sy
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
    return $this->reportDefaultLayout($config);
  }

  public function getSectionlist($config, $courseid)
  {
    $sectionid = isset($config['params']['dataparams']['sectionid']) ? $config['params']['dataparams']['sectionid'] : '';
    $sy = isset($config['params']['dataparams']['sy']) ? $config['params']['dataparams']['sy'] : '';
    $filter = '';
    if ($sectionid != '') $filter = " and s.sectionid=".$sectionid;
    if ($sy != '') $filter .= " and ss.sy='".$sy."'";
    $query = "select distinct sec.section, s.sectionid
      from en_studentinfo as s
      left join en_glhead as es on es.trno=s.schedtrno
      left join en_schoolyear as ss on ss.line=es.syid
      left join en_section as sec on sec.line=s.sectionid
      where s.courseid=".$courseid." ".$filter." order by sec.section";
    return $this->coreFunctions->opentable($query);
  }

  public function getCourselist($config)
  {
    $courseid = isset($config['params']['dataparams']['courseid']) ? $config['params']['dataparams']['courseid'] : '';
    $sectionid = isset($config['params']['dataparams']['sectionid']) ? $config['params']['dataparams']['sectionid'] : '';
    $sy = isset($config['params']['dataparams']['sy']) ? $config['params']['dataparams']['sy'] : '';

    $filter = 'where 1=1';
    if ($courseid != '') $filter .= " and s.courseid=".$courseid;
    if ($sectionid != '') $filter .= " and s.sectionid=".$sectionid;
    if ($sy != '') $filter .= " and ss.sy='".$sy."'";
    $query = "select distinct course.coursename, s.courseid
      from en_studentinfo as s
      left join en_glhead as es on es.trno=s.schedtrno
      left join en_course as course on course.line=s.courseid
      left join en_section as sec on sec.line=s.sectionid
      left join en_schoolyear as ss on ss.line=es.syid ".$filter." order by course.coursename";
    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault($config)
  {
    $courseid = isset($config['params']['dataparams']['courseid']) ? $config['params']['dataparams']['courseid'] : '';
    $sectionid = isset($config['params']['dataparams']['sectionid']) ? $config['params']['dataparams']['sectionid'] : '';
    $sy = isset($config['params']['dataparams']['sy']) ? $config['params']['dataparams']['sy'] : '';

    $filter   = "where 1=1";
    if ($courseid != '') $filter .= " and s.courseid=".$courseid;
    if ($sectionid != '') $filter .= " and s.sectionid=".$sectionid;
    if ($sy != '') $filter .= " and ss.sy='".$sy."'";
    $query = "select c.clientname, s.studentid, s.chinesename, s.gender, date(c.bday) as bday, course.coursename, sec.section, s.courseid, s.sectionid, ss.sy
      from en_studentinfo as s
      left join client as c on c.clientid=s.clientid
      left join en_course as course on course.line=s.courseid
      left join en_glhead as es on es.trno=s.schedtrno
      left join en_schoolyear as ss on ss.line=es.syid
      left join en_section as sec on sec.line=s.sectionid ".$filter." order by course.coursename, sec.section, c.clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config, $recordCount)
  {
    $course = isset($config['params']['dataparams']['coursename']) ? $config['params']['dataparams']['coursename'] : '';
    $section = isset($config['params']['dataparams']['section']) ? $config['params']['dataparams']['section'] : '';
    $sy = isset($config['params']['dataparams']['sy']) ? $config['params']['dataparams']['sy'] : '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    if ($course == "") $course = "ALL COURSE";

    $str = '';
    $layoutsize = '1000';
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
    $str .= $this->reporter->col('STUDENT LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Course : ' . strtoupper($course), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Section : ' . ($section == '' ? 'ALL SECTION' : $section), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('School Year : ' . ($sy == '' ? 'ALL SCHOOL YEAR' : $sy), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STUDENT ID', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAME', '325', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CHINESE NAME', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GENDER', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BIRTHDAY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $courselist = $this->getCourselist($config);

    $count = 50;
    $page = 50;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, count($result));

    $course = $section = '';
    $studcount = 0;

    if (!empty($courselist)) {
      foreach ($courselist as $cl) {
        $coursetotal = 0;
        $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($cl->coursename), '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $sectionlist = $this->getSectionlist($config, $cl->courseid);
        if (!empty($sectionlist)) {
          foreach ($sectionlist as $sl) {
            $studentcount = 0;
            $str .= $this->reporter->begintable($layoutsize);
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("&nbsp;&nbsp;&nbsp;SECTION: ".($sl->section == '' ? 'NO SECTION' : $sl->section), '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $studentcount = 0;
            foreach ($result as $key => $data) {
              if ($data->courseid == $cl->courseid && $data->sectionid == $sl->sectionid) {
                $studentcount++;
                $coursetotal++;
                $str .= $this->reporter->begintable($layoutsize);
                  $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$data->studentid, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->clientname, '325', null, false, $border, '', '', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->chinesename, '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->gender, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->bday, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
              }
            }
            if ($studentcount > 0) {
              $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col('', '875', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
                  $str .= $this->reporter->col('Student Count: '.$studentcount, '125', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
              $str .= $this->reporter->endtable();
            }
          } // end sectionlist
        }
        if ($coursetotal > 0) {
          $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '875', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('Total Count: '.$coursetotal, '125', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
      } // end courselist
    }


    // foreach ($result as $key => $data) {
    //   if (($course != '' && $course != strtoupper($data->coursename))) {
    //     studentCount:
    //     $str .= $this->reporter->begintable($layoutsize);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('', '125', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '325', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '300', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '125', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('Student Count: ' . $studcount, '125', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //     $studcount = 0;
    //     $course = $section = '';
    //   }

    //   $studcount += 1;

    //   if ($course == '' && $course != strtoupper($data->coursename)) {
    //     if ($course != '' && $course != strtoupper($data->coursename)) {
    //       goto studentCount;
    //     }
    //     $str .= $this->reporter->begintable($layoutsize);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col(strtoupper($data->coursename).'waw', '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //   }
    //   if ($section == '' && $section != $data->section) {
    //     if ($section != '' && $section != $data->section) {
    //       goto studentCount;
    //     }
    //     $str .= $this->reporter->begintable($layoutsize);
    //       $str .= $this->reporter->startrow();
    //         $str .= $this->reporter->col("&nbsp&nbsp;".strtoupper('section '.$data->section).'wew', '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    //       $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //   }
    //   $str .= $this->reporter->begintable($layoutsize);
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->addline();
    //   $str .= $this->reporter->col("&nbsp&nbsp;&nbsp;&nbsp;".$data->studentid, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col($data->clientname, '325', null, false, $border, '', '', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col($data->chinesename, '300', null, false, $border, '', '', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col($data->gender, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->col($data->bday.'-'.$data->section, '125', null, false, $border, '', '', $font, $fontsize, '', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();

    //   $course = strtoupper($data->coursename);
    //   $section = $data->section;

    //   if ($key == count($result) - 1) {
    //     $str .= $this->reporter->begintable($layoutsize);
    //     $str .= $this->reporter->startrow();
    //     $str .= $this->reporter->col('', '125', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '325', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '300', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('', '125', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
    //     $str .= $this->reporter->col('Student Count: ' . $studcount, '125', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    //     $str .= $this->reporter->endrow();
    //     $str .= $this->reporter->endtable();
    //   }
    // }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class