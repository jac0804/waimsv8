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

class uncollected_creditable_withholding_tax
{
  public $modulename = 'Uncollected Creditable Withholding Tax';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dbranchname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'radiosortby.options', [
      ['label' => 'Customer', 'value' => 'clientname', 'color' => 'orange'],
      ['label' => 'Area', 'value' => 'area', 'color' => 'orange']
    ]);

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
    left(now(),10) as end,
    '' as branchcode,
    0 as branchid,
    '' as branchname,
    '' as clientname,
    '' as client,
    '' as dclientname,
    '' as dbranchname
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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefault($config);

    return $this->AftiCustomerListReport($config, $result);
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $branch   = $config['params']['dataparams']['branchid'];
    $branchcode   = $config['params']['dataparams']['branchcode'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and client.client = " . $client . " ";
    }

    if ($branchcode != "") {
      $filter .= " and branch.clientid = " . $branch . " ";
    }

    $query = "select date(head.dateid) as dateid, client.clientname, detail.ref, detail.db as amt, head.docno
          from arledger as ar 
          left join gldetail as detail on detail.trno = ar.trno and detail.line = ar.line
          left join glhead as head on head.trno = ar.trno 
          left join client on client.clientid = head.clientid
          left join client as branch on head.branch = branch.clientid
          left join coa on coa.acnoid = detail.acnoid
          where coa.alias = 'ARWT' and ar.bal <> 0 and date(head.dateid) between '" . $start . "' and '" . $end . "'  " . $filter . "
          ";

    return $this->coreFunctions->opentable($query);
  }


  private function AftiCustomerListHeader($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $branchname   = $config['params']['dataparams']['branchname'];


    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date : ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '','', '');

    $str .= $this->reporter->col('Customer : ' . ($clientname != "" ? $clientname : 'ALL'), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Branch : ' . ($branchname != "" ? $branchname : 'ALL'), NULL, null, false, $border, '', 'L', $font, $fontsize, '','', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REF #', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function AftiCustomerListReport($config, $result)
  {


    $count = 10;
    $page = 10;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->AftiCustomerListHeader($config);

    foreach ($result as $key => $data) {
      $dateid = date("m/d/Y", strtotime($data->dateid));
      $amt = number_format($data->amt, 2);
      if ($amt == 0) {
        $amt = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($dateid, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($amt, '120', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class