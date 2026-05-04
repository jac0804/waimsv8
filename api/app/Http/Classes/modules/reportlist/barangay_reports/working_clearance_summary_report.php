<?php

namespace App\Http\Classes\modules\reportlist\barangay_reports;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class working_clearance_summary_report 
{
  public $modulename = 'Working Clearance Summary Report';
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

  public function createHeadField($config)// Essentially the input fields from the web 
  {
     $companyid = $config['params']['companyid'];

     $fields = ['start','end','street']; // 'name' =>

        $col1 = $this->fieldClass->create($fields);
        data_set($col1,'start.type','date');
        data_set($col1,'end.type','date');
        // street lookup
        data_set($col1, 'street.type', 'lookup');
        data_set($col1, 'street.lookupclass', 'lookupstreet');
        data_set($col1, 'street.action', 'lookupstreet');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

     return array('col1'=>$col1, 'col2'=> $col2);
  }

  public function paramsdata($config)//data parameters; the default values of the input fields
  { // 'names' or 'alias'

     $center = $config['params']['center'];
     $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

      return $this->coreFunctions->opentable( "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as area, '' as street
      ");
  }
  public function getloaddata($config)
  {
      return [];
  }
  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status'=>true, 'msg'=>'Msg works', 'report'=>$str,'params'=>$this->reportParams];
  }

  public function reportplotting($config)// Type of Report (radio option) case connection
  {
    $data=$this->data_query($config);
    return $this->reportDefaultLayout($config, $data);
  }

  public function data_query($config)  // Query for Detailed Report
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area = ($config['params']['dataparams']['area']);
    $street = ($config['params']['dataparams']['street']);

    $filter = '';

    if ($area != '') {
      $filter .= " and cinfo.companyaddress ='$area'";
    }

    $query = "
    select date(dateid) as `dateid`, glhead.docno, client.client as `brgyid`, glhead.clientname as `fullname`, glhead.amount, day(dateid) as day, cinfo.companyaddress
    from glhead
    left join  client on client.clientid = glhead.clientid
    left join clientinfo as cinfo on cinfo.clientid = client.clientid
    where docno like 'WR%' and date(dateid) between '$start' and '$end' $filter
    union all
    select  date(dateid) as `dateid`, lahead.docno, client.client as `brgyid`, lahead.clientname as `fullname`, lahead.amount, day(dateid) as day, cinfo.companyaddress
    from lahead
    left join  client on client.client = lahead.client 
    left join clientinfo as cinfo on cinfo.clientid = client.clientid
    where docno like 'WR%' and date(dateid) between '$start' and '$end' $filter
    order by day
    ;";
    // var_dump( $query );
    return $this->coreFunctions->openTable($query);  
  }

  public function DefaultHeader($config)
  {
    $center = $config['params']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $street = ($config['params']['dataparams']['area']);
    $printDate = date('m/d/Y g:i a');
    $str = ''; // required
    $layoutsize = '1000';
    $font = 'Tahoma';
    $fontsize = "12";
    $border = "1px solid ";
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name),'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Print Date : '.$printDate, '500', null, false, $border, '', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WORKING CLEARANCE SUMMARY REPORT','500' , null, false, $border, '', '', $font, '13', 'B', '#8B0000', '');
    $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '9', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE FROM ' . $start . ' TO ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($street == ''){
      $street = 'ALL';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STREET: ' . $street,'250', null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '0', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->col('DATE', '120', null, false, $border, 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '10',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->col('CONTROL #', '180', null, false, $border, 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '10',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->col('BRGY. ID', '180', null, false, $border, 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '10',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->col('FULL NAME', '300', null, false, $border, 'B', 'C', $font, '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '10',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->col('AMOUNT', '80', null, false, $border, 'B', 'C', $font, '10', 'B', '', '', ''); 
    $str .= $this->reporter->col('', '50',null, null, false, '','C', 'Arial', '14', 'B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {
    $str = '';
    $layoutsize = '1000';
    $font = 'Tahoma';
    $fontsize = "10";
    $border = "1px solid ";
    $broken = "1px dashed";


    $totalAmount = 0;
    $totalClearance = 0;
    $date = null;
    $printedDate = null;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->DefaultHeader($config);


    foreach ($result as $data) {
      //When day changes
      if ($date !== null && $date !== $data->day) {
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, null, false, '', 'C', 'Arial', '10', 'B', '', '');
        $str .= $this->reporter->col('TOTAL CLEARANCE : ', '130', null, null, false, '', 'L', 'Arial', '9', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, null, false, '', 'C', 'Arial', '10', 'B', '', '');
        $str .= $this->reporter->col($totalClearance, '120', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '160', null, null, false, '', 'C', 'Arial', '10', 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT : ', '300', null, null, false, '', 'R', 'Arial', '9', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, null, false, '', 'C', 'Arial', '10', 'B', '', '');
        $str .= $this->reporter->col($totalAmount, '140', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '40', null, null, false, '', 'C', 'Arial', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //line
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '0', '20', null, $broken, 'B', 'C', 'Arial', '14', 'B', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        //Reset
        $totalAmount = 0;
        //$totalClearance = 0;
      }
      $date = $data->day;
      $totalAmount += $data->amount;
      $totalClearance += 1;

      $dateDisplay = ($printedDate !== $data->day) ? $data->dateid : '';

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($dateDisplay, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $printedDate = $data->day;
      $str .= $this->reporter->col('', '10', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->docno, '180', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '10', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->brgyid, '180', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '10', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->fullname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '10', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('', '50', null, null, false, '', 'C', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->col('TOTAL CLEARANCE : ', '130', null, null, false, '', 'L', 'Arial', '9', 'B', '', '');
        $str .= $this->reporter->col('', '5', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->col($totalClearance, '120', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '160', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT : ', '300', null, null, false, '', 'R', 'Arial', '9', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->col($totalAmount, '140', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '40', null, null, false, '', 'C', 'Arial', '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '0', null, false, $border, 'B', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '0', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
    

   
}
