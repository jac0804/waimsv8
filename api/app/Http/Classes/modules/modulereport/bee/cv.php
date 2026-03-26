<?php

namespace App\Http\Classes\modules\modulereport\bee;

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

class cv
{

  private $modulename = "Cash/Check Voucher";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'position', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'VOUCHER - Without Logo', 'value' => '13', 'color' => 'blue'],
        ['label' => 'BHT Voucher', 'value' => '10', 'color' => 'blue'],
        ['label' => 'GBT Voucher', 'value' => '11', 'color' => 'blue'],
        ['label' => 'CCSC Voucher', 'value' => '12', 'color' => 'blue'],
        ['label' => 'CHECK', 'value' => '1', 'color' => 'blue'],
        ['label' => 'BPI CHECK', 'value' => '3', 'color' => 'blue'],
        ['label' => 'Eastwest BHT CHECK', 'value' => '4', 'color' => 'blue'],
        ['label' => 'Eastwest GBT CHECK', 'value' => '17', 'color' => 'blue'],
        ['label' => 'BDO CHECK', 'value' => '5', 'color' => 'blue'],
        ['label' => 'Security Bank CHECK', 'value' => '6', 'color' => 'blue'],
        ['label' => 'Robinsons Bank CHECK', 'value' => '7', 'color' => 'blue'],
        ['label' => 'UnionBank CHECK', 'value' => '8', 'color' => 'blue'],
        ['label' => 'May Bank CHECK', 'value' => '14', 'color' => 'blue'],
        ['label' => 'BPI St. Francis CHECK', 'value' => '15', 'color' => 'blue'],
        ['label' => 'Metrobank CHECK', 'value' => '16', 'color' => 'blue'],
        ['label' => 'Back Check Printing ', 'value' => '9', 'color' => 'blue'],
        ['label' => 'BIR Form 2307', 'value' => '2', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportplotting($config, $data)
  {
    switch ($config['params']['dataparams']['reporttype']) {
      case '0': // VOUCHER

        $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
        break;
      case '1':
        $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT2($data, $config);
        break;
      case '2':
        $str = $this->PDF_CV_WTAXREPORT($data, $config);
        break;
      case '3':
        $str = $this->PDF_BPI_CHECK_LAYOUT($data, $config);
        break;

      case '4':
        $str = $this->PDF_EASTWEST_CHECK_LAYOUT($data, $config);
        break;
      case '5':
        $str = $this->PDF_BDO_CHECK_LAYOUT($data, $config);
        break;
      case '6':
        $str = $this->PDF_SECURITY_CHECK_LAYOUT($data, $config);
        break;
      case '7':
        $str = $this->PDF_ROBINSONS_CHECK_LAYOUT($data, $config);
        break;
      case '8':
        $str = $this->PDF_UNIONBANK_CHECK_LAYOUT($data, $config);
        break;
      case '9':
        $str = $this->PDF_BACK_CHECK_LAYOUT($data, $config);
        break;
      case '14':
        $str = $this->PDF_MAYBANK_CHECK_LAYOUT($data, $config);
        break;
      case '15':
        $str = $this->PDF_STFRANCIS_CHECK_LAYOUT($data, $config);
        break;
      case '16':
        $str = $this->PDF_METROBANK_CHECK_LAYOUT($data, $config);
        break;
      case '10':
      case '11':
      case '12':
      case '13':
        $str = $this->PDF_BHT_VOUCHER_LAYOUT($data, $config);
        break;
      case '17':
        $str = $this->PDF_EASTWEST_CHECK_LAYOUT($data, $config, true);
        break;
    }

    return $str;
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        'Maricon Rallos' as prepared,
        'Charles Ley' as approved,
        '' as received,
        'Chennie Marcello' as checked,
        '' as payor,
        '' as position,
        '' as tin,
        '0' as reporttype
        "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case 2:
        $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, pm.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,
        ifnull(pm.tin,'') as tin
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        left join projectmasterfile as pm on pm.line = head.projectid
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, pm.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname, 
        ifnull(pm.tin,'') as tin
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join projectmasterfile as pm on pm.line = head.projectid
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc, tbl.postdate";
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
            $arrs[$value['ewtcode']]['month'] = date("m", strtotime($value['postdate']));
            $arrs[$value['ewtcode']]['ewtdesc'] = $value['ewtdesc'];
          } else {
            array_push($arrss, $arrs);
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = date("m", strtotime($value['postdate']));
            $arrs[$value['ewtcode']]['ewtdesc'] = $value['ewtdesc'];
          }

          $ewt = $value['ewtcode'];
        } //end for each

        array_push($arrss, $arrs);
        $keyers = '';
        $finalarrs = [];
        foreach ($arrss as $key => $value) {
          foreach ($value as $keyx => $y) {
            $finalarrs[$keyx][$y['month']]['month'] = $y['month'];
            $finalarrs[$keyx][$y['month']]['oamt'] = $y['oamt'];
            $finalarrs[$keyx][$y['month']]['xamt'] = $y['xamt'];
            $finalarrs[$keyx][$y['month']]['ewtdesc'] = $y['ewtdesc'];
          }
        } //end for each
        if (empty($result1)) {
          $returnarr[0]['payee'] = '';
          $returnarr[0]['tin'] = '';
          $returnarr[0]['payortin'] = '';
          $returnarr[0]['address'] = '';
          $returnarr[0]['month'] = '';
          $returnarr[0]['yr'] = '';
          $returnarr[0]['payorcompname'] = '';
          $returnarr[0]['payoraddress'] = '';
          $returnarr[0]['payorzipcode'] = '';
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
        $query = "select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%m/%d/%Y')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,client.rem as clientrem,bus.code as buscode, bus.name as busname,
        concat(ccm.code,'~',ccm.name) as costcode
        from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        left join projectmasterfile as bus on bus.line=head.projectid
        left join costcode_masterfile as ccm on ccm.line = head.costcodeid
        where head.doc='cv' and head.trno ='$trno'
        union all
        select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%m/%d/%Y')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,client.rem as clientrem,bus.code as buscode, bus.name as busname,
        concat(ccm.code,'~',ccm.name) as costcode
        from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
        left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        left join projectmasterfile as bus on bus.line=head.projectid
        left join costcode_masterfile as ccm on ccm.line = head.costcodeid
        where head.doc='cv' and head.trno ='$trno'";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        break;
    } // end switch

    return $result;
  }

  //without logo
  public function PDF_default_header($params, $data)
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


    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 15, 'Bus. Unit ', '', 'L', false, 0, 550, 10);
    PDF::MultiCell(300, 15, ': ' . $data[0]['buscode'], '', 'L', false, 1, 620, 10);
    PDF::MultiCell(200, 15, 'Cost Code ', '', 'L', false, 0, 550, 30);
    PDF::MultiCell(300, 15, ': ' . $data[0]['costcode'], '', 'L', false, 1, 620, 30);

    $trno = $params['params']['dataid'];

    $fcbq = "select sum(ifnull(value,0)) as value from (select sum(detail.cr-detail.db) as value from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and  detail.trno = ?
    union all
    select sum(detail.cr-detail.db) as value from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and detail.trno = ?) as x
    ";

    $fcb = $this->coreFunctions->datareader($fcbq, [$trno, $trno]);
    if ($fcb >= 200000) {
      $r1 = 'checked="checked"';
    } else {
      $r1 = '';
    }


    $html = '
      <form  action="http://localhost/printvars.php" enctype="multipart/form-data">
      <input type="checkbox" name="agree1" value="1" readonly="true"  ' . $r1 . '/> <label for="agree1"> FCB 200</label>
      </form>';
    PDF::MultiCell(200, 20, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(275, 20, '', '', 'L', false, 0, '', '');
    PDF::writeHTML($html, true, 0, true, 0, 'C');


    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(300, 5, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 5, "Docno #: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 5, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(300, 5, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 5, "", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 5, '', 'T', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, 'PAYEE : ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(425, 5, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(85, 5, 'DATE:', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 5, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(425, 5, '', 'T', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(85, 5, '', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 5, '', 'T', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, 'ADDRESS : ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(425, 5, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(85, 5, 'REFERENCE # :', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 5, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(425, 5, '', 'T', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(85, 5, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 5, '', 'T', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, 'NOTES : ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 5, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(70, 5, '', '', 'L', false, 0);
    PDF::MultiCell(630, 5, '', 'T', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(70, 0, 'ACCT #', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'ACCOUNT NAME', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'CHECK DETAILS', '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'DATE', '', 'C', false, 0);
    PDF::MultiCell(85, 0, 'DEBIT', '', 'R', false, 0);
    PDF::MultiCell(85, 0, 'CREDIT', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'C', false, 0);
    PDF::MultiCell(110, 0, 'REMARKS', '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_default_header($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $acno =  $data[$i]['acno'];
        $acnonamedescs = $data[$i]['acnoname'];
        $checkno = $data[$i]['checkno'];
        $pdate = $data[$i]['pdate'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $debit = $debit < 0 ? '-' : $debit;

        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $credit = $credit < 0 ? '-' : $credit;
        $drem = $data[$i]['drem'];

        $arr_acno = $this->reporter->fixcolumn([$acno], '15', 0);
        $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '25', 0);
        $arr_checkno = $this->reporter->fixcolumn([$checkno], '15', 0);
        $arr_pdate = $this->reporter->fixcolumn([$pdate], '13', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
        $arr_drem = $this->reporter->fixcolumn([$drem], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnonamedescs, $arr_checkno, $arr_pdate, $arr_debit, $arr_credit, $arr_drem]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(180, 15, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_checkno[$r]) ? $arr_checkno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(75, 15, ' ' . (isset($arr_pdate[$r]) ? $arr_pdate[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 15, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_drem[$r]) ? $arr_drem[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];


        if (PDF::getY() > 900) {
          $this->PDF_default_header($params, $data);
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(705, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(425, 5, 'GRAND TOTAL : ', '', 'R', false, 0);
    PDF::MultiCell(85, 5, number_format($totaldb, $decimal), '', 'R', false, 0);
    PDF::MultiCell(85, 5, number_format($totalcr, $decimal), '', 'R', false);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 12);


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  //end without logo

  //BHT VOUCHER
  public function PDF_BHT_VOUCHER_HEADER($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    switch ($params['params']['dataparams']['reporttype']) {
      case '10':
      case '13':
        $qry2 = "select code, name, address, tin from projectmasterfile where code='BHT'";
        break;
      case '11':
        $qry2 = "select code, name, address, tin from projectmasterfile where code='GBT'";
        break;
      case '12':
        $qry2 = "select code, name, address, tin from projectmasterfile where code='CCSC'";
        break;
    }

    $headerdata = $this->coreFunctions->opentable($qry);
    $headerdata2 = $this->coreFunctions->opentable($qry2);
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


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(200, 20, 'Bus. Unit ', '', 'L', false, 0, 570, 49);
    PDF::MultiCell(300, 20, ': ' . $data[0]['buscode'], '', 'L', false, 1, 635, 49);
    PDF::MultiCell(200, 20, 'Cost Code :', '', 'L', false, 0, 570, 66);

    $arrcostcode = $this->reporter->fixcolumn([$data[0]['costcode']], '30', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arrcostcode]);

    $ccy = 0;
    for ($cc = 0; $cc < $maxrow; $cc++) {
      $ccy = $cc == 0 ? 66 : $ccy += 20;
      PDF::MultiCell(300, 20, isset($arrcostcode[$cc]) ? $arrcostcode[$cc] : '', '', 'L', false, 1, 635, $ccy);
    }

    $trno = $params['params']['dataid'];

    $fcbq = "select sum(ifnull(value,0)) as value from (select sum(detail.cr-detail.db) as value from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and  detail.trno = ?
    union all
    select sum(detail.cr-detail.db) as value from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and detail.trno = ?) as x
    ";

    $fcb = $this->coreFunctions->datareader($fcbq, [$trno, $trno]);
    if ($fcb >= 200000) {
      $r1 = 'checked="checked"';
    } else {
      $r1 = '';
    }


    $html = '
      <form  action="http://localhost/printvars.php" enctype="multipart/form-data">
      <input type="checkbox" name="agree1" value="1" readonly="true"  ' . $r1 . '/> <label for="agree1"> FCB 200</label>
      </form>';

    PDF::SetFont($fontbold, '', 10);
    switch ($params['params']['dataparams']['reporttype']) {
      case '10':
        $path = 'public/images/bee/b1.png';
        PDF::Image($path, '38', '50', 180, 60, '', '', 'M', false, 300, 'M', false, false, 0, true, false, false);
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->name), '', 'L', false, 1, 240, 50); //40
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L', false, 1, 240, 67); //57
        break;
      case '11':
        $path = 'public/images/bee/b2.png';
        PDF::Image($path, '30', '40', 200, 60, '', '', 'M', false, 300, 'M', false, false, 0, true, false, false);
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->name), '', 'L', false, 1, 210, 50); //40
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L', false, 1, 210, 67); //57

        break;
      case '12':
        $path = 'public/images/bee/b3.jpg';
        PDF::Image($path, '33', '38', 200, 65, '', '', 'M', false, 300, 'M', false, false, 0, true, false, false);
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->name), '', 'L', false, 1, 180, 50); //40
        PDF::MultiCell(700, 0, strtoupper($headerdata2[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L', false, 1, 180, 67); //57
        break;
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(530, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "CV#: ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, 'PAYEE : ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(455, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, 'DATE:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['kdate']) ? $data[0]['kdate'] : ''), 'B', 'L', false, 1);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n");

    $trno = $params['params']['dataid'];
    $qry = "select detail.checkno, coa.acnoname from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and  detail.trno = ?
    union all
    select detail.checkno, coa.acnoname from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and detail.trno = ?";

    $datax = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 5, 'ADDRESS : ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(455, 5, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 5, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 5, 'CHECK #:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 5, (isset($datax[0]->checkno) ? $datax[0]->checkno : ''), 'B', 'L', false, 1);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n\n\n");

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(360, 20, ' PARTICULARS', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, ' DATE', 'TLRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(200, 20, ' AMOUNT ', 'TLRB', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
  }

  public function PDF_BHT_VOUCHER_LAYOUT($data, $params)
  {
    $trno = $params['params']['dataid'];

    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_BHT_VOUCHER_HEADER($params, $data);

    $countarr = 0;

    $fcbq = "select sum(ifnull(value,0)) as value from (select sum(detail.cr) as value from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and  detail.trno = ?
    union all
    select sum(detail.cr) as value from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where left(coa.alias,2) = 'cb' and detail.trno = ?) as x
    ";

    $fcb = $this->coreFunctions->datareader($fcbq, [$trno, $trno]);

    if (!empty($data)) {

      $maxrow = 3;
      $rem = $data[0]['rem'];
      $kdate = $data[0]['kdate'];

      $arr_acnonamedescs = $this->reporter->fixcolumn([$rem], '50', 0);
      $arr_pdate = $this->reporter->fixcolumn([$kdate], '13', 0);
      $arr_amtx = $this->reporter->fixcolumn([number_format($fcb, $decimalcurr)], '15', 0);

      $maxrow += $this->othersClass->getmaxcolumn([$arr_acnonamedescs,  $arr_pdate, $arr_amtx]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($fontbold, '', '12');
        PDF::MultiCell(360, 0, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::SetFont($font, '', '12');
        PDF::MultiCell(160, 0, ' ' . (isset($arr_pdate[$r]) ? $arr_pdate[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(200, 0, ' ' . (isset($arr_amtx[$r]) ? $arr_amtx[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }

      if (PDF::getY() > 900) {
        $this->PDF_BHT_VOUCHER_HEADER($params, $data);
      }
    }


    PDF::SetFont($fontbold, '', '12');
    PDF::MultiCell(360, 20, ' TOTAL', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, ' ', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(200, 20, ' ' . number_format($fcb, $decimalcurr) . ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 12);


    PDF::MultiCell(188, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(78, 0, '', '', 'L', false, 0);
    PDF::MultiCell(188, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(78, 0, '', '', 'L', false, 0);
    PDF::MultiCell(188, 0, 'Approved By: ', '', 'L', false, 1, 570);


    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(188, 0, $params['params']['dataparams']['prepared'], 'T', 'L', false, 0);
    PDF::MultiCell(78, 0, '', '', 'L', false, 0);
    PDF::MultiCell(188, 0, $params['params']['dataparams']['checked'], 'T', 'L', false, 0);
    PDF::MultiCell(78, 0, '', '', 'L', false, 0);
    PDF::MultiCell(188, 0, $params['params']['dataparams']['approved'], 'T', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, '', '', 'L', false, 0);
    PDF::MultiCell(188, 0, "Payment Received by: ", '', 'L', false);
    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(266, 0, "", '', 'L', false, 0);
    PDF::MultiCell(188, 0, "(print name, sign and date)", 'T');


    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


  public function PDF_DEFAULT_CCVOUCHER_LAYOUT2($data, $params)
  {
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $cc = '';
    $cdate = '';

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');

    PDF::SetMargins(40, 40);

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' and left(coa.alias,2) = 'CB' 
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {
      PDF::AddPage('p', [800, 1000]);
      $cc = $data2[$i]['cr'];
      $cdate = $data2[$i]['postdate'];

      PDF::SetFont($font, '', 10);

      PDF::MultiCell(50, 5, '', '', 'C', false, 0);
      PDF::MultiCell(170, 5, '', '', 'C', false, 0);
      PDF::MultiCell(420, 5, ('' . isset($cdate) ? $cdate : ''), '', 'C', false, 0);

      PDF::MultiCell(120, 5, '', '', 'C', false);
      PDF::MultiCell(120, 5, '', '', 'C', false, 0);
      PDF::MultiCell(250, 5, $data[0]['clientname'], '', 'L', false, 0);
      PDF::MultiCell(220, 5, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'C', false, 0);
      PDF::MultiCell(210, 5, '', '', 'C', false);

      $dd = number_format((float)$cc, 2, '.', '');

      PDF::MultiCell(120, 5, '', '', 'C', false, 0);
      PDF::setFontSpacing(2);
      PDF::MultiCell(320, 5, $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false);

      $this->reporter->linecounter = 30;
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_CV_WTAXREPORT($data, $params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::MultiCell(140, 10, 'Republic of the Philippines' . "\n" . 'Department of Finance' . "\n" . 'Bureau of Internal Revenue', '', 'C', false, 0);
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

    $mmddyy = $this->coreFunctions->opentable("
          select min(mindate) as mindate, max(maxdate) as maxdate from (
            select min(postdate) as mindate, max(postdate) as maxdate from ladetail where trno = $trno and isewt = 1 or isvewt = 1
            union all
            select min(postdate) as mindate, max(postdate) as maxdate from gldetail where trno = $trno and isewt = 1 or isvewt = 1
      ) as x
      ");

    $d1 = "01";
    $m1 = date("m", strtotime($mmddyy[0]->mindate));
    $y1 = date("y", strtotime($mmddyy[0]->mindate));

    $d2 = date("t", strtotime($mmddyy[0]->maxdate));
    $m2 = date("m", strtotime($mmddyy[0]->maxdate));
    $y2 = date("y", strtotime($mmddyy[0]->maxdate));

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
    PDF::MultiCell(25, 15, $m1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $d1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y1, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(25, 15, $m2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $d2, 'LTB', 'C', false, 0);
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
    PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'L', false, 0);
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
    PDF::MultiCell(630, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'LTRB', 'C', false, 0);
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
    PDF::MultiCell(520, 18, (isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), 'LTBR', 'L', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 15
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
    PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

    //Row 16
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), 'LTRB', 'L', false, 0);
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
    PDF::MultiCell(630, 18, (isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), 'LTRB', 'C', false, 0);
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
    PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LR', 'C', false, 0);
    PDF::MultiCell(80, 20, 'ATC', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, 'Total', 'LTR', 'C', false, 0);
    PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTR', 'C', false, 1);

    //Row 16
    PDF::MultiCell(780, 20, '', 'T', '', false);

    //Row 17
    PDF::MultiCell(780, 10, '', 'T', '', false, 1, 10, 500);

    PDF::MultiCell(200, 10, '', 'LR', '', false, 0, 10, 500);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0, 210);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 290);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 385);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 480);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 575);

    PDF::MultiCell(120, 10, '', 'LR', 'R', false, 1, 670);

    //Row 18 ---atc1

    $total = 0;
    $a = -1;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;


    $ewtcodex = "";
    $nx = 0;
    $cx = "";
    $totalxamt = $totalc = $ctotalxamt = 0;

    foreach ($data['detail'] as $key => $xdata) {
      $cx = count($xdata);
      if ($cx == 1) {
        $xdata[13]["month"] = "";
        $xdata[13]["oamt"] = "";
        $xdata[13]["xamt"] = "";
        $xdata[13]["ewtdesc"] = "";
        $xdata[14]["month"] = "";
        $xdata[14]["oamt"] = "";
        $xdata[14]["xamt"] = "";
        $xdata[14]["ewtdesc"] = "";
      } else if ($cx == 2) {
        $xdata[13]["month"] = "";
        $xdata[13]["oamt"] = "";
        $xdata[13]["xamt"] = "";
        $xdata[13]["ewtdesc"] = "";
      }
      foreach ($xdata as $key2 => $value) {

        if ($ewtcodex != $key) {

          $ewt_height = PDF::GetStringHeight(200, $value['ewtdesc']);
          $key_height = PDF::GetStringHeight(80, $key);
          $max_height = max($ewt_height, $key_height);

          if ($max_height > 25) {
            $max_height = $max_height + 15;
          }
          PDF::MultiCell(200, $max_height, $value['ewtdesc'], 'LRB', '', false, 0);
          PDF::MultiCell(80, $max_height, $key, 'LRB', '', false, 0);
        }

        switch ($value['month']) {
          case '01':
          case '04':
          case '07':
          case '10':
            PDF::MultiCell(95, $max_height, number_format($value['oamt'], $decimalcurr), 'LRB', 'R', false, 0);

            $totalwtx1 += $value['oamt'];
            $nx = 1;
            break;
          case '02':
          case '05':
          case '08':
          case '11':
            PDF::MultiCell(95, $max_height, number_format($value['oamt'], $decimalcurr), 'LRB', 'R', false, 0);
            if ($cx == 1) {
              $totalwtx1 += $value['oamt'];
            } else if ($cx == 2) {
              if ($nx == 1) {
                $totalwtx2 +=  $value['oamt'];
              } else {
                $totalwtx1 +=  $value['oamt'];
              }
            } else {
              $totalwtx2 +=  $value['oamt'];
            }
            break;
          case '03':
          case '06':
          case '09':
          case '12':
            PDF::MultiCell(95, $max_height, number_format($value['oamt'], $decimalcurr), 'LRB', 'R', false, 0);
            if ($cx == 1) {
              $totalwtx1 += $value['oamt'];
            } else if ($cx == 2) {
              $totalwtx2 +=  $value['oamt'];
            } else {
              $totalwtx3 +=  $value['oamt'];
            }
            break;
          default:
            PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
            break;
        }

        $ewtcodex = $key;
        $totalc += $value['oamt'];
        $ctotalxamt += $value['xamt'];
        $totalxamt += $value['xamt'];
        $totalwtx += $value['oamt'];
      }
      PDF::MultiCell(95, $max_height,  number_format($totalc, $decimalcurr), 'LRB', 'R', false, 0);
      PDF::MultiCell(120, $max_height, number_format($ctotalxamt, $decimalcurr), 'LRB', 'R', false);

      $totalc = 0;
      $ctotalxamt = 0;
      $nx = 0;
    }


    //Row 19 ----total
    $totaltax = 0;
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx1 != 0 ? number_format($totalwtx1, $decimalcurr) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx2 != 0 ? number_format($totalwtx2, $decimalcurr) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx3 != 0 ? number_format($totalwtx3, $decimalcurr) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx != 0 ? number_format($totalwtx, $decimalcurr) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(120, 10, number_format($totalxamt, $decimalcurr), 'LR', 'R', false);
    PDF::SetFont($font, '', 9);

    //Row 20 ---space for total 
    PDF::MultiCell(200, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

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
    PDF::MultiCell(120, 20, number_format($totalxamt, $decimalcurr), 'TLR', 'R', false);

    //Row 26
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.', 'TLR', 'C', false);

    //Row 27
    PDF::MultiCell(10, 30, '', 'LT', '', false, 0);
    PDF::MultiCell(395, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(175, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(170, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'TR', '', false);

    //Row 28

    if ($params['params']['dataparams']['payor'] == '') {
      $payor = $data['head'][0]['payorcompname'] . ' / ';
    } else {
      $payor = $params['params']['dataparams']['payor'] . ' / ';
    }

    if ($params['params']['dataparams']['tin'] == '') {
      $tin = $data['head'][0]['payortin'];
    } else {
      $tin = $params['params']['dataparams']['tin'];
    }

    if ($params['params']['dataparams']['position'] == '') {
      $position = '';
    } else {
      $position = ' / ' . $params['params']['dataparams']['position'];
    }


    PDF::MultiCell(780, 30, ucwords($payor) . $tin . ucwords($position), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
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
    PDF::MultiCell(780, 30, '', 'LTRB', '', false);

    //Row 32
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

  public function PDF_MAYBANK_CHECK_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $payor = '';
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }


    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-8px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(10);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '590', '12');
      PDF::setFontSpacing(11);
      PDF::MultiCell(60, 0, date('d', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '635', '12');
      PDF::setFontSpacing(12);
      PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, '685', '12');
      PDF::MultiCell(30, 0, '', '', 'C');

      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::setFontSpacing(0);
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);

      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 125, 48);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 48);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 26);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false, 1, 590, 43);
      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 105, 77);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 207, 110);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 125);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 110);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_STFRANCIS_CHECK_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $payor = '';
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";


    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }


    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-8px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 603, 14);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);

      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::setFontSpacing(0);
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);

      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 125, 50);
      } else if ($cpayor >= 39 && $cpayor <= 47) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 50);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 28);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false, 1, 590, 45);
      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 110, 79);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 207, 115);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 130);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 115);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_BPI_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $payor = '';
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";


    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }


    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-8px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(10);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '590', '14');
      PDF::setFontSpacing(11);
      PDF::MultiCell(60, 0, date('d', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '635', '14');
      PDF::setFontSpacing(12);
      PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, '685', '14');
      PDF::MultiCell(30, 0, '', '', 'C');

      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::setFontSpacing(0);
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);

      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 130, 45);
      } else if ($cpayor >= 39 && $cpayor <= 47) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 45);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 23);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal) : ''), '', 'L', false, 1, 570, 45);
      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 110, 77);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 207, 105);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 120);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 105);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_EASTWEST_CHECK_LAYOUT($data, $filters, $boxdate = false)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      if ($boxdate) {
        PDF::setFontSpacing(9);
        PDF::SetFont($fontcambria, '', 11);
        PDF::MultiCell(570, 0, date('m', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '588', 15);
        PDF::setFontSpacing(11);
        PDF::MultiCell(60, 0, date('d', strtotime($data2[$i]['postdate'])) . '/', '', 'L', false, 0, '633', 15);
        PDF::setFontSpacing(12);
        PDF::MultiCell(90, 0, date('Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, '683', 15);
        PDF::MultiCell(30, 0, '', '', 'C');
        PDF::MultiCell(720, 5, "\n", '', 'L', false);
      } else {
        PDF::setFontSpacing(1);
        PDF::SetFont($fontcambria, '', 11);
        PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 598, 15);
        PDF::MultiCell(720, 5, "\n", '', 'L', false);
      }


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);

      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 130, 45);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 45);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 30);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 585, 43);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 110, 75);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 265, 105);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 265, 120);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 265, 105);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_BDO_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 600, 17);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(420, 0,  $payor, '', 'L', false, 0, 120, 48);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(420, 0, $payor, '', 'L', false, 0, 120, 48);
      } else if ($cpayor >= 44 && $cpayor <= 50) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(420, 0, $payor, '', 'L', false, 0, 120, 48);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(420, 0, $payor, '', 'L', false, 0, 120, 31);
      }

      PDF::MultiCell(100, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 590, 45);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 95, 80);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 175, 115);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'] . '--', '', 'L', false, 1, 175, 141);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 175, 115);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_SECURITY_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 600, 18);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 120, 48);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 120, 48);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 120, 34);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 605, 45);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 95, 78);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 240, 110);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 240, 125);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 240, 110);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_ROBINSONS_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 600, 19);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 125, 52);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 52);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 125, 37);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 590, 45);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 90, 83);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 350, 120);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 350, 135);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 350, 120);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_UNIONBANK_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 605, 15);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);
      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 130, 47);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 47);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 32);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 595, 43);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 95, 78);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 207, 110);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 125);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 207, 110);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_METROBANK_CHECK_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fontcambria = "";
    $fontcambriab = "";
    $fontcalibrii = "";
    $fontarialnb = "";

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/cambria.ttf')) {
      $fontcambria = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambria.ttf');
      $fontcambriab = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/cambriab.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/calibrii.ttf')) {
      $fontcalibrii = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibrii.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $fontarialnb = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate
    UNION ALL
    select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
    from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = " . $data[0]['trno'] . "
    and left(coa.alias,2) = 'CB'  and detail.cr>0
    group by 
    detail.checkno,coa.acno,
    detail.cr, detail.postdate

    ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {

      $cc = $data2[$i]['cr'];
      $month = "<span style='letter-spacing:10px; margin-right: 15px;'>" . date('m', strtotime($data2[$i]['postdate'])) . "</span>";
      $day = "<span style='letter-spacing:10px; margin-right: 10px;'>" . date('d', strtotime($data2[$i]['postdate'])) . "</span>";
      $year = "<span style='letter-spacing:10px; margin-right:-7px'>" . date('Y', strtotime($data2[$i]['postdate'])) . "</span>";

      PDF::setFontSpacing(1);
      PDF::SetFont($fontcambria, '', 11);
      PDF::MultiCell(100, 0, date('m/d/Y', strtotime($data2[$i]['postdate'])), '', 'L', false, 0, 598, 17);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);

      PDF::MultiCell(720, 5, "\n", '', 'L', false);


      if ($filters['params']['dataparams']['payor'] != '') {
        $payor = mb_strtoupper($filters['params']['dataparams']['payor'], 'UTF-8');
      } else {
        $payor = mb_strtoupper($data[0]['clientname'], 'UTF-8');
      }

      $cpayor = strlen($payor);
      PDF::setFontSpacing(2);

      if ($cpayor <= 38) {
        PDF::SetFont($fontcambriab, '', 11);
        PDF::MultiCell(370, 0,  $payor, '', 'L', false, 0, 130, 50);
      } else if ($cpayor >= 39 && $cpayor <= 43) {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 50);
      } else {
        PDF::SetFont($fontcambriab, '', 10);
        PDF::MultiCell(370, 0, $payor, '', 'L', false, 0, 130, 35);
      }

      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::SetFont($fontarialnb, '', 12);
      PDF::MultiCell(220, 0, (isset($cc) ? number_format($cc, $decimal)  : ''), '', 'L', false, 1, 605, 45);

      PDF::setFontSpacing(1);
      PDF::MultiCell(720, 5, "\n", '', 'L', false);
      PDF::SetFont($fontcalibrii, '', 12);
      $dd = number_format((float)$cc, 2, '.', '');
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(690, 0,  $this->reporter->ftNumberToWordsConverter($dd) . ' ONLY', '', 'L', false, 0, 110, 80);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(300, 0, $data[0]['clientrem'], '', 'L', false, 1, 265, 115);
      if ($data[0]['clientrem'] != '') {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 265, 130);
      } else {
        PDF::MultiCell(300, 0, $data[0]['rem'], '', 'L', false, 1, 265, 115);
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_BACK_CHECK_LAYOUT($data, $filters)
  {
    $font = "";
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(740, 0, $data[0]['clientname'], '', 'C', false, 1, 30, 53);
    PDF::MultiCell(740, 0, $data[0]['clientrem'], '', 'C', false, 1, 30, 68);
    if ($data[0]['clientrem'] != '') {
      PDF::MultiCell(740, 0, $data[0]['rem'], '', 'C', false, 1, 30, 83);
    } else {
      PDF::MultiCell(740, 0, $data[0]['rem'], '', 'C', false, 1, 30, 68);
    }
    // }
    return PDF::Output($this->modulename . '.pdf', 'S');
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
