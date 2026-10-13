<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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

class analyzed_agent_sales_monthly
{
  public $modulename = 'Analyzed Agent Sales Monthly';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

  public function __construct($params = null)
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass($params);
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $fields = ['radioprint', 'dcentername'];
    } else {
      $fields = ['radioprint', 'dcentername', 'categoryname', 'subcatname'];
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
      case 21: //kinggeorge
        array_push($fields, 'divsion');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        array_push($fields, 'brandname');
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    unset($col1['divsion']['labeldata']);
    unset($col1['labeldata']['divsion']);
    data_set($col1, 'divsion.name', 'stockgrp');

    $fields = ['year', 'radioposttype'];
    $col2 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 72: //Hahsy
        array_push($fields, 'radioreporttype');
        $col2 = $this->fieldClass->create($fields);
        break;
    }
    data_set(
      $col2,
      'radioreporttype.options',
      [
        ['label' => 'Sales Less Return', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Return from GJ', 'value' => '1', 'color' => 'teal'],
        ['label' => 'Discount Only', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,
    left(now(),4) as year,
    '0' as posttype,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as project, 
    0 as projectid, 
    '' as projectname,
    0 as deptid, 
    '' as dept, 
    '' as deptname,
    '' as ddeptname,
    '' as industry, 
    '' as divsion, 
    0 as groupid, 
    '' as stockgrp,
    0 as brandid, 
    '' as brandname,
    '' as subcat,
    '' as subcatname,
    '' as category,
    '' as categoryname,
    '0' as reporttype";

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
    $params = $this->reportParams;
    if ($this->isHahsySalesLessReturn($config)) {
      $params = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1300'];
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $params];
  }

  public function reportplotting($config)
  {
    if ($this->isHahsySalesLessReturn($config)) {
      if ($config['params']['dataparams']['reporttype'] == '2') {
        return $this->reportDefaultLayout_HAHSY_DISC($config);
      }
      if ($config['params']['dataparams']['reporttype'] == '1') {
        return $this->reportDefaultLayout_HAHSY_GJ($config);
      }
      return $this->reportDefaultLayout_HAHSY($config);
    }
    return $this->reportDefaultLayout($config);
  }

  private function isHahsySalesLessReturn($config)
  {
    $companyid = $config['params']['companyid'];
    $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : '0';
    return ($companyid == 72 && in_array($reporttype, ['0', '1', '2']));
  }

  public function reportDefault($config)
  {
    // QUERY
    $transac = $config['params']['dataparams']['posttype'];

    switch ($transac) {
      case '0': // posted
        $query = $this->reportDefault_POSTED($config);
        break;
      case '1': // unposted
        $query = $this->reportDefault_UNPOSTED($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $year    = $config['params']['dataparams']['year'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    if ($categoryname != "") {
      $category  = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat =  $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }

    $filter1 = "";
    $code    = $config['params']['dataparams']['center'];
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    $joins = "";
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $indus = $config['params']['dataparams']['industry'];
        $projectname = $config['params']['dataparams']['project'];
        $dept = $config['params']['dataparams']['dept'];
        $joins .= "left join client as indus on indus.clientid=head.clientid";

        if ($indus != "") {
          $filter1 .= " and indus.industry = '$indus'";
        }
        if ($projectname != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($dept != "") {
          $deptid = $config['params']['dataparams']['deptid'];
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 21: //kinggeorge
        $groupname =  $config['params']['dataparams']['stockgrp'];

        if ($groupname) {
          $groupid  = $config['params']['dataparams']['groupid'];
          $filter .= " and item.groupid=$groupid";
        }
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $brand = $config['params']['dataparams']['brandname'];
        if ($brand != '') {
          $brandid = $config['params']['dataparams']['brandid'];
          $filter .= " and item.brand=$brandid";
        }
        break;
    }

    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $query = "
      select clientname, yr, 
      sum(mojan) as mojan, 
      sum(mofeb) as mofeb, 
      sum(momar) as momar,
      sum(moapr) as moapr, 
      sum(momay) as momay, 
      sum(mojun) as mojun, 
      sum(mojul) as mojul,
      sum(moaug) as moaug,
      sum(mosep) as mosep, 
      sum(mooct) as mooct, 
      sum(monov) as monov, 
      sum(modec) as modec

      from (select 'p' as tr, ifnull(agent.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (stock.cr-stock.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (stock.cr-stock.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (stock.cr-stock.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (stock.cr-stock.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (stock.cr-stock.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (stock.cr-stock.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (stock.cr-stock.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (stock.cr-stock.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (stock.cr-stock.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (stock.cr-stock.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (stock.cr-stock.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (stock.cr-stock.db) else 0 end) as modec

      from glhead as head left join gldetail as stock on stock.trno=head.trno
      left join client as agent on agent.clientid=head.agentid
      left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=stock.acnoid
      where head.doc in ('SD','SE','SF', 'SJ','cp') and left(coa.alias,2) in ('SA', 'SD', 'SR') and year(head.dateid)='$year'  $filter $filter1 
      group by ifnull(agent.clientname,''), year(head.dateid)) as x 

      group by clientname, yr order by clientname, yr";
    } else {
      $query = "
      select clientname, yr, 
      sum(mojan) as mojan, 
      sum(mofeb) as mofeb, 
      sum(momar) as momar,
      sum(moapr) as moapr, 
      sum(momay) as momay, 
      sum(mojun) as mojun, 
      sum(mojul) as mojul,
      sum(moaug) as moaug,
      sum(mosep) as mosep, 
      sum(mooct) as mooct, 
      sum(monov) as monov, 
      sum(modec) as modec
      
      from (select 'p' as tr, ifnull(agent.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
      sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
      sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
      sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
    
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join client as agent on agent.clientid=head.agentid
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid

      $joins
      where head.doc in ('SD','SE','SF', 'SJ','MJ') and year(head.dateid)='$year' $filter $filter1 and item.isofficesupplies=0     and stock.ext<>0
      group by ifnull(agent.clientname,''), year(head.dateid)) as x 
  
      group by clientname, yr order by clientname, yr";
    }

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $year    = $config['params']['dataparams']['year'];
    $category  = $config['params']['dataparams']['category'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcat =  $config['params']['dataparams']['subcat'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filter = "";
    if ($categoryname != "") {
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $filter .= " and item.subcat='$subcat'";
    }

    $filter1 = "";
    $code    = $config['params']['dataparams']['center'];
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    $joins = "";
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $indus = $config['params']['dataparams']['industry'];
        $projectname = $config['params']['dataparams']['project'];
        $dept = $config['params']['dataparams']['dept'];
        $joins .= "left join client as indus on indus.client=head.client";

        if ($indus != "") {
          $filter1 .= " and indus.industry = '$indus'";
        }
        if ($projectname != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($dept != "") {
          $deptid = $config['params']['dataparams']['deptid'];
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 21: //kinggeorge 
        $groupname =  $config['params']['dataparams']['stockgrp'];

        if ($groupname) {
          $groupid  = $config['params']['dataparams']['groupid'];
          $filter .= " and item.groupid=$groupid";
        }
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $brand = $config['params']['dataparams']['brandname'];

        if ($brand != '') {
          $brandid = $config['params']['dataparams']['brandid'];
          $filter .= " and item.brand=$brandid";
        }
        break;
      default:
        $filter1 .= "";
        break;
    }


    if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
      $query = "select clientname, yr, 
      sum(mojan) as mojan, 
      sum(mofeb) as mofeb, 
      sum(momar) as momar,
      sum(moapr) as moapr, 
      sum(momay) as momay, 
      sum(mojun) as mojun, 
      sum(mojul) as mojul, 
      sum(moaug) as moaug,
      sum(mosep) as mosep, 
      sum(mooct) as mooct, 
      sum(monov) as monov, 
      sum(modec) as modec
  
      from (select 'u' as tr, ifnull(agent.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then (stock.cr-stock.db) else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then (stock.cr-stock.db) else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then (stock.cr-stock.db) else 0 end) as momar,
      sum(case when month(head.dateid)=4 then (stock.cr-stock.db) else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then (stock.cr-stock.db) else 0 end) as momay,
      sum(case when month(head.dateid)=6 then (stock.cr-stock.db) else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then (stock.cr-stock.db) else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then (stock.cr-stock.db) else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then (stock.cr-stock.db) else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then (stock.cr-stock.db) else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then (stock.cr-stock.db) else 0 end) as monov,
      sum(case when month(head.dateid)=12 then (stock.cr-stock.db) else 0 end) as modec     
  
      from lahead as head 
      left join ladetail as stock on stock.trno=head.trno 
      left join client as agent on agent.client=head.agent
      left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acnoid=stock.acnoid
      where head.doc in ('SD','SE','SF', 'SJ','CP') and left(coa.alias,2) in ('SA', 'SD', 'SR') and year(head.dateid)='$year' $filter $filter1
      group by ifnull(agent.clientname,''), year(head.dateid)) as x 
      group by clientname, yr order by clientname, yr";
    } else {
      $query = "select clientname, yr, 
      sum(mojan) as mojan, 
      sum(mofeb) as mofeb, 
      sum(momar) as momar,
      sum(moapr) as moapr, 
      sum(momay) as momay, 
      sum(mojun) as mojun, 
      sum(mojul) as mojul, 
      sum(moaug) as moaug,
      sum(mosep) as mosep, 
      sum(mooct) as mooct, 
      sum(monov) as monov, 
      sum(modec) as modec
      
      from (select 'u' as tr, ifnull(agent.clientname,'') as clientname, year(head.dateid) as yr,
      sum(case when month(head.dateid)=1 then stock.ext else 0 end) as mojan,
      sum(case when month(head.dateid)=2 then stock.ext else 0 end) as mofeb,
      sum(case when month(head.dateid)=3 then stock.ext else 0 end) as momar,
      sum(case when month(head.dateid)=4 then stock.ext else 0 end) as moapr,
      sum(case when month(head.dateid)=5 then stock.ext else 0 end) as momay,
      sum(case when month(head.dateid)=6 then stock.ext else 0 end) as mojun,
      sum(case when month(head.dateid)=7 then stock.ext else 0 end) as mojul,
      sum(case when month(head.dateid)=8 then stock.ext else 0 end) as moaug,
      sum(case when month(head.dateid)=9 then stock.ext else 0 end) as mosep,
      sum(case when month(head.dateid)=10 then stock.ext else 0 end) as mooct,
      sum(case when month(head.dateid)=11 then stock.ext else 0 end) as monov,
      sum(case when month(head.dateid)=12 then stock.ext else 0 end) as modec
      
      from lahead as head 
      left join lastock as stock on stock.trno=head.trno 
      left join client as agent on agent.client=head.agent
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      $joins
      where head.doc in ('SD','SE','SF', 'SJ','MJ') and year(head.dateid)='$year' $filter $filter1 and item.isofficesupplies=0
      and stock.ext<>0
      group by ifnull(agent.clientname,''), year(head.dateid)) as x 
      group by clientname, yr order by clientname, yr";
    }

    return $query;
  }

  private function displayHeader($config)
  {
    $username = $config['params']['user'];
    $dcenter  = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $transac = $config['params']['dataparams']['posttype'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $center  = $config['params']['dataparams']['center'];
    if ($center == '') {
      $center = 'ALL';
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($dcenter, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE AGENT SALES ( MONTHLY ) ', '', '', '', $border, '', '', $font, '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow('200', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($transac == 0) {
      $transac = 'Posted';
    } else {
      $transac = 'Unposted';
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($transac), '200', '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    if ($companyid != 21) { //not kinggeorge
      $str .= $this->reporter->col('Center: ' . $center, null, '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    }
    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '200', '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname), '200', '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '200', '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname), '200', '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Industry : ' . $indus, null, '', '', $border, '', '', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, null, '', '', $border, '', '', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, '', '', $border, '', '', $font, $fontsize, '', '', '', '');
    }
    $str .= $this->reporter->col('Print Date: ' . date('Y-m-d H:i:s'), null, '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila, technolab
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), null, '', '', $border, '', '', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function columnHeader($config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT NAME', '120', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('JAN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('FEB', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MAR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('APR', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('MAY', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('JUN', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('JUL', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AUG', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('SEP', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('OCT', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('NOV', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DEC', '65', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;
    $this->reporter->linecounter = 0;
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->displayHeader($config);
    $str .= $this->columnHeader($config);

    $jan = 0;
    $feb = 0;
    $mar = 0;
    $apr = 0;
    $may = 0;
    $jun = 0;
    $jul = 0;
    $aug = 0;
    $sep = 0;
    $oct = 0;
    $nov = 0;
    $dec = 0;
    $gtotal = 0;

    foreach ($result as $key => $data) {
      $sumrow = 0;

      $str .= $this->reporter->startrow();

      if ($data->clientname == '') {
        $str .= $this->reporter->col('No Agent', '120', '', '', $border, '', 'L', $font, $fontsize, '', '', '', '');
      } else {
        $str .= $this->reporter->col($data->clientname, '120', '', '', $border, '', 'L', $font, $fontsize, '', '', '', '');
      }

      if ($data->mojan != 0) {
        $str .= $this->reporter->col(number_format($data->mojan, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mojan;
        $jan = $jan + $data->mojan;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->mofeb != 0) {
        $str .= $this->reporter->col(number_format($data->mofeb, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mofeb;
        $feb = $feb + $data->mofeb;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->momar != 0) {
        $str .= $this->reporter->col(number_format($data->momar, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->momar;
        $mar = $mar + $data->momar;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->moapr != 0) {
        $str .= $this->reporter->col(number_format($data->moapr, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->moapr;
        $apr = $apr + $data->moapr;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->momay != 0) {
        $str .= $this->reporter->col(number_format($data->momay, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->momay;
        $may = $may + $data->momay;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->mojun != 0) {
        $str .= $this->reporter->col(number_format($data->mojun, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mojun;
        $jun = $jun + $data->mojun;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->mojul != 0) {
        $str .= $this->reporter->col(number_format($data->mojul, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mojul;
        $jul = $jul + $data->mojul;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->moaug != 0) {
        $str .= $this->reporter->col(number_format($data->moaug, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->moaug;
        $aug = $aug + $data->moaug;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->mosep != 0) {
        $str .= $this->reporter->col(number_format($data->mosep, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mosep;
        $sep = $sep + $data->mosep;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->mooct != 0) {
        $str .= $this->reporter->col(number_format($data->mooct, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->mooct;
        $oct = $oct + $data->mooct;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->monov != 0) {
        $str .= $this->reporter->col(number_format($data->monov, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->monov;
        $nov = $nov + $data->monov;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($data->modec != 0) {
        $str .= $this->reporter->col(number_format($data->modec, 2), '65', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $sumrow = $sumrow + $data->modec;
        $dec = $dec + $data->modec;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      if ($sumrow != 0) {
        $str .= $this->reporter->col(number_format($sumrow, 2), '130', '', '', $border, '', 'R', $font, $fontsize, '', '', '', '');
        $gtotal = $gtotal + $sumrow;
      } else {
        $str .= $this->reporter->col('-', '', '', '', '', '', 'C', '', '', '', '', '', '');
      }

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader($config);
        $str .= $this->columnHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '120', '', '', $border, 'T', 'L', $font, $fontsize, 'B', '', '');

    if ($jan != 0) {
      $str .= $this->reporter->col(number_format($jan, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($feb != 0) {
      $str .= $this->reporter->col(number_format($feb, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($mar != 0) {
      $str .= $this->reporter->col(number_format($mar, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($apr != 0) {
      $str .= $this->reporter->col(number_format($apr, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($may != 0) {
      $str .= $this->reporter->col(number_format($may, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($jun != 0) {
      $str .= $this->reporter->col(number_format($jun, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($jul != 0) {
      $str .= $this->reporter->col(number_format($jul, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($aug != 0) {
      $str .= $this->reporter->col(number_format($aug, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($sep != 0) {
      $str .= $this->reporter->col(number_format($sep, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($oct != 0) {
      $str .= $this->reporter->col(number_format($oct, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($nov != 0) {
      $str .= $this->reporter->col(number_format($nov, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($dec != 0) {
      $str .= $this->reporter->col(number_format($dec, 2), '65', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '65', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }

    if ($gtotal != 0) {
      $str .= $this->reporter->col(number_format($gtotal, 2), '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('-', '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefault_HAHSY($config)
  {
    $year         = $config['params']['dataparams']['year'];
    $transac      = $config['params']['dataparams']['posttype'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $code         = $config['params']['dataparams']['center'];

    $filter = "";
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    if ($transac == '0') { // posted
      $from = "from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as agent on agent.clientid=head.agentid";
    } else { // unposted
      $from = "from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as agent on agent.client=head.agent";
    }

    // SJ adds to sales, CM (return) is subtracted from sales
    $amt = "(case when head.doc='CM' then -stock.ext else stock.ext end)";

    $query = "select clientname, yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun,
      sum(mojul) as mojul, sum(moaug) as moaug, sum(mosep) as mosep,
      sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
        select upper(ifnull(agent.clientname,'')) as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1  then $amt else 0 end) as mojan,
        sum(case when month(head.dateid)=2  then $amt else 0 end) as mofeb,
        sum(case when month(head.dateid)=3  then $amt else 0 end) as momar,
        sum(case when month(head.dateid)=4  then $amt else 0 end) as moapr,
        sum(case when month(head.dateid)=5  then $amt else 0 end) as momay,
        sum(case when month(head.dateid)=6  then $amt else 0 end) as mojun,
        sum(case when month(head.dateid)=7  then $amt else 0 end) as mojul,
        sum(case when month(head.dateid)=8  then $amt else 0 end) as moaug,
        sum(case when month(head.dateid)=9  then $amt else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then $amt else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then $amt else 0 end) as monov,
        sum(case when month(head.dateid)=12 then $amt else 0 end) as modec
        $from
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        where head.doc in ('SJ','CM','SD','SE','SF','MJ') and year(head.dateid)='$year' $filter
        group by 1, 2
      ) as x
      group by clientname, yr
      order by clientname, yr";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader_HAHSY($config, $layoutsize, $font, $fontsize)
  {

    $username = $config['params']['user'];
    $dcenter  = $config['params']['center'];
    $year     = $config['params']['dataparams']['year'];
    $transac  = $config['params']['dataparams']['posttype'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $centername   = $config['params']['dataparams']['centername'];
    $code         = $config['params']['dataparams']['center'];

    $transtype = ($transac == '0') ? 'POSTED' : 'UNPOSTED';
    $centertxt = ($code == '') ? 'ALL CENTER' : strtoupper($centername);
    $itemclass = ($categoryname == '') ? 'ALL CATEGORY' : strtoupper($categoryname);
    $subcattxt = ($subcatname == '') ? 'ALL SUB-CATEGORY' : strtoupper($subcatname);

    $str = '';
    $layoutsize = '1300';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($dcenter, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE AGENT SALES MONTHLY - ' . $year, '800', null, false, '', '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/Y g:i:sA'), '400', null, false, '', '', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRANSACTION :', '95', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($transtype, '105', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('CENTER :', '60', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($centertxt, '180', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('ITEM CATEGORY :', '110', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($itemclass, '170', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('SUB-CATEGORY :', '100', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($subcattxt, '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TYPE :', '50', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('SALES LESS RETURN', '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->pagenumber('Page', 130, null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('S A L E S', $layoutsize, null, false, '', '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function columnHeader_HAHSY($config, $layoutsize, $font, $fontsize)
  {
    $layoutsize = '1300';
    $str = '';
    $headers = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT NAME', '130', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    foreach ($headers as $h) {
      $str .= $this->reporter->col($h, '88', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('TOTAL', '125', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_HAHSY($config)
  {
    $result = $this->reportDefault_HAHSY($config);
    $count = 38;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '9';
    $border = '1px solid ';
    $months = ['mojan', 'mofeb', 'momar', 'moapr', 'momay', 'mojun', 'mojul', 'moaug', 'mosep', 'mooct', 'monov', 'modec'];

    $str = '';
    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:0px;margin-left:20px');
    $str .= $this->displayHeader_HAHSY($config, $layoutsize, $font, $fontsize);
    $str .= $this->columnHeader_HAHSY($config, $layoutsize, $font, $fontsize);

    $mtotal = array_fill_keys($months, 0);
    $gtotal = 0;

    foreach ($result as $data) {
      $sumrow = 0;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->clientname == '' ? 'NO AGENT' : $data->clientname, '130', null, false, '', '', 'L', $font, $fontsize, '', '', '');

      foreach ($months as $m) {
        $val = $data->$m;
        $sumrow += $val;
        $mtotal[$m] += $val;
        $str .= $this->reporter->col($val == 0 ? '-' : number_format($val, 2), '78', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      }

      $gtotal += $sumrow;
      $str .= $this->reporter->col($sumrow == 0 ? '-' : number_format($sumrow, 2), '134', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader_HAHSY($config, $layoutsize, $font, $fontsize);
        $str .= $this->columnHeader_HAHSY($config, $layoutsize, $font, $fontsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    foreach ($months as $m) {
      $str .= $this->reporter->col($mtotal[$m] == 0 ? '-' : number_format($mtotal[$m], 2), '78', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col($gtotal == 0 ? '-' : number_format($gtotal, 2), '134', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefault_HAHSY_GJ($config)
  {
    $year   = $config['params']['dataparams']['year'];
    $transac = $config['params']['dataparams']['posttype'];
    $code   = $config['params']['dataparams']['center'];

    $filter = "";
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    if ($transac == '0') {
      $from = "from glhead as head
        left join gldetail as stock on stock.trno=head.trno
        left join client as agent on agent.clientid=head.agentid";
    } else {
      $from = "from lahead as head
        left join ladetail as stock on stock.trno=head.trno
        left join client as agent on agent.client=head.agent";
    }

    $amt = "(stock.db-stock.cr)";

    $query = "select clientname, yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun,
      sum(mojul) as mojul, sum(moaug) as moaug, sum(mosep) as mosep,
      sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
        select upper(ifnull(agent.clientname,'')) as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1  then $amt else 0 end) as mojan,
        sum(case when month(head.dateid)=2  then $amt else 0 end) as mofeb,
        sum(case when month(head.dateid)=3  then $amt else 0 end) as momar,
        sum(case when month(head.dateid)=4  then $amt else 0 end) as moapr,
        sum(case when month(head.dateid)=5  then $amt else 0 end) as momay,
        sum(case when month(head.dateid)=6  then $amt else 0 end) as mojun,
        sum(case when month(head.dateid)=7  then $amt else 0 end) as mojul,
        sum(case when month(head.dateid)=8  then $amt else 0 end) as moaug,
        sum(case when month(head.dateid)=9  then $amt else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then $amt else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then $amt else 0 end) as monov,
        sum(case when month(head.dateid)=12 then $amt else 0 end) as modec
        $from
        left join cntnum on cntnum.trno=head.trno
        left join coa on coa.acnoid=stock.acnoid
        where head.doc='GJ' and coa.alias='SR1' and year(head.dateid)='$year' $filter
        group by 1, 2
      ) as x
      group by clientname, yr
      order by clientname, yr";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader_HAHSY_GJ($config, $font, $fontsize)
  {
    $username = $config['params']['user'];
    $dcenter  = $config['params']['center'];
    $year     = $config['params']['dataparams']['year'];
    $transac  = $config['params']['dataparams']['posttype'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $centername   = $config['params']['dataparams']['centername'];
    $code         = $config['params']['dataparams']['center'];

    $transtype = ($transac == '0') ? 'POSTED' : 'UNPOSTED';
    $centertxt = ($code == '') ? 'ALL CENTER' : strtoupper($centername);
    $subcattxt = ($subcatname == '') ? 'ALL SUB-CATEGORY' : strtoupper($subcatname);
    $itemclass = ($categoryname == '') ? 'ALL CATEGORY' : strtoupper($categoryname);
    $layoutsize = '1300';

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($dcenter, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE AGENT SALES MONTHLY - ' . $year, '800', null, false, '', '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/Y g:i:sA'), '500', null, false, '', '', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRANSACTION :', '95', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($transtype, '105', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('CENTER :', '60', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($centertxt, '180', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('ITEM CATEGORY :', '110', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($itemclass, '170', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('SUB-CATEGORY :', '100', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($subcattxt, '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TYPE :', '50', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('SALES LESS RETURN', '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->pagenumber('Page', 130, null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('S A L E S   R E T U R N', $layoutsize, null, false, '', '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_HAHSY_GJ($config)
  {
    $result = $this->reportDefault_HAHSY_GJ($config);
    $count = 38;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '9';
    $border = '1px solid ';
    $months = ['mojan', 'mofeb', 'momar', 'moapr', 'momay', 'mojun', 'mojul', 'moaug', 'mosep', 'mooct', 'monov', 'modec'];

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:0px;margin-left:20px');
    $str .= $this->displayHeader_HAHSY_GJ($config, $font, $fontsize);
    $str .= $this->columnHeader_HAHSY_DISC($font, $fontsize);

    $mtotal = array_fill_keys($months, 0);
    $gtotal = 0;

    foreach ($result as $data) {
      $sumrow = 0;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->clientname == '' ? 'NO AGENT' : $data->clientname, '140', null, false, '', '', 'L', $font, $fontsize, '', '', '');

      foreach ($months as $m) {
        $val = $data->$m;
        $sumrow += $val;
        $mtotal[$m] += $val;
        $str .= $this->reporter->col($val == 0 ? '-' : number_format($val, 2), '88', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      }

      $gtotal += $sumrow;
      $str .= $this->reporter->col($sumrow == 0 ? '-' : number_format($sumrow, 2), '104', null, false, '', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader_HAHSY_GJ($config, $font, $fontsize);
        $str .= $this->columnHeader_HAHSY_DISC($font, $fontsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    foreach ($months as $m) {
      $str .= $this->reporter->col($mtotal[$m] == 0 ? '-' : number_format($mtotal[$m], 2), '88', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col($gtotal == 0 ? '-' : number_format($gtotal, 2), '104', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function hahsyMap($config, $expr)
  {
    $year = $config['params']['dataparams']['year'];
    $transac = $config['params']['dataparams']['posttype'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $code = $config['params']['dataparams']['center'];

    $filter = "";
    if ($categoryname != "") {
      $filter .= " and item.category='" . $config['params']['dataparams']['category'] . "'";
    }
    if ($subcatname != "") {
      $filter .= " and item.subcat='" . $config['params']['dataparams']['subcat'] . "'";
    }
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    if ($transac == '0') {
      $from = "from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as agent on agent.clientid=head.agentid";
    } else {
      $from = "from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as agent on agent.client=head.agent";
    }

    $cols = '';
    for ($i = 1; $i <= 12; $i++) {
      $cols .= "sum(case when month(head.dateid)=$i then $expr else 0 end) as m$i,";
    }
    $cols = rtrim($cols, ',');

    $query = "select upper(ifnull(agent.clientname,'')) as clientname, $cols
      $from
      left join cntnum on cntnum.trno=head.trno
      left join item on item.itemid=stock.itemid
      where head.doc in ('SJ','CM') and year(head.dateid)='$year' $filter
      group by 1 order by 1";

    $map = [];
    foreach ($this->coreFunctions->opentable($query) as $r) {
      $name = $r->clientname == '' ? 'NO AGENT' : $r->clientname;
      for ($i = 1; $i <= 12; $i++) {
        $k = 'm' . $i;
        $map[$name][$i] = $r->$k;
      }
    }
    return $map;
  }

  private function hahsyTotals($map)
  {
    $t = array_fill(1, 12, 0);
    foreach ($map as $row) {
      for ($i = 1; $i <= 12; $i++) $t[$i] += $row[$i];
    }
    return $t;
  }

  public function reportDefault_HAHSY_DISC($config)
  {
    $year         = $config['params']['dataparams']['year'];
    $transac      = $config['params']['dataparams']['posttype'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $code         = $config['params']['dataparams']['center'];

    $filter = "";
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($code != '') {
      $filter .= " and cntnum.center='$code'";
    }

    if ($transac == '0') {
      $from = "from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join client as agent on agent.clientid=head.agentid";
    } else {
      $from = "from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as agent on agent.client=head.agent";
    }

    $amt = "stock.disc";

    $query = "select clientname, yr,
      sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,
      sum(moapr) as moapr, sum(momay) as momay, sum(mojun) as mojun,
      sum(mojul) as mojul, sum(moaug) as moaug, sum(mosep) as mosep,
      sum(mooct) as mooct, sum(monov) as monov, sum(modec) as modec
      from (
        select upper(ifnull(agent.clientname,'')) as clientname, year(head.dateid) as yr,
        sum(case when month(head.dateid)=1  then $amt else 0 end) as mojan,
        sum(case when month(head.dateid)=2  then $amt else 0 end) as mofeb,
        sum(case when month(head.dateid)=3  then $amt else 0 end) as momar,
        sum(case when month(head.dateid)=4  then $amt else 0 end) as moapr,
        sum(case when month(head.dateid)=5  then $amt else 0 end) as momay,
        sum(case when month(head.dateid)=6  then $amt else 0 end) as mojun,
        sum(case when month(head.dateid)=7  then $amt else 0 end) as mojul,
        sum(case when month(head.dateid)=8  then $amt else 0 end) as moaug,
        sum(case when month(head.dateid)=9  then $amt else 0 end) as mosep,
        sum(case when month(head.dateid)=10 then $amt else 0 end) as mooct,
        sum(case when month(head.dateid)=11 then $amt else 0 end) as monov,
        sum(case when month(head.dateid)=12 then $amt else 0 end) as modec
        $from
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid=stock.itemid
        where head.doc in ('SJ','CM','SD','SE','SF','MJ') and year(head.dateid)='$year' $filter
        group by 1, 2
      ) as x
      group by clientname, yr
      order by clientname, yr";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader_HAHSY_DISC($config, $font, $fontsize)
  {
    $username = $config['params']['user'];
    $dcenter  = $config['params']['center'];
    $year     = $config['params']['dataparams']['year'];
    $transac  = $config['params']['dataparams']['posttype'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $centername   = $config['params']['dataparams']['centername'];
    $code         = $config['params']['dataparams']['center'];

    $transtype = ($transac == '0') ? 'POSTED' : 'UNPOSTED';
    $centertxt = ($code == '') ? 'ALL CENTER' : strtoupper($centername);
    $subcattxt = ($subcatname == '') ? 'ALL SUB-CATEGORY' : strtoupper($subcatname);
    $itemclass = ($categoryname == '') ? 'ALL CATEGORY' : strtoupper($categoryname);
    $layoutsize = '1300';

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($dcenter, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ANALYZE AGENT SALES MONTHLY - ' . $year, '800', null, false, '', '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/Y g:i:sA'), '500', null, false, '', '', 'R', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TRANSACTION :', '95', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($transtype, '105', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('CENTER :', '60', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($centertxt, '180', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('ITEM CATEGORY :', '110', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($itemclass, '170', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('SUB-CATEGORY :', '100', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col($subcattxt, '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->col('TYPE :', '50', null, false, '', '', 'L', $font, $fontsize);
    $str .= $this->reporter->col('SALES LESS RETURN', '150', null, false, '', '', 'L', $font, $fontsize, 'B');
    $str .= $this->reporter->pagenumber('Page', 130, null, false, '', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  private function columnHeader_HAHSY_DISC($font, $fontsize)
  {
    $headers = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    $str = '';
    $str .= $this->reporter->begintable('1300');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AGENT NAME', '140', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    foreach ($headers as $h) {
      $str .= $this->reporter->col($h, '88', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('TOTAL', '104', null, false, '1px dashed', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function hahsySection($title, $map, $totals, $font, $fontsize, $isPct = false)
  {
    $border = '1px solid ';
    $fmt = function ($v) {
      return $v == 0 ? '-' : number_format($v, 2);
    };

    $str = '';
    $str .= $this->reporter->begintable('1300');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($title, '1300', null, false, '', '', 'C', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->columnHeader_HAHSY_DISC($font, $fontsize);

    foreach ($map as $name => $row) {
      $sum = 0;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($name, '140', null, false, '1px dotted', 'B', 'L', $font, $fontsize, '', '', '');
      for ($i = 1; $i <= 12; $i++) {
        $sum += $row[$i];
        $str .= $this->reporter->col($fmt($row[$i]), '88', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', '');
      }
      $tot = $isPct ? $row['total'] : $sum;
      $str .= $this->reporter->col($fmt($tot), '104', null, false, '1px dotted', 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '140', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $gt = 0;
    for ($i = 1; $i <= 12; $i++) {
      $gt += $totals[$i];
      $str .= $this->reporter->col($fmt($totals[$i]), '88', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col($fmt($isPct ? $totals['total'] : $gt), '104', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function reportDefaultLayout_HAHSY_DISC($config)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '9';
    $layoutsize = '1300';

    $gross = "(stock.isamt*stock.isqty)";
    $sign  = "(case when head.doc='CM' then -1 else 1 end)";
    $disc = [];
    $months = ['mojan', 'mofeb', 'momar', 'moapr', 'momay', 'mojun', 'mojul', 'moaug', 'mosep', 'mooct', 'monov', 'modec'];
    foreach ($this->reportDefault_HAHSY_DISC($config) as $r) {
      $name = $r->clientname == '' ? 'NO AGENT' : $r->clientname;
      foreach ($months as $k => $m) {
        $disc[$name][$k + 1] = $r->$m;
      }
    }
    $sales = $this->hahsyMap($config, "$sign*$gross");
    // $cost  = $this->hahsyMap($config, "$sign*(stock.cost*stock.isqty)");
    $cost  = $this->hahsyMap($config, "$sign*(item.cost*stock.isqty)");

    if (empty($disc) && empty($sales)) {
      return $this->othersClass->emptydata($config);
    }

    $profit = [];
    foreach ($sales as $name => $row) {
      for ($i = 1; $i <= 12; $i++) {
        $d = isset($disc[$name][$i]) ? $disc[$name][$i] : 0;
        $c = isset($cost[$name][$i]) ? $cost[$name][$i] : 0;
        $profit[$name][$i] = $row[$i] - $d - $c;
      }
    }

    $tsales = $this->hahsyTotals($sales);
    $tdisc  = $this->hahsyTotals($disc);
    $tcost  = $this->hahsyTotals($cost);
    $pct = [];
    foreach ($sales as $name => $row) {
      $ss = 0;
      $cc = 0;
      for ($i = 1; $i <= 12; $i++) {
        $d = isset($disc[$name][$i]) ? $disc[$name][$i] : 0;
        $c = isset($cost[$name][$i]) ? $cost[$name][$i] : 0;
        $net = $row[$i] - $d;
        $pct[$name][$i] = $net != 0 ? ($c / $net) * 100 : 0;
        $ss += $net;
        $cc += $c;
      }
      $pct[$name]['total'] = $ss != 0 ? ($cc / $ss) * 100 : 0;
    }
    $tpct = [];
    for ($i = 1; $i <= 12; $i++) {
      $tnet = $tsales[$i] - $tdisc[$i];
      $tpct[$i] = $tnet != 0 ? ($tcost[$i] / $tnet) * 100 : 0;
    }
    $tnetall = array_sum($tsales) - array_sum($tdisc);
    $tpct['total'] = $tnetall != 0 ? (array_sum($tcost) / $tnetall) * 100 : 0;

    $str = '';
    // $str .= $this->reporter->beginreport('1300');
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '0px;margin-top:0px;margin-left:20px');
    $str .= $this->displayHeader_HAHSY_DISC($config, $font, $fontsize);
    $str .= $this->hahsySection('SALES   DISCOUNT', $disc, $tdisc, $font, $fontsize);
    $str .= $this->hahsySection('PROFIT', $profit, $this->hahsyTotals($profit), $font, $fontsize);
    $str .= $this->hahsySection('PERCENTAGE', $pct, $tpct, $font, $fontsize, true);
    $str .= $this->reporter->endreport();

    return $str;
  }
}// end class
