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

class cv
{

  private $modulename = "Cash/Check Voucher";
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
    $fields = ['radioprint', 'radioreporttype', 'requested', 'checked', 'approved', 'prepared', 'checked2', 'noted', 'approved2', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
    data_set(
      $col1,
      'radioreporttype.options',
      [
        
        ['label' => 'CPV', 'value' => '6', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportplotting($config, $data)
  {
    if ($config['params']['dataparams']['print'] == "default") {
    
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
     
      switch ($config['params']['dataparams']['reporttype']) {
         
        case '6': // CPV
          $str = $this->PDF_CPV($data, $config);
          break;
      }
    }

    return $str;
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '' as requested,
        '' as checked,
        '' as approved,
        '' as prepared,
        '' as checked2,
        '' as noted,
        '' as approved2,
        '6' as reporttype
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
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
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
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
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
        where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc";
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
        $query = "select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,ifnull(dept.clientname,'') as deptname,client.tel,client.addr,ifnull(right(detaildept.client,4),'') as costcenter,
        substring_index(substring_index(coa.acno,'-',1),'\\\',-1) as gl,substring_index(coa.acno,'-',-1) as sl
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        left join client as dept on dept.clientid=head.deptid
        left join client as detaildept on detaildept.clientid=detail.deptid
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        where head.doc='cv' and head.trno ='$trno'
        union all
        select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
        DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
        date(head.dateid) as dateid, 
        date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
        client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
        coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
        detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno,ifnull(dept.clientname,'') as deptname,client.tel,client.addr,ifnull(right(detaildept.client,4),'') as costcenter,
        substring_index(substring_index(coa.acno,'-',1),'\\\',-1) as gl,substring_index(coa.acno,'-',-1) as sl
        from glhead as head 
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join glhead as head2 on head2.trno = detail.refx
        left join cntnum on cntnum.trno=head.trno
        left join client as dept on dept.clientid=head.deptid
        left join client as detaildept on detaildept.clientid=detail.deptid
        LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
        where head.doc='cv' and head.trno ='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        break;
    } // end switch
    return $result;
  }


  public function PDF_CPV_header($params, $data)
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(720, 0, "", '', 'C', false, 1);


    PDF::SetFont($fontbold, '', $fontsize + 5);
    PDF::MultiCell(220, 0, "", '', 'C', false, 0);
    PDF::MultiCell(280, 0, "CHECK PAYMENT VOUCHER (CPV)", 'B', 'C', false, 0);
    PDF::MultiCell(220, 0, "", '', 'C', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, "", 'LT', 'L', false, 0);
    PDF::MultiCell(150, 0, "", 'LT', 'L', false, 0);
    PDF::MultiCell(200, 0, "", 'LTR', 'L', false, 1);


    $date = date_create($data[0]['dateid']);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 0, "Requesting Department : ", 'L', 'L', false, 0);
    PDF::MultiCell(230, 0, $data[0]['deptname'], '', 'L', false, 0);
    PDF::MultiCell(40, 0, "Date : ", 'L', 'L', false, 0);
    PDF::MultiCell(110, 0, $date, '', 'L', false, 0);
    PDF::MultiCell(70, 0, "Request No.", 'L', 'L', false, 0);
    $reqno = '';
    for ($i = 0; $i < count($data); $i++) {
      if ($data[$i]['dyourref'] != '') {
        if ($reqno == '') {
          $reqno .= $data[$i]['dyourref'];
        } else {
          $reqno .= ', ' . $data[$i]['dyourref'];
        }
      }
    }
    PDF::MultiCell(130, 0, (isset($reqno) ? $reqno : ''), 'R', 'L', false, 1);

    $descloop = 0;
    $border = 'TL';
    $desc = '';
    if (!empty($data)) {

      $maxrow = 1;

      $rem =  $data[0]['rem'];

      $arr_rem = $this->reporter->fixcolumn([$rem], '97', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_rem]);

      for ($r = 0; $r < $maxrow; $r++) {

        if ($descloop != 0) {
          $border = 'L';
        }
        if ($descloop == 2) {
          $desc = 'Description';
        } else {
          $desc = '';
        }
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 15, $desc, $border, 'L', false, 0);
        PDF::MultiCell(600, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'TLR', 'L', false, 1);


        $descloop += 1;
      }
      if ($descloop < 6) {
        for ($c = $descloop; $c < 6; $c++) {
          if ($descloop != 0) {
            $border = 'L';
          }
          if ($descloop == 2) {
            $desc = 'Description';
          } else {
            $desc = '';
          }
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(120, 15, $desc, $border, 'L', false, 0);
          PDF::MultiCell(600, 15, '', 'TLR', 'L', false, 1);

          $descloop += 1;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 15, 'Payee', 'TL', 'L', false, 0);
    PDF::MultiCell(400, 15, $data[0]['clientname'], 'TL', 'L', false, 0);
    PDF::MultiCell(40, 15, 'Tel No.', 'TL', 'L', false, 0);
    PDF::MultiCell(160, 15, $data[0]['tel'], 'TR', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 15, 'Payee` Address', 'TL', 'L', false, 0);
    PDF::MultiCell(600, 15, $data[0]['addr'], 'TLR', 'L', false, 1);

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 15, 'Amount', 'TL', 'L', false, 0);
    PDF::MultiCell(250, 15, number_format($totaldb, 2), 'TL', 'L', false, 0);
    PDF::MultiCell(350, 15, 'Terms of Payment:', 'TLR', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize - 8);
    PDF::MultiCell(120, 0, '', 'TL', 'L', false, 0);
    PDF::MultiCell(600, 0, '', 'TLR', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, 'Attachments', 'L', 'L', false, 0);

    PDF::MultiCell(10, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(85, 0, ' Invoice/O.R.', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);

    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(85, 0, ' Purchase Slip', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);

    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(85, 0, ' Job Order', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);

    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(85, 0, ' Payroll', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);

    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(85, 0, ' Others', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize - 8);
    PDF::MultiCell(120, 0, '', 'BL', 'L', false, 0);
    PDF::MultiCell(600, 0, '', 'BLR', 'L', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 0, 'Requested By : ', 'TL', 'L', false, 0);
    PDF::MultiCell(280, 0, 'Checked By : ', 'TL', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Approved By : ', 'TLR', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['requested'], 'L', 'L', false, 0);
    PDF::MultiCell(280, 0, $params['params']['dataparams']['checked'], 'L', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'LR', 'L', false, 1);


    PDF::SetFont($fontbold, '', $fontsize + 2);
    PDF::MultiCell(720, 0, 'Finance Department', 'TLR', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Cost', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, 'Code No.', 'TL', 'C', false, 0);
    PDF::MultiCell(280, 0, '', 'TL', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Amount ( P )', 'TLR', 'C', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Center', 'TL', 'C', false, 0);
    PDF::MultiCell(70, 0, 'GL', 'TL', 'C', false, 0);
    PDF::MultiCell(70, 0, 'SL', 'TL', 'C', false, 0);
    PDF::MultiCell(280, 0, 'Account Title', 'TL', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Debit', 'TL', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Credit', 'TLR', 'C', false, 1);
  }

  public function PDF_CPV($data, $params)
  {
    $trno = $params['params']['dataid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
 
    $font = "";
    $fontbold = "";
    $fontitalic = "";
    $fontitalicbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
      $fontitalic = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICI.TTF');
      $fontitalicbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICBI.TTF');
    }
    $this->PDF_CPV_header($params, $data);

    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $acnonamedescs = $data[$i]['acnoname'];
        $costcenter = $data[$i]['costcenter'];
        $gl = $data[$i]['gl'];
        $sl = $data[$i]['sl'];

        $debit = number_format($data[$i]['db'], $decimalcurr);
        $debit = $debit < 0 ? '-' : $debit;

        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $credit = $credit < 0 ? '-' : $credit;
      
        $arr_costcenter = $this->reporter->fixcolumn([$costcenter], '30', 0);
        $arr_acnonamedescs = $this->reporter->fixcolumn([$acnonamedescs], '40', 0);
        $arr_gl = $this->reporter->fixcolumn([$gl], '10', 0);
        $arr_sl = $this->reporter->fixcolumn([$sl], '10', 0);
  
        $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
      
        $maxrow = $this->othersClass->getmaxcolumn([$arr_costcenter, $arr_acnonamedescs, $arr_gl, $arr_sl, $arr_debit, $arr_credit]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_costcenter[$r]) ? $arr_costcenter[$r] : ''), 'TL', 'C', false, 0);
          PDF::MultiCell(70, 0, ' ' . (isset($arr_gl[$r]) ? $arr_gl[$r] : ''), 'TL', 'C', false, 0);
          PDF::MultiCell(70, 0, ' ' . (isset($arr_sl[$r]) ? $arr_sl[$r] : ''), 'TL', 'C', false, 0);
          PDF::MultiCell(280, 0, ' ' . (isset($arr_acnonamedescs[$r]) ? $arr_acnonamedescs[$r] : ''), 'TL', 'L', false, 0);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), 'TL', 'C', false, 0);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), 'TLR', 'C', false, 1);
          $countarr += 1;
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];


        if (PDF::getY() > 900) {
          $this->PDF_CPV_header($params, $data);
        }
      }
    }
    if ($countarr < 10) {
      for ($c = $countarr; $c < 10; $c++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, '', 'TL', 'C', false, 0);
        PDF::MultiCell(70, 0, '', 'TL', 'C', false, 0);
        PDF::MultiCell(70, 0, '', 'TL', 'C', false, 0);
        PDF::MultiCell(280, 0, '', 'TL', 'C', false, 0);
        PDF::MultiCell(100, 0, '', 'TL', 'C', false, 0);
        PDF::MultiCell(100, 0, '', 'TLR', 'C', false, 1);
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, 'Check No.', 'TL', 'C', false, 0);
    PDF::MultiCell(120, 0, 'Date of Check', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, 'Name of Bank', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, 'Amount (P)', 'TL', 'C', false, 0);
    PDF::MultiCell(200, 0, 'ACKNOWLEDGEMENT:', 'TLR', 'C', false, 1);

    $checkqry = "select date_format(date(detail.postdate),'%m-%d-%Y') as checkdate,
    substring_index(detail.checkno,',',-1) as checknum,
    substring_index(detail.checkno,',',1) as bank,detail.cr
    from ladetail as detail where trno='$trno'
    and detail.cr<>0
    union all
    select date_format(date(detail.postdate),'%m-%d-%Y') as checkdate,
    substring_index(detail.checkno,',',-1) as checknum,
    substring_index(detail.checkno,',',1) as bank,detail.cr
    from gldetail as detail where trno='$trno'
    and detail.cr<>0";

    $checkresult = json_decode(json_encode($this->coreFunctions->opentable($checkqry)), true);

    for ($i = 0; $i < count($checkresult); $i++) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(120, 0, $checkresult[$i]['checknum'], 'TL', 'C', false, 0);
      PDF::MultiCell(120, 0, $checkresult[$i]['checkdate'], 'TL', 'C', false, 0);
      PDF::MultiCell(140, 0, $checkresult[$i]['bank'], 'TL', 'C', false, 0);
      PDF::MultiCell(140, 0, number_format($checkresult[$i]['cr'], 2), 'TL', 'C', false, 0);
      PDF::MultiCell(200, 0, '', 'LR', 'C', false, 1);
    }



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(150, 0, 'Received the amount of', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', 'R', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 0, 'P', 'L', 'R', false, 0);
    PDF::MultiCell(140, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(30, 0, '', 'R', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, 'Prepared by:', 'TL', 'C', false, 0);
    PDF::MultiCell(120, 0, 'Checked by:', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, 'Noted by:', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, 'Approved by:', 'TL', 'C', false, 0);
    PDF::MultiCell(200, 0, '', 'LR', 'C', false, 1);

    $prepared = $params['params']['dataparams']['prepared'];
    $checked2 = $params['params']['dataparams']['checked2'];
    $noted = $params['params']['dataparams']['noted'];
    $approved = $params['params']['dataparams']['approved2'];

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, $prepared, 'L', 'C', false, 0);
    PDF::MultiCell(120, 0, $checked2, 'L', 'C', false, 0);
    PDF::MultiCell(140, 0, $noted, 'L', 'C', false, 0);
    PDF::MultiCell(140, 0, $approved, 'L', 'C', false, 0);
    PDF::MultiCell(200, 0, '', 'LR', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Recipient', 'LR', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(120, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Signature over printed name', 'LR', 'C', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, '', 'TL', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, '  CPV NO.', 'TLR', 'L', false, 1);

    $docno = $data[0]['docno'];

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(200, 0, $docno, 'LR', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, '', 'TL', 'C', false, 0);
    PDF::MultiCell(200, 0, '', 'LR', 'L', false, 1);


    PDF::SetFont($fontitalicbold, '', $fontsize - 1);
    PDF::MultiCell(720, 0, 'Department requesting payment should accomplish 2 copies with attachments for processing by Finance Dept.', 'T', 'L', false, 1);

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
