<?php

namespace App\Http\Classes\modules\modulereport\mcpc;

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

class cr
{

    private $modulename = "Received Payment";
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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
            
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
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
    client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno) 
    left join coa on coa.acnoid=detail.acnoid) 
    left join client on client.client=detail.client
    where head.doc='cr' and head.trno='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
    client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno) 
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid 
    where head.doc='cr' and head.trno='$trno' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    }

    public function reportplotting($params, $data)
    {

        return $this->default_CR_PDF($params, $data);
    }
    public function default_CR_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $font = "";
        $fontbold = "";
        $fontsize = 13;
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
        PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(540, 0, $this->modulename, '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '135');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(490, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '155');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(490, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');


        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
        PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
        PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
        PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
        PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
        PDF::MultiCell(10, 0, "", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
    }
    public function default_CR_PDF($params, $data)
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
        $fontsize = "13";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_CR_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');

        $countarr = 0;

        if (!empty($data)) {
            $totaldb = 0;
            $totalcr = 0;
            for ($i = 0; $i < count($data); $i++) {
                
                $maxrow = 1;
                $acno = $data[$i]['acno'];
                $acnoname = $data[$i]['acnoname'];
                $ref = $data[$i]['ref'];
                $postdate = $data[$i]['postdate'];
                $debit = number_format($data[$i]['db'], $decimalcurr);
                $credit = number_format($data[$i]['cr'], $decimalcurr);
                $client = $data[$i]['client'];
                $debit = $debit < 0 ? '-' : $debit;
                $credit = $credit < 0 ? '-' : $credit;

                $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
                $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
                $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
                $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', 12);
                    PDF::MultiCell(100, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
                    PDF::MultiCell(160, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
                    PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
                    PDF::MultiCell(105, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
                }

                $totaldb += $data[$i]['db'];
                $totalcr += $data[$i]['cr'];

                if (intVal($i) + 1 == $page) {
                    $this->default_CR_header_PDF($params, $data);
                    $page += $count;
                }
            }
        }


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', '');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(447, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, '', '', 'L', false, 0);
        PDF::MultiCell(560, 0, '', '', 'L');

        PDF::MultiCell(0, 0, "\n\n\n");


        PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
        PDF::MultiCell(240, 0, 'Received By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");

        PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
        PDF::MultiCell(240, 0, $params['params']['dataparams']['received'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
