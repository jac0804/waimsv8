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
    $fields = ['radioprint', 'start', 'ddeptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of Date');
    data_set($col1, 'ddeptname.lookupclass', 'lookupheaddept');
    data_set($col1, 'ddeptname.action', 'lookupheaddept');
    data_set($col1, 'ddeptname.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
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
    $str .= $this->reporter->col('PRINT DATE: ' . date("F d, Y", strtotime($config['params']['dataparams']['start'])), '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->col('(  ) Paper Audit within 5 working days only', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if (!empty($deptname)) {
      $str .= $this->reporter->col('DEPARTMENT: ' . $deptname, '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    } else {
      $str .= $this->reporter->col('DEPARTMENT: ALL', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    }

    $str .= $this->reporter->col('(  ) Physical Audit as per Department confirmed schedule', '500', null, false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
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
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 27;
    $page = 27;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $i = 1;
    $empname = '';

    foreach ($result as $key => $data) {
      if ($empname != $data->empname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("", null, '40', false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col($data->empname, '180', null, false, $border, 'TLR', 'L', $font, $font_size, 'B', '', '6px');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '6px');
        $str .= $this->reporter->endrow();

        $i = 1;
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($i++, '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col($data->barcode, '180', '', false, $border, 'TBLR', 'L', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col($data->itemname, '250', '', false, $border, 'TBLR', 'L', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col($data->serialno, '120', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col('', '40', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col('', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->col($data->rem, '150', '', false, $border, 'TBLR', 'C', $font, $font_size, '', '', '6px');
      $str .= $this->reporter->endrow();

      $empname = $data->empname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page += $count;
      }
    } //end foreach

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col("This is to acknowledge that all of the above-mentioned signed declarations are true, that they are currently in use personally and are physically available with my department. Should there be false declarations, I will be held accountable and be subjected to the company's disciplinary actions, and charges may be applied.", '700', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $depthead = '';
    if ($config['params']['dataparams']['deptname'] != '') $depthead = $config['params']['dataparams']['depthead'];

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/><br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('BU HEAD SIGNATURE & DATE: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200', '', false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(strtoupper($depthead), '200', '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '300', '', false, $border, '', 'C', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->col('Audit by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->col('Checked by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->col('Tagged by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->col('Noted by: ', '200', '', false, $border, '', 'L', $font, $font_size, 'B', '', '6px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Data Supervisor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Auditor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Audit Supervisor', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('AMD Coordinator', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->col('', '20', '', false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Sr. Asset Mgmt Head', '180', '', false, $border, '', 'L', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE:  ALL ITEMS (CODED / NSI & PBS)  SHOULD BE CHECKED AND PROCESSED. ANY ITEM/s MISSING AFTER 5 WORKING DAYS TO BE REPORTED FOR NTH REPORT & CHARGING PROCESS', '1000', '', false, $border, '', 'C', $font, $font_size, '', '', '', '6px', 0, '', 0, 10);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class