<?php

namespace App\Http\Classes\modules\modulereport\afti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;

class px
{
  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
  }

  public function createreportfilter($config)
  {
    $companyid = $config['params']['companyid'];
   
      $fields = ['radioprint','prepared', 'checked', 'noted', 'approved', 'print'];
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'radioprint.options', [
        ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ['label' => 'Excel', 'value' => 'excel', 'color' => 'red']
      ]);

      data_set($col1, 'noted.type', 'lookup');
      data_set($col1, 'noted.action', 'lookuppreparedby');
      data_set($col1, 'noted.lookupclass', 'noted');
      data_set($col1, 'noted.readonly', true);

      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookuppreparedby');
      data_set($col1, 'prepared.lookupclass', 'prepared');
      data_set($col1, 'prepared.readonly', true);

      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookuppreparedby');
      data_set($col1, 'approved.lookupclass', 'approved');
      data_set($col1, 'approved.readonly', true);

      data_set($col1, 'checked.type', 'lookup');
      data_set($col1, 'checked.action', 'lookuppreparedby');
      data_set($col1, 'checked.lookupclass', 'checked');
      data_set($col1, 'checked.readonly', true);

      // data_set($col1, 'radiopoafti.options', [
      //   ['label' => 'AFTECH PO', 'value' => 'AFTI', 'color' => 'red'],
      //   ['label' => '2 signatories', 'value' => '2', 'color' => 'red'],
      //   ['label' => '3 signatories', 'value' => '3', 'color' => 'red'],
      //   ['label' => '4 signatories', 'value' => '4', 'color' => 'red'],
      //   ['label' => 'USD Format', 'value' => 'TC', 'color' => 'red']
      // ]);
    

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    
      return $this->coreFunctions->opentable(
        "select
          'PDFM' as print,
          '' as noted,
          '' as prepared,
          '' as approved,
          '' as checked
       "
      );
    
  }

  
  public function reportplotting($config, $data)
  {
    $layout = $config['params']['dataparams']['print'];
    // $companyid = $config['params']['companyid'];
    $pcfAdminAccess = $this->othersClass->checkAccess($config['params']['user'], 5389);
    
    if($layout == 'PDFM'){
      if($pcfAdminAccess){
        $str = $this->pcf_admin_pdf($config, $data);
      }else{
        $str = $this->pcf_default_pdf($config, $data);  
      }
    }else{
      if($pcfAdminAccess){
        $str = $this->pcf_admin_default($config, $data);
      }else{
        $str = $this->pcf_default_default($config, $data);  
      }
    }
    

    return $str;
  }

  public function report_default_query($config)
  {
    
    $trno = $config['params']['dataid'];
    $query = "
      
      select oandaphpusd,oandausdphp,osphpusd,head.clientname,project,agent.clientname as agentname,
      head.rem as reason,head.remarks,head.projectid,head.dateid,head.pcfno,
      head.dtcno,head.poref,head.aftistock,
      p.name as stockgrp_name,i.itemname as model,stock.rrqty,stock.rrcost as stocklist,
      stock.ext as totallist,stock.srp,stock.totalsrp,stock.tp,stock.totaltp,head.terms,head.termsdetails,head.fullcomm,head.checkdate
      from pxhead as head
      left join pxstock as stock on stock.trno=head.trno
      left join client as agent on agent.clientid = head.agentid
      left join item as i on i.itemid = stock.itemid
      left join projectmasterfile as p on p.line = i.projectid 
      where head.trno='$trno'
      union all
      
      select oandaphpusd,oandausdphp,osphpusd,head.clientname,project,agent.clientname as agentname,
      head.rem as reason,head.remarks,head.projectid,head.dateid,head.pcfno,
      head.dtcno,head.poref,head.aftistock,
      p.name as stockgrp_name,i.itemname as model,stock.rrqty,stock.rrcost as stocklist,
      stock.ext as totallist,stock.srp,stock.totalsrp,stock.tp,stock.totaltp,head.terms,head.termsdetails,head.fullcomm,head.checkdate
      from hpxhead as head
      left join hpxstock as stock on stock.trno=head.trno
      left join client as agent on agent.clientid = head.agentid
      left join item as i on i.itemid = stock.itemid
      left join projectmasterfile as p on p.line = i.projectid 
      where head.trno='$trno'";
      // var_dump($query);
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn


  //general access
  public function pcf_default_pdf($params, $data)
  {
    
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = '';
    $fontbold = '';
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

    
        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '50', '40', 100, 90);
        

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    $fontsize8 = 8;
    $fontsize9 = 9;
    $fontsize10 = 10;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [1100, 1100]);
    PDF::SetMargins(40, 40);


    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', $fontsize9);
    // PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s'), '', 'L');


    PDF::MultiCell(0, 0, "\n");

    // PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 200, 50);



    
    PDF::Image('/images/afti/qslogo.png', '', '', 200, 50);

    
    PDF::SetFont($fontbold, '', $fontsize10);
    
    PDF::MultiCell(0, 0, "\n\n\n\n\n");
    PDF::MultiCell(0, 20, "Project Costing Form", '', 'L');


    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(90, 20, "CHECKING ON", '', 'L',false,0);
    PDF::MultiCell(260, 20, date_format(date_create($data[0]->checkdate), 'm/d/Y'), '', 'L');
    
    // PDF::MultiCell(150, 20, "OANDA PHP-USD", '', 'L',false,0);
    // PDF::MultiCell(150, 20, number_format($data[0]->oandaphpusd,2), '', 'L');

    // PDF::MultiCell(150, 20, "OANDA USD-PHP", '', 'L',false,0);
    // PDF::MultiCell(150, 20, number_format($data[0]->oandausdphp,2), '', 'L');
    
    // PDF::MultiCell(150, 20, "OS PHP-USD", '', 'L',false,0);
    // PDF::MultiCell(150, 20, number_format($data[0]->osphpusd,2), '', 'L');
    
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(150, 20, "Name Of Company:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->clientname, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "DATE:", '', 'L',false,0);
    PDF::MultiCell(290, 20, date_format(date_create($data[0]->dateid), 'm/d/Y'), '', 'L');

    
    PDF::MultiCell(150, 20, "Name Of Project:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->project, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "PCF No.:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->pcfno, '', 'L');


    PDF::MultiCell(150, 20, "Project Owner:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->agentname, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "DTC No.:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->dtcno, '', 'L');


    PDF::MultiCell(150, 20, "Reason:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->reason, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "PO Reference:", '', 'L',false,0);
    if($data[0]->poref == 'NO PO YET'){
      PDF::writeHTMLCell(290, 20, '', '', '<div style="text-align: left; color:red;">'.$data[0]->poref.'</div>', '', 1);
    }else{
      PDF::MultiCell(290, 20, $data[0]->poref, '', 'L');
    }
    
   // PDF::MultiCell(260, 20, $data[0]->poref, '', 'L');

    
    PDF::MultiCell(150, 20, "Remarks:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->remarks, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "AFTI STOCK:", '', 'L',false,0);
    if($data[0]->aftistock==0){
      PDF::MultiCell(290, 20, 'No', '', 'L');
    }else{
      PDF::MultiCell(290, 20, 'Yes', '', 'L');
    }
    
    
    PDF::MultiCell(150, 20, "PROJECT ID:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->projectid, '', 'L',false,0);
    PDF::MultiCell(240, 20, "", '', 'L',false,0);
    PDF::MultiCell(150, 20, "", '', 'L',false,0);
    PDF::MultiCell(290, 20, "", '', 'L');

    

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(250, 15, " " . "ITEM GROUP", 'B', 'L', false, 0);
    PDF::MultiCell(250, 15, " " . "MODEL", 'B', 'L', false, 0);
    PDF::MultiCell(100, 15, " " . "QUANTITY", 'B', 'R', false, 0);
    PDF::MultiCell(100, 15, " " . "LIST", 'B', 'R', false, 0);

    PDF::MultiCell(100, 15, " " . "TOTAL LIST", 'B', 'R', false, 0);
    PDF::MultiCell(100, 15, " " . "SELLING", 'B', 'R', false, 0);
    PDF::MultiCell(100, 15, " " . "TOTAL SELL", 'B', 'R', false);
    $totallist = 0;
    $totalsrp = 0;

    $totalhioki = 0;
    $hashioki = 0;
    // foreach ($data as $key => $value) {
    //   // var_dump($key);
    //   // var_dump($value->dtcno);
    //   if(str_contains(strtoupper($value->stockgrp_name),'HIOKI')){
    //     $totalhioki += $value->srp;
    //     $hashioki = 1;
    //   }
      
    //   PDF::SetFont($font, '', $fontsize8);
    //   PDF::MultiCell(150, 10, $value->stockgrp_name, '', 'L', false, 0);
    //   PDF::MultiCell(150, 10, $value->model, '', 'L', false, 0);
    //   PDF::MultiCell(80, 10, number_format($value->rrqty), '', 'R', false, 0);
    //   PDF::MultiCell(70, 10, 'PHP '.number_format($value->stocklist,2), '', 'R', false, 0);

    //   PDF::MultiCell(70, 10, 'PHP '.number_format($value->totallist,2), '', 'R', false, 0);
    //   PDF::MultiCell(100, 10, 'PHP '.number_format($value->srp,2), '', 'R', false, 0);
    //   PDF::MultiCell(100, 10, 'PHP '.number_format($value->totalsrp,2), '', 'R', false);

    //   $totallist += $value->totallist;
    //   $totalsrp += $value->totalsrp;

    // }

    
    if (!empty($data)) {
      foreach ($data as $key => $value) {

        if(str_contains(strtoupper($value->stockgrp_name),'HIOKI')){
          $totalhioki += $value->totalsrp;
          $hashioki = 1;
        }
        
        $maxrow = 1;


        $itemgroup =  $value->stockgrp_name;
        $model =  $value->model;

        // $itemgroup =  '$value->stockgcasdq23rp_name';
        // $model =  '$value->modezxczxzxcx zczxcx zxczzl';
        

        $rrqty = number_format($value->rrqty, 0);
        $stocklist = number_format($value->stocklist, 2);
        $coltotallist = number_format($value->totallist, 2);
        $srp = number_format($value->srp, 2);
        $coltotalsrp = number_format($value->totalsrp, 2);

        
        $arr_itemgroup = $this->reporter->fixcolumn([$itemgroup], '45', 0);
        $arr_model = $this->reporter->fixcolumn([$model], '40', 0);

        $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '15', 0);
        $arr_stocklist = $this->reporter->fixcolumn([$stocklist], '15', 0);
        $arr_totallist = $this->reporter->fixcolumn([$coltotallist], '15', 0);
        $arr_srp = $this->reporter->fixcolumn([$srp], '15', 0);
        $arr_totalsrp = $this->reporter->fixcolumn([$coltotalsrp], '15', 0);

        

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemgroup, $arr_model, $arr_rrqty, $arr_stocklist, $arr_totallist, $arr_srp, $arr_totalsrp]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize8);
          
          
      
          PDF::writeHTMLCell(250, 10, '', '', '<div style="text-align: left;">'.    (isset($arr_itemgroup[$r]) ? $arr_itemgroup[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(250, 10, '', '', '<div style="text-align: left;">'.    (isset($arr_model[$r]) ? $arr_model[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;">'.   (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_stocklist[$r]) ? $arr_stocklist[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_totallist[$r]) ? $arr_totallist[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_srp[$r]) ? $arr_srp[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_totalsrp[$r]) ? $arr_totalsrp[$r] : '').'</div>', '', 1);

        }

        $totallist += $value->totallist;
        $totalsrp += $value->totalsrp;
        // $totaltp += $value->totaltp;

      }
    }

    
    PDF::SetFont($fontbold, '', $fontsize8);
    PDF::MultiCell(700, 10, 'TOTAL', 'T', 'R', false, 0);

    PDF::MultiCell(100, 10, 'PHP '.number_format($totallist,2), 'T', 'R', false, 0);
    PDF::MultiCell(100, 10, '', 'T', 'R', false, 0);
    PDF::MultiCell(100, 10, 'PHP '.number_format($totalsrp,2), 'T', 'R', false);

    if($hashioki){
      PDF::MultiCell(700, 10, '', 'T', 'R', false, 0);

      PDF::MultiCell(100, 10, '', 'T', 'R', false, 0);
      PDF::MultiCell(100, 10, 'HIOKI TOTAL', 'T', 'R', false, 0);
      PDF::MultiCell(100, 10, 'PHP '.number_format($totalhioki,2), 'T', 'R', false);
    }
    

    $trno = $params['params']['dataid'];

    $query = "
      select 
      case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,
      chk.budget,chk.actual,chk.rem,chk.reftrno 
      from pxhead as head
      left join pxchecking as chk on chk.trno=head.trno 
      left join reqcategory as req on req.line=chk.expenseid
      where 
      req.category is not null
      and head.trno='$trno'
      union all
      select 
      case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,
      chk.budget,chk.actual,chk.rem,chk.reftrno 
      from hpxhead as head
      left join hpxchecking as chk on chk.trno=head.trno 
      left join reqcategory as req on req.line=chk.expenseid
      
      where 
      req.category is not null
	    and head.trno='$trno'
      ";

    $data2 = $this->coreFunctions->opentable($query);

    
    PDF::MultiCell(0, 0, "\n\n\n");

    
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(500, 15, 'EXPENSE', 'B', 'L', false, 0);
    PDF::MultiCell(200, 15, "AMOUNT", 'B', 'R', false);

    $totalbudget = 0;
    foreach ($data2 as $key2 => $value2) {
      
      PDF::SetFont($font, '', $fontsize8);
      
      PDF::MultiCell(500, 0, $value2->expense, '', 'L', false, 0);
      PDF::MultiCell(200, 0, number_format($value2->budget,2), '', 'R', false);

      $totalbudget += $value2->budget;


    }

    
      PDF::SetFont($font, 'B', $fontsize9);
      PDF::MultiCell(500, 0, 'TOTAL', 'T', 'R', false, 0);
      PDF::MultiCell(200, 0, number_format($totalbudget,2), 'T', 'R', false);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function pcf_default_default($params, $data)
  {
      // --- 1. Data & Configuration Extraction ---
      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = 2;//$this->companysetup->getdecimal('price', $params['params']);
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
      $totalext = 0;

      // --- 2. Reporter Setup ---
      $str = '';
      $count = 55;
      $page = 54;
      $font = "Arial"; // Default font style
      $fontsize = "11";
      $border = "1px solid"; // Default border for data rows

      $str .= $this->reporter->beginreport('1200');

      // --- 3. Logo/Header (Based on your start) ---
      $aftilogo = URL::to($this->companysetup->getlogopath($params['params']) . 'aftilogo.png');
      
      // 1st row (Logo)
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      // Assuming the image col takes full width
      $str .= $this->reporter->col('<img src ="' . $aftilogo . '" alt="BIR" width="60px" height ="60px">', null, null, false, $border, '', 'L', 'Century Gothic', '15', 'B', '', '1px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // --- 4. Report Title ---
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project Costing Form', null, null, false, $border, '', 'L', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // --- 5. Header Details ---
      // CHECKING ON
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CHECKING ON', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col(date_format(date_create($data[0]->checkdate), 'm/d/Y'), 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // Name Of Company / DATE (Already merged above, splitting based on original TCPDF)
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Name Of Company:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->clientname, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('DATE:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col(date_format(date_create($data[0]->dateid), 'm/d/Y'), 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      // Name Of Project / PCF No.
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Name Of Project:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->project, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('PCF No.:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->pcfno, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // Project Owner / DTC No.
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project Owner:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->agentname, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('DTC No.:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->dtcno, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // Reason / PO Reference
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Reason:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->reason, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('PO Reference:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      
      $poref_content = $data[0]->poref;
      if($data[0]->poref == 'NO PO YET'){
          // TCPDF used writeHTMLCell for red text, here we embed style in the content
          $poref_content = '<span style="color:red;">'.$data[0]->poref.'</span>';
      }
      $str .= $this->reporter->col($poref_content, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // Remarks / AFTI STOCK
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Remarks:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->remarks, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('AFTI STOCK:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      
      $aftistock_val = ($data[0]->aftistock==0) ? 'No' : 'Yes';
      $str .= $this->reporter->col($aftistock_val, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // PROJECT ID / Empty Row Spacer
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PROJECT ID:', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($data[0]->projectid, 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 90, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', 260, null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      $str .= '<br><br>';

      // --- 6. Items Table Header ---
      // PDF::MultiCell(150, 15, " " . "ITEM GROUP", 'B', 'L', false, 0); ...
      $b_header = 'B';
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp;ITEM GROUP', 150, null, false, $border,$b_header,  'L', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;MODEL', 150, null, false, $border,$b_header,  'L', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;QUANTITY', 80, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;LIST (PHP)', 70, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;TOTAL LIST (PHP)', 70, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;SELLING (PHP)', 100, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('&nbsp;TOTAL SELL (PHP)', 100, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->endrow();

      // --- 7. Items Table Data Loop ---
      $totallist = 0;
      $totalsrp = 0;
      $totalhioki = 0;
      $hashioki = 0;

      if (!empty($data)) {
          foreach ($data as $key => $value) {

              if(str_contains(strtoupper($value->stockgrp_name),'HIOKI')){
                  $totalhioki += $value->totalsrp;
                  $hashioki = 1;
              }
              
              // --- REPORTER VERSION OF FIXCOLUMN LOGIC ---
              // Note: The original TCPDF version relied on fixcolumn/getmaxcolumn to handle wrapping
              // which is highly specific. In the string-building world, we typically rely on
              // the reporter's internal column/row wrapping logic or simplify the presentation.
              // Since we don't have the $this->reporter->fixcolumn source, we'll use a single row
              // representation per item, assuming the $this->reporter->col can handle content height.

              $rrqty = number_format($value->rrqty, 0);
              $stocklist = number_format($value->stocklist, $decimalprice);
              $totallist += $value->totallist;
              $display_totallist = number_format($value->totallist, 2);
              $srp = number_format($value->srp, 2);
              $totalsrp += $value->totalsrp;
              $display_totalsrp = number_format($value->totalsrp, 2);

              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($value->stockgrp_name, 150, null, false, $border, '', 'L', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($value->model, 150, null, false, $border, '', 'L', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($rrqty, 80, null, false, $border, '', 'R', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($stocklist, 70, null, false, $border, '', 'R', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($display_totallist, 70, null, false, $border, '', 'R', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($srp, 100, null, false, $border, '', 'R', $font, '8', '', '', '1px');
              $str .= $this->reporter->col($display_totalsrp, 100, null, false, $border, '', 'R', $font, '8', '', '', '1px');
              $str .= $this->reporter->endrow();
          }
      }
      
      // --- 8. Items Table Footer Totals ---
      $b_top = 'T';

      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col('', 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col('', 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col('TOTAL', 450, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totallist, 2), 70, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col('', 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totalsrp, 2), 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
      $str .= $this->reporter->endrow();

      if($hashioki){
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 450, null, false, $border,$b_top, 'R', $font, '8', 'B', '', '1px');
          $str .= $this->reporter->col('', 70, null, false, $border,$b_top, 'R', $font, '8', 'B', '', '1px');
          $str .= $this->reporter->col('HIOKI TOTAL', 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
          $str .= $this->reporter->col(number_format($totalhioki, 2), 100, null, false, $border,$b_top,  'R', $font, '8', 'B', '', '1px');
          $str .= $this->reporter->endrow();
      }
      
      $str .= $this->reporter->endtable();
      $str .= '<br><br><br>';


      // --- 9. Expenses Table Header and Data ---
      $trno = $params['params']['dataid'];
      $query = "
        select 
        case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,
        chk.budget,chk.actual,chk.rem,chk.reftrno 
        from pxhead as head
        left join pxchecking as chk on chk.trno=head.trno 
        left join reqcategory as req on req.line=chk.expenseid
        where 
        req.category is not null
        and head.trno='$trno'
        union all
        select 
        case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,
        chk.budget,chk.actual,chk.rem,chk.reftrno 
        from hpxhead as head
        left join hpxchecking as chk on chk.trno=head.trno 
        left join reqcategory as req on req.line=chk.expenseid
        
        where 
        req.category is not null
        and head.trno='$trno'
        ";
      $data2 = $this->coreFunctions->opentable($query);

      // Expenses Header
      $str .= $this->reporter->begintable('400'); // Total width 300+100=400px
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('EXPENSE', 300, null, false, $border,$b_header,  'L', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col('AMOUNT', 100, null, false, $border,$b_header,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->endrow();

      // Expenses Data Loop
      $totalbudget = 0;
      foreach ($data2 as $key2 => $value2) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($value2->expense, 300, null, false, $border, '', 'L', $font, '8', '', '', '1px');
          $str .= $this->reporter->col(number_format($value2->budget, 2), 100, null, false, $border, '', 'R', $font, '8', '', '', '1px');
          $str .= $this->reporter->endrow();
          $totalbudget += $value2->budget;
      }

      // Expenses Total
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TOTAL', 300, null, false, $border,$b_top,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totalbudget,2), 100, null, false, $border,$b_top,  'R', $font, '9', 'B', '', '1px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      // --- 10. Report Finalization ---
      $str .= $this->reporter->endreport();
      
      return $str;
  }

  
  //excel near 1:1 copy
  public function pcf_admin_pdf($params, $data)
  {
    
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = '';
    $fontbold = '';
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

    
        // PDF::Image($this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '50', '40', 100, 90);
        

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    $fontsize8 = 8;
    $fontsize9 = 9;
    $fontsize10 = 10;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [1100, 1100]);
    PDF::SetMargins(40, 40);


    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', $fontsize9);
    // PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s'), '', 'L');


    PDF::MultiCell(0, 0, "\n");

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 200, 50);
        
    PDF::SetFont($fontbold, '', $fontsize10);
    
    PDF::MultiCell(0, 0, "\n\n\n\n\n");
    PDF::MultiCell(0, 20, "Project Costing Form", '', 'L');


    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, "CHECKING ON ".date_format(date_create($data[0]->checkdate), 'F d, Y'), '', 'L',false);
    

    PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: left;">OANDA PHP-USD</div>', 0, 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;">'.number_format($data[0]->oandaphpusd,6).'</div>', 0, 1);

    PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: left;">OANDA USD-PHP</div>', 0, 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;">'.number_format($data[0]->oandausdphp,6).'</div>', 0, 1);

    PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: left;">OS PHP-USD</div>', 0, 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;">'.number_format($data[0]->osphpusd,6).'</div>', 0, 1);


    
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(150, 20, "Name Of Company:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->clientname, '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);
    PDF::MultiCell(100, 20, "DATE:", '', 'R',false,0);
    PDF::MultiCell(100, 20, date_format(date_create($data[0]->dateid), 'm/d/Y'), '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,1);

    
    PDF::MultiCell(150, 20, "Name Of Project:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->project, '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;">PCF No.:</div>', 0, 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="color:red; text-align: center;">'.$data[0]->pcfno.'</div>', 0, 0);
    PDF::MultiCell(100, 0, "", '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,1);


    PDF::MultiCell(150, 20, "Project Owner:", '', 'L',false,0);
    PDF::MultiCell(290, 20, $data[0]->agentname, '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;">DTC No.:</div>', 0, 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="color:red; text-align: center;">'.$data[0]->dtcno.'</div>', 0, 0);
    PDF::MultiCell(100, 0, "", '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,1);


    PDF::writeHTMLCell(150, 0, '', '', '<div style="color:red; text-align: left;">Reason:</div>', 0, 0);
    PDF::writeHTMLCell(290, 0, '', '', '<div style="color:red; text-align: left;">'.$data[0]->reason.'</div>', 0, 0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);
    PDF::MultiCell(100, 0, "PO Reference:", '', 'R',false,0);
    if($data[0]->poref == 'NO PO YET'){
      PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center; color:red;">'.$data[0]->poref.'</div>', 0, 0);
    }else{
      PDF::MultiCell(100, 0, $data[0]->poref, '', 'C',false,0);
    }
     PDF::MultiCell(100, 0, "", '', 'C',false,0);
     PDF::MultiCell(100, 0, "", '', 'C',false,1);

    // PDF::MultiCell(90, 20, "PO Reference:", '', 'L',false,0);
    // PDF::MultiCell(260, 20, $data[0]->poref, '', 'L');



    PDF::MultiCell(150, 0, "PROJECT ID:", '', 'L',false,0);
    PDF::MultiCell(290, 0, $data[0]->projectid, '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);
     //NEW ADD
     PDF::MultiCell(100, 0, "PAYMENT TERMS:", '', 'R',false,0);//730
     PDF::MultiCell(100, 0, $data[0]->terms, '', 'C',false,0); //300
     PDF::SetFont($fontbold, '', $fontsize9);
     PDF::MultiCell(100, 0, "ONGOING", '', 'C',false,0);
     PDF::MultiCell(100, 0, "APPROVED", '', 'C',false,1);
     
    
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 0, "", '', 'L',false,0);
    PDF::MultiCell(290, 0, '', '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);

    PDF::MultiCell(100, 0, "AFTI STOCK:", '', 'R',false,0);
    if($data[0]->aftistock==0){
      PDF::MultiCell(100, 0, 'NO', '', 'C',false,0);
    }else{
      PDF::MultiCell(100, 0, 'YES', '', 'C',false,0);
    }
    if($data[0]->termsdetails=='On going'){
     PDF::MultiCell(100, 0, "YES", '', 'C',false,0);
    }else{
     PDF::MultiCell(100, 0, "NO", '', 'C',false,0);
    }

    if($data[0]->termsdetails=='Approve'){
     PDF::MultiCell(100, 0, "YES", '', 'C',false,1);
    }else{
    PDF::MultiCell(100, 0, "NO", '', 'C',false,1);
    }



    //commission

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 0, "", '', 'L',false,0);
    PDF::MultiCell(290, 0, '', '', 'L',false,0);
    PDF::MultiCell(190, 0, "", '', 'L',false,0);

    PDF::MultiCell(100, 0, "COMMISSION:", '', 'R',false,0);
    PDF::MultiCell(100, 0, $data[0]->fullcomm, '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,0);
    PDF::MultiCell(100, 0, "", '', 'C',false,1);


    PDF::SetFont($font, '', $fontsize9);
    PDF::writeHTMLCell(160, 15, '', '', '<div style="text-align: center;"><b><u>ITEM GROUP</u></b></div>', 'TBL', 0);
    PDF::writeHTMLCell(160, 15, '', '', '<div style="text-align: center;"><b><u>MODEL</u></b></div>', 'TBL', 0);
    PDF::writeHTMLCell(110, 15, '', '', '<div style="text-align: center;"><b><u>QUANTITY</u></b></div>', 'TBL', 0);

    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>LIST</u></b></div>', 'TBL', 0);
    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>TOTAL LIST</u></b></div>', 'TBL', 0);

    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>SELLING</u></b></div>', 'TBL', 0);
    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>TOTAL SELL</u></b></div>', 'TBL', 0);

    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>TP</u></b></div>', 'TBL', 0);
    PDF::writeHTMLCell(100, 15, '', '', '<div style="text-align: center;"><b><u>TOTAL TP</u></b></div>', 'TBLR', 1);


    $totallist = 0;
    $totalsrp = 0;
    $totaltp = 0;
    $totalhioki = 0;
    $hashioki = 0;
    $totalosl=0;
    $hasosl = 0;
    $totalselling=0;
    
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        
        if(str_contains(strtoupper($value->stockgrp_name),'HIOKI')){
          // $totalhioki += $value->totalsrp;
          $totalhioki += $value->totallist;
          $hashioki = 1;
        }

        if(str_contains(strtoupper($value->stockgrp_name),'OUTSOURCE-LOCAL')){
          $totalosl += $value->totallist;
          $totalselling +=$value->totalsrp;
          $hasosl = 1;
        }

       
        $maxrow = 1;

        $itemgroup =  $value->stockgrp_name;
        $model =  $value->model;

        $rrqty = number_format($value->rrqty, 0);
        $stocklist = number_format($value->stocklist, 2);
        $coltotallist = number_format($value->totallist, 2);
        $srp = number_format($value->srp, 2);
        $coltotalsrp = number_format($value->totalsrp, 2);
        $tp = number_format($value->tp, 2);
        $coltotaltp = number_format($value->totaltp, 2);

        $arr_itemgroup = $this->reporter->fixcolumn([$itemgroup], '25', 0);
        $arr_model = $this->reporter->fixcolumn([$model], '20', 0);
        $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '12', 0);
        $arr_stocklist = $this->reporter->fixcolumn([$stocklist], '12', 0);
        $arr_totallist = $this->reporter->fixcolumn([$coltotallist], '12', 0);
        $arr_srp = $this->reporter->fixcolumn([$srp], '12', 0);
        $arr_totalsrp = $this->reporter->fixcolumn([$coltotalsrp], '12', 0);
        $arr_tp = $this->reporter->fixcolumn([$tp], '12', 0);
        $arr_totaltp = $this->reporter->fixcolumn([$coltotaltp], '12', 0);

        

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemgroup, $arr_model, $arr_rrqty, $arr_stocklist, $arr_totallist, $arr_srp, $arr_totalsrp, $arr_tp, $arr_totaltp]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize8);
          
          PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;">'.    (isset($arr_itemgroup[$r]) ? $arr_itemgroup[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;">'.    (isset($arr_model[$r]) ? $arr_model[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: center;">'.   (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_stocklist[$r]) ? $arr_stocklist[$r] : '').'</div>', '', 0);
          
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_totallist[$r]) ? $arr_totallist[$r] : '').'</div>', '', 0);
       
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_srp[$r]) ? $arr_srp[$r] : '').'</div>', '', 0);
          
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.(isset($arr_totalsrp[$r]) ? $arr_totalsrp[$r] : '').'</div>', '', 0);
          
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">USD '.(isset($arr_tp[$r]) ? $arr_tp[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">USD '.(isset($arr_totaltp[$r]) ? $arr_totaltp[$r] : '').'</div>', '', 1);

          
        }

        $totallist += $value->totallist;
        $totalsrp += $value->totalsrp;
        $totaltp += $value->totaltp;

      }
    }

    
    PDF::SetFont($fontbold, '', $fontsize8);
    
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'T', 0);
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'T', 0);
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: center;"></div>', 'T', 0);

    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'T', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totallist,2).'</div>', 'TB', 0);

    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'T', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totalsrp,2).'</div>', 'TB', 0);

    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'T', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">USD '.number_format($totaltp,2).'</div>', 'TB', 1);

    
    PDF::SetFont($fontbold, '', 2);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: left;"></div>', '', 0);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: left;"></div>', '', 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: center;"></div>', '', 0);

    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'B', 0);

    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'B', 0);

    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'B', 1);
    
    
    PDF::SetFont($font, '', $fontsize8);
    PDF::MultiCell(0, 0, "\n");

    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;"></div>', '', 0);
    PDF::writeHTMLCell(120, 10, '', '', '<div style="text-align: center;"></div>', '', 0);

    if($hashioki){
      PDF::writeHTMLCell(110, 10, '', '', '<div style="color:red; text-align: center;">HIOKI ONLY</div>', 'TBL', 0);//530
      PDF::writeHTMLCell(100, 10, '', '', '<div style="color:red; text-align: right;">PHP '.number_format($totalhioki,2).'</div>', 'TBLR', 0);
  
    }else{
      PDF::writeHTMLCell(110, 10, '', '', '<div style="color:red; text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(100, 10, '', '', '<div style="color:red; text-align: right;"></div>', '', 0);
    }
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', '', 0);

    if($hasosl){
      PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">OS SELLING</div>', '', 0);
      PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totalselling,2).'</div>', '', 0);
      PDF::writeHTMLCell(300, 10, '', '', '<div style="text-align: right;"></div>', '', 1);
    }else{
      PDF::writeHTMLCell(100, 10, '', '', '<div style="color:red; text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(300, 10, '', '', '<div style="text-align: right;"></div>', '', 1);

    }
    

    ////os local here

   
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;">CHECKING ON '.date_format(date_create($data[0]->checkdate), 'F d, Y').'</div>', '', 0);
    PDF::writeHTMLCell(120, 10, '', '', '<div style="text-align: center;"></div>', '', 0);

    if($hasosl){
      PDF::writeHTMLCell(110, 10, '', '', '<div style="color:red; text-align: center;">OS LOCAL LIST PRICE</div>', 'TBL', 0);//530
      PDF::writeHTMLCell(100, 10, '', '', '<div style="color:red; text-align: right;">PHP '.number_format($totalosl,2).'</div>', 'TBLR', 0);
      PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: right;"></div>', '', 1);
    }else{
      PDF::writeHTMLCell(110, 10, '', '', '<div style="color:red; text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(100, 10, '', '', '<div style="color:red; text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
      PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: right;"></div>', '', 1);
    }





    
    // PDF::MultiCell(450, 10, 'TOTAL', 'T', 'R', false, 0);

    // PDF::MultiCell(70, 10, 'PHP '.number_format($totallist,2), 'T', 'R', false, 0);
    // PDF::MultiCell(100, 10, '', 'T', 'R', false, 0);
    // PDF::MultiCell(100, 10, 'PHP '.number_format($totalsrp,2), 'T', 'R', false);


    $trno = $params['params']['dataid'];

    $query = "
    select 
    g.expense,
    sum(g.budget) as budget,sum(g.actual) as actual from(
      select chk.budget,chk.actual,chk.rem,chk.reftrno,
      case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,req.line
      from pxhead as head
      left join pxchecking as chk on chk.trno=head.trno 
      left join reqcategory as req on req.line=chk.expenseid
      where 
      req.category is not null
      and head.trno='$trno'
      union all
      select chk.budget,chk.actual,chk.rem,chk.reftrno,
      case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,req.line
      from hpxhead as head
      left join hpxchecking as chk on chk.trno=head.trno 
      left join reqcategory as req on req.line=chk.expenseid
      where 
      req.category is not null
	    and head.trno='$trno'
    ) as g
    group by g.expense
    order by g.line

      ";

    // $data2 = $this->coreFunctions->opentable($query);
    
    // $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    
    PDF::SetFont($font, '', $fontsize9);
    // PDF::MultiCell(300, 15, 'EXPENSE', 'B', 'C', false, 0);
    // PDF::MultiCell(100, 15, "BUDGET", 'B', 'C', false);

    
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'TBL', 0);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: right;">OANDA USD-PHP</div>', 'TB', 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;">'.number_format($data[0]->oandausdphp,6).'</div>', 'TB', 0);
    // PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
    // PDF::writeHTMLCell(500, 10, '', '', '<div style="text-align: center;"></div>', '', 1);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0); //SPACE
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;">LIST</div>', 'TL', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totallist-$totalselling,2).'</div>', 'T', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'TR', 1);

    //selling
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;">SELLING</div>', 'L', 0);
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totalsrp,2).'</div>', '', 0);
   
    // PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'R', 0);
    // PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0); //SPACE
    // PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;">LIST</div>', 'TL', 0);
    // PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totallist-$totalselling,2).'</div>', 'T', 0);
    // PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'TR', 1);

    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'R', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);


    // $totalbudget = 0;
    $totalbudget = $totallist;
    $count = 0;
    $tpval = 0;
    $totalpercent = 0;
    $dutysum = 0;
    $sum = 0;
    $dutylabel='';

    
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);

    
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $printed_os_local = false;
    if (!empty($data2)) {
      for ($i = 0; $i < count($data2); $i++) {

        if($count == 0)
        {


          
          $hiokipercent = 0;
          $markuphioki = 0;
          $markuphioki1=0;
          if($totalhioki !=0){
            PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left; color:red;">MARK UP-HIOKI ONLY</div>', 'L', 0);
            $markuphioki = $totalhioki*0.1;
            PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;">'.number_format($markuphioki,2).'</div>', '', 0);
            // $hiokipercent = $this->othersClass->calculatePercentage($markuphioki, $totalsrp);
            $markuphioki1=$markuphioki / $totalhioki;
            $hiokipercent=$markuphioki1*100;
            PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;">'.number_format($hiokipercent,2).'%'.'</div>', 'R', 1);


          }else{
            PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;"></div>', 'L', 0);
            PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
            PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: left;"></div>', 'R', 1);
          }




          PDF::writeHTMLCell(220, 10, '', '', '<div style="text-align: left;">TP</div>', 'L', 0);
          $tpval = $totaltp * $data[0]->oandausdphp;
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($tpval,2).'</div>', '', 0);
          PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: center;"></div>', '', 0);

          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'R', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);

                  //////OS LOCAL

        if($totalselling != 0){
          PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left; color:red;">OS LOCAL SELLING</div>', 'L', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;">PHP '.number_format($totalselling,2).'</div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;"></div>', 'R', 1);
 
          }else{
          PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left; color:red;"></div>', 'L', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;"></div>', '', 0);
          // PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right; color:red;"></div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;"></div>', 'R', 1);
          
          }

          $totalbudget += $markuphioki;
          
          $count=1;
        }
      

          
    
        if(str_contains(strtoupper($data2[$i]['expense']),'DUTY')){
          $dutylabel = $data2[$i]['expense'];
          $dutysum += $data2[$i]['budget'];
          $sum += $data2[$i]['budget'];
          continue;
        }

        $maxrow = 1;

        $expense = trim($data2[$i]['expense']);
        $budget = number_format($data2[$i]['budget'], 2);
        $actual = number_format($data2[$i]['actual'], 2);
        $budgetpercent = $this->othersClass->calculatePercentage($data2[$i]['budget'], $totalsrp);
        $budgetpercent = number_format($budgetpercent, 2);
        // $budget = $budget <= 0 ? '-' : $budget;
        // $credit = $credit <= 0 ? '-' : $credit;


        $arr_expense = $this->reporter->fixcolumn([$expense], '90', 0);
        
        $arr_expense2 = $this->reporter->fixcolumn([$expense], '90', 0);
        $arr_budget = $this->reporter->fixcolumn([$budget], '15', 0);
        $arr_actual = $this->reporter->fixcolumn([$actual], '15', 0);

        $arr_budgetpercent = $this->reporter->fixcolumn([$budgetpercent], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_expense,$arr_expense2, $arr_budget, $arr_actual, $arr_budgetpercent]);

        for ($r = 0; $r < $maxrow; $r++) {
          // PDF::SetFont($font, '', $fontsize);
          // PDF::MultiCell(300, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          // PDF::MultiCell(210, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), 'L', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          // PDF::MultiCell(210, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::writeHTMLCell(220, 10, '', '', '<div style="text-align: left;">'.(isset($arr_expense[$r]) ? $arr_expense[$r] : '').'</div>', 'L', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">'.(isset($arr_budget[$r]) ? 'PHP '.$arr_budget[$r] : '').'</div>', '', 0);
          PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'R', 0);

          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);

          PDF::writeHTMLCell(220, 10, '', '', '<div style="text-align: left; color:red;">'.(isset($arr_expense2[$r]) ? $arr_expense2[$r] : '').'</div>', 'L', 0);
          PDF::writeHTMLCell(80, 10, '', '', '<div style="text-align: right; color:red;">'.(isset($arr_budget[$r]) ? 'PHP '.$arr_budget[$r] : '').'</div>', '', 0);
          // $markup = $totalhioki*0.01;
          PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; color:red;">'.(isset($arr_budgetpercent[$r]) ? $arr_budgetpercent[$r].'%' : '').'</div>', 'R', 1);
         
        }

        $totalbudget += $data2[$i]['budget'];
        $totalpercent += $budgetpercent;
        $sum += $data2[$i]['budget'];
        // $totaldb = $totaldb + $data2[$i]['db'];
        // $totalcr = $totalcr + $data2[$i]['cr'];

        // if (PDF::getY() > 900) {
        //     $this->PDF_default_header($params, $data2);
        // }
      }
    }else{
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'BR', 1);
    }


    // $duty = $tpval * 0.02;
    
    $sum += $tpval;
    
    // $dutysum += $duty;
    if($dutysum<=0){
      PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'L', 0);
      PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
    }else{
      PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;">'.$dutylabel.'</div>', 'L', 0);
      PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;">PHP '.number_format($dutysum,2).'</div>', '', 0);  
    }
    
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;">PHP '.number_format($sum,2).'</div>', '', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'R', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);   
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totalbudget,2).'</div>', '', 0);
    // $markup = $totalhioki*0.01;
    // $postlooppercent = $this->othersClass->calculatePercentage($totalbudget, $totalsrp);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">'.number_format($totalpercent,2).'%</div>', 'BR', 1);

    
    PDF::SetFont($font, '', 2);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', 'R', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(200, 0, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'BR', 1);
    PDF::SetFont($font, '', $fontsize9);

    $aftimargin = 0;
    $aftimarginpercent = 0;

    $aftimargin = $totalsrp - $sum;
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;">AFTI MARGIN</div>', 'TB', 0);
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;">PHP '.number_format($aftimargin,2).'</div>', 'TB', 0);
    
    $aftimarginpercent = $this->othersClass->calculatePercentage($aftimargin,$totalsrp);
    //kapag 14.99% below-red font, 15% above -black font
    $color = ($aftimarginpercent < 15) ? 'red' : 'black';
    $html = '<div style="text-align: center; color: '.$color.';">'. number_format($aftimarginpercent, 2) . '%' . '</div>';
    PDF::writeHTMLCell(100, 10, '', '', $html, 'TBR', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left;">SELLING</div>', 'LB', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;">PHP '.number_format($totalsrp,2).'</div>', 'B', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'RB', 1);
    

    PDF::SetFont($font, '', 2);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: right;"></div>', 'B', 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;"></div>', 'B', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', 'TRB', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(200, 0, '', '', '<div style="text-align: left;"></div>', 'L', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'R', 1);
    PDF::SetFont($font, '', $fontsize9);


    $discount = 0;
    $discount = $totalsrp - $totalbudget;
    $discountpercent = 0;
    
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', 'LB', 0);
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;"></div>', 'B', 0);
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;"></div>', 'B', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', 'RB', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);
    
    $netlabel = "EXTRA";
    if($discount<=0){
      $netlabel = "DISCOUNT";
      $color = 'color:red;';
    }else{
      $color = 'color:black;';
    }
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: left; '.$color.'">'.$netlabel.'</div>', 'L', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; '.$color.'">PHP '.number_format($discount,2).'</div>', '', 0);
    $discountpercent = $this->othersClass->calculatePercentage($discount, $totalbudget);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right; '.$color.'">'.number_format($discountpercent,2).'%</div>', 'R', 1);

    
    PDF::SetFont($font, '', 2);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: left;"></div>', '', 0);
    PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(110, 0, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(200, 0, '', '', '<div style="text-align: center;"></div>', 'TBL', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: left;"></div>', 'TB', 0);
    PDF::writeHTMLCell(100, 0, '', '', '<div style="text-align: right;"></div>', 'TBR', 1);
    PDF::SetFont($font, '', $fontsize8);

    
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: left;"></div>', '', 0);
    PDF::writeHTMLCell(160, 10, '', '', '<div style="text-align: right;"></div>', '', 0);
    PDF::writeHTMLCell(110, 10, '', '', '<div style="text-align: right;"></div>', '', 0);

    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: center;"></div>', '', 0);
    PDF::writeHTMLCell(200, 10, '', '', '<div style="text-align: center;"></div>', 'LB', 0);
    
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: left; color:red;"></div>', 'B', 0);
    // $markup = $totalhioki*0.01;
    // $budgetpercent = $this->othersClass->calculatePercentage($totalbudget, $totalsrp);
    PDF::writeHTMLCell(100, 10, '', '', '<div style="text-align: right;"></div>', 'BR', 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function pcf_admin_default($params, $data)
  {
      // --- 1. Data & Configuration Extraction ---
      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = 2;//$this->companysetup->getdecimal('price', $params['params']);
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
      $totalext = 0;

      // --- 2. Reporter Setup ---
      $str = '';
      $font = "Arial"; // Default font style
      $fontsize = "11";
      // $border = ""; // Default border for data rows
      
      $border = "1px solid ";
      $fontsize8 = '8';
      $fontsize9 = '9';
      $fontsize10 = '10';

      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

      $str .= $this->reporter->beginreport('1200');
      
      // Calculate totals for use outside the loop
      $totallist = 0;
      $totalsrp = 0;
      $totaltp = 0;
      $totalhioki = 0;
      $hashioki = 0;
      $totalosl=0;
      $hasosl = 0;
      $totalselling=0;
      foreach ($data as $val) {
          $totallist += $val->totallist;
          $totalsrp += $val->totalsrp;
          $totaltp += $val->totaltp;
          if (str_contains(strtoupper($val->stockgrp_name), 'HIOKI')) {
              // $totalhioki += $val->totalsrp;
              $totalhioki += $val->totallist;
              $hashioki = 1;
          }

        if(str_contains(strtoupper($val->stockgrp_name),'OUTSOURCE-LOCAL')){
          $totalosl += $val->totallist;
          $totalselling +=$val->totalsrp;
          $hasosl = 1;
        }

      }

      // --- 3. Logo and Title Header (Simulating PDF::Image and MultiCell) ---
      // Note: The original PDF logic used explicit positioning (PDF::Image and MultiCell)
      // We use sequential tables to maintain flow.

      // 3.1. User/Timestamp (Commented out in original PDF, but useful for context)
      // The original TCPDF had: PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s'), '', 'L');
      // We skip this for now to match the TCPDF output structure more closely, but reserve space.

      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      // 3.2. Image (Simulating PDF::Image('/images/afti/qslogo.png', '', '', 200, 50);)
      $qslogo = URL::to($this->companysetup->getlogopath($params['params']) . 'qslogo.png'); // Assuming a way to get 'qslogo.png' path
      $str .= $this->reporter->col('<img src="/images/afti/qslogo.png" alt="Logo" width="200px" height="50px">', 200, null, false, $border, '', 'L', $font, $fontsize10, '', '', '1px');
      $str .= $this->reporter->col('', 1000, null, false, $border, '', 'L', $font, $fontsize10, '', '', '1px'); // Spacer
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br><br><br>'; // Simulating MultiCell(0, 0, "\n\n\n\n\n");

      // 3.3. Report Title (Project Costing Form)
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project Costing Form', 1200, null, false, $border, '', 'L', $font, '18', 'B', '', '1px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br>';

      // --- 4. Header Details (Mixing fixed width and two-column layout) ---
      
      // 4.1. CHECKING ON (Full width row, L)
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CHECKING ON ' . date_format(date_create($data[0]->checkdate), 'F d, Y'), 200, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 1000, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      // 4.2. OANDA/OS Rates (3-row, 2-column layout for rates)
      // Left column width: 200 (90+110). Right column width: 1000 (spacer)
      $str .= $this->reporter->begintable('200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('OANDA PHP-USD', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[0]->oandaphpusd, 6), 110, null, false, $border, '', 'R', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->endrow();
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('OANDA USD-PHP', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[0]->oandausdphp, 6), 110, null, false, $border, '', 'R', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->endrow();
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('OS PHP-USD', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[0]->osphpusd, 6), 110, null, false, $border, '', 'R', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br>'; // Simulating MultiCell(0, 0, "\n");
      
      // 4.3. Detail Block (Two Columns: 90/260 | 120 Spacer | 90/260. Total width 820px used for content)
      
      $str .= $this->reporter->begintable('1200'); // Use 1200 for full row control
      // Name Of Company / DATE
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Name Of Company:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->clientname, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('DATE:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col(date_format(date_create($data[0]->dateid), 'm/d/Y'), 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 380, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Extra Spacer
      $str .= $this->reporter->endrow();

      // Name Of Project / PCF No. (PCF No. is RED)
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Name Of Project:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->project, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('PCF No.:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('<span style="color:red;">' . $data[0]->pcfno . '</span>', 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 380, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Extra Spacer
      $str .= $this->reporter->endrow();

      // Project Owner / DTC No. (DTC No. is RED)
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project Owner:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->agentname, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('DTC No.:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('<span style="color:red;">' . $data[0]->dtcno . '</span>', 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 380, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Extra Spacer
      $str .= $this->reporter->endrow();

      // Reason (RED) / PO Reference (RED if 'NO PO YET')
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<span style="color:red;">Reason:</span>', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('<span style="color:red;">' . $data[0]->reason . '</span>', 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('PO Reference:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $poref_content = ($data[0]->poref == 'NO PO YET') ? '<span style="color:red;">' . $data[0]->poref . '</span>' : $data[0]->poref;
      $str .= $this->reporter->col($poref_content, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 380, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Extra Spacer
      $str .= $this->reporter->endrow();
      
      // // PROJECT ID / AFTI STOCK
      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('PROJECT ID:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      // $str .= $this->reporter->col($data[0]->projectid, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      // $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      // $str .= $this->reporter->col('AFTI STOCK:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      // $aftistock_val = ($data[0]->aftistock == 0) ? 'NO' : 'YES';
      // $str .= $this->reporter->col($aftistock_val, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      // $str .= $this->reporter->col('', 380, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Extra Spacer
      // $str .= $this->reporter->endrow();
      // $str .= $this->reporter->endtable();


          // PROJECT ID / PAYMENT TERMS
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PROJECT ID:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->projectid, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('PAYMENT TERMS:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->terms, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('ONGOING', 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->col('APPROVED', 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->endrow();
      // $str .= $this->reporter->endtable();

      //AFTI STOCK
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('AFTI STOCK:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $aftistock_val = ($data[0]->aftistock == 0) ? 'NO' : 'YES';
      $approve = ($data[0]->termsdetails == 'Approve') ? 'YES' : 'NO';
      $ongoing = ($data[0]->termsdetails == 'On going') ? 'YES' : 'NO';
      $str .= $this->reporter->col($aftistock_val, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($ongoing, 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->col($approve, 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px'); // Spacer
      $str .= $this->reporter->col('COMMISSION:', 90, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col($data[0]->fullcomm, 260, null, false, $border, '', 'L', $font, $fontsize9, '', '', '1px');
      $str .= $this->reporter->col('', 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->col('', 190, null, false, $border, '', 'C', $font, $fontsize9, '', '', '1px'); 
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br>';


      $header_border = 'TBLR'; 

      $str .= $this->reporter->begintable('1200'); // Items table
      // 5.1. Header Row
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('<b><u>ITEM GROUP</u></b>', 180, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>MODEL</u></b>', 180, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>QUANTITY</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>LIST (PHP)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>TOTAL LIST (PHP)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>SELLING (PHP)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>TOTAL SELL (PHP)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>TP (USD)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->col('<b><u>TOTAL TP (USD)</u></b>', 120, null, false, $border, $header_border, 'C', $font, $fontsize9, 'B', '', '1px');
      $str .= $this->reporter->endrow();

      // 5.2. Data Loop
      $data_border = 'LRTB'; // Simulating the borders used in TCPDF loop

      if (!empty($data)) {
          foreach ($data as $value) {
              
              $itemgroup = $value->stockgrp_name;
              $model = $value->model;
              $rrqty = number_format($value->rrqty, 0);
              $stocklist = number_format($value->stocklist, $decimalprice);
              $display_totallist = number_format($value->totallist, $decimalprice);
              $srp = number_format($value->srp, $decimalprice);
              $display_totalsrp = number_format($value->totalsrp, $decimalprice);
              $tp = number_format($value->tp, 4);
              $display_totaltp = number_format($value->totaltp, 4);

              $str .= $this->reporter->startrow();
              // function col($txt = '', $w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $isamount = 0, $colspan = 0, $bc = null)
              
              $str .= $this->reporter->col($itemgroup, 180, null, false, $data_border, 'L', 'L', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($model, 180, null, false, $data_border, 'L', 'L', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($rrqty, 120, null, false, $data_border, 'C', 'C', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($stocklist, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($display_totallist, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($srp, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($display_totalsrp, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->col($tp, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
              $str .= $this->reporter->col($display_totaltp, 120, null, false, $data_border, 'R', 'R', $font, $fontsize8, '', '', '1px','',0,'',2);
              $str .= $this->reporter->endrow();
          }
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 180, null, false, $border, 'T', 'L', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col('', 180, null, false, $border, 'T', 'L', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'C', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totallist, 2), 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totalsrp, 2), 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->col(number_format($totaltp, 4), 120, null, false, $border, 'T', 'R', $font, $fontsize8, 'B', '', '1px');
      $str .= $this->reporter->endrow();
      
      $str .= $this->reporter->endtable();

      
      $printosl = 0;
      $printosl1 = 0;
      $printosl2 = 0;
      // $hashioki = 0;
      // $hasosl = 1;
      // $totalselling = 100;
      if ($hashioki) {
        $str .= $this->reporter->begintable('1200');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('<span style="color:red;">HIOKI ONLY</span>', 180, null, false, $border, 'TBL', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('<span style="color:red;">'. number_format($totalhioki, 2) . '</span>', 120, null, false, $border, 'TBLR', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
            
        if($hasosl){
          $str .= $this->reporter->col('<span style="color:red;">OS SELLING</span>', 180, null, false, $border, 'TBL', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('<span style="color:red;">' . number_format($totalselling, 2) . '</span>', 60, null, false, $border, 'TBLR', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
          $printosl = 1;
          $printosl1 = 1;
        }else{
          $str .= $this->reporter->col('', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        }
        $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      //os local here 
      // $hasosl = 0;
      if ($hasosl) {
        $str .= $this->reporter->begintable('1200');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('<span style="color:red;">OS LOCAL LIST PRICE</span>', 180, null, false, $border, 'TBL', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('<span style="color:red;">PHP ' . number_format($totalosl, 2) . '</span>', 120, null, false, $border, 'TBLR', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
        if($hasosl && $printosl==0){
          $str .= $this->reporter->col('<span style="color:red;">OS SELLING</span>', 180, null, false, $border, 'TBL', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('<span style="color:red;">PHP ' . number_format($totalselling, 2) . '</span>', 60, null, false, $border, 'TBLR', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
          $printosl = 1;
          $printosl2 = 1;
        }else{
          $str .= $this->reporter->col('', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        }
        // Extra Spacer
        $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      } 

      if ($printosl==0 && $totalselling>0 && $printosl1==0 && $printosl2 == 0) {
        $str .= $this->reporter->begintable('1200');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        if($hasosl && $printosl==0 && $printosl1==0 && $printosl2 == 0){
          $str .= $this->reporter->col('<span style="color:red;">OS SELLING</span>', 180, null, false, $border, 'TBL', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('<span style="color:red;">' . number_format($totalselling, 2) . '</span>', 60, null, false, $border, 'TBLR', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
          $printosl = 1;
        }else{
          $str .= $this->reporter->col('', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        }
        // Extra Spacer
        $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      } 

      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CHECKING ON ' . date_format(date_create($data[0]->checkdate), 'F d, Y'), 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      
      $str .= $this->reporter->col('', 180, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px'); // Spacer for alignment
      $str .= $this->reporter->col('', 60, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
       // Extra Spacer
      $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->endtable();
      $trno = $params['params']['dataid'];
      $query = "
        select 
        g.expense,
        sum(g.budget) as budget,sum(g.actual) as actual from(
          select chk.budget,chk.actual,chk.rem,chk.reftrno,
          case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,req.line
          from pxhead as head
          left join pxchecking as chk on chk.trno=head.trno 
          left join reqcategory as req on req.line=chk.expenseid
          where 
          req.category is not null
          and head.trno='$trno'
          union all
          select chk.budget,chk.actual,chk.rem,chk.reftrno,
          case when chk.rem<>'' then concat(req.category,' - ',chk.rem) else req.category end as expense,req.line
          from hpxhead as head
          left join hpxchecking as chk on chk.trno=head.trno 
          left join reqcategory as req on req.line=chk.expenseid
          where 
          req.category is not null
          and head.trno='$trno'
        ) as g
        group by g.expense
        order by g.line
        ";
      $data2 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
      

      
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 180, null, false, $border, 'TL', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('OANDA USD-PHP', 180, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      // $str .= $this->reporter->col('', 120, null, false, $border, 'T', '', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[0]->oandausdphp,6), 360, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 60, null, false, $border, 'TR', 'R', $font, $fontsize8, '', '', '1px');
      

      $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer


      // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');

      // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      //right
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();

      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SELLING', 180, null, false, $border, 'TL', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 180, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      // $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($totalsrp,2), 360, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 60, null, false, $border, 'TR', 'R', $font, $fontsize8, '', '', '1px');
      

      $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer

      $str .= $this->reporter->col('LIST', 120, null, false, $border, 'TL', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($totallist-$totalselling,2), 120, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'TR', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();


      $totalbudget = $totallist;
      $count = 0;
      $tpval = 0;
      $totalpercent = 0;
      $dutysum = 0;
      $sum = 0;
      $dutylabel='';

      
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);

      
      $font = "";
      $fontbold = "";
      $border = "1px solid ";
      $fontsize = "11";


      if (!empty($data2)) {
        for ($i = 0; $i < count($data2); $i++) {

          if($count == 0)
          {

            $hiokipercent = 0;
            $markuphioki = 0;
            $markuphioki1=0;           

            //tp row
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TP', 180, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
            $tpval = $totaltp * $data[0]->oandausdphp;
            $str .= $this->reporter->col(number_format($tpval,2), 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            
            
            $str .= $this->reporter->col('', 360, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('', 60, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer
            
            if($totalhioki!=0){
              //right
              $str .= $this->reporter->col('<div style="text-align: left; color:red;">MARK UP-HIOKI ONLY</div>', 120, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
              $markuphioki = $totalhioki*0.1;
              $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.number_format($markuphioki,2).'</div>', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
              // $hiokipercent = $this->othersClass->calculatePercentage($markuphioki, $totalsrp);
              $markuphioki1=$markuphioki / $totalhioki;
              $hiokipercent=$markuphioki1*100;
              $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.number_format($hiokipercent,2).'%'.'</div>', 120, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->endrow();
            }else{
              //right
              $str .= $this->reporter->col('<div style="text-align: left; color:red;">MARK UP-HIOKI ONLY</div>', 120, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
              $markuphioki = $totalhioki*0.1;
              $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.number_format(0,2).'</div>', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
              // $hiokipercent = $this->othersClass->calculatePercentage($markuphioki, $totalsrp);
              // $markuphioki1=$markuphioki / $totalhioki;
              // $hiokipercent=$markuphioki1*100;
              $str .= $this->reporter->col('<div style="text-align: right; color:red;">0.00%'.'</div>', 120, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->endrow();
            }
  //end tp row


            $sum += $totalosl;
            if($totalosl || $totalselling){

              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('AFTI OS COST', 180, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->col(number_format($totalosl, 2), 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
              // $str .= $this->reporter->col('', 120, null, false, $border, 'T', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->col('', 360, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->col('', 60, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
            

            $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer

            
            // if($totalselling !=0){
              //right
              $str .= $this->reporter->col('<div style="text-align: left; color:red;">OS LOCAL SELLING</div>', 120, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.number_format($totalselling,2).'</div>', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->col('<div style="text-align: right; color:red;"></div>', 120, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
              $str .= $this->reporter->endrow();
            }
            // }else{
              //   //right
            //   $str .= $this->reporter->col('<div style="text-align: left; color:red;"></div>', 120, 2, false, $border, 'L', 'L', $font, $fontsize8-7, '', '', '1px');
            //   $str .= $this->reporter->col('<div style="text-align: right; color:red;"></div>', 120, 2, false, $border, '', 'R', $font, $fontsize8-7, '', '', '1px');
            //   $str .= $this->reporter->col('<div style="text-align: right; color:red;"></div>', 120, 2, false, $border, 'R', 'R', $font, $fontsize8-7, '', '', '1px');
            //   $str .= $this->reporter->endrow();
            // }
          
            $totalbudget += $markuphioki;
            
            // $totalpercent += $hiokipercent;
            $count=1;
          }

             //////OS LOCAL        
          
          if(str_contains(strtoupper($data2[$i]['expense']),'DUTY')){
            $dutylabel = $data2[$i]['expense'];
            $dutysum += $data2[$i]['budget'];
            
            
            $sum += $data2[$i]['budget'];
            
            continue;
          }

          // $maxrow = 1;

          $expense = trim($data2[$i]['expense']);

          $budget = number_format($data2[$i]['budget'], 2);
          $actual = number_format($data2[$i]['actual'], 2);

          
          $budgetpercent = $this->othersClass->calculatePercentage($data2[$i]['budget'], $totalsrp);

          
          $budgetpercent = number_format($budgetpercent, 2);
          

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($expense, 180, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col($budget, 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('', 360, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('', 60, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer
            //right
            $str .= $this->reporter->col('<div style="text-align: left; color:red;">'.$expense.'</div>', 120, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.$budget.'</div>', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->col('<div style="text-align: right; color:red;">'.$budgetpercent.'%'.'</div>', 120, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
            $str .= $this->reporter->endrow();
            $totalbudget += $data2[$i]['budget'];
            $totalpercent += $budgetpercent;
            $sum += $data2[$i]['budget'];

          // $totaldb = $totaldb + $data2[$i]['db'];
          // $totalcr = $totalcr + $data2[$i]['cr'];

          // if (PDF::getY() > 900) {
          //     $this->PDF_default_header($params, $data2);
          // }
        }// end for ($i = 0; $i < count($data2); $i++) 
      }

      $sum += $tpval;

      
            // $dutysum += $duty;
      $str .= $this->reporter->startrow();
      if($dutysum<=0){
        $str .= $this->reporter->col('DUTY-2%', 180, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col('0', 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      }else{ 
        $str .= $this->reporter->col($dutylabel, 180, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
        $str .= $this->reporter->col(number_format($dutysum,2), 180, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      }
      
      
      // $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($sum,2), 360, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 60, null, false, $border, 'R', 'R', $font, $fontsize8, '', '', '1px');
      

      $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer

      //right
      $str .= $this->reporter->col('', 120, null, false, $border, 'L', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($totalbudget,2), 120, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(''.number_format($totalpercent,2).'%', 120, null, false, $border, 'BR', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();

      
      $aftimargin = 0;
      $aftimarginpercent = 0;
      
      $aftimargin = $totalsrp - $sum;

      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 180, null, false, $border, 'LB', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('AFTI MARGIN', 180, null, false, $border, 'TB', 'R', $font, $fontsize8, '', '', '1px');
      // $str .= $this->reporter->col('', 120, null, false, $border, 'TB', 'R', $font, $fontsize8, '', '', '1px');
      $aftimarginpercent = $this->othersClass->calculatePercentage($aftimargin,$totalsrp);
      $color = ($aftimarginpercent < 15) ? 'red' : 'black';
      $str .= $this->reporter->col(number_format($aftimargin,2), 360, null, false, $border, 'TB', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('<div style="text-align: right; color:'.$color.';">'.number_format($aftimarginpercent,2).'%'.'</div>', 60, null, false, $border, 'TBR', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer

      //right
      $str .= $this->reporter->col('SELLING', 120, null, false, $border, 'LB', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col(number_format($totalsrp,2), 120, null, false, $border, 'B', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 120, null, false, $border, 'RB', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();

      

      $discount = 0;
      $discount = $totalsrp - $totalbudget;
      $discountpercent = 0;

      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', 180, 15, false, $border, '', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 180, 15, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      // $str .= $this->reporter->col('', 120, 15, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 360, 15, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('', 60, 15, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');

      $str .= $this->reporter->col('', 60, 15, false, $border, '', 'R', $font, $fontsize8, '', '', '1px');//spacer
      
      $netlabel = "EXTRA";
      if($discount<=0){
        $netlabel = "DISCOUNT";
        $color = 'color:red;';
      }else{
        $color = 'color:black;';
      }
      
      //right
      $str .= $this->reporter->col('<div style="text-align: left; '.$color.'">'.$netlabel.'</div>', 120, 20, false, $border, 'BL', 'L', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->col('<div style="text-align: right; '.$color.'">'.number_format($discount,2).'</div>', 120, 20, false, $border, 'B', 'R', $font, $fontsize8, '', '', '1px','',0,'',1);
      
      $discountpercent = $this->othersClass->calculatePercentage($discount, $totalbudget);
      
      $str .= $this->reporter->col('<div style="text-align: right; '.$color.'">'.number_format($discountpercent,2).'%</div>', 120, 20, false, $border, 'BR', 'R', $font, $fontsize8, '', '', '1px');
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->endtable();

      
      $str .= $this->reporter->endreport();
      
      return $str;
  }

  
}
