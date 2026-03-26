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

class approved_without_vehicle_list
{
  public $modulename = 'Approved Without Vehicle List';
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

    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $query = "
  select docno, dateid, department, customername, purpose, timein, timeout, loc, itemname, passengername, approveddate from(
  select  head.docno, date(head.dateid) as dateid,
        dept.clientname as department, customer.clientname as customername, p.purpose, 
        head.schedin as timein, head.schedout as timeout, ship.addr as loc,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from vritems as v where v.trno=stock.trno and v.line=stock.line) as itemname, 
        ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from vrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=stock.trno and pass.line=stock.line),'') as passengername, 
        head.approveddate
        from vrhead as head left join vrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        left join billingaddr as ship on ship.line = stock.shipid
        left join purpose_masterfile as p on stock.purposeid = p.line
        where head.doc='vr' and head.approveddate is not null and head.vehicleid = 0 and date(head.schedin) between '" . $start . "' and '" . $end . "'
        group by stock.trno,stock.line,head.docno, date(head.dateid) , dept.clientname, customer.clientname, p.purpose, head.schedin, head.schedout, ship.addr, head.approveddate

        union all
        select head.docno, date(head.dateid) as dateid,
        dept.clientname as department, customer.clientname as customername, p.purpose, 
        head.schedin as timein, head.schedout as timeout, ship.addr as loc,
        (select group_concat(concat(itemname,' (',round(qty),uom,')') SEPARATOR '\r') as itemdesc from hvritems as v where v.trno=stock.trno and v.line=stock.line) as itemname, 
        ifnull((select group_concat(client.clientname SEPARATOR '\r') as passenger from hvrpassenger as pass left join client on client.clientid=pass.passengerid where pass.trno=stock.trno and pass.line=stock.line),'') as passengername, 
        head.approveddate
        from hvrhead as head left join hvrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        left join billingaddr as ship on ship.line = stock.shipid
        left join purpose_masterfile as p on stock.purposeid = p.line
        where head.doc='vr' and head.approveddate is not null and head.vehicleid = 0 and date(head.schedin) between '" . $start . "' and '" . $end . "'
        group by stock.trno,stock.line,head.docno, date(head.dateid) , dept.clientname, customer.clientname, p.purpose, head.schedin, head.schedout, ship.addr, head.approveddate
        ) as x 
        group by approveddate, docno, dateid, department, customername, purpose, timein, timeout, loc, itemname, passengername
        order by dateid, timein, department, docno, customername, purpose, timein, timeout, loc, itemname, passengername

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

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($this->modulename), null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("DATE : " . $start . " - " . $end, null, null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRIP NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE OF TRAVEL', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('START & END TIME', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DEPARTMENT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESTINATION', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PURPOSE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CARGO', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PASSENGERS', '160', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATETIME STAMP', '60', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
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
    $layoutsize = '1020';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $docno = '';
    $dateid = '';
    $timesched = '';
    $department = '';
    $approveddate = "";

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
        $dateid = "";
        $timesched = "";
        $department = "";
        $approveddate = "";
        $bordrline = "LR";
      } else {
        $docno = $data->docno;
        $dateid = date_format(date_create($data->dateid), 'm/d/Y');
        $timesched = strtoupper($timein) . ' - ' . strtoupper($timeout);
        $department = $data->department;
        $approveddate = date_format(date_create($data->approveddate), "m/d/Y h:i:sa");
        $bordrline = "TLR";
      }

      $str .= $this->reporter->col($docno, '100', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($dateid, '100', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($timesched, '100', null, false, $border, $bordrline, 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($department, '100', null, false, $border, $bordrline, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->customername, '100', null, false, $border, 'TLR', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->loc, '100', null, false, $border, 'TLR', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->purpose, '100', null, false, $border, 'TLR', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(nl2br(htmlspecialchars($data->itemname)), '100', null, false, $border, 'TLR', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(nl2br(htmlspecialchars($data->passengername)), '160', null, false, $border, 'TLR', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($approveddate, '60', null, false, $border, $bordrline, '', $font, $font_size, 'TLR', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $result);
        $page = $page + $count;
      }
      $docno = $data->docno;
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
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
