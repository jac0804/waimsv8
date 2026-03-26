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

class purchases_detailed_report
{
  public $modulename = 'Purchase Detailed Report';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname'];

    switch ($companyid) {
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

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    // $companyid = $config['params']['companyid'];
    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,
              left(now(),10) as end, 
              '' as client, '' as dclientname, 
              0 as projectid, '' as dprojectname, '' as projectname, '' as projectcode, 
              '' as subprojectname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    return $this->reportDefaultLayout_DETAILED($config);;
  }

  public function reportDefault($config)
  {
    $query = $this->reportQuery_DETAILED($config);
    return $this->coreFunctions->opentable($query);
  }

  public function reportQuery_DETAILED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];

    $filter = '';
    if ($companyid == 8) { //maxipro
      $project = $config['params']['dataparams']['dprojectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];

      if ($project != '') {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter .= " and head.projectid = " . $projectid . "";
      }
      if ($subprojectname != '') {
        $filter .= " and head.subproject = '" . $subprojectname . "' ";
      }
    }

    if ($client != '') {
      $filter .= " and client.client = '$client'";
    }

    $query = "
    select barcode, itemname, uom, sum(tons) as tons, client, clientname, address, sum(isqty) as isqty, sum(cost) as cost, sum(ext) as ext from (
    select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,
    head.address,head.docno,client.client,client.clientname, agent.client as agent, agent.clientname as agentname,head.dateid,
    case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
    stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.cost from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join client as client on client.client = head.client
    left join client as agent on agent.client = head.agent
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
    where head.doc ='RR' and head.dateid between '$start' and '$end' $filter
    group by client.client,item.barcode,item.class,
    head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
    class,item.barcode,item.itemname,
    stock.uom,uom.kilos,client.area,stock.cost
    union all 
    select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,head.address,head.docno,client.client,client.clientname, agent.client as agent,agent.clientname as agentname,head.dateid,
    case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
    stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.cost from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join client as client on client.clientid = head.clientid
    left join client as agent on agent.clientid = head.agentid
    left join item on item.itemid = stock.itemid
    left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
    where head.doc ='RR' and head.dateid between '$start' and '$end' $filter
    group by client.client,item.barcode,item.class,
    head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
    class,item.barcode,item.itemname,
    stock.uom,uom.kilos,client.area,stock.cost) as a
    group by client, clientname, address, barcode, itemname, uom
    order by clientname, itemname
    ";
    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PURCHASE DETAILED', 800, null, false, $border, '', 'C', $font, '16', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), 800, null, false, $border, '', 'C', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);

    if ($client != '') {
      $client = $client;
    } else {
      $client = 'ALL';
    }

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Supplier : ' . $client, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    if ($companyid == 8) { //maxipro
      $project = $config['params']['dataparams']['dprojectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      if ($project == '') {
        $project = "ALL";
      }
      if ($subprojectname == '') {
        $subprojectname = "ALL";
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $project, 1000, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Project : ' . $subprojectname, 1000, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 300, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $client     = $config['params']['dataparams']['client'];

    $count = 48;
    $page = 50;

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
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DESCRIPTION', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('TOTAL COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $itemname = "";
    // $date = "";
    $docno = "";
    // $yourref = "";
    $totalext = 0;
    $totalqty = 0;
    $totalcost = 0;
    $totaltons = 0;
    $subtotalqty = 0;
    $subtotalcost = 0;
    $subtotalext = 0;
    $subtotalpv = 0;
    $subtotaltons = 0;
    $gsubtotalqty = 0;
    $gsubtotalext = 0;
    $gsubtotalcost = 0;
    // $gsubtotalpv = 0;
    // $member = "";
    // $grandtotalpv = 0;
    // $grandtotalqty = 0;
    $gsubtotaltons = 0;

    $iitem = "";
    foreach ($result as $key => $data) {
      $tons = $data->tons;

      if ($itemname == "") {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->client . '  ' . $data->clientname . '  ' . $data->address, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      if (strtoupper($itemname) == strtoupper($data->clientname)) {
        $itemname = "";
        if (strtoupper($docno) == strtoupper($data->itemname)) {
          $docno = "";
        } else {
          if ($docno != '') {
            $subtotalqty = 0;
            $subtotalext = 0;
          }
          $itemname = strtoupper($data->clientname);
        }
      } else {
        if ($docno != '') {
        }
        $str .= $this->reporter->begintable($layoutsize);
        if ($itemname != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(':', '350', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalcost, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        if ($itemname != '') {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col($data->client . '  ' . $data->clientname . '  ' . $data->address, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $subtotalqty = 0;
        $subtotalext = 0;
        $subtotaltons = 0;
        $subtotalcost = 0;

        $gsubtotalqty = 0;
        $gsubtotalext = 0;
        $gsubtotaltons = 0;
        $gsubtotalcost = 0;
        $docno = $data->clientname;

        if (strtoupper($docno) == strtoupper($data->itemname)) {
          $docno = "";
        } else {

          $docno = strtoupper($data->clientname);
        }
      }

      if ($iitem == $data->itemname) {
        $iitem = "";
      } else {
        $iitem = $data->itemname;
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $subtotalext = $subtotalext + $data->ext;
      $subtotalqty = $subtotalqty + $data->isqty;
      $subtotalcost = $subtotalcost + $data->cost;
      $subtotaltons = $subtotaltons + $tons;


      $gsubtotalext = $gsubtotalext + $data->ext;
      $gsubtotalqty = $gsubtotalqty + $data->isqty;
      $gsubtotalcost = $gsubtotalcost + $data->cost;
      $gsubtotaltons = $gsubtotaltons + $tons;

      $totaltons = $totaltons + $tons;
      $totalext = $totalext + $data->ext;
      $totalcost = $totalcost + $data->cost;
      $totalqty = $totalqty + $data->isqty;

      $itemname = strtoupper($data->clientname);
      $docno = $data->itemname;

      $iitem = $data->itemname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(':', '350', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gsubtotalcost, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= '<br/><br/><br/>';
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '350', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcost, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
