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

class collection_summary
{
    public $modulename = 'Collection Summary';
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
        $fields = ['radioprint', 'radioreporttype', 'start', 'end', 'radioposttype', 'tenants'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'radioposttype.options', [
            ['label' => 'Posted', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Unposted', 'value' => '1', 'color' => 'orange'],
            ['label' => 'ALL', 'value' => '2', 'color' => 'orange']
        ]);
        data_set($col1, 'start.readonly', false);
        data_set($col1, 'end.readonly', false);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'tenants.lookupclass', 'lookupclient');
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
        '0' as posttype,
        '0' as reporttype,
         adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as tenants,
        '' as client,
        '' as clientname
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
        return $reporttype == 0 ? $this->reportDefaultLayout_summary($config) : $this->reportDefaultLayout_detail($config);
    }
    public function reportDefault($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];;
        $tenant = $config['params']['dataparams']['clientname'];
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        #tenant
        #posted
        #unposted

        if ($reporttype == 0) {
            if ($tenant != '') {
                $tenant = "and tenant.clientname =  '$tenant'";
            } else {
                $tenant = '';
            }
        } else {
            if ($tenant != '') {
                $tenant = "and clientname =  '$tenant'";
            } else {
                $tenant = '';
            }
        }
        switch ($reporttype) {
                //summary
            case '0':
                switch ($posttype) {
                        //posted
                    case '0':
                        $query =   "select date(dateid) as dateid,head.docno,tenant.clientname, ifnull(loc.name,'') as loc,sum(detail.db-detail.cr) as amount
    from glhead as head
    left join gldetail as detail on detail.trno = head.trno
    left join client as tenant on tenant.clientid = head.clientid
    left join loc as loc on loc.line = tenant.locid
    left join coa as al on al.acnoid = detail.acnoid
     where doc = 'CR' and left(al.alias,2) in ('AR')  and date(head.dateid) between '$start' and '$end'  $tenant
    group by date(head.dateid),head.docno,tenant.clientname,loc order by dateid";
                        break;
                        //unposted
                    case '1':
                        $query = "select date(dateid) as dateid,head.docno,tenant.clientname, ifnull(loc.name,'') as loc,sum(detail.db-detail.cr) as amount
    from lahead as head
    left join ladetail as detail on detail.trno = head.trno
    left join client as tenant on tenant.client = head.client
    left join loc as loc on loc.line = tenant.locid
  left join coa as al on al.acnoid = detail.acnoid
    where doc = 'CR' and left(al.alias,2) in ('AR')  and date(head.dateid) between '$start' and '$end' $tenant
    group by date(head.dateid),head.docno,tenant.clientname,loc order by dateid";
                        break;
                        //all
                    case '2':
                        $query = "select date(dateid) as dateid,head.docno,tenant.clientname, ifnull(loc.name,'') as loc,sum(detail.db-detail.cr) as amount
    from glhead as head
    left join gldetail as detail on detail.trno = head.trno
    left join client as tenant on tenant.clientid = head.clientid
    left join loc as loc on loc.line = tenant.locid
    left join coa as al on al.acnoid = detail.acnoid
     where doc = 'CR' and left(al.alias,2) in ('AR')  and date(head.dateid) between '$start' and '$end' $tenant
    group by date(head.dateid),head.docno,tenant.clientname,loc
union all
select date(dateid) as dateid,head.docno,tenant.clientname, ifnull(loc.name,'') as loc,sum(detail.db-detail.cr) as amount
    from lahead as head
    left join ladetail as detail on detail.trno = head.trno
    left join client as tenant on tenant.client = head.client
    left join loc as loc on loc.line = tenant.locid
  left join coa as al on al.acnoid = detail.acnoid
    where doc = 'CR' and left(al.alias,2) in ('AR')  and date(head.dateid) between '$start' and '$end'  $tenant
    group by date(head.dateid),head.docno,tenant.clientname,loc order by dateid";
                        break;
                }

                break;
            case '1':
                //detailed
                switch ($posttype) {
                        //posted
                    case '0':
                        $query = "select dateid, doc,clientname,loc,docno,rent,cusa,aircon,water,elec,reimb,others,amtt from (
select date(head.dateid) as dateid ,head.doc ,head.docno,cl.clientname, ifnull(loc.name,'') as loc,
sum(detail.rent) as rent,sum(detail.cusa) as cusa,sum(detail.aircon) as aircon,sum(detail.water) as water,
sum(detail.elec) as elec,sum(detail.reimb) as reimb,sum(detail.penalty) as others,
sum(detail.rent + detail.cusa + detail.aircon + detail.water + detail.elec + detail.penalty + detail.reimb) as amtt
from glhead as head
left join (select trno,clientid,al.acnoid,
(case when al.alias = 'AR1' then (db-cr) else 0 end)  as rent,
(case when al.alias = 'AR2' then (db-cr) else 0 end) as cusa,
(case when al.alias = 'AR3' then (db-cr) else 0 end) as aircon,
(case when al.alias = 'AR4' then (db-cr) else 0 end) as water,
(case when al.alias = 'AR5' then (db-cr) else 0 end) as elec,
(case when al.alias = 'ARR1' then (db-cr) else 0 end) as reimb,
(case when al.alias not in ('AR1','AR2','AR3','AR4','AR5','ARR1') then (db-cr) else 0 end) as penalty
from gldetail as detail
left join coa as al on al.acnoid = detail.acnoid
where left(al.alias,2) = 'AR' 
) as detail on detail.trno = head.trno
left join client as cl on cl.clientid = detail.clientid
left join loc as loc on loc.line = cl.locid  where head.doc = 'CR'  and date(head.dateid) between '$start' and '$end' $tenant group by cl.clientname,head.docno,head.doc,head.dateid,loc.name)  as cr order by dateid";
                        break;
                        //unposted
                    case '1':
                        $query = "select dateid, doc,clientname,loc,docno,rent,cusa,aircon,water,elec,reimb,others,amtt  from (
select date(head.dateid) as dateid,head.doc,head.docno,cl.clientname,ifnull(loc.name,'') as loc,
sum(detail.rent) as rent ,sum(detail.cusa) as cusa ,sum(detail.aircon) as aircon ,sum(detail.water) as water,sum(detail.elec) as elec,
sum(detail.reimb) as reimb,sum(detail.penalty) as others,
sum(detail.rent + detail.cusa + detail.aircon + detail.water + detail.elec + detail.penalty +detail.reimb)
as amtt
 from lahead as head
left join (select trno, detail.client, al.acnoid,
(case when al.alias = 'AR1' then (db-cr) else 0 end)  as rent,
(case when al.alias = 'AR2' then (db-cr) else 0 end) as cusa,
(case when al.alias = 'AR3' then (db-cr) else 0 end) as aircon,
(case when al.alias = 'AR4' then (db-cr) else 0 end) as water,
(case when al.alias = 'AR5' then (db-cr) else 0 end) as elec,
(case when al.alias = 'ARR1' then (db-cr) else 0 end) as reimb,
(case when al.alias not in ('AR1','AR2','AR3','AR4','AR5','ARR1') then (db - cr) else 0 end) as penalty
from ladetail as detail
left join coa as al on al.acnoid = detail.acnoid 
where left(al.alias,2) = 'AR' ) as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join loc as loc on loc.line = cl.locid
where head.doc = 'CR'  and date(head.dateid) between '$start' and '$end' $tenant group by cl.clientname,head.docno,head.doc,head.dateid,loc) as cr";
                        break;
                    case '2':
                        $query = "select dateid, doc,clientname,loc,docno,rent,cusa,aircon,water,elec,reimb,others,amtt from (
select date(head.dateid) as dateid ,head.doc ,head.docno,cl.clientname, ifnull(loc.name,'') as loc,
sum(detail.rent) as rent,sum(detail.cusa) as cusa,sum(detail.aircon) as aircon,sum(detail.water) as water,
sum(detail.elec) as elec,sum(detail.reimb) as reimb,sum(detail.penalty) as others,
sum(detail.rent + detail.cusa + detail.aircon + detail.water + detail.elec + detail.penalty + detail.reimb) as amtt
from glhead as head
left join (select trno,clientid,al.acnoid,
(case when al.alias = 'AR1' then (db-cr) else 0 end)  as rent,
(case when al.alias = 'AR2' then (db-cr) else 0 end) as cusa,
(case when al.alias = 'AR3' then (db-cr) else 0 end) as aircon,
(case when al.alias = 'AR4' then (db-cr) else 0 end) as water,
(case when al.alias = 'AR5' then (db-cr) else 0 end) as elec,
(case when al.alias = 'ARR1' then (db-cr) else 0 end) as reimb,
(case when al.alias not in ('AR1','AR2','AR3','AR4','AR5','ARR1') then (db-cr) else 0 end) as penalty
from gldetail as detail
left join coa as al on al.acnoid = detail.acnoid
where left(al.alias,2) = 'AR'
) as detail on detail.trno = head.trno
left join client as cl on cl.clientid = detail.clientid
left join loc as loc on loc.line = cl.locid  where head.doc = 'CR'  and date(head.dateid) between '$start' and '$end' $tenant group by cl.clientname,head.docno,head.doc,head.dateid,loc.name)  as cr
union all
select dateid, doc,clientname,loc,docno,rent,cusa,aircon,water,elec,reimb,others,amtt  from (
select date(head.dateid) as dateid,head.doc,head.docno,cl.clientname,ifnull(loc.name,'') as loc,
sum(detail.rent) as rent ,sum(detail.cusa) as cusa ,sum(detail.aircon) as aircon ,sum(detail.water) as water,sum(detail.elec) as elec,
sum(detail.reimb) as reimb,sum(detail.penalty) as others,
sum(detail.rent + detail.cusa + detail.aircon + detail.water + detail.elec + detail.penalty +detail.reimb)
as amtt
 from lahead as head
left join (select trno, detail.client, al.acnoid,
(case when al.alias = 'AR1' then (db-cr) else 0 end)  as rent,
(case when al.alias = 'AR2' then (db-cr) else 0 end) as cusa,
(case when al.alias = 'AR3' then (db-cr) else 0 end) as aircon,
(case when al.alias = 'AR4' then (db-cr) else 0 end) as water,
(case when al.alias = 'AR5' then (db-cr) else 0 end) as elec,
(case when al.alias = 'ARR1' then (db-cr) else 0 end) as reimb,
(case when al.alias not in ('AR1','AR2','AR3','AR4','AR5','ARR1') then (db - cr) else 0 end) as penalty
from ladetail as detail
left join coa as al on al.acnoid = detail.acnoid
where left(al.alias,2) = 'AR') as detail on detail.trno = head.trno
left join client as cl on cl.client = detail.client
left join loc as loc on loc.line = cl.locid
where head.doc = 'CR' and date(head.dateid) between '$start' and '$end' $tenant group by cl.clientname,head.docno,head.doc,head.dateid,loc.name) as cr order by dateid";
                        break;
                }
                break;
        }




        return $this->coreFunctions->opentable($query);
    }
    private function displayHeader_summary($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];
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
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tin),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        #date text 
        $str .= $this->reporter->col(date('m-d-Y', strtotime($start)) . ' to ' . date('m-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Status: ' . $posttype, null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $date = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '4px', '');
        $str .= $this->reporter->col('Run Date: ' . date("m-d-Y H:i:s A", strtotime($date)), '740', null, false, $border, '', 'L', $font, '10', '');
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
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('OR/PR #', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Tenant', '260', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Location', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Amount', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        return $str;
    }
    private function displayHeader_detail($config)
    {
        $posttype = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $center     = $config['params']['center'];
        $start   = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $str = '';
        $layoutsize = '1200';

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
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tin),  null, null, false, '1px solid ', '', 'C', 'Century Gothic', '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        #date text 

        $str .= $this->reporter->col(date('m-d-Y', strtotime($start)) . ' to ' . date('m-d-Y', strtotime($end)), null, null, false, $border, '', 'C', $font, '10', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Status: ' . $posttype, null, null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $date = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->col('Run Date: ' . date("m-d-Y H:i:s A", strtotime($date)), '1140', null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->col($this->reporter->pagenumber('Page'), null, null, false, $border, '', 'L', $font, '10', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->col('', '', null, false, '2px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
        $str .= $this->reporter->col('', '', null, false, '1px solid', 'B', '', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('OR/PR #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Tenant', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Location', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Rent', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('CUSA', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('A/C', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Electricity', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Water', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Reimbursable', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Others', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '4', null, false, $border, '', '', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Amount', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function reportDefaultLayout_summary($config)
    {
        $result     = $this->reportDefault($config);
        $reporttype = $config['params']['dataparams']['reporttype'];
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '800';

        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $totalamount = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_summary($config);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('m-d-Y', strtotime($data->dateid)), '100', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->clientname, '260', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->loc, '120', null, false, $border, '', 'L', $font, $fontsize, '');
            $amountt = abs($data->amount);
            $str .= $this->reporter->col(number_format($amountt, 2), '120', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->endrow();
            $totalamount += $amountt;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_summary($config);
                $page = $page + $count;
            }
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->printline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' . number_format($totalamount, 2), null, null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
    public function reportDefaultLayout_detail($config)
    {
        $result     = $this->reportDefault($config);
        $reporttype = $config['params']['dataparams']['reporttype'];
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;
        $str = '';

        $layoutsize = '1200';
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $totalamount = 0;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_detail($config);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date('m-d-Y', strtotime($data->dateid)), '80', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data->loc, '180', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->rent, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->cusa, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->aircon, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->elec, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->water, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->reimb, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col(number_format($data->others, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->col('', '4', null, false, $border, '', 'L', $font, $fontsize, '');
            $amount = abs($data->amtt);
            $str .= $this->reporter->col(number_format($amount, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '');
            $str .= $this->reporter->endrow();
            $totalamount += $amount;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader_detail($config);
                $page = $page + $count;
            }
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->printline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ' . number_format($totalamount, 2), null, null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}
