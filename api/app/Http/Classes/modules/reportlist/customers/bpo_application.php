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
use DateTime;


class bpo_application
{
    public $modulename = 'Bonus Purchase Order Application Report';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'area', 'dagentname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');
        data_set($col1, 'dagentname.label', 'Salesman');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1,  'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
            'default' as print,
            adddate(left(now(),10),-30) as start,
            left(now(),10) as end,
            '' as client,
            '' as clientname,
            '' as dclientname,
            '' as agentid,
            '' as agentname,
            '' as dagentname,
            '' as agent,'' as area ";

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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $agent    = $config['params']['dataparams']['agent'];
        $client    = $config['params']['dataparams']['client'];
        $area = $config['params']['dataparams']['area'];
        $filter = "";
        if ($agent != "") {
            $filter .= " and agent.client='$agent'";
        }

        if ($client != "") {
            $filter .= " and cl.client='$client'";
        }

        if ($area != "") {
            $filter .= " and cl.area='" . $area . "'";
        }
        $query = " select dateid,docno,bpo,clientname,carton
                    from (
                    select date(head.dateid) as dateid,head.docno,head.bpo, cl.clientname, if(head.ctnsno='','',head.ctnsno) as carton
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter

                    union all

                    select date(head.dateid) as dateid,head.docno,head.bpo,cl.clientname, if(head.ctnsno='','',head.ctnsno) as carton
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter ) as s
                    group by dateid,docno,bpo,clientname,carton order by dateid,docno";
        // var_dump($query);
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '12';

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1000';

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Bonus Purchase Order Application Report', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('m/d/Y');

        $enddate = $end;
        $endd = new DateTime($enddate);
        $end = $endd->format('m/d/Y');

        $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '120', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('SI #', '150', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '10', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('CUSTOMER', '420', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('B.P.O', '150', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('TOTAL CTNS', '140', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '10', '', '', $border, 'TBR', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $result = $this->reportDefault($config);
        $count = 40;
        $page = 34;
        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);

        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $carton = (int) ($data->carton != '' ? preg_replace('/\D+/', '', $data->carton) : 0);
            $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'L', 'CT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->docno, '150', null, false, $border, 'L', 'CT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'RT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->clientname, '420', null, false, $border, '', 'LT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->bpo, '150', null, false, $border, 'L', 'CT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($carton, '140', null, false, $border, 'L', 'RT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'RT', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '420', null, false, $border, 'T', 'L', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'R', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'R', $font, $font_size, '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class