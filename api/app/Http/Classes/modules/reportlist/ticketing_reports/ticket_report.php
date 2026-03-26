<?php

namespace App\Http\Classes\modules\reportlist\ticketing_reports;

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

class ticket_report
{
    public $modulename = 'Ticket Report';
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

        $fields = ['radioprint', 'start', 'end', 'ordertype', 'channel', 'clienttype', 'dbranchname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,'' as ordertype,'' as channel,'' as clienttype,
        '' as dbranchname, '' as branchcode, '' as branchname ";

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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }


    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $filter = "";

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $ordertype = $config['params']['dataparams']['ordertype'];
        $channel  = $config['params']['dataparams']['channel'];
        $clienttype  = $config['params']['dataparams']['clienttype'];
        $dbranchname  = $config['params']['dataparams']['dbranchname'];

        if ($ordertype != '') {
            $filter .= " and req1.category = '$ordertype'";
        }

        if ($channel != '') {
            $filter .= " and req2.category = '$channel'";
        }

        if ($clienttype != '') {
            $filter .= " and head.clienttype = '$clienttype'";
        }

        if ($dbranchname != '') {
            $bcode  = $config['params']['dataparams']['branchcode'];
            $filter .= " and branch.client = '$bcode'";
        }


        $query = "select head.trno,head.docno,client.client,client.clientname,client.tel,date(head.dateid) as dateid,
                    client.email,req1.category as ordertype,head.clienttype,req2.category as channel,emp.clientname as recipient,
                    branch.client as branchcode,branch.clientname as branchname,client.registername,head.rem
                from csstickethead as head
                left join transnum as num on num.trno = head.trno
                left join client on client.client = head.client
                left join client as emp on head.empid = emp.clientid
                left join client as branch on branch.clientid = head.branchid
                left join reqcategory as req1 on req1.line=head.orderid
                left join reqcategory as req2 on req2.line=head.channelid
                where head.doc='CA' and date(head.dateid) between '$start' and '$end' $filter
                union all
                select head.trno,head.docno,client.client,client.clientname,client.tel,date(head.dateid) as dateid,
                    client.email,req1.category as ordertype,head.clienttype,req2.category as channel,emp.clientname as recipient,
                    branch.client as branchcode,branch.clientname as branchname,client.registername,head.rem
                from hcsstickethead as head
                left join transnum as num on num.trno = head.trno
                left join client on client.clientid = head.clientid
                left join client as emp on head.empid = emp.clientid
                left join client as branch on branch.clientid = head.branchid
                left join reqcategory as req1 on req1.line=head.orderid
                left join reqcategory as req2 on req2.line=head.channelid
                where head.doc='CA' and date(head.dateid) between '$start' and '$end' $filter
                order by docno";
        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 71;
        $page = 70;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 1700;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 12;
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->header_table($config, $layoutsize);

        $i = 1;
        $total = 0;
        $ctr = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->email, '150', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->ordertype, '150', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->clienttype, '120', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->channel, '150', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->recipient, '110', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->branchname, '120', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->registername, '120', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->rem, '200', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');


                $str .= $this->reporter->endrow($layoutsize);

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config, $layoutsize);
                    $str .= $this->header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if

                $ctr = $i++;
            }
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_table($config, $layoutsize)
    {
        $str = "";
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 12;
        $border = "1px solid";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Customer Code', '100', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Customer Name', '170', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Contact Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Date', '90', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Email', '150', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Order Type', '150', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Client Type', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Channel', '150', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Recipient', '110', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Branch Name', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Company Name', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Description', '200', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $ordertype = $config['params']['dataparams']['ordertype'];
        $channel  = $config['params']['dataparams']['channel'];
        $clienttype  = $config['params']['dataparams']['clienttype'];
        $dbranchname  = $config['params']['dataparams']['dbranchname'];


        ////

        if ($ordertype == "") {
            $ordertype = 'ALL';
        }
        if ($channel == "") {
            $channel = 'ALL';
        }
        if ($clienttype == "") {
            $clienttype = 'ALL';
        }
        if ($dbranchname == "") {
            $dbranchname = 'ALL';
        }

        //////

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize9 = 9;
        $fontsize14 = 14;
        $fontsize16 = 16;
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, 12, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ticket Report', null, null, false, $border, '', 'C', $font, $fontsize16, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($start . ' - ' . $end, null, null, false, $border, '', 'C', $font, 14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ORDER TYPE : ' . $ordertype, 850, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('CHANNEL : ' . $channel, 850, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CLIENT TYPE : ' . $clienttype, 850, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('BRANCH NAME : ' . $dbranchname, 850, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        return $str;
    }
}//end class