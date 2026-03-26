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

class purchase_per_supplier
{
  public $modulename = 'Purchase Per Supplier';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'class'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        break;

      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    unset($col1['class']['labeldata']);
    unset($col1['labeldata']['class']);
    data_set($col1, 'class.name', 'classic');

    $fields = ['radiovatfilter', 'radioitemsort', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 16) { //ati
      data_set(
        $col2,
        'radioreporttype.options',
        [
          ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Itemname and Qty only', 'value' => '2', 'color' => 'orange']
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
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,left(now(),10) as end,
    '' as client,
    0 as classid,
    '' as classic,
    'barcode' as itemsort,
    'vat' as vatfilter,
    '0' as reporttype,
    '' as dclientname,
    '' as class,
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid    = $config['params']['companyid'];

    switch ($companyid) {
      case 21: //kinggeorge
        switch ($reporttype) {
          case '0':
            $result = $this->kinggeorge_summarized($config);
            break;
          case '1':
            $result = $this->kinggeorge_detailed($config);
            break;
        }
        break;

      default:
        switch ($reporttype) {
          case '0':
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1':
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
          case '2': //for ati only
            $result = $this->reportDefaultLayout_other($config);
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0':
        $query = $this->reportQuery_SUMMARIZED($config);
        break;
      case '1':
        $query = $this->reportQuery_DETAILED($config);
        break;
      case '2': //for ATI only
        $query = $this->reportQuery_other($config);
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportQuery_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $class      = $config['params']['dataparams']['classic'];
    $itemsort   = $config['params']['dataparams']['itemsort'];
    $vatfilter  = $config['params']['dataparams']['vatfilter'];
    $companyid    = $config['params']['companyid'];

    $vattype = '';
    $filter = "";
    $filter1 = "";
    if (strtoupper($vatfilter) == 'VAT') {
      $vattype = " and head.vattype = 'VATABLE' ";
    } else if (strtoupper($vatfilter) == 'NVAT') {
      $vattype = " and head.vattype = 'NON-VATABLE' ";
    } else {
      $vattype = " ";
    }

    if ($client != '') {
      $client = " and client.client = '$client'";
    }
    if ($class != '') {
      $classid = $config['params']['dataparams']['classid'];
      $filter = " and item.class = $classid";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    switch ($companyid) {
      case 6: // MITSUKOSHI
        $addqry = "union all
        select barcode, itemname, uom, sum(tons) as tons, client, clientname, address, sum(isqty) as isqty, sum(ext) as ext from (
        select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,
        head.address,head.docno,client.client,client.clientname, agent.client as agent, agent.clientname as agentname,head.dateid,
        case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
        stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client as client on client.clientid = stock.suppid
        left join client as agent on agent.client = head.agent
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.doc ='rp' and head.dateid between '$start' and '$end' $vattype $client $filter
        group by client.client,item.barcode,item.class, ext, ((stock.qty * uom.kilos) / 1000),
        head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
        class,item.barcode,item.itemname,
        stock.uom,uom.kilos,client.area,stock.isamt
        union all 
        select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,head.address,head.docno,client.client,client.clientname, agent.client as agent,agent.clientname as agentname,head.dateid,
        case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
        stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client as client on client.clientid = stock.suppid
        left join client as agent on agent.clientid = head.agentid
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.doc ='rp' and head.dateid between '$start' and '$end' $vattype $client $filter
        group by client.client,item.barcode,item.class,
        head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
        class,item.barcode,item.itemname,
        stock.uom,uom.kilos,client.area,stock.isamt) as a
        group by client, clientname, address, barcode, itemname, uom";
        break;

      default:
        $addqry = "";
        break;
    }

    $query = "
    select barcode, itemname, uom, sum(tons) as tons, client, clientname, address, sum(isqty) as isqty, sum(ext) as ext from (
      select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,
      head.address,head.docno,client.client,client.clientname, agent.client as agent, agent.clientname as agentname,head.dateid,
      case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join client as client on client.client = head.client
      left join client as agent on agent.client = head.agent
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rr' and head.dateid between '$start' and '$end' $vattype $client $filter $filter1
      group by client.client,item.barcode,item.class, 
      head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
      class,item.barcode,item.itemname,
      stock.uom,uom.kilos,client.area,stock.isamt
      
      UNION ALL

      select sum(stock.ext) as ext, sum(((stock.qty * uom.kilos) / 1000)) as tons,head.address,head.docno,client.client,client.clientname, agent.client as agent,agent.clientname as agentname,head.dateid,
      case ifnull(item.class,'') when '' then 'No Class' else item.class end as class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join client as client on client.clientid = head.clientid
      left join client as agent on agent.clientid = head.agentid
      left join item on item.itemid = stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rr' and head.dateid between '$start' and '$end' $vattype $client $filter $filter1
      group by client.client,item.barcode,item.class,
      head.address,head.docno,client.client,client.clientname,agent.client,agent.clientname,head.dateid,
      class,item.barcode,item.itemname,
      stock.uom,uom.kilos,client.area,stock.isamt) as a
      where itemname <> ''
    group by client, clientname, address, barcode, itemname, uom
    $addqry
    order by clientname,$itemsort";
    return $query;
  }

  public function reportQuery_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $class      = $config['params']['dataparams']['classic'];
    $itemsort   = $config['params']['dataparams']['itemsort'];
    $vatfilter  = $config['params']['dataparams']['vatfilter'];
    $companyid = $config['params']['companyid'];

    $vattype = '';
    $filter = "";
    $filter1 = "";
    if (strtoupper($vatfilter) == 'VAT') {
      $vattype = " and head.vattype = 'VATABLE' ";
    } else if (strtoupper($vatfilter) == 'NVAT') {
      $vattype = " and head.vattype = 'NON-VATABLE' ";
    } else {
      $vattype = " ";
    }

    if ($client != '') {
      $client = " and client.client = '$client'";
    }
    if ($class != '') {
      $classid = $config['params']['dataparams']['classid'];
      $filter = " and item.class = $classid";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['project'];

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($deptname != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    switch ($companyid) {
      case 6: // MITSUKOSHI
        $addqry = "union all
      select sum(stock.ext) as ext,sum((stock.qty * uom.kilos) / 1000) as tons, 
      head.docno,client.client,client.clientname,agent.client as agent,agent.clientname as agentname,head.dateid,
      item.class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt,client.addr from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join client as client on client.clientid = stock.suppid
      left join client as agent on agent.client = head.agent
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rp' and head.dateid between '$start' and '$end' $vattype $client $filter
      group by client.client, head.docno, client.clientname, agent.client, agent.clientname, dateid,
      item.class, item.barcode, item.itemname, stock.uom, uom.kilos, client.area, stock.isamt, client.addr
      union all
      select sum(stock.ext) as ext,sum((stock.qty * uom.kilos) / 1000) as tons,head.docno,client.client,client.clientname,
      agent.client as agent,agent.clientname as agentname,head.dateid,
      item.class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt,client.addr from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join client as client on client.clientid = stock.suppid
      left join client as agent on agent.clientid = head.agentid
      left join item on item.itemid = stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rp' and head.dateid between '$start' and '$end' $vattype $client $filter
      group by client.client, head.docno, client.clientname, agent.client, agent.clientname, dateid,
      item.class, item.barcode, item.itemname, stock.uom, uom.kilos, client.area, stock.isamt, client.addr";
        break;
      default:
        $addqry = "";
        break;
    }

    $query = "
    select sum(tons) as tons, client, clientname, addr, sum(isqty) as isqty, sum(ext) as ext from (
      select sum(stock.ext) as ext,sum((stock.qty * uom.kilos) / 1000) as tons, 
      head.docno,client.client,client.clientname,agent.client as agent,agent.clientname as agentname,head.dateid,
      item.class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt,client.addr from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join client as client on client.client = head.client
      left join client as agent on agent.client = head.agent
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rr' and head.dateid between '$start' and '$end' $vattype $client $filter $filter1
      group by client.client, head.docno, client.clientname, agent.client, agent.clientname, dateid,
      item.class, item.barcode, item.itemname, stock.uom, uom.kilos, client.area, stock.isamt, client.addr
      union all
      select sum(stock.ext) as ext,sum((stock.qty * uom.kilos) / 1000) as tons,head.docno,client.client,client.clientname,
      agent.client as agent,agent.clientname as agentname,head.dateid,
      item.class,item.barcode,item.itemname,
      stock.uom,sum(stock.qty) as isqty,uom.kilos,client.area,stock.isamt,client.addr from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join client as client on client.clientid = head.clientid
      left join client as agent on agent.clientid = head.agentid
      left join item on item.itemid = stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      where head.doc ='rr' and head.dateid between '$start' and '$end' $vattype $client $filter $filter1
      group by client.client, head.docno, client.clientname, agent.client, agent.clientname, dateid,
      item.class, item.barcode, item.itemname, stock.uom, uom.kilos, client.area, stock.isamt, client.addr
      $addqry
      order by $itemsort) as a
    group by client, clientname, addr";

    return $query;
  }

  public function reportQuery_other($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $class      = $config['params']['dataparams']['classic'];
    $itemsort   = $config['params']['dataparams']['itemsort'];
    $vatfilter  = $config['params']['dataparams']['vatfilter'];

    $vattype = '';
    $filter = "";
    if (strtoupper($vatfilter) == 'VAT') {
      $vattype = " and head.vattype = 'VATABLE' ";
    } else if (strtoupper($vatfilter) == 'NVAT') {
      $vattype = " and head.vattype = 'NON-VATABLE' ";
    } else {
      $vattype = " ";
    }

    if ($client != '') {
      $client = " and client.client = '$client'";
    }
    if ($class != '') {
      $classid = $config['params']['dataparams']['classid'];
      $filter = " and item.class = $classid";
    }

    $query = "select client,clientname,itemname,address,sum(qty) as isqty
              from (select item.itemname,stock.qty,head.client,head.clientname,head.address
                    from lahead as head
                    left join lastock as stock on stock.trno = head.trno
                    left join item on item.itemid=stock.itemid
                    left join client as client on client.client = head.client
                    where head.doc ='RR' and head.dateid between '$start' and '$end' $vattype $client $filter 
                    UNION ALL
                    select item.itemname,stock.qty,client.client,head.clientname,head.address
                    from glhead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join item on item.itemid = stock.itemid
                    left join client as client on client.clientid = head.clientid
                    where head.doc ='RR' and head.dateid between '$start' and '$end' $vattype $client $filter ) as a
              where itemname <> ''
              group by client,clientname,address,itemname
              order by clientname,itemname";

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
    $class      = $config['params']['dataparams']['classic'];
    $vatfilter  = $config['params']['dataparams']['vatfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['ddeptname'];
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

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($reporttype == 1) {
      $rtype = "DETAILED";
    } else {
      $rtype = "SUMMARIZED";
    }

    $str .= $this->reporter->col('PURCHASE PER SUPPLIER ' . $rtype, 800, null, false, $border, '', 'C', $font, '16', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), 800, null, false, $border, '', 'C', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);

    if (strtoupper($vatfilter) != 'ALL') {
      if (strtoupper($vatfilter) == 'VAT') {
        $vattype = 'VATABLE';
      } else {
        $vattype = 'NON-VATABLE';
      }
    } else {
      $vattype = 'ALL';
    }

    if ($client != '') {
      $client = $client;
    } else {
      $client = 'ALL';
    }

    if ($class != '') {
      $cla = $class;
    } else {
      $cla = 'ALL';
    }

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Supplier : ' . $client, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Vat : ' . $vattype, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . $cla, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('Project : ' . $projname, 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
      $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 51;
    $page = 50;
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
    $str .= $this->default_displayDetailtable($config);

    $itemname = "";
    $docno = "";
    $totalext = 0;
    $totalqty = 0;
    $totaltons = 0;
    $totalunitprice = 0;
    $subtotalqty = 0;
    $subtotalext = 0;
    $subtotaltons = 0;
    $subtotalunitprice = 0;
    $gsubtotalqty = 0;
    $gsubtotalext = 0;
    $gsubtotaltons = 0;
    $gsubtotalunitprice = 0;

    $iitem = "";
    foreach ($result as $key => $data) {
      $tons = $data->tons;
      $unitprice = ($data->isqty != 0) ? $data->ext / $data->isqty : 0;

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

        $str .= $this->reporter->begintable($layoutsize);
        if ($itemname != '') {
          $str .= $this->reporter->startrow();

          switch ($companyid) {
            case 19: //housegem
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(':', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalunitprice, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              break;

            default:
              $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
              if ($companyid == 16) { //ati
                $str .= $this->reporter->col(':', 450, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', 100, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($gsubtotalqty, 2), 100, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              } else {
                $str .= $this->reporter->col(':', '350', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($gsubtotaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              }
              $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
              break;
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->client . '  ' . $data->clientname . '  ' . $data->address, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $subtotalqty = 0;
        $subtotalext = 0;
        $subtotalunitprice = 0;
        $subtotaltons = 0;

        $gsubtotalqty = 0;
        $gsubtotalext = 0;
        $gsubtotalunitprice = 0;
        $gsubtotaltons = 0;
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

      switch ($companyid) {
        case 19: //housegem
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($tons, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($unitprice, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          break;

        default:
          $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          if ($companyid == 16) { //ati
            $str .= $this->reporter->col($data->itemname, '450', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          } else {
            $str .= $this->reporter->col($data->itemname, '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($tons, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          break;
      }

      $str .= $this->reporter->endrow();

      $subtotalext = $subtotalext + $data->ext;
      $subtotalqty = $subtotalqty + $data->isqty;
      $subtotaltons = $subtotaltons + $tons;
      $subtotalunitprice = $subtotalunitprice + $unitprice;

      $gsubtotalext = $gsubtotalext + $data->ext;
      $gsubtotalqty = $gsubtotalqty + $data->isqty;
      $gsubtotaltons = $gsubtotaltons + $tons;
      $gsubtotalunitprice = $gsubtotalunitprice + $unitprice;

      $totaltons = $totaltons + $tons;
      $totalext = $totalext + $data->ext;
      $totalqty = $totalqty + $data->isqty;
      $totalunitprice = $totalunitprice + $unitprice;

      $itemname = strtoupper($data->clientname);
      $docno = $data->itemname;

      $iitem = $data->itemname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader($config);
        $str .= $this->default_displayDetailtable($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 19: //housegem
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(':', '300', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalunitprice, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;

      default:
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        if ($companyid == 16) { //ati
          $str .= $this->reporter->col(':', 450, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalqty, 2), 100, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col(':', '350', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 19: //housegem
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL :', '300', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalunitprice, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;

      default:
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        if ($companyid == 16) {
          $str .= $this->reporter->col('TOTAL :', 450, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), 100, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col('TOTAL :', '350', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_displayDetailtable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 19: //housegem
        $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('DESCRIPTION', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('TOT TONS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
        break;

      default:
        $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
        if ($companyid == 16) {
          $str .= $this->reporter->col('DESCRIPTION', '450', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        } else {
          $str .= $this->reporter->col('DESCRIPTION', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        }
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        if ($companyid != 16) {
          $str .= $this->reporter->col('TOT TONS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
        }
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
        break;
    }
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result  = $this->reportDefault($config);
    $count = 41;
    $page = 40;
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

    $totalqty = 0;
    $totaltons = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $tons = $data->tons;

      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      if ($config['params']['companyid'] == 16) { //ati
        $str .= $this->reporter->col($data->addr, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->addr, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($tons, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $totalqty = $totalqty + $data->isqty;
      $totaltons = $totaltons + $tons;
      $totalamt = $totalamt + $data->ext;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader($config);
        $str .= $this->default_displayHeadertable($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    if ($config['params']['companyid'] == 16) { //ati
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    } else {
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col(number_format($totaltons, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    }

    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endreport();

    return $str;
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

    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SUPPLIER', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');

    if ($config['params']['companyid'] == 16) { //ati
      $str .= $this->reporter->col('ADDRESS', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
    } else {
      $str .= $this->reporter->col('ADDRESS', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
      $str .= $this->reporter->col('TONS', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
    }
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function kinggeorge_detailed($config)
  {
    $result  = $this->reportDefault($config);

    $count = 51;
    $page = 50;
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
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CODE', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('DESCRIPTION', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $itemname = "";
    $docno = "";
    $totalext = 0;
    $totalqty = 0;
    $totaltons = 0;
    $subtotalqty = 0;
    $subtotalext = 0;
    $subtotaltons = 0;
    $gsubtotalqty = 0;
    $gsubtotalext = 0;
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

        $gsubtotalqty = 0;
        $gsubtotalext = 0;
        $gsubtotaltons = 0;
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

      $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $subtotalext = $subtotalext + $data->ext;
      $subtotalqty = $subtotalqty + $data->isqty;
      $subtotaltons = $subtotaltons + $tons;


      $gsubtotalext = $gsubtotalext + $data->ext;
      $gsubtotalqty = $gsubtotalqty + $data->isqty;
      $gsubtotaltons = $gsubtotaltons + $tons;

      $totaltons = $totaltons + $tons;
      $totalext = $totalext + $data->ext;
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

    $str .= $this->reporter->col(number_format($gsubtotalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '350', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function kinggeorge_summarized($config)
  {
    $result  = $this->reportDefault($config);

    $count = 41;
    $page = 40;
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SUPPLIER', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('ADDRESS', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    // $item = null;
    $totalqty = 0;
    $totaltons = 0;
    $totalamt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $tons = $data->tons;

      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->isqty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $totalqty = $totalqty + $data->isqty;
      $totaltons = $totaltons + $tons;
      $totalamt = $totalamt + $data->ext;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endreport();

    return $str;
  }


  ///////////////////////////// additional option for ATI only

  private function default_displayHeader_other($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $class      = $config['params']['dataparams']['classic'];
    $vatfilter  = $config['params']['dataparams']['vatfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $str = '';
    $layoutsize = 850;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('PURCHASE PER SUPPLIER ', 850, null, false, $border, '', 'C', $font, '16', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), 850, null, false, $border, '', 'C', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);

    if (strtoupper($vatfilter) != 'ALL') {
      if (strtoupper($vatfilter) == 'VAT') {
        $vattype = 'VATABLE';
      } else {
        $vattype = 'NON-VATABLE';
      }
    } else {
      $vattype = 'ALL';
    }

    if ($client != '') {
      $client = $client;
    } else {
      $client = 'ALL';
    }

    if ($class != '') {
      $cla = $class;
    } else {
      $cla = 'ALL';
    }

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Supplier : ' . $client, 400, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Vat : ' . $vattype, 450, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Class : ' . $cla, 400, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 450, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', 450, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_other($config)
  {
    $result  = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 850;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader_other($config);
    $str .= $this->default_displayOthertable($config);

    $itemname = "";
    $docno = "";
    $totalqty = 0;
    $subtotalqty = 0;
    $gsubtotalqty = 0;

    $iitem = "";
    foreach ($result as $key => $data) {
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
          }
          $itemname = strtoupper($data->clientname);
        }
      } else {

        $str .= $this->reporter->begintable($layoutsize);
        if ($itemname != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col(':', 600, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($gsubtotalqty, 2), 250, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->client . '  ' . $data->clientname . '  ' . $data->address, 850, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $subtotalqty = 0;
        $gsubtotalqty = 0;
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
      $str .= $this->reporter->col($data->itemname, 600, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->isqty, 2), 250, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $subtotalqty = $subtotalqty + $data->isqty;
      $gsubtotalqty = $gsubtotalqty + $data->isqty;
      $totalqty = $totalqty + $data->isqty;

      $itemname = strtoupper($data->clientname);
      $docno = $data->itemname;

      $iitem = $data->itemname;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->default_displayHeader_other($config);
        $str .= $this->default_displayOthertable($config);
        $page += $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(':', 600, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gsubtotalqty, 2), 250, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL :', 600, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), 250, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_displayOthertable($config)
  {
    $str = "";
    $layoutsize = 850;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM DESCRIPTION', 600, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('QTY', 250, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    return $str;
  }
}
