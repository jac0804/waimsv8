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


class customer_ledger
{
    public $modulename = 'Customer Ledger';
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
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        // client
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
        data_set($col2, 'dclientname.required', true);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);
        $fields = [];
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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function default_query($filters)
    {
        $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
        $client = $filters['params']['dataparams']['dclientname'];
        $clientid = $filters['params']['dataparams']['clientid'];
        $center = $filters['params']['dataparams']['center'];
        $filter = "";

        if ($center != '') {
            $filter .= " and num.center='" . $center . "' ";
        }
        if ($client != '') {
            $filter .= " and cl.clientid= $clientid  ";
        }

        $query = "        
        select head.trno,head.doc,head.docno,head.clientname,
        format(head.amount,2) as amount,head.yourref,
        date(head.dateid) as dateid
        from cehead as head 
        left join transnum as num on num.trno=head.trno
        left join client as cl on cl.clientid = head.clientid
        where head.doc = 'CE' and date(head.dateid) between '$start' and '$end' $filter

		  union all
		  
		select head.trno,head.doc,head.docno,head.clientname,
        format(head.amount,2) as amount,head.yourref,
        date(head.dateid) as dateid
        from hcehead as head 
        left join transnum as num on num.trno=head.trno
        left join client as cl on cl.clientid = head.clientid
        where head.doc = 'CE' and date(head.dateid) between '$start' and '$end' $filter
        order by dateid asc";

        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function reportplotting($config)
    {

        $result = $this->default_query($config);

        $reportdata =  $this->custumer_ledger($config, $result);

        return $reportdata;
    }

    public function custumer_ledger($config, $result)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = 10;
        $border = "1px solid ";
        $layoutsize = 1000;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_default($config);
        $str .= $this->tableheader($config, $result);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . date('d-M-y', strtotime($data->dateid)), 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->amount, 100, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_default($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client = $config['params']['dataparams']['dclientname'];
        $dcenter = $config['params']['dataparams']['dcentername'];
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
        $str .= $this->reporter->begintable(800);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, 300, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Branch : ' . $dcenter, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($config, $data)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = 10;
        $border = "1px solid ";
        $layoutsize = 1000;

        $str .= $this->reporter->begintable(270);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Name: ', 120, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('' . $data[0]->clientname, 150, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', 100, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CR#', 100, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', 100, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class