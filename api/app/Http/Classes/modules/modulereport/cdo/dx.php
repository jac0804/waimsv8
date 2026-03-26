<?php

namespace App\Http\Classes\modules\modulereport\cdo;

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

class dx
{

    private $modulename = "Deposit Slip";
    private $reportheader;
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
        $this->reportheader = new reportheader;
    }

    public function createreportfilter($config)
    {
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
            // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);

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
        $query = "
                select  head.trno,head.docno as dsdocno,head.doc,head.yourref,head.ourref,left(head.dateid,10) as dsdateid,
                head.rem as dsrem,ce.dstrno,ce.clientname,cl.clientid,left(ce.dateid,10) as cedate,
                left(ce.checkdate,10) as checkdate,ce.checkinfo,ce.amount,ce.docno as cedocno,ce.purposeofpayment,ce.rem as cerem
                from dxhead as head
                left join hcehead as ce on ce.dstrno=head.trno
                left join client as cl on cl.clientid=ce.clientid
                where ce.dstrno <> 0 and head.trno ='$trno'
            union all

        select  head.trno,head.docno as dsdocno,head.doc,head.yourref,head.ourref,left(head.dateid,10) as dsdateid,
                head.rem as dsrem,ce.dstrno,ce.clientname,cl.clientid,left(ce.dateid,10) as cedate,
               left(ce.checkdate,10) as checkdate,ce.checkinfo,ce.amount,ce.docno as cedocno,ce.purposeofpayment,ce.rem as cerem
                from hdxhead as head
                left join hcehead as ce on ce.dstrno=head.trno
                left join client as cl on cl.clientid=ce.clientid
                where ce.dstrno <> 0 and head.trno ='$trno'
                order by dsdateid, dsdocno";

        // var_dump($query);
        $result = $this->coreFunctions->opentable($query);
        return $result;
    }

    public function reportplotting($params, $data)
    {
        if ($params['params']['dataparams']['print'] == "default") {
            return $this->default_ds_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
            return $this->default_ds_PDF($params, $data);
        }
    }

    public function rpt_default_header($data, $filters)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

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
        $str .= $this->reporter->col('DEPOSIT SLIP', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->dsdocno) ? $data[0]->dsdocno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->dsdateid) ? $data[0]->dsdateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('YOURREF : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]->yourref) ? $data[0]->yourref : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('OURREF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->ourref) ? $data[0]->ourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DOCNO', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('NAME', '120', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CHECK DATE', '70', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CHECKNO #', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('AMOUNT', '80', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('PURPOSE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('NOTES', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        return $str;
    }

    public function default_ds_layout($filters, $data)
    {
        $companyid = $filters['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

        $center = $filters['params']['center'];
        $username = $filters['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();

        $str .= $this->rpt_default_header($data, $filters);

        $totalamt = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->cedate, '70', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->cedocno, '80', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->clientname, '120', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->checkdate, '70', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->checkinfo, '80', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col(number_format($data->amount, 2), '80', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->purposeofpayment, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $str .= $this->reporter->col($data->notes, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
            $totalamt = $totalamt + $data[$i]->amount;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->rpt_default_header($data, $filters);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '120', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('GRAND TOTAL :', '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
        $str .= $this->reporter->col(number_format($totalamt, $decimal), '80', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
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
        $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

    public function default_ds_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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



        PDF::SetFont($font, '', 9);

        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

        $this->reportheader->getheader($params);
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(55, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(125, 0, (isset($data[0]->dsdocno) ? $data[0]->dsdocno : ''), 'B', 'L', false, 0, '',  '');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(35, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(115, 0, (isset($data[0]->dsdateid) ? $data[0]->dsdateid : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(370, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, (isset($data[0]->yourref) ? $data[0]->yourref : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(520, 0, "", '', 'L', false, 0, '',  '');
        PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(130, 0, (isset($data[0]->ourref) ? $data[0]->ourref : ''), 'B', 'L', false, 1, '',  '');


        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');
        PDF::SetFont($font, 'B', 12);

        PDF::MultiCell(70, 0, "Date", '', 'C', false, 0);
        PDF::MultiCell(80, 0, "Docno", '', 'L', false, 0);
        PDF::MultiCell(120, 0, "Name", '', 'L', false, 0);
        PDF::MultiCell(70, 0, "CheckDate", '', 'C', false, 0);
        PDF::MultiCell(80, 0, "Checkno.", '', 'C', false, 0);
        PDF::MultiCell(80, 0, "Amount", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "Purpose", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "Notes", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_ds_PDF($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;

        $font = "";
        $fontbold = "";
        $border = "1px solid ";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_ds_header_PDF($params, $data);

        $totalamt = 0;
        if (!empty($data)) {

            for ($i = 0; $i < count($data); $i++) {
                $maxrow = 1;

                $cedate = $data[$i]->cedate;
                $clientname = $data[$i]->clientname;
                $checkdate = $data[$i]->checkdate;
                $checkinfo = $data[$i]->checkinfo;
                $amount = number_format($data[$i]->amount, $decimalcurr);
                $cedocno = $data[$i]->cedocno;
                $purposeofpayment = $data[$i]->purposeofpayment;
                $cerem = $data[$i]->cerem;


                $arr_cedate = $this->reporter->fixcolumn([$cedate], '16', 0);
                $arr_clientname = $this->reporter->fixcolumn([$clientname], '35', 0);
                $arr_checkdate = $this->reporter->fixcolumn([$checkdate], '16', 0);
                $arr_checkinfo = $this->reporter->fixcolumn([$checkinfo], '16', 0);
                $arr_amount = $this->reporter->fixcolumn([$amount], '16', 0);
                $arr_cedocno = $this->reporter->fixcolumn([$cedocno], '16', 0);
                $arr_purposeofpayment = $this->reporter->fixcolumn([$purposeofpayment], '20', 0);
                $arr_cerem = $this->reporter->fixcolumn([$cerem], '16', 0);


                $maxrow = $this->othersClass->getmaxcolumn([
                    $arr_clientname,
                    $arr_checkdate,
                    $arr_checkinfo,
                    $arr_amount,
                    $arr_cedocno,
                    $arr_purposeofpayment,
                    $arr_cerem
                ]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(70, 0, (isset($arr_cedate[$r]) ? $arr_cedate[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(80, 0, (isset($arr_cedocno[$r]) ? $arr_cedocno[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(120, 0, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(70, 0, (isset($arr_checkdate[$r]) ? $arr_checkdate[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(80, 0, (isset($arr_checkinfo[$r]) ? $arr_checkinfo[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(80, 0, (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_purposeofpayment[$r]) ? $arr_purposeofpayment[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_cerem[$r]) ? $arr_cerem[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }
                $totalamt += $data[$i]->amount;

                if (intVal($i) + 1 == $page) {
                    $this->default_ds_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(420, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(80, 0, number_format($totalamt, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(200, 0, '', '', '', false, 0);


        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
