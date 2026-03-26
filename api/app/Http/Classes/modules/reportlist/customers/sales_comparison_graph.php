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

class sales_comparison_graph
{
  public $modulename = 'Sales Comparison Graph';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;



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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $fields = ['radioprint'];
    } else {
      $fields = ['radioprint', 'categoryname', 'subcatname'];
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['radioposttype', 'year', 'print'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'year.required', true);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select
   'default' as print,
    '0' as posttype, 
    left(now(),4) as year,
   '' as category,
   '' as categoryname,
    '' as subcat,
    '' as subcatname";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as industry ";
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $result = $this->default_query($config);
    $result1 = $this->default_query($config, 1);
    $result2 = $this->default_query($config, 2);

    $str = $this->generateDefaultHeader($config);
    $graph = $this->default_sales_comparison_graph($config, $result, $result1, $result2);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'graph' => $graph, 'params' => ['orientation' => 'p']];
  }


  public function default_query($filters, $y = 0)
  {
    $companyid = $filters['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($filters['params']);
    $year = $filters['params']['dataparams']['year'];
    $center = $filters['params']['center'];
    $posttype = $filters['params']['dataparams']['posttype'];
    $category  = $filters['params']['dataparams']['category'];
    $subcatname =  $filters['params']['dataparams']['subcat'];

    $year = $year - $y;
    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }
    $joins = '';
    switch ($posttype) {
      case 1:
        $tablehead = "lahead";
        $tablestock = "lastock";
        $tabledetail = "ladetail";
        $joins = 'left join client on client.client=head.client';
        break;
      case 0:
        $tablehead = "glhead";
        $tablestock = "glstock";
        $tabledetail = "gldetail";
        $joins = 'left join client on client.clientid=head.clientid';
    }
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $filters['params']['dataparams']['project'];
      $deptid = $filters['params']['dataparams']['ddeptname'];
      $project = $filters['params']['dataparams']['projectid'];
      $indus = $filters['params']['dataparams']['industry'];

      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $filters['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter .= " and stock.projectid = $project";
      }
      if ($deptid != "") {
        $filter .= " and head.deptid = $dept";
      }
      if ($indus != "") {
        $filter .= " and client.industry = '$indus'";
      }
    } else {
      $filter .= "";
    }

    $center = $filters['params']['center'];

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION' || $systemtype == 'AIMSPOS') {
      if ($posttype == 1 || $posttype == 0) {
        $qry = "select m, sum(amt) as amt
      from (
        select month(head.dateid) as m, sum(detail.cr-detail.db) as amt 
        from " . $tablehead . " as head 
        left join " . $tabledetail . " as detail on detail.trno=head.trno 
        left join cntnum on cntnum.trno=head.trno 
        left join coa on coa.acnoid=detail.
        acnoid where head.doc in ('SJ', 'SD', 'SE', 'SF','CP') and year(head.dateid)='" . $year . "' 
        and left(coa.alias,2) in ('SA', 'SD', 'SR') " . $filter . " 
        group by month(head.dateid)) as T 
        group by m";
      } else {
        $qry = "select m, sum(amt) as amt
      from (
        select month(head.dateid) as m, sum(detail.cr-detail.db) as amt 
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno 
        left join cntnum on cntnum.trno=head.trno 
        left join coa on coa.acnoid=detail.
        acnoid where head.doc in ('SJ', 'SD', 'SE', 'SF','CP') and year(head.dateid)='" . $year . "' 
        and left(coa.alias,2) in ('SA', 'SD', 'SR') " . $filter . " 
        group by month(head.dateid)
        union all 
        select month(head.dateid) as m, sum(detail.cr-detail.db) as amt 
        from glhead as head 
        left join gldetail as detail on detail.trno=head.trno 
        left join cntnum on cntnum.trno=head.trno 
        left join coa on coa.acnoid=detail.
        acnoid where head.doc in ('SJ', 'SD', 'SE', 'SF','CP') and year(head.dateid)='" . $year . "' 
        and left(coa.alias,2) in ('SA', 'SD', 'SR') " . $filter . " 
        group by month(head.dateid)
        
        ) as T 
        group by m";
      }
    } else {
      if ($posttype == 1 || $posttype == 0) {
        $qry = "select m,sum(amt) as amt, 
          category,subcatname
          from (select month(head.dateid) as m,sum(stock.ext) as amt,cat.name as category, 
          subcat.name as subcatname
          from 
          $tablehead as head 
          left join $tablestock as stock on stock.trno=head.trno 
          left join cntnum on cntnum.trno=head.trno 
          
            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
          $joins
          where head.doc in ('SJ','MJ','SD','SE','SF') and 
          year(head.dateid) = '" . $year . "' $filter and item.isofficesupplies=0
          group by month(head.dateid),  cat.name, subcat.name) as T 
      
          group by m,category,subcatname";
      } else {
        $qry = "
          select m,sum(amt) as amt, 
          category,subcatname
          from (select month(head.dateid) as m,sum(stock.ext) as amt,cat.name as category, 
          subcat.name as subcatname
          from 
          lahead as head 
          left join lastock as stock on stock.trno=head.trno 
          left join cntnum on cntnum.trno=head.trno 
          
            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            left join client on client.client=head.client

          
          where head.doc in ('SJ','MJ','SD','SE','SF') and 
          year(head.dateid) = '" . $year . "' $filter and item.isofficesupplies=0
          group by month(head.dateid),  cat.name, subcat.name
          
          union all 
          select month(head.dateid) as m,sum(stock.ext) as amt,cat.name as category, 
          subcat.name as subcatname
          from 
          glhead as head 
          left join glstock as stock on stock.trno=head.trno 
          left join cntnum on cntnum.trno=head.trno 
          
            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat
            left join client on client.clientid=head.clientid

          
          where head.doc in ('SJ','MJ','SD','SE','SF') and 
          year(head.dateid) = '" . $year . "' $filter and item.isofficesupplies=0
          group by month(head.dateid),  cat.name, subcat.name
          ) as T 
      
          group by m,category,subcatname";
      }
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function generateDefaultHeader($params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $categoryname  = $params['params']['dataparams']['categoryname'];
    $subcatname =  $params['params']['dataparams']['subcat'];

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else {
      $post = 'Unposted';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      $proj   = $params['params']['dataparams']['project'];
      $indus   = $params['params']['dataparams']['industry'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $params['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }

      if ($indus == "") {
        $indus = 'ALL';
      }
    }


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Comparison Graph ', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('');
    $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    } else {
      $subcatname =  $params['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
    }


    $str .= $this->reporter->endrow();
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Industry : ' . $indus, null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, null, null, '', '1px solid ', '', 'l', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  } //end fn

  private function default_sales_comparison_graph($config, $data, $data1, $data2)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $year = $config['params']['dataparams']['year'];
    $center = $config['params']['center'];

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $graphdata1 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $graphdata2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    foreach ($data as $key => $value) {
      $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
    }

    foreach ($data1 as $key => $value1) {
      $graphdata1[$data1[$key]->m - 1] = $data1[$key]->amt;
    }

    foreach ($data2 as $key => $value2) {
      $graphdata2[$data2[$key]->m - 1] = $data2[$key]->amt;
    }

    $series = [['name' => $year - 2, 'data' => $graphdata2], ['name' => $year - 1, 'data' => $graphdata1], ['name' => $year, 'data' => $graphdata]];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 400],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'white']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['categories' => $months],
      'yaxis' => ['title' => ['text' => '']],
      'fill' => ['opacity' => 1]
    ];
    return array('series' => $series, 'chartoption' => $chartoption);
  }
}//end class