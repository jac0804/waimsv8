<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class purchases_comparison_graph
{
  public $modulename = 'Purchases Comparison Graph';
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
    $fields = ['radioprint'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['year', 'radioposttype', 'print'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );
    data_set($col2, 'year.required', true);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 'default' as print, '0' as posttype, left(now(),4) as year, 
    '' as project, 0 as projectid, '' as projectname, 
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname";
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
    $graph = $this->default_purchases_comparison_graph($config, $result, $result1, $result2);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'graph' => $graph];
  }

  public function default_query($filters, $y = 0)
  {
    $systemtype = $this->companysetup->getsystemtype($filters['params']);
    $year = $filters['params']['dataparams']['year'];
    $posttype = $filters['params']['dataparams']['posttype'];
    $companyid  = $filters['params']['companyid'];
    $year = $year - $y;

    $filter1 = "";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $filters['params']['dataparams']['ddeptname'];
      $project = $filters['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $filters['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $filters['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }


    if ($posttype == 1) {
      $tablehead = "lahead";
      $tablestock = "lastock";
    } else {
      $tablehead = "glhead";
      $tablestock = "glstock";
    }

    switch ($companyid) {
      case 6: //MITSUKOSHI
        $docfilter = "head.doc in ('RR','RP')";
        break;
      default:
        $docfilter = "head.doc = 'RR'";
        break;
    }

    if ($systemtype == 'AMS') {
      $qry = "select m, sum(amt) as amt
        from (
          select month(head.dateid) as m, (detail.cr-detail.db) as amt
            from glhead as head
            left join gldetail as detail on detail.trno=head.trno
            left join cntnum on cntnum.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
          where head.doc in ('APV','CV')
          and year(dateid)='" . $year . "'
          and detail.refx=0 and left(coa.alias,2)='AP'
        ) as t
      group by m";
    } else {
      $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext) as amt from 
          $tablehead as head left join $tablestock as stock 
          on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where $docfilter and 
          year(dateid) = '" . $year . "' $filter1 group by month(head.dateid)
          ) as T group by m";

      if ($posttype == 2) {
        $qry = "select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext) as amt from 
        glhead as head left join glstock as stock 
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where $docfilter and 
        year(dateid) = '" . $year . "' $filter1 group by month(head.dateid)
        ) as T group by m
        union all
        select m,sum(amt) as amt from (select month(head.dateid) as m,sum(stock.ext) as amt from 
        lahead as head left join lastock as stock 
        on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno where $docfilter and 
        year(dateid) = '" . $year . "' $filter1 group by month(head.dateid)
        ) as T group by m;";
      }
    }
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function generateDefaultHeader($params)
  {
    $str = '';
    $fontsize = '10';
    $font = $this->companysetup->getrptfont($params['params']);
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    if ($params['params']['dataparams']['posttype'] == 0) {
      $post = 'Posted';
    } else if ($params['params']['dataparams']['posttype'] == 1) {
      $post = 'Unposted';
    } else {
      $post = 'All';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      $proj   = $params['params']['dataparams']['project'];
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
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchases Comparison Graph ', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow('');
    $str .= $this->reporter->col('Center:' . $params['params']['center'], null, null, '', '1px solid ', '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($post), null, null, '', '1px solid ', '', 'l', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, '', '1px solid ', '', 'l', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, null, null, '', '1px solid ', '', 'l', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_purchases_comparison_graph($config, $data, $data1, $data2)
  {
    $year = $config['params']['dataparams']['year'];
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
}
