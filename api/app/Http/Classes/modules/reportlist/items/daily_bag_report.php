<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class daily_bag_report
{
  public $modulename = 'Daily Bag Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1000px;max-width:1000px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1500'];

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

    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'brandid.name', 'brandid');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end
      ";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {

    $result = $this->reportDefault($config);

    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    switch ($config['params']['dataparams']['print']) {
      default:
        $result = $this->reportDefaultLayout($config, $result);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->DEFAULT_QUERY($config);
    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  public function begbal_qry($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    return "
            select a.Date as dateid,b.col1,b.col2,b.col3,b.col4,b.col5,b.col6 from
            (
                select '$end' - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
                from 
                    (
                        select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all
                        select 5 union all select 6 union all select 7 union all select 8 union all select 9
                    ) as a
                cross join 
                    (
                        select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all 
                        select 5 union all select 6 union all select 7 union all select 8 union all select 9
                    ) as b
                cross join 
                    (
                        select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all 
                        select 5 union all select 6 union all select 7 union all select 8 union all select 9
                    ) as c
            ) a
            cross join
            (
                select
                ifnull(sum(a.col1),0) as col1,
                ifnull(sum(a.col2),0) as col2,
                ifnull(sum(a.col3),0) as col3,
                ifnull(sum(a.col4),0) as col4,
                ifnull(sum(a.col5),0) as col5,
                ifnull(sum(a.col6),0) as col6
                from (
                    select
                    (case when i.body='MAYON TYPE 1P' then sum(stock.qty-stock.iss) end) as col1,
                    (case when i.body='MAYON TYPE 1T SUPER' then sum(stock.qty-stock.iss) end) as col2,
                    (case when i.body='MAYON TYPE 1T PREMIUM' then sum(stock.qty-stock.iss) end) as col3,
                    (case when i.body='MAYON TYPE 1T BICOL' then sum(stock.qty-stock.iss) end) as col4,
                    (case when i.body='MAYON PPC' then sum(stock.qty-stock.iss) end) as col5,
                    (case when i.body='MAYON GREEN' then sum(stock.qty-stock.iss) end) as col6

                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    where i.body in ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')
                    and head.dateid < '$start'
                    group by i.body

                    union all

                    select
                    (case when i.body='MAYON TYPE 1P' then sum(stock.qty-stock.iss) end) as col1,
                    (case when i.body='MAYON TYPE 1T SUPER' then sum(stock.qty-stock.iss) end) as col2,
                    (case when i.body='MAYON TYPE 1T PREMIUM' then sum(stock.qty-stock.iss) end) as col3,
                    (case when i.body='MAYON TYPE 1T BICOL' then sum(stock.qty-stock.iss) end) as col4,
                    (case when i.body='MAYON PPC' then sum(stock.qty-stock.iss) end) as col5,
                    (case when i.body='MAYON GREEN' then sum(stock.qty-stock.iss) end) as col6

                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    where i.body in ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')
                    and head.dateid < '$start'
                    group by i.body
                ) as a
            ) as b
            where a.Date between '$start' and '$end' order by a.Date";
  }

  // QUERY
  public function DEFAULT_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $filter = "";
    $filter1 = "";



    $order = " order by a.dept asc";

    $query = "
    select date(a.dateid) as dateid,
    ifnull(sum(a.col1),0) as col1,
    ifnull(sum(a.col2),0) as col2,
    ifnull(sum(a.col3),0) as col3,
    ifnull(sum(a.col4),0) as col4,
    ifnull(sum(a.col5),0) as col5,
    ifnull(sum(a.col6),0) as col6,

    ifnull(sum(a.col7),0) as col7,
    ifnull(sum(a.col8),0) as col8,
    ifnull(sum(a.col9),0) as col9,
    ifnull(sum(a.col10),0) as col10,
    ifnull(sum(a.col11),0) as col11,
    ifnull(sum(a.col12),0) as col12,

    ifnull(sum(a.col13),0) as col13,
    ifnull(sum(a.col14),0) as col14,
    ifnull(sum(a.col15),0) as col15,
    ifnull(sum(a.col16),0) as col16,
    ifnull(sum(a.col17),0) as col17,
    ifnull(sum(a.col18),0) as col18
    from (
      select head.dateid,head.doc,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1P' then sum(stock.qty) end) as col1,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T SUPER' then sum(stock.qty) end) as col2,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.qty) end) as col3,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T BICOL' then sum(stock.qty) end) as col4,
      (case when head.doc in ('IS','AJ') and i.body='MAYON PPC' then sum(stock.qty) end) as col5,
      (case when head.doc in ('IS','AJ') and i.body='MAYON GREEN' then sum(stock.qty) end) as col6,

      (case when head.doc='SJ' and i.body='MAYON TYPE 1P' then sum(stock.iss) end) as col7,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T SUPER' then sum(stock.iss) end) as col8,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.iss) end) as col9,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T BICOL' then sum(stock.iss) end) as col10,
      (case when head.doc='SJ' and i.body='MAYON PPC' then sum(stock.iss) end) as col11,
      (case when head.doc='SJ' and i.body='MAYON GREEN' then sum(stock.iss) end) as col12,

      (case when head.doc='AJ' and i.body='MAYON TYPE 1P' then sum(stock.iss) end) as col13,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T SUPER' then sum(stock.iss) end) as col14,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.iss) end) as col15,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T BICOL' then sum(stock.iss) end) as col16,
      (case when head.doc='AJ' and i.body='MAYON PPC' then sum(stock.iss) end) as col17,
      (case when head.doc='AJ' and i.body='MAYON GREEN' then sum(stock.iss) end) as col18

      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join item as i on i.itemid=stock.itemid
      where head.doc in ('IS','AJ','SJ') and i.body in
      ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')
      and head.dateid between '$start' and '$end'
      group by head.dateid,head.doc,i.body

      union all

      select head.dateid,head.doc,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1P' then sum(stock.qty) end) as col1,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T SUPER' then sum(stock.qty) end) as col2,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.qty) end) as col3,
      (case when head.doc in ('IS','AJ') and i.body='MAYON TYPE 1T BICOL' then sum(stock.qty) end) as col4,
      (case when head.doc in ('IS','AJ') and i.body='MAYON PPC' then sum(stock.qty) end) as col5,
      (case when head.doc in ('IS','AJ') and i.body='MAYON GREEN' then sum(stock.qty) end) as col6,

      (case when head.doc='SJ' and i.body='MAYON TYPE 1P' then sum(stock.iss) end) as col7,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T SUPER' then sum(stock.iss) end) as col8,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.iss) end) as col9,
      (case when head.doc='SJ' and i.body='MAYON TYPE 1T BICOL' then sum(stock.iss) end) as col10,
      (case when head.doc='SJ' and i.body='MAYON PPC' then sum(stock.iss) end) as col11,
      (case when head.doc='SJ' and i.body='MAYON GREEN' then sum(stock.iss) end) as col12,

      (case when head.doc='AJ' and i.body='MAYON TYPE 1P' then sum(stock.iss) end) as col13,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T SUPER' then sum(stock.iss) end) as col14,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T PREMIUM' then sum(stock.iss) end) as col15,
      (case when head.doc='AJ' and i.body='MAYON TYPE 1T BICOL' then sum(stock.iss) end) as col16,
      (case when head.doc='AJ' and i.body='MAYON PPC' then sum(stock.iss) end) as col17,
      (case when head.doc='AJ' and i.body='MAYON GREEN' then sum(stock.iss) end) as col18
      
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item as i on i.itemid=stock.itemid
      where head.doc in ('IS','AJ','SJ') and i.body in
      ('MAYON TYPE 1P','MAYON TYPE 1T SUPER','MAYON TYPE 1T PREMIUM','MAYON TYPE 1T BICOL','MAYON PPC','MAYON GREEN')
      and head.dateid between '$start' and '$end'
      group by head.dateid,head.doc,i.body
    ) as a
    group by a.dateid";


    return $query;
  }


  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $layoutsize = '1500';

    $supp = '';
    if ($companyid == 21) $supp = $config['params']['dataparams']['client']; //kinggeorge

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Daily Bag Report', $layoutsize, null, false, $border, '', 'C', $font, $fontsize + 4, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 2, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('INVENTORY', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col(' MORNING', 51, null, false, $border, 'T', 'L', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 2, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('DELIVERIES', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col(' TODAY', 51, null, false, $border, 'T', 'L', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 2, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('DIS', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('PATCH', 51, null, false, $border, 'T', 'L', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 2, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('DAMAGE/', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('ALLOCATE', 51, null, false, $border, 'T', 'L', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('TYPE 1', 51, null, false, $border, 'TL', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('TYPE 1T', 51, null, false, $border, 'T', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');

    $str .= $this->reporter->col('TYPE 1', 51, null, false, $border, 'TL', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('TYPE 1T', 51, null, false, $border, 'T', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');

    $str .= $this->reporter->col('TYPE 1', 51, null, false, $border, 'TL', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('TYPE 1T', 51, null, false, $border, 'T', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');

    $str .= $this->reporter->col('TYPE 1', 51, null, false, $border, 'TL', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TL', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('TYPE 1T', 51, null, false, $border, 'T', 'C', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'L', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'TR', 'R', $font, $fontsize + 1, 'B', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PORT', 51, null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPER', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PREMIUM', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BICOL', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PPC', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GREEN', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', 51, null, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('PORT', 51, null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPER', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PREMIUM', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BICOL', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PPC', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GREEN', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', 51, null, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('PORT', 51, null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPER', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PREMIUM', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BICOL', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PPC', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GREEN', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', 51, null, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('PORT', 51, null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPER', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PREMIUM', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BICOL', 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PPC', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GREEN', 51, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', 51, null, false, $border, 'R', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 7;
    $padding = '';
    $margin = '';
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $query = $this->begbal_qry($config);

    $begbal = json_decode(json_encode($this->coreFunctions->opentable($query)), true);



    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1500';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $begbalCol1 = $begbal[0]['col1'];
    $begbalCol2 = $begbal[0]['col2'];
    $begbalCol3 = $begbal[0]['col3'];
    $begbalCol4 = $begbal[0]['col4'];
    $begbalCol5 = $begbal[0]['col5'];
    $begbalCol6 = $begbal[0]['col6'];
    $totalBegBalCol = 0;
    $totalDeliveryCol = 0;
    $totalDispatchCol = 0;
    $totalDamageCol = 0;

    $maxResultCount = count($result);
    $resultCount = 0;
    for ($i = 0; $i < count($begbal); $i++) {


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($begbal[$i]['dateid'], 51, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col(number_format($begbalCol1, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($begbalCol2, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($begbalCol3, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($begbalCol4, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($begbalCol5, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($begbalCol6, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
      $totalBegBalCol = $begbalCol1 + $begbalCol2 + $begbalCol3 + $begbalCol4 + $begbalCol5 + $begbalCol6;
      $str .= $this->reporter->col(number_format($totalBegBalCol, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

      if (isset($result[$resultCount]['dateid'])) {
        if ($begbal[$i]['dateid'] == $result[$resultCount]['dateid']) {
          $str .= $this->reporter->col(number_format($result[$resultCount]['col1'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col2'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col3'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col4'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col5'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col6'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $totalDeliveryCol = $result[$resultCount]['col1'] + $result[$resultCount]['col2'] + $result[$resultCount]['col3'] + $result[$resultCount]['col4'] + $result[$resultCount]['col5'] + $result[$resultCount]['col6'];
          $str .= $this->reporter->col(number_format($totalDeliveryCol, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col(number_format($result[$resultCount]['col7'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col8'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col9'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col10'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col11'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col12'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $totalDispatchCol = $result[$resultCount]['col7'] + $result[$resultCount]['col8'] + $result[$resultCount]['col9'] + $result[$resultCount]['col10'] + $result[$resultCount]['col11'] + $result[$resultCount]['col12'];
          $str .= $this->reporter->col(number_format($totalDispatchCol, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col(number_format($result[$resultCount]['col13'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col14'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col15'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col16'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col17'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($result[$resultCount]['col18'], 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $totalDamageCol = $result[$resultCount]['col13'] + $result[$resultCount]['col14'] + $result[$resultCount]['col15'] + $result[$resultCount]['col16'] + $result[$resultCount]['col17'] + $result[$resultCount]['col18'];
          $str .= $this->reporter->col(number_format($totalDamageCol, 2), 51, null, false, $border, 'TLR', 'R', $font, $fontsize, 'B', '', '');


          $begbalCol1 += $result[$resultCount]['col1'] - ($result[$resultCount]['col7'] + $result[$resultCount]['col13']);
          $begbalCol2 += $result[$resultCount]['col2'] - ($result[$resultCount]['col8'] + $result[$resultCount]['col14']);
          $begbalCol3 += $result[$resultCount]['col3'] - ($result[$resultCount]['col9'] + $result[$resultCount]['col15']);
          $begbalCol4 += $result[$resultCount]['col4'] - ($result[$resultCount]['col10'] + $result[$resultCount]['col16']);
          $begbalCol5 += $result[$resultCount]['col5'] - ($result[$resultCount]['col11'] + $result[$resultCount]['col17']);
          $begbalCol6 += $result[$resultCount]['col6'] - ($result[$resultCount]['col12'] + $result[$resultCount]['col18']);
          $resultCount++;
        } else {

          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TLR', 'R', $font, $fontsize, 'B', '', '');
        }
      } else {

        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TL', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format(0, 2), 51, null, false, $border, 'TLR', 'R', $font, $fontsize, 'B', '', '');
      }

      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', 51, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class