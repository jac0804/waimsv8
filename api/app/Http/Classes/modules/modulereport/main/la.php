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

class la
{

    private $modulename = "Loan Approval Form";

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

    public $tablenum = 'cntnum';
    public $lahead = 'lahead';
    public $glhead = 'glhead';

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

    public function report_default_query($config)
    {
        $trno = $config['params']['dataid'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $eahead = 'heahead';
        $info = 'heainfo';
        $tablenum = $this->tablenum;
        $head = $this->lahead;
        $hhead = $this->glhead;

        $qryselect =
            "select
             num.center, 
             cp.trno, 
             cp.docno,
             head.docno as afdocno,
             cp.aftrno, 
             left(head.dateid,10) as dateid,
         ifnull(r.reqtype,'') as categoryname,  format(head.monthly,2) as monthly, '' as leasecontract,
         head.interest,
         head.planid, info.pf, 
         cp.tax,
         cp.vattype,
         '' as dvattype,

         head.purpose, head.client,head.lname,head.fname,head.mname,head.mmname,
         concat(head.lname,', ',head.fname,' ',head.mname)  as clientname,
         head.address,head.province,head.addressno,info.issameadd, info.isbene,  info.ispf,head.contactno,head.street,
    
         ifnull(head.civilstatus,'') as civilstatus, 
         head.dependentsno,head.nationality,head.employer,
         head.subdistown,head.country,head.brgy,

         ifnull(head.sname,'') as sname,ifnull(info.paddress,'') as companyaddress,
         ifnull(head.ename,'') as ename, '' as mmoq,
         
         ifnull(head.city,'') as city, ifnull(info.pcity,'') as pcity,
         ifnull(info.pf,'') as pf, ifnull(info.pcountry,'') as pcountry, ifnull(info.pprovince,'') as pprovince,
          format(info.amount,2) as amount,  head.terms,
         
         info.idno,format(info.value,2) as value,info.isdp,info.isotherid,
         head.zipcode,head.yourref,head.email,
         
         
         ifnull(info.bank1,'')  as pemail,
         ifnull(head.current1,'') as current1 ,'' as customername,
         ifnull(head.current2,'') as current2 ,'' as prref,
         ifnull(head.others1,'') as others1 ,'' as checkinfo,

        
         ifnull(head.others2,'') as others2, '' as entryndiffot,
          ifnull(format(head.mincome,2),'') as mincome, '' as regnum,
         ifnull(format(head.mexp,2),'') as mexp, '' as purchaser,
         ifnull(info.pob,'') as pob, '' as registername,
         ifnull(info.otherplan,'') as otherplan, '' as shipto,
         ifnull(head.num,'') as num, '' as revisionref,
         ifnull(date(head.voiddate),'') as voiddate, '' as returndate,
         ifnull(head.pliss,'') as pliss, '' as mlcp_freight,
         ifnull(head.tin,'') as tin,ifnull(head.sssgsis,'') as sssgsis,



         info.lname as lname2, info.fname as fname2, info.mname as mname2,info.mmname as maidname,
         info.address as truckno,
         info.province as rprovince,info.addressno as raddressno,
         info.iswife,info.isretired,info.isofw,head.contactno2,info.street as rstreet,
         ifnull(info.civilstat,'') as civilstat, '' as mstatus,
         
         info.dependentsno as mobile,info.nationality as citizenship,
         info.employer as owner,info.subdistown as rsubdistown,info.country as rcountry, info.brgy as rbrgy,
         info.sname as empfirst, info.pstreet, info.ename as emplast, info.city as rcity,
         info.paddressno, format(info.dp,2)as dp,info.psubdistown,info.othersource,

         info.ext as ext2,format(info.value2,2) as rem, info.isprc,
         info.isdriverlisc,info.zipcode as minimum,head.ourref,
         info.savings1 as recondate,info.savings2 as endingbal, 
         info.current1 as unclear,info.current2 as revision,
         info.others1 as ftruckname,info.others2 as frprojectname,
         format(info.mincome,2) as poref, format(info.mexp,2) as soref,
         info.pbrgy,info.appref,info.num as numdays,
        date(info.bday) as bday,info.pliss as entryot, info.tin as othrs, 
         info.sssgsis as apothrs,
         info.issenior,info.tenthirty,info.thirtyfifty,
         info.fiftyhundred,
         info.hundredtwofifty,info.fivehundredup,
         info.isemployed,info.isselfemployed,info.isplanholder,
         info.credits,info.payrolltype,info.employeetype,info.expiration,info.loanlimit,info.loanamt,
         info.amortization,info.penalty, concat(info.lname,', ',info.fname,' ',info.mname)  as comakername
         ";

        $qry = $qryselect . " from $head as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where cp.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $hhead as cp
        left join $tablenum as num on num.trno = cp.trno
        left join $eahead as head on cp.aftrno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        where cp.trno = ? and num.doc=? and num.center=? ";
        
        return $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    } //end fn


    public function reportplotting($params, $data)
    {
        return $this->default_PO_PDF($params, $data);
    }

    public function default_le_header_PDF($params, $data)
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

        //changeimage
        //margin left  margin top   width height
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);

        //changeimage

        PDF::Image($this->companysetup->getlogopath($params['params']) . 'samplelogo.jpg', '50', '40', 100, 90);


        PDF::MultiCell(0, 20, "\n\n\n");

        PDF::SetFont($fontbold, '', 20);
        PDF::MultiCell(300, 0, "ASCEND FINANCE AND LEASING (AFLI) INC.", '', 'C', false, 0, '170',  '60', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n\n\n");

        $r1 = '';
        $r2 = '';
        $r3 = '';
        if ($data[0]->categoryname == 'SALARY LOAN') {
            $r1 = 'checked="checked"';
        } else if ($data[0]->categoryname == 'WORKING CAPITAL') {
            $r2 = 'checked="checked"';
        } else {
            $r3 = 'checked="checked"';
        }
        $html = '
        <form  action="http://localhost/printvars.php" enctype="multipart/form-data">
        <input type="checkbox" name="agree1" value="1" readonly="true" ' . $r1 . '/>  
        <label for="agree1" style="font-size: 11; display: inline-block;">SALARY LOAN</label>
        <input type="checkbox" name="agree2" value="2" readonly="true" ' . $r2 . '/> 
        <label for="agree2" style="font-size: 11; display: inline-block;">WORKING CAPITAL</label>
        <input type="checkbox" name="agree3" value="3" readonly="true" ' . $r3 . '/>  
        <label for="agree3" style="font-size: 11; display: inline-block;">HOUSING LOAN</label>
        </form>';
        PDF::writeHTML($html, true, 0, true, 0);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, "", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(200, 20, "NAME OF BORROWER", 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(200, 20, 'NAME OF CO-MAKER', 'TLR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(120, 20, "AMOUNT", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 20, 'TERM', 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, " Surname: " . $data[0]->lname, 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, ' Surname: ' . $data[0]->lname2, 'LBR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 20, $data[0]->amount, 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, $data[0]->terms, 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, " Given Name: " . $data[0]->fname, 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, ' Given Name: ' . $data[0]->lname2, 'LBR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 20, "Purpose of Loan", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, " Middle Name: " . $data[0]->mname, 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, ' Middle Name: ' . $data[0]->mname2, 'LBR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 20, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(210, 20, $data[0]->purpose, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, " Mother's Maiden Name: ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 20, " Mother's Maiden Name: ", 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->mmname, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->maidname, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " PRESENT ADDRESS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->address, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->truckno, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " PROVINCIAL ADDRESS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->province, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rprovince, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " MAILING ADDRESS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->addressno, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->raddressno, 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " HOUSE", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        //   Prepare the checkbox values
        PDF::Ln(30);
        $owned = '';
        $rented = '';
        $free = '';
        if ($data[0]->issameadd == '1') {
            $owned = 'checked="checked"';
        }
        if ($data[0]->isbene == '1') {
            $rented = 'checked="checked"';
        }
        if ($data[0]->ispf == '1') {
            $free = 'checked="checked"';
        }
        PDF::SetXY(270, PDF::GetY() - 23);
        //first chickbox
        $html = '
        <form action="http://localhost/printvars.php" enctype="multipart/form-data">
            <input type="checkbox" name="owned" value="1" readonly="true" ' . $owned . '/>  
            <label for="owned" style="font-size: 11; display: inline-block;">OWNED</label>
            <input type="checkbox" name="rented" value="2" readonly="true" ' . $rented . '/> 
            <label for="rented" style="font-size: 11; display: inline-block;">RENTED</label>
            <input type="checkbox" name="free" value="3" readonly="true" ' . $free . '/>  
            <label for="free" style="font-size: 11; display: inline-block;">FREE</label>
        </form>
    ';
        PDF::writeHTML($html, true, 0, true, 0);
        // para sa space ng element
        PDF::Ln(7);

        //secon checkbox
        $owned2 = '';
        $rented2 = '';
        $free2 = '';
        if ($data[0]->iswife == '1') {
            $owned2 = 'checked="checked"';
        }
        if ($data[0]->isretired == '1') {
            $rented2 = 'checked="checked"';
        }
        if ($data[0]->isofw == '1') {
            $free2 = 'checked="checked"';
        }

        PDF::SetXY(470, PDF::GetY() - 22); // 470-layo mula sa kaliwa 
        $html2 = '
        <form action="http://localhost/printvars.php" enctype="multipart/form-data">
            <input type="checkbox" name="owned" value="1" readonly="true" ' . $owned2 . '/>  
            <label for="owned" style="font-size: 11; display: inline-block;">OWNED</label>
            <input type="checkbox" name="rented" value="2" readonly="true" ' . $rented2 . '/> 
            <label for="rented" style="font-size: 11; display: inline-block;">RENTED</label>
            <input type="checkbox" name="free" value="3" readonly="true" ' . $free2 . '/>  
            <label for="free" style="font-size: 11; display: inline-block;">FREE</label>
        </form>
    ';
        PDF::writeHTML($html2, true, 0, true, 0);

        PDF::Rect(260, PDF::GetY(), 200, -19.6); // 260 layo mula sa kaliwa 200-width 
        // Column 2 Border
        PDF::Rect(460, PDF::GetY(), 200, -19.6);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " TELEPHONE NUMBER ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->contactno, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->contactno2, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " DATE & PLACE OF BIRTH ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->street, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rstreet, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);




        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " CIVIL STATUS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->civilstatus, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20,  $data[0]->civilstat, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " NO. OF DEPENDENTS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->dependentsno, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->mobile, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " NATIONALITY ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->nationality, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->citizenship, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, " NAME OF EMPLOYER/BUSINESS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->employer, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20,  $data[0]->owner, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " ADDRESS & TEL NUMBER ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->subdistown, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rsubdistown, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " POSITION HELD ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->country, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rcountry, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " LENGTH OF STAY ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->brgy, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rbrgy, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(620, 20, " SPOUSE DATA (IF MARRIED) ", '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " NAME OF SPOUSE ", 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->sname, 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->empfirst, 'TBR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " DATE & PLACE OF BIRTH ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->companyaddress, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->pstreet, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, " NAME OF EMPLOYER/BUSINESS ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->ename, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->emplast, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, " ADDRESS & TEL NUMBER ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->city, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->rcity, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "POSITION HELD ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->pcity, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->paddressno, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "MONTHLY INCOME", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->monthly, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->dp, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "LENGTH OF STAY", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->pcountry, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->psubdistown, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "IMMEDIATE SUPERIOR", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->pprovince, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->othersource, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        $arr = ["P", "R", "O", "P", "E", "R", "T", "I", "E", "S"];


        for ($i = 0; $i < count($arr); $i++) {

            if ($arr[$i] == 'P' && $i == 0) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20,  $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'LOCATION', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->idno, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->ext2, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }
            if ($arr[$i] == 'R' && $i == 1) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20,  $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, 'REAL', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'VALUE', 'TBR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->value, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->rem, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }





            if ($arr[$i] == 'O') {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20,  $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, 'ESTATE', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, '', 'TBR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::Ln(30);
                $nmortage = '';
                $mortage = '';
                if ($data[0]->isdp == '1') {
                    $nmortage = 'checked="checked"';
                }
                if ($data[0]->isotherid == '1') {
                    $mortage = 'checked="checked"';
                }

                PDF::SetXY(270, PDF::GetY() - 23);
                //first checkbox
                $html = '
                <form action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="nmortage" value="1" readonly="true" ' . $nmortage . '/>  
                    <label for="nmortage" style="font-size: 11; display: inline-block;">NOT MORTAGE</label>
                    <input type="checkbox" name="mortage" value="2" readonly="true" ' . $mortage . '/> 
                    <label for="mortage" style="font-size: 11; display: inline-block;">MORTAGE</label>
                </form> ';
                PDF::writeHTML($html, true, 0, true, 0);
                // para sa space ng element
                PDF::Ln(7);

                //secon checkbox isprc,isdriverlisc
                $nmortage2 = '';
                $mortage2 = '';

                if ($data[0]->isprc == '1') {
                    $nmortage2 = 'checked="checked"';
                }
                if ($data[0]->isdriverlisc == '1') {
                    $mortage2 = 'checked="checked"';
                }

                PDF::SetXY(470, PDF::GetY() - 22); // 470-layo mula sa kaliwa 
                $html2 = '
                <form action="http://localhost/printvars.php" enctype="multipart/form-data">
                    <input type="checkbox" name="nmortage2" value="1" readonly="true" ' . $nmortage2 . '/>  
                    <label for="nmortage2" style="font-size: 11; display: inline-block;">NOT MORTAGE</label>
                    <input type="checkbox" name="mortage2" value="2" readonly="true" ' . $mortage2 . '/> 
                    <label for="mortage2" style="font-size: 11; display: inline-block;">MORTAGE</label>
                </form> ';
                PDF::writeHTML($html2, true, 0, true, 0);

                PDF::Rect(260, PDF::GetY(), 200, -19.6); // 260 layo mula sa kaliwa 200-width 
                // Column 2 Border
                PDF::Rect(460, PDF::GetY(), 200, -19.6);
            }

            if ($arr[$i] == 'P' && $i == 3) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20,  $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'YEAR', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->zipcode, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->minimum, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }


            if ($arr[$i] == 'E' && $i == 4) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, 'VEHICLE', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'MODEL', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->yourref, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(195, 20, $data[0]->ourref, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }

            //Acc no. bank
            if ($arr[$i] == 'R' && $i == 5) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, '', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(95, 20, 'ACCOUNT NO.', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, 'BANK', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(95, 20, 'ACCOUNT NO.', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, 'BANK', 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }

            if ($arr[$i] == 'T' && $i == 6) {


                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, '', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'SAVINGS', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


                //SAVINGS 1 BANK 1
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(95, 20, $data[0]->email, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->pemail, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->recondate, 'BR', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->endingbal, 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }

            if ($arr[$i] == 'I'  && $i == 7) {

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, 'BANK', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'CURRENT', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                //CURRENT BANK 1
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(95, 20, $data[0]->current1, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->current2, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->unclear, 'BR', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->revision, 'BR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }


            if ($arr[$i] == 'E'  && $i == 8) {

                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, 'DEPOSIT', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, 'OTHERS', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(95, 20, $data[0]->others1, 'R', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //others acct#
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", '', '', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->others2, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->ftruckname, 'R', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(5, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(95, 20, $data[0]->frprojectname, 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }

            if ($arr[$i] == 'S'  && $i == 9) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(73, 20, $arr[$i], 'LRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(73, 20, '', 'RB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(74, 20, '', 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(95, 20, '', 'RB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(95, 20, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(95, 20, '', 'RB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(5, 20, "", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(95, 20, '', 'RB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
                //PDF::MultiCell(200, 20, '', 'LBR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            }
        }

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(217, 20, "MONTHLY INCOME (APPLICANT)", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->mincome, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->poref, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "MONTHLY EXPENSES", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->mexp, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, $data[0]->soref, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(3, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(117, 20, "PERSONAL REFERENCES", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, '1. ' . $data[0]->pob, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, '1. ' . $data[0]->pbrgy, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, '2. ' . $data[0]->otherplan, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, '2. ' . $data[0]->appref, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);




        //Reidence Certificate
        //NUMBER
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'NUMBER: ' . $data[0]->num, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'NUMBER: ' . $data[0]->numdays, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        //DATE OF ISSUE

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(220, 20, ' RESIDENCE CERTIFICATE ', 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'DATE OF ISSUE: ' . $data[0]->voiddate, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'DATE OF ISSUE: ' . $data[0]->bday, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        //PLACE OF ISSUE

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(120, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'PLACE OF ISSUE: ' . $data[0]->pliss, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(5, 20, "", 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(195, 20, 'PLACE OF ISSUE: ' . $data[0]->entryot, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");



        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(620, 0, ' We hereby certify that all data and statements in the application are true, correct and complete and are made ', '', 'R', false, 1);
        PDF::MultiCell(620, 0, ' for the purpose of credit accommodation, and that the signature appearing hereon are genuine. We authorize you to', '', 'J', false, 1);
        PDF::MultiCell(620, 0, ' obtain such information as you may need relative to our credit application and that the sources of such information ', '', 'J', false, 1);

        PDF::MultiCell(570, 0, ' are authorized to provide such information as you may require concerning this loan request. We agree that ', '', 'J', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, ' ASCEND ', '', 'J', false, 1);
        PDF::MultiCell(200, 0, ' FINANCE AND LEASING (AFLI) INC. ', '', 'J', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, 'may retain the application whether or not our application for credit is ', '', 'J', false, 1);
        PDF::MultiCell(620, 0, ' granted or not. ', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(170, 20, "Borrower", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(140, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(170, 20, "Co-Maker", 'T', 'C', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, "TIN: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, $data[0]->tin, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(70, 20, "TIN: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, $data[0]->othrs, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, "SSS/GSIS NO. ", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, $data[0]->sssgsis, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(140, 20, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(70, 20, "SSS/GSIS NO. ", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 20, $data[0]->apothrs, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


        PDF::MultiCell(0, 0, "\n\n");


        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(310, 20, "CHECKLIST", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(310, 20, "FOR COMPANY USE ONLY", 'TLBR', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        // Checkbox conditions
        $cbox1 = '';
        $cbox2 = '';
        $cbox3 = '';
        $cbox4 = '';
        $cbox5 = '';
        $cbox6 = '';

        if ($data[0]->issenior == '1') {
            $cbox1 = 'checked="checked"';
        }

        if ($data[0]->tenthirty == '1') {
            $cbox2 = 'checked="checked"';
        }

        if ($data[0]->thirtyfifty == '1') {
            $cbox3 = 'checked="checked"';
        }

        if ($data[0]->fiftyhundred == '1') {
            $cbox4 = 'checked="checked"';
        }

        if ($data[0]->hundredtwofifty == '1') {
            $cbox5 = 'checked="checked"';
        }

        if ($data[0]->fivehundredup == '1') {
            $cbox6 = 'checked="checked"';
        }

        $cbox7 = '';
        $cbox8 = '';
        $cbox9 = '';

        if ($data[0]->isemployed == '1') {
            $cbox7 = 'checked="checked"';
        }

        if ($data[0]->isselfemployed == '1') {
            $cbox8 = 'checked="checked"';
        }

        if ($data[0]->isplanholder == '1') {
            $cbox9 = 'checked="checked"';
        }

        $html = '
    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">


        <!-- free row empty -->
                <tr>
                <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                            </td>
                    <td style="width: 310px; border-right: 1px solid black; text-align: left; padding: 0;">
                        <!-- Empty for this row -->
                    </td>
                </tr>


        <!-- First row (issenior and Condition and recommendation) -->
        <tr>
            <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                <input type="checkbox" name="issenior" value="1" readonly="true" ' . $cbox1 . '/>  
                <label for="issenior" style="display: inline-block;">Certificate of Employment w/ Salary Information and Certificate of Appointment or Employment Contract w/Allotment Slip </label>
            </td>
            <td style="width: 310px; border-right: 1px solid black; text-align: left; padding: 0;">
                Condition and recommendation
            </td>
        </tr>



        <!-- Second row (tenthirty checkbox and Approved with PDC checkbox) -->
        <tr>
            <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                <input type="checkbox" name="tenthirty" value="1" readonly="true" ' . $cbox2 . '/>  
                <label for="tenthirty" style="display: inline-block;">Photocopy of government issued IDs and any valid Identification</label>
            </td>

             <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                <input type="checkbox" name="isemployed" value="1" readonly="true" ' . $cbox7 . '/>  
                <label for="isemployed" style="display: inline-block;">Approved with PDC</label>
            </td>


        </tr>


        <!-- third row (thirtyfifty checkbox and Approved with PDC checkbox) -->
                <tr>
                    <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="thirtyfifty" value="1" readonly="true" ' . $cbox3 . '/>  
                        <label for="thirtyfifty" style="display: inline-block;">Ownership of Collateral/Post-dated Checks/ATM/ Passbook or Withdrawal Slip of Borrower</label>
                    </td>

                    <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="isselfemployed" value="1" readonly="true" ' . $cbox8 . '/>  
                        <label for="isselfemployed" style="display: inline-block;">Approved Salary Deduction</label>
                    </td>


                </tr>



                     <!-- 4 row (fiftyhundred checkbox and Approved with REM checkbox) -->
                <tr>
                    <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="fiftyhundred" value="1" readonly="true" ' . $cbox4 . '/>  
                        <label for="fiftyhundred" style="display: inline-block;">Bank Certification that the current account used is active and properly Handled or proof of pension record</label>
                    </td>

                    <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="isplanholder" value="1" readonly="true" ' . $cbox9 . '/>  
                        <label for="isplanholder" style="display: inline-block;">Approved with REM</label>
                    </td>


                </tr>


        <!-- 5 row ( checkbox) -->
        <tr>
           <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="hundredtwofifty" value="1" readonly="true" ' . $cbox5 . '/>  
                        <label for="hundredtwofifty" style="display: inline-block;">Picture of Collateral, Billing, Statement, Brgy. Clearance, Marriage Contract or Birth Certificate</label>
                    </td>
             <td style="width: 310px; border-right: 1px solid black; text-align: center; padding: 0;">
            </td>
        </tr>

         <!-- 6 row ( checkbox) -->
        <tr>
           <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="fivehundredup" value="1" readonly="true" ' . $cbox6 . '/>  
                        <label for="fivehundredup" style="display: inline-block;">Special Power of Attorney (Roxas City based)</label>
                    </td>
           
                    <td style="width: 310px; border-left: 1px solid black; border-right: 1px solid black; text-align: center; padding: 0;">
                     CREDIT COMMITTEE
                    </td>
        </tr>
    </table>';
        PDF::writeHTML($html, true, false, false, false, '');
        // $text = wordwrap($data[0]->credits, 65, "\n", true);
        $currentY = PDF::GetY(); //467.50000033333
        PDF::SetXY(40, $currentY - 15.5);
        PDF::SetFont($font, '', 11);
        PDF::SetTextColor(255, 255, 255);
        PDF::MultiCell(310, 0, $data[0]->credits, 'LBR', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 11);
        PDF::SetTextColor(0, 0, 0);
        PDF::MultiCell(310, 0, $data[0]->credits, 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::Ln(15);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 20, "ACTION ON APPROVING BODY", '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetXY(40, PDF::GetY() - 35);  // Set position
        PDF::Rect(40, PDF::GetY(), 620, 40);
        PDF::Ln(40);


        // Checkbox conditions
        $cbox10 = '';
        $cbox11 = '';
        $cbox12 = '';


        if ($data[0]->payrolltype == 'Weekly') {
            $cbox10 = 'checked="checked"';
        }
        if ($data[0]->payrolltype == 'Semi Monthly') {
            $cbox11 = 'checked="checked"';
        }

        if ($data[0]->payrolltype == 'Monthly') {
            $cbox12 = 'checked="checked"';
        }

        $cbox13 = '';
        $cbox14 = '';
        $cbox15 = '';
        $cbox16 = '';


        if ($data[0]->employeetype == 'Regular') {
            $cbox13 = 'checked="checked"';
        }
        if ($data[0]->employeetype == 'Probationary') {
            $cbox14 = 'checked="checked"';
        }

        if ($data[0]->employeetype == 'Contractual') {
            $cbox15 = 'checked="checked"';
        }


        if ($data[0]->employeetype == 'Under Agency') {
            $cbox16 = 'checked="checked"';
        }

        $html = '
            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">

       <!-- free row empty -->
                <tr>
                <td style="width: 600px;  text-align: left; padding: 0;">
                     </td>
                </tr>

            <!-- First row (check1 and Condition and recommendation) -->
            <tr>
                <td style="width: 100px; text-align: center; padding: 0;">
                   PAYROLL TYPE
                </td>
               <td style="width: 200px;  text-align: left; padding: 0;">
                    <input type="checkbox" name="check1" value="1" readonly="true" ' . $cbox10 . '/>  
                    <label for="check1" style="display: inline-block;">Weekly</label>
                </td>
                 <td style="width: 100px; text-align: left; padding: 0;">
                    EMPLOYEE TYPE
                </td>
                  <td style="width: 200px; text-align: left; padding: 0;">
                    <input type="checkbox" name="check1" value="1" readonly="true" ' . $cbox13 . '/>  
                    <label for="check1" style="display: inline-block;">Regular</label>
                </td>
            </tr>

      <!-- 2nd row (check2 Semi monthly and probationary) -->
            <tr>
                <td style="width: 100px; text-align: center; padding: 0;">
                </td>
               <td style="width: 200px;  text-align: left; padding: 0;">
                    <input type="checkbox" name="check2" value="1" readonly="true" ' . $cbox11 . '/>  
                    <label for="check2" style="display: inline-block;">Semi-Monthly</label>
                </td>
                 <td style="width: 100px; text-align: left; padding: 0;">
                </td>
                  <td style="width: 200px; text-align: left; padding: 0;">
                    <input type="checkbox" name="check3" value="1" readonly="true" ' . $cbox14 . '/>  
                    <label for="check3" style="display: inline-block;">Probationary</label>
                </td>
            </tr>
              <!-- 3nd row (check3 and Monthly  and Contractual) -->
            <tr>
                <td style="width: 100px; text-align: center; padding: 0;">
                </td>
               <td style="width: 200px;  text-align: left; padding: 0;">
                    <input type="checkbox" name="check3" value="1" readonly="true" ' . $cbox12 . '/>  
                    <label for="check3" style="display: inline-block;">Monthly</label>
                </td>
                 <td style="width: 100px; text-align: left; padding: 0;">
                </td>
                  <td style="width: 200px; text-align: left; padding: 0;">
                    <input type="checkbox" name="check4" value="1" readonly="true" ' . $cbox15 . '/>  
                    <label for="check4" style="display: inline-block;">Contractual</label>
                </td>
            </tr>
                     <!-- 4 row (check5 Under Agency) -->
            <tr>
                <td style="width: 100px; text-align: center; padding: 0;">
                </td>
               <td style="width: 200px;  text-align: left; padding: 0;">
                </td>
                 <td style="width: 100px; text-align: left; padding: 0;">
                </td>
                  <td style="width: 200px; text-align: left; padding: 0;">
                    <input type="checkbox" name="check6" value="1" readonly="true" ' . $cbox16 . '/>  
                    <label for="check6" style="display: inline-block;">Under Agency</label>
                </td>
            </tr>
        </table>';

        PDF::writeHTML($html, true, false, false, false, '');
        PDF::SetXY(50, PDF::GetY() - -115);  // Set position
        PDF::Rect(50, PDF::GetY(), 600, -200);

        $ex = $data[0]->expiration != '' ? $data[0]->expiration : '__';

        PDF::SetXY(60, PDF::GetY() - 130);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(60, PDF::GetY(), 580, 12, 'F');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 0, $ex, '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(380, 0, ' If applicable, contract expiration date', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        $l = $data[0]->loanlimit != 0 ? number_format($data[0]->loanlimit, 2) : '__';

        PDF::SetXY(60, PDF::GetY() - -10);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(60, PDF::GetY(), 580, 15, 'F');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(300, 0, 'Loan Limit ('  . $l . "% of Salary)", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(280, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        $lamt = $data[0]->loanamt != 0 ? number_format($data[0]->loanamt, 2) : '__';
        PDF::SetXY(60, PDF::GetY() - -3);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(60, PDF::GetY(), 580, 15, 'F');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(150, 0, 'P ' . $lamt, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(430, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



        PDF::SetXY(60, PDF::GetY() - 1);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(60, PDF::GetY(), 580, 10, 'F');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(380, 0,  '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, '____________________________________', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetXY(60, PDF::GetY() - 1);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(60, PDF::GetY(), 580, 10, 'F');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(380, 0,  '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, 'Signature Above Printed Name and Date', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        PDF::Ln(50);
        $html = '
            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
         <!-- free row empty -->
                <tr>
                <td style="width: 600px;  text-align: left; padding: 0;">
                     </td>
                </tr>
            <tr>
                <td style="width: 150px; text-align: center; padding: 0;">
                    <input type="checkbox" name="check10" value="1" readonly="true"/>  
                     <label for="check10" style="display: inline-block;">APPROVED</label>
                </td>
               <td style="width: 150px;  text-align: center; padding: 0;">
                    <input type="checkbox" name="check11" value="1" readonly="true"/>  
                    <label for="check11" style="display: inline-block;">DISAPPROVED</label>
                </td>
                 <td style="width: 150px;  text-align: center; padding: 0;">
                    <input type="checkbox" name="check12" value="1" readonly="true"/>  
                    <label for="check12" style="display: inline-block;">APPROVED</label>
                </td>
                 <td style="width: 150px;  text-align: center; padding: 0;">
                    <input type="checkbox" name="check13" value="1" readonly="true"/>  
                    <label for="check13" style="display: inline-block;">DISAPPROVED</label>
                </td>
            </tr>

  </table>';
        PDF::writeHTML($html, true, false, false, false, '');

        PDF::Ln(5);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(20, 20, "", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(230, 20, "JUDITH O. ALFORTE", 'B', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 20, "", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($font, '', 13);
        PDF::MultiCell(250, 20, "________________________________", '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetFont($font, '', 13);
        PDF::MultiCell(250, 20, "ACCOUNTING HEAD", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 20, "", '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(250, 20, "BOARD OF DIRECTOR", '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);


        PDF::SetXY(40, PDF::GetY() - -35);
        PDF::SetFillColor(255, 255, 255);
        PDF::Rect(40, PDF::GetY(), 620, -320);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(380, 0,  '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, '', '', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
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
        $this->default_le_header_PDF($params, $data);
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
