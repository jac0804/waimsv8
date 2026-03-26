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

class job_requests
{
  public $modulename = 'Job Request';
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
    $fields = ['radioprint', 'expiry1', 'expiry2', 'start', 'end', 'oicname', 'vesselstatus', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'start.label', 'Date 1 Needed');
    data_set($col1, 'end.label', 'Date 2 Needed');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as expiry1,
    '' as expiry2,
    '' as start,
    '' as end,
    '' as wh,
    '' as vesselstatus,
    '0' as vesseloicid,
    '' as oicname,
    '' as oiccode,
    '' as whname,
    '' as dwhname
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $expiry1 = date('Y-m-d', strtotime($config['params']['dataparams']['expiry1']));
    $expiry2 = date('Y-m-d', strtotime($config['params']['dataparams']['expiry2']));
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $whcode     = $config['params']['dataparams']['wh'];
    $oicname     = $config['params']['dataparams']['oicname'];
    $vesselstatus     = $config['params']['dataparams']['vesselstatus'];

    $filter   = "";

    if ($config['params']['dataparams']['expiry1'] != "" && $config['params']['dataparams']['expiry2'] != "") {
      $filter .= " and date(jreq.expiry) between '$expiry1' and '$expiry2'";
    }

    if ($config['params']['dataparams']['start'] != "" && $config['params']['dataparams']['end'] != "") {
      $filter .= " and date(jreq.dateid) between '$start' and '$end'";
    }

    if ($vesselstatus != "") {
      $filter .= " and jreq.status = '$vesselstatus'";
    }

    if ($oicname != "") {
      $filter .= " and (jreq.oic1 = '$oicname' or jreq.oic2 = '$oicname')";
    }

    if ($whcode != "") {
      $filter .= " and wh.client = '$whcode'";
    }

    $query = "select jreq.line, jreq.docno, left(jreq.issued, 10) as issued, left(jreq.expiry, 10) as expiry, 
  left(jreq.dateid, 10) as dateid, jreq.oic1, jreq.oic2, jreq.rem, jreq.status, 
  jreq.whid, wh.client as whcode, wh.clientname as whname
  from whjobreq as jreq 
  left join client as wh on wh.clientid = jreq.whid
  where 1=1 
  $filter ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $expiry1 = $config['params']['dataparams']['expiry1'];
    $expiry2 = $config['params']['dataparams']['expiry2'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $whcode     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $vesseloicid     = $config['params']['dataparams']['vesseloicid'];
    $oicname     = $config['params']['dataparams']['oicname'];
    $vesselstatus     = $config['params']['dataparams']['vesselstatus'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $str = '';
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

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB REQUESTS', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    if ($whname == "") {
      $whname = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Vessel: ' . $whname, null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($expiry1 == "" || $expiry2 == "") {
      $str .= $this->reporter->col('Expiry: ALL', null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Expiry: ' . $expiry1 . ' - ' . $expiry2, null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($start == "" || $end == "") {
      $str .= $this->reporter->col('Date Needed: ALL', null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Date Needed: ' . $start . ' - ' . $end, null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->endrow();

    if ($vesselstatus == "") {
      $vesselstatus = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Status: ' . $vesselstatus, null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    if ($oicname == "") {
      $oicname = "ALL";
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('OIC: ' . $oicname, null, null, false, '10px solid ', '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Request', '310', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Issued', '70', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Expiry', '70', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Date Needed', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OIC 1', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OIC 2', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Status', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
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

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $whname = "";

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();

        if ($whname != $data->whname) {
          $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->whname, '310', null, false, $border, $border_line, '', $font, $font_size, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '310', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->issued, '70', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->expiry, '70', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->oic1, '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->oic2, '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->rem, '280', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, $border_line, 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, $border_line, 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->displayHeader($config);
          $page = $page + $count;
        }

        $whname = $data->whname;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class