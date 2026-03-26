<?php

namespace App\Http\Classes\modules\reportlist\vehicle_scheduling_report;

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

class vehicle_schedule_list
{
  public $modulename = 'Vehicle Schedule List';
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
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint'];

    array_push($fields, 'start', 'end');
    $col1 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-30) as start,
    adddate(left(now(),10),1) as end
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

  public function reportDefault($config)
  {

    $start     = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

    $query = "
  select docno, dateid, department, customername, purpose, timein, timeout, loc, itemname, passengername,
  vehiclename, drivername from(
  select head.docno, date(head.dateid) as dateid,
        dept.clientname as department, customer.clientname as customername, p.purpose, 
        head.schedin as timein, head.schedout as timeout, group_concat(distinct(ship.addr)) as loc,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=stock.trno and v.line=stock.line) as itemname, 
        ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=stock.trno and pass.line=stock.line),'') as passengername, 
        concat(vehicle.clientname,' / ',vehicle.plateno) as vehiclename, driver.clientname as drivername
        from vrhead as head left join vrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        left join billingaddr as ship on ship.line = stock.shipid
        left join purpose_masterfile as p on stock.purposeid = p.line
        left join vrpassenger as pass on stock.trno = pass.trno and pass.line = stock.line
        left join client as cpass on pass.passengerid = cpass.clientid
        left join vritems as item on stock.trno = item.trno and item.line = stock.line
        where head.doc='vr' and head.vehicleid <> 0 and date(head.schedin) between '" . $start . "' and '" . $end . "'
        group by stock.trno,stock.line,
        head.docno, date(head.dateid),
        dept.clientname, customer.clientname, p.purpose, 
        head.schedin, head.schedout,
        item.itemname,vehicle.clientname, vehicle.plateno, driver.clientname
        union all
        select head.docno, date(head.dateid) as dateid,
        dept.clientname as department, customer.clientname as customername, p.purpose, 
        head.schedin as timein, head.schedout as timeout, group_concat(distinct(ship.addr)) as loc,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=stock.trno and v.line=stock.line) as itemname, 
        ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=stock.trno and pass.line=stock.line),'') as passengername, 
        concat(vehicle.clientname,' / ',vehicle.plateno) as vehiclename, driver.clientname as drivername
        from hvrhead as head left join hvrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        left join billingaddr as ship on ship.line = stock.shipid
        left join purpose_masterfile as p on stock.purposeid = p.line
        left join hvrpassenger as pass on stock.trno = pass.trno and pass.line = stock.line
        left join client as cpass on pass.passengerid = cpass.clientid
        left join hvritems as item on stock.trno = item.trno and item.line = stock.line
        where head.doc='vr' and head.vehicleid <> 0 and date(head.schedin) between '" . $start . "' and '" . $end . "'
        group by stock.trno,stock.line,
        head.docno, date(head.dateid),
        dept.clientname, customer.clientname, p.purpose, 
        head.schedin, head.schedout,
        item.itemname,vehicle.clientname, vehicle.plateno, driver.clientname
        ) as x 
        group by docno, dateid, department, customername, purpose, timein, timeout, loc, itemname, passengername,
  vehiclename, drivername
        order by docno, dateid, department, docno, customername, purpose, timein, timeout, loc, itemname, passengername,
  vehiclename, drivername

  ";
    return $this->coreFunctions->opentable($query);
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    return $this->reportDefaultLayout($config);
  }


  private function displayHeader($config, $data)
  {

    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start     = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

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

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($this->modulename), null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("DATE : " . $start . " - " . $end, null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRIP NO.', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE OF TRAVEL', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('START & END TIME', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('VEHICLE', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DRIVER ASSIGNED', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DEPARTMENT', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESTINATION', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PURPOSE', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CARGO', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PASSENGERS', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE & TIME STAMP', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $this->reporter->linecounter = 0;
    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $docno = '';
    $dateid = '';
    $times = '';
    $drivername = '';
    $vehiclename = '';
    $passengername = '';
    $bordrline = '';

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config, $result);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();

      $time = strtotime($data->timein);
      $timein = date('h:ia', $time);

      $time = strtotime($data->timeout);
      $timeout = date('h:ia', $time);

      $str .= $this->reporter->addline();

      if ($docno == $data->docno) {
        $docno = "";
        $bordrline = "LR";
        $bordrlines = "LR";
      } else {
        $docno = $data->docno;
        $bordrline = "TLR";
        $dateid = '';
        $times = '';
        $drivername = '';
        $vehiclename = '';
        $passengername = '';
        $bordrlines = '';
      }

      if ($dateid == $data->dateid) {
        $dateid = "";
      } else {
        $dateid = $data->dateid;
      }

      if ($times == strtoupper($timein) . ' - ' . strtoupper($timeout)) {
        $times = "";
      } else {
        $times = strtoupper($timein) . ' - ' . strtoupper($timeout);
      }

      if ($vehiclename == $data->vehiclename) {
        $vehiclename = "";
      } else {
        $vehiclename = $data->vehiclename;
      }

      if ($drivername == $data->drivername) {
        $drivername = "";
      } else {
        $drivername = $data->drivername;
      }

      if ($passengername == $data->passengername) {
        $passengername = "";
      } else {
        $passengername = $data->passengername;
        $bordrlines = "TLR";
      }



      $str .= $this->reporter->col($docno, '90', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($dateid, '90', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($times, '90', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($vehiclename, '90', null, false, $border, $bordrline, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($drivername, '90', null, false, $border, $bordrline, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->department, '90', null, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->customername, '100', null, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->loc, '90', null, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->purpose, '90', null, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '90', null, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($passengername, '90', null, false, $border, $bordrlines, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, $bordrline, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $result);
        $page = $page + $count;
      }
      $docno = $data->docno;
      $dateid = $data->dateid;
      $times = strtoupper($timein) . ' - ' . strtoupper($timeout);
      $vehiclename = $data->vehiclename;
      $drivername = $data->drivername;
      $passengername = $data->passengername;
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
