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

class monthly_sales_report_graph
{
  public $modulename = 'Monthly Sales Report Graph';
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
      case 22: //eipi
        array_push($fields, 'ditemname', 'brandname', 'dclientname', 'dagentname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        break;

      default:
        $col1 = $this->fieldClass->create($fields);

        break;
    }

    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');


    $fields = ['radioposttype', 'year', 'print'];
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
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
                'default' as print, 
                '0' as posttype,
                '' as category,
                '' as categoryname,
                  '' as subcat, 
                  '' as subcatname,
                left(now(),4) as year";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= ", '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as industry ";
        break;
      case 22: //eipi
        $paramstr .= ", '' as itemname,'' as barcode,'' as ditemname,'' as brandid,'' as brandname,'' as brand,'' as client,'' as dclientname,'' as dagentname,'' as agent,'' as agentname";
        break;
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
    switch ($config['params']['companyid']) {
      case 22: //eipi
        $result = $this->default_query_EIPI($config);
        break;
      default:
        $result = $this->default_query($config);
        break;
    }
    $str = $this->generateDefaultHeader($config, $result);
    $graph = $this->default_montly_sales_graph($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'graph' => $graph, 'params' => ['orientation' => 'p']];
  }


  public function default_query($filters)
  {
    $companyid = $filters['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($filters['params']);
    $year = $filters['params']['dataparams']['year'];
    $center = $filters['params']['center'];
    $company = $filters['params']['companyid'];
    $posttype = $filters['params']['dataparams']['posttype'];
    $category  = $filters['params']['dataparams']['category'];
    $subcatname =  $filters['params']['dataparams']['subcat'];

    if ($year == '') {
      $year = date('Y');
    }

    switch ($posttype) {
      case 0: //posted
        $tablehead = "glhead";
        $tablestock = "glstock";
        $tabledetail = "gldetail";

        break;
      case 1: //unposted
        $tablehead = "lahead";
        $tablestock = "lastock";
        $tabledetail = "ladetail";

        break;
    }


    $joins = '';
    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $filters['params']['dataparams']['project'];
      $deptid = $filters['params']['dataparams']['ddeptname'];
      $project = $filters['params']['dataparams']['projectid'];
      $indus = $filters['params']['dataparams']['industry'];


      switch ($posttype) {
        case 0: //posted
          $joins = 'left join client on client.clientid=head.clientid';
          break;
        case 1: //unposted
          $joins = 'left join client on client.client=head.client';
          break;
      }


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
    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      if ($posttype == 2) { //all
        $qry = "select m, sum(amt) as amt from (
          select month(head.dateid) as m,
                sum(detail.cr-detail.db) as amt from glhead as head
                left join gldetail as detail on detail.trno=head.trno 
                left join cntnum on cntnum.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid where head.doc in ('sj', 'sd', 'se', 'sf','cp') 
                and year(head.dateid)='" . $year . "' and left(coa.alias,2) in ('SD', 'SA', 'SR')  " . $filter . " group by month(head.dateid)
          union all
          select month(head.dateid) as m,
                sum(detail.cr-detail.db) as amt from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                left join coa on coa.acnoid=detail.acnoid where head.doc in ('sj', 'sd', 'se', 'sf','cp')
                and year(head.dateid)='" . $year . "' and left(coa.alias,2) in ('SD', 'SA', 'SR')  " . $filter . " group by month(head.dateid)) as t group by m";
      } else {
        $qry = "select m, sum(amt) as amt from (select month(head.dateid) as m, 
                sum(detail.cr-detail.db) as amt from " . $tablehead . " as head
                left join " . $tabledetail . " as detail on detail.trno=head.trno 
                left join cntnum on cntnum.trno=head.trno 
                left join coa on coa.acnoid=detail.acnoid where head.doc in ('sj', 'sd', 'se', 'sf','cp') 
                and year(head.dateid)='" . $year . "' and left(coa.alias,2) in ('SD', 'SA', 'SR') " . $filter . " group by month(head.dateid)) as t group by m";
      }
    } else {
      if ($posttype == 2) { //all

        $qry = "select m,sum(amt) as amt,
           category,subcatname
          from  (
          select month(head.dateid) as m,sum(stock.ext) as amt,
          cat.name as category, subcat.name as subcatname
          from
          glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
              $joins
          where head.doc in ('SJ','MJ','SD','SE','SF','CP') and
          year(head.dateid)='" . $year . "' $filter   and item.isofficesupplies=0
          group by month(head.dateid), cat.name, subcat.name
          union all
          select month(head.dateid) as m,sum(stock.ext) as amt,
          cat.name as category, subcat.name as subcatname
          from 
          lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
              $joins
          where head.doc in ('SJ','MJ','SD','SE','SF','CP') and
          year(head.dateid)='" . $year . "' $filter   and item.isofficesupplies=0
          group by month(head.dateid), cat.name, subcat.name) as T
          group by m,category,subcatname";
      } else {
        $qry = "select m,sum(amt) as amt,
           category,subcatname
          from (select month(head.dateid) as m,sum(stock.ext) as amt,
          cat.name as category, subcat.name as subcatname

          from 
          $tablehead as head 
          left join $tablestock as stock on stock.trno=head.trno 
          left join cntnum on cntnum.trno=head.trno 

            left join item on item.itemid=stock.itemid
            left join itemcategory as cat on cat.line = item.category
            left join itemsubcategory as subcat on subcat.line = item.subcat

          $joins
          where head.doc in ('SJ','MJ','SD','SE','SF','CP') and 
          year(head.dateid)='" . $year . "' $filter and item.isofficesupplies=0
          group by month(head.dateid), cat.name, subcat.name) as T
           group by m,category,subcatname";
      }
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function default_query_EIPI($filters)
  {
    $companyid = $filters['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($filters['params']);
    $year = $filters['params']['dataparams']['year'];
    $center = $filters['params']['center'];
    $posttype = $filters['params']['dataparams']['posttype'];
    $category  = $filters['params']['dataparams']['category'];
    $subcatname =  $filters['params']['dataparams']['subcat'];
    $item = $filters['params']['dataparams']['ditemname'];
    $brand = $filters['params']['dataparams']['brandname'];
    $client = $filters['params']['dataparams']['dclientname'];
    $agent = $filters['params']['dataparams']['dagentname'];

    if ($year == '') {
      $year = date('Y');
    }

    switch ($posttype) {
      case 0: //posted
        $tablehead = "glhead";
        $tablestock = "glstock";
        $tabledetail = "gldetail";
        $left = " left join client on client.clientid=head.clientid ";
        $left .= " left join client as ag on ag.clientid=head.agentid ";
        break;

      case 1: //unposted
        $tablehead = "lahead";
        $tablestock = "lastock";
        $tabledetail = "ladetail";
        $left = " left join client on client.client=head.client ";
        $left .= " left join client as ag on ag.client=head.agent ";
        break;
    }

    $filter = "";
    $select = "";
    $group = "";
    if ($category != "") {
      $filter .= " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter .= " and item.subcat='$subcatname'";
    }

    if ($item != "") {
      $itemid = $filters['params']['dataparams']['itemid'];
      $filter .= " and item.itemid= '$itemid'";
      $select = ", itemid,itemname";
      $group = ", itemid,itemname";
    }

    if ($client != "") {
      $clientcode = $filters['params']['dataparams']['client'];
      $filter .= " and head.client= '$clientcode'";
    }

    if ($agent != "") {
      $agentcode = $filters['params']['dataparams']['agent'];
      $filter .= " and head.agent= '$agentcode'";
    }

    if ($brand != "") {
      $brandid = $filters['params']['dataparams']['brand'];
      $filter .= " and item.brand= '$brandid'";
      $select .= ",brand,brand_desc";
      $group .= ",brand,brand_desc";
    }

    $center = $filters['params']['center'];
    if ($posttype == 2) {
      $qry = "select m,sum(amt) as amt,category,subcatname,client,clientname,
                   agent, agentname  $select
          from 
          (select month(head.dateid) as m,sum(stock.ext) as amt,
                      cat.name as category, subcat.name as subcatname,
                      client.client,head.clientname,ag.client as agent, ag.clientname as agentname,
                      stock.itemid,item.itemname,item.brand,brand.brand_desc
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno 
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join frontend_ebrands as brand on brand.brandid=item.brand
                left join client on client.clientid=head.clientid  left join client as ag on ag.clientid=head.agentid 
                where head.doc in ('SJ','SD','SE','SF','CP') and
                year(head.dateid)='" . $year . "' $filter  and item.isofficesupplies=0
                group by month(head.dateid), cat.name, subcat.name,client.client,head.clientname,
                ag.client, ag.clientname,stock.itemid,item.itemname,item.brand,brand.brand_desc

            union all

          select month(head.dateid) as m,sum(stock.ext) as amt,
                      cat.name as category, subcat.name as subcatname,
                      client.client,head.clientname,ag.client as agent, ag.clientname as agentname,
                      stock.itemid,item.itemname,item.brand,brand.brand_desc
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join frontend_ebrands as brand on brand.brandid=item.brand
                left join client on client.client=head.client  left join client as ag on ag.client=head.agent
                where head.doc in ('SJ','SD','SE','SF','CP') and
                year(head.dateid)='" . $year . "' $filter  and item.isofficesupplies=0
                group by month(head.dateid), cat.name, subcat.name,client.client,head.clientname,
                ag.client, ag.clientname,stock.itemid,item.itemname,item.brand,brand.brand_desc) as T
                group by m,category,subcatname,client,clientname,
                  agent, agentname $group";
    } else {
      $qry = "select m,sum(amt) as amt,category,subcatname,client,clientname,
                   agent, agentname $select
          from (select month(head.dateid) as m,sum(stock.ext) as amt,
                      cat.name as category, subcat.name as subcatname,
                      client.client,head.clientname,ag.client as agent, ag.clientname as agentname,
                      stock.itemid,item.itemname,item.brand,brand.brand_desc
                from $tablehead as head 
                left join $tablestock as stock on stock.trno=head.trno 
                left join cntnum on cntnum.trno=head.trno 
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                left join frontend_ebrands as brand on brand.brandid=item.brand
                $left
                where head.doc in ('SJ','SD','SE','SF','CP') and 
                      year(head.dateid)='" . $year . "' $filter and item.isofficesupplies=0
                group by month(head.dateid), cat.name, subcat.name,client.client,head.clientname,
                         ag.client, ag.clientname,stock.itemid,item.itemname,item.brand,brand.brand_desc) as T
           group by m,category,subcatname,client,clientname,
                  agent, agentname $group";
    }
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function generateDefaultHeader($params, $data)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $categoryname  = $params['params']['dataparams']['categoryname'];
    $subcatname =  $params['params']['dataparams']['subcat'];


    $posttype = $params['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0: //posted
        $post = 'Posted';
        break;
      case 1: //unposted
        $post = 'Unposted';
        break;
      case 2: //all
        $post = 'All';
        break;
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
    $str .= $this->reporter->col('Monthly Sales Report Graph YEAR (' . $params['params']['dataparams']['year'] . ')', null, null, '', '1px solid ', '', 'l', $font, '18', 'B', '', '');
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

  private function default_montly_sales_graph($config, $data)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $dateid = date('Y-m-d');
    $year = date('Y');
    $center = $config['params']['center'];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $graphdata = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    foreach ($data as $key => $value) {
      $graphdata[$data[$key]->m - 1] = $data[$key]->amt;
    }

    $series = [['name' => 'monthly', 'data' => $graphdata]];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 400, 'width' => 800],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '55%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales ' . $year, 'align' => 'left', 'style' => ['color' => 'white']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['categories' => $months],
      'yaxis' => ['title' => ['text' => 'Sales']],
      'fill' => ['opacity' => 1]
    ];
    return array('series' => $series, 'chartoption' => $chartoption);
  }
}//end class