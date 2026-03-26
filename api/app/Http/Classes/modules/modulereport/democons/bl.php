<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class bl
{

  private $modulename = "Budget Liquidation";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  
  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter()
  {
    $fields = ['radioprint','radioreporttype', 'prepared', 'received', 'approved', 'refresh'];
    
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'BRUR', 'value' => '0', 'color' => 'red'],
      ['label' => 'Liquidation', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1,'received.label','Verified by');  
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {

    $user = $config['params']['user'];
    $qry="select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if((isset($name[0]['name']) ? $name[0]['name'] : '')!=''){
      $user = $name[0]['name'];
    }
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$user' as prepared,
      '' as approved,
      '' as received,
      '0' as reporttype,
      '0' as radioreporttype
      "
    );
  }

  public function report_default_query($trno)
  {
    $qry = "select center,trno,docno,yourref,ourref,dateid,address,createdate,rem,projectid,particulars,sum(ext) as ext,qty,
    uom,remarks,projectname,projectcode,subproject,subprojectname,brdocno,brtrno,bal,start,end,refx,brsamount,
    brsrrcost,brsext,brsuom,brsqty from ( ";
    $groupby = ") as a group by center,trno,docno,yourref,ourref,dateid,address,createdate,rem,projectid,particulars,qty,
    uom,remarks,projectname,projectcode,subproject,subprojectname,brdocno,brtrno,bal,start,end,refx,brsamount,
    brsrrcost,brsext,brsuom,brsqty";

    $qryselect = "select num.center,head.trno,head.docno,head.yourref,head.ourref,date(head.dateid) as dateid,head.address,
                        date_format(head.createdate,'%Y-%m-%d') as createdate,
                        head.rem,head.projectid,stock.particulars,stock.rrcost,stock.ext,
                        stock.qty,stock.uom,stock.rem as remarks,ifnull(project.name,'') as projectname,
                        '' as dprojectname,ifnull(project.code,'') as projectcode,
                        s.line as subproject,s.subproject as subprojectname,
                        ifnull(br.docno,'') as brdocno,head.brtrno,head.bal,
                        date(br.start) as start, date(br.end) as end,stock.refx,
                        brs.amount as brsamount,brs.qty as brsqty,brs.rrcost as brsrrcost,brs.ext as brsext,brs.uom as brsuom";

    $query = $qry . $qryselect . " from blhead as head
            left join blstock as stock on head.trno = stock.trno
            left join transnum as num on num.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid 
            left join subproject as s on s.line = head.subproject
            left join hbrhead as br on br.trno = head.brtrno
            left join hbrstock as brs on brs.trno = stock.refx and brs.line=stock.linex
            where head.trno = '$trno' and stock.refx <> 0
            union all " . $qryselect . " 
            from hblhead as head
            left join hblstock as stock on head.trno = stock.trno
            left join transnum as num on num.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid 
            left join subproject as s on s.line = head.subproject
            left join hbrhead as br on br.trno = head.brtrno
            left join hbrstock as brs on brs.trno = stock.refx and brs.line=stock.linex
            where head.trno = '$trno' and stock.refx <> 0" . $groupby;
            
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_other_query($trno)
  {
    $qryselect = "select num.center,head.trno,head.docno,head.yourref,head.ourref,date(head.dateid) as dateid,head.address,
                        date_format(head.createdate,'%Y-%m-%d') as createdate,
                        head.rem,head.projectid,stock.particulars,stock.rrcost,stock.ext,
                        stock.qty,stock.uom,stock.rem as remarks,ifnull(project.name,'') as projectname,
                        '' as dprojectname,ifnull(project.code,'') as projectcode,
                        s.line as subproject,s.subproject as subprojectname,
                        ifnull(br.docno,'') as brdocno,head.brtrno,head.bal,
                        date(br.start) as start, date(br.end) as end,stock.refx";

    $query = $qryselect . " from blhead as head
            left join blstock as stock on head.trno = stock.trno
            left join transnum as num on num.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid 
            left join subproject as s on s.line = head.subproject
            left join hbrhead as br on br.trno = head.brtrno
            where head.trno = '$trno' and stock.refx = 0
            union all " . $qryselect . " 
            from hblhead as head
            left join hblstock as stock on head.trno = stock.trno
            left join transnum as num on num.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid 
            left join subproject as s on s.line = head.subproject
            left join hbrhead as br on br.trno = head.brtrno
            where head.trno = '$trno' and stock.refx = 0";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function report_detailed_query($trno)
  {
    $qryselect = "select head.docno,right(head.docno,13) as liqno,stock.location,stock.supplier,stock.address,
                          stock.tin,stock.ref,
                         stock.particulars,stock.qty,stock.rrcost,
                         stock.purchase,stock.vat,stock.ext,date(stock.ordate) as ordate,stock.isvat,
                         date(stock.dateid) as dateid,brh.start,brh.end";

    $query = $qryselect ." from blstock as stock
            left join blhead as head on head.trno = stock.trno
            left join hbrhead as brh on brh.trno = head.brtrno
            where head.trno = $trno
            union all " . $qryselect . "
             from hblstock as stock
            left join hblhead as head on head.trno = stock.trno
            left join hbrhead as brh on brh.trno = head.brtrno
            where head.trno = $trno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($config){
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0:
        if($config['params']['dataparams']['print'] == "default"){
          return $this->report_summarized_layout($config);
        }else if($config['params']['dataparams']['print'] == "PDFM") {
          return $this->report_summarized_layout_PDF($config);
        }
        break;
      case 1:
        if($config['params']['dataparams']['print'] == "default"){
          return $this->report_detailed_layout($config);
        }else if($config['params']['dataparams']['print'] == "PDFM") {
          return $this->report_detailed_layout_PDF($config);
        }
        
        break;
      
    }
    
  }

  public function reportheader($config, $data, $prevdrdocno)
  {
    $otherdata = $this->report_other_query($config['params']['dataid']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    
    $str .= "<div style='position: relative;'>";
    $str .= $this->reporter->begintable('1200');
    $qry = "select name,address,tel from center where code = '".$center."'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name),null,null,false,'1px solid ','','c','Century Gothic','14','B','','').'<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address),null,null,false,'1px solid ','','c','Century Gothic','13','B','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel),null,null,false,'1px solid ','','c','Century Gothic','13','B','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= "<div style='position:absolute; top: 60px;'>";
   
    $str .= "</div>";

    $str .= "</div>";

    $str .= '<br>';

    if (!empty($data)) {
      $start = $data[0]['start'];
      $end = $data[0]['end'];
      $projectname = $data[0]['projectname'];
      $docno = $data[0]['docno'];
      $projectcode = $data[0]['projectcode'];
      $dateid = $data[0]['dateid'];
      $address = $data[0]['address'];
    } else {
      $start = $otherdata[0]['start'];
      $end = $otherdata[0]['end'];
      $projectname = $otherdata[0]['projectname'];
      $docno = $otherdata[0]['docno'];
      $projectcode = $otherdata[0]['projectcode'];
      $dateid = $otherdata[0]['dateid'];
      $address = $otherdata[0]['address'];
    }

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('BUDGET LIQUIDATION', '1200', null, false, $border, '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Previous BR# ' . $prevdrdocno . ' Period Covered: ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '1200', null, false, $border, '', 'C', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project: ' . $projectname, '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('BL No.: ' . $docno, '200', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Contract No.: ' . $projectcode, '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Date: ' . $dateid, '200', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Location: ' . $address, '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('REQUESTED BUDGET', '425', null, false, $border, 'BTLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('PURCHASES/EXPENSES', '175', null, false, $border, 'BTLR', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM NO.', '50', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('PARTICULARS', '200', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');

    $str .= $this->reporter->col('Qty.', '50', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Unit', '50', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Estimated Amount', '100', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Approved Amount', '100', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Total', '125', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');

    $str .= $this->reporter->col('BALANCE AMOUNT', '100', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '250', null, false, $border, 'LR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('A.', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Requested Budget', '200', null, false, $border, 'TBLR', 'L', $font, '12', 'B', '', '');
    
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    
    return $str;
  }

  public function report_summarized_layout($config)
  {
    $data = $this->report_default_query($config['params']['dataid']);
    $otherdata = $this->report_other_query($config['params']['dataid']);
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $count = 28;
    $page = 28;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    if (!empty($data)) {
      $projectid = $data[0]['projectid'];
      $brtrno = $data[0]['brtrno'];
      $bal = $data[0]['bal'];
    } else {
      $projectid = $otherdata[0]['projectid'];
      $brtrno = $otherdata[0]['brtrno'];
      $bal = $otherdata[0]['bal'];
    }

    $qry = "select head.trno,head.docno,head.dateid,head.start,head.end,p.name as projectname,
      format(ifnull((select ((select sum(br.ext) from hbrstock as br 
      where br.trno = hd.brtrno)+hd.bal)-sum(st.ext) 
      from hblhead as hd 
      left join hblstock as st on st.trno = hd.trno 
      where hd.projectid = head.projectid and hd.subproject = head.subproject 
      group by hd.brtrno,hd.bal,hd.dateid
      order by hd.dateid desc limit 1),0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as brbal,
      format(sum(stock.ext)-ifnull((select sum(s.ext) 
      from hblhead as h 
      left join hblstock as s on s.trno=h.trno 
      where h.projectid = head.projectid and h.subproject = head.subproject 
      group by h.trno,h.dateid
      order by h.dateid desc limit 1),0),2) as curbal 
      from hbrhead as head 
      left join hbrstock as stock on stock.trno = head.trno 
      left join projectmasterfile as p on p.line = head.projectid 
      where head.projectid = '" . $projectid . "' and head.trno < '" . $brtrno . "'
      group by head.trno,head.docno,head.dateid,head.start,
      head.end,p.name,head.projectid,head.subproject
      order by head.trno desc";
    $previousbr = $this->coreFunctions->opentable($qry);
   
    $str .= $this->reporter->beginreport();
    
    $str .= $this->reportheader($config, $data, $previousbr[0]->docno);
    
    $subtotalA = 0;
    $subtotalB = 0;
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $balamount = $data[$i]['brsamount'] - $data[$i]['ext'];
      $str .= $this->reporter->col($i + 1, '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col($data[$i]['particulars'], '200', null, false, $border, 'LRTB', 'L', $font, '12', '', '', '');
      $str .= $this->reporter->col(round($data[$i]['brsqty']), '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col($data[$i]['brsuom'], '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');

      $str .= $this->reporter->col(number_format($data[$i]['brsrrcost'], $decimal), '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['brsamount'], $decimal), '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['brsext'], $decimal), '125', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');

      $str .= $this->reporter->col(round($data[$i]['qty']), '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');

      $str .= $this->reporter->col(number_format($balamount,2), '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
      $str .= $this->reporter->col($data[$i]['remarks'], '250', null, false, $border, 'LRTB', 'L', $font, '12', '', '', '');

      $subtotalA += $data[$i]['ext'];
      
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Subtotal A', '200', null, false, $border, 'TBLR', 'L', $font, '12', 'I', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalA, $decimal), '125', null, false, $border, 'TBLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    if (!empty($otherdata)) {
      $str .= $this->reporter->begintable('1200');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('B.', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Other', '200', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
      
      $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');

      $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');

      $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');


      for ($i = 0; $i < count($otherdata); $i++) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
      
        $str .= $this->reporter->col($i + 1, '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col($otherdata[$i]['particulars'], '200', null, false, $border, 'LRTB', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col(round($otherdata[$i]['qty']), '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col($otherdata[$i]['uom'], '50', null, false, $border, 'LRTB', 'C', $font, '12', '', '', '');
  
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
  
        $str .= $this->reporter->col('', '50', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->col(number_format($otherdata[$i]['rrcost'], $decimal), '125', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
  
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRTB', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->col($otherdata[$i]['remarks'], '250', null, false, $border, 'LRTB', 'L', $font, '12', '', '', '');
  
        $subtotalB += $otherdata[$i]['ext'];
        
      }
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Subtotal B', '200', null, false, $border, 'TBLR', 'L', $font, '12', 'I', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotalB, $decimal), '125', null, false, $border, 'TBLR', 'R', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
  
      $str .= $this->reporter->endtable();


    }

    $totalext = $subtotalA + $subtotalB;
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();



    $qry = "
      select sum(stock.rrcost) as budgetreq, sum(stock.amount) as budgetapp
      from hbrhead as head 
      left join hbrstock as stock on stock.trno = head.trno
      where head.trno = '" . $brtrno . "'";
    $result2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('BUDGET REQUEST', '200', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Php ', '25', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($result2[0]['budgetreq'], $decimal), '100', null, false, $border, 'BR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');

    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('APPROVED BUDGET', '200', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Php ', '25', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($result2[0]['budgetapp'], $decimal), '100', null, false, $border, 'BR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('BALANCE FROM PREVIOUS BUDGET', '250', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
   
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Php ', '25', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($bal, $decimal), '100', null, false, $border, 'BR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('REMAINING OF BR# ' . $previousbr[0]->docno, '250', null, false, $border, 'BLR', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('ACTUAL TOTAL EXPENSES', '200', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Php ', '25', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $border, 'BR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // cash on hand = (approved budget + balance)-actual expense
    $coh = ($result2[0]['budgetapp'] + $bal) - $totalext;

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('CASH ON HAND', '200', null, false, $border, 'BLR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Php ', '25', null, false, $border, 'BL', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($coh, $decimal), '100', null, false, $border, 'BR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'BLR', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Verified By : ', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left: 20px;">' . $config['params']['dataparams']['prepared'] . '</div>', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<div style="margin-left: 20px;">' . $config['params']['dataparams']['received'] . '</div>', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<div style="margin-left: 20px;">' . $config['params']['dataparams']['approved'] . '</div>', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left: 20px; margin-top: -15px;">______________________________</div>
      &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Project In Charge', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
      $str .= $this->reporter->col('<div style="margin-left: 20px; margin-top: -15px;">______________________________</div>', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('<div style="margin-left: 20px; margin-top: -15px;">______________________________</div>', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_detailed_layout($config)
  {
    $data = $this->report_detailed_query($config['params']['dataid']);
    $center = $config['params']['center'];


    $str = '';
    $count = 28;
    $page = 28;
    $font =  "Century Gothic";
    $fontsizehead = "12";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    
    $str .= $this->reporter->begintable('1200');
    $qry = "select name,address,tel from center where code = '".$center."'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name),null,null,false,'1px solid ','','L','Century Gothic','13','B','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(' Period Covered: ' . date('M-d-Y', strtotime($data[0]['start'])) . ' TO ' . date('M-d-Y', strtotime($data[0]['end'])),null,null,false,'1px solid ','','L','Century Gothic','13','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= "<br>";

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Liq. No.', '50', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Location', '100', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Supplier.', '100', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Address', '100', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('TIN Number', '90', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('O.R. Number', '90', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Particular', '90', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Purchase', '80', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Input VAT', '80', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Total Amount', '100', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('O.R. Date', '70', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('VAT', '30', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    $str .= $this->reporter->col('Entry Date', '70', null, false, $border, 'TLR', 'C', $font, $fontsizehead, 'B', '', '');
    
    $totalamt = $totalpurchase = $totalvat =0;

    $totalext = 0;
    $liq = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $liq = $data[0]['liqno'] * 1;
      $str .= $this->reporter->col('LIQ.#'.$liq, '50', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['location'], '100', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['supplier'], '100', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['address'], '100', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data[$i]['tin'], '90', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['ref'], '90', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['particulars'], '90', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(round($data[$i]['qty']), '50', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['rrcost'],2), '100', null, false, $border, 'LRTB', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($data[$i]['purchase'],2), '80', null, false, $border, 'LRTB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['vat'],2), '80', null, false, $border, 'LRTB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'],2), '100', null, false, $border, 'LRTB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['ordate'], '70', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      if ($data[$i]['isvat'] == 1) {
        $isvat = 'T';
      } else {
        $isvat = 'F';
      }
      $str .= $this->reporter->col($isvat, '30', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['dateid'], '70', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');

      $totalamt += $data[$i]['rrcost'];
      $totalpurchase += $data[$i]['purchase'];
      $totalvat += $data[$i]['vat'];
      $totalext += $data[$i]['ext'];
      
    }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalamt,2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col(number_format($totalpurchase,2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalvat,2), '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalext,2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '</br></br>';
    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left: 20px;">' . $config['params']['dataparams']['prepared'] . '</div>', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('<div style="margin-left: 20px;">' . $config['params']['dataparams']['received'] . '</div>', '400', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<div style="margin-left: 20px; margin-top: -15px;">______________________________</div>
      &nbsp&nbsp&nbsp&nbsp&nbsp PREPARED BY : ', '400', null, false, $border, '', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('<div style="margin-left: 20px; margin-top: -15px;">______________________________</div>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp CHECKED/VERIFIED : ', '400', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function PDF_header($config, $data, $prevdrdocno) 
  {
      $otherdata = $this->report_other_query($config['params']['dataid']);
      $decimal = $this->companysetup->getdecimal('currency', $config['params']);
      $center = $config['params']['center'];
      $username = $config['params']['user'];

      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $font = "";
      $fontbold = "";
      $fontsize = 11;
      if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
      }

      PDF::SetTitle($this->modulename);
      PDF::SetAuthor('Solutionbase Corp.');
      PDF::SetCreator('Solutionbase Corp.');
      PDF::SetSubject($this->modulename . ' Module Report');
      PDF::setPageUnit('px');
      PDF::AddPage('l', [1200, 800]);
      PDF::SetMargins(40, 40);

      PDF::MultiCell(0, 0, "\n");
      // SetFont(family, style, size)
      // MultiCell(width, height, txt, border, align, x, y)
      // write2DBarcode(code, type, x, y, width, height, style, align)

      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(0, 0, $headerdata[0]->address."\n".$headerdata[0]->tel."\n", '', 'C');

      PDF::MultiCell(0, 0, "\n\n");

      PDF::Image('public/images/reports/mdc.jpg', '90', '20', 120, 65);
      PDF::Image('public/images/reports/tuv.jpg', '970', '20', 120, 65);
      
      if (!empty($data)) {
        $start = $data[0]['start'];
        $end = $data[0]['end'];
        $projectname = $data[0]['projectname'];
        $docno = $data[0]['docno'];
        $projectcode = $data[0]['projectcode'];
        $dateid = $data[0]['dateid'];
        $address = $data[0]['address'];
      } else {
        $start = $otherdata[0]['start'];
        $end = $otherdata[0]['end'];
        $projectname = $otherdata[0]['projectname'];
        $docno = $otherdata[0]['docno'];
        $projectcode = $otherdata[0]['projectcode'];
        $dateid = $otherdata[0]['dateid'];
        $address = $otherdata[0]['address'];
      }

      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper(strtoupper($this->modulename)), '', 'C');
      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(0, 0, "");
      PDF::SetFont($fontbold, '', 10);
     
      PDF::MultiCell(0, 0, 'Previous BR# ' . $prevdrdocno ." Period Covered: ". date('M d Y', strtotime($start)).' - '.date('M d Y', strtotime($end)) . "\n\n", '', 'C');

      PDF::MultiCell(0, 0, "\n\n");

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 0, "Project: ", '', 'L', false, 0, '',  '');
      PDF::MultiCell(900, 0, $projectname, '', 'L', false, 0, '',  '');
      PDF::MultiCell(50, 0, "BL No.: ", '', 'L', false, 0, '',  '');
      PDF::MultiCell(100, 0, $docno, '', 'L', false, 1, '',  '');

      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(0, 0, "");

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, 0, "Contact No.: ", '', 'L', false, 0, '',  '');
      PDF::MultiCell(880, 0, $projectcode, '', 'L', false, 0, '',  '');
      PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
      PDF::MultiCell(100, 0, $dateid, '', 'L', false, 1, '',  '');

      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(0, 0, "");

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(60, 0, "Location: ", '', 'L', false, 0, '',  '');
      PDF::MultiCell(900, 0, $address, '', 'L', false, 1, '',  '');

      PDF::MultiCell(0, 0, "\n\n");
      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(200, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(420, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(175, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(100, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(180, 0, "", 'TLR', 'C', false);

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 20, "", 'LR', 'C', false, 0);
      PDF::MultiCell(200, 20, "", 'LR', 'C', false, 0);
      PDF::MultiCell(420, 20, "REQUESTED BUDGET", 'LRB', 'C', false, 0);
      PDF::MultiCell(175, 20, "PURCHASES/EXPENSES", 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 20, "", 'LR', 'C', false, 0);
      PDF::MultiCell(180, 20, "", 'LR', 'C', false);

      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(200, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(90, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(100, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(130, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(125, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(100, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(180, 10, "", 'LR', 'C', false);
      
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 20, "ITEM NO.", 'LR', 'C', false, 0);
      PDF::MultiCell(200, 20, "PARTICULARS", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 20, "Qty", 'LR', 'C', false, 0);
      PDF::MultiCell(50, 20, "Unit", 'LR', 'C', false, 0);
      PDF::MultiCell(90, 20, "Estimated Amount", 'LR', 'C', false, 0);
      PDF::MultiCell(100, 20, "Approved Amount", 'LR', 'C', false, 0);
      PDF::MultiCell(130, 20, "Total", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 20, "QTY", 'LR', 'C', false, 0);
      PDF::MultiCell(125, 20, "AMOUNT", 'LR', 'C', false, 0);

      PDF::MultiCell(100, 20, "BALANCE AMOUNT", 'LR', 'C', false, 0);
      PDF::MultiCell(180, 20, "REMARKS", 'LR', 'C', false);

      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(200, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(90, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(100, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(130, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(50, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(125, 10, "", 'LR', 'C', false, 0);

      PDF::MultiCell(100, 10, "", 'LR', 'C', false, 0);
      PDF::MultiCell(180, 10, "", 'LR', 'C', false);


      PDF::SetFont($fontbold, '', 5);
      PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(200, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(90, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(100, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(130, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(125, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(100, 0, "", 'TLR', 'C', false, 0);
      PDF::MultiCell(180, 0, "", 'TLR', 'C', false);

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 20, "A", 'LRB', 'C', false, 0);
      PDF::MultiCell(200, 20, " Requested Budget", 'LRB', 'L', false, 0);
      PDF::MultiCell(50, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(50, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(90, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(130, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(50, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(125, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 20, "", 'LRB', 'C', false, 0);
      PDF::MultiCell(180, 20, "", 'LRB', 'C', false);

  }

  public function report_summarized_layout_PDF($config)
  {
    $data = $this->report_default_query($config['params']['dataid']);
    $otherdata = $this->report_other_query($config['params']['dataid']);

    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    if (!empty($data)) {
      $projectid = $data[0]['projectid'];
      $brtrno = $data[0]['brtrno'];
      $bal = $data[0]['bal'];
    } else {
      $projectid = $otherdata[0]['projectid'];
      $brtrno = $otherdata[0]['brtrno'];
      $bal = $otherdata[0]['bal'];
    }

    $qry = "select head.trno,head.docno,head.dateid,head.start,head.end,p.name as projectname,
      format(ifnull((select ((select sum(br.ext) from hbrstock as br 
      where br.trno = hd.brtrno)+hd.bal)-sum(st.ext) 
      from hblhead as hd 
      left join hblstock as st on st.trno = hd.trno 
      where hd.projectid = head.projectid and hd.subproject = head.subproject 
      group by hd.brtrno,hd.bal,hd.dateid
      order by hd.dateid desc limit 1),0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as brbal,
      format(sum(stock.ext)-ifnull((select sum(s.ext) 
      from hblhead as h 
      left join hblstock as s on s.trno=h.trno 
      where h.projectid = head.projectid and h.subproject = head.subproject 
      group by h.trno,h.dateid
      order by h.dateid desc limit 1),0),2) as curbal 
      from hbrhead as head 
      left join hbrstock as stock on stock.trno = head.trno 
      left join projectmasterfile as p on p.line = head.projectid 
      where head.projectid = '" . $projectid . "' and head.trno < '" . $brtrno . "'
      group by head.trno,head.docno,head.dateid,head.start,
      head.end,p.name,head.projectid,head.subproject
      order by head.trno desc";
    $previousbr = $this->coreFunctions->opentable($qry);

    $this->PDF_header($config, $data, $previousbr[0]->docno);

    $subtotalA = 0;
    $subtotalB = 0;
    $totalext = 0;

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $particularname = $this->reporter->fixcolumn([$data[$i]['particulars']],'30');

      $maxrow = 1;

      $countarr = count($particularname);
      $maxrow = $countarr;

      $balamount = $data[$i]['brsamount'] - $data[$i]['ext'];
      if ($data[$i]['particulars'] == "") {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, $i+1, 'TLR', 'C', false, 0, '', '', true, 1);
        PDF::MultiCell(200, 0, ' '.$data[$i]['particulars'], 'TLR', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, round($data[$i]['brsqty']), 'TLR', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, $data[$i]['brsuom'], 'TLR', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(90, 0, number_format($data[$i]['brsrrcost'], $decimalprice), 'TLR', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, number_format($data[$i]['brsamount'], $decimalprice), 'TLR', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(130, 0, number_format($data[$i]['brsext'], $decimalprice), 'TLR', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, round($data[$i]['qty']), 'TLR', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(125, 0, number_format($data[$i]['ext'], $decimalprice), 'TLR', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, number_format($balamount,2), 'TLR', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(180, 0, $data[$i]['remarks'], 'TLR', 'L', false, 1, '', '', false, 1);
      } else {
        for($r = 0; $r < $maxrow; $r++) {
          if($r == 0) {
              $brsqty = round($data[$i]['brsqty']);
              $brsuom = $data[$i]['brsuom'];
              $brsrrcost = number_format($data[$i]['brsrrcost'], $decimalprice);
              $brsamount = number_format($data[$i]['brsamount'], $decimalprice);
              $brsext = number_format($data[$i]['brsext'], $decimalprice);
              $qty = round($data[$i]['qty']);
              $ext = number_format($data[$i]['ext'], $decimalprice);
              $balamount = number_format($balamount,2);
              $remarks = (isset($data[$i]['remarks']) ? $data[$i]['remarks'] : '');
              $n= $i + 1;
          } else {
              $brsqty = "";
              $brsuom = "";
              $brsrrcost = "";
              $brsamount = "";
              $brsext = "";
              $qty = "";
              $ext = "";
              $balamount = "";
              $remarks = "";
              $n = '';

          }
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 0, $n, 'LR', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(200, 0, isset($particularname[$r]) ? ' '.$particularname[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $brsqty, 'L', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $brsuom, 'L', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(90, 0, $brsrrcost.' ', 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, $brsamount.' ', 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(130, 0, $brsext.' ', 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $qty, 'L', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(125, 0, $ext.' ', 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, $balamount.' ', 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(180, 0, ' '.$remarks, 'LR', 'L', false, 1, '', '', false, 1);
        }

            PDF::SetFont($font, '', 2);
            PDF::MultiCell(50, 0, '', 'LRT', 'C', false, 0,'','',true,0);
            PDF::MultiCell(200, 0, '', 'LT', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, '', 'LT', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(90, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(130, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(125, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(180, 0, '', 'LRT', 'C', false, 1, '', '', false, 1);
      }
     
      $subtotalA += $data[$i]['ext'];
      
    }
  
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' Subtotal A', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(130, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, number_format($subtotalA, $decimalprice).' ', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);

    if (!empty($otherdata)) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 20, 'B', 'LRB', 'C', false, 0, '', '', true, 1);
      PDF::MultiCell(200, 20, ' Other', 'LRB', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(130, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);


      for ($i = 0; $i < count($otherdata); $i++) {
        $particularname = $this->reporter->fixcolumn([$otherdata[$i]['particulars']],'30');

        $maxrow = 1;

        $countarr = count($particularname);
        $maxrow = $countarr;

        if ($otherdata[$i]['particulars'] == "") {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 20, $i+1, 'TLR', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(200, 20, $otherdata[$i]['particulars'], 'TLR', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 20, round($otherdata[$i]['qty']), 'TLR', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 20, $otherdata[$i]['uom'], 'TLR', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(90, 20, '', 'TLR', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 20, '', 'TLR', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(130, 20, '', 'TLR', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 20, '', 'TLR', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(125, 20, number_format($otherdata[$i]['rrcost'], $decimalprice), 'TLR', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 20, '', 'TLR', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(180, 20, $otherdata[$i]['remarks'], 'TLR', 'L', false, 1, '', '', false, 1);
        } else {
          for($r = 0; $r < $maxrow; $r++) {
            if($r == 0) {
                $qty = round($otherdata[$i]['qty']);
                $uom = $otherdata[$i]['uom'];
                $rrcost = number_format($otherdata[$i]['rrcost'], $decimalprice);
                $remarks = $otherdata[$i]['remarks'];
                $n= $i + 1;
            } else {
                $qty = "";
                $uom = "";
                $rrcost = "";
                $remarks = "";
                $n = '';
  
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, $n, 'LR', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(200, 0, isset($particularname[$r]) ? ' '.$particularname[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $qty, 'L', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $uom, 'L', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(90, 0, '', 'L', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', 'L', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(130, 0, '', 'L', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, '', 'L', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(125, 0, $rrcost.' ', 'L', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', 'L', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(180, 0, ' '.$remarks, 'LR', 'L', false, 1, '', '', false, 1);
          }
  
              PDF::SetFont($font, '', 2);
              PDF::MultiCell(50, 0, '', 'LRT', 'C', false, 0,'','',true,0);
              PDF::MultiCell(200, 0, '', 'LT', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(50, 0, '', 'LT', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(50, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(90, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(130, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(50, 0,'', 'LT', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(125, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0, '', 'LT', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(180, 0, '', 'LRT', 'C', false, 1, '', '', false, 1);
        }

          $subtotalB += $otherdata[$i]['ext'];
        
      }
  
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
      PDF::MultiCell(200, 20, ' Subtotal B', 'LRB', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(130, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(125, 20, number_format($subtotalB, $decimalprice).' ', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);
  
    }

    $totalext = $subtotalA + $subtotalB;
 
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
      PDF::MultiCell(200, 20, 'TOTAL', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(130, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(125, 20, number_format($totalext, $decimalprice).' ', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);

    $qry = "select sum(stock.rrcost) as budgetreq, sum(stock.amount) as budgetapp
    from hbrhead as head 
    left join hbrstock as stock on stock.trno = head.trno
    where head.trno = '" . $brtrno . "'";
    $result2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' BUDGET REQUEST', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(30, 20, 'Php ', 'LB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, number_format($result2[0]['budgetreq'], $decimalprice).' ', 'RB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' APPROVED BUDGET', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(30, 20, 'Php ', 'LB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, number_format($result2[0]['budgetapp'], $decimalprice).' ', 'RB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' BALANCE FROM PREVIOUS BUDGET', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(30, 20, 'Php ', 'LB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, number_format($bal, $decimalprice).' ', 'RB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(180, 20, ' REMAINING OF BR# ' . $previousbr[0]->docno, 'LRB', 'L', false, 1, '', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' ACTUAL TOTAL EXPENSES', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(30, 20, 'Php ', 'LB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, number_format($totalext, $decimalprice).' ', 'RB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);

    // cash on hand = (approved budget + balance)-actual expense
    $coh = ($result2[0]['budgetapp'] + $bal) - $totalext;

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 20, ' CASH ON HAND', 'LRB', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(30, 20, 'Php ', 'LB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, number_format($coh, $decimalprice).' ', 'RB', 'R', false, 0, '', '', false, 1);

    PDF::MultiCell(50, 20, '', 'LRB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(125, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, '', 'LRB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 20, '', 'LRB', 'L', false, 1, '', '', false, 1);

    PDF::MultiCell(0, 50, "\n\n\n\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'PREPARED BY : ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, ' ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'VERIFIED BY : ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, ' ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'CHECKED BY :', '', 'L', false, 0);
    PDF::MultiCell(300, 0, ' ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, $config['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, $config['params']['dataparams']['received'], 'B', 'C', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, $config['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L');

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(300, 0, ' ', '', 'L');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Project in Charge', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_detailed_layout_PDF($config)
  {
    $data = $this->report_detailed_query($config['params']['dataid']);
    $center = $config['params']['center'];
    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [800, 1300]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(1000, 0, ' '.strtoupper($headerdata[0]->name), '', 'L',false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(1000, 0, ' Period Covered: ' . date('M-d-Y', strtotime($data[0]['start'])) . ' TO ' . date('M-d-Y', strtotime($data[0]['end'])), '', 'L',false);
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(140, 0, '', '', 'L', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(50, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(170, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(100, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(110, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(90, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(170, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(50, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(60, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(60, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(80, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(30, 0, "", 'TLR', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Liq. No.", 'LR', 'C', false,0);
    PDF::MultiCell(70, 0, "Location", 'LR', 'C', false,0);
    PDF::MultiCell(170, 0, "Supplier", 'LR', 'C', false,0);
    PDF::MultiCell(100, 0, "Address", 'LR', 'C', false,0);
    PDF::MultiCell(110, 0, "TIN Number", 'LR', 'C', false,0);
    PDF::MultiCell(90, 0, "O.R. Number", 'LR', 'C', false,0);
    PDF::MultiCell(170, 0, "Particular", 'LR', 'C', false,0);
    PDF::MultiCell(50, 0, "Qty", 'LR', 'C', false,0);
    PDF::MultiCell(70, 0, "Amount", 'LR', 'C', false,0);
    PDF::MultiCell(60, 0, "Purchase", 'LR', 'C', false,0);
    PDF::MultiCell(60, 0, "Input VAT", 'LR', 'C', false,0);
    PDF::MultiCell(80, 0, "Total Amount", 'LR', 'C', false,0);
    PDF::MultiCell(70, 0, "O.R. Date", 'LR', 'C', false,0);
    PDF::MultiCell(30, 0, "VAT", 'LR', 'C', false,0);
    PDF::MultiCell(70, 0, "Entry Date", 'LR', 'C', false);
    
    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(50, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(170, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(100, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(110, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(90, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(170, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(50, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(60, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(60, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(80, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(30, 0, "", 'LRB', 'C', false,0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', false);

    $totalamt = $totalpurchase = $totalvat =0;

    $countarr = 0;
    $countarrlocation = 0;
    $countarraddress = 0;
    $countarrtin = 0;
    $countarrornum = 0;
    $countarrparticular = 0;

    $totalext = 0;
    $liq = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        
        $location = $data[$i]['location'];
        $supplier = $data[$i]['supplier'];
        $address = $data[$i]['address'];
        $tin = $data[$i]['tin'];
        $ref = $data[$i]['ref'];
        $particular = $data[$i]['particulars'];

        $arrsupplier = $this->reporter->fixcolumn([$supplier],'23');
        $countarr = count($arrsupplier);

        $arrlocation = $this->reporter->fixcolumn([$location],'10');
        $countarrlocation = count($arrlocation);

        $arraddress = $this->reporter->fixcolumn([$address],'13');
        $countarraddress = count($arraddress);

        $arrtin = $this->reporter->fixcolumn([$tin],'17');
        $countarrtin = count($arrtin);

        $arrref = $this->reporter->fixcolumn([$ref],'13');
        $countarrornum = count($arrref);

        $arrparticular = $this->reporter->fixcolumn([$particular],'23');
        $countarrparticular = count($arrparticular);

        $maxrow = 1;
        $maxrow = max($countarr,$countarrlocation,$countarraddress,$countarrtin,$countarrornum,$countarrparticular);

        if ($data[$i]['particulars'] == "") {
          
          PDF::SetFont($font, '', 10);
          $liq = 'LIQ. #'.$data[0]['liqno'] * 1;
          PDF::MultiCell(50, 0, $liq, 'LB', 'C', false, 0);
          PDF::MultiCell(70, 0, $data[$i]['location'], 'LB', 'L', false, 0);
          PDF::MultiCell(170, 0, $data[$i]['supplier'], 'LB', 'L', false, 0);
          PDF::MultiCell(100, 0, $data[$i]['address'], 'LB', 'L', false, 0);
          PDF::MultiCell(110, 0, $data[$i]['tin'], 'LB', 'L', false, 0);
          PDF::MultiCell(90, 0, $data[$i]['ref'], 'LB', 'L', false, 0);
          PDF::MultiCell(170, 0, $data[$i]['particulars'], 'LB', 'L', false, 0);
          PDF::MultiCell(50, 0, round($data[$i]['qty']), 'LB', 'C', false, 0);
          PDF::MultiCell(70, 0, number_format($data[$i]['rrcost'],2), 'LB', 'R', false, 0);
          PDF::MultiCell(60, 0, number_format($data[$i]['purchase'],2), 'LB', 'R', false, 0);
          PDF::MultiCell(60, 0, number_format($data[$i]['vat'],2), 'LB', 'R', false, 0);
          PDF::MultiCell(80, 0, number_format($data[$i]['ext'],2), 'LB', 'R', false, 0);
          PDF::MultiCell(70, 0, $data[$i]['ordate'], 'LB', 'C', false, 0);
          if ($data[$i]['isvat'] == 1) {
            $isvat = 'T';
          } else {
            $isvat = 'F';
          }
          
          PDF::MultiCell(30, 0, $isvat, 'LB', 'C', false, 0);
          PDF::MultiCell(70, 0, $data[$i]['dateid'], 'LBR', 'C');
        } else {
            for($r = 0; $r < $maxrow; $r++) {
              if($r == 0) {
                  $qty = round($data[$i]['qty']);
                  $rrcost = number_format($data[$i]['rrcost'],2);
                  $purchase = number_format($data[$i]['purchase'],2);
                  $vat = number_format($data[$i]['vat'],2);
                  $ext = number_format($data[$i]['ext'],2);
                  $ordate = $data[$i]['ordate'];
                  if ($data[$i]['isvat'] == 1) {
                    $isvat = 'T';
                  } else {
                    $isvat = 'F';
                  }
                  $dateid = $data[$i]['dateid'];
                  $liq = 'LIQ. #'.$data[0]['liqno'] * 1;
              } else {
                  $qty = '';
                  $rrcost = '';
                  $purchase = '';
                  $vat = '';
                  $ext = '';
                  $ordate = '';
                  $isvat = '';
                  $dateid = '';
                  $liq = '';
              }
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(50, 0, $liq , 'LR', 'C', false, 0,'','',true,1);
              PDF::MultiCell(70, 0, isset($arrlocation[$r]) ? ' '.$arrlocation[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(170, 0, isset($arrsupplier[$r]) ? ' '.$arrsupplier[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0, isset($arraddress[$r]) ? ' '.$arraddress[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(110, 0, isset($arrtin[$r]) ? ' '.$arrtin[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(90, 0, isset($arrref[$r]) ? ' '.$arrref[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(170, 0, isset($arrparticular[$r]) ? ' '.$arrparticular[$r] : '', 'L', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(50, 0, $qty, 'L', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(70, 0, $rrcost, 'L', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(60, 0, $purchase, 'L', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(60, 0, $vat, 'L', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(80, 0, $ext, 'L', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(70, 0, $ordate, 'L', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(30, 0, $isvat, 'L', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(70, 0, $dateid, 'LR', 'C', false, 1, '', '', false, 0);
            }

            PDF::SetFont($font, '', 2);
            PDF::MultiCell(50, 0, '', 'LRB', 'C', false, 0,'','',true,0);
            PDF::MultiCell(70, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(170, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(110, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(90, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(170, 0, '', 'LB', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, '', 'LB', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(70, 0,'', 'LB', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0,'', 'LB', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0,'', 'LB', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0,'', 'LB', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(70, 0, '', 'LB', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(30, 0, '', 'LB', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(70, 0, '', 'LRB', 'C', false, 1, '', '', false, 1);
        }

        $totalamt += $data[$i]['rrcost'];
        $totalpurchase += $data[$i]['purchase'];
        $totalvat += $data[$i]['vat'];
        $totalext += $data[$i]['ext'];

      }

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 0, '', '', 'C', false, 0,'','',true,0);
      PDF::MultiCell(70, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(170, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(110, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(90, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(170, 0, '', '', 'L', false, 0, '', '', false, 1);
      PDF::MultiCell(50, 0, '', '', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(70, 0,number_format($totalamt,2), '', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(60, 0,number_format($totalpurchase,2), '', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(60, 0,number_format($totalvat,2), '', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(80, 0,number_format($totalext,2), '', 'R', false, 0, '', '', false, 1);
      PDF::MultiCell(70, 0, '', '', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(30, 0, '', '', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(70, 0, '', '', 'C', false, 1, '', '', false, 1);
    }

    PDF::MultiCell(0, 50, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $config['params']['dataparams']['prepared'], 'T', 'L', false, 0);
    PDF::MultiCell(500, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $config['params']['dataparams']['received'], 'T', 'L');
    
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(200, 0, '', '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'PREPARED BY : ', '', 'L', false, 0);
    PDF::MultiCell(500, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'CHECKED/VERIFIED : ', '', 'L');
    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

}
