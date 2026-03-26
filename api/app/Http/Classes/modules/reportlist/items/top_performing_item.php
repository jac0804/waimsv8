<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class top_performing_item
{
  public $modulename = 'Top Performing Item';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'project', 'ddeptname', 'radiotypeofreportsales'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;

      case 15: //nathina
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'dcentername', 'ditemname', 'part', 'model', 'class', 'brand', 'ddeptname', 'category'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'category.name', 'categoryname');
        break;
      case 27: //nte
      case 36: //rozlab
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'categoryname', 'subcatname', 'class', 'radiotypeofreportsales'];
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'categoryname', 'subcatname', 'radiotypeofreportsales'];
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dcentername.required', false);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    unset($col1['part']['labeldata']);
    unset($col1['model']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['brand']['labeldata']);
    unset($col1['labeldata']['part']);
    unset($col1['labeldata']['model']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['brand']);
    data_set($col1, 'part.name', 'stockgrp');
    data_set($col1, 'model.name', 'modelname');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'brand.name', 'brandname');

    switch ($companyid) {
      case 15: //nathina
        $fields = ['salestype', 'radiotypeofreportsales'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'salestype.required', false);
        break;
      default:
        $fields = ['radioposttype', 'radiosortby'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'radioposttype.options', [
          ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]);
        break;
    }


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as center,
    0 as clientid,
    '' as client,
    '' as clientname,
    '' as category,
    '' as categoryname,
    '' as subcatname,
    '' as subcat,
    'report' as typeofreport,
    'docno' as sortby,
    '' as dclientname,
    '' as dcentername,
    '' as dagentname,
    '' as agent,
    0 as agentid,
    '0' as posttype,
    'si' as typeofdrsi,
    '' as project,
    0 as projectid,
    '' as projectname,
    0 as deptid,
    '' as ddeptname,
    '' as dept,
    '' as deptname,
    '' as barcode,
    0 as itemid,
    '' as itemname,
    '' as part,
    0 as partid,
    '' as partname,
    '' as model,
    0 as modelid,
    '' as modelname,
    '' as class,
    0 as classid,
    '' as classic,
    '' as brand,
    0 as brandid,
    '' as brandname,
    '' as salestype
    ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }
  // 
  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }
  // GET THE FINISH LAYOUT OF REPORT
  public function reportplotting($config)
  {
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    // ADD SWITCH IF EVER MORE LAYOUT OR PER COMPANY
    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout_REPORT($config);
        break;
      default:
        $result = $this->reportDefaultLayout_REPORT($config);
        break;
    }

    return $result;
  }
  // QUERY RESULT PER FUNCTION
  public function reportDefault($config)
  {
    // QUERY
    // $center     = $config['params']['dataparams']['center'];
    // $client     = $config['params']['dataparams']['client'];
    $type   = $config['params']['dataparams']['typeofreport'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $posttype =  $config['params']['dataparams']['posttype'];
    $client = $config['params']['dataparams']['client'];
    $agent = $config['params']['dataparams']['agent'];
    $center = $config['params']['dataparams']['center'];
    $companyid = $config['params']['companyid'];

    $option = '';
    if ($companyid == 15) { //nathina
      $salestype = $config['params']['dataparams']['salestype'];
    }

    $filter = "";
    //FILTERS START
    $start  = $config['params']['dataparams']['start'];
    $end  = $config['params']['dataparams']['end'];

    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and cust.clientid=" . $clientid;
    }
    if ($agent != "") {
      $agentid = $config['params']['dataparams']['agentid'];
      $filter .= " and ag.clientid=" . $agentid;
    }
    if ($center != "") {
      $filter .= " and num.center = '" . $center . "' ";
    }

    #Class
    if ($companyid == 27 || $companyid == 36) { //nte, rozlab
      $class = $config['params']['dataparams']['classic'];
      if ($class != '') {
        $classid = $config['params']['dataparams']['classid'];
        $filter .= " and item.class=" . $classid;
      }
    }

    if ($companyid == 15) { //nathina
      $barcode = $config['params']['dataparams']['barcode'];
      $part = $config['params']['dataparams']['part'];
      $model = $config['params']['dataparams']['model'];
      $class = $config['params']['dataparams']['classic'];
      $brand = $config['params']['dataparams']['brandname'];
      $dept = $config['params']['dataparams']['ddeptname'];
      $salestype = $config['params']['dataparams']['salestype'];

      if ($barcode != "") {
        $itemid = $config['params']['dataparams']['itemid'];
        $filter .= " and stock.itemid=" . $itemid;
      }
      if ($part != "") {
        $partid = $config['params']['dataparams']['partid'];
        $filter .= " and item.part=" . $partid;
      }
      if ($model != "") {
        $modelid = $config['params']['dataparams']['modelid'];
        $filter .= " and item.model=" . $modelid;
      }
      if ($class != "") {
        $classid = $config['params']['dataparams']['classid'];
        $filter .= " and item.class=" . $classid;
      }
      if ($brand != "") {
        $brandid = $config['params']['dataparams']['brandid'];
        $filter .= " and item.brand=" . $brandid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter .= " and head.deptid=" . $deptid;
      }
      if ($categoryname != "") {
        $category = $config['params']['dataparams']['category'];
        $filter .= " and item.category='$category'";
      }
      if ($salestype != "") {
        $filter .= " and head.salestype = '" . $salestype . "' ";
      }

      #salestype/trnxtype
      $filter .= " and head.salestype='$salestype' ";
      switch ($option) {
        case 'qty':
          $viewfield = 'stock.iss';
          $col = 'stock.ext';
          break;
        default:
          $viewfield = 'stock.ext';
          $col = 'stock.iss';
          break;
      }
    } else {
      $viewfield = 'stock.ext';
      $col = 'stock.iss';
    }
    //FILTERS END

    $SJselect = "ifnull(sum(" . $col . "),0) as `qty`, ag.client as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) as `sales`, item.barcode, item.itemname";
    $CMselect = "ifnull(sum(" . $col . "),0) as `qty`, ag.client as code,ifnull(ag.clientname,'') as category,ifnull(sum(" . $viewfield . "),0) * -1  as `sales`, item.barcode, item.itemname";
    $Unpostedfrom = "from lahead as head";
    $Unpostedjoins = "left join lastock as stock on stock.trno = head.trno
                            left join cntnum as num on num.trno = head.trno
                            left join item on item.itemid = stock.itemid
                            left join client as cust on cust.client = head.client
                            left join client as ag on ag.client = head.agent
                            left join client as dept on dept.clientid = head.deptid
                            left join itemcategory as cat on cat.line = item.category
                            left join itemsubcategory as subcat on subcat.line = item.subcat";
    $Postedfrom = "from glhead as head";
    $Postedjoins = "left join glstock as stock on stock.trno = head.trno
                          left join cntnum as num on num.trno = head.trno
                          left join item on item.itemid = stock.itemid
                          left join client as cust on cust.clientid = head.clientid
                          left join client as ag on ag.clientid = head.agentid
                          left join client as dept on dept.clientid = head.deptid
                          left join itemcategory as cat on cat.line = item.category
                          left join itemsubcategory as subcat on subcat.line = item.subcat";
    $SJdoc = "head.doc in ('SJ','MJ')";
    // $SJbref = "and num.bref in ('SJ','MJ')";
    $CMdoc = "head.doc = 'CM'";
    // $CMbref = "and num.bref = 'CM'";
    $dateAndFilter = "and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "";
    $groupby = "group by ag.client,ag.clientname,item.barcode,item.itemname";
    $qry = "";
    $report = "select $SJselect 
                      $Unpostedfrom
                      $Unpostedjoins
                      where $SJdoc
                      $dateAndFilter
                      $groupby

                    UNION ALL

                    select $SJselect
                      $Postedfrom
                      $Postedjoins
                      where $SJdoc
                      $dateAndFilter
                      $groupby
          ";
    $return = "select $CMselect 
                      $Unpostedfrom
                      $Unpostedjoins
                      where $CMdoc
                      $dateAndFilter
                      $groupby

                    UNION ALL

                    select $CMselect
                      $Postedfrom
                      $Postedjoins
                      where $CMdoc
                      $dateAndFilter
                      $groupby
          ";
    switch ($posttype) {
      case '0': //posted

        $report = "select $SJselect
                      $Postedfrom
                      $Postedjoins
                      where $SJdoc
                      $dateAndFilter
                      $groupby";
        $return = "  select $CMselect
                      $Postedfrom
                      $Postedjoins
                      where $CMdoc
                      $dateAndFilter
                      $groupby";
        break;
      case '1': //unposted
        $report = "select $SJselect 
                      $Unpostedfrom
                      $Unpostedjoins
                      where $SJdoc
                      $dateAndFilter
                      $groupby";
        $return = "select $CMselect 
                      $Unpostedfrom
                      $Unpostedjoins
                      where $CMdoc
                      $dateAndFilter
                      $groupby";

        break;
    }
    switch ($type) {
      case 'report':
        $qry = "$report
              ";
        break;
      case 'return':
        $qry = "$return
              ";
        break;
      case 'lessreturn':
        $qry = "$report

                      UNION ALL
                      
                      $return";
        break;
    }

    $qryset = "set @row_num = 0";
    $this->coreFunctions->execqry($qryset);
    $query = "select @row_num := @row_num + 1 as rank,sales, barcode, itemname, qty
            from (
              select sum(sales) as sales, barcode, itemname, sum(qty) as qty 
              from (
                $qry
              )  as tbl
            group by barcode,itemname order by sales desc
            )
            as tbl2 where sales <> 0";

    return $this->coreFunctions->opentable($query);
  }

  // FUNTION FOR QUERY PER COMPANY
  public function reportDefault_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];

    $filter = "";
    $filter1 = "";
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter = $filter . " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter = $filter . " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($agent != "") {
      $agentid = $config['params']['dataparams']['agentid'];
      $filter .= " and agent.clientid=" . $agentid;
    }
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    } else {
      $filter1 .= "";
    }

    switch ($typeofreport) {
      case 'report': {
          $query = "
          select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, 
          agent.clientname as agent, sum(stock.ext) as amount,
          cat.name as category, subcat.name as subcatname
          from glhead as head 
          left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc in ('sj','mj','sd','se','sf')
          and date(head.dateid) between '$start' and '$end' 
          $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, 
          client.clientname, agent.client, agent.clientname,
          cat.name, subcat.name
          order by $sortby";
          break;
        } // case report
      case 'lessreturn': {
          $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
          sum(case when head.doc in ('sj','mj') then (stock.ext) else (stock.ext)*-1 end) as amount,
          cat.name as category, subcat.name as subcatname         
          from glhead as head left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno          
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc in ('sj','mj','sd','se','sf','cm')
          and date(head.dateid) between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0          
          group by head.dateid, head.docno, client.client, 
          client.clientname, agent.client, agent.clientname,head.doc,
          cat.name, subcat.name
          order by $sortby";
          break;
        } // case lessreturn
      case 'return': {
          $query = "
          select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
          sum(stock.ext) as amount,
          cat.name as category, subcat.name as subcatname
          from glhead as head 
          left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join client as agent on agent.clientid=head.agentid
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc='CM' 
          and date(head.dateid) between '$start' and '$end' 
          $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname,
          cat.name, subcat.name
          order by $sortby";
          break;
        } // case return
        break;
    }

    return $query;
  }

  // FUNTION FOR QUERY PER COMPANY
  public function reportDefault_UNPOSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];

    $filter = "";
    $filter1 = "";
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter = $filter . " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter = $filter . " and item.subcat='$subcat'";
    }
    if ($client != "") {
      $clientid = $config['params']['dataparams']['clientid'];
      $filter .= " and client.clientid=" . $clientid;
    }
    if ($agent != "") {
      $agentid = $config['params']['dataparams']['agentid'];
      $filter .= " and agent.clientid=" . $agentid;
    }
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      $dept = $config['params']['dataparams']['ddeptname'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter1 .= " and stock.projectid=" . $projectid;
      }
      if ($dept != "") {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid=" . $deptid;
      }
    } else {
      $filter1 .= "";
    }

    switch ($typeofreport) {
      case 'report': {
          $query = "
          select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
          head.yourref, sum(stock.ext) as amount,
          cat.name as category, subcat.name as subcatname
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join client as agent on agent.client=head.agent
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$start' and '$end' 
          $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, 
          client.clientname, agent.client, agent.clientname, head.yourref, cat.name, subcat.name
          order by $sortby";
          break;
        }
      case 'lessreturn': {
          $query = "
          select head.doc,'sales less return' as type, 'u' as tr, 
          date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, 
          agent.client as agcode, agent.clientname as agent,
          sum(case when head.doc in ('sj','mj') then stock.ext else (stock.ext*-1) end) as amount,
          cat.name as category, subcat.name as subcatname
          from lahead as head left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join client as agent on agent.client=head.agent
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc in ('sj','mj','sd','se','sf','cm') 
          and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, 
          client.clientname, agent.client, agent.clientname,head.doc, cat.name, subcat.name
          order by $sortby";
          break;
        }
      case 'return': {
          $query = "
          select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
          client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
          sum(stock.ext) as amount,
          cat.name as category, subcat.name as subcatname
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join client as agent on agent.client=head.agent
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          left join itemcategory as cat on cat.line = item.category
          left join itemsubcategory as subcat on subcat.line = item.subcat
          where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
          group by head.dateid, head.docno, client.client, client.clientname, agent.client,
          agent.clientname, cat.name, subcat.name
          order by $sortby";
          break;
        }
    }

    return $query;
  }

  private function MAJESTY_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];
    $filters = $config['params']['dataparams'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('TOP PERFORMING ITEM', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    if ($filters['clientname'] != '') {
      $str .= $this->reporter->col('<B>Customer: </B>' . $filters['clientname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Customer: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    if ($filters['dagentname'] != '') {
      $str .= $this->reporter->col('<B>Agent: </B>' . $filters['dagentname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Agent: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    if ($filters['center'] != '') {
      $str .= $this->reporter->col('<B>Center: </B>' . $filters['center'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Center: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }



    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    if ($config['params']['companyid'] == 15) { //nathina
      if ($filters['itemname'] != '') {
        $str .= $this->reporter->col('<B>Item: </B>' . $filters['itemname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Item: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['partname'] != '') {
        $str .= $this->reporter->col('<B>Part: </B>' . $filters['partname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Part: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['modelname'] != '') {
        $str .= $this->reporter->col('<B>Model: </B>' . $filters['modelname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Model: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($filters['classic'] != '') {
        $str .= $this->reporter->col('<B>Class: </B>' . $filters['classic'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Class: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['brandname'] != '') {
        $str .= $this->reporter->col('<B>Brand: </B>' . $filters['brandname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Brand: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      if ($filters['categoryname'] != '') {
        $str .= $this->reporter->col('<B>Category: </B>' . $filters['categoryname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Category: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['salestype'] != '') {
        $str .= $this->reporter->col('<B>Transaction Type: </B>' . $filters['salestype'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Transaction Type: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }
    }

    if ($companyid == 10 || $companyid == 15) { //afti, nathina
      if ($filters['deptname'] != '') {
        $str .= $this->reporter->col('<B>Department: </B>' . $filters['deptname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Department: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }
    }

    switch ($filters['typeofreport']) {
      case 'report':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> SALES', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 'lessreturn':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> SALES NET OF RETURN', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 'return':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> RETURNS', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RANKING', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEMNAME', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VALUE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function MAJESTY_Layout_REPORT($config)
  {
    $result = $this->reportDefault($config);

    // $count = 34;
    // $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);

    $totalqty = 0;
    $totalsales = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->rank, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalqty += $data->qty;
      $totalsales += $data->sales;
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsales, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }


  // HEADER OF REPORT LAYOUT
  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $filters = $config['params']['dataparams'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('TOP PERFORMING ITEM', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    if ($filters['clientname'] != '') {
      $str .= $this->reporter->col('<B>Customer: </B>' . $filters['clientname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Customer: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    if ($filters['dagentname'] != '') {
      $str .= $this->reporter->col('<B>Agent: </B>' . $filters['dagentname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Agent: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    if ($filters['center'] != '') {
      $str .= $this->reporter->col('<B>Center: </B>' . $filters['center'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<B>Center: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

    if ($config['params']['companyid'] == 15) { //nathina
      if ($filters['itemname'] != '') {
        $str .= $this->reporter->col('<B>Item: </B>' . $filters['itemname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Item: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['partname'] != '') {
        $str .= $this->reporter->col('<B>Part: </B>' . $filters['partname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Part: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['modelname'] != '') {
        $str .= $this->reporter->col('<B>Model: </B>' . $filters['modelname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Model: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }



      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      if ($filters['classic'] != '') {
        $str .= $this->reporter->col('<B>Class: </B>' . $filters['classic'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Class: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['brandname'] != '') {
        $str .= $this->reporter->col('<B>Brand: </B>' . $filters['brandname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Brand: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      if ($filters['categoryname'] != '') {
        $str .= $this->reporter->col('<B>Category: </B>' . $filters['categoryname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Category: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }

      if ($filters['salestype'] != '') {
        $str .= $this->reporter->col('<B>Transaction Type: </B>' . $filters['salestype'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Transaction Type: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }
    }

    if ($companyid == 10 || $companyid == 15) { //afti, nathina
      if ($filters['deptname'] != '') {
        $str .= $this->reporter->col('<B>Department: </B>' . $filters['deptname'], null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('<B>Department: </B>ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      }
    }

    switch ($filters['typeofreport']) {
      case 'report':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> SALES', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 'lessreturn':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> SALES NET OF RETURN', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
      case 'return':
        $str .= $this->reporter->col('<B>TYPE OF REPORT: </B> RETURNS', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
        break;
    }

    if ($categoryname == '') {
      $str .= $this->reporter->col('<b>Category :</b> ALL', null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('<b>Category : </b>' . $categoryname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    if ($subcatname == '') {
      $str .= $this->reporter->col('<b>Sub-Category:</b> ALL',  null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('<b>Sub-Category :</b> ' . $subcatname, null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    if ($config['params']['companyid'] == 27 || $config['params']['companyid'] == 36) { //nte, rozlab
      $str .= $this->reporter->col('<b>Class: </b> ' . ($filters['classic'] != '' ? $filters['classic'] : ''), null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RANKING', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEMNAME', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VALUE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  // LAYOUT OF REPORT
  // BUT NOW THIS IS FOR DEFAULT
  public function reportDefaultLayout_REPORT($config)
  {
    $result = $this->reportDefault($config);

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $totalqty = 0;
    $totalsales = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->rank, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->sales, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalqty += $data->qty;
      $totalsales += $data->sales;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalsales, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function reportDefaultLayout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $clientname   = $config['params']['dataparams']['clientname'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $typeofreport = $config['params']['dataparams']['typeofreport'];
    // $sortby       = $config['params']['dataparams']['sortby'];
    // $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $clientname   = $config['params']['dataparams']['clientname'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $typeofreport = $config['params']['dataparams']['typeofreport'];
    // $sortby       = $config['params']['dataparams']['sortby'];
    // $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class
