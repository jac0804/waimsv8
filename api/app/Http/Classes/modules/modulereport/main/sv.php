<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class sv
{
  private $modulename = "Petty Cash Voucher";
  public $tablenum = 'transnum';
  public $head = 'svhead';
  public $hhead = 'hsvhead';
  public $detail = 'svdetail';
  public $hdetail = 'hsvdetail';

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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $signatories = $this->othersClass->getSignatories($config);
    $prepared = '';
    $approved = '';
    $received =  '';
    if ($companyid == 39) {
      foreach ($signatories as $key => $value) {
        switch ($value->fieldname) {
          case 'prepared':
            $prepared = $value->fieldvalue;
            break;
          case 'approved':
            $approved = $value->fieldvalue;
            break;
          case 'received':
            $received = $value->fieldvalue;
            break;
        }
      }
    }

    if ($companyid == 37) {
      return $this->coreFunctions->opentable(
        "select 
        'PDFM' as print,
        '" . $username . "' as prepared,
        '' as approved,
        '' as received
        "
      );
    }

    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '" . $prepared . "' as prepared,
      '" . $approved . "' as approved,
      '" . $received . "' as received
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select head.rem, detail.rem as remarks, 
    date(head.dateid) as dateid, head.docno, 
    client.client, client.clientname, head.address,  
    head.yourref, head.ourref,
    coa.acno, coa.acnoname, detail.ref, 
    date(detail.postdate) as postdate, detail.db,detail.cr,  
    detail.client as dclient,pca.acnoname as headaccount,pmaster.code,pmaster.name,head.amt, proj.name as detailproj
    from " . $this->head . " as head 
    left join " . $this->detail . " as detail on detail.trno=head.trno 
    left join client on client.client=head.client
    left join coa on coa.acnoid=detail.acnoid
    left join coa as pca on pca.acno=head.contra
    left join projectmasterfile as pmaster on pmaster.line=head.projectid
    left join projectmasterfile as proj on proj.line = detail.projectid
    where  head.trno='$trno'
    union all
    select head.rem, detail.rem as remarks, 
    date(head.dateid) as dateid, head.docno, 
    client.client, client.clientname, head.address, 
    head.yourref, head.ourref,
    coa.acno, coa.acnoname, detail.ref, 
    date(detail.postdate) as postdate, detail.db,detail.cr, 
    dclient.client as dclient,pca.acnoname as headaccount,pmaster.code,pmaster.name,head.amt, proj.name as detailproj
    from " . $this->hhead . " as head left join " . $this->hdetail . " as detail on detail.trno=head.trno 
    left join client on client.client=head.client
    left join coa on coa.acnoid=detail.acnoid
    left join coa as pca on pca.acno=head.contra 
    left join projectmasterfile as pmaster on pmaster.line=head.projectid
    left join projectmasterfile as proj on proj.line = detail.projectid
    left join client as dclient on dclient.client=detail.client
    where head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_sv_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_SV_PDF($params, $data);
    }
  }

  public function rpt_default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PETTY CASH VOUCHER', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('NOTES', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_sv_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($params, $data);
    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      $debit = number_format($data[$i]['db'], $decimal);
      $debit = $debit < 0 ? '-' : $debit;

      $credit = number_format($data[$i]['cr'], $decimal);
      $credit = $credit < 0 ? '-' : $credit;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['remarks'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        if ($companyid == 1) {
          $loggeduser = $username;
          $str .= $this->rpt_default_header($params, $data);
          $str .= $this->reporter->printline();
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function default_SV_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

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
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');

    if ($companyid == 39) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(120, 0, "Petty Cash Account: ", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(430, 0, (isset($data[0]['headaccount']) ? $data[0]['headaccount'] : ''), 'B', 'L', false, 0, '',  '');
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(90, 0, "Amt Released: ", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(60, 0, number_format((isset($data[0]['amt']) ? $data[0]['amt'] : ''), 2), 'B', 'R', false, 1, '',  '');
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "ACCT NO.", '', 'L', false, 0);
    PDF::MultiCell(190, 0, "ACCOUNT NAME", '', 'C', false, 0);

    if ($companyid == 39) {
      PDF::MultiCell(80, 0, "PROJECT", '', 'C', false, 0);
    }

    PDF::MultiCell(80, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "DEBIT", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "CREDIT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "NOTES", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_SV_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->default_SV_header_PDF($params, $data);

    $totaldb = 0;
    $totalcr = 0;

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', '');

    for ($i = 0; $i < count($data); $i++) {
      // ///////////////// start acnoname
      // $arracno = array();
      // $acnoname = [];
      // $acnoword=[];
      // $acnoword=explode(' ',$data[$i]['acnoname']);
      // $acnowordstring='';
      // foreach($acnoword as $word) {
      //   $acnowordstring=$acnowordstring.$word.' ';
      //   if(strlen($acnowordstring)>42){
      //     $acnowordstring=str_replace($word,'',$acnowordstring);
      //     array_push($arritem,$acnowordstring);
      //     $acnowordstring='';
      //     $acnowordstring=$acnowordstring.$word.' ';
      //   }
      // }
      // array_push($arracno,$acnowordstring);
      // $acnowordstring='';
      // ///////////////// acnoname
      // if(!empty($arracno)) {
      //   foreach($arracno as $arri) {
      //     if(strstr($arri, "\n")) {
      //       $array = preg_split("/\r\n|\n|\r/", $arri);
      //       foreach($array as $arr) {
      //         array_push($acnoname, $arr);
      //       }
      //     } else {
      //       array_push($acnoname, $arri);
      //     }
      //   }
      // }
      // ////////////////////// end acnoname
      // $maxrow = 1;
      // $countarr = count($acnoname);
      // $maxrow = $countarr;

      $maxrow = 1;
      $acno = $data[$i]['acno'];
      $acnoname = $data[$i]['acnoname'];
      $detailproj = $data[$i]['detailproj'];
      $postdate = $data[$i]['postdate'];
      $notes = $data[$i]['rem'];
      $debit = number_format($data[$i]['db'], $decimalcurr);
      $credit = number_format($data[$i]['cr'], $decimalcurr);
      $remarks = $data[$i]['remarks'];
      $debit = $debit < 0 ? '-' : $debit;
      $credit = $credit < 0 ? '-' : $credit;

      $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
      $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
      $arr_detailproj = $this->reporter->fixcolumn([$detailproj], '16', 0);
      $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
      $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
      $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
      $arr_remarks = $this->reporter->fixcolumn([$remarks], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_detailproj, $arr_postdate, $arr_debit, $arr_credit, $arr_remarks]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
        PDF::MultiCell(190, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
        if ($companyid == 39) {
          PDF::MultiCell(80, 0, (isset($arr_detailproj[$r]) ? $arr_detailproj[$r] : ''), '', 'C', false, 0, '', '', false, 1);
        }
        PDF::MultiCell(80, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(70, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(80, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, (isset($arr_remarks[$r]) ? $arr_remarks[$r] : ''), '', 'L', false, 1, '', '', false, 1);
      }

      // if ($data[$i]['acnoname'] == '') {
      // } else {
      //     for($r = 0; $r < $maxrow; $r++) {
      //         if($r == 0) {
      //           $debit=number_format($data[$i]['db'],$decimalcurr);
      //           if ($debit<1)
      //           {
      //             $debit='-';
      //           }

      //           $credit=number_format($data[$i]['cr'],$decimalcurr);
      //           if ($credit<1)
      //           {
      //             $credit='-';
      //           }
      //             $acno =  $data[$i]['acno'];
      //             $postdate = $data[$i]['postdate'];
      //             $remarks = $data[$i]['remarks'];
      //         } else {
      //             $acno = '';
      //             $postdate = '';
      //             $debit = '';
      //             $credit = '';
      //             $remarks = '';
      //         }
      //     }
      // }
      $totaldb += $data[$i]['db'];
      $totalcr += $data[$i]['cr'];

      if (intVal($i) + 1 == $page) {
        $this->default_SV_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(437, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(70, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(80, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    if ($companyid == 39) {
      PDF::MultiCell(0, 0, "\n\n");
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 0, 'NOTES: ', '', 'L', false, 0, '',  '');
      PDF::MultiCell(300, 0, $notes, '', 'L', false, 0, '',  '');
    }

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
