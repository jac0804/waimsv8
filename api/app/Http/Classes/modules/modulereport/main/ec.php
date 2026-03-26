<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class ec
{

  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
            'default' as print,
            '' as prepared,
            '' as approved,
            '' as received
        "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.trno,head.doc,head.docno,head.dateid,head.curriculumcode,head.curriculumname,course.coursename,sy.sy,course.coursecode,course.coursename,l.levels,
    stock.units,stock.lecture,stock.laboratory,stock.hours,s.subjectcode,s.subjectname,y.year as yr
    from en_cchead as head left join en_ccsubject as stock on stock.trno=head.trno left join en_ccyear as y on y.trno=stock.trno and stock.cline=y.line left join en_subject as s on s.trno=stock.subjectid
    left join en_course as course on course.line=head.courseid left join en_schoolyear as sy on sy.line=head.syid
    left join en_levels as l on l.line=head.levelid where head.trno=" . $trno . "
    union all
    select head.trno,head.doc,head.docno,head.dateid,head.curriculumcode,head.curriculumname,course.coursename,sy.sy,course.coursecode,course.coursename,l.levels,
    stock.units,stock.lecture,stock.laboratory,stock.hours,s.subjectcode,s.subjectname,y.year as yr
    from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join en_glyear as y on y.trno=stock.trno and stock.cline=y.line left join en_subject as s on s.trno=stock.subjectid
    left join en_course as course on course.line=head.courseid left join en_schoolyear as sy on sy.line=head.syid
    left join en_levels as l on l.line=head.levelid where head.doc='EC' and head.trno=" . $trno . " order by yr";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function books_query($trno)
  {
    $query = "select item.barcode as code,item.itemname as description,books.isqty as qty,books.isamt as amount
    from en_ccbooks as books 
    left join item on item.itemid=books.itemid
    where books.trno=" . $trno . "

    UNION ALL

    select item.barcode as code,item.itemname as description,books.isqty as qty,books.isamt as amount
    from en_glbooks as books
    left join item on item.itemid=books.itemid
    where books.trno=" . $trno . "";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CURRICULUM', '580', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col((isset($data[0]['curriculumcode']) ? $data[0]['curriculumname'] . ' ' . $data[0]['curriculumcode'] : ''), '580', null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col((isset($data[0]['coursecode']) ? $data[0]['coursename'] . ' ' . $data[0]['coursecode'] : ''), '580', null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '580', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO. : ', '130', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '410', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? date('Y-m-d', strtotime($data[0]['dateid'])) : ''), '140', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    //   $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '110', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('NAME', '250', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('UNITS', '100', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('HOURS', '100', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');

    return $str;
  }

  private function books_table($books, $font, $fontsize, $border)
  {
    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= '<br>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BOOKS', '150', null, false, $border, '', 'L', $font, '12', 'B', '', '8px');
    $str .= $this->reporter->col('', '320', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '150', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '320', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '120', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    // var_dump($books);
    $totalamt = 0;
    for ($j = 0; $j < count($books); $j++) {
      // col($txt = '', $w = null, $h = null, $bg = false, $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $jsamount = 0, $colspan = 0, $bc = null)
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($books[$j]['code'], '150', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp' . $books[$j]['description'], '320', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($books[$j]['qty'], '120', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($books[$j]['amount'], 2) . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalamt += $books[$j]['amount'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '320', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amount:', '120', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2) . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportplotting($params, $data, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $yr = '';

    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      if ($data[$i]['yr'] != $yr) {
        $yr = $data[$i]['yr'];
        // $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLB', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[$i]['coursecode'] . ' ' . $data[$i]['yr'], '250', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TBR', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->col($data[$i]['subjectcode'], '110', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['subjectname'], '250', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['units'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['hours'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');

      if ($data[$i]['yr'] != $yr) {
        $yr = $data[$i]['yr'];
        // $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'TLB', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($data[$i]['coursecode'] . ' X ' . $data[$i]['yr'], '250', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TBR', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->books_table($books, $font, $fontsize, $border);

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
