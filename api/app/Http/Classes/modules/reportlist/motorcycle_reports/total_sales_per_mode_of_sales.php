<?php

namespace App\Http\Classes\modules\reportlist\motorcycle_reports;

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

class total_sales_per_mode_of_sales
{
    public $modulename = 'Total Sales Per Mode of Sales';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1340'];

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
        $fields = ['radioprint', 'start', 'end', 'dwhname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');

        data_set($col1,  'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        return $this->coreFunctions->opentable(
            "select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '' as wh,
            '' as whname,
            '' as dwhname,
            '0' as whid"
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $result = $this->CDO_Layout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->CDO_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function CDO_QUERY($config)
    {
        $username   = $config['params']['user'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $wh         = $config['params']['dataparams']['wh'];
        $whid     = $config['params']['dataparams']['whid'];

        $filter = '';

        if ($wh != "") {
            $filter = $filter . " and wh.clientid='$whid'";
        }

        $query = "   select name,sum(totalsold) as totalsold,sum(retqty) as retqty,sum(retamt) as retamt,sum(ext) as ext from (
                    select date(head.dateid) as datenow,mode.name,
                     sum(stock.iss)  as totalsold,sum(stock.ext) as ext,head.docno,
                     0 as retqty, 0 as retamt
                     from lahead as head
                    left join mode_masterfile as mode on mode.line = head.modeofsales
                     left join client as wh on wh.client=head.wh
                     left join cntnum as num on num.trno=head.trno
                     left join lastock as stock on stock.trno=head.trno
                    where head.doc ='MJ' and head.dateid between '$start' and '$end' $filter
                    group by date(head.dateid),mode.name, head.docno
                    union all
                    select date(head.dateid) as datenow, mode.name,
                    sum(stock.iss)  as totalsold, sum(stock.ext) as ext,head.docno,
                    
                     (select sum(ret) as ret from (
                      select ss.refx, ifnull(sum(ss.qty),0) as ret from lastock as ss
                      left join lahead as hd on hd.trno=ss.trno where hd.doc='CM' group by ss.refx
                     union all
                     select ss.refx, ifnull(sum(ss.qty),0) as ret from glstock as ss
                      left join glhead as hd on hd.trno=ss.trno where hd.doc='CM' group by ss.refx) as rwn
                     where refx = stock.trno) as retqty,


                    (select sum(ext) as ext from (
                      select ss.refx, ifnull(sum(ss.ext),0) as ext from lastock as ss
                      left join lahead as hd on hd.trno=ss.trno where hd.doc='CM' group by ss.refx
                     union all
                     select ss.refx, ifnull(sum(ss.ext),0) as ext from glstock as ss
                     left join glhead as hd on hd.trno=ss.trno where hd.doc='CM'  group by ss.refx) as rwn1
                     where refx = stock.trno) as retamt

                   from glhead as head
                    left join mode_masterfile as mode on mode.line = head.modeofsales
                    left join client as wh on wh.clientid=head.whid
                    left join cntnum as num on num.trno=head.trno
                     left join glstock as stock on stock.trno=head.trno
                    where head.doc ='MJ' and head.dateid between '$start' and '$end' $filter
                   group by date(head.dateid),mode.name,head.docno,stock.trno) as nmn
                 group by name order by name";

        // var_dump($query);
        return $query;
    }


    public function CDO_Layout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "11";
        $border = "1px solid ";
        $layoutsize = '800';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->CDO_header($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {

            $total_qty = 0;
            $totalqty = 0;
            $totalamount = 0;
            $totalpercent = 0;

            foreach ($result as $data) {
                $returnqty = $data->retqty != 0 ? $data->retqty : 0;
                $soldqty = $data->totalsold != 0 ? $data->totalsold : 0;

                $qty = $soldqty - $returnqty;
                $total_qty += $qty;
            }


            foreach ($result as $key => $data) {

                $returnqty = $data->retqty != 0 ? $data->retqty : 0;
                $returnamt = $data->retamt != 0 ? $data->retamt : 0;

                $soldqty = $data->totalsold != 0 ? $data->totalsold : 0;
                $soldamt = $data->ext != 0 ? $data->ext : 0;

                $qty = $soldqty - $returnqty;
                $amt = $soldamt - $returnamt;
                $percent = ($qty / $total_qty) * 100;
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->name, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($qty == 0) ? '-' : number_format($qty, 0), '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($amt == 0) ? '-' : number_format($amt, 2), '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(($percent == 0) ? '-' : number_format($percent, 2) . ' % ', '200', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $totalqty += $qty;
                $totalamount += $amt;
                $totalpercent += $percent;


                // if ($this->reporter->linecounter == $page) {
                //     $str .= $this->reporter->endtable();
                //     $str .= $this->reporter->page_break();
                //    $str .= $this->tableheader_detailed($layoutsize, $config);
                //     $page = $page + $count;
                // }
            }
        }
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '300', null, false, '1px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($totalqty == 0) ? '-' : number_format($totalqty, 0), '100', null, false, '1px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($totalamount == 0) ? '-' : number_format($totalamount, 2), '200', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($totalpercent == 0) ? '-' : number_format($totalpercent, 2) . ' % ', '200', null, false, '1px solid ', 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function CDO_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $monthof        = date("F Y", strtotime($config['params']['dataparams']['end']));
        $wh         = $config['params']['dataparams']['wh'];
        $whname     = $config['params']['dataparams']['whname'];

        $str = '';
        $layoutsize = '800';

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
        $str .= $this->reporter->col('Total Sales Per Mode of Sales', null, null, false, $border, '', '', $font, '20', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        if ($whname == '') {
            $str .= $this->reporter->col('WH: ALL ', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('WH: ' . $whname, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '800';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MODE OF SALES', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PERCENTAGE', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        return $str;
    }
}//end class