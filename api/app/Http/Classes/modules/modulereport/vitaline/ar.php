<?php

namespace App\Http\Classes\modules\modulereport\vitaline;

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

class ar
{
  private $modulename = "Receivable Setup";
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

  public function createreportfilter($config){
    $fields = ['radioprint','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
    ");
  }

  public function report_default_query($config){
    $trno = $config['params']['dataid'];
    $query = "
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address, 
      head.terms, head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, detail.db, 
      detail.cr, detail.client as dclient, detail.checkno
      from lahead as head 
      left join ladetail as detail on detail.trno=head.trno 
      left join client on client.client=head.client
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='ar' and head.trno='$trno'
      union all
      select head.rem, detail.rem as remarks, 
      date(head.dateid) as dateid, head.docno, 
      client.client, client.clientname, head.address, 
      head.terms, head.yourref, head.ourref,
      coa.acno, coa.acnoname, detail.ref, 
      date(detail.postdate) as postdate, detail.db, 
      detail.cr, dclient.client as dclient, detail.checkno
      from glhead as head 
      left join gldetail as detail on detail.trno=head.trno 
      left join client on client.clientid=head.clientid
      left join coa on coa.acnoid=detail.acnoid 
      left join client as dclient on dclient.clientid=detail.clientid
      where head.doc='ar' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }//end fn

  public function reportplotting($params, $data) {
    if($params['params']['dataparams']['print'] == "default") {
      return $this->default_ar_layout($params, $data);
    } else if($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_AR_PDF($params, $data);
    }
  }

  public function rpt_ar_header_default($params,$data){
    $str = '';
    $border = "1px solid";
    $font = "Century Gothic";
    $fontsize = "11";

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center,$username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('RECEIVABLE SETUP','600',null,false,$border,'','L',$font,'18','B','','');
    $str .= $this->reporter->col('DOCUMENT # :','100',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,$border,'B','L',$font, $fontsize,'','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ','80',null,false,$border,'','L',$font, $fontsize,'B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,$border,'B','L',$font, $fontsize,'','30px','4px');
    $str .= $this->reporter->col('DATE : ','40',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,$border,'B','R',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ','80',null,false,$border,'','L',$font, $fontsize,'B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false,$border,'B','L',$font, $fontsize,'','30px','4px');
    $str .= $this->reporter->col('REF. :','40',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'160',null,false,$border,'B','R',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null,null,false,$border,'','R',$font,'10','','','4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('ACCOUNT NAME','350',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('REFERENCE&nbsp#','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('DATE','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('DEBIT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('CREDIT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('CLIENT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');



    return $str;
  }

  public function default_ar_layout($params,$data){

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count=35;
    $page=35;
    $border = "1px solid";
    $font = "Century Gothic";
    $fontsize = "11";

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_ar_header_default($params,$data);
    $totaldb=0;
    $totalcr=0;
    for($i=0;$i<count($data);$i++){

      $debit=number_format($data[$i]['db'],$decimal);
      $debit = $debit < 0 ? '-' : $debit;
      $credit=number_format($data[$i]['cr'],$decimal);
      $credit = $credit < 0 ? '-' : $credit;
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[$i]['acno'],'75',null,false,$border,'','C',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['acnoname'],'350',null,false,$border,'','L',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['ref'],'75',null,false,$border,'','C',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,$border,'','C',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($debit,'75',null,false,$border,'','R',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($credit,'75',null,false,$border,'','R',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['client'],'75',null,false,$border,'','C',$font, $fontsize,'','','2px');
    $totaldb=$totaldb+$data[$i]['db'];
    $totalcr=$totalcr+$data[$i]['cr'];



    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();

    $loggeduser = $username;
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($loggeduser).' '.date('m/d/Y h:i:s a',time()) . '&nbsp;&nbsp;&nbsp;RSSC','600',null,false,$border,'','L',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();   
      
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('RECEIVABLE SETUP','600',null,false,$border,'','L',$font,'18','B','','');
    $str .= $this->reporter->col('DOCUMENT # :','100',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,$border,'B','L',$font, $fontsize,'','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ','80',null,false,$border,'','L',$font, $fontsize,'B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,$border,'B','L',$font, $fontsize,'','30px','4px');
    $str .= $this->reporter->col('DATE : ','40',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,$border,'B','R',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ','80',null,false,$border,'','L',$font, $fontsize,'B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false,$border,'B','L',$font, $fontsize,'','30px','4px');
    $str .= $this->reporter->col('REF. :','40',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'160',null,false,$border,'B','R',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null,null,false,$border,'','R',$font,'10','','','4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('ACCOUNT NAME','350',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('REFERENCE #','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('DATE','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('DEBIT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('CREDIT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->col('CLIENT','75',null,false,$border,'B','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->endrow();
         $str .= $this->reporter->printline();
    $page=$page + $count;
    }
    }       

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','2px');
    $str .= $this->reporter->col('GRAND TOTAL :','350',null,false,'1px dotted ','T','R',$font, $fontsize,'B','30px','2px');
    $str .= $this->reporter->col(number_format($totaldb,2),'75',null,false,'1px dotted ','T','R',$font, $fontsize,'B','','2px');
    $str .= $this->reporter->col(number_format($totalcr,2),'75',null,false,'1px dotted ','T','R',$font, $fontsize,'B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C',$font, $fontsize,'B','30px','8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ','266',null,false,$border,'','L',$font, $fontsize,'','','');
    $str .= $this->reporter->col('Approved By :','266',null,false,$border,'','C',$font, $fontsize,'','','');
    $str .= $this->reporter->col('Received By :','266',null,false,$border,'','R',$font, $fontsize,'','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"],'266',null,false,$border,'','L',$font, $fontsize,'B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"],'266',null,false,$border,'','C',$font, $fontsize,'B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["received"],'266',null,false,$border,'','R',$font, $fontsize,'B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }//end fn

  public function default_AR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

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
    PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.strtoupper($headerdata[0]->name), '', 'L');
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
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(105, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', 'B');
  }

  public function default_AR_PDF($params, $data)
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
    $this->default_AR_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        // $acnoname = $this->reporter->fixcolumn([$data[$i]['acnoname']],'100');
        $maxrow = 1;
        // $countarr = count($acnoname);
        // $maxrow = $countarr;
        $acno = $data[$i]['acno'];
        $acnoname = $data[$i]['acnoname'];
        $ref = $data[$i]['ref'];
        $postdate = $data[$i]['postdate'];
        $debit = number_format($data[$i]['db'],$decimalcurr);
        $credit = number_format($data[$i]['cr'],$decimalcurr);
        $client = $data[$i]['client'];
        $debit = $debit < 0 ? '-' : $debit;
        $credit = $credit < 0 ? '-' : $credit;

        $arr_acno = $this->reporter->fixcolumn([$acno],'16',0);
        $arr_acnoname = $this->reporter->fixcolumn([$acnoname],'100',0);
        $arr_ref = $this->reporter->fixcolumn([$ref],'16',0);
        $arr_postdate = $this->reporter->fixcolumn([$postdate],'16',0);
        $arr_debit = $this->reporter->fixcolumn([$debit],'13',0);
        $arr_credit = $this->reporter->fixcolumn([$credit],'13',0);
        $arr_client = $this->reporter->fixcolumn([$client],'16',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(160, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(105, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', 0, 1, '', '', true, 0, true, false);
        }

        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];
        if (intVal($i) + 1 == $page) {
          $this->default_AR_header_PDF($params, $data);
          $page += $count;
        }
    }
    
  }
  PDF::SetFont($font, '', 5);
  PDF::MultiCell(705, 0, '', 'T');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(430, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

}
