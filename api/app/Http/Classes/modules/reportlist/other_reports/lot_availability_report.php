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

class lot_availability_report
{
  public $modulename = 'Lot Availability Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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

    $fields = ['radioprint', 'dprojectname', 'phase'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'projectname.lookupclass', 'fproject');
    data_set($col1, 'phase.addedparams', ['projectid']);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as dprojectname, 
    '' as projectcode, 
    '' as projectname,
    '' as phase");
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
    $prjname = $config['params']['dataparams']['projectname'];
    $phase = $config['params']['dataparams']['phase'];

    $filter = '';
    if (!empty($prjname)) $filter .= " and project.name = '$prjname'";
    if (!empty($phase)) $filter .= " and phase.code = '$phase'";

    // QUERY
    $query = "select project.name, model.model, phase.code, concat(blk, '/', lot) as blk_lot, 
    if(blklot.clientid <> 0, 'sold', 'available') as status 
    from projectmasterfile as project
    left join housemodel as model on model.line = project.line
    left join phase on phase.projectid = model.projectid
    left join blklot on blklot.line = project.line
    where project.name <> ''$filter";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LOT AVAILABILITY', null, null, false, '10px solid ', '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br/><br/>";

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('HOUSE MODEL', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BLOCK/LOT', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 55;
    $page = 55;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $soldcount = 0;
    $availcount = 0;
    $grandtotalsold = 0;
    $grandtotalavail = 0;

    foreach ($result as $key => $data) {
      $statusbbg = ($data->status == 'sold') ? "<div style='background-color: green'>$data->status</div>" : "<div style='background-color: red'>$data->status</div>";

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->name, '200', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->model, '200', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->blk_lot, '200', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($statusbbg, '200', null, false, $border, $border_line, 'C', $font, $font_size, '', '#fff', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }

      if ($data->status == 'sold') $soldcount++;
      if ($data->status == 'available') $availcount++;
    } //end foreach

    $grandtotalsold += $soldcount;
    $grandtotalavail += $availcount;

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->addline();
    $str .= '<br/>';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL AVAILABLE: ' . $availcount, null, '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL SOLD: ' . $soldcount, null, '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL AVAILABLE: ' . $grandtotalavail, null, '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL SOLD: ' . $grandtotalsold, null, '', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class