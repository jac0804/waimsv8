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

class af
{

  private $modulename = "Application Form";

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

  public $tablenum = 'transnum';
  public $head = 'eahead';
  public $hhead = 'heahead';
  public $info = 'eainfo';
  public $hinfo = 'heainfo';

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
    $companyid = $config['params']['companyid'];

    
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select
                  'PDFM' as print,
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->info;
    $hinfo = $this->hinfo;
    $tablenum = $this->tablenum;

    $qryselect = "select 
         num.center,
         num.bref,num.seq,
         head.trno, 
         head.docno,
         date_format(head.dateid,'%d %M %Y') as dateid,
         head.client,
         head.fname,
         head.mname,
         head.lname,
         client.clientname,
         head.ext,
         head.address,
         head.addressno,
         head.street,
         concat(head.subdistown,' ',head.brgy) as subdistown,
         concat(head.city,' ',head.province) as city,
         head.country,
         head.zipcode,
         head.terms,
         head.otherterms,
         head.rem,
         head.voiddate,
         head.yourref,
         head.ourref,
         head.contactno,
         head.contactno2,
         head.email,
         head.vattype,
         head.planid,
         head.tax,

         ifnull(ag.client,'') as agent,
         ifnull(ag.clientname,'') as agentname,
         ifnull(pt.name,'') as plantype,
         ifnull(case info.issenior when 1 then pt.amount/1.12 else pt.amount end,'') as amount,         
         '' as dagentname,
         info.isplanholder,
         info.client as bclient,
         info.clientname as bclientname,
         info.lname as lname2,
         info.fname as fname2,
         info.mname as mname2,
         info.ext as ext2,
         info.gender,
         info.civilstat as civilstatus,

         info.addressno as raddressno,
         info.street as rstreet,
         concat(info.subdistown,' ',info.brgy) as rsubdistown,
         concat(info.city,' ',info.province) as rcity,
         info.country as rcountry,
         info.zipcode as rzipcode,
         info.paddressno,
         info.pstreet,
         concat(info.psubdistown,' ',info.pbrgy) as psubdistown,
         concat(info.pcity,' ',info.pprovince) as pcity,
         info.pcountry,
         info.pzipcode,

         date_format(info.bday,'%m/%d/%Y') as bday,
         info.nationality,
         info.pob,
         info.ispassport,
         info.isdriverlisc,
         info.isprc,
         info.isseniorid,
         info.isotherid,
         info.idno,
         info.expiration,
         info.isemployment,
         info.isbusiness,
         info.isinvestment,
         info.isothersource,
         info.othersource,
         info.isemployed,
         info.isselfemployed,
         info.isofw,
         info.isretired,
         info.iswife,
         info.isnotemployed,
         info.tin,
         info.sssgsis,
         info.lessten,
         info.tenthirty,
         info.thirtyfifty,
         info.fiftyhundred,
         info.hundredtwofifty,
         info.twofiftyfivehundred,
         info.fivehundredup,
         info.employer,
         info.otherplan,
         info.isbene,pg.code as plangrp
         ";


    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $info as info on head.trno = info.trno
        left join client on head.client = client.client
        left join client as ag on ag.client = head.agent
        left join plangrp as pg on pg.line = head.plangrpid
        left join plantype as pt on pt.line = head.planid
        left join terms as terms on terms.terms = head.terms
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $hinfo as info on head.trno = info.trno
        left join client on head.client = client.client
        left join client as ag on ag.client = head.agent
        left join plangrp as pg on pg.line = head.plangrpid
        left join plantype as pt on pt.line = head.planid
        left join terms as terms on terms.terms = head.terms
        where head.trno = ? and num.doc=? and num.center=? ";
    return $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
  } //end fn


  public function reportplotting($params, $data)
  {
    return $this->default_PO_PDF($params, $data);
  }

  public function default_PO_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

    
    
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [615, 790]);
    PDF::SetMargins(40, 40);

    //changeimage
    
    PDF::Image($this->companysetup->getlogopath($params['params']) . 'elsi.png', '18', '12', 342, 78);
  
    PDF::MultiCell(0, 20, "\n");

    $strdocno = $data[0]->bref . $data[0]->seq;
    $strdocno = $this->othersClass->PadJ($strdocno, 10);


    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Application No: ", '', 'R', false, 0, '370',  '35', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(108, 0, $strdocno, 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 0, "Plan Type: ", '', 'R', false, 0, '370', 60, true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(108, 0, (isset($data[0]->plantype) ? $data[0]->plangrp . "-" . $data[0]->plantype : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    


    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 20, "Amount: ", '', 'R', false, 0, '370', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);

    PDF::MultiCell(108, 20, (isset($data[0]->amount) ? number_format(floatval($data[0]->amount), 2) : '0.00'), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 0, "Plan Holder Information", '', 'L', false, 0, '20',  '105', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 0, "Last", '', 'L', false, 0, '20',  '125', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "First", '', 'L', false, 0, '162',  '125', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "Middle", '', 'L', false, 0, '304',  '125', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "Ext.", '', 'L', false, 0, '422',  '125', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(200, 0, "Gender", '', 'L', false, 0, '460',  '130', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "Civil Status ", '', 'L', false, 0, '460',  '148', true, 0, false, true, 0, 'B', true);

    

    PDF::SetCellPadding(2);
    PDF::SetFillColor(232, 239, 217);
    PDF::SetFont($font, '', 10);
    $lname2 = strlen($data[0]->lname2);
    if ($lname2 <= 24) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(135, 0, (isset($data[0]->lname2) ? $data[0]->lname2 : ''), 'TLRB', 'L', true, 0, '20',  '140', true, 0, false, true, 0, 'B', true);
    } else if ($lname2 >= 25 && $lname2 <= 30) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(135, 0, (isset($data[0]->lname2) ? $data[0]->lname2 : ''), 'TLRB', 'L', true, 0, '20',  '140', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetCellPadding(0.5);
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(135, 0, (isset($data[0]->lname2) ? $data[0]->lname2 : ''), 'TLRB', 'L', true, 0, '20',  '140', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);

    $fname2 = strlen($data[0]->fname2);
    if ($fname2 <= 24) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(135, 0, (isset($data[0]->fname2) ? $data[0]->fname2 : ''), 'TLRB', 'L', true, 0, '163',  '140', true, 0, false, true, 0, 'B', true);
    } else if ($fname2 >= 25 && $fname2 <= 30) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(135, 0, (isset($data[0]->fname2) ? $data[0]->fname2 : ''), 'TLRB', 'L', true, 0, '163',  '140', true, 0, false, true, 0, 'B', true);
    } else {
      
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(135, 0, (isset($data[0]->fname2) ? $data[0]->fname2 : ''), 'TLRB', 'L', true, 0, '163',  '140', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);


    $mname2 = strlen($data[0]->mname2);
    if ($mname2 <= 15) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(110, 0, (isset($data[0]->mname2) ? $data[0]->mname2 : ''), 'TLRB', 'L', true, 0, '305',  '140', true, 0, false, true, 0, 'B', true);
    } else if ($mname2 >= 16 && $mname2 <= 24) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(110, 0, (isset($data[0]->mname2) ? $data[0]->mname2 : ''), 'TLRB', 'L', true, 0, '305',  '140', true, 0, false, true, 0, 'B', true);
    } else {
      
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(110, 0, (isset($data[0]->mname2) ? $data[0]->mname2 : ''), 'TLRB', 'L', true, 0, '305',  '140', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);

    PDF::MultiCell(30, 0, (isset($data[0]->ext2) ? $data[0]->ext2 : ''), 'TLRB', 'L', true, 0, '423',  '140', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(70, 0, (isset($data[0]->gender) ? $data[0]->gender : ''), 'B', 'L', false, 0, '515',  '125', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(70, 0, (isset($data[0]->civilstatus) ? $data[0]->civilstatus : ''), 'B', 'L', false, 0, '515',  '145', true, 0, false, true, 0, 'B', true);

    PDF::SetCellPadding(0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 0, "Address (Residence)", '', 'L', false, 0, '20',  '160', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, "No.", '', 'L', false, 0, '20',  '170', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Street", '', 'L', false, 0, '70',  '170', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Subdivision/District/Town", '', 'L', false, 0, '205',  '170', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "City/Province", '', 'L', false, 0, '340',  '170', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "Country", '', 'L', false, 0, '440',  '170', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, "Zip Code", '', 'L', false, 0, '540',  '170', true, 0, false, true, 0, 'B', true);

    PDF::SetCellPadding(2);

    $raddressno = strlen($data[0]->raddressno);
    if ($raddressno <= 13) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(45, 0, (isset($data[0]->raddressno) ? $data[0]->raddressno : ''), 'TLRB', 'L', true, 0, '20',  '182', true, 0, false, true, 0, 'B', true);
    } else if ($raddressno >= 14 && $raddressno <= 15) {
      PDF::SetFont($font, '', 6);
      PDF::MultiCell(45, 0, (isset($data[0]->raddressno) ? $data[0]->raddressno : ''), 'TLRB', 'L', true, 0, '20',  '182', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(45, 0, (isset($data[0]->raddressno) ? $data[0]->raddressno : ''), 'TLRB', 'L', true, 0, '20',  '182', true, 0, false, true, 0, 'B', true);
    }


    $rstreet = strlen($data[0]->rstreet);
    if ($rstreet <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->rstreet) ? $data[0]->rstreet : ''), 'TLRB', 'L', true, 0, '70',  '182', true, 0, false, true, 0, 'B', true);
    } else if ($rstreet >= 35 && $rstreet <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->rstreet) ? $data[0]->rstreet : ''), 'TLRB', 'L', true, 0, '70',  '182', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->rstreet) ? $data[0]->rstreet : ''), 'TLRB', 'L', true, 0, '70',  '182', true, 0, false, true, 0, 'B', true);
    }


    $rsubdistown = strlen($data[0]->rsubdistown);
    if ($rsubdistown <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->rsubdistown) ? $data[0]->rsubdistown : ''), 'TLRB', 'L', true, 0, '205',  '182', true, 0, false, true, 0, 'B', true);
    } else if ($rsubdistown >= 35 && $rsubdistown <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->rsubdistown) ? $data[0]->rsubdistown : ''), 'TLRB', 'L', true, 0, '205',  '182', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->rsubdistown) ? $data[0]->rsubdistown : ''), 'TLRB', 'L', true, 0, '205',  '182', true, 0, false, true, 0, 'B', true);
    }


    $rcity = strlen($data[0]->rcity);
    if ($rcity <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->rcity) ? $data[0]->rcity : ''), 'TLRB', 'L', true, 0, '340',  '182', true, 0, false, true, 0, 'B', true);
    } else if ($rcity >= 25 && $rcity <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->rcity) ? $data[0]->rcity : ''), 'TLRB', 'L', true, 0, '340',  '182', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->rcity) ? $data[0]->rcity : ''), 'TLRB', 'L', true, 0, '340',  '182', true, 0, false, true, 0, 'B', true);
    }


    $rcountry = strlen($data[0]->rcountry);
    if ($rcountry <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->rcountry) ? $data[0]->rcountry : ''), 'TLRB', 'L', true, 0, '440',  '182', true, 0, false, true, 0, 'B', true);
    } else if ($rcountry >= 25 && $rcountry <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->rcountry) ? $data[0]->rcountry : ''), 'TLRB', 'L', true, 0, '440',  '182', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->rcountry) ? $data[0]->rcountry : ''), 'TLRB', 'L', true, 0, '440',  '182', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(45, 0, (isset($data[0]->rzipcode) ? $data[0]->rzipcode : ''), 'TLRB', 'L', true, 0, '540',  '182', true, 0, false, true, 0, 'B', true);
    PDF::SetCellPadding(0);

    // -- permanent address

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 0, "Permanent Address", '', 'L', false, 0, '20',  '202', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, "No.", '', 'L', false, 0, '20',  '212', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Street", '', 'L', false, 0, '70',  '212', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Subdivision/District/Town", '', 'L', false, 0, '205',  '212', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "City/Province", '', 'L', false, 0, '340',  '212', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "Country", '', 'L', false, 0, '440',  '212', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, "Zip Code", '', 'L', false, 0, '540',  '212', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 7);
    PDF::SetCellPadding(2);

    $paddressno = strlen($data[0]->paddressno);
    if ($paddressno <= 13) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(45, 0, (isset($data[0]->paddressno) ? $data[0]->paddressno : ''), 'TLRB', 'L', true, 0, '20',  '224', true, 0, false, true, 0, 'B', true);
    } else if ($paddressno >= 14 && $paddressno <= 15) {
      PDF::SetFont($font, '', 6);
      PDF::MultiCell(45, 0, (isset($data[0]->paddressno) ? $data[0]->paddressno : ''), 'TLRB', 'L', true, 0, '20',  '224', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(45, 0, (isset($data[0]->paddressno) ? $data[0]->paddressno : ''), 'TLRB', 'L', true, 0, '20',  '224', true, 0, false, true, 0, 'B', true);
    }



    $pstreet = strlen($data[0]->pstreet);
    if ($pstreet <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->pstreet) ? $data[0]->pstreet : ''), 'TLRB', 'L', true, 0, '70',  '224', true, 0, false, true, 0, 'B', true);
    } else if ($pstreet >= 35 && $pstreet <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->pstreet) ? $data[0]->pstreet : ''), 'TLRB', 'L', true, 0, '70',  '224', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->pstreet) ? $data[0]->pstreet : ''), 'TLRB', 'L', true, 0, '70',  '224', true, 0, false, true, 0, 'B', true);
    }

    $psubdistown = strlen($data[0]->psubdistown);
    if ($psubdistown <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->psubdistown) ? $data[0]->psubdistown : ''), 'TLRB', 'L', true, 0, '205',  '224', true, 0, false, true, 0, 'B', true);
    } else if ($psubdistown >= 35 && $psubdistown <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->psubdistown) ? $data[0]->psubdistown : ''), 'TLRB', 'L', true, 0, '205',  '224', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->psubdistown) ? $data[0]->psubdistown : ''), 'TLRB', 'L', true, 0, '205',  '224', true, 0, false, true, 0, 'B', true);
    }

    $pcity = strlen($data[0]->pcity);
    if ($pcity <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->pcity) ? $data[0]->pcity : ''), 'TLRB', 'L', true, 0, '340',  '224', true, 0, false, true, 0, 'B', true);
    } else if ($pcity >= 25 && $pcity <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->pcity) ? $data[0]->pcity : ''), 'TLRB', 'L', true, 0, '340',  '224', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->pcity) ? $data[0]->pcity : ''), 'TLRB', 'L', true, 0, '340',  '224', true, 0, false, true, 0, 'B', true);
    }

    $pcountry = strlen($data[0]->pcountry);
    if ($pcountry <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->pcountry) ? $data[0]->pcountry : ''), 'TLRB', 'L', true, 0, '440',  '224', true, 0, false, true, 0, 'B', true);
    } else if ($pcountry >= 25 && $pcountry <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->pcountry) ? $data[0]->pcountry : ''), 'TLRB', 'L', true, 0, '440',  '224', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->pcountry) ? $data[0]->pcountry : ''), 'TLRB', 'L', true, 0, '440',  '224', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(45, 0, (isset($data[0]->pzipcode) ? $data[0]->pzipcode : ''), 'TLRB', 'L', true, 0, '540',  '224', true, 0, false, true, 0, 'B', true);
    PDF::SetCellPadding(0);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(100, 0, "Date of Birth", '', 'L', false, 0, '20',  '245', true, 0, false, true, 0, 'T', false);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(150, 0, "Place of Birth", '', 'L', false, 0, '105',  '245', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 0, "Natonality/Citizenship", '', 'L', false, 0, '240',  '245', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 0, "Govt. I.D. (with picture)", '', 'L', false, 0, '355',  '243', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::SetCellPadding(2);
    PDF::MultiCell(80, 0, (isset($data[0]->bday) ? $data[0]->bday : ''), 'TLRB', 'L', true, 0, '20',  '260', true, 0, false, true, 0, 'B', true);
    $pob = strlen($data[0]->pob);
    if ($pob <= 25) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(130, 0, (isset($data[0]->pob) ? $data[0]->pob : ''), 'TLRB', 'L', true, 0, '105',  '260', true, 0, false, true, 0, 'B', true);
    } else if ($pob >= 26 && $pob <= 35) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(130, 0, (isset($data[0]->pob) ? $data[0]->pob : ''), 'TLRB', 'L', true, 0, '105',  '260', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetCellPadding(0.5);
      PDF::SetFont($font, '', 7.5);
      PDF::MultiCell(130, 0, (isset($data[0]->pob) ? $data[0]->pob : ''), 'TLRB', 'L', true, 0, '105',  '260', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(110, 0, (isset($data[0]->nationality) ? $data[0]->nationality : ''), 'TLRB', 'L', true, 0, '240',  '260', true, 0, false, true, 0, 'B', true);

    $p1 = $p2 = $p3 = $p4 = "&#x2610;"; // uncheck

    if ($data[0]->ispassport == 1) {
      $p1 = "&#x2611;";
    }

    if ($data[0]->isprc == 1) {
      $p2 = "&#x2611;";
    }

    if ($data[0]->isdriverlisc == 1) {
      $p3 = "&#x2611;";
    }

    if ($data[0]->isotherid == 1 || $data[0]->isseniorid == 1) {
      $p4 = "&#x2611;";
    }

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '355', '255', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Passport", '', 'L', false, 1, '365', '257', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '435', '255', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "PRC", '', 'L', false, 1, '445', '256', true, 0, true);
    PDF::MultiCell(100, 25, "Number/Type:", '', 'L', false, 1, '480', '257', true, 0, true);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(60, 0, $data[0]->idno, 'B', 'L', false, 1, '530', '253', true, 0, true);


    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '355', '270', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Driver License", '', 'L', false, 1, '365', '272', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p4, '', 'L', false, 1, '435', '270', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Other", '', 'L', false, 1, '445', '272', true, 0, true);
    PDF::MultiCell(100, 25, "Expiration:", '', 'L', false, 1, '480', '272', true, 0, true);
    PDF::MultiCell(60, 0,  $data[0]->expiration, 'B', 'L', false, 1, '530', '269', true, 0, true);

    PDF::MultiCell(240, 35, "", 'TLRB', 'L', false, 0, '354',  '254', true, 0, false, true, 0, 'B', true); // box

    // --> source of income
    $p1 = $p2 = $p3 = $p4 = "&#x2610;"; // uncheck

    if ($data[0]->isemployment == 1) {
      $p1 = "&#x2611;";
    }

    if ($data[0]->isbusiness == 1) {
      $p2 = "&#x2611;";
    }

    if ($data[0]->isinvestment == 1) {
      $p3 = "&#x2611;";
    }

    if ($data[0]->isothersource == 1) {
      $p4 = "&#x2611;";
    }

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 0, "Source of Income", '', 'L', false, 0, '20',  '278', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(175, 35, "", 'TLRB', 'L', false, 0, '20',  '293', true, 0, false, true, 0, 'B', true); // box

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '20', '295', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Employment", '', 'L', false, 1, '30', '297', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '80', '295', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Investment/Pension", '', 'L', false, 1, '90', '297', true, 0, true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '20', '310', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Business", '', 'L', false, 1, '30', '312', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p4, '', 'L', false, 1, '80', '310', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Other (Specify):", '', 'L', false, 1, '90', '312', true, 0, true);

    PDF::MultiCell(40, 0,  $data[0]->othersource, 'B', 'L', false, 1, '149', '310', true, 0, true);

    // --> Occupation

    $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = "&#x2610;"; // uncheck

    if ($data[0]->isemployed == 1) {
      $p1 = "&#x2611;";
    }

    if ($data[0]->isofw == 1) {
      $p2 = "&#x2611;";
    }

    if ($data[0]->iswife == 1) {
      $p3 = "&#x2611;";
    }

    if ($data[0]->isselfemployed == 1) {
      $p4 = "&#x2611;";
    }

    if ($data[0]->isretired == 1) {
      $p5 = "&#x2611;";
    }

    if ($data[0]->isnotemployed == 1) {
      $p6 = "&#x2611;";
    }

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 0, "Occupation", '', 'L', false, 0, '205',  '278', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 35, "", 'TLRB', 'L', false, 0, '200',  '293', true, 0, false, true, 0, 'B', true); // box

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '200', '295', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Employed", '', 'L', false, 1, '210', '297', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '273', '295', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "OFW", '', 'L', false, 1, '283', '297', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '360', '295', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(80, 0, "Stay-at-Home/Spouse/ Housewife", '', 'L', false, 1, '370', '293', true, 0, true, true, 0, 'T', true);


    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p4, '', 'L', false, 1, '200', '310', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(80, 25, "Self-Employed", '', 'L', false, 1, '210', '312', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p5, '', 'L', false, 1, '273', '310', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Retired/Pensioner", '', 'L', false, 1, '283', '312', true, 0, true);
    

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p6, '', 'L', false, 1, '360', '310', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(80, 25, "Not Employed/Student", '', 'L', false, 1, '370', '313', true, 0, true);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(85, 0, "Tax Payer Number(TIN):", '', 'L', false, 0, '450',  '297', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(70, 0, (isset($data[0]->tin) ? $data[0]->tin : ''), 'B', 'L', false, 1, '528',  '295', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(60, 0, "SSS/GSIS #:", '', 'L', false, 0, '450',  '312', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(93, 0, (isset($data[0]->sssgsis) ? $data[0]->sssgsis : ''), 'B', 'L', false, 1, '500',  '310', true, 0, false, true, 0, 'B', true);

    // Monthly Income

    $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = $p7 = "&#x2610;"; // uncheck

    if ($data[0]->lessten == 1) {
      $p1 = "&#x2611;";
    }

    if ($data[0]->fiftyhundred == 1) {
      $p2 = "&#x2611;";
    }

    if ($data[0]->tenthirty == 1) {
      $p3 = "&#x2611;";
    }

    if ($data[0]->hundredtwofifty == 1) {
      $p4 = "&#x2611;";
    }

    if ($data[0]->thirtyfifty == 1) {
      $p5 = "&#x2611;";
    }

    if ($data[0]->twofiftyfivehundred == 1) {
      $p6 = "&#x2611;";
    }

    if ($data[0]->fivehundredup == 1) {
      $p7 = "&#x2611;";
    }


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 0, "Monthly Income", '', 'C', false, 0, '20',  '330', true, 0, false, true, 0, 'B', true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '20', '345', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Less than &#8369;10,000", '', 'L', false, 1, '33', '347', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '115', '345', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "&#8369;50,001 - &#8369;100,000", '', 'L', false, 1, '128', '347', true, 0, true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '20', '360', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "&#8369;10,001 - &#8369;30,000", '', 'L', false, 1, '33', '362', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p4, '', 'L', false, 1, '115', '360', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "&#8369;100,001 - &#8369;250,000", '', 'L', false, 1, '128', '362', true, 0, true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p5, '', 'L', false, 1, '20', '375', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "&#8369;30,001 - &#8369;50,000", '', 'L', false, 1, '33', '377', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p6, '', 'L', false, 1, '115', '375', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "&#8369;250,001 - &#8369;500,000", '', 'L', false, 1, '128', '377', true, 0, true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p7, '', 'L', false, 1, '115', '390', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "More than &#8369;500,001", '', 'L', false, 1, '128', '392', true, 0, true);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "Employer Name", '', 'L', false, 0, '210',  '330', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(250, 0, " (if employed)/Business Name (if self-employed):", '', 'L', false, 0, '290',  '330', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, (isset($data[0]->employer) ? $data[0]->employer : ''), 'B', 'L', false, 0, '490',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(380, 0, "", 'B', 'L', false, 0, '210',  '350', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(380, 0, "Does Planholder Have Other Life Or Death Insurance? If Yes, please specify.", '', 'L', false, 0, '210',  '370', true, 0, false, true, 0, 'B', true);
    

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(380, 20, (isset($data[0]->otherplan) ? $data[0]->otherplan : ''), 'TLRB', 'L', true, 0, '210',  '385', true, 0, false, true, 0, 'C', true);

    PDF::SetLineStyle(array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    PDF::MultiCell(575, 20, '', 'T', 'L', false, 0, '20',  '415', true, 0, false, true, 0, 'C', true);
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 0, "Payor Information", '', 'L', false, 0, '20',  '420', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 0, "Payor Name (if different than Planholder)", '', 'L', false, 0, '20',  '435', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 0, "Last", '', 'L', false, 0, '20',  '447', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "First", '', 'L', false, 0, '162',  '447', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "Middle", '', 'L', false, 0, '304',  '447', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, "Ext.", '', 'L', false, 0, '422',  '447', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    

    $lname = strlen($data[0]->lname);
    if ($lname <= 24) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(135, 0, (isset($data[0]->lname) ? $data[0]->lname : ''), 'TLRB', 'L', true, 0, '20',  '462', true, 0, false, true, 0, 'B', true);
    } else if ($lname >= 25 && $lname <= 30) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(135, 0, (isset($data[0]->lname) ? $data[0]->lname : ''), 'TLRB', 'L', true, 0, '20',  '462', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetCellPadding(0.5);
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(135, 0, (isset($data[0]->lname) ? $data[0]->lname : ''), 'TLRB', 'L', true, 0, '20',  '462', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);

    

    $fname = strlen($data[0]->fname);
    if ($fname <= 24) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(135, 0, (isset($data[0]->fname) ? $data[0]->fname : ''), 'TLRB', 'L', true, 0, '163',  '462', true, 0, false, true, 0, 'B', true);
    } else if ($fname >= 25 && $fname <= 30) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(135, 0, (isset($data[0]->fname) ? $data[0]->fname : ''), 'TLRB', 'L', true, 0, '163',  '462', true, 0, false, true, 0, 'B', true);
    } else {
      
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(135, 0, (isset($data[0]->fname) ? $data[0]->fname : ''), 'TLRB', 'L', true, 0, '163',  '462', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);

    
    $mname = strlen($data[0]->mname);
    if ($mname <= 15) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(110, 0, (isset($data[0]->mname) ? $data[0]->mname : ''), 'TLRB', 'L', true, 0, '305',  '462', true, 0, false, true, 0, 'B', true);
    } else if ($mname >= 16 && $mname <= 24) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(110, 0, (isset($data[0]->mname) ? $data[0]->mname : ''), 'TLRB', 'L', true, 0, '305',  '462', true, 0, false, true, 0, 'B', true);
    } else {
      
      PDF::SetFont($font, '', 7.8);
      PDF::MultiCell(110, 0, (isset($data[0]->mname) ? $data[0]->mname : ''), 'TLRB', 'L', true, 0, '305',  '462', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetCellPadding(2);
    PDF::SetFont($font, '', 10);


    PDF::MultiCell(30, 0, (isset($data[0]->ext) ? $data[0]->ext : ''), 'TLRB', 'L', true, 0, '423',  '462', true, 0, false, true, 0, 'B', true);


    PDF::SetCellPadding(0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 0, "Billing Address", '', 'L', false, 0, '20',  '485', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, "No.", '', 'L', false, 0, '20',  '495', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Street", '', 'L', false, 0, '70',  '495', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 0, "Subdivision/District/Town", '', 'L', false, 0, '205',  '495', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "City/Province", '', 'L', false, 0, '340',  '495', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "Country", '', 'L', false, 0, '440',  '495', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, "Zip Code", '', 'L', false, 0, '540',  '495', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 7);
    PDF::SetCellPadding(2);
    
    $addressno = strlen($data[0]->addressno);
    if ($addressno <= 13) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(45, 0, (isset($data[0]->addressno) ? $data[0]->addressno : ''), 'TLRB', 'L', true, 0, '20',  '510', true, 0, false, true, 0, 'B', true);
    } else if ($addressno >= 14 && $addressno <= 15) {
      PDF::SetFont($font, '', 6);
      PDF::MultiCell(45, 0, (isset($data[0]->addressno) ? $data[0]->addressno : ''), 'TLRB', 'L', true, 0, '20',  '510', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(45, 0, (isset($data[0]->addressno) ? $data[0]->addressno : ''), 'TLRB', 'L', true, 0, '20',  '510', true, 0, false, true, 0, 'B', true);
    }



    

    $street = strlen($data[0]->street);
    if ($street <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->street) ? $data[0]->street : ''), 'TLRB', 'L', true, 0, '70',  '510', true, 0, false, true, 0, 'B', true);
    } else if ($street >= 35 && $street <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->street) ? $data[0]->street : ''), 'TLRB', 'L', true, 0, '70',  '510', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->street) ? $data[0]->street : ''), 'TLRB', 'L', true, 0, '70',  '510', true, 0, false, true, 0, 'B', true);
    }



    

    $subdistown = strlen($data[0]->subdistown);
    if ($subdistown <= 34) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(130, 0, (isset($data[0]->subdistown) ? $data[0]->subdistown : ''), 'TLRB', 'L', true, 0, '205',  '510', true, 0, false, true, 0, 'B', true);
    } else if ($subdistown >= 35 && $subdistown <= 41) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(130, 0, (isset($data[0]->subdistown) ? $data[0]->subdistown : ''), 'TLRB', 'L', true, 0, '205',  '510', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(130, 0, (isset($data[0]->subdistown) ? $data[0]->subdistown : ''), 'TLRB', 'L', true, 0, '205',  '510', true, 0, false, true, 0, 'B', true);
    }


    
    $city = strlen($data[0]->city);
    if ($city <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->city) ? $data[0]->city : ''), 'TLRB', 'L', true, 0, '340',  '510', true, 0, false, true, 0, 'B', true);
    } else if ($city >= 25 && $city <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->city) ? $data[0]->city : ''), 'TLRB', 'L', true, 0, '340',  '510', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->city) ? $data[0]->city : ''), 'TLRB', 'L', true, 0, '340',  '510', true, 0, false, true, 0, 'B', true);
    }

    

    $country = strlen($data[0]->country);
    if ($country <= 24) {
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(95, 0, (isset($data[0]->country) ? $data[0]->country : ''), 'TLRB', 'L', true, 0, '440',  '510', true, 0, false, true, 0, 'B', true);
    } else if ($country >= 25 && $country <= 30) {
      PDF::SetFont($font, '', 7);
      PDF::MultiCell(95, 0, (isset($data[0]->country) ? $data[0]->country : ''), 'TLRB', 'L', true, 0, '440',  '510', true, 0, false, true, 0, 'B', true);
    } else {
      PDF::SetFont($font, '', 5.6);
      PDF::MultiCell(95, 0, (isset($data[0]->country) ? $data[0]->country : ''), 'TLRB', 'L', true, 0, '440',  '510', true, 0, false, true, 0, 'B', true);
    }
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(45, 0, (isset($data[0]->zipcode) ? $data[0]->zipcode : ''), 'TLRB', 'L', true, 0, '540',  '510', true, 0, false, true, 0, 'B', true);
    PDF::SetCellPadding(0);

    $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = "&#x2610;"; // uncheck

    if ($data[0]->terms == 'Monthly') {
      $p1 = "&#x2611;";
    } else if ($data[0]->terms == 'Upfront 3 %') {
      $p2 = "&#x2611;";
    } else if ($data[0]->terms == 'Annual') {
      $p3 = "&#x2611;";
    } else if ($data[0]->terms == 'Quarterly') {
      $p4 = "&#x2611;";
    } else if ($data[0]->terms == 'Semi-Annual') {
      $p5 = "&#x2611;";
    } else {
      $p6 = "&#x2611;";
    }


    PDF::SetCellPadding(0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, "Payment Terms", '', 'L', false, 0, '20',  '530', true, 0, false, true, 0, 'B', true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '20', '540', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Monthly", '', 'L', false, 1, '30', '542', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '75', '542', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Upfront 3 %", '', 'L', false, 1, '85', '542', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '145', '542', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Yearly", '', 'L', false, 1, '155', '542', true, 0, true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p4, '', 'L', false, 1, '20', '555', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Quarterly", '', 'L', false, 1, '30', '557', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p5, '', 'L', false, 1, '75', '555', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Semi-Annual", '', 'L', false, 1, '85', '557', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p6, '', 'L', false, 1, '145', '555', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Other (specify)", '', 'L', false, 1, '155', '557', true, 0, true);

    // --> Method 

    $p1 = $p2 = $p3 =  "&#x2610;"; // uncheck

    if ($data[0]->yourref == 'Cash') {
      $p1 = "&#x2611;";
    } else if ($data[0]->yourref == 'Online') {
      $p2 = "&#x2611;";
    } else {
      $p3 = "&#x2611;";
    }

    PDF::SetCellPadding(0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, "Method", '', 'L', false, 0, '230',  '530', true, 0, false, true, 0, 'B', true);

    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p1, '', 'L', false, 1, '230', '540', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Cash", '', 'L', false, 1, '240', '542', true, 0, true);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p2, '', 'L', false, 1, '285', '542', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Online Banking", '', 'L', false, 1, '295', '542', true, 0, true);


    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(100, 25, $p3, '', 'L', false, 1, '230', '555', true, 0, true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(100, 25, "Cheque", '', 'L', false, 1, '240', '557', true, 0, true);

    
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(150, 0, 'Primary Contact Number:', '', 'R', false, 1, '330',  '542', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(105, 0, $data[0]->contactno, 'B', 'L', false, 1, '485',  '542', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(150, 0, 'Alternative Contact Number:', '', 'R', false, 1, '330',  '557', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(105, 0, $data[0]->contactno2, 'B', 'L', false, 1, '485',  '557', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(150, 0, 'Email:', '', 'R', false, 1, '330',  '580', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(105, 0, $data[0]->email, 'B', 'L', false, 1, '485',  '580', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(150, 0, 'Specify Other Payment Terms:', '', 'L', false, 1, '20',  '580', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 0, $data[0]->otherterms, 'B', 'L', false, 1, '150',  '580', true, 0, false, true, 0, 'B', true);

    
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    PDF::MultiCell(575, 20, '', 'T', 'L', false, 0, '20',  '610', true, 0, false, true, 0, 'C', true);
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));

    // -- >  beneficiaries
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 0, "Beneficiaries", '', 'L', false, 0, '20',  '615', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "", '', 'L', false, 1, '',  '620', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, "Name(s)", '', 'L', false, 0, '20',  '630', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(40, 0, "Age", '', 'L', false, 0, '150',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(270, 0, "Address", '', 'L', false, 0, '200',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 0, "Relationship", '', 'L', false, 1, '480',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(150, 0, "", '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    $recordcount = 1;
    $maxrow = 1;

    if ($data[0]->isbene == 0) {
      $benedata = $this->coreFunctions->opentable("select name, age, address, relation from beneficiary where trno = ?", [$data[0]->trno]);

      if (count($benedata) != 0) {
        foreach ($benedata as $key => $value) {
          $height = 16;
          $name = $this->reporter->fixcolumn([$value->name], '25');
          $age = $this->reporter->fixcolumn([$value->age], '5');
          $address = $this->reporter->fixcolumn([$value->address], '75');
          $relation = $this->reporter->fixcolumn([$value->relation], '20');

          $maxrow = $this->othersClass->getmaxcolumn([$name, $age, $address, $relation]);

          $height =  $maxrow * $height;
          $this->coreFunctions->LogConsole($maxrow);

          PDF::SetFont($font, '', 9);
          PDF::MultiCell(30,  $height, $recordcount . ".) ", '', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(100,  $height, $value->name, 'B', 'L', false, 0, '35',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(40,  $height, $value->age, 'B', 'L', false, 0, '150',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 8);
          PDF::MultiCell(270,  $height, $value->address, 'B', 'L', false, 0, '200',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 9);
          PDF::MultiCell(100,  $height, $value->relation, 'B', 'L', false, 1, '480',  '', true, 0, false, true, 0, 'B', true);
          $recordcount += 1;
        } // end foreach
      } else {
        $height = 16;
        $name = 'NOT APPLICABLE';
        $fname = $this->reporter->fixcolumn([$name], '20');

        $maxrow = $this->othersClass->getmaxcolumn([$fname]);

        $height =  $maxrow * $height;

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(30,  $height, $recordcount . ".) ", '', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100,  $height, 'NOT APPLICABLE', 'B', 'L', false, 0, '35',  '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(40,  $height, '', 'B', 'L', false, 0, '150',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(270,  $height, '', 'B', 'L', false, 0, '200',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100,  $height, '', 'B', 'L', false, 1, '480',  '', true, 0, false, true, 0, 'B', true);
      }
    } else {
      $height = 16;
      $name = 'NOT APPLICABLE';
      $fname = $this->reporter->fixcolumn([$name], '20');

      $maxrow = $this->othersClass->getmaxcolumn([$fname]);

      $height =  $maxrow * $height;

      PDF::SetFont($font, '', 9);
      PDF::MultiCell(30,  $height, $recordcount . ".) ", '', 'L', false, 0, '20',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(100,  $height, 'NOT APPLICABLE', 'B', 'L', false, 0, '35',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(40,  $height, '', 'B', 'L', false, 0, '150',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 8);
      PDF::MultiCell(270,  $height, '', 'B', 'L', false, 0, '200',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(100,  $height, '', 'B', 'L', false, 1, '480',  '', true, 0, false, true, 0, 'B', true);
    }

    
  } // end fn

  public function default_PO_PDF($params, $data)
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
    $this->default_PO_header_PDF($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}

// use dejavusans to work
// &#x2713; check
// &#x2611; checkbox w/ check
// &#9744;  checkbox w/o
// &#8369;  peso sign
