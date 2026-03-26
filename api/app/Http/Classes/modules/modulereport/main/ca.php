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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ca
{
    private $modulename = "Ticket";
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
        $signatories = $this->othersClass->getSignatories($config);
        $approved = '';
        $prepared = '';
        $received = '';

        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
                case 'received':
                    $received = $value->fieldvalue;
                    break;
            }
        }
        return $this->coreFunctions->opentable(
            "select
            'PDFM' as print,
            '$prepared' as prepared,
            '$approved' as approved,
            '$received' as received
            "
        );
    }

    public function report_default_query($trno)
    {
        $query = "select head.docno, client.client, left(head.dateid,10) as dateid, client.addr as address, client.clientname, head.rem, 
        ifnull(head.clienttype, '') as clienttype, ifnull(req1.category, '') as ordertype, ifnull(client.tel, '') as tel, 
        ifnull(client.email, '') as email, ifnull(req2.category, '') as channel, emp.clientname as empname, 
        ifnull(branch.clientname, '') as branchname, client.registername, com.comment  
        from csstickethead as head
        left join client on client.client = head.client
        left join client as emp on head.empid = emp.clientid
        left join client as branch on branch.clientid = head.branchid
        left join reqcategory as req1 on req1.line = head.orderid
        left join reqcategory as req2 on req2.line = head.channelid
        left join csscomment as com on com.trno = head.trno
        where head.trno = $trno
        union all 
        select head.docno, client.client, left(head.dateid, 10) as dateid, client.addr as address, client.clientname, head.rem, 
        ifnull(head.clienttype, '') as clienttype, ifnull(req1.category, '') as ordertype, ifnull(client.tel, '') as tel, 
        ifnull(client.email, '') as email, ifnull(req2.category, '') as channel, emp.clientname as empname, 
        ifnull(branch.clientname, '') as branchname, client.registername, com.comment  
        from hcsstickethead as head 
        left join client on client.clientid = head.clientid
        left join client as emp on head.empid = emp.clientid
        left join client as branch on branch.clientid = head.branchid
        left join reqcategory as req1 on req1.line = head.orderid
        left join reqcategory as req2 on req2.line = head.channelid
        left join hcsscomment as com on com.trno = head.trno
        where head.trno = $trno";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn

    public function reportplotting($params, $data)
    {
        return $this->default_CA_PDF($params, $data);
    }

    public function default_CA_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $qry = "select code, name, address, tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

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
        PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, $data[0]['docno'], 'B', 'L', false);

        PDF::MultiCell(0, 30, "", '', 'L');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Client Type: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['clienttype']) ? $data[0]['clienttype'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Email: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, (isset($data[0]['email']) ? $data[0]['email'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Order Type: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['ordertype']) ? $data[0]['ordertype'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Reciept: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Co. Name: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Contact No. ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(420, 0, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Br. Name: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['branchname']) ? $data[0]['branchname'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n\n");

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(500, 0, "COMMENT SECTION", 'TB', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'TB', 'L', false, 0);
        PDF::MultiCell(100, 0, "", 'TB', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'T');
    }

    public function default_CA_PDF($params, $data)
    {
        $font = "";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        $this->default_CA_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

                $maxrow = 1;

                $comment = $data[$i]['comment'];
                $arr_comment = $this->reporter->fixcolumn([$comment], '80', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_comment]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(500, 0, ' ' . (isset($arr_comment[$r]) ? $arr_comment[$r] : ''), '', 'L', false, 0);
                    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
                    PDF::MultiCell(100, 0, '', '', 'L', false);
                }

                if (PDF::getY() > 900) {
                    $this->default_CA_header_PDF($params, $data);
                    // $page += $count;
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

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
