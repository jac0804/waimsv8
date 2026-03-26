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

class billing_summary
{
    public $modulename = 'Billing Summary';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1600px;max-width:1600px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1500'];



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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'radioreporttype', 'tenants', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Summary', 'value' => '0', 'color' => 'blue'],
                ['label' => 'Current', 'value' => '1', 'color' => 'blue']
            ]
        );
        data_set($col1, 'tenants.lookupclass', 'lookuptenant');
        data_set($col1, 'tenants.required', false);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
        'default' as print,
        '0' as reporttype,
        '' as tenants,
        '' as client,
        '' as clientname,
        adddate(left(now(),10),-30) as start,
        left(now(),10) as end";

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
            case '0': // summary
                $str = $this->summary_reportDefaultLayout($config);
                break;
            case '1': //current
                $str = $this->current_reportDefaultLayout($config);
                break;
        }

        return $str;
    }

    public function report_qry_Default($config)
    {
        $companyid = $config['params']['companyid'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client    = $config['params']['dataparams']['client'];
        $filter1 = "";
        if ($client != "") {
            $filter1 = "and cl.client='$client'";
        }


        $query = "select cl.clientid,cl.clientname as tenant, loc.name as locname,

                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias='AR1'),0) as rent,
                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias='AR2'),0) as cusa,
                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='AR3'),0) as aircon,
                               
                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias='AR4'),0) as water,
                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias='AR5'),0) as electricity,

                                ifnull((select sum(abs(d.db-d.cr)) as dvat from gldetail as d  left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='TX2'),0) as dvat,
                                ifnull((select sum(abs(d.db-d.cr)) as secdep from gldetail as d  left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='AP10'),0) as secdepo,
                                ifnull((select sum(d.db-d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias not in ('AR1','AR2','AR3','AR4','AR5') and left(coa.alias,2)='AR'),0) as othercharge,
                                0 as selec
                            
                                from glhead as head
                                left join gldetail as detail on detail.trno=head.trno
                                left join cntnum as cnt on cnt.trno = head.trno
                                left join client as cl on cl.clientid=head.clientid
                                left join loc as loc on loc.line = cl.locid
                                left join coa on coa.acnoid = detail.acnoid where cnt.doc='MB' and date(head.dateid) between '" . $start . "' and '" . $end . "' and ifnull(cl.client,'')<>''  $filter1
                                group by cl.clientid,cl.clientname,loc.name,head.trno,head.dateid";
        return $this->coreFunctions->opentable($query);
    }

    private function current_default_header($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1400';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $start        = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
        $end          = date("m-d-Y", strtotime($config['params']['dataparams']['end']));
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1400', null, false, $border, '', 'C', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1400', null, false, $border, '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Billing Summary(Current Charges)', '1400', null, false, $border, '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the period of ' . $start . ' to ' . $end, '1400', null, false, $border, '', 'C', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $date1 = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date :' . date('m/d/Y', strtotime($date1)), '1400', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '10', '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tenant', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Location', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rent', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Aircon', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Electricity', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Water', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Storage Electricity', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Other Charges', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sec. Deposit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Deferred Vat', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    private function current_reportDefaultLayout($config)
    {
        $result     = $this->report_qry_Default($config);
        $str = '';
        $count = 60; #60
        $page = 59; #59
        $layoutsize = '1400';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->current_default_header($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $grent = 0;
        $gcusa = 0;
        $gaircon = 0;
        $gelectricity = 0;
        $gwater = 0;
        $gselec = 0;
        $gothercharge = 0;
        $gsecdepo = 0;
        $gdvat = 0;
        $gtotal = 0;

        foreach ($result as $key => $data) {
            $total = $data->rent + $data->cusa + $data->aircon + $data->electricity + $data->water + $data->selec + $data->othercharge + $data->secdepo + $data->dvat;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->tenant, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->locname, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->rent, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->cusa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->aircon, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->electricity, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->water, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->selec, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->othercharge, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->secdepo, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->dvat, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $grent += $data->rent;
            $gcusa += $data->cusa;
            $gaircon += $data->aircon;
            $gelectricity += $data->electricity;
            $gwater += $data->water;
            $gselec += $data->selec;
            $gothercharge += $data->othercharge;
            $gsecdepo += $data->secdepo;
            $gdvat += $data->dvat;
            $gtotal += $total;

            $str .= $this->reporter->endrow();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->current_default_header($config);
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grent, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gcusa, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gaircon, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gelectricity, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gwater, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gselec, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gothercharge, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsecdepo, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gdvat, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }



    private function summary_default_header($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1500';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $start        = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
        $end          = date("m-d-Y", strtotime($config['params']['dataparams']['end']));

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1500', null, false, $border, '', 'C', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1500', null, false, $border, '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Billing Summary', '1500', null, false, $border, '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the period of ' . $start . ' to ' . $end, '1500', null, false, $border, '', 'C', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $date1 = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date :' . date('m/d/Y', strtotime($date1)), '1500', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '10', '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tenant', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Location', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Outstanding Balance', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rent', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Aircon', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Electricity', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Water', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Storage Electricity', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Other Charges', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sec. Deposit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Deferred Vat', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }


    private function summary_reportDefaultLayout($config)
    {
        $result     = $this->report_qry_Default($config);
        $str = '';
        $count = 30; #60
        $page = 30; #59
        $layoutsize = '1500';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->summary_default_header($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $gobalance = 0;
        $grent = 0;
        $gcusa = 0;
        $gaircon = 0;
        $gelectricity = 0;
        $gwater = 0;
        $gselec = 0;
        $gothercharge = 0;
        $gsecdepo = 0;
        $gdvat = 0;
        $gtotal = 0;
        foreach ($result as $key => $data) {

            $qry2 = " select cl.client,sum(ar.bal) as bal from arledger as ar
                                           left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
                                            left join glhead as head on head.trno=detail.trno
                                            left join cntnum as cnt on cnt.trno = head.trno
                                            left join client as cl on cl.clientid=head.clientid
                                            left join loc as loc on loc.line = cl.locid
                                             where cnt.doc='MB' and date(ar.dateid)<'" . $start . "' and cl.clientid='" . $data->clientid . "' 
                                            group by cl.client,head.trno";
            $data2 = $this->coreFunctions->opentable($qry2);
            if (!empty($data2)) {
                $obalance = $data2[0]->bal;
            } else {
                $obalance = 0;
            }

            $total = $obalance + $data->rent + $data->cusa + $data->aircon + $data->electricity + $data->water + $data->selec + $data->othercharge + $data->secdepo + $data->dvat;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->tenant, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->locname, '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($obalance, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->rent, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->cusa, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->aircon, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->electricity, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->water, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->selec, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->othercharge, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->secdepo, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($data->dvat, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $gobalance += $obalance;
            $grent += $data->rent;
            $gcusa += $data->cusa;
            $gaircon += $data->aircon;
            $gelectricity += $data->electricity;
            $gwater += $data->water;
            $gselec += $data->selec;
            $gothercharge += $data->othercharge;
            $gsecdepo += $data->secdepo;
            $gdvat += $data->dvat;
            $gtotal += $total;

            $str .= $this->reporter->endrow();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->summary_default_header($config);
                $page = $page + $count;
            }
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gobalance, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grent, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gcusa, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gaircon, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gelectricity, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gwater, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gselec, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gothercharge, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsecdepo, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gdvat, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}
