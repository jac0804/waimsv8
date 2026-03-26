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

class statement_of_account_report
{
    public $modulename = 'Statement of Account Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1000px;max-width:1000px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];



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

        $fields = ['radioprint', 'month', 'byear', 'tenants'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');

        data_set($col1, 'byear.readonly', false);
        data_set($col1, 'byear.name', 'byear');
        data_set($col1, 'tenants.lookupclass', 'lookuptenant');
        data_set($col1, 'tenants.required', false);

        $fields = ['prepared', 'received', 'notedby1',];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'notedby1.label', 'Noted By');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $paramstr = "select 
        'default' as print,
          date_format(now(), '%M') as month,
            month(now()) as bmonth,
            year(now()) as byear,
            '' as tenants,
            '' as client,
            '' as clientname,
             '' as prepared,
              '' as received,
            '' as notedby1
           ";

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
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        return $this->reportDefaultLayout($config);
    }



    public function reportDefault($config)
    {
        $companyid = $config['params']['companyid'];
        $bmonth = $config['params']['dataparams']['bmonth'];
        $year = $config['params']['dataparams']['byear'];
        $center    = $config['params']['center'];
        $client    = $config['params']['dataparams']['client'];

        $month1 =  $bmonth - 1;
        $year1 =  $year - 1;

        $filter1 = "";

        if ($client != "") {
            $filter1 = "and cl.client='$client'";
        }




        $query = "select cl.client,cl.clientname,loc.name as locname,loc.area,head.docno,head.dateid as billingdate,head.due as ddue,
        ifnull((select sum(d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='SA6'),0) as penalty,
        ifnull((select sum(d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='SA1'),0) as rent,
        ifnull((select sum(d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='SA2'),0) as cusa,
        ifnull((select sum(d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='SA3'),0) as aircon,
        ifnull((select sum(d.cr) as cr from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='TX2'),0) as vat

                        from glhead as head
                        left join gldetail as detail on detail.trno=head.trno
                        left join cntnum as cnt on cnt.trno = head.trno
                        left join client as cl on cl.clientid=head.clientid
                        left join tenantbal as tbal on tbal.clientid=cl.clientid
                        left join loc as loc on loc.line = cl.locid
                        left join coa as coa on coa.acnoid=detail.acnoid where cnt.doc='MB' and
                        month(head.dateid)='" . $bmonth . "' and year(head.dateid)='" . $year . "' 
                          and ifnull(cl.client,'')<>'' and cnt.center = '" . $center . "' $filter1 
                        group by cl.client,cl.clientname,loc.name,loc.area,head.docno,head.dateid,head.due,head.trno
                        order by cl.client, cl.clientname";

        return $this->coreFunctions->opentable($query);
    }


    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '800';
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '800', null, false, '1px solid ', '', 'C', 'Century Gothic', '20', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '800', null, false, '1px solid ', '', 'C', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), '800', null, false, '1px solid ', '', 'C', 'Century Gothic', '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('STATEMENT OF ACCOUNTS', '800', null, false, $border, '', 'C', 'Courier New', '20', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result     = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $client    = $config['params']['dataparams']['client'];
        $daysdue = $this->companysetup->getdaysdue($config['params']);

        $prepared  = $config['params']['dataparams']['prepared'];
        $notedby   = $config['params']['dataparams']['notedby1'];
        $received  = $config['params']['dataparams']['received'];

        $bmonth = $config['params']['dataparams']['bmonth'];
        $year = $config['params']['dataparams']['byear'];

        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '800';
        $font = "Century Gothic";
        //  change to 12 $fontsize = "15";
        //  change to 10 $fontsize = "12";
        $fontsize = "10";

        $border = "1px solid ";

        $date = $year . '-' . $bmonth . '-01';
        $datep = date('Y-m-d', strtotime($date . '- 1 months'));
        $datep2 = date('Y-m-d', strtotime($datep . ' + 1 months'));
        $date1 = date_create($datep);
        $date2 = date_create($datep2);
        $diff = date_diff($date1, $date2);
        $difference = $diff->format('%a days');
        $datep3 = date('Y-m-d', strtotime($datep . $diff->format(' %a days')));
        $date4 =  date('Y-m-d', strtotime($datep3 . ' -1 day'));
        $prevdate = $datep;
        $enddate = $date4;
        $month1 =  date("m", strtotime($datep));
        $year1 =  date("Y", strtotime($datep));

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $tenant = '';
        foreach ($result as $key => $data) {
            if ($tenant == '' || ($tenant == $data->clientname && $data->clientname != '')) {
                if ($tenant != $data->clientname) {
                    $tenant = $data->clientname;
                    startdata:
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Tenant Name', '125', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col($data->clientname, '265', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '55', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('SOA No', '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col($data->docno, '265', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Location', '125', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col($data->locname, '263', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '55', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Billing Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(date('m/d/Y', strtotime($data->billingdate)), '267', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Area', '125', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(intval($data->area) . ' sqm', '263', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '55', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Due Date', '100', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(date('m/d/Y', strtotime($data->ddue)), '267', null, false, $border, 'B', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    //PREVIOUS ACC BALANCE
                    $previous_acc_bal = $this->coreFunctions->datareader("select tenantbal.amt as value from tenantbal left join client as client on client.clientid=tenantbal.clientid where client.client='" . $data->client . "' and tenantbal.bmonth='" . $month1 . "' and tenantbal.byear= '" . $year1 . "'  ");
                    if (empty($previous_acc_bal)) {
                        $previous_acc_bal = '0.00';
                    }

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Previous Balance', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, '12', 'B');
                    $str .= $this->reporter->col(number_format($previous_acc_bal, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';


                    // payment adjustment


                    $qry4 = "select head.docno,head.rem,date_format(head.dateid,'%m/%d/%Y') as dateid,client.clientname,
                    sum(detail.db-detail.cr) as pa
                    from glhead as head
                    left join gldetail as detail on detail.trno=head.trno
                    left join cntnum as cnt on cnt.trno = head.trno
                    left join client as client on client.clientid=detail.clientid
                    left join coa as coa on coa.acnoid=detail.acnoid where cnt.doc in ('CR','GJ','GD','GC','AR') and left(coa.alias,2)='ar'  and 
                    date(head.dateid) between '" . $prevdate . "' and '" . $enddate . "' 
                     and  client.client='" . $data->client . "'
                    group by head.docno,head.rem,head.dateid,client.clientname";

                    $data5 = $this->coreFunctions->opentable($qry4);
                    $totalpa = 0;
                    if (!empty($data5)) {
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('Payments and Adjustment', '300', null, false, $border, '', 'L', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();

                        foreach ($data5 as $i => $value) {
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col($value->docno, '200', null, false, $border, '', 'C', $font, $fontsize, '');
                            $str .= $this->reporter->col($value->dateid . ' ' . $value->rem, '400', null, false, $border, '', 'L', $font, $fontsize, '');
                            $str .= $this->reporter->col(number_format($value->pa, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalpa = $totalpa + $value->pa;
                        }
                    } else {
                        $totalpa = 0;
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('Payments and Adjustment', '300', null, false, $border, '', 'L', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B');
                        $str .= $this->reporter->col(number_format($totalpa, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    $str .= '<br>';
                    $outstanding_bal = $previous_acc_bal + $totalpa;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Outstanding Balance', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col(number_format($outstanding_bal, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Penalty Charges', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(number_format($data->penalty, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Current Charges', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, '');

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Rent', '200', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col(number_format($data->rent, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Cusa', '200', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col(number_format($data->cusa, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Aircon', '200', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(number_format($data->aircon, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Utilities', '90', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '190', null, false, $border, '', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Rate', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Previous', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Current', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('Consumption', '100', null, false, $border, '', 'C', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '120', null, false, $border, '', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    //electricity
                    $qry2 = "select hread.erate as rate, hread.estart as previous,client.clientname,
                            hread.eend as current, hread.consump,
                            date(hread.readstart) as rstart,date(hread.readend) as rend
                            from helectricreading as hread
                            left join gldetail as detail on detail.clientid=hread.clientid
                            left join coa as coa on coa.acnoid=detail.acnoid
                            left join client as client on client.clientid=detail.clientid
                            where   client.client='" . $data->client . "' and hread.bmonth='" . $bmonth . "' and hread.byear= '" . $year . "' and coa.alias='SA5'
                            group by hread.erate, hread.estart,client.clientname,
                            hread.eend, hread.consump, hread.readstart,hread.readend
                            order by client.client";
                    $data2 = $this->coreFunctions->opentable($qry2);


                    if (!empty($data2)) {
                        $total_elec = $data2[0]->rate * $data2[0]->consump;



                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('Electricity', '80', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col(date('m/d/Y', strtotime($data2[0]->rstart)) . ' - ' . date('m/d/Y', strtotime($data2[0]->rend)), '190', null, false, $border, '', 'C', $font, '14', '');
                        $str .= $this->reporter->col(number_format($data2[0]->rate, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data2[0]->previous, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data2[0]->current, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data2[0]->consump, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($total_elec, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    } else {

                        $rstart = '';
                        $rend = '';
                        $rate = 0;
                        $previous = 0;
                        $current = 0;
                        $consump = 0;
                        $total_elec = 0;

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('Electricity', '80', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col($rstart . '-' . $rend, '190', null, false, $border, '', 'C', $font, '14', '');
                        $str .= $this->reporter->col(number_format($rate, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($previous, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($current, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($consump, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($total_elec, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }


                    //UTILITIES WATER
                    $qry3 = "select wread.wrate as rate, wread.wstart as previous,
                     wread.wend as current, wread.consump,
                     date(wread.readstart) as rstart,date(wread.readend) as rend, client.clientname
                     from hwaterreading as wread
                     left join gldetail as detail on detail.clientid=wread.clientid
                    left join coa as coa on coa.acnoid=detail.acnoid
                    left join client as client on client.clientid=detail.clientid
                     where client.client='" . $data->client . "'and wread.bmonth='" . $bmonth . "' and wread.byear= '" . $year . "' and coa.alias='SA4'
                    group by wread.wrate, wread.wstart,
                    wread.wend, wread.consump,wread.readstart,wread.readend,client.clientname
                      order by client.client";
                    $data4 = $this->coreFunctions->opentable($qry3);

                    if (!empty($data4)) {
                        $total_water = $data4[0]->rate * $data4[0]->consump;
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('Water', '80', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col(date('m/d/Y', strtotime($data4[0]->rstart)) . ' - ' . date('m/d/Y', strtotime($data4[0]->rend)), '190', null, false, $border, '', 'C', $font, '14', '');
                        $str .= $this->reporter->col(number_format($data4[0]->rate, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data4[0]->previous, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data4[0]->current, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($data4[0]->consump, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($total_water, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    } else {
                        $rstart = '';
                        $rend = '';
                        $rate = 0;
                        $previous = 0;
                        $current = 0;
                        $consump = 0;
                        $total_water = 0;
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('Water', '80', null, false, $border, '', 'L', $font, $fontsize, '');
                        $str .= $this->reporter->col($rstart . '-' . $rend, '190', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($rate, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($previous, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($current, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($consump, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '');
                        $str .= $this->reporter->col(number_format($total_water, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    $str .= '<br>';



                    //OTHER CHARGES
                    $qry1 = "select o.description,hcharge.amt,o.isvat,client.clientname from hchargesbilling as hcharge
                        left join ocharges as o on o.line = hcharge.cline
                        left join client as client on client.clientid=hcharge.clientid
                         where client.client='" . $data->client . "' and hcharge.bmonth='" . $bmonth . "' and hcharge.byear= '" . $year . "' 
                         group by o.description,hcharge.amt,o.isvat,client.clientname
                           order by client.client";

                    $data3 = $this->coreFunctions->opentable($qry1);

                    $totalocharge = 0;
                    if (!empty($data3)) {

                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('Other Charges', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '600', null, false, $border, '', '', $font, $fontsize, 'B');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();

                        foreach ($data3 as $i => $value) {
                            if ($value->isvat == 1) {
                                $other_charges =  $value->amt / 1.12;
                            } else {
                                $other_charges = $value->amt;
                            }


                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, 'B');
                            $str .= $this->reporter->col($value->description, '300', null, false, $border, '', 'L', $font, $fontsize, '');
                            $str .= $this->reporter->col('', '280', null, false, $border, '', 'L', $font, $fontsize, '');
                            $str .= $this->reporter->col(number_format($other_charges, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalocharge = $totalocharge + $other_charges;
                        }
                    } else {
                        $other_charges = 0;
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col('Other Charges', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                        $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B');
                        $str .= $this->reporter->col(number_format($other_charges, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }


                    $total_charges = $data->rent + $data->cusa + $data->aircon + $total_water + $total_elec + $totalocharge;


                    $str .= '<br>';
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total Current Charges', '200', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '430', null, false, $border, '', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(number_format($total_charges, 2), '170', null, false, $border, 'T', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();



                    $qry5 = "select isnonvat from client where client='" . $data->client . "'";
                    $data6 = $this->coreFunctions->opentable($qry5);

                    if ($data6[0]->isnonvat == 1) {
                        $addvat = 0;
                    } else {
                        $addvat = $data->vat; //$total_charges * .12;
                    }



                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Add VAT', '80', null, false, $border, '', 'L', $font, $fontsize, 'B');
                    $str .= $this->reporter->col('', '520', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col(number_format($addvat, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    //Total Amount Due - Total charges + vat + penalty + outstanding balance

                    $total_amt_due = $total_charges + $addvat + $data->penalty + $outstanding_bal;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total Amount Due', '200', null, false, $border, 'T', 'L', $font, '20', 'B');
                    $str .= $this->reporter->col('', '430', null, false, $border, 'T', '', $font, $fontsize, 'B');
                    $str .= $this->reporter->col(number_format($total_amt_due, 2), '170', null, false, $border, 'TB', 'R', $font, '20', 'B');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br><br><br>';

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($prepared, '234', null, false, $border, '', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '149', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col($notedby, '234', null, false, $border, '', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '149', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col($received, '234', null, false, $border, '', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();

                    $str .= $this->reporter->col('Prepared By', '234', null, false, $border, 'T', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '149', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col('Noted By', '234', null, false, $border, 'T', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->col('', '149', null, false, $border, '', '', $font, $fontsize, '');
                    $str .= $this->reporter->col('Received By', '234', null, false, $border, 'T', 'C', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= '<br>';


                    //COMPANYNAME
                    $qry = "select name  from center where code = '" . $center . "'";
                    $data1 = $this->coreFunctions->opentable($qry);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Please make checks payable to ' . $data1[0]->name, '800', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    //PENALTY RATE
                    $penaltyrate  = $this->coreFunctions->datareader("select round(tenantinfo.penalty) as value from tenantinfo left join client as client on client.clientid=tenantinfo.clientid  where client.client='" . $data->client  . "'");
                    //daysdue
                    $date = $year . '-' . $daysdue . '-1';
                    $duedate = date('Y-n-d', strtotime($date . '+ 1 month'));
                    $due = date('n', strtotime($duedate));
                    $locale = 'en_US';
                    $nf = numfmt_create($locale, \NumberFormatter::ORDINAL);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('All unpaid balance is subject to ' . $penaltyrate . ' %' . ' penalty', '800', null, false, $border, '', 'L', $font, $fontsize, '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Due date is every ' . $nf->format($due) . ' ' . 'of the month', '800', null, false, $border, '', 'L', $font, $fontsize, ''); //$nf->format($due)
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            } else {

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config);
                    goto   startdata;
                } else {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config);
                    goto   startdata;
                }
            }
        }


        return $str;
    }
}
