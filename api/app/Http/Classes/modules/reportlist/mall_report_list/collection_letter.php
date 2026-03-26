<?php

namespace App\Http\Classes\modules\reportlist\mall_report_list;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


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
use NumberFormatter;

class collection_letter
{
    public $modulename = 'Collection Letter';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $fields = ['radioprint', 'radioreporttype', 'tenants', 'prepared', 'position', 'approved', 'position2', 'noted', 'position3', 'dateid'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => '1st Letter', 'value' => '0', 'color' => 'orange'],
            ['label' => '2nd Letter', 'value' => '1', 'color' => 'orange'],
            ['label' => '3rd Letter', 'value' => '2', 'color' => 'orange']
        ]);
        data_set($col1, 'tenants.lookupclass', 'lookuptenant');
        data_set($col1, 'tenants.required', false);
        data_set($col1, 'position2.label', 'Position');
        data_set($col1, 'position3.label', 'Position');
        data_set($col1, 'dateid.label', 'As of');
        data_set($col1, 'dateid.readonly', false);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
        'default' as print,
        '' as tenants,
        '' as client,
        '' as clientname,
         '' as prepared,
         '' as approved,
         '' as noted,
         '' as position,
         '' as position2,
         '' as position3,
            '0' as reporttype,
            left(now(),10) as dateid";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0: // 1st Letter
                $result = $this->reportDefaultLayout_1stletter($config);
                break;
            case 1: // 2nd Letter
                $result = $this->reportDefaultLayout_2ndletter($config);
                break;
            case 2: // 3rd Letter
                $result = $this->reportDefaultLayout_3rdletter($config);
                break;
        }

        return $result;
    }

    public function report_qry_Default($config)
    {
        $client    = $config['params']['dataparams']['client'];
        $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $filter1 = "";

        $filter1 = "";
        if ($client != "") {
            $filter1 = "and cl.client='$client'";
        }

        $query = "select cl.clientid,cl.client,cl.addr,cl.clientname from glhead as head
         left join gldetail as detail on detail.trno=head.trno
         left join arledger as ar on ar.trno=detail.trno and ar.line=detail.line
         left join client as cl on cl.clientid=head.clientid
         where  ifnull(cl.client,'')<>'' $filter1 and ar.bal<>0 and date(ar.dateid)<='" . $asof . "'
         group by cl.clientid,cl.client,cl.clientname,cl.addr
         order by cl.clientid,cl.client, cl.clientname,cl.addr";

        return $this->coreFunctions->opentable($query);
    }

    private function default_header_3rd_letter($config)
    {

        $center     = $config['params']['center'];
        $asof2     = date('F d, Y', strtotime($config['params']['dataparams']['dateid']));
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "15";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid ', '', 'L', $font, '35', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1000', null, false, '1px solid ', '', 'L', $font, '25', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();


        $str .= '<br>';


        return $str;
    }

    private function default_header($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "15";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid ', '', 'C', $font, '30', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1000', null, false, '1px solid ', '', 'C', $font, '25', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, false, $border, '', '', $font, '', '');
        $str .= $this->reporter->endrow();

        $str .= '<br>';

        return $str;
    }

    public function reportDefaultLayout_1stletter($config)
    {
        $result     = $this->report_qry_Default($config);
        $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $asof2     = date('F d, Y', strtotime($config['params']['dataparams']['dateid']));
        $center     = $config['params']['center'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $companyname = $this->coreFunctions->opentable($qry);
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "17";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);
        $tenant = '';
        foreach ($result as $key => $data) {
            if ($tenant == '' || ($tenant == $data->clientname && $data->clientname != '')) {
                if ($tenant != $data->clientname) {
                    $tenant = $data->clientname;
                    startdata:


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, $border, '', '', $font, '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '880', null, false, $border, '', '', $font, '', '');
                    $str .= $this->reporter->col('1st NOTICE', '120', null, false,  '3px solid', 'B', 'C', $font, '20', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($asof2, '1000', null, false, $border, '', '', $font, '18', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->clientname, '1000', null, false, $border, '', 'L', $font, '18', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Dear Sir/Ma\'am,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Upon review of your accounts, it has come to our attention that you have not remitted to us the payment as follows:', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br><br>';

                    $qry2 = "select c.acnoname,sum(ar.bal) as bal from coa as c
                    left join gldetail as detail on detail.acnoid=c.acnoid
                    left join glhead as head on head.trno=detail.trno
                     left join client as cl on cl.clientid=head.clientid
                     left join arledger as ar on ar.trno=detail.trno and ar.line=detail.line
                     where cl.client='" . $data->client . "'  and ar.bal<>0 and date(ar.dateid)<='" . $asof . "'
                     group by c.acnoname
                     ";
                    $data2 = $this->coreFunctions->opentable($qry2);

                    $totalbal = 0;
                    if (!empty($data2)) {
                        foreach ($data2 as $i => $value) {
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '');
                            $str .= $this->reporter->col($value->acnoname, '200', null, false, $border, '', 'L', $font, $fontsize, '');
                            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->col('&#x20B1', '50', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->col(number_format($value->bal, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->col('', '450', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalbal = $totalbal + $value->bal;
                        }
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col('Total', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($totalbal, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '450', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '150', null, false, '3px solid', 'B', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->col('', '450', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('We feel sure that this must be an oversight on your part. May we, therefore, request for full settlement of the above accounts with in three (3) days from receipt hereof.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Thank you.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Very truly yours,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(strtoupper($companyname[0]->name), '1000', null, false,  $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($config['params']['dataparams']["position"], '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col($config['params']['dataparams']["noted"], '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($config['params']['dataparams']["position3"], '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    goto   startdata;
                } else {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    goto   startdata;
                }
            }
        }


        return $str;
    }



    public function reportDefaultLayout_2ndletter($config)
    {
        $result     = $this->report_qry_Default($config);
        $prepared  = $config['params']['dataparams']['prepared'];
        $notedby   = $config['params']['dataparams']['noted'];
        $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $asof2     = date('F d, Y', strtotime($config['params']['dataparams']['dateid']));
        $center     = $config['params']['center'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $companyname = $this->coreFunctions->opentable($qry);
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "17";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);
        $tenant = '';
        foreach ($result as $key => $data) {
            if ($tenant == '' || ($tenant == $data->clientname && $data->clientname != '')) {
                if ($tenant != $data->clientname) {
                    $tenant = $data->clientname;
                    startdata:
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, $border, '', '', $font, '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '880', null, false, $border, '', '', $font, '', '');
                    $str .= $this->reporter->col('2nd NOTICE', '120', null, false,  '3px solid', 'B', 'C', $font, '20', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($asof2, '1000', null, false, $border, '', '', $font, '18', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->clientname, '1000', null, false, $border, '', 'L', $font, '18', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Dear Sir/Ma\'am,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $qry4 = "select c.acnoname,sum(ar.bal) as bal from coa as c
                    left join gldetail as detail on detail.acnoid=c.acnoid
                    left join glhead as head on head.trno=detail.trno
                     left join client as cl on cl.clientid=head.clientid
                     left join arledger as ar on ar.trno=detail.trno and ar.line=detail.line
                     where cl.client='" . $data->client . "'  and ar.bal<>0 and date(ar.dateid)<='" . $asof . "'
                     group by c.acnoname
                     ";
                    $data4 = $this->coreFunctions->opentable($qry4);

                    $totalbal = 0;
                    if (!empty($data4)) {
                        foreach ($data4 as $i => $value) {
                            $totalbal = $totalbal + $value->bal;
                        }
                    }

                    $fntotalbal = number_format((float) $totalbal, 2, '.', '');
                    $word = strtolower($this->reporter->ftNumberToWordsConverter($fntotalbal));
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Our record shows that as of this date you have an outstanding balance of ' . ' ' . '<span style="font-weight:bold">' . trim(ucwords($word)) . ' ' . '(' . '&#x20B1' . ' ' . number_format($totalbal, 2) . ')' . '</span>' . '.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('We have notified you regarding your unpaid account but you did not reply. Please settle the amout due within three (3) days from the receipt of this notice.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Thank you.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Very truly yours,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(strtoupper($companyname[0]->name), '1000', null, false,  $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["prepared"]), '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["position"]), '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["noted"]), '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["position3"]), '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    goto   startdata;
                } else {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    goto   startdata;
                }
            }
        }


        return $str;
    }


    public function reportDefaultLayout_3rdletter($config)
    {
        $result     = $this->report_qry_Default($config);
        $prepared  = $config['params']['dataparams']['prepared'];
        $notedby   = $config['params']['dataparams']['noted'];
        $asof     = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
        $asof2     = date('F d, Y', strtotime($config['params']['dataparams']['dateid']));
        $center     = $config['params']['center'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $companyname = $this->coreFunctions->opentable($qry);
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "20";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header_3rd_letter($config);
        $tenant = '';
        foreach ($result as $key => $data) {
            if ($tenant == '' || ($tenant == $data->clientname && $data->clientname != '')) {
                if ($tenant != $data->clientname) {
                    $tenant = $data->clientname;
                    startdata:

                    $str .= '<br>';
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($asof2, '1000', null, false, $border, '', '', $font, '20', '') . '<br />';
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->clientname, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->addr, '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Dear Sir/Ma\'am,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('This refers to your account with ' . ' ' . strtoupper($companyname[0]->name), '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br>';

                    $qry3 = "select c.acnoname,sum(ar.bal) as bal from coa as c
                    left join gldetail as detail on detail.acnoid=c.acnoid
                    left join glhead as head on head.trno=detail.trno
                     left join client as cl on cl.clientid=head.clientid
                     left join arledger as ar on ar.trno=detail.trno and ar.line=detail.line
                     where cl.client='" . $data->client . "'  and ar.bal<>0 and date(ar.dateid)<='" . $asof . "'
                     group by c.acnoname
                     ";
                    $data3 = $this->coreFunctions->opentable($qry3);

                    $totalbal = 0;
                    if (!empty($data3)) {
                        foreach ($data3 as $i => $value) {
                            $totalbal = $totalbal + $value->bal;
                        }
                    }

                    $fntotalbal = number_format((float) $totalbal, 2, '.', '');
                    $word = strtolower($this->reporter->ftNumberToWordsConverter($fntotalbal));
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Based on our records, you have unpaid balance with us amounting to' . ' ' . '<span style="font-weight:bold">' . trim(ucwords($word)) . ' ' . '(' . '&#x20B1' . ' ' . number_format($totalbal, 2) . ')' . '</span>' . ',' . ' ' . 'as of ' . '<span style="font-weight:bold">' . $asof2 . '</span>' . '.' . ' 
                    As this maybe an oversight on your part,we request to settle your balance three (3) days from receipt of this letter to avoid disconnection of all
                    utility services in your leased area. ', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('If you have already paid your overdue balance, please ignore this notice.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Thank you for your prompt action on this matter.', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();



                    $str .= '<br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Respectfully yours,', '1000', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br><br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Prepared', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["prepared"]), '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["position"]), '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br><br>';
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Noted By:', '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Note', '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(ucfirst($config['params']['dataparams']["position3"]), '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('Received Original Copy', '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('Signature', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();



                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('Printed Name', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();




                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header_3rd_letter($config);
                    goto   startdata;
                } else {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header_3rd_letter($config);
                    goto   startdata;
                }
            }
        }


        return $str;
    }
}
