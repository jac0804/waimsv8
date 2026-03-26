<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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
use Illuminate\Support\Facades\Storage;

class employee_time_in_and_time_out_logs
{
  public $modulename = 'Employee Time in and Time out Logs';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['start', 'end', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);
    data_set($col2, 'radioreporttype.label', 'Report Type');
    data_set($col2, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'orange'],
      ['label' => 'W/ Captured Image', 'value' => '1', 'color' => 'orange']
    ]);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as deptrep,
    '' as timeinout,
    '0' as reporttype
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    switch ($config['params']['dataparams']['reporttype']) {
      case '0':
        return $this->reportDefaultLayout($config);
        break;
      case '1':
        return $this->reportWithImageLayout($config);
        break;
    }
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";
    $filter2   = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and e.deptid = $deptid";
    }
    if ($divid != 0) {
      $filter2 .= " and e.divid = $divid";
    }

    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select t.userid, t.timeinout,e.empid,c.client,c.clientname ,d.divname,dept.clientname as deptname, t.mode, lp.picture, c.picture as cpicture
      FROM timerec as t 
      left join employee as e on e.idbarcode=t.userid
      left join client as c on c.clientid=e.empid
      left join division as d on d.divid = e.divid
      left join client as dept on dept.clientid = e.deptid
      left join loginpic as lp on lp.dateid=t.timeinout and lp.mode=t.mode and lp.idbarcode=e.empid
      where date(t.timeinout) between '" . $start . "' and '" . $end . "' $filter $filter1 $filter2 and e.idbarcode<>0
      order by c.clientname,t.timeinout";
    $data = $this->coreFunctions->opentable($query);
    if ($config['params']['dataparams']['reporttype'] == '1') {
      if (!empty($data)) {
        foreach ($data as $d) {
          if ($d->picture != '') {
            if (Storage::disk('public')->exists($d->picture)) {
              $d->picture = 'data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get($d->picture));
            } else {
              $d->picture = '';
            }
          }
        }
      }
    }
    return $data;
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];


    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE TIME IN LOGS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    if ($divid == 0) {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->col('DATE RANGE : ' . $start . ' to ' . $end, NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    switch ($config['params']['dataparams']['reporttype']) {
      case '0':
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('C O D E', '266', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '268', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T Y P E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T I M E  I N   L O G S ', '166', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      case '1':
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("&nbsp;", '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('E M P L O Y E E', '268', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T Y P E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('T I M E  I N   L O G S ', '166', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('I M A G E', '166', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    }
    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportWithImageLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $clientname = '';
    foreach ($result as $key => $data) {
      if ($clientname == '' || $clientname != $data->clientname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        if ($data->cpicture != '') {
          $filename = str_replace('/images/', '', $data->cpicture);
          if (Storage::disk('public')->exists($filename)) {
            $str .= $this->reporter->col('<img src="data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get($filename)) . '" style="width:50px;height:50px;">', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          } else {
            if (Storage::disk('public')->exists('/demo/nouserimg.jpg')) {
              $str .= $this->reporter->col('<img src="data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get('/demo/nouserimg.jpg')) . '" style="width:50px;height:50px;">', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            } else {
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            }
          }
        } else {
          if (Storage::disk('public')->exists('/demo/nouserimg.jpg')) {
            $str .= $this->reporter->col('<img src="data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get('/demo/nouserimg.jpg')) . '" style="width:50px;height:50px;">', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          } else {
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          }
        }
        $str .= $this->reporter->col($data->client . ' - ' . $data->clientname, '268', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '268', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->mode, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->timeinout, '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      if ($data->picture != '') {
        $str .= $this->reporter->col('<img src="' . $data->picture . '" style="width:50px;height:50px;">', '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      } else {
        if (Storage::disk('public')->exists('/demo/nouserimg.jpg')) {
          $str .= $this->reporter->col('<img src="data:image/jpeg;base64,' . base64_encode(Storage::disk('public')->get('/demo/nouserimg.jpg')) . '" style="width:50px;height:50px;">', '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col('', '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        }
      }
      $str .= $this->reporter->endrow();
      $clientname = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '267', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->mode, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->timeinout, '166', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300px', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class