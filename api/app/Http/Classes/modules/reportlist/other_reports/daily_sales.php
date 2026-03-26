<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class daily_sales
{
    public $modulename = 'Daily Sales';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,   left(now(),10) as end");
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
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {
        // QUERY

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "  select count(datenow) as datenow,itemname,itemid,max(maximum) as maximum,aveleadtime,maxleadtime, sum(sold) as sold from (
                    select datenow, sum(sj) as sold,itemname,itemid,aveleadtime,maxleadtime,max(sj) as maximum from (
                    select date(head.dateid) as datenow,i.itemname,i.itemid,i.aveleadtime,i.maxleadtime, sum(stock.iss) as sj
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    where head.doc='sj'  and date(head.dateid) between '$start' and '$end'
                    group by date(head.dateid),i.itemname,i.itemid,i.aveleadtime,i.maxleadtime

                    union all

                    select date(head.dateid) as datenow,i.itemname,i.itemid,i.aveleadtime,i.maxleadtime, sum(stock.iss) as sj
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    where head.doc='sj'  and date(head.dateid) between '$start' and '$end'
                    group by date(head.dateid),i.itemname,i.itemid,i.aveleadtime,i.maxleadtime) as a
                    group by a.datenow,a.itemname,a.itemid,a.aveleadtime,a.maxleadtime) as test  where test.itemid is not null
                    group by test.itemname,test.itemid,test.aveleadtime,test.maxleadtime
                    order by itemname";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '1050';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        // $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Daily Sales', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($start . ' - ' . $end, '1150', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PRODUCT NAME', '341', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AVERAGE INVENTORY', '134', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('MAXIMUM INVENTORY', '125', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('AVERAGE LEAD TIME', '125', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('MAXIMUM LEAD TIME', '125', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('SAFETY LOCK', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('RE ORDER LEVEL', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $count = 55;
        $page = 55;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $layoutsize = '1050';
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->displayHeader($config);

        foreach ($result as $key => $data) {
            // echo "Checking sold  " . $data->sold . ' - ' . $data->itemname;
            $ave = $data->datenow > 0 ? $data->sold / $data->datenow : 0;
            ////safetylock...  maximum inventory * max lead time -average inventory * ave lead time 
            // ////reorder level...  ave inventory * average lead time + safetylock
            $safetylock   = (($data->maximum * $data->maxleadtime) - $ave) * $data->aveleadtime;
            $reorderlevel = ($ave * $data->aveleadtime) + $safetylock;
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->itemname, '341', '', false, $border, '', 'L', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($ave, 2), '134', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->maximum > 0 ? number_format($data->maximum, 2) : '-', '125', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->aveleadtime > 0 ? number_format($data->aveleadtime, 2) : '-', '125', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($data->maxleadtime > 0 ? number_format($data->maxleadtime, 2) : '-', '125', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($safetylock > 0 ? $safetylock : '-', '100', '', false, $border, '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col($reorderlevel > 0 ? $reorderlevel : '-', '100', '', false, $border, '', 'C', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();
            // if ($this->reporter->linecounter == $page) {
            //     $str .= $this->reporter->endtable();
            //     $str .= $this->reporter->page_break();
            //     $str .= $this->displayHeader($config, $layoutsize);
            //     $page += $count;
            // }
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class