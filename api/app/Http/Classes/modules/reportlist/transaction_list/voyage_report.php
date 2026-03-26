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

class voyage_report
{
  public $modulename = 'Voyage Report';
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
    $result = $this->reportdefault($config);
    $data = $this->report_default_layout($config, $result);
    return $data;
  }

  public function reportdefault($config)
  {

    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $query = "select trno, docno, dateid, whid, yourref, ourref, notes, port, arrival, departure, enginerpm, 
        timeatsea, avespeed, enginefueloil, cylinderoil, enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, 
        locoolwater, lopress, fwpress, airpress, airinletpress, coolerin, coolerout, coolerfwin, coolerfwout, seawatertemp,
        engroomtemp, begcash, addcash, usagefeeamt, mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, 
        bunkeringamt, sopamt, othersamt, purchaseamt, 
        crewsubsistenceamt, waterexpamt, localtranspoamt, others2amt,usagefee, mooring, coastguardclearance, pilotage, lifebouy, 
        bunkering, sop, others, purchase, 
        crewsubsistence, waterexp, localtranspo, others2, reqcash,
        wh.clientname as warehouse
      from rvoyage as head
      left join client as wh on head.whid = wh.clientid
      where dateid between '$start' and '$end'
      union all
      select trno, docno, dateid, whid, yourref, ourref, notes, port, arrival, departure, enginerpm, 
        timeatsea, avespeed, enginefueloil, cylinderoil, enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, 
        locoolwater, lopress, fwpress, airpress, airinletpress, coolerin, coolerout, coolerfwin, coolerfwout, seawatertemp,
        engroomtemp, begcash, addcash, usagefeeamt, mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, 
        bunkeringamt, sopamt, othersamt, purchaseamt, 
        crewsubsistenceamt, waterexpamt, localtranspoamt, others2amt,usagefee, mooring, coastguardclearance, pilotage, lifebouy, 
        bunkering, sop, others, purchase, 
        crewsubsistence, waterexp, localtranspo, others2, reqcash,
        wh.clientname as warehouse
      from hrvoyage as head
      left join client as wh on head.whid = wh.clientid
      where dateid between '$start' and '$end' order by docno";

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
    $layoutsize = '800';
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
        if ($docno != '') {
          $str .= '<br>';
          $str .= $this->reporter->printline();
        }
        $docno = $data->docno;
        $str .= '<br>';
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
        $str .= $this->reporter->col('Port : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->port, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Cool Water Highest/Cyl Nr. : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->hicoolwater, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time Arrival : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->arrival, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Cool Water lowest/Cyl Nr. : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->locoolwater, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time Departure : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->departure, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('LO. Press : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->lopress, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engine Rpm : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->enginerpm, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Cool F.W Press : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->fwpress, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Time At Sea: ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->timeatsea, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Scay Air Press : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->airpress, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Average Speed : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->avespeed, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Scay Air Inlet Temp : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->airinletpress, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engine Fuel Oil Consumption : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->enginefueloil, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('LO Cooler In : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->coolerin, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cylinder Oil Consumption : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->cylinderoil, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('LO Cooler Out : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->coolerout, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Main Engine Lube Oil Sump Tank Sounding : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->enginelubeoil, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('F.W. Cooler F.W. Out : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->coolerfwin, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Highest Exhaust Temp/Cyl Nr. : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->hiexhaust, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('F.W. Cooler F.W. In : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->coolerfwout, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Lowest Exhaust Temp/Cyl Nr. : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->loexhaust, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Sea Water Temp : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->seawatertemp, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('T/C Exhaust Gas Outlet Temperature : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->exhaustgas, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->col('Eng Room Temp : ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '1px');
        $str .= $this->reporter->col($data->engroomtemp, '200', null, false, $border, '', 'L', $font, $fontsize, '', 'false', '1px');
        $str .= $this->reporter->endrow();

        ////
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->startrow();
        $str .= '<br/>';
        $str .= $this->reporter->col('FUND LIQUIDATION', '250', null, false, $border, '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->col('Cash Beginning', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col(number_format($data->begcash, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Add Cash Received', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col(number_format($data->addcash, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $cash_received = $data->begcash + $data->addcash;
        $str .= $this->reporter->col(number_format($cash_received, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Port Charges', '150', null, false, $border, 'B', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Usage Fee/PPA Clearance', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->usagefee, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->usagefeeamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Mooring/Unmooring', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->mooring, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->mooringamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Coast Guard Clearance', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->coastguardclearance, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->coastguardclearanceamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Pliotage', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->pilotage, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->pilotageamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Life Bouy/Marker', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->lifebouy, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->lifebouyamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Bunkering Permit', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->bunkering, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->bunkeringamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SOP', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->sop, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->sopamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Others', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->others, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->othersamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL PORT CHARGES', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $totalport_charges =
          $data->usagefeeamt +
          $data->mooringamt +
          $data->coastguardclearanceamt +
          $data->pilotageamt +
          $data->lifebouyamt +
          $data->bunkeringamt +
          $data->sopamt +
          $data->othersamt;
        $str .= $this->reporter->col(number_format($totalport_charges, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Purchases', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->purchase, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->purchaseamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Crew Subsistence', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->crewsubsistence, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->crewsubsistenceamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Water Expense', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->waterexp, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->waterexpamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Local Transportation', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->localtranspo, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->localtranspoamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Others', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col($data->others2, '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->others2amt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EXPENSE', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $total_expense =
          $data->purchaseamt +
          $data->crewsubsistenceamt +
          $data->waterexpamt +
          $data->localtranspoamt +
          $data->others2amt;
        $str .= $this->reporter->col(number_format($total_expense, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CASH BALANCE', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $cash_balance = (($totalport_charges + $cash_received) - $total_expense);
        $str .= $this->reporter->col(number_format($cash_balance, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Requested Cash', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(number_format($data->reqcash, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        ////

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
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '10', 'B', '', '') . '<br />';
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
    $str .= $this->reporter->col('Voyage Report', null, null, false, $border, '', '', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    return $str;
  }
}//end class