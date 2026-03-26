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

class daily_collection_report
{
    public $modulename = 'Daily Collection Report';
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

        $fields = ['radioprint', 'radiocollectiontype', 'radioposttype', 'start', 'end', 'paytype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
            ['label' => 'ALL', 'value' => '2', 'color' => 'teal']
        ]);
        data_set($col1, 'radiopaymenttype.label', 'Payment Type');
        data_set($col1, 'radiopaymenttype.options', [
            ['label' => 'Cash', 'value' => 'cash', 'color' => 'teal'],
            ['label' => 'Charge', 'value' => 'charge', 'color' => 'teal'],
            ['label' => 'Check', 'value' => 'check', 'color' => 'teal'],
            ['label' => 'Deposit', 'value' => 'deposit', 'color' => 'teal'],
            ['label' => 'All', 'value' => 'all', 'color' => 'teal']
        ]);

        data_set($col1, 'start.readonly', false);
        data_set($col1, 'end.readonly', false);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }
    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $paramstr = "select 
        'default' as print,
        'pdc' as collection,
        '0' as posttype,
         adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as paytype
        
        
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

        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $paytype = $config['params']['dataparams']['paytype'];
        $collectiontype = $config['params']['dataparams']['collection'];
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        switch ($paytype) {
            case 'Cash':
                $paytype =  "head.ourref = 'Cash' and";
                break;
            case 'Cheque':
                $paytype = "head.ourref = 'Cheque' and";
                break;

            default:
                $paytype = "";
                break;
        }
        switch ($collectiontype) {
            case 'pdc':
                switch ($posttype) {
                        //posted
                    case '0':
                        $query = "select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,detail.amount as amount
 from hrchead as head
left join hrcdetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join loc as loc on loc.line = cl.locid
where $paytype  date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'RC'
group by head.doc,date(head.dateid),clientname,loc,rem,ourref,bank,detail.checkno,seqdoc,detail.amount order by seqdoc,dateid";
                        break;
                        //unposted
                    case '1':
                        $query = "select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,detail.amount as amount
 from rchead as head
left join rcdetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join loc as loc on loc.line = cl.locid
where $paytype date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'RC'
group by head.doc,date(head.dateid),clientname,loc,rem,ourref,bank,detail.checkno,seqdoc,detail.amount order by seqdoc,dateid";
                        break;
                        //all
                    case '2':
                        $query = "select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,detail.amount as amount
 from rchead as head
left join rcdetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join loc as loc on loc.line = cl.locid
where $paytype date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'RC'
group by head.doc,date(head.dateid),clientname,loc,rem,ourref,bank,detail.checkno,seqdoc,detail.amount

union all
select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,detail.amount as amount
 from hrchead as head
left join hrcdetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join loc as loc on loc.line = cl.locid
where $paytype date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'RC'
group by head.doc,date(head.dateid),clientname,loc,rem,ourref,bank,detail.checkno,seqdoc,detail.amount order by seqdoc,dateid";
                        break;
                }
                break;

            case 'cach':
                switch ($posttype) {
                        //posted
                    case '0':
                        $query =   "select head.doc,date(head.dateid) as dateid,tenant.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,sum(detail.db-detail.cr) as amount from glhead as head
left join gldetail as detail on detail.trno = head.trno
left join coa as al on al.acnoid = detail.acnoid
left join cntnum as num on num.trno=head.trno
left join client as tenant on tenant.clientid = head.clientid
left join loc as loc on loc.line = tenant.locid
where $paytype left(al.alias,2) in ('CR','CA','CB') and
date(head.dateid) between '" . $start . "' and '" . $end . "'  and head.doc = 'CR' 
group by head.doc,head.dateid,tenant.clientname,loc,rem,ourref,bank,head.amount,detail.checkno,seqdoc order by seqdoc";
                        break;
                    case '1':
                        //unposted
                        $query = "select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc,sum(detail.db-detail.cr) as amount
from lahead as head
left join ladetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join coa as al on al.acnoid = detail.acnoid
left join loc as loc on loc.line = cl.locid
where $paytype left(al.alias,2) in ('CR','CA','CB') and date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'CR'
group by head.doc,head.dateid,cl.clientname,loc,head.rem,head.ourref,bank,detail.checkno,seqdoc,head.amount";
                        break;
                        //all
                    case '2':
                        $query = "select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc ,sum(detail.db-detail.cr) as amount from glhead as head
left join gldetail as detail on detail.trno = head.trno
left join coa as al on al.acnoid = detail.acnoid
left join cntnum as num on num.trno=head.trno
left join client as cl on cl.clientid = head.clientid
left join loc as loc on loc.line = cl.locid
where $paytype left(al.alias,2) in ('CR','CA','CB') and
date(head.dateid) between '" . $start . "' and '" . $end . "'  and head.doc = 'CR'
group by head.doc,head.dateid,cl.clientname,loc,head.rem,head.ourref,bank,head.amount,detail.checkno,seqdoc
union all
select head.doc,date(head.dateid) as dateid,cl.clientname,ifnull(loc.name,'') as loc,head.rem,head.ourref,
substring_index(detail.checkno,',',1) as bank,substring_index(detail.checkno,',',-1) as checkno,
num.seq as seqdoc,sum(detail.db-detail.cr) as amount
from lahead as head
left join ladetail as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join cntnum as num on num.trno=head.trno
left join coa as al on al.acnoid = detail.acnoid
left join loc as loc on loc.line = cl.locid
where $paytype left(al.alias,2) in ('CR','CA','CB') and date(head.dateid) between '" . $start . "' and '" . $end . "' and head.doc = 'CR'
group by head.doc,head.dateid,cl.clientname,loc,head.rem,head.ourref,bank,detail.checkno,seqdoc,head.amount order by seqdoc,dateid";
                        break;
                }
                break;
        }


        return $this->coreFunctions->opentable($query);
    }
    private function displayHeader($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $center     = $config['params']['center'];
        $start   = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $str = '';
        $layoutsize = '800';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        switch ($posttype) {
            case '0':
                $posttype = 'Posted';
                break;

            case '1':
                $posttype = 'Unposted';
                break;
            case '2':
                $posttype = 'All';
                break;
        }
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
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tin),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '') . '';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('m-d-Y', strtotime($start)) . ' to ' . date('m-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Status: ' . $posttype, null, null, false, $border, '', 'C', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $date = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('Run Date: ' . date("m-d-Y H:i:s A", strtotime($date)), null, null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->col($this->reporter->pagenumber('Page'), null, null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('', '', null, false, '2px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Location', '88', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Tenant', '128', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Particulars', '118', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Payment Type', '88', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Bank', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Check No.', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('OR/PR #', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Amount', '86', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
    public function reportDefaultLayout($config)
    {
        $result     = $this->reportDefault($config);

        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '800';
        $font = "Century Gothic";
        //  change to 10 $fontsize = "11";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('m-d-Y', strtotime($data->dateid)), '80', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->loc, '88', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->clientname, '128', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->rem, '118', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->ourref, '88', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->bank, '60', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->checkno, '60', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col($data->seqdoc, '60', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '86', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->endtable();
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
            }
        }


        return $str;
    }
}
