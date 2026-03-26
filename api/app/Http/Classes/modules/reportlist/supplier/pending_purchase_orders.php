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

class pending_purchase_orders
{
  public $modulename = 'Pending Purchase Orders';
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

    switch ($companyid) {
      case 21: //kinggeorge
      case 8: //maxipro
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'ditemname', 'divsion', 'brand', 'class'];
        break;
      case 40: //cdo
        $fields = ['radioprint', 'dclientname', 'age', 'dcentername', 'ditemname', 'divsion', 'brand', 'class'];
        break;
      default:
        $fields = ['radioprint', 'dclientname', 'dcentername', 'ditemname', 'divsion', 'brand', 'class'];
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      case 8: //maxipro
        array_push($fields, 'dprojectname', 'subprojectname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'subprojectname.required', false);
        data_set($col1, 'subprojectname.readonly', false);
        data_set($col1, 'dprojectname.lookupclass', 'projectcode');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'age.readonly', false);

    unset($col1['divsion']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['brand']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'brand.name', 'brandname');
    data_set($col1, 'class.name', 'classic');

    $fields = ['radioposttype'];
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

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $companyid = $config['params']['companyid'];

    $paramstr = "select 'default' as print, '' as client, '' as clientname, 0 as age,
              0 as itemid, '' as ditemname, '' as barcode, 0 as groupid, 0 as brandid,
              0 as classid, '0' as posttype, '' as dclientname, '' as divsion, '' stockgrp,
              '' as brand, '' as brandname, '' as class, '' as classic, '" . $defaultcenter[0]['center'] . "' as center,
              '" . $defaultcenter[0]['centername'] . "' as centername,
              '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $paramstr .= " , '' as project, 0 as projectid, '' as projectname, 0 as deptid, '' as ddeptname, '' as dept, '' as deptname";
        break;
      case 21: //kinggeorge
        $paramstr .= ", adddate(left(now(), 10), -360) as start, left(now(), 10) as end";
        break;
      case 8: //maxipro
        $paramstr .= ", adddate(left(now(), 10), -360) as start, left(now(), 10) as end,
        0 as projectid, '' as dprojectname, '' as projectname, '' as projectcode, '' as subprojectname ";
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    if($config['params']['companyid']==60){
      $result = $this->ericco_Layout($config);
    }else{
      $result = $this->reportDefaultLayout($config);
    }
    

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $client  = $config['params']['dataparams']['client'];
    $barcode = $config['params']['dataparams']['barcode'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];
    $brandname = $config['params']['dataparams']['brandname'];
    $classic = $config['params']['dataparams']['classic'];
    $age     = $config['params']['dataparams']['age'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    $center     = $config['params']['dataparams']['center'];

    if ($center != "") {
      $filter .= " and transnum.center='$center'";
    }
    if ($age != 0) {
      $filter .= " and datediff(curdate(), head.dateid) >= $age";
    }

    if ($config['params']['dataparams']['posttype'] == '0') {
      $filter .= " and transnum.postdate is not null";
    } else if ($config['params']['dataparams']['posttype'] == '1') {
      $filter .= " and transnum.postdate is null";
    } else {
      $filter .= "";
    }

    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=$itemid";
    }
    if ($stockgrp) {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter = $filter . " and item.groupid=$groupid";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=$brandid";
    }
    if ($classic != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=$classid";
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $deptname = $config['params']['dataparams']['deptname'];
        $project = $config['params']['dataparams']['project'];

        if ($project != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and stock.projectid = $projectid";
        }
        if ($deptname != "") {
          $deptid = $config['params']['dataparams']['deptid'];
          $filter1 .= " and head.deptid = $deptid";
        }
        break;
      case 8: //maxipro
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $dprojectname = $config['params']['dataparams']['dprojectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];

        if ($dprojectname != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter1 .= " and head.projectid = $projectid";
        }
        if ($subprojectname != "") {
          $filter1 .= " and head.subproject = '$subprojectname' ";
        }
        $filter1 .= " and head.dateid between '$start' and '$end' ";
        break;
    }

    $hjoin = '';
    $field = '';
    $hfield = '';
    $start = '';
    $end = '';
    $datefilter = '';
    $join = '';

    if ($companyid == 21) { //kinggeorge
      $join = 'left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom';
      $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      $datefilter = " and date(head.dateid) between '" . $start . "' and '" . $end . "'";

      $hjoin = "   
      left join (
        select head.docno,head.dateid as partialdate,stock.trno,stock.line,stock.refx,stock.linex from lahead as head
        left join lastock as stock on stock.trno=head.trno
        where head.doc='RR'
        union all
        select head.docno,head.dateid as partialdate,stock.trno,stock.line,stock.refx,stock.linex from glhead as head
        left join glstock as stock on stock.trno=head.trno
        where head.doc='RR'
      ) as x on x.refx=stock.trno and x.linex=stock.line
      left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom";
      $field = ",'' as partialdate,(stock.qty-stock.qa)/uom.factor as pounserved,uom.uom as pouom,stock.rrqty as poqty";
      $hfield = ",ifnull(x.partialdate,'') as partialdate,(stock.qty-stock.qa)/uom.factor as pounserved,uom.uom as pouom,stock.rrqty as poqty";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname, item.groupid, item.brand, item.class,
                  stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $hfield
                  from hpohead as head 
                  left join hpostock as stock on stock.trno=head.trno
                  $hjoin
                  left join item on item.itemid=stock.itemid
                  left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  where stock.void=0 and stock.isreturn=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;

      case 1: // unposted
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname, item.groupid, item.brand, item.class,
                          stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $field
                  from ((pohead as head left join postock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  $join
                  where stock.void=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;

      case 2: //all
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname, item.groupid, item.brand, item.class,
                          stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $field
                  from ((pohead as head left join postock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  $join
                  where stock.void=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter
                  union all
                  select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname, item.groupid, item.brand, item.class,
                  stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $hfield
                  from hpohead as head 
                  left join hpostock as stock on stock.trno=head.trno
                  $hjoin
                  left join item on item.itemid=stock.itemid
                  left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  where stock.void=0 and stock.isreturn=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  
  public function report_ericco($config)
  {
    $companyid = $config['params']['companyid'];
    $client  = $config['params']['dataparams']['client'];
    $barcode = $config['params']['dataparams']['barcode'];
    $stockgrp = $config['params']['dataparams']['stockgrp'];
    $brandname = $config['params']['dataparams']['brandname'];
    $classic = $config['params']['dataparams']['classic'];
    $age     = $config['params']['dataparams']['age'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $filter1 = "";
    $center     = $config['params']['dataparams']['center'];

    if ($center != "") {
      $filter .= " and transnum.center='$center'";
    }
    if ($age != 0) {
      $filter .= " and datediff(curdate(), head.dateid) >= $age";
    }

    if ($config['params']['dataparams']['posttype'] == '0') {
      $filter .= " and transnum.postdate is not null";
    } else if ($config['params']['dataparams']['posttype'] == '1') {
      $filter .= " and transnum.postdate is null";
    } else {
      $filter .= "";
    }

    if ($client != "") {
      $filter = $filter . " and client.client='$client'";
    }
    if ($barcode != "") {
      $itemid = $config['params']['dataparams']['itemid'];
      $filter = $filter . " and stock.itemid=$itemid";
    }
    if ($stockgrp) {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter = $filter . " and item.groupid=$groupid";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand=$brandid";
    }
    if ($classic != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter = $filter . " and item.class=$classid";
    }


    $hjoin = '';
    $field = '';
    $hfield = '';
    $start = '';
    $end = '';
    $datefilter = '';
    $join = '';


    switch ($posttype) {
      case 0: // posted
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname,item.barcode, item.groupid, item.brand, item.class,
                  stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $hfield
                  from hpohead as head 
                  left join hpostock as stock on stock.trno=head.trno
                  $hjoin
                  left join item on item.itemid=stock.itemid
                  left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  where stock.void=0 and stock.isreturn=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;

      case 1: // unposted
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname,item.barcode, item.groupid, item.brand, item.class,
                          stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $field
                  from ((pohead as head left join postock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  $join
                  where stock.void=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;

      case 2: //all
        $query = "select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname,item.barcode, item.groupid, item.brand, item.class,
                          stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $field
                  from ((pohead as head left join postock as stock on stock.trno=head.trno)
                  left join item on item.itemid=stock.itemid)left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  $join
                  where stock.void=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter
                  union all
                  select client.clientname as cgrp, concat(item.groupid,' ',item.brand,' ',item.itemname) as igrp,
                          head.docno, date(head.dateid) as dateid, datediff(curdate(), head.dateid) as age, 
                          client.clientname, item.itemname,item.barcode, item.groupid, item.brand, item.class,
                  stock.qty as qty, (stock.qty-stock.qa) as unserved, item.uom,stock.qa $hfield
                  from hpohead as head 
                  left join hpostock as stock on stock.trno=head.trno
                  $hjoin
                  left join item on item.itemid=stock.itemid
                  left join client on client.client=head.client
                  left join transnum on transnum.trno=head.trno
                  where stock.void=0 and stock.isreturn=0 and (stock.qty-stock.qa)>0 $filter $filter1 $datefilter";
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeadertable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER NAME', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AGE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ORDERED', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SERVED', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTIAL DELIVERY DATE', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
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
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PENDING PURCHASE ORDERS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($client == '') {
      $cus = 'ALL';
    } else {
      $cus = $client;
    }
    if ($barcode == '') {
      $item = 'ALL';
    } else {
      $item = $barcode;
    }
    if ($groupid == '') {
      $group = 'ALL';
    } else {
      $group = $groupid;
    }
    if ($brandid == '') {
      $brand = 'ALL';
    } else {
      $brand = $brandid;
    }
    if ($classid == '') {
      $class = 'ALL';
    } else {
      $class = $classid;
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier : ' . strtoupper($cus), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($item), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Group :' . strtoupper($group), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brand), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . strtoupper($class), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    switch ($companyid) {
      case 21: //kinggeorge
        break;
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('Project : ' . $projname, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        break;
      default:
        $filtercenter = $config['params']['dataparams']['center'];
        $str .= $this->reporter->col('Center : ' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        break;
    }

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($companyid == 8) { //maxipro
      $projectname = $config['params']['dataparams']['dprojectname'];
      $subproject  = $config['params']['dataparams']['subprojectname'];

      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subproject == '') {
        $subproject = 'ALL';
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projectname, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub-Project : ' . $subproject, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->printline();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $companyid = $config['params']['companyid'];

    $count = 31;
    $page = 30;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->default_displayHeadertable($config);

    $item = null;
    $partial = '';

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();


      $display = $data->itemname;
      $docno = $data->docno;
      $date = $data->dateid;
      $age = $data->age;
      $served = $data->qa;

      switch ($companyid) {
        case 21: //kinggeorge
          $bal = $data->pounserved;
          $uom = $data->pouom;
          $order = $data->poqty;
          break;
        default:
          $bal = $data->unserved;
          $uom = $data->uom;
          $order = $data->qty;
          break;
      }

      if (isset($data->partialdate)) {
        $partial = $data->partialdate;
      }
      if ($partial != '') {
        $partial = date("d-M-y", strtotime($partial));
      }

      $str .= $this->reporter->startrow();
      if ($item == $data->clientname) {
      } else {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '1000', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($display, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($date, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($age, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($order, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($served, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($bal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($uom, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($partial, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $item = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader($config);
        $str .= $this->default_displayHeadertable($config);
        $page += $count;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  
  private function ericco_displayHeadertable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM CODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM DESCRIPTION', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AGE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ORDERED', '85', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SERVED', '85', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '85', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTIAL DELIVERY DATE', '115', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function ericco_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $client     = $config['params']['dataparams']['client'];
    $classid    = $config['params']['dataparams']['classid'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $posttype   = $config['params']['dataparams']['posttype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
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
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PENDING PURCHASE ORDERS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($client == '') {
      $cus = 'ALL';
    } else {
      $cus = $client;
    }
    if ($barcode == '') {
      $item = 'ALL';
    } else {
      $item = $barcode;
    }
    if ($groupid == '') {
      $group = 'ALL';
    } else {
      $group = $groupid;
    }
    if ($brandid == '') {
      $brand = 'ALL';
    } else {
      $brand = $brandid;
    }
    if ($classid == '') {
      $class = 'ALL';
    } else {
      $class = $classid;
    }

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier : ' . strtoupper($cus), NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Item : ' . strtoupper($item), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Group :' . strtoupper($group), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Brand : ' . strtoupper($brand), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . strtoupper($class), null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    switch ($companyid) {
      case 21: //kinggeorge
        break;
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('Project : ' . $projname, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        break;
      default:
        $filtercenter = $config['params']['dataparams']['center'];
        $str .= $this->reporter->col('Center : ' . $filtercenter, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        break;
    }

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($companyid == 8) { //maxipro
      $projectname = $config['params']['dataparams']['dprojectname'];
      $subproject  = $config['params']['dataparams']['subprojectname'];

      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subproject == '') {
        $subproject = 'ALL';
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projectname, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub-Project : ' . $subproject, NULL, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->printline();
    return $str;
  }

  public function ericco_Layout($config)
  {
    $result = $this->report_ericco($config);

    $companyid = $config['params']['companyid'];

    $count = 31;
    $page = 30;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->ericco_displayHeader($config);
    $str .= $this->ericco_displayHeadertable($config);

    $item = null;
    $partial = '';

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      $code = $data->barcode;
      $display = $data->itemname;
      $docno = $data->docno;
      $date = $data->dateid;
      $age = $data->age;
      $served = $data->qa;

      $bal = $data->unserved;
      $uom = $data->uom;
      $order = $data->qty;

      if (isset($data->partialdate)) {
        $partial = $data->partialdate;
      }
      if ($partial != '') {
        $partial = date("d-M-y", strtotime($partial));
      }

      $str .= $this->reporter->startrow();
      if ($item == $data->clientname) {
      } else {
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->col($data->clientname, '1000', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->col('', '115', null, false, $border, '', 'C', $font, $fontsize, '', '', '5px');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($code, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($display, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($docno, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($date, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($age, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($order, 2), '85', null, false, $border, '', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($served, 2), '85', null, false, $border, '', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($bal, 2), '85', null, false, $border, '', 'RT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($uom, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($partial, '115', null, false, $border, '', 'CT', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $item = $data->clientname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->ericco_displayHeader($config);
        $str .= $this->ericco_displayHeadertable($config);
        $page += $count;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}
