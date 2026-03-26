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

class monthly_summary_of_input_tax
{
  public $modulename = 'Monthly Summary of Input Tax';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 56) {  //homeworks
      data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }

    $fields = ['dateid', 'due', 'dcentername'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'project.required', false);
        data_set($col2, 'ddeptname.label', 'Department');
        data_set($col2, 'project.label', 'Item Group');
        break;
      case 56: //homeworks
        array_push($fields, 'dclientname');
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,
    adddate(left(now(),10),-360) as dateid,
    adddate(left(now(),10),1) as due,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as project, 0 as projectid, '' as projectname,
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

    switch ($companyid) {
      case 56: //homeworks
        $paramstr .= ", '0' as clientid, '' as client, '' as clientname, '' as dclientname ";
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


  public function default_query($filters)
  {
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $companyid = $filters['params']['companyid'];
    $filter = "";
    $filter1 = "";
    $crnet2 = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

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
    } elseif ($companyid == 56) { //homeworks
      $client    = $filters['params']['dataparams']['client'];
      $clientid = $filters['params']['dataparams']['clientid'];
      if ($client != "") {
        $filter1 .= " and client.clientid = '$clientid'";
      }
    } else {
      $filter1 .= "";
    }

    $isvatexsales = $this->companysetup->getvatexsales($filters['params']);
    $crnet = ", ((sum(stock.ext)/1.12) * 0.12) as 'cr', (sum(stock.ext)/1.12) as 'net'";
    $crnet2 = ", ((sum(detail.db-detail.cr)/1.12) * 0.12) as 'cr', (sum(detail.db-detail.cr)/1.12) as 'net'";
    if ($isvatexsales) {
      $crnet = ", (sum(stock.ext) * 0.12) as 'cr', sum(stock.ext) as 'net'";
      $crnet2 = ", (sum(detail.db-detail.cr) * 0.12) as 'cr', sum(detail.db-detail.cr) as 'net'";
    }

    $rrcr = '';
    $rrnet = '';
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $rrcr = "
    ,(select sum(d.db-d.cr) as cr from gldetail as d
    left join coa as c on c.acnoid=d.acnoid
    where d.trno= head.trno and c.alias='TX1') as cr";
      $rrnet = ", (sum(stock.ext)/1.12) as 'net'";
      if ($isvatexsales) {
        $rrnet = ", sum(stock.ext) as 'net'";
      }
      $rrcr = $rrcr . $rrnet;
    } else {
      $rrcr = $crnet;
    }

    switch ($companyid) {
      case 6: //MITSUKOSHI
        $addqry = "union all
      select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,
      head.docno ,sum(stock.ext) as 'db', ((sum(stock.ext)/1.12) * 0.12) as 'cr', (sum(stock.ext)/1.12) AS 'net'
      from glhead AS head
      left join glstock as stock on stock.trno = head.trno
      left join client on head.clientid = stock.suppid
      left join cntnum on cntnum.trno = head.trno
      where head.doc = 'RP' and head.vattype = 'VATABLE' and
      head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
      group by dateid, clientname, tin, addr, docno";
        break;

      case 10: //afti
      case 12: //afti usd
        $addqry = "union all
        select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,
        head.docno , sum(detail.db-detail.cr) as 'db' " . $crnet2 . "
        from glhead AS head
        left join gldetail as detail on detail.trno = head.trno
        left join glstock as stock on stock.trno = head.trno
        left join client on head.clientid = client.clientid
        left join cntnum on cntnum.trno = head.trno
        where head.doc = 'GJ' and detail.isvat = 1 and
        head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1
        group by dateid, clientname, tin, addr, docno, head.trno";
        break;

      default:
        $addqry = "";
        break;
    }

    $query = "select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,
          head.docno ,sum(stock.ext) as 'db' $rrcr
          from glhead AS head
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          where head.doc in ('GJ', 'CV', 'RR', 'AC') and head.vattype = 'VATABLE' and
          head.dateid between '" . $start . "' and '" . $end . "' AND ( (SELECT SUM(d.db-d.cr) AS cr FROM gldetail AS d
          LEFT JOIN coa AS c ON c.acnoid=d.acnoid
          WHERE d.trno= head.trno AND c.alias='TX1') IS NOT NULL OR stock.ext IS NOT NULL)  $filter $filter1
          group by dateid, clientname, tin, addr, docno, head.trno
          union all
          select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,
          head.docno , sum(detail.db-detail.cr) as 'db' " . $crnet2 . "
          from glhead AS head
          left join gldetail as detail on detail.trno = head.trno
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          where head.doc = 'PV' and detail.isvat = 1 and
          head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1
          group by dateid, clientname, tin, addr, docno, head.trno
          $addqry order by dateid,docno";



    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function homeworks_query($filters)
  {
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $filter = "";
    $crnet = "";
    $crnet2 = "";
    $crnet3 = "";
    $printtype = $filters['params']['dataparams']['print'];

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    $client    = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid'";
    }


    $isvatexsales = $this->companysetup->getvatexsales($filters['params']);
    $crnet = ", ((sum(stock.ext)/1.12) * 0.12) as 'cr', (sum(stock.ext)/1.12) as 'net'";
    $crnet2 = ", ((sum(detail.db-detail.cr)/1.12) * 0.12) as 'cr', (sum(detail.db-detail.cr)/1.12) as 'net'";
    $crnet3 = ", ((sum(detail.db-detail.cr)/1.12)*0.12)*-1 as 'cr', (sum(detail.db-detail.cr)/1.12)*-1 as 'net'";

    if ($isvatexsales) {
      $crnet = ", (sum(stock.ext) * 0.12) as 'cr', sum(stock.ext) as 'net'";
      $crnet2 = ", (sum(detail.db-detail.cr) * 0.12) as 'cr', sum(detail.db-detail.cr) as 'net'";
      $crnet3 = ", (sum(detail.db-detail.cr) * 0.12)*-1 as 'cr', sum(detail.db-detail.cr)*-1 as 'net'";
    }
    switch ($printtype) {
      case 'default':
      case 'excel':
        $query = "select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,client.client,
          head.docno ,sum(stock.ext) as 'db' $crnet
          from glhead AS head
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          where head.doc in ('GJ', 'CV', 'AC') and head.vattype = 'VATABLE' and
          date(head.dateid) between '" . $start . "' and '" . $end . "' AND ( (SELECT SUM(d.db-d.cr) AS cr FROM gldetail AS d
          LEFT JOIN coa AS c ON c.acnoid=d.acnoid
          WHERE d.trno= head.trno AND c.alias='TX1') IS NOT NULL OR stock.ext IS NOT NULL)  $filter
          group by head.dateid, clientname, tin, addr, docno, head.trno,client.client
          union all
          select date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,client.client,
          head.docno , sum(detail.db-detail.cr) as 'db' " . $crnet2 . "
          from glhead AS head
          left join gldetail as detail on detail.trno = head.trno
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          where head.doc = 'PV' and detail.isvat = 1 and
          date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
          group by head.dateid, clientname, tin, addr, docno, head.trno,client.client
        
          union all

            select date_format(head.dateid,'%m-%d-%Y') as dateid,
            client.clientname, client.tin, client.addr,  client.client,head.docno ,
            sum((detail.db-detail.cr)*-1) as db
            " . $crnet3 . "
            from glhead AS head
            left join gldetail as detail on detail.trno = head.trno
            left join client as client on client.clientid=detail.clientid
            left join cntnum on cntnum.trno = head.trno
            left join coa as c on c.acnoid=detail.acnoid
            where left(cntnum.bref,3) in ('sjs','srs') and c.alias in ('AP1','AP2')  and
            date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
            group by head.dateid, clientname, tin, addr, docno, head.trno,client.client
            order by dateid,docno";
        break;
      case 'CSV':
        $query = "select  branchname as `BRANCHNAME`, dateid as `DATE`, client as `CODE`,clientname as `SUPPLIER`,tin as `TIN`,addr as `ADDRESS`,docno as `DOCNO`,db as `PURCHASES`,cr as `VATAMT`,net as `NETPURCHASE` 
          from (select if(center.name !='',center.name, br.clientname) as branchname, date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,client.client,
          head.docno ,sum(stock.ext) as 'db' $crnet
          from glhead AS head
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          left join center on center.code=cntnum.center
          left join client as br on br.clientid=head.branch and center.branchid
          where head.doc in ('GJ', 'CV', 'AC') and head.vattype = 'VATABLE' and
          date(head.dateid) between '" . $start . "' and '" . $end . "' AND ( (SELECT SUM(d.db-d.cr) AS cr FROM gldetail AS d
          LEFT JOIN coa AS c ON c.acnoid=d.acnoid
          WHERE d.trno= head.trno AND c.alias='TX1') IS NOT NULL OR stock.ext IS NOT NULL)  $filter
          group by head.dateid, client.clientname, tin, addr, docno, head.trno,client.client, center.name,br.clientname
          union all
          select if(center.name !='',center.name, br.clientname) as branchname, date_format(dateid,'%m-%d-%Y') as dateid, client.clientname, client.tin, client.addr,client.client,
          head.docno , sum(detail.db-detail.cr) as 'db' " . $crnet2 . "
          from glhead AS head
          left join gldetail as detail on detail.trno = head.trno
          left join glstock as stock on stock.trno = head.trno
          left join client on head.clientid = client.clientid
          left join cntnum on cntnum.trno = head.trno
          left join center on center.code=cntnum.center
          left join client as br on br.clientid=head.branch and center.branchid
          where head.doc = 'PV' and detail.isvat = 1 and
          date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
          group by head.dateid, client.clientname, tin, addr, docno, head.trno,client.client, center.name,br.clientname
        
          union all

            select if(center.name !='',center.name, br.clientname) as branchname, date_format(head.dateid,'%m-%d-%Y') as dateid,
            client.clientname, client.tin, client.addr,  client.client,head.docno ,
            sum((detail.db-detail.cr)*-1) as db
            " . $crnet3 . "
            from glhead AS head
            left join gldetail as detail on detail.trno = head.trno
            left join client as client on client.clientid=detail.clientid
            left join cntnum on cntnum.trno = head.trno
            left join coa as c on c.acnoid=detail.acnoid
            left join center on center.code=cntnum.center
            left join client as br on br.clientid=head.branch and center.branchid
            where left(cntnum.bref,3) in ('sjs','srs') and c.alias in ('AP1','AP2')  and
            date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
            group by head.dateid, client.clientname, tin, addr, docno, head.trno,client.client, center.name,br.clientname) as a 
            order by dateid,docno";

        break;
    }


    // var_dump($query); // sum(if(left(cntnum.bref,3) = 'SJS', detail.db-detail.cr, (detail.db-detail.cr * -1))) as 'db',
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }


  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 56) { //homeworks
      $result = $this->homeworks_query($config);
    } else {
      $result = $this->default_query($config);
    }
    $reportdata =  $this->DEFAULT_INPUT_TAX_LAYOUT($result, $config);
    return $reportdata;
  }

  private function DEFAULT_INPUT_TAX_HEADER($params)
  {
    $username   = $params['params']['user'];
    $ccenter   = $params['params']['center'];
    $companyid = $params['params']['companyid'];

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

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

    $str = '';
    $layoutsize = '1000';
    if ($companyid == 56) { //homewroks
      $font = 'calibri';
    } else {
      $font = $this->companysetup->getrptfont($params['params']);
    }
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Input Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
    // $str .= $this->reporter->col('Supplier', '175', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
    // $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
    switch ($companyid) {
      case 10:
      case 12:
        $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Supplier', '175', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax Base', '100', '', false, $border, 'TB', 'R',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Vat', '100', '', false, $border, 'TB', 'C',  $font, '12',  'b', '', '', '', '');
        break;
      case 56: //homeworks
        $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Code', '100', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Supplier', '150', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Address', '140', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Doc #', '115', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Total', '90', '', false, $border, 'TB', 'C',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Vat', '90', '', false, $border, 'TB', 'C',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax Base', '90', '', false, $border, 'TB', 'R',  $font, '12',  'b', '', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Supplier', '175', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Address', '175', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Total', '100', '', false, $border, 'TB', 'C',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Vat', '100', '', false, $border, 'TB', 'C',  $font, '12',  'b', '', '', '', '');
        $str .= $this->reporter->col('Tax Base', '100', '', false, $border, 'TB', 'R',  $font, '12',  'b', '', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_INPUT_TAX_LAYOUT($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    // $count = 41;
    // $page = 40;
    $this->reporter->linecounter = 0;
    if ($companyid == 56) { //homeworks
      $font = 'calibri';
    } else {
      $font = $this->companysetup->getrptfont($params['params']);
    }
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $col = array(
      array('100', '', false, $border, '', 'l',  $font, '',  '', '', '', '', ''),
      array('175', '', false, $border, '', 'l',  $font, '',  '', '', '', '', ''),
      array('125', '', false, $border, '', 'l',  $font, '',  '', '', '', '', ''),
      array('175', '', false, $border, '', 'l',  $font, '',  '', '', '', '', ''),
      array('125', '', false, $border, '', 'l',  $font, '',  '', '', '', '', ''),
      array('100', '', false, $border, '', 'r',  $font, '',  '', '', '', '', ''),
      array('100', '', false, $border, '', 'r',  $font, '',  '', '', '', '', ''),
      array('100', '', false, $border, '', 'r',  $font, '',  '', '', '', '', ''),
    );
    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_INPUT_TAX_HEADER($params);

    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      // $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
      // $str .= $this->reporter->col($data_->clientname, '175', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
      // $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->clientname, '175', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '100', '', false, $border, '', 'R',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '100', '', false, $border, $fontsize, 'R',  $font, $fontsize,  '', '', '', '', '');
          break;
        case 56: //homewrek
          $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->client, '100', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->clientname, '150', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->addr, '140', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '115', '', false, $border, '', 'LT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->db, $decimal_currency), '90', '', false, $border, '', 'RT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '90', '', false, $border, '', 'RT',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '90', '', false, $border, '', 'RT',  $font, $fontsize,  '', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->clientname, '175', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->addr, '175', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'L',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->db, $decimal_currency), '100', '', false, $border, '', 'R',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->cr, $decimal_currency), '100', '', false, $border, '', 'R',  $font, $fontsize,  '', '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->net, $decimal_currency), '100', '', false, $border, '', 'R',  $font, $fontsize,  '', '', '', '', '');
          break;
      }
      $str .= $this->reporter->endrow();

      // $dateid = $data_->dateid;
      $a += $data_->db;
      $b += $data_->cr;
      $c += $data_->net;
      $totala = $totala + $data_->db;
      $totalb = $totalb + $data_->cr;
      $totalc = $totalc + $data_->net;


      if ($cnt == $cnt1) {
        if ($data_->dateid == '') {
          // $group = 'NO DATE';
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->DEFAULT_INPUT_TAX_SUBTOTAL($a, $b, $c, $companyid, $params);
          $str .= $this->reporter->addline();

          $a = 0;
          $b = 0;
          $c = 0;
          // $group = $data_->dateid;
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L',  $font, $fontsize,  'B', '', '', '');
    // $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
    // $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L',  $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'R',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        break;
      case 56:
        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L',  $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '150', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '140', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '115', '', false, $border, 'T', 'R',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totala, 2), '90', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalb, 2), '90', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalc, 2), '90', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        break;
      default:
        $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L',  $font, $fontsize,  'B', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'C',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'R',  $font, $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'R',  $font,  $fontsize,  'B', '', '', '', 0);
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_INPUT_TAX_SUBTOTAL($a, $b, $c, $companyid, $config)
  {
    $str = '';
    if ($companyid == 56) { //homeworks
      $font = 'calibri';
    } else {
      $font = $this->companysetup->getrptfont($config['params']);
    }
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      // $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
      // $str .= $this->reporter->col('', '175', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
      // $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'R',  $font, '',  'i', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          break;
        case 56: //homeworks
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '140', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '115', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '90', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '90', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '90', false, '1px dashed', 'T', 'R',  $font, '',  'i', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'R',  $font, '',  'i', '', '', '', '');
          break;
      }
    } else {
      // $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
      // $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
      // $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '100', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          break;
        case 56: //home
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '140', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '115', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '90', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '90', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '90', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C',  $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($b, 2), '100', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R',  $font, $fontsize,  'B', '', '', '', '');
          break;
      }
    }

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportdatacsv($config)
  {
    $data = $this->homeworks_query($config);

    $total_total = 0;
    $total_tax = 0;
    $total_net = 0;

    foreach ($data as $row => $value) {

      $total_total += $value->PURCHASES;
      $total_tax += $value->VATAMT;
      $total_net += $value->NETPURCHASE;

      $value->PURCHASES = number_format($value->PURCHASES, 2);
      $value->VATAMT = number_format($value->VATAMT, 2);
      $value->NETPURCHASE = number_format($value->NETPURCHASE, 2);
    }

    // gumawa ng total row
    $data[] = [
      'BRANCHNAME' => '',
      'DATE' => '',
      'CODE' => '',
      'SUPPLIER' => '',
      'TIN' => '',
      'ADDRESS' => '',
      'DOCNO' => 'TOTAL',
      'PURCHASES' => number_format($total_total, 2),
      'VATAMT' => number_format($total_tax, 2),
      'NETPURCHASE' => number_format($total_net, 2)
    ];

    $status = true;
    $msg = 'Generating CSV successfully';

    if (empty($data)) {
      $status = false;
      $msg = 'No data Found';
    }

    return [
      'status' => $status,
      'msg' => $msg,
      'data' => $data,
      'params' => $this->reportParams,
      'name' => 'Monthly_Summary_of_Input_Tax'
    ];
  }
}
