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

class aging_of_over_due_accounts_receivable
{
    public $modulename = 'Aging of Over Due Accounts Receivable';
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
        $fields = ['radioprint', 'start',  'dagentname', 'area'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.label', 'As of');
        data_set($col1, 'dagentname.label', 'Salesman');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
        'default' as print,
        date(now()) as start,
        '' as dagentname,
        '' as agent,
        '' as agentname,
        '' as agentid,
        '' as area ";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportDefault($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config, $result)
    {
        $result = $this->roosevelt_LAYOUT($config, $result);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->roosevelt_QUERY($config);
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $this->reportplotting($config, $result);
    }




    public function roosevelt_QUERY($config)
    {
        // $filtercenter = $config['params']['dataparams']['center'];
        // $client       = $config['params']['dataparams']['client'];
        $asof   = $config['params']['dataparams']['start'];
        $agent    = $config['params']['dataparams']['agent'];
        $area = $config['params']['dataparams']['area'];

        $filter = "";

        if ($agent != "") {
            $filter .= " and agent.client='$agent'";
        }

        if ($area != "") {
            $filter .= " and client.area='" . $area . "'";
        }

        // if ($filtercenter != "") {
        //   $filter .= " and cntnum.center='$filtercenter'";
        // }


        $qry = "
          select  clientname,elapse,sum(balance) as balance,agentname,area from (
           select  ifnull(client.clientname,'no clientname') as clientname,
               datediff(now(), head.dateid) as elapse, sum(stock.ext) as balance,
              ifnull(agent.clientname, 'No Salesman') as agentname,
              ifnull(client.area,'No area') as area
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              left join client as agent on agent.client=head.agent
              where head.doc in ('SK', 'CM') and datediff(now(), head.dateid) >= 150 and head.dateid<='$asof' $filter 
              group by clientname,elapse,agentname,client.area
              union all
              select  ifnull(client.clientname,'no clientname') as clientname,
               datediff(now(), head.dateid) as elapse,
              sum(detail.db-detail.cr) as balance,
              ifnull(agent.clientname, 'No Salesman') as agentname,
              ifnull(client.area,'No area') as area
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join client as agent on agent.client=head.agent
              where left(coa.alias,2)='AR' and datediff(now(), head.dateid) >= 150 and head.dateid<='$asof' $filter
              group by clientname,elapse,agentname,client.area
              union all
              select ifnull(client.clientname,'no clientname') as clientname,
              datediff(now(), detail.dateid) as elapse,
              sum(case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
              ifnull(agent.clientname, 'No Salesman') as agentname,
              ifnull(client.area,'No area') as area
              from arledger as detail
              left join client on client.clientid=detail.clientid
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              left join client as agent on agent.clientid=head.agentid
              where detail.bal<>0 and client.iscustomer = 1 and datediff(now(), head.dateid) >= 150 and head.dateid<='$asof' $filter
              group by clientname,elapse,agentname,client.area
              order by clientname ) as x
              group by clientname,elapse,agentname,area
              order by agentname,area";

        Logger($qry);
        return $qry;
    }


    private function roosevelt_displayHeader($params, $data)
    {
        $str = "";
        $layoutsize = '1100';
        $border = '1px solid';
        $font = 'Calibri';
        $center     = $params['params']['center'];
        $username   = $params['params']['user'];
        $agent    = $params['params']['dataparams']['agent'];
        $area = $params['params']['dataparams']['area'];
        $center     = $params['params']['center'];
        $username   = $params['params']['user'];
        $start      = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
        // $end        = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
        $font_size = '14';


        // $str .= $this->reporter->beginreport('1000');

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font,  '10', '', '', '', 0, '', 0, 5);
        $str .=  $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name, null, null, false, '1px solid ', '', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '1px solid ', '', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, '1px solid ', '', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Aging of Over Due Accounts Receivable', null, null, false, '1px solid ', '', 'L', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('m/d/Y');
        // $enddate = $end;
        // $endd = new DateTime($enddate);
        // $end = $endd->format('m/d/Y');
        $str .= $this->reporter->col('As of ' . $start, null, null, '', $border, '', 'L', $font, '12', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);


        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font,  $font_size, '', 'b', '');
        if ($agent == '') {
            $str .= $this->reporter->col('Salesman : ALL', '400', null, false, '1px solid ', '', 'L', $font,  $font_size, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Salesman : ' . strtoupper($agent), '400', null, false, '1px solid ', '', 'L', $font,  $font_size, 'B', '', '');
        }

        if ($area == '') {
            $area = 'ALL';
        } else {
            $area = strtoupper($area);
        }

        $str .= $this->reporter->col('Area : ' . $area, '400', null, false, '1px solid ', '', 'L', $font,  $font_size, 'B', 'b', '');
        $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font,  $font_size, '', '', '');
        // $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->pagenumber('Page', '100',  null, false, '1px solid ', '', 'R', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER', '440', null, false, '1px solid ', 'TB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('150 days', '105', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('180  days', '105', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('210 days', '105', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('240 days', '105', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('240+ days', '105', null, false, '1px solid ', 'LTB', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('TOTAL AMOUNT', '140', null, false, '1px solid ', 'LTBR', 'C', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;', '5', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '440', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->col('&nbsp;', '140', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }


    private function roosevelt_LAYOUT($params, $data)
    {
        $str = "";
        $count = 40;
        // $page = 40;

        $layoutsize = '1100';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '2;margin-top:10px;margin-left:40px');
        $str .= $this->roosevelt_displayHeader($params, $data);
        $str .= $this->reporter->begintable($layoutsize);
        $border = "1px solid";
        $font = 'Calibri';
        $font_size = '14';

        $this->reporter->linecounter = 0;
        // $rowCount = 0;

        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $tota = 0;
        $totb = 0;
        $totc = 0;
        $totd = 0;
        $tote = 0;
        $gt = 0;

        //subtotal
        $subtota = 0;
        $subtotb = 0;
        $subtotc = 0;
        $subtotd = 0;
        $subtote = 0;
        $subgt = 0;


        $agent = "";

        for ($i = 0; $i < count($data); $i++) {

            $str .= $this->reporter->addline();
            if ($agent != $data[$i]['agentname']) {
                if ($agent != '') {
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('SALESMAN TOTAL', '440', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subtota == 0 ? '-' : number_format($subtota, 2)), '105', null, false, $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subtotb == 0 ? '-' : number_format($subtotb, 2)), '105', null, false, $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subtotc == 0 ? '-' : number_format($subtotc, 2)), '105', null, false, $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subtotd == 0 ? '-' : number_format($subtotd, 2)), '105', null, false, $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subtote == 0 ? '-' : number_format($subtote, 2)), '105', null, false, $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($subgt == 0 ? '-' : number_format($subgt, 2)), '140', null, false, $border, 'LTBR', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->endrow();

                    $subtota = 0;
                    $subtotb = 0;
                    $subtotc = 0;
                    $subtotd = 0;
                    $subtote = 0;
                    $subgt = 0;


                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('&nbsp;', '5', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '440', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '105', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->col('&nbsp;', '140', null, false, '1px solid ', '', 'C', $font,  '4', 'B', '', '');
                    $str .= $this->reporter->endrow();

                    // $rowCount += 2; // subtotal + spacer
                } //end if

                $str .= $this->reporter->addline(); //space sa pagitan ng header
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', $font, $font_size, 'B', '', '');

                $str .= $this->reporter->col(strtoupper($data[$i]['agentname']), '440', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

                $str .= $this->reporter->col('&nbsp', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp', '105', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp', '140', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                // $rowCount++;
                // var_dump($rowCount);
            } //end if

            // var_dump($rowCount);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', null, false, '1px solid ', 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$i]['clientname'], '440', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');

            if ($data[$i]['elapse'] == 150) {
                $a = $data[$i]['balance'];
                $b = 0;
                $c = 0;
                $d = 0;
                $e = 0;

                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '140', null, false, '1px solid ', 'R', 'r', $font, $font_size, '', '', '');

                $subtota = $subtota + $a;
                $subgt = $subgt + $data[$i]['balance'];
            }
            if ($data[$i]['elapse'] >= 151 && $data[$i]['elapse'] <= 180) {
                $b = $data[$i]['balance'];
                $a = 0;
                $c = 0;
                $d = 0;
                $e = 0;

                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '140', null, false, '1px solid ', 'R', 'r', $font, $font_size, '', '', '');

                $subtotb = $subtotb + $b;
                $subgt = $subgt + $data[$i]['balance'];
            }
            if ($data[$i]['elapse'] >= 181 && $data[$i]['elapse'] <= 210) {
                $c = $data[$i]['balance'];
                $a = 0;
                $b = 0;
                $d = 0;
                $e = 0;

                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '140', null, false, '1px solid ', 'R', 'r', $font, $font_size, '', '', '');

                $subtotc = $subtotc + $c;
                $subgt = $subgt + $data[$i]['balance'];
            }
            if ($data[$i]['elapse'] >= 211 && $data[$i]['elapse'] <= 240) {
                $d = $data[$i]['balance'];
                $a = 0;
                $c = 0;
                $b = 0;
                $e = 0;

                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '140', null, false, '1px solid ', 'R', 'r', $font, $font_size, '', '', '');

                $subtotd = $subtotd + $d;
                $subgt = $subgt + $data[$i]['balance'];
            }
            if ($data[$i]['elapse'] > 240) {
                $e = $data[$i]['balance'];
                $a = 0;
                $c = 0;
                $d = 0;
                $b = 0;

                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('-', '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '105', null, false, '1px solid ', '', 'r', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['balance'], 2), '140', null, false, '1px solid ', 'R', 'r', $font, $font_size, '', '', '');

                $subtote = $subtote + $e;
                $subgt = $subgt + $data[$i]['balance'];
            }
            $str .= $this->reporter->endrow();

            // $rowCount++;
            $agent = $data[$i]['agentname'];



            $tota = $tota + $a;
            $totb = $totb + $b;
            $totc = $totc + $c;
            $totd = $totd + $d;
            $tote = $tote + $e;
            $gt = $gt + $data[$i]['balance'];
            // if ($rowCount >= $count) {
            //   $str .= $this->reporter->endtable();
            //   $str .= $this->reporter->page_break();

            //   // new header
            //   $str .= $this->roosevelt_displayHeader($params, $data);
            //   $str .= $this->reporter->begintable('1000');

            //   // reprint current agent header
            //   $str .= $this->reporter->startrow();
            //   $str .= $this->reporter->col(strtoupper($agent), '335', null, false, $border, 'TBL', 'L', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '115', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
            //   $str .= $this->reporter->endrow();

            //   // reset
            //   $rowCount = 1; // counted the reprinted agent row
            // }
            if ($this->reporter->linecounter >= $count) {

                // close current page
                // $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();
                // $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '440', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '105', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '105', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '105', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '105', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '105', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();

                // reset line counter
                $this->reporter->linecounter = 0;

                // print new header
                $str .= $this->roosevelt_displayHeader($params, $data);
                $str .= $this->reporter->begintable($layoutsize);

                // reprint current agent name
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'TBL', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col(strtoupper($agent), '440', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '105', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('&nbsp;', '140', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
            }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false,  $border, 'TBL', 'L', $font, $font_size, 'B', '', '');;
        $str .= $this->reporter->col(' SALESMAN TOTAL', '440', null, false,  $border, 'TB', 'L', $font, $font_size, 'B', '',  '');
        $str .= $this->reporter->col(($subtota == 0 ? '-' : number_format($subtota, 2)), '105', null, false,  $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($subtotb == 0 ? '-' : number_format($subtotb, 2)), '105', null, false,  $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($subtotc == 0 ? '-' : number_format($subtotc, 2)), '105', null, false,  $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($subtotd == 0 ? '-' : number_format($subtotd, 2)), '105', null, false,  $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($subtote == 0 ? '-' : number_format($subtote, 2)), '105', null, false,  $border, 'LTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($subgt == 0 ? '-' : number_format($subgt, 2)), '140', null, false,  $border, 'LTBR', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false,  $border, 'TBL', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(' GRAND TOTAL', '335', null, false,  $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tota == 0 ? '-' : number_format($tota, 2)), '105', null, false,  $border, 'LTB', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totb == 0 ? '-' : number_format($totb, 2)), '105', null, false,  $border, 'LTB', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totc == 0 ? '-' : number_format($totc, 2)), '105', null, false,  $border, 'LTB', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totd == 0 ? '-' : number_format($totd, 2)), '105', null, false,  $border, 'LTB', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tote == 0 ? '-' : number_format($tote, 2)), '105', null, false,  $border, 'LTB', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($gt == 0 ? '-' : number_format($gt, 2)), '140', null, false,  $border, 'LTBR', 'r', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

}
