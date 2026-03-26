<?php

namespace App\Http\Classes\modules\reportlist\masterfile_report;

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

class tenant_list
{
  public $modulename = 'Tenant List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1150'];

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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'radiosortby', 'prepared', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radiosortby.options', [
      ['label' => 'By Location', 'value' => 'loc', 'color' => 'orange'],
      ['label' => 'By Name', 'value' => 'name', 'color' => 'orange'],
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      'loc' as sortby,
      '' as prepared,
      '' as approved
    ";

    return $this->coreFunctions->opentable($paramstr);
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $sortby = $config['params']['dataparams']['sortby'];
    $companyid = $config['params']['companyid'];

    $filter   = "";

    if ($sortby == "loc") {
      $sortby = " loc.name";
    } else {
      $sortby = " cl.clientname";
    }

    $query = "
      select cl.clientid, cl.client, cl.clientname, cl.addr, cl.tel, cl.contact, 
      left(cl.start, 10) as start, left(cl.enddate, 10) as enddate,
      case when cl.isinactive = 0 then 'ACTIVE' else 'INACTIVE' end as status,
      tinfo.leaserate as rentrate, (tinfo.leaserate * loc.area) as rent, 
      tinfo.cusarate, (tinfo.cusarate * loc.area) as cusa,
      tinfo.acrate, (tinfo.acrate * loc.area) as aircon,
      tinfo.elecrate, tinfo.waterrate,
      loc.name as locname, loc.area as sqm,
      center.name as centername
      from client as cl
      left join tenantinfo as tinfo on tinfo.clientid = cl.clientid
      left join loc as loc on loc.line = cl.locid
      left join center as center on center.code = cl.center
      where cl.istenant = 1
      order by " . $sortby . "
    ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';


    $sortby     = $config['params']['dataparams']['sortby'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TENANT  LIST', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    if ($sortby == "loc") {
      $sort = "Sort by Location";
    } else {
      $sort = "Sort by Name";
    }

    $str .= $this->reporter->col($sort, NULL, null, false, $border, '', 'C', $font, '10', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Location', '50', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Code', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Site', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Name', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Address', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Tel #', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Start Date', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('End Date', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Status', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('SQM', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Rent Rate', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Rent', '75', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CUSA Rate', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CUSA', '75', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Aircon Rate', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Aircon', '75', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Electricity', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Water', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $sortby  = $config['params']['dataparams']['sortby'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= "<div style='margin-left: -70px;'>";
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $center = "";
    foreach ($result as $key => $data) {
      $this->reporter->addline();
      if ($center != $data->centername) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->centername, NULL, null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->locname, '50', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->client, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->centername, '75', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '75', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '75', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel, '75', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->start, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->enddate, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->status, '75', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col(number_format($data->sqm, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->rentrate, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->rent, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cusarate, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cusa, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->acrate, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->aircon, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->elecrate, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->waterrate, $decimalcurr), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $center = $data->centername;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    $str .= "</div>";
    return $str;
  }
}//end class