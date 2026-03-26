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
use DateTime;
use DateInterval;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class le
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


        $fields = ['radioprint', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'DEFAULT', 'value' => '0', 'color' => 'red'],
            ['label' => 'SPECIAL POWER OF ATTORNEY', 'value' => '1', 'color' => 'red'],
            ['label' => 'AUTHORITY TO DEDUCT AND REMIT', 'value' => '2', 'color' => 'red'],
            ['label' => 'COMPREHENSIVE SURETY AGREEMENT', 'value' => '3', 'color' => 'red'],
            ['label' => 'DISCLOSURE', 'value' => '4', 'color' => 'red'],
            ['label' => 'PAYMENT SCHED', 'value' => '7', 'color' => 'red'],
            ['label' => 'DOC SPA', 'value' => '5', 'color' => 'red'],
            ['label' => 'PROMISSORY NOTE', 'value' => '6', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Loan Agreement With Real Estate Mortgage', 'value' => '8', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Real Estate Mortgage', 'value' => '9', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Acknowledgment Receipt and Promissory Note', 'value' => '10', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Quitclaim and Waiver of Rights and Interests', 'value' => '11', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Deed of Assignment', 'value' => '12', 'color' => 'red'],
            ['label' => 'HOUSING LOAN PRINTOUT - Take out Fees', 'value' => '13', 'color' => 'red']

        ]);

        data_set($col1, 'radioreporttype.label', 'ADDITIONAL PRINTOUTS');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function reportparamsdata($config)
    {
        $paramstr = "select
                  'PDFM' as print,
                  '' as prepared,
                  '' as approved,
                  '' as received,
                  '0' as reporttype";

        return $this->coreFunctions->opentable($paramstr);
    }

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

        $qryselect = "select num.center,  head.trno, 
         head.docno,left(head.dateid,10) as dateid,left(head.releasedate,10) as releasedate,          
         ifnull(r.reqtype,'') as categoryname,  format(head.monthly,2) as monthly,         
         head.interest,head.planid, ifnull(info.pf,0) as pf, ifnull(info.nf,0) as nf,
         head.purpose, head.client,head.lname,head.fname,head.mname,head.mmname,head.clientname,
         head.address,head.province,head.addressno,info.issameadd, info.isbene,  info.ispf,head.contactno,head.street,    
         ifnull(head.civilstatus,'') as civilstatus, head.dependentsno,head.nationality,head.employer, head.subdistown,head.country,head.brgy,
         ifnull(head.sname,'') as sname,ifnull(info.paddress,'') as companyaddress,ifnull(head.ename,'') as ename,          
         ifnull(head.city,'') as city, ifnull(info.pcity,'') as pcity,
         ifnull(info.pf,'') as pf, ifnull(info.pcountry,'') as pcountry, ifnull(info.pprovince,'') as pprovince,
         info.amount, head.terms,info.idno,format(info.value,2) as value,info.isdp,info.isotherid,
         head.zipcode,head.yourref,head.email,          
         ifnull(info.bank1,'') as bank1 ,
         ifnull(head.current1,'') as current1 ,
         ifnull(head.current2,'') as current2 ,
         ifnull(head.others1,'') as others1 ,        
         ifnull(head.others2,'') as others2, 
         ifnull(format(head.mincome,2),'') as mincome,
          ifnull(format(head.mexp,2),'') as mexp,
         ifnull(info.pob,'') as pob,
         ifnull(info.otherplan,'') as otherplan, 
         ifnull(head.num,'') as num, 
         ifnull(date(head.voiddate),'') as voiddate, 
         ifnull(head.pliss,'') as pliss, 
         ifnull(head.tin,'') as tin,ifnull(head.sssgsis,'') as sssgsis,
         info.lname as lname2, info.fname as fname2, info.mname as mname2,info.mmname as maidname,
         info.address as truckno,
         info.province as rprovince,info.addressno as raddressno,
         info.iswife,info.isretired,info.isofw,head.contactno2,info.street as rstreet,
         ifnull(info.civilstat,'') as civilstat,          
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
         info.credits,
         info.payrolltype,info.employeetype,info.expiration,info.loanlimit,info.loanamt,
         info.amortization,info.penalty, info.clientname  as comakername,terms.days+info.voidint as days, ifnull(date(num.postdate),'') as postdate,
         info.attorneyinfact,info.attorneyaddress,info.intannum,info.subdivision,info.blklot,
         case when info.isemployed = 1 then 'Approved with PDC'
         when info.isselfemployed = 1 then 'Approved Salary Deduction'
         When info.isplanholder=1 then 'Approved with REM'
         ELSE 'No approved condition/recommendations.' end as testpaymode,r.isdiminishing,ifnull(info.voidint,0) as voidint,sbu.reqtype as sbu,ifnull(info.mri,0) as mri,
         ifnull(info.docstamp,0) as dst,         
         ifnull(format(info.entryfee,2),0) as entryfee,
         ifnull(format(info.lrf,2),0) as lrf, 
         ifnull(format(info.itfee,2),0) as itfee, 
         ifnull(format(info.regfee,2),0) as regfee, 
         ifnull(format(info.docstamp1,2),0) as docstamp1,
         ifnull(format(info.nf,2),0) as nf, 
         ifnull(format(info.annotationfee,2),0) as annotationfee,
         ifnull(format(info.articles,2),0) as articles,
         ifnull(format(info.annotationexp,2),0) as annotationexp,
         ifnull(format(info.otransfer,2),0) as otransfer,
         info.pf,
         ifnull(format(info.rpt,2),0) as rpt,
         ifnull(format(info.docstamp,2),0) as docstamp, 
         ifnull(info.mri,0) as fmri,
         ifnull(format(info.handling,2),0) as handling,
         ifnull(format(info.appraisal,2),0) as appraisal,
         ifnull(format(info.filing,2),0) as filing,
         ifnull(format(info.nf2,2),0) as nf2,
         ifnull(format(info.nf3,2),0) as nf3,
         ifnull(format(info.ofee,2),0) as ofee,
         ifnull(format(info.referral,2),0) as referral,
         ifnull(format(info.cancellation4,2),0) as cancellation4,
         ifnull(format(info.cancellation7,2),0) as cancellation7,
         ifnull(format(info.annotationoc1,2),0) as annotationoc1,
         ifnull(format(info.annotationoc2,2),0) as annotationoc2,
         ifnull(format(info.cancellationu,2),0) as cancellationu,
         ifnull(format(info.entryfee + info.lrf + info.itfee + info.regfee+info.docstamp+info.pf + info.nf + info.nf2+info.nf3+info.ofee+info.annotationfee+info.docstamp1+info.articles+info.annotationexp+info.otransfer+info.rpt+info.handling+info.appraisal+info.filing+info.referral+info.cancellation4+info.cancellation7+info.annotationoc1+info.annotationoc2+info.cancellationu+info.mri,2),0) as totalcharges,r.isdiminishing";


        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join $info as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sbu on sbu.line=info.sbuid and sbu.issbu =1
        left join terms on terms.terms=head.terms
        where head.trno = ? and num.doc=? and num.center = ? 
        
        UNION ALL
        
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join $hinfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid
        left join reqcategory as sbu on sbu.line=info.sbuid and sbu.issbu =1
        left join terms on terms.terms=head.terms
        where head.trno = ? and num.doc=? and num.center=? ";
        return $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    } //end fn

    public function reportplotting($params, $data)
    {
        //not yet done 
        $reporttype = $params['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0: //power of attorney
                return $this->default_PO_PDF($params, $data);
                break;
            case 1: //power of attorney
                return $this->power_of_attorney_layout($params, $data);
                break;
            case 2: //deduct and remit
                return $this->deduct_and_remit_layout($params, $data);
                break;
            case 3: //COMPREHENSIVE SURETY AGREEMENT
                return $this->comprehensive_surety_agreement($params, $data);
                break;
            case 4: //DISCLOSURE
                return $this->disclosure_layout($params, $data);
                break;

            case 7: //PAYMENT SCHED
                return $this->payment_sched_layout($params, $data);
            case 5: //DOC SPA
                return $this->doc_spa_layout($params, $data);
                break;
            case 6: //PROMISSORY NOTE
                return $this->promissory_note_layout($params, $data);
                break;
            case 8: //Loan Agreement With Real Estate Mortgage
                return $this->loan_agreement_with_real_estate_mortgage_layout($params, $data);
                break;
            case 9: //Loan Agreement With Real Estate Mortgage
                return $this->real_estate_mortgage_layout($params, $data);
                break;
            case 10: //Acknowledgment Receipt and Promissory Note
                return $this->acknowledgment_receipt_and_promissory_note_layout($params, $data);
                break;
            case 11: //QUITCLAIM AND WAIVER OF RIGHTS AND INTERESTS
                return $this->quitclaim_and_waiver_of_rights_and_interests_layout($params, $data);
                break;
            case 12: //DEED OF ASSIGNMENT
                return $this->deed_of_assignment_layout($params, $data);
                break;
            case 13: //TAKE OUT FEES
                return $this->take_out_fees_layout($params, $data);
                break;
                
        }

        // if ($params['params']['dataparams']['print'] == "PDFM") {
        //     return $this->default_PO_PDF($params, $data);
        // }
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

        PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']) . 'afli.jpg', '50', '40', 100, 90);


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
        PDF::MultiCell(120, 20, number_format($data[0]->amount, 2), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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
                PDF::MultiCell(95, 20, $data[0]->bank1, 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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
               <td style="width: 150px;  text-align: left; padding: 0;">
                    <input type="checkbox" name="check11" value="1" readonly="true"/>  
                    <label for="check11" style="display: inline-block;">DISAPPROVED</label>
                </td>
                 <td style="width: 150px;  text-align: left; padding: 0;">
                    <input type="checkbox" name="check12" value="1" readonly="true"/>  
                    <label for="check12" style="display: inline-block;">APPROVED</label>
                </td>
                 <td style="width: 150px;  text-align: left; padding: 0;">
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

    public function power_of_attorney_layout($params, $data)
    {

        $center = $params['params']['center'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);


        PDF::SetFont($fontbold, '', 20);
        PDF::MultiCell(620, 0, "ASCEND FINANCE AND LEASING (AFLI) INC.", '', 'C', false, 1, '30',  '20', true, 0, false, true, 0, 'B', true);

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($font, '', 13);
        PDF::MultiCell(620, 0, 'SPECIAL POWER OF ATTORNEY', '', 'L', false, 1);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(620, 0, 'KNOW ALL MEN BY THESE PRESENTS:', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($font, '', 11);
        $maritalstat = $data[0]->civilstatus;


        $totalamt = $data[0]->amount;
        $totalamt = number_format((float)$totalamt, 2, '.', '');

        PDF::MultiCell(90, 0, 'principal amount of', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 0, '  ' . $this->reporter->ftNumberToWordsConverter($totalamt) . ' ONLY', 'B', 'L', false, 0);
        PDF::SetFont('dejavusans', '', 11);
        PDF::MultiCell(20, 0, ' ' . '(' . '₱', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(80, 0,  number_format($totalamt, 2), 'B', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(150, 0,  ') granted to and received', '', 'J', false, 1);

        $maritalstring = 'single/married';
        if ($maritalstat == 'Single') {
            $maritalstring = '<u>single</u>/married';
        } else {
            $maritalstring = 'single/<u>married</u>';
        }

        PDF::writeHTMLCell(620, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">I,<u>'.strtoupper($data[0]->clientname).'</u> of legal age, Filipino, '.$maritalstring.' and with a residence and postal address at <u>'.$data[0]->address.'</u>, Philippines for and consideration of loan in the principal amount of <u><b>' . $this->reporter->ftNumberToWordsConverter($totalamt) . '</b></u> ONLY (' . '₱ <b><u>'.number_format($totalamt, 2).'</u></b>) granted to and received by me, has named, constituted and appointed ASCEND FINANCE AND LEASING (AFLI) INC. or its authorized representative a business duly organized and existing under the laws of the Philippines with principal office and place of business at <u>'.$data[0]->address.'</u> Philippines to be my true and lawful Attorney in fact for me and in my name, place and stead, to do and perform any of the following acts and deeds:</div><br>', 0, 1);

        /////////////////
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '1. To demand, claim, collect, received and receipt of cash, check/s, treasury warrants, or any other negotiable instruments', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'involving the payment of sum of money, due and accruing to me from __________________ the said amount representing', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'my salary/ies and/or compensation, allowances, remuneration, commission, bonus from the said office.', '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '2. To demand, collect, claim, received and receipt for cash, checks, treasury warrants, or other negotiable  instruments ', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'involving in payment of the sum of money, due and accruing to me from any private firm or government which shall ', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'include but not limited to financial institutions (SSS, GSIS, banks).', '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '3. To sign in my behalf, payrolls, vouchers, or any other papers or documents involving the payment of my salary or ', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'wages, overtime pay, bonuses, allowances, remuneration, traveling or emergency allowances, and other transactions ', '', 'J', false, 1);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'involving transfer of funds to me or wherein I, a payee thereon. ', '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '4. To sign, negotiate, endorse, encash, any check/s, treasury warrant/s bill of bill of exchange, demand draft/s, money', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'order/s or any other negotiable instrument involving the payment of sum of money in my favor and to collect thereof', '', 'J', false, 1);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'from any banking or financial institutions.', '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '5. For all any of the purposes of these presents or for the purpose of securing payment of the loan received by me as a ', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'aforementioned the said Attorney in Fact is hereby authorized and empowered to do and perform such other acts as  ', '', 'J', false, 1);
        PDF::MultiCell(55, 0, '', '', 'R', false, 0);
        PDF::MultiCell(565, 0, 'may be necessary or incidental to those aforementioned.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'This Special Power of Attorney cannot be revoked in whole or in part without the written consent of the herein attorney in', '', 'J', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, 'fact, and these presents shall be null and void as soon as the loan above mentioned together with the accrued interest and all  ', '', 'J', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, 'other charges had been paid in full, otherwise the same shall continue to be in full force and effect. ', '', 'L', false, 1);


        PDF::MultiCell(0, 20, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'HEREBY GIVING AND GRANTING unto the said attorney in fact full power of authority to do and perform any and all acts  and', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, 'things reasonably proper or necessary to be done in and about the premises as fully to all intents and purposes as I might do or could do if', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, 'personally present and acting in person and hereby ratifying and conforming all that my said attorney in fact shall lawfully door cause', '', 'J', false, 1);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, 'to be done under and by virtue of these presents.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);

        $date = $data[0]->dateid;
        $dateh = date('j', strtotime($date)); //day without leading zero

        //suffics
        $day = (int)$dateh; // convert to int
        if (($day % 10 == 1) && ($day != 11)) {
            $suffix = 'st';
        } elseif (($day % 10 == 2) && ($day != 12)) {
            $suffix = 'nd';
        } elseif (($day % 10 == 3) && ($day != 13)) {
            $suffix = 'rd';
        } else {
            $suffix = 'th';
        }

        $month = date('F', strtotime($date));
        $year = date('Y', strtotime($date));

        $lastTwoDigits = substr($year, -2);

        $monthy = date('F d,Y', strtotime($date));

        PDF::MultiCell(370, 0, 'IN WITNESS WHEREOF, I hereto affix my signature this', '', 'J', false, 0);
        PDF::MultiCell(200, 0, $monthy, 'B', 'L', false, 0);
        PDF::MultiCell(50, 0, '.', '', 'L', false, 1);




        PDF::MultiCell(0, 20, "\n\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(370, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, $data[0]->clientname, 'B', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'J', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(370, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, 'Borrower', '', 'C', false, 0);
        PDF::MultiCell(50, 0, '', '', 'J', false, 1);


        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'SIGNED IN THE PRESENCE OF:', '', 'C', false, 1);


        PDF::MultiCell(0, 20, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(310, 0, '______________________', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(310, 0, '______________________', '', 'C', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 0, 'Witness', '', 'C', false, 0);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(310, 0, 'Witness', '', 'C', false, 1);



        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'REPUBLIC OF THE PHILLIPINES          )', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'ROXAS CITY                                              )', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'X-----------------------------------------X', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(80, 0, 'BEFORE ME, this', '', 'L', false, 0);
        PDF::MultiCell(40, 0, ' ' . $dateh . $suffix, 'B', 'C', false, 0);
        PDF::MultiCell(40, 0, 'day of', '', 'C', false, 0);
        PDF::MultiCell(50, 0, $month . ', ', '', 'L', false, 0);
        PDF::MultiCell(14, 0, '20', '', 'R', false, 0);
        PDF::MultiCell(14, 0, $lastTwoDigits, 'B', 'L', false, 0);
        PDF::MultiCell(382, 0, 'at Roxas City, Philippines. ', '', 'L', false, 1);


        PDF::MultiCell(0, 20, "\n");


        PDF::MultiCell(150, 0, 'NAME', '', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, 'RES. CERT. NO.', '', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, 'DATE & PLACE OF ISSUE', '', 'C', false, 1);


        // PDF::MultiCell(150, 0,  $data[0]->clientname, 'B', 'L', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(150, 0, $data[0]->num, 'B', 'L', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(150, 0, $data[0]->voiddate . ' ' . $data[0]->pliss, 'B', 'L', false, 1);


        // PDF::MultiCell(150, 0,  $data[0]->comakername, 'B', 'L', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(150, 0, $data[0]->numdays, 'B', 'L', false, 0);
        // PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        // PDF::MultiCell(150, 0, $data[0]->bday . ' ' . $data[0]->entryot, 'B', 'L', false, 1);



        PDF::MultiCell(150, 0,  '', 'B', 'L', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 1);


        PDF::MultiCell(150, 0,  '', 'B', 'L', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'Known to me and to me known to be the same person(s) who executed the foregoing instrument, and acknowledged to me that he/they ', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'executed the same as is his/their free and voluntary act and deed, for the uses and purposes herein set forth.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(570, 0, 'WITNESS MY HAND AND SEAL on the date and place first above-written.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        $docno = $data[0]->docno;
        $lastFourDigits = substr($docno, -4);
        PDF::MultiCell(620, 0, 'Doc. No. ' . $lastFourDigits, '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'Page No. 1', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'Book No.', '', 'L', false, 1);
        PDF::MultiCell(55, 0, 'Series of 20', '', 'L', false, 0);
        PDF::MultiCell(15, 0, $lastTwoDigits, 'B', 'L', false, 0);
        PDF::MultiCell(550, 0, '', '', 'L', false, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function deduct_and_remit_layout($params, $data)
    {
        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/xcalibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/xcalibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/xcalibrib.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'AUTHORITY TO DEDUCT AND REMIT', '', 'L', false, 1);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'TO: THE PAYROLL SECTION', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        //Remove commas
       // $totalamt = $data[0]->amortization*$data[0]->days;//str_replace(',', '', $this->gettotal($trno, 'principal + interest + pfnf + nf'));

       $qry = "select sum(principal) as principal,sum(interest) as interest,sum(pfnf) as pf, sum(nf) as nf,sum(mri) as mri,sum(dst) as dst from htempdetailinfo where trno=?";
       $mri = $data[0]->mri*$data[0]->days;
       $dst = $data[0]->dst*$data[0]->days;
       $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
       if (!empty($dinfo)) {
        $totalamt = $data[0]->amount+$dinfo[0]->interest+$dinfo[0]->pf+$dinfo[0]->nf+$mri+$dst;
       }else{
        $totalamt = $data[0]->amount;
       }
        
        
        //$totalamt = number_format(floor($totalamt), 2, '.', '');

        //$totalamt =ceil($totalamt );
        $tamt = $this->reporter->ftNumberToWordsConverter($totalamt,false,"PHP");

        
        $date = $data[0]->dateid;
        $reldate = $data[0]->releasedate;
        $year = date('F d, Y', strtotime($date));
        $reldate =date('F d, Y', strtotime($reldate));

        
        $pfnf1 = $data[0]->pf + $data[0]->nf;
        $monthly_amortization = round($data[0]->amortization,2);// $pfnf1 + $principal1 + $interest1;

        $monthly = strtolower($this->reporter->ftNumberToWordsConverter($monthly_amortization,false,'PHP',true));

        
        $terms = $data[0]->terms;
        $try = preg_match_all('/\d+/', $terms, $matches);
        $termhere = (int) $matches[0][0];
        $termhere = $termhere -1;

        $dateid = $data[0]->dateid; //initial date
        $finalDate = date("F d, Y", strtotime("+".$termhere." month", strtotime($date)));

        $date = new DateTime($dateid);

        //format Y-m-d
        $cdate = $date->format('F d, Y'); //initial date 

        PDF::SetFont($font, '', 11);

        
        PDF::writeHTMLCell(620, 0, '', '', '<div style="text-align: justify; text-indent: 50px;"><b>FOR AND IN CONSIDERATION</b> of the sum of <b><u>'.$tamt.'</u></b> <b>(<b>₱</b> <u>'.number_format($totalamt,2).'</u>)</b> granted to and secured by me/us and in the accordance with terms of the Promissory Note executed and delivered on <u>'.$reldate.'</u>. I/We hereby authorized ASCEND FINANCE AND LEASING (AFLI) INC. to deduct from my/our salary, allowances, compensation, and other forms of monetary compensation due me/us in the amount of PESOS: <b><u>'.trim(ucwords($monthly)).'</u></b> <b>(<b>₱</b> <u>'.number_format($monthly_amortization, 2).'</u>)</b> every 15th of payday. Effective <u>'.$cdate.'</u> and every payroll date until <u>'.$finalDate.'</u> or until fully paid.</div>', 0, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(570, 0, 'It is understood that if the payday corresponding to the 15th and 30th falls on the holiday, then the sum of the', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'foretasted shall be deductible/collected on the payday closest to the 15th  and 30th of the month.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(570, 0, 'It is expressly stipulated that where we apply for vacation/sick/maternity leave privileges, this authorization', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'extends to the deductions/collection of the amounts due and payable by me/us within the period of', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'vacation/sick/maternity leave. It is further stipulated that when I/we apply for commutations of my', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'vacation/sick/maternity leave privileges, deduction/collection of the amounts due and payable by me during the period', '', 'J', false, 1);
        PDF::MultiCell(520, 0, 'of such approved leave shall likewise be made. Such amounts due shall be made payable to ', '', 'J', false, 0);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 0, 'ASCEND FINANCE ', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'LEASING (AFLI) INC.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(570, 0, 'It is likewise expressly stipulated that in the event of payment to me/us of terminal benefits, including but', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'not limited to, retirement pay, pensions, separation pay, gratuities, terminal pay accumulated leave benefits, etc.,', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'arising from my/our resignation, retirement, separation, or termination of my services I hereby further authorized the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'Treasurer/Cashier/Disbursing Officer of my employer, or his representative to deduct/collect from such funds, monies,', '', 'J', false, 1);
        PDF::MultiCell(520, 0, 'properties, accruing to me in such amounts that would fully extinguish my obligation to', '', 'J', false, 0);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 0, 'ASCEND FINANCE', '', 'R', false, 1);

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(115, 0, 'LEASING (AFLI) INC.,', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(505, 0, 'and that no amount of funds accruing to me shall be delivered/remitted to me without fully', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'extinguish aforesaid obligations plus accrued interest thereon.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 0, '', '', 'R', false, 0);
        PDF::MultiCell(570, 0, 'Should there be any balance after said application of payment, the whole obligation shall become due and', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'demandable upon the effective date of said separation from employment as provided by the employer without notice', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'or demand.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(620, 0, 'DATE: ___________________', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(250, 0, strtoupper($data[0]->clientname), 'B', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'L', false, 0);
        PDF::MultiCell(60, 0, '', '', 'R', false, 0);
        PDF::MultiCell(250, 0, strtoupper($data[0]->comakername), 'B', 'C', false, 1);


        PDF::MultiCell(250, 0, 'Borrower', '', 'C', false, 0);
        PDF::MultiCell(60, 0, '', '', 'L', false, 0);
        PDF::MultiCell(60, 0, '', '', 'C', false, 0);
        PDF::MultiCell(250, 0, 'Co-maker', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(620, 0, 'Signed in the presence of:', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(155, 0, '__________________________', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'R', false, 0);
        PDF::MultiCell(155, 0, '__________________________', '', 'R', false, 1);


        PDF::MultiCell(155, 0, 'Witness', '', 'C', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'C', false, 0);
        PDF::MultiCell(155, 0, 'Witness', '', 'C', false, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function comprehensive_surety_agreement($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1150]);
        PDF::SetMargins(40, 40);

        $dateid = $data[0]->postdate;
        $date = new DateTime($dateid);
        $cdate = $date->format('F d, Y');

        $reldate = $data[0]->releasedate;
        $rdate = new DateTime($reldate);
        $rdate = $rdate->format('F d, Y');


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'COMPREHENSIVE SURETY AGREEMENT', '', 'C', false, 1);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(430, 0, '', '', 'R', false, 0);
        PDF::MultiCell(190, 0, $rdate, '', 'L', false, 1);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(620, 0, ' Gentlemen:', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(285, 0, 'For and in consideration of any existing indebtedness to you of', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(250, 0, strtoupper($data[0]->clientname), 'B', 'C', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(45, 0, 'legal age,', '', 'L', false, 1);


        $maritalstat = $data[0]->civilstatus;
        if ($maritalstat == 'Single') { //OK
            $maritalstring = 'married/<u>single</u>/widow';
        } elseif ($maritalstat == 'Married') { //k
            $maritalstring = '<u>married</u>/single/widow';
        } else { //OK
            $maritalstring = 'married/single/<u>widow</u>';
        }


       $qry = "select sum(principal) as principal,sum(interest) as interest,sum(pfnf) as pf, sum(nf) as nf,sum(dst) as dst,sum(mri) as mri from htempdetailinfo where trno=?";
       $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
       $mri = $data[0]->mri*$data[0]->days;
       $dst = $data[0]->dst*$data[0]->days;
    //    if (!empty($dinfo)) {
    //     $totalamt = $data[0]->amount+$dinfo[0]->interest+$dinfo[0]->pf+$dinfo[0]->nf+$dst+$mri;
    //    }else{
    //     $totalamt = $data[0]->amount;
    //    }        'change this to principal only as per glo 01.12.2026
       $totalamt = $data[0]->amount;
        //$totalamt = $data[0]->amount;
        // $totalamt = $data[0]->amortization*$data[0]->days;
        // // $totalamt = 78787817;
        // $totalamt = number_format(floor($totalamt), 2, '.', '');
        
        $numwords = $this->reporter->ftNumberToWordsConverter($totalamt,false);
        
        $addressparagraph = '<p style = "text-align: justify; line-height: 1.3;">'.$maritalstring.', Filipino with principal place of business and postal address at <u>'.$data[0]->address.'</u> (hereinafter called the Borrower) and/or in order to induce you, in your discretion at any time hereafter, to make loans or advances or increases thereof, to extend credit in any other manner, to or for the account of the Borrower, either with or without security, and/or to purchase or discount or to make any loans, or advances evidenced or secured by any notes, bills receivable, drafts, acceptances, checks or other instruments or evidences of indebtedness (all hereinafter called instruments) upon which the Borrower is or may become liable as maker, endorser, acceptor, or otherwise, the undersigned agrees to guarantee, and does hereby guarantee in joint and several capacity, the punctual payment on maturity to you of any and all said instruments, loans, advances, credits/increase and/or other obligations herein before referred to, and also any and all other indebtedness of every kind which is now or may hereafter become due or owing to you by the Borrower, together with any and all expenses which may be incurred by you in collecting all or any such instruments, or their indebtedness/obligations herein before referred to, and/or endorsing any rights hereunder, and also to make or cause any and all such payments to be made strictly in accordance with the terms and provisions of any such agreement(s), express or implied, which has(have) been or may hereafter be made or entered into by the Borrower in reference thereto, regardless of any law, regulations or decree, now or hereafter in effect, which might in any manner affect any of the terms or provisions of any such agreement(s) or your rights with respect thereto as against the Borrower, or cause or permit to be invoked any alteration in the time, amount or manner of payment by the Borrower of any such instruments, obligations, or indebtedness, provided however that the liability of the undersigned hereunder shall not exceed at any one time the aggregate principal sum of <b><u>'. (isset($numwords) ? $numwords : '') . '</u></b>(' . '₱ <b><u>' . number_format($totalamt,2).'</u></b>) irrespective of the currency (ies) in which the obligation hereby guaranteed are payable), and such interest as may accrue thereon either before or after any maturity (ies) thereof, and such expenses as may be incurred by you as referred above.</p>';
        PDF::writeHTML($addressparagraph, true, false, true, false, '');
        ///////////////



        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'As security for any and/or all indebtedness or obligations of the undersigned to you, now existing or hereafter arising hereunder,', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'or otherwise, you hereby given the right to retain, and you are hereby given a lien upon, any all moneys or other property, and/or the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'proceeds thereof, which have been, or may hereafter be, deposited or left with you (or with any third party acting on your behalf) by or', '', 'J', false, 1);


        PDF::MultiCell(620, 0, 'for the account or credit of the undersigned including (without limitation of forgoing) that in safekeeping or in which the undersigned may', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'have any interest. All remittances and property shall be deemed left with you as soon as put in transit or you by mail or carrier. If default', '', 'J', false, 1);

        PDF::MultiCell(620, 0, 'be made in the payment of any of the instruments, indebtedness, or other obligations hereby guaranteed by the undersigned, or if the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'Borrower or the undersigned should die, dissolve, fail in business or become insolvent or if a petition in bankruptcy should be filed or', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'against the Borrower, or the undersigned, or any proceedings, in bankruptcy, or under any Acts of the Government relating to the relief of', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'debtor, should be commended for the relief or readjustment of any indebtedness of the Borrower or the undersigned, either through', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'reorganization, composition, extension or otherwise, or if the Borrower or the undersigned should make an assignment for the benefit of', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'creditors or if the receiver of any property of the Borrower, or of the undersigned should be appointed at any time, or if any funds or', '', 'J', false, 1);


        PDF::MultiCell(620, 0, 'other property of the Borrower or of the undersigned which may be or come into possession or control, or that of any third party acting', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'on your behalf as aforesaid, should be attached or distrained, or become subject to any mandatory order of the court or other legal', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'process, then, or anytime after happening of any such event, any or all of the instruments, indebtedness or other obligations hereby', '', 'J', false, 1);

        PDF::MultiCell(620, 0, 'guaranteed, shall at your option become ( for the purpose of his guaranty) due and payable by the undersigned forthwith without', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'demand or notice, and full power and authority are hereby given you, in your discretion, to sell, assign and deliver all or any part of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'property, upon which you may then have a lien hereunder, at any broker\'s board, or at public or private sale, at your option, either', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'for cash or credit, or for future delivery, without assumption by you of any credit risk, and without either demand, advertisement or', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'notice of any kind all of which are hereby expressly waived. At any sale hereunder, you may at your option, purchase the whole of any', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'part of the property so sold, free from any right of redemption on the part of the undersigned, all such rights being also hereby waived', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'and released. In case of any sale or other disposition of any of the property aforesaid, after deducting all costs and expenses of every kind', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'for care, safekeeping, collection, sale, delivery or otherwise, you may apply the residue of the proceeds of the sale, or other disposition', '', 'J', false, 1);

        PDF::MultiCell(620, 0, 'thereof, to the payment or reduction, either in whole or in part of any one or more of the obligations or liabilities hereunder of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'undersigned, whether or not except for this agreement such liabilities or obligations would then be due, making proper allowances for', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'interest on obligations, if any, to the undersigned, all without prejudice to your rights as against the undersigned with respect to any', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'and all amounts which may be or remain unpaid to any of the obligations or liabilities aforesaid at anytime.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'No delay on your part in exercising any power of sale, lien, option or other right hereunder, and no notice or demand which may', '', 'R', false, 1);
        PDF::MultiCell(620, 0, 'be given to or made upon the undersigned by you with respect to any power of sale, lien, option or other right hereunder, shall constitute', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'a waiver thereof, or limit or impair your right to take any action or to exercise any power of sale, lien, option or any other rights', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'hereunder without notice or demand, or prejudice your right as against the undersigned in any respect. ', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'The word "Property" as used herein includes goods and merchandise as well as any and all documents relative thereto; all', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'securities, funds, chooses in action and any and all forms of property whether real, personal or mixed; and any right or interest of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'undersigned therein or thereto. It is understood and agreed that you shall have no liability for safekeeping of any such property beyond', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'that of according to it same degree of care, which may be given from time to time your own property of like character. ', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'The undersigned hereby consents and agree that you may at any time in your discretion: (1) grant an increase, extend or change', '', 'L', false, 1);

        PDF::MultiCell(620, 0, 'the time of payment and/or the manner, place or terms of payment of all or any such instruments, loans, advances, credits or other', '', 'J', false, 1);


        PDF::MultiCell(620, 0, 'obligation hereby guaranteed or any part(s) thereof, or any renewals), thereof (2) exchange, release and/or surrender all or any of', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'the collateral security, or any part(s) thereof, by whomsoever deposited, which is now or may hereafter be held by you in connection with', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'all or any instrument, loans, advances, credits or other obligations hereby guaranteed; (3) sell and/or purchase all or any such collateral at', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'public or private sale, or at any brokers board and after deducting all cost and expenses of every kind for collection, sale or delivery, the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'net proceeds of any sale(s) may be applied by you upon all or any of the obligation hereby guaranteed and; (4) settle or compromise with', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'the Borrower and/or any other person(s) liable thereon, any kind of instrument, loans, advances, credits or other obligations hereby', '', 'J', false, 1);

        PDF::MultiCell(620, 0, 'guaranteed and/or subordinate the payment of the same, or any part(s) thereof to the payments of any of other debts, or claims, which', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'may at anytime(s) be due or owing to you and/or any other person(s) or corporation(s); all in such manner and upon such terms as you', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'may deem proper and without notice to or further assent from the undersigned, who hereby agrees to be remain bound upon this', '', 'J', false, 1);

        PDF::MultiCell(620, 0, 'guaranty, irrespective of the existence, value or condition of any collateral and notwithstanding any such change, exchange, settlement,', '', 'J', false, 1);


        // next page here

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::MultiCell(620, 0, 'compromise, surrender, release, sale, application, renewal, extension or increase and notwithstanding also that all obligation of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'Borrower to you outstanding and unpaid anytime(s) may exceed the aggregate principal sum herein above prescribed.', '', 'J', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'This is a continuing guaranty and shall remain in full force and effect until written notice shall have been received by you that has', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'been revoked by the undersigned, but any such notice shall not release the undersigned from any liability as to any instrument, loans,', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'advances or other obligations hereby guaranteed, which may be held by you, or in which you may have any interest, at the time of your', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'receipt of such notice. No act of omission of any kind on your part in the premises shall in any event affect or impair this guaranty, nor', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'shall the same be affected by any change which may arise by reason of the death of the undersigned or of any partners) of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'undersigned, or the Borrower, or the accession to any such partnership of any one or more new partners.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'The undersigned hereby waives notice of acceptance of this guaranty, and also presentment, demand, protest, and notice of', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'dishonor of any and all such instruments, loans, advances, credits or other indebtedness or obligation herein before referred to and', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'promptness in commencing suit against any party thereto are liable thereon and/or in giving any notice to or of making any claim or', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'demand hereunder upon the undersigned.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'This guaranty shall be binding upon the undersigned, the heirs, executors, administrators, successor, and assigns of the', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'undersigned and shall inure to the benefit of, and be enforceable by you, your successors, transferees, and assigns. If this guaranty is', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'executed by two or more parties, they shall be soldierly liable hereunder; and the word "undersigned" whether used here in, shall be', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'construed to refer to each of such parties separately, all in the same manner and with the same effect as if each of them signed', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'instruments; and in any such case this guaranty shall not be revoked or impaired as to any one or more of such parties by the death of any ', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'of the others or by the revocation or release of any obligations hereunder of any one or more of such other parties.', '', 'L', false, 1);




        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'This guaranty shall he deemed to be made under and shall be governed by the laws of the Republic of the Philippines in all', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'respects, including matters of construction, validity and performance, and it is understood and agreed that none of its terms or provisions', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'may be waived, altered, or modified, or amended, except in writing dully signed for and on your behalf.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Should the Borrower at this or at any future time furnish, or should he heretofore have furnished, another surety or sureties to', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'guarantee the payment of his obligation to you, the undersigned hereby expressly waives all benefits to which the undersigned might be', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'entitled under the provisions of Article 2058 (benefit of excussion) and Article 2065 (benefit of division) of the Civil Code of the', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'Philippines, the liability of the undersigned under any and all circumstances, being joint and several. ', '', 'L', false, 1);


        //Attorneys
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'In the event of judicial proceedings being instituted by you against the undersigned to enforce any of the terms and conditions of', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'this undertaking, the undersigned further agrees to pay you a reasonable compensation R.A, as Attorney\'s fees and costs of collection,', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'which shall not in any event be less than twenty percent (20%) of the amount due; plus costs of expenses for collection.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Venue of any action arising hereunder shall be at Roxas City only. If any of the provisions of this Comprehensive Surety', '', 'J', false, 1);


        PDF::MultiCell(620, 0, 'Agreement is invalid or unenforceable, the rest of the provisions shall not be affected thereby.', '', 'L', false, 1);


        PDF::MultiCell(310, 0, '', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Very truly yours,', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(240, 0, strtoupper($data[0]->comakername), 'B', 'C', false, 1);

        PDF::MultiCell(155, 0, '', '', 'C', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Signature of Co-maker', '', 'C', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(620, 0, 'SIGNED IN THE PRESENCE OF:', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::MultiCell(155, 0, '__________________________', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'R', false, 0);
        PDF::MultiCell(155, 0, '__________________________', '', 'R', false, 1);

        PDF::MultiCell(155, 0, 'Witness', '', 'C', false, 0);
        PDF::MultiCell(155, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', '', 'C', false, 0);
        PDF::MultiCell(155, 0, 'Witness', '', 'C', false, 1);


        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'REPUBLIC OF THE PHILLIPINES          )', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'ROXAS CITY                                              )', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'X-----------------------------------------X', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'BEFORE ME, this _______________ day of ______________________, 20 ___________ at Roxas City, Philippines. ', '', 'L', false, 1);


        PDF::MultiCell(0, 20, "\n");
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::MultiCell(157, 0, 'NAME', '', 'L', false, 0);
        PDF::MultiCell(206, 0, 'RES. CERT. NO.', '', 'L', false, 0);
        PDF::MultiCell(207, 0, 'DATE & PLACE OF ISSUE', '', 'L', false, 1);

        PDF::MultiCell(207, 0, '________________________', '', 'L', false, 0);
        PDF::MultiCell(206, 0, '________________________', '', 'L', false, 0);
        PDF::MultiCell(207, 0, '________________________', '', 'L', false, 1);
        PDF::MultiCell(207, 0, '________________________', '', 'L', false, 0);
        PDF::MultiCell(206, 0, '________________________', '', 'L', false, 0);
        PDF::MultiCell(207, 0, '________________________', '', 'L', false, 1);


        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'Known to me and to me known to be the same person(s) who executed the foregoing instrument, and acknowledged to me that he/they ', '', 'J', false, 1);
        PDF::MultiCell(620, 0, 'executed the same as is his/their free and voluntary act and deed, for the uses and purposes herein set forth.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(570, 0, 'WITNESS MY HAND AND SEAL on the date and place first above-written.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(620, 0, 'Doc. No.', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'Page No.', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'Book No.', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'Series of 20 __', '', 'L', false, 1);



        PDF::MultiCell(0, 20, "\n\n");


        PDF::MultiCell(465, 0, '', '', 'R', false, 0);
        PDF::MultiCell(155, 0, '__________________________', '', 'R', false, 1);

        PDF::MultiCell(465, 0, '', '', 'C', false, 0);
        PDF::MultiCell(155, 0, 'Notary Public', '', 'C', false, 1);


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function disclosure_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'C', false, 1);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(620, 0, 'DISCLOSURE STATEMENT ON LOAN/CREDIT TRANSACTION', '', 'C', false, 1);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(620, 0, '(SINGLE PAYMENT OR INSTALLMENT PLAN)', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 13);
        PDF::MultiCell(125, 0, 'NAME OF BORROWER', '', 'L', false, 0);
        PDF::MultiCell(495, 0, strtoupper($data[0]->clientname), 'B', 'L', false, 1);

        PDF::SetFont($font, '', 13);
        PDF::MultiCell(60, 0, 'ADDRESS:', '', 'L', false, 0);
        PDF::MultiCell(560, 0, strtoupper($data[0]->address), 'B', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '1.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(80, 0, 'LOAN GRANTED', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(390, 0, '(Amount to be Finance)', '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($data[0]->amount, 2), 'B', 'R', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '2.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(120, 0, 'NON-FINANCE CHARGES', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(430, 0, '(Advanced by Creditor)', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(290, 0, 'a.     Interest ____ % P.A. From ________ to ___________            ', '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(X) simple', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(X) Monthly', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);


        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) compound', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Quarterly', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);

        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Semi-Annual', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);

        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Annual', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);

        if($data[0]->isdiminishing ==1){
            $dst =  $data[0]->dst;
            $mri =  $data[0]->mri;
        }else{
            $dst =  $data[0]->dst * $data[0]->days;
            $mri =  $data[0]->mri * $data[0]->days;
        }
        

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'b.    Insurance Premium', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($mri,2), 'B', 'R', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'c.    Taxes', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'd.    Registration Fees', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'e.    Documentary Stamps', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($dst,2), 'B', 'R', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        if($data[0]->isdiminishing ==1){
            $nf = $this->othersClass->sanitizekeyfield("amt",$data[0]->nf);
            $pf =  $data[0]->pf;
        }else{
            $nf = $data[0]->nf * $data[0]->days;
            $pf =  $data[0]->pf * $data[0]->days;
        }
        
        
        $interest = 0;
        $principal = 0;
        $qry = "select sum(principal) as principal,sum(interest) as interest from htempdetailinfo where trno=?";
        $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($dinfo)) {
            $interest = $dinfo[0]->interest;
            $principal = $dinfo[0]->principal;
        }

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'f.    Notarial', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($nf,2), 'B', 'R', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'g.    Others', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, '      ___________________', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, '      ___________________', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, '      Total Non-Finance Charges', '', 'L', false, 0);
        PDF::MultiCell(170, 0, '', '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($nf+$dst+$mri,2), 'B', 'R', false, 1);

        $clientid = $this->coreFunctions->getfieldvalue('client', "clientid", "client=?", [$data[0]->client]);
        $qry = "select format(sum(ifnull(bal,0)),2) as value from arledger where dateid<=? and left(docno,2) in ('HL','VL','LE') and clientid=?";
        $prevbal = $this->coreFunctions->datareader($qry, [$data[0]->dateid, $clientid]);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '3.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 0, 'FINANCE CHARGES', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(170, 0, '', '', 'L', false, 0); //390
        PDF::MultiCell(210, 0, 'Less: Previous balance', '', 'L', false, 0);
        PDF::MultiCell(70, 0, $prevbal, 'B', 'R', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);

        $date = $data[0]->dateid;
        $terms = $data[0]->days;
        $terms1 = $terms-1;

        $dateid = date('m/d/Y', strtotime($date . ' + ' . $terms1 . ' months'));
        $intperc = $data[0]->interest * $terms;

        PDF::MultiCell(290, 0, 'a.     Interest <u>' . number_format($intperc, 2) . '</u> % P.A. From <u>' . date("m/d/Y",strtotime($data[0]->dateid)) . '</u> to <u>' . $dateid. '</u>           ', '', 'L', false, 0, '', '', true, 0, true);

        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($interest, 2), 'B', 'R', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(X) simple', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(X) Monthly', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);


        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) compound', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Quarterly', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);

        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Semi-Annual', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);

        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '', '', 'L', false, 0);
        PDF::MultiCell(183, 0, '(  ) Annual', '', 'L', false, 0);
        PDF::MultiCell(164, 0, '', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'b.    Discounts', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'c.    Service/Handling Charges', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'd.    Collection Charges', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'e.    Credit Investigation Fees', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'f.    Appraisal fee', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'g.    Attorney’s/legal fees', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, 'h.    Other Charges incidental to the extension of credit (specify)', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(100, 0, 'PROCESSING FEE', 'B', 'C', false, 0);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($pf,2), 'B', 'R', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);


        PDF::SetFont($font, '', 13);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, '', '', 'L', false, 0);
        PDF::MultiCell(70, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(180, 0, ' ', '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(70, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, '      Total Finance Charges', '', 'L', false, 0);
        PDF::MultiCell(170, 0, '', '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        $financecharge = $pf + $dinfo[0]->interest;
        PDF::MultiCell(70, 0, number_format($financecharge, 2), 'B', 'R', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '4.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(470, 0, 'NET PROCEEDS OF LOAN ', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        $prevbal = $this->othersClass->sanitizekeyfield("amt",$prevbal);
        $netproc = $data[0]->amount - $prevbal;
        PDF::MultiCell(70, 0, number_format($netproc, 2), 'B', 'R', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '5.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(55, 0, 'Percentage', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        $percfinance = round(($financecharge/$data[0]->amount)*100,2);
        PDF::MultiCell(495, 0, 'of Finance Charges to Total amount Financed <u>'.$percfinance.'</u>% .', '', 'L', false, 1, '', '', true, 0, true);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '6.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(105, 0, 'Effective Interest Rate', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(445, 0, '(Method of Computation Attached) ______%', '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '7.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(550, 0, 'Payment', '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(110, 0, 'a.   Single Payment due ', '', 'L', false, 0);
        PDF::MultiCell(70, 0, date("m/d/Y",strtotime($data[0]->dateid)) , 'B', 'C', false, 0);
        PDF::MultiCell(270, 0, '', '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0, number_format($data[0]->amortization, 2), 'B', 'R', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(200, 0, '', '', 'R', false, 0);
        PDF::MultiCell(70, 0, '(date)', '', 'C', false, 0);
        PDF::MultiCell(70, 0, 'Prin:', '', 'R', false, 0);
        PDF::MultiCell(280, 0, number_format($data[0]->amount, 2), '', 'L', false, 1);


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(180, 0, 'b.   Total Installment Payments ', '', 'L', false, 0);
        PDF::MultiCell(70, 0, 'Int.:', '', 'R', false, 0);
        PDF::MultiCell(280, 0, number_format($interest, 2), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(180, 0, '   (Payable in <u>' . $data[0]->days . '</u> weeks/<u>months</u> @', '', 'L', false, 0, '', '', true, 0, true);
        PDF::MultiCell(70, 0, 'P.fee:', '', 'R', false, 0);
        PDF::MultiCell(200, 0, number_format($pf + $nf + $dst +$mri, 2), '', 'L', false, 0);
        PDF::MultiCell(10, 0, 'P', '', 'L', false, 0);
        PDF::MultiCell(70, 0,  number_format($pf + $nf + $interest + $data[0]->amount + $dst + $mri, 2), 'B', 'R', false, 1);

        // PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(180, 0, '(No. of Payments)', '', 'C', false, 0);
        PDF::MultiCell(350, 0, '', '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '8.', '', 'R', false, 0);
        PDF::MultiCell(10, 0, '', '', 'R', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(550, 0, 'Additional Charges in case certain stipulation in the contract are not met by debtor.', '', 'L', false, 1);

        PDF::MultiCell(0, 20, "\n");

        PDF::MultiCell(150, 0, 'Nature', '', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, 'Rate', '', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, 'Amount', '', 'C', false, 1);

        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 1);

        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(85, 0, '', '', 'C', false, 0);
        PDF::MultiCell(150, 0, '', 'B', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(420, 0, 'Certified Correct:', '', 'R', false, 0);
        PDF::MultiCell(200, 0, '', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(420, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, 'JUDITH O. ALFORTE', 'B', 'C', false, 1);

        PDF::MultiCell(420, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '(Signature of Creditor/ Authorized', '', 'C', false, 1);
        PDF::MultiCell(420, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, 'Representative Over Printed Name)', '', 'C', false, 1);

        PDF::MultiCell(420, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, '', 'B', 'R', false, 1);
        PDF::MultiCell(420, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, 'Position', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(60, 0, '', '', 'R', false, 0);
        PDF::MultiCell(560, 0, 'I acknowledged receipt of a copy of this statement prior to the consummation of the credit transaction and that I', '', 'L', false, 1);
        PDF::MultiCell(620, 0, 'understand and fully agree to the terms and conditions thereof:', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(420, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, strtoupper($data[0]->clientname), 'B', 'C', false, 1);

        PDF::MultiCell(420, 0, '', '', 'C', false, 0);
        PDF::MultiCell(200, 0, '(Signature of Borrower Over Printed Name)', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(620, 0, 'Date _________________________________', '', 'L', false, 1);


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    private function gettotal($trno, $field)
    {
        $qry = "select format(sum(" . $field . "),2) as value from htempdetailinfo where trno=?";
        return $this->coreFunctions->datareader($qry, [$trno]);
    }

    public function payment_sched_layout($params, $data)
    {
        $center = $params['params']['center'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(20, 20);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);

        PDF::MultiCell(620, 0, 'ASCEND FINANCE AND LEASING (AFLI) INC.', '', 'C', false, 1);
        PDF::MultiCell(0, 0, "\n");


        $terms = $data[0]->terms;
        $try = preg_match_all('/\d+/', $terms, $matches);

        $termhere = (int) $matches[0][0];
        PDF::SetFont($fontbold, '', 13);


        switch ($termhere) {
            case 6: // 6 months
            case 12: //12 months
            case 24:
            case 18:
                PDF::MultiCell(620, 0, $data[0]->categoryname, '', 'C', false, 1);
                break;
            case 10: //10 months
                PDF::MultiCell(620, 0, 'SCHEDULE OF LOAN AMORTIZATION', '', 'C', false, 1);
                break;
        }

        $loanterms = $data[0]->terms; 

        if($data[0]->voidint !=0){
            $loanterms = $data[0]->days . ' MOS';
        }

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 11);

        if ($termhere == 10) {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Employee\'s Name', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, $data[0]->clientname, 'TR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Loan Cycle: ', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, '', 'TR', 'L', false, 1);
        } else {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Borrower\'s Name', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, $data[0]->clientname, 'TR', 'L', false, 1);
        }

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'SBU/Department', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, $data[0]->sbu, 'TR', 'L', false, 1);

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Employer', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, $data[0]->employer, 'TR', 'L', false, 1);

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Length of Service', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, $data[0]->brgy, 'TR', 'L', false, 1);

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Current Position', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, $data[0]->country, 'TR', 'L', false, 1);

        if ($termhere == 10) {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Amount Loan', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($data[0]->amount, 2), 'TR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Loan Period', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, $loanterms, 'TR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Payment Term', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'LT', 'L', false, 0);
            PDF::MultiCell(450, 0, $data[0]->payrolltype, 'TR', 'L', false, 1);
        } else {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Loan Amount', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($data[0]->amount, 2), 'TR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Loan Term', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, $loanterms, 'BTR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Payment Mode', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, $data[0]->testpaymode, 'R', 'L', false, 1);
            // PDF::Ln(24);

            // $isemployed = '';
            // $isselfemployed = '';
            // $isplanholder = '';
            // if ($data[0]->isemployed == '1') {
            //     $isemployed = 'checked="checked"';
            // }
            // if ($data[0]->isselfemployed == '1') {
            //     $isselfemployed = 'checked="checked"';
            // }
            // if ($data[0]->isplanholder == '1') {
            //     $isplanholder = 'checked="checked"';
            // }
            // PDF::SetXY(240, PDF::GetY() - 23);
            // //first checkbox
            // $html = '
            // <form action="http://localhost/printvars.php" enctype="multipart/form-data">
            //     <input type="checkbox" name="isemployed" value="1" readonly="true" ' . $isemployed . '/>  
            //     <label for="isemployed" style="font-size: 11; display: inline-block;"> Approved with PDC</label>
            //     <input type="checkbox" name="isselfemployed" value="2" readonly="true" ' . $isselfemployed . '/> 
            //     <label for="isselfemployed" style="font-size: 11; display: inline-block;">Approved Salary Deduction</label>
            //     <input type="checkbox" name="isplanholder" value="3" readonly="true" ' . $isplanholder . '/>  
            //     <label for="isplanholder" style="font-size: 11; display: inline-block;">Approved with REM</label>
            // </form> ';
            // PDF::writeHTML($html, true, 0, true, 0);
            // PDF::Line(660, PDF::GetY() - 15, 660, PDF::GetY() - 15 + 15);
        }



        $trno = $params['params']['dataid'];

        $lay = true;
        if ($lay == false) {
            $query = "select trno, line, interest, principal, pfnf+nf as pfnf, rem, editby, editdate,mri,dst
             FROM ( select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,mri,dst from tempdetailinfo where trno=$trno
                    union all
                    select trno,line,interest,principal,pfnf,nf,rem,editby,editdate,mri,dst from htempdetailinfo where trno=$trno
                    ) AS combined_data  
                    LIMIT 1";
        } else {
            $query = "select * from (select trno,line,interest,principal,pfnf+nf as pfnf,pfnf as pf,nf,rem,editby,editdate,mri,dst from tempdetailinfo where trno=$trno
                      union all
                      select trno,line,interest,principal,pfnf+nf as pfnf,pfnf as pf,nf,rem,editby,editdate,mri,dst from htempdetailinfo where trno=$trno) as a order by line";
        }

        $data2 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $lay = false;
        $pfnf1 = $data2[0]['pfnf'] != 0 ? $data2[0]['pfnf'] : 0; //combine of notarial and processing fee
        // $pr_fee = $pfnf1 / 2 * $data[0]->terms;
        $terms = $data[0]->terms;
        $try = preg_match_all('/\d+/', $terms, $matches);
        $termhere = (int) $matches[0][0];

        $pr_fee = ($pfnf1 / 2) * $termhere;
        //$tdst = $data2[0]['dst']*$termhere;
        $tdst = $data[0]->dst*$termhere;//info.docstamp monthly
        $tmri = $data[0]->mri*$termhere;
        $infopf = round($data[0]->pf*$termhere,2);//total pf eainfo
        $infonf = round($data[0]->nf*$termhere,2);//total nf eainfo


        $r = $data[0]->interest != 0 ? $data[0]->interest : 0; // 1.5
        $rate = $r / 100; // 0.015 float 
        $totalamt = $data[0]->amount;
        $totalamt = (float) $totalamt;
        

        if($data[0]->isdiminishing == 1){
            $total_interest = $this->coreFunctions->datareader("select sum(interest) as value from (".$query.") as a");
        }else{
            $total_interest = $totalamt * $rate * $termhere;
        }
    

        $principal1 = $data2[0]['principal'];
        $interest1 = $data2[0]['interest'] != 0 ? $data2[0]['interest'] : 0;
        $hdst = $tdst;
        $hmri = $tmri;
        if($data[0]->isdiminishing ==0){
            $dst = $data2[0]['dst'];
            $mri = $data2[0]['mri'];
           // $hdst = $data[0]->dst;
            $monthly_amortization = $pfnf1 + $principal1 + $interest1 + $dst + $mri;
        }else{
            $monthly_amortization = $pfnf1 + $principal1 + $interest1;
            $hdst = $tdst;
        }
       
        //$monthly_amortization = $pfnf1 + $principal1 + $interest1 + $dst + $mri;

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Add on rate', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($r, 2) . '%', 'TR', 'L', false, 1);


        if ($termhere == 10) {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Annual Interest', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($total_interest, 2), 'TR', 'L', false, 1);
        } else {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Total Interest', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($total_interest, 2), 'TR', 'L', false, 1);
        }

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'DST', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($hdst, 2), 'TR', 'L', false, 1);

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'MRI', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($tmri, 2), 'TR', 'L', false, 1);

        $otherfees = $pr_fee;
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Other Fees/Notarial', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($infonf, 2), 'TR', 'L', false, 1);

        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Processing Fee', 'T', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($infopf, 2), 'TR', 'L', false, 1);
        $totalint = $data2[0]['interest'] != 0 ? $data2[0]['interest'] * $data[0]->days : 0;
 
        if($data[0]->isdiminishing == 1){
            $beginningbal = $totalamt +  $total_interest;
        }else{
            $dst = $data2[0]['dst'];
            $mri = $data2[0]['mri'];
            $beginningbal = $totalamt + $infonf + $infopf + $total_interest + $tdst + $tmri;
        }
        

        if ($termhere == 10) {
            // $purpose = $data[0]->purpose != '' ? $data[0]->purpose : '';
            $purpose = $data[0]->purpose;
            $monthlydeduction = $beginningbal / 12;
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Purpose of Loan', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0,  $purpose, 'TR', 'L', false, 1);

            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Monthly Deduction', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($monthlydeduction, 2), 'TR', 'L', false, 1);
        } else {
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(150, 0, 'Monthly Amortization', 'T', 'L', false, 0);
            PDF::MultiCell(10, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(450, 0, number_format($monthly_amortization, 2), 'TR', 'L', false, 1);
        }

        PDF::MultiCell(10, 0, '', 'TBL', 'L', false, 0);
        PDF::MultiCell(150, 0, 'Total Payable', 'TB', 'L', false, 0);
        PDF::MultiCell(10, 0, '', 'TBL', 'L', false, 0);
        PDF::MultiCell(450, 0, number_format($beginningbal, 2), 'TRB', 'L', false, 1);

        if ($termhere == 10) {
            PDF::MultiCell(620, 0, 'AMORTIZATION SCHEDULE ', 'TLR', 'C', false, 1);
            PDF::MultiCell(100, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(100, 0, 'Scheduled', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'TLR', 'C', false, 1);
            PDF::MultiCell(80, 0, '', 'TLR', 'C', false, 1);
            PDF::MultiCell(80, 0, '', 'TLR', 'C', false, 1);

            PDF::MultiCell(100, 0, 'Payment No.', 'BL', 'C', false, 0);
            PDF::MultiCell(100, 0, 'Payment Date', 'LB', 'C', false, 0);
            PDF::MultiCell(100, 0, 'Payment', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Principal', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Interest', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Processing', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Other Fees', 'LBR', 'C', false, 1);
            PDF::MultiCell(80, 0, 'DST', 'LBR', 'C', false, 1);
            PDF::MultiCell(80, 0, 'MRI', 'LBR', 'C', false, 1);
        } else {
            PDF::MultiCell(40, 0, '', 'T', 'L', false, 0);
            PDF::MultiCell(560, 0, 'AMORTIZATION SCHEDULE ', 'T', 'C', false, 1);
            PDF::MultiCell(40, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(75, 0, 'Beginning', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Processing Fee', 'TL', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'TL', 'L', false, 0);
            PDF::MultiCell(50, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Total Monthly', 'TL', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Ending', 'TLR', 'C', false, 1);

            PDF::MultiCell(40, 0, 'Period', 'LB', 'C', false, 0);
            PDF::MultiCell(100, 0, 'Installment', 'LB', 'C', false, 0);
            PDF::MultiCell(75, 0, 'Balance', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'and Other Fees', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, 'Principal', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, 'Interest', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, 'DST', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, 'MRI', 'LB', 'C', false, 0);            
            PDF::MultiCell(80, 0, 'Amortization', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, 'Balance', 'LBR', 'C', false, 1);
        }


        $lay = true;
        $totalpfnf = 0;
        $totalprincipal = 0;
        $totalint = 0;
        $totaldst =0;
        $totalmri =0;
        $totalpf =0;
        $total_monthly = 0;
        $total_pfnf = 0;
        $total_other_fee = 0;
        $pf =0;
        $adjmri =0;

        $dateid = $data[0]->dateid; //initial date
        $date = new DateTime($dateid);

        // if($data[0]->isdiminishing == 1){
        //     $beginningbal = $totalamt;
        // }

        if ($termhere != 10) {
            PDF::MultiCell(40, 0, '0', 'BL', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(75, 0, number_format($beginningbal, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'LBR', 'C', false, 1);
        }

        $try = 0;

        for ($i = 0; $i < count($data2); $i++) {
            $pfnf = $data2[$i]['pfnf'] != 0 ? $data2[$i]['pfnf'] : 0;
            $principal = $data2[$i]['principal'];
            $interest = $data2[$i]['interest'] != 0 ? $data2[$i]['interest'] : 0;
            $amortization = $pfnf + $principal + $interest;
            $dst = $data2[$i]['dst'];
            $mri = $data2[$i]['mri'];
            $pf = $data2[$i]['pf'];
            $nf = $data2[$i]['nf'];

            if($data[0]->isdiminishing == 0){
                $amortization = $pfnf + $principal + $interest+$dst+$mri;
            }

            $installmentdate = $date->format('F d, Y');
            $try++;
            if($i == count($data2)-1){
                $adjprin = $data[0]->amount - ($totalprincipal + $principal);
                if($adjprin !=0){
                    $principal = $principal+$adjprin;
                    $amortization = $amortization + $adjprin;
                }
                
            }
            if($i == count($data2)-1){
                $adjint = $total_interest - ($totalint + $interest);
                if($adjint !=0){
                    $interest = $interest+$adjint;
                    $amortization = $amortization + $adjint;
                }
                
            }

            if($i == count($data2)-1){
                $adjdst = $hdst - ($totaldst + $dst);
                if($adjdst !=0){
                    $dst = $dst+$adjdst;
                    $amortization = $amortization + $adjdst;
                }
                
            }

            if($i == count($data2)-1){
                $adjpf = $infopf - ($totalpf + $pf);
                if($adjpf !=0){
                    $pf = $pf+$adjpf;
                    $amortization = $amortization + $adjpf;
                }
                
            }

            if($i == count($data2)-1){
                $adjmri = $hmri - ($totalmri + $mri);
                if($adjmri !=0){
                    $mri = $mri+$adjmri;
                    $amortization = $amortization + $adjmri;
                }                
            }


            if ($termhere == 10) {
                $pfnf = $data2[$i]['pfnf'] != 0 ? $data2[$i]['pfnf'] / 2 : 0;
                $other_fee = $data2[$i]['pfnf'] != 0 ? $data2[$i]['pfnf'] / 2 : 0;
                PDF::MultiCell(100, 0, $try, 'BL', 'C', false, 0);
                PDF::MultiCell(100, 0, ' ' . $installmentdate, 'LB', 'C', false, 0);
                PDF::MultiCell(100, 0, number_format($amortization, 2), 'LB', 'C', false, 0);
                
                PDF::MultiCell(80, 0, number_format($principal, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(80, 0,  number_format($interest, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(80, 0, number_format($pfnf, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(80, 0,  number_format($other_fee, 2), 'LBR', 'C', false, 1);
                $total_other_fee += $other_fee;
                $total_pfnf += $pfnf;
            } else {
                $pfnf = $pf+$nf;
                PDF::MultiCell(40, 0, $i+1, 'BL', 'L', false, 0);
                PDF::MultiCell(100, 0, '  ' . $installmentdate, 'LB', 'L', false, 0);
                if ($i == 0) {
                    $beg_bal = $beginningbal;
                    PDF::MultiCell(75, 0,  number_format($beg_bal, 2), 'LB', 'C', false, 0);
                } elseif ($i == 1) {
                    $beg_bal -= $amortization;
                    PDF::MultiCell(75, 0,  number_format($beg_bal, 2), 'LB', 'C', false, 0);
                } else {
                    $beg_bal -= $amortization;
                    PDF::MultiCell(75, 0,  number_format(round($beg_bal), 2), 'LB', 'C', false, 0);
                }
                // PDF::MultiCell(75, 0,  number_format($beginningbal, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(80, 0, number_format($pfnf, 2), 'LB', 'C', false, 0);
                // if($i == count($data2)-1){
                //     $adjprin = $data[0]->amount - ($totalprincipal + $principal);
                //     if($adjprin !=0){
                //         $principal = $principal+$adjprin;
                //         $amortization = $amortization + $adjprin;
                //     }
                    
                // }
                PDF::MultiCell(50, 0, number_format($principal, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(50, 0, number_format($interest, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(50, 0, number_format($dst, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(50, 0, number_format($mri, 2), 'LB', 'C', false, 0);
                PDF::MultiCell(80, 0, number_format($amortization, 2), 'LB', 'C', false, 0);
            }

            if ($termhere != 10) {
                if ($i == 0) {
                    $ending_bal = $beginningbal - $amortization;
                   
                    PDF::MultiCell(80, 0,  number_format($ending_bal, 2), 'LBR', 'C', false, 1);
                } else {
                    $ending_bal -= $amortization;
                    PDF::MultiCell(80, 0,  number_format(round($ending_bal), 2), 'LBR', 'C', false, 1);
                }
            }

            $totalpfnf += ($pf+$nf);//$data2[$i]['pfnf'] != 0 ? $data2[$i]['pfnf'] : 0;
            $totalprincipal += $principal;
            $totalint += $interest;// $data2[$i]['interest'] != 0 ? $data2[$i]['interest'] : 0;
            $total_monthly += $amortization;
            $totaldst +=$dst;
            $totalmri +=$mri;
            $totalpf +=$pf;
            $interval = new DateInterval('P1M'); // 'P1M' stands for 1 month
            $date->add($interval);
        }


        if ($termhere == 10) {
            PDF::MultiCell(100, 0, '', 'B', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'B', 'C', false, 0);
            PDF::MultiCell(100, 0, number_format($total_monthly, 2), 'B', 'C', false, 0);
            PDF::MultiCell(80, 0, number_format($totalprincipal, 2), 'B', 'C', false, 0);
            PDF::MultiCell(80, 0,  number_format($totalint, 2), 'B', 'C', false, 0);
            PDF::MultiCell(80, 0, number_format($total_pfnf, 2), 'B', 'C', false, 0);
            PDF::MultiCell(80, 0,  number_format($total_other_fee, 2), 'B', 'C', false, 1);
        } else {
            PDF::MultiCell(40, 0, '', 'BL', 'L', false, 0);
            PDF::MultiCell(100, 0, '', 'LB', 'C', false, 0);
            PDF::MultiCell(75, 0, ' Total', 'LB', 'L', false, 0);
            PDF::MultiCell(80, 0, number_format($totalpfnf, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, number_format($totalprincipal, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, number_format($totalint, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, number_format($totaldst, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(50, 0, number_format($totalmri, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, number_format($total_monthly, 2), 'LB', 'C', false, 0);
            PDF::MultiCell(80, 0, '', 'LBR', 'C', false, 1);
        }


       // if ($termhere != 18) {
            PDF::MultiCell(0, 0, "\n\n\n");
            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'Conforme: ', '', 'L', false, 0);
            PDF::MultiCell(315, 0, strtoupper($data[0]->clientname), 'B', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);

            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, '', '', 'C', false, 0);
            PDF::MultiCell(315, 0, 'SIGNATURE ABOVE  PRINTED NAME', '', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);

            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'DATE: ', '', 'L', false, 0);
            PDF::MultiCell(315, 0, '', 'B', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);
        //}

        //if ($termhere == 12 || $termhere == 10) {
            PDF::MultiCell(0, 0, "\n\n\n");
            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'Verified By: ', '', 'L', false, 0);
            PDF::MultiCell(315, 0, '', 'B', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);

            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, '', '', 'C', false, 0);
            PDF::MultiCell(315, 0, 'SIGNATURE ABOVE  PRINTED NAME', '', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);

            PDF::MultiCell(40, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'DATE: ', '', 'L', false, 0);
            PDF::MultiCell(315, 0, '', 'B', 'C', false, 0);
            PDF::MultiCell(165, 0, '', '', 'C', false, 1);
       // }


        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function doc_spa_layout($params, $data)
    {

        $center = $params['params']['center'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'SPECIAL POWER OF ATTORNEY', '', 'C', false, 1);
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'KNOW ALL MEN BY THESE PRESENTS:', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");


        $civilstat = 'single/married';

        switch (strtolower($data[0]->civilstatus)) {
            case 'single':
                $civilstat = '<u>single</u>/married';
                break;
            case 'married':
                $civilstat = 'single/<u>married</u>';
                break;
        }

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'I/WE <u>' . $data[0]->clientname . '</u>, Filipino citizen/s, of legal age, ' . $civilstat . ', with residence and postal address at', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, '<u>' . $data[0]->address . '</u> do hereby name, constitute,  and  appoint 
        <u>' . $data[0]->attorneyinfact . '</u> of legal age, single/married, with residence and postal address at <u>' . $data[0]->attorneyaddress . '</u> to be my/our true and lawful Attorney-in-Fact for me/us in my/our name, place and stead, to do and perform the following acts, to wit:', '', 'L', false, 1, '', '', true, 0, true);


        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(100, 0, '1.', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'To execute, sign and deliver loan application and mortgage contract in', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'connection with my/our application from the ASCEND FINANCE AND', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'LEASING (AFLI) INC. for a loan in any amounts that may be approved by the', '', 'L', false, 1);


        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'said financial institution, offering as security for the payment of said loan', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, '1.the property/ies situated at ________________________________________ and registered', '', 'L', false, 1);


        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'under TCT No.__________ , Lot No.__________ , Block No.__________ ,', '', 'L', false, 1);


        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'Survey No.__________ of the Registry of Deeds for', '', 'L', false, 1);


        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, '____________________ under such terms and conditions and under such ', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'covenants which my/our said Attorney-in-Fact may deem proper and convenient;', '', 'L', false, 1);


        PDF::MultiCell(100, 0, '2.', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'To execute, sign and deliver Promissory Notes in favor of the ASCEND', '', 'L', false, 1);
        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'FINANCE AND LEASING (AFLI) INC., as well as other documents that may', '', 'L', false, 1);
        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'be required by the said financial institution in connection with the said loan;', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '3.', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'To receive the proceeds of the Promissory Notes which my said Attorney-in-Fact', '', 'L', false, 1);

        PDF::MultiCell(100, 0, '', '', 'R', false, 0);
        PDF::MultiCell(520, 0, 'may execute in connection with the mortgage contract relative thereto;', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'L', false, 0);
        PDF::MultiCell(580, 0, 'HEREBY GIVING AND GRANTING unto my said Attorney-in-Fact full power and authority to do and perform each and every act', '', 'L', false, 1);

        PDF::MultiCell(620, 0, 'which may be necessary or convenient, in connection with any of the foregoing as fully to all intents and purposes as I might or could do, if personally present and acting in person, HEREBY RATIFYING AND CONFIRMING all that my said Attorney-in-Fact may also do or cause to be done under and by virtue of these presents.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'L', false, 0);
        PDF::MultiCell(580, 0, 'IN WITNESS WHEREOF, I have hereunto set my hand this __________ day of __________, 20___ at the Province/City of.', '', 'L', false, 1);

        PDF::MultiCell(620, 0, '________________________________________', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::SetFont($font, '', 11);
        PDF::MultiCell(260, 0, $data[0]->attorneyinfact, 'B', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, $data[0]->clientname, 'B', 'C', false, 1, '', '', true, 0, true);




        PDF::SetFont($font, '', 11);
        PDF::MultiCell(260, 0, 'Attorney-in-Fact', '', 'C', false, 0);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, 'Principal', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");


        PDF::MultiCell(120, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(20, 0, 'No.', '', 'C', false, 0);
        PDF::MultiCell(120, 0, '', 'B', 'C', false, 0);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);

        PDF::MultiCell(120, 0, '', 'B', 'C', false, 0);
        PDF::MultiCell(20, 0, 'No.', '', 'C', false, 0);
        PDF::MultiCell(120, 0, '', 'B', 'C', false, 1);



        PDF::MultiCell(60, 0, 'Date of Issue', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', 'B', 'C', false, 0);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);

        PDF::MultiCell(60, 0, 'Date of Issue', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', 'B', 'C', false, 1);


        PDF::MultiCell(60, 0, 'Expiry Date', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', 'B', 'C', false, 0);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);

        PDF::MultiCell(60, 0, 'Expiry Date', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '', 'B', 'C', false, 1);


        PDF::MultiCell(0, 0, "\n");


        PDF::MultiCell(360, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, 'With marital consent', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(360, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, '', 'B', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'SIGNED IN THE PRESENCE OF:', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 3);
        PDF::MultiCell(260, 0, '', 'T', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, '', 'T', 'L', false, 1);


        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1150]);
        PDF::SetMargins(40, 40);


        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'ACKNOWLEDGMENT', '', 'C', false, 1);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(620, 0, 'REPUBLIC OF THE PHILIPPINES( PROVINCE/CITY OF __________) S.S.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 11);

        PDF::MultiCell(40, 0, '', '', 'C', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(60, 0, 'BEFORE ME,', '', 'R', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(520, 0, 'a Notary Public for and in the ____________________, Province of ______________________________, this', '', 'L', false, 1);

        PDF::MultiCell(620, 0, ' __________ day of ____________________, 20___ , personally appeared the above-named person/s, who has satisfactorily proven to me his/her/their identity through his/her/their identifying documents written below his/her/their name and signature, that they are the same person/s who executed and voluntarily signed the foregoing Special Power of Attorney, duly signed by his/her/their instrumental witnesses at the spaces herein provided which he/she/they acknowledged to me as his/her/their free and voluntary act and deed.', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(40, 0, '', '', 'C', false, 0);
        PDF::MultiCell(580, 0, 'The foregoing instrument relates to a Special Power of Attorney consisting of __________(_) pages including the page on which', '', 'L', false, 1);
        PDF::MultiCell(620, 0, ' this Acknowledgment is written, has been signed on the left margin of each and every page by the parties and the  witnesses.', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(40, 0, '', '', 'C', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 0, 'WITNESS MY HAND AND NOTARIAL SEAL,', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(400, 0, 'this __________ day of ____________________, 20___ in the ', '', 'L', false, 1);


        PDF::MultiCell(620, 0, '________________________________________, Province of ___________________________________', '', 'L', false, 1);


        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(620, 0, 'NOTARY PUBLIC', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 11);

        PDF::MultiCell(50, 0, 'Doc. No.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'R', false, 0);
        PDF::MultiCell(470, 0, ';', '', 'L', false, 1);

        PDF::MultiCell(50, 0, 'Page No.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'R', false, 0);
        PDF::MultiCell(470, 0, ';', '', 'L', false, 1);

        PDF::MultiCell(50, 0, 'Book No.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'R', false, 0);
        PDF::MultiCell(470, 0, ';', '', 'L', false, 1);

        PDF::MultiCell(50, 0, 'Series of', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'R', false, 0);
        PDF::MultiCell(470, 0, '.', '', 'L', false, 1);



        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function promissory_note_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [700, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(620, 0, 'PROMISSORY NOTE', '', 'C', false, 1);
        PDF::MultiCell(0, 0, "\n");


        PDF::MultiCell(150, 0, '', 'B', '', false, 0);
        PDF::MultiCell(160, 0, '', '', '', false, 0);
        PDF::MultiCell(100, 0, 'No.', '', 'R', false, 0);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(100, 0, $data[0]->docno, 'B', 'L', false, 0);
        PDF::MultiCell(110, 0, '', '', 'L', false, 1);

        PDF::MultiCell(150, 0, '', '', '', false, 0);
        PDF::MultiCell(160, 0, '', '', '', false, 0);
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(100, 0, 'Date.', '', 'R', false, 0);
        PDF::MultiCell(100, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(110, 0, '', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', 11);


        $nf = $data[0]->nf * $data[0]->days;
        $pf =  $data[0]->pf * $data[0]->days;
        $dst = $data[0]->dst * $data[0]->days;
        $interest = 0;
        $principal = 0;
        $mri = $data[0]->mri * $data[0]->days;
        $qry = "select sum(principal) as principal,sum(interest) as interest,sum(mri) as mri from htempdetailinfo where trno=?";
        $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($dinfo)) {
            $interest = $dinfo[0]->interest;
            $principal = $dinfo[0]->principal;
            //$mri = $dinfo[0]->mri;
        }

        // LOAN AMOUNT + TOTAL INTEREST + TOTAL PF & NF
        $cc = $data[0]->amount + $interest + $pf + $nf + $mri+$dst;
        $charge = $data[0]->interest * $data[0]->days;
        $dd = number_format((float)$cc, 2, '.', '');

        PDF::MultiCell(620, 0, 'its office at Punta Dulog Commercial Complex, St. Joseph Ave., Pueblo de Panay Township, Brgy. Lawaan, Roxas City, Philippines the sum of <u>' . $this->reporter->ftNumberToWordsConverter($dd) . '</u> Pesos (P <u>' . number_format($dd, 2) . '</u>) Philippine Currency together with interest thereon at the rate of <u>' . $data[0]->interest . '</u> % per month until paid. I/We also agree to pay jointly and severally,  <u>' . $charge . '</u> % per annum penalty charge and by way of liquidated damages, should this note be unpaid or it is not renewed on due date. ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Payment of this note shall be as follows: ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        $day = date('j', strtotime($data[0]->dateid)); //day without leading zero
        $ordinalday = $this->othersClass->getOrdinal($day);
        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Installment Amount: P <u>' . number_format($data[0]->amortization, 2) . '</u> payable every <u>' . $ordinalday . '</u> of each next month starting <u>' . date('F j, Y', strtotime($data[0]->dateid)) . '</u>. ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Without need for notice or demand, failure to pay any installments thereon when due shall constitute default and the entire', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, 'principal of this notes including interest, penalties and charges if any, shall immediately become due and demandable.', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'In the event that this notice is not paid at maturity or the same becomes due under any of the provisions hereof, I/We hereby', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, ' authorize <b>ASCEND FINANCE AND LEASING (AFLI) INC.</b>, at its option and without notice, to apply the payment of this note any and all monies, securities and things or value which may be in its hands on deposit or otherwise belonging to me/us and for this purpose, I/We hereby, jointly and severally, irrevocably constitute and appoint <b>ASCEND FINANCE AND LEASING (AFLI) INC.</b>, to be my/our Attorney in Fact with full power and authority for me/us and in my/our name and behalf, without prior notice, to negotiate, sell and transfer any monies, securities and things or value which it may hold by public or private sale and apply the proceeds thereof to the payment of this note.', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'It is likewise understood that any partial payment or performance of this note or any extension granted will not alter or vary the ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, 'terms of the original conditions of the obligations nor discharge the same and such partial payment or performance shall be considered as a written acknowledgement of this obligation which shall interrupt the period prescription.', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'I/We hereby expressly consent to be bound to any extension of payment and/or renewal of this note in whole or in part as well', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, 'as to the terms of payment and/or any partial payment on this note which may be granted to any one of us, without notice and/or without consent and without need of executing a new or a renewal note. I/We hereby agree that any interest collected in advance on the original note will not be refunded as interest rebates in the event, renewal is granted to any one of us or the whole obligation, was prepaid or paid in advance.', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Should it become necessary to collect this note through a lawyer, I/We hereby expressly agree to pay jointly and severally, ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, "<u>" . number_format($dd, 2) . "</u> of the total amount due on this note as attorney's fees which in no case shall be less than P 10,00.00 exclusive of all costs and fees allowed by law.", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'Installment/payment not paid when due shall be computed every 30 days added to, and become integral part of the principal ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, "and shall likewise bear interest at the same rate of indicated hereon.", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'I/We further agree to pay <b>ASCEND FINANCE AND LEASING (AFLI) INC.</b>, a service charge at the highest rate authorized ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, "by LAW at the time of incurrence of this obligation and the maximum of such other charges, fines or penalties that the LAW at hereinafter and/or from time to time authorized or allow <b>ASCEND FINANCE AND LEASING (AFLI) INC.</b>, to collect. ", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, '<b>Demand and Dishonor Waived.</b> Holder may accept partial payments and grants renewals or extensions of payment reserving its ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, "right of recourse against the accommodation co-makers and each and all endorsers to this note. ", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'R', false, 0);
        PDF::MultiCell(580, 0, 'In case judicial execution of this obligation or any part of it, I/we hereby waived all my/our rights under the provisions of Rule ', '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(620, 0, "39 Sec. 12 of the Revised Rules of Court.", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(210, 0, $data[0]->clientname, 'B', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(220, 0, $data[0]->comakername, 'B', 'C', false, 1, '', '', true, 0, true);

        PDF::MultiCell(210, 0, '(Signature over Printed Name)', '', 'C', false, 0);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(220, 0, '(Signature over Printed Name)', '', 'C', false, 1);


        PDF::MultiCell(210, 0, "<b>Borrower</b>", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(90, 0, '', '', 'L', false, 0);
        PDF::MultiCell(220, 0, "<b>Co-Maker</b>", '', 'C', false, 1, '', '', true, 0, true);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(620, 0, 'SIGNED IN THE PRESENCE OF:', '', 'C', false, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(40, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(115, 0, '', '', 'L', false, 0);
        PDF::MultiCell(40, 0, '', '', 'L', false, 0);
        PDF::MultiCell(155, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(115, 0, '', '', 'L', false, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(620, 0, "SUBSCRIBED and sworn to before me, in the city/municipality of ______________, this ___________ day of __________,___________", '', 'L', false, 1, '', '', true, 0, true);
        PDF::MultiCell(620, 0, "by <u>(insert name)</u> with <u>(insert I.D. Type)</u> No. ________________ issued at ___________________ on ______________, 20_____.", '', 'L', false, 1, '', '', true, 0, true);


        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "NOTARY PUBLIC", '', 'C', false, 1, '', '', true, 0, true);

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "My commission expires Dec. 31, 20__", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "Not. Reg. No._____________________;", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "Page No.________________________ ;", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "Book ___________________________;", '', 'L', false, 1, '', '', true, 0, true);

        PDF::MultiCell(430, 0, "", '', 'C', false, 0, '', '', true, 0, true);
        PDF::MultiCell(190, 0, "Series of 20___________", '', 'L', false, 1, '', '', true, 0, true);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function loan_agreement_with_real_estate_mortgage_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);
        PDF::MultiCell(720, 0, 'LOAN AGREEMENT', '', 'C', false, 1);
        PDF::MultiCell(720, 0, 'With', '', 'C', false, 1);
        PDF::MultiCell(720, 0, 'REAL ESTATE MORTGAGE', '', 'C', false, 1);
        PDF::MultiCell(0, 0, "\n");
        
        $date = strtotime($data[0]->releasedate);
        $day = date('j',$date);
        $ordinalday = $this->othersClass->getOrdinal($day);
        $month = date('F',$date);
        $year = date('Y',$date);
        PDF::SetFont($font, '', 16);
        PDF::writeHTMLCell(720, 0, '', '', 'This LOAN AGREEMENT with REAL ESTATE MORTGAGE (“Agreement”), entered into this <u>'.$ordinalday.'</u> day of <u>'.$month.'</u> '.$year.' in <u>Roxas City, Capiz,</u> by and between:', 0, 1, false, true, 'J');

        // MultiCell(100, 0, '', '','C', false, 1, '', '', true, 0, $ishtml=false) {
        // writeHTMLCell(100, 0, '', '', $html='', 0, 0)
        
        // public function writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true) {
        //     return $this->MultiCell($w, $h, $html, $border, $align, $fill, $ln, $x, $y, $reseth, 0, true, $autopadding, 0, 'T', false);
        // }


        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(570, 0, '', '', '<b>ASCEND FINANCE AND LEASING (AFLI) INC.,</b> a Corporation duly organized and existing under the laws of the Philippines, with office address at Punta Dulog Commercial Complex, St. Joseph Ave., Pueblo de Panay Township, Brgy. Lawaan, Roxas City, represented herein by JOSE NERY D. ONG, and  hereinafter referred to as the “LENDER”;', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(720, 0, '-and-', '', 'C', false, 1);
        
        PDF::MultiCell(0, 0, "\n");
        $address = $data[0]->address;
        $fullname = $data[0]->fname.' '.$data[0]->mname.' '.$data[0]->lname;
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(570, 0, '', '', '<u>'.$fullname.',</u> Filipino, of legal age, married, with address at <u>'.$address.'</u>, and hereinafter referred to as the “BORROWER”;', 0, 1);
        
        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(570, 0, '', '', 'Collectively known as the “PARTIES”,', 0, 1);
        
        PDF::MultiCell(0, 0, "\n\n");

        PDF::MultiCell(720, 0, '-WITNESSETH-', '', 'C', false, 1);
        
        PDF::MultiCell(0, 0, "\n\n");

       
        $interest = 0;
        $principal = 0;
        $qry = "select sum(principal) as principal,sum(interest) as interest,sum(mri) as mri from htempdetailinfo where trno=?";
        $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($dinfo)) {
            $interest = $dinfo[0]->interest;
            $principal = $dinfo[0]->principal;
        }

        $totalloan = $data[0]->amount + $interest;
        $loaninwords = $this->reporter->ftNumberToWordsBuilder($totalloan);
        PDF::writeHTMLCell(720, 0, '', '', 'WHEREAS, the BORROWER has requested a total loan of <u><b>'.$loaninwords.'</b></u> (₱ <u><b>'.number_format($totalloan,2).'</b></u>) from the LENDER (“Loan”) to finance the
        purchase of a house and lot from Pueblo de Panay, Inc. (“PDPI”);”,', 0, 1);

        PDF::MultiCell(0, 0, "\n\n");
        // placeholder
        $tctno = '097-2019000054';
        PDF::writeHTMLCell(570, 0, '', '', 'WHEREAS, the house and lot to be purchased by the BORROWER is covered by TCT No. <u><b>'.$tctno.'</b></u>, more particularly described as follows:', 0, 1);

        

        PDF::MultiCell(0, 0, "\n");
        
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(720, 0, 'Condominium Certificate of Title ', '', 'C', false, 1);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(720, 0, "OWNERS DUPLICATE • OWNERS DUPLICATE • OWNER'S DUPLICATE • OWNERS DUPLICATE • OWNER'S", '', 'C', false, 1);
        PDF::MultiCell(720, 0, "DUPLICATE ", '', 'C', false, 1);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(720, 0, 'No. '.$tctno.'', '', 'C', false, 1);


        PDF::SetFont($font, '', 16);
        
        PDF::MultiCell(0, 0, "\n\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IT IS HEREBY CERTIFIED that the unit identified and described as: ("SITIO UNO") UNIT NO. 323 THIRD FLOOR WITH AN AREA OF 32.12 SO. M., MORE OR LESS. in the diagrammatic floor plan appended to the enabling or master deed of the condominium project annotated on TRANSFER CERTIFICATE OF TITLE 2017001465 which embraces and describes the land located at BARANGAY LAWAAN, ROXAS CITY with an area Of FOUR THOUSAND FIVE HUNDRED SEVENTY-EIGHT (1,578) Square Meter, is registered in the name of:</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;"><b>Owner: PUEBLO DE PANAY, INC., A CORPORATION DULY ORGANIZED AND EXISTING UNDER AND BY VIRTUE OF THE LAWS OF THE </b>Address: GOV. HERNANDEZ AVENUE, ROXAS CITY, CAPIZ WESTERN VISAYAS (continued on next pages )</div>', 0, 1);


        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::SetFont($font, '', 16);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">as owner(s) of said unit in fee simple, with all the incidents provided by the Condominium to such encumbrances noted on this condominium certificate of title and on the certificate of title of the land as may affect the unit; to those mentioned in the enabling or master deed and declaration of restrictions; and to those provided by law. </div>', 0, 1);

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">This condominium certificate is a transfer from (Not Applicable, Original), which is/are cancelled by virtue hereof insofar as the above-identified unit is concerned. </div>', 0, 1);

        PDF::MultiCell(0, 0, "\n\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">Entered at Roxas City, Philippines on the 29th day of MAY 2019 at 11:03am.</div>', 0, 1);
        

        PDF::MultiCell(0, 0, "\n");

        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', 'Hereinafter referred to as the “Property”.', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">WHEREAS, the LENDER has agreed to grant the Loan of <b>'.$loaninwords.'</b> (₱ <b><u>'.number_format($totalloan,2).'</u></b>) to the BORROWER;</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">NOW, THEREFORE, the PARTIES agree as follows:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '1.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>PRINCIPAL</b>. The principal amount of the Loan shall be <b>'.$loaninwords.'</b> (₱ <b><u>'.number_format($totalloan,2).'</u></b>). The BORROWER shall execute a Promissory Note which shall be attached herewith and made and integral part thereof. </div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '2.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>PAYMENT OF PROCEEDS TO PDPI.</b> All proceeds of the loan shall be paid directly by the LENDER to PDPI. Upon transfer of the proceeds of the loan, the BORROWER shall execute the deed of absolute sale with PDPI.</div>', 0, 1);
        
        
        $annum = number_format($data[0]->intannum);
        
        $annuminwords = $this->reporter->ftNumberToWordsBuilder($annum);
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '3.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>INTEREST AND PENALTIES</b>. The Loan shall bear interest at the rate of <b><u>'.$annuminwords.'</u></b> (<u>'.$annum.'%</u>) per annum. </div>', 0, 1);


        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">In the event the BORROWER fails to pay any amount on its due date, the amount due shall be subject to a daily penalty interest at the rate of <b><u>five</u></b> percent (<b><u>5%</u></b>) accruing from the date of non-payment to the date of full payment. The penalty interest shall be compounded with the unpaid amount at the end of each month following the date on which that unpaid amount became due, and shall likewise bear interest at the same rate indicated herein. The penalty interest shall be immediately payable upon demand by the LENDER.</div>', 0, 1);

        
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">In the event the BORROWER fails to pay the amounts due under the payment schedule for three (3) consecutive months, there shall be an automatic permanent increase in the interest rate by twenty <b><u>twenty three</u></b> percent (<b><u>23%</u></b>)', 0, 1);

        $termmonths = $data[0]->days;
        
        $termmonthsinwords = $this->reporter->ftNumberToWordsBuilder($termmonths);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '4.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>PAYMENT TERMS</b>. The BORROWER shall repay the loan to the LENDER in <b><u>'.$termmonthsinwords.' months</u></b> (<b><u>'.$termmonths.'</u></b>) equal consecutive monthly installments commencing on the 15th day after the close of the month when the principal amount of the Loan was received, as set forth in the payment schedule attached herewith as <b>Annex “A”</b>.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">The BORROWER may prepay the amounts due on the Loan, in whole or in part; provided, however, that in case of partial prepayment of at least twelve (12) monthly amortizations, the LENDER shall either shorten the term of the loan or </div>', 0, 1);

        // next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">lower the monthly amortizations in which case a new schedule of payments shall be made.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '5.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>REAL ESTATE MORTGAGE.</b> As security for the repayment of the Loan, interest and other charges thereon, and the due and faithful performance of the BORROWER of his obligations under this Agreement, the BORROWER hereby transfers and conveys by way of First Real Estate Mortgage (“REM”) of the Property, including all its improvements, increments and accessories now existing or which may thereafter exist, in favor of the LENDER, its successors or assigns, under the following conditions:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'a.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER hereby warrants that it has a true, valid and perfect title to the Property, and that the same is free from any lien or encumbrance; and that the BORROWER will forever warrant and defend the same against all claims whatsoever.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'b.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER shall keep the Property in good condition and shall maintain the integrity, quality and sufficiency at a level acceptable to or directed by the LENDER.</div>', 0, 1);


        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'c.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">In the event that the LENDER finds that the Property is lost, impaired or depreciated due to any cause whatsoever, the BORROWER shall substitute the Property with new property/ies and/or provide additional collateral with equivalent value and deemed sufficient by the LENDER to secure the Loan. Otherwise, the obligations of the BORROWER shall be due and demandable.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'd.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER shall not lease, sell, dispose, mortgage, or encumber the Property, or assign any right in relation thereto, without prior written consent of the LENDER nor commit any act which may impair directly or indirectly, the value of the Property. </div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'e.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">In the event the Property is sold, disposed of or otherwise transferred in whole or in part by the BORROWER, the BORROWER shall not be released from his liability but shall be liable jointly and severally with the transferee unless expressly released therefrom in writing by the LENDER. In all cases this REM shall constitute a first and superior lien on the Property.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'f.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">This Agreement shall be recorded with the Registry of Deeds. All expenses and taxes in relation to registration of this agreement, such as, but not limited to, notarial fees, registration fees with the appropriate office and documentary stamp taxes, shall be for the account of the BORROWER; </div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'g.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The LENDER shall register the REM and cause its annotation on the title of the Property. For this purpose, the deed of absolute sale, title of the Property and such other documents necessary for the registration of the REM shall be turned-over by the BORROWER to the LENDER. </div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'h.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">Upon full payment of the loan, interest, penalties and other charges, in accordance with the terms and conditions of the Loan Agreement, this REM shall be cancelled and cease to have any force and effect. Otherwise, it shall remain in full force and effect and shall be enforceable in the manner provided by law.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'i.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">All expenses, documentary stamp taxes and other charges necessary for the registration of the REM shall be for the account of the BORROWER.</div>', 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        
        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '6.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>SPECIAL POWER OF ATTORNEY</b></div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">The BORROWER appoints, names and constitutes the LENDER and its duly authorized representatives as its duly constituted attorney-in-fact to submit, process, follow-up and receive documents necessary for the registration of the REM and its annotation to the title of the Property. The said attorney-in-fact is hereby granted full power and authority to do and perform any and every act and thing whatsoever requisite, necessary, incidental or proper to be done for the accomplishment of the foregoing, as fully to all intents and purposes as the BORROWER might or could do. </div>', 0, 1);

         
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '7.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>FORECLOSURE.</b> In the event that the BORROWER fails to perform any of the obligations herein secured, or violate the terms and conditions of the Loan Agreement, institute insolvency, suspension of payment or similar proceedings, or be involuntary declared insolvent or writ of garnishment and/or attachment be issued against any of the assets or income of the BORROWER or if this mortgage cannot be recorded in the Registry of Deeds, the LENDER may, in addition to whatever legal remedies it may have by law or agreement, declare the obligations secured by this mortgage due and payable, and upon failure to receive full payment, the LENDER may immediately proceed to judicially or extra-judicially foreclose this mortgage. </div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">The BORROWER hereby appoints the LENDER as its Attorney-in-Fact with full power of substitution and authority to take actual possession of the Property, without need of any judicial order, and to remove, sell or dispose of the same, and to do any and/or all acts which the LENDER may deem necessary to maintain and preserve the Property, and to perform such acts as may be necessary to dispose of the Property in accordance with the provision of Act No. 3135 as amended. </div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;">In the event of such judicial or extra-judicial foreclosure or other legal action, the LENDER shall be entitled to compensation for expenses, attorney’s fees and costs of collection. Moreover, the BORROWER hereby waives all its rights under the provisions of Rule 39 Sec. 12 of the Revised Rules of Court.</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '8.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>TAXES AND FEES.</b> Any and all taxes and fees imposed on or in relation to the execution of the PARTIES of this Agreement and the Loan, such as, but not limited to, documentary stamp tax, shall be for the sole account and responsibility of the BORROWER. The BORROWER shall hold the LENDER free and harmless from any tax, surcharge, interest and penalties that may be imposed or assessed on or in relation to this Agreement or the Loan. In the event that the LENDER shall be required by any relevant government authority to pay any tax, surcharge, interest or penalties, the BORROWER shall indemnify the LENDER within five (5) days after demand of the LENDER.</div>', 0, 1);

        
        $termmonths = $data[0]->days;
        
        $termmonthsinwords = $this->reporter->ftNumberToWordsBuilder($termmonths);
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '9.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', '<div style="text-align: justify;"><b>EVENTS OF DEFAULT.</b> Any of the following shall be considered an event of default:</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'a.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER fails to pay any amount within <u><b>'.$termmonthsinwords.'</b></u> (<u><b>'.$termmonths.'</b></u>) months from the due date</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'b.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER is unable to, or admits its inability to, pay the Loan as they fall due, or institutes insolvency, suspension of payment or similar proceedings, or be involuntary declared insolvent or writ of garnishment and/or attachment be issued against any of the assets or income of the BORROWER </div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'c.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The REM cannot be recorded in the Registry of Deeds;</div>', 0, 1);
        
        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'd.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">Any representation, warranty or statement made, repeated or deemed made by the BORROWER in, or pursuant to, this Agreement is incomplete, untrue, incorrect or misleading when made, repeated or deemed made.</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::MultiCell(25, 0, 'e.', '', 'L', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">The BORROWER commits a breach of any of the terms and conditions of this Agreement.</div>', 0, 1);
        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '10.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>DEFAULT.</b> In the event of default of the BORROWER, the remaining unpaid Loan, including interest and penalties, shall become immediately due and demandable. The BORROWER shall pay the LENDER all costs and expenses incurred by the latter to collect the amount due, including reasonable attorney's fees. </div>", 0, 1);
        
        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'>The LENDER shall have the right to immediately cause the foreclosure of the Property under REM, judicially or extra-judicially under Act No. 3135, as amended. For this purpose, the BORROWER hereby appoints and constitutes the LENDER as its Attorney-in-Fact with full power of substitution and authority to take actual possession of the Property, without need of any judicial order, and to remove, sell or dispose of the same, and to do any and/or all acts which the LENDER may deem necessary to maintain and preserve the Property, and to perform such acts as may be necessary to dispose of the Property in accordance with the provision of Act No. 3135 as amended. In the event of such judicial or extra-judicial foreclosure or other legal action, the MORTGAGEE shall be entitled to compensation for expenses, attorney’s fees and costs of collection. Moreover, the BORROWER hereby waives all its rights under the provisions of Rule 39 Sec. 12 of the Revised Rules of Court.</div>", 0, 1);

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'>The BORROWER shall fully indemnify the LENDER for any loss, damage, claim or injury incurred in connection with the breach by the BORROWER of any of the terms and conditions of this Agreement. </div>", 0, 1);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '11.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>DISPUTE RESOLUTION.</b> Any dispute, controversy or claim relating to this Agreement, or the breach, termination or invalidity thereof, shall, at the first instance be settled by negotiation and consultation by the PARTIES in good faith. </div>", 0, 1);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '12.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>GOVERNING LAW.</b> The rights and remedies of the PARTIES in case of breach of this Agreement and other documents that the PARTIES may execute shall be governed by the Civil Code of the Philippines. Failure or delay of either PARTY to insist, once or in several instances, on the strict performance by the other PARTY of any of the terms and conditions provided in this Agreement and other documents that the PARTIES may execute, or to exercise any right or option, shall not be construed as an abandonment, withdrawal, waiver or cancellation of such term, condition, right or option.</div>", 0, 1);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(25, 0, '13.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>VENUE.</b> Should the PARTIES resort to court of justice for the protection or enforcement of their respective rights under this Agreement and other documents that the PARTIES may execute, the PARTIES hereby agree to submit to the jurisdiction of the proper court of <b><u>Roxas, City Capiz</u></b>, to the exclusion of other venues;</div>", 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);


        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '14.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>WARRANTY.</b> The PARTIES warrant that they have the right, authority and corporate powers to enter into and execute this Agreement. </div>", 0, 1);


        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(25, 0, '15.', '', 'L', false, 0);
        PDF::writeHTMLCell(695, 0, '', '', "<div style='text-align: justify;'><b>COVENANT TO EXECUTE ADDITIONAL INSTRUMENTS.</b> The Parties agree to execute and deliver any instruments in writing necessary to carry out the agreement, term, condition or assurance, whenever such an occasion shall arise and upon request for such instruments.</div>", 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IN WITNESS WHEREOF, the PARTIES have hereunto set their hands on the date first above written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ASCEND FINANCE AND LEASING (AFLI) INC.</b></div>', 0, 1);
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">Lender</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">Represented by:</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>CORAZON R. CRUZ </b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">With Martial Consent:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");


        
        $spousename = $data[0]->sname;
        if($spousename == '' || $spousename == 'N/A'){
            $spousename = '';
        }
        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>'.$fullname.'</b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>'.$spousename.'</b></div>', 0, 1);
        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Borrower</div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Legal Spouse</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Conforme:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>PUEBLO DE PANAY, INC.</b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Represented by:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>JOSE NERY D. ONG </b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ACKNOWLEDGMENT</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Republic of the Philippines  )</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">City of ________	           ) S.S.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">BEFORE ME, a Notary Public for and in ____________ City, personally appeared before me:</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Name</b></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Government-Issued ID</b></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">known to me and to me known to be the same persons who executed the foregoing Loan Agreement with Real Estate Mortgage consisting of _____ (___) pages including the page on which this Acknowledgment is written, and who acknowledged to me that the same is their free and voluntary act and deed, and that of the corporation represented herein.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IN WITNESS WHEREOF, I have placed my hand and seal on the date and at the place first above-written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Doc. No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Page No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Book No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Series of 2025. </div>', 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        $month = date('F',$date);
        $year = date('Y',$date);

        $startdate = $month.' '.$day.','.$year;

        $term = $data[0]->terms;
        $monthlyamort = $data[0]->amortization;

        $trno = $data[0]->trno;
        $query = "
        select line,dateid,principal,bal,interest from tempdetailinfo where trno = $trno
        union all 
        select line,dateid,principal,bal,interest from htempdetailinfo where trno = $trno
        order by line";
        $data2 = $this->coreFunctions->opentable($query);
        $rbal = $this->coreFunctions->datareader("select sum(principal+interest) as value from (".$query.") as a ");
        
        PDF::MultiCell(0, 0, "\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ANNEX "A"</b></div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">PAYMENT SCHEDULE</div>', 0, 1);

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Borrower:</b> '.$fullname.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Start Date:</b> '.$startdate.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Term:</b> '.$term.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Monthly Amortization:</b> '.number_format($monthlyamort,2).'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Total Loan Amount:</b> '.number_format($rbal,2).'</div>', 0, 1);

        
        
            PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: center;">No.</div>', 'TBLR', 0);
            PDF::writeHTMLCell(180, 0, '', '', '<div style="text-align: center;">Due Date</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">Monthly Amortization</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">Balance Remaining</div>', 'TBLR', 1);
            $loopdate = strtotime($data[0]->dateid);
            $pdate = date("Y-m-d",$loopdate);
        for ($y = 1; $y <= $data[0]->days; $y++) {           
            $converteddate = date('M j, Y',strtotime($pdate));
            $rbal = $rbal - $monthlyamort;
            PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: center;">'.$y.'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(180, 0, '', '', '<div style="text-align: center;">'.$converteddate.'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">'.number_format($monthlyamort,2).'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">'.number_format($rbal,2).'</div>', 'TBLR', 1);
            $pdate = date("Y-m-d", strtotime("+$y month", $loopdate));
        }

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function real_estate_mortgage_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);
        PDF::MultiCell(720, 0, 'REAL ESTATE MORTGAGE', '', 'C', false, 1);
        
        
        PDF::SetFont($font, '', 16);
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">KNOWN ALL MEN BY THESE PRESENTS:</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">This REAL ESTATE MORTGAGE (“REM”), made and executed in <b><u>Pueblo De Panay Township Roxas City, Philippines</u></b>, by and between:</div>', 0, 1);

        
        
        $address = $data[0]->address;
        $fullname = $data[0]->fname.' '.$data[0]->mname.' '.$data[0]->lname;

        $attorneyinfact = $data[0]->attorneyinfact;
        if($attorneyinfact==''){
            $attorneyinfact = 'N/A';
        }
        $attorneyaddress = $data[0]->attorneyaddress;
        if($attorneyaddress==''){
            $attorneyaddress = 'N/A';
        }

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(620, 0, '', '', '<div style="text-align: justify;"><b><u>'.$fullname.', Attorney in Fact “'.$attorneyinfact.',</u></b> Filipino, of legal age, married, with address at <b><u>'.$attorneyaddress.'</u></b>, and hereinafter referred to as the “MORTGAGOR”;</div>', 0, 0);
        PDF::MultiCell(50, 0, '', '', 'C', false, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;">-and-</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;"><b>ASCEND FINANCE AND LEASING (AFLI) INC.</b>, a Corporation duly organized and existing under the laws of the Philippines, with office address at Punta Dulog Commercial Complex, St. Joseph Ave., Pueblo de Panay Township, Brgy. Lawaan, Roxas City, represented herein by <b><u>JOSE NERY D. ONG</u></b>, and hereinafter referred to as the “MORTGAGEE”;</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: justify;">Collectively referred to as the “PARTIES”.</div>', 0, 1);
        
        
        
        $totalloan = $data[0]->amount;
        $loaninwords = $this->reporter->ftNumberToWordsBuilder($totalloan);

        
        $unitaddress = $data[0]->blklot.' '.$data[0]->subdivision;
        if($unitaddress==''){
            $unitaddress = 'N/A';
        }

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;">WITNESSETH:</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">WHEREAS, the PARTIES entered into a Loan Agreement dated February 28, 2025 for the loan of <b><u>'.$loaninwords.'</u></b>  (₱<b><u>'.number_format($totalloan,2).'</u></b>) to finance the purchase by the MORTGAGOR of a certain parcel of land, together with all the buildings and improvements thereon, situated in <b><u>'.$unitaddress.'</u></b> more particularly described as follows:</div>', 0, 1);



        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>CONDOMINIUM CERTIFICATE OF TITLE</b></div>', 0, 1);

        $tct = '097-2019000089';
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>NO. '.$tct.'</b></div>', 0, 1);
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>(Republic Act No. 4726)</b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IT IS HEREBY CERTIFIED that the unit identified and described as: (“SITIO UNO”)
        UNIT NO. 426 FOURTH FLOOR WITH AN AREA OF 27.33 SQ.M., MORE OR LESS. </div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">In the diagrammatic floor plan appended to the enabling or master deed of the condominium project annotated on TRANSFER CERTIFICATE OF TITLE 2017001465 which embraces and describes the land located at BARANGAY LAWAAN, ROXAS CITY with an area of FOUR THOUSAND FIVE HUNDRED SEVENTY-EIGHT (4,578) Square Meter, is registered in the name of: </div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;"><b>Owner: PUEBLO DE PANAY, INC., A CORPORATION DULY ORGANIZEDAND EXISTING UNDER AND BY VIRTUE OF THE LAWS OF THE 
        Address:</b> GOV. HERNANDEZ AVENUE, ROXAS CITY, CAPIZ WESTERNVISAYAS (Continued on next page) as owner(s) of said unit in fee simple, with all the incidents provided by the Condominium Act, subject to such of the encumbrances noted on this condominium certificate of title and on the certificate of title of the land as may affect the unit; to those mentioned in the enabling or master deed and declaration of restrictions; and to those provided by law. </div>', 0, 1);

        

        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">This condominium certificate is a transfer from (Not Applicable, Original), which is/are cancelled by virtue hereof insofar as the above-identified unit is concerned. 
        </div>

        <div style="text-align: justify;">
        Entered at Roxas City, Philippines on the 29th day of MAY 2019 at 11:03am. 
        </div>

        <div style="text-align: justify; text-indent: 50px;">
        Hereinafter referred to as the “Property”.
        </div>

        <div style="text-align: justify; text-indent: 50px;">
        WHEREAS, the MORTGAGOR is the absolute owner of the Property;
        </div>

        <div style="text-align: justify; text-indent: 50px;">
        WHEREAS, under the Loan Agreement, the PARTIES agreed to constitute a REAL ESTATE MORTGAGE over the Property to secure the payment by the MORTGAGOR of the loan, interest, penalties and other charges and to ensure the faithful performance of the MORTGAGOR’s obligations under the Loan Agreement; 
        </div>
            
        <div style="text-align: left;">
        NOW THEREFORE, for and in consideration of the foregoing premises, the parties agree as follows:
        </div>

        
        <ol>
        <li><b>REAL ESTATE MORTGAGE.</b> As security for the payment by the MORTGAGOR of the loan, interest, penalties and other charges and to ensure the faithful performance of the MORTGAGOR’S obligations under the Loan Agreement, the MORTGAGOR hereby transfers and conveys by way of First Real Estate Mortgage of the Property, including the improvements, increments and accessories now existing or which may thereafter exist, in favor of the MORTGAGEE, its successors or assigns, under the following conditions:
        </li>
            <ol type="a">
            <li>The MORTGAGOR hereby warrants that it has a true, valid and perfect title to the Property, and that the same is free from any lien or encumbrance; and that the MORTGAGOR will forever warrant and defend the same against all claims whatsoever.</li><br>

            <li>The MORTGAGOR shall keep the Property in good condition and shall maintain the integrity, quality and sufficiency at a level acceptable to or directed by the MORTGAGEE.</li><br>

            <li>In the event that the MORTGAGEE finds that the Property is lost, impaired or depreciated due to any cause whatsoever, the MORTGAGOR shall substitute the Property with new property/ies and/or provide additional collateral with equivalent value and deemed sufficient by the MORTGAGEE to secure the Loan. Otherwise, the obligations of the MORTGAGOR shall be due and demandable.</li><br>

            <li>The MORTGAGOR shall not lease, sell, dispose, mortgage, or encumber the Property, or assign any right in relation thereto, without prior written consent of the MORTGAGEE nor commit any act which may impair directly or indirectly, the value of the Property. </li><br>

            <li>In the event the Property is sold, disposed of or otherwise transferred in whole or in part by the MORTGAGOR, the MORTGAGOR shall not be released from his liability but shall be liable jointly and severally with the transferee unless expressly released therefrom in writing by the MORTGAGEE. In all cases this REM shall constitute a first and superior lien on the Property.</li><br>
            </ol>
        </ol>
            
        ', 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n");


        PDF::writeHTMLCell(720, 0, '', '', '
        <ol start = "2">
            <ol type="a" start = "6">
            <li>This Agreement shall be recorded with the Registry of Deeds. All expenses and taxes in relation to registration of this agreement, such as, but not limited to, notarial fees, registration fees with the appropriate office and documentary stamp taxes, shall be for the account of the MORTGAGOR; </li><br>

            <li>The MORTGAGEE shall register the REM and cause its annotation on the title of the Property. For this purpose, the deed of absolute sale, title of the Property and such other documents necessary for the registration of the REM shall be turned-over by the MORTGAGOR to the MORTGAGEE. </li><br>

            <li>Upon full payment of the loan, interest, penalties and other charges, in accordance with the terms and conditions of the Loan Agreement, this Real Estate Mortgage shall be cancelled and cease to have any force and effect. Otherwise, it shall remain in full force and effect and shall be enforceable in the manner provided by law.</li><br>
            </ol>

            <li><b>SPECIAL POWER OF ATTORNEY.</b> The MORTGAGOR appoints, names and constitutes MORTGAGEE and its duly authorized representatives as its duly constituted attorney-in-fact to submit, process, follow-up and receive documents necessary for the registration of the REM and its annotation to the title of the Property. The said attorney-in-fact is hereby granted full power and authority to do and perform any and every act and thing whatsoever requisite, necessary, incidental or proper to be done for the accomplishment of the foregoing, as fully to all intents and purposes as the MORTGAGOR might or could do.</li><br> 

            
            <li><b>FORECLOSURE.</b> In the event that the MORTGAGOR fails to perform any of the obligations herein secured, or violate the terms and conditions of the Loan Agreement, institute insolvency, suspension of payment or similar proceedings, or be involuntary declared insolvent or writ of garnishment and/or attachment be issued against any of the assets or income of the MORTGAGOR or if this mortgage cannot be recorded in the Registry of Deeds, the MORTGAGEE may, in addition to whatever legal remedies it may have by law or agreement, declare the obligations secured by this mortgage due and payable, and upon failure to receive full payment, the MORTGAGEE may immediately proceed to judicially or extra-judicially foreclose this mortgage. </li> 

            <p>The MORTGAGOR hereby appoints the MORTGAGEE as its Attorney-in-Fact with full power of substitution and authority to take actual possession of the Property, without need of any judicial order, and to remove, sell or dispose of the same, and to do any and/or all acts which the MORTGAGEE may deem necessary to maintain and preserve the Property, and to perform such acts as may be necessary to dispose of the Property in accordance with the provision of Act No. 3135 as amended. </p>

            
            <p>In the event of such judicial or extra-judicial foreclosure or other legal action, the MORTGAGEE shall be entitled to compensation for expenses, attorney’s fees and costs of collection. Moreover, the BORROWER hereby waives all its rights under the provisions of Rule 39 Sec. 12 of the Revised Rules of Court.</p>

            
            <li><b>NOTICE.</b> Any Notice required to be served under this Agreement may be served by registered mail or private courier to the Party’s address stated above, and such notice shall be deemed to have been served at the time at which the letter would be delivered in the ordinary course of post.</li><br> 

        </ol>', 0, 1);

        
        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::writeHTMLCell(720, 0, '', '', '
        <ol start = "5">
            <li><b>GOVERNING LAW AND DISPUTE RESOLUTION.</b> This Agreement shall be governed and construed in accordance with the laws of the Philippines. Should the Parties resort to court of justice for the protection or enforcement of their respective rights under this Agreement and other documents that the Parties may execute, the Parties hereby agree to submit to the jurisdiction of the proper court of _____ City, to the exclusion of other venues.</li><br> 

            
            <li><b>SEVERABILITY.</b> The invalidity or unenforceability of any provision of this Agreement shall not affect the validity or enforceability of any other provision herein, which shall remain in full force and effect.</li> 


        </ol>

        <br>
        
        <div style="text-align: justify; text-indent: 50px;">
        IN WITNESS WHEREOF, I have hereunto set my hand this ______ day of _________________, 20____, in ________________________, Philippines.
        </div>

        <br>
        
        
        ', 0, 1);

        
        $spousename = $data[0]->sname;
        if($spousename == '' || $spousename == 'N/A'){
            $spousename = '';
        }
        
        $fullname = $data[0]->fname.' '.$data[0]->mname.' '.$data[0]->lname;

        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">With Martial Consent:</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>'.$fullname.'</b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>'.$spousename.'</b></div>', 0, 1);
        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Borrower</div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Legal Spouse</div>', 0, 1);

        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Attorney in Fact</div>', 0, 1);
        
        

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>ASCEND FINANCE AND LEASING (AFLI) INC.</b></div>', 0, 1);
        
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;">Mortgagee</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;">Represented by:</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>JOSE NERY D. ONG</b></div>', 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

	

        PDF::MultiCell(0, 0, "\n");
        
        PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(670, 0, '', '', '<div style="text-align: center;"><b>ACKNOWLEDGMENT</b></div>', 0, 1);


        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">REPUBLIC OF THE PHILIPPINES		)) S.S.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">BEFORE ME, a Notary Public for and in __________ on this ________________, personally appeared the following parties:</div>', 0, 1);

        

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Name</b></div>', 'TLR', 0);
        PDF::writeHTMLCell(200, 0, '', '', '<div style="text-align: center;"><b>Competent Evidence </b></div>', 'TR', 0);
        PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: center;"><b>Place and Date of Issue</b></div>', 'TR', 1);

        
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b></b></div>', 'BLR', 0);
        PDF::writeHTMLCell(200, 0, '', '', '<div style="text-align: center;"><b>of Identity</b></div>', 'BR', 0);
        PDF::writeHTMLCell(160, 0, '', '', '<div style="text-align: center;"><b></b></div>', 'BR', 1);

        
        
        PDF::writeHTMLCell(360, 30, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(200, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(160, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 30, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(200, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(160, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 30, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(200, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(160, 30, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);

        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">known to me to be the same persons who executed the foregoing Agreement consisting of ___ (_) pages including the page on which this Acknowledgment is written, and who acknowledged to me that the same is their free and voluntary act and deed, and that of the corporations they respectively represent.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IN WITNESS WHEREOF, I have placed my hand and seal on the date and at the place first above-written.</div>', 0, 1);


        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">WITNESS my hand and seal on the date and at the place first above written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Doc. No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Page No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Book No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Series of 2025. </div>', 0, 1);

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function acknowledgment_receipt_and_promissory_note_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);
        PDF::MultiCell(720, 0, 'ACKNOWELDGMENT RECEIPT ', '', 'C', false, 1);
        PDF::MultiCell(720, 0, 'AND', '', 'C', false, 1);
        PDF::MultiCell(720, 0, 'PROMISSORY NOTE', '', 'C', false, 1);

        
        $address = $data[0]->address;
        $fullname = $data[0]->lname.', '.$data[0]->fname.' '.$data[0]->mname;

        
        
        $spousename = $data[0]->sname;
        if($spousename == '' || $spousename == 'N/A'){
            $spousename = '';
        }

        
        $date = strtotime($data[0]->releasedate);
        $day = date('j',$date);
        $ordinalday = $this->othersClass->getOrdinal($day);
        $month = date('F',$date);
        $year = date('Y',$date);

        $interest = 0;
        $principal = 0;
        $qry = "select sum(principal) as principal,sum(interest) as interest,sum(mri) as mri from htempdetailinfo where trno=?";
        $dinfo = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($dinfo)) {
            $interest = $dinfo[0]->interest;
            $principal = $dinfo[0]->principal;
        }

        $totalloan = $data[0]->amount + $interest;
        $loaninwords = $this->reporter->ftNumberToWordsBuilder($totalloan);
        // PDF::writeHTMLCell(720, 0, '', '', 'WHEREAS, the BORROWER has requested a total loan of <u><b>'.$loaninwords.'</b></u> (₱ <u><b>'.number_format($totalloan,2).'</b></u>) from the LENDER (“Loan”) to finance the
        // purchase of a house and lot from Pueblo de Panay, Inc. (“PDPI”);”,', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 16);
        PDF::writeHTMLCell(720, 0, '', '', '
        <div style="text-align: justify;">I, <b><u>'.$fullname.'</u></b>, Filipino, of legal age, married, with address at <b><u>'.$address.'</u></b> ,hereby acknowledge receipt of the proceeds of the Loan per Loan Agreement executed by and between me and AFLI <u>dated <b>'.$ordinalday.'</b> day of <b>'.$month.'</b> '.$year.'</u>, which has been paid on my behalf by ASCEND FINANCE AND LEASING (AFLI) INC. (“AFLI”) to Pueblo de Panay, Inc.</div>

        <br>

        <div style="text-align: justify;">
        I hereby promise to pay, without need of demand, to AFLI the loan amount of <b><u>'.$loaninwords.'</u></b> (₱ <b><u>'.number_format($totalloan,2).'</u></b>), including interest and penalties, as specified in and subject to terms and conditions provided in the Loan Agreement. The Payment Schedule is attached herewith as Annex “A”.</div>

        <br>

        <div style="text-align: justify;">This Acknowledgment Receipt and Promissory Note is issued in connection with, and subject to, the terms and conditions of the Loan Agreement.</div>

        <br>
        
        
        <div style="text-align: justify;">Made this <b><u>'.$ordinalday.'</u></b> day of <b><u>'.$month.'</u></b> '.$year.', 2025 in <b><u>Roxas City, Capiz,</u></b> Philippines.</div>

        <br>
        

        <div style="text-align: center;"><b><u>'.$fullname.'</u></b><br>
        Maker</div>

        <br>
        <br>

        <div style="text-align: center;">With Marital Consent:</div>

        <br>
        <br>

        <div style="text-align: center;"><b><u>'.$spousename.'</u></b><br>
        Legal Spouse</div>

        <br>

        <div style="text-align: center;">Conforme:</div>

        <br>

        <div style="text-align: center;">ASCEND FINANCE AND LEASING (AFLI) INC.<br>
        Payee</div>

        <br>

        <div style="text-align: center;">Represented by:</div>

        <div style="text-align: center;"><b><u>JOSE NERY D. ONG</u></b></div>

        <br>


        ', 0, 1);

        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        
        $month = date('F',$date);
        $year = date('Y',$date);

        $startdate = $month.' '.$day.','.$year;

        $term = $data[0]->terms;
        $monthlyamort = $data[0]->amortization;
        
        PDF::MultiCell(0, 0, "\n");
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ANNEX "A"</b></div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">PAYMENT SCHEDULE</div>', 0, 1);

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Borrower:</b> '.$fullname.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Start Date:</b> '.$startdate.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Term:</b> '.$term.'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Monthly Amortization:</b> '.number_format($monthlyamort,2).'</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;"><b>Total Loan Amount:</b> '.number_format($totalloan,2).'</div>', 0, 1);

        $trno = $data[0]->trno;
        $query = "
        select line,dateid,principal,bal from tempdetailinfo where trno = $trno
        union all 
        select line,dateid,principal,bal from htempdetailinfo where trno = $trno
        order by line";
        $data2 = $this->coreFunctions->opentable($query);
        $count = 0;
        
            PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: center;">No.</div>', 'TBLR', 0);
            PDF::writeHTMLCell(180, 0, '', '', '<div style="text-align: center;">Due Date</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">Monthly Amortization</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">Balance Remaining</div>', 'TBLR', 1);
        foreach ($data2 as $key => $d) {
            $count++;
            $loopdate = strtotime($d->dateid);
            $converteddate = date('M j, Y',$loopdate);
            PDF::writeHTMLCell(90, 0, '', '', '<div style="text-align: center;">'.$count.'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(180, 0, '', '', '<div style="text-align: center;">'.$converteddate.'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">'.number_format($d->principal,2).'</div>', 'TBLR', 0);
            PDF::writeHTMLCell(225, 0, '', '', '<div style="text-align: center;">'.number_format($d->bal,2).'</div>', 'TBLR', 1);
        }

        
        
        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ACKNOWLEDGMENT</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Republic of the Philippines  )</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">City of ________	           ) S.S.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">BEFORE ME, a Notary Public for and in ____________ City, personally appeared before me:</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Name</b></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Government-Issued ID</b></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(360, 40, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">known to me and to me known to be the same persons who executed the foregoing Acknowledgment Receipt and Promissory Note, consisting of two (2) pages including the page on which this Acknowledgment is written, and who acknowledged to me that the same is their free and voluntary act and deed.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">IN WITNESS WHEREOF, I have placed my hand and seal on the date and at the place first above-written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Doc. No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Page No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Book No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Series of 2025. </div>', 0, 1);
        
        
        return PDF::Output($this->modulename . '.pdf', 'S');
    }


    public function quitclaim_and_waiver_of_rights_and_interests_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);
        PDF::MultiCell(720, 0, 'QUITCLAIM AND WAIVER OF RIGHTS AND INTERESTS', '', 'C', false, 1);

        
        // PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">This REAL ESTATE MORTGAGE (“REM”), made and executed in <b><u>Pueblo De Panay Township Roxas City, Philippines</u></b>, by and between:</div>', 0, 1);

        
        $address = $data[0]->address;
        $fullname = $data[0]->lname.', '.$data[0]->fname.' '.$data[0]->mname;
        $civilstatus = $data[0]->civilstatus;
        
        
        $spousename = $data[0]->sname;
        if($spousename == '' || $spousename == 'N/A'){
            $spousename = '';
        }

        
        $date = strtotime($data[0]->releasedate);
        $day = date('j',$date);
        $ordinalday = $this->othersClass->getOrdinal($day);
        $month = date('F',$date);
        $year = date('Y',$date);

        
        $totalloan = $data[0]->amount;
        $loaninwords = $this->reporter->ftNumberToWordsBuilder($totalloan);
        // PDF::writeHTMLCell(720, 0, '', '', '<b>WHEREAS</b> , the BORROWER has requested a total loan of <u><b>'.$loaninwords.'</b></u> (₱ <u><b>'.number_format($totalloan,2).'</b></u>) from the LENDER (“Loan”) to finance the
        // purchase of a house and lot from Pueblo de Panay, Inc. (“PDPI”);”,', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 13);
        PDF::writeHTMLCell(720, 0, '', '', '
        <div style="text-align: justify;">KNOW ALL MEN BY THESE PRESENTS:</div>

        <div style="text-align: justify; text-indent: 50px;">This Quitclaim and Waiver of Rights and Interests (this <b>"Quitclaim"</b>) is made and executed by:</div> <br>', 0, 1);

        

        
        // PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        // PDF::writeHTMLCell(620, 0, '', '', '<div style="text-align: justify;"><b><u>'.$fullname.', Attorney in Fact “'.$attorneyinfact.',</u></b> Filipino, of legal age, married, with address at <b><u>'.$attorneyaddress.'</u></b>, and hereinafter referred to as the “MORTGAGOR”;</div>', 0, 0);
        // PDF::MultiCell(50, 0, '', '', 'C', false, 1);

        
        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: justified;"><b>'.$fullname.'</b>, of legal age, Filipino citizen, '.$civilstatus.', and a resident of '.$address.', hereinafter referred to as the <b>GRANTOR</b>;</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        
        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: center;">- in favor of -</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: justified;"><b>ASCEND FINANCE AND LEASING, INC.</b>, a corporation duly organized and existing by virtue of the laws of the Republic of the Philippines, with principal place of business at Punta Dulog Commercial Complex, St. Joseph Avenue, Pueblo de Panay Township, Brgy. Lawaan, Roxas City, Capiz, represented by its [Position], [NAME], hereinafter referred to as the <b>GRANTEE</b>.</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justified; text-indent: 50px;" >(The <b>GRANTOR</b> and the <b>GRANTEE</b> shall collectively be referred to in this Quitclaim as the <b>"Parties"</b>.)</div>
        
        <div style="text-align: center;"><b>WITNESSETH:</b></div>
        
        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b> , the <b>GRANTOR</b> is the registered consumer of the electric connection with the Capiz Electric Cooperative, Inc. (<b>"CAPELCO"</b>) under Account Number [Number] and Meter Serial Number [Number] at [Property Location] (the <b>"Subject Property"</b>);</div>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b> , the <b>GRANTOR</b> is also the registered consumer of the water connection with the Metro Roxas Water District (<b>"MRWD"</b>) under Account Number [Number] and Meter Serial Number [Number] at the Subject Property;</div>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b> , the <b>GRANTOR</b>, in the Deed of Assignment dated __________ and entered as Doc. No. ___; Page No. ___; Book No. ___; Series of _____ in the Notarial Register of ____________________, has assigned all his/her rights and interests over the foregoing electric and water connections, including, but not limited to, the right to transfer the registration of said electric and water connections under the name of the <b>GRANTEE</b> and/or to request the disconnection thereof, and appointed the <b>GRANTEE</b> to do the same;</div>

        <div style="text-align: justify; text-indent: 50px;">NOW, THEREFORE, in consideration of the foregoing premises, the <b>GRANTOR</b> hereby, unconditionally and absolutely <b>RELINQUISHES</b>, <b>QUITS</b>, <b>WAIVES</b>, and <b>FOREVER ABANDONS</b> all his/her rights and interests, participation, causes of action, and claims of whatever nature, that they might have now or in the future over the (a) electric connection with CAPELCO under Account Number [Number] and Meter Serial Number [Number] and (b) water connection with MRWD under Account Number [Number] and Meter Serial Number [Number], both located at the Subject Property;</div>

        <div style="text-align: justify; text-indent: 50px;">That, notwithstanding the foregoing Deed of Assignment, this Quitclaim, and the transfer of the registration of the foregoing electric and water connections under the name of the ASSIGNEE, should it opt to do so, the ASSIGNOR, as actual consumer of the electric and water services, shall remain liable for all bills for the electricity and water incurred, and all penalties imposed therefor, if any, while the latter is in possession of the Subject Property;</div>

        <div style="text-align: justify; text-indent: 50px;">That the <b>GRANTOR</b> agrees to bring no action, claim, or complaint of whatever nature against anyone whomsoever, including, but not limited to, the <b>GRANTEE</b>, arising from, by reason of, or in connection with his/her foregoing electric and water connections, and to defend and hold harmless the <b>GRANTEE</b>;</div>

        <div style="text-align: justify; text-indent: 50px;">That the <b>GRANTOR</b> undertakes, guarantees, and obligates himself/herself, under pain of damages and liabilities, to protect and forever defend the rights and interests of the <b>GRANTEE</b> over the foregoing electric and water connections, and warrants against whatever kinds and forms of disturbances on the benefits of the latter;</div>

        <div style="text-align: justify; text-indent: 50px;">That the <b>GRANTOR</b> solemnly states and affirms that he/she fully realizes the significance of this Quitclaim, and the same is not being made because of any persuasive statement or representation by anyone whomsoever, or for any reason other than those stated herein;</div>', 0, 1);


        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '
        <div style="text-align: justified; text-indent: 50px;" >That this Quitclaim shall extend to the <b>GRANTOR</b>’s heirs, successors-in-interest, assigns, executors, and/or administrators;</div>

        <div style="text-align: justified; text-indent: 50px;" >That if, for any reason, additional documents or acts need to be executed or performed to effect or implement this Quitclaim, the <b>GRANTOR</b> hereby agrees to execute such documents and perform such acts at the soonest possible time, upon the request of the <b>GRANTEE</b>;</div>

        <div style="text-align: justified; text-indent: 50px;" >That, in the event of any dispute, controversy, or claim arising out of or in relation to this Deed, the Parties agree that they shall, as condition precedent before any court action, endeavor to amicably settle such dispute, controversy or claim by mutual consultation within thirty (30) days after written notice thereof has been given by the complaining Party. Should the Parties fail to agree within the said period of time, the matter in dispute shall be finally settled by arbitration in accordance with Republic Act No. 9285, otherwise known as the “Alternative Dispute Resolution Act of 2004”. However, should such disputes reach the courts of law, the Parties agree that such shall be exclusively brought before the court of proper jurisdiction in Roxas City, Capiz;</div>

        <div style="text-align: justified; text-indent: 50px;" >That the <b>GRANTOR</b> represents and warrants that he/she is capable of executing this Quitclaim, and that he/she has read and fully understood its contents, and that the same is voluntarily and willing executed with full knowledge of the consequences thereof.</div>
        
        <div style="text-align: justified; text-indent: 50px;" ><b>IN WITNESS WHEREOF</b>, the Parties have hereunto affixed their signatures this ____________________ at Roxas City, Capiz, Philippines.</div>', 0, 1);

        
        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Represented by:</div>', 0, 1);

        
        // PDF::MultiCell(360, 0, '[NAME]', '', 'L', false, 0);
        // PDF::MultiCell(360, 0, '', '', 'L', false, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>'.$fullname.'</b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>ASCEND FINANCE AND LEASING (AFLI) INC.</b></div>', 0, 1);

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b><b>GRANTOR</b></b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b><b>GRANTEE</b></b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::writeHTMLCell(360, 0, '', '', '', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>[NAME]</b></div>', 0, 1);

        PDF::writeHTMLCell(360, 0, '', '', '', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>[POSITION]</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">SIGNED IN THE PRESENCE OF:</div>', 0, 1);
        

        PDF::MultiCell(0, 0, "\n");
        // PDF::MultiCell(0, 0, "");
        
        PDF::MultiCell(60, 10, '', '', '', false, 0);
        PDF::MultiCell(240, 10, '', 'B', '', false, 0);
        PDF::MultiCell(60, 10, '', '', '', false, 0);

        PDF::MultiCell(60, 10, '', '', '', false, 0);
        PDF::MultiCell(240, 10, '', 'B', '', false, 0);
        PDF::MultiCell(60, 10, '', '', '', false);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ACKNOWLEDGMENT</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Republic of the Philippines  )</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">City of ________	           ) S.S.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">BEFORE ME, this _______________ at Roxas City, Capiz, Philippines, personally appeared:</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>NAME</b></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>COMPETENT EVIDENCE OF IDENTITY</b></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>DATE/PLACE ISSUED</b></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);



        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Name</b></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Government-Issued ID</b></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">known to me and to me known to be the same persons who executed the foregoing instrument and who acknowledged to me that the same is their free and voluntary act and deed, and that of the corporation herein represented.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">This instrument consisting of _____ (___) pages including this page on which this Acknowledgment is written is duly signed by the Parties and their instrumental witnesses on each and every page thereof and refers to a <b>QUITCLAIM AND WAIVER OF RIGHTS AND INTERESTS</b>.</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">WITNESS MY HAND AND SEAL at the place and on the date above-written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Doc. No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Page No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Book No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Series of 2025. </div>', 0, 1);
        
        
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function deed_of_assignment_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);
        PDF::MultiCell(720, 0, 'DEED OF ASSIGNMENT', '', 'C', false, 1);

        
        // PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">This REAL ESTATE MORTGAGE (“REM”), made and executed in <b><u>Pueblo De Panay Township Roxas City, Philippines</u></b>, by and between:</div>', 0, 1);

        
        $address = $data[0]->address;
        $fullname = $data[0]->lname.', '.$data[0]->fname.' '.$data[0]->mname;

        $civilstatus = $data[0]->civilstatus;
        
        
        $spousename = $data[0]->sname;
        if($spousename == '' || $spousename == 'N/A'){
            $spousename = '';
        }

        
        $date = strtotime($data[0]->releasedate);
        $day = date('j',$date);
        $ordinalday = $this->othersClass->getOrdinal($day);
        $month = date('F',$date);
        $year = date('Y',$date);

        
        $totalloan = $data[0]->amount;
        $loaninwords = $this->reporter->ftNumberToWordsBuilder($totalloan);
        // PDF::writeHTMLCell(720, 0, '', '', '<b>WHEREAS</b>, the BORROWER has requested a total loan of <u><b>'.$loaninwords.'</b></u> (₱ <u><b>'.number_format($totalloan,2).'</b></u>) from the LENDER (“Loan”) to finance the
        // purchase of a house and lot from Pueblo de Panay, Inc. (“PDPI”);”,', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n");

        PDF::SetFont($font, '', 13);
        PDF::writeHTMLCell(720, 0, '', '', '
        <div style="text-align: justify;">KNOW ALL MEN BY THESE PRESENTS:</div>

        <div style="text-align: justify; text-indent: 50px;">This Deed of Assignment (this <b>"Deed"</b>) is made and executed by:</div> <br>', 0, 1);

        

        
        // PDF::MultiCell(50, 0, '', '', 'C', false, 0);
        // PDF::writeHTMLCell(620, 0, '', '', '<div style="text-align: justify;"><b><u>'.$fullname.', Attorney in Fact “'.$attorneyinfact.',</u></b> Filipino, of legal age, married, with address at <b><u>'.$attorneyaddress.'</u></b>, and hereinafter referred to as the “MORTGAGOR”;</div>', 0, 0);
        // PDF::MultiCell(50, 0, '', '', 'C', false, 1);

        
        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: justified;">'.$fullname.', of legal age, Filipino citizen, '.$civilstatus.', and a resident of '.$address.', hereinafter referred to as the <b>ASSIGNOR</b>;</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        
        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: center;">- in favor of -</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        PDF::MultiCell(100, 0, '', '', 'C', false, 0);
        PDF::writeHTMLCell(520, 0, '', '', '<div style="text-align: justified;"><b>ASCEND FINANCE AND LEASING, INC.</b>, a corporation duly organized and existing by virtue of the laws of the Republic of the Philippines, with principal place of business at Punta Dulog Commercial Complex, St. Joseph Avenue, Pueblo de Panay Township, Brgy. Lawaan, Roxas City, Capiz, represented by its [Position], [NAME] , hereinafter referred to as the <b>ASSIGNEE</b>.</div>', 0, 1);
        PDF::MultiCell(100, 0, '', '', 'C', false, 1);

        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justified; text-indent: 50px;" >(The <b>ASSIGNOR</b> and the <b>ASSIGNEE</b> shall collectively be referred to in this Deed as the <b>"Parties"</b>.)</div><br>
        
        <div style="text-align: center;">WITNESSETH:</div><br>
        
        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b>, the <b>ASSIGNOR</b> is the registered consumer of the electric connection with the Capiz Electric Cooperative, Inc. (<b>"CAPELCO"</b>) under Account Number [Number] and Meter Serial Number [Number] at [Property Location] (the <b>"Subject Property"</b>);</div><br>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b>, the <b>ASSIGNOR</b> is also the registered consumer of the water connection with the Metro Roxas Water District (“MRWD”) under Account Number [Number] and Meter Serial Number [Number] at the Subject Property;</div><br>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b>, the <b>ASSIGNOR</b> obtained a loan from the <b>ASSIGNEE</b> as evidenced by the Contract of Loan dated __________ and entered as Doc. No. ___; Page No. ___; Book No. ___; Series of _____ in the Notarial Register of ____________________;</div><br>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b>, to secure the aforementioned loan, the <b>ASSIGNOR</b> executed a Deed of Real Estate Mortgage over the Subject Property in favor of the <b>ASSIGNEE</b> dated __________ and entered as Doc. No. ___; Page No. ___; Book No. ___; Series of _____ in the Notarial Register of ____________________;</div><br>

        <div style="text-align: justify; text-indent: 50px;"><b>WHEREAS</b>, to further secure the aforementioned loan, the <b>ASSIGNOR</b> hereby offers to assign all his/her rights and interests over the foregoing electric and water connections, including, but not limited to, the right to transfer the registration of said electric and water connections under the name of the <b>ASSIGNEE</b> and/or to request the disconnection thereof, and the <b>ASSIGNEE</b> hereby accepts the same, and without any liabilities on the part of the ASSIGNEE;</div><br>

        <div style="text-align: justify; text-indent: 50px;">NOW, THEREFORE, for and in consideration of the foregoing premises, the ASSIGNORS hereby ASSIGN, TRANSFER and CONVEY unto the ASSIGNEE, its assigns and successors-in-interest all their rights and interests over the (a) electric connection with CAPELCO under Account Number [Number] and Meter Serial Number [Number] and (b) water connection with MRWD under Account Number [Number] and Meter Serial Number [Number], both located at the Subject Property, including, but not limited to, the right to transfer the registration of said electric and water connections under the name of the <b>ASSIGNEE</b> and/or to request disconnection thereof for whatever reason, and hereby appoint the <b>ASSIGNEE</b> to do the same;</div><br>

        <div style="text-align: justify; text-indent: 50px;">That, notwithstanding this Agreement and the transfer of the registration of the foregoing electric and water connections under the name of the ASSIGNEE, should it opt to do so, the ASSIGNOR, as actual consumer of the electric and water services, shall remain liable for all bills for the electricity and water incurred, and all penalties imposed therefor, if any, while the latter is in possession of the Subject Property;</div>', 0, 1);


        //next page
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '
        <div style="text-align: justified; text-indent: 50px;" >That this Deed shall extend to the ASSIGNOR’s heirs, successors-in-interest, assigns, executors, and/or administrators;</div>

        <div style="text-align: justified; text-indent: 50px;" >That if, for any reason, additional documents or acts need to be executed or performed to effect or implement this Deed, the <b>ASSIGNOR</b> hereby agrees to execute such documents and perform such acts at the soonest possible time, upon the request of the ASSIGNEE;</div>

        <div style="text-align: justified; text-indent: 50px;" >That, in the event of any dispute, controversy, or claim arising out of or in relation to this Deed, the Parties agree that they shall, as condition precedent before any court action, endeavor to amicably settle such dispute, controversy or claim by mutual consultation within thirty (30) days after written notice thereof has been given by the complaining Party. Should the Parties fail to agree within the said period of time, the matter in dispute shall be finally settled by arbitration in accordance with Republic Act No. 9285, otherwise known as the “Alternative Dispute Resolution Act of 2004”. However, should such disputes reach the courts of law, the Parties agree that such shall be exclusively brought before the court of proper jurisdiction in Roxas City, Capiz; and</div>

        <div style="text-align: justified; text-indent: 50px;" >That the <b>ASSIGNOR</b> represents and warrants that he/she is capable of executing this Deed, and that he/she has read and fully understood its contents, and that the same is voluntarily and willing executed with full knowledge of the consequences thereof.</div>
        
        <div style="text-align: justified; text-indent: 50px;" >IN WITNESS WHEREOF, the Parties have hereunto affixed their signatures this ____________________ at Roxas City, Capiz, Philippines. </div>', 0, 1);

        
        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;">Represented by:</div>', 0, 1);

        
        // PDF::MultiCell(360, 0, '<b>'.$fullname.'</b> ', '', 'L', false, 0);
        // PDF::MultiCell(360, 0, '', '', 'L', false, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b><b>'.$fullname.'</b> </b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>ASCEND FINANCE AND LEASING (AFLI) INC.</b></div>', 0, 1);

        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>ASSIGNOR</b></div>', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>ASSIGNEE</b></div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::writeHTMLCell(360, 0, '', '', '', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>[NAME] </b></div>', 0, 1);

        PDF::writeHTMLCell(360, 0, '', '', '', 0, 0);
        PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>[POSITION]</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;">SIGNED IN THE PRESENCE OF:</div>', 0, 1);
        

        PDF::MultiCell(0, 0, "\n");
        // PDF::MultiCell(0, 0, "");
        
        PDF::MultiCell(60, 10, '', '', '', false, 0);
        PDF::MultiCell(240, 10, '', 'B', '', false, 0);
        PDF::MultiCell(60, 10, '', '', '', false, 0);

        PDF::MultiCell(60, 10, '', '', '', false, 0);
        PDF::MultiCell(240, 10, '', 'B', '', false, 0);
        PDF::MultiCell(60, 10, '', '', '', false);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: center;"><b>ACKNOWLEDGMENT</b></div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Republic of the Philippines  )</div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">City of ________	           ) S.S.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");

        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">BEFORE ME, this _______________ at Roxas City, Capiz, Philippines, personally appeared:</div>', 0, 1);
        
        
        PDF::MultiCell(0, 0, "\n");

        
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>NAME</b></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>COMPETENT EVIDENCE OF IDENTITY</b></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 0, '', '', '<div style="text-align: center;"><b>DATE/PLACE ISSUED</b></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 0);
        PDF::writeHTMLCell(240, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);



        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Name</b></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 0, '', '', '<div style="text-align: center;"><b>Government-Issued ID</b></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);
        
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBLR', 0);
        // PDF::writeHTMLCell(360, 15, '', '', '<div style="text-align: center;"></div>', 'TBR', 1);

        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify;">known to me and to me known to be the same persons who executed the foregoing instrument and who acknowledged to me that the same is their free and voluntary act and deed, and that of the corporation herein represented.</div>', 0, 1);

        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">This instrument consisting of _____ (___) pages including this page on which this Acknowledgment is written is duly signed by the Parties and their instrumental witnesses on each and every page thereof and refers to a <b>DEED OF ASSIGNMENT</b>.</div>', 0, 1);
        
        PDF::MultiCell(0, 0, "\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: justify; text-indent: 50px;">WITNESS MY HAND AND SEAL at the place and on the date above-written.</div>', 0, 1);

        
        PDF::MultiCell(0, 0, "\n\n\n\n");
        
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Doc. No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Page No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Book No. </div>', 0, 1);
        PDF::writeHTMLCell(720, 0, '', '', '<div style="text-align: left;">Series of 2025. </div>', 0, 1);
        
        
        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    
    public function take_out_fees_layout($params, $data)
    {

        $center = $params['params']['center'];
        $trno = $params['params']['dataid'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1100]);
        PDF::SetMargins(40, 40);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 19);

        
        PDF::Image(public_path() . $this->companysetup->getlogopath($params['params']).'afli.jpg', '280', '50', 200, 150);

        
        PDF::SetFont($fontbold, '', 10);
        PDF::writeHTMLCell(720, 0, '', '190', '
        <div style="text-align: left;">Ascend Finance & Leasing Inc.</div>', 0, 1);

        PDF::MultiCell(360, 0, 'Real Estate Loan', '', 'L', false, 0);
        PDF::MultiCell(200, 0, '5 Years', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n");

        $address = $data[0]->address;
        $fullname = $data[0]->lname.', '.$data[0]->fname.' '.$data[0]->mname;

        
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(160, 0, 'BORROWER/S:', '', 'L', false, 0);
        PDF::MultiCell(200, 0, $fullname, '', 'L', false, 1);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(160, 0, 'Property', '', 'L', false, 0);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(200, 0, $address, '', 'L', false, 1);
        
        PDF::MultiCell(0, 0, "\n");

        
        $totalloan = $data[0]->amount;

        
        PDF::MultiCell(360, 0, 'LOAN AMOUNT', '', 'L', false, 0);
        PDF::MultiCell(100, 0, 'P'.number_format($totalloan,2), 'B', 'R', false, 0);
        PDF::MultiCell(260, 0, '', '', 'R', false, 1);

        

        PDF::MultiCell(0, 0, "\n\n");
        
        $entryfee = $data[0]->entryfee;
        $lrf = $data[0]->lrf; 
        $itfee = $data[0]->itfee; 
        $regfee = $data[0]->regfee; 

        $docstamp1 = $data[0]->docstamp1;
        $nf = $data[0]->nf; 
        $annotationfee = $data[0]->annotationfee;
        $articles = $data[0]->articles;

        $annotationexp = $data[0]->annotationexp;
        $otransfer = $data[0]->otransfer;
        $pf = $data[0]->pf;
        $rpt = $data[0]->rpt;

        $subtotal1 = $entryfee + $lrf + $itfee + $regfee + $docstamp1 + $nf + $annotationfee + $articles + 
        $annotationexp + $otransfer + $pf + $rpt;
        PDF::SetFont($fontbold, '', 10);

        PDF::MultiCell(360, 0, 'A. REAL ESTATE MORTGAGE  - approximate computation', '', 'L', false, 1);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Entry Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($entryfee,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Legal Research Fund', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($lrf,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'IT Fee/ Computer Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($itfee,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Registration Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($regfee,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Documentary Stamps', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($docstamp1,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Legal  & Notarial Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($nf,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Annotation of Special Power of Attorney', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($annotationfee,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Articles of Inc. & By Laws', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($articles,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Annotation expenses', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($annotationexp,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Transfer of ownership', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($otransfer,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Service Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($pf,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Real Property Tax', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($rpt,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'SUB-TOTAL', '', 'L', false, 0);
            
            PDF::SetFont($fontbold, '', 10);
            PDF::MultiCell(30, 0, '', '', 'R', false, 0);
            PDF::MultiCell(70, 0, 'P'.number_format($subtotal1,2), 'B', 'R', false, 0);
            PDF::MultiCell(160, 0, '', '', 'R', false, 1);


            
        $docstamp = $data[0]->docstamp;
        $fmri = $data[0]->fmri;
        $handling = $data[0]->handling;
        $appraisal = $data[0]->appraisal;
        $filing = $data[0]->filing;
        $nf2 = $data[0]->nf2;
        $nf3 = $data[0]->nf3;

        $subtotal2 = $docstamp + $fmri+ $handling+ $appraisal+ $filing+ $nf2+ $nf3;

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(360, 0, 'B. BANK CHARGES', '', 'L', false, 1);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Documentary Stamp', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($docstamp,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Annual Mortgage Redemption Insurance (MRI)', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($fmri,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Handling Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($handling,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Appraisal Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($appraisal,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Processing Fee/ Filling Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($filing,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Notarial Fee: DEED OF UNDERTAKING', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($nf2,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Notarial Fee: DEED OF ASSIGNMENT', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($nf3,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'SUB-TOTAL', '', 'L', false, 0);
            
            PDF::SetFont($fontbold, '', 10);
            PDF::MultiCell(30, 0, '', '', 'R', false, 0);
            PDF::MultiCell(70, 0, 'P'.number_format($subtotal2,2), 'B', 'R', false, 0);
            PDF::MultiCell(160, 0, '', '', 'R', false, 1);


        $ofee = $data[0]->ofee;
        $referral = $data[0]->referral;
        $cancellation4 = $data[0]->cancellation4;
        $cancellation7 = $data[0]->cancellation7;
        $annotationoc1 = $data[0]->annotationoc1;
        $annotationoc2 = $data[0]->annotationoc2;
        $cancellationu = $data[0]->cancellationu;

        $subtotal3 = $ofee + $referral + $cancellation4 + $cancellation7 + $annotationoc1 + $annotationoc2 + $cancellationu;
        
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(360, 0, 'C. OTHER CHARGES FOR RD REGISTRATION FEE AND SERVICE FEE ', '', 'L', false, 1);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Other Fees', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($docstamp,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Referral Fee', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($fmri,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Cancellation : Sec 4 Rule 74', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($handling,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Cancellation : Sec 7 RA 26', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($appraisal,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Annotation of correct tech description', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($filing,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Annotation of Aff of one and the same person', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($nf2,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, 'Cancellation: ULAMA', '', 'L', false, 0);
            PDF::MultiCell(100, 0, number_format($nf3,2), '', 'R', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 1);

            
            PDF::MultiCell(100, 0, '', '', 'L', false, 0);
            PDF::MultiCell(260, 0, '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, 'SUB-TOTAL', '', 'L', false, 0);
            
            PDF::SetFont($fontbold, '', 10);
            PDF::MultiCell(30, 0, '', '', 'R', false, 0);
            PDF::MultiCell(70, 0, 'P'.number_format($subtotal3,2), 'B', 'R', false, 0);
            PDF::MultiCell(160, 0, '', '', 'R', false, 1);
            
        PDF::MultiCell(0, 0, "\n\n");

        $grandtotal = $subtotal1 + $subtotal2 + $subtotal3;
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(260, 0, 'On or before 28th of the Month', '', 'L', false, 0);
        PDF::MultiCell(100, 0, 'GRAND TOTAL', '', 'R', false, 0);
        PDF::MultiCell(30, 0, '', '', 'R', false, 0);
        PDF::MultiCell(70, 0, 'P'.number_format($grandtotal,2), 'B', 'R', false, 0);
        PDF::MultiCell(260, 0, '', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(100, 0, 'NOTE:', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'Any additional charges that may be incurred in the course of registration not included', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'in this computation will be collected from the borrowers.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'Rest Estate Mortgage fees subject to final computation as determined by The Register of Deeds.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'Fire Insurance quoation subject to final computation as determined by  Insurance Company.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'Processing of updated MRI, FI, RPT, and TCT is not part of the computation; this will be processed', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        PDF::MultiCell(100, 0, '', '', 'L', false, 0);
        PDF::MultiCell(620, 0, 'separately upon request.', '', 'L', false, 0);
        PDF::MultiCell(100, 0, '', '', 'R', false, 1);

        
        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(560, 0, 'CONFORME', '', 'L', false, 0);
        PDF::MultiCell(160, 0, '', '', 'R', false, 1);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(100, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(620, 0, '', '', 'R', false, 1);
        
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(560, 0, 'Borrower and Mortgagor', '', 'L', false, 0);
        PDF::MultiCell(160, 0, '', '', 'R', false, 1);

        
        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(100, 0, '', 'B', 'L', false, 0);
        PDF::MultiCell(620, 0, '', '', 'R', false, 1);
        
        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(560, 0, 'Co borrower and Co-Mortgagor', '', 'L', false, 0);
        PDF::MultiCell(160, 0, '', '', 'R', false, 1);
        PDF::MultiCell(560, 0, 'As Represented by Attorney-in-Fact', '', 'L', false, 0);
        PDF::MultiCell(160, 0, '', '', 'R', false, 1);

        
        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
