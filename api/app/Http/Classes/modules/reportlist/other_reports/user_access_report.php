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

class user_access_report
{
  public $modulename = 'User Access Report';
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
    $fields = ['radioprint', 'reportusers', 'daccesslist'];

    if ($companyid == 10) { //afti
      array_push($fields, 'radioreporttype');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'reportusers.required', true);
    data_set($col1, 'reportusers.lookupclass', 'useraccess');
    data_set($col1, 'daccesslist.required', false);

    if ($companyid == 8) { //maxipro
      data_set($col1, 'reportusers.label', 'Level');
    }
    if ($companyid == 10) { //afti
      data_set(
        $col1,
        'radioreporttype.options',
        array(
          ['label' => 'Allowed', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Not Allowed', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
        )
      );
    }

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as userid,
    '' as username,
    '' as accesscode,
    '' as accessname,
    '' as reportusers,
    '' as daccesslist,
    '2' as reporttype
    ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $code = $config['params']['dataparams']['accesscode'];
    $idno = $config['params']['dataparams']['userid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";

    if ($idno != '') {
      $filter = $filter2 = $filter3 .= " and moduleaccess.idno='$idno'";
      $filter1 .= "  and attr1.levelid = $idno ";
    }

    if ($code != '') {
      $code = '\\' . $code;
      $filter .= " and attributes.parent='$code'";
      $filter1 .= " and (attr1.parent='$code' or attr1.code='$code')";
      $filter2 .= " and attr2.parent='$code'";
      $filter3 .= " and attr2.code='$code'";
    }

    switch ($reporttype) {
      case '0': //allowed
        $query = "select 'ALLOWED' as status, attributes.attribute,attributes.description,attributes.code,  attributes.parent from attributes
      left join moduleaccess on moduleaccess.attribute=attributes.attribute and moduleaccess.idno=attributes.levelid
      where 1=1 $filter and attributes.parent <> '\\\'
      and attributes.parentid=0 and attributes.allowed <> 1";
        break;
      case '1': //not allowed
        $query = " select 'NOT-ALLOWED' as status, attr1.attribute,attr1.description,attr1.code, attr1.parent 
      from attributes as attr1 where 1=1 $filter1 and attr1.parent <> '\\\'
      and attr1.parentid=0 and attr1.attribute
      not in (select attr2.attribute from attributes as attr2
      left join moduleaccess on moduleaccess.attribute=attr2.attribute and moduleaccess.idno=attr2.levelid
      where 1=1 $filter2 and attr2.parentid=0
      and attr2.allowed <> 1
      union all
      select attr2.attribute from attributes as attr2
      left join moduleaccess on moduleaccess.attribute=attr2.attribute and moduleaccess.idno=attr2.levelid
      where 1=1 $filter3
      and attr2.parentid=0 and attr2.allowed <> 1) order by code";
        break;
      default:
        $query = "
    select 'ALLOWED' as status, attributes.attribute,attributes.description,attributes.code,  attributes.parent from attributes
      left join moduleaccess on moduleaccess.attribute=attributes.attribute and moduleaccess.idno=attributes.levelid
      where 1=1 $filter and attributes.parent <> '\\\'
      and attributes.parentid=0 and attributes.allowed <> 1
    union all
    select 'NOT-ALLOWED' as status, attr1.attribute,attr1.description,attr1.code, attr1.parent 
      from attributes as attr1 where 1=1 $filter1 and attr1.parent <> '\\\'
      and attr1.parentid=0 and attr1.attribute
      not in (select attr2.attribute from attributes as attr2
      left join moduleaccess on moduleaccess.attribute=attr2.attribute and moduleaccess.idno=attr2.levelid
      where 1=1 $filter2 and attr2.parentid=0
      and attr2.allowed <> 1
      union all
      select attr2.attribute from attributes as attr2
      left join moduleaccess on moduleaccess.attribute=attr2.attribute and moduleaccess.idno=attr2.levelid
      where 1=1 $filter3
      and attr2.parentid=0 and attr2.allowed <> 1) order by code";
        break;
    }
    $this->coreFunctions->LogConsole($query);
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $idno       = $config['params']['dataparams']['userid'];
    $userlevel  = $config['params']['dataparams']['username'];
    $accessname  = $config['params']['dataparams']['accessname'];

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

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('USER ACCESS REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $qry = "SELECT * FROM useraccess WHERE accessid = '\\" . $idno . "'";
    $result = $this->coreFunctions->opentable($qry);

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($userlevel . ' :', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');

    if ($accessname == "") {
      $accessname = "ALL";
    }

    $str .= $this->reporter->col('MODULE : ' . strtoupper($accessname), '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    foreach ($result as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->name, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '', '');
      $str .= $this->reporter->col('', '900', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('STATUS', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('CODE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result     = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $idno = $config['params']['dataparams']['userid'];
    $companyid = $config['params']['companyid'];

    $count = 34;
    $page = 33;
    $this->reporter->linecounter = 0;

    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $filter = "";
    if ($idno != '') {
      $filter = "  and levelid = $idno ";
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $value) {
      $str .= $this->reporter->addline();
      if ($value->parent == '\\') {
        $status = '-';
      } else {
        $status = $value->status;
      }
      $parent = '\\' . $value->parent;
      $qry = "select * from attributes where code = '$parent' " . $filter;

      $indent = $this->coreFunctions->opentable($qry);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($status, '150', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '', '');
      if ($value->parent == '\\') {
        $str .= $this->reporter->col($value->description, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
      }

      foreach ($indent as $k => $v) {
        if ($v->code == $value->parent && $v->parent == '\\') {
          $str .= $this->reporter->col($value->description, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
        } else {
          $str .= $this->reporter->col($value->description, '400', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '', '');
        }
      }

      $str .= $this->reporter->col($value->code, '150', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '', '');
      $str .= $this->reporter->endrow();

      if ($companyid != 10) { //afti
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->reporter->begintable($layoutsize);
         // $str .= $this->displayHeader($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end classs