<?php

namespace App\Http\Classes\modules\modulereport\main;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class ek
{

  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'approved', 'ehstudentlookup', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'ehstudentlookup.label', 'Student');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as approved,
      '' as clientid,
      '' as ehstudentlookup
      "
    );
  }

  public function report_default_query($trno)
  {
    return [];
  } //end fn


  public function report_EK_headerpdf($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $this->modulename = app('App\Http\Classes\modules\sales\ai')->modulename;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(760, 20, 'Name: ' . $data->clientname, '', 'L');

    PDF::MultiCell(340, 20, '姓名: ' . $data->chinesename, '', 'L', false, 0);
    PDF::MultiCell(210, 20, 'Curriculum: Sample', '', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(210, 20, 'Grade: ' . $data->yr, '', 'L', false, 1);

    PDF::MultiCell(170, 20, 'Age: ' . $data->age, '', 'L', false, 0);
    PDF::MultiCell(170, 20, 'Sex: ' . $data->gender, '', 'L', false, 0);
    PDF::MultiCell(210, 20, 'School Year: ' . $data->sy, '', 'L', false, 0);
    PDF::MultiCell(210, 20, 'Chinese Grade: ', '', 'L', false, 1);

    PDF::MultiCell(760, 20, 'Address: ' . $data->haddr, '', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(0, 0, "");

    PDF::SetFont($font, 'B', '10');
    PDF::MultiCell(180, 26, 'SUBJECTS', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::MultiCell(43, 26, '1', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::MultiCell(43, 26, '2', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::MultiCell(43, 26, '3', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::MultiCell(43, 26, '4', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::Setfont($font, '', '10');
    PDF::MultiCell(43, 26, 'Final Ave.', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    PDF::MultiCell(43, 26, 'Action Taken', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');

    PDF::MultiCell(10, 26, '', 0, 'C', false, 1);

    // PDF::SetFont($font, 'B', '10');
    // PDF::MultiCell(127, 26, '科目', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    // PDF::MultiCell(36, 26, '一', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    // PDF::MultiCell(36, 26, '二', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    // PDF::MultiCell(36, 26, '三', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    // PDF::MultiCell(36, 26, '四', 1, 'C', false, 0, '', '', true, 0, false, true, 26, 'M');
    // PDF::SetFont($font, '', '10');
    // PDF::MultiCell(36, 26, '最終平均值', 1, 'C', false, 1, '', '', true, 0, false, true, 26, 'M');
  }

  public function reportplotting($params)
  {
    $fontsize11 = "11";
    $fontsize12 = "12";
    $font = 'kozminproregular';
    if ($params['params']['dataparams']['ehstudentlookup'] != '') {
      $students = $this->getStudents($params, $params['params']['dataparams']['clientid']);
    } else {
      $students = $this->getStudents($params);
    }
    $data = [];
    if (!empty($students)) {
      foreach ($students as $stud) {
        $this->report_EK_headerpdf($params, $stud, $font);

        // PRINT GRADES
        $this->printGrades2($params, $stud, $font);
        // PRINT GRADES

        // PRINT ATTENDANCE
        $waw = $this->printAttendance($params, $stud, $font);

        // PRINT ATTENDANCE

        // PRINT REMARKS
        $this->printRemarks($stud, $font);
        // PRINT REMARKS
      }
      $pdf = PDF::Output($this->modulename . '.pdf', 'S');
      return $pdf;
    }
  }

  public function printGrades2($params, $stud, $font)
  {
    $engdata = $this->getStudData($stud, 0);
    $avegrade = $rcardtotal = $edindex = 0;
    $gdata = ['subject' => '', 'Q1' => '', 'Q2' => '', 'Q3' => '', 'Q4' => '', 'average' => ''];
    if (!empty($engdata)) {
      foreach ($engdata as $ed) {
        $gdata['subject'] = $ed[0]['subjectcode'];
        $gdata[$ed[0]['quartercode']] = $ed[0]['rcardtotal'];
        $rcardtotal = $ed[0]['rcardtotal'] == '' ? 0 : $ed[0]['rcardtotal'];
        $avegrade += $rcardtotal;
        if ($edindex == (count($engdata) - 1)) {
          $gdata['average'] = $avegrade / 4;
        }
        $edindex += 1;
      }
      if ($gdata['Q1'] == '' || $gdata['Q2'] == '' || $gdata['Q3'] == '' || $gdata['Q4'] == '') $gdata['average'] = '';
      PDF::MultiCell(180, 20, ' '.$gdata['subject'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, ' '.$gdata['Q1'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, ' '.$gdata['Q2'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, ' '.$gdata['Q3'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, ' '.$gdata['Q4'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, ' '.$gdata['average'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      PDF::MultiCell(43, 20, '', 1, 'L', false, 1, '', '', true, 0, false, true, 20, 'M');
    }
  }

  public function printGrades($params, $stud, $font)
  {
    // GET STUDENT DATAS
    $engdata = $this->getStudData($stud, 0);
    $chidata = $this->getStudData($stud, 1);
    // GET STUDENT DATAS

    $isengletter = false;
    $ischiletter = false;
    $levelid = $this->coreFunctions->opentable("select levelid from en_srchead where trno=? union all select levelid from en_glhead where trno=?", [$params['params']['dataid'], $params['params']['dataid']]);
    if (!empty($levelid)) {
      $isengletter = $this->coreFunctions->getfieldvalue('en_levels', 'isenconvertgrade', 'line=?', [$levelid[0]->levelid]);
      $ischiletter = $this->coreFunctions->getfieldvalue('en_levels', 'ischiconvertgrade', 'line=?', [$levelid[0]->levelid]);
    }

    // CONSOLIDATE GRADES PER QUARTER
    $englistdata = [];
    $chinesedata = [];
    $engQ = ['first' => [], 'second' => [], 'third' => [], 'fourth' => []];
    $chiQ = ['first' => [], 'second' => [], 'third' => [], 'fourth' => []];
    $esubs = [];
    $csubs = [];
    $egrades = [];
    $cgrades = [];

    $allGrades = [];
    $allGradesC = [];
    // ENGLISH GRADES
    if (!empty($engdata)) {
      foreach ($engdata as $ekey => $edata) {
        foreach ($edata as $ed) {
          $englishdata[$ed['quartercode']][] = $ed;
        }
      }
      if (isset($englishdata['Q1'])) $engQ['first'] = $englishdata['Q1'];
      if (isset($englishdata['Q2'])) $engQ['second'] = $englishdata['Q2'];
      if (isset($englishdata['Q3'])) $engQ['third'] = $englishdata['Q3'];
      if (isset($englishdata['Q4'])) $engQ['fourth'] = $englishdata['Q4'];
      $esubs = array_merge($engQ['first'], $engQ['second'], $engQ['third'], $engQ['fourth']);
      if (!empty($esubs)) {
        foreach ($esubs as $sub) {
          $egrades[$sub['subjectcode']][$sub['subjectname']][$sub['quartercode']] = $sub['rcardtotal'];
        }
      }
    }
    if (!empty($egrades)) {
      $ecount = 0;
      foreach ($egrades as $key => $eg) {
        if (count($eg) > 0) {
          $parentSub = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
          $allGrades[$ecount] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 'subject' => $key];
          foreach ($eg as $egkey => $egg) {
            $totalG = 0;
            if (isset($egg['Q1'])) {
              $totalG += $egg['Q1'];
              $parentSub[1] += $egg['Q1'];
            }
            if (isset($egg['Q2'])) {
              $totalG += $egg['Q2'];
              $parentSub[2] += $egg['Q2'];
            }
            if (isset($egg['Q3'])) {
              $totalG += $egg['Q3'];
              $parentSub[3] += $egg['Q3'];
            }
            if (isset($egg['Q4'])) {
              $totalG += $egg['Q4'];
              $parentSub[4] += $egg['Q4'];
            }
            $egg['average'] = $totalG / 4;
            $egg['actiontaken'] = '';
            $ak = $this->coreFunctions->opentable("select actiontaken from en_gradeequivalent where '" . $egg['average'] . "' between range1 and range2 and gradeequivalent='' and chineseequivalent=''");
            if (!empty($ak)) $egg['actiontaken'] = $ak[0]->actiontaken;
            $egg['isparent'] = false;
            $egg['subject'] = $egkey;
            $allGrades[] = $egg;
          }
          $allGrades[$ecount][1] = $parentSub[1] / count($eg);
          $allGrades[$ecount][2] = $parentSub[2] / count($eg);
          $allGrades[$ecount][3] = $parentSub[3] / count($eg);
          $allGrades[$ecount][4] = $parentSub[4] / count($eg);
          $allGrades[$ecount]['average'] = ($allGrades[$ecount][1] + $allGrades[$ecount][2] + $allGrades[$ecount][3] + $allGrades[$ecount][4]) / 4;
          $allGrades[$ecount]['actiontaken'] = '';
          $ak = $this->coreFunctions->opentable("select actiontaken from en_gradeequivalent where '" . $allGrades[$ecount]['average'] . "' between range1 and range2 and gradeequivalent='' and chineseequivalent=''");
          if (!empty($ak)) $allGrades[$ecount]['actiontaken'] = $ak[0]->actiontaken;
          $allGrades[$ecount]['isparent'] = true;
        } else {
          foreach ($eg as $egkey => $egg) {
            $egg['isparent'] = true;
            $egg['subject'] = $key;
            $egg['average'] = ((isset($egg[1]) ? $egg[1] : 0) + (isset($egg[2]) ? $egg[2] : 0) + (isset($egg[3]) ? $egg[3] : 0) + (isset($egg[4]) ? $egg[4] : 0)) / 4;
            $egg['actiontaken'] = '';
            $ak = $this->coreFunctions->opentable("select actiontaken from en_gradeequivalent where '" . $egg['average'] . "' between range1 and range2 and gradeequivalent='' and chineseequivalent=''");
            if (!empty($ak)) $egg['actiontaken'] = $ak[0]->actiontaken;
            $allGrades[$ecount] = $egg;
          }
        }
        $ecount += 1;
      }
    }
    // ENGLISH GRADES

    // CHINESE GRADES
    if (!empty($chidata)) {
      foreach ($chidata as $ckey => $cdata) {
        foreach ($cdata as $cd) {
          $chinesedata[$cd['quartercode']][] = $cd;
        }
      }
      if (isset($chinesedata[1])) $chiQ['first'] = $chinesedata[1];
      if (isset($chinesedata[2])) $chiQ['second'] = $chinesedata[2];
      if (isset($chinesedata[3])) $chiQ['third'] = $chinesedata[3];
      if (isset($chinesedata[4])) $chiQ['fourth'] = $chinesedata[4];
      $csubs = array_merge($chiQ['first'], $chiQ['second'], $chiQ['third'], $chiQ['fourth']);
      if (!empty($csubs)) {
        foreach ($csubs as $sub2) {
          $cgrades[$sub2['subjectcode']][$sub2['subjectname']][$sub2['quartercode']] = $sub['rcardtotal'];
        }
      }
    }

    if (!empty($cgrades)) {
      $ccount = 0;
      foreach ($cgrades as $key => $cg) {
        if (count($cg) > 1) {
          $parentSub = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
          $allGradesC[$ccount] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 'subject' => $key];
          foreach ($cg as $cgkey => $cgg) {
            $totalG = 0;
            if (isset($cgg[1])) {
              $totalG += $cgg[1];
              $parentSub[1] += $cgg[1];
            }
            if (isset($cgg[2])) {
              $totalG += $cgg[2];
              $parentSub[2] += $cgg[2];
            }
            if (isset($cgg[3])) {
              $totalG += $cgg[3];
              $parentSub[3] += $cgg[3];
            }
            if (isset($cgg[4])) {
              $totalG += $cgg[4];
              $parentSub[4] += $cgg[4];
            }
            $cgg['average'] = $totalG / 4;
            $cgg['isparent'] = false;
            $cgg['subject'] = $cgkey;
            $allGradesC[] = $cgg;
          }
          $allGradesC[$ccount][1] = $parentSub[1];
          $allGradesC[$ccount][2] = $parentSub[2];
          $allGradesC[$ccount][3] = $parentSub[3];
          $allGradesC[$ccount][4] = $parentSub[4];
          $allGradesC[$ccount]['average'] = ($parentSub[1] + $parentSub[2] + $parentSub[3] + $parentSub[4]) / 4;
          $allGradesC[$ccount]['isparent'] = true;
        } else {
          foreach ($cg as $cgkey => $cgg) {
            $cgg['isparent'] = true;
            $cgg['subject'] = $key;
            $cgg['average'] = ((isset($cgg[1]) ? $cgg[1] : 0) + (isset($cgg[2]) ? $cgg[2] : 0) + (isset($cgg[3]) ? $cgg[3] : 0) + (isset($cgg[4]) ? $cgg[4] : 0)) / 4;
            $allGradesC[$ccount] = $cgg;
          }
        }
        $ccount += 1;
      }
    }
    // CHINESE GRADES
    // CONSOLIDATE GRADES PER QUARTER

    $totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 'total' => 0];
    $totalc = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 'total' => 0];

    if (!empty($allGrades)) {
      $total1st = 0;
      $total2nd = 0;
      $total3rd = 0;
      $total4th = 0;
      $subcount = 0;
      // OVERALL AVERAGE COMPUTATION
      foreach ($allGrades as $gk => $g) {
        if ($g['isparent']) {
          $subcount += 1;
          if (isset($allGrades[$gk][1])) $total1st += $allGrades[$gk][1];
          if (isset($allGrades[$gk][2])) $total2nd += $allGrades[$gk][2];
          if (isset($allGrades[$gk][3])) $total3rd += $allGrades[$gk][3];
          if (isset($allGrades[$gk][4])) $total4th += $allGrades[$gk][4];
        }
      }
      $totals = [
        1 => $total1st / $subcount,
        2 => $total2nd / $subcount,
        3 => $total3rd / $subcount,
        4 => $total4th / $subcount,
        'total' => ($total1st + $total2nd + $total3rd + $total4th) / 4
      ];
      $totals[1] == 0 ? $totals[1] = '' : '';
      $totals[2] == 0 ? $totals[2] = '' : '';
      $totals[3] == 0 ? $totals[3] = '' : '';
      $totals[4] == 0 ? $totals[4] = '' : '';
      $totals['total'] == 0 ? $totals['total'] = '' : '';
      // OVERALL AVERAGE COMPUTATION
    }
    if (!empty($allGradesC)) {
      $total1st = 0;
      $total2nd = 0;
      $total3rd = 0;
      $total4th = 0;
      $subcount = 0;
      // OVERALL AVERAGE COMPUTATION
      foreach ($allGradesC as $gk => $g) {
        if ($g['isparent']) {
          $subcount += 1;
          if (isset($allGrades[$gk][1])) $total1st += $allGrades[$gk][1];
          if (isset($allGrades[$gk][2])) $total2nd += $allGrades[$gk][2];
          if (isset($allGrades[$gk][3])) $total3rd += $allGrades[$gk][3];
          if (isset($allGrades[$gk][4])) $total4th += $allGrades[$gk][4];
        }
      }
      $totalc = [
        1 => $total1st / $subcount,
        2 => $total2nd / $subcount,
        3 => $total3rd / $subcount,
        4 => $total4th / $subcount,
        'total' => ($total1st + $total2nd + $total3rd + $total4th) / 4
      ];
      $totalc[1] == 0 ? $totalc[1] = '' : '';
      $totalc[2] == 0 ? $totalc[2] = '' : '';
      $totalc[3] == 0 ? $totalc[3] = '' : '';
      $totalc[4] == 0 ? $totalc[4] = '' : '';
      $totalc['total'] == 0 ? $totalc['total'] = '' : '';
      // OVERALL AVERAGE COMPUTATION
    }

    // SANITIZE GRADES (REMOVE ZERO VALUES/ADD MISSING FIELDS)
    if (!empty($allGrades)) {
      foreach ($allGrades as $key => $g) {
        if (isset($g[1])) {
          if ($g[1] == 0) $allGrades[$key][1] = '';
        } else {
          $allGrades[$key][1] = '';
        }
        if (isset($g[2])) {
          if ($g[2] == 0) $allGrades[$key][2] = '';
        } else {
          $allGrades[$key][2] = '';
        }
        if (isset($g[3])) {
          if ($g[3] == 0) $allGrades[$key][3] = '';
        } else {
          $allGrades[$key][3] = '';
        }
        if (isset($g[4])) {
          if ($g[4] == 0) $allGrades[$key][4] = '';
        } else {
          $allGrades[$key][4] = '';
        }
      }
    }
    if (!empty($allGradesC)) {
      foreach ($allGradesC as $key => $g) {
        if (isset($g[1])) {
          if ($g[1] == 0) $allGradesC[$key][1] = '';
        } else {
          $allGradesC[$key][1] = '';
        }
        if (isset($g[2])) {
          if ($g[2] == 0) $allGradesC[$key][2] = '';
        } else {
          $allGradesC[$key][2] = '';
        }
        if (isset($g[3])) {
          if ($g[3] == 0) $allGradesC[$key][3] = '';
        } else {
          $allGradesC[$key][3] = '';
        }
        if (isset($g[4])) {
          if ($g[4] == 0) $allGradesC[$key][4] = '';
        } else {
          $allGradesC[$key][4] = '';
        }
      }
    }
    // SANITIZE GRADES (REMOVE ZERO VALUES/ADD MISSING FIELDS)

    $allSubjects = [];
    if (!empty($allGrades)) {
      foreach ($allGrades as $g) array_push($allSubjects, ['eng' => $g]);
    }
    if (!empty($allGradesC)) {
      foreach ($allGradesC as $ckey => $g2) {
        if (isset($allSubjects[$ckey])) {
          $allSubjects[$ckey]['chi'] = $g2;
        } else {
          array_push($allSubjects, ['chi' => $g2]);
        }
      }
    }

    $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    $fdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GFC'");
    $gdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GGC'");
    if ($rcdecimal == '') $rcdecimal = 0;
    if ($fdecimal == '') $fdecimal = 0;
    if ($gdecimal == '') $gdecimal = 0;

    // PRINT GRADES
    PDF::SetFont($font, '', '10');
    foreach ($allSubjects as $grade) {
      if (isset($grade['eng'])) {
        $addSpace = ' ';
        if (!$grade['eng']['isparent']) $addSpace = '      ';
        PDF::MultiCell(180, 20, $addSpace . '' . $grade['eng']['subject'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
        if ($grade['eng']['subject'] == 'CONDUCT GRADE') {
          $cgeng1 = $this->coreFunctions->datareader("select conductenglish as value from en_conductgrade  where '" . round($grade['eng'][1], $rcdecimal) . "' between lowgrade and highgrade");
          $cgeng2 = $this->coreFunctions->datareader("select conductenglish as value from en_conductgrade  where '" . round($grade['eng'][2], $rcdecimal) . "' between lowgrade and highgrade");
          $cgeng3 = $this->coreFunctions->datareader("select conductenglish as value from en_conductgrade  where '" . round($grade['eng'][3], $rcdecimal) . "' between lowgrade and highgrade");
          $cgeng4 = $this->coreFunctions->datareader("select conductenglish as value from en_conductgrade  where '" . round($grade['eng'][4], $rcdecimal) . "' between lowgrade and highgrade");
          PDF::MultiCell(43, 20, $grade['eng'][1] == '' ? '' : $cgeng1, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng'][2] == '' ? '' : $cgeng2, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng'][3] == '' ? '' : $cgeng3, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng'][4] == '' ? '' : $cgeng4, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng']['average'] == '' ? '' : round($grade['eng']['average'], $fdecimal), 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng']['actiontaken'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        } else {
          $q1eng = '';
          $q2eng = '';
          $q3eng = '';
          $q4eng = '';
          if ($isengletter == 1) {
            $q1eng = $this->coreFunctions->datareader("select gradeequivalent as value from en_gradeequivalent where '" . round($grade['eng'][1], $rcdecimal) . "' between range1 and range2 and gradeequivalent<>''");
            $q2eng = $this->coreFunctions->datareader("select gradeequivalent as value from en_gradeequivalent where '" . round($grade['eng'][2], $rcdecimal) . "' between range1 and range2 and gradeequivalent<>''");
            $q3eng = $this->coreFunctions->datareader("select gradeequivalent as value from en_gradeequivalent where '" . round($grade['eng'][3], $rcdecimal) . "' between range1 and range2 and gradeequivalent<>''");
            $q4eng = $this->coreFunctions->datareader("select gradeequivalent as value from en_gradeequivalent where '" . round($grade['eng'][4], $rcdecimal) . "' between range1 and range2 and gradeequivalent<>''");
          } else {
            $q1eng = round($grade['eng'][1], $rcdecimal);
            $q2eng = round($grade['eng'][2], $rcdecimal);
            $q3eng = round($grade['eng'][3], $rcdecimal);
            $q4eng = round($grade['eng'][4], $rcdecimal);
          }
          PDF::MultiCell(43, 20, $q1eng == '' ? '' : $q1eng, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $q2eng == '' ? '' : $q2eng, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $q3eng == '' ? '' : $q3eng, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $q4eng == '' ? '' : $q4eng, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng']['average'] == '' ? '' : round($grade['eng']['average'], $fdecimal), 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
          PDF::MultiCell(43, 20, $grade['eng']['actiontaken'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        }
      } else {
        PDF::MultiCell(180, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(43, 20, '', 1, 'L', false, 0);
      }
      PDF::MultiCell(10, 20, '', 0, 'C', false, 0);
      if (isset($grade['chi'])) {
        $addSpace = ' ';
        if (!$grade['chi']['isparent']) $addSpace = '      ';
        $q1chi = '';
        $q2chi = '';
        $q3chi = '';
        $q4chi = '';
        if ($ischiletter == 1) {
          $q1chi = $this->coreFunctions->datareader("select chineseequivalent as value from en_gradeequivalent where '" . round($grade['chi'][1], $rcdecimal) . "' between range1 and range2 and chineseequivalent<>''");
          $q2chi = $this->coreFunctions->datareader("select chineseequivalent as value from en_gradeequivalent where '" . round($grade['chi'][2], $rcdecimal) . "' between range1 and range2 and chineseequivalent<>''");
          $q3chi = $this->coreFunctions->datareader("select chineseequivalent as value from en_gradeequivalent where '" . round($grade['chi'][3], $rcdecimal) . "' between range1 and range2 and chineseequivalent<>''");
          $q4chi = $this->coreFunctions->datareader("select chineseequivalent as value from en_gradeequivalent where '" . round($grade['chi'][4], $rcdecimal) . "' between range1 and range2 and chineseequivalent<>''");
        } else {
          $q1chi = round($grade['chi'][1], $rcdecimal);
          $q2chi = round($grade['chi'][2], $rcdecimal);
          $q3chi = round($grade['chi'][3], $rcdecimal);
          $q4chi = round($grade['chi'][4], $rcdecimal);
        }

        PDF::MultiCell(127, 20, $addSpace . '' . $grade['chi']['subject'], 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
        PDF::MultiCell(36, 20, $q1chi == '' ? '' : $q1chi, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        PDF::MultiCell(36, 20, $q2chi == '' ? '' : $q2chi, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        PDF::MultiCell(36, 20, $q3chi == '' ? '' : $q3chi, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        PDF::MultiCell(36, 20, $q4chi == '' ? '' : $q4chi, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        PDF::MultiCell(36, 20, $grade['chi']['average'] == '' ? '' : round($grade['chi']['average'], $fdecimal), 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
      } else {
        PDF::MultiCell(127, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(36, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(36, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(36, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(36, 20, '', 1, 'L', false, 0);
        PDF::MultiCell(36, 20, '', 1, 'L', false, 1);
      }
    }
    // PRINT GRADES

    // PRINT AVERAGE
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, 'B', '10');
    PDF::MultiCell(180, 20, 'Average', 'T L B', 'R', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::SetFont($font, '', '10');
    PDF::MultiCell(43, 20, round($totals[1], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(43, 20, round($totals[2], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(43, 20, round($totals[3], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(43, 20, round($totals[4], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(43, 20, round($totals['total'], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(43, 20, '', 'T R B', 'C', false, 0);

    PDF::MultiCell(10, 26, '', 0, 'C', false, 0);

    PDF::SetFont($font, 'B', '10');
    PDF::MultiCell(127, 20, '最終評估', 'T L B', 'R', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::SetFont($font, '', '10');
    PDF::MultiCell(36, 20, round($totalc[1], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(36, 20, round($totalc[2], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(36, 20, round($totalc[3], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(36, 20, round($totalc[4], $gdecimal), 'T B', 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
    PDF::MultiCell(36, 20, round($totalc['total'], $gdecimal), 'T R B', 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
    // PRINT AVERAGE
  }

  public function printAttendance($params, $stud, $font)
  {
    $trno = $params['params']['dataid'];
    $qry = "select periodid, syid, yr, schedtrno, courseid, adviserid, sectionid, levelid from en_srchead where trno=?";
    $head = $this->coreFunctions->opentable($qry, [$trno]);

    $months = [];
    $dtotal = [];
    $dpresent = [];
    $dlate = [];
    if (!empty($head)) {
      $totalpresent = $totaltardy = $totalall = 0;
      $atsetup = $this->coreFunctions->opentable("select jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec`, totaldays, month(startmonth) as startmonth, month(endmonth) as endmonth from en_attendancesetup where syid=".$head[0]->syid." and levelid=".$head[0]->levelid);
      $attendance = $this->coreFunctions->opentable("select jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec` from en_glstudents where schedtrno=".$head[0]->schedtrno." and clientid=".$stud->clientid);
      $tardy = $this->coreFunctions->opentable("select tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec from en_glstudents where schedtrno=".$head[0]->schedtrno." and clientid=".$stud->clientid);
      $atmonths = [];
      $atsmonths = [];
      $tarmonths = [];
      $atsetupmonths = [];
      $attendance2 = [];
      $tardy2 = [];
      if (!empty($attendance)) {
        $attendance2 = json_decode(json_encode($attendance[0]), true);
        foreach ($attendance2 as $att) {
          array_push($atmonths, $att);
        }
      }
      if (!empty($tardy)) {
        $tardy2 = json_decode(json_encode($tardy[0]), true);
        foreach ($tardy2 as $tt) {
          array_push($tarmonths, $tt);
        }
      }
      if (!empty($atsetup)) {
        $atsetup2 = json_decode(json_encode($atsetup[0]), true);
        foreach ($atsetup2 as $ats) {
          array_push($atsmonths, $ats);
        }
      }
      if (!empty($atsetup)) {
        $months2 = $this->getMonths($atsetup[0]->startmonth, $atsetup[0]->endmonth);
        if (!empty($atmonths)) {
          foreach($months2 as $mk => $m) {
            switch ($mk) {
              case 1: $monthname = 'JAN'; break;
              case 2: $monthname = 'FEB'; break;
              case 3: $monthname = 'MAR'; break;
              case 4: $monthname = 'APR'; break;
              case 5: $monthname = 'MAY'; break;
              case 6: $monthname = 'JUN'; break;
              case 7: $monthname = 'JUL'; break;
              case 8: $monthname = 'AUG'; break;
              case 9: $monthname = 'SEP'; break;
              case 10: $monthname = 'OCT'; break;
              case 11: $monthname = 'NOV'; break;
              default: $monthname = 'DEC'; break;
            }
            $months2[$mk]['month'] = $monthname;
            $months2[$mk]['total'] = $atsmonths[$mk - 1];
            $months2[$mk]['present'] = $atmonths[$mk - 1];
            $months2[$mk]['tardy'] = $tarmonths[$mk - 1];
            $totalpresent += $months2[$mk]['present'];
            $totaltardy += $months2[$mk]['tardy'];
            $totalall += $months2[$mk]['total'];
          }
        }
      }
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($font, '', '9');
      PDF::MultiCell(80, 30, 'Months', 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      if (!empty($months2)) {
        foreach ($months2 as $m2) {
          PDF::MultiCell(23, 30, $m2['month'], 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
        }
        PDF::MultiCell(44, 30, 'TOTAL', 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
        PDF::MultiCell(2, 30, '', 0, 'C', false, 1);
        
        PDF::MultiCell(80, 20, '  Days of School', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
        foreach ($months2 as $m2) {
          PDF::MultiCell(23, 20, $m2['total'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        }
        PDF::MultiCell(44, 20, $totalall, 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');

        PDF::MultiCell(80, 20, '  Days Present', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
        foreach ($months2 as $m2) {
          PDF::MultiCell(23, 20, $m2['present'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        }
        PDF::MultiCell(44, 20, $totalpresent, 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');

        PDF::MultiCell(80, 20, '  Times Tardy', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
        foreach ($months2 as $m2) {
          PDF::MultiCell(23, 20, $m2['tardy'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
        }
        PDF::MultiCell(44, 20, $totaltardy, 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
      }



      // $atsetup = $this->coreFunctions->opentable("select `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`, `totaldays`, month(startmonth) as startmonth, month(endmonth) as endmonth from en_attendancesetup where syid=? and levelid=?", [$head[0]->syid, $head[0]->levelid]);
      // $atpresent = $this->coreFunctions->opentable("select `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`, `dayspresent` from en_srcattendance where trno=? and clientid=?", [$trno, $stud->clientid]);
      // $atlate = $this->coreFunctions->opentable("select `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`, `dayspresent` from en_srcattendance where trno=? and clientid=? and islate=1", [$trno, $stud->clientid]);
      // if (!empty($atsetup)) {
      //   $months = $this->getMonths($atsetup[0]->startmonth, $atsetup[0]->endmonth);
      //   if (!empty($months)) {
      //     foreach ($months as $mk => $m) {
      //       switch ($mk) {
      //         case 1:
      //           $months['eng'][] = 'JAN';
      //           $months['chi'][] = '一';
      //           $dtotal[$mk] = $atsetup[0]->jan;
      //           if (isset($atpresent[0]->jan)) {
      //             $dpresent[$mk] = $atpresent[0]->jan;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->jan)) {
      //             $dlate[$mk] = $atlate[0]->jan;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 2:
      //           $months['eng'][] = 'FEB';
      //           $months['chi'][] = '二';
      //           $dtotal[$mk] = $atsetup[0]->feb;
      //           if (isset($atpresent[0]->feb)) {
      //             $dpresent[$mk] = $atpresent[0]->feb;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->feb)) {
      //             $dlate[$mk] = $atlate[0]->feb;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 3:
      //           $months['eng'][] = 'MAR';
      //           $months['chi'][] = '三';
      //           $dtotal[$mk] = $atsetup[0]->mar;
      //           if (isset($atpresent[0]->mar)) {
      //             $dpresent[$mk] = $atpresent[0]->mar;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->mar)) {
      //             $dlate[$mk] = $atlate[0]->mar;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 4:
      //           $months['eng'][] = 'APR';
      //           $months['chi'][] = '四';
      //           $dtotal[$mk] = $atsetup[0]->apr;
      //           if (isset($atpresent[0]->apr)) {
      //             $dpresent[$mk] = $atpresent[0]->apr;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->apr)) {
      //             $dlate[$mk] = $atlate[0]->apr;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 5:
      //           $months['eng'][] = 'MAY';
      //           $months['chi'][] = '五';
      //           $dtotal[$mk] = $atsetup[0]->may;
      //           if (isset($atpresent[0]->may)) {
      //             $dpresent[$mk] = $atpresent[0]->may;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->may)) {
      //             $dlate[$mk] = $atlate[0]->may;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 6:
      //           $months['eng'][] = 'JUN';
      //           $months['chi'][] = '月';
      //           $dtotal[$mk] = $atsetup[0]->jun;
      //           if (isset($atpresent[0]->jun)) {
      //             $dpresent[$mk] = $atpresent[0]->jun;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->jun)) {
      //             $dlate[$mk] = $atlate[0]->jun;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 7:
      //           $months['eng'][] = 'JUL';
      //           $months['chi'][] = '七';
      //           $dtotal[$mk] = $atsetup[0]->jul;
      //           if (isset($atpresent[0]->jul)) {
      //             $dpresent[$mk] = $atpresent[0]->jul;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->jul)) {
      //             $dlate[$mk] = $atlate[0]->jul;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 8:
      //           $months['eng'][] = 'AUG';
      //           $months['chi'][] = '八';
      //           $dtotal[$mk] = $atsetup[0]->aug;
      //           if (isset($atpresent[0]->aug)) {
      //             $dpresent[$mk] = $atpresent[0]->aug;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->aug)) {
      //             $dlate[$mk] = $atlate[0]->aug;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 9:
      //           $months['eng'][] = 'SEP';
      //           $months['chi'][] = '九';
      //           $dtotal[$mk] = $atsetup[0]->sep;
      //           if (isset($atpresent[0]->sep)) {
      //             $dpresent[$mk] = $atpresent[0]->sep;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->sep)) {
      //             $dlate[$mk] = $atlate[0]->sep;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 10:
      //           $months['eng'][] = 'OCT';
      //           $months['chi'][] = '十';
      //           $dtotal[$mk] = $atsetup[0]->oct;
      //           if (isset($atpresent[0]->oct)) {
      //             $dpresent[$mk] = $atpresent[0]->oct;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->oct)) {
      //             $dlate[$mk] = $atlate[0]->oct;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 11:
      //           $months['eng'][] = 'NOV';
      //           $months['chi'][] = '十一';
      //           $dtotal[$mk] = $atsetup[0]->nov;
      //           if (isset($atpresent[0]->nov)) {
      //             $dpresent[$mk] = $atpresent[0]->nov;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->nov)) {
      //             $dlate[$mk] = $atlate[0]->nov;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //         case 12:
      //           $months['eng'][] = 'DEC';
      //           $months['chi'][] = '十二';
      //           $dtotal[$mk] = $atsetup[0]->dec;
      //           if (isset($atpresent[0]->dec)) {
      //             $dpresent[$mk] = $atpresent[0]->dec;
      //           } else {
      //             $dpresent[$mk] = 0;
      //           }
      //           if (isset($atlate[0]->dec)) {
      //             $dlate[$mk] = $atlate[0]->dec;
      //           } else {
      //             $dlate[$mk] = 0;
      //           }
      //           break;
      //       }
      //     }
      //     $dtotal['total'] = round($atsetup[0]->totaldays, 0);
      //     if (!empty($atpresent)) $dpresent['total'] = round($atpresent[0]->dayspresent, 0);
      //     if (isset($atlate[0]->dayspresent)) {
      //       $dlate['total'] = round($atlate[0]->dayspresent, 0);
      //     } else {
      //       $dlate['total'] = 0;
      //     }
      //   }
      //   // PRINT ATTENDANCE HEADER
      //   PDF::MultiCell(0, 0, "\n");
      //   PDF::SetFont($font, '', '9');
      //   PDF::MultiCell(80, 30, 'Months', 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      //   if (!empty($months)) {
      //     if (isset($months['eng'])) {
      //       foreach ($months['eng'] as $em) {
      //         PDF::MultiCell(23, 30, $em, 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      //       }
      //     }
      //     PDF::MultiCell(44, 30, 'TOTAL', 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      //     PDF::MultiCell(2, 30, '', 0, 'C', false, 1);
      //     // PDF::MultiCell(80, 30, '月', 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      //     // if (isset($months['chi'])) {
      //     //   foreach ($months['chi'] as $cm) {
      //     //     PDF::MultiCell(23, 30, $cm, 1, 'C', false, 0, '', '', true, 0, false, true, 30, 'M');
      //     //   }
      //     // }
      //     // PDF::MultiCell(44, 30, '总额', 1, 'C', false, 1, '', '', true, 0, false, true, 30, 'M');
      //   }
      //   // PRINT ATTENDANCE HEADER
      //   // PRINT ATTENDANCE
      //   // TOTAL
      //   PDF::MultiCell(80, 20, '  Days of School', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   if (!empty($dtotal)) {
      //     foreach ($dtotal as $dtk => $dt) {
      //       if ($dtk != 'total') PDF::MultiCell(23, 20, $dt, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //     }
      //     PDF::MultiCell(44, 20, $dtotal['total'], 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   }
      //   PDF::MultiCell(2, 20, '', 0, 'C', false, 1);
      //   // PDF::MultiCell(80, 20, '  上學的日子', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   // if (!empty($dtotal)) {
      //   //   foreach ($dtotal as $dtk => $dt) {
      //   //     if ($dtk != 'total') PDF::MultiCell(23, 20, $dt, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   //   }
      //   // }
      //   // PDF::MultiCell(44, 20, $dtotal['total'], 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
      //   // TOTAL
      //   // PRESENT
      //   PDF::MultiCell(80, 20, '  Days Present', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   if (!empty($dpresent)) {
      //     foreach ($dpresent as $dpk => $dp) {
      //       if ($dpk != 'total') PDF::MultiCell(23, 20, $dp, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //     }
      //     PDF::MultiCell(44, 20, isset($dpresent['total']) ? $dpresent['total'] : 0, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   }
      //   PDF::MultiCell(2, 20, '', 0, 'C', false, 1);
      //   // PDF::MultiCell(80, 20, '  現在的日子', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   // if (!empty($dpresent)) {
      //   //   foreach ($dpresent as $dpk => $dp) {
      //   //     if ($dpk != 'total') PDF::MultiCell(23, 20, $dp, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   //   }
      //   //   PDF::MultiCell(44, 20, isset($dpresent['total']) ? $dpresent['total'] : 0, 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
      //   // }
      //   // PRESENT
      //   // LATE
      //   PDF::MultiCell(80, 20, '  Times Tardy', 1, 'L', false, 0, '', '', true, 0, false, true, 20, 'M');
      //   if (!empty($dlate)) {
      //     foreach ($dlate as $dlk => $dl) {
      //       if ($dlk != 'total') PDF::MultiCell(23, 20, $dl, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
      //     }
      //     PDF::MultiCell(44, 20, isset($dlate['total']) ? $dlate['total'] : 0, 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
      //   }
      //   // LATE
      //   // PRINT ATTENDANCE
      // }
    }


    // PDF::MultiCell(80, 20, 'Days of School', 1, 'C', false, 1, '', '', true, 0, false, true, 20, 'M');
  }

  public function getMonths($start, $end)
  {
    $months = [];
    if ($start > $end) {
      $m = $start;
      while ($m <= 12) {
        $months[$m] = [];
        $m++;
      }
      $m = 1;
      while ($m <= $end) {
        $months[$m] = [];
        $m++;
      }
    } else if ($start < $end) {
      $m = $start;
      while ($m <= $end) {
        $months[$m] = [];
        $m++;
      }
    } else {
      $months[$start] = [];
    }
    return $months;
  }

  public function printRemarks($stud, $font)
  {
    $engremarks = $this->getStudRemarks($stud, 0);
    $chiremarks = $this->getStudRemarks($stud, 1);
    PDF::setCellMargins(10);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', '15');
    PDF::MultiCell(369, 25, "TEACHER'S REMARKS", '', 'L', false, 1);
    // PDF::MultiCell(369, 25, "老師筆記", '', 'L', false, 1);
    PDF::SetFont($font, '', '10');
    $remData = [];
    if (!empty($engremarks)) {
      foreach ($engremarks as $rem) array_push($remData, ['eng' => $rem]);
    }
    if (!empty($chiremarks)) {
      foreach ($chiremarks as $ckey => $rem2) {
        if (isset($remData[$ckey])) {
          $remData[$ckey]['chi'] = $rem2;
        } else {
          array_push($remData, ['chi' => $rem2]);
        }
      }
    }
    if (!empty($remData)) {
      foreach ($remData as $rem) {
        PDF::setCellMargins(0);
        PDF::MultiCell(369, 35, isset($rem['eng']) ? $rem['eng']->quarter . '. ' . $rem['eng']->remarks : '', '', 'L', false, 1);
        PDF::setCellMargins(2);
        // PDF::MultiCell(369, 35, isset($rem['chi']) ? $rem['chi']->chiquarter . ' ' . $rem['chi']->remarks : '', '', 'L', false, 1);
      }
    }
    PDF::setCellMargins(0);
  }

  public function getStudRemarks($stud, $ischinese)
  {
    $qry = "select rem.remarks, q.code as quarter, q.chinesecode as chiquarter from en_srcremarks as rem left join en_quartersetup as q on q.line=rem.quarterid where rem.clientid=? and rem.ischinese=? union all select rem.remarks, q.code as quarter, q.chinesecode as chiquarter from en_glsrcremarks as rem left join en_quartersetup as q on q.line=rem.quarterid where rem.clientid=? and rem.ischinese=?";
    $data = $this->coreFunctions->opentable($qry, [$stud->clientid, $ischinese, $stud->clientid, $ischinese]);
    return $data;
  }

  public function getStudData($stud, $ischinese)
  {
    // $qry = "select qg.trno, qg.line, qg.scoregrade, qg.totalgrade, qg.quarterid, qg.tentativetotal, qg.finaltotal, qg.rcardtotal, q.name as quartername, qg.clientid, sub.title as subjectname from en_gequartergrade as qg left join en_subject on en_subject.trno=qg.subjectid left join en_quartersetup as q on q.line=qg.quarterid left join en_rcdetail as sub on sub.trno=qg.schedtrno and sub.line=qg.schedline where qg.clientid=? and en_subject.ischinese=?";
    $qry = "select qg.isconduct,qg.trno, qg.line, qg.scoregrade, qg.totalgrade, qg.quarterid, qg.tentativetotal,
        qg.finaltotal, qg.rcardtotal, q.name as quartername, q.code as quartercode, qg.clientid, rc.title as subjectcode, en_subject.subjectname
        from en_gequartergrade as qg
        left join en_subject on en_subject.trno=qg.subjectid
        left join en_quartersetup as q on q.line=qg.quarterid
        left join en_glsubject as sub on sub.trno=qg.schedtrno and sub.line=qg.schedline
        left join en_rcdetail as rc on rc.trno=sub.rctrno and rc.line=sub.rcline
        where qg.clientid=? and en_subject.ischinese=? and qg.isconduct=0
        union all 
        select qg.isconduct,qg.trno, qg.line, qg.scoregrade, qg.totalgrade, qg.quarterid, qg.tentativetotal,
        qg.finaltotal, qg.rcardtotal, q.name as quartername, q.code as quartercode, qg.clientid, 'CONDUCT GRADE' as subjectcode, 'CONDUCT GRADE' as subjectname
        from en_gequartergrade as qg
        left join en_subject on en_subject.trno=qg.subjectid
        left join en_quartersetup as q on q.line=qg.quarterid
        left join en_glsubject as sub on sub.trno=qg.schedtrno and sub.line=qg.schedline
        left join en_rcdetail as rc on rc.trno=sub.rctrno and rc.line=sub.rcline
        where qg.clientid=? and en_subject.ischinese=? and qg.isconduct=1";
    $data = $this->coreFunctions->opentable($qry, [$stud->clientid, $ischinese, $stud->clientid, $ischinese]);
    $data2 = [];
    if (!empty($data)) {
      foreach ($data as $d) {
        $data2[$d->quartername][] = ['trno' => $d->trno, 'line' => $d->line, 'scoregrade' => $d->scoregrade, 'totalgrade' => $d->totalgrade, 'quarterid' => $d->quarterid, 'tentativetotal' => $d->tentativetotal, 'finaltotal' => $d->finaltotal, 'rcardtotal' => $d->rcardtotal, 'subjectcode' => $d->subjectcode, 'subjectname' => $d->subjectname, 'quartercode' => $d->quartercode, 'quartername' => $d->quartername, 'clientid' => $d->clientid];
      }
    }
    return $data2;
  }

  public function getStudents($params, $clientid = 0)
  {
    $trno = $params['params']['dataid'];
    $qry = "select periodid, syid, courseid, yr, adviserid, sectionid from en_srchead where trno=? union all select periodid, syid, courseid, yr, adviserid, sectionid from en_glhead where trno=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    $stock = [];
    $added = '';
    if (!empty($head)) {
      if ($clientid != 0) {
        $added = ' and client.clientid=?';
        $data = [$head[0]->periodid, $head[0]->syid, $head[0]->courseid, $head[0]->yr, $head[0]->adviserid, $head[0]->sectionid, $clientid];
      } else {
        $data = [$head[0]->periodid, $head[0]->syid, $head[0]->courseid, $head[0]->yr, $head[0]->adviserid, $head[0]->sectionid];
      }
      $qry = "select distinct " . $trno . " as trno, qg.clientid, head.yr, client.clientname, stud.chinesename, stud.gender, '' as bgcolor, schoolyear.sy, stud.haddr,
        DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(client.bday, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(client.bday, '00-%m-%d')) AS age
        from en_gequartergrade as qg
        left join client on client.clientid=qg.clientid
        left join en_glhead as head on head.trno=qg.trno
        left join en_studentinfo as stud on stud.clientid=client.clientid
        left join en_schoolyear as schoolyear on schoolyear.line=head.syid
        where head.periodid=? and head.syid=? and head.courseid=? and head.yr=? and head.adviserid=? and head.sectionid=?" . $added;
      $stock = $this->coreFunctions->opentable($qry, $data);
    }
    return $stock;
  }
}
