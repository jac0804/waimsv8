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

class comparative_data_of_billing_and_collection
{
    public $modulename = ' Comparative Data of Billing & Collection';
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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        left(now(),10) as end
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function report_qry_Default($config)
    {
        $companyid = $config['params']['companyid'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $center    = $config['params']['center'];

        $query = "select cl.clientid,cl.clientname as tenant,sum(abs(detail.cr-detail.db)) as totalbilling from glhead as head
                    left join client as cl on cl.clientid=head.clientid
                    left join gldetail as detail on detail.trno=head.trno
                    left join coa as coa on coa.acnoid=detail.acnoid where head.doc='MB'
                    and left(coa.alias,2)='AR' and date(head.dateid) between '" . $start . "' and '" . $end . "'
                    group by cl.clientid,cl.clientname";

        return $this->coreFunctions->opentable($query);
    }

    private function default_header($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $start        = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
        $end          = date("m-d-Y", strtotime($config['params']['dataparams']['end']));
        $daysdue = $this->companysetup->getdaysdue($config['params']);

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid ', '', 'C', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1000', null, false, '1px solid ', '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Comparative Data of Billing & Collection', '1000', null, false, $border, '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the period of ' . $start . ' to ' . $end, '1000', null, false, $border, '', 'C', $font, '12', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $date1 = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date :' . date('m/d/Y', strtotime($date1)), '500', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '12', '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //daysdue
        $dates = $this->othersClass->getCurrentTimeStamp();
        $yearmon = date('Y-n', strtotime($dates));
        $date = $yearmon . '-' . $daysdue;
        $duedate = date('Y-n-j', strtotime($date . '+ 1 days'));
        $due = date('j', strtotime($duedate));
        $locale = 'en_US';
        $nf = numfmt_create($locale, \NumberFormatter::ORDINAL);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tenant', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Billing', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Collection', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Collected(%)', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Uncollected(%)', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Collection on or before the ' . $nf->format($due) . '(%)', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    private function reportDefaultLayout($config)
    {
        $result     = $this->report_qry_Default($config);
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $daysdue = $this->companysetup->getdaysdue($config['params']);
        $str = '';
        $count = 60; #60
        $page = 59; #59
        $layoutsize = '1000';
        $font = "Arial";
        //  change to 10 $fontsize = "13";
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        foreach ($result as $key => $data) {
            //Total Collection
            $qry2 = " select cl.clientid , cl.clientname, ifnull(sum(detail.db-detail.cr),0) as totalcollections
                        from glhead as head
                        left join client as cl on cl.clientid=head.clientid
                        left join gldetail as detail on detail.trno=head.trno
                        left join coa as coa on coa.acnoid=detail.acnoid where head.doc='CR' and left(coa.alias,2) in ('ca','cb','cr')
                        and date(head.dateid) between '" . $start . "' and '" . $end . "'
                        and cl.clientid='" . $data->clientid . "'
                        group by cl.clientid,cl.clientname ";

            $data1 = $this->coreFunctions->opentable($qry2);
            if (!empty($data1)) {
                $totalcollection = $data1[0]->totalcollections;
            } else {
                $totalcollection = 0;
            }

            //Collected %- if hindi 0 ung billing = (collected/billing)*100
            if ($data->totalbilling <> 0) {
                $collected = ($totalcollection / $data->totalbilling) * 100;
            }

            //Uncollected% - ((billing-collected)/billing)*100
            $uncollected = (($data->totalbilling - $totalcollection) / $data->totalbilling) * 100;

            //Collected on or before daysdue
            //daysdue
            $dates = $this->othersClass->getCurrentTimeStamp();
            $yearmon = date('Y-n', strtotime($dates));
            $date = $yearmon . '-' . $daysdue;
            $duedate = date('Y-n-j', strtotime($date . '+ 1 days'));
            $due = date('d', strtotime($duedate));

            //startdate ym and ddue
            $date3 = date('Y-m', strtotime($start));
            $start2 = $date3 . '-' . '01';
            $end2 =  $date3 . '-' . $due;


            $qry3 = "select cl.clientid , cl.clientname, ifnull(sum(detail.db-detail.cr),0) as totalcollections
            from glhead as head
            left join client as cl on cl.clientid=head.clientid
            left join gldetail as detail on detail.trno=head.trno
            left join coa as coa on coa.acnoid=detail.acnoid where head.doc='CR' and left(coa.alias,2) in ('ca','cb','cr')
            and date(head.dateid) between '" . $start2 . "' and '" . $end2 . "'
            and cl.clientid='" . $data->clientid . "'
            group by cl.clientid,cl.clientname ";
            $data2 = $this->coreFunctions->opentable($qry3);

            if (!empty($data2)) {
                $totalcollection2 = $data2[0]->totalcollections;
            } else {
                $totalcollection2 = 0;
            }

            //Collected on or before daysdue
            $collb = ($totalcollection2 / $data->totalbilling) * 100;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->tenant, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->totalbilling, 2), '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($totalcollection, 2), '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($collected, 2) . '%', '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($uncollected, 2) . '%', '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($collb, 2) . '%', '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($config);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}
