<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class profit_and_loss_per_department
{
  public $modulename = 'Profit and Loss per Department';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $xdept = [];
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
    $fields = ['radioprint', 'start', 'end'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['project', 'branchname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.name', "projectname");
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'project.required', false);
    data_set($col2, 'branchname.style', '');
    data_set($col2, 'branchname.lookupclass', 'branch');
    data_set($col2, 'branchname.action', 'lookupclient');
    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
     adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`,
    0 as clientid,
    '' as client,
    '' as clientname,
    '' as branchname,
    '' as project,
    '' as projectcode,
    0 as projectid,
    '' as projectname
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $result = $this->default_layout($config); // $this->default_query
    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->default_query($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_query_tree($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $branchname = $config['params']['dataparams']['client'];
    $project    = $config['params']['dataparams']['project'];

    $filter = "";
    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filter .= " and detail.projectid=" . $projectid;
    }
    if ($branchname != "") {
      $branch = $config['params']['dataparams']['clientid'];
      $filter .= " and detail.branch=" . $branch;
    }

    $query2 = "select '' as acno,'' as acnoname,0 as levelid,'' as cat,'' as parent,0 as detail";
    $query = "select clientid, client, clientname from client where isdepartment =1  order by clientname";
    $depts = $this->coreFunctions->opentable($query);
    $deptqry = "";
    $deptarray = [];
    foreach ($depts as $key => $data) {
      if ($deptqry == "") {
        $deptqry = "," . $depts[$key]->clientid;
      } else {
        $deptqry = $deptqry . "," . $depts[$key]->clientid;
      }
      $deptarray[$depts[$key]->clientid] = 0;
    }

    $result = $this->coreFunctions->opentable($query2 . $deptqry);
    $coa = json_decode(json_encode($result), true); // for convert to array

    $rdept = $deptarray;
    $gdept = $deptarray;
    $edept = $deptarray;
    $odept = $deptarray;

    $this->PLANTTREE($coa, '\\\\', 'R', $start, $end, $branch, $projectid, $rdept, $filter, $depts);
    $this->PLANTTREE($coa, '\\\\', 'G', $start, $end, $branch, $projectid, $gdept, $filter, $depts);
    $this->PLANTTREE($coa, '\\\\', 'E', $start, $end, $branch, $projectid, $edept, $filter, $depts);
    $this->PLANTTREE($coa, '\\\\', 'O', $start, $end, $branch, $projectid, $odept, $filter, $depts);

    $coa[] = array('acno' => '//4999', 'acnoname' => 'NET INCOME', 'levelid' => 1, 'cat' => 'X', 'parent' => 'X', 'detail' => 2);
    foreach ($depts as $key => $data) {
      $coa[$data[$key]->clientid] = $rdept[$data[$key]->clientid] - $edept[$data[$key]->clientid] - $odept[$data[$key]->clientid];
    }
    $array = json_decode(json_encode($coa), true);
    return $array;
  }

  public function default_query($config)
  {
    $query = "select 0 as clientid,'' as client,'' as clientname union all select clientid, client, clientname from client where isdepartment =1  order by clientname";
    return $query;
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

  public function default_header($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $companyid  = $config['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $branchname  = $config['params']['dataparams']['branchname'];
    $projectname  = $config['params']['dataparams']['projectname'];

    $str = "";
    $layoutsize = '800';
    $font =  "cambria";
    // $fontsize = "10";
    $border = "1px solid ";
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '800', null, false, $border, '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As Of ' . date('F d, Y', strtotime($end)), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Project: " . ($projectname == '' ? "ALL" : $projectname), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Branch: " . ($branchname == '' ? 'ALL' : $branchname), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    return $str;
  }

  private function default_layout_tree($params, $data)
  {
    // $border = '1px solid';
    // $border_line = '';
    // $alignment = '';
    $font = 'cambria';
    // $font_size = '10';
    // $padding = '';
    // $margin = '';

    // $center     = $params['params']['center'];
    // $username   = $params['params']['user'];
    // $companyid  = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $start      = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    // $branch = $params['params']['dataparams']['clientid'];
    // $projectid = $params['params']['dataparams']['projectid'];

    $count = 57;
    $page = 57;
    $str = '';

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_HEADER($params);

    $query = "select clientid, client, clientname from client where isdepartment =1  order by clientname";
    $dt = $this->coreFunctions->opentable($query);

    foreach ($dt as $k => $value) {
      for ($i = 0; $i < count($data); $i++) {

        $lineTotal = 0;
        $bold = '';

        if ($data[$i]['detail'] == 1 and $data[$i][$dt[$k]->clientid] == 0) {
        } else {

          if ($data[$i]['acnoname'] != '') {

            $indent = '5' * ($data[$i]['levelid'] * 3);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            if ($data[$i]['detail'] == 2) {
              $bold = 'B';
            }
            $str .= $this->reporter->col($data[$i]['acnoname'], '280', null, false, '1px solid ', '', '', $font, '12', $bold, '', '0px 0px 0px ' . $indent . 'px');

            if ($data[$i]['detail'] != 0) {
              if ($data[$i][$dt[$k]->clientid] == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font,  $bold, '', '');
              } else {
                $str .= $this->reporter->col(number_format($data[$i][$dt[$k]->clientid], 2), '90', null, false, '1px solid ', '', 'R', $font, '12', $bold, '', '');
              }

              $lineTotal = $data[$i][$dt[$k]->clientid];
              if ($lineTotal == 0) {
                $str .= $this->reporter->col('-', '90', null, false, '1px solid ', '', 'R', $font, '12', $bold, '', '');
              } else {
                $str .= $this->reporter->col(number_format($lineTotal, 2), '90', null, false, '1px solid ', '', 'R', $font, '12', $bold, '', '');
              }
            }

            $str .= $this->reporter->endrow();
          }
        }

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_HEADER($params, $data);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function default_layout($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $companyid  = $config['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $config['params']);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $project = $config['params']['dataparams']['project'];
    $branch = $config['params']['dataparams']['branchname'];
    // $count = 38;
    // $page = 38;

    $str = '';
    $layoutsize = '800';
    $font =  "cambria";
    // $fontstyle = "";
    $fontsize = "10";
    $border = "1px solid ";
    $b = "";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config);
    // $totalamt = 0;
    // $grandtotal = 0;
    // $prjname = "";
    // $acnoname = "";
    // $pacnoname = "";

    // $cols = "";
    // $acnos = "";
    // $gtotalcols = "";
    $headerwidth = count($result) * 200;
    $wpercol = $headerwidth / count($result);
    $counter = count($result);

    //plot departments
    $str .= $this->reporter->begintable($headerwidth);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(($data->clientname == '' ? 'No Dept.' : $data->clientname), $wpercol, null, false, $border, $b, 'C', $font, $fontsize, 'B', '', '');
      $xdept[$key] = $data->clientid . "~" . $data->clientname;
    }
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, $b, 'R', $font, $fontsize, 'B', '', '');
    //end plotting dept

    $filter = "";
    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filter .= " and detail.projectid=". $projectid;
    }
    if ($branch != "") {
      $branchid = $config['params']['dataparams']['clientid'];
      $filter .= " and detail.branch=". $branchid;
    }

    $str .= $this->plotting('R', $xdept, $start, $end, $filter, $counter, $wpercol);
    $str .= $this->plotting('E', $xdept, $start, $end, $filter, $counter, $wpercol);
    $str .= $this->plotting('C', $xdept, $start, $end, $filter, $counter, $wpercol);

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function getaccqry($acnoid, $start, $end, $filter, $addfilters = '')
  {
    $accqry = "select acnoid,acno, acnoname, levelid, cat, parent, detail, case when cat ='E' then sum(db-cr) else sum(cr-db) end as amt,ifnull(deptname,'') as deptname,ifnull(deptid,0) as deptid
    from (
    select coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,ifnull(sum(tb.db),0)as db , ifnull(sum(tb.cr),0) as cr,tb.deptid,tb.deptname
    from coa left join (
    select detail.acnoid,sum(detail.db) as db,sum(detail.cr) as cr,detail.deptid,d.clientname as deptname from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join client as d on d.clientid = detail.deptid
    left join cntnum on cntnum.trno=head.trno
    where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
    group by detail.acnoid, head.dateid,detail.deptid,d.clientname
    ) as tb on tb.acnoid=coa.acnoid where coa.acnoid =" . $acnoid . $addfilters . " 
    group by coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,tb.deptid,tb.deptname
    ) as inc group by acnoid,acno, acnoname, levelid, cat, parent, detail,inc.deptname,inc.deptid";
    return $accqry;
  }

  private function getsumaccqry($cat, $start, $end, $filter, $addfilters = '')
  {
    $accqry = "select case when cat ='E' then sum(db-cr) else sum(cr-db) end as amt,ifnull(deptname,'') as deptname,ifnull(deptid,0) as deptid
    from (
    select coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,ifnull(sum(tb.db),0)as db , ifnull(sum(tb.cr),0) as cr,ifnull(tb.deptid,0) as deptid,ifnull(tb.deptname,'') as deptname
    from coa left join (
    select detail.acnoid,sum(detail.db) as db,sum(detail.cr) as cr,ifnull(detail.deptid,0) as deptid,ifnull(d.clientname,'') as deptname from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join client as d on d.clientid = detail.deptid
    left join cntnum on cntnum.trno=head.trno
    where head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
    group by detail.acnoid, head.dateid,detail.deptid,d.clientname
    ) as tb on tb.acnoid=coa.acnoid where coa.cat ='" . $cat . "' " . $addfilters . " 
    group by coa.acnoid,coa.acno, coa.acnoname, coa.levelid, coa.cat, coa.parent, coa.detail,tb.deptid,tb.deptname
    ) as inc group by inc.cat, inc.deptname,inc.deptid";
    return $accqry;
  }

  private function PLANTTREE(&$a, $acno, $cat, $start, $end, $branch, $projectid, &$deptval, $filter, $dt)
  {
    foreach ($dt as $key2 => $depts) { //loop per dept
      if ($projectid != 0) {
        $filter .= " and detail.projectid = " . $projectid;
      }

      $filter .= " and detail.branch = " . $dt[$key2]->clientid;
      $query2 = $this->getaccqry($cat, $start, $end, $filter);
      $data = $this->coreFunctions->opentable($query2);
      $result2 = json_decode(json_encode($data), true);
      $oldacno = '';
      $key = '';

      for ($b = 0; $b < count($result2); $b++) { //loop per trans
        if ($oldacno == '' || $oldacno != $result2[$b]['acno']) {
          $a[] = array(
            'acno' => $result2[$b]['acno'], 'acnoname' => $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'], 'cat' => $result2[$b]['cat'],
            'parent' => $result2[$b]['parent'], 'detail' => $result2[$b]['detail']
          );
          $a[$dt[$key2]->clientid] = number_format((float)$result2[$b]['amt'], 2, '.', '');
          $oldacno = $result2[$b]['acno'];
        } else {
          $key = array_search($result2[$b]['acno'], array_column($a, 'acno'));
          $a[$key][$dt[$key2]->clientid] = $a[$key][$dt[$key2]->clientid] + number_format((float)$result2[$b]['amt'], 2, '.', '');
        }

        $deptval[$dt[$key2]->clientid] = $deptval[$dt[$key2]->clientid] + number_format((float)$result2[$b]['amt'], 2, '.', '');

        if ($result2[$b]['detail'] == 0) {
          if ($this->PLANTTREE($a, '\\' . $result2[$b]['acno'], $result2[$b]['cat'], $start, $end, $branch, $projectid, $deptval, $filter, $dt)) {
            if ($result2[$b]['levelid'] > 1) {
              $a[] = array(
                'acno' => $result2[$b]['acno'], 'acnoname' => 'TOTAL ' . $result2[$b]['acnoname'], 'levelid' => $result2[$b]['levelid'],
                'cat' => $result2[$b]['cat'], 'parent' => $result2[$b]['parent'], 'detail' => 2
              );
              $a[$dt[$key2]->clientid] = $deptval[$dt[$key2]->clientid];
              $deptval[$dt[$key2]->clientid] = 0;
            }
          }
        }
      } //end b=0
    } //end dept

    if (count($result2) > 0) {
      return true;
    } else {
      return false;
    }
  }

  private function plotting($cat, $xdept, $start, $end, $filter, $counter, $wpercol)
  {
    $accounts = $this->coreFunctions->opentable("select acno,acnoid,acnoname,parent,detail,cat,levelid from coa where cat in ('" . $cat . "') order by acno");
    // $levelid = 0;
    $font =  "Century Gothic";
    $fontstyle = "";
    $fontsize = "10";
    $border = "1px solid ";
    $b = "";
    $acnoname = '';
    $str = '';

    foreach ($accounts as $k => $vaccount) {
      $fontstyle = '';
      if ($vaccount->detail == 0) {
        $fontstyle = "B";
        // $pacnoname = $vaccount->acnoname;
      }

      $indent = '5' * ($vaccount->levelid * 3);
      if ($acnoname != $vaccount->acnoname) {
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($vaccount->acnoname, '200', null, false, $border, $b, 'L', $font, $fontsize, $fontstyle, '', '0px 0px 0px ' . $indent . 'px');
      }
      $xx = 0;
      // $yy = 0;
      $rowtotal = 0;

      foreach ($xdept as $value) { // dept array
        $depts = explode("~", $value);
        $addfilters = " and tb.deptid = " . $depts[0];

        $accqry = $this->getaccqry($vaccount->acnoid, $start, $end, $filter, $addfilters);
        $trans = $this->coreFunctions->opentable($accqry);
        if (count($trans) == 0) {
          $str .= $this->reporter->col('-', $wpercol, null, false, $border, $b, 'R', $font, $fontsize, $fontstyle, '', '');
          $xx++;
        } else {
          foreach ($trans as $kk => $vvdata) {
            if ($vvdata->detail != 0) {
              if ($depts[0] == $vvdata->deptid) {
                $str .= $this->reporter->col(number_format($vvdata->amt, 2), $wpercol, null, false, $border, $b, 'R', $font, $fontsize, '', '', '');
                $rowtotal += $vvdata->amt;
                $xx++;
              }
            }
          }
        }
        if ($xx == $counter) {
          $str .= $this->reporter->col(number_format($rowtotal, 2), $wpercol, null, false, $border, $b, 'R', $font, $fontsize, '', '', '');
        }
      }
      // $levelid = $vaccount->levelid;
      $acnoname = $vaccount->acnoname;
    } //accounts

    $str .= $this->reporter->startrow();
    switch ($cat) {
      case 'R':
        $str .= $this->reporter->col('TOTAL REVENUE', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        break;
      case 'E':
        $str .= $this->reporter->col('TOTAL EXPENSES', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        break;
    }

    $rowtotal = 0;
    $xx = 0;

    foreach ($xdept as $value) { // dept array
      $depts = explode("~", $value);
      $addfilters = " and tb.deptid = " . $depts[0];
      $sumqry = $this->coreFunctions->opentable($this->getsumaccqry($cat, $start, $end, $filter, $addfilters));
      if (count($sumqry) == 0) {
        $str .= $this->reporter->col('-', $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, $fontstyle, '', '');
        $xx++;
      } else {
        foreach ($sumqry as $kk => $sumdata) {
          $str .= $this->reporter->col(number_format($sumdata->amt, 2), $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $rowtotal += $sumdata->amt;
          $xx++;
        }
      }

      if ($xx == $counter) {
        $str .= $this->reporter->col('GRAND TOTAL', 200, null, false, $border, 'T', 'l', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($rowtotal, 2), $wpercol, null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
      }
    }

    return $str;
  }
}//end class