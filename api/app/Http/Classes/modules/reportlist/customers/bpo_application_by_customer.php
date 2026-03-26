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


class bpo_application_by_customer
{
    public $modulename = 'B.P.O. Application by Customer';
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
        $query = " select dateid,docno,bpo,clientname,carton,sum(amount) as amount
                    from (
                    select date(head.dateid) as dateid,head.docno,head.bpo, cl.clientname, if(head.ctnsno='','',head.ctnsno) as carton,sum(stock.ext) as amount
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join client as cl on cl.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter
                    group by date(head.dateid) ,head.docno,head.bpo, cl.clientname,head.ctnsno

                    union all

                    select date(head.dateid) as dateid,head.docno,head.bpo,cl.clientname, if(head.ctnsno='','',head.ctnsno) as carton,sum(stock.ext) as amount
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join client as cl on cl.client=head.client
                    left join client as agent on agent.client=head.agent
                    where head.doc='sj' and date(head.dateid) between '$start' and '$end'   $filter
                    group by date(head.dateid) ,head.docno,head.bpo, cl.clientname,head.ctnsno ) as s
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
        $layoutsize = '800';


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
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('B.P.O. Application by Customer', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
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

        $str .= $this->reporter->col('From ' . $start . ' to ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }


    private function default_table_cols($config, $client = '')
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
        $clientname = $config['params']['dataparams']['clientname'];

        $str = '';
        $layoutsize = '800';
        $customerName = '';
        if ($client != '') {
            $customerName = strtoupper($client);
        } elseif (!empty($clientname)) {
            $customerName = strtoupper($clientname);
        }


        $str .= '<br>';

        // $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer :', '100', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col($customerName, '700', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BPO', '190', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('DATE', '120', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('SI #', '170', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('INVOICE AMOUNT', '170', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('TOTAL CTNS', '150', '', '', $border, 'LTBR', 'C', $font, $font_size, 'B', '', '5px');

        $str .= $this->reporter->endrow();
        return $str;
    }

    public function reportDefaultLayouts($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $result = $this->reportDefault($config);
        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '800';
        $customerFilter = '';
        $customerGroup = '';
        $count = 30;
        $page = 2;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        foreach ($result as $key => $data) {
            if ($customerFilter == '' && ($customerGroup == '' || $customerGroup != $data->clientname)) {
                // if not the first group, close the current table before starting a new one
                if ($customerGroup != '') {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '190', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '120', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '170', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '140', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
                    $str .= $this->reporter->endrow();
                    //naka comment para pag ginawa na per page per customer 
                    // $str .= $this->reporter->endtable();
                    // $str .= $this->reporter->page_break(); 
                }

                // update the current client name sa header
                $customerGroup = $data->clientname;

                // print a new header for this client and start a new table
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->default_table_col($config, $customerGroup);
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $carton = (int) ($data->carton != '' ? preg_replace('/\D+/', '', $data->carton) : 0);
            $str .= $this->reporter->col($data->bpo, '190', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->docno, '170', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '160', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', '', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($carton, '140', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $count) {
                // $str .= $this->reporter->endtable();
                // $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '190', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '120', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '170', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '140', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                // $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();

                // $str .= $this->reporter->page_break();

                // $this->reporter->linecounter = 0;
                // // $res = $result[$key];
                // // var_dump($res);

                // $str .= $this->default_displayHeader($config);
                // $str .= $this->default_table_col($config, $customerGroup);
                // $str .= $this->reporter->begintable($layoutsize);
                // // $page = $page + $count;

                if ($key + 1 < count($result)) {
                    // tapusin ang kasalukuyang table at mag‑page break
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '190', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '120', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '170', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '140', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '2px', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->page_break();
                    $this->reporter->linecounter = 0;

                    // print ulit ang report header
                    $str .= $this->default_displayHeader($config);

                    // alamin ang next clientname
                    $nextGroup = $result[$key + 1]->clientname;
                    // var_dump($nextGroup);

                    // kung pareho pa rin ang group, ulitin ang column header at magsimulang muli ng table
                    if ($nextGroup == $customerGroup) {
                        $str .= $this->default_table_col($config, $customerGroup);
                        $str .= $this->reporter->begintable($layoutsize);
                    }
                }
            }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '800', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }






    private function default_table_col($config, $client = '')
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
        $clientname = $config['params']['dataparams']['clientname'];

        $str = '';
        $layoutsize = '800';
        $customerName = '';
        // if ($client != '') {
        //     $customerName = strtoupper($client);
        // } elseif (!empty($clientname)) {
        // $customerName = strtoupper($clientname);
        // }


        $str .= '<br>';

        // $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer :', '100', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col($customerName, '700', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BPO', '190', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('DATE', '120', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('SI #', '170', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('INVOICE AMOUNT', '160', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '10', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('TOTAL CTNS', '150', '', '', $border, 'LTB', 'C', $font, $font_size, 'B', '', '5px');
        $str .= $this->reporter->col('', '10', '', '', $border, 'TBR', 'C', $font, $font_size, 'B', '', '5px');

        $str .= $this->reporter->endrow();
        return $str;
    }



    public function reportDefaultLayout($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $font_size2 = '12';
        $result = $this->reportDefault($config);
        $this->reporter->linecounter = 0;
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '800';
        $customerFilter = '';
        $customerGroup = '';
        $count = 25;
        $page = 30;
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        foreach ($result as $key => $data) {
            if ($customerFilter == '' && ($customerGroup == '' || $customerGroup != $data->clientname)) {
                // if not the first group, close the current table before starting a new one
                if ($customerGroup != '') {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '190', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '120', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '170', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '140', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                    $str .= $this->reporter->endrow();
                    // naka comment para pag ginawa na per page per customer 
                    // $str .= $this->reporter->endtable();
                    // $str .= $this->reporter->page_break(); 

                }
                // $this->reporter->linecounter = 0;
                // update the current client name sa header
                $customerGroup = $data->clientname;

                // print a new header for this client and start a new table
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Customer :', '100', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
                $str .= $this->reporter->col($customerGroup, '700', '', '', $border, '', 'L', $font, $font_size, 'B', '', '5px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('BPO', '190', '', '', $border, 'LTB', 'C', $font, $font_size2, 'B', '', '5px');
                $str .= $this->reporter->col('DATE', '120', '', '', $border, 'LTB', 'C', $font, $font_size2, 'B', '', '5px');
                $str .= $this->reporter->col('SI #', '170', '', '', $border, 'LTB', 'C', $font, $font_size2, 'B', '', '5px');
                $str .= $this->reporter->col('INVOICE AMOUNT', '160', '', '', $border, 'LTB', 'C', $font, $font_size2, 'B', '', '5px');
                $str .= $this->reporter->col('', '10', null, false, $border, 'TB', '', $font, $font_size2, '', '', '5px', '');
                $str .= $this->reporter->col('TOTAL CTNS', '140', '', '', $border, 'LTB', 'C', $font, $font_size2, 'B', '', '5px');
                $str .= $this->reporter->col('', '10', null, false, $border, 'TBR', '', $font, $font_size2, '', '', '5px', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $carton = (int) ($data->carton != '' ? preg_replace('/\D+/', '', $data->carton) : 0);
            $str .= $this->reporter->col($data->bpo, '190', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($data->docno, '170', null, false, $border, 'L', 'C', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '160', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', '', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col($carton, '140', null, false, $border, 'L', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '2px', '');
            $str .= $this->reporter->endrow();

            $this->reporter->linecounter++;

            if ($this->reporter->linecounter >= $count) {
                // close table 
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '190', '', '', $border, 'T', '', $font, '4', '', '', '');
                $str .= $this->reporter->col('', '120', '', '', $border, 'T', '', $font, '4', '', '', '');
                $str .= $this->reporter->col('', '170', '', '', $border, 'T', '', $font, '4', '', '', '');
                $str .= $this->reporter->col('', '160', '', '', $border, 'T', '', $font, '4', '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                $str .= $this->reporter->col('', '140', '', '', $border, 'T', '', $font, '4', '', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, '4', '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // add page break
                $str .= $this->reporter->page_break();

                $this->reporter->linecounter = 0;
                // reprint header for the new page
                $str .= $this->default_displayHeader($config);
            }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '800', null, false, $border, 'T', '', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class