<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class lp
{

  private $modulename = "Lease Provision";
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
    $fields = ['radioprint', 'prepared', 'noted', 'notedby1', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      
    ]);
    data_set($col1,'prepared.label','Prepared by Position');
    data_set($col1,'noted.label','Noted by');
    data_set($col1,'notedby1.label','Noted by Position');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '' as prepared,
      '' as noted,
      '' as notedby1
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $center = $config['params']['center'];
    $query = "select num.trno, num.docno,head.client, head.clientname,date_format(head.dateid,'%m/%d/%Y') as dateid, head.address, head.bstyle, head.category, head.email, 
    head.locid, date_format(head.start,'%m/%d/%Y') as start, date_format(head.enddate,'%m/%d/%Y') as enddate, head.escalation, head.contract,head.tin, head.tel, head.contact,
    head.isnonvat,head.type, head.position, format(tinfo.leaserate,2) as leaserate, format(tinfo.acrate,2) as acrate, format(tinfo.cusarate,2) as cusarate, format(tinfo.drent,2) as drent,
    tinfo.dac, tinfo.dcusa, tinfo.billtype, tinfo.rentcat, tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, format(tinfo.mcharge,2) as mcharge, tinfo.percentsales,
    format(tinfo.msales,2) as msales, format(tinfo.elecrate,2) as elecrate, format(tinfo.selecrate,2) as selecrate, format(tinfo.waterrate,2) as waterrate, tinfo.classification, 
    tinfo.eratecat,tinfo.wratecat, format(tinfo.secdep,2) as secdep, tinfo.secdepmos, format(tinfo.ewcharges,2) as ewcharges, format(tinfo.concharges,2) as concharges, 
    format(tinfo.fencecharge,2) as fencecharge,format(tinfo.powercharges,2) as powercharges, format(tinfo.watercharges,2) as watercharges, format(tinfo.housekeeping,2) as housekeeping, 
    format(tinfo.docstamp,2) as docstamp, format(tinfo.consbond,2) as consbond, format(tinfo.emeterdep,2) as emeterdep, format(tinfo.servicedep,2) as servicedep, tinfo.rem,
    tinfo.tenanttype,loc.name as loc, loc.area, loc.emeter, loc.semeter, loc.wmeter,loc.code as loccode,
    elect.category as eratecatname, water.category as wratecatname,
    tinfo.isspecialrate,(tinfo.leaserate*loc.area)/1.12 as fixedmonthlyrent 
    from lphead as head
    left join transnum as num on num.trno = head.trno     
    left join tenantinfo as tinfo on tinfo.trno = head.trno    
    left join loc as loc on loc.line = head.locid
    left join ratecategory as elect on elect.line = tinfo.eratecat
    left join ratecategory as water on water.line = tinfo.wratecat
    where head.trno = $trno and num.doc='LP' and num.center = '$center'
    union all 
    select num.trno, num.docno,head.client, head.clientname,date_format(head.dateid,'%m/%d/%Y') as dateid, head.address, head.bstyle, head.category, head.email, 
    head.locid, date_format(head.start,'%m/%d/%Y') as start, date_format(head.enddate,'%m/%d/%Y') as enddate, head.escalation, head.contract,head.tin, head.tel, head.contact,
    head.isnonvat,head.type, head.position, format(tinfo.leaserate,2) as leaserate, format(tinfo.acrate,2) as acrate, format(tinfo.cusarate,2) as cusarate, format(tinfo.drent,2) as drent,
    tinfo.dac, tinfo.dcusa, tinfo.billtype, tinfo.rentcat, tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, format(tinfo.mcharge,2) as mcharge, tinfo.percentsales,
    format(tinfo.msales,2) as msales, format(tinfo.elecrate,2) as elecrate, format(tinfo.selecrate,2) as selecrate, format(tinfo.waterrate,2) as waterrate, tinfo.classification, 
    tinfo.eratecat,tinfo.wratecat, format(tinfo.secdep,2) as secdep, tinfo.secdepmos, format(tinfo.ewcharges,2) as ewcharges, format(tinfo.concharges,2) as concharges, 
    format(tinfo.fencecharge,2) as fencecharge,format(tinfo.powercharges,2) as powercharges, format(tinfo.watercharges,2) as watercharges, format(tinfo.housekeeping,2) as housekeeping, 
    format(tinfo.docstamp,2) as docstamp, format(tinfo.consbond,2) as consbond, format(tinfo.emeterdep,2) as emeterdep, format(tinfo.servicedep,2) as servicedep, tinfo.rem,
    tinfo.tenanttype,loc.name as loc, loc.area, loc.emeter, loc.semeter, loc.wmeter,loc.code as loccode,
    elect.category as eratecatname, water.category as wratecatname,
    tinfo.isspecialrate,(tinfo.leaserate*loc.area)/1.12 as fixedmonthlyrent
    from hlphead as head
    left join transnum as num on num.trno = head.trno      
    left join tenantinfo as tinfo on tinfo.trno = head.trno   
    left join loc as loc on loc.line = head.locid
    left join ratecategory as elect on elect.line = tinfo.eratecat
    left join ratecategory as water on water.line = tinfo.wratecat
    where head.trno = $trno and num.doc='LP' and num.center = '$center' ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data) {
    
    if($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_LP_PDF($params, $data);
    }
  }
  
  



  public function default_LP_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize11 = 11;
    $fontsize10 = 10;
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
    PDF::SetMargins(70, 70);

    

    PDF::SetFont($fontbold, '', $fontsize11);
    $style = array(
      'border' => false,
      'padding' => 0,
    );

    
    PDF::MultiCell(660, 0, "\n\n\n\n\n\n\n", '', 'C');
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(500, 0, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    $date = date_format(date_create($data[0]['dateid']), "F d, Y");
    PDF::MultiCell(160, 0, (isset($data[0]['dateid']) ? $date : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n", '', 'C');
    PDF::SetFont($fontbold, '', $fontsize11);
    PDF::MultiCell(660, 0, "AWARD NOTICE", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::MultiCell(660, 0, "\n", '', 'C');
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(660, 0, $data[0]['contact'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, $data[0]['position'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, $data[0]['clientname'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, $data[0]['address'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, $data[0]['tel'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, $data[0]['email'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n\n", '', 'C');
    PDF::MultiCell(660, 0, '  This is a formal proposal regarding your business at the '.$headerdata[0]->name, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, ' in '.$headerdata[0]->address.'. Please find the following terms and conditions:', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n\n", '', 'C');
    PDF::MultiCell(160, 0, 'Trade Name ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['clientname'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Nature of Business ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['category'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Location ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['loc'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Unit No ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['loccode'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Floor Area ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['area'].' sqm', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Term of Contract ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['contract'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Rental Escalation ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['escalation'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Lease Term ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    $startdate=date_format(date_create($data[0]['start']),'F d, Y');
    $enddate=date_format(date_create($data[0]['enddate']),'F d, Y');
    PDF::MultiCell(500, 0, ':  '.$startdate.' - '.$enddate.' or start of operations whichever
    comes first.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Fixed Monthly Rent ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::SetFont($fontbold, '', $fontsize11);
    PDF::MultiCell(500, 0, ':  P '.number_format($data[0]['fixedmonthlyrent'],2).'/month computed at P '.number_format($data[0]['fixedmonthlyrent']/$data[0]['area'],2).'/sqm', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(160, 0, 'Security Deposit ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  P '.$data[0]['secdep'].' or representing '.$data[0]['secdepmos'].' months rental (No VAT) which', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   shall be refunded sixty (60) days after the expiration of the lease contract', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   and shall not be used for payment of monthly rentals.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   '.$data[0]['rem'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   The Security Deposit shall be forfeited in favor of the lessor upon:', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '     a) Cancellation of the agreement after the lessee have signed and', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '     confirmed such proposal.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '     b) Failure to occupy the base premises for the full term of the lease.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '     c) Pre - termination of contract for whatever reason.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::SetFont($fontbold, '', $fontsize11);
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   This should be paid upon signing of this Award Notice. ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   All cheques shall be submitted together with signed Award Notice
    and made payable to '.strtoupper($headerdata[0]->name), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(70, 70);
    

    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Construction Bond ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['consbond'].' , a cash bond equivalent to one (1) month fixed rental (No VAT) and', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   shall be paid before the start of construction and shall be refunded 30', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   days after correction of construction deficiencies and completion of all', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   governmental & mall requirements. Any unpaid construction charges', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   shall be deducted from the bond.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    

    PDF::MultiCell(160, 0, 'Document Stamp Tax ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['docstamp'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Aircon Charges ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['acrate'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'CUSA ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['cusarate'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Electric and Water Charges ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['ewcharges'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Electric Meter Deposit ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['emeter'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Service Bill Deposit ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['servicedep'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Construction Charges ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['concharges'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Est. Cost of Plywood Fencing ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['fencecharge'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Est. Power Charges ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['powercharges'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Est. Water Charges ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['watercharges'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Housekeeping/Debris Hauling ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  '.$data[0]['housekeeping'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Construction Plans ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  Complete plans to be submitted for approval of developer’s architect prior', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   to construction.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, 'Cancellation ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  Should you fail to commence operation within thirty (30) days From', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
     
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   effectivity of the lease, we shall have the right to unilaterally cancel this', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   agreement and all deposits received by us will not be refunded. We', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   therefore reserve the right to offer the same premises to a third party .', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::MultiCell(160, 0, 'Lease Contract ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, ':  Lease Contract to be signed and submitted to MALL ADMIN OFFICE on', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   or before the start of lease.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n\n", '', 'C');
    
    PDF::MultiCell(660, 0, 'If the foregoing is acceptable to you, please sign on the space provided below to signify your conformity ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, 'and return a signed copy to us not later than five (5) days so we can facilitate lease documentation. In ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, 'the event we do not receive the signed copy within the above period, we shall assume you are no ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, 'longer interested and shall offer the space to another party .', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n\n", '', 'C');
    PDF::MultiCell(660, 0, 'This letter only sets forth the highlights of the contract of lease which we will jointly execute. The', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, 'Contract of Lease will incorporate all our standard terms and condition.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(70, 70);

    
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(500, 0, '   ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::MultiCell(330, 0, 'Very Yours Truly,', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, 'Conforme:', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(660, 0, "\n\n\n\n", '', 'C');

    PDF::MultiCell(330, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, $data[0]['clientname'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    
    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, $data[0]['position'], '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    
    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, 'Date  ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(280, 0, '', 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(330, 0, 'Noted by:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(330, 0, $params['params']['dataparams']['noted'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    
    PDF::MultiCell(330, 0, $params['params']['dataparams']['notedby1'], '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(330, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 0, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    

    PDF::MultiCell(660, 0, 'NOTE: All rental and other charges are exclusive of Value Added Tax, which shall be for the account of the
    ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(660, 0, 'Lessee.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    

    

  }

  public function default_LP_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->default_LP_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;


    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



}
