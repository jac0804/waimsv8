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

class billing_vs_collection
{
    public $modulename = 'Billing vs Collection';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    private $logger;
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
        #tenants
        $fields = ['radioprint', 'radioreporttype', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);


        if ($this->companysetup->getmultibranch($config['params'])) {
            data_set($col1, 'radioreporttype.option', [
                ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'orange']

            ]);
        } else {
            data_set($col1, 'radioreporttype.options', [
                ['label' => 'Detailed', 'value' => '1', 'color' => 'orange']
            ]);
        }
        data_set($col1, 'start.readonly', false);
        data_set($col1, 'end.readonly', false);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        #multi branch
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }
    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $paramstr = "select 
        'default' as print,
        '1' as reporttype,
         adddate(left(now(),10),-360) as start,
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        return  $reporttype == 0 ?  $this->reportDefaultLayout_summary($config) : $this->reportDefaultLayout_detail($config);
    }
    public function reportDefault($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {

                #summary
            case '0':
                $query = "select  cn.name as mall,cn.line, date(head.dateid) as dateid ,sum(detail.db - detail.cr) as tbill from glhead as head
         left join gldetail as detail on detail.trno = head.trno
         left join cntnum as cnt on cnt.trno = head.trno
         left join center  as cn on cn.code = cnt.center where head.doc = 'MB' and date(head.dateid) between '" . $start . "' and '" . $end . "' group by cn.name,head.dateid,cn.line";
                break;
            case '1':
                #detail
                $query = "select date(head.dateid) as dateid, tenant.clientname as tenant,ifnull(loc.name,'') as location, sum(detail.db - detail.cr) as tbill,head.trno,tenant.clientid
                        from glhead as head
                        left join gldetail as detail on detail.trno = head.trno
                        left join client as tenant on tenant.clientid = detail.clientid
                        left join loc as loc on loc.line = tenant.locid
                        where head.doc = 'MB' and date(head.dateid) between '" . $start . "' and '" . $end . "' 
                        group by head.dateid,tenant.clientname,head.docno,loc.name,head.trno,tenant.clientid";
                break;
        }
        return $this->coreFunctions->opentable($query);
    }
    public function displayHeader_detail($config)
    {
        $center     = $config['params']['center'];
        $start   = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $str = '';
        $layoutsize = '800';

        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '20', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tin),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename . '(Detailed)', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the period of ' . date('m-d-Y', strtotime($start)) . ' to ' . date('m-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $date = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('Print Date: ' . date("m-d-Y H:i:s A", strtotime($date)), '740', null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->col($this->reporter->pagenumber('Page'), null, null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('', '', null, false, '2px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('', '', null, false, '1px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tenant', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Location', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Outstanding Balance', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Billing', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Collection', '126', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Balance', '114', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function reportDefaultLayout_detail($config)
    {

        $result = $this->reportDefault($config);
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';

        $layoutsize = '800';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $totalamount = 0;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_detail($config);
        $tenant = '';

        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $query = "select * from (select ifnull(sum(detail.db - detail.cr),0) as collection,
                   ifnull((select sum(ar.bal) from arledger as ar 
                   where head.doc = 'MB' and  date(head.dateid) < '" . $start . "'),0) as outstandbal
            from gldetail as detail
            left join glhead as head on head.trno=detail.trno
            left join client as tenant on tenant.clientid = detail.clientid
            where head.doc in ('CR','MB') and detail.clientid = $data->clientid and date(head.dateid) between '" . $start . "' and '" . $end . "'  
            group by outstandbal order by head.dateid) as t";
            $collectb =  $this->coreFunctions->opentable($query);
            foreach ($collectb as $key23 => $data2) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->tenant, '150', null, false, $border, '', 'L', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col($data->location, '130', null, false, $border, '', 'L', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data2->outstandbal, 2), '130', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data->tbill, 2), '130', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data2->collection, 2), '126', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $balance = (($data->tbill + $data2->outstandbal) - $data2->collection);
                $str .= $this->reporter->col(number_format($balance, 2), '114', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
    public function displayHeader_summary($config)
    {
        $center     = $config['params']['center'];
        $start   = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $str = '';
        $layoutsize = '800';

        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tin from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '20', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tin),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename . '(Summary)', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the period of ' . date('m-d-Y', strtotime($start)) . ' to ' . date('m-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $date = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('Print Date: ' . date("m-d-Y H:i:s A", strtotime($date)), '740', null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->col($this->reporter->pagenumber('Page'), null, null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('', '', null, false, '2px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('', '', null, false, '1px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Mall', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Outstanding Balance', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Billing', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Collection', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Uncollected', '144', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function reportDefaultLayout_summary($config)
    {
        $result = $this->reportDefault($config);
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';

        $layoutsize = '800';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $totalamount = 0;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_summary($config);
        $balance = 0;
        $totaloutstandbal  = 0;
        $totalbill = 0;
        $totalcollection = 0;
        $totalbalance = 0;
        foreach ($result as $key => $data) {

            $query = "select sum(collection) as collection,sum(outstandbal) as outstandbal from (
select sum(detail.db - detail.cr) as collection , ifnull((select sum(ar.bal) from arledger as ar where head.doc = 'MB' and date(head.dateid) < '" . $start . "'),0) as outstandbal from glhead as head
 left join gldetail as detail on detail.trno = head.trno
 left join cntnum as cnt on cnt.trno = head.trno
         left join center  as cn on cn.code = cnt.center 
         where head.doc in ('CR','MB') and cn.line = $data->line and date(head.dateid) between '" . $start . "' and '" . $end . "'   group by head.dateid,outstandbal
)as t";
            $collection =  $this->coreFunctions->opentable($query);
            foreach ($collection as $key2 => $data2) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->mall, '160', null, false, $border, '', 'L', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data2->outstandbal, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data->tbill, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $str .= $this->reporter->col(number_format($data2->collection, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
                $balance = (($data2->outstandbal + $data->tbill) - $data2->collection);
                $str .= $this->reporter->col(number_format($balance, 2), '144', null, false, $border, '', 'R', $font, $fontsize, '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $totaloutstandbal += $data2->outstandbal;
                $totalbill += $data->tbill;
                $totalcollection += $data2->collection;
                $totalbalance += $balance;
            }
        }
        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '160', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($totaloutstandbal, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($totalbill, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($totalcollection, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
        $str .= $this->reporter->col(number_format($totalbalance, 2), '144', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}
