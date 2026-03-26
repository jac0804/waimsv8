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

class cp
{

  private $modulename = "Lifeplan Agreement";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';

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


    $fields = ['radioprint', 'prepared', 'approved', 'received'];

    $col1 = $this->fieldClass->create($fields);



    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    data_set($col1, 'prepared.label', 'Planholder/Representative');
    data_set($col1, 'approved.label', 'President/General Manager');
    data_set($col1, 'received.label', 'Sales & Marketing Head ');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config, $type = 1)
  {
    $companyid = $config['params']['companyid'];
    $allowCertificateForm = $this->othersClass->checkAccess($config['params']['user'], 4172);

    $user = $config['params']['user'];
    $trno = $config['params']['trno'];
    $clientname = $this->coreFunctions->datareader("select concat(ea.lname,', ',ea.fname,' ',ea.mname,' ',ea.ext) as value from lahead as head left join heainfo as ea on ea.trno = head.aftrno where head.trno = ? union all select concat(ea.lname,', ',ea.fname,' ',ea.mname,' ',ea.ext) as value from glhead as head left join heainfo as ea on ea.trno = head.aftrno where head.trno = ?", [$trno, $trno]);

    $paramstr = "select
    'PDFM' as print,
    '$clientname' as prepared,
    'Jesse Baloca' as approved,
    'Jesse Baloca' as received";

    if ($type == 2) {
      $paramstr .= ",'certificate' as reporttype";
    } else {
      $paramstr .= ",'' as reporttype";
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $qryselect = "select head.aftrno, head.trno, head.doc,cntnum.bref,cntnum.seq,num.bref as eabref,num.seq as easeq, concat(left(head.docno,2),'',right(head.docno,8)) as docno, c.clientname, info.fname, info.mname, info.lname,info.ext as payext, date_format(info.bday, '%m/%d/%Y') as bday, info.gender,
      info.address, ehead.fname as pfname, ehead.mname as pmname, ehead.lname as plname, ehead.ext as pext, concat(left(ehead.docno,2),'',right(ehead.docno,8)) as afdocno, date_format(head.dateid, '%m/%d/%Y') as dateid,
      ehead.terms, ehead.yourref, plan.name as plantype,case info.issenior when 1 then plan.amount/1.12 else plan.amount end as amount,plan.cash,plan.annual,plan.semi,plan.monthly,plan.quarterly, date_format(head.due, '%m/%d/%Y') as due, ehead.contactno,info.isplanholder,pg.code as plangrp,info.issenior,
      concat(info.addressno,' ',info.street,', ',info.subdistown,' Brgy. ',info.brgy,', ',info.city,' ',info.zipcode) as certaddr ";
    $qry = $qryselect . "
      from lahead as head
        left join cntnum on cntnum.trno=head.trno
        left join heainfo as info on info.trno=head.aftrno
        left join heahead as ehead on ehead.trno=head.aftrno
        left join transnum as num on num.trno = ehead.trno
        left join plantype as plan on plan.line=ehead.planid
        left join plangrp as pg on pg.line = ehead.plangrpid
        left join client as c on c.client = head.client
      where cntnum.doc='" . $doc . "' and head.trno=" . $trno . " and cntnum.center='" . $center . "'
      union all
      " . $qryselect . "
      from glhead as head
        left join cntnum on cntnum.trno=head.trno
        left join heainfo as info on info.trno=head.aftrno
        left join heahead as ehead on ehead.trno=head.aftrno
        left join transnum as num on num.trno = ehead.trno
        left join plantype as plan on plan.line=ehead.planid
        left join plangrp as pg on pg.line = ehead.plangrpid
        left join client as c on c.clientid = head.clientid
      where cntnum.doc='" . $doc . "' and head.trno=" . $trno . " and cntnum.center='" . $center . "'";
    return $this->coreFunctions->opentable($qry);
  } //end fn


  public function reportplotting($params, $data)
  {
    $allowCertificateForm = $this->othersClass->checkAccess($params['params']['user'], 4172);
    if ($allowCertificateForm) {
      $reporttype = $params['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case 'certificate':
          return $this->certificate_CP_PDF($params, $data);
          break;

        default:
          return $this->default_CP_PDF($params, $data);
          break;
      }
    } else {
      return $this->default_CP_PDF($params, $data);
    }
  }

  public function certificate_CP_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $trno = $params['params']['dataid'];


    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontreg = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

    if (Storage::disk('sbcpath')->exists('/fonts/myriadproregular.t11')) {
      $fontreg = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/myriadproregular.t11');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [615, 790]);
    PDF::SetMargins(40, 40);

    $trno = $params['params']['dataid'];
    $bal = $this->coreFunctions->datareader("select sum(bal) as value from arledger where trno = ?", [$trno]);
    if (floatval($bal) != 0) {
      PDF::MultiCell(680, 0, 'NOT YET FULLY PAID', '', 'C');
    } else {
      // header changeimage

      PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '18', '12', 342, 78);
      $strdocno = $data[0]->bref . $data[0]->seq;
      $strdocno = $this->othersClass->PadJ($strdocno, 10);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      


      PDF::SetFont($fontbold, '', '20');
      PDF::MultiCell(560, 0, 'CERTIFICATE OF FULL PAYMENT', '', 'C', false, 0);
      PDF::MultiCell(170, 0, '', '', 'C', false, 1);

      PDF::SetFont($font, '', 7);
      PDF::MultiCell(680, 0, '', '', '', false, 1);

      // header2
      PDF::SetFont($font, '', '11');
      PDF::SetFont($fontreg, '', '10');

      PDF::MultiCell(720, 0, 'KNOW ALL MEN BY THESE PRESENTS:', '', 'L');
      PDF::MultiCell(720, 0, '', '', '', false, 1);

      PDF::MultiCell(720, 0, 'EVERGREEN LIFEPLAN SERVICES, INC., a corporation duly organized and existing under and by virtue of the the laws of the Republic', '', 'L', false, 1);
      PDF::MultiCell(720, 0, 'of the Philippines, with principal office at 300 C. Raymundo Ave., Maybunga, Pasig City, hereby certifies that full payment has been', '', 'L', false, 1);
      PDF::MultiCell(720, 0, 'made for the LIFEPLAN Contract as follow: ', '', 'L', false, 1);

      PDF::SetFont($fontbold, '', '5');
      PDF::MultiCell(720, 0, '', '', '', false, 1);

      // planholder info
      PDF::SetFont($fontbold, '', '11.81');
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(710, 0, 'I. Planholder Information', '', 'L', false, 1);

      PDF::SetFont($fontbold, '', '9.9');

      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(280, 0, 'Last, First, Middle', '', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(130, 0, 'Birthdate (mm/dd/yyyy)', '', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(50, 0, 'Gender', '', 'L', false, 0);
      PDF::MultiCell(210, 0, '', '', 'L', false, 1);

      

      PDF::SetFont($fontbold, '', '10');
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(280, 0, (isset($data[0]->lname) ? $data[0]->lname : '') . ', ' . (isset($data[0]->fname) ? $data[0]->fname : '') . ' ' . (isset($data[0]->mname) ? $data[0]->mname : '') . ' ' . (isset($data[0]->payext) ? $data[0]->payext : ''), 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(130, 0, (isset($data[0]->bday) ? $data[0]->bday : ''), 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(50, 0, (isset($data[0]->gender) ? $data[0]->gender : ''), 'B', 'L', false, 0);
      PDF::MultiCell(210, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', '6');
      PDF::MultiCell(680, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', '9.9');
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(330, 0, 'Address', '', 'L', false, 0);
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, 'Contact No.', '', 'L', false, 0);
      PDF::MultiCell(210, 0, '', '', 'L', false, 1);

      
      $arr_address = $this->reporter->fixcolumn([$data[0]->certaddr], '65', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
      $addrpadding = 0;
      if (strlen(isset($data[0]->address) ? $data[0]->address : '') > 65) {
        $addrpadding = 35;
      }

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::MultiCell(20, 0, '', '', '', false, 0);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(330, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(20, 0, '', '', '', false, 0);

        if ($r + 1 == $maxrow) {
          PDF::MultiCell(140, 0, (isset($data[0]->contactno) ? $data[0]->contactno : ''), '', 'L', false, 0);
        } else {
          PDF::MultiCell(140, 0, '', '', 'L', false, 0);
        }
        PDF::MultiCell(210, 0, '', '', 'L', false, 1);
      }
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(330, 0, '', 'T', 'L', false, 0);
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, '', 'T', 'L', false, 0);
      PDF::MultiCell(210, 0, '', '', 'L', false, 1);

      PDF::SetFont($fontbold, '', '9.9');
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(700, 0, 'Payor Name (if different than Planholder)', '', 'L', false, 1);



      PDF::MultiCell(20, 0, '', '', '', false, 0);
      if ($data[0]->isplanholder == 1) {
        PDF::MultiCell(280, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(420, 0, '', '', 'L', false, 1);
      } else {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(280, 0, (isset($data[0]->plname) ? $data[0]->plname : '') . ', ' . (isset($data[0]->pfname) ? $data[0]->pfname : '') . ' ' . (isset($data[0]->pmname) ? $data[0]->pmname : '') . ' ' . (isset($data[0]->pext) ? $data[0]->pext : ''), 'B', 'L', false, 0);
        PDF::MultiCell(420, 0, '', '', 'L', false, 1);
      }

      PDF::MultiCell(720, 0, '', '', '', false, 1);
      //PDF::MultiCell(720, 0, '', '', '', false, 1);

      // contract specs
      PDF::SetFont($fontbold, '', '11.81');
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(710, 0, 'II. Contract Specifications', '', 'L', false, 1);

      

      PDF::SetFont($fontbold, '', '9.9');
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(120, 0, 'Application No.', '', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, 'Contract & Agreement Date', '', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, 'Initial/Base Value', '', 'L', false, 0);
      PDF::MultiCell(270, 0, '', '', 'L', false, 1);

      // PDF::MultiCell(720, 0, '', '', '', false, 1);

      $strdocno = $data[0]->eabref . $data[0]->easeq;
      $strdocno = $this->othersClass->PadJ($strdocno, 10);
      $inival =  ($data[0]->issenior == 1 ? $data[0]->amount : $data[0]->amount / 1.12);

      $strladocno = $data[0]->bref . $data[0]->seq;
      $strladocno = $this->othersClass->PadJ($strladocno, 10);

      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(120, 0, $strdocno, 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0,  $strladocno . ' ' . (isset($data[0]->dateid) ? $data[0]->dateid : ''), 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, number_format($inival, 2), 'B', 'L', false, 0);
      PDF::MultiCell(270, 0, '', '', '', false, 1);

      
      PDF::SetFont($fontbold, '', '5');
      PDF::MultiCell(720, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', '9.9');
      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(150, 0, 'Plan Type', '', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(110, 0, 'Full Payment Date', '', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::MultiCell(270, 0, '', '', '', false, 1);

      // PDF::MultiCell(720, 0, '', '', '', false, 1);

      PDF::SetFont($font, '', 12);
      PDF::MultiCell(20, 0, '', '', '', false, 0);

      $amt = 0;
      switch (strtoupper($data[0]->terms)) {
        case 'ANNUAL':
          $amt = ($data[0]->issenior == 1 ? $data[0]->annual / 1.12 : $data[0]->annual);
          break;
        case 'SEMI-ANNUAL':
          $amt = ($data[0]->issenior == 1 ? $data[0]->semi / 1.12 : $data[0]->semi);
          break;
        case 'MONTHLY':
          $amt = ($data[0]->issenior == 1 ? $data[0]->monthly / 1.12 : $data[0]->monthly);
          break;
        case 'QUARTERLY':
          $amt = ($data[0]->issenior == 1 ? $data[0]->quarterly / 1.12 : $data[0]->quarterly);
          break;
        case 'FULL PAYMENT':
          $amt =  $data[0]->amount;
          break; 
        default:
          $amt = ($data[0]->issenior == 1 ? $data[0]->cash / 1.12 : $data[0]->cash);
          break;
      }

      $fullpaydate = $this->coreFunctions->datareader("select dateid as value from 
      (select h.dateid from glhead as h left join cntnum as cnt on cnt.trno = h.trno left join hcntnuminfo as c on c.trno = h.trno where cnt.doc='CR' and  c.cptrno = ?
      union all
      select h.dateid from lahead as h left join cntnum as cnt on cnt.trno = h.trno left join cntnuminfo as c on c.trno = h.trno where  cnt.doc='CR' and  c.cptrno = ?) as a order by dateid desc limit 1",[$trno,$trno]);

      PDF::MultiCell(150, 0, (isset($data[0]->plantype) ? $data[0]->plangrp . "-" . $data[0]->plantype : ''), 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::MultiCell(110, 0, (isset($fullpaydate) ? date('m/d/Y',strtotime($fullpaydate)) : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(150, 0, '', '', 'L', false, 0);
      PDF::MultiCell(270, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', '11.81');
      PDF::MultiCell(720, 0, '', '', '', false, 1);
      PDF::MultiCell(720, 0, '', '', '', false, 1);
      

      // beneficiaries
      $beneficiaries = $this->getBeneficiaries($data[0]->aftrno);

      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(710, 0, 'III. Beneficiaries/Assignments', '', 'L', false, 1);

      PDF::SetFont($fontbold, '', '9.9');
      

      PDF::MultiCell(20, 0, '', '', '', false, 0);
      PDF::MultiCell(150, 0, 'Name(s)', '', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, 'Relationship', '', 'L', false, 0);
      PDF::MultiCell(30, 0, '', '', '', false, 0);
      PDF::MultiCell(170, 0, 'Name(s)', '', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(70, 0, 'Relationship', '', 'L', false, 0);
      PDF::MultiCell(195, 0, '', '', 'L', false, 1);

      

      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', '9');
      PDF::MultiCell(15, 0, '(1)', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(150, 0, (isset($beneficiaries[0]->name) ? $beneficiaries[0]->name : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, (isset($beneficiaries[0]->relation) ? $beneficiaries[0]->relation : ''), 'B', 'L', false, 0);
      PDF::MultiCell(15, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(15, 0, '(2)', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(170, 0, (isset($beneficiaries[1]->name) ? $beneficiaries[1]->name : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(70, 0, (isset($beneficiaries[1]->relation) ? $beneficiaries[1]->relation : ''), 'B', 'L', false, 0);
      PDF::MultiCell(190, 0, '', '', 'L', false, 1);

      PDF::SetFont($font, '', '7');
      PDF::MultiCell(720, 0, '', '', '', false, 1);

      PDF::SetFont($font, '', '9');
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(15, 0, '(3)', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(150, 0, (isset($beneficiaries[2]->name) ? $beneficiaries[2]->name : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, (isset($beneficiaries[2]->relation) ? $beneficiaries[2]->relation : ''), 'B', 'L', false, 0);
      
      if(count($beneficiaries)>3){
        //$this->coreFunctions->LogConsole("Over".count($beneficiaries));
        PDF::MultiCell(15, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(15, 0, '(4)', '', '', false, 0);
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(170, 0, (isset($beneficiaries[3]->name) ? $beneficiaries[3]->name : ''), 'B', 'L', false, 0);
        PDF::MultiCell(5, 0, '', '', '', false, 0);
        PDF::MultiCell(70, 0, (isset($beneficiaries[3]->relation) ? $beneficiaries[3]->relation : ''), 'B', 'L', false, 0);
        PDF::MultiCell(190, 0, '', '', 'L', false, 1);
      }else{
        PDF::MultiCell(15, 0, '', '', 'L', false, 1);
      }
     
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      // PDF::MultiCell(680, 0, '', '', '', false, 1);

      $current_date = date("m/d/Y", strtotime($this->othersClass->getCurrentTimeStamp()));
      $locale = 'en_US';
      $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);

      $d =  date("d", strtotime($this->othersClass->getCurrentTimeStamp()));
      $day = $nf->format($d);
      $m =  date("F", strtotime($this->othersClass->getCurrentTimeStamp()));
      $yr=date("Y", strtotime($this->othersClass->getCurrentTimeStamp()));
      PDF::SetFont($fontreg, '', '10');
      PDF::MultiCell(680, 0, 'EVERGREEN LIFEPLAN SERVICES, INC., through this certificate guarantees to the PLANHOLDER, his/her executor, administrator, or', '', 'L', false, 1);
      PDF::MultiCell(680, 0, 'assignee, the benefits and privileges of the PLANHOLDER subject only and always to the LIFEPLAN Contract and any and all ', '', 'L', false, 1);
      PDF::MultiCell(680, 0, 'amendments thereto.', '', 'L', false, 1);

      PDF::SetFont($fontreg, '', '4');
      PDF::MultiCell(680, 0, '', '', '', false, 1);

      PDF::SetFont($fontreg, '', '10');
      PDF::MultiCell(680, 0, 'All terms, provisions, and conditions constrained under the General Provisions of the Contract together with any and all amendments', '', 'L', false, 1);
      PDF::MultiCell(680, 0, 'thereto, duly assigned by EVERGREEN and the application consitutes the entire Agreement between EVERGREEN and the PLANHOLDER.', '', 'L', false, 1);

      PDF::SetFont($fontreg, '', '4');
      PDF::MultiCell(680, 0, '', '', '', false, 1);

      PDF::SetFont($fontreg, '', '10');
      PDF::MultiCell(680, 0, 'IN WITNESS WHEREOF, Evergreen Lifeplan Services, Inc. has caused this instrument to be signed and acknowledged by its duly authorized', '', 'L', false, 1);
      PDF::MultiCell(170, 0, 'officer and its corporate seal affixed this ', '', 'L', false, 0);
      PDF::MultiCell(40, 0, $day, 'B', 'C', false, 0);
      PDF::MultiCell(20, 0, ' of ', '', 'L', false, 0);
      PDF::MultiCell(60, 0, $m, 'B', 'C', false, 0);
      PDF::MultiCell(10, 0, ', ', '', 'L', false, 0);
      PDF::MultiCell(30, 0, $yr, 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '.', '', 'L', false, 1);

      PDF::SetFont($fontreg, '', '4');
      PDF::MultiCell(680, 0, '', '', '', false, 1);
      PDF::MultiCell(680, 0, '', '', '', false, 1);

      PDF::SetFont($fontreg, '', '10');
      PDF::MultiCell(680, 0, 'Signed for Evergreen at its Head Office, Pasig City, Philippines.', '', 'L', false, 1);



      $current_date = date("m/d/Y", strtotime($this->othersClass->getCurrentTimeStamp()));
      // footer

      PDF::MultiCell(680, 0, '', '', '', false, 1);


      
    $arr_planholder = $this->reporter->fixcolumn([$params['params']['dataparams']['prepared']], '27', 0);
    // $arr_planholder = $this->reporter->fixcolumn(['Miravite, Eurydice Teresita Zzxcxczzzzzzz'], '35', 0);

    $maxrow = $this->othersClass->getmaxcolumn([$arr_planholder]);
    
    

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', '12');
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      
      if ($r + 1 == $maxrow) {
        PDF::MultiCell(140, 0, (isset($arr_planholder[$r]) ? $arr_planholder[$r] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, $current_date, 'B', 'L', false, 0);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(150, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, $current_date, 'B', 'L', false, 0);
        PDF::MultiCell(210, 0, '', '', 'L', false, 1);
      } else {
        PDF::MultiCell(140, 0, (isset($arr_planholder[$r]) ? $arr_planholder[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, '', '', 'L', false, 0);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(150, 0, '', '', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, '', '', 'L', false, 0);
        PDF::MultiCell(210, 0, '', '', 'L', false, 1);
      }
    }


      PDF::SetFont($fontbold, '', '11.81');
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(140, 0, 'Planholder/Representative', '', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, 'Date', '', 'L', false, 0);
      PDF::MultiCell(30, 0, '', '', '', false, 0);
      PDF::MultiCell(150, 0, 'President/General Manager', '', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, 'Date', '', 'L', false, 0);
      PDF::MultiCell(210, 0, '', '', 'L', false, 1);


      PDF::MultiCell(680, 0, '', '', '', false, 1);

      PDF::SetFont($font, '', $fontsize - 3);
      PDF::MultiCell(550, 0, '(Not Valid Without Corporate Seal)', '', 'R', false, 0);
      PDF::MultiCell(170, 0, '', '', 'R', false, 1);
    }
  }

  public function certificate_CP_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->certificate_CP_header_PDF($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function default_CP_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $trno = $params['params']['dataid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();


    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $fontmyriad = "";

    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    if (Storage::disk('sbcpath')->exists('/fonts/myriadproreg.t11')) {
      $fontmyriad = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/myriadproreg.t11');
    }


    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(15, 15);
    PDF::AddPage('p', [610, 820]);

    // header changeimage

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '18', '12', 342, 78);

    $strdocno = $data[0]->bref . $data[0]->seq;
    $strdocno = $this->othersClass->PadJ($strdocno, 10);

    
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(400, 0, '', '', '', false, 0);
    PDF::MultiCell(70, 0, 'Contract No:', '', 'R', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(85, 0, $strdocno, 'B', 'L', false, 1);


    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(350, 0, '', '', '', false, 0);
    PDF::MultiCell(75, 0, 'Plan Type: ', '', 'R', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]->plantype) ? $data[0]->plangrp . "-" . $data[0]->plantype : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(350, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(75, 0, 'Amount: ', '', 'R', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]->amount) ? number_format(floatval($data[0]->amount), 2) : '') . ' ' . ($data[0]->issenior == 1 ? 'Senior' : ''), 'B', 'L', false, 1);

    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::SetFont($fontbold, '', '14');
    // PDF::MultiCell(560, 0, "\n");
    PDF::MultiCell(560, 0, 'LIFEPLAN AGREEMENT', '', 'C');

    PDF::SetFont($fontmyriad, '', '8');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    // header2
    PDF::SetFont($fontmyriad, '', '10');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(355, 0, 'KNOW ALL MEN BY THESE PRESENTS:', '', 'L');
    PDF::SetFont($font, '', '6');
    PDF::MultiCell(600, 0, '', '', '', false, 1);
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', '10');
    PDF::MultiCell(565, 0, 'That this Contract and Agreement between EVERGREEN LIFEPLAN SERVICES, INC., a corporation duly organized and existing under and', '', 'L');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'by virtue of the the laws of the Republic of the Philippines, hereby agrees with the PLANHOLDER and guarantees to pay for the', '', 'L');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'arrangement, delivery and performance of funeral and/or cremation services by THE EVERGREEN at the time of need, subject to the terms', '', 'L');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'and conditions set forth under the Lifeplan’s Provisions, provided that the lifeplan is fully paid or the payment terms are current.', '', 'L'); 

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    // planholder info
    PDF::SetFont($font, '', '5');
    PDF::MultiCell(560, 0, "");
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '11');
    PDF::MultiCell(560, 0, 'I. Planholder Information', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::SetFont($fontbold, '', '9');

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Last, First, Middle', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Birthdate (mm/dd/yyyy)', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, 'Gender', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(185, 0, (isset($data[0]->lname) ? $data[0]->lname : '') . ', ' . (isset($data[0]->fname) ? $data[0]->fname : '') . ' ' . (isset($data[0]->mname) ? $data[0]->mname : '') . ' ' . (isset($data[0]->payext) ? $data[0]->payext : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(185, 0, (isset($data[0]->bday) ? $data[0]->bday : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(100, 0, (isset($data[0]->gender) ? $data[0]->gender : ''), 'B', 'L', false, 1);

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(345, 0, 'Address', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(130, 0, 'Contact No.', '', 'L', false, 1);

    $arr_address = $this->reporter->fixcolumn([$data[0]->certaddr], '68', 0);

    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $addrpadding = 0;
    if (strlen(isset($data[0]->address) ? $data[0]->address : '') > 68) {
      $addrpadding = 35;
    }

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::MultiCell(70, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(345, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);

      if ($r + 1 == $maxrow) {
        PDF::MultiCell(130, 0, (isset($data[0]->contactno) ? $data[0]->contactno : ''), '', 'L', false, 1);
      } else {
        PDF::MultiCell(130, 0, '', '', 'L', false, 1);
      }
     // PDF::MultiCell(210, 0, '', '', 'L', false, 1);
    }

    PDF::SetFont($font, '', '4');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(345, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(130, 0, '', 'T', 'L', false, 1);   


    // PDF::SetFont($fontbold, '', '4');
    // PDF::MultiCell(560, 0, '', '', '', false, 1);
    

    // PDF::MultiCell(70, 0, '', '', '', false, 0);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(345, 0, (isset($data[0]->certaddr) ? $data[0]->certaddr : ''), 'B', 'L', false, 0);
    // PDF::MultiCell(5, 0, '', '', '', false, 0);
    // PDF::SetFont($font, '', 12);
    // PDF::MultiCell(130, 0, (isset($data[0]->contactno) ? $data[0]->contactno : ''), 'B', 'L', false, 1);

    PDF::SetFont($font, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(420, 0, 'Payor Name (if different than Planholder)', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    if ($data[0]->isplanholder == 1) {
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(300, 0, '', 'B', 'L', false, 1);
    } else {
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(300, 0, (isset($data[0]->plname) ? $data[0]->plname : '') . ', ' . (isset($data[0]->pfname) ? $data[0]->pfname : '') . ' ' . (isset($data[0]->pmname) ? $data[0]->pmname : ''). ' ' . (isset($data[0]->pext) ? $data[0]->pext : ''), 'B', 'L', false, 1);
    }

    PDF::SetFont($font, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);


    // contract specs
    PDF::SetFont($font, '', '5');
    PDF::MultiCell(560, 0, "");
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '11');
    PDF::MultiCell(560, 0, 'II. Contract Specifications', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Application No.', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Contract & Agreement Date', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, 'Initial Value', '', 'L', false, 1);


    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    $strdocno = $data[0]->eabref . $data[0]->easeq;
    $strdocno = $this->othersClass->PadJ($strdocno, 10);
    $inival =  ($data[0]->issenior == 1 ? $data[0]->amount : $data[0]->amount / 1.12);

    $strladocno = $data[0]->bref . $data[0]->seq;
    $strladocno = $this->othersClass->PadJ($strladocno, 10);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(185, 0, $strdocno, 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, $strladocno . ' ' . (isset($data[0]->dateid) ? $data[0]->dateid : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, number_format($inival, 2), 'B', 'L', false, 1);

    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Payment Terms (mthly, qtrly, periodic)', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(185, 0, 'Length of Payment Term', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, 'Full Payment Due Date', '', 'L', false, 1);


    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 12);

    $amt = 0;
    switch (strtoupper($data[0]->terms)) {
      case 'ANNUAL':
        $amt = ($data[0]->issenior == 1 ? $data[0]->annual / 1.12 : $data[0]->annual);
        break;
      case 'SEMI-ANNUAL':
        $amt = ($data[0]->issenior == 1 ? $data[0]->semi / 1.12 : $data[0]->semi);
        break;
      case 'MONTHLY':
        $amt = ($data[0]->issenior == 1 ? $data[0]->monthly / 1.12 : $data[0]->monthly);
        break;
      case 'QUARTERLY':
        $amt = ($data[0]->issenior == 1 ? $data[0]->quarterly / 1.12 : $data[0]->quarterly);
        break;
      case 'FULL PAYMENT':
        $amt = $data[0]->amount;
      break;
      default:
        $amt = ($data[0]->issenior == 1 ? $data[0]->cash / 1.12 : $data[0]->cash);
        break;
    }


    PDF::MultiCell(185, 0, (isset($data[0]->terms) ? $data[0]->terms . ' - P ' . number_format($amt, 2) : ''), 'B', 'L', false, 0);

    PDF::MultiCell(5, 0, '', '', '', false, 0);
    if (strtoupper($data[0]->terms) == 'COD' || strtoupper($data[0]->terms) == 'SPOT CASH' || strtoupper($data[0]->terms) == 'UPFRONT 3%') {
      PDF::MultiCell(185, 0, 'NOT APPLICABLE', 'B', 'L', false, 0);
    } else {
      PDF::MultiCell(185, 0, '2 Years', 'B', 'L', false, 0);
    }

    $due = $this->coreFunctions->datareader("select date_format(dateid, '%m/%d/%Y') as value from arledger where trno = ".$trno." order by dateid desc limit 1");
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(100, 0, (isset($due) ? $due : ''), 'B', 'L', false, 1);

    PDF::SetFont($fontbold, '', '6');
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    

    // beneficiaries
    $beneficiaries = $this->getBeneficiaries($data[0]->aftrno);

    PDF::SetFont($font, '', '5');
    PDF::MultiCell(560, 0, "");
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '11');
    PDF::MultiCell(560, 0, 'III. Beneficiaries/Assignments', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', '4');

    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::SetFont($fontbold, '', '9');
    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::MultiCell(148, 0, 'Name(s)', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, 'Relationship', '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(147, 0, 'Name(s)', '', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, 'Relationship', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(15, 0, '(1)', '', '', false, 0);
    PDF::SetFont($font, '', '12');
    PDF::MultiCell(133, 0, (isset($beneficiaries[0]->name) ? $beneficiaries[0]->name : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, (isset($beneficiaries[0]->relation) ? $beneficiaries[0]->relation : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(15, 0, '(3)', '', '', false, 0);
    PDF::SetFont($font, '', '12');
    PDF::MultiCell(142, 0, (isset($beneficiaries[2]->name) ? $beneficiaries[2]->name : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, (isset($beneficiaries[2]->relation) ? $beneficiaries[2]->relation : ''), 'B', 'L', false, 1);

    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(70, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(15, 0, '(2)', '', '', false, 0);
    PDF::SetFont($font, '', '12');
    PDF::MultiCell(133, 0, (isset($beneficiaries[1]->name) ? $beneficiaries[1]->name : ''), 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, '', '', '', false, 0);
    
    if(count($beneficiaries)>3){
      PDF::MultiCell(80, 0, (isset($beneficiaries[1]->relation) ? $beneficiaries[1]->relation : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(15, 0, '(4)', '', '', false, 0);
      PDF::SetFont($font, '', '12');
      PDF::MultiCell(142, 0, (isset($beneficiaries[3]->name) ? $beneficiaries[3]->name : ''), 'B', 'L', false, 0);
      PDF::MultiCell(5, 0, '', '', '', false, 0);
      PDF::MultiCell(80, 0, (isset($beneficiaries[3]->relation) ? $beneficiaries[3]->relation : ''), 'B', 'L', false, 1);
    }else{
      PDF::MultiCell(80, 0, (isset($beneficiaries[1]->relation) ? $beneficiaries[1]->relation : ''), 'B', 'L', false, 1);
    }
    
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    // terms
    PDF::SetFont($fontmyriad, '', '13');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::SetFont($fontmyriad, '', '10');
    PDF::MultiCell(565, 0, 'All the terms, provisions, conditions and subsequent amendements contained in this Contract and in the Agreement Provisions duly', '', 'L', false, 1);
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'assigned by The Evergreen and acceptance by the Planholder constitues the entire agreement between The Evergreen and the Planholder.', '', 'L', false, 1);
    PDF::MultiCell(15, 0, '', '', '', false, 0);

    PDF::SetFont($font, '', '4');
    PDF::MultiCell(565, 0, '', '', '', false, 1);

    PDF::SetFont($fontmyriad, '', '10');
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'IN WITNESS WHEREOF, Evergreen Lifeplan Services, Inc. and Planholder have caused this Contract and Agreement to be effective in', '', 'L', false, 1);
    PDF::MultiCell(15, 0, '', '', '', false, 0);
    PDF::MultiCell(565, 0, 'accordance to the terms stated above.', '', 'L', false, 1);    

    
    PDF::SetFont($font, '', '15');
    PDF::MultiCell(565, 0, '', '', '', false, 1);


    $current_date = date("m/d/Y", strtotime($this->othersClass->getCurrentTimeStamp()));
    
    $arr_planholder = $this->reporter->fixcolumn([(isset($data[0]->lname) ? $data[0]->lname : '') . ', ' . (isset($data[0]->fname) ? $data[0]->fname : '') . ' ' . (isset($data[0]->mname) ? $data[0]->mname : '')], '30', 0);
    // $arr_planholder = $this->reporter->fixcolumn(['Miravite, Eurydice Teresita Zzxcxczzzzzzz'], '35', 0);

    $maxrow = $this->othersClass->getmaxcolumn([$arr_planholder]);
    
    

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::MultiCell(40, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', '11');
      
      if ($r + 1 == $maxrow) {
        PDF::MultiCell(140, 0, (isset($arr_planholder[$r]) ? $arr_planholder[$r] : ''), 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, $current_date, 'B', 'L', false, 0);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(140, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, $current_date, 'B', 'L', false, 1);
      } else {
        PDF::MultiCell(140, 0, (isset($arr_planholder[$r]) ? $arr_planholder[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, '', '', 'L', false, 0);
        PDF::MultiCell(30, 0, '', '', '', false, 0);
        PDF::MultiCell(140, 0, '', '', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::MultiCell(80, 0, '', '', 'L', false, 1);
      }
    }


    PDF::MultiCell(40, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '11');
    PDF::MultiCell(140, 0, 'Planholder/Representative', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, 'Date', '', 'L', false, 0);
    PDF::MultiCell(30, 0, '', '', '', false, 0);
    PDF::MultiCell(140, 0, 'President/General Manager', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, 'Date', '', 'L', false, 1);

    
    PDF::SetFont($font, '', '4');
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::MultiCell(300, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', '11');
    PDF::MultiCell(140, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, $current_date, 'B', 'L', false, 1);

    //PDF::MultiCell(20, 0, '', '', '', false, 0);
    PDF::MultiCell(300, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', '10');
    PDF::MultiCell(140, 0, 'Sales & Marketing Head', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0, 'Date', '', 'L', false, 1);

    //PDF::MultiCell(550, 0, "\n");
    PDF::SetFont($font, '', $fontsize - 3);
    PDF::MultiCell(20, 0, '', '', '', false, 0);
    PDF::MultiCell(300, 0, '(Not Valid Without Corporate Seal)', '', '', false, 0);

    PDF::SetFont($fontmyriad, '', '8');
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    
    //    PDF::MultiCell(550, 0, "\n");
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(2, 0, '', '', '', false, 0);
    PDF::MultiCell(575, 0, 'This Lifeplan Agreement is issued in consideration of your Application and payment of the First Plan installment, including handling and other charges, and is conditioned on our', '', 'L', false, 1);
    PDF::MultiCell(2, 0, '', '', '', false, 0);
    PDF::MultiCell(577, 0, 'approval of your application. This Agreement, together with any annexes, riders or endorsments duly signed by any of our authorized officers constitutes the entire contract. No', '', 'L', false, 1);
    PDF::MultiCell(2, 0, '', '', '', false, 0);
    PDF::MultiCell(575, 0, 'statement, promise, inducememt made by any person or through any agent, employee or representative not contained herein shall be binding or valid.', '', 'L', false, 1);
     
    //    PDF::MultiCell(550, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::MultiCell(2, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(582, 0, 'This Lifeplan Agreement may not be amended, endorsed or otherwise changed except through a written document signed by the President, Corporate Secretary, or other officers', '', 'L', false, 1);   
    PDF::MultiCell(2, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(575, 0, 'duly authorized by the Company at the time of the amendment. Any such modification must be made in writing and submitted to the Insurance Commission for prior approval. ', '', 'L', false, 1);
    PDF::SetFont($font, '', 10);
    //page 1of 4
    PDF::MultiCell(575, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '40', '770');

    // blank page
    PDF::AddPage('p', [610, 820]);
    PDF::SetMargins(20, 20);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(560, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '40', '10');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(560, 0, '(This Page Intentionally Left Blank)', '', 'B', false, 1, '200', '250');

    PDF::AddPage('p', [610, 820]);    
    //changeimage
    PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '19', '12', 342, 78);
    PDF::SetFont($font, '', 10);
    //PDF::MultiCell(575, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '40', '10');

    PDF::SetFont($font, '', 57);
    
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($font, '', '8');
    PDF::MultiCell(560, 0, '', '', '', false, 1);
    PDF::SetFont($fontbold, '', '14');
    PDF::MultiCell(560, 0, 'TERMS & CONDITIONS', '', 'C', false, 1);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(560, 0, '', '', '', false, 1);

    PDF::SetFont($font, '', 7.8);    
    PDF::MultiCell(575, 0, ' I. DEFINITION OF TERMS', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' o ln this Plan Contract the words "you" or "your` refer to the Planholder named in the Applicaton. The words "we", "us" and "our" or the "Company" refer to Evergreen Lifeplan', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Service, Inc.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "BENEFICIARY" means the person indicated as such in the Applicaton and is the designated recipient of the pre-need benefit.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "CONTRACT PRICE", refers to the stipulated price paid by the Planholder for the purchase of this Plan Contract, net of handling and other charges, if any.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' “GROSS CONTRACT PRICE", refers to the stipulated price paid by the Planholder for the purchase of this Plan Contract including handling and other charges.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "GRACE PERIOD" refers to the sixty (60)-day period counted from the due date of the first unpaid installment within which the Planholder may settle his account. During the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Grace Period, this Plan Contract is still considered in force.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "LAPSED PLAN" refers to a delinquent Plan that has remained unpaid beyond the Grace Period. A Lapsed Plan has no force and effect.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "MEMORIAL SERVICE BENEFIT" refers to your chosen memorial service package in consideration of your full payment of the Gross Contract Price, as described under this Plan ', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Contract.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PAYING PERIOD" refers to the number of years you have to pay for the Gross Contract Price in monthly, quarterly, semi-annual, annual installments or lump sum.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PLAN` means the Life Plan covered by this Plan Contract.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PLAN ANNIVERSARY` refers to a day recurring on the same date as the Plan`s issue date each year.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PLAN INSTALLMENT" refers to the monthly, quarterly, semi-annual, annual amounts paid by the Planholder', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' PLAN TERMINATION VALUE" refers to the amount payable upon the termination of this Plan Contract as indicated in the Plan Termination Value Table attached to this Plan', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Contract.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PLAN VALUE" refers to the value of the benefits which the Company undertakes to administer for the memorial service package which your family may incur by reason of', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Planholder`s death. The Plan Value is the Memorial Service Benefit at the time of death of the Planholder.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "PLANHOLDER" means the person named in the Application for Life Plan who purchases pre-need plans for whom or for whose beneficiaries` benefits are to be delivered, as', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' stipulated and guaranteed by the pre-need company. The term includes the assignee, transferee and any successor-in-interest of the planholder.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' "REINSTATEMENT PERIOD` refers to the two (2)-year period after the end of the Grace Period where a Planholder may re-activate his Lapsed Plan.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' II. ELIGIBILITY', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' If your age was misstated in the application and your true age at that time was beyond the maximum entry age, you or your beneficiary shall not be entitled to any of the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' benefits of the Plan Contract. All your payments shall be refunded accordingly without interest, provided, that you or your beneficiary surrender your Plan Contract and all', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' supporting documents associated with the same. After all the payments made are paid to you or your beneficiary, we shall be discharged from any liability or obligation under', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' this Plan Contract', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' III. PLAN BENEFITS', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1. MEMORIAL SERVICE BENEFIT', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' In consideration of your full payment of the Gross Contract Price, we guarantee, subject to the terms and conditions of this Plan Contract, to arrange and provide for the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' funeral [and cremation] services, casket and such other furnishings for the final rites and burial services, as indicated in this Plan Contract, upon death of the Planholder or his', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' assigns. The administration of such services shall be performed exclusively by the accredited Mortuary [and Crematory hereinatier referred to as "Mortuary"].', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 4);
    PDF::MultiCell(575, 0, '', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 7.8);
    PDF::MultiCell(575, 0, ' 2. PROFESSIONAL ADMINISTRATION OF MEMORIAL SERVICE', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' In case of Planholder`s death, we shall render professional administration of the memorial service package through the Mortuary. PHASE I - FIRST CALL - We will act as the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Planholder`s administrator to see to it that the servicing mortuary personnel shall coordinate with the authorized family representative for the release of the body from the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' place of death, assist in processing of the death certificates from the place of death or attending physician and transport the body to the mortuary.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' PHASE II - PRESERVATION, COSMETICS AND ARMNGEMENT - We will see to', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' it that the servicing mortuary performs the following initial services: proper preservation of the body, restoration of disfigured features, when possible, and application of', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' needed cosmetics on the body and placement of the body in the chosen casket. For Cremation packages, we will see to it that the servicing mortuary performs a solemn and', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' dignified cremation service.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' PHASE III - FINAL ARRANGEMENTS AND VIEWING - We will see to it that the servicing mortuary takes care of the arrangements and provides facilities for the dignified and', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' solemn viewing of the body in the mortuary, church, temple or home. Final consultation with the family will be undertaken on the details of the arrangements.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' PHASE IV - INTERMENT - We will see to it that the funeral cortege leaves on time as scheduled, provide an appropriate coach for the deceased, appropriate music upon', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' request, one appropriate vehicle, if needed, to transport flowers to the interment site, and coordinate activities to the satisfaction of all ethnic and religious groups.', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 4);
    PDF::MultiCell(575, 0, '', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 7.8);
    PDF::MultiCell(575, 0, ' 3. TRANSPORTATION', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' If the Planholder`s death occurs or his body otherwise requires transport within 15 kilometers from Mortuary, we shall provide transport of the body with no additional', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' charge to the Mortuary premises and facilities. lf the aforementioned distance exceeds 15 kilometers, we likewise agree to provide assistance in arranging for the contracted', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' memorial service, provided that any additional expenses for transportation beyond the stated distance shall be borne by the Planholder`s family.', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 4);
    PDF::MultiCell(575, 0, '', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 7.8);
    PDF::MultiCell(575, 0, ' 4. EXTRA SERVICES AND ITEMS NOT INCLUDED', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' We will use all reasonable effort to provide such extra services as may be requested by your family to ensure satisfaction, with the understanding that any additional cost', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' for services not included in this Plan Contract or beyond the Memorial Service benefit shall be borne by the Planholder`s family, heirs, beneficiary/ies, executor/s or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' administrator/s.', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 4);
    PDF::MultiCell(575, 0, '', 'LR', 'L', false, 1);
    PDF::SetFont($font, '', 7.8);
    PDF::MultiCell(575, 0, ' 5. REQUEST FOR RENDITION OF MEMORIAL SERVICE', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' We shall have the sole and exclusive right to make all negotiations and necessary arrangements with Mortuary in connection with the contracted memorial service.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Time being of the essence, it is the responsibility of your family, heirs, beneficiary/ies, executor/s or administrator/s to give immediate notification to us in person or by', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' telephone or other form of communication in order for us to make the necessary arrangements for the rendition of memorial services. lf the Planholder`s family will negotiate', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' directly with another mortuary for the rendition of memorial services, we will consider the plan unrendered in accordance with the provision on Unrendered Services', 'LR', 'L', false, 1);
    
    PDF::MultiCell(575, 0, ' IV. CONTRACT PRICE', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You agree to pay the Contract Price, plus handling and other charges, if any, under this Plan Contract, according to the selected mode of payment on the designated due', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' dates. You may pay at any of our offices or through our authorized representatives or through auto-debit facility without any need of notice or demand. We only honor', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' payments acknowledged by our official receipts. In case death occurs prior to full payment of the Gross Contract Price, the beneficiary may opt to continue to pay the unpaid', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' balance of the Gross Contract Price or surrender this Plan Contract for its Plan Termination Value.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' V. GRACE PERIOD', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You are given a Grace Period of sixty (60) days from due date to pay for any installment due. lf the applicable installment is not paid after said period, this Life Plan shall lapse.', 'LRB', 'L', false, 1);
    // PDF::SetFont($font, '', 7.8);
    // PDF::MultiCell(575, 0, '', 'LRB', 'L', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(560, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '40', '10');

    // page 2 of 2
    PDF::AddPage('p', [610, 820]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(560, 0, '', '', '', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 7.8);
    PDF::MultiCell(575, 0, ' VI. REINSTATEMENT', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You have two (2) years from the end of the grace period to reinstate this Plan Contract. lf you do not reinstate this Plan Contract before the end of the two (2) year', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' reinstatement period despite written notice, this Plan Contract shall be automatically cancelled and all payments made shall be forfeited as liquidated damages To reinstate,', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' you have to submit an application for reinstatement, in a standard Company form provided by us, for our approval.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' All overdue installments are paid with surcharge at a rate prevailing at the time of reinstatement, plus processing fee and other charges, if any.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You may also reinstate this Plan Contract by redating; that is, by paying one current installment at the current rate and terms plus processing fee and other charges, if any.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Any payment by you after the Grace Period shall be reimbursed to you unless you duly reinstate this Plan Contract within the Reinstatement Period and in accordance with', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' this Section.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' VII. BENEFICIARY', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You have the right to change the beneficiary/ies designated in the Application. If you die during the Paying Period, your Beneficiary will have the following options to choose', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' from:', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1.request for the transfer of the Plan in her/her name and after approval of the request for transfer, your Beneficiary may continue paying the balance of the Gross Contract', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Price. Your Beneficiary will be entitled to the memorial service benefit only; or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 2.terminate this Plan and avail of the Plan Termination Value.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' VIII. PLAN TERMINATION VALUE', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' While this Plan Contract is in force, you may surrender this Plan Contract and you will be entitled to Plan Termination Value, if any, as shown in the Plan Termination Value', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Table attached to this Plan Contract. After the Plan Termination Value has been paid to you, we shall be discharged from any liability or obligation in this Plan Contract.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' IX. TRANSFERABILITY', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You may request us to transfer your rights and privileges under this Plan Contract to another person, subject to the following conditions:', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1. you must submit your request in writing, in a company form provided by us, plus a new Application for Life Plan signed by the person to whom it is to be transferred;', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 2. this Plan Contract must be in force at the time of transfer;', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 3. you must pay the appropriate charges, if any; and', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 4. the transfer will be subject to the same terms and conditions of this Plan Contract.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' We are not bound by any transfer of this Plan Contract if it is not recorded and approved at our Head Office or any of our authorized branch offices. We cannot be responsible', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' for the validity or effect of such transfer. The transfer shall be effective only upon our approval and the issuance of a new Plan Contract to the new Planholder.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' X. TRUST FUND', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' We shall deposit with a Trustee Bank all amounts required by the lnsurance Commission. The trust fund shall be administered and maintained in accordance with the trust', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' agreement and the Pre-Need Code.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XI. TERMINATION', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' This Plan Contract shall automatically end:', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1. upon Surrender of this Plan Contract for its Plan Termination Value; or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 2. if this Plan Contract remains lapsed at the end of the Reinstatement Period despite written notice, or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 3. after we have rendered the professional assistance and services as provided in this Plan Contract; or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 4. after we have paid the corresponding amount to your Beneficiary due to unrendered service; or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 5. upon transfer of your Plan Contract to another person.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Any installment received by us after termination of this Plan Contract, shall be refunded to you and shall not create any liability on our part.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XII. FORTUITOUS EVENTS', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' We shall not be liable for any inconvenience, loss, damage, or delay that you may sustain due to fire, flood, earthquake, war, or civil disturbance, extra-ordinary economic', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' upheaval, strikes or labor disputes, acts of God, government legislation or regulation, or such other conditions that are beyond our control in connection with the implementation', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' of our obligation under this Plan Contract. [However, the Unrendered Service Provision shall still apply. - applicable only to traditional plans', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XIII. TAXES AND FEES', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' The Gross Contract Price stated in this Plan Contract does not include any tax or fee which any law or regulation may impose in the future. lf during the effectivity of this Plan', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Contract, the Contract Price or the benefits are subjected to any tax or fee, said tax or fee will be charged to you, or your Beneficiary, as the case may be.', 'LR', 'L', false, 1);
    
    PDF::MultiCell(575, 0, ' XIV. LIMITATION OF ACTION', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' No legal action under this Plan Contract may be filed after five (5) years from the time the cause of action accrues.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XV. ASSIGNABILITY', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' You may assign the privileges and benefits of the memorial services described herein to any deceased third person, subject to the following conditions:', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1. The plan must be up-to-date in the payment of installment. A lapsed plan cannot be assigned.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 2. The assignment shall be in writing duly signed by you, in proper form, and delivered to the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' company or its duly authorized representative for confirmation.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 3. Any insurance coverage provided herein shall automatically terminate.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XVI. WAIVER OF ARTICLE 1250', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' The provision of Article 1250 of the Republic Act No. 386, otherwise known as the Civil Code of the Philippines which states that in case an extraordinary inflation or deflation', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' of the currency stipulated should supervene, the value of the currency at the time of the establishment of the obligation shall be the basis of payment, unless there is a contract', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' to the contrary, shall be waived in determining the extent of benefits or liabilities under this Plan Contract.', 'LR', 'L', false, 1);
    
    PDF::MultiCell(575, 0, ' XVII. JURISDICTION AND VENUE', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' The lnsurance Commission shall have the primary and exclusive power to adjudicate any and all claims involving pre-need plans. lf the amount of pre-need benefits does not', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' exceed One Hundred Thousand Pesos (P100, 000.00), the decision of the lnsurance Commission shall be final and executory.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XVIII. UNRENDERED SERVICE (Applicable only to traditional plans)', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' If we cannot render the memorial service at the time of death of the Planholder, due to circumstances beyond the control of either your family or the Company, or due to the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' circumstances described in the Fortuitous Events Provision, your Beneficiary will have the following options to choose from.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 1. request for the transfer of the Plan in his/her name and after approval of the request for transfer, your Beneficiary may continue paying the balance of the Contract Price.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Your Beneficiary will be entitled to the memorial service benefit only; or', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' 2. terminate this Plan and avail of the Plan Termination Values.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XIX. EXTENDING PROFESSIONAL ASSISTANCE TO RENDER MEMORIAL SERVICES (Applicable onlv to fixed value plans)', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' Upon immediate notification, we will provide professional assistance to any member of your family for free. You will pay directly to the servicing mortuary the total cost of the', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' funeral services before such services are performed.', 'LR', 'L', false, 1);

    PDF::MultiCell(575, 0, ' XX.IMPORTANT NOTICE', 'LRT', 'L', false, 1);
    PDF::MultiCell(575, 0, ' The lnsurance Commission, with offices in Manila, Cebu and Davao, is the government office in charge of the enforcement of all laws related to pre-need and insurance and', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' has supervision over pre-need and insurance companies and intermediaries. lt is ready at all times to assist the general public in matters pertaining to pre-need and insurance.', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' For any inquiries or complaints, please', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' contact the Public Assistance and Mediation Division (PAMD) of the lnsurance Commission at 1071 United Nations Avenue, Manila with telephone numbers +632-8523-8461', 'LR', 'L', false, 1);
    PDF::MultiCell(575, 0, ' to 70 and email address pubassist@insurance.qov.ph. The official website of the lnsurance Commission is www. insurance. gov. ph.', 'LRB', 'L', false, 1);
    
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(560, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '40', '10');
  }

  public function default_CP_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_CP_header_PDF($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function getBeneficiaries($trno)
  {
    return $this->coreFunctions->opentable("select name, relation from beneficiary where trno=" . $trno . " order by line ");
  }
}

// use dejavusans to work
// &#x2713; check
// &#x2611; checkbox w/ check
// &#9744;  checkbox w/o
// &#8369;  peso sign