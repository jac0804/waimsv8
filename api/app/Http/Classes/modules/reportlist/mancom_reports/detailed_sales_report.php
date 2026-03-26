<?php

namespace App\Http\Classes\modules\reportlist\mancom_reports;

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

class detailed_sales_report
{
  public $modulename = 'Detailed Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'start', 'end', 'repitemgroup', 'dclientname', 'agentname', 'salesgroup', 'cur', 'industry'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'agentname.label', 'Sales Person');
    data_set($col1, 'cur.type', 'input');
    data_set($col1, 'cur.class', 'cscur');
    data_set($col1, 'cur.readonly', false);
    data_set($col1, 'cur.required', false);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    if ($companyid == 12) { //afti usd
      data_set($col1, 'cur.label', 'USD to SGD Rate');
    } else {
      data_set($col1, 'cur.label', 'PHP to SGD Rate');
    }

    data_set($col1, 'industry.type', 'lookup');
    data_set($col1, 'industry.lookupclass', 'lookupindustry');
    data_set($col1, 'industry.action', 'lookupindustry');

    $fields = [];
    if ($companyid == 10 || $companyid == 12) array_push($fields, 'radiolayoutformat'); //afti, afti usd
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col2, 'radiolayoutformat.label', 'Layout Format');
      data_set(
        $col2,
        'radiolayoutformat.options',
        [
          ['label' => 'With Customer ID', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Without Customer ID', 'value' => '1', 'color' => 'teal']
        ]
      );
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as cur,
      0 as agentid,
      '' as agent,
      '' as agentname,
      0 as clientid,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as repitemgroup,
      0 as salesgroupid,
      '' as salesgroup,
      0 as projectid,
      '' as industry,
      '1' as layoutformat
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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $result = $this->reportDefaultLayout_SUMMARIZED($config);
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $itemgroupname  = $config['params']['dataparams']['repitemgroup'];
    $agentname  = $config['params']['dataparams']['agentname'];
    $salesgroup  = $config['params']['dataparams']['salesgroup'];
    $client  = $config['params']['dataparams']['client'];
    $indus = $config['params']['dataparams']['industry'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    if ($indus != "") {
      $filter .= " and client.industry = '$indus'";
    }
    if ($itemgroupname != "") {
      $itemgroupid  = $config['params']['dataparams']['projectid'];
      $filter .= " and stock.projectid =" . $itemgroupid;
    }
    if ($agentname != "") {
      $salespersonid  = $config['params']['dataparams']['agentid'];
      $filter .= " and ag.clientid =" . $salespersonid;
    }
    if ($salesgroup != "") {
      $salesgroupid  = $config['params']['dataparams']['salesgroupid'];
      $filter .= " and ag.salesgroupid =" . $salesgroupid;
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid =" . $clientid;
    }
    $leftjoin = "";
    $addfield = ",client.industry";
    if ($companyid == 10 || $companyid == 12) { //afti
      $addfield = ", rc.description as category";
      $leftjoin = "left join reqcategory as rc on client.industryid = rc.line ";
    }

    $query = "
      select 'SO' as ttype, sohead.trno, sohead.docno as sodocno,
      date(sohead.dateid) as dateid,
      head.client, head.clientname, prj.name as projectname,
      item.barcode, item.itemname, (stock.isqty-(stock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end)) as qty, 
      (stock.amt*(ifnull(uom.factor,1))) as itemrate,
      ifnull(head.agent, '') as agcode, ifnull(ag.clientname, '1') as agname,
      head.yourref as ponum, date(head.due) as podate, item.partno as skuno,
      if(head.cur = 'P', 'PHP', head.cur ) as cur, ifnull(sg.groupname,' NO SALES GROUP') as leader, branch.clientname as branchname,stock.sgdrate,client.industry $addfield
      from hsqhead as sohead
      left join hqshead as head on head.sotrno = sohead.trno
      left join hqsstock as stock on stock.trno = head.trno
      left join client as branch on head.branch = branch.clientid
      left join item as item on item.itemid = stock.itemid
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join salesgroup as sg on sg.line = ag.salesgroupid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom " . $leftjoin . "
      where stock.void<>1 and date(sohead.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      
      UNION ALL

      select 'SSO' as ttype, sohead.trno, ifnull(sohead.docno,head.docno) as sodocno,
      date(head.due) as dateid,
      head.client, head.clientname, prj.name as projectname,
      item.barcode, item.itemname, (stock.isqty-(stock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end)) as qty, (stock.amt*(ifnull(uom.factor,1))) as itemrate,
      ifnull(head.agent, '') as agcode, ifnull(ag.clientname, '1') as agname,
      head.yourref as ponum, date(head.due) as podate, item.partno as skuno, 
      if(head.cur = 'p', 'php', head.cur ) as cur, ifnull(sg.groupname,' no sales group') as leader, branch.clientname as branchname,stock.sgdrate,client.industry $addfield
      from hqshead as head
      left join hqtstock as stock on head.trno = stock.trno
      left join hsrhead on hsrhead.qtrno = head.trno
      left join hsshead as sohead on sohead.trno = hsrhead.sotrno
      left join client as branch on head.branch = branch.clientid
      left join item as item on item.itemid = stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join salesgroup as sg on sg.line = ag.salesgroupid " . $leftjoin . "
      where item.islabor =1 and stock.void<>1 and date(head.due) between '" . $start . "' and '" . $end . "' " . $filter . "
      order by leader, agname, dateid, ponum";

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $itemgroupname  = $config['params']['dataparams']['repitemgroup'];
    // $sgdrate        = $config['params']['dataparams']['cur'];
    $indus   = $config['params']['dataparams']['industry'];

    if ($indus == "") {
      $indus = 'ALL';
    }

    $str = '';
    $layoutsize = '1600';
    if ($companyid == 10 || $companyid == 12) {
      $layoutsize = '1750';
    }
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if ($itemgroupname == "") {
      $itemgroupname = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= '<br /><br />';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Industry: ' . $indus, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Group: ' . $itemgroupname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES GROUP', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES PERSON', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BRANCH', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');

    $size = '100';
    if ($config['params']['dataparams']['layoutformat'] == 0) {
      $str .= $this->reporter->col('CUSTOMER ID', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
      $size = '50';
    }
    if ($companyid == 10 || $companyid == 12) {
      $str .= $this->reporter->col('INDUSTRY/SUB-INDUSTRY', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('INDUSTRY', '50', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->col('CUSTOMER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SO NUMBER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO NUMBER', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO DATE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM GROUP', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM NAME', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM CODE', '50', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', $size, null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEM RATE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', $size, null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VALUE IN SGD', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $sgdrate = $config['params']['dataparams']['cur'];
    // $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    // $count = 38;
    // $page = 40;

    $str = '';
    $layoutsize = '1600';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '15px;margin-top:10px;margin-left:20px');
    $str .= $this->header_DEFAULT($config);

    // $agentname = "";
    // $leader = "";
    $amount = 0;
    $subtotal = 0;
    $subtotalSGD = 0;
    $totalperday = 0;
    $grandtotalSGD = 0;
    $teamtotal = 0;
    // $date = "";

    // $datacount = count($result);
    $counter = 0;
    $size = '100';

    foreach ($result as $key => $data) {
      $counter++;
      $str .= $this->reporter->addline();
      $agname = "";

      if ($data->agname == "1") {
        $agname = "NO SALES PERSON";
      } else {
        $agname = $data->agname;
      }

      $data->dateid = date("M/d/Y", strtotime($data->dateid));
      $data->podate = date("M/d/Y", strtotime($data->podate));

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->leader, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($agname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->branchname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

      if ($config['params']['dataparams']['layoutformat'] == 0) {
        $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $size = '50';
      }

      if ($companyid == 10 || $companyid == 12) { //afti
        $str .= $this->reporter->col($data->industry, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->category, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->industry, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sodocno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ponum, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->podate, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->projectname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->skuno, '50', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 0), $size, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data->cur == 'P' ? 'PHP' : $data->cur) . " " . number_format($data->itemrate, $decimalprice), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

      $amount = $data->qty * $data->itemrate;
      if ($data->cur != 'SGD') {
        if ($sgdrate == "") {
          $sgd = $amount * $data->sgdrate;
        } else {
          $sgd = $amount * $sgdrate;
        }
      } else {
        $sgd = $amount;
      }


      $str .= $this->reporter->col($data->cur . " " . number_format($amount, $decimalprice), $size, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($sgd, $decimalprice), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      // $agentname = $data->agname;
      // $leader = $data->leader;
      // $date = $data->dateid;
      $subtotal += $amount;
      $subtotalSGD += $sgd;
      $totalperday += $sgd;
      $teamtotal += $sgd;
      $grandtotalSGD += $sgd;
    } // end for loop


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    if ($config['params']['dataparams']['layoutformat'] == 0) {
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', $size, null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($subtotal, $decimalprice), $size, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotalSGD, $decimalprice), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class