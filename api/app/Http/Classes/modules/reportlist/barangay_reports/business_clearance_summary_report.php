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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;


// add ito sa lookup class ng ('supplier') line 3217

//  if($systemtype == 'BMS' && $config['params']['doc'] == '')
//         {
//           $condition = " where client.isbusiness=1 and client.isinactive =0 order by client.client";
//         }
//         else{
//         $condition = " where client.issupplier=1 and client.isinactive =0 order by client.client";
//         }


//setreportlist
//  $rep_business_clearance_summary_report = "('','\\918','','','',0,1,0,'Business Clearance Summary Report','\\91803',5604,'0'," . $params['levelid'] . ")";

class business_clearance_summary_report
{
  public $modulename = 'Business Clearance Summary Report';
  public $companysetup;
  public $coreFunctions;
  public $fieldClass;
  public $othersClass;
  public $reporter;
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
    $fields = ['radioprint', 'start', 'end', 'area', 'businesstype', 'clientname'];
    $col1 = $this->fieldClass->create($fields);

    // data_set($col1, 'businesstype.type', 'lookup');
    // data_set($col1, 'businesstype.action', 'businesstype');
    // data_set($col1, 'businesstype.class', 'sbccsreadonly');
    // data_set($col1, 'businesstype.label', 'Business Type');
    // data_set($col1, 'businesstype.readonly', true);


    data_set($col1, 'area.type', 'lookup');
    data_set($col1, 'area.action', 'lookupstreet');
    data_set($col1, 'area.class', 'sbccsreadonly');
    data_set($col1, 'area.label', 'Street');
    data_set($col1, 'area.readonly', true);


    data_set($col1, 'clientname.type', 'lookup');
    data_set($col1, 'clientname.readonly', true);
    data_set($col1, 'clientname.class', 'csclient sbccsreadonly');
    data_set($col1, 'clientname.lookupclass', 'supplier');
    data_set($col1, 'clientname.action', 'lookupclient');
    data_set($col1, 'clientname.label', 'Business Name');
    data_set($col1, 'clientname.required', false);




    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);



    return array('col1' => $col1, 'col' => $col3);
  }


  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];

    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 
            'default' as print,
             adddate(left(now(),10),-360) as start,left(now(),10) as end,
             '' as businesstype,
            '' as clientid,
            '' as clientname,
            '' as area,
            '' as client,
            '0' as posttype,
            '' as dclientname,
            '' as dagentname,
            '' as agent,
            '' as agentname,
            '' as agentid,
            '' as docno,
            '' as seqstart,
            '' as pref";

    return $this->coreFunctions->opentable($paramstr);
  }
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }
  public function reportplotting($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end     = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['pref'];
    $businesstype    = $config['params']['dataparams']['businesstype'];
    $client = $config['params']['dataparams']['client'];
    $area = $config['params']['dataparams']['area'];


    return $this->reportDefaultLayout($config);
  }
  public function reportDefault($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end     = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['pref'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $client = $config['params']['dataparams']['clientname'];
    $area = $config['params']['dataparams']['area'];
    $businesstype    = $config['params']['dataparams']['businesstype'];
    $filter = "";

    $filter1 = "";

     
    if ($area != '') {
      $filter .= " and cl.area = '$area' ";
    }

    if ($businesstype != '') {
      $filter .= " and head.bstype = '$businesstype' ";
    }
    if ($client != '') {
      $filter .= " and cl.clientname = '$client' ";
    }
  

    $query = "select st.code,cl.area,date(head.dateid) as dateid, loccl.clearance as purpose,cl.client as brgy_id, head.docno, cl.clientname, head.amount, cl.addr,head.bstype
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client as cl on cl.client = head.client
        left join cntnum as cnum on cnum.trno = head.trno
        left join locclearance as  loccl on loccl.line = head.purposeid
        LEFT JOIN street AS st ON st.street = cl.area
        where cnum.doc = 'BC' and date(head.dateid) between '$start' and '$end' $filter
        
        union all 
        
        select st.code,cl.area,date(head.dateid) as dateid, loccl.clearance as purpose,cl.client as brgy_id, head.docno, cl.clientname, head.amount, cl.addr,head.bstype
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client as cl on cl.clientid = head.clientid
        left join cntnum as cnum on cnum.trno = head.trno
        left join locclearance as  loccl on loccl.line = head.purposeid
        LEFT JOIN street AS st ON st.street = cl.area
        where cnum.doc = 'BC'  and date(head.dateid) between '$start' and '$end' $filter
        order by dateid";
              
// var_dump($query);



    return $this->coreFunctions->opentable($query);
  }
  public function displayHeader($config)
  {
    $border = '1px solid';
    $font = 'tahoma';
    $font_size = 10;
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $prefix    = $config['params']['dataparams']['pref'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $client = $config['params']['dataparams']['clientname'];
    $area = $config['params']['dataparams']['area'];
    $printDate = date('Y-m-d H:i:s');
    $layoutsize = 1000;
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $startFormat = date('M-d-Y', strtotime($start));
    $endFormat = date('M-d-Y', strtotime($end));
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    $str = '';
    

    // $logopath = URL::to($this->companysetup->getlogopath($config['params']) . 'sbclogo1.jpg');
    // $path = $this->companysetup->getlogopath($config['params']);
    // $path = str_replace('public/', '', $path);
    // $logopath = URL::to($path . 'sbclogo1.jpg');

    // $str .= "<div style='margin-bottom:30px; text-align:left;margin-left:-110px;margin-top:-20px;'>"; //margin-top:-30px;
    // $str .= "<img src='{$logopath}' width='1350' height='300'>";
    // $str .= "</div>";

       $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $font_size);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br></br>';

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
       $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
      //  $str .= $this->reporter->col('BARANGGAY', '500', '20', false, '2px solid', '', 'L', $font, '12', 'B', '', '');
       
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();
       

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
       $str .= $this->reporter->col('BUSINESS CLEARANCE SUMMARY REPORT', '1000', null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
       $str .= $this->reporter->col('DATE FROM '.$startFormat .' to ' .$endFormat, '100', null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
      //  $str .= $this->reporter->col('STREET: '. $street, null, null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
      //  $str .= $this->reporter->col('STREET: '. (!empty($area) ? $area : 'ALL'), null, null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
       $str .= $this->reporter->col('STREET: '. (!empty($area) ? $area : 'ALL'), '1000', null, false, '2px solid', '', 'L', $font, '12', 'B', '', '');
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();

     

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $str = '';
    $result = $this->reportDefault($config);


    $border = '1px solid';
    $font = 'Tahoma';
    $font_size = 10;
    $count = 55;
    $page = 55;
    $str = '';
    $border2 = '1px solid';
    $layoutsize = 1000;

    $totalDb = 0;
    $totalCr = 0;
    $totalRefDb = 0;
    $totalRefCr = 0;

    $prevLine = '';
    $prevtrno = '';
    $prevDocno = '';
    $prevClient = '';
    $prevRem = '';
    $balance = 0;


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }


    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
       $str .= $this->reporter->col('', '1000', null, false, '3px solid', 'B', 'L', $font, '12', '', '', '');
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();

       $str .= $this->reporter->begintable($layoutsize);
       $str .= $this->reporter->startrow();
       $str .= $this->reporter->col('DATE', '100', null, false, '2px solid', 'B', 'C', $font, $font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, $font, 'B', '', '');
       $str .= $this->reporter->col('CONTORL #', '130', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, $font, 'B', '', '');
       $str .= $this->reporter->col('BRGY. ID', '160', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, $font, 'B', '', '');
       $str .= $this->reporter->col('FULL NAME', '180', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, $font, 'B', '', '');
       $str .= $this->reporter->col('ADDRESS', '160', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font, $font, 'B', '', '');
       $str .= $this->reporter->col('BUSINESS TYPE', '140', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size ,'B', '', '');
       $str .= $this->reporter->col('AMOUNT', '105', null, false, '2px solid', 'B', 'C', $font,$font_size, 'B', '', '');
       $str .= $this->reporter->endrow();
       $str .= $this->reporter->endtable();



    $prevDate = '';
    $dateTotal = 0;
    $dateCount = 0;

    $grandTotal = 0;
    $grandCount = 0;

    foreach ($result as $key => $data) {

    if ($prevDate != '' && $prevDate != $data->dateid) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL CLEARANCE:', '170', null, false, '', 'B', 'LT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($dateCount, '150', null, false, '1px solid', 'TBRL', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT : ', '210', null, false, '', 'B', 'RT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($dateTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '120', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '160', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '225', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '160', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '225', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->col('', '163', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        
        $dateTotal = 0;
        $dateCount = 0;
    }

    if ($prevDate != $data->dateid) {
        $str .= $this->reporter->begintable($layoutsize);
    }

    
    $dateTotal += $data->amount;
    $dateCount++;

    $grandTotal += $data->amount;
    $grandCount++;

   
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data->dateid, '100', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col($data->docno, '130', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col($data->brgy_id, '160', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col($data->clientname, '180', null, false, '2px solid', '', 'L', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col($data->addr, '160', null, false, '2px solid', '', 'L', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col($data->bstype, '140', null, false, '2px solid', '', 'L', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid', '', 'C', $font,$font_size, '', '', '');
    $str .= $this->reporter->col(number_format($data->amount,2), '105', null, false, '2px solid', '', 'R', $font,$font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $prevDate = $data->dateid;
}


    if ($prevDate != '') {

    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TOTAL CLEARANCE:', '170', null, false, '', 'B', 'LT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($dateCount, '150', null, false, '1px solid', 'TBRL', 'CT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT : ', '210', null, false, '', 'B', 'RT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($dateTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
            

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '120', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '160', '20', false, '1px dashed', 'B', 'LT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '225', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '160', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '225', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '10', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->col('', '163', '20', false, '1px dashed', 'B', 'RT', $font, '', '', '', '', '', 0, '', 0, 0, '#C4C0C0');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
}


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', '10', false, '2px solid', '', 'C', $font, $font, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

  
    $str .= $this->reporter->col('', '50', null, false, '', 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '170', null, false, '', 'B', 'LT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'CT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '290', null, false, '', 'B', 'LT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL : ', '210', null, false, '', 'B', 'RT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandTotal, 2) ?: '-', '130', null, false, '1px solid', 'TBRL', 'RT', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  
}
}