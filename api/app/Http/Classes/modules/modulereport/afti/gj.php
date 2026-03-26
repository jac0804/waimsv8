<?php

namespace App\Http\Classes\modules\modulereport\afti;

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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'radiosjafti', 'prepared', 'approved', 'received', 'checked', 'empcode', 'empname', 'tin', 'position', 'print'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 10) {
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookuppreparedby');
      data_set($col1, 'prepared.lookupclass', 'prepared');
      data_set($col1, 'prepared.readonly', true);

      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookuppreparedby');
      data_set($col1, 'approved.lookupclass', 'approved');
      data_set($col1, 'approved.readonly', true);

      data_set($col1, 'empcode.label', 'Payor Code');
      data_set($col1, 'empname.label', 'Payor');
      data_set($col1, 'tin.readonly', true);
      data_set($col1, 'position.readonly', true);
    }
    data_set($col1, 'radiosjafti.label', 'Report Type');

    data_set($col1, 'radiosjafti.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'red'],
      ['label' => 'Credit Note', 'value' => '1', 'color' => 'red'],
      ['label' => 'BIR 2307', 'value' => '2', 'color' => 'red']
    ]);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $adminid = $config['params']['adminid'];
    $user = $this->coreFunctions->opentable("
    select cl.clientname as prepared, emp.clientname as approved
    from client as cl
    left join client as emp on emp.clientid = cl.empid
    where cl.clientid = $adminid");

    $prepared = !empty($user) ? $user[0]->prepared : $config['params']['user'];
    $approved = !empty($user) ? $user[0]->approved : 'Elezandra Dela Cruz Tandayag';

    $payorcode = 'EM00000050';
    $payor = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$payorcode]);
    $payortin = $this->coreFunctions->getfieldvalue('client', 'tin', 'client=?', [$payorcode]);
    $payorposition = $this->coreFunctions->getfieldvalue('client', 'position', 'client=?', [$payorcode]);

    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '" . $prepared . "' as prepared,
      '" . $approved . "' as approved,
      '' as received,
      '" . $payorcode . "' as empcode,
      '" . $payor . "' as payor,
      '" . $payor . "' as empname,
      '" . $payorposition . "' as position,
      '" . $payortin . "' as tin,
      '0' as radiosjafti
      "
    );
  }

  public function report_default_query($filters)
  {

    switch ($filters['params']['dataparams']['radiosjafti']) {
      case '2':
        $trno = $filters['params']['dataid'];
        $filter = '(detail.isewt=1 or detail.isvewt=1)';
        if ($filters['params']['companyid'] === 10) $filter = 'coa.acno = "\\\2010109"';
        $query = "select * from(
            select detail.line, month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
            head.address,detail.rem, head.yourref, head.ourref,client.tin,
            coa.acno, coa.acnoname, detail.ref,detail.postdate,
            detail.db, detail.cr, detail.client as dclient, detail.checkno,
            detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
            client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,
            b.addr as billaddress, b.zipcode as billzipcode
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno
            left join client on client.client=head.client
            left join billingaddr as b on b.line=client.billid
            left join ewtlist on ewtlist.code = detail.ewtcode
            left join cntnum on cntnum.trno = head.trno
            left join center on center.code = cntnum.center
            left join coa on coa.acnoid=detail.acnoid
            where head.doc='GJ' and head.trno =" . $trno . " and " . $filter . "
            union all
            select detail.line, month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
            head.address,detail.rem, head.yourref, head.ourref,client.tin,
            coa.acno, coa.acnoname, detail.ref, detail.postdate,
            detail.db, detail.cr, dclient.client as dclient, detail.checkno,
            detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
            client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,
            b.addr as billaddress, b.zipcode as billzipcode
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno
            left join client on client.clientid=head.clientid
            left join billingaddr as b on b.line=client.billid
            left join coa on coa.acnoid=detail.acnoid
            left join client as dclient on dclient.clientid=detail.clientid
            left join ewtlist on ewtlist.code = detail.ewtcode
            left join cntnum on cntnum.trno = head.trno
            left join center on center.code = cntnum.center
            where head.doc='GJ' and head.trno =" . $trno . " and " . $filter . ")
            as tbl order by acnoname, ewtdesc";
        $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $arrs = [];
        $arrss = [];
        $ewt = '';
        $lines = 0;
        foreach ($result1 as $key => $value) {
          $ewtrateval = floatval($value['ewtrate']) / 100;
          $ewtamt = $value['cr'];
          $db = $ewtamt / $ewtrateval;

          array_push($arrss, $arrs);
          $arrs[$value['line']]['oamt'] = $db;
          $arrs[$value['line']]['xamt'] = $ewtamt;
          $arrs[$value['line']]['month'] = $value['month'];

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
          $returnarr[0]['billaddress'] = '';
          $returnarr[0]['billzipcode'] = '';
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
          $returnarr[0]['billaddress'] = $result1[0]['billaddress'];
          $returnarr[0]['billzipcode'] = $result1[0]['billzipcode'];
        }

        $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
        return $result;
        break;

      default:
        $trno = md5($filters['params']['dataid']);
        $query = "
        select head.trno, date(head.dateid) as dateid, head.docno, 
        head.clientname, head.address, head.yourref, head.rem,
        left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client,concat(left(detail.ref,3),right(detail.ref,5)) as ref, 
        date(detail.postdate) as postdate, detail.checkno, 
        detail.db, detail.cr, detail.line,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch,coa.cat
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.client=detail.client
        left join projectmasterfile as p on p.line=detail.projectid
        left join client as dept on dept.clientid=detail.deptid
        left join client as branch on branch.clientid=detail.branch
        where head.doc='gj' and md5(head.trno)='$trno'
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, 
        head.clientname, head.address, head.yourref, head.rem,
        left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client,concat(left(detail.ref,3),right(detail.ref,5)) as ref, 
        date(detail.postdate) as postdate, detail.checkno, 
        detail.db, detail.cr, detail.line,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch,coa.cat
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid
        left join projectmasterfile as p on p.line=detail.projectid
        left join client as dept on dept.clientid=detail.deptid
        left join client as branch on branch.clientid=detail.branch
        where head.doc='gj' and md5(head.trno)='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
        break;
    }
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_gj_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['radiosjafti']) {
        case 1:
          return $this->creditnote_gj_PDF($params, $data);
          break;
        case 2:
          return $this->BIR_GJ_PDF($params, $data);
          break;

        default:
          return $this->GJ_PDF($params, $data);
          break;
      }
    }
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
    PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']) . 'birlogo.png', '310', '10', 55, 55);

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

  public function rpt_gj_header_default($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];
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
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER/SUPPLIER: ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->address) ? $result[0]->address : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->yourref) ? $result[0]->yourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
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
    $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_gj_layout($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_gj_header_default($config, $result);


    $totaldb = 0;
    $totalcr = 0;
    foreach ($result as $key => $data) {
      $debit = number_format($data->db, 2);
      if ($debit < 1) {
        $debit = '-';
      }
      $credit = number_format($data->cr, 2);
      if ($credit < 1) {
        $credit = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acno, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->acnoname, '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->ref, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->postdate, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->client, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data->db;
      $totalcr = $totalcr + $data->cr;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_gj_header_default($config, $result);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
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
    $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function GJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '35', '30', 60, 50);
    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($font, '');

    PDF::SetFont($fontbold, '', 12);

    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0,  "VAT REG TIN: " . strtoupper($headerdata[0]->tin) . "\n\n\n", '', 'L');
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(530, 0, 'JOURNAL VOUCHER', '', 'C');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(70, 0, "Name: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 0, $data[0]['clientname'], 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "JV#: ", '', 'R', false, 0, '',  '');
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(70, 0, "Customer PO: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 0, '', 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(530, 0, '', 'T', 'C', false);
    PDF::MultiCell(530, 0, 'PARTICULARS', '', 'C', false);
    PDF::MultiCell(530, 0, '', 'B', 'C', false);
  }

  public function GJ_Details($config, $data)
  {
    $fontsize = "11";
    $font = "";
    $fontbold = "";
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);

    $totaldb = 0;
    $totalcr = 0;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $fontsize9 = "9";
    PDF::SetFont($fontbold, 'B', $fontsize9);
    PDF::MultiCell(55, 0, "ACOUNT CODE", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEBIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "CREDIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "ITEM GROUP", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "DEPT.", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "BRANCH", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "REF", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function GJ_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->GJ_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    $remarks = '';
    $trno = $data[0]['trno'];
    $tamt = 0;
    $particulars = $this->coreFunctions->opentable("select rem,amount from particulars where trno = ? union all select rem,amount from hparticulars where trno = ? ", [$trno, $trno]);
    $particulars =  json_decode(json_encode($particulars), true);
    if (!empty($particulars)) {
      PDF::MultiCell(0, 0, "\n");
      for ($x = 0; $x < count($particulars); $x++) {
        if ($particulars[$x]['rem'] != '') {

          $arrrem = $this->reporter->fixcolumn([$particulars[$x]['rem']], 80, 0);
          $crem = count($arrrem);
          $camt = count($particulars[$x]['amount']);
          $maxrow = 1;
          $maxrow = max($crem, $camt);
          for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(350, 0, isset($arrrem[$r]) ? ' ' . $arrrem[$r] : '', '', 'L', false, 0);
            if ($r == 0) {
              $partiamt = $particulars[$x]['amount'];
              PDF::MultiCell(180, 0,  number_format($partiamt, 2), '', 'R', false, 350);
            } else {
              $partiamt = '';
              PDF::MultiCell(180, 0,  $partiamt, '', 'R', false, 350);
            }
          }
        }
        $tamt = $tamt + $particulars[$x]['amount'];
      }

      PDF::MultiCell(0, 0, "\n");
      PDF::MultiCell(530, 0, '', 'T', 'C', false);
      PDF::SetFont($fontbold, '', $fontsize9);
      PDF::MultiCell(350, 0, 'TOTAL: ', '', 'L', false, 0);
      PDF::MultiCell(180, 0, number_format($tamt, 2), '', 'R', false, 0);
      PDF::MultiCell(0, 0, "\n");
    } else {
      if ($remarks == $data[0]['rem']) {
        $remarks = "";
      } else {
        $remarks = $data[0]['rem'];
      }

      PDF::SetFont($font, '', $fontsize9);
      PDF::MultiCell(350, 25, $remarks, '', 'L', false, 0);
      PDF::MultiCell(180, 25, '', '', 'R', false);
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(530, 0, '', 'T', 'C', false);

    $this->GJ_Details($params, $data);

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      $padding = 0;

      for ($i = 0; $i < count($data); $i++) {

        $arracnoname = $this->reporter->fixcolumn([$data[$i]['acnoname']], 13, 0);
        $cacnoname = count($arracnoname);

        $arrcostcenter = $this->reporter->fixcolumn([$data[$i]['costcenter']], 8, 0);
        $ccostcenter = count($arrcostcenter);

        $arrdepartment = $this->reporter->fixcolumn([$data[$i]['department']], 9, 0);
        $cdepartment = count($arrdepartment);

        $maxrow = max($cacnoname, $ccostcenter, $cdepartment);

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['ref'];
              $center = $data[$i]['costcenter'];
              $dept = $data[$i]['department'];
              $branch = $data[$i]['branch'];
              $ref = $data[$i]['ref'];
              $cat = $data[$i]['cat'];
              $postdate = $data[$i]['postdate'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]['client'];
            } else {
              $acno = '';
              $ref = '';
              $Cat = '';
              $postdate = '';
              $center = '';
              $dept = '';
              $branch = '';
              $debit = '';
              $credit = '';
              $client = '';
              $ref = '';
            }

            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(55, $padding, $acno, '', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            PDF::MultiCell(100, $padding, isset($arracnoname[$r]) ? $arracnoname[$r] : '', '', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            PDF::MultiCell(75, $padding, $debit, '', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            PDF::MultiCell(75, $padding, $credit, '', 'R', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            PDF::MultiCell(75, $padding, isset($arrcostcenter[$r]) ? $arrcostcenter[$r] : '', '', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            switch ($cat) {
              case 'R':
              case 'C':
              case 'E':
                PDF::MultiCell(50, $padding, isset($arrdepartment[$r]) ? $arrdepartment[$r] : '', '', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                PDF::MultiCell(50, $padding, $branch, '', 'C', false, 0);
                break;

              default:
                PDF::MultiCell(50, $padding, '', '', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                PDF::MultiCell(50, $padding, '', '', 'C', false, 0, '', '', true, 1, true, true, 0, 'B', true);
                break;
            }

            PDF::MultiCell(50, $padding, $ref, '', 'C', false, 1, '', '', true, 1, true, true, 0, 'B', true);

            if (PDF::getY() >= 750) {
              $this->GJ_header_PDF($params, $data);
            }
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];
      }
    }

    PDF::MultiCell(150, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(75, 0, number_format($totaldb, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(80, 0, '', 'T', 'R', false);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(23, 0, '', '', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(47, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Approved By:', '', 'L', false);



    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(23, 0, '', '', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(47, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(385, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Page : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function old_GJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']) . 'qslogo.png', '30', '', 220, 50);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 9);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tin), '', 'L');
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(530, 0, 'JOURNAL VOUCHER', '', 'C');

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(360, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "JV#: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(40, 0, "Name: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(320, 0, $data[0]['clientname'], 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9 + 1);
    PDF::MultiCell(530, 450, "", 'TBLR', 'L', false, 1, '40',  '180');
    PDF::MultiCell(70, 450, '', 'TBLR', 'L', false, 0, '40', '180');
    PDF::MultiCell(100, 450, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(220, 450, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(70, 450, '', 'TBLR', 'L', false, 0);
    PDF::MultiCell(70, 450, '', 'TBLR', 'L', false);

    PDF::MultiCell(70, 15, "COST CENTER", 'TBL', 'C', false, 0, '40', '180');
    PDF::MultiCell(100, 15, "DEPARTMENT", 'TBL', 'C', false, 0);
    PDF::MultiCell(220, 15, "ACCOUNT NAME", 'TBL', 'C', false, 0);
    PDF::MultiCell(70, 15, "DEBIT", 'TBL', 'C', false, 0);
    PDF::MultiCell(70, 15, "CREDIT", 'TBLR', 'C', false);
  }

  public function old_GJ_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->old_GJ_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $arracnoname = (str_split($data[$i]['acnoname'], 40));
        $acnonamedescs = [];

        if (!empty($arracnoname)) {
          foreach ($arracnoname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($acnonamedescs, $arr);
              }
            } else {
              array_push($acnonamedescs, $arri);
            }
          }
        }
        $countarr = count($acnonamedescs);

        $maxrow = $countarr;

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['ref'];
              $center = $data[$i]['costcenter'];
              $dept = $data[$i]['department'];
              $postdate = $data[$i]['postdate'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]['client'];
            } else {
              $acno = '';
              $ref = '';
              $postdate = '';
              $center = '';
              $dept = '';
              $debit = '';
              $credit = '';
              $client = '';
            }
            $accountlen = strlen(isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '') / 40;

            if ($acnonamedescs[$r] == '') {
              $accountlen = 1;
            }
            $padding = 20 * $accountlen;
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(70, $padding, $center, 'L', 'C', false, 0);
            PDF::MultiCell(100, $padding, $dept, 'L', 'C', false, 0);
            PDF::MultiCell(220, $padding, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', 'L', 'L', false, 0);
            PDF::MultiCell(70, $padding, $debit, 'L', 'R', false, 0);
            PDF::MultiCell(70, $padding, $credit, 'LR', 'R', false);
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->old_GJ_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(70, 20, '', 'TLB', 'C', false, 0, '40', '630');
    PDF::MultiCell(170, 20, '', 'TB', 'C', false, 0);
    PDF::MultiCell(150, 20, 'Total (PHP)', 'TB', 'C', false, 0);
    PDF::MultiCell(70, 20, $totaldb, 'TLB', 'R', false, 0);
    PDF::MultiCell(70, 20, $totalcr, 'TLRB', 'R', false);

    $remlen = strlen(isset($data[0]['rem']) ? $data[0]['rem'] : '') / 50;

    if ($data[0]['rem'] == '') {
      $remlen = 1;
    }
    $padding = 20 * $remlen;
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(15, $padding, '', 'TLB', 'L', false, 0);
    PDF::MultiCell(100, $padding, 'DESCRIPTION', 'TB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(415, $padding, $data[0]['rem'], 'TBR', 'L', false);

    PDF::MultiCell(530, 0, '', 'TBLR');

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(15, 35, ' ', 'L', 'L', false, 0);
    PDF::MultiCell(238, 35, 'Prepared By: ', 'T', 'L', false, 0);
    PDF::MultiCell(15, 35, ' ', 'L', 'L', false, 0);
    PDF::MultiCell(238, 35, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(24, 35, '', 'R', 'C');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(30, 15, '', 'BL', 'C', false, 0);
    PDF::MultiCell(200, 15, $params['params']['dataparams']['prepared'], 'TB', 'C', false, 0);
    PDF::MultiCell(23, 15, '', 'BR', 'C', false, 0);
    PDF::MultiCell(30, 15, '', 'B', 'C', false, 0);
    PDF::MultiCell(200, 15, $params['params']['dataparams']['approved'], 'TB', 'C', false, 0);
    PDF::MultiCell(47, 15, '', 'BR', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(385, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Page : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_GJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(380, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(100, 0, "Customer/Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(280, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(330, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(60, 0, "ACCOUNT#", '', 'L', false, 0);
    PDF::MultiCell(120, 0, "ACCOUNT NAME", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(60, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(55, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(55, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "CLIENT", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function default_GJ_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_GJ_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $arracnoname = (str_split($data[$i]['acnoname'], 40));
        $acnonamedescs = [];

        if (!empty($arracnoname)) {
          foreach ($arracnoname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($acnonamedescs, $arr);
              }
            } else {
              array_push($acnonamedescs, $arri);
            }
          }
        }
        $countarr = count($acnonamedescs);

        $maxrow = $countarr;

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['ref'];
              $postdate = $data[$i]['postdate'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]['client'];
            } else {
              $acno = '';
              $ref = '';
              $postdate = '';
              $debit = '';
              $credit = '';
              $client = '';
            }
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(60, 0, $acno, '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(120, 0, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ref, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(55, 0, $debit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(55, 0, $credit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0, $client, '', 'L', false, 1, '', '', false, 1);
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->default_GJ_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(530, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(340, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(55, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(55, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(480, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(153, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(153, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function creditnote_header_gj_PDF($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $font = '';
    $fontbold = '';
    $fontsize = '11';
    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    PDF::SetFont($font, '', 14);

    PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 200, 50);
    PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);

    $drdocno = isset($data[0]['docno']) ? $data[0]['docno'] : '';

    PDF::MultiCell(0, 40, "\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, ' ' . '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->address, '', '', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, '', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 40, "\n");

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(525, 0, 'CREDIT NOTE', '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "CN NO.: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Currency: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['cur']) ? $data[0]['cur'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Page: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n");

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $totalext += $data[$i]['db'];
    }

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Credit to : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Amount : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0,  '  ' . (isset($data[0]['cur']) ? $data[0]['cur'] : '') . number_format($totalext, $decimalprice), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 120, " Description : ", 'TLRB', 'R', false, 0, '',  '');
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(470, 120, '  ' . (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'TLRB', 'L', false, 1);

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(535, 0, 'This is a system-generated document Signature of approver is not required.', '', 'C', false, 1);
  }

  public function creditnote_gj_PDF($params, $data)
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
    $this->creditnote_header_gj_PDF($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
