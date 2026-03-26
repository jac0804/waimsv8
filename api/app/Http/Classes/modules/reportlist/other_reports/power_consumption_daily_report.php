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

class power_consumption_daily_report
{
  public $modulename = 'Power Consumption Daily Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => 1200];

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

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['powercat', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as powercat,
    '' as powercatline,
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

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0':
        return $this->default_Summarized_Layout($config);
        break;

      case '1':
        return $this->default_Detailed_Layout($config);
        break;
    }

    
  }

  public function reportDefault($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0':
        $query = $this->default_Summarized_Query($config);
        break;

      case '1':
        $query = $this->default_Detailed_Query($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  // QUERY START
  public function default_Summarized_Query($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $powercat     = $config['params']['dataparams']['powercat'];
    $powercatline = $config['params']['dataparams']['powercatline'];

    $filter = '';

    if ($powercat != '') {
      $filter .= " and cat.line='$powercatline'";
    }

    $query = "
    select a.day,a.grouping,a.cat,sum(a.isamt) as amt from(
    select head.dateid,day(head.dateid) as day,stock.isamt*stock.isqty as isamt,cat.groupid as grouping,cat.name as cat
    from pwhead as head
    left join pwstock as stock on stock.trno=head.trno
    left join powercat as cat on cat.line=stock.catid

    where date(head.dateid) between '$start' and '$end' and stock.trno is not null $filter
    union all
    select head.dateid,day(head.dateid) as day,stock.isamt*stock.isqty as isamt,cat.groupid as grouping,cat.name as cat
    from hpwhead as head
    left join hpwstock as stock on stock.trno=head.trno
    left join powercat as cat on cat.line=stock.catid

    where date(head.dateid) between '$start' and '$end' and stock.trno is not null $filter
    order by dateid,grouping,cat
    ) as a
    group by a.dateid,a.day,grouping,cat";

    return $query;
  }

  public function default_Detailed_Query($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $powercat     = $config['params']['dataparams']['powercat'];
    $powercatline = $config['params']['dataparams']['powercatline'];

    $filter = '';

    if ($powercat != '') {
      $filter .= " and cat.line='$powercatline'";
    }

    $query = "
      select head.dateid,day(head.dateid) as day,head.docno,stock.line,stock.isamt*stock.isqty as isamt,cat.name as cat,subcat1.name as subcat,subcat2.name as subcat2
      from pwhead as head
      left join pwstock as stock on stock.trno=head.trno
      left join powercat as cat on cat.line=stock.catid
      left join subpowercat as subcat1 on subcat1.catid=stock.catid and subcat1.line=stock.subcat
      left join subpowercat2 as subcat2 on subcat2.catid=stock.catid and subcat2.line=stock.subcat2 and subcat2.subcatid=stock.subcat
      where date(head.dateid) between '$start' and '$end' $filter
      union all
      select head.dateid,day(head.dateid) as day,head.docno,stock.line,stock.isamt*stock.isqty as isamt,cat.name as cat,subcat1.name as subcat,subcat2.name as subcat2
      from hpwhead as head
      left join hpwstock as stock on stock.trno=head.trno
      left join powercat as cat on cat.line=stock.catid
      left join subpowercat as subcat1 on subcat1.catid=stock.catid and subcat1.line=stock.subcat
      left join subpowercat2 as subcat2 on subcat2.catid=stock.catid and subcat2.line=stock.subcat2 and subcat2.subcatid=stock.subcat
      where date(head.dateid) between '$start' and '$end' $filter
      order by dateid,docno,subcat asc,subcat2 asc
    ";

    return $query;
  }
  // QUERY END

  // LAYOUT START
  public function header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
    $end        = date("m/d/Y", strtotime($config['params']['dataparams']['end']));
    $powercat     = $config['params']['dataparams']['powercat'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $report = '';
    switch ($reporttype) {
      case '0':
        $report = 'Summary';
        break;

      case '1':
        $report = 'Detail';
        break;
    }


    if ($powercat == '') {
      $powercat = 'ALL';
    }


    $str = '';
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
    $str .= $this->reporter->col('Power Consumption Daily Report(' . $report . ')', 1200, null, false, $border, '', '', $font, '18', 'B', '','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Category: ' . $powercat, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    return $str;
  }

  public function default_Summarized_Layout($config)
  {
    $mainqry = $this->default_Summarized_Query($config);

    // mainly displays the day, cat and amt
    $mainresult = $this->coreFunctions->opentable($mainqry);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $powercat     = $config['params']['dataparams']['powercat'];
    $powercatline = $config['params']['dataparams']['powercatline'];


    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $groupCountForTotal = [];
    $widthPerColumn = 0;
    $totalColumnCount = 0;
    $index = 0;
    $columnCount = 0;

    $resultIndex = 0;
    $resultColumnCount = 0;

    $totalPerCat = 0;
    $grandTotalPerLine = 0;
    $grandTotal = 0;
    $loop1 = 0;
    if (empty($mainresult)) {
      return $this->othersClass->emptydata($config);
    }


    $getMostCategoriesPerDayqry = "
    select b.day as value,count(b.day) as cnt from(
      $mainqry
    ) as b
    group by day
    order by cnt desc
    limit 1;
    ";
    // gets the day with the most cat
    $day = $this->coreFunctions->datareader($getMostCategoriesPerDayqry);

    $groupQry = "
    
    select count(c.cat) as count from(
      $mainqry
    ) as c
    where c.day=$day
    
    
    ";
    // gets the # of groups/categories
    $groupResult = $this->coreFunctions->opentable($groupQry);

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config, $layoutsize);

    // sets the # of columns
    foreach ($groupResult as $key => $sidecol1) {
      $totalColumnCount += $sidecol1->count;
    }
    // this is the # of columns, less the day and total/grand total
    // gets the # of group totals(can have multiple totals depending on group count)
    $widthPerColumn = ($layoutsize / ($totalColumnCount +  2));
    // fixed width based on # of columns
    $grandTotalArrayValue = array_fill(0, $totalColumnCount + 2, 0);
    // array of all columns from date to grand total
    $grandTotalArrayCount = 0;

    if (!empty($mainresult)) {

      // this prints the column head
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DATE', $widthPerColumn, null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
      foreach ($groupResult as $key => $sidecol1) {
        $columnCount = $sidecol1->count;
        $catQry = "select distinct cat from($mainqry
              ) as b
              order by cat
              limit $index, $columnCount";
        $catResult = $this->coreFunctions->opentable($catQry);
        $loop1 = 0;
        foreach ($catResult as $key => $sidecol2) {
          if ($loop1 == 0) {
            $str .= $this->reporter->col($sidecol2->cat, $widthPerColumn, null, false, $border, 'TBRL', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($sidecol2->cat, $widthPerColumn, null, false, $border, 'TBR', 'C', $font, $fontsize, '', '', '');
          }
          $loop1 = 1;
        }

        $index += $columnCount;
      }
      $str .= $this->reporter->col('Total (&#8369;)', $widthPerColumn, null, '#bfbfbf', $border, 'TBLR', 'C', $font, $fontsize, '', '', '');

      // start of the main loop

      $mainDownColqry = "select distinct day from($mainqry
          ) as c
          ";
      $mainDownColResults = $this->coreFunctions->opentable($mainDownColqry);
      foreach ($mainDownColResults as $key => $downcol1) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($downcol1->day, $widthPerColumn, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');

        $grandTotalArrayCount = 1;

        $groupcatQry = "
              select distinct cat from(
                $mainqry
              ) as c
              order by cat
              
              ";
        // gets the category and grouping
        $groupcatResult = $this->coreFunctions->opentable($groupcatQry);
        //display for side column
        foreach ($groupcatResult as $key => $sidecol1) {
          $displayamtQry = "select cat,sum(amt) as amt from(
                  $mainqry
                  )as c
                where day=$downcol1->day and cat='$sidecol1->cat'
                group by cat
                order by cat";
          $displayAmt = json_decode(json_encode($this->coreFunctions->opentable($displayamtQry)), true);
          // display amt when not empty or not 0
          if (!empty($displayAmt) && $displayAmt[0]['amt'] != 0) {
            $str .= $this->reporter->col(number_format($displayAmt[0]['amt'], 2), $widthPerColumn, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
            $totalPerCat += $displayAmt[0]['amt'];
            $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + $displayAmt[0]['amt'];
            $grandTotalArrayCount++;
          } else { //display - when 0
            $str .= $this->reporter->col('0', $widthPerColumn, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');
            $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + 0;
            $grandTotalArrayCount++;
          }
        }
        if ($totalPerCat != 0) {
          $str .= $this->reporter->col(number_format($totalPerCat, 2), $widthPerColumn, null, '#bfbfbf', $border, 'BR', 'R', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col('0', $widthPerColumn, null, '#bfbfbf', $border, 'BR', 'R', $font, $fontsize, '', '', '');
        }

        $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + $totalPerCat;
        $grandTotalArrayCount++;

        $grandTotalPerLine += $totalPerCat;
        $totalPerCat = 0;
        $resultIndex += $resultColumnCount;
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', $widthPerColumn, null, '#bfbfbf', $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      for ($x = 1; $x < count($grandTotalArrayValue); $x++) {

        $str .= $this->reporter->col(number_format($grandTotalArrayValue[$x], 2), $widthPerColumn, null, '#bfbfbf', $border, 'BR', 'R', $font, $fontsize, '', '', '');
      }
      // end of main loop
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_Detailed_Layout($config)
  {
    $mainqry = $this->default_Detailed_Query($config);
    $mainresult = $this->coreFunctions->opentable($mainqry);

    $subcat1qry = "
    select subcat,count(subcat2) as count from(
      select cat,subcat,subcat2 from(
        $mainqry
      ) as a
      group by cat,subcat,subcat2
    ) as b
    group by subcat
    ";
    $subcat1result = $this->coreFunctions->opentable($subcat1qry);

    $subcat2qry = "select distinct subcat2 from($mainqry
    ) as a";
    //limit index,range

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $powercat     = $config['params']['dataparams']['powercat'];
    $powercatline = $config['params']['dataparams']['powercatline'];

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1650;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $subcat2CountForTotal = [];
    $widthPerColumn = 0;
    $totalColumnCount = 0;
    $index = 0;
    $columnCount = 0;

    $resultIndex = 0;
    $resultColumnCount = 0;

    $totalPerCat = 0;
    $grandTotalPerLine = 0;
    $loop1 = 0;

    if (empty($powercat)) {
      return $this->othersClass->parameterRequired($config, 'Category', 'Detailed');
    }
    if (empty($mainresult)) {
      return $this->othersClass->emptydata($config);
    }

    // $w = null, $h = null, $bg = false,  $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = ''
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '10px');
    $str .= $this->header_DEFAULT($config, $layoutsize);

    $str .= $this->reporter->begintable($layoutsize);


    foreach ($subcat1result as $key => $sidecol1) {
      array_push($subcat2CountForTotal, $sidecol1->count);
      $totalColumnCount += $sidecol1->count;
    }

    $widthPerColumn = ($layoutsize / ($totalColumnCount + count($subcat2CountForTotal) + 2));
    $grandTotalArrayValue = array_fill(0, $totalColumnCount + count($subcat2CountForTotal) + 2, 0);
    $grandTotalArrayCount = 0;
    if (!empty($mainresult)) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', $widthPerColumn, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      //FOR DISPLAYING CATEGORIES
      foreach ($subcat1result as $key => $sidecol1) {
        for ($h = 0; $h < $sidecol1->count; $h++) {
          if ($h == 0) {
            $str .= $this->reporter->col($sidecol1->subcat, $widthPerColumn, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col('', $widthPerColumn, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          }
        }
        $str .= $this->reporter->col('', $widthPerColumn, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col('', $widthPerColumn, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DATE', $widthPerColumn, null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');

      //FOR DISPLAYING SUBCATEGORIES WITH TOTAL EVERY END OF CATEGORY
      foreach ($subcat1result as $key => $sidecol1) {
        //GET NUMBER OF COLS FOR SUBCATEGORY PER CATEGORY
        $columnCount = $sidecol1->count;
        $subcat2qry = "select distinct subcat2 from($mainqry
              ) as a
              limit $index, $columnCount";
        $subcat2result = $this->coreFunctions->opentable($subcat2qry);
        $loop1 = 0;
        //FOR DISPLAYING SUBCATEGORIES
        foreach ($subcat2result as $key => $sidecol2) {
          if ($loop1 == 0) {
            $str .= $this->reporter->col($sidecol2->subcat2, $widthPerColumn, null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($sidecol2->subcat2, $widthPerColumn, null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          }
          $loop1 = 1;
        }
        //DISPLAY TOTAL
        $str .= $this->reporter->col('Total (&#8369;)', $widthPerColumn, null, '#bfbfbf', $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
        $index += $columnCount;
      }
      $str .= $this->reporter->col('Grand Total (&#8369;)', $widthPerColumn, null, '#bfbfbf', $border, 'TBR', 'C', $font, $fontsize, '', '', '');

      //DOWNWARD LOOP QRY & RESULT
      $mainDownColqry = "select distinct day from($mainqry
          ) as c
          ";
      $mainDownColResults = $this->coreFunctions->opentable($mainDownColqry);

      //FOR DISPLAYING MAIN LOOP
      foreach ($mainDownColResults as $key => $downcol1) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($downcol1->day, $widthPerColumn, null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
        //SETTING GRANDTOTAL INDEX TO START AT 1, 0 is for date, not included
        $grandTotalArrayCount = 1;
        //FOR DISPLAYING ALL ISAMT FOR THE DAY
        for ($i = 0; $i < count($subcat2CountForTotal); $i++) {
          //GET SUBSET OF ISAMT FOR THE DAY
          $resultColumnCount = $subcat2CountForTotal[$i];
          $mainSideColqry = "select sum(isamt) as isamt from($mainqry
                  ) as d
                  where day=$downcol1->day
                  group by subcat,subcat2
                  limit $resultIndex, $resultColumnCount";
          $mainSideColResults = $this->coreFunctions->opentable($mainSideColqry);

          //FOR MAIN DISPLAY OF ISAMT SIDEWAYS
          foreach ($mainSideColResults as $key => $data) {
            $str .= $this->reporter->col(number_format($data->isamt, 2), $widthPerColumn, null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '');

            $totalPerCat += $data->isamt;
            //ADDING UP ISAMT FOR GRANDTOTAL
            $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + $data->isamt;
            $grandTotalArrayCount++;
          }
          //DISPLAY TOTAL PER SUBCATEGORY
          $str .= $this->reporter->col(number_format($totalPerCat, 2), $widthPerColumn, null, '#bfbfbf', $border, 'BLR', 'R', $font, $fontsize, '', '', '');

          //ADDING UP TOTAL FOR GRANDTOTAL
          $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + $totalPerCat;
          $grandTotalArrayCount++;
          //ADDING UP FOR LINE GRANDTOTAL
          $grandTotalPerLine += $totalPerCat;
          //RESET TOTAL
          $totalPerCat = 0;
          //CARRYING ON INDEX
          $resultIndex += $resultColumnCount;
        }
        //RESET INDEX
        $resultIndex = 0;
        //DISPLAY LINE GRANDTOTAL
        $str .= $this->reporter->col(number_format($grandTotalPerLine, 2), $widthPerColumn, null, '#bfbfbf', $border, 'BR', 'R', $font, $fontsize, '', '', '');

        //ADDING UP LINE GRANDTOTAL FOR GRAND TOTAL
        $grandTotalArrayValue[$grandTotalArrayCount] = $grandTotalArrayValue[$grandTotalArrayCount] + $grandTotalPerLine;

        //RESET LINE GRANDTOTAL
        $grandTotalPerLine = 0;
      }
      //DISPLAY GRAND TOTAL PER COLUMN
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', $widthPerColumn, null, '#bfbfbf', $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      //FOR DISPLAYING GRAND TOTAL
      for ($x = 1; $x < count($grandTotalArrayValue); $x++) {

        $str .= $this->reporter->col(number_format($grandTotalArrayValue[$x], 2), $widthPerColumn, null, '#bfbfbf', $border, 'BR', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  // LAYOUT END


}//end class