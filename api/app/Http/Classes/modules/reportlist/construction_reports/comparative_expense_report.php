<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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
use Illuminate\Support\Facades\URL;

class comparative_expense_report
{
  public $modulename = 'Comparative Expense Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

    if ($companyid == 8) { //maxipro
      array_push($fields, 'costcenter', 'radioposttype', 'year');
    } else {
      array_push($fields, 'radioposttype', 'year');
    }
    $col1 = $this->fieldClass->create($fields);

    data_set(
      $col1,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $addparams = '';
    if ($companyid == 8) { //maxipro
      $addparams = " , '' as costcenter,'' as code ,'' as name";
    }
    $paramstr = "select 
      'default' as print,
      left(now(),4) as year,
      '2' as posttype" . $addparams;

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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1': // UNPOSTED
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      case  '2': // ALL
        $query = $this->reportDefault_ALL($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter = '';
    $selecthjc = '';
    if ($companyid == 8) { //maxipro
      $costcenter = $config['params']['dataparams']['code'];
      if ($costcenter != "") {
        $costcenterid = $config['params']['dataparams']['costcenterid'];
        $filter = " and head.projectid = '" . $costcenterid . "' ";
      }
      $selecthjc = " union all select (case when coa.levelid = 2 then '' else parent.acnoname end) as parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join gldetail as detail on detail.acnoid=coa.acnoid
          left join hjchead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname ";
    }

    $query = "select parent,acno,acnoname,sum(prevyr) as prevyr,sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,sum(moapr) as moapr,
              sum(momay) as momay,sum(mojun) as mojun,sum(mojul) as mojul,sum(moaug) as moaug,sum(mosep) as mosep, sum(mooct) as mooct,
              sum(monov) as monov, sum(modec) as modec from (select (case when coa.levelid = 2 then '' else parent.acnoname end) as parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join gldetail as detail on detail.acnoid=coa.acnoid
          left join glhead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname  $selecthjc) as k
          group by parent,acno,acnoname
          order by parent,acnoname";

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $selectjc = '';
    if ($companyid == 8) { //maxipro
      $costcenter = $config['params']['dataparams']['code'];
      if ($costcenter != "") {
        $costcenterid = $config['params']['dataparams']['costcenterid'];
        $filter = " and head.projectid = '" . $costcenterid . "' ";
      }
      $selectjc = " union all select (case when coa.levelid = 2 then '' else parent.acnoname end) as parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join ladetail as detail on detail.acnoid=coa.acnoid
          left join jchead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname ";
    }

    $query = "select parent,acno,acnoname,sum(prevyr) as prevyr,sum(mojan) as mojan, sum(mofeb) as mofeb, sum(momar) as momar,sum(moapr) as moapr,
              sum(momay) as momay,sum(mojun) as mojun,sum(mojul) as mojul,sum(moaug) as moaug,sum(mosep) as mosep, sum(mooct) as mooct,
              sum(monov) as monov, sum(modec) as modec from ( select (case when coa.levelid = 2 then '' else parent.acnoname end) as parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join ladetail as detail on detail.acnoid=coa.acnoid
          left join lahead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname $selectjc) as k
          group by parent,acno,acnoname
          order by parent,acnoname";

    return $query;
  }

  public function reportDefault_ALL($config)
  {
    $year       = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter = '';
    $selectjc = '';
    $selecthjc = '';

    if ($companyid == 8) { //maxipro
      $costcenter = $config['params']['dataparams']['code'];
      if ($costcenter != "") {
        $costcenterid = $config['params']['dataparams']['costcenterid'];
        $filter = " and head.projectid = '" . $costcenterid . "' ";
      }
      $selectjc = " union all select (case when coa.levelID = 2 then '' else parent.acnoname end) as Parent,coa.acno, coa.acnoname,
                    (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                    sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                    sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                    sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                    sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                    sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                    sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                    sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                    sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                    sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
              from (coa left join ladetail as detail on detail.acnoid=coa.acnoid
              left join jchead as head on head.trno=detail.trno)
              left join coa as parent on parent.acno=coa.parent
              where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
              group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname ";
      $selecthjc = " union all  select (case when coa.levelID = 2 then '' else parent.acnoname end) AS Parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join gldetail as detail on detail.acnoid=coa.acnoid
          left join hjchead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname ";
    }

    $query = "select parent,acno,acnoname,sum(prevyr) as prevyr,sum(mojan) as mojan,sum(mofeb) as mofeb,sum(momar) as momar,sum(moapr) as moapr,sum(momay) as momay,
       sum(mojun) mojun,sum(mojul) as mojul,sum(moaug) as moaug,sum(mosep) as mosep,sum(mooct) as mooct,sum(monov) as monov,sum(modec) as modec
          from (select (case when coa.levelID = 2 then '' else parent.acnoname end) as Parent,coa.acno, coa.acnoname,
                    (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                    sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                    sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                    sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                    sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                    sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                    sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                    sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                    sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                    sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                    sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                    sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                    sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
              from (coa left join ladetail as detail on detail.acnoid=coa.acnoid
              left join lahead as head on head.trno=detail.trno)
              left join coa as parent on parent.acno=coa.parent
              where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
              group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname $selectjc
          union all
          select (case when coa.levelID = 2 then '' else parent.acnoname end) AS Parent,coa.acno, coa.acnoname,
                (sum(case when year(head.dateid)=($year-1) then (detail.db-detail.cr) else 0 end) / 12) as prevyr,
                sum(case when month(head.dateid)=1 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojan,
                sum(case when month(head.dateid)=2 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mofeb,
                sum(case when month(head.dateid)=3 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momar,
                sum(case when month(head.dateid)=4 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moapr,
                sum(case when month(head.dateid)=5 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as momay,
                sum(case when month(head.dateid)=6 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojun,
                sum(case when month(head.dateid)=7 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mojul,
                sum(case when month(head.dateid)=8 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as moaug,
                sum(case when month(head.dateid)=9 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mosep,
                sum(case when month(head.dateid)=10 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as mooct,
                sum(case when month(head.dateid)=11 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as monov,
                sum(case when month(head.dateid)=12 and year(head.dateid)=$year then (detail.db-detail.cr) else 0 end) as modec
          from (coa left join gldetail as detail on detail.acnoid=coa.acnoid
          left join glhead as head on head.trno=detail.trno)
          left join coa as parent on parent.acno=coa.parent
          where coa.cat = 'E' and coa.detail= 1 and (year(head.dateid)=$year or year(head.dateid)=($year-1)) " . $filter . "
          group by coa.acno, coa.acnoname,coa.levelid,parent.acnoname $selecthjc)  as a
          group by parent,acno,acnoname
      order by parent,acnoname";
    return $query;
  }


  private function default_displayHeader($config)
  {
    $mdc = URL::to('/images/reports/mdc.jpg');
    $tuv = URL::to('/images/reports/tuv.jpg');
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];

    $str = '';
    $layoutsize = '1200';
    $font = "Arial Narrow";
    $fontsize = "10";
    $border = "1px solid ";



    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->generateReportHeader($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= "<div style='position:absolute; top: 60px;'>";
    $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px" style="margin-left: 100px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('<img src ="' . $tuv . '" alt="TUV" width="140px" height ="70px" style="margin-left: 750px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= "</div>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPARATIVE EXPENSE REPORT - ' . $year, '1000', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->col('Print Date : ' . date('M-d-Y h:i:s a', time()), '200', null, false, $border, '', 'R', $font, '13', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(
      '200',
      null,
      false,
      $border,
      '',
      'C',
      $font,
      $fontsize,
      '',
      'b',
      ''
    );


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


    $str .= $this->reporter->col(
      'Transaction : ' . strtoupper($posttype),
      '400',
      null,
      false,
      $border,
      '',
      'L',
      $font,
      '10',
      '',
      'b',
      ''
    );

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(
      '200',
      null,
      false,
      $border,
      '',
      'C',
      $font,
      $fontsize,
      '',
      'b',
      ''
    );

    if ($companyid == 8) { //maxipro
      $costcenter = $config['params']['dataparams']['code'];
      $projname = $config['params']['dataparams']['name'];
      if (empty($costcenter)) {
        $proj = 'ALL';
      } else {
        $proj = $costcenter . ' ~ ' . $projname;
      }

      $str .= $this->reporter->col(
        'Project : ' . strtoupper($proj),
        '400',
        null,
        false,
        $border,
        '',
        'L',
        $font,
        '10',
        '',
        'b',
        ''
      );

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACNONAME', '150', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prev. Year Average', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JAN', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FEB', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAR', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APR', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MAY', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUN', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('JUL', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AUG', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SEP', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OCT', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOV', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DEC', '75', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '87', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AVERAGE', '88', '', '', $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $posttype     = $config['params']['dataparams']['posttype'];
    $year         = $config['params']['dataparams']['year'];

    $count = 36;
    $page = 38;

    $str = '';
    $layoutsize = '1200';
    $font = "Arial Narrow";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalprevyr = 0;
    $totalmojan = 0;
    $totalmofeb = 0;
    $totalmomar = 0;
    $totalmoapr = 0;
    $totalmomay = 0;
    $totalmojun = 0;
    $totalmojul = 0;
    $totalmoaug = 0;
    $totalmosep = 0;
    $totalmooct = 0;
    $totalmonov = 0;
    $totalmodec = 0;
    $amt = 0;
    $totalamt = 0;
    $totalave = 0;
    $parent = '';
    $subprevyr = 0;
    $submojan = 0;
    $submofeb = 0;
    $submomar = 0;
    $submoapr = 0;
    $submomay = 0;
    $submojun = 0;
    $submojul = 0;
    $submoaug = 0;
    $submosep = 0;
    $submooct = 0;
    $submonov = 0;
    $submodec = 0;
    $subtotal = 0;
    $subave = 0;
    foreach ($result as $key => $data) {
      $amt = $data->mojan + $data->mofeb + $data->momar + $data->moapr + $data->momay + $data->mojun + $data->mojul + $data->moaug + $data->mosep + $data->mooct + $data->monov + $data->modec;

      if ($parent != $data->parent) {
        if ($parent != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($parent . ' Total : ', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($subprevyr == 0 ? '-' : number_format($subprevyr, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submojan == 0 ? '-' : number_format($submojan, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submofeb == 0 ? '-' : number_format($submofeb, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submomar == 0 ? '-' : number_format($submomar, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submoapr == 0 ? '-' : number_format($submoapr, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submomay == 0 ? '-' : number_format($submomay, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submojun == 0 ? '-' : number_format($submojun, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submojul == 0 ? '-' : number_format($submojul, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submoaug == 0 ? '-' : number_format($submoaug, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submosep == 0 ? '-' : number_format($submosep, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submooct == 0 ? '-' : number_format($submooct, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submonov == 0 ? '-' : number_format($submonov, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($submodec == 0 ? '-' : number_format($submodec, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($subtotal == 0 ? '-' : number_format($subtotal, 2), '87', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($subave == 0 ? '-' : number_format($subave, 2), '88', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $subprevyr = 0;
          $submojan = 0;
          $submofeb = 0;
          $submomar = 0;
          $submoapr = 0;
          $submomay = 0;
          $submojun = 0;
          $submojul = 0;
          $submoaug = 0;
          $submosep = 0;
          $submooct = 0;
          $submonov = 0;
          $submodec = 0;
          $subtotal = 0;
          $subave = 0;
        }
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acnoname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->prevyr == 0 ? '-' : number_format($data->prevyr, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mojan == 0 ? '-' : number_format($data->mojan, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mofeb == 0 ? '-' : number_format($data->mofeb, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->momar == 0 ? '-' : number_format($data->momar, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->moapr == 0 ? '-' : number_format($data->moapr, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->momay == 0 ? '-' : number_format($data->momay, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mojun == 0 ? '-' : number_format($data->mojun, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mojul == 0 ? '-' : number_format($data->mojul, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->moaug == 0 ? '-' : number_format($data->moaug, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mosep == 0 ? '-' : number_format($data->mosep, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->mooct == 0 ? '-' : number_format($data->mooct, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->monov == 0 ? '-' : number_format($data->monov, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->modec == 0 ? '-' : number_format($data->modec, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '87', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $currdate = $this->othersClass->getCurrentTimeStamp();
      $curryear = date('Y', strtotime($currdate));

      if ($year == $curryear) {
        $average = $amt / 12;
      } else {
        if ($year > $curryear) {
          $average = 0;
        } else {
          $average = $amt / 12;
        }
      }
      $str .= $this->reporter->col(number_format($average, 2), '88', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $subprevyr += $data->prevyr;
      $submojan += $data->mojan;
      $submofeb += $data->mofeb;
      $submomar += $data->momar;
      $submoapr += $data->moapr;
      $submomay += $data->momay;
      $submojun += $data->mojun;
      $submojul += $data->mojul;
      $submoaug += $data->moaug;
      $submosep += $data->mosep;
      $submooct += $data->mooct;
      $submonov += $data->monov;
      $submodec += $data->modec;
      $subtotal += $amt;
      $subave += $average;


      $parent = $data->parent;

      $totalprevyr += $data->prevyr;
      $totalmojan += $data->mojan;
      $totalmofeb += $data->mofeb;
      $totalmomar += $data->momar;
      $totalmoapr += $data->moapr;
      $totalmomay += $data->momay;
      $totalmojun += $data->mojun;
      $totalmojul += $data->mojul;
      $totalmoaug += $data->moaug;
      $totalmosep += $data->mosep;
      $totalmooct += $data->mooct;
      $totalmonov += $data->monov;
      $totalmodec += $data->modec;
      $totalamt += $amt;
      $totalave += $average;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($parent . ' Total : ', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($subprevyr == 0 ? '-' : number_format($subprevyr, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submojan == 0 ? '-' : number_format($submojan, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submofeb == 0 ? '-' : number_format($submofeb, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submomar == 0 ? '-' : number_format($submomar, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submoapr == 0 ? '-' : number_format($submoapr, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submomay == 0 ? '-' : number_format($submomay, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submojun == 0 ? '-' : number_format($submojun, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submojul == 0 ? '-' : number_format($submojul, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submoaug == 0 ? '-' : number_format($submoaug, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submosep == 0 ? '-' : number_format($submosep, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submooct == 0 ? '-' : number_format($submooct, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submonov == 0 ? '-' : number_format($submonov, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($submodec == 0 ? '-' : number_format($submodec, 2), '75', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($subtotal == 0 ? '-' : number_format($subtotal, 2), '87', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($subave == 0 ? '-' : number_format($subave, 2), '88', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL :', '120', null, false, $border, 'TB', 'R', 'Century Gothic', '9', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalprevyr, 2), '75', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojan, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmofeb, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomar, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoapr, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmomay, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojun, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmojul, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmoaug, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmosep, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmooct, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmonov, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalmodec, 2), '65', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '87', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalave, 2), '88', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }


  private function generateReportHeader($center, $username)
  {
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    return $str;
  } //end function generate report header


}//end class