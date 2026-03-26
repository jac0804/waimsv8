<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class list_of_voyage_report_per_vessel
{
  public $modulename = 'List of Voyage Report per Vessel';
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
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end
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

    $result = $this->reportdefault($config);
    $data = $this->report_default_layout($config, $result);
    return $data;
  }

  public function reportdefault($config)
  {

    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $query = "select trno, docno, dateid, whid, yourref, ourref, notes, port, arrival, departure, enginerpm, 
        timeatsea, avespeed, enginefueloil, cylinderoil, enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, 
        locoolwater, lopress, fwpress, airpress, airinletpress, coolerin, coolerout, coolerfwin, coolerfwout, seawatertemp,
        engroomtemp, begcash, addcash, usagefeeamt, mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, 
        bunkeringamt, sopamt, othersamt, purchaseamt, 
        crewsubsistenceamt, waterexpamt, localtranspoamt, others2amt, reqcash,
        wh.clientname as warehouse
      from rvoyage as head
      left join client as wh on head.whid = wh.clientid
      where dateid between '$start' and '$end' ";

    return $this->coreFunctions->opentable($query);
  }


  public function report_default_layout($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_default($config, $result);
    $docno = "";
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc # : ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Doc Date : ' . date("Y-m-d", strtotime($data->dateid)), '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Warehouse/Vessel :' . $data->warehouse, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Yourref : ' . $data->yourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ourref : ' . $data->ourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Notes : ' . $data->notes, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= '<br>';

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Port : ' . $data->port, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time Arrival : ' . $data->arrival, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time Departure : ' . $data->departure, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engine Rpm : ' . $data->enginerpm, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time At Sea: ' . $data->timeatsea, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Average Speed : ' . $data->avespeed, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engine Fuel Oil Consumption : ' . $data->enginefueloil, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cylinder Oil Consumption : ' . $data->cylinderoil, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engin Lube Oil Sump Tank Sounding : ' . $data->enginelubeoil, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Highest Exhaust Temp/Cyl Nr. : ' . $data->hiexhaust, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Lowest Exhaust Temp/Cyl Nr. : ' . $data->loexhaust, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('T/C Exhaust Gas Outlet Temperature : ' . $data->exhaustgas, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cool Water Highest/Cyl Nr. : ' . $data->hicoolwater, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cool Water Lowest/Cyl Nr. : ' . $data->locoolwater, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LO. Press : ' . $data->lopress, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cool F.W Press : ' . $data->fwpress, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Scay Air Press : ' . $data->airpress, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Scay Air Inlet Temp : ' . $data->airinletpress, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LO Cooler In : ' . $data->coolerin, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LO Cooler Out : ' . $data->coolerout, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('F.W. Cooler F.W In : ' . $data->coolerfwin, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('F.W. Cooler F.W Out : ' . $data->coolerfwout, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sea Water Temp : ' . $data->seawatertemp, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Eng Room Temp : ' . $data->engroomtemp, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }
    }

    $str .= $this->reporter->endreport();
    return $str;
  }


  public function header_default($config, $data)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
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
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('List of Voyage Report per Vessel', null, null, false, $border, '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }
}//end class