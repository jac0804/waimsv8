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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class asset_accountability_report
{
  public $modulename = 'Asset Accountability Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

  public function __construct($params = null)
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass($params);
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $fields = ['radioprint', 'start', 'ddeptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of Date');
    data_set($col1, 'ddeptname.lookupclass', 'lookupheaddept');
    data_set($col1, 'ddeptname.action', 'lookupheaddept');
    data_set($col1, 'ddeptname.required', true);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
      ['label' => 'excel', 'value' => 'excel', 'color' => 'red'],
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'PDFM' as print,
    left(now(),10) as start,
    '' as ddeptname,
    '' as dept,
    '' as deptname,
    '' as deptid,
    '' as depthead");
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
    if ($config['params']['dataparams']['print'] == 'PDFM') {
      return $this->reportPDFLayout($config);
    }
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $deptname  = $config['params']['dataparams']['ddeptname'];
    $dept      = $config['params']['dataparams']['deptid'];
    $asof      =  $config['params']['dataparams']['start'];

    $filter   = '';
    if ($deptname != '') $filter .= " and issue.locid = '$dept'";
    $query = "select client.clientname as empname,item.itemname, item.barcode, info.serialno,stock.rem
    from issueitem as issue
    left join transnum as num on num.trno = issue.trno
    left join client on client.clientid = issue.clientid
    left join issueitemstock as stock on stock.trno = issue.trno
    left join item on item.itemid = stock.itemid
    left join iteminfo as info on info.itemid = item.itemid
    where num.doc = 'FI' and stock.returndate is null and item.itemname <> '' 
          and item.barcode <> '' and item.isinactive = 0
          and date(issue.dateid) <= '" . $asof . "' " . $filter . " 
    order by client.clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $deptname = $config['params']['dataparams']['ddeptname'];

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ASSET ACCOUNTABILITY REPORT', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRINT DATE: ' . date("F d, Y", strtotime($config['params']['dataparams']['start'])), '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->col('(  ) Paper Audit within 5 working days only', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if (!empty($deptname)) {
      $str .= $this->reporter->col('DEPARTMENT: ' . $deptname, '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    } else {
      $str .= $this->reporter->col('DEPARTMENT: ALL', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    }

    $str .= $this->reporter->col('(  ) Physical Audit as per Department confirmed schedule', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BLANK(OLD), T-TRANSFER, N-NEW & RPL-REPLACEMENT', '430', null, false, $border, '', 'L', $font, $font_size, 'B', '', '', '', 0, '', 0, 2);
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(strtoupper(date("F", strtotime($config['params']['dataparams']['start']))), '160', null, false, $border, 'LRT', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 4);
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NO.', '40', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PRODUCT CODE', '180', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '250', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SERIAL NO.', '120', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col("<div style='transform: rotate(-90deg); -webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); white-space: nowrap;'>IN USE</div>", '40', '60', false, $border, 'TBLR', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col("<div style='transform: rotate(-90deg); -webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg);'>PULLOUT</div>", '40', '60', false, $border, 'TBLR', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col("<div style='transform: rotate(-90deg); -webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg);'>DAMAGE</div>", '40', '60', false, $border, 'TBLR', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col("<div style='transform: rotate(-90deg); -webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg);'>MISSING</div>", '40', '60', false, $border, 'TBLR', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SIGN', '100', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '150', '60', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $border = '1px solid';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = 'Arial';
    $font_size = '10';

    $rowOverhead = 0.45;
    $page = 68;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= '<style>#report_content tr { page-break-inside: avoid; }</style>';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $i = 1;
    $empname = '';

    foreach ($result as $key => $data) {
      if ($empname != $data->empname) {
        if ($empname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", 40, null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
          $this->reporter->addline();
          $this->reporter->linecounter += $rowOverhead;
        }

        // FIX: use $data->empname (the employee being printed now), not $empname (the previous one)
        $headerLines = $this->reporter->estimateLines($data->empname, 180, $font_size, '2px', true);
        for ($l = 0; $l < $headerLines; $l++) {
          $this->reporter->addline();
        }
        $this->reporter->linecounter += $rowOverhead;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col($data->empname, '180', null, false, $border, 'TLR', 'L', $font, $font_size, 'B', '', '2px');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
        $str .= $this->reporter->endrow();

        $i = 1;
      }

      $lines = $this->reporter->estimateRowLines(array(
        array($data->barcode,  180, '2px'),
        array($data->itemname, 250, '2px'),
        array($data->serialno, 120, '2px'),
        array($data->rem,      150, '2px'),
      ), $font_size);

      for ($l = 0; $l < $lines; $l++) {
        $this->reporter->addline();
      }
      $this->reporter->linecounter += $rowOverhead;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($i++, '40', '', false, $border, 'TBLR', 'CT', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col($data->barcode, '180', '', false, $border, 'TBLR', 'LT', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col($data->itemname, '250', '', false, $border, 'TBLR', 'L', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col($data->serialno, '120', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col('', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->col($data->rem, '150', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '2px');
      $str .= $this->reporter->endrow();

      $empname = $data->empname;

      if ($this->reporter->linecounter >= $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);

        // FIX: linecounter resets each break, so $page must stay fixed at $count —
        // do NOT accumulate ($page += $count removed), or the next break's threshold
        // balloons and the manual break effectively stops firing.
        $this->reporter->linecounter = 0;

        // repeat the employee header row if the group continues onto this new page
        $hasMore = false;
        foreach ($result as $checkKey => $checkData) {
          if ($checkKey > $key && $checkData->empname == $empname) {
            $hasMore = true;
            break;
          }
          if ($checkKey > $key) {
            break; // next row belongs to a different employee, no continuation needed
          }
        }

        if ($hasMore) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col($empname, '180', null, false, $border, 'TLR', 'L', $font, $font_size, 'B', '', '2px');
          $str .= $this->reporter->col('(continued)', '250', null, false, $border, '', 'L', $font, $font_size, 'I', '', '2px');
          $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '2px');
          $str .= $this->reporter->endrow();

          $headerLines = $this->reporter->estimateLines($empname, 180, $font_size, '2px', true);
          for ($l = 0; $l < $headerLines; $l++) {
            $this->reporter->addline();
          }
          $this->reporter->linecounter += $rowOverhead;
        }
      }
    } //end foreach

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col("This is to acknowledge that all of the above-mentioned signed declarations are true, that they are currently in use personally and are physically available with my department. Should there be false declarations, I will be held accountable and be subjected to the company's disciplinary actions, and charges may be applied.", '700', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $depthead = '';
    if ($config['params']['dataparams']['deptname'] != '') $depthead = $config['params']['dataparams']['depthead'];

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/><br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('BU HEAD SIGNATURE & DATE: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200', '', false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(strtoupper($depthead), '200', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->col('Audit by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->col('Checked by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->col('Tagged by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->col('Noted by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Data Supervisor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Auditor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Audit Supervisor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Coordinator', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD/RMA Head', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE:  ALL ITEMS (CODED / NSI & PBS)  SHOULD BE CHECKED AND PROCESSED. ANY ITEM/s MISSING AFTER 5 WORKING DAYS TO BE REPORTED FOR NTH REPORT & CHARGING PROCESS', '1000', '', false, $border, '', 'C', $font, $font_size, '', '', '', '2px', 0, '', 0, 10);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


  public function displayHeaderPDF($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $deptname = $config['params']['dataparams']['ddeptname'];
    $asof = $config['params']['dataparams']['start'];

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $font = 'Arial';
    $fontbold = 'Arial';
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(40, 20);
    PDF::SetAutoPageBreak(false, 0);
    PDF::AddPage('p', [800, 1318]);

    $x = PDF::GetX();
    $y = PDF::GetY();
    PDF::SetFont($font, '', 10);
    PDF::SetXY(40, PDF::getPageHeight() - 45);
    PDF::MultiCell(780, 15, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R', false, 1);
    PDF::SetXY($x, $y);

    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    // PDF::SetFont($fontbold, '', 14);
    // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    // PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, 'ASSET ACCOUNTABILITY REPORT', '', 'C');
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 0, 'PRINT DATE: ' . date("F d, Y", strtotime($asof)), '', 'L', false, 0);
    PDF::MultiCell(360, 0, '(  ) Paper Audit within 5 working days only', '', 'L', false, 1);
    PDF::MultiCell(360, 0, 'DEPARTMENT: ' . (!empty($deptname) ? $deptname : 'ALL'), '', 'L', false, 0);
    PDF::MultiCell(360, 0, '(  ) Physical Audit as per Department confirmed schedule', '', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(30, 15, '', '', 'L', false, 0);
    PDF::MultiCell(290, 15, 'BLANK(OLD), T-TRANSFER, N-NEW & RPL-REPLACEMENT', '', 'L', false, 0);
    PDF::MultiCell(100, 15, '', '', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 15, strtoupper(date("F", strtotime($asof))), 'LRT', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(180, 15, '', '', 'C', false, 1);

    // column
    $hh = 60;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, $hh, 'NO.', 1, 'C', false, 0, '', '', true, 0, false, true, $hh, 'M');
    PDF::MultiCell(120, $hh, 'PRODUCT CODE', 1, 'C', false, 0, '', '', true, 0, false, true, $hh, 'M');
    PDF::MultiCell(170, $hh, 'DESCRIPTION', 1, 'C', false, 0, '', '', true, 0, false, true, $hh, 'M');
    PDF::MultiCell(100, $hh, 'SERIAL NO.', 1, 'C', false, 0, '', '', true, 0, false, true, $hh, 'M');

    // rotated headers
    $rot = array('IN USE', 'PULLOUT', 'DAMAGE', 'MISSING');
    PDF::SetFont($fontbold, '', 8);
    foreach ($rot as $txt) {
      $x = PDF::GetX();
      $y = PDF::GetY();
      PDF::Rect($x, $y, 30, $hh);
      $tw = PDF::GetStringWidth($txt);
      PDF::StartTransform();
      PDF::Rotate(90, $x + 10, $y + $hh - (($hh - $tw) / 2));
      PDF::Text($x + 10, $y + $hh - (($hh - $tw) / 2), $txt);
      PDF::StopTransform();
      PDF::SetXY($x + 30, $y);
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, $hh, 'SIGN', 1, 'C', false, 0, '', '', true, 0, false, true, $hh, 'M');
    PDF::MultiCell(110, $hh, 'REMARKS', 1, 'C', false, 1, '', '', true, 0, false, true, $hh, 'M');
  }

  private function pdfEmployeeRow($empname, $suffix)
  {
    $font = 'Arial';
    $fontbold = 'Arial';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $fontsize = 10;

    PDF::SetFont($fontbold, '', $fontsize);
    $h = PDF::getStringHeight(120, $empname);
    if ($h < 18) $h = 18;

    PDF::MultiCell(30, $h, '', '', 'C', false, 0);
    PDF::MultiCell(120, $h, $empname, 'TLR', 'L', false, 0);
    PDF::SetFont($font, 'I', $fontsize);
    PDF::MultiCell(170, $h, $suffix, '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, $h, '', '', 'C', false, 0);
    PDF::MultiCell(30, $h, '', '', 'C', false, 0);
    PDF::MultiCell(30, $h, '', '', 'C', false, 0);
    PDF::MultiCell(30, $h, '', '', 'C', false, 0);
    PDF::MultiCell(30, $h, '', '', 'C', false, 0);
    PDF::MultiCell(70, $h, '', '', 'C', false, 0);
    PDF::MultiCell(110, $h, '', '', 'C', false, 1);
  }

  public function reportPDFLayout($config)
  {
    $result = $this->reportDefault($config);
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $font = 'Arial';
    $fontbold = 'Arial';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $fontsize = 10;

    $this->displayHeaderPDF($config);
    $limit = PDF::getPageHeight() - 60; // bottom of usable area

    $i = 1;
    $empname = '';

    foreach ($result as $key => $data) {
      $barcode  = isset($data->barcode) ? $data->barcode : '';
      $itemname = isset($data->itemname) ? $data->itemname : '';
      $serialno = isset($data->serialno) ? $data->serialno : '';
      $rem      = isset($data->rem) ? $data->rem : '';

      PDF::SetFont($font, '', $fontsize);
      $rowh = max(
        PDF::getStringHeight(120, $barcode),
        PDF::getStringHeight(170, $itemname),
        PDF::getStringHeight(100, $serialno),
        PDF::getStringHeight(110, $rem),
        18
      );

      if ($empname != $data->empname) {
        if ($empname != '') {
          PDF::MultiCell(0, 8, '', '', 'L', false, 1);
        }
        if (PDF::GetY() + 18 + $rowh > $limit) {
          $this->displayHeaderPDF($config);
        }
        $this->pdfEmployeeRow($data->empname, '');
        $i = 1;
        $empname = $data->empname;
      } else if (PDF::GetY() + $rowh > $limit) {
        $this->displayHeaderPDF($config);
        $this->pdfEmployeeRow($empname, '(continued)');
      }

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(30, $rowh, $i++, 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(120, $rowh, $barcode, 1, 'L', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(170, $rowh, $itemname, 1, 'L', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(100, $rowh, $serialno, 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(30, $rowh, '', 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(30, $rowh, '', 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(30, $rowh, '', 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(30, $rowh, '', 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(70, $rowh, '', 1, 'C', false, 0, '', '', true, 0, false, true, $rowh, 'M');
      PDF::MultiCell(110, $rowh, $rem, 1, 'C', false, 1, '', '', true, 0, false, true, $rowh, 'M');
    } //end foreach

    if (PDF::GetY() + 300 > $limit) {
      PDF::AddPage('p', [800, 1318]);
    }

    $depthead = '';
    if ($config['params']['dataparams']['deptname'] != '') $depthead = $config['params']['dataparams']['depthead'];

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "This is to acknowledge that all of the above-mentioned signed declarations are true, that they are currently in use personally and are physically available with my department. Should there be false declarations, I will be held accountable and be subjected to the company's disciplinary actions, and charges may be applied.", '', 'C');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(210, 0, 'BU HEAD SIGNATURE & DATE: ', '', 'L', false, 0);
    PDF::MultiCell(190, 0, '', 'B', 'L', false, 1);
    PDF::MultiCell(370, 0, '', '', 'L', false, 0);
    PDF::MultiCell(190, 0, strtoupper($depthead), '', 'C', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(144, 0, 'Prepared by: ', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'Audit by: ', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'Checked by: ', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'Tagged by: ', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'Noted by: ', '', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(144, 0, 'AMD Data Supervisor', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'AMD Auditor', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'Audit Supervisor', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'AMD Coordinator', '', 'L', false, 0);
    PDF::MultiCell(144, 0, 'AMD/RMA Head', '', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, 'NOTE:  ALL ITEMS (CODED / NSI & PBS)  SHOULD BE CHECKED AND PROCESSED. ANY ITEM/s MISSING AFTER 5 WORKING DAYS TO BE REPORTED FOR NTH REPORT & CHARGING PROCESS', '', 'C');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}//end class