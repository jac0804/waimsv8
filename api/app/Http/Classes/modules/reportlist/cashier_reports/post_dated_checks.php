<?php

namespace App\Http\Classes\modules\reportlist\cashier_reports;

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


class post_dated_checks
{
    public $modulename = 'Post Dated Checks';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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

        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['start', 'end', 'dclientname', 'dcentername'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'start.label', 'StartDate');
        data_set($col2, 'start.readonly', false);
        data_set($col2, 'end.label', 'EndDate');
        data_set($col2, 'due.readonly', false);
        data_set($col2, 'dclientname.lookupclass', 'lookupclient');
        data_set($col2, 'dclientname.label', 'Customer');

        $fields = ['radioreporttype', 'radioposttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set(
            $col3,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );
        data_set($col3, 'radioreporttype.label', 'Date Range Options:');
        data_set($col3, 'radioreporttype.options', [
            ['label' => 'Collection Date', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Check Date', 'value' => '1', 'color' => 'orange']
        ]);

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as center,
        '' as centername,
        '' as dcentername,
        '' as dclientname,
        '0' as clientid,
        '' as client,
        '' as clientname,
        '0' as posttype,
        '0' as reporttype
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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function default_query($filters)
    {
        $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
        $isposted = $filters['params']['dataparams']['posttype'];
        $client = $filters['params']['dataparams']['dclientname'];
        $clientid = $filters['params']['dataparams']['clientid'];
        $center = $filters['params']['dataparams']['center'];
        $reporttype  = $filters['params']['dataparams']['reporttype'];
        $filter = "";

        if ($center != '') {
            $filter .= " and transnum.center='" . $center . "' ";
        }


        if ($client != '') {
            $filter .= " and client.clientid= $clientid  ";
        }

        if ($reporttype == 0) {
            $filter .= " and date(head.dateid) between '$start' and '$end' ";
        } else {
            $filter .= " and date(detail.checkdate) between '$start' and '$end' ";
        }

        switch ($isposted) {
            case 0: // posted
                $query = "select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid, 
                                (select date(dateid) from dxhead as dx where trno=cenum.dstrno
                                union all
                                select date(dateid) from hdxhead as dx where trno=cenum.dstrno) as depdate
                        from hrchead as head
                        left join hrcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        left join hcehead as ce on ce.rctrno=detail.trno and ce.rcline=detail.line
                        left join transnum as cenum on cenum.trno=ce.trno
                        where 1=1 $filter order by clientname,checkdate";
                break;
            case 1: // unposted 
                $query = "select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid,
                                (select date(dateid) from dxhead as dx where trno=cenum.dstrno
                                union all
                                select date(dateid) from hdxhead as dx where trno=cenum.dstrno) as depdate
                        from rchead as head
                        left join rcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        left join hcehead as ce on ce.rctrno=detail.trno and ce.rcline=detail.line
                        left join transnum as cenum on cenum.trno=ce.trno
                        where 1=1 $filter order by clientname,checkdate";
                break;
            case 2: //all
                $query = "select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid,
                                (select date(dateid) from dxhead as dx where trno=cenum.dstrno
                                union all
                                select date(dateid) from hdxhead as dx where trno=cenum.dstrno) as depdate
                        from rchead as head
                        left join rcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        left join hcehead as ce on ce.rctrno=detail.trno and ce.rcline=detail.line
                        left join transnum as cenum on cenum.trno=ce.trno
                        where 1=1 $filter 
                        union all
                        select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid,
                                (select date(dateid) from dxhead as dx where trno=cenum.dstrno
                                union all
                                select date(dateid) from hdxhead as dx where trno=cenum.dstrno) as depdate
                        from hrchead as head
                        left join hrcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        left join hcehead as ce on ce.rctrno=detail.trno and ce.rcline=detail.line
                        left join transnum as cenum on cenum.trno=ce.trno
                        where 1=1 $filter 
                        order by clientname,checkdate";
                break;
        } // end switch
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function reportplotting($config)
    {
        $companyid = $config['params']['companyid'];
        $result = $this->default_query($config);

        $reportdata =  $this->POST_DATED_CHECKS($config, $result);

        return $reportdata;
    }

    public function POST_DATED_CHECKS($config, $result)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = 10;
        $border = "1px solid ";
        $posttype  = $config['params']['dataparams']['posttype'];
        $layoutsize = 1000;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, 150, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkdate, 150, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkno, 150, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, 280, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), 120, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->depdate, 150, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $posttype  = $config['params']['dataparams']['posttype'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client = $config['params']['dataparams']['dclientname'];
        $dcenter = $config['params']['dataparams']['dcentername'];
        // $center = $config['params']['dataparams']['dcentername'];
        $str = '';
        $layoutsize = 1000;

        if ($client == '') {
            $client = 'ALL';
        }

        if ($dcenter == '') {
            $dcenter = 'ALL';
        }

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

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Post Dated Checks', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Branch : ' . $dcenter, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : ' . $client, 700, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = 10;
        $border = "1px solid ";
        $posttype  = $config['params']['dataparams']['posttype'];
        $layoutsize = 1000;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Collection Date', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check Date', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check #', 150, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Name', 280, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', 120, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Deposit Date', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class