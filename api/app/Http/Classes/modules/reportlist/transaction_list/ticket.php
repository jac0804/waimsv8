<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class ticket
{
    public $modulename = 'Ticket';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];



    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];


        $fields = ['radioprint', 'start', 'end', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.label', 'Ticket Status');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'All', 'value' => 'all', 'color' => 'teal'],
                ['label' => 'Draft', 'value' => 'draft', 'color' => 'teal'],
                ['label' => 'Open', 'value' => 'open', 'color' => 'teal'],
                ['label' => 'In-Progress', 'value' => 'inprogress', 'color' => 'teal'],
                ['label' => 'Resolved', 'value' => 'resolved', 'color' => 'teal'],
                ['label' => 'Close', 'value' => 'posted', 'color' => 'teal']
            ]
        );

        data_set(
            $col1,
            'radioprint.options',
            [
                ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ]
        );
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable(
            "select
             'default' as print,
              adddate(left(now(),10),-360) as start,
              left(now(),10) as `end`,
              'draft' as reporttype"
        );
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        return $this->reportDefaultLayout_ticket($config);
    }

    public function report_default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $reporttype = $config['params']['dataparams']['reporttype'];
        $filter = "";
        $status = '';

        $status = "ifnull(stat.status,'Draft')";

        switch ($reporttype) {
            case 'draft':
                $filter = ' and num.postdate is null and num.statid=0';
                break;
            case 'open':
                $filter = " and num.postdate is null and num.statid=92";
                break;
            case 'inprogress':
                $filter = " and num.postdate is null and num.statid=93";
                break;
            case 'resolved':
                $filter = " and num.postdate is null and num.statid=94";
                break;
            case 'posted':
                $status = "'Close'";
                $filter = ' and num.postdate is not null';
                break;
        }
        switch ($reporttype) {
            case 'open':
            case 'draft':
            case 'inprogress':
            case 'resolved':
                $query = "select comm.comment,
         num.center,
         head.trno,
         head.docno,
         client.client,
          client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         client.clientname,
         date_format(comm.createdate,'%Y-%m-%d') as createdate,
         ifnull(comm.createby,'') as createby,
         head.rem, ifnull(head.clienttype,'') as clienttype,
         ifnull(req1.category,'') as ordertype , head.orderid, ifnull(client.tel,'') as tel, ifnull(client.email,'') as email,
         ifnull(req2.category,'') as channel , head.channelid,emp.clientname as empname, head.empid,
          ''  as dbranchname,ifnull(branch.client,'') as branchcode,ifnull(branch.clientid,'') as branch,
         ifnull(branch.clientname,'') as branchname, head.branchid, head.compid,client.registername as company, $status as status
         from csstickethead as head
         left join transnum as num on num.trno = head.trno
         left join trxstatus as stat on stat.line=num.statid
         left join client on client.client = head.client
         left join client as emp on head.empid = emp.clientid
         left join client as branch on branch.clientid = head.branchid
         left join reqcategory as req1 on req1.line=head.orderid
         left join reqcategory as req2 on req2.line=head.channelid
         left join csscomment as comm on comm.trno = head.trno
          where date(head.dateid) between '$start' and '$end' $filter";
                break;
            case 'posted':
                $query = "  select comm.comment,
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         client.clientname,
         date_format(comm.createdate,'%Y-%m-%d') as createdate,
          ifnull(comm.createby,'') as createby,
         head.rem, ifnull(head.clienttype,'') as clienttype,
         ifnull(req1.category,'') as ordertype , head.orderid, ifnull(client.tel,'') as tel, ifnull(client.email,'') as email,
         ifnull(req2.category,'') as channel , head.channelid,emp.clientname as empname, head.empid,
          ''  as dbranchname,ifnull(branch.client,'') as branchcode,ifnull(branch.clientid,'') as branch,
         ifnull(branch.clientname,'') as branchname, head.branchid, head.compid,client.registername as company, $status as status
         from hcsstickethead as head
         left join transnum as num on num.trno = head.trno
         left join trxstatus as stat on stat.line=num.statid
         left join client on client.clientid = head.clientid
         left join client as emp on head.empid = emp.clientid
         left join client as branch on branch.clientid = head.branchid
         left join reqcategory as req1 on req1.line=head.orderid
         left join reqcategory as req2 on req2.line=head.channelid
         left join csscomment as comm on comm.trno = head.trno
           where date(head.dateid) between '$start' and '$end' $filter";
                break;
            case 'all':
                $query = "select comm.comment,
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         client.clientname,
         date_format(comm.createdate,'%Y-%m-%d') as createdate,
        ifnull(comm.createby,'') as createby,
         head.rem, ifnull(head.clienttype,'') as clienttype,
         ifnull(req1.category,'') as ordertype , head.orderid, ifnull(client.tel,'') as tel, ifnull(client.email,'') as email,
         ifnull(req2.category,'') as channel , head.channelid,emp.clientname as empname, head.empid,
          ''  as dbranchname,ifnull(branch.client,'') as branchcode,ifnull(branch.clientid,'') as branch,
         ifnull(branch.clientname,'') as branchname, head.branchid, head.compid,client.registername as company, $status as status
         from csstickethead as head
         left join transnum as num on num.trno = head.trno
         left join trxstatus as stat on stat.line=num.statid
         left join client on client.client = head.client
         left join client as emp on head.empid = emp.clientid
         left join client as branch on branch.clientid = head.branchid
         left join reqcategory as req1 on req1.line=head.orderid
         left join reqcategory as req2 on req2.line=head.channelid
         left join csscomment as comm on comm.trno = head.trno
          where date(head.dateid) between '$start' and '$end' $filter
          
          union all 
         select comm.comment,
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         client.clientname,
         date_format(comm.createdate,'%Y-%m-%d') as createdate,
         ifnull(comm.createby,'') as createby,
         head.rem, ifnull(head.clienttype,'') as clienttype,
         ifnull(req1.category,'') as ordertype , head.orderid, ifnull(client.tel,'') as tel, ifnull(client.email,'') as email,
         ifnull(req2.category,'') as channel , head.channelid,emp.clientname as empname, head.empid,
          ''  as dbranchname,ifnull(branch.client,'') as branchcode,ifnull(branch.clientid,'') as branch,
         ifnull(branch.clientname,'') as branchname, head.branchid, head.compid,client.registername as company,'Close' as status
         from hcsstickethead as head
         left join transnum as num on num.trno = head.trno
         left join trxstatus as stat on stat.line=num.statid
         left join client on client.clientid = head.clientid
         left join client as emp on head.empid = emp.clientid
         left join client as branch on branch.clientid = head.branchid
         left join reqcategory as req1 on req1.line=head.orderid
         left join reqcategory as req2 on req2.line=head.channelid
         left join csscomment as comm on comm.trno = head.trno
           where date(head.dateid) between '$start' and '$end' $filter";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }
    public function reportDefaultLayout_ticket($config)
    {

        $data = $this->report_default_query($config);

        $companyid = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $i = 0;
        $docno = "";
        $total = 0;
        foreach ($data as $key => $value) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$key]->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->status, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->clienttype, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->company, '100', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data[$key]->comment, '100', null, false, $border, '', 'L', $font, $fontsize, '');
            $str .= $this->reporter->col($data[$key]->createdate, '100', null, false, $border, '', 'C', $font, $fontsize, '');
            $str .= $this->reporter->col($data[$key]->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $companyid  = $config['params']['companyid'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];


        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '',  $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('User: ' . $username, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . strtoupper($reporttype == 'posted' ? 'Close' : $reporttype), null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $companyid = $config['params']['companyid'];

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CLIENT TYPE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COMPANY', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COMMENT', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREATEDATE ', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREATED BY ', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class	