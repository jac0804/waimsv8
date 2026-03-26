<?php

namespace App\Http\Classes\modules\modulereport\conti;

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

class pq
{

  private $modulename = "Petty Cash Request";
  public $tablenum = 'transnum';
  public $head = 'pqhead';
  public $hhead = 'hpqhead';
  public $detail = 'pqdetail';
  public $hdetail = 'hpqdetail';
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

  public function createreportfilter(){
    $fields = ['radioprint','checked','noted','approved','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
     
    ]);
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as checked,
      '' as noted,
      '' as approved");
  }

  public function report_default_query($config){
    $trno = $config['params']['dataid'];
    $query = "
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address,  
      head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, 
      detail.amt,  detail.client as dclient,pca.acnoname as headaccount,pmaster.code,pmaster.name, proj.name as detailproj
      from ".$this->head." as head 
      left join ".$this->detail." as detail on detail.trno=head.trno 
      left join client on client.client=head.client
      left join coa on coa.acnoid=detail.acnoid
      left join coa as pca on pca.acno=head.contra 
      left join projectmasterfile as pmaster on pmaster.line=head.projectid
      left join projectmasterfile as proj on proj.line = detail.projectid
      where head.doc='PQ' and head.trno='$trno'
      union all
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address, 
      head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, 
      detail.amt, dclient.client as dclient,pca.acnoname as headaccount,pmaster.code,pmaster.name, proj.name as detailproj
      from ".$this->hhead." as head 
      left join ".$this->hdetail." as detail on detail.trno=head.trno 
      left join client on client.client=head.client
      left join coa on coa.acnoid=detail.acnoid 
      left join coa as pca on pca.acno=head.contra 
      left join projectmasterfile as pmaster on pmaster.line=head.projectid
      left join projectmasterfile as proj on proj.line = detail.projectid
      left join client as dclient on dclient.client=detail.client
      where head.doc='PQ' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }//end fn

  public function reportplotting($params, $data) {
    if($params['params']['dataparams']['print'] == "default") {
      return $this->default_pq_layout($params, $data);
    } else if($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_PQ_PDF($params, $data);
    }
  }

  public function rpt_default_header($params,$data){

    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $project = $data[0]['code'].' ~ '.$data[0]['name'];

    $str = '';
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
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
    

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PETTY CASH REQUEST','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
    $str .= $this->reporter->col('','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','','L','Century Gothic','13','','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE : ','100',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'500',null,false,'1px solid ','','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('DATE : ','60',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'140',null,false,'1px solid ','','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT : ','100',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($project)? $project:''),'500',null,false,'1px solid ','','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('REF. :','60',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'140',null,false,'1px solid ','','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PETTY CASH ACCOUNT : ','160',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['headaccount'])? $data[0]['headaccount']:''),'440',null,false,'1px solid ','','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col('','160',null,false,'1px solid ','','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCT.#','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('ACCOUNT NAME','350',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('PROJECT','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('DATE','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('AMOUNT','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('NOTES','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    return $str;
  }

  public function default_pq_layout($params,$data){
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count=35;
    $page=35;
    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($params,$data);
    $totaldb=0;
    $totalcr=0;
    for($i=0;$i<count($data);$i++){

    $debit=number_format($data[$i]['amt'],$decimal);
    if ($debit<1)
    {
    $debit='-';
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[$i]['acno'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($data[$i]['acnoname'],'350',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($data[$i]['detailproj'],'100',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($debit,'100',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($data[$i]['remarks'],'100',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
    $totaldb=$totaldb+$data[$i]['amt'];

    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      }
    }       

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('GRAND TOTAL :','350',null,false,'1px dotted ','T','R','Century Gothic','12','B','30px','2px');
    $str .= $this->reporter->col(number_format($totaldb,2),'75',null,false,'1px dotted ','T','R','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Notes : '.$data[0]['rem'],'800',null,false,'1px solid ','','L','Century Gothic','12','','','');
    
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Requested By : ','300',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('Checked By :','500',null,false,'1px solid ','','C','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'300',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["checked"],'500',null,false,'1px solid ','','C','Century Gothic','12','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Noted By : ','300',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('Approved By :','500',null,false,'1px solid ','','C','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["noted"],'300',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"],'500',null,false,'1px solid ','','C','Century Gothic','12','B','','');
  
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }//end fn

  public function default_PQ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Employee: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    $project = $data[0]['code'].' ~ '.$data[0]['name'];
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Project: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, $project, 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Petty Cash Account: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 0, (isset($data[0]['headaccount']) ? $data[0]['headaccount'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "ACCOUNT NO.", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "PROJECT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "AMOUNT", '', 'R', false, 0);
    PDF::MultiCell(20, 0, "", '', 'R', false, 0);
    PDF::MultiCell(130, 0, "NOTES", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_PQ_PDF($params, $data)
  {
  
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PQ_header_PDF($params, $data);

   
  
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($data)) {
    
      for ($i = 0; $i < count($data); $i++) {
        $acnoname = $this->reporter->fixcolumn([$data[$i]['acnoname']],'100',0);
     
      ////////////////////// end acnoname
      $maxrow = 1;

      $countarr = count($acnoname);
  
      $maxrow = $countarr;
  
      if ($data[$i]['acnoname'] == '') {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, $acno, '', 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(150, 0, isset($acnoname[$r]) ? $acnoname[$r] : '', '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, $detailproj, '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(20, 0, '', '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(130, 0, $remarks, '', 'L', false, 1, '', '', false, 1);
      } else {
          for($r = 0; $r < $maxrow; $r++) {
              if($r == 0) {
                  $acno =  $data[$i]['acno'];
                  $detailproj = $data[$i]['detailproj'];
                  $postdate = $data[$i]['postdate'];
                  $amt=number_format($data[$i]['amt'],$decimalcurr);
                  $remarks = $data[$i]['remarks'];
              } else {
                  $acno = '';
                  $detailproj = '';
                  $postdate = '';
                  $amt = '';
                  $remarks = '';
              }
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 0, $acno, '', 'L', false, 0, '', '', true, 1);
              PDF::MultiCell(150, 0, isset($acnoname[$r]) ? $acnoname[$r] : '', '', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0, $detailproj, '', 'L', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
              PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(20, 0, '', '', 'R', false, 0, '', '', false, 1);
              PDF::MultiCell(130, 0, $remarks, '', 'L', false, 1, '', '', false, 1);
          }
      }
        $totaldb += $data[$i]['amt'];
    
        if (intVal($i) + 1 == $page) {
          $this->default_PQ_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
   

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totaldb, $decimalprice), '', 'R');
    PDF::MultiCell(100, 0, '', '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Requested By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Checked By: ', '', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $data[0]['clientname'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);


    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Noted By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['noted'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);

    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

}
