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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pm
{
  private $modulename = "Project Management";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$username' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "select head.trno,head.docno,head.client,client.clientname,client.addr,proj.code as projcode,proj.name as projname,
                head.tcp,head.cost,left(head.dateid,10) as dateid,head.due,head.completed as hcompleted,head.retention,head.dp,head.rem,
                head.clientname,head.address,head.wh,head.projectid,
                subproj.line,subproj.subproject,subproj.projpercent,subproj.completed as scompleted,head.closedate
            from pmhead as head
            left join subproject as subproj on subproj.trno = head.trno
            left join client on client.client=head.client
            left join projectmasterfile as proj on proj.line = head.projectid
            where head.trno = $trno
            union all
            select head.trno,head.docno,head.client,client.clientname,client.addr,proj.code as projcode,proj.name as projname,
                head.tcp,head.cost,left(head.dateid,10) as dateid,head.due,head.completed as hcompleted,head.retention,head.dp,head.rem,
                head.clientname,head.address,head.wh,head.projectid,
                subproj.line,subproj.subproject,subproj.projpercent,subproj.completed as scompleted,head.closedate
            from hpmhead as head
            left join subproject as subproj on subproj.trno = head.trno
            left join client on client.client=head.client
            left join projectmasterfile as proj on proj.line = head.projectid
            where head.trno = $trno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_pm_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_PM_PDF($params, $data);
    }
  }

  private function rpt_default_header($data, $filters)
  {
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT MANAGEMENT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['addr']) ? $data[0]['addr'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('CLOSING DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['closedate']) ? $data[0]['closedate'] : ''), '160', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['wh']) ? $data[0]['wh'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('COMPLETED : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['hcompleted']) ? $data[0]['hcompleted'] : ''), '160', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['projname']) ? $data[0]['projname'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('RETENTION : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['retention']) ? $data[0]['retention'] : ''), '160', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CONTACT PRICE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['tcp']) ? $data[0]['tcp'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('NOTES : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '160', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ESTIMATE COST : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['cost']) ? $data[0]['cost'] : ''), '510', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PLU', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R P T I O N', '500px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('COST', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_pm_layout($filters, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    $totalext = 0;

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


  public function default_PM_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,email from center where code = '" . $center . "'";
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
    PDF::AddPage('l', [1200, 1000]);

    $this->reportheader->getheader($params);
    PDF::MultiCell(0, 0, "\n");
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(800, 0, $this->modulename, '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(250, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 10, "", '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Total Contract Price: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, (isset($data[0]['tcp']) ? number_format($data[0]['tcp'], 2) : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Closing Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['closedate']) ? $data[0]['closedate'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Estimate Cost: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, (isset($data[0]['cost']) ? number_format($data[0]['cost'], 2) : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, (isset($data[0]['wh']) ? $data[0]['wh'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Completed: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (isset($data[0]['hcompleted']) ? $data[0]['hcompleted'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Project: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, (isset($data[0]['projname']) ? $data[0]['projname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Retention: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (isset($data[0]['retention']) ? $data[0]['retention'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n\n\n");
  }

  public function default_PM_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 970;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px dotted ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetMargins(40, 40);
    $this->default_PM_header_PDF($params, $data);

    $countarr = 0;
    for ($i = 0; $i < count($data); $i++) {
      PDF::MultiCell(0, 15, "Page" . PDF::PageNo() . " of " . PDF::getAliasNbPages(), '', 'R', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(1100, 0, '', 'TLR');

      PDF::SetFont($fontbold, '', 15);
      PDF::MultiCell(1100, 0, ' SUBPROJECT: ', 'LR', 'L', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(1100, 0, '', 'LR');

      PDF::SetFont($fontbold, '', 13);
      PDF::MultiCell(550, 20, ' SUBPROJECT', 'TLRB', 'L', false, 0, '', '', true, 1);
      PDF::MultiCell(275, 20, '% PROJECT', 'TRB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(275, 20, '% COMPLETED (AP)', 'TLRB', 'C', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(550, 0, '', 'LR', 'L', false, 0, '', '', true, 1);
      PDF::MultiCell(275, 0, '', 'R', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(275, 0, '', 'LR', 'C', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(550, 10, ' ' . $data[$i]['subproject'], 'LR', 'L', false, 0, '', '', true, 1);
      PDF::MultiCell(275, 10, $data[$i]['projpercent'], 'R', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(275, 10, $data[$i]['scompleted'], 'LR', 'C', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(550, 0, '', 'LRB', 'L', false, 0, '', '', true, 1);
      PDF::MultiCell(275, 0, '', 'RB', 'C', false, 0, '', '', false, 1);
      PDF::MultiCell(275, 0, '', 'LRB', 'C', false);

      $line = $data[$i]['line'];
      $projid = $data[$i]['projectid'];
      $stages = "select s.line,sm.stage as stages ,sm.description,s.projpercent as percentstage,s.cost as estcost, 
                        s.projectprice,s.ar, s.ap,s.boq,s.pr, s.po,s.rr,s.jr as jor,s.jo, s.jc, 
                        s.mi as totalissued,s.completed as completedap, s.completedar,sm.line as smline, s.subproject
                  from stages as s
                  left join stagesmasterfile as sm on sm.line=s.stage
                  where s.projectid = $projid and s.subproject = $line";

      $stagesresult = json_decode(json_encode($this->coreFunctions->opentable($stages)), true);

      for ($a = 0; $a < count($stagesresult); $a++) {
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(1100, 0, '', 'TLR');

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(1100, 0, ' STAGES: ', 'LR', 'L', false);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(1100, 0, '', 'LR');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, ' STAGES: ', 'L', 'L', false, 0, '', '', true, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(450, 0, $stagesresult[$a]['stages'], '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, 'AR AMOUNT: ', '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['ar'], 2), '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'TOTAL PO: ', '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['po'], 2), '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 0, '% COMPLETED (AP): ', '', 'L', false, 0, '', '', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, $stagesresult[$a]['completedap'], 'R', 'L', false, 1, '', '', false, 0);


        if ($stagesresult[$a]['description'] != '') {
          $description = $this->reporter->fixcolumn([$stagesresult[$a]['description']], 80);
          $countdescription = count($description);
          $totalap = $this->reporter->fixcolumn([$stagesresult[$a]['ap']], 50);
          $counttotalap = count($totalap);
          $totalar = $this->reporter->fixcolumn([$stagesresult[$a]['rr']], 50);
          $counttotalar = count($totalar);
          $completedar = $this->reporter->fixcolumn([$stagesresult[$a]['completedar']], 50);
          $countcompletedar = count($completedar);

          $maxrow = max($countdescription, $counttotalap, $counttotalar, $countcompletedar);
          for ($r = 0; $r < $maxrow - 1; $r++) {
            if ($r == 0) {
              $desclabel = ' DESCRIPTION: ';
              $aplabel = 'AP AMOUNT: ';
              $arlabel = 'TOTAL RR: ';
              $comparlabel = '% COMPLETED (AR): ';
            } else {
              $desclabel = '';
              $aplabel = '';
              $arlabel = '';
              $comparlabel = '';
            }

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(100, 0, $desclabel, 'L', 'L', false, 0, '', '', true, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(450, 0, isset($description[$r]) ? ' ' . $description[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(100, 0, $aplabel, '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, isset($totalap[$r]) ? ' ' . $totalap[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(80, 0, $arlabel, '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, isset($totalar[$r]) ? ' ' . $totalar[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 0, $comparlabel, '', 'L', false, 0, '', '', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, isset($completedar[$r]) ? ' ' . $completedar[$r] : '', 'R', 'L', false, 1, '', '', false, 1);
          }
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, ' % STAGE: ', 'L', 'L', false, 0, '', '', true, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(450, 0, $stagesresult[$a]['percentstage'], '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, 'TOTAL BOQ (Qty): ', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['boq'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'TOTAL JOR: ', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['jor'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(120, 0, '', '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', 'R', 'L', false, 1, '', '', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, ' ESTIMATED COST: ', 'L', 'L', false, 0, '', '', true, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(450, 0, number_format($stagesresult[$a]['estcost'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, 'TOTAL PR (Qty): ', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['pr'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'TOTAL JC: ', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['jc'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(120, 0, '', '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', 'R', 'L', false, 1, '', '', false, 1);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 0, ' PROJECT PRICE: ', 'L', 'L', false, 0, '', '', true, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(450, 0, number_format($stagesresult[$a]['projectprice'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'TOTAL ISSUED: ', '', 'L', false, 0, '', '', false, 1);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, number_format($stagesresult[$a]['totalissued'], 2), '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(120, 0, '', '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, '', 'R', 'L', false, 1, '', '', false, 1);

        PDF::SetFont($font, '', 10);
        PDF::MultiCell(1100, 0, '', 'LR');

        $actline = $stagesresult[$a]['smline'];
        $subproject = $stagesresult[$a]['subproject'];
        $activity = "select a.line, a.substage from substages as a
                     left join activity as act on act.line=a.line
                     where a.stage = $actline and act.subproject = $subproject";


        $activityresult = json_decode(json_encode($this->coreFunctions->opentable($activity)), true);

        for ($b = 0; $b < count($activityresult); $b++) {
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(1100, 0, '', 'TLR');

          PDF::SetFont($fontbold, '', 15);
          PDF::MultiCell(100, 10, ' ACTIVITY: ', 'L', 'L', false, 0, '', '', true, 1);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(1000, 0, ' ' . $activityresult[$b]['substage'], 'R', 'L', false);

          PDF::SetFont($font, '', 10);
          PDF::MultiCell(1100, 0, '', 'BLR');

          $subactline = $activityresult[$b]['line'];

          $subproj = $stagesresult[$a]['subproject'];
          $stage = $stagesresult[$a]['smline'];
          $trno = $data[$i]['trno'];

          $subactivity = "select sub.line,psub.subactid,sub.subactivity, sub.description,
                                  psub.rrqty,psub.qty,psub.uom,psub.rrcost, psub.ext, psub.cost,psub.totalcost
                          from subactivity as sub
                          left join psubactivity as psub on psub.line=sub.line
                          where trno=$trno and psub.substage = $subactline and 
                          psub.stage = $stage and subproject = $subproj and psub.rrqty <> 0 and uom <> ''";
          $subactivityresult = json_decode(json_encode($this->coreFunctions->opentable($subactivity)), true);

          PDF::SetFont($fontbold, '', 15);
          PDF::MultiCell(1100, 0, ' SUB-ACTIVITY: ', 'LR', 'L', false);

          $this->titlesubactivity();


          PDF::SetFont($font, '', 10);
          PDF::MultiCell(1100, 0, '', 'LR');

          for ($c = 0; $c < count($subactivityresult); $c++) {
            $subactid = $this->reporter->fixcolumn([$subactivityresult[$c]['subactid']], 8, 0);
            $subactivity = $this->reporter->fixcolumn([$subactivityresult[$c]['subactivity']], 40, 0);
            $desc = $this->reporter->fixcolumn([$subactivityresult[$c]['description']], 30, 0);
            $countsubactid = count($subactid);
            $countsubactivity = count($subactivity);
            $countdesc = count($desc);
            $maxrow = max($countsubactivity, $countdesc, $countsubactid);

            for ($r = 0; $r < $maxrow; $r++) {
              if ($r == 0) {

                $uom = $subactivityresult[$c]['uom'];
                $rrqty = number_format($subactivityresult[$c]['rrqty'], 2);
                $rrcost = number_format($subactivityresult[$c]['rrcost'], 2);
                $ext = number_format($subactivityresult[$c]['ext'], 2);
                $qty = number_format($subactivityresult[$c]['qty'], 2);
                $cost = number_format($subactivityresult[$c]['cost'], 2);
                $totalcost = number_format($subactivityresult[$c]['totalcost'], 2);
              } else {

                $uom = '';
                $rrqty = '';
                $rrcost = '';
                $ext = '';
                $qty = '';
                $cost = '';
                $totalcost = '';
              }

              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(50, 0, isset($subactid[$r]) ? ' ' . $subactid[$r] : '', 'L', 'L', false, 0, '', '', true, 0);
              PDF::MultiCell(260, 0, isset($subactivity[$r]) ? ' ' . $subactivity[$r] : '', '', 'L', false, 0, '', '', false, 0);
              PDF::MultiCell(200, 0, isset($desc[$r]) ? ' ' . $desc[$r] : '', '', 'L', false, 0, '', '', false, 0);
              PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 0);
              PDF::MultiCell(70, 0, $rrqty, '', 'R', false, 0, '', '', false, 0);
              PDF::MultiCell(100, 0, $rrcost, '', 'R', false, 0, '', '', false, 0);
              PDF::MultiCell(100, 0, $ext, '', 'R', false, 0, '', '', false, 0);
              PDF::MultiCell(70, 0, $qty, '', 'R', false, 0, '', '', false, 0);
              PDF::MultiCell(100, 0, $cost, '', 'R', false, 0, '', '', false, 0);
              PDF::MultiCell(100, 0, $totalcost, 'R', 'R', false);

              if (PDF::getY() >= $page) {
                $newpageadd = 1;
                $this->addrow();
                PDF::MultiCell(0, 15, "Page" . PDF::PageNo() . " of " . PDF::getAliasNbPages(), '', 'R', false);


                $this->titlesubactivity();
                PDF::SetFont($font, '', 10);
                PDF::MultiCell(1100, 0, '', 'LR');
              }
            }
          }

          PDF::SetFont($font, '', 5);
          PDF::MultiCell(1100, 0, '', 'LR');
        }
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(1100, 0, '***NOTHING TO FOLLOWS***', 'LR', 'C');
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(1100, 0, '', 'BLR');
      }



      do {
        $this->addrowspace();
      } while (PDF::getY() <= 960);

      PDF::SetFont($font, '', 10);
      PDF::MultiCell(1100, 0, '', '');
    } //for end $data

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrow()
  {
    PDF::MultiCell(1100, 0, '', 'T', 'L', false);
  }

  private function addrowspace()
  {
    PDF::MultiCell(760, 0, '', '', 'L', false);
  }

  private function titlesubactivity()
  {
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $fontsize = 11;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'SUBACT', 'TLR', 'C', false, 0, '', '', true, 0);
    PDF::MultiCell(260, 0, 'SUBACTIVITY', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(200, 0, 'DESCRIPTION', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(50, 0, 'UOM', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(70, 0, 'CONTRACT', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, 'CONTRACT', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, 'TOTAL AMOUNT', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(70, 0, 'ESTIMATED', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, 'ESTIMATED', 'TLR', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, 'TOTAL COST', 'TLR', 'C', false);

    PDF::MultiCell(50, 0, 'ID', 'LRB', 'C', false, 0, '', '', true, 0);
    PDF::MultiCell(260, 0, '', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(200, 0, '', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(50, 0, '', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(70, 0, ' QTY', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, ' PRICE', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, ' ', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(70, 0, 'QTY', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, 'COST', 'LRB', 'C', false, 0, '', '', false, 0);
    PDF::MultiCell(100, 0, '', 'LRB', 'C', false);
  }
}
