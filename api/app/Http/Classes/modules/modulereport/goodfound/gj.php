<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

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

class gj
{

  private $modulename = "General Journal";
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

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'tin', 'position', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.label', 'Report Type');
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
        ['label' => 'BIR Form 2307 (New)', 'value' => '2', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received,
        '' as checked,
        '' as payor,
        '' as tin,
        '' as position,
        '0' as reporttype
        "
    );
  }

  public function reportplotting($config, $data)
  {
    if ($config['params']['dataparams']['print'] == "default") {
      switch ($config['params']['dataparams']['reporttype']) {
        case 0: // VOUCHER
          $str = $this->rpt_DEFAULT_GJVOUCHER_LAYOUT($data, $config);
          break;
      }
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      switch ($config['params']['dataparams']['reporttype']) {
        case 0: // VOUCHER
          $str = $this->PDF_DEFAULT_GJVOUCHER_LAYOUT($data, $config);
          break;
        case 2:
          $str = $this->BIR_GJ_PDF($config, $data);
          break;
      }
    }
    return $str;
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case 2:
        $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        client.addr as address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        where head.doc='GJ' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        client.addr as address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        where head.doc='GJ' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc";
        $this->coreFunctions->LogConsole($query);
        $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $arrs = [];
        $arrss = [];
        $ewt = '';
        foreach ($result1 as $key => $value) {
          $ewtrateval = floatval($value['ewtrate']) / 100;
          if ($value['db'] == 0) {
            //FOR CR
            if ($value['cr'] < 0) {
              $db = $value['cr'];
            } else {
              $db = floatval($value['cr']) * -1;
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }

            $ewtamt = $db * $ewtrateval;
          } else {
            //FOR DB
            if ($value['db'] < 0) {
              $db = floatval($value['db']) * -1;
            } else {
              $db = $value['db'];
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }
            $ewtamt = $db * $ewtrateval;
          } //end if

          if ($ewt != $value['ewtcode']) {
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          } else {
            array_push($arrss, $arrs);
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          }

          $ewt = $value['ewtcode'];
        } //end for each

        array_push($arrss, $arrs);
        $keyers = '';
        $finalarrs = [];
        foreach ($arrss as $key => $value) {
          foreach ($value as $key => $y) {
            if ($keyers == '') {
              $keyers = $key;
              $finalarrs[$key]['oamt'] = $y['oamt'];
              $finalarrs[$key]['xamt'] = $y['xamt'];
            } else {
              if ($keyers == $key) {
                $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
                $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
              } else {
                $finalarrs[$key]['oamt'] = $y['oamt'];
                $finalarrs[$key]['xamt'] = $y['xamt'];
              } //end if
            } //end if
            $finalarrs[$key]['month'] = $y['month'];
          }
        } //end for each
        if (empty($result1)) {
          $returnarr[0]['payee'] = '';
          $returnarr[0]['tin'] = '';
          $returnarr[0]['address'] = '';
          $returnarr[0]['month'] = '';
          $returnarr[0]['yr'] = '';
        } else {
          $returnarr[0]['payee'] = $result1[0]['clientname'];
          $returnarr[0]['tin'] = $result1[0]['tin'];
          $returnarr[0]['payortin'] = $result1[0]['payortin'];
          $returnarr[0]['address'] = $result1[0]['address'];
          $returnarr[0]['month'] = $result1[0]['month'];
          $returnarr[0]['yr'] = $result1[0]['yr'];
          $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
          $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
          $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
        }

        $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
        break;

      default:
        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem as headrem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          detail.client as dclient, detail.checkno, head.project,detail.rem,
          right(dept.client,4) as deptcostcenter,
          coa.acno as acctcode,concat(coa.acno,'~',coa.acnoname,' ',head.project) as entry
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno 
          left join client on client.client=head.client
          left join client as dept on dept.clientid=detail.deptid
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='GJ' and head.trno=$trno
          union all
          select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem as headrem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          dclient.client as dclient, detail.checkno, head.project,detail.rem,
          right(dept.client,4) as deptcostcenter,
          coa.acno as acctcode,concat(coa.acno,'~',coa.acnoname,' ',head.project) as entry
          from glhead as head 
          left join gldetail as detail on detail.trno=head.trno 
          left join client on client.clientid=head.clientid
          left join client as dept on dept.clientid=detail.deptid
          left join coa on coa.acnoid=detail.acnoid 
          left join client as dclient on dclient.clientid=detail.clientid
          where head.doc='GJ' and head.trno=$trno";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        break;
    } // end switch
    return $result;
  }

  public function BIR_GJ_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,tin,zipcode from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $border = '2px solid';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(10, 10);



    //Row 1 Logo
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(780, 10, '', '', 'L', false);
    PDF::MultiCell(50, 10, 'For BIR' . "\n" . 'Use Only', '', 'L', false, 0);
    PDF::MultiCell(50, 10, 'BCS/' . "\n" . 'Item:', '', 'L', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false, 0);
    PDF::MultiCell(140, 10, 'Repupblic of the Philippines' . "\n" . 'Department of Finance' . "\n" . 'Bureau of Internal Revenue', '', 'C', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false);
    PDF::Image(public_path() . '/images/afti/birlogo.png', '310', '10', 55, 55);

    //Row 2
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(120, 55, '', 'TBLR', 'L', false, 0, 10);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(460, 55, 'Certificate of Credible Tax' . "\n" . 'Withheld at Source', 'TBLR', 'C', false, 0, 130);

    PDF::MultiCell(200, 55, '', 'TBLR', 'L', false, 1, 590);
    PDF::Image(public_path() . '/images/afti/bir2307.png', '12', '80', 103, 43);
    PDF::Image(public_path() . '/images/afti/birbarcode.png', '595', '80', 190, 43);

    //Row 3
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 10, 'Fill in all applicable spaces. Mark all appropriate boxes with an "X"', 'TBLR', 'L', false, 1, 10, 129);

    //Row 4
    $d1 = '';
    $m1 = '';
    $y1 = '';

    $d2 = '';
    $m2 = '';
    $y2 = '';

    $month = "";
    $year = "";

    $trno = $params['params']['dataid'];
    if ($data['head'][0]['month'] == "" || $data['head'][0]['yr'] == "") {
      $mmyy = $this->coreFunctions->opentable("select month, right(yr,2) as yr from (select month(dateid) as month, year(dateid) as yr from lahead
        where doc= 'GJ' and trno = $trno
        union all
        select month(dateid) as month, year(dateid) as yr from glhead
        where doc = 'GJ' and trno = $trno) as a");
      $month = $mmyy[0]->month;
      $year = $mmyy[0]->yr;
    } else {
      $month = $data['head'][0]['month'];
      // $year = $data['head'][0]['yr'];
      $year = substr($data['head'][0]['yr'], -2);
    }

    switch ($month) {
      case '1':
      case '2':
      case '3':
        $d1 = '01';
        $m1 = '01';
        $y1 = $year;

        $d2 = '03';
        $m2 = '31';
        $y2 = $year;
        break;

      case '4':
      case '5':
      case '6':
        $d1 = '04';
        $m1 = '01';
        $y1 = $year;

        $d2 = '06';
        $m2 = '30';
        $y2 = $year;
        break;

      case '7':
      case '8':
      case '9':
        $d1 = '07';
        $m1 = '01';
        $y1 = $year;

        $d2 = '09';
        $m2 = '30';
        $y2 = $year;
        break;

      default:
        $d1 = '10';
        $m1 = '01';
        $y1 = $year;

        $d2 = '12';
        $m2 = '31';
        $y2 = $year;
        break;
    }

    PDF::SetFont($font, '', 16);
    PDF::MultiCell(780, 10, '', 'LR', '', false, 0, 10, 142);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 10, '1', 'L', 'C', false, 0, 10, 145);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 10, 'For the Period', '', 'L', false, 0);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(35, 10, 'From', '', '', false, 0);
    PDF::MultiCell(20, 10, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(25, 15, $d1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y1, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(25, 15, $d2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y2, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::MultiCell(95, 10, '', 'R', '', false);

    //Row 5
    PDF::MultiCell(780, 18, '', 'LTBR', '', false, 0, 10, 163);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part I - Payee Information', 'LTBR', 'C', false, 1, 10, 164);

    //Row 6
    PDF::MultiCell(780, 25, '', 'LTBR', '', false, 0);
    PDF::MultiCell(50, 25, '2', '', 'C', false, 0, 10, 185);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'C', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 7
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '3', 'LT', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payee's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'TR', 'L', false);

    //Row 8
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 9
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '4', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '4A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 10
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, (isset($data['head'][0]['billaddress']) ? $data['head'][0]['billaddress'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['res'][0]['billzipcode']) ? $data['res'][0]['billzipcode'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 11
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '5', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Foreign Address If Applicable", 'R', 'L', false);

    //Row 12
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, "", 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 13
    PDF::MultiCell(780, 18, '', 'LRB', '', false, 1, 10, 295);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part II - Payor Information', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(780, 25, '', 'LTR', '', false, 0);
    PDF::MultiCell(50, 25, '6', '', 'C', false, 0, 10, 335);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, $headerdata[0]->tin, 'LTBR', 'C', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 15
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
    PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

    //Row 16
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, $headerdata[0]->name, 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);


    //Row 17
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '8', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '8A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 18
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, $headerdata[0]->address, 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, $headerdata[0]->zipcode, 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 13
    PDF::MultiCell(780, 1, '', 'LRB', '', false, 1, 10, 425);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part III - Details of Monthly Income Payments and Taxes Withheld', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 457);
    PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 457);
    PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 457);
    PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 457);

    //Row 15
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(80, 20, 'ATC', 'LTRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(95, 20, 'Total', 'LTRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'T', true);

    //Row 18 ---atc1

    $total = 0;
    $a = -1;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;

    foreach ($data['detail'] as $key => $value) {
      $a++;

      $ewt_height = PDF::GetStringHeight(200, $data['res'][$a]['ewtdesc']);
      $key_height = PDF::GetStringHeight(80, $key);
      $max_height = max($ewt_height, $key_height);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }
      // ($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) 

      PDF::MultiCell(200, $ewt_height, $data['res'][$a]['ewtdesc'], 'LRB', 'L', false, 0, '', '', true, 0, false, true, 0, 'T', true);
      PDF::MultiCell(80, $ewt_height, $data['res'][$a]['ewtcode'], 'LRB', '', false, 0);

      switch ($data['head'][0]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          PDF::MultiCell(95, $ewt_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $ewt_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $ewt_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $ewt_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      PDF::MultiCell(95, $ewt_height, $total, 'LRB', 'R', false, 0);
      PDF::MultiCell(120, $ewt_height, number_format($data['detail'][$key]['xamt'], 2), 'LRB', 'R', false);

      $totalwtx += $data['detail'][$key]['oamt'];
    }

    //Row 19 ----total
    $totaltax = 0;
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx != 0 ? number_format($totalwtx, 2) : ''), 'LR', 'R', false, 0);
    foreach ($data['detail'] as $key => $value) {
      $totaltax = $totaltax + $data['detail'][$key]['xamt'];
    }
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'LR', 'R', false);
    PDF::SetFont($font, '', 9);

    //Row 21
    PDF::MultiCell(200, 10, 'Money Payments Subjects to', 'TLR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 10, 'Withholding of Business Tax', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    PDF::MultiCell(200, 10, '(Government & Private)', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    //Row 22
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);


    //Row 23
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 24
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 25
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'TLR', 'R', false);

    //Row 26
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.', 'TLR', 'C', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(780, 30, ucwords($params['params']['dataparams']['payor']) . ' / ' . $params['params']['dataparams']['tin'] . ' / ' . ucwords($params['params']['dataparams']['position']), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //Row 28

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29
    PDF::MultiCell(780, 25, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

    //Row 30
    PDF::MultiCell(780, 15, 'CONFORME:', 'LTRB', 'C', false);

    //Row 31
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(780, 30, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //Row 32
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29
    PDF::MultiCell(780, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_default_header_gjvoucher($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

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
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '');

    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '', 'L');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');


    PDF::SetFont($fontbold, '', $fontsize + 7);
    PDF::MultiCell(720, 0, $this->modulename, '', 'C', 0, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(620, 0, "APV #: ", '', 'R', 0, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'R', 0, 1);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Supplier: ", '', 'L', 0, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, $data[0]['clientname'], 'B', 'L', 0, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(320, 0, "DATE: ", '', 'R', 0, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'R', 0, 1);
  }

  public function PDF_DEFAULT_GJVOUCHER_LAYOUT($data, $params)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
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
    $this->PDF_default_header_gjvoucher($params, $data);



    PDF::setFontSpacing(4);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, 'COST', 'TL', 'C', 0, 0);
    PDF::MultiCell(390, 0, 'ACCOUNTING ENTRY', 'TL', 'C', 0, 0);
    PDF::MultiCell(90, 0, 'DEBIT', 'TL', 'C', 0, 0);
    PDF::MultiCell(90, 0, 'CREDIT', 'TL', 'C', 0, 0);
    PDF::MultiCell(80, 0, 'REMARKS', 'TLR', 'C', 0, 1);

    PDF::MultiCell(70, 0, 'CENTER', 'BL', 'C', 0, 0);
    PDF::MultiCell(390, 0, '', 'BL', 'C', 0, 0);
    PDF::MultiCell(90, 0, '', 'BL', 'C', 0, 0);
    PDF::MultiCell(90, 0, '', 'BL', 'C', 0, 0);
    PDF::MultiCell(80, 0, '', 'BLR', 'C', 0, 1);


    $totaldb = 0;
    $totalcr = 0;
    $acname = "";
    $costcenter = "";
    PDF::setFontSpacing(1);
    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;


      if ($data[$i]['db'] <= 0) {
        $debit = '-';
      } else {
        $debit = number_format($data[$i]['db'], 2);
      }
      if ($data[$i]['cr'] <= 0) {
        $credit = '-';
      } else {
        $credit = number_format($data[$i]['cr'], 2);
      }
      $rem = $acname = $data[$i]['rem'];
      $costcenter = $data[$i]['deptcostcenter'];
      $acname = $data[$i]['entry'];

      $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
      $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
      $arr_rem = $this->reporter->fixcolumn([$rem], '20', 0);
      $arr_acname = $this->reporter->fixcolumn([$acname], '50', 0);
      $arr_costcenter = $this->reporter->fixcolumn([$costcenter], '20', 0);


      $maxrow = $this->othersClass->getmaxcolumn([$arr_debit, $arr_credit, $arr_rem, $arr_acname, $arr_costcenter]);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 0, ' ' . (isset($arr_costcenter[$r]) ? $arr_costcenter[$r] : ''), 'BL', 'C', 0, 0);
        PDF::MultiCell(390, 0, ' ' . (isset($arr_acname[$r]) ? $arr_acname[$r] : ''), 'BL', 'L', 0, 0);
        PDF::MultiCell(90, 0, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), 'BL', 'R', 0, 0);
        PDF::MultiCell(90, 0, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), 'BL', 'R', 0, 0);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'BLR', 'L', 0, 1);
      }

      $totaldb += $data[$i]['db'];
      $totalcr += $data[$i]['cr'];

      if (PDF::getY() > 950) {

        PDF::MultiCell(720, 0, '', '', '', 0, 1);
        PDF::MultiCell(720, 0, '', '', '', 0, 1);
        PDF::setFontSpacing(4);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(70, 0, 'COST', 'TL', 'C', 0, 0);
        PDF::MultiCell(390, 0, 'ACCOUNTING ENTRY', 'TL', 'C', 0, 0);
        PDF::MultiCell(90, 0, 'DEBIT', 'TL', 'C', 0, 0);
        PDF::MultiCell(90, 0, 'CREDIT', 'TL', 'C', 0, 0);
        PDF::MultiCell(80, 0, 'REMARKS', 'TLR', 'C', 0, 1);

        PDF::MultiCell(70, 0, 'CENTER', 'BL', 'C', 0, 0);
        PDF::MultiCell(390, 0, '', 'BL', 'C', 0, 0);
        PDF::MultiCell(90, 0, '', 'BL', 'C', 0, 0);
        PDF::MultiCell(90, 0, '', 'BL', 'C', 0, 0);
        PDF::MultiCell(80, 0, '', 'BLR', 'C', 0, 1);
        PDF::setFontSpacing(1);
      }
    }



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, '', 'LB', 'C', 0, 0);
    PDF::MultiCell(390, 0, '', 'LB', 'C', 0, 0);
    PDF::MultiCell(90, 0, '', 'LB', 'R', 0, 0);
    PDF::MultiCell(90, 0, '', 'LB', 'R', 0, 0);
    PDF::MultiCell(80, 0, '', 'LBR', 'L', 0, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, '', 'LB', 'C', 0, 0);
    PDF::MultiCell(390, 0, '', 'LB', 'C', 0, 0);
    PDF::MultiCell(90, 0, '', 'LB', 'R', 0, 0);
    PDF::MultiCell(90, 0, '', 'LB', 'R', 0, 0);
    PDF::MultiCell(80, 0, '', 'LBR', 'L', 0, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, '', 'BL', 'C', 0, 0);
    PDF::MultiCell(390, 0, 'TOTAL', 'BL', 'C', 0, 0);
    PDF::MultiCell(90, 0, number_format($totaldb, 2), 'BL', 'R', 0, 0);
    PDF::MultiCell(90, 0, number_format($totalcr, 2), 'BL', 'R', 0, 0);
    PDF::MultiCell(80, 0, '', 'BLR', 'L', 0, 1);

    PDF::SetFont($font, '', $fontsize - 5);
    PDF::MultiCell(70, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(390, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(80, 0, '', 'R', 'L', 0, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Attachments:', 'L', 'C', 0, 0);

    PDF::MultiCell(17, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'C', 0, 0);
    PDF::MultiCell(18, 0, '', '', 'C', 0, 0);

    PDF::MultiCell(100, 0, 'Invoice/O.R.', '', 'C', 0, 0);

    PDF::MultiCell(17, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'C', 0, 0);
    PDF::MultiCell(18, 0, '', '', 'C', 0, 0);

    PDF::MultiCell(100, 0, 'Purchase SLip', '', 'C', 0, 0);
    PDF::MultiCell(12, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'R', 0, 0);
    PDF::MultiCell(13, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(70, 0, 'Payroll', '', 'L', 0, 0);
    PDF::MultiCell(12, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'R', 0, 0);
    PDF::MultiCell(13, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(70, 0, 'Job Order', '', 'L', 0, 0);
    PDF::MultiCell(12, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'R', 0, 0);
    PDF::MultiCell(13, 0, '', '', 'R', 0, 0);
    PDF::MultiCell(60, 0, 'Others', 'R', 'L', 0, 1);



    PDF::SetFont($font, '', $fontsize - 5);
    PDF::MultiCell(100, 0, '', 'BL', 'C', 0, 0);
    PDF::MultiCell(300, 0, '', 'B', 'C', 0, 0);
    PDF::MultiCell(110, 0, '', 'B', 'R', 0, 0);
    PDF::MultiCell(110, 0, '', 'B', 'R', 0, 0);
    PDF::MultiCell(100, 0, '', 'BR', 'L', 0, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Explanation:', 'LR', 'L', 0, 1);
    PDF::MultiCell(720, 0, '', 'LR', 'L', 0, 1);
    PDF::MultiCell(720, 0, $data[0]['headrem'], 'LR', 'L', 0, 1);

    PDF::SetFont($font, '', $fontsize - 5);
    PDF::MultiCell(720, 0, '', 'BLR', 'L', 0, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 0, 'Prepared By: ', 'L', 'L', 0, 0);
    PDF::MultiCell(240, 0, 'Checked By: ', '', 'L', 0, 0);
    PDF::MultiCell(240, 0, 'Approved By: ', 'R', 'L', 0, 1);

    PDF::MultiCell(720, 0, '', 'LR', 'L', 0, 1);



    PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], 'LB', 'L', 0, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['checked'], 'B', 'L', 0, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], 'BR', 'L', 0, 1);


    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


  public function rpt_default_header($data, $filters)
  {
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('GENERAL JOURNAL', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYEE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '480', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REFERENCE # :', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTES : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '720', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
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
    $str .= $this->reporter->col('ACCOUNT NAME', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CHECK DETAILS', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REMARKS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function rpt_default_header_gjvoucher($data, $filters)
  {
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS PAYABLE VOUCHER', '400', null, false, '', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('# :', '251', null, false, '1px  ', '', 'R', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '149', null, false, '', '', 'R', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAID&nbspTO&nbsp:', '70', null, false, '1px  ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '530', null, false, '1px  ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE :', '100', null, false, '', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS&nbsp:', '30', null, false, '1px  ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '405', null, false, '', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('YOURREF&nbsp:', '10', null, false, '', '', 'R', 'Century Gothic', '12', 'B', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '120', null, false, '1px  ', 'B', 'R', 'Century Gothic', '12', '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('P A R T I C U L A R S', '650', null, false, '1px solid ', 'LRTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    return $str;
  }

  public function rpt_DEFAULT_GJVOUCHER_LAYOUT($data, $filters)
  {
    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];
    $str = '';
    $count = 60;
    $page = 58;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header_gjvoucher($data, $filters);
    $totaldb = 0;
    $totalcr = 0;
    $remarks = "";

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    if ($remarks == $data[0]['rem']) {
      $remarks = "";
    } else {
      $remarks = $data[0]['rem'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($remarks, '650', null, false, '1px solid ', 'LRBT', 'L', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, '1px solid ', 'LRBT', 'R', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTING ENTRY', '500', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', 'RTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $totaldb = 0;
    $totalcr = 0;
    $acname = "";
    for ($i = 0; $i < count($data); $i++) {
      $debit = number_format($data[$i]['db'], 2);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data[$i]['cr'], 2);
      $credit = $credit < 0 ? '-' : $credit;
      if ($acname == $data[$i]['acnoname']) {
        $acname = "";
      } else {
        $acname = $data[$i]['acnoname'];
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($acname, '500', null, false, '1px solid ', 'L', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['project'], '100', null, false, '1px solid ', 'R', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($debit, '100', null, false, '1px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($credit, '100', null, false, '1px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '3px');
    }

    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('', '500', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '215', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '320', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


  public function ftNumberToWordsConverter($number)
  {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if (strpos($numberwords, "/") == false) {
      $numberwords .= " PESOS ";
    } else {
      $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
    } //end if

    return $numberwords;
  } //end function convert to words

  public function ftNumberToWordsBuilder($number)
  {
    if ($number == 0) {
      return 'Zero';
    } else {
      $hyphen      = ' ';
      $conjunction = ' ';
      $separator   = ' ';
      $negative    = 'negative ';
      $decimal     = ' and ';
      $dictionary  = array(
        0                   => '',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Forty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion'
      );

      if (!is_numeric($number)) {
        return false;
      } //end if

      if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        return false;
      } //end if

      if ($number < 0) {
        return $negative . $this->ftNumberToWordsBuilder(abs($number));
      } //end if

      $string = $fraction = null;

      if (strpos($number, '.') !== false) {
        $fractionvalues = explode('.', $number);
        if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
          list($number, $fraction) = explode('.', $number);
        } //end if
      } //end if

      switch (true) {
        case $number < 21:
          $string = $dictionary[$number];
          break;

        case $number < 100:
          $tens   = ((int) ($number / 10)) * 10;
          $units  = $number % 10;
          $string = $dictionary[$tens];
          if ($units) {
            $string .= $hyphen . $dictionary[$units];
          } //end if
          break;

        case $number < 1000:
          $hundreds  = $number / 100;
          $remainder = $number % 100;
          $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
          if ($remainder) {
            $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;

        default:
          $baseUnit = pow(1000, floor(log($number, 1000)));
          $numBaseUnits = (int) ($number / $baseUnit);
          $remainder = $number % $baseUnit;
          $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
          if ($remainder) {
            $string .= $remainder < 100 ? $conjunction : $separator;
            $string .= $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;
      } //end switch
      if (null !== $fraction && is_numeric($fraction)) {

        $string .= $decimal . ' ' . $fraction .  '/100';
        $words = array();
        $string .= implode(' ', $words);
      } //end if

      return strtoupper($string);
    } //end
  } //end fn

}
