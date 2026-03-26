<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class pending_sales_order_monitoring
{
    public $modulename = 'Pending Sales Order Monitoring';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '' as client,
          '' as clientname,
          '' as dclientname,'' as agent,'' as agentid,'' as agentname,'' as dagentname
                ");
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

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $date = $this->othersClass->getCurrentTimeStamp();
        $client     = $config['params']['dataparams']['client'];
        $agent       = $config['params']['dataparams']['agent'];

        $filter = '';
        if ($client != "") {
            $filter = $filter . " and head.client='$client'";
        }

        if ($agent != "") {
            $filter .= " and head.agent='$agent'";
        }

        $query = "select head.trno,head.docno,head.terms, date(num.postdate) as postdate,rem.prref,
                  concat(rem.createdate,' ',rem.rem) as rem,terms.days,
                        TIMESTAMPDIFF(day, num.postdate,'" . $date . "' ) AS elapsed,
                        head.clientname,ag.clientname as agentname, sum((stock.iss-stock.qa) * stock.amt) as amt,head.address,head.shipto
                from hsohead as head
                left join transnum as num on num.trno=head.trno
                left join hsostock as stock on stock.trno=head.trno
                left join terms on terms.terms=head.terms
                left join client as ag on ag.client=head.agent
                left join headprrem as rem on rem.trno=head.trno
                where date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                    and stock.iss>stock.qa and stock.void=0 and num.postdate is not null
                    group by head.trno,head.docno,head.terms, num.postdate,rem.rem,terms.days,
                    head.dateid,head.clientname,ag.clientname,rem.createdate,rem.prref,head.address,head.shipto
                order by head.docno,terms.days, TIMESTAMPDIFF(day, num.postdate,'" . $date . "' ) desc, rem.createdate desc";

        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $company   = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('qty', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $docno = "";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        $ctr = 0;
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total += $data->amt;
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->clientname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->address, '145', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->col($data->shipto, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->agentname, '95', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->postdate, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->elapsed, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col(number_format($data->amt, $decimal), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->prref, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $ctr += 1;
                } else {
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '145', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->col('', '95', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                    $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->prref, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->rem, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            }
        }

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total Records: ' . $ctr, '880', null, false, $border, '', 'LT', $font, 12, '', '', '');
        $str .= $this->reporter->col(number_format($total, $decimal), '70', null, false, $border, '', 'RT', $font, 12, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'RT', $font, 12, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $agent     = $config['params']['dataparams']['agentname'];
        $client     = $config['params']['dataparams']['clientname'];

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if ($client == '') {
            $client = 'ALL';
        }
        if ($agent == '') {
            $agent = 'ALL';
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Pending Sales Order Monitoring', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '1200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : ' . $client, '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agent : ' . $agent, '600', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $company   = $config['params']['companyid'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '650', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL PENDING', '160', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCNO', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('ADDRESS', '145', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SHIPPING ADDRESS', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('AGENTNAME', '95', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('POST DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ELAPSED', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TYPE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REMARKS', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        return $str;
    }
}//end class